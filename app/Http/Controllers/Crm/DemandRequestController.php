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

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $query = DemandRequest::with(['creator', 'items', 'payments'])->orderBy('request_date', 'desc')->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('requested_by', 'like', "%{$s}%")
                    ->orWhere('request_no', 'like', "%{$s}%")
                    ->orWhereHas('items', function ($iq) use ($s) {
                        $iq->where('description', 'like', "%{$s}%")->orWhere('category', 'like', "%{$s}%")->orWhere('job_no', 'like', "%{$s}%");
                    });
            });
        }

        $requests = $query->paginate(20)->appends($request->all());

        $all = DemandRequest::with(['items', 'payments'])->get();
        $summary = [
            'total' => $all->count(),
            'estimated' => (float) $all->sum('estimated_total'),
            'paid' => (float) $all->sum(fn($d) => $d->paidTotal()),
            'outstanding' => round((float) $all->sum(fn($d) => $d->outstandingTotal()), 2),
            'balance' => round((float) $all->sum(fn($d) => $d->paidTotal() + $d->writeOffTotal() - (float) $d->estimated_total), 2),
        ];

        return view('crm.demand_requests.index', [
            'requests' => $requests,
            'summary' => $summary,
            'priorities' => self::PRIORITIES,
        ]);
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
            'items' => [['category' => 'Consumable', 'qty' => '', 'estimated_price' => '', 'estimated_total' => '']],
        ]);
    }

    public function edit($id)
    {
        $this->authorizeAccess();
        $demandRequest = DemandRequest::with('items')->findOrFail($id);

        return view('crm.demand_requests.create', [
            'categories' => self::CATEGORIES,
            'priorities' => self::PRIORITIES,
            'defaultRequestedBy' => $demandRequest->requested_by,
            'demandRequest' => $demandRequest,
            'items' => $demandRequest->items->map(function ($it) {
                return [
                    'category' => $it->category, 'job_no' => $it->job_no, 'description' => $it->description,
                    'specification' => $it->specification, 'qty' => $it->qty,
                    'estimated_price' => $it->estimated_price, 'estimated_total' => $it->estimated_total,
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

        $demand = DemandRequest::create([
            'request_no' => $nextNo,
            'request_date' => $validated['request_date'],
            'requested_by' => $validated['requested_by'] ?? null,
            'priority' => $validated['priority'] ?? 'Normal',
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'estimated_total' => collect($items)->sum('estimated_total'),
            'created_by' => \Auth::guard('crm')->id(),
        ]);
        $demand->items()->createMany($items);

        return redirect()->route('crm.demand_requests.index')
            ->with('status', 'Demand Request #' . $demand->request_no . ' saved (' . $status . ').');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $demand = DemandRequest::findOrFail($id);
        $validated = $this->validateRequest($request);

        $items = $this->normalizeItems($validated['items']);
        $status = $request->input('action') === 'draft' ? 'Draft' : ($demand->status === 'Draft' ? 'Submitted' : $demand->status);

        $demand->update([
            'request_date' => $validated['request_date'],
            'requested_by' => $validated['requested_by'] ?? null,
            'priority' => $validated['priority'] ?? 'Normal',
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'estimated_total' => collect($items)->sum('estimated_total'),
        ]);
        $demand->items()->delete();
        $demand->items()->createMany($items);

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
        $dr = DemandRequest::with(['items', 'creator', 'approver', 'payments', 'attachments'])->findOrFail($id);

        return view('crm.demand_requests.show', [
            'dr' => $dr,
            'canApprove' => $this->canApprove(),
            'company' => $this->companyInfo(),
            'payerSummary' => $this->payerSummary($dr),
            'payers' => self::PAYERS,
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
            $proof = ['attachment_path' => null, 'attachment_name' => null, 'attachment_mime' => null];
            $file = $request->file("rows.$i.proof");
            if ($file) {
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
                'pay_type' => (($row['pay_type'] ?? 'Account') === 'Direct') ? 'Direct' : 'Account',
                'amount' => $amount,
                'method' => $row['method'] ?? null,
                'paid_to' => $row['paid_to'] ?? null,
                'note' => $row['note'] ?? null,
                'paid_at' => now()->toDateString(),
                'created_by' => \Auth::guard('crm')->id(),
            ], $proof));
            $count++;
        }
        $this->recomputeStatus($dr);

        return back()->with('status', $count . ' payment(s) recorded for Demand #' . $dr->request_no . '.');
    }

    public function deletePayment($id, $paymentId)
    {
        $this->authorizeAccess();
        $dr = DemandRequest::findOrFail($id);
        $pmt = $dr->payments()->where('id', $paymentId)->first();
        if ($pmt) {
            if ($pmt->attachment_path && is_file(public_path($pmt->attachment_path))) {
                @unlink(public_path($pmt->attachment_path));
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
        $dr = DemandRequest::with(['items', 'creator', 'approver', 'payments'])->findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.demand_requests.pdf', [
            'dr' => $dr,
            'company' => $this->companyInfo(),
            'paid' => $dr->paidTotal(),
            'outstanding' => $dr->outstandingTotal(),
        ]);

        return $pdf->stream('demand-request-' . str_pad($dr->request_no, 3, '0', STR_PAD_LEFT) . '.pdf');
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
            1 => ['name' => 'The Custom Boxes (TCB)', 'address' => '', 'logo' => 'tcb-crm-logo.png'],
            2 => ['name' => 'ALMASSA AL MALAKIYA BOXES & PACKAGING IND. LLC', 'address' => 'Shed 4, Al Diyar Building 33, Fourth Industrial St, Industrial Area 12, Sharjah UAE  |  Contact: +971 56 682 0097', 'logo' => null],
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
        $dr = DemandRequest::findOrFail($id);
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,csv|max:20480',
        ]);
        $dir = public_path('uploads/demand-requests');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        foreach ((array) $request->file('files', []) as $file) {
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

        return back()->with('status', 'Attachment(s) uploaded.');
    }

    public function deleteAttachment($id, $attId)
    {
        $this->authorizeAccess();
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
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.category' => 'nullable|string|max:120',
            'items.*.job_no' => 'nullable|string|max:80',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.specification' => 'nullable|string|max:255',
            'items.*.qty' => 'nullable|string|max:60',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'items.*.estimated_total' => 'nullable|numeric|min:0',
        ]);
    }

    private function normalizeItems(array $items)
    {
        $out = [];
        $pos = 1;
        foreach ($items as $item) {
            // skip fully-empty rows
            $hasContent = collect(['category', 'job_no', 'description', 'specification', 'qty', 'estimated_price', 'estimated_total'])
                ->contains(fn($k) => trim((string) ($item[$k] ?? '')) !== '');
            if (!$hasContent) {
                continue;
            }
            $price = $item['estimated_price'] !== null && $item['estimated_price'] !== '' ? round((float) $item['estimated_price'], 2) : null;
            $total = $item['estimated_total'] !== null && $item['estimated_total'] !== '' ? round((float) $item['estimated_total'], 2) : 0;
            // auto: if total not given but qty is a plain number and price present
            if ($total == 0 && $price !== null && is_numeric(trim((string) ($item['qty'] ?? '')))) {
                $total = round((float) $item['qty'] * $price, 2);
            }
            $out[] = [
                'position' => $pos++,
                'category' => $item['category'] ?? null,
                'job_no' => $item['job_no'] ?? null,
                'description' => $item['description'] ?? null,
                'specification' => $item['specification'] ?? null,
                'qty' => $item['qty'] ?? null,
                'estimated_price' => $price,
                'estimated_total' => $total,
            ];
        }
        if (empty($out)) {
            $out[] = ['position' => 1, 'category' => null, 'description' => null, 'estimated_total' => 0];
        }
        return $out;
    }
}
