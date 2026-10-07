@php
    $b = $order->billing ?? [];
    $amount = strtoupper($order->currency ?: 'USD') . ' ' . number_format((float) $order->total, 2);
    $name = trim((string) ($b['name'] ?? '')) ?: 'Customer';
    $logo = public_path($brand['logo']);
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Credit Card Authorization - {{ $label }}</title></head>
<body style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;background:#f4f5f9;margin:0;padding:20px;color:#1e293b;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f5f9;padding:20px 0;"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
    <tr><td height="4" style="background:{{ $brand['color'] }};"></td></tr>
    <tr><td style="padding:26px 40px;border-bottom:1px solid #f1f5f9;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
            <td valign="top">
                @if(is_file($logo))<img src="{{ asset($brand['logo']) }}" alt="{{ $brand['name'] }}" height="46" style="display:block;">@else<div style="font-size:20px;font-weight:800;color:{{ $brand['color'] }};">{{ $brand['name'] }}</div>@endif
            </td>
            <td align="right" valign="top" style="text-align:right;">
                <div style="font-size:10px;font-weight:800;color:{{ $brand['color'] }};text-transform:uppercase;letter-spacing:.1em;">Credit Card Authorization</div>
                <div style="font-size:18px;font-weight:800;color:#0f172a;margin-top:2px;">Invoice {{ $label }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:4px;">{{ optional($order->invoice_date)->format('F d, Y') ?: $order->created_at->format('F d, Y') }}</div>
            </td>
        </tr></table>
    </td></tr>
    <tr><td style="padding:28px 40px;font-size:14px;line-height:1.7;color:#334155;">
        <p style="margin:0 0 14px;">Dear {{ $name }},</p>
        <p style="margin:0 0 14px;">Thank you for your order with {{ $brand['name'] }}. To process the payment of <strong>{{ $amount }}</strong> for invoice <strong>{{ $label }}</strong> by credit card, please complete the attached <strong>Credit Card Authorization Form</strong>.</p>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 18px;">
            <tr><td style="padding:14px 18px;font-size:13px;line-height:1.8;">
                <div><span style="color:#64748b;">Invoice:</span> <strong>{{ $label }}</strong></div>
                <div><span style="color:#64748b;">Amount:</span> <strong>{{ $amount }}</strong></div>
                @if($order->enquiry_number)<div><span style="color:#64748b;">Enquiry #:</span> <strong>{{ $order->enquiry_number }}</strong></div>@endif
                @if($order->payment_term)<div><span style="color:#64748b;">Payment term:</span> <strong>{{ $order->payment_term }}</strong></div>@endif
            </td></tr>
        </table>
        <p style="margin:0 0 8px;font-weight:700;color:#0f172a;">How to complete it</p>
        <ol style="margin:0 0 18px;padding-left:20px;">
            <li>Print the attached form (or fill it digitally).</li>
            <li>Fill in your card details and billing address, then sign and date it.</li>
            <li>Reply to this email with the completed form, or send it to <a href="mailto:{{ $brand['email'] }}" style="color:{{ $brand['color'] }};">{{ $brand['email'] }}</a>.</li>
        </ol>
        <p style="margin:0 0 14px;">Your card will be charged only for the amount stated above. If you have any questions, simply reply to this email.</p>
        <p style="margin:0;">Best regards,<br><strong>{{ $agentUser->name ?? 'Sales Team' }}</strong><br>{{ $brand['name'] }}</p>
    </td></tr>
    <tr><td style="padding:16px 40px;background:#f8fafc;border-top:1px solid #f1f5f9;font-size:11px;color:#94a3b8;text-align:center;line-height:1.6;">
        &#9742; {{ $brand['phones'] }} &nbsp;&nbsp; &#9993; {{ $brand['email'] }} &nbsp;&nbsp; {{ $brand['site'] }}
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
