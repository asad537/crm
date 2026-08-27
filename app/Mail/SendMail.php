<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if ($this->data['subject'] == "contact_us") {
            return $this->from($this->data['email'])->subject('Contact US')->view('email/contact_us')->with('data', $this->data);
        } elseif ($this->data['subject'] == "order_email") {
            return $this->from($this->data['email'])->subject('New Order Email')->view('email/order_email')->with('data', $this->data);
        } elseif ($this->data['subject'] == "review_mail") {
            return $this->from("support@bookletbrochure.com")->subject('Send Review Email')->view('email/review_mail')->with('data', $this->data);
        } elseif ($this->data['subject'] == "create_account") {
            return $this->from("support@bookletbrochure.com")->subject('Welcome To Booklet Brochure')->view('email/create_account')->with('data', $this->data);
        } elseif ($this->data['subject'] == "order_cancel") {
            return $this->from("support@bookletbrochure.com")->subject('Cancellation Alert - My Box Printing')->view('email.order_cancel')->with('data', $this->data);
        }
    }
}
