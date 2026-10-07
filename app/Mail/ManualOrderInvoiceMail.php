<?php

namespace App\Mail;

use App\CrmManualOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Emails the customer the invoice PDF for a TCB manual order. */
class ManualOrderInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $agentUser;
    public $brand;

    public function __construct(CrmManualOrder $order, $agentUser = null)
    {
        $this->order = $order;
        $this->agentUser = $agentUser;
        $this->brand = OrderCcaMail::brandFor($order);
    }

    public function build()
    {
        $order = $this->order;
        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
        $subject = 'Invoice ' . $label . ' - ' . $this->brand['name'];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.orders.pdf', ['order' => $order])->setPaper('a4');

        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'support@myboxprinting.com';
        $fromName = ($this->agentUser->name ?? null) ?: $this->brand['name'];

        return $this
            ->from($fromAddress, $fromName)
            ->subject($subject)
            ->view('email.order_invoice_manual')
            ->with(['order' => $order, 'agentUser' => $this->agentUser, 'brand' => $this->brand, 'label' => $label])
            ->attachData($pdf->output(), 'Invoice-' . ($order->invoice_number ?: $order->id) . '.pdf', ['mime' => 'application/pdf']);
    }
}
