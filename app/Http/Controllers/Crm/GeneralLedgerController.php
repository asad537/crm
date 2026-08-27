<?php

namespace App\Http\Controllers\Crm;

use App\CrmEmail;
use App\Http\Controllers\Controller;
use App\SalesOrder;
use App\VendorPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneralLedgerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeLedger();

        $tab = $request->input('tab', 'all');
        if (!in_array($tab, ['payable', 'receivable', 'all'])) $tab = 'payable';
        $filters = $this->filters($request);

        // Payable totals — merged into ONE aggregate query (was 4 separate SELECTs).
        $payableAgg = VendorPurchase::selectRaw('
                COALESCE(SUM(total_amount),0)   as total,
                COALESCE(SUM(paid_amount),0)    as paid,
                COALESCE(SUM(balance_amount),0) as balance,
                COUNT(*) as c
            ')->first();
        $payableTotals = [
            'total'   => (float) $payableAgg->total,
            'paid'    => (float) $payableAgg->paid,
            'balance' => (float) $payableAgg->balance,
            'count'   => (int)   $payableAgg->c,
        ];
        $receivableAll = $this->receivableRows();
        $receivableTotals = [
            'total' => $receivableAll->sum('total'),
            'received' => $receivableAll->where('is_received', true)->sum('total'),
            'pending' => $receivableAll->where('is_received', false)->sum('total'),
            'count' => $receivableAll->count(),
        ];

        $allEntries = $this->entriesFor($tab, $filters);
        $perPage = 20;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $entries = new \Illuminate\Pagination\LengthAwarePaginator(
            $allEntries->forPage($page, $perPage)->values(),
            $allEntries->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('crm.general_ledger.index', array_merge(compact('tab', 'entries', 'payableTotals', 'receivableTotals'), [
            'search' => $filters['search'],
            'dateFrom' => $filters['date_from'],
            'dateTo' => $filters['date_to'],
            'status' => $filters['status'],
        ]));
    }

    public function export(Request $request)
    {
        $this->authorizeLedger();

        $tab = $request->input('tab', 'all');
        if (!in_array($tab, ['payable', 'receivable', 'all'])) $tab = 'payable';
        $filters = $this->filters($request);
        $entries = $this->entriesFor($tab, $filters);
        if ($entries->isEmpty()) {
            return redirect()->route('crm.general_ledger.index', $request->except('format'))
                ->with('error', 'No ledger entries match the selected filters.');
        }

        $workspace = optional($request->attributes->get('crm_workspace'));
        $meta = [
            'tab' => $tab,
            'tabLabel' => ['all' => 'All Entries', 'payable' => 'Accounts Payable', 'receivable' => 'Accounts Receivable'][$tab],
            'workspaceName' => $workspace->name ?: 'CRM',
            'isAlMassa' => $workspace->slug === 'mybox-packaging-app',
            'generatedAt' => now()->format('d M Y, h:i A'),
            'rangeLabel' => trim(($filters['date_from'] ? 'From '.$filters['date_from'] : '').' '.($filters['date_to'] ? 'To '.$filters['date_to'] : '')) ?: 'All dates',
            'totals' => [
                'amount' => $entries->sum('total'),
                'paid' => $entries->sum('paid'),
                'balance' => $entries->sum('balance'),
            ],
        ];

        $filename = 'general-ledger-'.$tab.'-'.now()->format('Y-m-d');
        if ($request->input('format') === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.general_ledger.export_pdf', ['entries' => $entries, 'meta' => $meta])
                ->setPaper('a4', 'landscape');
            return $pdf->download($filename.'.pdf');
        }
        return response()->view('crm.general_ledger.export_excel', ['entries' => $entries, 'meta' => $meta])
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'.xls"');
    }

    protected function authorizeLedger()
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can view the general ledger.');
        }
    }

    protected function filters(Request $request)
    {
        return [
            'search' => trim((string) $request->input('search', '')),
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
            'status' => $request->input('status') ?: 'all',
        ];
    }

    /** Normalized receivable rows: sales-workflow orders + manual offline orders. */
    protected function receivableRows()
    {
        $rows = SalesOrder::with(['lead', 'agent'])->whereHas('lead')->get()->map(function ($order) {
            $lead = $order->lead;
            $total = (float) ($lead->order_price ?? 0) * (float) ($lead->order_quantity ?? 0);
            $isReceived = in_array($order->payment_status, ['received', 'paid'])
                || $order->final_payment_received_at !== null;
            return (object) [
                'order' => $order,
                'lead' => $lead,
                'total' => $total,
                'is_received' => $isReceived,
            ];
        });

        // Manual offline orders have no SalesOrder; pull them straight from the order record.
        // Prefer the line-items subtotal (exact) over the legacy weighted-average figures.
        $manual = CrmEmail::where('source', 'manual_offline_order')->with('orderItems')->get()->map(function ($email) {
            $itemsTotal = (float) $email->orderItems->sum('line_total');
            $total = $itemsTotal > 0
                ? $itemsTotal
                : (float) ($email->order_price ?? 0) * (float) ($email->order_quantity ?? 0);
            return (object) [
                'order' => $email,        // CrmEmail stands in for the order (payment_term etc. resolve to null)
                'lead' => $email,
                'total' => $total,
                'is_received' => $email->payment_status === 'Paid',
            ];
        });

        return $rows->concat($manual);
    }

    /** Unified, filtered ledger entries for a tab — used by both the page and exports. */
    protected function entriesFor($tab, array $filters)
    {
        $entries = collect();

        if (in_array($tab, ['payable', 'all'])) {
            $query = VendorPurchase::with('vendor');
            if ($filters['date_from']) $query->whereDate('purchase_date', '>=', $filters['date_from']);
            if ($filters['date_to']) $query->whereDate('purchase_date', '<=', $filters['date_to']);
            foreach ($query->get() as $purchase) {
                $paid = (float) $purchase->paid_amount;
                $balance = (float) $purchase->balance_amount;
                $status = $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
                $entries->push((object) [
                    'type' => 'payable',
                    'date' => $purchase->purchase_date ?: $purchase->created_at,
                    'party' => $purchase->vendor_name ?: (optional($purchase->vendor)->name ?: '—'),
                    'ref' => $purchase->invoice_number ?: '—',
                    'detail' => $purchase->item_name ?: ($purchase->category ?: '—'),
                    'total' => (float) $purchase->total_amount,
                    'paid' => $paid,
                    'balance' => $balance,
                    'currency' => $purchase->currency ?: 'AED',
                    'status' => $status,
                    'status_label' => ucfirst($status),
                    'status_tone' => $status === 'paid' ? 'ok' : ($status === 'partial' ? 'part' : 'due'),
                    'term' => $purchase->payment_method ?: null,
                    'settled_at' => null,
                ]);
            }
        }

        if (in_array($tab, ['receivable', 'all'])) {
            foreach ($this->receivableRows() as $row) {
                $order = $row->order;
                $date = $order->created_at;
                if ($filters['date_from'] && $date->lt(\Carbon\Carbon::parse($filters['date_from'])->startOfDay())) continue;
                if ($filters['date_to'] && $date->gt(\Carbon\Carbon::parse($filters['date_to'])->endOfDay())) continue;
                $status = $row->is_received ? 'received' : ($order->payment_status === 'approved' ? 'approved' : 'pending');
                $entries->push((object) [
                    'type' => 'receivable',
                    'date' => $date,
                    'party' => $row->lead->client_name ?: '—',
                    'ref' => $row->lead->order_invoice_number ?: ($row->lead->workflow_number ?? ('#'.$row->lead->id)),
                    'detail' => $row->lead->product_name ?: '—',
                    'total' => $row->total,
                    'paid' => $row->is_received ? $row->total : 0.0,
                    'balance' => $row->is_received ? 0.0 : $row->total,
                    'currency' => $row->lead->invoice_currency ?: 'AED',
                    'status' => $status,
                    'status_label' => $status === 'received' ? 'Received' : ($status === 'approved' ? 'Advance Approved' : 'Pending'),
                    'status_tone' => $status === 'received' ? 'ok' : ($status === 'approved' ? 'part' : 'due'),
                    'term' => trim(($order->payment_term ? ucwords(str_replace('_', ' ', $order->payment_term)) : '').($order->credit_days ? ' · '.$order->credit_days.'d' : '')) ?: null,
                    'settled_at' => $order->final_payment_received_at ?: $order->balance_received_at,
                ]);
            }
        }

        if ($filters['status'] !== 'all') {
            if ($tab === 'all' && in_array($filters['status'], ['payable', 'receivable'])) {
                $entries = $entries->where('type', $filters['status']);
            } else {
                $entries = $entries->where('status', $filters['status']);
            }
        }
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $entries = $entries->filter(function ($entry) use ($search) {
                return stripos((string) $entry->party, $search) !== false
                    || stripos((string) $entry->ref, $search) !== false
                    || stripos((string) $entry->detail, $search) !== false;
            });
        }

        return $entries->sortByDesc('date')->values();
    }
}
