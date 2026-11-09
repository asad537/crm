<?php

namespace App\Mail;

use App\CrmEmail;
use App\CrmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Welcome email sent to a client the moment a NEW lead lands (e.g. from the AI chat).
 * Sent from the brand's support mailbox but shown as coming from the sales agent (Sara),
 * with replies routed to the agent's own inbox — same pattern as ClientMessage.
 */
class LeadWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $lead;
    public $agent;
    public $clientName;
    public $productName;
    public $agentName;
    public $agentEmail;
    public $brandName;
    public $supportEmail;
    public $supportPhone;
    public $website;
    public $logoUrl;

    public function __construct(CrmEmail $lead, ?CrmUser $agent = null)
    {
        $this->lead        = $lead;
        $this->agent       = $agent;
        $this->clientName  = trim((string) $lead->client_name) ?: 'there';
        $this->productName = trim((string) $lead->product_name) ?: 'custom packaging';
        $this->agentName   = $agent->name ?? 'Sara';
        $this->agentEmail  = $agent->email ?? 'sara@myboxprinting.com';

        // Brand from the lead's source: premiumboxes chat vs mybox vs al massa.
        $src = strtolower((string) $lead->source);
        if (str_contains($src, 'almassa') || str_contains($src, 'al-massa')) {
            $this->brandName = 'Al Massa Packaging';
            $this->supportEmail = 'support@almassapackaging.com';
            $this->supportPhone = '1800-518-9441';
            $this->website = 'almassapackaging.com';
            $this->logoUrl = 'https://almassapackaging.com/images/img-home/al-massa-stamp.png';
        } else {
            $this->brandName = 'Premium Boxes';
            $this->supportEmail = 'support@myboxprinting.com';
            $this->supportPhone = '847-200-0974';
            $this->website = 'premiumboxes.com';
            $this->logoUrl = 'https://premiumboxes.com/assets/images/logo.png';
        }
    }

    public function build()
    {
        // When we deliver through the agent's OWN mailbox (Sara's SMTP), the FROM must be her
        // address or the provider rejects it; fall back to the brand support address otherwise.
        $fromAddress = $this->agentEmail ?: (config('mail.from.address') ?: 'support@myboxprinting.com');

        return $this
            ->from($fromAddress, $this->agentName . ' — ' . $this->brandName)
            ->replyTo($this->agentEmail ?: $fromAddress, $this->agentName)
            ->subject("Thanks for reaching out to {$this->brandName} — I'm here to help!")
            ->view('crm.emails.lead_welcome');
    }
}
