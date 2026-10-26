<?php

namespace App\Http\Controllers\Crm;

use App\CrmEmail;
use App\CustomerSale;
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
        // Purchase invoices (Purchase Invoices tab) are payables too.
        $purchaseInvoices = \App\CrmPurchaseInvoice::with('payments')->get();
        $payableTotals = [
            'total'   => (float) $payableAgg->total + (float) $purchaseInvoices->sum('total'),
            'paid'    => (float) $payableAgg->paid + (float) $purchaseInvoices->sum(fn ($inv) => $inv->paidTotal()),
            'balance' => (float) $payableAgg->balance + (float) $purchaseInvoices->sum(fn ($inv) => max(0, $inv->balanceDue())),
            'count'   => (int)   $payableAgg->c + $purchaseInvoices->count(),
        ];
        $receivableAll = $this->receivableRows();
        $income = \App\CrmIncomeEntry::with('customer')->get(); // rent / other income dues
        // Unlinked Customer Sales are separate from workflow invoices. Linked sales already
        // appear through their CRM order and must not be counted a second time.
        $customerSales = CustomerSale::with('customer')->whereNull('crm_email_id')->get();
        $receivableTotals = [
            'total' => $receivableAll->sum('total') + (float) $income->sum('amount_due') + (float) $customerSales->sum('total_amount'),
            'received' => $receivableAll->where('is_received', true)->sum('total') + (float) $income->sum('amount_paid') + (float) $customerSales->sum('paid_amount'),
            'pending' => $receivableAll->where('is_received', false)->sum('total') + (float) $income->sum(fn ($e) => $e->balance) + (float) $customerSales->sum('balance_amount'),
            'count' => $receivableAll->count() + $income->count() + $customerSales->count(),
        ];

        // Party dropdown options: customers (receivable) and/or vendors (payable),
        // built from the names that actually appear in the ledger for this tab.
        $vendorNames = $payableTotals ? VendorPurchase::get()->toBase()
                ->map(fn ($p) => $p->vendor_name ?: optional($p->vendor)->name)
                ->concat($purchaseInvoices->pluck('vendor_name'))
                ->filter()->unique()->sort()->values() : collect();
        $customerNames = $receivableAll->toBase()
                ->map(fn ($r) => $r->lead->client_name ?? null)
                ->concat($income->map(fn ($e) => optional($e->customer)->name))
                ->concat($customerSales->map(fn ($sale) => optional($sale->customer)->name))
                ->filter()->unique()->sort()->values();
        if ($tab === 'payable') {
            $partyOptions = $vendorNames;
            $partyLabel = 'Vendor';
        } elseif ($tab === 'receivable') {
            $partyOptions = $customerNames;
            $partyLabel = 'Customer';
        } else {
            $partyOptions = collect($customerNames)->concat($vendorNames)->unique()->sort()->values();
            $partyLabel = 'Customer / Vendor';
        }

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

        return view('crm.general_ledger.index', array_merge(compact('tab', 'entries', 'payableTotals', 'receivableTotals', 'partyOptions', 'partyLabel'), [
            'search' => $filters['search'],
            'dateFrom' => $filters['date_from'],
            'dateTo' => $filters['date_to'],
            'status' => $filters['status'],
            'party' => $filters['party'],
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

        // No customer/vendor picked: export one totals line per party instead of every entry.
        if (empty($filters['party'])) {
            $meta['partyLabel'] = ['all' => 'Customer / Vendor', 'payable' => 'Vendor', 'receivable' => 'Customer'][$tab];
            $meta['paidLabel'] = ['all' => 'Received / Paid', 'payable' => 'Paid (Out)', 'receivable' => 'Received (In)'][$tab];
            $summary = $this->partySummary($entries);
            $filename = 'general-ledger-'.$tab.'-'.($tab === 'payable' ? 'vendors' : ($tab === 'receivable' ? 'customers' : 'parties')).'-summary-'.now()->format('Y-m-d');
            if ($request->input('format') === 'pdf') {
                return \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.general_ledger.export_summary_pdf', ['rows' => $summary, 'meta' => $meta])
                    ->setPaper('a4', 'landscape')->download($filename.'.pdf');
            }
            return response()->view('crm.general_ledger.export_summary_excel', ['rows' => $summary, 'meta' => $meta])
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'.xls"');
        }

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

    /** One row per customer/vendor: how many entries, total, paid/received and balance. */
    protected function partySummary($entries)
    {
        return $entries->groupBy(fn ($entry) => $entry->type.'|'.$entry->party)
            ->map(function ($group) {
                $first = $group->first();
                return (object) [
                    'type' => $first->type,
                    'party' => $first->party,
                    'count' => $group->count(),
                    'total' => (float) $group->sum('total'),
                    'paid' => (float) $group->sum('paid'),
                    'balance' => (float) $group->sum('balance'),
                    'currency' => $group->pluck('currency')->filter()->unique()->implode(' / ') ?: 'AED',
                    'last_date' => $group->max(fn ($entry) => \Carbon\Carbon::parse($entry->date)),
                ];
            })
            ->sortBy(fn ($row) => [$row->type, mb_strtolower($row->party)])
            ->values();
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
            // Specific customer (receivable) or vendor (payable), matched by party name.
            'party' => $request->input('party') ? trim((string) $request->input('party')) : null,
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
            foreach (\App\CrmPurchaseInvoice::with(['items', 'payments'])->get() as $invoice) {
                if ($filters['date_from'] && $invoice->issue_date->lt(\Carbon\Carbon::parse($filters['date_from'])->startOfDay())) continue;
                if ($filters['date_to'] && $invoice->issue_date->gt(\Carbon\Carbon::parse($filters['date_to'])->endOfDay())) continue;
                $paid = $invoice->paidTotal();
                $balance = max(0, $invoice->balanceDue());
                $status = $balance <= 0.009 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
                $entries->push((object) [
                    'type' => 'payable',
                    'date' => $invoice->issue_date,
                    'party' => $invoice->vendor_name ?: '—',
                    'ref' => $invoice->number . ($invoice->reference ? ' · ' . $invoice->reference : ''),
                    'detail' => 'Purchase Invoice — ' . ($invoice->description ?: $invoice->items->pluck('name')->implode(', ')),
                    'total' => (float) $invoice->total,
                    'paid' => $paid,
                    'balance' => $balance,
                    'currency' => $invoice->currency ?: 'AED',
                    'status' => $status,
                    'status_label' => ucfirst($status),
                    'status_tone' => $status === 'paid' ? 'ok' : ($status === 'partial' ? 'part' : 'due'),
                    'term' => $invoice->payments->pluck('method')->filter()->unique()->implode(', ') ?: null,
                    'settled_at' => $balance <= 0.009 ? optional($invoice->payments->last())->paid_at : null,
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
            foreach (CustomerSale::with('customer')->whereNull('crm_email_id')->get() as $sale) {
                $date = $sale->order_date;
                if ($filters['date_from'] && $date->lt(\Carbon\Carbon::parse($filters['date_from'])->startOfDay())) continue;
                if ($filters['date_to'] && $date->gt(\Carbon\Carbon::parse($filters['date_to'])->endOfDay())) continue;
                $total = (float) $sale->total_amount;
                $paid = (float) $sale->paid_amount;
                $balance = (float) $sale->balance_amount;
                $status = $balance > 0 ? ($paid > 0 ? 'partial' : 'pending') : ($paid > 0 ? 'received' : 'pending');
                $entries->push((object) [
                    'type' => 'receivable',
                    'date' => $date,
                    'party' => optional($sale->customer)->name ?: '—',
                    'ref' => $sale->order_number,
                    'detail' => $sale->item_name . ($sale->description ? ' · ' . $sale->description : ''),
                    'total' => $total,
                    'paid' => $paid,
                    'balance' => $balance,
                    'currency' => $sale->currency ?: 'AED',
                    'status' => $status,
                    'status_label' => $status === 'received' ? 'Received' : ($status === 'partial' ? 'Partial' : ($total == 0 && $sale->quantity === null ? 'Price pending' : 'Pending')),
                    'status_tone' => $status === 'received' ? 'ok' : ($status === 'partial' ? 'part' : 'due'),
                    'term' => $sale->payment_method,
                    'settled_at' => null,
                ]);
            }
            // Rent / other income: one receivable line per month due.
            foreach (\App\CrmIncomeEntry::with('customer')->get() as $e) {
                $date = \Carbon\Carbon::parse($e->due_date);
                if ($filters['date_from'] && $date->lt(\Carbon\Carbon::parse($filters['date_from'])->startOfDay())) continue;
                if ($filters['date_to'] && $date->gt(\Carbon\Carbon::parse($filters['date_to'])->endOfDay())) continue;
                $st = $e->status; // paid | partial | overdue | due
                $entries->push((object) [
                    'type' => 'receivable',
                    'date' => $date,
                    'party' => optional($e->customer)->name ?: '—',
                    'ref' => ($e->income_head ?: 'Income') . ' ' . $e->period_label,
                    'detail' => ($e->income_head ?: 'Other income') . ' — ' . $e->period_label . ($e->reference ? ' · ' . $e->reference : ''),
                    'total' => (float) $e->amount_due,
                    'paid' => (float) $e->amount_paid,
                    'balance' => $e->balance,
                    'currency' => optional($e->customer)->currency ?: 'AED',
                    'status' => $st === 'paid' ? 'received' : ($st === 'partial' ? 'partial' : 'pending'),
                    'status_label' => $st === 'paid' ? 'Received' : ($st === 'partial' ? 'Partial' : ($st === 'overdue' ? 'Overdue' : 'Due')),
                    'status_tone' => $st === 'paid' ? 'ok' : ($st === 'partial' ? 'part' : 'due'),
                    'term' => $e->payment_method ?: 'Monthly',
                    'settled_at' => $e->paid_at,
                ]);
            }
        }

        if ($filters['status'] !== 'all') {
            if ($tab === 'all' && in_array($filters['status'], ['payable', 'receivable'])) {
                $entries = $entries->where('type', $filters['status']);
            } elseif ($tab === 'receivable' && $filters['status'] === 'pending') {
                // The Pending card is the outstanding balance, including partially paid items.
                $entries = $entries->filter(fn ($entry) => $entry->balance > 0);
            } else {
                $entries = $entries->where('status', $filters['status']);
            }
        }
        if (!empty($filters['party'])) {
            $party = $filters['party'];
            $entries = $entries->filter(function ($entry) use ($party) {
                return (string) $entry->party === $party;
            });
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
