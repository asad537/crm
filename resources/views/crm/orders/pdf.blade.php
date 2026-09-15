<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $isTcb = stripos($order->website ?? '', 'thecustomboxes') !== false || !$order->website;
    $brand = $isTcb ? 'The Custom Boxes' : 'My Box Printing';
    $addr = $isTcb ? '9933 Franklin Ave, Franklin Park, IL 60131' : '';
    $site = $isTcb ? 'www.thecustomboxes.com' : 'www.myboxprinting.com';
    $logo = public_path('thecustomboxes-logo.png');
    $logoData = ($isTcb && is_file($logo)) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logo)) : null;
    $cur = $order->currency ?: 'USD';
    $b = $order->billing ?? []; $s = $order->shipping ?? [];
    $addrLine = function($a){ return trim(implode(', ', array_filter([$a['company']??'',$a['street']??'',$a['city']??'',$a['state']??'',$a['country']??'',$a['zip']??'']))); };
@endphp
<style>
    @page{margin:26px 28px}
    body{font-family:DejaVu Sans,Arial,sans-serif;color:#1a1a1a;font-size:10px;margin:0}
    .hdr{width:100%;border-collapse:collapse;margin-bottom:8px}
    .hdr td{vertical-align:top}
    .logo{height:48px}
    .brand{font-size:18px;font-weight:bold;color:#2E75B6}
    .inv{font-size:26px;font-weight:bold;color:#2E75B6;text-align:right}
    .meta{border-collapse:collapse;width:250px;float:right;margin-top:4px}
    .meta td{border:1px solid #1a1a1a;padding:3px 6px;font-size:9px}
    .meta .k{font-weight:bold}
    .sec{background:#2E75B6;color:#fff;font-weight:bold;padding:3px 7px;font-size:9px;margin-top:10px}
    .addr{font-size:9px;line-height:1.5;padding:6px 2px}
    table.items{width:100%;border-collapse:collapse;margin-top:8px}
    table.items th{background:#2E75B6;color:#fff;font-size:8.5px;padding:5px 6px;text-align:left;border:1px solid #2E75B6}
    table.items td{border:1px solid #d5dbe2;padding:5px 6px;font-size:9px}
    .num{text-align:right}
    .tot{width:250px;float:right;border-collapse:collapse;margin-top:8px}
    .tot td{padding:4px 8px;font-size:9.5px;border-bottom:1px solid #eef2f7}
    .tot .g{font-weight:bold;font-size:11px;border-top:2px solid #2E75B6}
</style>
</head>
<body>
    <table class="hdr"><tr>
        <td style="width:60%">
            @if($logoData)<img src="{{ $logoData }}" class="logo">@else<div class="brand">{{ $brand }}</div>@endif
            <div class="addr">{{ $addr }}<br>{{ $site }}</div>
        </td>
        <td style="width:40%">
            <div class="inv">INVOICE</div>
            <table class="meta">
                <tr><td class="k">Invoice #</td><td>{{ $order->invoice_number ? 'TCB-'.$order->invoice_number : ('#'.$order->id) }}</td></tr>
                <tr><td class="k">Enquiry #</td><td>{{ $order->enquiry_number }}</td></tr>
                <tr><td class="k">Date</td><td>{{ optional($order->invoice_date)->format('m/d/Y') ?: $order->created_at->format('m/d/Y') }}</td></tr>
                <tr><td class="k">Status</td><td>{{ strtoupper($order->invoice_status) }}</td></tr>
                <tr><td class="k">Payment Term</td><td>{{ $order->payment_term }}</td></tr>
            </table>
        </td>
    </tr></table>

    <table style="width:100%;border-collapse:collapse"><tr>
        <td style="width:50%;vertical-align:top;padding-right:8px">
            <div class="sec">BILL TO</div>
            <div class="addr"><strong>{{ $b['name'] ?? '' }}</strong><br>{{ $addrLine($b) }}<br>{{ $b['phone'] ?? '' }}</div>
        </td>
        <td style="width:50%;vertical-align:top;padding-left:8px">
            <div class="sec">SHIP TO</div>
            <div class="addr"><strong>{{ $s['name'] ?? '' }}</strong><br>{{ $addrLine($s) }}<br>{{ $s['phone'] ?? '' }}</div>
        </td>
    </tr></table>

    <table class="items">
        <thead><tr>
            <th>Box Style</th><th>Specification</th><th>Finishing</th>
            <th class="num">Qty</th><th class="num">Unit ({{ $cur }})</th><th class="num">Other</th><th class="num">Total ({{ $cur }})</th>
        </tr></thead>
        <tbody>
        @forelse($order->line_items ?? [] as $it)
            <tr>
                <td><strong>{{ $it['box_style'] ?? '' }}</strong>@if(!empty($it['color'])) · {{ $it['color'] }}@endif</td>
                <td>{{ trim(($it['stock'] ?? '').' '.(($it['length']??'')!==''?($it['length'].'x'.($it['width']??'').'x'.($it['height']??'').' '.($it['unit']??'')):'')) }}</td>
                <td>{{ $it['finishing'] ?? '' }}</td>
                <td class="num">{{ $it['qty'] ?? 0 }}</td>
                <td class="num">{{ number_format((float)($it['unit_price'] ?? 0),3) }}</td>
                <td class="num">{{ number_format((float)($it['other_charges'] ?? 0),2) }}</td>
                <td class="num">{{ number_format((float)($it['line_total'] ?? 0),2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#888;padding:14px">No line items.</td></tr>
        @endforelse
        </tbody>
    </table>

    <table class="tot">
        <tr><td>Sub Total</td><td class="num">{{ $cur }} {{ number_format($order->sub_total,2) }}</td></tr>
        <tr><td>Package Price</td><td class="num">{{ $cur }} {{ number_format($order->package_price,2) }}</td></tr>
        <tr><td>Rush Charges</td><td class="num">{{ $cur }} {{ number_format($order->rush_charges,2) }}</td></tr>
        <tr><td>Discount</td><td class="num">- {{ $cur }} {{ number_format($order->discount,2) }}</td></tr>
        <tr class="g"><td>Total</td><td class="num">{{ $cur }} {{ number_format($order->total,2) }}</td></tr>
    </table>
</body>
</html>
