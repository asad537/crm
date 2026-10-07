<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $navy = $brand['color'] ?? '#376094';
    $cur = strtoupper($order->currency ?: 'USD');
    $b = $order->billing ?? [];
    $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
    $amount = $cur . ' ' . number_format((float) $order->total, 2);
    $img = function ($rel) {
        $p = public_path($rel);
        if (!is_file($p)) return null;
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
    };
    $logo = $img($brand['logo'] ?? '');
    $boxes = fn ($n = 16) => str_repeat('<span class="bx"></span>', $n);
@endphp
<style>
    @page { margin: 26px 30px; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2733; font-size: 9px; margin: 0; }
    table { border-collapse: collapse; }
    .head { width: 100%; }
    .head td { vertical-align: middle; }
    .title { color: {{ $navy }}; font-size: 17px; font-weight: bold; text-align: right; }
    .title .sub { display: block; color: #64748b; font-size: 8px; font-weight: normal; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 3px; }
    .bar { background: {{ $navy }}; color: #fff; font-weight: bold; font-size: 9px; padding: 5px 9px; margin-top: 12px; }
    .box { border: 1px solid #cfd6e2; border-top: none; padding: 8px 9px; }
    table.kv { width: 100%; }
    table.kv td { padding: 5px 6px; border: 1px solid #cfd6e2; font-size: 8.6px; vertical-align: middle; }
    table.kv td.k { background: #f3f6fa; font-weight: bold; color: #334155; width: 22%; }
    table.kv td.v { color: #0f172a; }
    .line { border-bottom: 1px solid #94a3b8; height: 14px; }
    .bx { display: inline-block; width: 13px; height: 15px; border: 1px solid #94a3b8; margin-right: 2px; vertical-align: middle; }
    .chk { display: inline-block; width: 9px; height: 9px; border: 1px solid #475569; margin-right: 4px; vertical-align: middle; }
    .amt { font-size: 14px; font-weight: bold; color: {{ $navy }}; }
    .terms { font-size: 7.8px; line-height: 1.55; color: #334155; }
    .terms p { margin: 0 0 5px; }
    table.sig { width: 100%; margin-top: 8px; }
    table.sig td { padding: 14px 8px 4px; font-size: 8px; color: #475569; vertical-align: bottom; }
    table.sig .l { border-bottom: 1px solid #1f2733; }
    .foot { text-align: center; margin-top: 16px; color: #475569; font-size: 7.8px; line-height: 1.6; }
    .foot .contact { color: {{ $navy }}; font-weight: bold; }
    .note { background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; padding: 6px 8px; font-size: 7.8px; margin-top: 10px; }
</style>
</head>
<body>
    <table class="head">
        <tr>
            <td style="width:55%">
                @if($logo)<img src="{{ $logo }}" style="height:46px">@else<span style="font-size:18px;font-weight:bold;color:{{ $navy }}">{{ $brand['name'] }}</span>@endif
            </td>
            <td class="title">CREDIT CARD AUTHORIZATION FORM<span class="sub">{{ $brand['name'] }} &middot; Invoice {{ $label }}</span></td>
        </tr>
    </table>

    <div class="bar">ORDER DETAILS</div>
    <div class="box">
        <table class="kv">
            <tr>
                <td class="k">Invoice #</td><td class="v">{{ $label }}</td>
                <td class="k">Invoice Date</td><td class="v">{{ optional($order->invoice_date)->format('d M Y') ?: $order->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="k">Enquiry #</td><td class="v">{{ $order->enquiry_number ?: '—' }}</td>
                <td class="k">Customer ID</td><td class="v">{{ $order->customer_id ?: '—' }}</td>
            </tr>
            <tr>
                <td class="k">Sales Person</td><td class="v">{{ $order->sales_person ?: ($order->user_name ?: '—') }}</td>
                <td class="k">Payment Term</td><td class="v">{{ $order->payment_term ?: '—' }}</td>
            </tr>
            <tr>
                <td class="k">Amount to Charge</td><td class="v amt" colspan="3">{{ $amount }}</td>
            </tr>
        </table>
    </div>

    <div class="bar">CUSTOMER / BILLING INFORMATION</div>
    <div class="box">
        <table class="kv">
            <tr><td class="k">Name</td><td class="v">{{ $b['name'] ?? '' }}</td><td class="k">Company</td><td class="v">{{ $b['company'] ?? '' }}</td></tr>
            <tr><td class="k">Email</td><td class="v">{{ $order->customerEmail() ?: '' }}</td><td class="k">Phone</td><td class="v">{{ $b['phone'] ?? '' }}</td></tr>
            <tr><td class="k">Street Address</td><td class="v" colspan="3">{{ $b['street'] ?? '' }}</td></tr>
            <tr>
                <td class="k">City</td><td class="v">{{ $b['city'] ?? '' }}</td>
                <td class="k">State / Zip</td><td class="v">{{ trim(($b['state'] ?? '') . ' ' . ($b['zip'] ?? '')) }}</td>
            </tr>
            <tr><td class="k">Country</td><td class="v" colspan="3">{{ $b['country'] ?? '' }}</td></tr>
        </table>
    </div>

    <div class="bar">CREDIT CARD INFORMATION (to be completed by the cardholder)</div>
    <div class="box">
        <table class="kv">
            <tr>
                <td class="k">Card Type</td>
                <td class="v" colspan="3"><span class="chk"></span> Visa &nbsp;&nbsp; <span class="chk"></span> MasterCard &nbsp;&nbsp; <span class="chk"></span> American Express &nbsp;&nbsp; <span class="chk"></span> Discover</td>
            </tr>
            <tr><td class="k">Cardholder Name</td><td class="v" colspan="3"><div class="line"></div></td></tr>
            <tr><td class="k">Card Number</td><td class="v" colspan="3">{!! $boxes(16) !!}</td></tr>
            <tr>
                <td class="k">Expiry (MM / YY)</td><td class="v">{!! $boxes(2) !!} &nbsp;/&nbsp; {!! $boxes(2) !!}</td>
                <td class="k">CVV / Security Code</td><td class="v">{!! $boxes(4) !!}</td>
            </tr>
            <tr><td class="k">Billing Address<br><span style="font-weight:normal;color:#64748b">(if different from above)</span></td><td class="v" colspan="3"><div class="line"></div><div class="line"></div></td></tr>
            <tr>
                <td class="k">City</td><td class="v"><div class="line"></div></td>
                <td class="k">State / Zip / Country</td><td class="v"><div class="line"></div></td>
            </tr>
        </table>
    </div>

    <div class="bar">AUTHORIZATION</div>
    <div class="box terms">
        <p>I, the undersigned cardholder, authorize <b>{{ $brand['name'] }}</b> to charge the credit card listed above for the amount of <b>{{ $amount }}</b> as payment for invoice <b>{{ $label }}</b>. I confirm that I am the authorized holder of this card and that the information provided is complete and accurate.</p>
        <p>I understand that this authorization is for the stated order only and that any additional charges (for example, approved changes to the order, rush charges or additional shipping) will require a separate written approval. The charge will appear on my statement as <b>{{ $brand['name'] }}</b>. Once the artwork is approved and production has started, the order is non-refundable as per {{ $brand['name'] }}&rsquo;s terms and conditions.</p>
        <p>By signing below I agree to the terms stated above and to {{ $brand['name'] }}&rsquo;s terms and conditions available at {{ $brand['site'] }}.</p>
        <table class="sig">
            <tr>
                <td class="l" style="width:46%">&nbsp;</td><td style="width:8%"></td><td class="l" style="width:22%">&nbsp;</td><td style="width:4%"></td><td class="l" style="width:20%">&nbsp;</td>
            </tr>
            <tr>
                <td style="padding-top:3px">Cardholder Signature</td><td></td><td style="padding-top:3px">Date</td><td></td><td style="padding-top:3px">Last 4 digits of card</td>
            </tr>
        </table>
        <div class="note">Please return the completed and signed form by replying to the email it was sent with, or to {{ $brand['email'] }}. For your security, do not send card details in the body of an email or by chat. Our team will securely destroy this form after processing.</div>
    </div>

    <div class="foot">
        If you have any questions, please contact our customer service team between 8.00am and 7.00pm CST, Monday to Friday.<br>
        <span class="contact">&#9742; {{ $brand['phones'] }} &nbsp;&nbsp; &#9993; {{ $brand['email'] }} &nbsp;&nbsp; {{ $brand['site'] }}</span>
    </div>
</body>
</html>
