<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply from My Box Printing</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;padding:24px 18px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:760px;">
                <tr>
                    <td style="padding:0 0 18px 0;">
                        <div style="font-size:18px; font-weight:700; color:#111827; line-height:1.4;">
                            Hi {{ $inquiry->client_name ?: 'there' }},
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 0 20px 0;">
                        <div style="font-size:15px; color:#111827; line-height:1.8;">
                            {!! $messageBody !!}
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:4px 0 22px 0;">
                        <div style="font-size:14px; color:#111827; line-height:1.8;">
                            Please let us know if you need any further help.
                        </div>
                    </td>
                </tr>

                @if(!empty($signatureHtml))
                <tr>
                    <td style="padding:18px 0 0 0; border-top:1px solid #e5e7eb;">
                        <div style="font-size:14px; color:#111827; line-height:1.7;">
                            {!! $signatureHtml !!}
                        </div>
                    </td>
                </tr>
                @endif

                <tr>
                    <td style="padding:18px 0 0 0;">
                        <div style="font-size:11px; color:#6b7280; line-height:1.6;">
                            This email was sent in response to your inquiry. Please do not share this email with others.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
