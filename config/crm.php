<?php

return [
    'auth' => [
        /*
        | Login step B. Local bcrypt is always checked first. When it fails and the user's
        | workspace roles are all sales/sales_manager, the legacy mailbox (IMAP) check may run
        | as a FALLBACK so nobody is locked out while hashes catch up. Each fallback success
        | re-hashes the password locally and is recorded in crm_users.imap_login_fallback_at.
        | Run `php artisan crm:auth-audit`; once no one has needed the fallback for a while,
        | set CRM_IMAP_LOGIN_FALLBACK=false to complete the migration to local authentication.
        */
        'imap_login_fallback' => env('CRM_IMAP_LOGIN_FALLBACK', true),
    ],
];
