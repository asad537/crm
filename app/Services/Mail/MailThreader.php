<?php

namespace App\Services\Mail;

use App\CrmMailAccount;
use App\CrmMailMessage;
use App\CrmMailThread;

/**
 * Header-based conversation threading (Message-ID / In-Reply-To / References).
 * Subject is only used to label a thread, never to group messages.
 */
class MailThreader
{
    /** Split In-Reply-To + References into clean message-ids (keeps angle brackets). */
    public static function referenceIds(?string $inReplyTo, ?string $references): array
    {
        $raw = trim(($inReplyTo ?? '') . ' ' . ($references ?? ''));
        if ($raw === '') return [];
        preg_match_all('/<[^<>\s]+>/', $raw, $m);
        $ids = $m[0] ?: array_filter(array_map('trim', preg_split('/\s+/', $raw)));
        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Find the thread this message belongs to (or create one) and return it.
     * $parsed is the ImapClient::fetchMessage() array.
     */
    public function assign(CrmMailAccount $account, CrmMailMessage $message, array $parsed): CrmMailThread
    {
        $refs = self::referenceIds($parsed['in_reply_to'] ?? null, $parsed['references'] ?? null);
        $thread = null;

        // 1. A message we already hold that this one replies to / references.
        if ($refs) {
            $parent = CrmMailMessage::withTrashed()->withoutGlobalScopes()
                ->where('account_id', $account->id)
                ->whereIn('message_id', $refs)
                ->whereNotNull('thread_id')
                ->orderByDesc('received_at')
                ->first();
            if ($parent) {
                $thread = $parent->thread()->withoutGlobalScopes()->first();
            }
        }

        // 2. Out-of-order arrival: a later message already references THIS message-id.
        if (!$thread && !empty($parsed['message_id'])) {
            $child = CrmMailMessage::withTrashed()->withoutGlobalScopes()
                ->where('account_id', $account->id)
                ->whereNotNull('thread_id')
                ->where(function ($q) use ($parsed) {
                    $q->where('in_reply_to', $parsed['message_id'])
                      ->orWhere('references_header', 'like', '%' . $parsed['message_id'] . '%');
                })
                ->first();
            if ($child) {
                $thread = $child->thread()->withoutGlobalScopes()->first();
            }
        }

        if (!$thread) {
            $thread = CrmMailThread::create([
                'account_id' => $account->id,
                'subject_norm' => mb_substr(CrmMailMessage::normalizeSubject($parsed['subject'] ?? ''), 0, 250),
                'root_message_id' => $parsed['message_id'] ?? null,
                'last_message_at' => $parsed['date'] ?? now(),
                'message_count' => 0,
                'unread_count' => 0,
            ]);
        }

        $message->thread_id = $thread->id;
        return $thread;
    }

    /** Recompute counters for a thread. */
    public function refresh(CrmMailThread $thread): void
    {
        $base = CrmMailMessage::withoutGlobalScopes()->where('thread_id', $thread->id);
        $thread->forceFill([
            'message_count' => (clone $base)->count(),
            'unread_count' => (clone $base)->where('is_read', false)->where('is_outgoing', false)->count(),
            'last_message_at' => (clone $base)->max('received_at') ?: $thread->last_message_at,
        ])->save();
    }
}
