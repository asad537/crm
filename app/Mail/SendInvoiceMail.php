<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $agentUser;
    public $generatedMessageId;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($order, $agentUser = null)
    {
        $this->order = $order;
        $this->agentUser = $agentUser;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'myboxprinting.com';
        $messageId = '<invoice-' . $this->order->id . '-' . uniqid('', true) . '@' . $domain . '>';
        
        $this->generatedMessageId = $messageId;

        $workspace = $this->order->workspace;
        $brandName = $workspace && $workspace->slug === 'mybox-packaging-app'
            ? 'Al Massa Packaging'
            : 'My Box Printing';
        $subject = 'Invoice #' . str_pad($this->order->id, 5, '0', STR_PAD_LEFT) . ' - ' . ($this->order->product_name ?: 'Custom Packaging Order') . ' - ' . $brandName;

        // Use config() (env is NULL when config is cached). Hostinger requires FROM = SMTP auth user.
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'support@myboxprinting.com';
        $fromName    = ($this->agentUser->name ?? null) ?: config('mail.from.name', 'My Box Printing');
        return $this
            ->from($fromAddress, $fromName)
            ->subject($subject)
            ->view('email.invoice_template')
            ->withSymfonyMessage(function ($message) use ($messageId) {
                // Symfony Mailer replacement for Swift's setId() (L10 uses Symfony Mailer)
                try {
                    $message->getHeaders()->addIdHeader('Message-ID', ltrim(rtrim($messageId, '>'), '<'));
                } catch (\Throwable $e) { /* ignore if header lib differs */ }
            })
            ->with([
                'order' => $this->order,
                'agentUser' => $this->agentUser,
            ]);
    }
}
