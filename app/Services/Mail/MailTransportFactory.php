<?php

namespace App\Services\Mail;

use App\CrmMailAccount;
use Symfony\Component\Mailer\Transport\TransportInterface;

/** Builds the SMTP transport for an account. Bound in the container so tests can substitute a fake. */
class MailTransportFactory
{
    public function for(CrmMailAccount $account): TransportInterface
    {
        return $account->smtpTransport();
    }
}
