<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome — {{ $brandName }}</title>
    <style>
        @media only screen and (max-width: 640px) {
            .email-container { width: 100% !important; border-radius: 0 !important; }
            .mobile-pad { padding-left: 22px !important; padding-right: 22px !important; }
        }
    </style>
</head>
@php
    $primary = '#7ec832'; $primaryDark = '#5aa520';
    $headerLight = '#f2faeb'; $pageBg = '#eef5e8'; $border = '#dceccd';
    $muted = '#52605a'; $dark = '#1a2414';
    $initial = strtoupper(substr(trim($agentName), 0, 1) ?: 'S');
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
        <td class="mobile-pad" style="padding:34px 40px 28px; background:{{ $headerLight }}; border-bottom:1px solid {{ $border }};">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="font-size:22px; font-weight:900; color:{{ $dark }}; letter-spacing:.3px;">{{ $brandName }}</td>
                <td align="right"><span style="background:{{ $primary }}; color:#fff; font-size:11px; font-weight:800; padding:7px 14px; border-radius:999px;">Inquiry Received</span></td>
            </tr></table>
            <div style="height:22px;">&nbsp;</div>
            <div style="font-size:34px; margin-bottom:10px;">👋</div>
            <h1 style="margin:0; font-size:25px; font-weight:900; color:{{ $dark }}; line-height:1.25;">Welcome — we've got your request!</h1>
            <p style="margin:10px 0 0; color:{{ $muted }}; font-size:14px; line-height:1.7;">Thank you for reaching out about your <strong style="color:{{ $primaryDark }};">{{ $productName }}</strong>. You're in good hands.</p>
        </td>
    </tr>

    <!-- Body -->
    <tr>
        <td class="mobile-pad" style="padding:32px 40px 10px;">
            <p style="margin:0 0 18px; font-size:16px; font-weight:800; color:{{ $dark }};">Hi {{ $clientName }},</p>
            <p style="margin:0 0 20px; font-size:14px; line-height:1.8; color:{{ $muted }};">
                I'm <strong style="color:{{ $dark }};">{{ $agentName }}</strong> from {{ $brandName }}, and I'll be looking after your custom packaging request personally. We've received your inquiry and our team is already putting together the details for your quote.
            </p>

            <!-- what's next card -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $headerLight }}; border:1px solid {{ $border }}; border-radius:13px; margin-bottom:24px;">
                <tr><td style="padding:20px 24px;">
                    <div style="font-size:12px; font-weight:800; letter-spacing:1px; color:{{ $primaryDark }}; text-transform:uppercase; margin-bottom:12px;">What happens next</div>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr><td style="font-size:14px; line-height:1.7; color:{{ $dark }}; padding:4px 0;">✅ &nbsp;Our team prepares your custom quote</td></tr>
                        <tr><td style="font-size:14px; line-height:1.7; color:{{ $dark }}; padding:4px 0;">✉️ &nbsp;I'll email you the pricing &amp; options shortly</td></tr>
                        <tr><td style="font-size:14px; line-height:1.7; color:{{ $dark }}; padding:4px 0;">🎨 &nbsp;Free design support &amp; a mockup if you need one</td></tr>
                    </table>
                </td></tr>
            </table>

            <p style="margin:0 0 24px; font-size:14px; line-height:1.8; color:{{ $muted }};">
                Have artwork, sizes, or a deadline in mind? Just <strong style="color:{{ $dark }};">reply to this email</strong> — it comes straight to me and helps me get your quote spot-on.
            </p>

            <!-- signature -->
            <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $border }}; margin-top:6px;">
                <tr><td style="padding-top:22px;">
                    <table cellpadding="0" cellspacing="0"><tr>
                        <td style="vertical-align:middle;">
                            <div style="width:48px; height:48px; border-radius:50%; background:{{ $primary }}; color:#fff; font-size:20px; font-weight:900; text-align:center; line-height:48px;">{{ $initial }}</div>
                        </td>
                        <td style="padding-left:14px; vertical-align:middle;">
                            <div style="font-size:15px; font-weight:900; color:{{ $dark }};">{{ $agentName }}</div>
                            <div style="font-size:12px; color:{{ $muted }};">Sales Specialist · {{ $brandName }}</div>
                            <div style="font-size:12px; color:{{ $primaryDark }}; font-weight:700;">{{ $agentEmail }}</div>
                        </td>
                    </tr></table>
                </td></tr>
            </table>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background:{{ $headerLight }}; padding:20px 40px; border-top:1px solid {{ $border }};">
            <p style="margin:0; font-size:12px; color:{{ $muted }}; line-height:1.6; text-align:center;">
                {{ $brandName }} · {{ $website }}<br>
                Premium custom boxes &amp; flexible packaging, made to order.
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
