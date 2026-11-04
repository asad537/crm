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

    'calls' => [
        /*
        | Team Chat calls go browser to browser. STUN lets two browsers find each other across networks;
        | a TURN server relays the call for the networks that block direct connections (some offices
        | and mobile networks). Without one, those calls ring but cannot connect.
        */
        'ice_servers' => array_values(array_filter([
            env('CRM_CALL_STUN_URL', 'stun:stun.l.google.com:19302') ? ['urls' => env('CRM_CALL_STUN_URL', 'stun:stun.l.google.com:19302')] : null,
            env('CRM_CALL_TURN_URL') ? ['urls' => env('CRM_CALL_TURN_URL'), 'username' => env('CRM_CALL_TURN_USERNAME'), 'credential' => env('CRM_CALL_TURN_CREDENTIAL')] : null,
        ])),
    ],
];
