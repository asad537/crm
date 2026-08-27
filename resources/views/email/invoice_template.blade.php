@php
    $__mailWorkspace = $order->workspace;
    $__mailIsAlMassa = $__mailWorkspace && $__mailWorkspace->slug === 'mybox-packaging-app';
    $__mailBrand = $__mailIsAlMassa ? 'Al Massa Packaging' : 'My Box Printing';
    $__mailLogo = $__mailIsAlMassa ? 'al-massa-invoice-email-logo.png' : 'my-box-printing-logo.svg';
    $__mailPrimary = $__mailIsAlMassa ? '#f45a24' : '#6c5ce7';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</title>
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f5f9; margin: 0; padding: 20px; color: #1e293b;-webkit-font-smoothing: antialiased;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f5f9; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                    <!-- Top accent bar -->
                    <tr>
                        <td height="4" style="background:{{ $__mailIsAlMassa ? '#f45a24' : '#6c5ce7' }};"></td>
                    </tr>
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px 40px; border-bottom: 1px solid #f1f5f9;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="top">
                                        <img src="{{ asset($__mailLogo) }}" alt="{{ $__mailBrand }}" height="{{ $__mailIsAlMassa ? '72' : '50' }}" style="display:block;">
                                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px; font-weight: 500;">Custom Packaging</div>
                                    </td>
                                    <td align="right" valign="top" style="text-align: right;">
                                        <div style="font-size:10px;font-weight:800;color:{{ $__mailPrimary }};text-transform:uppercase;letter-spacing:.1em;margin-bottom:2px;">ORDER INVOICE</div>
                                        <div style="font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1;">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</div>
                                        <div style="font-size: 12px; color: #94a3b8; margin-top: 5px;">{{ \Carbon\Carbon::parse($order->order_marked_at ?: $order->created_at)->format('F d, Y') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Payment Status Banner -->
                    <tr>
                        <td style="padding: 12px 40px; background-color: {{ $order->payment_status === 'Paid' ? '#ecfdf5' : '#fef2f2' }}; border-bottom: 1px solid {{ $order->payment_status === 'Paid' ? '#dcfce7' : '#fee2e2' }};">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="font-size: 13px; font-weight: 700; color: {{ $order->payment_status === 'Paid' ? '#15803d' : '#b91c1c' }};">
                                        Payment Status: {{ $order->payment_status ?: 'Unpaid' }}
                                    </td>
                                    <td align="right" style="font-size: 12px; color: #64748b; text-align: right;">
                                        Agent: <strong>{{ $order->order_marked_by ?? 'Sales Team' }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Details Section -->
                    <tr>
                        <td style="padding: 30px 40px 10px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="50%" valign="top" style="padding-right: 20px;">
                                        <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">Customer Information</div>
                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">{{ $order->client_name }}</div>
                                        <div style="font-size: 12px; color: #475569; margin-bottom: 2px;">{{ $order->client_email }}</div>
                                        @if($order->client_phone)
                                            <div style="font-size: 12px; color: #475569;">{{ $order->client_phone }}</div>
                                        @endif
                                    </td>
                                    <td width="50%" valign="top" style="padding-left: 20px;">
                                        <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">Billing &amp; Shipping</div>
                                        @if($order->billing_address)
                                            <div style="font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 2px;">Billing:</div>
                                            <div style="font-size: 12px; color: #475569; margin-bottom: 8px; white-space: pre-line;">{!! nl2br(e($order->billing_address)) !!}</div>
                                        @endif
                                        @if($order->shipping_address)
                                            <div style="font-size: 11px; font-weight: 700; color: #334155; margin-bottom: 2px;">Shipping:</div>
                                            <div style="font-size: 12px; color: #475569; white-space: pre-line;">{!! nl2br(e($order->shipping_address)) !!}</div>
                                        @endif
                                        @if(!$order->billing_address && !$order->shipping_address)
                                            <div style="font-size: 12px; color: #94a3b8; font-style: italic;">No addresses specified.</div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Product Table -->
                    <tr>
                        <td style="padding: 20px 40px 10px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #faf9ff;">
                                        <th style="padding: 8px 10px; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1.5px solid #f1f5f9; text-align: left; width: 60%;">Description</th>
                                        <th style="padding: 8px 10px; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1.5px solid #f1f5f9; text-align: center;">Qty</th>
                                        <th style="padding: 8px 10px; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1.5px solid #f1f5f9; text-align: right;">Unit Price</th>
                                        <th style="padding: 8px 10px; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1.5px solid #f1f5f9; text-align: right;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 15px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top;">
                                            <div style="font-weight: 700; color: #0f172a; font-size: 13px; margin-bottom: 3px;">{{ $order->product_name ?: 'Custom Packaging Order' }}</div>
                                            <div style="font-size: 11px; color: #94a3b8; line-height: 1.4;">
                                                @if($order->length || $order->width || $order->height)
                                                    Dimensions: {{ $order->length }}&times;{{ $order->width }}&times;{{ $order->height }} {{ $order->unit }}
                                                @endif
                                                @if($order->stock) | Material: {{ $order->stock }} @endif
                                                @if($order->color) | Color: {{ $order->color }} @endif
                                                @if($order->coating) | Coating: {{ $order->coating }} @endif
                                            </div>
                                        </td>
                                        <td align="center" style="padding:15px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;font-weight:700;color:{{ $__mailPrimary }};vertical-align:top;">
                                            {{ number_format($order->order_quantity ?? 0) }}
                                        </td>
                                        <td align="right" style="padding: 15px 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #475569; vertical-align: top;">
                                            ${{ number_format($order->order_price ?? 0, 2) }}
                                        </td>
                                        <td align="right" style="padding: 15px 10px; border-bottom: 1px solid #f1f5f9; font-size: 13px; font-weight: 800; color: #0f172a; vertical-align: top;">
                                            ${{ number_format(($order->order_price ?? 0) * ($order->order_quantity ?? 0), 2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <!-- Totals and Notes -->
                    <tr>
                        <td style="padding: 10px 40px 30px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <!-- Left side: Notes -->
                                    <td width="55%" valign="top" style="padding-right: 20px;">
                                        @if($order->order_notes)
                                            <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 5px;">Invoice Notes</div>
                                            <div style="font-size: 12px; color: #475569; line-height: 1.5; background-color: #fafbff; padding: 10px; border-radius: 8px; border: 1px solid #f0f0f8;">
                                                {{ $order->order_notes }}
                                            </div>
                                        @endif
                                    </td>
                                    <!-- Right side: Totals -->
                                    <td width="45%" valign="top" align="right" style="text-align: right;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size: 12px; color: #64748b;">
                                            <tr>
                                                <td style="padding: 4px 0; text-align: left;">Subtotal</td>
                                                <td style="padding: 4px 0; text-align: right; font-weight: 700; color: #334155;">${{ number_format(($order->order_price ?? 0) * ($order->order_quantity ?? 0), 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 4px 0; text-align: left; border-bottom: 1px solid #f1f5f9;">Tax / VAT</td>
                                                <td style="padding: 4px 0; text-align: right; border-bottom: 1px solid #f1f5f9; color: #94a3b8;">—</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 10px 0; text-align: left; font-size: 14px; font-weight: 800; color: #0f172a;">Total Due</td>
                                                <td style="padding:10px 0;text-align:right;font-size:15px;font-weight:800;color:{{ $__mailPrimary }};">${{ number_format(($order->order_price ?? 0) * ($order->order_quantity ?? 0), 2) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Agent Signature -->
                    @if($__mailIsAlMassa)
                    <tr>
                        <td style="padding: 18px 40px; border-top: 1px solid #f1f5f9; background-color: #fbfbff;">
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding-right:16px;vertical-align:middle;"><img src="{{ asset('al-massa-invoice-email-logo.png') }}" alt="Al Massa Al Malakiya" style="width:105px;height:auto;display:block;"></td>
                                    <td style="border-left:3px solid #d69a00;padding-left:16px;vertical-align:middle;">
                                        <div style="font-size:16px;font-weight:800;color:#0b2a62;">{{ $agentUser && $agentUser->name ? $agentUser->name : 'Sales Team' }}</div>
                                        <div style="font-size:12px;color:#64748b;margin-top:3px;">{{ $agentUser ? $agentUser->getRoleLabel() : 'Sales & Support Executive' }}</div>
                                        <div style="font-size:12px;color:#0b2a62;margin-top:6px;">www.almassapackaging.com &nbsp; | &nbsp; +971 6 579 6994</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @elseif($agentUser && $agentUser->signature)
                    <tr>
                        <td style="padding: 15px 40px; border-top: 1px solid #f1f5f9; background-color: #fbfbff;">
                            <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">Regards</div>
                            <div style="font-size: 12px; color: #475569; line-height: 1.5;">
                                {!! $agentUser->signature !!}
                            </div>
                        </td>
                    </tr>
                    @endif

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 40px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 11px; color: #94a3b8; background-color: #fafbfc;">
                            <div>Thank you for choosing <strong>{{ $__mailBrand }}</strong>.</div>
                            <div style="margin-top: 4px;">This is an automated invoice document. Do not reply directly to this email address.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
