<?php

namespace App\Services\FinancialReports;

use App\CrmBankClearance;
use App\CrmBankReconciliation;
use App\CrmBudget;
use App\CrmEmployee;
use App\CrmInventoryItem;
use App\CrmInventoryMovement;
use App\Services\BankLedgerService;
use App\Services\InventoryAccounting;
use App\VendorPurchase;
use Carbon\Carbon;

/** Budget vs Actual, VAT Return (VAT 201), End-of-Service Gratuity, Inventory and Bank Reconciliation for FinancialReportService. */
trait OperationsReports
{
    /** Budget vs Actual: each P&L category's budget for the period against what really happened. */
    protected function budget(): array
    {
        $actual = $this->plTotals($this->from, $this->to);
        // A yearly budget counts for the share of that year the period covers (e.g. one month ≈ 1/12).
        $budget = ['income' => [], 'cos' => [], 'opex' => []];
        $hasBudget = false;
        for ($year = $this->from->year; $year <= $this->to->year; $year++) {
            $yearStart = Carbon::create($year, 1, 1);
            $start = $this->from->gt($yearStart) ? $this->from->copy()->startOfDay() : $yearStart;
            $end = $this->to->lt($yearStart->copy()->endOfYear()) ? $this->to->copy()->startOfDay() : $yearStart->copy()->endOfYear()->startOfDay();
            $share = ($start->diffInDays($end) + 1) / $yearStart->daysInYear;
            foreach (CrmBudget::where('year', $year)->get() as $b) {
                if (!isset($budget[$b->group])) continue;
                $hasBudget = $hasBudget || (float) $b->amount > 0;
                $budget[$b->group][$b->category] = ($budget[$b->group][$b->category] ?? 0) + (float) $b->amount * $share;
            }
        }

        $rows = [];
        $totals = [];
        foreach (['income' => 'Income', 'cos' => 'Cost of Sales', 'opex' => 'Operating Expenses'] as $group => $title) {
            $rows[] = ['_style' => 'section', 'cells' => [$title, null, null, null, null]];
            $categories = collect(array_keys($actual[$group]))->merge(array_keys($budget[$group]))->unique()->sort()->values();
            foreach ($categories as $category) {
                $b = (float) ($budget[$group][$category] ?? 0);
                $a = (float) ($actual[$group][$category] ?? 0);
                // Variance is "good minus bad": more income than budget, or less cost than budget, is positive.
                $rows[] = [$this->link($category, $this->drill($group, $category)), $b ?: null, $a, $group === 'income' ? $a - $b : $b - $a, $b > 0 ? $a / $b * 100 : null];
            }
            $b = array_sum($budget[$group]);
            $a = array_sum($actual[$group]);
            $totals[$group] = [$b, $a];
            $rows[] = ['_style' => 'subtotal', 'cells' => ['Total ' . $title, $b, $a, $group === 'income' ? $a - $b : $b - $a, $b > 0 ? $a / $b * 100 : null]];
        }
        $budgetNet = $totals['income'][0] - $totals['cos'][0] - $totals['opex'][0];
        $rows[] = ['_style' => 'grand', 'cells' => ['Net Profit / (Loss)', $budgetNet, $actual['net'], $actual['net'] - $budgetNet, null]];

        return [
            'subtitle' => 'Budget for ' . $this->from->format('d M Y') . ' – ' . $this->to->format('d M Y') . ' (the yearly budget, for the part of the year the period covers) against the actual Profit & Loss.',
            'actions' => [['label' => $hasBudget ? 'Edit Budget ' . $this->to->year : 'Set Budget ' . $this->to->year, 'icon' => 'fa-pen', 'url' => route('crm.budgets.edit', ['year' => $this->to->year])]],
            'cards' => [
                ['label' => 'Budgeted Income', 'value' => $totals['income'][0], 'tone' => 'brand', 'icon' => 'fa-bullseye', 'note' => 'actual ' . number_format($totals['income'][1], 2)],
                ['label' => 'Budgeted Costs', 'value' => $totals['cos'][0] + $totals['opex'][0], 'tone' => 'brand', 'icon' => 'fa-receipt', 'note' => 'actual ' . number_format($totals['cos'][1] + $totals['opex'][1], 2)],
                ['label' => 'Budgeted Profit', 'value' => $budgetNet, 'tone' => $budgetNet < 0 ? 'red' : 'green', 'icon' => 'fa-chart-line', 'note' => 'actual ' . number_format($actual['net'], 2)],
                ['label' => 'Profit vs Budget', 'value' => $actual['net'] - $budgetNet, 'tone' => $actual['net'] >= $budgetNet ? 'green' : 'red', 'icon' => 'fa-balance-scale', 'note' => $actual['net'] >= $budgetNet ? 'ahead of budget' : 'behind budget'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Budget vs Actual', 'columns' => [['Category', 'text'], ['Budget', 'money'], ['Actual', 'money'], ['Variance', 'money'], ['% of Budget', 'share']], 'rows' => $rows],
            ],
            'notes' => array_values(array_filter([
                $hasBudget ? null : 'No budget is set for ' . $this->to->year . ' yet — click "Set Budget" to enter a yearly amount per category.',
                'Variance: positive = good (income above budget, or costs below budget); negative = needs attention.',
                'Actual figures are the same as the Profit & Loss report (accrual, without VAT).',
            ])),
        ];
    }

    /** VAT Return in the FTA VAT 201 layout (boxes 1–14), from invoices, customer sales and vendor purchases. */
    protected function vatReturn(): array
    {
        $emirates = ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'];
        $emirate = in_array($this->params['emirate'] ?? '', $emirates, true) ? $this->params['emirate'] : 'Sharjah';

        $sales = $this->incomeLines($this->from, $this->to)->whereIn('source', ['invoice', 'sale', 'order']);
        $standard = $sales->filter(fn ($s) => $s->vat > 0.004);
        $noVat = $sales->filter(fn ($s) => $s->vat <= 0.004 && $s->net > 0.004);
        $purchases = VendorPurchase::get()->filter(fn ($p) => $this->inRange(Carbon::parse($p->purchase_date ?: $p->created_at)) && (float) $p->tax_amount > 0.004);

        $outNet = (float) $standard->sum('net');
        $outVat = (float) $standard->sum('vat');
        $zeroNet = (float) $noVat->sum('net');
        $inNet = (float) $purchases->sum(fn ($p) => (float) $p->total_amount - (float) $p->tax_amount);
        $inVat = (float) $purchases->sum('tax_amount');

        $rows = [['_style' => 'section', 'cells' => ['VAT on Sales and all other Outputs', null, null, null]]];
        foreach ($emirates as $i => $name) {
            $mine = $name === $emirate;
            $rows[] = ['1' . chr(97 + $i), 'Standard rated supplies in ' . $name, $mine ? $outNet : 0.0, $mine ? $outVat : 0.0];
        }
        $rows[] = ['2', 'Tax refunds provided to tourists under the Tax Refunds for Tourists Scheme', 0.0, 0.0];
        $rows[] = ['3', 'Supplies subject to the reverse charge provisions', 0.0, 0.0];
        $rows[] = ['4', 'Zero rated supplies', $zeroNet, null];
        $rows[] = ['5', 'Exempt supplies', 0.0, null];
        $rows[] = ['6', 'Goods imported into the UAE', 0.0, 0.0];
        $rows[] = ['7', 'Adjustments to goods imported into the UAE', 0.0, 0.0];
        $rows[] = ['_style' => 'subtotal', 'cells' => ['8', 'Totals', $outNet + $zeroNet, $outVat]];
        $rows[] = ['_style' => 'section', 'cells' => ['VAT on Expenses and all other Inputs', null, null, null]];
        $rows[] = ['9', 'Standard rated expenses', $inNet, $inVat];
        $rows[] = ['10', 'Supplies subject to the reverse charge provisions', 0.0, 0.0];
        $rows[] = ['_style' => 'subtotal', 'cells' => ['11', 'Totals', $inNet, $inVat]];
        $rows[] = ['_style' => 'section', 'cells' => ['Net VAT Due', null, null, null]];
        $rows[] = ['12', 'Total value of due tax for the period', null, $outVat];
        $rows[] = ['13', 'Total value of recoverable tax for the period', null, $inVat];
        $rows[] = ['_style' => 'grand', 'cells' => ['14', $outVat >= $inVat ? 'Payable tax for the period' : 'Refundable tax for the period', null, $outVat - $inVat]];

        return [
            'subtitle' => 'VAT return for ' . $this->from->format('d M Y') . ' – ' . $this->to->format('d M Y') . ' in the FTA VAT 201 layout, ready to copy into EmaraTax. Pick the quarter with Custom Dates.',
            'filters' => [['name' => 'emirate', 'label' => 'Supplies made from', 'value' => $emirate, 'options' => array_combine($emirates, $emirates)]],
            'cards' => [
                ['label' => 'Box 1 · Standard Rated Sales', 'value' => $outNet, 'tone' => 'brand', 'icon' => 'fa-file-invoice', 'note' => $standard->count() . ' invoice(s) · VAT ' . number_format($outVat, 2)],
                ['label' => 'Box 9 · Standard Rated Expenses', 'value' => $inNet, 'tone' => 'brand', 'icon' => 'fa-shopping-cart', 'note' => $purchases->count() . ' purchase(s) · VAT ' . number_format($inVat, 2)],
                ['label' => 'Box 14 · ' . ($outVat >= $inVat ? 'VAT Payable' : 'VAT Refundable'), 'value' => abs($outVat - $inVat), 'tone' => $outVat >= $inVat ? 'red' : 'green', 'icon' => 'fa-landmark'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'VAT 201 — ' . $this->from->format('d M Y') . ' to ' . $this->to->format('d M Y'), 'columns' => [['Box', 'text'], ['Description', 'text'], ['Amount (AED)', 'money'], ['VAT Amount (AED)', 'money']], 'rows' => $rows],
            ],
            'notes' => [
                'All standard rated sales are put in the emirate picked above (the CRM does not store an emirate per customer). Change it if your supplies are made from another emirate.',
                'Box 4: sales with no VAT charged. Check them — exports are zero rated, but out-of-scope or exempt items belong elsewhere.',
                'Box 9: vendor purchases with a tax amount. Purchase invoices, expenses and demand requests have no VAT field, so their VAT is not recoverable here.',
                'Boxes 2, 3, 5, 6, 7 and 10 are not tracked in the CRM and show 0. Amounts are taken as AED.',
            ],
        ];
    }

    /** End-of-service gratuity each employee has earned so far (UAE Labour Law), i.e. what the company owes them. */
    protected function gratuity(): array
    {
        $asOf = $this->to->copy()->startOfDay();
        $rows = [];
        $total = 0.0;
        $eligibleTotal = 0.0;
        $missing = [];
        $employees = CrmEmployee::orderBy('name')->get()->filter(fn ($e) => $e->status === 'active' || ($e->left_date && $e->left_date->gt($asOf)));
        foreach ($employees as $e) {
            if (!$e->joining_date || $e->joining_date->gt($asOf)) {
                if (!$e->joining_date) $missing[] = $e->name;
                continue;
            }
            $years = $e->joining_date->diffInDays($asOf) / 365;
            $monthly = $e->salary_type === 'daily' ? (float) $e->basic_salary * 30 : (float) $e->basic_salary;
            $daily = $e->salary_type === 'daily' ? (float) $e->basic_salary : $monthly * 12 / 365;
            // 21 days' basic wage per year for the first five years, 30 days per year after; at most two years' wage.
            $days = min($years, 5) * 21 + max(0, $years - 5) * 30;
            $amount = round(min($days * $daily, $monthly * 24), 2);
            $eligible = $years >= 1;
            $total += $amount;
            if ($eligible) $eligibleTotal += $amount;
            $rows[] = [$this->link($e->name, route('crm.employees.index', ['search' => $e->name])), $e->designation ?: '—', $e->joining_date->format('d M Y'),
                round($years, 2), $monthly, round($daily, 2), round($days, 1), $amount, $eligible ? 'Yes' : 'Not yet (under 1 year)'];
        }

        return [
            'range_label' => 'As of ' . $asOf->format('d M Y'),
            'subtitle' => 'Gratuity each employee has earned up to ' . $asOf->format('d M Y') . ' under the UAE Labour Law — what the company would owe if their service ended that day.',
            'cards' => [
                ['label' => 'Total Gratuity Earned', 'value' => $total, 'tone' => 'red', 'icon' => 'fa-hourglass-end', 'note' => count($rows) . ' employee(s)'],
                ['label' => 'Payable Now (1+ year service)', 'value' => $eligibleTotal, 'tone' => 'brand', 'icon' => 'fa-user-check'],
                ['label' => 'Still Building (under 1 year)', 'value' => $total - $eligibleTotal, 'tone' => 'brand', 'icon' => 'fa-seedling'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Gratuity by Employee', 'columns' => [['Employee', 'text'], ['Designation', 'text'], ['Joined', 'text'], ['Years', 'number'], ['Basic / Month', 'money'],
                    ['Daily Wage', 'money'], ['Gratuity Days', 'number'], ['Gratuity', 'money'], ['Payable Now', 'text']],
                    'rows' => $rows, 'footer' => ['Total', null, null, null, null, null, null, $total, null]],
            ],
            'notes' => array_values(array_filter([
                'UAE Federal Decree-Law No. 33 of 2021: 21 days of basic wage for each of the first 5 years and 30 days for each year after, part years pro rata, not payable under 1 year of service, at most 2 years\' wage.',
                'Basic wage only (allowances are not included). Daily wage = monthly basic × 12 ÷ 365; for daily-wage staff the daily rate is used. Unpaid leave is not deducted.',
                $missing ? 'Joining date missing for: ' . implode(', ', $missing) . ' — add it on Employees to include them.' : null,
            ])),
        ];
    }

    /** Inventory: stock value in the books on the period's last day, the Inventory account for the period, stock movement and what jobs used. */
    protected function inventory(): array
    {
        $stock = $this->stock();
        $from = InventoryAccounting::from();
        $start = $this->from->copy()->startOfDay();
        $end = $this->to->copy()->endOfDay();
        $booked = $end->gte($from);
        $items = CrmInventoryItem::orderBy('name')->get()->keyBy('id');
        $moves = CrmInventoryMovement::whereIn('inventory_item_id', $items->keys())->orderBy('id')->get()->groupBy('inventory_item_id');
        // Each movement's change in quantity, dated like the books (a bill's stock on the bill date), per item.
        $changes = $moves->map(function ($list) {
            $previous = 0.0;
            $rows = collect();
            foreach ($list as $m) {
                $rows->push((object) ['movement' => $m, 'delta' => (float) $m->balance_after - $previous, 'date' => InventoryAccounting::dateOf($m)]);
                $previous = (float) $m->balance_after;
            }
            return $rows;
        });
        $qtyAt = fn ($list, Carbon $at) => (float) $list->filter(fn ($c) => $c->date->lte($at))->sum('delta');
        $label = fn ($item) => $this->link(InventoryAccounting::itemLabel($item), route('crm.inventory.movements', $item->id));

        // Stock on hand on the end date: at its value in the books once stock is in them; before that at today's average cost (information only).
        $valuation = [];
        if ($booked) {
            foreach ($stock->itemsAsOf($end)->sortBy(fn ($r) => $r['item']->name) as $r) {
                if (abs($r['qty']) < 0.0005 && abs($r['value']) < 0.005) continue;
                $valuation[] = [$label($r['item']), $r['item']->category ?: '—', $r['item']->unit, $r['qty'], $r['qty'] > 0.0005 ? $r['value'] / $r['qty'] : null, $r['value']];
            }
            $stockValue = $stock->valueAsOf($end);
        } else {
            foreach ($items as $item) {
                $qty = $qtyAt($changes[$item->id] ?? collect(), $end);
                if (abs($qty) > 0.0005) $valuation[] = [$label($item), $item->category ?: '—', $item->unit, $qty, (float) $item->unit_cost, $qty * (float) $item->unit_cost];
            }
            $stockValue = (float) array_sum(array_column($valuation, 5));
        }

        // The Inventory account for the period: opening value, what came in and went out (by what it means for the books), closing value.
        $period = $stock->events()->filter(fn ($e) => $e->date->between($start, $end));
        $sum = fn (string $kind) => (float) $period->where('kind', $kind)->sum('value');
        $account = [];
        if ($booked) {
            $account[] = ['_style' => 'subtotal', 'cells' => ['Opening value' . ($start->gt($from) ? ' (' . $start->copy()->subDay()->format('d M Y') . ')' : ''), $stock->valueAsOf($start->copy()->subSecond())]];
            foreach ([
                'start' => 'Stock on hand on ' . $from->format('d M Y') . ' (taken onto the books as Opening Stock)',
                'bill' => 'Bought on purchase invoices',
                'opening' => 'Opening stock added without a bill',
                'expensed' => 'Moved in from bills already in expenses',
                'job' => 'Used on jobs → Cost of Sales',
                'loss' => 'Wastage, damage & count differences → Stock Loss',
                'correction' => 'Cost corrections → Cost of Sales',
            ] as $kind => $text) {
                if (abs($sum($kind)) > 0.004) $account[] = [$text, $sum($kind)];
            }
            $account[] = ['_style' => 'total', 'cells' => ['Closing value (' . $end->format('d M Y') . ')', $stockValue]];
        }

        // Quantities in / out / adjusted in the period, from each movement's change in balance, on the same dates as the
        // values above. A bill edited or removed (or a demand receipt undone) nets off "In"; stock returned from a job nets off "Out".
        $movement = [];
        foreach ($items as $item) {
            $list = $changes[$item->id] ?? collect();
            $in = $out = $adjust = 0.0;
            foreach ($list as $c) {
                if (!$c->date->between($start, $end)) continue;
                $reason = InventoryAccounting::reasonOf($c->movement);
                if ($reason === 'job') $out += -$c->delta;
                elseif (in_array($reason, ['bill', 'opening', 'expensed'], true)) $in += $c->delta;
                else $adjust += $c->delta;
            }
            $opening = $qtyAt($list, $start->copy()->subSecond());
            $closing = $qtyAt($list, $end);
            if (abs($opening) + abs($in) + abs($out) + abs($adjust) + abs($closing) > 0.0005) $movement[] = [$label($item), $item->unit, $opening, $in, $out, $adjust, $closing];
        }

        // Stock used per job in the period: at its value in the books (returns from a job count back), before that at the cost on the movement.
        $bookedCost = $stock->events()->where('kind', 'job')->filter(fn ($e) => $e->movement)->keyBy(fn ($e) => $e->movement->id);
        $jobRows = $moves->flatten(1)->filter(fn ($m) => trim((string) $m->job_number) !== '' && in_array($m->type, ['in', 'out'], true)
                && InventoryAccounting::reasonOf($m) === 'job' && InventoryAccounting::dateOf($m)->between($start, $end))
            ->groupBy(fn ($m) => trim($m->job_number))
            ->map(fn ($g, $job) => [$job, $g->map(fn ($m) => optional($items[$m->inventory_item_id] ?? null)->name)->filter()->unique()->implode(', '), $g->where('type', 'out')->count(),
                (float) $g->sum(fn ($m) => isset($bookedCost[$m->id]) ? -$bookedCost[$m->id]->value
                    : ($m->type === 'out' ? 1 : -1) * (float) $m->quantity * (float) ($m->unit_cost ?? optional($items[$m->inventory_item_id] ?? null)->unit_cost))])
            ->sortByDesc(fn ($r) => $r[3])->values();
        $usedCost = (float) $jobRows->sum(3);

        $tables = [
            ['title' => 'Stock Valuation (as of ' . $end->format('d M Y') . ')', 'columns' => [['Item', 'text'], ['Category', 'text'], ['Unit', 'text'], ['Quantity', 'number'], ['Average Cost', 'money'], ['Value', 'money']],
                'rows' => $valuation, 'footer' => ['Total', null, null, null, null, $stockValue]],
        ];
        if ($account) $tables[] = ['title' => 'Inventory Account (' . $start->format('d M Y') . ' – ' . $end->format('d M Y') . ')', 'columns' => [['', 'text'], ['Value', 'money']], 'rows' => $account];
        $tables[] = ['title' => 'Stock Movement (quantities)', 'columns' => [['Item', 'text'], ['Unit', 'text'], ['Opening', 'number'], ['In', 'number'], ['Out', 'number'], ['Adjustments', 'number'], ['Closing', 'number']],
            'rows' => $movement];
        $tables[] = ['title' => 'Consumption by Job', 'columns' => [['Job', 'text'], ['Items', 'text'], ['Issues', 'number'], ['Cost', 'money']],
            'rows' => $jobRows->all(), 'footer' => ['Total', null, $jobRows->sum(2), $usedCost]];

        return [
            'subtitle' => 'Stock on hand and its value on ' . $end->format('d M Y') . ', how the Inventory account moved between ' . $start->format('d M Y') . ' and ' . $end->format('d M Y') . ', and what each job used.',
            'cards' => [
                ['label' => 'Stock Value', 'value' => $stockValue, 'tone' => 'brand', 'icon' => 'fa-cubes', 'note' => ($booked ? 'in the books' : 'not in the books yet') . ' · ' . $end->format('d M Y')],
                ['label' => 'Bought on Bills', 'value' => $sum('bill'), 'tone' => 'green', 'icon' => 'fa-file-invoice', 'note' => 'purchase invoices'],
                ['label' => 'Used on Jobs', 'value' => -$sum('job') - $sum('correction'), 'tone' => 'red', 'icon' => 'fa-briefcase', 'note' => 'Cost of Sales'],
                ['label' => 'Stock Loss', 'value' => -$sum('loss'), 'tone' => 'red', 'icon' => 'fa-trash-alt', 'note' => 'wastage / damage / count'],
                ['label' => 'Low Stock Now', 'value' => $items->filter(fn ($i) => $i->is_low)->count(), 'format' => 'number', 'tone' => 'red', 'icon' => 'fa-exclamation-triangle'],
            ],
            'chart' => null,
            'tables' => $tables,
            'notes' => [
                'Stock is in the books from ' . $from->format('d M Y') . ': stock lines on purchase invoices go to Inventory (Balance Sheet) instead of expenses; stock used on a job is Cost of Sales ("' . InventoryAccounting::USED . '"); wastage, damage and count differences are "' . InventoryAccounting::LOSS . '".',
                'The stock on hand on ' . $from->format('d M Y') . ' was bought on older bills that were already counted as costs; it is taken onto the books once as Opening Stock (Equity). Bills and movements before that day stay as they were.',
                'Values use moving-average cost: stock comes in at its bill price and goes out at the average.',
            ],
        ];
    }

    /** Bank Reconciliation overview: each account's balance, what has not cleared the bank yet, and its last reconciliation. */
    protected function bankReconciliation(): array
    {
        $ledger = new BankLedgerService();
        $end = $this->to->copy()->endOfDay();
        $balances = $ledger->balances($end);
        $last = CrmBankReconciliation::orderByDesc('statement_date')->orderByDesc('id')->get()->groupBy('bank_account_id')->map->first();
        $cleared = CrmBankClearance::get()->groupBy('bank_account_id')->map(fn ($g) => $g->pluck('movement_key')->flip());

        $rows = [];
        $openItems = 0;
        foreach ($ledger->accounts() as $account) {
            $statement = $ledger->statement($account, null, $end);
            $done = $cleared[$account->id] ?? collect();
            $pending = collect($statement['rows'])->reject(fn ($r) => $done->has($r->key));
            $openItems += $pending->count();
            $rec = $last[$account->id] ?? null;
            $rows[] = [$this->link($account->name, route('crm.bank_accounts.reconcile', $account->id)), $balances['accounts'][$account->id]['balance'] ?? 0.0,
                $pending->count(), (float) $pending->sum('in'), (float) $pending->sum('out'),
                $rec ? $rec->statement_date->format('d M Y') : 'Never', $rec ? (float) $rec->statement_balance : null, $rec ? (float) $rec->difference : null];
        }

        return [
            'range_label' => 'As of ' . $end->format('d M Y'),
            'subtitle' => 'Each bank / cash account\'s balance in the CRM as of ' . $end->format('d M Y') . ', the entries not yet ticked as cleared on the bank statement, and the last reconciliation. Click an account to reconcile it.',
            'cards' => [
                ['label' => 'Accounts', 'value' => count($rows), 'format' => 'number', 'tone' => 'brand', 'icon' => 'fa-university'],
                ['label' => 'Entries Not Cleared', 'value' => $openItems, 'format' => 'number', 'tone' => $openItems ? 'red' : 'green', 'icon' => 'fa-hourglass-half'],
                ['label' => 'Book Balance (all accounts)', 'value' => (float) $balances['total'], 'tone' => 'brand', 'icon' => 'fa-wallet'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Accounts', 'columns' => [['Account', 'text'], ['Balance in CRM', 'money'], ['Not Cleared', 'number'], ['Uncleared In', 'money'], ['Uncleared Out', 'money'],
                    ['Last Reconciled', 'text'], ['Statement Balance', 'money'], ['Difference', 'money']], 'rows' => $rows],
            ],
            'notes' => array_values(array_filter([
                $rows ? null : 'No bank or cash accounts yet — add them under Bank & Cash.',
                'Reconcile: enter the balance shown on the bank statement, tick every entry that appears on the statement; the CRM balance minus what has not cleared should equal the statement.',
            ])),
        ];
    }
}
