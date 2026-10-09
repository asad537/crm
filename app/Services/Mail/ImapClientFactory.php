<?php

namespace App\Services\Mail;

use App\CrmMailAccount;

/** Builds the IMAP client for an account. Bound in the container so tests can substitute a fake. */
class ImapClientFactory
{
    public function make(CrmMailAccount $account): ImapClient
    {
        return new ImapClient($account);
    }
}
