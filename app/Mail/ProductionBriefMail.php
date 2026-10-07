<?php

namespace App\Mail;

use App\CrmManualOrderProductionBrief;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Notifies the production team that a paid order has been sent to production (brief PDF attached). */
class ProductionBriefMail extends Mailable
{
    use Queueable, SerializesModels;

    public $brief;
    public $agentUser;

    public function __construct(CrmManualOrderProductionBrief $brief, $agentUser = null)
    {
        $this->brief = $brief;
        $this->agentUser = $agentUser;
    }

    public function build()
    {
        $brief = $this->brief->load('order');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.orders.production_pdf', ['brief' => $brief, 'order' => $brief->order])->setPaper('a4');
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'support@myboxprinting.com';
        $products = collect($brief->products ?: [])->pluck('product')->filter()->implode(', ');

        return $this
            ->from($fromAddress, ($this->agentUser->name ?? null) ?: 'CRM')
            ->subject('Production Job ' . $brief->job_number . ' - ' . ($brief->client_name ?: 'Client') . ($products ? ' - ' . $products : ''))
            ->html(view('email.production_brief', ['brief' => $brief, 'order' => $brief->order, 'agentUser' => $this->agentUser, 'products' => $products])->render())
            ->attachData($pdf->output(), 'Production-Job-' . preg_replace('/[^A-Za-z0-9_-]+/', '', $brief->job_number ?: $brief->id) . '.pdf', ['mime' => 'application/pdf']);
    }
}
