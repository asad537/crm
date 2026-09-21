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
        'Production' => ['Paper Board & Stock','Label & stickers','Special Paper','CTP Plates','Die Making','Foil Block Making','Embossing/Debossing Block','Digital Print','Outsource Printing','Outsource Labor','Sampling Charge','Magnets','PVC Sheets','Ribbons','Foam','Velvet','Leather','Production Misc'],
        'Consumable' => ['Inks, Varnish & Coatings','Chemicals, IPA, Liquids','Press Blankets & Rollers','Foil Rolls','Lamination Films','Glue','Adhesive Tapes','Machine Oil, Lubricants & Grease','Powder & Sprays','Consumable Misc'],
        'Admin / General' => ['Salaries & Wages','Staff Visa, Labour Card & Medical','Staff Accommodation & Transport','Rent (Ejari)','DEWA (Electricity & Water)','Telecom & Internet','Trade License & Government Fees','Vehicle Fuel, Salik & Repair','Generator Diesel & Repair','Meals & Late Night Meals','Kitchen / Pantry Stock','Stationery & Printing','IT Expense','Marketing & Advertising','Bank Charges & VAT Adjustments','Professional Fees','Insurance','Travel & Fare Charges','Electric Work & Office Repairs','Admin Other Expenses','Machine Repair & Maintenance','Production Wastage & Rejections','Freight & Delivery','Admin/General Misc'],
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

    /** Demand Requests offered in the "link to demand" dropdowns. */
    private function demandRequestOptions()
    {
        return \App\DemandRequest::whereIn('status', ['Approved', 'Partially Paid', 'Completed'])
            ->orderByDesc('request_no')
            ->get(['id', 'request_no', 'requested_by'])
            ->map(fn ($d) => ['id' => $d->id, 'label' => '#'.str_pad($d->request_no, 3, '0', STR_PAD_LEFT).' — '.($d->requested_by ?: 'Demand')]);
    }

    public function create(Request $request)
    {
        $this->authorizeAccess();
        $vendors = Vendor::orderBy('name')->get();
        $selectedVendorId = $request->filled('vendor_id') ? (int) $request->vendor_id : null;
        $demandOptions = $this->demandRequestOptions();

        return view('crm.vendor_purchases.create', compact('vendors', 'selectedVendorId', 'demandOptions'));
    }

    /** Clean, vendor-type-specific "Add Purchase" form (only the columns that type uses). */
    public function createTyped(Request $request)
    {
        $this->authorizeAccess();
        $vendors = Vendor::orderBy('name')->get();
        $selectedVendorId = $request->filled('vendor_id') ? (int) $request->vendor_id : null;
        $demandOptions = $this->demandRequestOptions();

        return view('crm.vendor_purchases.create_typed', compact('vendors', 'selectedVendorId', 'demandOptions'));
    }

    public function storeTyped(Request $request)
    {
        $this->authorizeAccess();
        $request->merge(['invoice_number' => trim((string) $request->input('invoice_number')) ?: null]);
        $data = $request->validate([
            'vendor_id' => 'required|integer|exists:vendors,id',
            'purchase_date' => 'required|date',
            'invoice_number' => ['nullable','string','max:100', \Illuminate\Validation\Rule::unique('vendor_purchases','invoice_number')->where(fn($q)=>$q->where('workspace_id', \App\Support\CrmWorkspaceContext::id()))],
            'job_id' => 'nullable|string|max:100',
            'demand_id' => 'nullable|integer|exists:demand_requests,id',
            'gp_status' => 'nullable|string|max:40',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,csv|max:20480',
            'items' => 'required|array|min:1',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.paper_type' => 'nullable|string|max:255',
            'items.*.gsm' => 'nullable|string|max:50',
            'items.*.size' => 'nullable|string|max:100',
            'items.*.unit' => 'nullable|string|max:40',
            'items.*.colours' => 'nullable|string|max:100',
            'items.*.up_imposition' => 'nullable|string|max:50',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.gst_percentage' => 'nullable|numeric|min:0|max:100',
            'deduction' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:Cash,Bank Transfer,Card,Cheque,Credit',
            'currency' => 'required|in:USD,AED,GBP,EUR,PKR',
        ]);

        $vendor = Vendor::findOrFail($data['vendor_id']);

        // Build normalized line items and running totals.
        $rows = [];
        $subtotalSum = 0.0;
        $taxSum = 0.0;
        foreach ($data['items'] as $it) {
            $qty = (float) ($it['quantity'] ?? 1);
            if ($qty <= 0) { $qty = 1; }
            $rate = round((float) ($it['rate'] ?? 0), 2);
            $line = round($qty * $rate, 2);
            $gstPct = round((float) ($it['gst_percentage'] ?? 0), 2);
            $tax = round($line * $gstPct / 100, 2);
            // Skip completely empty rows.
            if ($line <= 0 && empty($it['description']) && empty($it['paper_type'])) { continue; }
            $subtotalSum += $line;
            $taxSum += $tax;
            $rows[] = [
                'description' => $it['description'] ?: ($it['paper_type'] ?? $vendor->typeLabel().' item'),
                'paper_type' => $it['paper_type'] ?? null,
                'gsm' => $it['gsm'] ?? null,
                'size' => $it['size'] ?? null,
                'unit' => $it['unit'] ?: 'Items',
                'colours' => $it['colours'] ?? null,
                'up_imposition' => $it['up_imposition'] ?? null,
                'qty' => $qty,
                'rate' => $rate,
                'gst' => $gstPct,
                'line' => $line,
            ];
        }
        if (empty($rows)) {
            return back()->withInput()->withErrors(['items' => 'Add at least one item with a rate.']);
        }

        $subtotalSum = round($subtotalSum, 2);
        $taxSum = round($taxSum, 2);
        $total = round($subtotalSum + $taxSum, 2);
        $deduction = round((float) ($data['deduction'] ?? 0), 2);
        $paid = min(round((float) ($data['paid_amount'] ?? 0), 2), $total);

        $demandNo = null;
        if (!empty($data['demand_id'])) {
            $dr = \App\DemandRequest::find($data['demand_id']);
            $demandNo = $dr ? '#'.str_pad($dr->request_no, 3, '0', STR_PAD_LEFT) : null;
        }

        // Optional invoice/proof attachment on the purchase header.
        $attach = ['attachment_path' => null, 'attachment_name' => null, 'attachment_mime' => null];
        if ($request->hasFile('attachment')) {
            $attach = $this->storeAttachment($request->file('attachment'));
        }

        $first = $rows[0];

        DB::transaction(function () use ($data, $vendor, $rows, $first, $subtotalSum, $taxSum, $total, $deduction, $paid, $demandNo, $attach) {
            $purchase = VendorPurchase::create(array_merge([
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'vendor_phone' => $vendor->phone,
                'vendor_email' => $vendor->email,
                'purchase_date' => $data['purchase_date'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'job_id' => $data['job_id'] ?? null,
                'demand_id' => $data['demand_id'] ?? null,
                'demand_no' => $demandNo,
                'category' => $vendor->typeLabel(),
                'expense_type' => 'Production Expense',
                'item_name' => $first['description'],
                'material' => $first['paper_type'],
                'size' => $first['size'],
                'gsm' => $first['gsm'],
                'up_imposition' => $first['up_imposition'],
                'gp_status' => $data['gp_status'] ?? null,
                'color' => $first['colours'],
                'quantity' => $first['qty'],
                'unit' => $first['unit'],
                'unit_price' => $first['rate'],
                'subtotal' => $subtotalSum,
                'vat_percentage' => $first['gst'],
                'tax_amount' => $taxSum,
                'shipping_cost' => 0,
                'deduction' => $deduction,
                'total_amount' => $total,
                'currency' => $data['currency'],
                'created_by' => \Auth::guard('crm')->id(),
            ], $attach));

            foreach ($rows as $i => $r) {
                $purchase->items()->create([
                    'category' => $vendor->typeLabel(),
                    'expense_type' => 'Production Expense',
                    'item_name' => $r['description'],
                    'material' => $r['paper_type'],
                    'size' => $r['size'],
                    'gsm' => $r['gsm'],
                    'color' => $r['colours'],
                    'quantity' => $r['qty'],
                    'unit' => $r['unit'],
                    'unit_price' => $r['rate'],
                    'line_total' => $r['line'],
                    'vat_percentage' => $r['gst'],
                    'position' => $i,
                ]);
            }
            if ($paid > 0.009) {
                $purchase->payments()->create([
                    'amount' => $paid,
                    'method' => $data['payment_method'] ?? null,
                    'paid_at' => $data['purchase_date'],
                    'note' => 'Initial payment',
                    'created_by' => \Auth::guard('crm')->id(),
                ]);
            }
            $purchase->recomputePayments();
        });

        return redirect()->route('crm.vendor_purchases.index', ['vendor_id' => $vendor->id])
            ->with('success', 'Vendor purchase recorded successfully.');
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

        $demandOptions = $this->demandRequestOptions();

        return view('crm.vendor_purchases.create', compact('vendors', 'selectedVendorId', 'purchase', 'purchaseItems', 'demandOptions'));
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

        $query = VendorPurchase::with(['creator', 'items', 'payments'])->orderBy('purchase_date', 'desc')->orderBy('id', 'desc');
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
        // Demand Requests for the payment "link to demand" dropdown (approved / in-payment).
        $demandOptions = $this->demandRequestOptions();
        $vendorType = $selectedVendor->vendor_type ?? null;
        // Type-specific list columns [label, purchase-field, format]. Inserted after "Packaging Item".
        $typeColumnMap = [
            'paper' => [
                ['Paper Type', 'material', 'text'], ['GSM', 'gsm', 'text'], ['Size', 'size', 'text'],
                ['Unit', 'unit', 'text'], ['Sheet Qty', 'quantity', 'text'],
                ['Rate', 'unit_price', 'money'], ['GST', 'tax_amount', 'money'],
            ],
            'ctp_plate' => [
                ['Up', 'up_imposition', 'text'], ['Rate', 'unit_price', 'money'],
            ],
            'die_making' => [
                ['Size', 'size', 'text'], ['Colours', 'color', 'text'], ['Rate', 'unit_price', 'money'],
            ],
            'general' => [],
        ];
        $typeCols = $typeColumnMap[$vendorType] ?? [];
        $vpColspan = 14 + count($typeCols);
        return view('crm.vendor_purchases.index', compact('purchases', 'summary', 'vendors', 'selectedVendor', 'directorySummary', 'jobSummary', 'expenseCatGroups', 'demandOptions', 'vendorType', 'typeCols', 'vpColspan'));
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
        $request->merge(['invoice_number' => trim((string) $request->input('invoice_number')) ?: null]);

        $validated = $request->validate([
            'vendor_id' => 'required|integer|exists:vendors,id',
            'vendor_phone' => 'nullable|string|max:50',
            'vendor_email' => 'nullable|email|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv|max:20480',
            'purchase_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:purchase_date',
            'invoice_number' => ['nullable','string','max:100', \Illuminate\Validation\Rule::unique('vendor_purchases','invoice_number')->where(fn($q)=>$q->where('workspace_id', \App\Support\CrmWorkspaceContext::id()))],
            'job_id' => 'nullable|string|max:100',
            'demand_id' => 'nullable|integer|exists:demand_requests,id',
            'deduction' => 'nullable|numeric|min:0',
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
        // Deduction reduces the balance alongside payments.
        $deduction = round((float) ($validated['deduction'] ?? 0), 2);
        $validated['deduction'] = $deduction;
        $validated['paid_amount'] = $payment['paid'];
        $validated['balance_amount'] = max(0, round($total - $payment['paid'] - $deduction, 2));
        $validated['payment_status'] = ($payment['paid'] + $deduction) <= 0.009
            ? 'Unpaid'
            : ($validated['balance_amount'] > 0.009 ? 'Partial' : 'Paid');
        // Link to a Demand Request (optional) and store its display number.
        $validated['demand_id'] = $validated['demand_id'] ?? null;
        if (!empty($validated['demand_id'])) {
            $dr = \App\DemandRequest::find($validated['demand_id']);
            $validated['demand_no'] = $dr ? '#'.str_pad($dr->request_no, 3, '0', STR_PAD_LEFT) : null;
        }
        $validated['up_imposition'] = trim((string) $request->input('up_imposition')) ?: null;
        $validated['gp_status'] = trim((string) $request->input('gp_status')) ?: null;
        $validated['created_by'] = \Auth::guard('crm')->id();
        DB::transaction(function () use ($validated, $items, $payment) {
            $purchase = VendorPurchase::create($validated);
            $purchase->items()->createMany($items);
            // Any initial paid amount becomes the first payment record so payments stay the source of truth.
            if (($payment['paid'] ?? 0) > 0.009) {
                $purchase->payments()->create([
                    'amount' => round((float) $payment['paid'], 2),
                    'method' => $validated['payment_method'] ?? null,
                    'paid_at' => $validated['purchase_date'] ?? now()->toDateString(),
                    'note' => 'Initial payment',
                    'created_by' => \Auth::guard('crm')->id(),
                ]);
            }
            $purchase->recomputePayments();
        });

        return redirect()->route('crm.vendor_purchases.index', ['vendor_id' => $validated['vendor_id']])
            ->with('success', 'Vendor purchase recorded successfully.');
    }

    public function storeVendor(Request $request)
    {
        $this->authorizeAccess();
        $data = $request->validate(['name'=>'required|string|max:255','category'=>'nullable|string|max:100','vendor_type'=>'nullable|in:'.implode(',', array_keys(Vendor::TYPES)),'trn_number'=>'nullable|string|max:100','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:255','address'=>'nullable|string','notes'=>'nullable|string']);
        $data['vendor_type'] = $data['vendor_type'] ?? 'general';
        Vendor::create($data);
        return redirect()->route('crm.vendor_purchases.index')->with('success','Vendor added successfully.');
    }

    public function updateVendor(Request $request, $id)
    {
        $this->authorizeAccess();
        $vendor = Vendor::findOrFail($id);
        $data = $request->validate(['name'=>'required|string|max:255','category'=>'nullable|string|max:100','vendor_type'=>'nullable|in:'.implode(',', array_keys(Vendor::TYPES)),'trn_number'=>'nullable|string|max:100','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:255','address'=>'nullable|string','notes'=>'nullable|string']);
        $data['vendor_type'] = $data['vendor_type'] ?? $vendor->vendor_type ?? 'general';
        $vendor->update($data);
        // Keep the denormalised vendor name/contact on existing purchases in sync.
        VendorPurchase::where('vendor_id', $vendor->id)->update([
            'vendor_name' => $vendor->name,
            'vendor_phone' => $vendor->phone,
            'vendor_email' => $vendor->email,
        ]);
        return redirect()->route('crm.vendor_purchases.index', ['vendor_id' => $vendor->id])->with('success','Vendor updated successfully.');
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

    /** Record one payment against a purchase (optionally linked to a Demand Request), with receipt. */
    public function addPayment(Request $request, $id)
    {
        $this->authorizeAccess();
        $purchase = VendorPurchase::findOrFail($id);
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'demand_id' => 'nullable|integer|exists:demand_requests,id',
            'method' => 'nullable|string|max:40',
            'paid_at' => 'nullable|date',
            'note' => 'nullable|string|max:500',
            'receipt' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv|max:20480',
        ]);

        $demandNo = null;
        if (!empty($validated['demand_id'])) {
            $dr = \App\DemandRequest::find($validated['demand_id']);
            $demandNo = $dr ? '#'.str_pad($dr->request_no, 3, '0', STR_PAD_LEFT) : null;
        }

        $payload = [
            'demand_id' => $validated['demand_id'] ?? null,
            'demand_no' => $demandNo,
            'amount' => round((float) $validated['amount'], 2),
            'method' => $validated['method'] ?? null,
            'paid_at' => $validated['paid_at'] ?? now()->toDateString(),
            'note' => $validated['note'] ?? null,
            'created_by' => \Auth::guard('crm')->id(),
        ];
        if ($request->hasFile('receipt')) {
            $payload = array_merge($payload, $this->storeReceipt($request->file('receipt')));
        }
        $purchase->payments()->create($payload);
        $purchase->recomputePayments();

        return back()->with('success', 'Payment recorded. Balance updated.');
    }

    public function deletePayment($id, $paymentId)
    {
        $this->authorizeAccess();
        $purchase = VendorPurchase::findOrFail($id);
        $payment = $purchase->payments()->where('id', $paymentId)->first();
        if ($payment) {
            if ($payment->receipt_path && is_file(public_path($payment->receipt_path))) {
                @unlink(public_path($payment->receipt_path));
            }
            $payment->delete();
            $purchase->recomputePayments();
        }

        return back()->with('success', 'Payment removed. Balance updated.');
    }

    private function storeReceipt($file)
    {
        $dir = public_path('uploads/vendor-purchases/receipts');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ext = strtolower($file->getClientOriginalExtension());
        $fname = 'vp_receipt_' . uniqid('', true) . ($ext ? '.' . $ext : '');
        $name = $file->getClientOriginalName();
        $mime = $file->getClientMimeType();
        $file->move($dir, $fname);

        return [
            'receipt_path' => 'uploads/vendor-purchases/receipts/' . $fname,
            'receipt_name' => $name,
            'receipt_mime' => $mime,
        ];
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $request->merge(['invoice_number' => trim((string) $request->input('invoice_number')) ?: null]);
        $purchase = VendorPurchase::findOrFail($id);
        $validated = $request->validate([
            'vendor_id' => 'required|integer|exists:vendors,id',
            'vendor_phone' => 'nullable|string|max:50',
            'vendor_email' => 'nullable|email|max:255',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv|max:20480',
            'purchase_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:purchase_date',
            'invoice_number' => ['nullable','string','max:100', \Illuminate\Validation\Rule::unique('vendor_purchases','invoice_number')->ignore($id)->where(fn($q)=>$q->where('workspace_id', \App\Support\CrmWorkspaceContext::id()))],
            'job_id' => 'nullable|string|max:100',
            'demand_id' => 'nullable|integer|exists:demand_requests,id',
            'deduction' => 'nullable|numeric|min:0',
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
        $validated['deduction'] = round((float) ($validated['deduction'] ?? 0), 2);
        // Link demand.
        $validated['demand_id'] = $validated['demand_id'] ?? null;
        if (!empty($validated['demand_id'])) {
            $dr = \App\DemandRequest::find($validated['demand_id']);
            $validated['demand_no'] = $dr ? '#'.str_pad($dr->request_no, 3, '0', STR_PAD_LEFT) : null;
        } else {
            $validated['demand_no'] = null;
        }
        $validated['up_imposition'] = trim((string) $request->input('up_imposition')) ?: null;
        $validated['gp_status'] = trim((string) $request->input('gp_status')) ?: null;
        // Payments table is the source of truth; don't let the form paid_amount override recorded payments.
        unset($validated['paid_amount']);
        DB::transaction(function () use ($purchase, $validated, $items, $payment) {
            $purchase->update($validated);
            $purchase->items()->delete();
            $purchase->items()->createMany($items);
            // First-time paid amount (no prior payment records) becomes an initial payment record.
            if (!$purchase->payments()->exists() && ($payment['paid'] ?? 0) > 0.009) {
                $purchase->payments()->create([
                    'amount' => round((float) $payment['paid'], 2),
                    'method' => $validated['payment_method'] ?? null,
                    'paid_at' => $validated['purchase_date'] ?? now()->toDateString(),
                    'note' => 'Initial payment',
                    'created_by' => \Auth::guard('crm')->id(),
                ]);
            }
            $purchase->recomputePayments();
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
