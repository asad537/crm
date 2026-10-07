<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $navy = '#1f2d4a';
    $fields = \App\CrmManualOrderProductionBrief::PRODUCT_FIELDS;
    $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
    $d = fn ($x) => $x ? $x->format('d M Y') : '—';
    $img = function ($rel) { $p = public_path($rel); return is_file($p) ? 'data:image/png;base64,' . base64_encode(file_get_contents($p)) : null; };
    $logo = $img('thecustomboxes-logo.png');
@endphp
<style>
    @page { margin: 24px 28px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2733; font-size: 9px; margin: 0; }
    table { border-collapse: collapse; }
    .head td { vertical-align: middle; }
    .title { color: {{ $navy }}; font-size: 16px; font-weight: bold; text-align: right; }
    .title .sub { display: block; color: #64748b; font-size: 7.5px; font-weight: normal; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 3px; }
    .bar { background: {{ $navy }}; color: #fff; font-weight: bold; font-size: 8.5px; padding: 5px 9px; margin-top: 10px; text-transform: uppercase; letter-spacing: .5px; }
    table.kv { width: 100%; }
    table.kv td { border: 1px solid #cfd6e2; padding: 4px 6px; font-size: 8.4px; vertical-align: top; }
    table.kv td.k { background: #f3f6fa; font-weight: bold; color: #334155; width: 17%; }
    table.kv td.v { width: 33%; color: #0f172a; }
    .prod-title { background: #fff7ed; border: 1px solid #fdba74; border-bottom: none; color: #9a3412; font-weight: bold; font-size: 9px; padding: 4px 7px; margin-top: 8px; }
    .meta { color: #64748b; font-size: 7.6px; margin-top: 4px; }
    .foot { margin-top: 14px; font-size: 7.4px; color: #64748b; text-align: center; }
    .lock { display: inline-block; padding: 2px 6px; border: 1px solid #94a3b8; border-radius: 3px; font-size: 7px; color: #475569; }
</style>
</head>
<body>
    <table class="head" style="width:100%"><tr>
        <td style="width:50%">@if($logo)<img src="{{ $logo }}" style="height:40px">@else<b style="font-size:16px;color:{{ $navy }}">The Custom Boxes</b>@endif</td>
        <td class="title">PRODUCTION JOB BRIEFING<span class="sub">Job {{ $brief->job_number }} &middot; Order {{ $label }}</span></td>
    </tr></table>
    <div class="meta">Sent {{ optional($brief->sent_at)->format('d M Y H:i') }} by {{ optional($brief->sender)->name ?: '—' }} &nbsp;&middot;&nbsp; <span class="lock">LOCKED</span></div>

    <div class="bar">Job Briefing</div>
    <table class="kv">
        <tr><td class="k">Date</td><td class="v">{{ $d($brief->brief_date) }}</td><td class="k">Production Type</td><td class="v">{{ $brief->productionTypeLabel() }}</td></tr>
        <tr><td class="k">Job Number</td><td class="v">{{ $brief->job_number }}</td><td class="k">Job Type</td><td class="v">{{ $brief->job_type }}</td></tr>
        <tr><td class="k">Client Name</td><td class="v">{{ $brief->client_name }}</td><td class="k">Enquiry #</td><td class="v">{{ $order->enquiry_number ?: '—' }}</td></tr>
        <tr><td class="k">Sales Person</td><td class="v">{{ $order->sales_person ?: ($order->user_name ?: '—') }}</td><td class="k">Invoice / Amount</td><td class="v">{{ $label }} &middot; {{ strtoupper($order->currency ?: 'USD') }} {{ number_format((float) $order->total, 2) }} ({{ strtoupper($order->invoice_status) }})</td></tr>
    </table>

    <div class="bar">Product Description</div>
    @foreach(($brief->products ?: []) as $i => $pr)
        <div class="prod-title">Product {{ $i + 1 }}: {{ $pr['product'] ?? '' }}</div>
        <table class="kv">
            <tr><td class="k">Product</td><td class="v">{{ $pr['product'] ?? '' }}</td><td class="k">Material</td><td class="v">{{ $pr['material'] ?? '' }}</td></tr>
            <tr><td class="k">Quantity</td><td class="v">{{ $pr['quantity'] ?? '' }}</td><td class="k">Finish Size</td><td class="v">{{ $pr['finish_size'] ?? '' }}</td></tr>
            <tr><td class="k">Lamination</td><td class="v">{{ $pr['lamination'] ?? '' }}</td><td class="k">Printing</td><td class="v">{{ $pr['printing'] ?? '' }}</td></tr>
            <tr><td class="k">Diecut Window</td><td class="v">{{ $pr['diecut_window'] ?? '' }}</td><td class="k">Plastic Film</td><td class="v">{{ $pr['plastic_film'] ?? '' }}</td></tr>
            <tr><td class="k">Pasting</td><td class="v">{{ $pr['pasting'] ?? '' }}</td><td class="k">Spot UV</td><td class="v">{{ $pr['spot_uv'] ?? '' }}</td></tr>
            <tr><td class="k">Deboss / Emboss</td><td class="v">{{ $pr['deboss_emboss'] ?? '' }}</td><td class="k">Raised Ink / Foiling</td><td class="v">{{ $pr['raised_ink_foiling'] ?? '' }}</td></tr>
            <tr><td class="k">Additional Requirements</td><td class="v" colspan="3" style="white-space:pre-wrap">{{ $pr['additional_requirements'] ?? '' }}</td></tr>
        </table>
    @endforeach

    <div class="bar">Files &amp; Deadlines</div>
    <table class="kv">
        <tr><td class="k">Folder Path</td><td class="v" colspan="3">{{ $brief->folder_path ?: '—' }}</td></tr>
        <tr><td class="k">Job Forwarding Date</td><td class="v">{{ $d($brief->job_forwarding_date) }}</td><td class="k">Printer's Deadline</td><td class="v">{{ $d($brief->printers_deadline) }}</td></tr>
        <tr><td class="k">Client's Deadline</td><td class="v">{{ $d($brief->clients_deadline) }}</td><td class="k">Notes</td><td class="v" style="white-space:pre-wrap">{{ $brief->additional_requirements ?: '—' }}</td></tr>
    </table>

    <div class="foot">Generated from CRM &middot; The Custom Boxes &middot; This briefing is locked once sent; changes require an admin.</div>
</body>
</html>
