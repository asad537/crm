<?php

namespace App\Http\Controllers\Crm;

use App\CrmCustomer;
use App\CrmEmail;
use App\CustomerSale;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerSaleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $user = \Auth::guard('crm')->user();
        $canSeeIncome = $user->isAdmin() || $user->isSuperAdmin() || $user->isAccounts() || $user->isSalesManager();
        $canEditIncome = $user->isAdmin() || $user->isSuperAdmin() || $user->isAccounts();
        app(\App\Services\IncomeDueService::class)->ensureAll(); // this month's rent dues exist before we show anything

        $customers = CrmCustomer::withCount('sales')
            ->with(['sales:id,customer_id,total_amount,paid_amount,balance_amount', 'incomeEntries'])
            ->when(!$canSeeIncome, fn ($q) => $q->where('customer_type', '!=', 'income'))
            ->orderBy('name')
            ->get();

        $selectedCustomer = $request->filled('customer_id')
            ? CrmCustomer::findOrFail($request->customer_id)
            : null;
        if ($selectedCustomer && $selectedCustomer->isIncomeCustomer() && !$canSeeIncome) abort(403);
        if ($request->query('view') === 'income' && !$canSeeIncome) abort(403);
        $incomeEntries = collect();
        $incomeSummary = null;
        if ($selectedCustomer && $canSeeIncome) {
            $incomeEntries = $selectedCustomer->incomeEntries()->get();
            $incomeSummary = [
                'due' => (float) $incomeEntries->sum('amount_due'),
                'paid' => (float) $incomeEntries->sum('amount_paid'),
                'balance' => (float) $incomeEntries->sum(fn ($e) => $e->balance),
                'overdue' => $incomeEntries->filter(fn ($e) => $e->status === 'overdue')->count(),
            ];
        }
        $showIncome = $selectedCustomer && ($selectedCustomer->isIncomeCustomer() || ($request->query('view') === 'income' && $incomeEntries->isNotEmpty()));
        $hasBoth = $selectedCustomer && $incomeEntries->isNotEmpty()
            && CustomerSale::where('customer_id', $selectedCustomer->id)->exists();
        $directoryOrderCount = $customers->sum('sales_count');
        $directorySalesTotal = $customers->sum(function ($customer) {
            return $customer->sales->sum('total_amount');
        });
        $directorySummary = [
            'customers' => $customers->count(),
            'orders' => $directoryOrderCount,
            'average_order' => $directoryOrderCount > 0 ? $directorySalesTotal / $directoryOrderCount : 0,
        ];

        $sales = collect();
        $summary = ['total' => 0, 'paid' => 0, 'balance' => 0, 'credit' => 0, 'open_count' => 0];

        if ($selectedCustomer) {
            $query = CustomerSale::where('customer_id', $selectedCustomer->id)
                ->orderBy('order_date', 'desc')->orderBy('id', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('item_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
            if ($request->filled('payment_status')) $query->where('payment_status', $request->payment_status);
            if ($request->filled('order_status')) $query->where('order_status', $request->order_status);

            $sales = $query->paginate(20)->appends($request->all());
            $base = CustomerSale::where('customer_id', $selectedCustomer->id);
            $summary = [
                'total' => (clone $base)->sum('total_amount'),
                'paid' => (clone $base)->sum('paid_amount'),
                'balance' => (clone $base)->sum('balance_amount'),
                'credit' => (clone $base)->get(['total_amount', 'paid_amount'])
                    ->sum(fn ($sale) => max(0, round((float) $sale->paid_amount - (float) $sale->total_amount, 2))),
                'open_count' => (clone $base)->whereNotIn('order_status', ['Completed', 'Cancelled'])->count(),
            ];
        }

        return view('crm.customer_sales.index', compact('customers', 'selectedCustomer', 'sales', 'summary', 'directorySummary', 'incomeEntries', 'incomeSummary', 'canEditIncome', 'canSeeIncome', 'showIncome', 'hasBoth'));
    }

    public function storeCustomer(Request $request)
    {
        $this->authorizeAccess();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:60',
            'email' => 'nullable|email|max:255',
            'country' => 'required|string|max:100',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:100',
            'currency' => 'required|string|max:10',
            'notes' => 'nullable|string',
            'customer_type' => 'nullable|in:sales,income',
            'income_head' => 'nullable|string|max:80',
            'recurring_amount' => 'nullable|numeric|min:0',
            'recurring_day' => 'nullable|integer|min:1|max:31',
            'recurring_start' => 'nullable|date_format:Y-m',
        ]);
        $user = \Auth::guard('crm')->user();
        if (($data['customer_type'] ?? 'sales') === 'income') {
            if (!$user->isAdmin() && !$user->isSuperAdmin() && !$user->isAccounts()) {
                abort(403, 'Only Accounts / Admin can add other-income customers.');
            }
            $request->validate(['income_head' => 'required', 'recurring_amount' => 'required|numeric|min:0.01', 'recurring_day' => 'required', 'recurring_start' => 'required']);
            $data['recurring_start'] = $data['recurring_start'] . '-01';
            $data['recurring_active'] = true;
        } else {
            $data['customer_type'] = 'sales';
            unset($data['income_head'], $data['recurring_amount'], $data['recurring_day'], $data['recurring_start']);
        }
        $customer = CrmCustomer::create($data);
        if ($customer->isIncomeCustomer()) {
            app(\App\Services\IncomeDueService::class)->ensureDues($customer);
            return redirect()->route('crm.customer_sales.index', ['customer_id' => $customer->id])
                ->with('success', 'Income customer added. Monthly dues are listed below.');
        }

        return redirect()->route('crm.customer_sales.index', ['customer_id' => $customer->id])
            ->with('success', 'Customer added successfully. You can now add a sale.');
    }

    public function destroyCustomer($id)
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager() && !$user->isAccounts())) {
            abort(403);
        }

        return DB::transaction(function () use ($id) {
            $customer = CrmCustomer::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($customer->sales()->exists()) {
                return redirect()->route('crm.customer_sales.index')
                    ->with('error', 'Customer has sales records and cannot be deleted.');
            }
            if ($customer->incomeEntries()->where('amount_paid', '>', 0)->exists()) {
                return redirect()->route('crm.customer_sales.index')
                    ->with('error', 'Customer has recorded income payments and cannot be deleted. Deactivate the schedule instead.');
            }
            $customer->incomeEntries()->delete();

            $email = strtolower(trim((string) $customer->email));
            if ($email !== '' && CrmEmail::where('workspace_id', $customer->workspace_id)
                ->whereRaw('LOWER(client_email) = ?', [$email])
                ->where(function ($query) {
                    $query->where('status', 'Order Done')->orWhereHas('salesOrder');
                })->exists()) {
                return redirect()->route('crm.customer_sales.index')
                    ->with('error', 'Customer has a CRM order and cannot be deleted.');
            }

            $customer->delete();

            return redirect()->route('crm.customer_sales.index')
                ->with('success', 'Customer profile deleted.');
        });
    }

    public function storeSale(Request $request)
    {
        $this->authorizeAccess();
        $data = $this->validateSale($request);
        $customer = CrmCustomer::findOrFail($data['customer_id']);
        $this->calculateSale($data);
        $data['created_by'] = \Auth::guard('crm')->id();
        CustomerSale::create($data);

        return redirect()->route('crm.customer_sales.index', ['customer_id' => $customer->id])
            ->with('success', 'Customer sale added successfully.');
    }

    public function updateSale(Request $request, $id)
    {
        $this->authorizeAccess();
        $sale = CustomerSale::findOrFail($id);
        if ($sale->crm_email_id) {
            return redirect()->route('crm.orders.invoice.edit', $sale->crm_email_id)
                ->with('error', 'This sale is linked to an order. Edit its invoice instead.');
        }
        $data = $this->validateSale($request, $sale);
        $this->calculateSale($data);
        $sale->update($data);

        return redirect()->route('crm.customer_sales.index', ['customer_id' => $sale->customer_id])
            ->with('success', 'Customer sale updated successfully.');
    }

    public function updatePayment(Request $request, $id)
    {
        $this->authorizeAccess();
        $sale = CustomerSale::findOrFail($id);
        if ($sale->crm_email_id) {
            return redirect()->route('crm.orders.invoice', $sale->crm_email_id)
                ->with('error', 'This sale is linked to an order. Record payments on its invoice.');
        }
        $data = $request->validate(['paid_amount' => 'required|numeric|min:0']);
        $paid = round((float) $data['paid_amount'], 2);
        $balance = max(0, round((float) $sale->total_amount - $paid, 2));
        $sale->update([
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'payment_status' => $paid <= 0 ? 'Unpaid' : ($balance > 0 ? 'Partial' : 'Paid'),
        ]);

        return redirect()->back()->with('success', 'Payment updated successfully.');
    }

    private function validateSale(Request $request, ?CustomerSale $existing = null)
    {
        return $request->validate([
            'customer_id' => 'required|integer|exists:crm_customers,id',
            'order_number' => 'required|string|max:100',
            'order_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:order_date',
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => $existing && $existing->import_source_key ? 'nullable|numeric|min:0.01' : 'required|numeric|min:0.01',
            'unit' => 'required|in:Pieces,Boxes,Sheets,Rolls,Kg,Sets,Units',
            'unit_price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:10',
            'order_status' => 'required|in:Draft,Confirmed,In Production,Ready,Dispatched,Delivered,Completed,On Hold,Cancelled',
            'payment_method' => 'nullable|in:Cash,Bank Transfer,Card,Cheque,Credit',
            'notes' => 'nullable|string',
        ]);
    }

    private function calculateSale(&$data)
    {
        $subtotal = round((float) ($data['quantity'] ?? 0) * (float) $data['unit_price'], 2);
        $discount = round((float) ($data['discount_amount'] ?? 0), 2);
        $tax = round((float) ($data['tax_amount'] ?? 0), 2);
        $shipping = round((float) ($data['shipping_cost'] ?? 0), 2);
        $total = max(0, round($subtotal - $discount + $tax + $shipping, 2));
        $paid = round((float) ($data['paid_amount'] ?? 0), 2);
        $balance = max(0, round($total - $paid, 2));
        $data['subtotal'] = $subtotal;
        $data['discount_amount'] = $discount;
        $data['tax_amount'] = $tax;
        $data['shipping_cost'] = $shipping;
        $data['total_amount'] = $total;
        $data['paid_amount'] = $paid;
        $data['balance_amount'] = $balance;
        $data['payment_status'] = $paid <= 0 ? 'Unpaid' : ($balance > 0 ? 'Partial' : 'Paid');
    }

    private function authorizeAccess()
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager() && !$user->isAccounts() && !$user->isSales())) {
            abort(403, 'Unauthorized access.');
        }
    }
}
