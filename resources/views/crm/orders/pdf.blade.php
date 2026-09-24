<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $navy = '#405f8e';
    $cur = $order->currency ?: 'USD';
    $b = $order->billing ?? [];
    $s = $order->shipping ?? [];
    $addrLine = function ($a) {
        return trim(implode(', ', array_filter([$a['company'] ?? '', $a['street'] ?? '', $a['city'] ?? '', $a['state'] ?? '', $a['country'] ?? '', $a['zip'] ?? ''])));
    };
    $img = function ($rel) {
        $p = public_path($rel);
        return is_file($p) ? 'data:image/png;base64,' . base64_encode(file_get_contents($p)) : null;
    };
    $icon = $img('tcb-icon.png');
    $paid = strtolower($order->invoice_status ?: '') === 'paid';
    $pays = ['paypal.png', 'master-card.png', 'visa.png', 'american-express.png', 'discover.png', 'ebank-transfer.png'];
    $money = fn ($v) => $cur . ' ' . number_format((float) $v, 2);
    $unit = function ($v) {
        $s = number_format((float) $v, 4, '.', ',');
        if (strpos($s, '.') !== false) {
            $s = rtrim($s, '0');
            if (strlen(substr($s, strrpos($s, '.') + 1)) < 2) { $s = number_format((float) $v, 2, '.', ','); }
        }
        return $s;
    };
@endphp
<style>
    @page { margin: 22px 26px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2733; font-size: 9px; margin: 0; }
    table { border-collapse: collapse; }
    .head { width: 100%; }
    .head td { vertical-align: middle; }
    .brand-cell { background: {{ $navy }}; padding: 12px 16px; border-radius: 6px; }
    .brand-cell img { height: 34px; vertical-align: middle; }
    .brand-name { color: #fff; font-size: 17px; font-weight: bold; }
    .brand-name .light { color: #c7d2e6; }
    .brand-tag { color: #aebbd4; font-size: 6.5px; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 3px; }
    .sale { color: {{ $navy }}; font-size: 23px; font-weight: bold; text-align: right; }

    .addr { font-size: 8.5px; line-height: 1.6; color: #475569; }
    .addr .lbl { font-weight: bold; color: #1f2733; font-size: 9px; }
    table.kv { width: 250px; }
    table.kv td { border: 1px solid #98a6bd; padding: 4px 7px; font-size: 8.5px; }
    table.kv td.k { font-weight: bold; width: 52%; }
    .stamp { text-align: right; font-weight: bold; font-size: 11px; margin-bottom: 4px; }
    .stamp .paid { color: #0a7d33; } .stamp .unpaid { color: #d9342b; }

    .bar { background: {{ $navy }}; color: #fff; font-weight: bold; font-size: 9px; padding: 5px 9px; }
    .party { border: 1px solid #cfd6e2; border-top: none; padding: 8px 9px; font-size: 8.5px; line-height: 1.55; color: #334155; height: 58px; }
    .party b { color: #0f172a; }

    table.strip { width: 100%; margin-top: 14px; }
    table.strip th { background: {{ $navy }}; color: #fff; font-size: 7.5px; padding: 5px 6px; border: 1px solid {{ $navy }}; }
    table.strip td { border: 1px solid #cfd6e2; padding: 6px; font-size: 8px; text-align: center; color: #334155; }

    table.items { width: 100%; margin-top: 14px; }
    table.items th { background: {{ $navy }}; color: #fff; font-size: 7.8px; padding: 6px 6px; border: 1px solid {{ $navy }}; text-align: left; text-transform: uppercase; }
    table.items td { border: 1px solid #cfd6e2; padding: 7px 6px; font-size: 8.5px; color: #334155; vertical-align: top; }
    table.items td b { color: #0f172a; }
    .num { text-align: right; } .ctr { text-align: center; }

    .terms .bar { margin: 0; }
    .terms-body { border: 1px solid #cfd6e2; border-top: none; padding: 8px 10px; font-size: 7.6px; line-height: 1.5; color: #334155; }
    .terms-body ol { margin: 0; padding-left: 14px; }
    .terms-body li { margin-bottom: 5px; }
    .terms-body .sub { color: #475569; }
    table.tot { width: 100%; }
    table.tot td { border: 1px solid #98a6bd; padding: 6px 9px; font-size: 9px; }
    table.tot td.k { font-weight: bold; width: 56%; }
    table.tot td.v { text-align: right; }
    table.tot tr.g td { background: {{ $navy }}; color: #fff; font-weight: bold; font-size: 11px; border-color: {{ $navy }}; }

    .foot { text-align: center; margin-top: 20px; }
    .foot .ty { color: {{ $navy }}; font-weight: bold; font-size: 12px; }
    .foot .cs { color: #475569; font-size: 8px; margin-top: 5px; line-height: 1.5; }
    .foot .contact { color: {{ $navy }}; font-size: 8px; font-weight: bold; margin-top: 8px; }
    .foot .pay img { height: 18px; margin: 0 4px; vertical-align: middle; }
</style>
</head>
<body>

    {{-- Header --}}
    <table class="head"><tr>
        <td style="width:58%">
            <table class="brand-cell" style="width:auto"><tr>
                @if($icon)<td style="padding-right:10px"><img src="{{ $icon }}"></td>@endif
                <td><div class="brand-name">TheCustom<span class="light">Boxes</span></div><div class="brand-tag">Smart Packaging Solutions</div></td>
            </tr></table>
        </td>
        <td style="width:42%"><div class="sale">Sale Invoice</div></td>
    </tr></table>

    {{-- Address + meta --}}
    <table style="width:100%;margin-top:12px"><tr>
        <td style="width:55%;vertical-align:top">
            <div class="addr"><span class="lbl">Address:</span><br>9933 Franklin Ave,<br>Franklin Park, IL 60131<br>1800-396-1840</div>
        </td>
        <td style="width:45%;vertical-align:top">
            <div class="stamp"># <span class="{{ $paid ? 'paid' : 'unpaid' }}">{{ $paid ? 'PAID' : strtoupper($order->invoice_status ?: 'UNPAID') }}</span></div>
            <table class="kv" style="float:right">
                <tr><td class="k">Invoice no :</td><td>{{ $order->invoice_number ? 'TCB-'.$order->invoice_number : ('#'.$order->id) }}</td></tr>
                <tr><td class="k">Date :</td><td>{{ optional($order->invoice_date)->format('m/d/Y') ?: optional($order->created_at)->format('m/d/Y') }}</td></tr>
                <tr><td class="k">Purchase Order # :</td><td>{{ $order->enquiry_number ?: '—' }}</td></tr>
            </table>
        </td>
    </tr></table>

    {{-- Bill / Ship --}}
    <table style="width:100%;margin-top:14px"><tr>
        <td style="width:50%;vertical-align:top;padding-right:7px">
            <div class="bar">Bill To :</div>
            <div class="party"><b>{{ $b['name'] ?? '' }}</b><br>{{ $addrLine($b) }}<br>{{ $b['phone'] ?? '' }}</div>
        </td>
        <td style="width:50%;vertical-align:top;padding-left:7px">
            <div class="bar">Ship To :</div>
            <div class="party"><b>{{ $s['name'] ?? ($b['name'] ?? '') }}</b><br>{{ $addrLine(!empty($s) ? $s : $b) }}<br>{{ $s['phone'] ?? ($b['phone'] ?? '') }}</div>
        </td>
    </tr></table>

    {{-- Meta strip --}}
    <table class="strip">
        <tr><th>Sales person</th><th>Shipping Method</th><th>Shipping Terms</th><th>Payment Terms</th><th>Due Date</th></tr>
        <tr>
            <td>{{ $order->sales_person ?: '—' }}</td>
            <td>{{ $order->shipping_method ?: '—' }}</td>
            <td>{{ $order->shipping_term ?: 'Standard' }}</td>
            <td>{{ $order->payment_term ?: '—' }}</td>
            <td>—</td>
        </tr>
    </table>

    {{-- Items --}}
    <table class="items">
        <thead><tr>
            <th style="width:6%" class="ctr">Sr.</th>
            <th style="width:13%">Size</th>
            <th style="width:16%">Stock</th>
            <th>Description</th>
            <th style="width:8%" class="ctr">Qty</th>
            <th style="width:11%" class="num">Unit Price</th>
            <th style="width:15%" class="num">Line Total</th>
        </tr></thead>
        <tbody>
        @forelse($order->line_items ?? [] as $i => $it)
            @php $size = trim((($it['length'] ?? '') !== '' ? ($it['length'].' x '.($it['width'] ?? '').' x '.($it['height'] ?? '').' '.($it['unit'] ?? '')) : '')); @endphp
            <tr>
                <td class="ctr">{{ $i + 1 }}</td>
                <td class="ctr">{{ $size ?: '—' }}</td>
                <td>{{ $it['stock'] ?? '—' }}</td>
                <td><b>{{ $it['box_style'] ?? '' }}</b>@if(!empty($it['color'])) · {{ $it['color'] }}@endif @if(!empty($it['finishing']))<br><span style="color:#64748b">{{ $it['finishing'] }}</span>@endif</td>
                <td class="ctr">{{ $it['qty'] ?? 0 }}</td>
                <td class="num">{{ $unit($it['unit_price'] ?? 0) }}</td>
                <td class="num">{{ $money($it['line_total'] ?? 0) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="ctr" style="color:#888;padding:14px">No line items.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- Terms + totals --}}
    <table style="width:100%;margin-top:16px"><tr>
        <td style="width:58%;vertical-align:top;padding-right:10px">
            <div class="terms">
                <div class="bar">Terms &amp; Conditions :</div>
                <div class="terms-body">
                    <ol>
                        <li>As a part of order placement we require the users and customers of our website to approve proof (whether an electronic file or hard copy) of the printing products or other services they order.</li>
                        <li>Following the approval of proof by the customers the printing jobs are to press or via our website, no changes are allowed to the artwork files, job specifications, or printing turnaround time.</li>
                        <li>Lead Time once a print job has been approved by customer and sent to press.<br><span class="sub">Standard (10 to 12 Business Days and 2 to 3 days for ground shipping.)</span><br><span class="sub">Rush (6 to 8 Business Days and 2 to 3 days for ground shipping.)</span></li>
                        <li>No hidden or setup charges.</li>
                    </ol>
                </div>
            </div>
        </td>
        <td style="width:42%;vertical-align:top">
            <table class="tot">
                <tr><td class="k">Discount</td><td class="v">- {{ $money($order->discount) }}</td></tr>
                <tr><td class="k">Sub Total</td><td class="v">{{ $money($order->sub_total) }}</td></tr>
                @if((float)$order->package_price > 0.009)<tr><td class="k">Package Price</td><td class="v">{{ $money($order->package_price) }}</td></tr>@endif
                <tr><td class="k">Rush Charges</td><td class="v">{{ $money($order->rush_charges) }}</td></tr>
                <tr class="g"><td>Total</td><td class="v">{{ $money($order->total) }}</td></tr>
            </table>
        </td>
    </tr></table>

    {{-- Footer --}}
    <div class="foot">
        <div class="ty">Thank you for your business</div>
        <div class="cs">If You Have Any Questions Or Require Further Assistance, Please Contact Our Customer Service Team<br>Between 8.00am And 7.00pm CST, Monday-Friday</div>
        <div class="contact">&#9742; 1800-396-1840, 630-364-3944 &nbsp;&nbsp; &#9742; 800-604-1874 &nbsp;&nbsp; &#9993; support@thecustomboxes.com &nbsp;&nbsp; www.thecustomboxes.com</div>
        <div class="pay" style="margin-top:10px">
            <span style="font-size:8px;color:#64748b;font-weight:bold">Payment Options :</span>
            @foreach($pays as $pf)@php $pi = $img('box_assets/img/'.$pf); @endphp @if($pi)<img src="{{ $pi }}">@endif @endforeach
        </div>
    </div>

</body>
</html>
