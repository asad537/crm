<?php

namespace App\Services\Auth;

use App\CrmUser;

/**
 * Legacy "password = mailbox password" check against the user's IMAP server.
 * Isolated so AuthController can be tested without a network, and so the fallback
 * can be switched off in one place (config crm.auth.imap_login_fallback).
 */
class MailboxPasswordVerifier
{
    public function verify(CrmUser $user, string $email, string $password): bool
    {
        if (!function_exists('imap_open') || $password === '') {
            return false;
        }
        $host = $user->imap_host ?: 'imap.hostinger.com';
        $port = (int) ($user->imap_port ?: 993);
        $enc = $user->imap_encryption ?: 'ssl';
        $mailbox = '{' . $host . ':' . $port . '/imap/' . $enc . '/novalidate-cert}INBOX';

        imap_timeout(IMAP_OPENTIMEOUT, 8);
        imap_errors();
        $conn = @imap_open($mailbox, $email, $password, OP_HALFOPEN, 1);
        imap_errors();
        if ($conn) {
            imap_close($conn);
            return true;
        }
        return false;
    }
}
