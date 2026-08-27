<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public function create()
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$this->canManageOrders($currentUser)) {
            return redirect()->route('crm.orders.index')->with('error', 'Unauthorized to create orders.');
        }

        return view('crm.orders.create');
    }

    public function store(Request $request)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$this->canManageOrders($currentUser)) {
            return redirect()->route('crm.orders.index')->with('error', 'Unauthorized to create orders.');
        }

        $validated = $request->validate([
            'client_name'      => 'required|string|max:255',
            'client_email'     => 'required|email|max:255',
            'client_phone'     => 'nullable|string|max:50',
            'customer_trn'     => 'nullable|string|max:100',
            'company_trn'      => 'nullable|string|max:100',
            'trade_license_number' => 'nullable|string|max:100',
            'order_invoice_number' => 'nullable|string|max:100',
            'length'           => 'nullable|string|max:50',
            'width'            => 'nullable|string|max:50',
            'height'           => 'nullable|string|max:50',
            'unit'             => 'nullable|in:inch,mm,cm',
            'stock'            => 'nullable|string|max:100',
            'color'            => 'nullable|string|max:100',
            'coating'          => 'nullable|string|max:100',
            'products'                 => 'required|array|min:1|max:50',
            'products.*.name'          => 'required|string|max:255',
            'products.*.quantity'      => 'required|integer|min:1',
            'products.*.unit_price'    => 'required|numeric|min:0',
            'payment_status'   => 'required|in:Paid,Unpaid',
            'invoice_currency' => 'required|in:AED,USD,GBP,EUR',
            'vat_percentage'   => 'required|numeric|min:0|max:100',
            'order_date'       => 'required|date|before_or_equal:today',
            'billing_address'  => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'order_notes'      => 'nullable|string',
        ]);

        $orderDate = Carbon::parse($validated['order_date'])->setTimeFrom(now());

        // Build line items; the CrmEmail keeps aggregate figures for legacy displays.
        $products = array_values(array_map(function ($p) {
            $qty = (int) ($p['quantity'] ?? 0);
            $unit = (float) ($p['unit_price'] ?? 0);
            return [
                'product_name' => trim((string) ($p['name'] ?? '')),
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => round($qty * $unit, 2),
            ];
        }, $validated['products']));
        $subtotal = array_sum(array_column($products, 'line_total'));
        $totalQty = array_sum(array_column($products, 'quantity'));
        $firstName = $products[0]['product_name'];
        $legacyUnitPrice = $totalQty > 0 ? round($subtotal / $totalQty, 4) : 0;

        $order = DB::transaction(function () use ($validated, $currentUser, $orderDate, $products, $subtotal, $totalQty, $firstName, $legacyUnitPrice) {
            unset($validated['order_date'], $validated['products']);

            $order = new CrmEmail(array_merge($validated, [
                'source'          => 'manual_offline_order',
                'subject'         => 'Offline order - ' . $firstName,
                'message'         => 'Manually recorded after being completed offline.',
                'product_name'    => count($products) > 1 ? $firstName . ' + ' . (count($products) - 1) . ' more' : $firstName,
                'order_quantity'  => $totalQty,
                'order_price'     => $legacyUnitPrice,
                'quantity'        => $totalQty,
                'status'          => 'Order Done',
                'is_spam'         => false,
                'order_marked_at' => $orderDate,
                'order_marked_by' => $currentUser->name,
                'assigned_to'     => $currentUser->id,
                'assigned_by'     => $currentUser->id,
                'assigned_at'     => now(),
            ]));
            $order->created_at = $orderDate;
            $order->save();

            foreach ($products as $item) {
                $order->orderItems()->create($item);
            }

            DB::table('crm_assignment_logs')->insert([
                'crm_email_id' => $order->id,
                'assigned_by'  => $currentUser->id,
                'assigned_to'  => $currentUser->id,
                'note'         => 'Auto-assigned upon manual offline order creation.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            \App\CrmStatusLog::create([
                'crm_email_id' => $order->id,
                'user_name'    => $currentUser->name,
                'old_status'   => 'Created Offline',
                'new_status'   => 'Order Done',
            ]);

            return $order;
        });

        return redirect()->route('crm.orders.invoice', $order->id)
            ->with('success', 'Offline order created successfully.');
    }

    /**
     * Admin-only orders list with date range filter
     */
    /**
     * Orders list with date range filter (accessible by Admin, Manager, and Sales Agent)
     */
    public function index(Request $request)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        // Accounts (accountant) sees the full invoice list read-only, like admin/sales manager.
        $isRestricted = !$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isAccounts();

        $query = CrmEmail::where(function($q) {
                             $q->where(function ($manualOrder) {
                                 $manualOrder->where('status', 'Order Done')
                                     ->whereDoesntHave('salesOrder');
                             })->orWhereHas('salesOrder', function ($salesOrder) {
                                 $salesOrder->whereIn('payment_status', ['received', 'approved']);
                             });
                         })
                         ->where('is_spam', false)
                         // Newest first; fall back to created_at when the order was never marked.
                         ->orderByRaw('COALESCE(order_marked_at, created_at) DESC')
                         ->orderBy('id', 'desc');

        // Sales Agents can ONLY see orders assigned to them or marked by them
        if ($isRestricted) {
            $query->where(function($q) use ($currentUser) {
                $q->where('assigned_to', $currentUser->id)
                  ->orWhere('order_marked_by', $currentUser->name);
            });
        }

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('order_marked_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        // Agent filter - restrict to admin/manager
        if (!$isRestricted && $request->filled('agent')) {
            $query->where('order_marked_by', $request->agent);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('client_email', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->appends($request->all());

        // Agent list for dropdown filter — cached 5 min (agent names rarely change).
        if ($isRestricted) {
            $agents = collect([$currentUser->name]);
        } else {
            $agents = \Illuminate\Support\Facades\Cache::remember(
                'crm:orders:agents:'.session('crm_workspace_id'),
                300,
                function () {
                    return CrmEmail::where(function ($q) {
                            $q->where('status', 'Order Done')->orWhereHas('salesOrder');
                        })
                        ->whereNotNull('order_marked_by')
                        ->distinct()
                        ->pluck('order_marked_by');
                }
            );
        }

        // Summary stats — merged into ONE aggregate query (was 4 separate queries).
        $statsQuery = CrmEmail::where(function ($q) {
                $q->where('status', 'Order Done')->orWhereHas('salesOrder');
            })->where('is_spam', false);
        if ($isRestricted) {
            $statsQuery->where(function ($q) use ($currentUser) {
                $q->where('assigned_to', $currentUser->id)->orWhere('order_marked_by', $currentUser->name);
            });
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $statsQuery->whereBetween('order_marked_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }
        if (!$isRestricted && $request->filled('agent')) {
            $statsQuery->where('order_marked_by', $request->agent);
        }

        $agg = $statsQuery->selectRaw("
                COUNT(*) as total_orders,
                COALESCE(SUM(order_price * order_quantity), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN LOWER(COALESCE(payment_status,'')) NOT IN ('paid','received','approved')
                                  THEN order_price * order_quantity ELSE 0 END), 0) as unpaid_total,
                SUM(CASE WHEN LOWER(COALESCE(payment_status,'')) NOT IN ('paid','received','approved') THEN 1 ELSE 0 END) as unpaid_count
            ")->first();

        $totalOrders   = (int)   $agg->total_orders;
        $totalRevenue  = (float) $agg->total_revenue;
        $unpaidTotal   = (float) $agg->unpaid_total;
        $unpaidCount   = (int)   $agg->unpaid_count;
        $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;

        return view('crm.orders.index', compact(
            'orders', 'agents', 'totalOrders', 'totalRevenue', 'avgOrderValue', 'unpaidTotal', 'unpaidCount'
        ));
    }

    /**
     * Export the filtered invoice list to Excel or PDF.
     */
    public function export(Request $request)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }
        $isRestricted = !$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isAccounts();

        $query = CrmEmail::where(function ($q) {
                $q->where(function ($manualOrder) {
                    $manualOrder->where('status', 'Order Done')->whereDoesntHave('salesOrder');
                })->orWhereHas('salesOrder', function ($salesOrder) {
                    $salesOrder->whereIn('payment_status', ['received', 'approved']);
                });
            })
            ->where('is_spam', false)
            ->orderByRaw('COALESCE(order_marked_at, created_at) DESC')
            ->orderBy('id', 'desc');

        if ($isRestricted) {
            $query->where(function ($q) use ($currentUser) {
                $q->where('assigned_to', $currentUser->id)->orWhere('order_marked_by', $currentUser->name);
            });
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('order_marked_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }
        if (!$isRestricted && $request->filled('agent')) {
            $query->where('order_marked_by', $request->agent);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->get();
        if ($orders->isEmpty()) {
            return redirect()->route('crm.orders.index', $request->except('format'))
                ->with('error', 'No invoices match the selected filters.');
        }

        $workspace = $request->attributes->get('crm_workspace');
        $meta = [
            'workspaceName' => optional($workspace)->name ?: 'CRM',
            'isAlMassa' => optional($workspace)->slug === 'mybox-packaging-app',
            'generatedAt' => now()->format('d M Y, h:i A'),
            'count' => $orders->count(),
            'total' => (float) $orders->sum(function ($o) {
                return (float) ($o->order_price ?? 0) * (float) ($o->order_quantity ?? 0);
            }),
        ];

        $filename = 'invoices-' . now()->format('Y-m-d');
        if ($request->input('format') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.orders.export_pdf', compact('orders', 'meta'))
                ->setPaper('a4', 'landscape');
            return $pdf->download($filename . '.pdf');
        }
        return response()->view('crm.orders.export_excel', compact('orders', 'meta'))
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.xls"');
    }

    /**
     * Bulk printable invoice for all orders (with optional filters)
     */
    public function bulkInvoice(Request $request)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSalesManager())) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $query = CrmEmail::where(function($q) {
                             $q->where('status', 'Order Done')
                               ->orWhereHas('salesOrder');
                         })
                         ->where('is_spam', false)
                         ->orderBy('order_marked_at', 'asc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('order_marked_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        if ($request->filled('agent')) {
            $query->where('order_marked_by', $request->agent);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('client_name',  'like', "%{$s}%")
                  ->orWhere('client_email', 'like', "%{$s}%")
                  ->orWhere('product_name', 'like', "%{$s}%");
            });
        }

        $orders       = $query->get();
        $totalRevenue = $orders->sum(fn($o) => ($o->order_price ?? 0) * ($o->order_quantity ?? 0));
        $startDate    = $request->start_date;
        $endDate      = $request->end_date;

        return view('crm.orders.bulk_invoice', compact('orders', 'totalRevenue', 'startDate', 'endDate'));
    }

    /**
     * Print-friendly invoice for a single order
     */
    public function invoice($id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $order = CrmEmail::findOrFail($id);

        if ($order->status !== 'Order Done' && !$order->salesOrder) {
            return redirect()->back()->with('error', 'This lead is not an order yet.');
        }

        // Sales agents and other restricted roles can only see invoices assigned to them or marked by them.
        // Accounts (accountant) may view every invoice read-only.
        $isRestricted = !$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isAccounts();
        if ($isRestricted && $order->assigned_to != $currentUser->id && $order->order_marked_by !== $currentUser->name) {
            return redirect()->back()->with('error', 'Unauthorized to view this invoice.');
        }

        $isAlMassa = $order->workspace && $order->workspace->slug === 'mybox-packaging-app';

        return view($isAlMassa ? 'crm.orders.invoice_al_massa' : 'crm.orders.invoice', compact('order'));
    }

    /**
     * Show form to edit an invoice
     */
    public function editInvoice($id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $order = CrmEmail::findOrFail($id);

        if ($order->status !== 'Order Done' && !$order->salesOrder) {
            return redirect()->back()->with('error', 'This lead is not an order yet.');
        }

        // Only Admin, Sales Manager, and Sales Agent (who owns/marked the order) can edit the invoice
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isAccounts()) {
            if (!$currentUser->isSales() || ($order->assigned_to != $currentUser->id && $order->order_marked_by !== $currentUser->name)) {
                return redirect()->back()->with('error', 'Unauthorized to edit this invoice.');
            }
        }

        return view('crm.orders.edit_invoice', compact('order'));
    }

    /**
     * Update invoice details in the database
     */
    public function updateInvoice(Request $request, $id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $order = CrmEmail::findOrFail($id);

        if ($order->status !== 'Order Done' && !$order->salesOrder) {
            return redirect()->back()->with('error', 'This lead is not an order yet.');
        }

        // Admin, Sales Manager, Accounts, and the owning Sales Agent can update the invoice
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isAccounts()) {
            if (!$currentUser->isSales() || ($order->assigned_to != $currentUser->id && $order->order_marked_by !== $currentUser->name)) {
                return redirect()->back()->with('error', 'Unauthorized to update this invoice.');
            }
        }

        $validated = $request->validate([
            'client_name'     => 'required|string|max:255',
            'client_email'    => 'required|email|max:255',
            'client_phone'    => 'nullable|string|max:50',
            'customer_trn'    => 'nullable|string|max:100',
            'company_trn'     => 'nullable|string|max:100',
            'trade_license_number' => 'nullable|string|max:100',
            'order_invoice_number' => 'nullable|string|max:100',
            'length'          => 'nullable|string|max:50',
            'width'           => 'nullable|string|max:50',
            'height'          => 'nullable|string|max:50',
            'unit'            => 'nullable|string|max:20',
            'stock'           => 'nullable|string|max:100',
            'color'           => 'nullable|string|max:100',
            'coating'         => 'nullable|string|max:100',
            'products'                 => 'required|array|min:1|max:50',
            'products.*.name'          => 'required|string|max:255',
            'products.*.quantity'      => 'required|integer|min:1',
            'products.*.unit_price'    => 'required|numeric|min:0',
            'payment_status'  => 'required|in:Paid,Unpaid',
            'invoice_currency'=> 'required|in:AED,USD,GBP,EUR',
            'vat_percentage'  => 'required|numeric|min:0|max:100',
            'order_date'      => 'nullable|date|before_or_equal:today',
            'billing_address' => 'nullable|string',
            'shipping_address'=> 'nullable|string',
            'order_notes'     => 'nullable|string',
        ]);

        // Rebuild line items and recompute aggregates (mirrors store()).
        $products = array_values(array_map(function ($p) {
            $qty = (int) ($p['quantity'] ?? 0);
            $unit = (float) ($p['unit_price'] ?? 0);
            return [
                'product_name' => trim((string) ($p['name'] ?? '')),
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => round($qty * $unit, 2),
            ];
        }, $validated['products']));
        $subtotal = array_sum(array_column($products, 'line_total'));
        $totalQty = array_sum(array_column($products, 'quantity'));
        $firstName = $products[0]['product_name'];
        $legacyUnitPrice = $totalQty > 0 ? round($subtotal / $totalQty, 4) : 0;

        DB::transaction(function () use ($order, $request, $products, $subtotal, $totalQty, $firstName, $legacyUnitPrice) {
            $update = [
                'client_name'      => $request->client_name,
                'client_email'     => $request->client_email,
                'client_phone'     => $request->client_phone,
                'customer_trn'     => $request->customer_trn,
                'company_trn'      => $request->company_trn,
                'trade_license_number' => $request->trade_license_number,
                'order_invoice_number' => $request->order_invoice_number,
                'product_name'     => count($products) > 1 ? $firstName . ' + ' . (count($products) - 1) . ' more' : $firstName,
                'length'           => $request->length,
                'width'            => $request->width,
                'height'           => $request->height,
                'unit'             => $request->unit,
                'stock'            => $request->stock,
                'color'            => $request->color,
                'coating'          => $request->coating,
                'order_quantity'   => $totalQty,
                'order_price'      => $legacyUnitPrice,
                'quantity'         => $totalQty,
                'payment_status'   => $request->payment_status,
                'invoice_currency' => $request->invoice_currency,
                'vat_percentage'   => $request->vat_percentage,
                'billing_address'  => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'order_notes'      => $request->order_notes,
            ];
            if ($request->filled('order_date')) {
                $update['order_marked_at'] = Carbon::parse($request->order_date)->setTimeFrom($order->order_marked_at ?: now());
            }
            $order->update($update);

            // Replace line items with the edited set.
            $order->orderItems()->delete();
            foreach ($products as $item) {
                $order->orderItems()->create($item);
            }
        });

        if ($order->salesOrder) {
            $salesOrderPaymentStatus = $request->payment_status === 'Paid'
                ? ($order->salesOrder->payment_term === 'credit' ? 'approved' : 'received')
                : 'pending';

            $salesOrderStatus = $order->salesOrder->status;
            if ($request->payment_status === 'Paid' && $salesOrderStatus === 'pending_payment') {
                $salesOrderStatus = 'pending_artwork';
            }

            $order->salesOrder->update([
                'payment_status' => $salesOrderPaymentStatus,
                'status' => $salesOrderStatus
            ]);
        }

        return redirect()->route('crm.orders.invoice', $order->id)->with('success', 'Invoice details updated successfully.');
    }

    /**
     * Send invoice email to the client, CC support & admins
     */
    public function sendInvoice($id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $order = CrmEmail::findOrFail($id);

        if ($order->status !== 'Order Done' && !$order->salesOrder) {
            return redirect()->back()->with('error', 'This lead is not an order yet.');
        }

        // Admin, Sales Manager, Accounts, and the owning Sales Agent can send the invoice
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isAccounts()) {
            if (!$currentUser->isSales() || ($order->assigned_to != $currentUser->id && $order->order_marked_by !== $currentUser->name)) {
                return redirect()->back()->with('error', 'Unauthorized to send this invoice.');
            }
        }

        try {
            // NOTE (L10 upgrade): dynamic per-user SMTP is disabled.
            // config is cached in prod, so env() returns NULL at runtime — overriding the
            // mailer with env() would blank the SMTP username and break auth (554/553).
            // The default cached SMTP mailer already holds support@ creds; FROM is set
            // explicitly inside SendInvoiceMail via config('mail.from.*').

            // Get admins to CC
            $adminEmails = \App\CrmUser::inWorkspace(null, ['admin'])->pluck('email')->toArray();
            $ccEmails = array_unique(array_filter(array_merge($adminEmails, ['support@myboxprinting.com'])));

            \Illuminate\Support\Facades\Mail::to($order->client_email)
                ->cc($ccEmails)
                ->send(new \App\Mail\SendInvoiceMail($order, $currentUser));

            return redirect()->back()->with('success', 'Invoice #' . str_pad($order->id, 5, '0', STR_PAD_LEFT) . ' has been successfully sent to ' . $order->client_email);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send invoice: ' . $e->getMessage());
        }
    }

    /**
     * Delete an invoice (manual order). Admin, super_admin and accounts only; logged.
     */
    public function destroyInvoice($id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSuperAdmin() && !$currentUser->isAccounts())) {
            return redirect()->back()->with('error', 'You do not have permission to delete invoices.');
        }
        $order = CrmEmail::findOrFail($id);
        if ($order->status !== 'Order Done' && !$order->salesOrder) {
            return redirect()->back()->with('error', 'This lead is not an order yet.');
        }

        $label = ($order->order_invoice_number ?: '#'.str_pad($order->id, 5, '0', STR_PAD_LEFT))
            . ' — ' . ($order->client_name ?: 'Client');
        $snapshot = [
            'invoice_number' => $order->order_invoice_number,
            'client_name'    => $order->client_name,
            'client_email'   => $order->client_email,
            'order_price'    => $order->order_price,
            'order_quantity' => $order->order_quantity,
            'total'          => (float) ($order->order_price * $order->order_quantity),
            'currency'       => $order->invoice_currency,
            'payment_status' => $order->payment_status,
            'order_date'     => optional($order->order_marked_at)->format('Y-m-d'),
        ];
        \App\CrmDeletionLog::record('invoice', $order, $label, $snapshot);

        // Remove line items first, then the order itself.
        if (method_exists($order, 'orderItems')) {
            $order->orderItems()->delete();
        }
        $order->delete();

        return redirect()->route('crm.orders.index')->with('success', 'Invoice deleted (logged).');
    }

    private function canManageOrders($user)
    {
        // Accounts (accountant) may also record invoices; their name shows as the agent.
        return $user && ($user->isAdmin() || $user->isSalesManager() || $user->isSales() || $user->isAccounts());
    }
}
