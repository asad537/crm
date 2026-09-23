@php
    $isAlMassa = isset($activeCrmWorkspace) && $activeCrmWorkspace && $activeCrmWorkspace->slug === 'mybox-packaging-app';
    $companyName = $isAlMassa ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING';
    $logoPath = public_path($isAlMassa ? 'al-massa-packaging-logo-pdf.jpg' : 'my-box-printing-logo-pdf.jpg');
    $logoData = is_file($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null;
    $currency = optional($ledger->firstWhere('purchase'))->purchase->currency ?? '';
    $primary = $isAlMassa ? '#f45a24' : '#6c5ce7';
@endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>Vendor Ledger</title><style>
@page{margin:25px 30px 34px}*{box-sizing:border-box}body{margin:0;color:#1d2939;font-family:DejaVu Sans,sans-serif;font-size:8.5px;line-height:1.35}
.letterhead{width:100%;border-collapse:collapse}.letterhead td{padding:0;vertical-align:top}.logo-cell{width:102px}.logo{width:84px;max-height:84px}.company-cell{padding-top:3px!important}.company-name{color:#06265e;font-size:14px;font-weight:bold;letter-spacing:.2px}.company-line{margin-top:6px;color:#27364b;font-size:8px}.document-cell{width:150px;padding-top:38px!important;color:#e9aa10;font-size:20px;text-align:right}.letter-rule{height:1px;margin:9px 0 14px;background:#9aa0a6;border-left:48px solid #06265e}
.report-head{display:table;width:100%;padding:10px 12px;margin-bottom:10px;border:1px solid #e1e7ef;background:#f7f9fc}.report-head-copy,.report-head-meta{display:table-cell;vertical-align:middle}.report-head-copy strong{display:block;color:#06265e;font-size:13px}.report-head-copy span,.report-head-meta{color:#6f7d90}.report-head-meta{text-align:right}
.summary{width:100%;margin:0 0 12px;border-collapse:separate;border-spacing:6px 0}.summary td{width:33.33%;padding:8px 10px;border:1px solid #dfe6ef;background:#fff}.summary .label{color:#7b899c;font-size:6.8px;font-weight:bold;text-transform:uppercase}.summary .value{display:block;margin-top:3px;color:#172033;font-size:11px;font-weight:bold}.summary .balance .value{color:#d97706}
table.data{width:100%;table-layout:fixed;border-collapse:collapse}table.data th{padding:7px 6px;background:{{ $primary }};color:#fff;font-size:6.8px;text-align:center;text-transform:uppercase}table.data td{padding:6px;border-bottom:1px solid #dce3eb;vertical-align:middle;overflow-wrap:break-word;word-wrap:break-word}table.data tr:nth-child(even) td{background:#eef7fb}.num{text-align:right;white-space:nowrap}.credit{color:#047857}.tot td{font-weight:bold;background:#f1f5ff!important;border-top:1.5px solid #06265e}
.footer{position:fixed;left:0;right:0;bottom:-20px;padding-top:6px;border-top:1px solid #cbd5e1;color:#7b899c;font-size:7px;text-align:center}
</style></head><body>
<table class="letterhead"><tr><td class="logo-cell">@if($logoData)<img class="logo" src="{{ $logoData }}">@endif</td><td class="company-cell"><div class="company-name">{{ $companyName }}</div>@if($isAlMassa)<div class="company-line">All Cosmetics &amp; Perfumes Hard, Soft Boxes and Paper Bags</div><div class="company-line">Al Diyar Building 33, 4th Industrial Street, Industrial Area 12,<br>Sharjah, United Arab Emirates</div><div class="company-line">+971 6 579 6994 &nbsp;|&nbsp; +971 56 997 0652</div><div class="company-line">info@almassapackaging.com</div>@else<div class="company-line">Premium Custom Packaging Solutions</div><div class="company-line">www.myboxprinting.com &nbsp;|&nbsp; support@myboxprinting.com</div>@endif</td><td class="document-cell">LEDGER</td></tr></table>
<div class="letter-rule"></div>
<div class="report-head"><div class="report-head-copy"><strong>Vendor Ledger — {{ optional($vendor)->name }}</strong><span>Purchases &amp; payments with running balance</span></div><div class="report-head-meta">Generated {{ now()->format('d M Y, h:i A') }}<br>{{ $ledger->count() }} entries</div></div>
<table class="summary"><tr><td><span class="label">Total Purchases</span><span class="value">{{ $currency }} {{ number_format($totalDebit,2) }}</span></td><td><span class="label">Total Paid</span><span class="value">{{ $currency }} {{ number_format($totalCredit,2) }}</span></td><td class="balance"><span class="label">Closing Balance</span><span class="value">{{ $currency }} {{ number_format($ledgerBalance,2) }}</span></td></tr></table>
<table class="data">
<colgroup><col style="width:9%"><col style="width:15%"><col style="width:11%"><col style="width:8%"><col style="width:8%"><col style="width:19%"><col style="width:10%"><col style="width:10%"><col style="width:10%"></colgroup>
<thead><tr><th>Date</th><th>Vendor</th><th>Invoice</th><th>Job No</th><th>Demand</th><th>Item / Description</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance</th></tr></thead>
<tbody>
@foreach($ledger as $e)
@php($p = $e->purchase ?? null)
<tr>
    <td style="white-space:nowrap">{{ $e->date ? $e->date->format('d M Y') : '' }}</td>
    <td>{{ $p ? $p->vendor_name : '' }}</td>
    <td>{{ $p ? ($p->invoice_number ?: '-') : '' }}</td>
    <td>{{ $p ? ($p->job_id ?: '-') : '' }}</td>
    <td>{{ $p ? ($p->demand_no ?: '-') : '' }}</td>
    <td>@if($p)<strong>{{ $p->items->pluck('item_name')->filter()->implode(', ') ?: $p->item_name }}</strong>@else {{ $e->desc }}@if(!empty($e->sub)) <span style="color:#6f7d90">— {{ $e->sub }}</span>@endif @endif</td>
    <td class="num">{{ $e->debit > 0.009 ? number_format($e->debit,2) : '' }}</td>
    <td class="num credit">{{ $e->credit > 0.009 ? number_format($e->credit,2) : '' }}</td>
    <td class="num">{{ number_format($e->balance,2) }}</td>
</tr>
@endforeach
<tr class="tot"><td colspan="6">TOTAL</td><td class="num">{{ number_format($totalDebit,2) }}</td><td class="num">{{ number_format($totalCredit,2) }}</td><td class="num">{{ number_format($ledgerBalance,2) }}</td></tr>
</tbody></table>
<div class="footer">Confidential vendor ledger - {{ $companyName }}</div>
</body></html>
