<?php

namespace App\Mail;

use App\SalesOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $notes;
    public $isAlMassa;
    public $brandName;
    public $supportEmail;
    public $supportPhone;
    public $logoUrl;
    public $websiteUrl;

    public function __construct(SalesOrder $order, $notes = null)
    {
        $this->order = $order;
        $this->notes = $notes;

        $order->loadMissing('lead.workspace');
        $workspace = optional($order->lead)->workspace;
        $this->isAlMassa = $workspace && $workspace->slug === 'mybox-packaging-app';
        $this->brandName = $this->isAlMassa ? 'Al Massa Packaging' : 'MyBox Printing';
        $this->supportEmail = $this->isAlMassa ? 'support@almassapackaging.com' : 'support@myboxprinting.com';
        $this->supportPhone = $this->isAlMassa ? '1800-518-9441' : '847-200-0974';
        $this->logoUrl = url($this->isAlMassa ? 'al-massa-invoice-email-logo.png' : 'my-box-printing-logo.svg');
        $this->websiteUrl = $this->isAlMassa ? 'https://almassapackaging.com' : 'https://myboxprinting.com';
    }

    public function build()
    {
        $mail = $this
            ->subject('Your Order Has Been Shipped! - Order #' . $this->order->id . ' | ' . $this->brandName)
            ->view('crm.emails.order_shipped');

        // Attach receipt if available
        if ($this->order->shipping_receipt_path) {
            $fullPath = storage_path('app/public/' . $this->order->shipping_receipt_path);
            if (file_exists($fullPath)) {
                $mail->attach($fullPath, [
                    'as'   => 'shipping_receipt.' . pathinfo($fullPath, PATHINFO_EXTENSION),
                    'mime' => mime_content_type($fullPath),
                ]);
            }
        }

        return $mail;
    }
}
