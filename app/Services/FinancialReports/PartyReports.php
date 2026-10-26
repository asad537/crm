<?php

namespace App\Services\FinancialReports;

use App\CrmEmail;
use App\CrmUser;
use App\CustomerSale;
use App\SalesOrder;
use Carbon\Carbon;

/** Customer Statement of Account and Sales by Product / Salesperson for FinancialReportService. */
trait PartyReports
{
    /**
     * Statement of account for one customer: opening balance, every invoice (debit) and payment (credit) in the
     * period with a running balance, the closing balance, and what is still open, aged.
     */
    protected function customerStatement(): array
    {
        $lines = $this->allIncome()->where('source', '!=', 'direct');   // other income received without an invoice is not on a customer's account
        $onAccount = \App\CrmDirectReceipt::where('kind', 'on_account')->get();   // customer payments not tied to one invoice
        $credits = $this->customerCredits();
        $balances = $lines->groupBy('party')->map(fn ($g) => (float) $g->sum(fn ($l) => $l->gross - $l->paid));
        foreach ($credits as $name => $amount) $balances[$name] = ($balances[$name] ?? 0) - $amount;
        $customers = $lines->pluck('party')->merge($credits->keys())->filter(fn ($p) => $p && $p !== '—')->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values();
        $customer = $this->params['customer'] ?? null;
        if (!$customer || !$customers->contains($customer)) {
            // Start on the customer who owes the most.
            $customer = $balances->only($customers->all())->sortDesc()->keys()->first() ?? $customers->first();
        }
        $filters = [['name' => 'customer', 'label' => 'Customer', 'value' => $customer,
            'options' => $customers->mapWithKeys(fn ($c) => [$c => $c . (abs($balances[$c] ?? 0) > 0.009 ? ' · due ' . number_format($balances[$c], 2) : '')])->all()]];

        $end = $this->to->copy()->endOfDay();
        $start = $this->from->copy()->startOfDay();
        $mine = $lines->where('party', $customer)->filter(fn ($l) => $l->date->lte($end));

        // Every invoice is a debit on its date; every payment a credit on its own date.
        $events = collect();
        foreach ($mine as $l) {
            $events->push(['date' => $l->date, 'type' => ['invoice' => 'Invoice', 'order' => 'Sales Order', 'sale' => 'Sale', 'income' => $l->category][$l->source] ?? 'Invoice',
                'ref' => $this->link($l->ref, $l->url), 'detail' => $l->vat > 0 ? 'incl. VAT ' . number_format($l->vat, 2) : '', 'debit' => $l->gross, 'credit' => 0.0, 'order' => 0]);
            foreach ($l->events as [$date, $amount, $label]) {
                if ($date->gt($end)) continue;
                $events->push(['date' => $date, 'type' => 'Payment', 'ref' => $this->link($l->ref, $l->url), 'detail' => $label, 'debit' => 0.0, 'credit' => (float) $amount, 'order' => 1]);
            }
        }
        foreach ($onAccount->filter(fn ($r) => trim((string) $r->payer_name) === $customer && $r->receipt_date->lte($end)) as $r) {
            $events->push(['date' => $r->receipt_date->copy(), 'type' => 'Payment (on account)', 'ref' => $this->link($r->number, route('crm.direct_receipts.show', $r->id)),
                'detail' => trim(($r->method ?: '') . ($r->reference ? ' · ' . $r->reference : '')), 'debit' => 0.0, 'credit' => (float) $r->amount, 'order' => 1]);
        }
        $before = $events->filter(fn ($e) => $e['date']->lt($start));
        $opening = round((float) $before->sum('debit') - (float) $before->sum('credit'), 2);
        $period = $events->filter(fn ($e) => $e['date']->gte($start))->sortBy(fn ($e) => [$e['date']->timestamp, $e['order']])->values();

        $rows = [['_style' => 'total', 'cells' => [$start->format('d M Y'), 'Opening balance', null, 'brought forward', null, null, $opening]]];
        $running = $opening;
        foreach ($period as $e) {
            $running = round($running + $e['debit'] - $e['credit'], 2);
            $rows[] = [$e['date']->format('d M Y'), $e['type'], $e['ref'], $e['detail'], $e['debit'] ?: null, $e['credit'] ?: null, $running];
        }
        $invoiced = (float) $period->sum('debit');
        $paid = (float) $period->sum('credit');

        // What is still open on the statement date, aged by invoice date.
        $buckets = [0, 0, 0, 0];
        $openRows = [];
        foreach ($mine->sortBy('date') as $l) {
            $due = round($l->gross - $this->paidAsOf($l, $end), 2);
            if ($due <= 0.009) continue;
            $days = (int) $l->date->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());
            $buckets[$days <= 30 ? 0 : ($days <= 60 ? 1 : ($days <= 90 ? 2 : 3))] += $due;
            $openRows[] = [$this->link($l->ref, $l->url), $l->date->format('d M Y'), $days, $l->gross, $l->gross - $due, $due];
        }
        // Paid on account (no invoice picked): taken off the total due, not off one invoice.
        $credit = (float) ($this->customerCredits($end)[$customer] ?? 0);
        if ($credit > 0.009) $openRows[] = ['Less: paid on account', null, null, null, null, -$credit];

        return [
            'range_label' => ($customer ?: 'No customer') . ' · ' . $start->format('d M Y') . ' – ' . $end->format('d M Y'),
            'subtitle' => 'Statement of account for ' . ($customer ?: '—') . ' from ' . $start->format('d M Y') . ' to ' . $end->format('d M Y')
                . ': opening balance, invoices, payments received and the balance due. Export it as PDF to send to the customer.',
            'filters' => $filters,
            'cards' => [
                ['label' => 'Opening Balance', 'value' => $opening, 'tone' => 'brand', 'icon' => 'fa-flag', 'note' => 'as of ' . $start->copy()->subDay()->format('d M Y')],
                ['label' => 'Invoiced', 'value' => $invoiced, 'tone' => 'brand', 'icon' => 'fa-file-invoice'],
                ['label' => 'Paid', 'value' => $paid, 'tone' => 'green', 'icon' => 'fa-hand-holding-usd'],
                ['label' => 'Balance Due', 'value' => $running, 'tone' => $running > 0.009 ? 'red' : 'green', 'icon' => 'fa-wallet', 'note' => 'as of ' . $end->format('d M Y')],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Statement of Account — ' . ($customer ?: '—'), 'columns' => [['Date', 'text'], ['Type', 'text'], ['Invoice / Ref', 'text'], ['Details', 'text'],
                    ['Invoiced', 'money'], ['Paid', 'money'], ['Balance', 'money']], 'rows' => $rows, 'footer' => ['Closing balance', null, null, null, $invoiced, $paid, $running]],
                ['title' => 'Open Invoices (aged as of ' . $end->format('d M Y') . ')', 'columns' => [['Invoice', 'text'], ['Date', 'text'], ['Days', 'number'],
                    ['Amount', 'money'], ['Paid', 'money'], ['Due', 'money']], 'rows' => $openRows, 'footer' => ['Total due', null, null, null, null, array_sum($buckets) - $credit]],
                ['title' => 'Ageing Summary', 'columns' => [['0–30 days', 'money'], ['31–60 days', 'money'], ['61–90 days', 'money'], ['90+ days', 'money'], ['Paid on Account', 'money'], ['Total Due', 'money']],
                    'rows' => [[$buckets[0], $buckets[1], $buckets[2], $buckets[3], $credit > 0.009 ? -$credit : null, array_sum($buckets) - $credit]]],
            ],
            'notes' => [
                'Includes Al Massa invoices, workflow sales orders, customer sales and rent / other income for this customer (amounts with VAT).',
                'A payment made before the period starts is in the opening balance; a payment received in advance shows as a credit balance.',
            ],
        ];
    }

    /** Sales by Product and by Salesperson: what sold and who sold it (amounts without VAT, same as Profit & Loss income). */
    protected function salesProducts(): array
    {
        $users = CrmUser::pluck('name', 'id');
        $sold = collect();   // [product, quantity, net, salesperson, invoice key]
        foreach (CrmEmail::where('source', 'manual_offline_order')->with('orderItems')->get() as $inv) {
            if (!$this->inRange($inv->created_at)) continue;
            $person = $users[$inv->assigned_to] ?? ($users[$inv->created_by] ?? ($inv->order_marked_by ?: 'Unassigned'));
            if ($inv->orderItems->isNotEmpty()) {
                foreach ($inv->orderItems as $item) $sold->push([$item->product_name ?: 'Unnamed item', (float) $item->quantity, (float) $item->line_total, $person, 'inv-' . $inv->id]);
            } else {
                $sold->push([$inv->product_name ?: 'Unnamed item', (float) $inv->order_quantity, (float) $inv->order_price * (float) $inv->order_quantity, $person, 'inv-' . $inv->id]);
            }
        }
        foreach (SalesOrder::with(['lead', 'agent'])->whereHas('lead')->get() as $order) {
            if (!$this->inRange($order->created_at)) continue;
            $lead = $order->lead;
            $person = optional($order->agent)->name ?: ($users[$lead->assigned_to] ?? 'Unassigned');
            $sold->push([$lead->product_name ?: 'Unnamed item', (float) $lead->order_quantity, (float) $lead->order_price * (float) $lead->order_quantity, $person, 'so-' . $order->id]);
        }
        foreach (CustomerSale::whereNull('crm_email_id')->get() as $sale) {
            if (!$sale->order_date || !$this->inRange(Carbon::parse($sale->order_date))) continue;
            $sold->push([$sale->item_name ?: 'Unnamed item', (float) $sale->quantity, (float) $sale->total_amount - (float) $sale->tax_amount, $users[$sale->created_by] ?? 'Unassigned', 'cs-' . $sale->id]);
        }

        $total = (float) $sold->sum(2);
        $share = fn ($v) => $total > 0 ? $v / $total * 100 : null;
        $products = $sold->groupBy(fn ($s) => mb_strtolower(trim(preg_replace('/\s+/', ' ', $s[0]))))
            ->map(fn ($g) => [$g->first()[0], (float) $g->sum(1), $g->pluck(4)->unique()->count(), (float) $g->sum(2)])
            ->sortByDesc(fn ($r) => $r[3])->values()
            ->map(fn ($r) => [$r[0], $r[1], $r[2], $r[3], $share($r[3])]);
        $people = $sold->groupBy(3)
            ->map(fn ($g, $person) => [$person, $g->pluck(4)->unique()->count(), (float) $g->sum(2)])
            ->sortByDesc(fn ($r) => $r[2])->values()
            ->map(fn ($r) => [$r[0], $r[1], $r[2], $r[1] ? $r[2] / $r[1] : 0, $share($r[2])]);
        $top = $products->take(10);

        return [
            'subtitle' => 'What was sold and who sold it between ' . $this->from->format('d M Y') . ' and ' . $this->to->format('d M Y') . ' (amounts without VAT).',
            'cards' => [
                ['label' => 'Sales (without VAT)', 'value' => $total, 'tone' => 'brand', 'icon' => 'fa-shopping-bag'],
                ['label' => 'Products Sold', 'value' => $products->count(), 'format' => 'number', 'tone' => 'brand', 'icon' => 'fa-tags'],
                ['label' => 'Top Product', 'value' => (float) optional($products->first())[3], 'tone' => 'green', 'icon' => 'fa-crown', 'note' => optional($products->first())[0] ?? '—'],
                ['label' => 'Top Salesperson', 'value' => (float) optional($people->first())[2], 'tone' => 'green', 'icon' => 'fa-user-tie', 'note' => optional($people->first())[0] ?? '—'],
            ],
            'chart' => $top->isNotEmpty() ? ['type' => 'bar', 'labels' => $top->map(fn ($r) => mb_strimwidth($r[0], 0, 28, '…'))->all(), 'datasets' => [
                ['label' => 'Sales', 'data' => $top->map(fn ($r) => round($r[3], 2))->all(), 'color' => '#f45a24'],
            ]] : null,
            'tables' => [
                ['title' => 'By Product', 'columns' => [['Product', 'text'], ['Quantity', 'number'], ['Invoices', 'number'], ['Sales', 'money'], ['Share', 'share']],
                    'rows' => $products->all(), 'footer' => ['Total', (float) $sold->sum(1), $sold->pluck(4)->unique()->count(), $total, $total > 0 ? 100 : null]],
                ['title' => 'By Salesperson', 'columns' => [['Salesperson', 'text'], ['Invoices', 'number'], ['Sales', 'money'], ['Average Invoice', 'money'], ['Share', 'share']],
                    'rows' => $people->all(), 'footer' => ['Total', $sold->pluck(4)->unique()->count(), $total, null, $total > 0 ? 100 : null]],
            ],
            'notes' => [
                'Products come from invoice lines, workflow sales orders and customer sales; the same product name is grouped (case and spaces ignored).',
                'Salesperson = the user the order is assigned to (sales orders: the sales agent; customer sales: who entered them). Rent / other income is not a product sale and is not included.',
            ],
        ];
    }
}
