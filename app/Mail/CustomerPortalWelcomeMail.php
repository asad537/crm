<?php

namespace App\Mail;

use App\SalesOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerPortalWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $clientName;
    public $clientEmail;
    public $plainPassword;
    public $trackingUrl;
    public $trackingAvailable;
    public $isAlMassa;
    public $brandName;
    public $supportEmail;
    public $supportPhone;
    public $logoUrl;

    public function __construct(SalesOrder $order, $clientName, $clientEmail, $plainPassword, $trackingUrl)
    {
        $this->order         = $order;
        $this->clientName    = $clientName;
        $this->clientEmail   = $clientEmail;
        $this->plainPassword = $plainPassword;
        $this->trackingUrl   = $trackingUrl;
        $trackingHost = strtolower((string) parse_url($trackingUrl, PHP_URL_HOST));
        $this->trackingAvailable = $trackingHost
            && !in_array($trackingHost, ['127.0.0.1', 'localhost', '::1'], true);

        $order->loadMissing('lead.workspace');
        $workspace = optional($order->lead)->workspace;
        $this->isAlMassa = $workspace && $workspace->slug === 'mybox-packaging-app';
        $this->brandName = $this->isAlMassa ? 'Al Massa Packaging' : 'MyBox Printing';
        $this->supportEmail = $this->isAlMassa ? 'support@almassapackaging.com' : 'support@myboxprinting.com';
        $this->supportPhone = $this->isAlMassa ? '1800-518-9441' : '847-200-0974';
        $this->logoUrl = $this->isAlMassa
            ? 'https://almassapackaging.com/images/img-home/al-massa-stamp.png'
            : url('my-box-printing-logo.svg');
    }

    public function build()
    {
        if (!$this->trackingAvailable) {
            return $this
                ->subject('Order #' . $this->order->id . ' Confirmed | ' . $this->brandName)
                ->text('crm.emails.customer_portal_welcome_plain');
        }

        return $this
            ->subject('Your Order Has Been Placed — Track It Online | ' . $this->brandName)
            ->view('crm.emails.customer_portal_welcome');
    }
}
