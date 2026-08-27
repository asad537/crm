<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Order Has Been Shipped</title>

    <style>
        @media only screen and (max-width: 640px) {
            .email-container {
                width: 100% !important;
                border-radius: 0 !important;
            }

            .mobile-padding {
                padding-left: 22px !important;
                padding-right: 22px !important;
            }

            .tracking-number {
                font-size: 24px !important;
            }

            .brand-title {
                font-size: 24px !important;
            }
        }
    </style>
</head>

@php
    $lead = $order->lead;
    $clientName = $lead ? ($lead->client_name ?? 'Valued Customer') : 'Valued Customer';
    $primary = $isAlMassa ? '#f45a24' : '#8ccf35';
    $primaryLight = $isAlMassa ? '#fff0e8' : '#f2faeb';
    $pageBg = $isAlMassa ? '#f8f5ef' : '#eef5e8';
    $border = $isAlMassa ? '#f7c7b3' : '#dceccd';
    $dark = $isAlMassa ? '#171717' : '#222a2f';
    $muted = $isAlMassa ? '#766f68' : '#52605a';

    $carrierLabels = [
        'ltl_freight' => 'LTL Freight',
        'fedex'       => 'FedEx',
        'dhl'         => 'DHL',
        'usps'        => 'USPS',
        'ups'         => 'UPS',
    ];
@endphp

<body style="margin:0; padding:0; background:{{ $pageBg }}; font-family:Arial, Helvetica, sans-serif; color:{{ $dark }};">

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:{{ $pageBg }}; padding:36px 14px;">
    <tr>
        <td align="center">

            <table class="email-container" width="640" cellpadding="0" cellspacing="0" role="presentation" style="width:640px; max-width:640px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 12px 36px rgba(34,42,47,0.14);">

                <!-- Top Brand Strip -->
                <tr>
                    <td style="background:{{ $primary }}; padding:12px 34px;">
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td align="left" style="font-size:13px; font-weight:700; color:#ffffff;">
                                    {{ $supportEmail }}
                                </td>
                                <td align="right" style="font-size:13px; font-weight:700; color:#ffffff;">
                                    {{ $supportPhone }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Header -->
                <tr>
                    <td class="mobile-padding" style="padding:34px 42px 36px; background:{{ $primaryLight }}; border-bottom:1px solid {{ $border }};">

                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td align="left">
                                    <img src="{{ $logoUrl }}" alt="{{ $brandName }}" style="height:{{ $isAlMassa ? '78px' : '52px' }}; width:auto; max-width:210px; display:block;">
                                </td>
                                <td align="right">
                                    <span style="display:inline-block; background:{{ $primary }}; color:#ffffff; font-size:12px; font-weight:800; padding:8px 15px; border-radius:999px;">
                                        Order Shipped
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <div style="height:28px; line-height:28px;">&nbsp;</div>

                        <div style="font-size:42px; line-height:1; margin-bottom:14px;">
                            🚚
                        </div>

                        <h1 class="brand-title" style="margin:0; color:{{ $dark }}; font-size:30px; line-height:1.25; font-weight:900; letter-spacing:-0.5px;">
                            Your Order Is On Its Way
                        </h1>

                        <p style="margin:12px 0 0; color:{{ $muted }}; font-size:15px; line-height:1.65; max-width:500px;">
                            Great news! Your order has been shipped and is now heading toward your delivery address.
                        </p>

                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td class="mobile-padding" style="padding:38px 42px 18px;">

                        <p style="margin:0 0 8px; font-size:18px; font-weight:800; color:{{ $dark }};">
                            Dear {{ $clientName }},
                        </p>

                        <p style="margin:0 0 26px; font-size:15px; line-height:1.75; color:{{ $muted }};">
                            We're happy to let you know that your order has been dispatched. Please review your order and tracking details below.
                        </p>

                        <!-- Order Details Card -->
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:{{ $isAlMassa ? '#fffaf7' : '#fbfdf8' }}; border:1px solid {{ $border }}; border-radius:15px; overflow:hidden; margin-bottom:22px;">
                            <tr>
                                <td style="padding:22px 24px;">

                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                            <td style="padding-bottom:14px;">
                                                <div style="font-size:11px; font-weight:900; color:{{ $primary }}; letter-spacing:0.10em; text-transform:uppercase; margin-bottom:5px;">
                                                    Order Number
                                                </div>
                                                <div style="font-size:16px; font-weight:800; color:{{ $dark }};">
                                                    #{{ $order->id }}
                                                </div>
                                            </td>
                                        </tr>

                                        @if($lead && $lead->product_name)
                                        <tr>
                                            <td style="padding:14px 0; border-top:1px solid #e5efd9;">
                                                <div style="font-size:11px; font-weight:900; color:{{ $primary }}; letter-spacing:0.10em; text-transform:uppercase; margin-bottom:5px;">
                                                    Product
                                                </div>
                                                <div style="font-size:15px; font-weight:800; color:{{ $dark }};">
                                                    {{ $lead->product_name }}
                                                </div>
                                            </td>
                                        </tr>
                                        @endif

                                        @if($order->shipping_address)
                                        <tr>
                                            <td style="padding:14px 0; border-top:1px solid #e5efd9;">
                                                <div style="font-size:11px; font-weight:900; color:{{ $primary }}; letter-spacing:0.10em; text-transform:uppercase; margin-bottom:5px;">
                                                    Shipping Address
                                                </div>
                                                <div style="font-size:15px; line-height:1.6; font-weight:700; color:{{ $dark }};">
                                                    {{ $order->shipping_address }}
                                                </div>
                                            </td>
                                        </tr>
                                        @endif

                                        <tr>
                                            <td style="padding-top:14px; border-top:1px solid #e5efd9;">
                                                <div style="font-size:11px; font-weight:900; color:{{ $primary }}; letter-spacing:0.10em; text-transform:uppercase; margin-bottom:5px;">
                                                    Ship Date
                                                </div>
                                                <div style="font-size:15px; font-weight:800; color:{{ $dark }};">
                                                    {{ now()->format('M d, Y') }}
                                                </div>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>
                        </table>

                        <!-- Tracking Box -->
                        @if($order->tracking_number)
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:{{ $primaryLight }}; border:1px solid {{ $primary }}; border-radius:15px; overflow:hidden; margin-bottom:22px;">
                            <tr>
                                <td align="center" style="padding:28px 24px;">

                                    <div style="font-size:12px; font-weight:900; color:{{ $primary }}; letter-spacing:0.13em; text-transform:uppercase; margin-bottom:10px;">
                                        Tracking Number
                                    </div>

                                    <div class="tracking-number" style="font-size:31px; line-height:1.25; font-weight:900; color:{{ $dark }}; letter-spacing:0.04em; word-break:break-word;">
                                        {{ $order->tracking_number }}
                                    </div>

                                    @if($order->shipping_carrier)
                                    <div style="margin-top:15px;">
                                        <span style="display:inline-block; background:{{ $primary }}; color:#ffffff; padding:8px 20px; border-radius:999px; font-size:13px; font-weight:900;">
                                            {{ $carrierLabels[$order->shipping_carrier] ?? strtoupper($order->shipping_carrier) }}
                                        </span>
                                    </div>
                                    @endif

                                </td>
                            </tr>
                        </table>
                        @endif

                        <!-- Notes -->
                        @if($notes)
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#fff9ed; border:1px solid #f5d9a6; border-radius:13px; margin-bottom:22px;">
                            <tr>
                                <td style="padding:16px 18px; font-size:14px; line-height:1.6; color:#8a5a12;">
                                    <strong>Shipping Notes</strong><br>
                                    {{ $notes }}
                                </td>
                            </tr>
                        </table>
                        @endif

                        <!-- Receipt -->
                        @if($order->shipping_receipt_path)
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#effbe8; border:1px solid #bfe398; border-radius:13px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:15px 18px; font-size:14px; line-height:1.6; color:#34780f; font-weight:800;">
                                    ✅ Shipping receipt is attached to this email for your records.
                                </td>
                            </tr>
                        </table>
                        @endif

                        <p style="margin:0 0 26px; font-size:15px; line-height:1.75; color:{{ $muted }};">
                            If you have any questions about your shipment, our team will be happy to help.
                        </p>

                    </td>
                </tr>

                <!-- CTA Footer -->
                <tr>
                    <td class="mobile-padding" style="padding:0 42px 34px;">
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#222a2f; border-radius:15px;">
                            <tr>
                                <td align="center" style="padding:24px 22px;">
                                    <div style="font-size:16px; font-weight:900; color:#ffffff; margin-bottom:7px;">
                                        {{ $brandName }}
                                    </div>
                                    <div style="font-size:13px; line-height:1.6; color:#cbd5c0; margin-bottom:14px;">
                                        Custom boxes and packaging made for your business.
                                    </div>
                                    <a href="{{ $websiteUrl }}" style="display:inline-block; background:{{ $primary }}; color:#ffffff; text-decoration:none; padding:11px 22px; border-radius:8px; font-size:14px; font-weight:900;">
                                        Visit Website
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Bottom Footer -->
                <tr>
                    <td style="background:{{ $primaryLight }}; border-top:1px solid {{ $border }}; padding:22px 42px; text-align:center;">
                        <div style="font-size:12px; line-height:1.6; color:#7b8a75;">
                            This email was sent automatically. Please do not reply directly to this message.
                        </div>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
