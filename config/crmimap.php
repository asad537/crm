<?php

/*
|--------------------------------------------------------------------------
| CRM IMAP (Sent-copy) settings
|--------------------------------------------------------------------------
| These are read at config:cache time from .env so that appendToImapSent()
| works even when config is cached (env() returns null at runtime under cache).
| Password falls back to the SMTP password (same Hostinger mailbox).
*/

return [
    'host'       => env('IMAP_HOST', 'imap.hostinger.com'),
    'port'       => env('IMAP_PORT', 993),
    'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
    'username'   => env('IMAP_USERNAME', env('MAIL_USERNAME')),
    'password'   => env('IMAP_PASSWORD', env('MAIL_PASSWORD')),
];
