<?php

namespace App\Http\Controllers\Crm;

use App\CrmDirectReceipt;
use App\CrmEmail;
use App\CrmIncomeEntry;
use App\CrmOrderPayment;
use App\CrmUser;
use App\CustomerSale;
use App\Http\Controllers\Controller;
use App\Support\CrmWorkspaceContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Al Massa "Receipts": every payment received from customers, newest first.
 * Sources mirror the General Ledger's receivable side so nothing is counted twice:
 * invoice payments (+ legacy opening amounts), unlinked customer sales, and rent / other income;
 * plus money received without an invoice ("Make Receipt" → Other income).
 */
class ReceiptController extends Controller
{
    const SOURCES = [
        'invoice' => 'Invoice Payment',
        'opening' => 'Invoice (Opening)',
        'sale' => 'Customer Sale',
        'income' => 'Other Income',
        'direct' => 'Direct Receipt',
        'account' => 'On Account',
    ];

    public function index(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can view receipts.');
        }

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
            'source' => array_key_exists($request->input('source'), self::SOURCES) ? $request->input('source') : null,
            'method' => $request->input('method') ? trim((string) $request->input('method')) : null,
            'customer' => $request->input('customer') ? trim((string) $request->input('customer')) : null,
        ];

        $all = $this->receipts();
        $receipts = $this->applyFilters($all, $filters);

        $monthStart = now()->startOfMonth();
        $totals = [
            'received' => (float) $receipts->sum('amount'),
            'count' => $receipts->count(),
            'month' => (float) $all->filter(fn ($r) => $r->date->gte($monthStart))->sum('amount'),
            'month_count' => $all->filter(fn ($r) => $r->date->gte($monthStart))->count(),
            'last' => $all->first(),
        ];
        $methodOptions = $all->pluck('method')->filter()->unique()->sort()->values();
        $customerOptions = $all->pluck('customer')->filter(fn ($c) => $c !== '—')->unique()->sort()->values();

        $perPage = 25;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $entries = new \Illuminate\Pagination\LengthAwarePaginator(
            $receipts->forPage($page, $perPage)->values(),
            $receipts->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('crm.receipts.index', [
            'entries' => $entries,
            'totals' => $totals,
            'filters' => $filters,
            'sources' => self::SOURCES,
            'methodOptions' => $methodOptions,
            'customerOptions' => $customerOptions,
            'canDelete' => $user->isAdmin() || $user->isAccounts(),
        ] + self::makeReceiptOptions());
    }

    /**
     * Lists the "Make Receipt" dialog needs (crm.receipts._make_receipt): every customer, and everything with money
     * still due — Al Massa invoices, customer sales and rent / other income dues.
     */
    public static function makeReceiptOptions(): array
    {
        $open = collect();
        // Al Massa invoices: the receipt is saved as a payment on the invoice.
        $invoices = CrmEmail::with(['orderItems', 'orderPayments'])
            ->where(fn ($q) => $q->where('status', 'Order Done')->orWhereHas('salesOrder'))->get();
        foreach ($invoices as $o) {
            if ($o->orderBalanceDue() <= 0.009) continue;
            $open->push(['type' => 'order', 'id' => $o->id, 'customer' => trim($o->client_name) ?: 'Unknown customer',
                'ref' => $o->order_invoice_number ? 'INV-' . $o->order_invoice_number : '#' . $o->id, 'kind' => 'Invoice',
                'date' => optional($o->created_at)->format('d M Y'), 'total' => round($o->orderInvoiceTotal(), 2), 'due' => round($o->orderBalanceDue(), 2),
                'currency' => $o->invoice_currency ?: 'AED']);
        }
        // Customer sales not linked to an invoice.
        foreach (CustomerSale::with('customer')->whereNull('crm_email_id')->where('balance_amount', '>', 0.009)->get() as $s) {
            $open->push(['type' => 'sale', 'id' => $s->id, 'customer' => trim(optional($s->customer)->name) ?: 'Unknown customer',
                'ref' => $s->order_number, 'kind' => 'Sale', 'date' => optional($s->order_date)->format('d M Y'),
                'total' => round((float) $s->total_amount, 2), 'due' => round((float) $s->balance_amount, 2), 'currency' => $s->currency ?: 'AED']);
        }
        // Rent / other income dues.
        foreach (CrmIncomeEntry::with('customer')->get() as $e) {
            if ($e->balance <= 0.009) continue;
            $open->push(['type' => 'income', 'id' => $e->id, 'customer' => trim(optional($e->customer)->name) ?: 'Unknown customer',
                'ref' => ($e->income_head ?: 'Income') . ' ' . $e->period_label, 'kind' => $e->income_head ?: 'Income',
                'date' => optional($e->due_date)->format('d M Y'), 'total' => round((float) $e->amount_due, 2), 'due' => $e->balance,
                'currency' => optional($e->customer)->currency ?: 'AED']);
        }
        $open = $open->sortBy(fn ($i) => [mb_strtolower($i['customer']), $i['date']])->values();

        // Every customer, those who owe money first, with what they owe.
        $owed = $open->groupBy('customer')->map(fn ($g) => round((float) $g->sum('due'), 2));
        $customers = \App\CrmCustomer::orderBy('name')->pluck('name')->map(fn ($n) => trim($n))->concat($owed->keys())->filter()->unique()
            ->map(fn ($name) => ['name' => $name, 'due' => (float) ($owed[$name] ?? 0)])
            ->sortBy(fn ($c) => [$c['due'] > 0 ? 0 : 1, mb_strtolower($c['name'])])->values();
        // Categories typed on other-income receipts before (a payment on a customer's account is not income).
        $used = CrmDirectReceipt::distinct()->orderBy('category')->pluck('category')
            ->reject(fn ($category) => $category === CrmDirectReceipt::ON_ACCOUNT_CATEGORY);

        return [
            'openInvoices' => $open,
            'receiptCustomers' => $customers,
            'receiptCategories' => collect(CrmDirectReceipt::CATEGORIES)->concat($used)->unique()->values(),
            'receiptMethods' => CrmDirectReceipt::METHODS,
            'payerTypes' => CrmDirectReceipt::PAYER_TYPES,
            'payerCustomers' => $customers->pluck('name'),
        ];
    }

    /**
     * "Make Receipt" from a customer: against a customer sale or a rent / other income due (Al Massa invoices go to
     * the invoice's own payment action), or "on account" when no invoice is picked — that reduces what the customer
     * owes (or is kept as their advance). Each keeps its date, method and bank / cash account.
     */
    public function receive(Request $request)
    {
        $user = Auth::guard('crm')->user();
        abort_unless($user->isAccounts() || $user->isSalesManager() || $user->isAdmin(), 403, 'Only accounts and admins can record receipts.');
        $data = $request->validate([
            'doc_type' => 'required|in:sale,income,account',
            'doc_id' => 'required_unless:doc_type,account|nullable|integer',
            'customer' => 'required_if:doc_type,account|nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'paid_at' => 'required|date|before_or_equal:today',
            'method' => 'nullable|string|max:60',
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:500',
        ]);
        $amount = round((float) $data['amount'], 2);
        $account = \App\CrmBankAccount::fromRequest($request);

        if ($data['doc_type'] === 'account') {
            $receipt = CrmDirectReceipt::create(array_merge([
                'receipt_date' => $data['paid_at'], 'kind' => 'on_account', 'category' => CrmDirectReceipt::ON_ACCOUNT_CATEGORY,
                'description' => 'Payment on account' . (!empty($data['note']) ? ' — ' . $data['note'] : ''),
                'payer_type' => 'customer', 'payer_name' => trim($data['customer']), 'amount' => $amount, 'currency' => 'AED',
                'method' => $data['method'] ?? null, 'reference' => $data['reference'] ?? null, 'note' => $data['note'] ?? null, 'created_by' => $user->id,
            ], $account));

            return redirect()->route('crm.receipts.index')->with('success', 'Receipt ' . $receipt->number . ' of ' . number_format($amount, 2) . ' from '
                . $receipt->payer_name . ' recorded on account (taken off what they owe).');
        }

        if ($data['doc_type'] === 'sale') {
            $sale = CustomerSale::whereNull('crm_email_id')->findOrFail($data['doc_id']);
            if ($amount > (float) $sale->balance_amount + 0.009) {
                return back()->withInput()->withErrors(['amount' => 'Only ' . number_format((float) $sale->balance_amount, 2) . ' is due on ' . $sale->order_number . '.']);
            }
            \Illuminate\Support\Facades\DB::transaction(function () use ($sale, $amount, $data, $account, $user) {
                \App\CustomerSalePayment::create(array_merge(['customer_sale_id' => $sale->id, 'amount' => $amount, 'paid_at' => $data['paid_at'],
                    'method' => $data['method'] ?? null, 'reference' => $data['reference'] ?? null, 'note' => $data['note'] ?? null, 'created_by' => $user->id], $account));
                $paid = round((float) $sale->paid_amount + $amount, 2);
                $balance = max(0, round((float) $sale->total_amount - $paid, 2));
                $sale->update(['paid_amount' => $paid, 'balance_amount' => $balance, 'payment_status' => $balance > 0 ? 'Partial' : 'Paid']);
            });
            $label = $sale->order_number;
        } else {
            $entry = CrmIncomeEntry::with('customer')->findOrFail($data['doc_id']);
            if ($amount > $entry->balance + 0.009) {
                return back()->withInput()->withErrors(['amount' => 'Only ' . number_format($entry->balance, 2) . ' is due for ' . $entry->period_label . '.']);
            }
            $entry->update(array_merge([
                'amount_paid' => round((float) $entry->amount_paid + $amount, 2), 'paid_at' => $data['paid_at'],
                'payment_method' => $data['method'] ?? $entry->payment_method, 'reference' => $data['reference'] ?? $entry->reference,
                'paid_recorded_by' => $user->id,
            ], $account));
            $label = ($entry->income_head ?: 'Income') . ' ' . $entry->period_label;
        }

        return redirect()->route('crm.receipts.index')->with('success', 'Receipt of ' . number_format($amount, 2) . ' recorded on ' . $label . '.');
    }

    /** Every received amount in the active workspace as normalized rows, newest first (also used by Financial Reports). */
    public function receipts()
    {
        $rows = collect();
        $userNames = CrmUser::pluck('name', 'id');

        // 1) Payments recorded on Al Massa invoices — one row per payment.
        $payments = CrmOrderPayment::with('order')
            ->where('workspace_id', CrmWorkspaceContext::id())
            ->get()
            ->filter(fn ($p) => $p->order);
        foreach ($payments as $payment) {
            $order = $payment->order;
            $rows->push($this->row([
                'date' => $payment->paid_at,
                'source' => 'invoice',
                'customer' => $order->client_name,
                'ref' => $order->order_invoice_number ? 'INV-'.$order->order_invoice_number : '#'.$order->id,
                'detail' => $payment->note ?: $order->product_name,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'received_by' => $userNames[$payment->created_by] ?? null,
                'amount' => $payment->amount,
                'currency' => $order->invoice_currency,
                'url' => route('crm.orders.invoice', $order->id),
                'sort_id' => $payment->id,
                'link_type' => 'order_payment', 'link_id' => $payment->id, 'bank_account_id' => $payment->bank_account_id,
            ]));
        }

        // 2) Amounts already paid before per-payment tracking existed (no payment date stored).
        foreach (CrmEmail::where('order_paid_opening_amount', '>', 0)->get() as $order) {
            $rows->push($this->row([
                'date' => $order->order_marked_at ?: $order->created_at,
                'date_note' => 'Payment date not recorded',
                'source' => 'opening',
                'customer' => $order->client_name,
                'ref' => $order->order_invoice_number ? 'INV-'.$order->order_invoice_number : '#'.$order->id,
                'detail' => $order->product_name,
                'amount' => $order->order_paid_opening_amount,
                'currency' => $order->invoice_currency,
                'url' => route('crm.orders.invoice', $order->id),
            ]));
        }

        // 3) Customer Sales not linked to an invoice (linked ones are already counted in 1/2): payments received with
        //    "Make Receipt" on their own date, and whatever else is paid on the sale date (older data has no payment date).
        foreach (CustomerSale::with(['customer', 'payments'])->whereNull('crm_email_id')->where('paid_amount', '>', 0)->get() as $sale) {
            foreach ($sale->payments as $payment) {
                $rows->push($this->row([
                    'date' => $payment->paid_at,
                    'source' => 'sale',
                    'customer' => optional($sale->customer)->name,
                    'ref' => $sale->order_number,
                    'detail' => $payment->note ?: $sale->item_name,
                    'method' => $payment->method,
                    'reference' => $payment->reference,
                    'received_by' => $userNames[$payment->created_by] ?? null,
                    'amount' => $payment->amount,
                    'currency' => $sale->currency,
                    'url' => route('crm.customer_sales.index'),
                    'sort_id' => $payment->id,
                    'link_type' => 'sale_payment', 'link_id' => $payment->id, 'bank_account_id' => $payment->bank_account_id,
                ]));
            }
            $onSaleDate = round((float) $sale->paid_amount - (float) $sale->payments->sum('amount'), 2);
            if ($onSaleDate <= 0.009) continue;
            $rows->push($this->row([
                'date' => $sale->order_date,
                'date_note' => 'Sale date',
                'source' => 'sale',
                'customer' => optional($sale->customer)->name,
                'ref' => $sale->order_number,
                'detail' => $sale->item_name,
                'method' => $sale->payment_method,
                'amount' => $onSaleDate,
                'currency' => $sale->currency,
                'url' => route('crm.customer_sales.index'),
                'link_type' => 'customer_sale', 'link_id' => $sale->id, 'bank_account_id' => $sale->bank_account_id,
            ]));
        }

        // 4) Rent / other income collected.
        foreach (CrmIncomeEntry::with('customer')->where('amount_paid', '>', 0)->get() as $entry) {
            $rows->push($this->row([
                'date' => $entry->paid_at ?: $entry->due_date,
                'date_note' => $entry->paid_at ? null : 'Due date (payment date not recorded)',
                'source' => 'income',
                'customer' => optional($entry->customer)->name,
                'ref' => ($entry->income_head ?: 'Income').' '.$entry->period_label,
                'detail' => $entry->note,
                'method' => $entry->payment_method,
                'reference' => $entry->reference,
                'received_by' => $userNames[$entry->paid_recorded_by] ?? null,
                'amount' => $entry->amount_paid,
                'currency' => optional($entry->customer)->currency,
                'url' => route('crm.customer_sales.index', ['view' => 'income']),
                'proof_url' => $entry->proof_path ? route('crm.income.proof', $entry->id) : null,
                'link_type' => 'income_entry', 'link_id' => $entry->id, 'bank_account_id' => $entry->bank_account_id,
            ]));
        }

        // 5) Money received without an invoice ("Make Receipt"): other income, or a customer paying on account.
        foreach (CrmDirectReceipt::all() as $receipt) {
            $rows->push($this->row([
                'date' => $receipt->receipt_date,
                'source' => $receipt->isOnAccount() ? 'account' : 'direct',
                'customer' => $receipt->payer_name ?: CrmDirectReceipt::PAYER_TYPES[$receipt->payer_type] ?? 'Other',
                'ref' => $receipt->number,
                'detail' => $receipt->category . ' · ' . $receipt->description,
                'method' => $receipt->method,
                'reference' => $receipt->reference,
                'received_by' => $userNames[$receipt->created_by] ?? null,
                'amount' => $receipt->amount,
                'currency' => $receipt->currency,
                'url' => route('crm.direct_receipts.show', $receipt->id),
                'proof_url' => $receipt->proof_path ? route('crm.direct_receipts.proof', $receipt->id) : null,
                'sort_id' => $receipt->id,
                'link_type' => 'direct_receipt', 'link_id' => $receipt->id, 'bank_account_id' => $receipt->bank_account_id,
                'form' => $receipt->formData(),
            ]));
        }

        return $rows->sort(function ($a, $b) {
            return [$b->date->timestamp, $b->sort_id] <=> [$a->date->timestamp, $a->sort_id];
        })->values();
    }

    protected function row(array $data)
    {
        return (object) array_merge([
            'date_note' => null, 'method' => null, 'reference' => null, 'received_by' => null, 'detail' => null,
            'url' => null, 'proof_url' => null, 'sort_id' => 0, 'link_type' => null, 'link_id' => null, 'bank_account_id' => null, 'form' => null,
        ], $data, [
            'date' => Carbon::parse($data['date'])->startOfDay(),
            'source_label' => self::SOURCES[$data['source']],
            'customer' => $data['customer'] ?: '—',
            'amount' => round((float) $data['amount'], 2),
            'currency' => ($data['currency'] ?? null) ?: 'AED',
        ]);
    }

    protected function applyFilters($rows, array $filters)
    {
        if ($filters['date_from']) {
            $from = Carbon::parse($filters['date_from'])->startOfDay();
            $rows = $rows->filter(fn ($r) => $r->date->gte($from));
        }
        if ($filters['date_to']) {
            $to = Carbon::parse($filters['date_to'])->endOfDay();
            $rows = $rows->filter(fn ($r) => $r->date->lte($to));
        }
        if ($filters['source']) {
            $rows = $rows->where('source', $filters['source']);
        }
        if ($filters['method']) {
            $rows = $rows->filter(fn ($r) => strcasecmp((string) $r->method, $filters['method']) === 0);
        }
        if ($filters['customer']) {
            $rows = $rows->where('customer', $filters['customer']);
        }
        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $rows = $rows->filter(function ($r) use ($search) {
                foreach (['customer', 'ref', 'detail', 'reference', 'method'] as $field) {
                    if (stripos((string) $r->$field, $search) !== false) return true;
                }
                return false;
            });
        }

        return $rows->values();
    }
}
