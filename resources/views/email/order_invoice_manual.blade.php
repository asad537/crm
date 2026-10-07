@php
    $b = $order->billing ?? [];
    $cur = strtoupper($order->currency ?: 'USD');
    $money = fn ($v) => $cur . ' ' . number_format((float) $v, 2);
    $name = trim((string) ($b['name'] ?? '')) ?: 'Customer';
    $logo = public_path($brand['logo']);
    $paid = strtolower($order->invoice_status ?: '') === 'paid';
    $due = $order->balanceDue();
    $items = is_array($order->line_items) ? $order->line_items : [];
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Invoice {{ $label }}</title></head>
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
                <div style="font-size:10px;font-weight:800;color:{{ $brand['color'] }};text-transform:uppercase;letter-spacing:.1em;">Order Invoice</div>
                <div style="font-size:18px;font-weight:800;color:#0f172a;margin-top:2px;">{{ $label }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:4px;">{{ optional($order->invoice_date)->format('F d, Y') ?: $order->created_at->format('F d, Y') }}</div>
            </td>
        </tr></table>
    </td></tr>
    <tr><td style="padding:12px 40px;background:{{ $paid ? '#ecfdf5' : '#fef2f2' }};border-bottom:1px solid {{ $paid ? '#dcfce7' : '#fee2e2' }};">
        <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
            <td style="font-size:13px;font-weight:700;color:{{ $paid ? '#15803d' : '#b91c1c' }};">Payment Status: {{ $paid ? 'Paid' : 'Unpaid' }}</td>
            <td align="right" style="font-size:12px;color:#64748b;text-align:right;">Agent: <strong>{{ $agentUser->name ?? ($order->sales_person ?: 'Sales Team') }}</strong></td>
        </tr></table>
    </td></tr>
    <tr><td style="padding:26px 40px;font-size:14px;line-height:1.7;color:#334155;">
        <p style="margin:0 0 14px;">Dear {{ $name }},</p>
        <p style="margin:0 0 16px;">Thank you for your order with {{ $brand['name'] }}. Please find your invoice <strong>{{ $label }}</strong> attached as a PDF. A summary is below.</p>

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin:0 0 16px;font-size:13px;">
            <tr style="background:#f8fafc;">
                <th align="left" style="padding:9px 12px;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">Item</th>
                <th align="center" style="padding:9px 12px;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">Qty</th>
                <th align="right" style="padding:9px 12px;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:.05em;">Total</th>
            </tr>
            @forelse($items as $it)
                <tr>
                    <td style="padding:9px 12px;border-top:1px solid #f1f5f9;">
                        <strong>{{ $it['box_style'] ?? 'Custom Packaging' }}</strong>
                        @php $meta = array_filter([$it['stock'] ?? null, isset($it['length']) && ($it['length'] !== '') ? ($it['length'].' x '.($it['width'] ?? '').' x '.($it['height'] ?? '').' '.($it['unit'] ?? '')) : null, $it['color'] ?? null]); @endphp
                        @if($meta)<div style="font-size:11px;color:#94a3b8;">{{ implode(' · ', $meta) }}</div>@endif
                    </td>
                    <td align="center" style="padding:9px 12px;border-top:1px solid #f1f5f9;">{{ rtrim(rtrim(number_format((float)($it['qty'] ?? 0), 2, '.', ','), '0'), '.') }}</td>
                    <td align="right" style="padding:9px 12px;border-top:1px solid #f1f5f9;">{{ $money($it['line_total'] ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="padding:9px 12px;border-top:1px solid #f1f5f9;color:#94a3b8;">See attached invoice for details.</td></tr>
            @endforelse
            @if((float)$order->package_price > 0)<tr><td colspan="2" style="padding:7px 12px;border-top:1px solid #f1f5f9;color:#64748b;">Packaging</td><td align="right" style="padding:7px 12px;border-top:1px solid #f1f5f9;">{{ $money($order->package_price) }}</td></tr>@endif
            @if((float)$order->rush_charges > 0)<tr><td colspan="2" style="padding:7px 12px;border-top:1px solid #f1f5f9;color:#64748b;">Rush charges</td><td align="right" style="padding:7px 12px;border-top:1px solid #f1f5f9;">{{ $money($order->rush_charges) }}</td></tr>@endif
            @if((float)$order->discount > 0)<tr><td colspan="2" style="padding:7px 12px;border-top:1px solid #f1f5f9;color:#64748b;">Discount</td><td align="right" style="padding:7px 12px;border-top:1px solid #f1f5f9;color:#15803d;">- {{ $money($order->discount) }}</td></tr>@endif
            <tr style="background:{{ $brand['color'] }};color:#fff;">
                <td colspan="2" style="padding:11px 12px;font-weight:800;">Total</td>
                <td align="right" style="padding:11px 12px;font-weight:800;font-size:15px;">{{ $money($order->total) }}</td>
            </tr>
            @if(!$paid && $order->paidAmount() > 0)
                <tr><td colspan="2" style="padding:7px 12px;color:#64748b;">Paid</td><td align="right" style="padding:7px 12px;color:#15803d;">{{ $money($order->paidAmount()) }}</td></tr>
                <tr><td colspan="2" style="padding:7px 12px;font-weight:700;color:#b91c1c;">Balance due</td><td align="right" style="padding:7px 12px;font-weight:700;color:#b91c1c;">{{ $money($due) }}</td></tr>
            @endif
        </table>

        @if(!$paid)
            <p style="margin:0 0 8px;font-weight:700;color:#0f172a;">Payment</p>
            <p style="margin:0 0 16px;">Payment term: <strong>{{ $order->payment_term ?: '100% upfront' }}</strong>{{ $order->payment_term_via ? ' via '.$order->payment_term_via : '' }}. We accept PayPal, credit/debit card (via our Credit Card Authorization form) and wire transfer. Reply to this email and we will send the payment request of your choice.</p>
        @endif
        @if($order->additional_info)
            <p style="margin:0 0 16px;padding:10px 14px;background:#f8fafc;border-left:3px solid {{ $brand['color'] }};font-size:13px;color:#475569;">{{ $order->additional_info }}</p>
        @endif
        <p style="margin:0 0 14px;">If you have any questions about this invoice, simply reply to this email.</p>
        <p style="margin:0;">Best regards,<br><strong>{{ $agentUser->name ?? ($order->sales_person ?: 'Sales Team') }}</strong><br>{{ $brand['name'] }}</p>
    </td></tr>
    <tr><td style="padding:16px 40px;background:#f8fafc;border-top:1px solid #f1f5f9;font-size:11px;color:#94a3b8;text-align:center;line-height:1.6;">
        &#9742; {{ $brand['phones'] }} &nbsp;&nbsp; &#9993; {{ $brand['email'] }} &nbsp;&nbsp; {{ $brand['site'] }}
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
