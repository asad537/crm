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
            .hero-img { display:none !important; }
            .next-cell { display:block !important; width:100% !important; padding:10px 0 !important; }
        }
    </style>
</head>
@php
    $navy = '#13284d'; $navyDark = '#0e1f3d'; $gold = '#e0a82e'; $goldSoft = '#f6c552';
    $ink = '#17233b'; $muted = '#5b6475'; $border = '#e5e9f0'; $pageBg = '#eef1f7';
    $green = '#58b84a'; $greenSoft = '#eef8ec';
    $initial = strtoupper(substr(trim($agentName), 0, 1) ?: 'S');
    $boxIcon = '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L3 7v10l9 5 9-5V7l-9-5z" stroke="'.$gold.'" stroke-width="1.7" stroke-linejoin="round"/><path d="M3 7l9 5 9-5M12 12v10" stroke="'.$gold.'" stroke-width="1.7" stroke-linejoin="round"/></svg>';
@endphp
<body style="margin:0; padding:0; background:{{ $pageBg }}; font-family:'Segoe UI',Arial,Helvetica,sans-serif; color:{{ $ink }};">

<table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $pageBg }}; padding:34px 14px;">
<tr><td align="center">

<table class="email-container" width="640" cellpadding="0" cellspacing="0" style="width:640px; max-width:640px; background:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 10px 36px rgba(19,40,77,0.14);">

    <!-- Header -->
    <tr>
        <td style="background:{{ $navy }}; padding:17px 34px;">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="vertical-align:middle;">
                    <table cellpadding="0" cellspacing="0"><tr>
                        <td style="vertical-align:middle; padding-right:10px;">{!! $boxIcon !!}</td>
                        <td style="vertical-align:middle; font-size:19px; font-weight:800; color:#fff; letter-spacing:.2px;">Premium <span style="color:{{ $gold }};">Boxes</span></td>
                    </tr></table>
                </td>
                <td align="right" style="vertical-align:middle; font-size:12px; color:#c9d4e6;">
                    <span style="color:{{ $gold }};">✉</span> {{ $supportEmail }} &nbsp;&nbsp;|&nbsp;&nbsp; <span style="color:{{ $gold }};">✆</span> {{ $supportPhone }}
                </td>
            </tr></table>
        </td>
    </tr>

    <!-- Hero -->
    <tr>
        <td class="mobile-pad" style="padding:30px 40px 26px;">
            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                <td style="vertical-align:top; width:58%;">
                    <span style="display:inline-block; background:{{ $green }}; color:#fff; font-size:11px; font-weight:800; padding:6px 13px; border-radius:999px;">✓ Inquiry Received</span>
                    <h1 style="margin:18px 0 0; font-size:30px; font-weight:900; color:{{ $navy }}; line-height:1.15;">Welcome — we've got<br><span style="color:{{ $gold }}; border-bottom:3px solid {{ $goldSoft }}; padding-bottom:2px;">your request!</span></h1>
                    <p style="margin:16px 0 0; color:{{ $muted }}; font-size:14px; line-height:1.7;">Thank you for reaching out about your <strong style="color:{{ $ink }};">{{ $productName }}</strong>. You're in good hands.</p>
                </td>
                <td class="hero-img" align="right" style="vertical-align:middle; width:42%;">
                    @if(!empty($heroImage))
                        <img src="{{ $heroImage }}" alt="Custom box" width="220" style="width:220px; max-width:100%; border-radius:12px; display:block;">
                    @else
                        <div style="width:200px; height:150px; background:linear-gradient(135deg,{{ $navy }},{{ $navyDark }}); border-radius:14px; text-align:center; line-height:150px; font-size:44px; color:{{ $gold }}; display:inline-block;">📦</div>
                    @endif
                </td>
            </tr></table>
        </td>
    </tr>

    <!-- Greeting -->
    <tr>
        <td class="mobile-pad" style="padding:6px 40px 0;">
            <p style="margin:0 0 14px; font-size:19px; font-weight:800; color:{{ $navy }};">Hi {{ $clientName }},</p>
            <p style="margin:0 0 24px; font-size:14px; line-height:1.8; color:{{ $muted }};">
                I'm <strong style="color:{{ $ink }};">{{ $agentName }}</strong> from {{ $brandName }}, and I'll be looking after your custom packaging request personally. We've received your inquiry and our team is already putting together the details for your quote.
            </p>
        </td>
    </tr>

    <!-- What happens next -->
    <tr>
        <td class="mobile-pad" style="padding:0 40px;">
            <div style="text-align:center; margin-bottom:6px;">
                <span style="display:inline-block; background:{{ $navy }}; color:#fff; font-size:14px; font-weight:800; padding:11px 26px; border-radius:999px;">&#9873;&nbsp; What Happens Next?</span>
            </div>
            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fc; border:1px solid {{ $border }}; border-radius:14px; margin:14px 0 24px;">
                <tr>
                    <td class="next-cell" width="33%" style="vertical-align:top; padding:22px 16px; text-align:center;">
                        <div style="width:44px; height:44px; background:#e8f0ff; border-radius:11px; display:inline-block; line-height:44px; font-size:20px;">📄</div>
                        <div style="margin-top:10px; font-size:13px; line-height:1.5; color:{{ $ink }};">Our team prepares your <strong>custom quote</strong></div>
                    </td>
                    <td class="next-cell" width="33%" style="vertical-align:top; padding:22px 16px; text-align:center; border-left:1px solid {{ $border }}; border-right:1px solid {{ $border }};">
                        <div style="width:44px; height:44px; background:#fff4e0; border-radius:11px; display:inline-block; line-height:44px; font-size:20px;">✉️</div>
                        <div style="margin-top:10px; font-size:13px; line-height:1.5; color:{{ $ink }};">I'll email you the <strong>pricing &amp; options</strong> shortly</div>
                    </td>
                    <td class="next-cell" width="33%" style="vertical-align:top; padding:22px 16px; text-align:center;">
                        <div style="width:44px; height:44px; background:#efe8ff; border-radius:11px; display:inline-block; line-height:44px; font-size:20px;">🎨</div>
                        <div style="margin-top:10px; font-size:13px; line-height:1.5; color:{{ $ink }};"><strong>Free design support</strong> &amp; a mockup if you need one</div>
                    </td>
                </tr>
            </table>

            <!-- Reply callout -->
            <table width="100%" cellpadding="0" cellspacing="0" style="background:{{ $greenSoft }}; border-radius:13px; margin-bottom:24px;">
                <tr>
                    <td style="padding:18px 22px; vertical-align:middle; width:52px;"><div style="width:40px; height:40px; background:#dff2db; border-radius:50%; text-align:center; line-height:40px; font-size:19px;">🕐</div></td>
                    <td style="padding:18px 20px 18px 0; vertical-align:middle; font-size:14px; line-height:1.6; color:#2f4a2a;">Have artwork, sizes, or a deadline in mind? Just <strong>reply to this email</strong> — it comes straight to me and helps me get your quote spot-on.</td>
                </tr>
            </table>

            <!-- Signature -->
            <table width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $border }};">
                <tr><td style="padding-top:22px; padding-bottom:28px;">
                    <table cellpadding="0" cellspacing="0"><tr>
                        <td style="vertical-align:middle;"><div style="width:50px; height:50px; border-radius:50%; background:{{ $green }}; color:#fff; font-size:21px; font-weight:900; text-align:center; line-height:50px;">{{ $initial }}</div></td>
                        <td style="padding-left:14px; vertical-align:middle;">
                            <div style="font-size:16px; font-weight:900; color:{{ $navy }};">{{ $agentName }}</div>
                            <div style="font-size:12px; color:{{ $muted }};">Sales Specialist &nbsp;|&nbsp; {{ $brandName }}</div>
                            <div style="font-size:12px;"><a href="mailto:{{ $agentEmail }}" style="color:{{ $gold }}; font-weight:700; text-decoration:none;">{{ $agentEmail }}</a></div>
                        </td>
                    </tr></table>
                </td></tr>
            </table>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background:{{ $navy }}; padding:22px 40px; text-align:center;">
            <p style="margin:0; font-size:12px; color:#c9d4e6; line-height:1.7;">
                {{ $brandName }} &nbsp;·&nbsp; <a href="https://{{ $website }}" style="color:{{ $gold }}; text-decoration:none; font-weight:700;">{{ $website }}</a><br>
                <span style="color:#8ea0bf;">Premium custom boxes &amp; flexible packaging, made to order.</span>
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
