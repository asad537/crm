@php
    $companyName = $meta['isAlMassa'] ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING';
    $party = $entries->pluck('party')->unique()->count() === 1 ? $entries->first()->party : null;
@endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>{{ $meta['workspaceName'] }} — General Ledger</title>
@include('crm.general_ledger._pdf_styles')
</head><body>
@include('crm.general_ledger._pdf_letterhead')
<div class="report-head">
    <div class="report-head-copy"><strong>{{ $meta['tabLabel'] }}{{ $party ? ' — '.$party : '' }}</strong><span>Every entry with total, paid / received and balance · {{ $meta['rangeLabel'] }}</span></div>
    <div class="report-head-meta">Generated {{ $meta['generatedAt'] }}<br>{{ $entries->count() }} entr{{ $entries->count() === 1 ? 'y' : 'ies' }}</div>
</div>
<table class="summary"><tr>
    <td><span class="label">Total</span><span class="value">AED {{ number_format($meta['totals']['amount'], 2) }}</span></td>
    <td class="paid"><span class="label">Paid / Received</span><span class="value">AED {{ number_format($meta['totals']['paid'], 2) }}</span></td>
    <td class="balance"><span class="label">Balance</span><span class="value">AED {{ number_format($meta['totals']['balance'], 2) }}</span></td>
</tr></table>
<table class="data">
    {{-- dompdf ignores <colgroup> widths, so widths sit on the header cells. --}}
    <thead><tr>
        <th style="width:8%">Date</th><th style="width:8%">Type</th><th style="width:15%;text-align:left">Party</th><th style="width:10%">Ref #</th>
        <th style="width:18%;text-align:left">Detail</th><th style="width:6%">Term</th>
        <th class="num" style="width:9%">Total</th><th class="num" style="width:9%">Paid</th><th class="num" style="width:9%">Balance</th><th style="width:8%">Status</th>
    </tr></thead>
    <tbody>
    @foreach($entries as $entry)
        <tr>
            <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</td>
            <td class="center"><span class="badge {{ $entry->type }}">{{ $entry->type }}</span></td>
            <td><strong>{{ $entry->party }}</strong></td>
            <td>{{ $entry->ref }}</td>
            <td>{{ $entry->detail }}</td>
            <td>{{ $entry->term ?: '-' }}</td>
            <td class="num">{{ number_format($entry->total, 2) }}@if($entry->currency !== 'AED') <span style="color:#6f7d90">{{ $entry->currency }}</span>@endif</td>
            <td class="num credit">{{ number_format($entry->paid, 2) }}</td>
            <td class="num {{ $entry->balance > 0.009 ? 'due' : '' }}">{{ number_format($entry->balance, 2) }}</td>
            <td class="center">{{ $entry->status_label }}@if($entry->settled_at)<br><span style="color:#6f7d90">{{ \Carbon\Carbon::parse($entry->settled_at)->format('d M Y') }}</span>@endif</td>
        </tr>
    @endforeach
    <tr class="tot">
        <td colspan="6">TOTAL</td>
        <td class="num">{{ number_format($meta['totals']['amount'], 2) }}</td>
        <td class="num">{{ number_format($meta['totals']['paid'], 2) }}</td>
        <td class="num">{{ number_format($meta['totals']['balance'], 2) }}</td>
        <td></td>
    </tr>
    </tbody>
</table>
<div class="footer">Confidential general ledger - {{ $companyName }}</div>
</body></html>
