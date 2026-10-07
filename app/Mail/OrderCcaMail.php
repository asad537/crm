<?php

namespace App\Mail;

use App\CrmManualOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Emails the customer a Credit Card Authorization (CCA) form for a TCB manual order,
 * attached as a PDF with the order details prefilled.
 */
class OrderCcaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $agentUser;
    public $brand;

    public function __construct(CrmManualOrder $order, $agentUser = null)
    {
        $this->order = $order;
        $this->agentUser = $agentUser;
        $this->brand = self::brandFor($order);
    }

    /** Brand block (name, site, support email, phones) picked from the order's website. */
    public static function brandFor(CrmManualOrder $order): array
    {
        $site = strtolower((string) $order->website);
        if (str_contains($site, 'myboxprinting')) {
            return [
                'name' => 'My Box Printing',
                'site' => 'www.myboxprinting.com',
                'email' => 'support@myboxprinting.com',
                'phones' => '1800-396-1840, 630-364-3944',
                'logo' => 'my-box-printing-logo-pdf.jpg',
                'color' => '#6c5ce7',
            ];
        }
        return [
            'name' => 'The Custom Boxes',
            'site' => 'www.thecustomboxes.com',
            'email' => 'support@thecustomboxes.com',
            'phones' => '1800-396-1840, 630-364-3944, 800-604-1874',
            'logo' => 'thecustomboxes-logo.png',
            'color' => '#376094',
        ];
    }

    public function build()
    {
        $order = $this->order;
        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
        $subject = 'Credit Card Authorization Form - Invoice ' . $label . ' - ' . $this->brand['name'];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.orders.cca_pdf', ['order' => $order, 'brand' => $this->brand])->setPaper('a4');

        // Hostinger requires FROM = SMTP auth user; config() is used because env() is null when config is cached.
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'support@myboxprinting.com';
        $fromName = ($this->agentUser->name ?? null) ?: $this->brand['name'];

        return $this
            ->from($fromAddress, $fromName)
            ->subject($subject)
            ->view('email.order_cca')
            ->with(['order' => $order, 'agentUser' => $this->agentUser, 'brand' => $this->brand, 'label' => $label])
            ->attachData($pdf->output(), 'CCA-Form-' . ($order->invoice_number ?: $order->id) . '.pdf', ['mime' => 'application/pdf']);
    }
}
