<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $inquiry;
    public $messageBody;
    public $agentUser;

    public $ccAddresses;
    public $bccAddresses;

    public $customSubject;
    public $signatureHtml;

    /**
     * @param  \App\CrmEmail  $inquiry
     * @param  string         $messageBody
     * @param  array          $attachmentPaths  Absolute file paths to attach
     * @param  \App\CrmUser   $agentUser
     * @param  array          $ccAddresses
     * @param  array          $bccAddresses
     * @param  string|null    $customSubject
     */
    public function __construct($inquiry, $messageBody, array $attachmentPaths = [], $agentUser = null, array $ccAddresses = [], array $bccAddresses = [], $customSubject = null)
    {
        $this->inquiry      = $inquiry;
        $this->messageBody  = $messageBody;
        $this->attachmentPaths = $attachmentPaths;
        $this->agentUser    = $agentUser;
        $this->ccAddresses  = $ccAddresses;
        $this->bccAddresses = $bccAddresses;
        $this->customSubject = $customSubject;
    }

    public function build()
    {
        // Generate a unique Message-ID for this outgoing email
        // so that when the client replies, their email client will include
        // this ID in the In-Reply-To / References header — which our IMAP
        // fetcher then uses to attach the reply to the correct CRM lead.
        $domain = parse_url(config('app.url'), PHP_URL_HOST);
        if (!$domain || $domain === 'localhost' || filter_var($domain, FILTER_VALIDATE_IP)) {
            // Match SMTP auth address to avoid Hostinger 553/554 rejects (config, not env — env is NULL when cached).
            $senderEmail  = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'support@myboxprinting.com';
            $senderDomain = $senderEmail && strpos($senderEmail, '@') !== false
                ? substr(strrchr($senderEmail, '@'), 1)
                : null;
            $domain = $senderDomain ?: 'myboxprinting.com';
        }
        $messageId = '<crm-' . uniqid('', true) . '@' . $domain . '>';

        // Store Message-ID on the mailable so EmailController can save it
        $this->generatedMessageId = $messageId;

        // The original lead's imap_message_id (or our own generated ID if absent)
        $inReplyTo  = $this->inquiry->imap_message_id ?: $messageId;
        $references = $this->inquiry->imap_message_id ?: $messageId;

        $mailSubject = $this->customSubject ?: ('Re: ' . ($this->inquiry->subject ?: 'Your Inquiry — My Box Printing'));

        $signatureHtml = $this->resolveSignatureHtml();
        $this->signatureHtml = $signatureHtml;

        // Use config() (not env — env is NULL when config is cached). Hostinger requires FROM = SMTP auth user.
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'support@myboxprinting.com';
        $fromName    = ($this->agentUser->name ?? null) ?: config('mail.from.name', 'My Box Printing');
        $mail = $this
            ->from($fromAddress, $fromName)
            ->subject($mailSubject)
            ->view('email.crm_client_message')
            ->withSymfonyMessage(function ($message) use ($messageId, $inReplyTo, $references) {
                try {
                    $headers = $message->getHeaders();
                    $headers->addIdHeader('Message-ID', ltrim(rtrim($messageId, '>'), '<'));
                    $headers->addTextHeader('In-Reply-To', $inReplyTo);
                    $headers->addTextHeader('References',  $references);
                } catch (\Throwable $e) { /* ignore if header lib differs */ }
            })
            ->with([
                'inquiry'     => $this->inquiry,
                'messageBody' => $this->messageBody,
                'agentUser'   => $this->agentUser,
                'signatureHtml' => $signatureHtml,
            ]);

        if (!empty($this->ccAddresses)) {
            $mail->cc($this->ccAddresses);
        }
        if (!empty($this->bccAddresses)) {
            $mail->bcc($this->bccAddresses);
        }

        // Attach any files
        foreach ($this->attachmentPaths as $path) {
            if (file_exists($path)) {
                $mail->attach($path);
            }
        }

        return $mail;
    }

    /**
     * Build a safe fallback signature block for outgoing CRM replies.
     */
    protected function resolveSignatureHtml()
    {
        $workspace = $this->inquiry->workspace;
        $isAlMassa = $workspace && $workspace->slug === 'mybox-packaging-app';
        $name = $this->agentUser && !empty($this->agentUser->name)
            ? e($this->agentUser->name)
            : 'Sales Team';

        $role = $this->agentUser && !empty($this->agentUser->role)
            ? e($this->agentUser->getRoleLabel())
            : 'Sales & Support Executive';

        $email = $this->agentUser && !empty($this->agentUser->email_user)
            ? e($this->agentUser->email_user)
            : ($isAlMassa ? 'support@almassapackaging.com' : 'support@myboxprinting.com');

        if ($isAlMassa) {
            return '
                <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; font-family:Arial,Helvetica,sans-serif; color:#1f2937; max-width:720px;">
                    <tr>
                        <td style="padding:0 0 2px 0; font-size:18px; font-weight:700; color:#2b4d8a; line-height:1.2;">
                            ' . $name . ' <span style="color:#7a7a7a; font-weight:700;">| ' . $role . '</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0; font-size:14px; font-weight:700; color:#2b4d8a; line-height:1.3;">
                            E: <a href="mailto:' . $email . '" style="color:#1260cc; text-decoration:underline;">' . $email . '</a>
                            <span style="color:#2b4d8a;"> | W: </span>
                            <a href="https://almassapackaging.com" style="color:#1260cc; text-decoration:underline;">www.almassapackaging.com</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0; font-size:14px; font-weight:700; color:#2b4d8a; line-height:1.3;">
                            P: +971 6 579 6994
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0 0 0;">
                            <img src="https://almassapackaging.com/images/img-home/al-massa-stamp.png" alt="Al Massa Al Malakiya" style="height:76px; width:auto; display:block;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0 0 0;">
                            <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:0 14px 0 0;"><img src="https://www.myboxprinting.com/uploads/email-signature-assets/wbenc.png" alt="WBENC" style="height:60px; width:auto; display:block;"></td>
                                    <td style="padding:0 14px 0 0;"><img src="https://www.myboxprinting.com/uploads/email-signature-assets/minority-owned.png" alt="Minority Owned" style="height:60px; width:auto; display:block;"></td>
                                    <td style="padding:0 14px 0 0;"><img src="https://www.myboxprinting.com/uploads/email-signature-assets/food-safety.png" alt="Food Safety" style="height:60px; width:auto; display:block;"></td>
                                    <td style="padding:0 14px 0 0;"><img src="https://www.myboxprinting.com/uploads/email-signature-assets/eco-friendly.png" alt="Eco Friendly" style="height:60px; width:auto; display:block;"></td>
                                    <td style="padding:0;"><img src="https://www.myboxprinting.com/uploads/email-signature-assets/fsc.png" alt="FSC" style="height:60px; width:auto; display:block;"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0 0 0; font-size:12px; color:#7b7b7b; line-height:1.35;">
                            <span style="font-style:italic; font-weight:700; color:#2b4d8a;">Please consider the environment before printing this e-mail.</span>
                            This communication (including any attachments) is intended for the use of the addressee only and may contain information that is privileged or confidential. If you are not the addressee, you are hereby notified that any disclosure, copying, distribution or taking any action on this communication is prohibited. If you received this communication in error, please destroy it, all copies and any attachments and notify the sender.
                        </td>
                    </tr>
                </table>
            ';
        }

        return '
            <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; font-family:Arial,Helvetica,sans-serif; color:#1f2937; max-width:720px;">
                <tr>
                    <td style="padding:0 0 2px 0; font-size:18px; font-weight:700; color:#2b4d8a; line-height:1.2;">
                        ' . $name . ' <span style="color:#7a7a7a; font-weight:700;">| ' . $role . '</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0; font-size:14px; font-weight:700; color:#2b4d8a; line-height:1.3;">
                        E: <a href="mailto:' . $email . '" style="color:#1260cc; text-decoration:underline;">' . $email . '</a>
                        <span style="color:#2b4d8a;"> | W: </span>
                        <a href="https://www.myboxprinting.com" style="color:#1260cc; text-decoration:underline;">www.myboxprinting.com</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0; font-size:14px; font-weight:700; color:#2b4d8a; line-height:1.3;">
                        P: 1847-200-0974
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 0 0 0;">
                        <img src="' . url('uploads/email-signature-assets/mybox-logo.png') . '" alt="My Box Printing" style="height:58px; display:block;">
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 0 0 0;">
                        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                            <tr>
                                <td style="padding:0 14px 0 0;"><img src="' . url('uploads/email-signature-assets/wbenc.png') . '" alt="WBENC" style="height:60px; width:auto; display:block;"></td>
                                <td style="padding:0 14px 0 0;"><img src="' . url('uploads/email-signature-assets/minority-owned.png') . '" alt="Minority Owned" style="height:60px; width:auto; display:block;"></td>
                                <td style="padding:0 14px 0 0;"><img src="' . url('uploads/email-signature-assets/food-safety.png') . '" alt="Food Safety" style="height:60px; width:auto; display:block;"></td>
                                <td style="padding:0 14px 0 0;"><img src="' . url('uploads/email-signature-assets/eco-friendly.png') . '" alt="Eco Friendly" style="height:60px; width:auto; display:block;"></td>
                                <td style="padding:0;"><img src="' . url('uploads/email-signature-assets/fsc.png') . '" alt="FSC" style="height:60px; width:auto; display:block;"></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 0 0 0; font-size:12px; color:#7b7b7b; line-height:1.35;">
                    <span style="font-style:italic; font-weight:700; color:#2b4d8a;">Please consider the environment before printing this e-mail.</span>
                    This communication (including any attachments) is intended for the use of the addressee only and may contain information that is privileged or confidential. If you are not the addressee, you are hereby notified that any disclosure, copying, distribution or taking any action on this communication is prohibited. If you received this communication in error, please destroy it, all copies and any attachments and notify the sender.
                    </td>
                </tr>
            </table>
        ';
    }
}
