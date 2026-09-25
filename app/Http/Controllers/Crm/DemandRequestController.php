<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\DemandRequest;
use App\Support\CrmWorkspaceContext;
use Illuminate\Http\Request;

class DemandRequestController extends Controller
{
    /** Categories for a demand request line (custom values are also allowed). */
    public const CATEGORIES = [
        'Salary', 'Tax / Govt Registration', 'Consumable', 'Admin / General',
        'Stock / Inventory', 'Petty Cash', 'Production', 'Other',
    ];

    public const PRIORITIES = ['Normal', 'Urgent'];

    /** Who the money came from when an item is received. Custom values also allowed. */
    public const PAYERS = ['Petty Cash', 'Bank', 'Cash', 'Company Account', 'Owner'];

    private function authorizeAccess()
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager() && !$user->isAccounts())) {
            abort(403, 'Unauthorized access.');
        }
    }

    /** Only Admin / Owner (Super Admin) may add or remove demand attachments. */
    private function authorizeManageFiles()
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin())) {
            abort(403, 'Only admins can manage attachments.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();

        // Same filters drive both the table AND the summary cards above.
        $applyFilters = function ($q) use ($request) {
            if ($request->filled('status')) {
                $q->where('status', $request->status);
            }
            if ($request->filled('priority')) {
                $q->where('priority', $request->priority);
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $q->where(function ($qq) use ($s) {
                    $qq->where('requested_by', 'like', "%{$s}%")
                        ->orWhere('request_no', 'like', "%{$s}%")
                        ->orWhereHas('items', function ($iq) use ($s) {
                            $iq->where('description', 'like', "%{$s}%")->orWhere('category', 'like', "%{$s}%")->orWhere('job_no', 'like', "%{$s}%");
                        });
                });
            }

            return $q;
        };

        $query = $applyFilters(DemandRequest::with(['creator', 'items', 'payments'])->orderBy('request_date', 'desc')->orderBy('id', 'desc'));
        $requests = $query->paginate(20)->appends($request->all());

        // Summary reflects the current filters (Cash in Hand stays the global pool figure).
        $all = $applyFilters(DemandRequest::with(['items', 'payments']))->get();
        $summary = [
            'total' => $all->count(),
            'estimated' => (float) $all->sum('estimated_total'),
            'paid' => (float) $all->sum(fn($d) => $d->paidTotal()),
            'outstanding' => round((float) $all->sum(fn($d) => $d->outstandingTotal()), 2),
            'balance' => round((float) $all->sum(fn($d) => $d->paidTotal() + $d->writeOffTotal() - (float) $d->estimated_total), 2),
            // Account/company top cards stay hidden until the demand is completed in the row table.
            'account_out' => 0,
            'company_out' => 0,
            'cash_in_hand' => $this->cashInHand(),
        ];

        return view('crm.demand_requests.index', [
            'requests' => $requests,
            'summary' => $summary,
            'priorities' => self::PRIORITIES,
        ]);
    }

    /**
     * Cash in Hand (signed) = net account balance carried by Completed demands, minus draws.
     *   Positive  → the accountant is holding the company's money (surplus/credit).
     *   Negative  → the accountant paid from their own pocket (the company owes them).
     * Draws via "cash_in_hand_used" reduce it; if draws exceed the credit it goes negative.
     */
    private function cashInHand()
    {
        $demands = DemandRequest::with(['items', 'payments'])->get();
        $net = (float) $demands->where('status', 'Completed')
            ->sum(fn($d) => $d->accountOutstanding());
        $used = (float) $demands->sum(fn($d) => (float) $d->cash_in_hand_used);

        return round($net - $used, 2);
    }

    /** Cash in Hand a given demand may draw = current pool + whatever it already holds (never below 0). */
    private function cashInHandAvailableFor(?DemandRequest $demand = null): float
    {
        return round(max(0, $this->cashInHand() + ($demand ? (float) $demand->cash_in_hand_used : 0)), 2);
    }

    public function create()
    {
        $this->authorizeAccess();
        $user = \Auth::guard('crm')->user();

        return view('crm.demand_requests.create', [
            'categories' => self::CATEGORIES,
            'priorities' => self::PRIORITIES,
            'defaultRequestedBy' => $user ? $user->name : '',
            'demandRequest' => null,
            'cashInHand' => $this->cashInHand(),
            'vendors' => \App\Vendor::orderBy('name')->pluck('name')->filter()->values(),
            'items' => [['category' => '', 'qty' => '', 'estimated_price' => '', 'estimated_total' => '']],
        ]);
    }

    public function edit($id)
    {
        $this->authorizeAccess();
        $demandRequest = DemandRequest::with('items.files', 'attachments')->findOrFail($id);

        return view('crm.demand_requests.create', [
            'categories' => self::CATEGORIES,
            'priorities' => self::PRIORITIES,
            'defaultRequestedBy' => $demandRequest->requested_by,
            'demandRequest' => $demandRequest,
            'canApprove' => $this->canApprove(),
            'cashInHand' => $this->cashInHand(),
            'vendors' => \App\Vendor::orderBy('name')->pluck('name')->filter()->values(),
            'items' => $demandRequest->items->map(function ($it) {
                return [
                    'category' => $it->category, 'job_no' => $it->job_no, 'description' => $it->description,
                    'specification' => $it->specification, 'gsm' => $it->gsm, 'vendor_name' => $it->vendor_name, 'vendor_invoice_no' => $it->vendor_invoice_no,
                    'qty' => $it->qty, 'estimated_price' => $it->estimated_price, 'vat_percentage' => $it->vat_percentage, 'estimated_total' => $it->estimated_total,
                    'pay_by' => $it->pay_by, 'files' => $it->files,
                ];
            })->all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $validated = $this->validateRequest($request);

        $items = $this->normalizeItems($validated['items']);
        $status = $request->input('action') === 'draft' ? 'Draft' : 'Submitted';
        $ws = CrmWorkspaceContext::id();
        $nextNo = (int) DemandRequest::withoutGlobalScopes()->where('workspace_id', $ws)->max('request_no') + 1;

        // Cash in Hand drawn against this demand — never more than the pool holds.
        $cashUsed = min(max(0, (float) $request->input('cash_in_hand_used', 0)), $this->cashInHandAvailableFor());

        $demand = DemandRequest::create([
            'request_no' => $nextNo,
            'request_date' => $validated['request_date'],
            'requested_by' => $validated['requested_by'] ?? null,
            'priority' => $validated['priority'] ?? 'Normal',
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'estimated_total' => collect($items)->sum('estimated_total'),
            'vat_percentage' => $validated['vat_percentage'] ?? 0,
            'cash_in_hand_used' => round($cashUsed, 2),
            'cash_in_hand_note' => trim((string) $request->input('cash_in_hand_note')) ?: null,
            'created_by' => \Auth::guard('crm')->id(),
        ]);
        $created = $demand->items()->createMany($items);
        $this->saveItemFiles($request, $items, $created);
        $this->saveDemandAttachments($request, $demand);

        return redirect()->route('crm.demand_requests.index')
            ->with('status', 'Demand Request #' . $demand->request_no . ' saved (' . $status . ').');
    }

    /** Save per-item uploaded files, matching each created item to its original row index. */
    private function saveItemFiles(Request $request, array $items, $created): void
    {
        foreach ($created as $idx => $item) {
            $this->saveOneItemFiles($request, $item, $items[$idx]['__key'] ?? null);
        }
    }

    /** Store any newly-uploaded files for a single item row (keeps existing files intact). */
    private function saveOneItemFiles(Request $request, $item, $key): void
    {
        if ($key === null) {
            return;
        }
        $files = $request->file("items.$key.files");
        if (!$files) {
            return;
        }
        $dir = public_path('uploads/demand-requests');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        foreach ((array) $files as $file) {
            if (!$file) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension());
            $fname = 'dri_' . uniqid('', true) . ($ext ? '.' . $ext : '');
            $file->move($dir, $fname);
            $item->files()->create([
                'path' => 'uploads/demand-requests/' . $fname,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => @filesize($dir . '/' . $fname) ?: null,
            ]);
        }
    }

    /** Store any files uploaded from the create/edit form as demand attachments. */
    private function saveDemandAttachments(Request $request, DemandRequest $dr): void
    {
        $files = $request->file('files');
        if (!$files) {
            return;
        }
        $dir = public_path('uploads/demand-requests');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        foreach ((array) $files as $file) {
            if (!$file) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension());
            $fname = 'dr_' . uniqid('', true) . ($ext ? '.' . $ext : '');
            $dr->attachments()->create([
                'path' => 'uploads/demand-requests/' . $fname,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'created_by' => \Auth::guard('crm')->id(),
            ]);
            $file->move($dir, $fname);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $demand = DemandRequest::findOrFail($id);
        $validated = $this->validateRequest($request);

        $items = $this->normalizeItems($validated['items']);
        $status = $request->input('action') === 'draft' ? 'Draft' : ($demand->status === 'Draft' ? 'Submitted' : $demand->status);

        // Cash in Hand drawn against this demand — capped at the pool + what it already holds.
        $cashUsed = min(max(0, (float) $request->input('cash_in_hand_used', 0)), $this->cashInHandAvailableFor($demand));

        $demand->update([
            'request_date' => $validated['request_date'],
            'requested_by' => $validated['requested_by'] ?? null,
            'priority' => $validated['priority'] ?? 'Normal',
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'estimated_total' => collect($items)->sum('estimated_total'),
            'vat_percentage' => $validated['vat_percentage'] ?? 0,
            'cash_in_hand_used' => round($cashUsed, 2),
            'cash_in_hand_note' => trim((string) $request->input('cash_in_hand_note')) ?: null,
        ]);
        // Reconcile items in place (by position order) so existing per-item files survive edits.
        $existing = $demand->items()->orderBy('position')->orderBy('id')->get()->values();
        $keptIds = [];
        foreach ($items as $idx => $data) {
            $key = $data['__key'] ?? null;
            $fields = \Illuminate\Support\Arr::except($data, '__key');
            if (isset($existing[$idx])) {
                $item = $existing[$idx];
                $item->update($fields);
            } else {
                $item = $demand->items()->create($fields);
            }
            $keptIds[] = $item->id;
            $this->saveOneItemFiles($request, $item, $key);
        }
        $removed = $existing->pluck('id')->diff($keptIds);
        if ($removed->isNotEmpty()) {
            \App\DemandRequestItemFile::whereIn('item_id', $removed)->delete();
            $demand->items()->whereIn('id', $removed)->delete();
        }
        $this->saveDemandAttachments($request, $demand);

        // Edit + approve in one step: approver saves and approves from the edit form.
        if ($request->input('action') === 'approve' && $this->canApprove() && in_array($demand->status, ['Submitted', 'Draft'], true)) {
            $demand->update([
                'status' => 'Approved',
                'approved_by' => \Auth::guard('crm')->id(),
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            return redirect()->route('crm.demand_requests.show', $demand->id)
                ->with('status', 'Demand Request #' . $demand->request_no . ' updated & approved.');
        }

        return redirect()->route('crm.demand_requests.index')
            ->with('status', 'Demand Request #' . $demand->request_no . ' updated.');
    }

    public function destroy($id)
    {
        $this->authorizeAccess();
        $demand = DemandRequest::findOrFail($id);
        $demand->items()->delete();
        $demand->delete();

        return redirect()->route('crm.demand_requests.index')->with('status', 'Demand Request deleted.');
    }

    public function show($id)
    {
        $this->authorizeAccess();
        $dr = DemandRequest::with(['items.files', 'creator', 'approver', 'payments.files', 'attachments'])->findOrFail($id);

        // When an approver opens a Submitted demand, the "approve page" IS the editable form
        // (with a Save & Approve button) — no separate Edit click needed.
        if ($this->canApprove() && $dr->status === 'Submitted') {
            return $this->edit($id);
        }

        return view('crm.demand_requests.show', [
            'dr' => $dr,
            'canApprove' => $this->canApprove(),
            'company' => $this->companyInfo(),
            'payerSummary' => $this->payerSummary($dr),
            'cashInHand' => $this->cashInHand(),
            'payers' => self::PAYERS,
            'vendors' => \App\Vendor::orderBy('name')->pluck('name')->filter()->values(),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function approve($id)
    {
        $this->authorizeApprove();
        $dr = DemandRequest::findOrFail($id);
        if (in_array($dr->status, ['Submitted', 'Rejected'], true)) {
            $dr->update(['status' => 'Approved', 'approved_by' => \Auth::guard('crm')->id(), 'approved_at' => now(), 'rejection_reason' => null]);
        }

        return back()->with('status', 'Demand Request #' . $dr->request_no . ' approved.');
    }

    public function reject(Request $request, $id)
    {
        $this->authorizeApprove();
        $data = $request->validate(['rejection_reason' => 'nullable|string|max:500']);
        $dr = DemandRequest::findOrFail($id);
        if ($dr->status === 'Submitted') {
            $dr->update(['status' => 'Rejected', 'approved_by' => \Auth::guard('crm')->id(), 'approved_at' => now(), 'rejection_reason' => $data['rejection_reason'] ?? null]);
        }

        return back()->with('status', 'Demand Request #' . $dr->request_no . ' rejected.');
    }

    public function addPayment(Request $request, $id)
    {
        $this->authorizeAccess();
        $dr = DemandRequest::with('items')->findOrFail($id);
        if (!in_array($dr->status, ['Approved', 'Partially Paid', 'Completed'], true)) {
            return back()->with('status', 'Approve the demand before recording a payment.');
        }
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'nullable|string|max:120',
            'paid_to' => 'nullable|string|max:150',
            'note' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'item_id' => 'nullable|integer',
            'pay_type' => 'nullable|in:Account,Direct',
            'proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,csv|max:20480',
        ]);
        $itemId = null;
        if (!empty($data['item_id']) && $dr->items->firstWhere('id', (int) $data['item_id'])) {
            $itemId = (int) $data['item_id'];
        }
        $proof = ['attachment_path' => null, 'attachment_name' => null, 'attachment_mime' => null];
        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $dir = public_path('uploads/demand-requests');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $ext = strtolower($file->getClientOriginalExtension());
            $fname = 'drp_' . uniqid('', true) . ($ext ? '.' . $ext : '');
            $proof = ['attachment_path' => 'uploads/demand-requests/' . $fname, 'attachment_name' => $file->getClientOriginalName(), 'attachment_mime' => $file->getClientMimeType()];
            $file->move($dir, $fname);
        }
        $dr->payments()->create(array_merge([
            'item_id' => $itemId,
            'pay_type' => $data['pay_type'] ?? 'Account',
            'amount' => round((float) $data['amount'], 2),
            'method' => $data['method'] ?? null,
            'paid_to' => $data['paid_to'] ?? null,
            'note' => $data['note'] ?? null,
            'paid_at' => $data['paid_at'] ?? now()->toDateString(),
            'created_by' => \Auth::guard('crm')->id(),
        ], $proof));
        $this->recomputeStatus($dr);

        return back()->with('status', 'Payment of ' . number_format((float) $data['amount'], 2) . ' recorded for Demand #' . $dr->request_no . '.');
    }

    public function addPayments(Request $request, $id)
    {
        $this->authorizeAccess();
        $dr = DemandRequest::with('items')->findOrFail($id);
        if (!in_array($dr->status, ['Approved', 'Partially Paid'], true)) {
            return back()->with('status', 'Approve or reopen the demand before recording payments.');
        }
        $rows = (array) $request->input('rows', []);

        // Collect one-or-more proof files per row (new multi-file `proofs[]` + legacy `proof`).
        $filesFor = function ($i) use ($request) {
            $out = [];
            $multi = $request->file("rows.$i.proofs");
            if ($multi) {
                foreach ((array) $multi as $f) {
                    if ($f) {
                        $out[] = $f;
                    }
                }
            }
            if ($single = $request->file("rows.$i.proof")) {
                $out[] = $single;
            }

            return $out;
        };

        // At least one proof attachment is mandatory for every row that carries an amount
        // (including cross-category adjustments).
        $missing = [];
        foreach ($rows as $i => $row) {
            if (round((float) ($row['amount'] ?? 0), 2) > 0 && empty($filesFor($i))) {
                $missing[] = trim(($row['note'] ?? '') !== '' ? $row['note'] : 'row #' . ((int) $i + 1));
            }
        }
        if (!empty($missing)) {
            return back()->withInput()->withErrors([
                'proof' => 'At least one proof attachment is required for every payment you enter. Missing proof for: ' . implode(', ', $missing) . '.',
            ]);
        }
        // Cap proof files per row.
        foreach ($rows as $i => $row) {
            if (count($filesFor($i)) > 5) {
                return back()->withInput()->withErrors([
                    'proof' => 'Maximum 5 proof files allowed per payment row.',
                ]);
            }
        }
        $dir = public_path('uploads/demand-requests');
        $count = 0;
        foreach ($rows as $i => $row) {
            $amount = round((float) ($row['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }
            $itemId = null;
            if (!empty($row['item_id']) && $dr->items->firstWhere('id', (int) $row['item_id'])) {
                $itemId = (int) $row['item_id'];
            }
            $adjustFrom = trim((string) ($row['adjust_from'] ?? '')) ?: null;
            $payment = $dr->payments()->create([
                'item_id' => $itemId,
                'category' => trim((string) ($row['category'] ?? '')) ?: null,
                'adjust_from' => $adjustFrom,
                'pay_type' => (($row['pay_type'] ?? 'Account') === 'Direct') ? 'Direct' : 'Account',
                'amount' => $amount,
                'method' => $adjustFrom ? (trim((string) ($row['method'] ?? '')) ?: 'Adjustment') : ($row['method'] ?? null),
                'paid_to' => $row['paid_to'] ?? null,
                'vendor_invoice_no' => trim((string) ($row['vendor_invoice_no'] ?? '')) ?: null,
                'note' => $row['note'] ?? null,
                'paid_at' => now()->toDateString(),
                'created_by' => \Auth::guard('crm')->id(),
            ]);
            foreach ($filesFor($i) as $file) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $ext = strtolower($file->getClientOriginalExtension());
                $fname = 'drp_' . uniqid('', true) . ($ext ? '.' . $ext : '');
                $file->move($dir, $fname);
                $payment->files()->create([
                    'path' => 'uploads/demand-requests/' . $fname,
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => @filesize($dir . '/' . $fname) ?: null,
                ]);
            }
            $count++;
        }
        $this->recomputeStatus($dr);

        return back()->with('status', $count . ' payment(s) recorded for Demand #' . $dr->request_no . '.');
    }

    public function deletePayment($id, $paymentId)
    {
        $this->authorizeAccess();
        $dr = DemandRequest::findOrFail($id);
        $pmt = $dr->payments()->with('files')->where('id', $paymentId)->first();
        if ($pmt) {
            if ($pmt->attachment_path && is_file(public_path($pmt->attachment_path))) {
                @unlink(public_path($pmt->attachment_path));
            }
            foreach ($pmt->files as $f) {
                if ($f->path && is_file(public_path($f->path))) {
                    @unlink(public_path($f->path));
                }
                $f->delete();
            }
            $pmt->delete();
        }
        $this->recomputeStatus($dr);

        return back()->with('status', 'Payment removed.');
    }

    /** Status follows the money: paid 0 -> Approved, partial -> Partially Paid, full -> Completed. */
    private function recomputeStatus(DemandRequest $dr): void
    {
        $dr->load(['payments', 'items']);
        $paid = $dr->paidTotal();
        if ($dr->force_completed) {
            $dr->update(['actual_total' => $paid, 'status' => 'Completed']);
            return;
        }
        $outstanding = $dr->outstandingTotal();
        $status = $dr->status;
        if (in_array($status, ['Approved', 'Partially Paid', 'Completed'], true)) {
            if ($paid <= 0) {
                $status = 'Approved';
            } elseif ($outstanding <= 0.009) {
                $status = 'Completed';
            } else {
                $status = 'Partially Paid';
            }
        }
        $dr->update(['actual_total' => $paid, 'status' => $status]);
    }

    public function pdf($id)
    {
        $this->authorizeAccess();
        $dr = DemandRequest::with(['items', 'creator', 'approver', 'payments.files'])->findOrFail($id);
        $company = $this->companyInfo();
        $isAlMassa = CrmWorkspaceContext::id() == 2;
        $brand = [
            'is_al_massa' => $isAlMassa,
            'primary' => $isAlMassa ? '#0b2a62' : '#6c5ce7',
            'accent'  => $isAlMassa ? '#d69a00' : '#6c5ce7',
            'row'     => $isAlMassa ? '#b8d9ec' : '#e4e0ff',
            'arabic'  => $isAlMassa ? 'الماسة الملكية لصناعة العلب و التغليف ذ.م.م' : '',
            'tagline' => $isAlMassa ? 'All Cosmetics & Perfumes Hard, Soft Boxes and Paper Bags' : 'Custom Packaging Boxes with Logo',
            'phone'   => $isAlMassa ? '+971 6 579 6994, +971 56 997 0652, +971 54 793 4286' : '',
            'email'   => $isAlMassa ? 'info@almassapackaging.com' : '',
            'signatory' => $isAlMassa ? 'AMIR BASHIR' : ($company['name'] ?? ''),
            'logo'    => asset($isAlMassa ? 'al-massa-invoice-email-logo.png' : 'my-box-printing-logo.svg'),
        ];

        return view('crm.demand_requests.pdf', [
            'dr' => $dr,
            'company' => $company,
            'brand' => $brand,
            'paid' => $dr->paidTotal(),
            'outstanding' => $dr->outstandingTotal(),
        ]);
    }

    private function canApprove(): bool
    {
        $u = \Auth::guard('crm')->user();
        return $u && $u->isAdmin();
    }

    private function authorizeApprove(): void
    {
        if (!$this->canApprove()) {
            abort(403, 'Only an administrator can approve or reject demand requests.');
        }
    }

    /** Company header shown on the detail page and PDF, per workspace. */
    private function companyInfo(): array
    {
        $wsId = CrmWorkspaceContext::id();
        $map = [
            1 => ['name' => 'The Custom Boxes (TCB)', 'address' => '', 'logo' => 'my-box-printing-logo-pdf.jpg'],
            2 => ['name' => 'ALMASSA AL MALAKIYA BOXES & PACKAGING IND. LLC', 'address' => 'Shed 4, Al Diyar Building 33, Fourth Industrial St, Industrial Area 12, Sharjah UAE  |  Contact: +971 56 682 0097', 'logo' => 'al-massa-packaging-logo-pdf.jpg'],
        ];
        $info = $map[$wsId] ?? ['name' => 'Company', 'address' => '', 'logo' => null];
        $info['logo_path'] = ($info['logo'] && file_exists(public_path($info['logo']))) ? public_path($info['logo']) : null;

        return $info;
    }

    /** Spend grouped by payer (who paid) + outstanding — the "kaun kitna / kis ne pay karna" breakdown. */
    /** Money grouped by source/method (who/what it came from) - the "kaun ne kitna diya" view. */
    private function payerSummary(DemandRequest $dr): array
    {
        $rows = [];
        foreach ($dr->payments as $pmt) {
            $src = trim((string) $pmt->method) ?: 'Unspecified';
            $rows[$src] = ($rows[$src] ?? 0) + (float) $pmt->amount;
        }
        arsort($rows);

        return $rows;
    }

    public function markComplete($id)
    {
        $this->authorizeAccess();
        $dr = DemandRequest::findOrFail($id);
        if (in_array($dr->status, ['Approved', 'Partially Paid', 'Completed'], true)) {
            $dr->update(['force_completed' => true, 'status' => 'Completed']);
        }

        return back()->with('status', 'Demand Request #' . $dr->request_no . ' marked complete.');
    }

    public function reopen($id)
    {
        $this->authorizeAccess();
        $dr = DemandRequest::findOrFail($id);
        // Reopen: clear the manual-complete flag and drop to Approved so payments can resume.
        $dr->update(['force_completed' => false, 'status' => 'Approved']);
        $this->recomputeStatus($dr);

        return back()->with('status', 'Demand Request #' . $dr->request_no . ' reopened.');
    }

    public function addAttachment(Request $request, $id)
    {
        $this->authorizeAccess();
        $this->authorizeManageFiles();
        $dr = DemandRequest::findOrFail($id);
        $request->validate([
            'entries' => 'required|array|max:20',
            'entries.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,csv|max:20480',
            'entries.*.note' => 'nullable|string|max:255',
            'entries.*.amount' => 'nullable|numeric|min:0',
        ]);
        $dir = public_path('uploads/demand-requests');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $count = 0;
        foreach ((array) $request->input('entries', []) as $i => $entry) {
            $file = $request->file("entries.$i.file");
            if (!$file) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension());
            $fname = 'dr_' . uniqid('', true) . ($ext ? '.' . $ext : '');
            $dr->attachments()->create([
                'path' => 'uploads/demand-requests/' . $fname,
                'name' => $file->getClientOriginalName(),
                'note' => trim((string) ($entry['note'] ?? '')) ?: null,
                'amount' => isset($entry['amount']) && $entry['amount'] !== '' ? round((float) $entry['amount'], 2) : null,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'created_by' => \Auth::guard('crm')->id(),
            ]);
            $file->move($dir, $fname);
            $count++;
        }
        if ($count === 0) {
            return back()->withErrors(['entries' => 'Attach at least one file.']);
        }

        return back()->with('status', $count . ' attachment(s) uploaded.');
    }

    public function deleteAttachment($id, $attId)
    {
        $this->authorizeAccess();
        $this->authorizeManageFiles();
        $dr = DemandRequest::findOrFail($id);
        $att = $dr->attachments()->where('id', $attId)->first();
        if ($att) {
            $full = public_path($att->path);
            if (is_file($full)) {
                @unlink($full);
            }
            $att->delete();
        }

        return back()->with('status', 'Attachment removed.');
    }

    public function export(Request $request)
    {
        $this->authorizeAccess();
        $rows = DemandRequest::withSum('payments as paid_sum', 'amount')->withCount('items')->orderBy('request_no')->get();
        $filename = 'demand-requests-' . date('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No', 'Date', 'Requested By', 'Priority', 'Items', 'Estimated', 'Paid', 'Outstanding', 'Status']);
            foreach ($rows as $r) {
                $paid = (float) ($r->paid_sum ?? 0);
                fputcsv($out, [
                    '#' . str_pad($r->request_no, 3, '0', STR_PAD_LEFT),
                    optional($r->request_date)->format('Y-m-d'),
                    $r->requested_by,
                    $r->priority,
                    $r->items_count,
                    number_format((float) $r->estimated_total, 2, '.', ''),
                    number_format($paid, 2, '.', ''),
                    number_format((float) $r->estimated_total - $paid, 2, '.', ''),
                    $r->status,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function validateRequest(Request $request)
    {
        return $request->validate([
            'request_date' => 'required|date',
            'requested_by' => 'nullable|string|max:150',
            'priority' => 'nullable|in:Normal,Urgent',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,csv|max:20480',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.category' => 'nullable|string|max:120',
            'items.*.job_no' => 'nullable|string|max:80',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.specification' => 'nullable|string|max:255',
            'items.*.gsm' => 'nullable|string|max:60',
            'items.*.vendor_name' => 'nullable|string|max:150',
            'items.*.qty' => 'nullable|string|max:60',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'items.*.vat_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.vendor_invoice_no' => 'nullable|string|max:120',
            'items.*.estimated_total' => 'nullable|numeric|min:0',
            'items.*.pay_by' => 'nullable|in:Company,Account',
            'items.*.files' => 'nullable|array|max:5',
            'items.*.files.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,csv|max:20480',
        ]);
    }

    private function normalizeItems(array $items)
    {
        $out = [];
        $pos = 1;
        foreach ($items as $__key => $item) {
            // skip fully-empty rows
            $hasContent = collect(['category', 'job_no', 'description', 'specification', 'gsm', 'vendor_name', 'vendor_invoice_no', 'qty', 'estimated_price', 'vat_percentage', 'estimated_total'])
                ->contains(fn($k) => trim((string) ($item[$k] ?? '')) !== '');
            if (!$hasContent) {
                continue;
            }
            $price = $item['estimated_price'] !== null && $item['estimated_price'] !== '' ? round((float) $item['estimated_price'], 2) : null;
            $vat = $item['vat_percentage'] !== null && $item['vat_percentage'] !== '' ? round((float) $item['vat_percentage'], 2) : 0;
            $total = $item['estimated_total'] !== null && $item['estimated_total'] !== '' ? round((float) $item['estimated_total'], 2) : 0;
            // auto: if total not given but qty is a plain number and price present (VAT included)
            if ($total == 0 && $price !== null && is_numeric(trim((string) ($item['qty'] ?? '')))) {
                $base = (float) $item['qty'] * $price;
                $total = round($base + $base * $vat / 100, 2);
            }
            $out[] = [
                '__key' => $__key,
                'position' => $pos++,
                'category' => $item['category'] ?? null,
                'job_no' => $item['job_no'] ?? null,
                'description' => $item['description'] ?? null,
                'specification' => $item['specification'] ?? null,
                'gsm' => $item['gsm'] ?? null,
                'vendor_name' => $item['vendor_name'] ?? null,
                'vendor_invoice_no' => $item['vendor_invoice_no'] ?? null,
                'qty' => $item['qty'] ?? null,
                'estimated_price' => $price,
                'vat_percentage' => $vat,
                'estimated_total' => $total,
                'pay_by' => (($item['pay_by'] ?? '') === 'Account') ? 'Account' : 'Company',
            ];
        }
        if (empty($out)) {
            $out[] = ['__key' => null, 'position' => 1, 'category' => null, 'description' => null, 'estimated_total' => 0];
        }
        return $out;
    }
}
