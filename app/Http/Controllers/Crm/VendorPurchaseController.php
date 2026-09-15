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
    /** Chart of Expense Heads, grouped by expense type — used by the filter/category dropdowns. */
    private const EXPENSE_CATEGORY_GROUPS = [
        'Production' => ['Paper & Board Stock','Corrugation Rolls / Kraft','Rigid Box Board & Greyboard','PVC / PET Sheets','Prepress & Artwork','Die Making & Cutting Dies','Block Making','Foiling Job Charges','Embossing & Debossing','UV / Spot Varnish','Digital Printing','Outsource Printing','Outsource Pasting & Finishing','Outsource Labour Charges','Job Expense','Sampling & Mockups','Machine Repair & Maintenance','Production Wastage & Rejections','Freight & Delivery'],
        'Consumable' => ['Offset Inks (CMYK & Pantone)','Flexo & Digital Inks / Toner','Ink Additives & Drier','Varnish & Coatings','CTP Plates','Plate Chemicals & Developer','Fountain Solution & IPA','Blanket Wash & Solvents','Press Blankets & Rollers','Spray Powder','Lamination Film','Foil Rolls','Glue & Adhesives','Corrugation Starch & Adhesive','Double Sided & Gum Tape','Die Rules & Rubber','Stitching Wire & Staples','Ribbons, Handles & Magnets','Window Patching Film','Cutting Blades & Knives','Machine Oil, Lubricants & Grease','Spare Parts (Small)','Tools & Small Equipment','Packing Materials','Labels & Barcode Stickers','Cleaning Supplies & Rags','Safety Gear & Uniforms','Miscellaneous Consumables'],
        'Admin / General' => ['Salaries & Wages','Staff Visa, Labour Card & Medical','Staff Accommodation & Transport','Rent (Ejari)','DEWA (Electricity & Water)','Telecom & Internet','Trade License & Government Fees','Vehicle Fuel, Salik & Repair','Generator Diesel & Repair','Meals & Late Night Meals','Kitchen / Pantry Stock','Stationery & Printing','IT Expense','Marketing & Advertising','Bank Charges & VAT Adjustments','Professional Fees','Insurance','Travel & Fare Charges','Electric Work & Office Repairs','Admin Other Expenses'],
    ];

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
                'expense_type' => $item->expense_type,
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
                'vat_percentage' => $item->vat_percentage,
                'extra' => $item->extra,
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
            // Match the header OR any line item so a mixed invoice shows under each of its types.
            if ($request->filled('expense_type')) {
                $this->applyExpenseTypeFilter($query, $request->expense_type);
            }
        };
        $vendorsQuery = Vendor::withCount(['purchases' => $applyPurchaseFilters])->with(['purchases' => function ($query) use ($applyPurchaseFilters) {
            $query->select('id', 'vendor_id', 'purchase_date', 'total_amount', 'paid_amount', 'balance_amount', 'payment_status', 'expense_type');
            $applyPurchaseFilters($query);
        }]);
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
        if ($request->filled('category') || $request->filled('payment_status') || $request->filled('date_from') || $request->filled('date_to') || $request->filled('expense_type')) {
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
        if ($request->filled('expense_type')) {
            $this->applyExpenseTypeFilter($query, $request->expense_type);
        }

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

        $expenseCatGroups = self::EXPENSE_CATEGORY_GROUPS;
        return view('crm.vendor_purchases.index', compact('purchases', 'summary', 'vendors', 'selectedVendor', 'directorySummary', 'jobSummary', 'expenseCatGroups'));
    }

    /**
     * Job-wise purchase report: all jobs with their total expense, or one job's purchases.
     */
    /** JSON feed for the header bell's auto-refresh (12h-before due reminders, workspace-scoped). */
    public function dueReminders(Request $request)
    {
        $this->authorizeAccess();

        return response()->json(\App\Support\VendorPurchaseReminders::current());
    }

    public function jobs(Request $request)
    {
        $this->authorizeAccess();
        $search = trim((string) $request->input('search', ''));
        $selectedJob = trim((string) $request->input('job_id', ''));

        // Attribute expense to jobs at the LINE-ITEM level: each item counts under its own
        // Job ID (extra.job_id) or, if it has none, the invoice header job_id. Amounts are the
        // item's share of the invoice total/paid/balance (by gross) so job totals tie to invoices.
        $jobItemRows = collect();
        VendorPurchase::with('items')->get()->each(function ($p) use ($jobItemRows) {
            if ($p->items->isEmpty()) {
                $jobId = trim((string) $p->job_id);
                if ($jobId !== '') {
                    $jobItemRows->push((object) ['job_id' => $jobId, 'purchase_id' => $p->id, 'vendor_name' => $p->vendor_name, 'total' => (float) $p->total_amount, 'paid' => (float) $p->paid_amount, 'balance' => (float) $p->balance_amount, 'purchase_date' => $p->purchase_date, 'currency' => $p->currency]);
                }
                return;
            }
            $grosses = $p->items->map(function ($it) { return (float) $it->line_total * (1 + (float) $it->vat_percentage / 100); });
            $sumGross = $grosses->sum();
            $count = $grosses->count();
            foreach ($p->items->values() as $i => $it) {
                $jobId = trim((string) (data_get($it->extra, 'job_id') ?: $p->job_id));
                if ($jobId === '') continue;
                $ratio = $sumGross > 0 ? (float) $grosses[$i] / $sumGross : ($count ? 1 / $count : 0);
                $jobItemRows->push((object) ['job_id' => $jobId, 'purchase_id' => $p->id, 'vendor_name' => $p->vendor_name, 'total' => round((float) $p->total_amount * $ratio, 2), 'paid' => round((float) $p->paid_amount * $ratio, 2), 'balance' => round((float) $p->balance_amount * $ratio, 2), 'purchase_date' => $p->purchase_date, 'currency' => $p->currency]);
            }
        });

        // A single job selected → show ONLY that job's purchases (matched on header OR any item).
        if ($selectedJob !== '') {
            $rows = $jobItemRows->where('job_id', $selectedJob);
            $jobSummary = [
                'job_id' => $selectedJob,
                'count' => $rows->pluck('purchase_id')->unique()->count(),
                'vendors' => $rows->pluck('vendor_name')->filter()->unique()->count(),
                'total' => round($rows->sum('total'), 2),
                'paid' => round($rows->sum('paid'), 2),
                'balance' => round($rows->sum('balance'), 2),
                'currency' => optional($rows->first())->currency ?: 'AED',
            ];
            $purchases = VendorPurchase::with(['vendor', 'items'])
                ->where(function ($q) use ($selectedJob) {
                    $q->where('job_id', $selectedJob)->orWhereHas('items', function ($i) use ($selectedJob) { $i->where('extra->job_id', $selectedJob); });
                })
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
        $jobGroups = $jobItemRows
            ->when($search !== '', function ($c) use ($search) { return $c->filter(function ($r) use ($search) { return stripos($r->job_id, $search) !== false; }); })
            ->groupBy('job_id')->map(function ($rows, $jobId) {
                return (object) [
                    'job_id' => $jobId,
                    'count' => $rows->pluck('purchase_id')->unique()->count(),
                    'vendors' => $rows->pluck('vendor_name')->filter()->unique()->count(),
                    'total' => round($rows->sum('total'), 2),
                    'paid' => round($rows->sum('paid'), 2),
                    'balance' => round($rows->sum('balance'), 2),
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
            'expense_type' => 'nullable|in:Production Expense,Consumable Expense,Admin/General Expense',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string|max:100',
            'items.*.expense_type' => 'nullable|in:Production Expense,Consumable Expense,Admin/General Expense',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.material' => 'nullable|string|max:255',
            'items.*.specification' => 'nullable|string|max:255',
            'items.*.size_length' => 'nullable|numeric|min:0',
            'items.*.size_width' => 'nullable|numeric|min:0',
            'items.*.size_height' => 'nullable|numeric|min:0',
            'items.*.gsm' => 'nullable|string|max:50',
            'items.*.color' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:40',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.line_total' => 'nullable|numeric|min:0',
            'items.*.vat_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.extra' => 'nullable|array',
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
        // Expense type is per item now; the header keeps the dominant type (for the list/filter).
        $validated['expense_type'] = collect($items)->countBy('expense_type')->sortDesc()->keys()->first() ?: 'Production Expense';
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
            'expense_type' => 'nullable|in:Production Expense,Consumable Expense,Admin/General Expense',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string|max:100',
            'items.*.expense_type' => 'nullable|in:Production Expense,Consumable Expense,Admin/General Expense',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.material' => 'nullable|string|max:255',
            'items.*.specification' => 'nullable|string|max:255',
            'items.*.size_length' => 'nullable|numeric|min:0',
            'items.*.size_width' => 'nullable|numeric|min:0',
            'items.*.size_height' => 'nullable|numeric|min:0',
            'items.*.gsm' => 'nullable|string|max:50',
            'items.*.color' => 'nullable|string|max:100',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:40',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.line_total' => 'nullable|numeric|min:0',
            'items.*.vat_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.extra' => 'nullable|array',
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
        // Expense type is per item now; the header keeps the dominant type (for the list/filter).
        $validated['expense_type'] = collect($items)->countBy('expense_type')->sortDesc()->keys()->first() ?: 'Production Expense';
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
            // Total is now driven by Qty × Price/Unit (Price/Unit is entered directly),
            // and VAT % is captured per item; Gross (Total + VAT) is derived on display.
            $quantity = round((float) $item['quantity'], 2);
            $unitPrice = round((float) ($item['unit_price'] ?? 0), 4);
            $lineTotal = round($quantity * $unitPrice, 2);
            $vat = round((float) ($item['vat_percentage'] ?? 0), 2);

            return [
                'position' => $index + 1,
                'category' => trim($item['category']),
                'expense_type' => $item['expense_type'] ?? 'Production Expense',
                'item_name' => trim($item['item_name']),
                'material' => $item['material'] ?? null,
                'specification' => $item['specification'] ?? null,
                'size' => null,
                'gsm' => $item['gsm'] ?? null,
                'color' => $item['color'] ?? null,
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? 'Items',
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'vat_percentage' => $vat,
                'extra' => array_filter((array) ($item['extra'] ?? []), function ($v) { return $v !== null && $v !== ''; }) ?: null,
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

    /**
     * Filter purchases by expense type, matching the header OR any line item, so a mixed
     * invoice surfaces under every expense type it contains. A missing type counts as Production.
     */
    private function applyExpenseTypeFilter($query, $type)
    {
        $query->where(function ($q) use ($type) {
            if ($type === 'Production Expense') {
                $q->where(function ($h) {
                    $h->where('expense_type', 'Production Expense')->orWhereNull('expense_type')->orWhere('expense_type', '');
                })->orWhereHas('items', function ($i) {
                    $i->where('expense_type', 'Production Expense')->orWhereNull('expense_type')->orWhere('expense_type', '');
                });
            } else {
                $q->where('expense_type', $type)->orWhereHas('items', function ($i) use ($type) {
                    $i->where('expense_type', $type);
                });
            }
        });
    }

    private function calculatePurchaseTotals(array $items, array $validated)
    {
        $subtotal = round(collect($items)->sum('line_total'), 2);
        // VAT is per item now: sum of each line's (Total × its VAT%).
        $tax = round(collect($items)->sum(function ($it) {
            return (float) $it['line_total'] * (float) ($it['vat_percentage'] ?? 0) / 100;
        }), 2);
        $shipping = round((float) ($validated['shipping_cost'] ?? 0), 2);
        $total = round($subtotal + $tax + $shipping, 2);
        // Store an effective header VAT% for display/back-compat.
        $vatPercentage = $subtotal > 0 ? round($tax / $subtotal * 100, 2) : 0;

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
            'expense_type' => 'nullable|in:Production Expense,Consumable Expense,Admin/General Expense',
            'payment_status' => 'nullable|in:Paid,Partial,Unpaid',
            'search' => 'nullable|string|max:255',
        ]);
        $query = VendorPurchase::with('items')->orderBy('purchase_date')->orderBy('id');
        if ($request->filled('ids')) $query->whereIn('id', $request->input('ids'));
        if ($request->filled('vendor_ids')) $query->whereIn('vendor_id', $request->input('vendor_ids'));
        if ($request->filled('vendor_id')) $query->where('vendor_id', $request->vendor_id);
        if ($request->filled('expense_type')) {
            $this->applyExpenseTypeFilter($query, $request->expense_type);
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
