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
            // Send from the ACTING AGENT'S own connected mailbox when they have one: the shared system
            // SMTP (support@myboxprinting.com) is frequently rejected, and sending through the agent's
            // box also mirrors the email into the lead's Conversation thread and marks it Responded
            // (via crm_email_id). Fall back to the framework mailer only if the agent has no usable box.
            $agentBox = $agentUser
                ? \App\CrmMailAccount::withoutGlobalScopes()->whereNull('deleted_at')
                    ->where('is_active', true)->where('crm_user_id', $agentUser->id)
                    ->orderByDesc('is_default')->orderByDesc('id')->first()
                : null;
            if ($agentBox) {
                $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
                $brand = \App\Mail\OrderCcaMail::brandFor($order);
                $subject = ($plain ? 'Your invoice portal login - Invoice ' : 'New invoice ready to pay - Invoice ')
                    . $label . ' - ' . $brand['name'];
                $html = (new PortalCredentialsMail($account, $order, $plain, $agentUser))->render();
                app(\App\Services\Mail\OutgoingMailService::class)->send($agentBox, [
                    'to' => $email,
                    'cc' => $cc,
                    'subject' => $subject,
                    'html' => $html,
                    'crm_email_id' => $order->crm_email_id,
                    'sent_by' => $agentUser,
                ]);
            } else {
                Mail::to($email)->cc($cc)->send(new PortalCredentialsMail($account, $order, $plain, $agentUser));
            }
        } catch (\Throwable $e) {
            return [$account, null, 'Failed to email login details: ' . $e->getMessage()];
        }

        $account->credentials_sent_at = now();
        $account->save();
        return [$account, $email, null];
    }
}
