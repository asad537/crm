{{-- Al Massa dashboard: business summary built by FinancialReportService::dashboardSummary(). Needs $s. --}}
@php
    $money = fn ($v) => ($v < -0.004 ? '(' . number_format(abs($v), 2) . ')' : number_format($v, 2));
    $monthQuery = ['period' => 'this_month'];
    $salaryLabel = ['draft' => 'Draft — not approved', 'approved' => 'Approved', 'paid' => 'Paid'];
@endphp
<style>
    .fs-wrap { margin-bottom: 1.4rem; }
    .fs-head { display: flex; justify-content: space-between; align-items: center; gap: .8rem; flex-wrap: wrap; margin-bottom: .8rem; }
    .fs-title { font-size: 1.02rem; font-weight: 800; color: #0f172a; }
    .fs-title small { font-weight: 600; color: #64748b; font-size: .8rem; margin-left: .35rem; }
    .fs-link-all { font-size: .8rem; font-weight: 700; color: var(--primary-purple); text-decoration: none; }
    .fs-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .fs-card { position: relative; display: block; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.05rem 1.15rem 1rem;
        text-decoration: none !important; color: inherit; box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 10px 28px -22px rgba(15,23,42,.25);
        transition: transform .12s, box-shadow .12s, border-color .12s; overflow: hidden; }
    .fs-card:hover { transform: translateY(-2px); border-color: var(--primary-purple); box-shadow: 0 10px 24px -10px rgba(15,23,42,.2); }
    .fs-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--fs-tone, #94a3b8); }
    .fs-card.green { --fs-tone: #16a34a; } .fs-card.red { --fs-tone: #dc2626; } .fs-card.brand { --fs-tone: var(--primary-purple); } .fs-card.navy { --fs-tone: #06265e; }
    .fs-top { display: flex; align-items: center; gap: .6rem; }
    .fs-ico { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; font-size: .85rem; background: #f1f5f9; color: var(--fs-tone, #475569); flex: none; }
    .fs-label { font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; }
    .fs-value { margin-top: .55rem; font-size: 1.45rem; font-weight: 800; letter-spacing: -.02em; color: #0f172a; font-variant-numeric: tabular-nums; line-height: 1.1; }
    .fs-value small { font-size: .62rem; color: #64748b; font-weight: 700; letter-spacing: .05em; }
    .fs-value.neg { color: #dc2626; } .fs-value.pos { color: #15803d; }
    .fs-note { margin-top: .4rem; font-size: .74rem; color: #64748b; }
    .fs-note b { color: #334155; font-weight: 700; }
    .fs-note .warn { color: #dc2626; font-weight: 700; }
    .fs-charts { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 1rem; margin-top: 1rem; }
    .fs-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.1rem 1.2rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    .fs-panel-head { display: flex; justify-content: space-between; align-items: flex-start; gap: .6rem; margin-bottom: .6rem; }
    .fs-panel-head b { display: block; font-size: .98rem; color: #0f172a; }
    .fs-panel-head span { font-size: .76rem; color: #94a3b8; }
    .fs-panel-head a { font-size: .74rem; font-weight: 700; color: var(--primary-purple); text-decoration: none; white-space: nowrap; }
    .fs-canvas { position: relative; height: 260px; }
    .fs-canvas.small { height: 170px; }
    .fs-split { margin-top: .7rem; display: flex; flex-direction: column; gap: .45rem; }
    .fs-split-row { display: grid; grid-template-columns: 10px minmax(0, 1fr) auto; gap: .5rem; align-items: center; font-size: .76rem; color: #334155; }
    .fs-split-row i { width: 10px; height: 10px; border-radius: 3px; display: block; }
    .fs-split-row span:nth-child(2) { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fs-split-row b { font-variant-numeric: tabular-nums; color: #0f172a; }
    .fs-empty { padding: 1rem 1.1rem; font-size: .8rem; color: #94a3b8; }
    .fs-books { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-top: 1rem; align-items: start; }
    .fs-viz-summary { display: flex; flex-direction: column; gap: .5rem; padding: .3rem 0 .9rem; border-bottom: 1px solid #f1f5f9; }
    .fs-bar { display: grid; grid-template-columns: minmax(0, 40%) minmax(0, 1fr) 118px; align-items: center; gap: .75rem; padding: .3rem .35rem;
        font-size: .79rem; color: #334155; text-decoration: none !important; border-radius: 8px; }
    a.fs-bar:hover { background: #f8fafc; }
    a.fs-bar:hover .fs-bar-label { color: var(--primary-purple); }
    .fs-bar-label { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fs-track { display: block; height: 9px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
    .fs-fill { display: block; height: 100%; border-radius: 99px; }
    .fs-fill.neg { background: repeating-linear-gradient(45deg, #dc2626 0 5px, #f87171 5px 10px); }
    .fs-bar-val { text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a; white-space: nowrap; }
    .fs-bar-val.neg { color: #dc2626; }
    .fs-bar.big { font-size: .86rem; font-weight: 800; color: #0f172a; }
    .fs-bar.big .fs-track { height: 18px; border-radius: 6px; }
    .fs-bar.big .fs-fill { border-radius: 6px; }
    .fs-bar.big .fs-bar-val { font-size: .95rem; font-weight: 800; }
    .fs-viz-title { display: flex; align-items: center; gap: .5rem; margin: .85rem .35rem .2rem; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: #64748b; }
    .fs-viz-title i { width: 10px; height: 10px; border-radius: 3px; background: var(--c); flex: none; }
    .fs-viz-title a { color: inherit; text-decoration: none; }
    .fs-viz-title a:hover { color: var(--primary-purple); }
    .fs-viz-title b { margin-left: auto; color: #0f172a; font-size: .8rem; letter-spacing: 0; font-variant-numeric: tabular-nums; }
    .fs-viz-title b.neg { color: #dc2626; }
    .fs-legend { display: flex; gap: 1rem; flex-wrap: wrap; font-size: .7rem; color: #94a3b8; margin-top: .9rem; padding: 0 .35rem; }
    .fs-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .fs-legend i { width: 14px; height: 8px; border-radius: 99px; display: inline-block; }
    .fs-footnote { font-size: .7rem; color: #94a3b8; margin-top: .7rem; padding: 0 .35rem; }
    @media (max-width: 1100px) { .fs-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 1100px) { .fs-charts { grid-template-columns: 1fr; } }
    @media (max-width: 700px) { .fs-grid, .fs-books { grid-template-columns: 1fr; } .fs-bar { grid-template-columns: minmax(0, 42%) minmax(0, 1fr) 96px; gap: .5rem; } }
</style>

<div class="fs-wrap">
    <div class="fs-head">
        <div class="fs-title"><i class="fas fa-chart-line" style="color:var(--primary-purple)"></i> Business Summary <small>{{ $s['month'] }} · balances as of today</small></div>
        <a class="fs-link-all" href="{{ route('crm.financial_reports.index') }}">Open Financial Reports <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="fs-grid">
        <a class="fs-card green" href="{{ route('crm.receipts.index', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]) }}">
            <div class="fs-top"><span class="fs-ico"><i class="fas fa-arrow-down"></i></span><span class="fs-label">Money In · This Month</span></div>
            <div class="fs-value">{{ $money($s['money_in']) }} <small>AED</small></div>
            <div class="fs-note">This year: <b>{{ $money($s['money_in_year']) }}</b></div>
        </a>
        <a class="fs-card red" href="{{ route('crm.payments.index', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]) }}">
            <div class="fs-top"><span class="fs-ico"><i class="fas fa-arrow-up"></i></span><span class="fs-label">Money Out · This Month</span></div>
            <div class="fs-value">{{ $money($s['money_out']) }} <small>AED</small></div>
            <div class="fs-note">This year: <b>{{ $money($s['money_out_year']) }}</b></div>
        </a>
        <a class="fs-card {{ $s['profit'] >= 0 ? 'green' : 'red' }}" href="{{ route('crm.financial_reports.index', array_merge(['report' => 'profit-loss'], $monthQuery)) }}">
            <div class="fs-top"><span class="fs-ico"><i class="fas fa-coins"></i></span><span class="fs-label">Net Profit / (Loss) · This Month</span></div>
            <div class="fs-value {{ $s['profit'] < 0 ? 'neg' : 'pos' }}">{{ $money($s['profit']) }} <small>AED</small></div>
            <div class="fs-note">Income <b>{{ $money($s['income']) }}</b> − Costs <b>{{ $money($s['costs']) }}</b> · Year: <b class="{{ $s['profit_year'] < 0 ? 'warn' : '' }}">{{ $money($s['profit_year']) }}</b></div>
        </a>
        <a class="fs-card brand" href="{{ route('crm.financial_reports.index', ['report' => 'receivables']) }}">
            <div class="fs-top"><span class="fs-ico"><i class="fas fa-hand-holding-usd"></i></span><span class="fs-label">Customers Owe Us</span></div>
            <div class="fs-value">{{ $money($s['receivable']) }} <small>AED</small></div>
            <div class="fs-note">{{ $s['receivable_customers'] }} customer(s)@if($s['receivable_90'] > 0) · <span class="warn">{{ $money($s['receivable_90']) }} over 90 days</span>@endif</div>
        </a>
        <a class="fs-card red" href="{{ route('crm.financial_reports.index', ['report' => 'payables']) }}">
            <div class="fs-top"><span class="fs-ico"><i class="fas fa-file-invoice-dollar"></i></span><span class="fs-label">We Owe Vendors</span></div>
            <div class="fs-value">{{ $money($s['payable']) }} <small>AED</small></div>
            <div class="fs-note">@if($s['overdue_bills'])<span class="warn">{{ $s['overdue_bills'] }} overdue purchase invoice(s) · {{ $money($s['overdue_bills_amount']) }}</span>@else No overdue purchase invoices @endif</div>
        </a>
        <a class="fs-card navy" href="{{ $s['salary_run_id'] ? route('crm.payroll.show', $s['salary_run_id']) : route('crm.payroll.index') }}">
            <div class="fs-top"><span class="fs-ico"><i class="fas fa-money-check-alt"></i></span><span class="fs-label">Salaries · {{ $s['month'] }}</span></div>
            @if($s['salary_status'])
                <div class="fs-value">{{ $money($s['salary_net']) }} <small>AED</small></div>
                <div class="fs-note">{{ $salaryLabel[$s['salary_status']] }}@if($s['salary_unpaid'] > 0 && $s['salary_status'] !== 'draft') · <span class="warn">{{ $money($s['salary_unpaid']) }} unpaid</span>@endif @if($s['open_advances'] > 0) · advances {{ $money($s['open_advances']) }}@endif</div>
            @else
                <div class="fs-value" style="font-size:1.05rem;color:#64748b">No salary sheet yet</div>
                <div class="fs-note">{{ $s['salary_employees'] }} active employee(s) · click to create</div>
            @endif
        </a>
    </div>

    @php
        $cashChart = $s['cash_chart'];
        $split = $s['expense_split'];
        $palette = ['#f45a24', '#06265e', '#16a34a', '#d97706', '#2563eb', '#9333ea', '#94a3b8'];
    @endphp
    <div class="fs-charts">
        <div class="fs-panel">
            <div class="fs-panel-head">
                <div><b>Money In vs Money Out</b><span>Receipts and payments per month · {{ now()->year }}</span></div>
                <a href="{{ route('crm.financial_reports.index', ['report' => 'cash-flow']) }}">Cash Flow report →</a>
            </div>
            <div class="fs-canvas"><canvas id="fsCashChart"></canvas></div>
        </div>
        <div class="fs-panel">
            <div class="fs-panel-head">
                <div><b>Where the Money Goes</b><span>Costs by category · {{ now()->year }}</span></div>
                <a href="{{ route('crm.financial_reports.index', ['report' => 'expenses']) }}">Details →</a>
            </div>
            @if($split['values'])
                <div class="fs-canvas small"><canvas id="fsExpenseChart"></canvas></div>
                <div class="fs-split">
                    @foreach($split['labels'] as $i => $label)
                        <div class="fs-split-row"><i style="background:{{ $palette[$i % count($palette)] }}"></i><span>{{ $label }}</span>
                            <b>{{ number_format($split['values'][$i], 0) }} <span style="color:#94a3b8;font-weight:600">· {{ $split['total'] > 0 ? round($split['values'][$i] / $split['total'] * 100) : 0 }}%</span></b></div>
                    @endforeach
                </div>
            @else
                <div class="fs-empty">No costs recorded this year.</div>
            @endif
        </div>
    </div>
    <script>
        (function () {
            // Inline loader (same as the dashboard's): <script src> tags do not run on AJAX page navigation.
            window.loadScriptOnce = window.loadScriptOnce || function (src, cb) {
                var existing = document.querySelector('script[data-lib-src="' + src + '"]');
                if (existing) { if (existing.dataset.loaded === '1') return cb(); return existing.addEventListener('load', cb); }
                var s = document.createElement('script');
                s.src = src; s.dataset.libSrc = src;
                s.addEventListener('load', function () { s.dataset.loaded = '1'; cb(); });
                document.body.appendChild(s);
            };
            var cash = @json($cashChart);
            var split = @json($split);
            var palette = @json($palette);
            var money = function (v) { return Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 }); };
            loadScriptOnce('https://cdn.jsdelivr.net/npm/chart.js', function () {
                var noLabels = { datalabels: false }; // fully off: the dashboard registers this plugin globally, sometimes after these charts exist
                var cashEl = document.getElementById('fsCashChart');
                if (cashEl) {
                    if (cashEl._fsChart) cashEl._fsChart.destroy();
                    cashEl._fsChart = new Chart(cashEl, {
                        type: 'bar',
                        data: { labels: cash.labels, datasets: [
                            { label: 'Money In', data: cash.in, backgroundColor: '#16a34acc', borderRadius: 6, order: 1 },
                            { label: 'Money Out', data: cash.out, backgroundColor: '#dc2626cc', borderRadius: 6, order: 1 },
                            { label: 'Net', data: cash.net, type: 'line', borderColor: '#06265e', backgroundColor: '#06265e', borderWidth: 2, tension: .3, pointRadius: 3, order: 0 }
                        ] },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: Object.assign({ legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                                tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + money(c.raw) + ' AED'; } } } }, noLabels),
                            scales: { y: { ticks: { callback: money }, grid: { color: '#eef1f5' } }, x: { grid: { display: false } } }
                        }
                    });
                }
                var expEl = document.getElementById('fsExpenseChart');
                if (expEl) {
                    if (expEl._fsChart) expEl._fsChart.destroy();
                    expEl._fsChart = new Chart(expEl, {
                        type: 'doughnut',
                        data: { labels: split.labels, datasets: [{ data: split.values, backgroundColor: palette, borderColor: '#fff', borderWidth: 2 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '62%',
                            plugins: Object.assign({ legend: { display: false },
                                tooltip: { callbacks: { label: function (c) { return c.label + ': ' + money(c.raw) + ' AED'; } } } }, noLabels)
                        }
                    });
                }
            });
        })();
    </script>

    @php
        $bs = $s['balance_sheet'];
        $pl = $s['pl_year'];
        $signed = fn ($v) => ($v < -0.004 ? '- ' . number_format(abs($v), 2) : (abs($v) < 0.005 ? '-' : number_format($v, 2)));
        // Totals and individual lines each get their own scale so small lines are still visible.
        $absMax = fn (array $values) => max(1, ...array_map(fn ($v) => abs((float) $v), $values ?: [0]));
        $bsTotalMax = $absMax([$bs['assets_total'], $bs['liabilities_total'], $bs['equity_total']]);
        $bsLineMax = $absMax(array_column(array_merge($bs['assets'], $bs['liabilities'], $bs['equity']), 1));
        $plTotalMax = $absMax([$pl['income_total'], $pl['expenses_total'], $pl['net']]);
        $plLineMax = $absMax(array_column(array_merge($pl['income'], $pl['cos'], $pl['opex']), 1));
        $bar = function ($label, $value, $url, $color, $max, $class = '') use ($signed) {
            $neg = $value < -0.004;
            $pct = abs($value) < 0.005 ? 0 : max(1.5, min(100, round(abs($value) / $max * 100, 1)));
            $open = $url ? '<a class="fs-bar ' . $class . '" href="' . e($url) . '" title="' . e($label) . '">' : '<div class="fs-bar ' . $class . '" title="' . e($label) . '">';
            return $open . '<span class="fs-bar-label">' . e($label) . '</span>'
                . '<span class="fs-track"><span class="fs-fill' . ($neg ? ' neg' : '') . '" style="width:' . $pct . '%' . ($neg ? '' : ';background:' . $color) . '"></span></span>'
                . '<span class="fs-bar-val' . ($neg ? ' neg' : '') . '">' . $signed($value) . '</span>' . ($url ? '</a>' : '</div>');
        };
        $colors = ['assets' => '#2563eb', 'liabilities' => '#d97706', 'equity' => '#06265e', 'income' => '#16a34a', 'cos' => '#f45a24', 'opex' => '#dc2626'];
    @endphp
    <div class="fs-books">
        <div class="fs-panel">
            <div class="fs-panel-head"><div><b>Balance Sheet</b><span>As of today</span></div></div>
            <div class="fs-viz-summary">
                {!! $bar('Assets', $bs['assets_total'], null, $colors['assets'], $bsTotalMax, 'big') !!}
                {!! $bar('Liabilities', $bs['liabilities_total'], null, $colors['liabilities'], $bsTotalMax, 'big') !!}
                {!! $bar('Equity', $bs['equity_total'], null, $colors['equity'], $bsTotalMax, 'big') !!}
            </div>
            @foreach(['assets' => 'Assets', 'liabilities' => 'Liabilities', 'equity' => 'Equity'] as $key => $title)
                <div class="fs-viz-title" style="--c:{{ $colors[$key] }}"><i></i>{{ $title }}<b class="{{ $bs[$key . '_total'] < -0.004 ? 'neg' : '' }}">{{ $signed($bs[$key . '_total']) }}</b></div>
                @foreach($bs[$key] as [$label, $value, $url]){!! $bar($label, $value, $url, $colors[$key], $bsLineMax) !!}@endforeach
            @endforeach
            <div class="fs-legend"><span><i style="background:repeating-linear-gradient(45deg,#dc2626 0 4px,#f87171 4px 8px)"></i> negative amount</span></div>
            @if(!empty($bs['has_bank_accounts']))
                <div class="fs-footnote">Bank &amp; cash lines are each account's actual balance (opening balance + money in − money out ± transfers). <a href="{{ route('crm.bank_accounts.index') }}" style="color:inherit;font-weight:700">Bank &amp; Cash →</a></div>
            @else
                <div class="fs-footnote">* Cash &amp; Bank is money received minus money paid in the CRM; opening cash / bank balances are not added yet. Suspense is the difference those opening balances will explain. Add your accounts under <a href="{{ route('crm.bank_accounts.index') }}" style="color:inherit;font-weight:700">Bank &amp; Cash</a> to see real balances.</div>
            @endif
        </div>

        <div class="fs-panel">
            <div class="fs-panel-head"><div><b>Profit &amp; Loss</b><span>{{ $pl['label'] }}</span></div><a href="{{ route('crm.financial_reports.index', ['report' => 'profit-loss']) }}">Full report →</a></div>
            <div class="fs-viz-summary">
                {!! $bar('Income', $pl['income_total'], $pl['links']['income'], $colors['income'], $plTotalMax, 'big') !!}
                {!! $bar('Expenses', $pl['expenses_total'], $pl['links']['costs'], $colors['opex'], $plTotalMax, 'big') !!}
                {!! $bar('Net Profit / (Loss)', $pl['net'], null, '#06265e', $plTotalMax, 'big') !!}
            </div>
            <div class="fs-viz-title" style="--c:{{ $colors['income'] }}"><i></i><a href="{{ $pl['links']['income'] }}">Income</a><b>{{ $signed($pl['income_total']) }}</b></div>
            @forelse($pl['income'] as [$label, $value, $url]){!! $bar($label, $value, $url, $colors['income'], $plLineMax) !!}@empty<div class="fs-empty">No income in this period.</div>@endforelse
            @if($pl['cos'])
                <div class="fs-viz-title" style="--c:{{ $colors['cos'] }}"><i></i><a href="{{ $pl['links']['cos'] }}">Direct Costs (Cost of Sales)</a><b>{{ $signed($pl['cos_total']) }}</b></div>
                @foreach($pl['cos'] as [$label, $value, $url]){!! $bar($label, $value, $url, $colors['cos'], $plLineMax) !!}@endforeach
            @endif
            @if($pl['opex'])
                <div class="fs-viz-title" style="--c:{{ $colors['opex'] }}"><i></i><a href="{{ $pl['links']['opex'] }}">Operating Expenses</a><b>{{ $signed($pl['opex_total']) }}</b></div>
                @foreach($pl['opex'] as [$label, $value, $url]){!! $bar($label, $value, $url, $colors['opex'], $plLineMax) !!}@endforeach
            @endif
        </div>
    </div>
</div>
