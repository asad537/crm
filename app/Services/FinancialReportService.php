<?php

namespace App\Services;

use App\CrmEmail;
use App\CrmEmployee;
use App\CrmExpense;
use App\CrmFixedAsset;
use App\CrmIncomeEntry;
use App\CrmPayrollLine;
use App\CrmPurchaseInvoice;
use App\CustomerSale;
use App\DesignJob;
use App\Http\Controllers\Crm\PaymentController;
use App\Http\Controllers\Crm\ReceiptController;
use App\SalesOrder;
use App\Support\CrmWorkspaceContext;
use App\VendorPurchase;
use App\VendorPurchasePayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Al Massa financial reports built from the data the CRM already holds (invoices, customer sales, rent,
 * vendor purchases, purchase invoices, demand-request and general expenses, salaries, receipts, payments).
 *
 * Profit & Loss is accrual-based (by invoice / bill date, amounts without VAT); Cash Flow uses actual
 * receipts and payments. Opening cash/bank balances are not recorded yet, so cash figures are movements only.
 * Every report returns the same shape (cards, chart, tables, notes) so one page and one export render them all.
 */
class FinancialReportService
{
    use FinancialReports\StatementReports;
    use FinancialReports\PartyReports;
    use FinancialReports\OperationsReports;

    /** report key => [title, icon, group]; the tab bar shows them by group. */
    const REPORTS = [
        'balance-sheet' => ['Balance Sheet', 'fa-balance-scale', 'Statements'],
        'profit-loss' => ['Profit & Loss', 'fa-chart-line', 'Statements'],
        'trial-balance' => ['Trial Balance', 'fa-th-list', 'Statements'],
        'cash-flow' => ['Cash Flow', 'fa-exchange-alt', 'Statements'],
        'owners-equity' => ["Owner's Equity", 'fa-user-tie', 'Statements'],
        'day-book' => ['Day Book', 'fa-book', 'Statements'],
        'customer-statement' => ['Customer Statement', 'fa-file-signature', 'Customers & Vendors'],
        'receivables' => ['Receivables Aging', 'fa-hand-holding-usd', 'Customers & Vendors'],
        'payables' => ['Payables Aging', 'fa-file-invoice-dollar', 'Customers & Vendors'],
        'sales' => ['Sales Report', 'fa-shopping-bag', 'Sales & Costs'],
        'sales-products' => ['Sales by Product / Salesperson', 'fa-tags', 'Sales & Costs'],
        'expenses' => ['Expense Analysis', 'fa-chart-pie', 'Sales & Costs'],
        'budget' => ['Budget vs Actual', 'fa-bullseye', 'Sales & Costs'],
        'jobs' => ['Job Profitability', 'fa-briefcase', 'Sales & Costs'],
        'salaries' => ['Salary Report', 'fa-money-check-alt', 'Payroll'],
        'gratuity' => ['End-of-Service Gratuity', 'fa-hourglass-end', 'Payroll'],
        'vat' => ['VAT Report', 'fa-percent', 'Tax'],
        'vat-return' => ['VAT Return (VAT 201)', 'fa-landmark', 'Tax'],
        'assets' => ['Fixed Assets', 'fa-boxes', 'Assets & Bank'],
        'inventory' => ['Inventory', 'fa-cubes', 'Assets & Bank'],
        'bank-reconciliation' => ['Bank Reconciliation', 'fa-check-double', 'Assets & Bank'],
    ];

    /** Extra filters some reports take from the URL (customer, emirate...). */
    protected array $params = [];

    public function withParams(array $params): self
    {
        $this->params = array_filter($params, fn ($v) => $v !== null && $v !== '');

        return $this;
    }

    /** Cost categories that make up Cost of Sales; everything else is an operating expense. */
    const COST_OF_SALES = ['Production Purchases', 'Stock & Raw Material', 'Supplier Invoices', InventoryAccounting::USED, InventoryAccounting::MOVED_IN];

    /** Spellings used across vendor purchases, demand requests and expenses, mapped to one name. */
    const CATEGORY_ALIASES = [
        'production' => 'Production Purchases', 'production expense' => 'Production Purchases',
        'stock / inventory' => 'Stock & Raw Material', 'raw material' => 'Stock & Raw Material',
        'consumable' => 'Consumables', 'consumable expense' => 'Consumables', 'consumables' => 'Consumables',
        'admin / general' => 'Admin & General', 'admin/general expense' => 'Admin & General',
        'salary' => 'Salaries & Wages', 'salaries & wages' => 'Salaries & Wages',
        'tax / govt registration' => 'Government Fees & Taxes', 'government fees & visa' => 'Government Fees & Taxes',
    ];

    const AGING_BUCKETS = ['0–30 days', '31–60 days', '61–90 days', '90+ days'];

    protected Carbon $from;
    protected Carbon $to;
    protected array $memo = [];

    public function __construct(Carbon $from, Carbon $to)
    {
        $this->from = $from->copy()->startOfDay();
        $this->to = $to->copy()->endOfDay();
    }

    public function build(string $report): array
    {
        $method = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $report))));
        $result = $this->$method();
        $result['key'] = $report;
        $result['title'] = self::REPORTS[$report][0];

        return $result;
    }

    // ───────────────────────────── Reports ─────────────────────────────

    protected function profitLoss(): array
    {
        $length = $this->from->diffInDays($this->to) + 1;
        $prevTo = $this->from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($length - 1)->startOfDay();

        $now = $this->plTotals($this->from, $this->to);
        $prev = $this->plTotals($prevFrom, $prevTo);

        $rows = [];
        $section = function (string $title, string $group, string $totalLabel) use (&$rows, $now, $prev) {
            $rows[] = ['_style' => 'section', 'cells' => [$title, null, null, null]];
            $categories = collect(array_keys($now[$group]))->merge(array_keys($prev[$group]))->unique()->sort()->values();
            foreach ($categories as $category) {
                $rows[] = [$this->link($category, $this->drill($group, $category)), $now[$group][$category] ?? 0, $prev[$group][$category] ?? 0,
                    $this->change($now[$group][$category] ?? 0, $prev[$group][$category] ?? 0)];
            }
            if ($categories->isEmpty()) $rows[] = ['—', 0, 0, null];
            $a = array_sum($now[$group]); $b = array_sum($prev[$group]);
            $rows[] = ['_style' => 'subtotal', 'cells' => [$this->link($totalLabel, $this->drill($group)), $a, $b, $this->change($a, $b)]];
        };
        $section('Income', 'income', 'Total Income');
        $section('Cost of Sales', 'cos', 'Total Cost of Sales');
        $rows[] = ['_style' => 'total', 'cells' => ['Gross Profit', $now['gross'], $prev['gross'], $this->change($now['gross'], $prev['gross'])]];
        $section('Operating Expenses', 'opex', 'Total Operating Expenses');
        $rows[] = ['_style' => 'grand', 'cells' => ['Net Profit / (Loss)', $now['net'], $prev['net'], $this->change($now['net'], $prev['net'])]];

        $monthRows = [];
        $chartIncome = $chartCost = $chartNet = [];
        foreach ($this->months() as [$label, $start, $end]) {
            $m = $this->plTotals($start, $end);
            $costs = array_sum($m['cos']) + array_sum($m['opex']);
            $monthRows[] = [$label, array_sum($m['income']), array_sum($m['cos']), array_sum($m['opex']), $m['net']];
            $chartIncome[] = round(array_sum($m['income']), 2);
            $chartCost[] = round($costs, 2);
            $chartNet[] = round($m['net'], 2);
        }

        $income = array_sum($now['income']);
        return [
            'subtitle' => 'Income minus costs by invoice / bill date (amounts without VAT), compared with the previous ' . $length . ' days.',
            'cards' => [
                ['label' => 'Total Income', 'value' => $income, 'tone' => 'brand', 'icon' => 'fa-arrow-down'],
                ['label' => 'Total Costs', 'value' => array_sum($now['cos']) + array_sum($now['opex']), 'tone' => 'red', 'icon' => 'fa-arrow-up'],
                ['label' => 'Gross Profit', 'value' => $now['gross'], 'tone' => $now['gross'] >= 0 ? 'green' : 'red', 'icon' => 'fa-layer-group'],
                ['label' => 'Net Profit / (Loss)', 'value' => $now['net'], 'tone' => $now['net'] >= 0 ? 'green' : 'red', 'icon' => 'fa-coins',
                    'note' => $income > 0 ? 'Net margin ' . number_format($now['net'] / $income * 100, 1) . '%' : null],
            ],
            'chart' => count($monthRows) > 1 ? ['type' => 'bar', 'labels' => array_column($monthRows, 0), 'datasets' => [
                ['label' => 'Income', 'data' => $chartIncome, 'color' => '#16a34a'],
                ['label' => 'Costs', 'data' => $chartCost, 'color' => '#dc2626'],
                ['label' => 'Net Profit', 'data' => $chartNet, 'color' => '#06265e', 'type' => 'line'],
            ]] : null,
            'tables' => [
                ['title' => 'Profit & Loss Statement', 'columns' => [['Item', 'text'], ['This Period', 'money'], ['Previous Period', 'money'], ['Change', 'percent']], 'rows' => $rows],
                ['title' => 'Month by Month', 'columns' => [['Month', 'text'], ['Income', 'money'], ['Cost of Sales', 'money'], ['Operating Expenses', 'money'], ['Net Profit', 'money']],
                    'rows' => $monthRows, 'footer' => ['Total', array_sum(array_column($monthRows, 1)), array_sum(array_column($monthRows, 2)), array_sum(array_column($monthRows, 3)), array_sum(array_column($monthRows, 4))]],
            ],
            'notes' => [
                'Fixed asset purchases are not expenses here (no depreciation yet); they are listed in the Fixed Assets report.',
                'Salaries count from approved salary sheets (gross minus absence and other deductions); advances are loans, not expenses.',
                'Demand-request payments that are also recorded as a paid vendor purchase are counted once.',
            ],
        ];
    }

    /** Cash flow statement lines, in the order shown: section => lines. */
    const CASH_FLOW_LINES = [
        'operating' => ['Cash received from customers', 'Rent & other income received', 'Paid to vendors / suppliers', 'Paid on purchase invoices',
            'Paid for expenses', 'Salaries paid', 'Salary advances given'],
        'investing' => ['Purchase of fixed assets', 'Sale of fixed assets'],
        'financing' => ["Owner's capital introduced", "Owner's drawings", 'Loans received / (repaid)'],
    ];

    /**
     * Cash Flow Statement (direct method): money in and out split into operating, investing and financing activities,
     * with cash & bank at the start and end of the period, compared with the previous period of the same length.
     */
    protected function cashFlow(): array
    {
        $length = $this->from->diffInDays($this->to) + 1;
        $prevTo = $this->from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($length - 1)->startOfDay();
        $now = $this->cashFlowTotals($this->from, $this->to);
        $prev = $this->cashFlowTotals($prevFrom, $prevTo);

        $link = function (string $line) {
            $dates = ['date_from' => $this->from->toDateString(), 'date_to' => $this->to->toDateString()];
            $sources = ['Paid to vendors / suppliers' => 'vendor', 'Paid on purchase invoices' => 'purchase', 'Salaries paid' => 'salary', 'Salary advances given' => 'advance'];
            if (str_starts_with($line, 'Cash received') || str_starts_with($line, 'Rent')) return route('crm.receipts.index', $dates);
            if (isset($sources[$line])) return route('crm.payments.index', $dates + ['source' => $sources[$line]]);
            if ($line === 'Paid for expenses') return route('crm.payments.index', $dates);
            if (str_contains($line, 'fixed assets')) return route('crm.fixed_assets.index');
            if (str_starts_with($line, "Owner's")) return route('crm.owner_capital.index');
            return null;
        };

        $rows = [];
        $titles = ['operating' => 'A. Operating Activities (day-to-day business)', 'investing' => 'B. Investing Activities (fixed assets)', 'financing' => 'C. Financing Activities (owners and loans)'];
        $netLabels = ['operating' => 'Net cash from operating activities', 'investing' => 'Net cash from investing activities', 'financing' => 'Net cash from financing activities'];
        foreach (self::CASH_FLOW_LINES as $section => $lines) {
            $rows[] = ['_style' => 'section', 'cells' => [$titles[$section], null, null, null]];
            foreach ($lines as $line) {
                $a = $now['lines'][$line] ?? 0.0;
                $b = $prev['lines'][$line] ?? 0.0;
                $url = $link($line);
                $rows[] = [$url && abs($a) > 0.004 ? $this->link($line, $url) : $line, abs($a) < 0.005 ? null : $a, abs($b) < 0.005 ? null : $b, abs($a - $b) < 0.005 ? null : $a - $b];
            }
            $rows[] = ['_style' => 'subtotal', 'cells' => [$netLabels[$section], $now[$section], $prev[$section], $now[$section] - $prev[$section]]];
        }
        $rows[] = ['_style' => 'grand', 'cells' => ['Net increase / (decrease) in cash (A + B + C)', $now['net'], $prev['net'], $now['net'] - $prev['net']]];
        $rows[] = ['_style' => 'total', 'cells' => ['Cash & bank at the start', $now['opening'], $prev['opening'], null]];
        if (abs($now['other']) > 0.5 || abs($prev['other']) > 0.5) {
            $rows[] = ['Opening balances entered / entries outside an account\'s dates', $now['other'] ?: null, $prev['other'] ?: null, null];
        }
        $rows[] = ['_style' => 'total', 'cells' => [$this->link('Cash & bank at the end (= Bank & Cash accounts)', route('crm.bank_accounts.index')), $now['closing'], $prev['closing'], $now['closing'] - $prev['closing']]];

        // Month by month, with the real cash & bank balance at each month end.
        $monthRows = [];
        $ledger = new BankLedgerService();
        foreach ($this->months() as [$label, $start, $end]) {
            $m = $this->cashFlowTotals($start, $end, $ledger, false);
            $monthRows[] = [$label, $m['operating'], $m['investing'], $m['financing'], $m['net'], $this->cashAt($end, $ledger)];
        }

        // Money in / out by payment method.
        $methods = [];
        foreach ($this->cashMoves()->filter(fn ($m) => $this->inRange($m->date)) as $m) {
            $group = $this->methodGroup($m->method);
            $methods[$group][$m->amount >= 0 ? 'in' : 'out'] = ($methods[$group][$m->amount >= 0 ? 'in' : 'out'] ?? 0) + abs($m->amount);
        }
        ksort($methods);
        $methodRows = collect($methods)->map(fn ($v, $method) => [$method, $v['in'] ?? 0, $v['out'] ?? 0, ($v['in'] ?? 0) - ($v['out'] ?? 0)])->values()->all();

        return [
            'subtitle' => 'Where cash came from and where it went between ' . $this->from->format('d M Y') . ' and ' . $this->to->format('d M Y')
                . ' — operating, investing and financing activities (direct method), compared with ' . $prevFrom->format('d M Y') . ' – ' . $prevTo->format('d M Y') . '.',
            'cards' => [
                ['label' => 'Cash & Bank at Start', 'value' => $now['opening'], 'tone' => 'brand', 'icon' => 'fa-flag', 'note' => $this->from->copy()->subDay()->format('d M Y')],
                ['label' => 'Net Cash from Operations', 'value' => $now['operating'], 'tone' => $now['operating'] < 0 ? 'red' : 'green', 'icon' => 'fa-industry'],
                ['label' => 'Net Change in Cash', 'value' => $now['net'], 'tone' => $now['net'] < 0 ? 'red' : 'green', 'icon' => 'fa-exchange-alt', 'note' => 'operating + investing + financing'],
                ['label' => 'Cash & Bank at End', 'value' => $now['closing'], 'tone' => $now['closing'] < 0 ? 'red' : 'brand', 'icon' => 'fa-wallet', 'note' => $this->to->format('d M Y')],
            ],
            'chart' => ['type' => 'bar', 'labels' => array_column($monthRows, 0), 'datasets' => [
                ['label' => 'Operating', 'data' => array_map(fn ($v) => round($v, 2), array_column($monthRows, 1)), 'color' => '#f45a24'],
                ['label' => 'Investing', 'data' => array_map(fn ($v) => round($v, 2), array_column($monthRows, 2)), 'color' => '#9333ea'],
                ['label' => 'Financing', 'data' => array_map(fn ($v) => round($v, 2), array_column($monthRows, 3)), 'color' => '#16a34a'],
                ['label' => 'Cash & bank at month end', 'type' => 'line', 'data' => array_map(fn ($v) => round($v, 2), array_column($monthRows, 5)), 'color' => '#06265e'],
            ]],
            'tables' => [
                ['title' => 'Cash Flow Statement', 'columns' => [['', 'text'], ['This Period', 'money'], ['Previous Period', 'money'], ['Change', 'money']], 'rows' => $rows],
                ['title' => 'Month by Month', 'columns' => [['Month', 'text'], ['Operating', 'money'], ['Investing', 'money'], ['Financing', 'money'], ['Net Change', 'money'], ['Cash & Bank at Month End', 'money']],
                    'rows' => $monthRows, 'footer' => ['Total', $now['operating'], $now['investing'], $now['financing'], $now['net'], $now['closing']]],
                ['title' => 'By Payment Method', 'columns' => [['Method', 'text'], ['Money In', 'money'], ['Money Out', 'money'], ['Net', 'money']], 'rows' => $methodRows],
            ],
            'notes' => [
                'Operating = receipts from customers and other income, less payments to vendors, purchase invoices, expenses, salaries and advances. Investing = fixed assets bought (incl. the asset part of a purchase invoice). Financing = owner\'s capital and drawings.',
                'Cash & bank at the start / end are the Bank & Cash account balances on those days (with entries not booked to any account). Transfers between your own accounts are not cash flow.',
                'Loans and sale of fixed assets are not recorded in the CRM yet, so those lines stay empty.',
            ],
        ];
    }

    /** Operating / investing / financing totals and each statement line for a window, plus cash at its start and end. */
    protected function cashFlowTotals(Carbon $from, Carbon $to, ?BankLedgerService $ledger = null, bool $withCash = true): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();
        $moves = $this->cashMoves()->filter(fn ($m) => $m->date->between($start, $end));
        $lines = $moves->groupBy('line')->map(fn ($g) => round((float) $g->sum('amount'), 2))->all();
        $section = fn ($s) => round((float) $moves->where('section', $s)->sum('amount'), 2);
        $result = ['lines' => $lines, 'operating' => $section('operating'), 'investing' => $section('investing'), 'financing' => $section('financing')];
        $result['net'] = round($result['operating'] + $result['investing'] + $result['financing'], 2);
        if ($withCash) {
            $ledger = $ledger ?: new BankLedgerService();
            // Cash at the start of the first day: an account opened that very day starts with its opening balance.
            $result['opening'] = round($this->cashAt($start->copy()->subDay(), $ledger)
                + (float) $ledger->accounts()->filter(fn ($a) => $a->opening_date->isSameDay($start))->sum('opening_balance'), 2);
            $result['closing'] = $this->cashAt($end, $ledger);
            // Whatever the movements don't explain: opening balances typed on accounts, entries before an account's opening date.
            $result['other'] = round($result['closing'] - $result['opening'] - $result['net'], 2);
        }

        return $result;
    }

    /** Cash & bank at the end of a day: every account's balance plus entries not booked to any account. */
    protected function cashAt(Carbon $date, BankLedgerService $ledger): float
    {
        if ($ledger->accounts()->isEmpty()) {
            return round((float) $this->cashMoves()->filter(fn ($m) => $m->date->lte($date->copy()->endOfDay()))->sum('amount'), 2);
        }
        $b = $ledger->balances($date);

        return round((float) $b['total'] + $b['unassigned']['in'] - $b['unassigned']['out'], 2);
    }

    /** Every cash movement with its statement section and line: amount + for money in, − for money out. */
    protected function cashMoves(): Collection
    {
        return $this->memo['cash_moves'] ??= (function () {
            $moves = collect();
            $push = function ($date, float $amount, string $section, string $line, $method) use (&$moves) {
                if (abs($amount) < 0.005) return;
                $moves->push((object) ['date' => Carbon::parse($date)->startOfDay(), 'amount' => round($amount, 2), 'section' => $section, 'line' => $line, 'method' => $method]);
            };
            foreach ($this->receipts() as $r) {
                $push($r->date, (float) $r->amount, 'operating', in_array($r->source, ['income', 'direct'], true) ? 'Rent & other income received' : 'Cash received from customers', $r->method);
            }
            // A purchase invoice payment is split: its fixed-asset share is investing, the rest operating.
            $assetShare = CrmPurchaseInvoice::with('items')->get()->mapWithKeys(fn ($i) => [$i->id => (float) $i->total > 0
                ? min(1, (float) $i->items->whereNotNull('fixed_asset_id')->sum('line_total') / (float) $i->total) : 0]);
            $invoiceOf = \App\CrmPurchaseInvoicePayment::pluck('purchase_invoice_id', 'id');
            $lineOf = ['vendor' => 'Paid to vendors / suppliers', 'direct' => 'Paid for expenses', 'account' => 'Paid for expenses', 'expense' => 'Paid for expenses',
                'salary' => 'Salaries paid', 'advance' => 'Salary advances given'];
            foreach ($this->payments()->whereNull('duplicate_of') as $p) {
                if ($p->source === 'purchase') {
                    $share = (float) ($assetShare[$invoiceOf[$p->link_id] ?? 0] ?? 0);
                    $push($p->date, -(float) $p->amount * (1 - $share), 'operating', 'Paid on purchase invoices', $p->method);
                    $push($p->date, -(float) $p->amount * $share, 'investing', 'Purchase of fixed assets', $p->method);
                    continue;
                }
                $push($p->date, -(float) $p->amount, 'operating', $lineOf[$p->source] ?? 'Paid for expenses', $p->method);
            }
            foreach (CrmFixedAsset::whereNotIn('payment_method', CrmFixedAsset::NOT_PAID_DIRECTLY)->get() as $a) {
                $push($a->purchase_date, -(float) $a->amount, 'investing', 'Purchase of fixed assets', $a->payment_method);
            }
            foreach ($this->capitalEntries()->filter->isCash() as $c) {
                $c->type === 'drawing'
                    ? $push($c->entry_date, -(float) $c->amount, 'financing', "Owner's drawings", $c->method)
                    : $push($c->entry_date, (float) $c->amount, 'financing', "Owner's capital introduced", $c->method);
            }

            return $moves;
        })();
    }

    protected function expenses(): array
    {
        $costs = $this->costLines($this->from, $this->to);
        $total = (float) $costs->sum('net');

        $byCategory = $costs->groupBy('category')->map(fn ($g, $c) => [$c, (float) $g->sum('net'), $total > 0 ? $g->sum('net') / $total * 100 : 0, $g->count()])
            ->sortByDesc(fn ($r) => $r[1])->values();
        $byPayee = $costs->groupBy(fn ($c) => $c->party ?: '—')->map(fn ($g, $p) => [$p, $g->count(), (float) $g->sum('net')])
            ->sortByDesc(fn ($r) => $r[2])->take(15)->values();
        $monthRows = [];
        foreach ($this->months() as [$label, $start, $end]) {
            $m = $costs->filter(fn ($c) => $c->date->between($start, $end));
            $monthRows[] = [$label, (float) $m->where('group', 'cos')->sum('net'), (float) $m->where('group', 'opex')->sum('net'), (float) $m->sum('net')];
        }
        $top = $byCategory->take(7);
        $otherSum = $byCategory->slice(7)->sum(fn ($r) => $r[1]);
        $months = max(1, count($monthRows));

        return [
            'subtitle' => 'Where the money goes: all costs (purchases, supplier invoices, demand requests, expenses, salaries) by category, without VAT.',
            'cards' => [
                ['label' => 'Total Spend', 'value' => $total, 'tone' => 'red', 'icon' => 'fa-wallet', 'note' => $costs->count() . ' entries'],
                ['label' => 'Average per Month', 'value' => $total / $months, 'tone' => 'brand', 'icon' => 'fa-calendar-alt'],
                ['label' => 'Biggest Category', 'value' => $byCategory->first()[1] ?? 0, 'tone' => 'brand', 'icon' => 'fa-tags', 'note' => $byCategory->first()[0] ?? '—'],
            ],
            'chart' => $byCategory->isNotEmpty() ? ['type' => 'doughnut',
                'labels' => $top->pluck(0)->concat($otherSum > 0 ? ['Other categories'] : [])->all(),
                'datasets' => [['label' => 'Spend', 'data' => $top->map(fn ($r) => round($r[1], 2))->concat($otherSum > 0 ? [round($otherSum, 2)] : [])->all()]]] : null,
            'tables' => [
                ['title' => 'By Category', 'columns' => [['Category', 'text'], ['Amount', 'money'], ['Share', 'share'], ['Entries', 'number']],
                    'rows' => $byCategory->map(fn ($r) => [$this->link($r[0], $this->drill('costs', $r[0])), $r[1], $r[2], $r[3]])->all(),
                    'footer' => [$this->link('Total', $this->drill('costs')), $total, $total > 0 ? 100 : null, $costs->count()]],
                ['title' => 'Month by Month', 'columns' => [['Month', 'text'], ['Cost of Sales', 'money'], ['Operating Expenses', 'money'], ['Total', 'money']],
                    'rows' => $monthRows, 'footer' => ['Total', (float) $costs->where('group', 'cos')->sum('net'), (float) $costs->where('group', 'opex')->sum('net'), $total]],
                ['title' => 'Top 15 Payees', 'columns' => [['Paid To', 'text'], ['Entries', 'number'], ['Amount', 'money']], 'rows' => $byPayee->all()],
            ],
            'notes' => ['Same cost figures as the Profit & Loss report. Fixed assets and salary advances are not included.'],
        ];
    }

    protected function receivables(): array
    {
        $items = $this->openReceivables();
        $credits = $this->customerCredits($this->to);
        // What a customer paid beyond a sale by then (an advance) is taken off their other open sales too.
        foreach ($this->allIncome()->filter(fn ($l) => $l->date->lte($this->to)) as $l) {
            $extra = round($this->paidAsOf($l, $this->to) - $l->gross, 2);
            if ($extra > 0.009) $credits[trim((string) $l->party) ?: '—'] = ($credits[trim((string) $l->party) ?: '—'] ?? 0) + $extra;
        }

        return $this->agingReport($items, 'Customer', 'Money customers still owe, aged by invoice / due date as of ' . $this->to->format('d M Y') . '.', [
            'Includes Al Massa invoices (with VAT), customer sales and rent / other income with a balance left, with what was received by ' . $this->to->format('d M Y') . '.',
            'Payments a customer made "on account" (Make Receipt without picking an invoice), and anything paid beyond an invoice, are shown in "On-account Paid" and taken off that customer\'s total.',
        ], $credits->isNotEmpty() ? $credits : null);
    }

    protected function payables(): array
    {
        $items = collect();
        foreach (VendorPurchase::where('balance_amount', '>', 0.009)->get() as $p) {
            $items->push((object) ['party' => $p->vendor_name ?: '—', 'date' => Carbon::parse($p->purchase_date ?: $p->created_at), 'due' => $p->due_date ? Carbon::parse($p->due_date) : null,
                'ref' => $p->invoice_number ?: 'Purchase #' . $p->id, 'url' => route('crm.vendor_purchases.index', array_filter(['vendor_id' => $p->vendor_id, 'search' => $p->invoice_number])),
                'type' => 'Vendor Purchase', 'total' => (float) $p->total_amount, 'paid' => (float) $p->paid_amount, 'balance' => (float) $p->balance_amount]);
        }
        // Purchase invoices as they stood on the report date: payments made after it don't count yet. What a vendor was
        // paid beyond a bill (or before the bill's date) is an advance, taken off that vendor's other bills.
        $advances = collect();
        foreach (CrmPurchaseInvoice::with('payments')->get() as $inv) {
            $issued = $inv->issue_date->lte($this->to);
            $paid = $inv->paidAsOf($this->to);
            $balance = round(($issued ? (float) $inv->total : 0) - $paid, 2);
            if ($balance < -0.009) $advances[$inv->vendor_name] = ($advances[$inv->vendor_name] ?? 0) - $balance;
            if (!$issued || $balance <= 0.009) continue;
            $items->push((object) ['party' => $inv->vendor_name, 'date' => $inv->issue_date, 'due' => $inv->due_date, 'ref' => $inv->number . ($inv->reference ? ' · ' . $inv->reference : ''),
                'url' => route('crm.purchase_invoices.show', $inv->id), 'type' => 'Purchase Invoice', 'total' => (float) $inv->total, 'paid' => $paid, 'balance' => $balance]);
        }
        $items = $items->filter(fn ($i) => $i->date->lte($this->to));

        // Payments made on a vendor's account (not against one purchase) reduce what that vendor is owed, as do advances.
        $onAccount = VendorPurchasePayment::with('vendor')->where('workspace_id', CrmWorkspaceContext::id())->whereNull('vendor_purchase_id')->get()
            ->filter(fn ($p) => $p->vendor)->groupBy(fn ($p) => $p->vendor->name)->map(fn ($g) => (float) $g->sum('amount'));
        foreach ($advances as $vendor => $amount) $onAccount[$vendor] = ($onAccount[$vendor] ?? 0) + $amount;

        return $this->agingReport($items, 'Vendor', 'Money we still owe vendors, aged by bill date as of ' . $this->to->format('d M Y') . '.', [
            'Includes unpaid / partly paid vendor purchases and purchase invoices, with the payments made by ' . $this->to->format('d M Y') . '.',
            'Payments made on a vendor\'s account (not against one bill), and anything paid beyond a purchase invoice (an advance to the vendor), are shown in "On-account Paid" and taken off the vendor total.',
        ], $onAccount);
    }

    protected function sales(): array
    {
        $sales = $this->incomeLines($this->from, $this->to)->whereIn('source', ['invoice', 'sale', 'order']);
        // Every customer receipt, including payments made on a customer's account (same as Cash Flow).
        $collected = $this->receipts()->filter(fn ($r) => $this->inRange($r->date) && in_array($r->source, ['invoice', 'opening', 'sale', 'account']));

        $monthRows = [];
        foreach ($this->months() as [$label, $start, $end]) {
            $m = $sales->filter(fn ($s) => $s->date->between($start, $end));
            $monthRows[] = [$label, $m->count(), (float) $m->sum('net'), (float) $m->sum('vat'), (float) $m->sum('gross'),
                (float) $collected->filter(fn ($r) => $r->date->between($start, $end))->sum('amount')];
        }
        $customerRows = $sales->groupBy('party')->map(fn ($g, $c) => [$c, $g->count(), (float) $g->sum('gross'), (float) $g->sum('paid'), (float) $g->sum('gross') - (float) $g->sum('paid')])
            ->sortByDesc(fn ($r) => $r[2])->values();

        $gross = (float) $sales->sum('gross');
        return [
            'subtitle' => 'Invoices and customer sales raised in the period (with VAT) and money collected from customers.',
            'cards' => [
                ['label' => 'Invoiced', 'value' => $gross, 'tone' => 'brand', 'icon' => 'fa-file-invoice', 'note' => $sales->count() . ' invoice(s) / sale(s)'],
                ['label' => 'Collected', 'value' => (float) $collected->sum('amount'), 'tone' => 'green', 'icon' => 'fa-hand-holding-usd', 'note' => 'receipts in the period'],
                ['label' => 'Average Invoice', 'value' => $sales->count() ? $gross / $sales->count() : 0, 'tone' => 'brand', 'icon' => 'fa-receipt'],
                ['label' => 'Customers', 'value' => $customerRows->count(), 'format' => 'number', 'tone' => 'brand', 'icon' => 'fa-users'],
            ],
            'chart' => count($monthRows) > 1 ? ['type' => 'bar', 'labels' => array_column($monthRows, 0), 'datasets' => [
                ['label' => 'Invoiced', 'data' => array_map(fn ($v) => round($v, 2), array_column($monthRows, 4)), 'color' => '#f45a24'],
                ['label' => 'Collected', 'data' => array_map(fn ($v) => round($v, 2), array_column($monthRows, 5)), 'color' => '#16a34a'],
            ]] : null,
            'tables' => [
                ['title' => 'Month by Month', 'columns' => [['Month', 'text'], ['Invoices', 'number'], ['Sales (without VAT)', 'money'], ['VAT', 'money'], ['Invoiced (with VAT)', 'money'], ['Collected', 'money']],
                    'rows' => $monthRows, 'footer' => ['Total', $sales->count(), (float) $sales->sum('net'), (float) $sales->sum('vat'), $gross, (float) $collected->sum('amount')]],
                ['title' => 'By Customer', 'columns' => [['Customer', 'text'], ['Invoices', 'number'], ['Invoiced', 'money'], ['Paid on These', 'money'], ['Still Due', 'money']],
                    'rows' => $customerRows->all(), 'footer' => ['Total', $sales->count(), $gross, (float) $sales->sum('paid'), $gross - (float) $sales->sum('paid')]],
            ],
            'notes' => ['"Collected" is every customer receipt dated in the period, including payments for older invoices.'],
        ];
    }

    protected function salaries(): array
    {
        $lines = CrmPayrollLine::with('run')
            ->whereHas('run', fn ($q) => $q->where('workspace_id', CrmWorkspaceContext::id())->whereIn('status', ['approved', 'paid']))
            ->get()->filter(fn ($l) => $l->run->periodStart()->between($this->from->copy()->startOfMonth(), $this->to));

        $monthRows = [];
        foreach ($lines->groupBy(fn ($l) => $l->run->period)->sortKeys() as $period => $g) {
            $monthRows[] = [Carbon::createFromFormat('Y-m-d', $period . '-01')->format('M Y'), $g->count(), (float) $g->sum('gross'),
                (float) $g->sum(fn ($l) => $l->totalDeductions()), (float) $g->sum('net'), (float) $g->whereNotNull('paid_at')->sum('net'), (float) $g->whereNull('paid_at')->sum('net')];
        }
        $deptRows = $lines->groupBy(fn ($l) => $l->department ?: 'No department')->map(fn ($g, $d) => [$d, $g->pluck('employee_id')->unique()->count(), (float) $g->sum('gross'), (float) $g->sum('net')])
            ->sortByDesc(fn ($r) => $r[3])->values();
        $advanceRows = CrmEmployee::with('advances')->get()->filter(fn ($e) => $e->advances->isNotEmpty())
            ->map(fn ($e) => [$e->name, $e->advanceGiven(), $e->advanceRecovered(), $e->advanceBalance()])->sortByDesc(fn ($r) => $r[3])->values();

        return [
            'subtitle' => 'Approved salary sheets in the period: gross, deductions, net and what is paid, plus open salary advances.',
            'cards' => [
                ['label' => 'Net Salaries', 'value' => (float) $lines->sum('net'), 'tone' => 'brand', 'icon' => 'fa-money-check-alt', 'note' => count($monthRows) . ' month(s)'],
                ['label' => 'Paid', 'value' => (float) $lines->whereNotNull('paid_at')->sum('net'), 'tone' => 'green', 'icon' => 'fa-check-circle'],
                ['label' => 'Approved, Not Paid', 'value' => (float) $lines->whereNull('paid_at')->sum('net'), 'tone' => 'red', 'icon' => 'fa-hourglass-half'],
                ['label' => 'Open Advances', 'value' => (float) $advanceRows->sum(fn ($r) => $r[3]), 'tone' => 'red', 'icon' => 'fa-hand-holding-usd'],
            ],
            'chart' => count($monthRows) > 1 ? ['type' => 'bar', 'labels' => array_column($monthRows, 0), 'datasets' => [
                ['label' => 'Net Salaries', 'data' => array_map(fn ($v) => round($v, 2), array_column($monthRows, 4)), 'color' => '#06265e']]] : null,
            'tables' => [
                ['title' => 'Month by Month', 'columns' => [['Month', 'text'], ['Employees', 'number'], ['Gross', 'money'], ['Deductions', 'money'], ['Net', 'money'], ['Paid', 'money'], ['Unpaid', 'money']],
                    'rows' => $monthRows, 'footer' => ['Total', null, (float) $lines->sum('gross'), (float) $lines->sum(fn ($l) => $l->totalDeductions()), (float) $lines->sum('net'),
                        (float) $lines->whereNotNull('paid_at')->sum('net'), (float) $lines->whereNull('paid_at')->sum('net')]],
                ['title' => 'By Department', 'columns' => [['Department', 'text'], ['Employees', 'number'], ['Gross', 'money'], ['Net', 'money']], 'rows' => $deptRows->all()],
                ['title' => 'Salary Advances (all time)', 'columns' => [['Employee', 'text'], ['Given', 'money'], ['Recovered', 'money'], ['Still Open', 'money']], 'rows' => $advanceRows->all()],
            ],
            'notes' => ['Draft salary sheets are not included until they are approved.'],
        ];
    }

    protected function vat(): array
    {
        $sales = $this->incomeLines($this->from, $this->to)->whereIn('source', ['invoice', 'sale', 'order']);
        $purchases = VendorPurchase::whereBetween('purchase_date', [$this->from->toDateString(), $this->to->toDateString()])->get();

        $rows = [];
        foreach ($this->months() as [$label, $start, $end]) {
            $s = $sales->filter(fn ($x) => $x->date->between($start, $end));
            $p = $purchases->filter(fn ($x) => Carbon::parse($x->purchase_date)->between($start, $end));
            $outVat = (float) $s->sum('vat');
            $inVat = (float) $p->sum('tax_amount');
            $rows[] = [$label, (float) $s->sum('net'), $outVat, (float) $p->sum(fn ($x) => (float) $x->total_amount - (float) $x->tax_amount), $inVat, $outVat - $inVat];
        }
        $output = (float) $sales->sum('vat');
        $input = (float) $purchases->sum('tax_amount');

        return [
            'subtitle' => 'Output VAT on sales minus input VAT on vendor purchases, by invoice date — a helper for the FTA VAT return.',
            'cards' => [
                ['label' => 'Output VAT (on sales)', 'value' => $output, 'tone' => 'brand', 'icon' => 'fa-arrow-up'],
                ['label' => 'Input VAT (on purchases)', 'value' => $input, 'tone' => 'green', 'icon' => 'fa-arrow-down'],
                ['label' => $output >= $input ? 'Net VAT Payable' : 'Net VAT Refundable', 'value' => abs($output - $input), 'tone' => $output >= $input ? 'red' : 'green', 'icon' => 'fa-percent'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'VAT by Month', 'columns' => [['Month', 'text'], ['Sales (without VAT)', 'money'], ['Output VAT', 'money'], ['Purchases (without VAT)', 'money'], ['Input VAT', 'money'], ['Net VAT', 'money']],
                    'rows' => $rows, 'footer' => ['Total', (float) $sales->sum('net'), $output, (float) $purchases->sum(fn ($x) => (float) $x->total_amount - (float) $x->tax_amount), $input, $output - $input]],
            ],
            'notes' => [
                'Output VAT comes from Al Massa invoices (their VAT %) and the tax amount on customer sales.',
                'Input VAT comes from the tax on vendor purchases. Purchase invoices, expenses and demand requests have no VAT field, so their VAT is not counted.',
                'Check the figures with your accountant before filing.',
            ],
        ];
    }

    protected function jobs(): array
    {
        $jobs = [];
        $touch = function (string $job) use (&$jobs) {
            $jobs[$job] ??= ['revenue' => 0.0, 'cost' => 0.0, 'materials' => 0.0, 'title' => null, 'client' => null, 'purchases' => 0];
        };
        foreach (CustomerSale::whereBetween('order_date', [$this->from->toDateString(), $this->to->toDateString()])->get() as $sale) {
            $job = $this->jobKey($sale->description . ' ' . $sale->notes . ' ' . $sale->item_name);
            if (!$job) continue;
            $touch($job);
            $jobs[$job]['revenue'] += (float) $sale->total_amount - (float) $sale->tax_amount;
            $jobs[$job]['client'] ??= optional($sale->customer)->name;
        }
        foreach (VendorPurchase::whereNotNull('job_id')->where('job_id', '!=', '')->whereBetween('purchase_date', [$this->from->toDateString(), $this->to->toDateString()])->get() as $p) {
            $job = $this->jobKey($p->job_id);
            if (!$job) continue;
            $touch($job);
            $jobs[$job]['cost'] += (float) $p->total_amount - (float) $p->tax_amount;
            $jobs[$job]['purchases']++;
        }
        // Stock used on the job (Cost of Sales from Inventory, at average cost).
        foreach ($this->costLines($this->from, $this->to)->where('type', 'Inventory')->where('category', InventoryAccounting::USED) as $l) {
            $job = $this->jobKey($l->job);
            if (!$job) continue;
            $touch($job);
            $jobs[$job]['materials'] += $l->net;
        }
        if (class_exists(DesignJob::class)) {
            foreach (DesignJob::where('workspace_id', CrmWorkspaceContext::id())->whereIn('job_number', array_keys($jobs))->get() as $dj) {
                $jobs[$dj->job_number]['title'] = $dj->title;
                $jobs[$dj->job_number]['client'] = $jobs[$dj->job_number]['client'] ?: $dj->client_name;
            }
        }
        ksort($jobs);
        $rows = [];
        foreach ($jobs as $job => $j) {
            $profit = $j['revenue'] - $j['cost'] - $j['materials'];
            $rows[] = [$this->link($job, route('crm.vendor_purchases.index', ['search' => $job])), trim(($j['title'] ?: '') . ($j['client'] ? ' · ' . $j['client'] : ''), ' ·') ?: '—', $j['revenue'], $j['cost'], $j['materials'], $profit,
                $j['revenue'] > 0 ? $profit / $j['revenue'] * 100 : null];
        }
        $revenue = array_sum(array_column($rows, 2));
        $vendorCost = array_sum(array_column($rows, 3));
        $materials = array_sum(array_column($rows, 4));
        $cost = $vendorCost + $materials;
        $losing = collect($rows)->filter(fn ($r) => $r[2] > 0 && $r[5] < 0)->count();

        return [
            'subtitle' => 'Revenue, vendor costs and stock used per job (AMS number), without VAT.',
            'cards' => [
                ['label' => 'Jobs', 'value' => count($rows), 'format' => 'number', 'tone' => 'brand', 'icon' => 'fa-briefcase', 'note' => $losing . ' with a loss'],
                ['label' => 'Job Revenue', 'value' => $revenue, 'tone' => 'green', 'icon' => 'fa-arrow-down'],
                ['label' => 'Job Costs', 'value' => $cost, 'tone' => 'red', 'icon' => 'fa-arrow-up', 'note' => 'vendors ' . number_format($vendorCost, 2) . ' · stock ' . number_format($materials, 2)],
                ['label' => 'Job Profit', 'value' => $revenue - $cost, 'tone' => $revenue >= $cost ? 'green' : 'red', 'icon' => 'fa-coins'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Profit by Job', 'columns' => [['Job', 'text'], ['Title / Client', 'text'], ['Revenue', 'money'], ['Vendor Costs', 'money'], ['Stock Used', 'money'], ['Profit', 'money'], ['Margin', 'share']],
                    'rows' => $rows, 'footer' => ['Total', null, $revenue, $vendorCost, $materials, $revenue - $cost, $revenue > 0 ? ($revenue - $cost) / $revenue * 100 : null]],
            ],
            'notes' => [
                'Revenue is matched from customer sales whose description mentions the job (e.g. "Job: AMS-041"). Costs are vendor purchases with that Job # and stock used on the job (Inventory → Use Stock / job card materials, at average cost).',
                'Jobs with no matched sale show costs only — link the sale to the job number to see their profit.',
            ],
        ];
    }

    protected function assets(): array
    {
        // Bought assets only (an asset not yet on a purchase invoice has no cost).
        $all = CrmFixedAsset::with('purchaseInvoice')->get()->reject->isAwaitingInvoice()->sortByDesc('purchase_date')->values();
        $asOf = $this->to;
        $inRange = $all->filter(fn ($a) => $this->inRange($a->purchase_date));
        $cost = (float) $all->sum(fn ($a) => $a->cost());
        $depreciated = (float) $all->sum(fn ($a) => $a->accumulatedDepreciation($asOf));

        $register = $all->map(fn ($a) => [
            $this->link($a->number . ' — ' . $a->name, route('crm.fixed_assets.index', ['search' => $a->name])),
            $a->purchase_date->format('d M Y'),
            $a->purchaseInvoice ? $this->link($a->purchaseInvoice->number, route('crm.purchase_invoices.show', $a->purchase_invoice_id)) : $a->payment_method,
            $a->rateLabel() . '%', $a->cost(), $a->accumulatedDepreciation($asOf), $a->bookValue($asOf),
        ])->values();
        $byYear = $all->groupBy(fn ($a) => $a->purchase_date->format('Y'))->sortKeysDesc()
            ->map(fn ($g, $y) => [(string) $y, $g->count(), (float) $g->sum(fn ($a) => $a->cost()), (float) $g->sum(fn ($a) => $a->bookValue($asOf))])->values();

        return [
            'subtitle' => 'Fixed asset register: cost, straight-line depreciation (each asset\'s yearly rate, from its invoice date) and book value as of ' . $asOf->format('d M Y') . '. Depreciation is not posted to Profit & Loss yet.',
            'cards' => [
                ['label' => 'Total Cost', 'value' => $cost, 'tone' => 'brand', 'icon' => 'fa-boxes', 'note' => $all->count() . ' asset(s)'],
                ['label' => 'Depreciation to Date', 'value' => $depreciated, 'tone' => 'red', 'icon' => 'fa-chart-line'],
                ['label' => 'Book Value', 'value' => $cost - $depreciated, 'tone' => 'brand', 'icon' => 'fa-balance-scale'],
                ['label' => 'Bought in Period', 'value' => (float) $inRange->sum(fn ($a) => $a->cost()), 'tone' => 'brand', 'icon' => 'fa-calendar-alt', 'note' => $inRange->count() . ' asset(s)'],
            ],
            'chart' => null,
            'tables' => [
                ['title' => 'Asset Register', 'columns' => [['Asset', 'text'], ['Bought', 'text'], ['Purchase Invoice', 'text'], ['Rate / Year', 'text'], ['Cost', 'money'], ['Depreciated', 'money'], ['Book Value', 'money']],
                    'rows' => $register->all(), 'footer' => ['Total', null, null, null, $cost, $depreciated, $cost - $depreciated]],
                ['title' => 'By Year Bought', 'columns' => [['Year', 'text'], ['Assets', 'number'], ['Cost', 'money'], ['Book Value', 'money']], 'rows' => $byYear->all()],
            ],
            'notes' => [],
        ];
    }

    /**
     * The entries behind one Profit & Loss / Expense Analysis line, each linking to its invoice or bill.
     * $group: income | cos | opex | costs (all costs); empty $category = every category in the group.
     */
    public function entries(string $group, string $category = ''): array
    {
        $isIncome = $group === 'income';
        $lines = $isIncome ? $this->incomeLines($this->from, $this->to) : $this->costLines($this->from, $this->to);
        if (in_array($group, ['cos', 'opex'], true)) $lines = $lines->where('group', $group);
        if ($category !== '') $lines = $lines->where('category', $category);
        $lines = $lines->sortByDesc(fn ($l) => $l->date->timestamp)->values();

        $groupLabel = ['income' => 'Income', 'cos' => 'Cost of Sales', 'opex' => 'Operating Expenses', 'costs' => 'All Costs'][$group];
        $total = (float) $lines->sum('net');
        $largest = $lines->sortByDesc('net')->first();

        if ($isIncome) {
            $columns = [['Date', 'text'], ['Customer', 'text'], ['Invoice / Ref', 'text'], ['Category', 'text'], ['Without VAT', 'money'], ['VAT', 'money'], ['With VAT', 'money'], ['Paid', 'money']];
            $rows = $lines->map(fn ($l) => [$l->date->format('d M Y'), $l->party, $this->link($l->ref, $l->url), $l->category, $l->net, $l->vat, $l->gross, $l->paid])->all();
            $footer = ['Total', null, null, null, $total, (float) $lines->sum('vat'), (float) $lines->sum('gross'), (float) $lines->sum('paid')];
        } else {
            $columns = [['Date', 'text'], ['Paid To / Vendor', 'text'], ['Invoice / Ref', 'text'], ['Type', 'text'], ['Category', 'text'], ['Amount', 'money']];
            $rows = $lines->map(fn ($l) => [$l->date->format('d M Y'), $l->party, $this->link($l->ref, $l->url), $l->type, $l->category, $l->net])->all();
            $footer = ['Total', null, null, null, null, $total];
        }

        return [
            'key' => 'entries',
            'title' => ($category !== '' ? $category : $groupLabel) . ' — Entries',
            'subtitle' => 'Every entry behind this line for the period' . ($isIncome ? ' (amounts without VAT count in Profit & Loss)' : ' (amounts without VAT)') . '. Click an invoice / ref to open it.',
            'cards' => [
                ['label' => 'Total', 'value' => $total, 'tone' => $isIncome ? 'green' : 'red', 'icon' => 'fa-coins'],
                ['label' => 'Entries', 'value' => $lines->count(), 'format' => 'number', 'tone' => 'brand', 'icon' => 'fa-list'],
                ['label' => 'Largest Entry', 'value' => $largest ? $largest->net : 0, 'tone' => 'brand', 'icon' => 'fa-arrow-up', 'note' => $largest ? $largest->party . ' · ' . $largest->ref : null],
            ],
            'chart' => null,
            'tables' => [['title' => $groupLabel . ($category !== '' ? ' · ' . $category : ''), 'columns' => $columns, 'rows' => $rows, 'footer' => $footer]],
            'notes' => [],
        ];
    }

    /**
     * Headline numbers for the Al Massa dashboard: this month's money in / out and profit (with the year
     * so far), what customers and vendors owe today, this month's salaries, a simplified balance sheet and this year's P&L.
     * Returns plain arrays so it can be cached.
     */
    public static function dashboardSummary(): array
    {
        $now = now();
        $year = new self($now->copy()->startOfYear(), $now);
        $monthStart = $now->copy()->startOfMonth();
        $plMonth = $year->plTotals($monthStart, $year->to);
        $plYear = $year->plTotals($year->from, $year->to);

        $receipts = $year->receipts();
        $payments = $year->payments()->whereNull('duplicate_of');
        $sumBetween = fn ($rows, $from) => (float) $rows->filter(fn ($r) => $r->date->between($from, $year->to))->sum('amount');

        $receivables = $year->receivables();
        $payables = $year->payables();
        $overdueBills = CrmPurchaseInvoice::with('payments')->get()->filter(fn ($i) => $i->status() === 'overdue');

        $run = \App\CrmPayrollRun::with('lines')->where('period', $now->format('Y-m'))->first();
        $openAdvances = (float) CrmEmployee::with('advances')->get()->sum(fn ($e) => $e->advanceBalance());

        return [
            'month' => $now->format('F Y'),
            'money_in' => $sumBetween($receipts, $monthStart), 'money_in_year' => $sumBetween($receipts, $year->from),
            'money_out' => $sumBetween($payments, $monthStart), 'money_out_year' => $sumBetween($payments, $year->from),
            'income' => array_sum($plMonth['income']), 'costs' => array_sum($plMonth['cos']) + array_sum($plMonth['opex']),
            'profit' => $plMonth['net'], 'profit_year' => $plYear['net'],
            'receivable' => (float) $receivables['cards'][0]['value'], 'receivable_90' => (float) $receivables['cards'][3]['value'],
            'receivable_customers' => count($receivables['tables'][0]['rows']),
            'payable' => (float) $payables['cards'][0]['value'], 'payable_90' => (float) $payables['cards'][3]['value'],
            'overdue_bills' => $overdueBills->count(), 'overdue_bills_amount' => (float) $overdueBills->sum(fn ($i) => $i->balanceDue()),
            'salary_status' => $run ? $run->status : null, 'salary_run_id' => $run ? $run->id : null,
            'salary_net' => $run ? (float) $run->lines->sum('net') : 0.0, 'salary_unpaid' => $run ? (float) $run->lines->whereNull('paid_at')->sum('net') : 0.0,
            'salary_employees' => CrmEmployee::where('status', 'active')->count(), 'open_advances' => $openAdvances,
            'balance_sheet' => $year->balanceSheetAsOf($now),
            'pl_year' => $year->plSummary(),
            'cash_chart' => $year->cashChart($receipts, $payments),
            'expense_split' => $year->expenseSplit($plYear),
        ];
    }

    /**
     * Chart of Accounts: every account the CRM keeps, grouped as Assets, Liabilities, Equity (balances as of today)
     * and Income, Cost of Sales, Operating Expenses (totals for $this->from – $this->to), each linking to its entries.
     */
    public function chartOfAccounts(): array
    {
        $bs = $this->balanceSheetAsOf($this->to);
        $pl = $this->plTotals($this->from, $this->to);
        $plAccounts = function (array $group, string $key) {
            ksort($group);
            return collect($group)->map(fn ($amount, $category) => [$category, (float) $amount, $this->drill($key, $category)])->values()->all();
        };

        return [
            'period' => $this->from->format('d M Y') . ' – ' . $this->to->format('d M Y'),
            'groups' => [
                ['key' => 'assets', 'title' => 'Assets', 'statement' => 'Balance Sheet', 'icon' => 'fa-wallet', 'accounts' => $bs['assets'], 'total' => $bs['assets_total']],
                ['key' => 'liabilities', 'title' => 'Liabilities', 'statement' => 'Balance Sheet', 'icon' => 'fa-file-invoice-dollar', 'accounts' => $bs['liabilities'], 'total' => $bs['liabilities_total']],
                ['key' => 'equity', 'title' => 'Equity', 'statement' => 'Balance Sheet', 'icon' => 'fa-user-tie', 'accounts' => $bs['equity'], 'total' => $bs['equity_total']],
                ['key' => 'income', 'title' => 'Income', 'statement' => 'Profit & Loss', 'icon' => 'fa-arrow-down', 'accounts' => $plAccounts($pl['income'], 'income'), 'total' => array_sum($pl['income'])],
                ['key' => 'cos', 'title' => 'Cost of Sales', 'statement' => 'Profit & Loss', 'icon' => 'fa-industry', 'accounts' => $plAccounts($pl['cos'], 'cos'), 'total' => array_sum($pl['cos'])],
                ['key' => 'opex', 'title' => 'Operating Expenses', 'statement' => 'Profit & Loss', 'icon' => 'fa-receipt', 'accounts' => $plAccounts($pl['opex'], 'opex'), 'total' => array_sum($pl['opex'])],
            ],
            'net_profit' => $pl['net'],
            'has_bank_accounts' => $bs['has_bank_accounts'],
        ];
    }

    /** Money in / out per month of the year so far, for the dashboard bar chart. */
    protected function cashChart(Collection $receipts, Collection $payments): array
    {
        $chart = ['labels' => [], 'in' => [], 'out' => [], 'net' => []];
        foreach ($this->months() as [$label, $start, $end]) {
            $in = round((float) $receipts->filter(fn ($r) => $r->date->between($start, $end))->sum('amount'), 2);
            $out = round((float) $payments->filter(fn ($p) => $p->date->between($start, $end))->sum('amount'), 2);
            $chart['labels'][] = $start->format('M');
            $chart['in'][] = $in;
            $chart['out'][] = $out;
            $chart['net'][] = round($in - $out, 2);
        }

        return $chart;
    }

    /** This year's costs by category (top 6 + "Other"), for the dashboard doughnut. */
    protected function expenseSplit(array $plYear): array
    {
        $costs = $plYear['cos'];
        foreach ($plYear['opex'] as $category => $amount) $costs[$category] = ($costs[$category] ?? 0) + $amount;
        arsort($costs);
        $top = array_slice($costs, 0, 6, true);
        $other = array_sum(array_slice($costs, 6));
        if ($other > 0.004) $top['Other categories'] = $other;

        return ['labels' => array_keys($top), 'values' => array_map(fn ($v) => round($v, 2), array_values($top)), 'total' => array_sum($costs)];
    }

    /** This year's Profit & Loss by category for the dashboard, each line linking to its entries. */
    protected function plSummary(): array
    {
        $pl = $this->plTotals($this->from, $this->to);
        $rows = function (array $group, string $key) {
            arsort($group);
            return collect($group)->map(fn ($amount, $category) => [$category, $amount, $this->drill($key, $category)])->values()->all();
        };

        return [
            'label' => $this->from->format('d M Y') . ' – ' . $this->to->format('d M Y'),
            'income' => $rows($pl['income'], 'income'), 'income_total' => array_sum($pl['income']),
            'cos' => $rows($pl['cos'], 'cos'), 'cos_total' => array_sum($pl['cos']),
            'opex' => $rows($pl['opex'], 'opex'), 'opex_total' => array_sum($pl['opex']),
            'expenses_total' => array_sum($pl['cos']) + array_sum($pl['opex']),
            'net' => $pl['net'],
            'links' => ['income' => $this->drill('income'), 'cos' => $this->drill('cos'), 'opex' => $this->drill('opex'), 'costs' => $this->drill('costs')],
        ];
    }

    // ───────────────────────────── Building blocks ─────────────────────────────

    /** Accrual income lines: invoices, workflow orders, unlinked customer sales, rent / other income. */
    protected function incomeLines(Carbon $from, Carbon $to): Collection
    {
        return $this->allIncome()->filter(fn ($l) => $l->date->between($from, $to))->values();
    }

    protected function allIncome(): Collection
    {
        return $this->memo['income'] ??= (function () {
            // Each line also carries its payment events [date, amount, label], so balances can be taken on any date.
            $lines = collect();
            foreach (CrmEmail::where('source', 'manual_offline_order')->with(['orderItems', 'orderPayments'])->get() as $inv) {
                $gross = $inv->orderInvoiceTotal();
                $net = $inv->orderItems->isNotEmpty() ? (float) $inv->orderItems->sum('line_total') : (float) $inv->order_price * (float) $inv->order_quantity;
                $line = $this->line('invoice', $inv->created_at, 'Sales Invoices', $inv->client_name, 'INV-' . ($inv->order_invoice_number ?: $inv->id), $net, $gross - $net,
                    $inv->orderReceivedTotal(), route('crm.orders.invoice', $inv->id));
                if ((float) $inv->order_paid_opening_amount > 0) {
                    $line->events[] = [Carbon::parse($inv->order_marked_at ?: $inv->created_at), (float) $inv->order_paid_opening_amount, 'Payment (before payment tracking)'];
                }
                foreach ($inv->orderPayments as $p) {
                    $line->events[] = [Carbon::parse($p->paid_at), (float) $p->amount, 'Payment' . ($p->method ? ' · ' . $p->method : '') . ($p->reference ? ' · ' . $p->reference : '')];
                }
                $lines->push($line);
            }
            foreach (SalesOrder::with('lead')->whereHas('lead')->get() as $order) {
                $lead = $order->lead;
                $net = (float) $lead->order_price * (float) $lead->order_quantity;
                $vat = round($net * (float) ($lead->vat_percentage ?? 0) / 100, 2);
                $received = in_array($order->payment_status, ['received', 'paid']) || $order->final_payment_received_at ? $net + $vat : 0;
                $line = $this->line('order', $order->created_at, 'Sales Invoices', $lead->client_name, $lead->workflow_number ?? '#' . $lead->id, $net, $vat, $received,
                    route('crm.orders.invoice', $lead->id));
                if ($received > 0) {
                    $line->events[] = [Carbon::parse($order->final_payment_received_at ?: ($order->balance_received_at ?: $order->updated_at)), $received, 'Payment received'];
                }
                $lines->push($line);
            }
            foreach (CustomerSale::with(['customer', 'payments'])->whereNull('crm_email_id')->get() as $sale) {
                $line = $this->line('sale', $sale->order_date, 'Customer Sales', optional($sale->customer)->name, $sale->order_number,
                    (float) $sale->total_amount - (float) $sale->tax_amount, (float) $sale->tax_amount, (float) $sale->paid_amount, route('crm.orders.historical_invoice', $sale->id));
                // Payments from "Make Receipt" on their own date; the rest of paid_amount was paid on the sale date.
                $onSaleDate = round((float) $sale->paid_amount - (float) $sale->payments->sum('amount'), 2);
                if ($onSaleDate > 0.009) {
                    $line->events[] = [Carbon::parse($sale->order_date), $onSaleDate, 'Payment' . ($sale->payment_method ? ' · ' . $sale->payment_method : '')];
                }
                foreach ($sale->payments as $p) {
                    $line->events[] = [Carbon::parse($p->paid_at), (float) $p->amount, 'Payment' . ($p->method ? ' · ' . $p->method : '') . ($p->reference ? ' · ' . $p->reference : '')];
                }
                $lines->push($line);
            }
            foreach (CrmIncomeEntry::with('customer')->get() as $e) {
                $line = $this->line('income', $e->due_date, ($e->income_head ?: 'Other') . ' Income', optional($e->customer)->name, ($e->income_head ?: 'Income') . ' ' . $e->period_label,
                    (float) $e->amount_due, 0, (float) $e->amount_paid, route('crm.customer_sales.index', ['customer_id' => $e->customer_id, 'view' => 'income']));
                if ((float) $e->amount_paid > 0) {
                    $line->events[] = [Carbon::parse($e->paid_at ?: $e->due_date), (float) $e->amount_paid, 'Payment' . ($e->payment_method ? ' · ' . $e->payment_method : '')];
                }
                $lines->push($line);
            }
            // Money received without an invoice (scrap sales, commission...): income in its own category, received the same day.
            // A customer paying "on account" is not income — see customerCredits().
            foreach (\App\CrmDirectReceipt::where('kind', '!=', 'on_account')->get() as $r) {
                $line = $this->line('direct', $r->receipt_date, $r->category, $r->payer_name, $r->number, (float) $r->amount, 0, (float) $r->amount,
                    route('crm.direct_receipts.show', $r->id));
                $line->events[] = [Carbon::parse($r->receipt_date), (float) $r->amount, 'Received'];
                $lines->push($line);
            }
            return $lines;
        })();
    }

    /** Accrual cost lines, each in Cost of Sales ("cos") or Operating Expenses ("opex"). */
    protected function costLines(Carbon $from, Carbon $to): Collection
    {
        return $this->allCosts()->filter(fn ($l) => $l->date->between($from, $to))->values();
    }

    protected function allCosts(): Collection
    {
        return $this->memo['costs'] ??= (function () {
            $lines = collect();
            foreach (VendorPurchase::all() as $p) {
                $lines->push($this->cost($p->purchase_date ?: $p->created_at, $p->expense_type ?: 'Production Expense', $p->vendor_name, (float) $p->total_amount - (float) $p->tax_amount,
                    'Vendor Purchase', $p->invoice_number ?: 'Purchase #' . $p->id,
                    route('crm.vendor_purchases.index', array_filter(['vendor_id' => $p->vendor_id, 'search' => $p->invoice_number])), $p->job_id));
            }
            // One cost line per item category on the invoice; items without a category stay under "Supplier Invoices".
            // Fixed-asset and stock lines are not costs (they are on the balance sheet under Fixed Assets / Inventory).
            foreach (CrmPurchaseInvoice::with('items')->get() as $inv) {
                $byCategory = $inv->items->whereNull('fixed_asset_id')->whereNull('inventory_item_id')
                    ->groupBy(fn ($item) => $item->category ?: 'Supplier Invoices')->map(fn ($items) => (float) $items->sum('line_total'));
                if ($inv->items->isEmpty()) $byCategory = collect(['Supplier Invoices' => (float) $inv->total]);
                foreach ($byCategory as $category => $amount) {
                    $lines->push($this->cost($inv->issue_date, $category, $inv->vendor_name, $amount,
                        'Purchase Invoice', $inv->number . ($inv->reference ? ' · ' . $inv->reference : ''), route('crm.purchase_invoices.show', $inv->id)));
                }
            }
            // Demand-request and general expenses come from the Payments register so duplicates stay excluded.
            foreach ($this->payments()->whereIn('source', ['direct', 'account', 'expense'])->whereNull('duplicate_of') as $row) {
                $lines->push($this->cost($row->date, $row->category ?: ($row->source === 'expense' ? 'Other' : 'Demand Requests'), $row->party, (float) $row->amount,
                    $row->source === 'expense' ? 'Expense' : 'Demand Request', $row->ref, $row->url));
            }
            $salaryLines = CrmPayrollLine::with('run')
                ->whereHas('run', fn ($q) => $q->where('workspace_id', CrmWorkspaceContext::id())->whereIn('status', ['approved', 'paid']))->get();
            foreach ($salaryLines as $l) {
                $lines->push($this->cost($l->run->periodStart(), 'Salaries & Wages', $l->employee_name, (float) $l->net + (float) $l->advance_deduction,
                    'Salary', 'Salary ' . $l->run->periodStart()->format('M Y'), route('crm.payroll.show', $l->payroll_run_id)));
            }
            // Stock in the books: materials used on jobs, purchases moved into stock, wastage (see InventoryAccounting).
            foreach ($this->stock()->costEvents() as $e) {
                if (abs($e->amount) < 0.005) continue;
                $m = $e->movement;
                $ref = $e->kind === 'correction' ? 'Stock cost correction' : ($m->reference ?: 'Stock movement #' . $m->id);
                $lines->push($this->cost($e->date, $e->category, InventoryAccounting::itemLabel($e->item), $e->amount, 'Inventory', $ref,
                    route('crm.inventory.movements', $e->item->id), $m->job_number ?: null));
            }
            return $lines;
        })();
    }

    /** Income / cost-of-sales / opex totals per category for one window. */
    /** Income / cost-of-sales / opex totals per category for any window (used by the budget editor). */
    public function plTotalsFor(Carbon $from, Carbon $to): array
    {
        return $this->plTotals($from, $to);
    }

    protected function plTotals(Carbon $from, Carbon $to): array
    {
        $income = $this->incomeLines($from, $to)->groupBy('category')->map(fn ($g) => (float) $g->sum('net'))->all();
        $costs = $this->costLines($from, $to);
        $cos = $costs->where('group', 'cos')->groupBy('category')->map(fn ($g) => (float) $g->sum('net'))->all();
        $opex = $costs->where('group', 'opex')->groupBy('category')->map(fn ($g) => (float) $g->sum('net'))->all();
        $gross = array_sum($income) - array_sum($cos);

        return ['income' => $income, 'cos' => $cos, 'opex' => $opex, 'gross' => $gross, 'net' => $gross - array_sum($opex)];
    }

    protected function openReceivables(): Collection
    {
        // As it stood on the report date: receipts after it don't count yet.
        return $this->allIncome()
            ->filter(fn ($l) => $l->date->lte($this->to))
            ->map(fn ($l) => [$l, $this->paidAsOf($l, $this->to)])
            ->filter(fn ($pair) => $pair[0]->gross - $pair[1] > 0.009)
            ->map(fn ($pair) => (object) ['party' => $pair[0]->party ?: '—', 'date' => $pair[0]->date, 'due' => null, 'ref' => $pair[0]->ref, 'url' => $pair[0]->url,
                'type' => ['invoice' => 'Invoice', 'order' => 'Sales Order', 'sale' => 'Customer Sale', 'income' => 'Other Income'][$pair[0]->source],
                'total' => $pair[0]->gross, 'paid' => $pair[1], 'balance' => round($pair[0]->gross - $pair[1], 2)])
            ->values();
    }

    /** Shared layout for receivables / payables aging. */
    protected function agingReport(Collection $items, string $partyLabel, string $subtitle, array $notes, ?Collection $onAccount = null): array
    {
        $bucketOf = function ($item) {
            $days = $item->date->copy()->startOfDay()->diffInDays($this->to->copy()->startOfDay(), false);
            $item->days = max(0, $days);
            return $days <= 30 ? 0 : ($days <= 60 ? 1 : ($days <= 90 ? 2 : 3));
        };
        $parties = [];
        $bucketTotals = [0, 0, 0, 0];
        foreach ($items as $item) {
            $b = $bucketOf($item);
            $parties[$item->party] ??= [0, 0, 0, 0];
            $parties[$item->party][$b] += $item->balance;
            $bucketTotals[$b] += $item->balance;
        }
        $partyRows = [];
        $onAccountTotal = 0.0;
        foreach ($parties as $party => $buckets) {
            $row = [$party, ...$buckets];
            if ($onAccount) {
                $paid = min((float) ($onAccount[$party] ?? 0), array_sum($buckets));
                $onAccountTotal += $paid;
                $row[] = $paid;
                $row[] = array_sum($buckets) - $paid;
            } else {
                $row[] = array_sum($buckets);
            }
            $partyRows[] = $row;
        }
        usort($partyRows, fn ($a, $b) => end($b) <=> end($a));

        $columns = [[$partyLabel, 'text']];
        foreach (self::AGING_BUCKETS as $bucket) $columns[] = [$bucket, 'money'];
        if ($onAccount) $columns[] = ['On-account Paid', 'money'];
        $columns[] = ['Total Due', 'money'];
        $total = array_sum($bucketTotals) - $onAccountTotal;
        $footer = ['Total', ...$bucketTotals];
        if ($onAccount) $footer[] = $onAccountTotal;
        $footer[] = $total;

        $itemRows = $items->sortByDesc('days')->map(fn ($i) => [$i->date->format('d M Y'), $i->party, $i->type, $this->link($i->ref, $i->url ?? null), $i->total, $i->paid, $i->balance, $i->days, $i->due ? $i->due->format('d M Y') : '—'])->values();

        return [
            'subtitle' => $subtitle,
            'cards' => [
                ['label' => 'Total Outstanding', 'value' => $total, 'tone' => 'red', 'icon' => 'fa-hourglass-half', 'note' => count($partyRows) . ' ' . strtolower($partyLabel) . '(s), ' . $items->count() . ' open item(s)'],
                ['label' => '0–30 days', 'value' => $bucketTotals[0], 'tone' => 'green', 'icon' => 'fa-clock'],
                ['label' => '31–90 days', 'value' => $bucketTotals[1] + $bucketTotals[2], 'tone' => 'brand', 'icon' => 'fa-exclamation-circle'],
                ['label' => 'Over 90 days', 'value' => $bucketTotals[3], 'tone' => 'red', 'icon' => 'fa-exclamation-triangle'],
            ],
            'chart' => array_sum($bucketTotals) > 0 ? ['type' => 'bar', 'labels' => self::AGING_BUCKETS, 'datasets' => [
                ['label' => 'Outstanding', 'data' => array_map(fn ($v) => round($v, 2), $bucketTotals), 'color' => '#f45a24']]] : null,
            'tables' => [
                ['title' => 'By ' . $partyLabel, 'columns' => $columns, 'rows' => $partyRows, 'footer' => $footer],
                ['title' => 'Open Items', 'columns' => [['Date', 'text'], [$partyLabel, 'text'], ['Type', 'text'], ['Ref', 'text'], ['Total', 'money'], ['Paid', 'money'], ['Balance', 'money'], ['Days', 'number'], ['Due Date', 'text']],
                    'rows' => $itemRows->all(), 'footer' => ['Total', null, null, null, (float) $items->sum('total'), (float) $items->sum('paid'), (float) $items->sum('balance'), null, null]],
            ],
            'notes' => $notes,
        ];
    }

    /** Owner's capital and drawings entries (Owner's Capital tab). */
    protected function capitalEntries(): Collection
    {
        return $this->memo['capital'] ??= \App\CrmCapitalEntry::with('owner')->orderBy('entry_date')->get();
    }

    protected function receipts(): Collection
    {
        return $this->memo['receipts'] ??= app(ReceiptController::class)->receipts();
    }

    protected function payments(): Collection
    {
        return $this->memo['payments'] ??= app(PaymentController::class)->payments();
    }

    /** [label, start, end] for every month touching the period, clipped to it. */
    protected function months(): array
    {
        $months = [];
        $cursor = $this->from->copy()->startOfMonth();
        while ($cursor->lte($this->to)) {
            $start = $cursor->copy()->max($this->from);
            $end = $cursor->copy()->endOfMonth()->min($this->to);
            $months[] = [$cursor->format('M Y'), $start, $end];
            $cursor->addMonth();
        }

        return $months;
    }

    protected function inRange($date): bool
    {
        return $date && Carbon::parse($date)->between($this->from, $this->to);
    }

    protected function line(string $source, $date, string $category, $party, $ref, float $net, float $vat, float $paid, ?string $url = null)
    {
        return (object) ['source' => $source, 'date' => Carbon::parse($date), 'category' => $category, 'party' => $party ?: '—', 'ref' => $ref,
            'net' => round($net, 2), 'vat' => round($vat, 2), 'gross' => round($net + $vat, 2), 'paid' => round($paid, 2), 'url' => $url, 'events' => []];
    }

    /** Customer payments received "on account" (no invoice picked), up to $asOf: customer name => amount. */
    protected function customerCredits(?Carbon $asOf = null): Collection
    {
        $end = $asOf ? $asOf->copy()->endOfDay() : null;

        return ($this->memo['credits'] ??= \App\CrmDirectReceipt::where('kind', 'on_account')->get())
            ->filter(fn ($r) => !$end || $r->receipt_date->lte($end))
            ->groupBy(fn ($r) => trim((string) $r->payer_name))
            ->map(fn ($g) => round((float) $g->sum('amount'), 2));
    }

    /** What had been received on an income line by the end of $asOf. */
    protected function paidAsOf($line, Carbon $asOf): float
    {
        $end = $asOf->copy()->endOfDay();

        return round(collect($line->events)->filter(fn ($e) => $e[0]->lte($end))->sum(fn ($e) => $e[1]), 2);
    }

    /** Stock in the books (perpetual inventory), shared by every report built from this instance. */
    protected function stock(): InventoryAccounting
    {
        return $this->memo['stock'] ??= new InventoryAccounting();
    }

    protected function cost($date, string $category, $party, float $net, string $type, $ref, ?string $url, $job = null)
    {
        $category = $this->category($category);

        return (object) ['date' => Carbon::parse($date), 'category' => $category, 'group' => in_array($category, self::COST_OF_SALES, true) ? 'cos' : 'opex',
            'party' => $party ?: '—', 'net' => round($net, 2), 'type' => $type, 'ref' => $ref, 'url' => $url, 'job' => $job];
    }

    /** A table cell that opens a page: rendered as a link on screen, plain text in Excel / PDF. */
    protected function link($text, ?string $url): array
    {
        return ['text' => (string) $text, 'url' => $url];
    }

    /** Link to the list of entries behind one P&L / expense line for the current period. */
    protected function drill(string $group, string $category = ''): string
    {
        return route('crm.financial_reports.entries', array_filter([
            'group' => $group, 'category' => $category, 'period' => 'custom',
            'date_from' => $this->from->toDateString(), 'date_to' => $this->to->toDateString(),
        ]));
    }

    protected function category(string $raw): string
    {
        $key = mb_strtolower(trim($raw));

        return self::CATEGORY_ALIASES[$key] ?? ucwords(trim($raw));
    }

    /** "AMS-41", "AMS-041", "Job: AMS-0041" → "AMS-0041". */
    protected function jobKey(?string $text): ?string
    {
        return $text && preg_match('/AMS-?\s*0*(\d+)/i', $text, $m) ? 'AMS-' . str_pad($m[1], 4, '0', STR_PAD_LEFT) : null;
    }

    protected function methodGroup(?string $method): string
    {
        return BankLedgerService::methodGroup($method);
    }

    protected function change(float $now, float $prev): ?float
    {
        return abs($prev) < 0.005 ? null : ($now - $prev) / abs($prev) * 100;
    }
}
