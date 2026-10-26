<?php

namespace App\Services\FinancialReports;

use App\CrmBankTransfer;
use App\CrmEmployee;
use App\CrmFixedAsset;
use App\CrmPayrollLine;
use App\CrmPurchaseInvoice;
use App\Services\BankLedgerService;
use App\Services\InventoryAccounting;
use App\Support\CrmWorkspaceContext;
use App\VendorPurchase;
use App\VendorPurchasePayment;
use Carbon\Carbon;

/**
 * Balance Sheet (on any date), Trial Balance, Owner's Equity and Day Book for FinancialReportService.
 * Every balance-sheet line is [label, amount, url, kind]; "kind" lets the other statements pick lines out.
 */
trait StatementReports
{
    /** Balance sheet as of the end of $asOf, from what the CRM records (dates of invoices, bills, payments...). */
    public function balanceSheetAsOf(Carbon $asOf): array
    {
        $end = $asOf->copy()->endOfDay();
        $onOrBefore = fn ($date) => $date && Carbon::parse($date)->lte($end);
        $capital = $this->capitalEntries()->filter(fn ($c) => $c->entry_date->lte($end));

        // Cash & bank: each account's balance on that date (or the plain net movement when no accounts are set up).
        $bank = new BankLedgerService();
        $hasAccounts = $bank->accounts()->isNotEmpty();
        if ($hasAccounts) {
            $balances = $bank->balances($end);
            $cashLines = collect($balances['accounts'])
                ->filter(fn ($row) => $row['account']->is_active || abs($row['balance']) > 0.004)
                ->map(fn ($row) => [$row['account']->name, $row['balance'], route('crm.bank_accounts.show', $row['account']->id), 'cash'])
                ->values()->all();
            if ($balances['unassigned']['count']) {
                $cashLines[] = ['Not booked to any account (' . $balances['unassigned']['count'] . ')', $balances['unassigned']['in'] - $balances['unassigned']['out'], route('crm.bank_accounts.unassigned'), 'cash'];
            }
        } else {
            // Same movements as the Cash Flow statement (receipts, payments, fixed assets bought outright, owner's capital).
            $cash = (float) $this->cashMoves()->filter(fn ($m) => $m->date->lte($end))->sum('amount');
            $cashLines = [['Cash & Bank (net movement*)', $cash, route('crm.financial_reports.index', ['report' => 'cash-flow']), 'cash']];
        }

        // What customers owed on that date: each invoice / sale / income less what was received by then, less what
        // each customer paid on account; a customer who paid more than they owed has an advance (a liability).
        $credits = $this->customerCredits($end);
        // Not floored per invoice: what a customer paid beyond one sale (or before its date) is netted against their other sales.
        $owedBy = $this->allIncome()->groupBy(fn ($l) => trim((string) $l->party))
            ->map(fn ($g) => (float) $g->sum(fn ($l) => round(($l->date->lte($end) ? $l->gross : 0) - $this->paidAsOf($l, $end), 2)));
        $receivable = 0.0;
        $customerAdvances = 0.0;
        foreach ($owedBy->keys()->merge($credits->keys())->unique() as $customer) {
            $net = round((float) ($owedBy[$customer] ?? 0) - (float) ($credits[$customer] ?? 0), 2);
            $net >= 0 ? $receivable += $net : $customerAdvances -= $net;
        }

        // Salary advances still to be recovered on that date. A sheet counts from its month (the day the P&L books the
        // salary), not from the day it was approved, so a month approved after it ended still balances at that month's end.
        // Not floored at 0: an advance given later in the month than the sheet's date is still owed to the employee until then.
        $advances = 0.0;
        foreach (CrmEmployee::with(['advances', 'payrollLines.run'])->get() as $employee) {
            $given = (float) $employee->advances->filter(fn ($a) => $onOrBefore($a->given_date))->sum('amount');
            $recovered = (float) $employee->payrollLines->filter(fn ($l) => $l->run && in_array($l->run->status, ['approved', 'paid'], true)
                && $l->run->periodStart()->lte($end))->sum('advance_deduction');
            $advances += round($given - $recovered, 2);
        }

        $fixedAssets = (float) CrmFixedAsset::get()->filter(fn ($a) => !$a->isAwaitingInvoice() && $onOrBefore($a->purchase_date))->sum(fn ($a) => $a->cost());

        // Vendors: each purchase less its payments by then, less payments made on a vendor's account.
        $vendorOwed = (float) VendorPurchase::with('payments')->get()->filter(fn ($p) => $onOrBefore($p->purchase_date ?: $p->created_at))
            ->sum(fn ($p) => max(0, round((float) $p->total_amount - (float) $p->deduction
                - (float) $p->payments->filter(fn ($pay) => $onOrBefore($pay->paid_at ?: $pay->created_at))->sum('amount'), 2)));
        $onAccount = (float) VendorPurchasePayment::where('workspace_id', CrmWorkspaceContext::id())->whereNull('vendor_purchase_id')->get()
            ->filter(fn ($p) => $onOrBefore($p->paid_at ?: $p->created_at))->sum('amount');
        $vendorPayable = max(0, $vendorOwed - $onAccount);

        // Purchase invoices, per vendor: bills dated by then less payments made by then. A vendor paid more than billed
        // (an overpaid bill, or paid before the bill's date) has an advance: an asset that the next bill uses up.
        $invoiceNet = CrmPurchaseInvoice::with('payments')->get()->groupBy(fn ($i) => $i->vendorKey())
            ->map(fn ($bills) => round((float) $bills->sum(fn ($i) => ($onOrBefore($i->issue_date) ? (float) $i->total : 0)
                - (float) $i->payments->filter(fn ($p) => $onOrBefore($p->paid_at))->sum('amount')), 2));
        $invoicePayable = (float) $invoiceNet->filter(fn ($net) => $net > 0)->sum();
        $vendorAdvances = -(float) $invoiceNet->filter(fn ($net) => $net < 0)->sum();
        $salariesPayable = (float) CrmPayrollLine::with('run')
            ->whereHas('run', fn ($q) => $q->where('workspace_id', CrmWorkspaceContext::id())->whereIn('status', ['approved', 'paid']))->get()
            ->filter(fn ($l) => $l->run->periodStart()->lte($end) && (!$l->paid_at || Carbon::parse($l->paid_at)->gt($end)))
            ->sum('net');
        $vatPayable = (float) $this->allIncome()->whereIn('source', ['invoice', 'sale', 'order'])->filter(fn ($l) => $l->date->lte($end))->sum('vat')
            - (float) VendorPurchase::get()->filter(fn ($p) => $onOrBefore($p->purchase_date ?: $p->created_at))->sum('tax_amount');
        $assetsOnCredit = (float) CrmFixedAsset::get()->filter(fn ($a) => ($a->payment_method === 'Credit' && $onOrBefore($a->purchase_date)) || $a->isAwaitingInvoice())
            ->sum('amount');

        // Stock is in the books from the perpetual-inventory start day (before that it was a cost when bought).
        $stockOnBooks = $end->gte(InventoryAccounting::from());
        $inventoryUrl = route('crm.financial_reports.index', ['report' => 'inventory', 'period' => 'custom', 'date_from' => InventoryAccounting::from()->toDateString(), 'date_to' => $end->toDateString()]);

        $assets = array_merge($cashLines, [
            ['Customers (Accounts Receivable)', $receivable, route('crm.financial_reports.index', ['report' => 'receivables']), 'receivable'],
            ['Salary Advances to Employees', $advances, route('crm.employees.index', ['tab' => 'advances']), 'advances'],
            ['Advances to Vendors (paid more than billed)', $vendorAdvances, route('crm.purchase_invoices.index', ['status' => 'overpaid']), 'vendor_advances'],
        ], $stockOnBooks ? [['Inventory (stock at cost)', $this->stock()->valueAsOf($end), $inventoryUrl, 'inventory']] : [], [
            ['Fixed Assets, at cost', $fixedAssets, route('crm.fixed_assets.index'), 'fixed'],
        ]);
        $liabilities = [
            ['Vendors (Accounts Payable)', $vendorPayable, route('crm.financial_reports.index', ['report' => 'payables']), 'vendor'],
            ['Purchase Invoices Payable', $invoicePayable, route('crm.purchase_invoices.index'), 'invoice'],
            ['Salaries Payable (approved, unpaid)', $salariesPayable, route('crm.payroll.index'), 'salary'],
            ['VAT Payable / (Refundable)', $vatPayable, route('crm.financial_reports.index', ['report' => 'vat']), 'vat'],
            ['Fixed Assets Bought on Credit', $assetsOnCredit, route('crm.fixed_assets.index'), 'credit_assets'],
            ['Customer Advances (paid ahead / on account)', $customerAdvances, route('crm.receipts.index', ['source' => 'account']), 'customer_advances'],
        ];
        $totalAssets = array_sum(array_column($assets, 1));
        $totalLiabilities = array_sum(array_column($liabilities, 1));
        $equity = $totalAssets - $totalLiabilities;

        // Equity: each owner's capital and drawings, profit of earlier years, this year's profit, and the balancing figure.
        $equityLines = [];
        foreach ($capital->groupBy('owner_id') as $ownerId => $entries) {
            $name = optional($entries->first()->owner)->name ?: 'Owner';
            $url = route('crm.owner_capital.index', ['owner' => $ownerId]);
            $equityLines[] = ["Owner's Capital — {$name}", (float) $entries->where('type', 'capital')->sum('amount'), $url, 'capital'];
            $drawn = (float) $entries->where('type', 'drawing')->sum('amount');
            if ($drawn > 0.004) $equityLines[] = ["Less: Drawings — {$name}", -$drawn, $url, 'drawings'];
        }
        // Stock that came onto the books without a bill: on hand on the start day (its old bills were already costs) and opening stock since.
        if ($stockOnBooks) $equityLines[] = ['Opening Stock (brought onto books)', $this->stock()->openingStockAsOf($end), $inventoryUrl, 'opening_stock'];
        $yearStart = $end->copy()->startOfYear();
        $priorEnd = $yearStart->copy()->subDay()->endOfDay();
        $equityLines[] = ['Retained Earnings (before ' . $yearStart->year . ')', $this->plTotals(Carbon::create(2000, 1, 1), $priorEnd)['net'],
            route('crm.financial_reports.index', ['report' => 'profit-loss', 'period' => 'custom', 'date_from' => '2000-01-01', 'date_to' => $priorEnd->toDateString()]), 'retained'];
        $equityLines[] = ['Profit / (Loss) ' . $yearStart->year . ' to date', $this->plTotals($yearStart, $end)['net'],
            route('crm.financial_reports.index', ['report' => 'profit-loss', 'period' => 'custom', 'date_from' => $yearStart->toDateString(), 'date_to' => $end->toDateString()]), 'profit'];
        $recorded = array_sum(array_column($equityLines, 1));
        $equityLines[] = [$hasAccounts || $capital->isNotEmpty() ? 'Opening Balances not recorded (balancing figure)' : 'Suspense (opening balances not recorded)',
            $equity - $recorded, null, 'balancing'];

        return [
            'as_of' => $end,
            'assets' => $assets, 'assets_total' => $totalAssets,
            'liabilities' => $liabilities, 'liabilities_total' => $totalLiabilities,
            'equity' => $equityLines,
            'equity_total' => $equity,
            'cash_lines' => count($cashLines),
            'has_bank_accounts' => $hasAccounts,
        ];
    }

    /** Balance Sheet report: as of the period's end, compared with the day before the period starts. */
    protected function balanceSheet(): array
    {
        $compareDate = $this->from->copy()->subDay();
        $now = $this->balanceSheetAsOf($this->to);
        $then = $this->balanceSheetAsOf($compareDate);
        // Across two different years, earlier-years profit and this-year profit are shown as one retained-earnings line.
        if ($this->to->year !== $compareDate->year) {
            foreach ([&$now, &$then] as &$sheet) {
                $retained = collect($sheet['equity'])->whereIn(3, ['retained', 'profit']);
                $sheet['equity'] = collect($sheet['equity'])->whereNotIn(3, ['retained', 'profit'])->values()->all();
                array_splice($sheet['equity'], count($sheet['equity']) - 1, 0, [['Retained Earnings (all profit to date)', (float) $retained->sum(1),
                    route('crm.financial_reports.index', ['report' => 'profit-loss']), 'retained']]);
            }
            unset($sheet);
        }
        $label = fn ($name) => preg_replace('/\s*\(\d+\)$/', '', $name);   // "Not booked to any account (12)" matches across dates

        $rows = [];
        $section = function (string $title, string $key, string $totalLabel) use (&$rows, $now, $then, $label) {
            $rows[] = ['_style' => 'section', 'cells' => [$title, null, null, null]];
            $nowLines = collect($now[$key])->keyBy(fn ($l) => $label($l[0]));
            $thenLines = collect($then[$key])->keyBy(fn ($l) => $label($l[0]));
            foreach ($nowLines->keys()->merge($thenLines->keys())->unique() as $name) {
                $a = (float) ($nowLines[$name][1] ?? 0);
                $b = (float) ($thenLines[$name][1] ?? 0);
                if (abs($a) < 0.005 && abs($b) < 0.005) continue;
                $url = $nowLines[$name][2] ?? ($thenLines[$name][2] ?? null);
                $text = $nowLines[$name][0] ?? $name;
                $rows[] = [$url ? $this->link($text, $url) : $text, $a, $b, $a - $b];
            }
            $rows[] = ['_style' => 'subtotal', 'cells' => [$totalLabel, $now[$key . '_total'], $then[$key . '_total'], $now[$key . '_total'] - $then[$key . '_total']]];
        };
        $section('Assets', 'assets', 'Total Assets');
        $section('Liabilities', 'liabilities', 'Total Liabilities');
        $section('Equity', 'equity', 'Total Equity');
        $rows[] = ['_style' => 'grand', 'cells' => ['Total Liabilities + Equity', $now['liabilities_total'] + $now['equity_total'],
            $then['liabilities_total'] + $then['equity_total'], ($now['liabilities_total'] + $now['equity_total']) - ($then['liabilities_total'] + $then['equity_total'])]];

        $balancing = (float) collect($now['equity'])->firstWhere(3, 'balancing')[1];

        return [
            'range_label' => 'As of ' . $this->to->format('d M Y') . ' (vs ' . $compareDate->format('d M Y') . ')',
            'subtitle' => 'What the business has (assets), what it owes (liabilities) and the owners\' share (equity) as of ' . $this->to->format('d M Y')
                . ', compared with ' . $compareDate->format('d M Y') . ' (the day before the period starts).',
            'cards' => [
                ['label' => 'Total Assets', 'value' => $now['assets_total'], 'tone' => 'brand', 'icon' => 'fa-wallet', 'note' => 'as of ' . $this->to->format('d M Y')],
                ['label' => 'Total Liabilities', 'value' => $now['liabilities_total'], 'tone' => 'red', 'icon' => 'fa-file-invoice-dollar'],
                ['label' => 'Equity (Net Worth)', 'value' => $now['equity_total'], 'tone' => $now['equity_total'] < 0 ? 'red' : 'green', 'icon' => 'fa-user-tie', 'note' => 'assets − liabilities'],
                ['label' => 'Not yet explained', 'value' => $balancing, 'tone' => abs($balancing) > 0.5 ? 'red' : 'green', 'icon' => 'fa-question-circle', 'note' => 'balancing figure in Equity'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Balance Sheet', 'columns' => [['Account', 'text'], ['As of ' . $this->to->format('d M Y'), 'money'], ['As of ' . $compareDate->format('d M Y'), 'money'], ['Change', 'money']], 'rows' => $rows],
            ],
            'notes' => [
                'Pick the period: the balance sheet is "as of" its last day and compared with the day before its first day (e.g. This Year = today vs 31 Dec last year).',
                'Bank & cash lines are each account\'s balance on that date (opening balance + money in − out ± transfers). Entries not booked to any account are shown on their own line.',
                'Fixed assets are at cost (depreciation is not posted yet). Vendors payable on an earlier date is rebuilt from purchase and payment dates.',
                'Inventory is stock on hand at average cost, in the books from ' . InventoryAccounting::from()->format('d M Y') . ': stock bought on purchase invoices from then on, plus the stock on hand that day (Opening Stock in Equity — its old bills were already costs).',
                '"Opening Balances not recorded" is what the CRM cannot explain yet — mainly opening bank balances and capital that are not entered. Record them under Owner\'s Capital to bring it towards zero.',
            ],
        ];
    }

    /** Trial Balance as of the period's end: every account on its debit or credit side; the two totals agree. */
    protected function trialBalance(): array
    {
        $end = $this->to;
        $yearStart = $end->copy()->startOfYear();
        $sheet = $this->balanceSheetAsOf($end);
        $pl = $this->plTotals($yearStart, $end);

        $rows = [];
        $debit = 0.0;
        $credit = 0.0;
        // $normal: which side a positive balance sits on (assets / expenses = debit; liabilities / equity / income = credit).
        $add = function (string $name, float $amount, ?string $url, string $normal) use (&$rows, &$debit, &$credit) {
            if (abs($amount) < 0.005) return;
            $onDebit = ($normal === 'debit') === ($amount >= 0);
            $value = abs($amount);
            $onDebit ? $debit += $value : $credit += $value;
            $rows[] = [$url ? $this->link($name, $url) : $name, $onDebit ? $value : null, $onDebit ? null : $value];
        };
        $rows[] = ['_style' => 'section', 'cells' => ['Assets', null, null]];
        foreach ($sheet['assets'] as [$name, $amount, $url]) $add($name, $amount, $url, 'debit');
        $rows[] = ['_style' => 'section', 'cells' => ['Liabilities', null, null]];
        foreach ($sheet['liabilities'] as [$name, $amount, $url]) $add($name, $amount, $url, 'credit');
        $rows[] = ['_style' => 'section', 'cells' => ['Equity', null, null]];
        foreach ($sheet['equity'] as [$name, $amount, $url, $kind]) {
            if ($kind !== 'profit') $add($name, $amount, $url, 'credit');   // this year's profit is the income and expense accounts below
        }
        foreach (['income' => ['Income', 'credit'], 'cos' => ['Cost of Sales', 'debit'], 'opex' => ['Operating Expenses', 'debit']] as $group => [$title, $side]) {
            $rows[] = ['_style' => 'section', 'cells' => [$title . ' (' . $yearStart->format('d M') . ' – ' . $end->format('d M Y') . ')', null, null]];
            ksort($pl[$group]);
            foreach ($pl[$group] as $category => $amount) $add($category, $amount, $this->drill($group, $category), $side);
        }
        $rows[] = ['_style' => 'grand', 'cells' => ['Total', $debit, $credit]];

        return [
            'range_label' => 'As of ' . $end->format('d M Y'),
            'subtitle' => 'Every account with its balance on the debit or credit side as of ' . $end->format('d M Y') . '. Income and expenses are for '
                . $yearStart->format('d M Y') . ' – ' . $end->format('d M Y') . '; earlier profit sits in Retained Earnings.',
            'cards' => [
                ['label' => 'Total Debit', 'value' => $debit, 'tone' => 'brand', 'icon' => 'fa-arrow-left'],
                ['label' => 'Total Credit', 'value' => $credit, 'tone' => 'brand', 'icon' => 'fa-arrow-right'],
                ['label' => 'Difference', 'value' => round($debit - $credit, 2), 'tone' => abs($debit - $credit) < 0.01 ? 'green' : 'red', 'icon' => 'fa-balance-scale', 'note' => 'should be 0'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Trial Balance', 'columns' => [['Account', 'text'], ['Debit', 'money'], ['Credit', 'money']], 'rows' => $rows],
            ],
            'notes' => [
                'Debit side: assets, drawings and expenses. Credit side: liabilities, capital, retained earnings and income. A negative balance moves to the other side.',
                'The CRM is not double-entry yet, so the "Opening Balances not recorded" line is what makes the two sides agree — the smaller it is, the more complete the books.',
            ],
        ];
    }

    /** Owner's Equity: how each owner's capital and the retained profit moved during the period. */
    protected function ownersEquity(): array
    {
        $openDate = $this->from->copy()->subDay()->endOfDay();
        $opening = $this->balanceSheetAsOf($openDate);
        $closing = $this->balanceSheetAsOf($this->to);
        $entries = $this->capitalEntries();

        $rows = [];
        $totals = [0, 0, 0, 0, 0, 0];
        $push = function ($name, $url, float $open, float $in, float $out, float $profit, float $other) use (&$rows, &$totals) {
            $close = $open + $in - $out + $profit + $other;
            $values = [$open, $in, -$out, $profit, $other, $close];
            foreach ($values as $i => $v) $totals[$i] += $v;
            $rows[] = array_merge([$url ? $this->link($name, $url) : $name], array_map(fn ($v) => abs($v) < 0.005 ? null : $v, $values));
        };
        $rows[] = ['_style' => 'section', 'cells' => ["Owner's Capital", null, null, null, null, null, null]];
        foreach ($entries->groupBy('owner_id') as $ownerId => $list) {
            $before = $list->filter(fn ($c) => $c->entry_date->lte($openDate));
            $during = $list->filter(fn ($c) => $this->inRange($c->entry_date));
            $push(optional($list->first()->owner)->name ?: 'Owner', route('crm.owner_capital.index', ['owner' => $ownerId]),
                (float) $before->where('type', 'capital')->sum('amount') - (float) $before->where('type', 'drawing')->sum('amount'),
                (float) $during->where('type', 'capital')->sum('amount'), (float) $during->where('type', 'drawing')->sum('amount'), 0, 0);
        }
        if ($entries->isEmpty()) $rows[] = [$this->link('No owners recorded yet — add them under Owner\'s Capital', route('crm.owner_capital.index')), null, null, null, null, null, null];

        $rows[] = ['_style' => 'section', 'cells' => ['Retained Earnings', null, null, null, null, null, null]];
        $openingProfit = $this->plTotals(Carbon::create(2000, 1, 1), $openDate)['net'];
        $periodProfit = $this->plTotals($this->from, $this->to)['net'];
        $push('Retained Earnings', route('crm.financial_reports.index', ['report' => 'profit-loss']), $openingProfit, 0, 0, $periodProfit, 0);

        $openStock = (float) (collect($opening['equity'])->firstWhere(3, 'opening_stock')[1] ?? 0);
        $closeStock = (float) (collect($closing['equity'])->firstWhere(3, 'opening_stock')[1] ?? 0);
        if (abs($openStock) + abs($closeStock) > 0.004) {
            $rows[] = ['_style' => 'section', 'cells' => ['Opening Stock', null, null, null, null, null, null]];
            $push('Opening Stock (brought onto books)', route('crm.financial_reports.index', ['report' => 'inventory']), $openStock, 0, 0, 0, $closeStock - $openStock);
        }

        $rows[] = ['_style' => 'section', 'cells' => ['Not Yet Explained', null, null, null, null, null, null]];
        $openBal = (float) collect($opening['equity'])->firstWhere(3, 'balancing')[1];
        $closeBal = (float) collect($closing['equity'])->firstWhere(3, 'balancing')[1];
        $push('Opening Balances not recorded (balancing figure)', null, $openBal, 0, 0, 0, $closeBal - $openBal);
        $rows[] = ['_style' => 'grand', 'cells' => array_merge(['Total Equity'], $totals)];

        $introduced = (float) $entries->filter(fn ($c) => $this->inRange($c->entry_date))->where('type', 'capital')->sum('amount');
        $drawn = (float) $entries->filter(fn ($c) => $this->inRange($c->entry_date))->where('type', 'drawing')->sum('amount');

        return [
            'subtitle' => 'How the owners\' equity changed from ' . $openDate->format('d M Y') . ' to ' . $this->to->format('d M Y') . ': capital put in, drawings taken out and the profit or loss of the period.',
            'cards' => [
                ['label' => 'Opening Equity', 'value' => $opening['equity_total'], 'tone' => 'brand', 'icon' => 'fa-flag', 'note' => 'as of ' . $openDate->format('d M Y')],
                ['label' => 'Capital Put In', 'value' => $introduced, 'tone' => 'green', 'icon' => 'fa-arrow-down'],
                ['label' => 'Drawings', 'value' => $drawn, 'tone' => 'red', 'icon' => 'fa-arrow-up'],
                ['label' => 'Profit / (Loss)', 'value' => $periodProfit, 'tone' => $periodProfit < 0 ? 'red' : 'green', 'icon' => 'fa-chart-line'],
                ['label' => 'Closing Equity', 'value' => $closing['equity_total'], 'tone' => 'brand', 'icon' => 'fa-user-tie', 'note' => 'as of ' . $this->to->format('d M Y')],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Statement of Changes in Equity', 'columns' => [['', 'text'], ['Opening', 'money'], ['Capital In', 'money'], ['Drawings', 'money'],
                    ['Profit / (Loss)', 'money'], ['Other Changes', 'money'], ['Closing', 'money']], 'rows' => $rows],
            ],
            'notes' => [
                'Capital and drawings come from Owner\'s Capital; profit / loss from the Profit & Loss report for the same period.',
                '"Other Changes" on the balancing line is movement the CRM cannot explain yet (e.g. opening balances or capital not recorded).',
            ],
        ];
    }

    /** Day Book: everything recorded in the period, day by day — invoices and bills raised, money in and out, transfers. */
    protected function dayBook(): array
    {
        $entries = collect();
        $typeOf = ['invoice' => 'Sales Invoice', 'order' => 'Sales Order', 'sale' => 'Customer Sale'];
        foreach ($this->incomeLines($this->from, $this->to)->where('source', '!=', 'direct') as $l) {   // direct receipts are listed as money in below
            $entries->push([$l->date, 0, $typeOf[$l->source] ?? $l->category, $this->link($l->ref, $l->url), $l->party, $l->gross, null, null]);
        }
        foreach (VendorPurchase::get() as $p) {
            $date = Carbon::parse($p->purchase_date ?: $p->created_at);
            if (!$this->inRange($date)) continue;
            $entries->push([$date, 1, 'Vendor Purchase (bill)', $this->link($p->invoice_number ?: 'Purchase #' . $p->id, route('crm.vendor_purchases.index', array_filter(['vendor_id' => $p->vendor_id, 'search' => $p->invoice_number]))),
                $p->vendor_name . ($p->item_name ? ' — ' . $p->item_name : ''), (float) $p->total_amount, null, null]);
        }
        foreach (CrmPurchaseInvoice::get() as $inv) {
            if (!$this->inRange($inv->issue_date)) continue;
            $entries->push([$inv->issue_date, 1, 'Purchase Invoice (bill)', $this->link($inv->number, route('crm.purchase_invoices.show', $inv->id)), $inv->vendor_name, (float) $inv->total, null, null]);
        }
        foreach ($this->receipts()->filter(fn ($r) => $this->inRange($r->date)) as $r) {
            $entries->push([$r->date, 2, $r->source_label, $this->link($r->ref ?: '—', $r->url), $r->customer, null, (float) $r->amount, null]);
        }
        foreach ($this->payments()->whereNull('duplicate_of')->filter(fn ($p) => $this->inRange($p->date)) as $p) {
            $entries->push([$p->date, 3, $p->source_label, $this->link($p->ref ?: '—', $p->url), $p->party . ($p->detail ? ' — ' . $p->detail : ''), null, null, (float) $p->amount]);
        }
        foreach ($this->capitalEntries()->filter(fn ($c) => $this->inRange($c->entry_date)) as $c) {
            $moves = $c->isCash();
            $entries->push([$c->entry_date, $c->type === 'drawing' ? 3 : 2, $c->type === 'drawing' ? "Owner's Drawing" : "Owner's Capital" . ($moves ? '' : ' (opening / non-cash)'),
                $this->link($c->number, route('crm.owner_capital.index', ['owner' => $c->owner_id])), optional($c->owner)->name,
                $moves ? null : (float) $c->amount, $moves && $c->type === 'capital' ? (float) $c->amount : null, $moves && $c->type === 'drawing' ? (float) $c->amount : null]);
        }
        foreach (CrmBankTransfer::with(['from', 'to'])->get() as $t) {
            if (!$this->inRange($t->transfer_date)) continue;
            $entries->push([$t->transfer_date, 4, 'Transfer', $t->reference ?: '—',
                number_format((float) $t->amount, 2) . ' · ' . optional($t->from)->name . ' → ' . optional($t->to)->name, null, null, null]);
        }

        $rows = [];
        $sorted = $entries->sortBy(fn ($e) => [$e[0]->timestamp, $e[1]])->values();
        foreach ($sorted->groupBy(fn ($e) => $e[0]->toDateString()) as $day => $list) {
            $rows[] = ['_style' => 'section', 'cells' => [Carbon::parse($day)->format('l, d M Y'), null, null, null, null, null]];
            foreach ($list as $e) $rows[] = [$e[2], $e[3], $e[4], $e[5], $e[6], $e[7]];
        }
        $billed = (float) $entries->sum(fn ($e) => $e[5] ?? 0);
        $in = (float) $entries->sum(fn ($e) => $e[6] ?? 0);
        $out = (float) $entries->sum(fn ($e) => $e[7] ?? 0);

        return [
            'subtitle' => 'Everything recorded between ' . $this->from->format('d M Y') . ' and ' . $this->to->format('d M Y') . ', day by day: invoices and bills raised, money received and paid, owner\'s capital and transfers.',
            'cards' => [
                ['label' => 'Entries', 'value' => $entries->count(), 'format' => 'number', 'tone' => 'brand', 'icon' => 'fa-list'],
                ['label' => 'Invoiced / Billed', 'value' => $billed, 'tone' => 'brand', 'icon' => 'fa-file-invoice', 'note' => 'documents raised (no money moved)'],
                ['label' => 'Money In', 'value' => $in, 'tone' => 'green', 'icon' => 'fa-arrow-down'],
                ['label' => 'Money Out', 'value' => $out, 'tone' => 'red', 'icon' => 'fa-arrow-up'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Day Book', 'columns' => [['Type', 'text'], ['Reference', 'text'], ['Party / Detail', 'text'], ['Invoiced / Billed', 'money'], ['Money In', 'money'], ['Money Out', 'money']],
                    'rows' => $rows, 'footer' => ['Total', null, null, $billed, $in, $out]],
            ],
            'notes' => [
                'Invoiced / Billed = sales invoices and supplier bills raised (they are owed, not paid yet). Money In / Out = actual receipts and payments.',
                'Transfers between your own bank / cash accounts are listed with their amount in the detail; they are not money in or out of the business.',
            ],
        ];
    }
}
