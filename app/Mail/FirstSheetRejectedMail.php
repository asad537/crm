<?php

namespace App\Mail;

use App\ProductionJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FirstSheetRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $job;
    public $notes;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(ProductionJob $job, $notes)
    {
        $this->job = $job;
        $this->notes = $notes;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Production Job #' . $this->job->id . ' - First Sheet Rejected')
                    ->view('crm.emails.first_sheet_rejected');
    }
}
