@php
    $showType = $meta['tab'] === 'all';
    $companyName = $meta['isAlMassa'] ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING';
@endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>{{ $meta['workspaceName'] }} — {{ $meta['partyLabel'] }} Summary</title>
@include('crm.general_ledger._pdf_styles')
</head><body>
@include('crm.general_ledger._pdf_letterhead')
<div class="report-head">
    <div class="report-head-copy"><strong>{{ $meta['tabLabel'] }} — {{ $meta['partyLabel'] }} Summary</strong><span>One line per {{ strtolower($meta['partyLabel']) }} with total, {{ strtolower($meta['paidLabel']) }} and balance · {{ $meta['rangeLabel'] }}</span></div>
    <div class="report-head-meta">Generated {{ $meta['generatedAt'] }}<br>{{ $rows->count() }} {{ strtolower($meta['partyLabel']) }}(s) · {{ $rows->sum('count') }} entries</div>
</div>
<table class="summary"><tr>
    <td><span class="label">Total</span><span class="value">AED {{ number_format($meta['totals']['amount'], 2) }}</span></td>
    <td class="paid"><span class="label">{{ $meta['paidLabel'] }}</span><span class="value">AED {{ number_format($meta['totals']['paid'], 2) }}</span></td>
    <td class="balance"><span class="label">Balance</span><span class="value">AED {{ number_format($meta['totals']['balance'], 2) }}</span></td>
</tr></table>
<table class="data">
    {{-- dompdf ignores <colgroup> widths, so widths sit on the header cells. --}}
    <thead><tr>
        <th style="width:5%">#</th><th style="width:{{ $showType ? 31 : 39 }}%;text-align:left">{{ $meta['partyLabel'] }}</th>@if($showType)<th style="width:10%">Type</th>@endif
        <th style="width:8%">Entries</th><th style="width:11%">Last Entry</th>
        <th class="num" style="width:12%">Total</th><th class="num" style="width:12%">{{ $meta['paidLabel'] }}</th><th class="num" style="width:11%">Balance</th>
    </tr></thead>
    <tbody>
    @foreach($rows as $i => $row)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td><strong>{{ $row->party }}</strong>@if($row->currency !== 'AED') <span style="color:#6f7d90">({{ $row->currency }})</span>@endif</td>
            @if($showType)<td class="center"><span class="badge {{ $row->type }}">{{ $row->type }}</span></td>@endif
            <td class="center">{{ $row->count }}</td>
            <td class="center" style="white-space:nowrap">{{ $row->last_date->format('d M Y') }}</td>
            <td class="num">{{ number_format($row->total, 2) }}</td>
            <td class="num credit">{{ number_format($row->paid, 2) }}</td>
            <td class="num {{ $row->balance > 0.009 ? 'due' : '' }}">{{ number_format($row->balance, 2) }}</td>
        </tr>
    @endforeach
    <tr class="tot">
        <td colspan="{{ $showType ? 3 : 2 }}">GRAND TOTAL</td><td class="center">{{ $rows->sum('count') }}</td><td></td>
        <td class="num">{{ number_format($meta['totals']['amount'], 2) }}</td>
        <td class="num">{{ number_format($meta['totals']['paid'], 2) }}</td>
        <td class="num">{{ number_format($meta['totals']['balance'], 2) }}</td>
    </tr>
    </tbody>
</table>
<div class="footer">Confidential general ledger - {{ $companyName }}</div>
</body></html>
