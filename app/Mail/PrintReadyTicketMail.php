<?php

namespace App\Mail;

use App\CrmPrintReadyTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Print Ready ticket notifications: new ticket for designers, completion for admins. */
class PrintReadyTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $event; // created | completed | change_requested
    public $actor;

    public function __construct(CrmPrintReadyTicket $ticket, string $event, $actor = null)
    {
        $this->ticket = $ticket;
        $this->event = $event;
        $this->actor = $actor;
    }

    public function build()
    {
        $t = $this->ticket;
        $titles = [
            'created' => 'New Print Ready ticket',
            'completed' => 'Print Ready ticket completed',
            'change_requested' => 'Change requested on Print Ready ticket',
        ];
        $title = $titles[$this->event] ?? 'Print Ready ticket update';
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'support@myboxprinting.com';
        $products = collect($t->products ?: [])->pluck('product')->filter()->implode(', ');
        $url = route('crm.print_ready.show', $t->id);

        $html = view('email.print_ready_ticket', [
            'ticket' => $t, 'title' => $title, 'event' => $this->event, 'actor' => $this->actor, 'products' => $products, 'url' => $url,
        ])->render();

        return $this->from($fromAddress, ($this->actor->name ?? null) ?: 'CRM')
            ->subject($title . ' - ' . $t->ticket_number . ' - ' . ($t->client_name ?: 'Client'))
            ->html($html);
    }
}
