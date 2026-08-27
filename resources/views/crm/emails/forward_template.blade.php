<!DOCTYPE html>
@php
    // Email bodies can't use CSS var() (email clients strip them), so compute the
    // brand primary in PHP: orange for Al Massa, indigo otherwise.
    $__fwdIsAlMassa = optional($email->workspace ?? null)->slug === 'mybox-packaging-app';
    $__fwdPrimary = $__fwdIsAlMassa ? '#f45a24' : '#4f46e5';
@endphp
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f6f9fc; color: #525f7f; margin: 0; padding: 40px 0; }
        .wrapper { width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background-color: {{ $__fwdPrimary }}; padding: 24px 32px; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 600; }
        .header p { margin: 8px 0 0; opacity: 0.9; font-size: 14px; }
        .content { padding: 32px; }
        .section-title { font-size: 12px; font-weight: 700; color: #8898aa; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; }
        .info-row { margin-bottom: 16px; border-bottom: 1px solid #f1f3f5; padding-bottom: 16px; }
        .info-row:last-child { border-bottom: none; }
        .label { font-size: 14px; font-weight: 600; color: #32325d; display: inline-block; width: 140px; }
        .value { font-size: 14px; color: #525f7f; }
        .message-box { background-color: #f8fafc; border-left: 4px solid {{ $__fwdPrimary }}; padding: 20px; border-radius: 4px; color: #32325d; margin-top: 24px; line-height: 1.6; }
        .footer { padding: 24px; background-color: #f6f9fc; text-align: center; font-size: 12px; color: #8898aa; }
        a { color: {{ $__fwdPrimary }}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Forwarded Inquiry</h1>
            <p>You received a forwarded customer inquiry from the CRM.</p>
        </div>
        
        <div class="content">
            <div class="section-title">Client Information</div>
            <div class="info-row">
                <span class="label">Name:</span>
                <span class="value">{{ $email->client_name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Email:</span>
                <span class="value"><a href="mailto:{{ $email->client_email }}">{{ $email->client_email }}</a></span>
            </div>
            <div class="info-row">
                <span class="label">Phone:</span>
                <span class="value">{{ $email->client_phone ?: 'Not Provided' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Received On:</span>
                <span class="value">{{ $email->created_at->format('M d, Y h:i A') }}</span>
            </div>

            <div style="margin-top: 32px;"></div>
            <div class="section-title">Product Details</div>
            <div class="info-row">
                <span class="label">Product:</span>
                <span class="value"><strong>{{ $email->product_name ?: 'General Inquiry' }}</strong></span>
            </div>
             @if($email->quantity)
            <div class="info-row">
                <span class="label">Quantity:</span>
                <span class="value">{{ $email->quantity }} {{ $email->unit }}</span>
            </div>
            @endif
             @if($email->length)
            <div class="info-row">
                <span class="label">Dimensions:</span>
                <span class="value">{{ $email->length }} x {{ $email->width }} x {{ $email->height }}</span>
            </div>
            @endif
             @if($email->stock)
            <div class="info-row">
                <span class="label">Material:</span>
                <span class="value">{{ $email->stock }}</span>
            </div>
            @endif

            <div style="margin-top: 32px;"></div>
            <div class="section-title">Customer Message</div>
            <div class="message-box">
                {!! nl2br(e($email->message)) !!}
            </div>
            
            @if($email->file_url)
            <div style="margin-top: 24px; text-align: center;">
                <a href="{{ $email->file_url }}" style="display: inline-block; padding: 10px 20px; background-color: {{ $__fwdPrimary }}; color: white; border-radius: 6px; font-weight: 600;">View Attachment</a>
            </div>
            @endif
        </div>
        
        <div class="footer">
            <p>Sent via CRM System. Please do not reply directly to this automated notification.</p>
        </div>
    </div>
</body>
</html>
