@extends('crm.layout')
@section('title', $report['title'])
@section('content')
@php
    $query = request()->only(['period', 'date_from', 'date_to']);
    $activeTab = $activeTab ?? $report['key'];
    $formAction = $formAction ?? route('crm.financial_reports.index', $report['key']);
    $hidden = $hidden ?? [];
    $exportParams = array_merge(['report' => $report['key']], $hidden, $query, request()->only(\App\Http\Controllers\Crm\FinancialReportController::PARAMS));
    $cell = function ($value, $type) {
        if ($value === null || $value === '') return '<span class="fr-muted">—</span>';
        if (is_array($value)) {
            return $value['url'] ? '<a class="fr-link" href="' . e($value['url']) . '">' . e($value['text']) . '</a>' : e($value['text']);
        }
        switch ($type) {
            case 'share': return '<span class="' . ($value < 0 ? 'fr-neg' : '') . '">' . number_format($value, 1) . '%</span>';
            case 'money': return '<span class="' . ($value < -0.004 ? 'fr-neg' : '') . '">' . ($value < -0.004 ? '(' . number_format(abs($value), 2) . ')' : number_format($value, 2)) . '</span>';
            case 'percent': return '<span class="' . ($value < 0 ? 'fr-neg' : 'fr-pos') . '">' . ($value > 0 ? '+' : '') . number_format($value, 1) . '%</span>';
            case 'number': return number_format((float) $value, fmod((float) $value, 1) ? 2 : 0);
            default: return e($value);
        }
    };
    $toneIcon = ['brand' => 'brand', 'green' => 'green', 'red' => 'red'];
@endphp

@include('crm.general_ledger._styles')
@include('crm.purchase_invoices._styles')
<style>
    .fr-tabs { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .3rem; margin-bottom: 1rem; align-items: center; }
    .fr-tab-group { flex: none; font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--faint);
        padding: 0 .2rem 0 .7rem; border-left: 2px solid var(--line); margin-left: .3rem; white-space: nowrap; }
    .fr-tab-group:first-child { border-left: 0; margin-left: 0; padding-left: 0; }
    .fr-tab { display: inline-flex; align-items: center; gap: .45rem; white-space: nowrap; padding: .55rem .9rem; border-radius: 10px; border: 1px solid var(--line);
        background: #fff; color: var(--muted); font-size: .8rem; font-weight: 700; text-decoration: none; }
    .fr-tab:hover { color: var(--ink); border-color: var(--brand); }
    .fr-tab.active { background: var(--brand); border-color: var(--brand); color: #fff; box-shadow: 0 4px 12px -4px var(--primary-shadow, rgba(0,0,0,.25)); }
    .fr-sub { font-size: .84rem; color: var(--muted); margin: -.2rem 0 1rem; }
    .fr-sub b { color: var(--ink); }
    .fr-chart { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 1rem 1.2rem; margin-bottom: 1rem; height: 320px; }
    .fr-card-title { padding: .85rem 1.15rem; font-weight: 800; color: var(--ink); border-bottom: 1px solid var(--line); font-size: .9rem; }
    .fr-table td.r, .fr-table th.r { text-align: right; }
    .fr-table td { font-variant-numeric: tabular-nums; }
    .fr-table tr.fr-section td { background: var(--well); font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--brand); }
    .fr-table tr.fr-subtotal td { font-weight: 800; color: var(--ink); border-top: 1px solid var(--line); }
    .fr-table tr.fr-total td { font-weight: 800; color: var(--ink); background: #eef2ff; }
    .fr-table tr.fr-grand td { font-weight: 800; font-size: .95rem; color: #fff; background: #06265e; }
    .fr-table tr.fr-grand .fr-neg { color: #fecaca; } .fr-table tr.fr-grand .fr-pos { color: #bbf7d0; }
    /* Same cell padding as the body rows so totals line up under their columns. */
    .fr-table tfoot td { padding: .85rem 1.15rem; font-size: .9rem; font-weight: 800; background: var(--well); color: var(--ink);
        border-top: 2px solid var(--line); vertical-align: middle; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .fr-table tfoot td:first-child { font-size: .7rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); }
    .fr-neg { color: var(--red); } .fr-pos { color: var(--green); } .fr-muted { color: var(--faint); }
    .fr-notes { margin-top: .4rem; padding: .85rem 1.1rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; color: #78350f; font-size: .78rem; }
    .fr-notes ul { margin: .3rem 0 0; padding-left: 1.1rem; }
    .gl-stat .gl-v.fr-neg { color: var(--red); }
    .fr-link { color: inherit; text-decoration: none; border-bottom: 1px dashed var(--faint); }
    .fr-link:hover { color: var(--brand); border-bottom-color: var(--brand); }
    .fr-table tr.fr-grand .fr-link { color: #fff; }
    .fr-back { display: inline-flex; align-items: center; gap: .4rem; margin-bottom: .8rem; font-size: .8rem; font-weight: 700; color: var(--brand); text-decoration: none; }
</style>

<div class="gl-page">
    <div class="fr-tabs">
        @foreach(collect($reports)->groupBy(fn ($r) => $r[2], true) as $group => $items)
            <span class="fr-tab-group">{{ $group }}</span>
            @foreach($items as $key => [$label, $icon])
                <a class="fr-tab {{ $activeTab === $key ? 'active' : '' }}" href="{{ route('crm.financial_reports.index', array_merge(['report' => $key], $query)) }}"><i class="fas {{ $icon }}"></i> {{ $label }}</a>
            @endforeach
        @endforeach
    </div>

    @if($report['key'] === 'entries')
        <a class="fr-back" href="{{ route('crm.financial_reports.index', array_merge(['report' => $activeTab], $query)) }}"><i class="fas fa-arrow-left"></i> Back to {{ $reports[$activeTab][0] }}</a>
    @endif

    <div class="gl-filterbar">
        <form class="gl-filters" method="GET" action="{{ $formAction }}" id="frForm">
            @foreach($hidden as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
            <div class="gl-field"><label>Period</label>
                <select name="period" onchange="document.getElementById('frCustom').style.display = this.value === 'custom' ? 'flex' : 'none'; if (this.value !== 'custom') this.form.submit();">
                    @foreach($periods as $value => $label)<option value="{{ $value }}" {{ $period === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div id="frCustom" style="display:{{ $period === 'custom' ? 'flex' : 'none' }};gap:.7rem;align-items:flex-end">
                <div class="gl-field"><label>From</label><input type="date" name="date_from" value="{{ $from->toDateString() }}"></div>
                <div class="gl-field"><label>To</label><input type="date" name="date_to" value="{{ $to->toDateString() }}"></div>
                <button type="submit" class="gl-apply">Apply</button>
            </div>
            @foreach($report['filters'] ?? [] as $filter)
                <div class="gl-field"><label>{{ $filter['label'] }}</label>
                    <select name="{{ $filter['name'] }}" onchange="this.form.submit()">
                        @foreach($filter['options'] as $value => $optionLabel)<option value="{{ $value }}" {{ (string) $filter['value'] === (string) $value ? 'selected' : '' }}>{{ $optionLabel }}</option>@endforeach
                    </select>
                </div>
            @endforeach
            <div class="gl-mutedtext" style="padding-bottom:.55rem"><i class="fas fa-calendar"></i> {{ $report['range_label'] ?? ($from->format('d M Y') . ' – ' . $to->format('d M Y')) }}</div>
        </form>
        <div class="gl-exports">
            @foreach($report['actions'] ?? [] as $action)
                <a class="gl-export" href="{{ $action['url'] }}"><i class="fas {{ $action['icon'] }}"></i> {{ $action['label'] }}</a>
            @endforeach
            <a class="gl-export" href="{{ route('crm.financial_reports.export', array_merge($exportParams, ['format' => 'excel'])) }}"><i class="fas fa-file-excel"></i> Excel</a>
            <a class="gl-export" href="{{ route('crm.financial_reports.export', array_merge($exportParams, ['format' => 'pdf'])) }}"><i class="fas fa-file-pdf"></i> PDF</a>
        </div>
    </div>
    @if($errors->any())<div class="ex-errors" style="margin-bottom:1rem;background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d;padding:.7rem 1rem;border-radius:10px">{{ $errors->first() }}</div>@endif

    <div class="fr-sub"><b>{{ $report['title'] }}</b> — {{ $report['subtitle'] }}</div>

    <div class="gl-stats">
        @foreach($report['cards'] as $card)
            @php $isMoney = ($card['format'] ?? 'money') === 'money'; @endphp
            <div class="gl-stat {{ $card['tone'] === 'green' ? 'green' : ($card['tone'] === 'red' ? 'red' : '') }}">
                <div class="gl-stat-top"><span class="gl-ico {{ $toneIcon[$card['tone']] ?? 'brand' }}"><i class="fas {{ $card['icon'] ?? 'fa-chart-bar' }}"></i></span><span class="gl-k">{{ $card['label'] }}</span></div>
                <div class="gl-v {{ $isMoney && $card['value'] < -0.004 ? 'fr-neg' : '' }}">
                    @if($isMoney){{ $card['value'] < -0.004 ? '(' . number_format(abs($card['value']), 2) . ')' : number_format($card['value'], 2) }} <small>AED</small>@else{{ number_format($card['value']) }}@endif
                </div>
                @if(!empty($card['note']))<div class="gl-note">{{ $card['note'] }}</div>@endif
            </div>
        @endforeach
    </div>

    @if($report['chart'])
        <div class="fr-chart"><canvas id="frChart"></canvas></div>
    @endif

    @foreach($report['tables'] as $table)
        <div class="gl-card" style="margin-bottom:1rem">
            <div class="fr-card-title">{{ $table['title'] }}</div>
            <div class="gl-tablewrap">
                <table class="gl-table fr-table" style="min-width:{{ count($table['columns']) > 6 ? 900 : 600 }}px">
                    <thead><tr>@foreach($table['columns'] as [$label, $type])<th class="{{ $type === 'text' ? '' : 'r' }}">{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                    @forelse($table['rows'] as $row)
                        @php $style = $row['_style'] ?? null; $cells = $style ? $row['cells'] : $row; @endphp
                        <tr class="{{ $style ? 'fr-' . $style : '' }}">
                            @foreach($table['columns'] as $i => [$label, $type])
                                <td class="{{ $type === 'text' ? '' : 'r' }}" data-label="{{ $label }}">{!! $style === 'section' && $i > 0 ? '' : $cell($cells[$i] ?? null, $type) !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($table['columns']) }}"><div class="gl-empty"><i class="fas fa-inbox"></i>Nothing in this period.</div></td></tr>
                    @endforelse
                    </tbody>
                    @if(!empty($table['footer']) && count($table['rows']))
                        <tfoot><tr>@foreach($table['columns'] as $i => [$label, $type])<td class="{{ $type === 'text' ? '' : 'r' }}" data-label="{{ $i === 0 ? '' : $label }}">{!! $table['footer'][$i] === null ? '' : $cell($table['footer'][$i], $type) !!}</td>@endforeach</tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endforeach

    @if(!empty($report['notes']))
        <div class="fr-notes"><b><i class="fas fa-info-circle"></i> How this report is calculated</b><ul>@foreach($report['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div>
    @endif
</div>

@if($report['chart'])
<script>
    // Inline loader (same as the dashboard's): <script src> tags do not run on AJAX page navigation.
    window.loadScriptOnce = window.loadScriptOnce || function (src, cb) {
        var existing = document.querySelector('script[data-lib-src="' + src + '"]');
        if (existing) { if (existing.dataset.loaded === '1') return cb(); return existing.addEventListener('load', cb); }
        var s = document.createElement('script');
        s.src = src; s.dataset.libSrc = src;
        s.addEventListener('load', function () { s.dataset.loaded = '1'; cb(); });
        document.body.appendChild(s);
    };
    loadScriptOnce('https://cdn.jsdelivr.net/npm/chart.js', function () {
        var spec = @json($report['chart']);
        var palette = ['#f45a24', '#06265e', '#16a34a', '#d97706', '#2563eb', '#9333ea', '#0891b2', '#dc2626'];
        var doughnut = spec.type === 'doughnut';
        var datasets = spec.datasets.map(function (d, i) {
            var color = d.color || palette[i % palette.length];
            return {
                label: d.label, data: d.data, type: d.type || spec.type,
                backgroundColor: doughnut ? palette : (d.type === 'line' ? color : color + 'cc'),
                borderColor: doughnut ? '#fff' : color, borderWidth: doughnut ? 2 : (d.type === 'line' ? 2.5 : 0),
                borderRadius: d.type === 'line' || doughnut ? 0 : 6, tension: .3, pointRadius: d.type === 'line' ? 3 : 0, order: d.type === 'line' ? 0 : 1
            };
        });
        var money = function (v) { return Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 }); };
        new Chart(document.getElementById('frChart'), {
            type: spec.type,
            data: { labels: spec.labels, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: doughnut ? 'right' : 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { callbacks: { label: function (ctx) { return (ctx.dataset.label ? ctx.dataset.label + ': ' : '') + (doughnut ? ctx.label + ' ' : '') + money(ctx.raw) + ' AED'; } } },
                    datalabels: false
                },
                scales: doughnut ? {} : { y: { ticks: { callback: money }, grid: { color: '#eef1f5' } }, x: { grid: { display: false } } }
            }
        });
    });
</script>
@endif
@endsection
