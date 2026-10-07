@php
    $b = $order->billing ?? [];
    $name = trim((string) ($account->name ?: ($b['name'] ?? ''))) ?: 'Customer';
    $amount = strtoupper($order->currency ?: 'USD') . ' ' . number_format((float) $order->total, 2);
    $logo = public_path($brand['logo']);
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Your invoice portal login</title></head>
<body style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;background:#f4f5f9;margin:0;padding:20px;color:#1e293b;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f5f9;padding:20px 0;"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
    <tr><td height="4" style="background:{{ $brand['color'] }};"></td></tr>
    <tr><td style="padding:26px 40px;border-bottom:1px solid #f1f5f9;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
            <td valign="top">@if(is_file($logo))<img src="{{ asset($brand['logo']) }}" alt="{{ $brand['name'] }}" height="46" style="display:block;">@else<div style="font-size:20px;font-weight:800;color:{{ $brand['color'] }};">{{ $brand['name'] }}</div>@endif</td>
            <td align="right" valign="top" style="text-align:right;">
                <div style="font-size:10px;font-weight:800;color:{{ $brand['color'] }};text-transform:uppercase;letter-spacing:.1em;">Invoice Portal</div>
                <div style="font-size:18px;font-weight:800;color:#0f172a;margin-top:2px;">Invoice {{ $label }}</div>
            </td>
        </tr></table>
    </td></tr>
    <tr><td style="padding:28px 40px;font-size:14px;line-height:1.7;color:#334155;">
        <p style="margin:0 0 14px;">Dear {{ $name }},</p>
        @if($plainPassword)
        <p style="margin:0 0 16px;">Thank you for your order with {{ $brand['name'] }}. We have created a secure customer portal where you can view your invoices, pay online by credit or debit card or PayPal, and download your invoice PDF at any time.</p>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 18px;">
            <tr><td style="padding:16px 18px;font-size:14px;line-height:1.9;">
                <div><span style="color:#64748b;display:inline-block;width:110px;">Portal link:</span> <a href="{{ $loginUrl }}" style="color:{{ $brand['color'] }};font-weight:700;">{{ $loginUrl }}</a></div>
                <div><span style="color:#64748b;display:inline-block;width:110px;">Email:</span> <strong>{{ $account->email }}</strong></div>
                <div><span style="color:#64748b;display:inline-block;width:110px;">Password:</span> <strong style="font-family:Menlo,Consolas,monospace;font-size:15px;letter-spacing:.04em;">{{ $plainPassword }}</strong></div>
            </td></tr>
        </table>
        @else
        <p style="margin:0 0 16px;">Thank you for your new order with {{ $brand['name'] }}. Invoice <strong>{{ $label }}</strong> is now available in your customer portal, where you can view it, pay online by credit or debit card or PayPal, and download the PDF.</p>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 18px;">
            <tr><td style="padding:16px 18px;font-size:14px;line-height:1.9;">
                <div><span style="color:#64748b;display:inline-block;width:110px;">Portal link:</span> <a href="{{ $loginUrl }}" style="color:{{ $brand['color'] }};font-weight:700;">{{ $loginUrl }}</a></div>
                <div><span style="color:#64748b;display:inline-block;width:110px;">Email:</span> <strong>{{ $account->email }}</strong></div>
                <div><span style="color:#64748b;display:inline-block;width:110px;">Password:</span> the password we sent you earlier. Forgot it? Use <em>"Email me new login details"</em> on the login page.</div>
            </td></tr>
        </table>
        @endif
        <table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;"><tr><td style="background:{{ $brand['color'] }};border-radius:8px;">
            <a href="{{ $loginUrl }}" style="display:inline-block;padding:12px 26px;color:#fff;font-weight:700;text-decoration:none;font-size:14px;">Log in &amp; pay invoice {{ $label }}</a>
        </td></tr></table>
        <p style="margin:0 0 6px;font-weight:700;color:#0f172a;">Invoice summary</p>
        <p style="margin:0 0 16px;">Invoice <strong>{{ $label }}</strong> &middot; Amount <strong>{{ $amount }}</strong>{{ $order->payment_term ? ' · '.$order->payment_term : '' }}</p>
        <p style="margin:0 0 14px;font-size:13px;color:#64748b;">Keep these details safe. You can use the same login for any future invoices from us. If you did not expect this email, simply ignore it or reply to let us know.</p>
        <p style="margin:0;">Best regards,<br><strong>{{ $agentUser->name ?? ($order->sales_person ?: 'Sales Team') }}</strong><br>{{ $brand['name'] }}</p>
    </td></tr>
    <tr><td style="padding:16px 40px;background:#f8fafc;border-top:1px solid #f1f5f9;font-size:11px;color:#94a3b8;text-align:center;line-height:1.6;">
        &#9742; {{ $brand['phones'] }} &nbsp;&nbsp; &#9993; {{ $brand['email'] }} &nbsp;&nbsp; {{ $brand['site'] }}
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
