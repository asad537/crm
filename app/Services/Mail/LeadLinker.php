<?php

namespace App\Services\Mail;

use App\CrmEmail;
use App\CrmMailAccount;
use App\CrmMailMessage;
use App\CrmMessage;

/**
 * Connects a synced mailbox message to a CRM lead (crm_emails) and, for inbound
 * client replies, mirrors it into the legacy crm_messages thread so the existing
 * lead workflow (status "Client Replied", unread counts, follow-ups) keeps working.
 */
class LeadLinker
{
    private const PROTECTED_STATUSES = ['Qualified Lead', 'Order Done', 'Closed', 'Rejected'];

    /** Returns the linked lead id (or null). Mutates $message->crm_email_id. */
    public function link(CrmMailAccount $account, CrmMailMessage $message, array $parsed): ?int
    {
        $refs = MailThreader::referenceIds($parsed['in_reply_to'] ?? null, $parsed['references'] ?? null);
        $leadQuery = fn () => CrmEmail::withoutGlobalScopes()->when($account->workspace_id, fn ($q) => $q->where('workspace_id', $account->workspace_id));
        $lead = null;

        // A) Our own sent mail (CRM-sent replies carry their Message-ID in crm_messages).
        if ($message->is_outgoing && !empty($parsed['message_id'])) {
            $sent = CrmMessage::withoutGlobalScopes()->where('message_id', $parsed['message_id'])->first();
            if ($sent) {
                $lead = $leadQuery()->find($sent->crm_email_id);
            }
        }

        // B) Header match against the original lead or a CRM-sent reply.
        if (!$lead && $refs) {
            $lead = $leadQuery()->whereIn('imap_message_id', $refs)->first();
            if (!$lead) {
                $sent = CrmMessage::withoutGlobalScopes()->whereIn('message_id', $refs)->whereIn('sender_type', ['admin', 'agent'])->orderByDesc('id')->first();
                if ($sent) {
                    $lead = $leadQuery()->find($sent->crm_email_id);
                }
            }
        }

        // C) Inherit from the thread (any earlier message already linked).
        if (!$lead && $message->thread_id) {
            $linked = CrmMailMessage::withoutGlobalScopes()->where('thread_id', $message->thread_id)
                ->whereNotNull('crm_email_id')->where('id', '!=', $message->id ?? 0)->orderByDesc('received_at')->first();
            if ($linked) {
                $lead = $leadQuery()->find($linked->crm_email_id);
            }
        }

        // D) Sender address matches a lead that belongs to this mailbox's owner (new thread from a known client).
        if (!$lead && !$message->is_outgoing && !empty($parsed['from_email'])) {
            $lead = $leadQuery()->where('client_email', $parsed['from_email'])
                ->where(function ($q) use ($account) {
                    $q->where('assigned_to', $account->crm_user_id)->orWhere('mail_account_id', $account->id);
                })
                ->orderByDesc('updated_at')->first();
        }

        if (!$lead) {
            return null;
        }

        $message->crm_email_id = $lead->id;
        if (!$lead->mail_account_id) {
            $lead->forceFill(['mail_account_id' => $account->id])->saveQuietly();
        }
        return $lead->id;
    }

    /**
     * Mirror an inbound client message into crm_messages (legacy lead thread), once.
     * Call after the mail message has been saved and linked.
     */
    public function mirrorInbound(CrmMailAccount $account, CrmMailMessage $message, array $parsed): bool
    {
        if ($message->is_outgoing || !$message->crm_email_id) {
            return false;
        }
        if (!empty($parsed['message_id']) && CrmMessage::withoutGlobalScopes()->where('message_id', $parsed['message_id'])->exists()) {
            return false; // already imported by the legacy fetcher or an earlier sync
        }
        if (self::looksAutomated($parsed)) {
            return false; // bounces / auto-replies / newsletters never become lead replies
        }
        $lead = CrmEmail::withoutGlobalScopes()->find($message->crm_email_id);
        if (!$lead) {
            return false;
        }

        $text = (string) ($parsed['text'] ?? '');
        if (trim($text) === '' && !empty($parsed['html'])) {
            $text = html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>|</div>#i', "\n", $parsed['html'])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $body = self::stripQuotedReply(trim($text)) ?: '(no body)';

        CrmMessage::create([
            'crm_email_id' => $lead->id,
            'message_id' => $parsed['message_id'] ?? null,
            'sender_type' => 'client',
            'message_body' => $body,
            'is_read' => false,
        ]);
        if (!in_array($lead->status, self::PROTECTED_STATUSES, true)) {
            $lead->forceFill(['status' => 'Client Replied'])->saveQuietly();
        }
        $lead->touch();
        return true;
    }

    /** Lightweight port of the legacy 3-layer system/bounce filter (headers → sender → subject). */
    public static function looksAutomated(array $parsed): bool
    {
        $h = (string) ($parsed['raw_headers'] ?? '');
        if (preg_match('/^Auto-Submitted:\s*(auto-generated|auto-replied|auto-notified)/im', $h)) return true;
        if (preg_match('/^Precedence:\s*(bulk|list|junk|auto_reply)/im', $h)) return true;
        if (preg_match('/^X-Auto-Response-Suppress:/im', $h) || preg_match('/^X-Autoreply:\s*yes/im', $h) || preg_match('/^X-Autorespond:/im', $h)) return true;
        if (preg_match('/^List-(Unsubscribe|Id|Post|Help|Archive):/im', $h)) return true;

        $local = strtolower(explode('@', (string) ($parsed['from_email'] ?? ''))[0] ?? '');
        foreach (['mailer-daemon', 'postmaster', 'noreply', 'no-reply', 'donotreply', 'do-not-reply', 'bounce', 'mailerdaemon', 'automailer', 'newsletter'] as $b) {
            if ($local !== '' && str_contains($local, $b)) return true;
        }
        $subject = strtolower((string) ($parsed['subject'] ?? ''));
        foreach (['undelivered mail', 'undeliverable', 'delivery status', 'delivery failure', 'mail delivery', 'returned mail', 'failure notice', 'automatic reply', 'auto-reply', 'autoreply', 'out of office'] as $s) {
            if ($subject !== '' && str_contains($subject, $s)) return true;
        }
        return false;
    }

    /** Remove quoted history ("On … wrote:", "> …") from a plain-text reply. */
    public static function stripQuotedReply(string $body): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $body);
        $out = [];
        $n = count($lines);
        for ($i = 0; $i < $n; $i++) {
            $t = trim($lines[$i]);
            if (preg_match('/^On .{10,} wrote:\s*$/i', $t)) break;
            if (preg_match('/^On .{6,}/i', $t) && isset($lines[$i + 1]) && preg_match('/^wrote:\s*$/i', trim($lines[$i + 1]))) break;
            if (preg_match('/^(-{3,}\s*Original Message\s*-{3,}|From:\s.+|_{10,})$/i', $t) && $i > 0) break;
            if (str_starts_with($t, '>')) break;
            $out[] = $lines[$i];
        }
        return trim(implode("\n", $out));
    }
}
