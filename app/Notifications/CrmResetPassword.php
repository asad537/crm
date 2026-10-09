<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Password-reset email for CRM users (points at the crm.* reset route, not the default web one). */
class CrmResetPassword extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('crm.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $minutes = (int) config('auth.passwords.crm.expire', 60);
        return (new MailMessage)
            ->subject('Reset your CRM password')
            ->greeting('Hello ' . ($notifiable->name ?: '') . ',')
            ->line('We received a request to reset the password for your CRM account.')
            ->action('Reset Password', $url)
            ->line("This link expires in {$minutes} minutes. If you did not request a reset, you can ignore this email.");
    }
}
