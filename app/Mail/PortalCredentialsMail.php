<?php

namespace App\Mail;

use App\CrmManualOrder;
use App\CrmPortalAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Sends the customer their invoice-portal login details. */
class PortalCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $account;
    public $order;
    public $plainPassword;
    public $agentUser;
    public $brand;

    public function __construct(CrmPortalAccount $account, CrmManualOrder $order, ?string $plainPassword, $agentUser = null)
    {
        $this->account = $account;
        $this->order = $order;
        $this->plainPassword = $plainPassword;
        $this->agentUser = $agentUser;
        $this->brand = OrderCcaMail::brandFor($order);
    }

    public function build()
    {
        $label = $this->order->invoice_number ? 'TCB-' . $this->order->invoice_number : '#' . $this->order->id;
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'support@myboxprinting.com';
        $fromName = ($this->agentUser->name ?? null) ?: $this->brand['name'];

        return $this
            ->from($fromAddress, $fromName)
            ->subject(($this->plainPassword ? 'Your invoice portal login - Invoice ' : 'New invoice ready to pay - Invoice ') . $label . ' - ' . $this->brand['name'])
            ->view('email.portal_credentials')
            ->with([
                'account' => $this->account,
                'order' => $this->order,
                'plainPassword' => $this->plainPassword,
                'agentUser' => $this->agentUser,
                'brand' => $this->brand,
                'label' => $label,
                'loginUrl' => route('invoice_portal.login'),
            ]);
    }
}
