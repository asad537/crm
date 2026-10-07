<?php

namespace App\Services;

use App\CrmManualOrder;
use App\CrmPortalAccount;
use App\Mail\PortalCredentialsMail;
use Illuminate\Support\Facades\Mail;

/** Creates customer portal logins for manual orders and emails the credentials. */
class PortalAccountService
{
    /**
     * Make sure the order's customer has a portal account, optionally (re)sending login details.
     * Returns [account|null, sentEmail|null, errorMessage|null].
     */
    public function ensureForOrder(CrmManualOrder $order, bool $send, $agentUser = null, bool $forceNewPassword = false): array
    {
        $email = CrmPortalAccount::normalizeEmail($order->customerEmail());
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [null, null, 'No valid customer email on this order.'];
        }

        $account = CrmPortalAccount::where('workspace_id', $order->workspace_id)->where('email', $email)->first();
        $plain = null;
        if (!$account) {
            $account = new CrmPortalAccount([
                'workspace_id' => $order->workspace_id,
                'email' => $email,
                'name' => data_get($order->billing, 'name') ?: optional($order->customer)->name,
                'created_by' => $agentUser->id ?? null,
            ]);
            $plain = $account->resetPassword();
        } elseif ($send && ($forceNewPassword || !$account->credentials_sent_at)) {
            // First-time send, or an explicit "Send Login Details" resend: issue a fresh password.
            $plain = $account->resetPassword();
        }
        if (!$account->name && data_get($order->billing, 'name')) {
            $account->name = data_get($order->billing, 'name');
            $account->save();
        }

        if (!$send) {
            return [$account, null, null];
        }
        // $plain stays null for an existing customer who already has their password: the email then
        // tells them a new invoice is ready and to use their existing login (with a reset option).

        try {
            $cc = array_values(array_unique(array_filter(
                array_merge(\App\CrmUser::inWorkspace(null, ['admin'])->pluck('email')->toArray(), ['support@myboxprinting.com']),
                fn ($e) => $e && strcasecmp($e, $email) !== 0
            )));
            Mail::to($email)->cc($cc)->send(new PortalCredentialsMail($account, $order, $plain, $agentUser));
        } catch (\Throwable $e) {
            return [$account, null, 'Failed to email login details: ' . $e->getMessage()];
        }

        $account->credentials_sent_at = now();
        $account->save();
        return [$account, $email, null];
    }
}
