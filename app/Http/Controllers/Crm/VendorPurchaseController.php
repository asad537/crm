<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\VendorPurchase;
use App\Vendor;
use App\Services\LocalInvoiceOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorPurchaseController extends Controller
{
    public function extractInvoice(Request $request, LocalInvoiceOcrService $ocr)
    {
        $this->authorizeAccess();
        $request->validate([
            'invoice_document' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
        ]);

        try {
            return response()->json(['ok' => true, 'data' => $ocr->extract($request->file('invoice_document'))]);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function create(Request $request)
    {
        $this->authorizeAccess();
        $vendors = Vendor::orderBy('name')->get();
        $selectedVendorId = $request->filled('vendor_id') ? (int) $request->vendor_id : null;

        return view('crm.vendor_purchases.create', compact('vendors', 'selectedVendorId'));
    }

    public function edit($id)
    {
        $this->authorizeAccess();
        $purchase = VendorPurchase::with('items')->findOrFail($id);
        $vendors = Vendor::orderBy('name')->get();
        $selectedVendorId = $purchase->vendor_id;
        $purchaseItems = $purchase->items->map(function ($item) {
            $size = preg_split('/\s*(?:x|×|\*)\s*/i', (string) $item->size);
            return [
                'category' => $item->category,
                'item_name' => $item->item_name,
                'material' => $item->material,
                'specification' => $item->specification,
                'size_length' => $size[0] ?? '',
                'size_width' => $size[1] ?? '',
                'size_height' => $size[2] ?? '',
                'gsm' => $item->gsm,
                'color' => $item->color,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ];
        })->all();

        return view('crm.vendor_purchases.create', compact('vendors', 'selectedVendorId', 'purchase', 'purchaseItems'));
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $applyPurchaseFilters = function ($query) use ($request) {
            if ($request->filled('category')) {
                $query->where(function ($purchaseQuery) use ($request) {
                    $purchaseQuery->where('category', $request->category)
                        ->orWhereHas('items', function ($itemQuery) use ($request) {
                            $itemQuery->where('category', $request->category);
                        });
                });
            }
            if ($request->filled('payment_status')) $query->where('payment_status', $request->payment_status);
            if ($request->filled('date_from')) $query->whereDate('purchase_date', '>=', $request->date_from);
            if ($request->filled('date_to')) $query->whereDate('purchase_date', '<=', $request->date_to);
        };
        $vendorsQuery = Vendor::withCount(['purchases' => $applyPurchaseFilters])->with(['purchases' => function ($query) use ($applyPurchaseFilters) {
            $query->select('id', 'vendor_id', 'purchase_date', 'total_amount', 'paid_amount', 'balance_amount', 'payment_status');
            $applyPurchaseFilters($query);
        }]);
        if ($request->filled('expense_type')) {
            if ($request->expense_type === 'Production Expense') {
                $vendorsQuery->where(function ($query) {
                    $query->where('category', 'Production Expense')->orWhereNull('category')->orWhere('category', '');
                });
            } else {
                $vendorsQuery->where('category', $request->expense_type);
            }
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $vendorsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('purchases', function ($purchaseQuery) use ($search) {
                        $purchaseQuery->where('item_name', 'like', "%{$search}%")
                            ->orWhere('invoice_number', 'like', "%{$search}%")->orWhere('job_id', 'like', "%{$search}%")
                            ->orWhere('material', 'like', "%{$search}%")
                            ->orWhereHas('items', function ($itemQuery) use ($search) {
                                $itemQuery->where('item_name', 'like', "%{$search}%")
                                    ->orWhere('material', 'like', "%{$search}%")
                                    ->orWhere('category', 'like', "%{$search}%");
                            });
                    });
            });
        }
        if ($request->filled('category') || $request->filled('payment_status') || $request->filled('date_from') || $request->filled('date_to')) {
            $vendorsQuery->whereHas('purchases', $applyPurchaseFilters);
        }
        $vendorsQuery->orderBy('name');
        $selectedVendor = $request->filled('vendor_id') ? Vendor::findOrFail($request->vendor_id) : null;

        // Directory summary — SQL aggregation instead of loading every vendor + purchase into PHP.
        // Old code loaded the entire matching set and summed in PHP (slow for large data).
        $vendorIds = (clone $vendorsQuery)->pluck('vendors.id');
        $purchaseAgg = VendorPurchase::whereIn('vendor_id', $vendorIds)
            ->selectRaw('COUNT(*) as purchases, COALESCE(SUM(balance_amount),0) as pending, COALESCE(SUM(paid_amount),0) as paid')
            ->first();
        $directorySummary = [
            'vendors'   => $vendorIds->count(),
            'purchases' => (int)   ($purchaseAgg->purchases ?? 0),
            'pending'   => (float) ($purchaseAgg->pending   ?? 0),
            'paid'      => (float) ($purchaseAgg->paid      ?? 0),
        ];

        $vendors = $vendorsQuery->paginate(20)->appends($request->all());

        $query = VendorPurchase::with(['creator', 'items'])->orderBy('purchase_date', 'desc')->orderBy('id', 'desc');
        if ($selectedVendor) $query->where('vendor_id', $selectedVendor->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                    ->orWhere('item_name', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")->orWhere('job_id', 'like', "%{$search}%")
                    ->orWhere('material', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('item_name', 'like', "%{$search}%")
                            ->orWhere('material', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%");
                    });
            });
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('category')) {
            $query->where(function ($purchaseQuery) use ($request) {
                $purchaseQuery->where('category', $request->category)
                    ->orWhereHas('items', function ($itemQuery) use ($request) {
                        $itemQuery->where('category', $request->category);
                    });
            });
        }
        if ($request->filled('date_from')) $query->whereDate('purchase_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('purchase_date', '<=', $request->date_to);

        $purchases = $query->paginate(20)->appends($request->all());
        $summaryQuery = VendorPurchase::query(); if ($selectedVendor) $summaryQuery->where('vendor_id',$selectedVendor->id);
        $summary = ['total'=>(clone $summaryQuery)->sum('total_amount'),'paid'=>(clone $summaryQuery)->sum('paid_amount'),'balance'=>(clone $summaryQuery)->sum('balance_amount'),'unpaid_count'=>(clone $summaryQuery)->whereIn('payment_status',['Unpaid','Partial'])->count()];

        // Job-wise total: when the search term matches a Job ID, auto-sum every vendor purchase against that job.
        $jobSummary = null;
        if ($request->filled('search')) {
            $jobRows = VendorPurchase::where('job_id', trim($request->search))->get();
            if ($jobRows->isNotEmpty()) {
                $jobSummary = [
                    'job_id' => trim($request->search),
                    'count' => $jobRows->count(),
                    'vendors' => $jobRows->pluck('vendor_name')->filter()->unique()->count(),
                    'total' => (float) $jobRows->sum('total_amount'),
                    'paid' => (float) $jobRows->sum('paid_amount'),
                    'balance' => (float) $jobRows->sum('balance_amount'),
                    'currency' => $jobRows->first()->currency ?: 'AED',
                ];
            }
        }

        return view('crm.vendor_purchases.index', compact('purchases', 'summary', 'vendors', 'selectedVendor', 'directorySummary', 'jobSummary'));
    }

    /**
     * Job-wise purchase report: all jobs with their total expense, or one job's purchases.
     */
    public function jobs(Request $request)
    {
        $this->authorizeAccess();
        $search = trim((string) $request->input('search', ''));
        $selectedJob = trim((string) $request->input('job_id', ''));

        // A single job selected → show ONLY that job's purchases (across every vendor).
        if ($selectedJob !== '') {
            $rows = VendorPurchase::where('job_id', $selectedJob)->get();
            $jobSummary = [
                'job_id' => $selectedJob,
                'count' => $rows->count(),
                'vendors' => $rows->pluck('vendor_name')->filter()->unique()->count(),
                'total' => (float) $rows->sum('total_amount'),
                'paid' => (float) $rows->sum('paid_amount'),
                'balance' => (float) $rows->sum('balance_amount'),
                'currency' => optional($rows->first())->currency ?: 'AED',
            ];
            $purchases = VendorPurchase::with(['vendor', 'items'])
                ->where('job_id', $selectedJob)
                ->orderBy('purchase_date', 'desc')->orderBy('id', 'desc')
                ->paginate(20)->appends($request->all());
            return view('crm.vendor_purchases.jobs', [
                'selectedJob' => $selectedJob,
                'jobSummary' => $jobSummary,
                'purchases' => $purchases,
                'jobGroups' => null,
                'search' => $search,
            ]);
        }

        // Otherwise → every job grouped, with its running totals.
        $query = VendorPurchase::whereNotNull('job_id')->where('job_id', '!=', '');
        if ($search !== '') $query->where('job_id', 'like', "%{$search}%");
        $jobGroups = $query->get()->groupBy('job_id')->map(function ($rows, $jobId) {
            return (object) [
                'job_id' => $jobId,
                'count' => $rows->count(),
                'vendors' => $rows->pluck('vendor_name')->filter()->unique()->count(),
                'total' => (float) $rows->sum('total_amount'),
                'paid' => (float) $rows->sum('paid_amount'),
                'balance' => (float) $rows->sum('balance_amount'),
                'currency' => optional($rows->first())->currency ?: 'AED',
                'last_date' => $rows->max('purchase_date'),
            ];
        })->sortByDesc('last_date')->values();

        $overall = [
            'jobs' => $jobGroups->count(),
            'total' => (float) $jobGroups->sum('total'),
            'paid' => (float) $jobGroups->sum('paid'),
            'balance' => (float) $jobGroups->sum('balance'),
        ];

        return view('crm.vendor_purchases.jobs', [
            'selectedJob' => '',
            'jobSummary' => null,
            'purchases' => null,
            'jobGroups' => $jobGroups,
            'overall' => $overall,
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'vendor_id' => 'required|integer|exists:vendors,id',
            'vendor_phone' => 'nullable|string|max:50',
            'vendor_email' => 'nullable|email|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv|max:20480',
            'purchase_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:purchase_date',
            'invoice_number' => 'nullable|string|max:100',
            'job_id' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string|max:100',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.material' => 'nullable|string|max:255',
            'items.*.specification' => 'nullable|string|max:255',
            'items.*.size_length' => 'nullable|numeric|min:0',
            'items.*.size_width' => 'nullable|numeric|min:0',
            'items.*.size_height' => 'nullable|numeric|min:0',
            'items.*.gsm' => 'nullable|string|max:50',
            'items.*.color' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|in:Sheets,Kg,Rolls,Pieces,Boxes,Liters,Meters,Pallets,Items,Services,Meals,Trips',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.line_total' => 'required|numeric|min:0',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:Unpaid,Partial,Paid',
            'payment_method' => 'nullable|in:Cash,Bank Transfer,Card,Cheque,Credit',
            'currency' => 'required|in:USD,AED,GBP,EUR,PKR',
            'notes' => 'nullable|string',
        ]);

        $items = $this->normalizePurchaseItems($validated['items']);
        unset($validated['items']);
        $vendor = Vendor::findOrFail($validated['vendor_id']);
        $validated['vendor_name'] = $vendor->name;
        $validated['vendor_phone'] = trim((string) ($validated['vendor_phone'] ?? '')) ?: $vendor->phone;
        $validated['vendor_email'] = trim((string) ($validated['vendor_email'] ?? '')) ?: $vendor->email;
        unset($validated['attachment']);
        if ($request->hasFile('attachment')) {
            $validated = array_merge($validated, $this->storeAttachment($request->file('attachment')));
        }
        $firstItem = $items[0];
        $validated = array_merge($validated, [
            'category' => $firstItem['category'],
            'item_name' => $firstItem['item_name'],
            'material' => $firstItem['material'],
            'specification' => $firstItem['specification'],
            'size' => $firstItem['size'],
            'gsm' => $firstItem['gsm'],
            'color' => $firstItem['color'],
            'quantity' => $firstItem['quantity'],
            'unit' => $firstItem['unit'],
            'unit_price' => $firstItem['unit_price'],
        ]);
        $totals = $this->calculatePurchaseTotals($items, $validated);
        $subtotal = $totals['subtotal'];
        $vatPercentage = $totals['vat_percentage'];
        $tax = $totals['tax'];
        $shipping = $totals['shipping'];
        $total = $totals['total'];
        $payment = $this->resolvePaymentAmounts($validated, $total);
        if ($payment instanceof \Illuminate\Http\RedirectResponse) return $payment;

        $validated['subtotal'] = $subtotal;
        $validated['vat_percentage'] = $vatPercentage;
        $validated['tax_amount'] = $tax;
        $validated['shipping_cost'] = $shipping;
        $validated['total_amount'] = $total;
        $validated['paid_amount'] = $payment['paid'];
        $validated['balance_amount'] = $payment['balance'];
        $validated['payment_status'] = $payment['status'];
        $validated['created_by'] = \Auth::guard('crm')->id();
        DB::transaction(function () use ($validated, $items) {
            $purchase = VendorPurchase::create($validated);
            $purchase->items()->createMany($items);
        });

        return redirect()->route('crm.vendor_purchases.index', ['vendor_id' => $validated['vendor_id']])
            ->with('success', 'Vendor purchase recorded successfully.');
    }

    public function storeVendor(Request $request)
    {
        $this->authorizeAccess();
        $data = $request->validate(['name'=>'required|string|max:255','category'=>'nullable|string|max:100','trn_number'=>'nullable|string|max:100','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:255','address'=>'nullable|string','notes'=>'nullable|string']);
        Vendor::create($data);
        return redirect()->route('crm.vendor_purchases.index')->with('success','Vendor added successfully.');
    }

    public function updatePayment(Request $request, $id)
    {
        $this->authorizeAccess();
        $purchase = VendorPurchase::findOrFail($id);
        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0|max:'.$purchase->total_amount,
            'payment_method' => 'nullable|in:Cash,Bank Transfer,Card,Cheque,Credit',
        ]);

        $paid = round((float) $validated['paid_amount'], 2);
        $balance = round((float) $purchase->total_amount - $paid, 2);
        $purchase->update([
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'payment_status' => $paid <= 0 ? 'Unpaid' : ($balance > 0 ? 'Partial' : 'Paid'),
            'payment_method' => $validated['payment_method'] ?? $purchase->payment_method,
        ]);

        return redirect()->back()->with('success', 'Vendor payment updated successfully.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $purchase = VendorPurchase::findOrFail($id);
        $validated = $request->validate([
            'vendor_id' => 'required|integer|exists:vendors,id',
            'vendor_phone' => 'nullable|string|max:50',
            'vendor_email' => 'nullable|email|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv|max:20480',
            'purchase_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:purchase_date',
            'invoice_number' => 'nullable|string|max:100',
            'job_id' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string|max:100',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.material' => 'nullable|string|max:255',
            'items.*.specification' => 'nullable|string|max:255',
            'items.*.size_length' => 'nullable|numeric|min:0',
            'items.*.size_width' => 'nullable|numeric|min:0',
            'items.*.size_height' => 'nullable|numeric|min:0',
            'items.*.gsm' => 'nullable|string|max:50',
            'items.*.color' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|in:Sheets,Kg,Rolls,Pieces,Boxes,Liters,Meters,Pallets,Items,Services,Meals,Trips',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.line_total' => 'required|numeric|min:0',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:Unpaid,Partial,Paid',
            'payment_method' => 'nullable|in:Cash,Bank Transfer,Card,Cheque,Credit',
            'currency' => 'required|in:USD,AED,GBP,EUR,PKR',
            'notes' => 'nullable|string',
        ]);
        $items = $this->normalizePurchaseItems($validated['items']);
        unset($validated['items']);
        $vendor = Vendor::findOrFail($validated['vendor_id']);
        $validated['vendor_name'] = $vendor->name;
        $validated['vendor_phone'] = trim((string) ($validated['vendor_phone'] ?? '')) ?: $vendor->phone;
        $validated['vendor_email'] = trim((string) ($validated['vendor_email'] ?? '')) ?: $vendor->email;
        unset($validated['attachment']);
        if ($request->hasFile('attachment')) {
            $validated = array_merge($validated, $this->storeAttachment($request->file('attachment')));
        }
        $firstItem = $items[0];
        $validated = array_merge($validated, [
            'category' => $firstItem['category'], 'item_name' => $firstItem['item_name'],
            'material' => $firstItem['material'], 'specification' => $firstItem['specification'],
            'size' => $firstItem['size'], 'gsm' => $firstItem['gsm'], 'color' => $firstItem['color'],
            'quantity' => $firstItem['quantity'], 'unit' => $firstItem['unit'], 'unit_price' => $firstItem['unit_price'],
        ]);
        $totals = $this->calculatePurchaseTotals($items, $validated);
        $subtotal = $totals['subtotal'];
        $validated['vat_percentage'] = $totals['vat_percentage'];
        $validated['tax_amount'] = $totals['tax'];
        $validated['shipping_cost'] = $totals['shipping'];
        $total = $totals['total'];
        $payment = $this->resolvePaymentAmounts($validated, $total);
        if ($payment instanceof \Illuminate\Http\RedirectResponse) return $payment;
        $validated['subtotal'] = $subtotal; $validated['total_amount'] = $total;
        $validated['paid_amount'] = $payment['paid'];
        $validated['balance_amount'] = $payment['balance'];
        $validated['payment_status'] = $payment['status'];
        DB::transaction(function () use ($purchase, $validated, $items) {
            $purchase->update($validated);
            $purchase->items()->delete();
            $purchase->items()->createMany($items);
        });
        return redirect()->route('crm.vendor_purchases.index', ['vendor_id' => $validated['vendor_id']])->with('success', 'Vendor purchase updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorizeDelete();
        $purchase = VendorPurchase::findOrFail($id);
        $vendorId = $purchase->vendor_id;
        $attachment = $purchase->attachment_path ? public_path($purchase->attachment_path) : null;
        $label = ($purchase->invoice_number ?: '#'.$purchase->id) . ' — ' . ($purchase->vendor_name ?: 'Vendor');
        $snapshot = [
            'invoice_number' => $purchase->invoice_number,
            'vendor_name'    => $purchase->vendor_name,
            'job_id'         => $purchase->job_id,
            'total_amount'   => $purchase->total_amount,
            'paid_amount'    => $purchase->paid_amount,
            'currency'       => $purchase->currency,
            'purchase_date'  => optional($purchase->purchase_date)->format('Y-m-d'),
        ];
        \App\CrmDeletionLog::record('vendor_purchase', $purchase, $label, $snapshot);
        $purchase->delete();
        if ($attachment && is_file($attachment)) @unlink($attachment);

        return redirect()->route('crm.vendor_purchases.index', ['vendor_id' => $vendorId])
            ->with('success', 'Vendor purchase deleted (logged).');
    }

    public function destroyVendor($id)
    {
        $this->authorizeDelete();
        $vendor = Vendor::findOrFail($id);
        if ($vendor->purchases()->exists()) {
            return redirect()->back()->with('error', 'Delete this vendor’s purchase records first, then delete the vendor.');
        }
        \App\CrmDeletionLog::record('vendor', $vendor, $vendor->name, [
            'name'  => $vendor->name,
            'trn'   => $vendor->trn_number ?? null,
            'phone' => $vendor->phone,
            'email' => $vendor->email,
        ]);
        $vendor->delete();

        return redirect()->route('crm.vendor_purchases.index')->with('success', 'Vendor deleted (logged).');
    }

    private function storeAttachment($file)
    {
        $directory = public_path('uploads/vendor-purchases');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = 'vendor_purchase_' . uniqid('', true) . ($extension ? '.' . $extension : '');
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $file->move($directory, $filename);

        return [
            'attachment_path' => 'uploads/vendor-purchases/' . $filename,
            'attachment_name' => $originalName,
            'attachment_mime' => $mimeType,
        ];
    }

    private function applyPurchaseSize(array $validated)
    {
        $parts = [
            $validated['size_length'] ?? null,
            $validated['size_width'] ?? null,
            $validated['size_height'] ?? null,
        ];
        unset($validated['size_length'], $validated['size_width'], $validated['size_height']);

        $hasSize = collect($parts)->contains(function ($value) {
            return $value !== null && $value !== '';
        });
        $validated['size'] = $hasSize
            ? implode(' × ', array_map(function ($value) {
                return $value === null || $value === '' ? '0' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
            }, $parts))
            : null;

        return $validated;
    }

    private function normalizePurchaseItems(array $items)
    {
        return collect($items)->values()->map(function ($item, $index) {
            $quantity = round((float) $item['quantity'], 2);
            $lineTotal = round((float) $item['line_total'], 2);
            $unitPrice = $quantity > 0 ? round($lineTotal / $quantity, 4) : 0;
            $parts = [
                $item['size_length'] ?? null,
                $item['size_width'] ?? null,
                $item['size_height'] ?? null,
            ];
            $hasSize = collect($parts)->contains(function ($value) {
                return $value !== null && $value !== '';
            });

            return [
                'position' => $index + 1,
                'category' => trim($item['category']),
                'item_name' => trim($item['item_name']),
                'material' => $item['material'] ?? null,
                'specification' => $item['specification'] ?? null,
                'size' => $hasSize ? implode(' × ', array_map(function ($value) {
                    return $value === null || $value === '' ? '0' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
                }, $parts)) : null,
                'gsm' => $item['gsm'] ?? null,
                'color' => $item['color'] ?? null,
                'quantity' => $quantity,
                'unit' => $item['unit'],
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        })->all();
    }

    private function resolvePaymentAmounts(array $validated, $total)
    {
        $status = $validated['payment_status'];
        $paid = round((float) ($validated['paid_amount'] ?? 0), 2);
        if ($status === 'Unpaid') $paid = 0;
        if ($status === 'Paid') $paid = $total;

        if ($paid > $total) {
            return redirect()->back()->withInput()->withErrors([
                'paid_amount' => 'Paid amount cannot be greater than the purchase total.',
            ]);
        }
        if ($status === 'Partial' && ($paid <= 0 || $paid >= $total)) {
            return redirect()->back()->withInput()->withErrors([
                'paid_amount' => 'For Partial status, enter a paid amount greater than zero and less than the purchase total.',
            ]);
        }

        return ['paid' => $paid, 'balance' => round($total - $paid, 2), 'status' => $status];
    }

    private function calculatePurchaseTotals(array $items, array $validated)
    {
        $subtotal = round(collect($items)->sum('line_total'), 2);
        $vatPercentage = (float) ($validated['vat_percentage'] ?? 0);
        $shipping = round((float) ($validated['shipping_cost'] ?? 0), 2);
        $tax = round($subtotal * $vatPercentage / 100, 2);
        $total = round($subtotal + $tax + $shipping, 2);

        return [
            'subtotal' => $subtotal,
            'vat_percentage' => $vatPercentage,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
        ];
    }

    public function export(Request $request)
    {
        $this->authorizeAccess();
        $request->validate([
            'format' => 'required|in:excel,pdf',
            'ids' => 'nullable|array', 'ids.*' => 'integer',
            'vendor_ids' => 'nullable|array', 'vendor_ids.*' => 'integer|exists:vendors,id',
            'date_from' => 'nullable|date', 'date_to' => 'nullable|date|after_or_equal:date_from',
            'vendor_id' => 'nullable|integer|exists:vendors,id',
            'category' => 'nullable|string|max:100',
            'expense_type' => 'nullable|in:Production Expense,Personal Expense',
            'payment_status' => 'nullable|in:Paid,Partial,Unpaid',
            'search' => 'nullable|string|max:255',
        ]);
        $query = VendorPurchase::with('items')->orderBy('purchase_date')->orderBy('id');
        if ($request->filled('ids')) $query->whereIn('id', $request->input('ids'));
        if ($request->filled('vendor_ids')) $query->whereIn('vendor_id', $request->input('vendor_ids'));
        if ($request->filled('vendor_id')) $query->where('vendor_id', $request->vendor_id);
        if ($request->filled('expense_type')) {
            $query->whereHas('vendor', function ($vendorQuery) use ($request) {
                if ($request->expense_type === 'Production Expense') {
                    $vendorQuery->where(function ($categoryQuery) {
                        $categoryQuery->where('category', 'Production Expense')
                            ->orWhereNull('category')
                            ->orWhere('category', '');
                    });
                } else {
                    $vendorQuery->where('category', $request->expense_type);
                }
            });
        }
        if ($request->filled('category')) {
            $query->where(function ($purchaseQuery) use ($request) {
                $purchaseQuery->where('category', $request->category)
                    ->orWhereHas('items', function ($itemQuery) use ($request) {
                        $itemQuery->where('category', $request->category);
                    });
            });
        }
        if ($request->filled('payment_status')) $query->where('payment_status', $request->payment_status);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")->orWhere('item_name', 'like', "%{$search}%")
                    ->orWhere('material', 'like', "%{$search}%")->orWhere('invoice_number', 'like', "%{$search}%")->orWhere('job_id', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('item_name', 'like', "%{$search}%")
                            ->orWhere('material', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%");
                    });
            });
        }
        if ($request->filled('date_from')) $query->whereDate('purchase_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('purchase_date', '<=', $request->date_to);
        $purchases = $query->get();
        if ($purchases->isEmpty()) return redirect()->back()->with('error', 'No purchases match the selected rows/date range.');
        $filename = 'vendor-purchases-'.now()->format('Y-m-d');
        if ($request->format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.vendor_purchases.export_pdf', compact('purchases'))->setPaper('a4', 'landscape');
            return $pdf->download($filename.'.pdf');
        }
        return response()->view('crm.vendor_purchases.export_excel', compact('purchases'))->header('Content-Type', 'application/vnd.ms-excel')->header('Content-Disposition', 'attachment; filename="'.$filename.'.xls"');
    }

    private function authorizeAccess()
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager() && !$user->isAccounts())) {
            abort(403, 'Unauthorized access.');
        }
    }

    private function authorizeOwner()
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only the Owner can delete vendors or vendor purchases.');
        }
    }

    /**
     * Owner (super_admin), admins and accountants may delete vendors/purchases;
     * every delete is recorded in crm_deletion_logs.
     */
    private function authorizeDelete()
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isAccounts())) {
            abort(403, 'You do not have permission to delete this record.');
        }
    }
}
