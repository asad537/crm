@php
    $isAlMassa = isset($activeCrmWorkspace) && $activeCrmWorkspace && $activeCrmWorkspace->slug === 'mybox-packaging-app';
    $companyName = $isAlMassa ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING';
    $logoPath = public_path($isAlMassa ? 'al-massa-packaging-logo-pdf.jpg' : 'my-box-printing-logo-pdf.jpg');
    $logoData = is_file($logoPath) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath)) : null;
    $reportCurrency = $purchases->pluck('currency')->filter()->unique()->count() === 1 ? $purchases->first()->currency : '';
    $reportTotal = $purchases->sum('total_amount');
    $reportPaid = $purchases->sum('paid_amount');
    $reportBalance = $purchases->sum('balance_amount');
@endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>Vendor Purchases Report</title><style>
@page{margin:25px 30px 34px}*{box-sizing:border-box}body{margin:0;color:#1d2939;font-family:DejaVu Sans,sans-serif;font-size:8.5px;line-height:1.35}.letterhead{width:100%;border-collapse:collapse}.letterhead td{padding:0;vertical-align:top}.logo-cell{width:102px}.logo{width:84px;max-height:84px}.company-cell{padding-top:3px!important}.company-name{color:#06265e;font-size:14px;font-weight:bold;letter-spacing:.2px}.company-line{margin-top:6px;color:#27364b;font-size:8px}.document-cell{width:150px;padding-top:38px!important;color:#e9aa10;font-size:20px;text-align:right}.letter-rule{height:1px;margin:9px 0 14px;background:#9aa0a6;border-left:48px solid #06265e}.report-head{display:table;width:100%;padding:10px 12px;margin-bottom:10px;border:1px solid #e1e7ef;background:#f7f9fc}.report-head-copy,.report-head-meta{display:table-cell;vertical-align:middle}.report-head-copy strong{display:block;color:#06265e;font-size:13px}.report-head-copy span,.report-head-meta{color:#6f7d90}.report-head-meta{text-align:right}.summary{width:100%;margin:0 0 12px;border-collapse:separate;border-spacing:6px 0}.summary td{width:25%;padding:8px 10px;border:1px solid #dfe6ef;background:#fff}.summary .label{color:#7b899c;font-size:6.8px;font-weight:bold;text-transform:uppercase}.summary .value{display:block;margin-top:3px;color:#172033;font-size:11px;font-weight:bold}.summary .balance .value{color:#d97706}table.data{width:100%;table-layout:fixed;border-collapse:collapse}table.data th{padding:7px 6px;background:#06265e;color:#fff;font-size:6.8px;text-align:center;text-transform:uppercase}table.data td{padding:7px 6px;border-bottom:1px solid #dce3eb;vertical-align:middle;text-align:center;overflow-wrap:break-word;word-wrap:break-word}table.data tr:nth-child(even) td{background:#eef7fb}.date{white-space:nowrap}.item{line-height:1.3;text-align:center}.num{text-align:center;white-space:nowrap}.status{text-align:center;font-weight:bold}.status-paid{color:#047857}.status-partial{color:#a16207}.status-unpaid{color:#be123c}.footer{position:fixed;left:0;right:0;bottom:-20px;padding-top:6px;border-top:1px solid #cbd5e1;color:#7b899c;font-size:7px;text-align:center}
</style></head><body>
<table class="letterhead"><tr><td class="logo-cell">@if($logoData)<img class="logo" src="{{ $logoData }}">@endif</td><td class="company-cell"><div class="company-name">{{ $companyName }}</div>@if($isAlMassa)<div class="company-line">All Cosmetics &amp; Perfumes Hard, Soft Boxes and Paper Bags</div><div class="company-line">Al Diyar Building 33, 4th Industrial Street, Industrial Area 12,<br>Sharjah, United Arab Emirates</div><div class="company-line">+971 6 579 6994 &nbsp;|&nbsp; +971 56 997 0652 &nbsp;|&nbsp; +971 54 793 4286</div><div class="company-line">info@almassapackaging.com</div>@else<div class="company-line">Premium Custom Packaging Solutions</div><div class="company-line">www.myboxprinting.com &nbsp;|&nbsp; support@myboxprinting.com</div>@endif</td><td class="document-cell">VENDOR REPORT</td></tr></table>
<div class="letter-rule"></div>
<div class="report-head"><div class="report-head-copy"><strong>Vendor Purchases Report</strong><span>Complete supplier purchase and payment overview</span></div><div class="report-head-meta">Generated {{ now()->format('d M Y, h:i A') }}<br>{{ $purchases->count() }} purchase record(s)</div></div>
<table class="summary"><tr><td><span class="label">Purchase Records</span><span class="value">{{ number_format($purchases->count()) }}</span></td><td><span class="label">Total Purchases</span><span class="value">{{ $reportCurrency }} {{ number_format($reportTotal,2) }}</span></td><td><span class="label">Total Paid</span><span class="value">{{ $reportCurrency }} {{ number_format($reportPaid,2) }}</span></td><td class="balance"><span class="label">Pending Balance</span><span class="value">{{ $reportCurrency }} {{ number_format($reportBalance,2) }}</span></td></tr></table>
<table class="data">
<colgroup><col style="width:8%"><col style="width:8%"><col style="width:12%"><col style="width:25%"><col style="width:9%"><col style="width:9%"><col style="width:10%"><col style="width:11%"><col style="width:8%"></colgroup>
<thead><tr><th>Date</th><th>Invoice</th><th>Vendor</th><th>Product / Specification</th><th class="num">Qty</th><th class="num">Unit Price</th><th class="num">VAT</th><th class="num">Total Price</th><th>Status</th></tr></thead>
<tbody>
@foreach($purchases as $p)
    @php($reportItems = $p->items->isNotEmpty() ? $p->items : collect([$p]))
    @foreach($reportItems as $item)
    <tr>
        <td class="date">{{ $p->purchase_date->format('d M Y') }}</td>
        <td>{{ $p->invoice_number ?: '-' }}</td>
        <td>{{ $p->vendor_name }}</td>
        <td class="item"><strong>{{ $item->item_name }}</strong><br><span style="color:#6f7d90">{{ collect([$item->category, $item->material, $item->specification, $item->size ? $item->size.' size' : null, $item->gsm ? $item->gsm.' GSM' : null, $item->color])->filter()->implode(' - ') }}</span></td>
        <td class="num">{{ rtrim(rtrim(number_format($item->quantity,2,'.',''),'0'),'.') }} {{ $item->unit }}</td>
        <td class="num">{{ $p->currency }} {{ number_format($item->unit_price,4) }}</td>
        @php($itemTotal = isset($item->line_total) ? (float) $item->line_total : (float) $item->subtotal)
        @php($itemVat = (float) $p->subtotal > 0 ? round((float) $p->tax_amount * $itemTotal / (float) $p->subtotal, 2) : 0)
        <td class="num">{{ number_format((float) ($p->vat_percentage ?? 0),2) }}%<br><span style="color:#6f7d90">{{ $p->currency }} {{ number_format($itemVat,2) }}</span></td>
        <td class="num">{{ $p->currency }} {{ number_format($itemTotal + $itemVat,2) }}</td>
        <td class="status status-{{ strtolower($p->payment_status) }}">{{ $p->payment_status }}</td>
    </tr>
    @endforeach
@endforeach
</tbody></table>
<div class="footer">Confidential vendor purchasing report - {{ $companyName }}</div>
</body></html>
