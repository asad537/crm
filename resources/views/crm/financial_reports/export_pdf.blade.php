@php
    $companyName = $meta['isAlMassa'] ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING';
    $documentTitle = 'FINANCIAL REPORT';
    $cell = function ($value, $type) {
        if ($value === null || $value === '') return '';
        if (is_array($value)) return e($value['text']);
        if ($type === 'share') return '<span class="' . ($value < 0 ? 'due' : '') . '">' . number_format($value, 1) . '%</span>';
        if ($type === 'money') return $value < -0.004 ? '<span class="due">(' . number_format(abs($value), 2) . ')</span>' : number_format($value, 2);
        if ($type === 'percent') return '<span class="' . ($value < 0 ? 'due' : 'credit') . '">' . ($value > 0 ? '+' : '') . number_format($value, 1) . '%</span>';
        if ($type === 'number') return number_format((float) $value, fmod((float) $value, 1) ? 2 : 0);
        return e($value);
    };
    $cardCount = count($report['cards']);
@endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>{{ $report['title'] }}</title>
@include('crm.general_ledger._pdf_styles')
<style>
    .document-cell{width:230px;font-size:18px;white-space:nowrap}
    .summary td{width:{{ round(100 / max(1, $cardCount), 2) }}%}
    .summary .value.neg{color:#b91c1c}
    .tbl-title{margin:14px 0 5px;color:#06265e;font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:.5px}
    table.data th{text-align:left}table.data th.num{text-align:right}
    table.data tr.section td{background:#f1f4f9!important;color:#06265e;font-weight:bold;text-transform:uppercase;font-size:7.5px;letter-spacing:.4px}
    table.data tr.subtotal td{font-weight:bold;border-top:1px solid #9aa4b2}
    table.data tr.total td{font-weight:bold;background:#e8eefb!important}
    table.data tr.grand td{font-weight:bold;background:#06265e!important;color:#fff;font-size:9.5px}
    table.data tr.grand .due{color:#fecaca}table.data tr.grand .credit{color:#bbf7d0}
    .notes{margin-top:14px;padding:8px 10px;border:1px solid #fde68a;background:#fffbeb;color:#78350f;font-size:7.5px}
</style>
</head><body>
@include('crm.general_ledger._pdf_letterhead')
<div class="report-head">
    <div class="report-head-copy"><strong>{{ $report['title'] }}</strong><span>{{ $report['subtitle'] }}</span></div>
    <div class="report-head-meta">{{ $meta['rangeLabel'] }}<br>Generated {{ $meta['generatedAt'] }}</div>
</div>
<table class="summary"><tr>
    @foreach($report['cards'] as $card)
        @php $isMoney = ($card['format'] ?? 'money') === 'money'; @endphp
        <td class="{{ $card['tone'] === 'green' ? 'paid' : ($card['tone'] === 'red' ? 'balance' : '') }}">
            <span class="label">{{ $card['label'] }}</span>
            <span class="value {{ $isMoney && $card['value'] < -0.004 ? 'neg' : '' }}">@if($isMoney)AED {{ $card['value'] < -0.004 ? '(' . number_format(abs($card['value']), 2) . ')' : number_format($card['value'], 2) }}@else{{ number_format($card['value']) }}@endif</span>
            @if(!empty($card['note']))<span style="color:#7b899c;font-size:7px">{{ $card['note'] }}</span>@endif
        </td>
    @endforeach
</tr></table>

@foreach($report['tables'] as $table)
    <div class="tbl-title">{{ $table['title'] }}</div>
    <table class="data">
        <thead><tr>@foreach($table['columns'] as [$label, $type])<th class="{{ $type === 'text' ? '' : 'num' }}">{{ $label }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($table['rows'] as $row)
            @php $style = $row['_style'] ?? null; $cells = $style ? $row['cells'] : $row; @endphp
            <tr class="{{ $style }}">
                @foreach($table['columns'] as $i => [$label, $type])<td class="{{ $type === 'text' ? '' : 'num' }}">{!! $style === 'section' && $i > 0 ? '' : $cell($cells[$i] ?? null, $type) !!}</td>@endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($table['columns']) }}" style="color:#7b899c">Nothing in this period.</td></tr>
        @endforelse
        @if(!empty($table['footer']) && count($table['rows']))
            <tr class="tot">@foreach($table['columns'] as $i => [$label, $type])<td class="{{ $type === 'text' ? '' : 'num' }}">{!! $cell($table['footer'][$i] ?? null, $type) !!}</td>@endforeach</tr>
        @endif
        </tbody>
    </table>
@endforeach

@if(!empty($report['notes']))
    <div class="notes"><b>How this report is calculated:</b>@foreach($report['notes'] as $note)<br>• {{ $note }}@endforeach</div>
@endif
<div class="footer">Confidential financial report - {{ $companyName }}</div>
</body></html>
