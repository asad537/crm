<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Order — {{ $brandName }}</title>
    <style>
        @media only screen and (max-width: 640px) {
            .email-container { width: 100% !important; border-radius: 0 !important; }
            .mobile-pad { padding-left: 22px !important; padding-right: 22px !important; }
        }
    </style>
</head>
@php
    $primary = $isAlMassa ? '#f45a24' : '#7ec832';
    $primaryLight = $isAlMassa ? '#fff0e8' : '#f0f9e0';
    $headerLight = $isAlMassa ? '#fff7f2' : '#f2faeb';
    $pageBg = $isAlMassa ? '#f8f5ef' : '#eef5e8';
    $border = $isAlMassa ? '#f7c7b3' : '#dceccd';
    $muted = $isAlMassa ? '#766f68' : '#52605a';
    $dark = $isAlMassa ? '#171717' : '#1a2414';
@endphp
<body style="margin:0; padding:0; background:{{ $pageBg }}; font-family:Arial, Helvetica, sans-serif; color:{{ $dark }};">

<table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $pageBg }}; padding:36px 14px;">
<tr><td align="center">

<table class="email-container" width="620" cellpadding="0" cellspacing="0" style="width:620px; max-width:620px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 8px 32px rgba(26,36,20,0.12);">

    <!-- Top strip -->
    <tr>
        <td style="background:{{ $primary }}; padding:11px 34px;">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="font-size:13px; font-weight:700; color:#fff;">{{ $supportEmail }}</td>
                <td align="right" style="font-size:13px; font-weight:700; color:#fff;">{{ $supportPhone }}</td>
            </tr></table>
        </td>
    </tr>

    <!-- Header -->
    <tr>
        <td class="mobile-pad" style="padding:32px 40px 28px; background:{{ $headerLight }}; border-bottom:1px solid {{ $border }};">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="font-size:15px; font-weight:900; color:{{ $dark }};"><img src="{{ $logoUrl }}" alt="{{ $brandName }}" style="height:{{ $isAlMassa ? '78px' : '55px' }}; width:auto; max-width:210px; display:block;"></td>
                <td align="right"><span style="background:{{ $primary }}; color:#fff; font-size:11px; font-weight:800; padding:7px 14px; border-radius:999px;">Order Confirmed</span></td>
            </tr></table>
            <div style="height:26px;">&nbsp;</div>
            <div style="font-size:38px; margin-bottom:12px;">🎉</div>
            <h1 style="margin:0; font-size:26px; font-weight:900; color:{{ $dark }}; line-height:1.25;">Your Order Is Confirmed!</h1>
            <p style="margin:10px 0 0; color:{{ $muted }}; font-size:14px; line-height:1.7;">Great news! Your order has been placed successfully and our team has started processing it.</p>
        </td>
    </tr>

    <!-- Body -->
    <tr>
        <td class="mobile-pad" style="padding:32px 40px;">

            <p style="margin:0 0 22px; font-size:16px; font-weight:800; color:{{ $dark }};">Dear {{ $clientName }},</p>
            <p style="margin:0 0 24px; font-size:14px; line-height:1.7; color:{{ $muted }};">
                Thank you for choosing {{ $brandName }}! We have received your order and are getting started on it right away.
                @if($trackingAvailable)
                    You can monitor your order's progress in real-time from your personal tracking page.
                @else
                    Your sales representative will keep you updated as your order moves through production.
                @endif
            </p>

            <!-- Order Number -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $headerLight }}; border:1px solid {{ $border }}; border-radius:13px; margin-bottom:22px;">
                <tr><td style="padding:18px 22px;">
                    <div style="font-size:10px; font-weight:900; color:{{ $primary }}; letter-spacing:0.1em; text-transform:uppercase; margin-bottom:5px;">Your Order</div>
                    <div style="font-size:22px; font-weight:900; color:{{ $dark }};">#{{ $order->id }}</div>
                    <div style="font-size:13px; color:#6b7f62; margin-top:4px;">{{ now()->format('M d, Y') }}</div>
                </td></tr>
            </table>

            @if($trackingAvailable)
            <!-- Credentials are only included when the portal has a public URL. -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $primaryLight }}; border:2px solid {{ $primary }}; border-radius:13px; margin-bottom:22px;">
                <tr><td style="padding:20px 22px;">
                    <div style="font-size:13px; font-weight:900; color:{{ $primary }}; margin-bottom:14px; display:flex; align-items:center; gap:6px;">
                        🔐 Your Login Credentials
                    </div>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding-bottom:12px;">
                                <div style="font-size:10px; font-weight:800; color:#7aa83e; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:4px;">Email / Username</div>
                                <div style="background:#fff; border:1px solid #c3e6a0; border-radius:8px; padding:10px 14px; font-size:14px; font-weight:800; color:#1a2414; font-family:monospace;">{{ $clientEmail }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-size:10px; font-weight:800; color:#7aa83e; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:4px;">Password</div>
                                <div style="background:#fff; border:1px solid #c3e6a0; border-radius:8px; padding:10px 14px; font-size:14px; font-weight:800; color:#1a2414; font-family:monospace; letter-spacing:0.15em;">{{ $plainPassword }}</div>
                            </td>
                        </tr>
                    </table>
                </td></tr>
            </table>

            <!-- Tracking CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                    <td align="center" style="padding:20px 24px; background:#1a2414; border-radius:13px;">
                        <div style="font-size:13px; color:#c3e6a0; margin-bottom:14px; font-weight:600;">Click below to track your order and chat with your sales agent:</div>
                        <a href="{{ $trackingUrl }}" style="display:inline-block; background:{{ $primary }}; color:#fff; text-decoration:none; padding:14px 30px; border-radius:10px; font-size:15px; font-weight:900; letter-spacing:0.01em;">
                            🚀 Track My Order
                        </a>
                    </td>
                </tr>
            </table>

            <p style="margin:0 0 6px; font-size:13px; line-height:1.7; color:#6b7f62;">
                You can also copy and paste this link into your browser:
            </p>
            <div style="background:{{ $headerLight }}; border:1px solid {{ $border }}; border-radius:8px; padding:10px 14px; font-size:11px; color:{{ $primary }}; word-break:break-all; margin-bottom:24px;">
                {{ $trackingUrl }}
            </div>

            <p style="margin:0; font-size:13px; line-height:1.7; color:#6b7f62;">
                If you have any questions, feel free to reply to this email or use the live chat on your tracking page to speak directly with your sales agent.
            </p>
            @else
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;">
                <tr><td style="padding:18px 22px; background:{{ $primaryLight }}; border:1px solid {{ $border }}; border-radius:13px; color:{{ $muted }}; font-size:14px; line-height:1.7;">
                    For updates or questions about this order, simply reply to this email and our team will assist you.
                </td></tr>
            </table>
            <p style="margin:0; font-size:13px; line-height:1.7; color:#6b7f62;">
                We will send your secure tracking access separately when the customer portal is available online.
            </p>
            @endif

        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background:{{ $headerLight }}; border-top:1px solid {{ $border }}; padding:20px 40px; text-align:center;">
            <div style="font-size:15px; font-weight:900; color:{{ $dark }}; margin-bottom:4px;">{{ $brandName }}</div>
            <div style="font-size:12px; color:#7b8a75;">Custom boxes and packaging made for your business.</div>
            <div style="font-size:11px; color:#9ab08c; margin-top:10px;">This email was sent automatically. Please keep your password safe and do not share it.</div>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>
