<?php

namespace App\Services\Mail;

use App\CrmMailAccount;
use App\CrmMailAttachment;
use App\CrmMailFolder;
use App\CrmMailMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Per-account IMAP synchronisation into crm_mail_* tables.
 *
 *  - one account at a time (Cache::lock), safe to run every minute
 *  - folders discovered + typed; per-folder UIDVALIDITY + last_uid cursor
 *  - first sync pulls the last N days (capped), later syncs only UIDs > cursor
 *  - duplicates prevented by (account, folder, uid) and (account, message_id)
 *  - bodies sanitised, attachments on the private "local" disk
 *  - never marks mail as read on the server (FT_PEEK)
 *  - messages are threaded and linked to CRM leads; inbound client replies are
 *    mirrored into crm_messages so the legacy lead workflow keeps working
 */
class ImapSyncService
{
    public const DISK = 'local';
    /** Heartbeat lock TTL: refreshed after every folder and every 25 messages, so a killed worker blocks an account for at most this long. */
    public const LOCK_SECONDS = 60;
    private ?string $lockKey = null;
    private ?string $lockToken = null;

    public function __construct(
        private HtmlSanitizerService $sanitizer,
        private MailThreader $threader,
        private LeadLinker $linker,
        private ImapClientFactory $clients,
    ) {
    }

    /**
     * @param array{since_days?:int,cap?:int,folders?:array|null,types?:array|null} $opts
     * @return array{status:string,folders:int,imported:int,skipped:int,linked:int,mirrored:int,errors:array}
     */
    public function syncAccount(CrmMailAccount $account, array $opts = []): array
    {
        $stats = ['status' => 'ok', 'folders' => 0, 'imported' => 0, 'skipped' => 0, 'linked' => 0, 'mirrored' => 0, 'errors' => []];
        if (self::inBackoff($account->id)) {
            $stats['status'] = 'backoff';
            return $stats;
        }
        $this->lockKey = 'mail:sync:' . $account->id;
        $this->lockToken = Str::random(16);
        if (!Cache::add($this->lockKey, $this->lockToken, self::LOCK_SECONDS)) {
            $stats['status'] = 'locked';
            return $stats;
        }

        $client = $this->clients->make($account);
        try {
            $folders = $this->syncFolders($account, $client);
            $stats['folders'] = count($folders);

            foreach ($folders as $folder) {
                if (!$this->shouldSyncFolder($folder, $opts)) {
                    continue;
                }
                $this->touchLock();
                try {
                    $r = $this->syncFolder($account, $folder, $client, $opts);
                    $stats['imported'] += $r['imported'];
                    $stats['skipped'] += $r['skipped'];
                    $stats['linked'] += $r['linked'];
                    $stats['mirrored'] += $r['mirrored'];
                } catch (\Throwable $e) {
                    $stats['errors'][] = $folder->path . ': ' . $e->getMessage();
                    Log::warning('Mail sync folder failed', ['account_id' => $account->id, 'folder' => $folder->path, 'error' => $e->getMessage()]);
                }
            }

            $this->resolveOrphans($account);
            foreach ($folders as $folder) { $this->refreshFolderCounts($folder->fresh() ?: $folder); }

            $account->forceFill([
                'last_synced_at' => now(),
                'last_sync_error' => $stats['errors'] ? Str::limit(implode(' | ', $stats['errors']), 500) : null,
            ])->saveQuietly();
        } catch (\Throwable $e) {
            $stats['status'] = 'error';
            $stats['errors'][] = $e->getMessage();
            $account->forceFill(['last_sync_error' => Str::limit($e->getMessage(), 500)])->saveQuietly();
            Log::warning('Mail sync failed', ['account_id' => $account->id, 'error' => $e->getMessage()]);
            // Back off so a dead password / unreachable host is not retried every few seconds
            // (repeated failed logins get the server IP throttled or blocked).
            $auth = stripos($e->getMessage(), 'login') !== false || stripos($e->getMessage(), 'password') !== false;
            Cache::put(self::backoffKey($account->id), now()->toIso8601String(), $auth ? 600 : 120);
        } finally {
            $client->close();
            $this->releaseLock();
        }

        return $stats;
    }

    /** Extend the per-account lock while long work is in progress. */
    private function touchLock(): void
    {
        if ($this->lockKey && Cache::get($this->lockKey) === $this->lockToken) {
            Cache::put($this->lockKey, $this->lockToken, self::LOCK_SECONDS);
        }
    }

    private function releaseLock(): void
    {
        if ($this->lockKey && Cache::get($this->lockKey) === $this->lockToken) {
            Cache::forget($this->lockKey);
        }
        $this->lockKey = $this->lockToken = null;
    }

    /** @return CrmMailFolder[] */
    private function syncFolders(CrmMailAccount $account, ImapClient $client): array
    {
        $remote = $client->listFolders();
        $out = [];
        $seenInbox = false;
        foreach ($remote as $f) {
            if (!$f['selectable']) continue;
            $folder = CrmMailFolder::withoutGlobalScopes()->firstOrNew(['account_id' => $account->id, 'path' => $f['path']]);
            $folder->name = $f['name'];
            // keep a manual override if an admin ever reclassified a folder; otherwise take the detected type
            if (!$folder->exists || $folder->type === 'custom' || $folder->type === $f['type']) {
                $folder->type = $f['type'];
            }
            $folder->save();
            if (strtoupper($f['path']) === 'INBOX') $seenInbox = true;
            $out[] = $folder;
        }
        if (!$seenInbox) {
            $folder = CrmMailFolder::withoutGlobalScopes()->firstOrCreate(['account_id' => $account->id, 'path' => 'INBOX'], ['name' => 'Inbox', 'type' => 'inbox']);
            $out[] = $folder;
        }
        return $out;
    }

    private function shouldSyncFolder(CrmMailFolder $folder, array $opts): bool
    {
        if (!empty($opts['folders'])) return in_array($folder->path, $opts['folders'], true);
        if (!empty($opts['types'])) return in_array($folder->type, $opts['types'], true);
        return true;
    }

    /** @return array{imported:int,skipped:int,linked:int,mirrored:int} */
    private function syncFolder(CrmMailAccount $account, CrmMailFolder $folder, ImapClient $client, array $opts): array
    {
        $r = ['imported' => 0, 'skipped' => 0, 'linked' => 0, 'mirrored' => 0];
        $status = $client->status($folder->path);

        // Cheap idle path: if the server STATUS (uidvalidity/uidnext/messages/unseen) is unchanged
        // since the last pass, nothing arrived, moved or was read — skip the search/overview work.
        // A full pass (search + overview) still runs at least every 10 minutes so star-only changes
        // made in another client are picked up; everything else is caught by STATUS changes.
        $sigKey = 'mail:folder:' . $folder->id . ':status';
        $sig = implode('|', [$status['uidvalidity'], $status['uidnext'], $status['messages'], $status['unseen']]);
        $last = Cache::get($sigKey);
        if (is_array($last) && $last['sig'] === $sig && (time() - (int) $last['at']) < 600) {
            return $r;
        }

        // UIDVALIDITY changed → UIDs are meaningless now; restart the cursor (message_id dedupe prevents dupes).
        if ($status['uidvalidity'] && $folder->uidvalidity && $status['uidvalidity'] !== (int) $folder->uidvalidity) {
            Log::info('Mail sync: UIDVALIDITY changed, resetting cursor', ['account_id' => $account->id, 'folder' => $folder->path]);
            $folder->last_uid = 0;
        }
        $folder->uidvalidity = $status['uidvalidity'] ?: $folder->uidvalidity;

        $sinceDays = (int) ($opts['since_days'] ?? (in_array($folder->type, ['junk', 'trash'], true) ? 7 : 30));
        $cap = (int) ($opts['cap'] ?? 200);
        $uids = $client->searchUids($folder->path, (int) $folder->last_uid, $sinceDays, $cap);

        $maxUid = (int) $folder->last_uid;
        $n = 0;
        foreach ($uids as $uid) {
            $maxUid = max($maxUid, $uid);
            if (++$n % 25 === 0) $this->touchLock();
            try {
                $exists = CrmMailMessage::withTrashed()->withoutGlobalScopes()
                    ->where('account_id', $account->id)->where('folder_id', $folder->id)->where('uid', $uid)->exists();
                if ($exists) { $r['skipped']++; continue; }

                $parsed = $client->fetchMessage($folder->path, $uid);

                if (!empty($parsed['message_id'])) {
                    $dupe = CrmMailMessage::withTrashed()->withoutGlobalScopes()
                        ->where('account_id', $account->id)->where('message_id', $parsed['message_id'])->first();
                    if ($dupe) {
                        // Same Message-ID already stored: either a copy we recorded locally (sent mail,
                        // uid null) or the message was MOVED on the server (Outlook/webmail) and now shows up
                        // here. Re-point the row instead of duplicating it. Gmail's "All Mail" (archive) also
                        // lists inbox/sent mail — never let it steal the pointer from those.
                        $dupeFolderType = optional($dupe->folder()->withoutGlobalScopes()->first())->type;
                        $gmailAllMail = $folder->type === 'archive' && in_array($dupeFolderType, ['inbox', 'sent'], true) && $dupe->uid !== null;
                        if (!$gmailAllMail && ($dupe->uid === null || (int) $dupe->folder_id !== (int) $folder->id)) {
                            $dupe->forceFill(['folder_id' => $folder->id, 'uid' => $uid, 'deleted_at' => null])->saveQuietly();
                        }
                        $r['skipped']++;
                        continue;
                    }
                }

                [$stored, $mirrored] = $this->storeMessage($account, $folder, $parsed);
                $r['imported']++;
                if ($stored->crm_email_id) $r['linked']++;
                if ($mirrored) $r['mirrored']++;
            } catch (\Throwable $e) {
                // keep going; this UID will NOT be retried automatically (cursor advances) — log it clearly
                Log::warning('Mail sync: message failed', ['account_id' => $account->id, 'folder' => $folder->path, 'uid' => $uid, 'error' => $e->getMessage()]);
                $r['skipped']++;
            }
        }

        $folder->last_uid = $maxUid;
        $this->reconcileFolder($account, $folder, $client);
        $this->refreshFolderCounts($folder);
        Cache::put($sigKey, ['sig' => $sig, 'at' => time()], 3600);
        return $r;
    }

    /**
     * Server → CRM: refresh read/star flags (server wins) and detect messages that vanished from
     * this folder (moved or deleted in another mail client). Vanished rows get uid=null; if the
     * same Message-ID is found in another folder during this run the pointer moves there,
     * otherwise resolveOrphans() files them under Trash.
     */
    private function reconcileFolder(CrmMailAccount $account, CrmMailFolder $folder, ImapClient $client): void
    {
        if ((int) $folder->message_count > 5000) {
            return; // keep the per-minute cost bounded for huge folders
        }
        try {
            $overview = $client->overview($folder->path);
        } catch (\Throwable $e) {
            Log::info('Mail reconcile skipped', ['account_id' => $account->id, 'folder' => $folder->path, 'error' => $e->getMessage()]);
            return;
        }
        CrmMailMessage::withoutGlobalScopes()->where('folder_id', $folder->id)->whereNotNull('uid')
            ->select(['id', 'uid', 'is_read', 'is_starred', 'is_outgoing'])
            ->chunkById(500, function ($rows) use ($overview) {
                foreach ($rows as $row) {
                    $o = $overview[(int) $row->uid] ?? null;
                    if ($o === null) {
                        $row->forceFill(['uid' => null])->saveQuietly(); // gone from this folder on the server
                        continue;
                    }
                    $changes = [];
                    if (!$row->is_outgoing && (bool) $row->is_read !== $o['seen']) $changes['is_read'] = $o['seen'];
                    if ((bool) $row->is_starred !== $o['flagged']) $changes['is_starred'] = $o['flagged'];
                    if ($changes) $row->forceFill($changes)->saveQuietly();
                }
            });
    }

    /** Rows that vanished from the server and were not re-found elsewhere → local Trash (never outgoing/draft rows, never very recent ones). */
    public function resolveOrphans(CrmMailAccount $account): int
    {
        $trash = CrmMailFolder::withoutGlobalScopes()->where('account_id', $account->id)->where('type', 'trash')->first();
        if (!$trash) return 0;
        $moved = 0;
        CrmMailMessage::withoutGlobalScopes()->where('account_id', $account->id)->whereNull('uid')
            ->where('is_outgoing', false)->where('is_draft', false)
            ->where('folder_id', '!=', $trash->id)
            ->where('created_at', '<', now()->subHour())
            ->chunkById(200, function ($rows) use ($trash, &$moved) {
                foreach ($rows as $row) { $row->forceFill(['folder_id' => $trash->id])->saveQuietly(); $moved++; }
            });
        return $moved;
    }

    /** @return array{0:CrmMailMessage,1:bool} message + whether it was mirrored into crm_messages */
    private function storeMessage(CrmMailAccount $account, CrmMailFolder $folder, array $parsed): array
    {
        $ownAddress = strtolower((string) $account->email_address);
        $isOutgoing = $folder->type === 'sent'
            || (!empty($parsed['from_email']) && strtolower($parsed['from_email']) === $ownAddress);

        $html = $this->sanitizer->sanitize($parsed['html'] ?? null);
        $text = $parsed['text'] ?? null;
        if (($text === null || trim($text) === '') && $html) {
            $text = trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>|</div>|</tr>#i', "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        $date = $parsed['date'] ?: now();

        return DB::transaction(function () use ($account, $folder, $parsed, $isOutgoing, $html, $text, $date) {
            $message = new CrmMailMessage([
                'account_id' => $account->id,
                'folder_id' => $folder->id,
                'uid' => $parsed['uid'],
                'message_id' => $parsed['message_id'] ? Str::limit($parsed['message_id'], 250, '') : null,
                'in_reply_to' => $parsed['in_reply_to'] ? Str::limit($parsed['in_reply_to'], 250, '') : null,
                'references_header' => $parsed['references'] ?: null,
                'subject' => $parsed['subject'],
                'from_name' => $parsed['from_name'] ? Str::limit($parsed['from_name'], 250, '') : null,
                'from_email' => $parsed['from_email'] ? Str::limit($parsed['from_email'], 250, '') : null,
                'to_json' => $parsed['to'] ?: null,
                'cc_json' => $parsed['cc'] ?: null,
                'bcc_json' => $parsed['bcc'] ?: null,
                'reply_to' => $parsed['reply_to'],
                'text_body' => $text,
                'html_body' => $html,
                'snippet' => HtmlSanitizerService::snippet($text, $html),
                'has_attachments' => collect($parsed['attachments'])->contains(fn ($a) => empty($a['inline'])),
                'is_read' => $isOutgoing ? true : (bool) $parsed['seen'],
                'is_starred' => (bool) $parsed['flagged'],
                'is_draft' => $folder->type === 'drafts',
                'is_outgoing' => $isOutgoing,
                'received_at' => $date,
                'sent_at' => $isOutgoing ? $date : null,
            ]);

            $thread = $this->threader->assign($account, $message, $parsed);
            $this->linker->link($account, $message, $parsed);
            // Legacy CRM-sent replies carry self-referential In-Reply-To headers, so header threading
            // cannot join them. If this message is linked to a lead that already has a thread in this
            // mailbox, join that thread instead of starting a new one.
            if ($message->crm_email_id && (int) $thread->message_count === 0) {
                $existing = CrmMailMessage::withoutGlobalScopes()->where('account_id', $account->id)
                    ->where('crm_email_id', $message->crm_email_id)->whereNotNull('thread_id')
                    ->where('thread_id', '!=', $thread->id)->orderByDesc('received_at')->value('thread_id');
                if ($existing) {
                    $thread->delete();
                    $thread = \App\CrmMailThread::withoutGlobalScopes()->find($existing);
                    $message->thread_id = $thread->id;
                }
            }
            $message->save();

            foreach ($parsed['attachments'] as $i => $att) {
                $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $att['name'] ?: ('file-' . $i)) ?: ('file-' . $i);
                $path = sprintf('mail/%d/%d/%s', $account->id, $message->id, Str::limit($safe, 120, ''));
                if (Storage::disk(self::DISK)->exists($path)) {
                    $path = sprintf('mail/%d/%d/%d-%s', $account->id, $message->id, $i, Str::limit($safe, 110, ''));
                }
                Storage::disk(self::DISK)->put($path, $att['content']);
                CrmMailAttachment::create([
                    'message_id' => $message->id,
                    'original_name' => Str::limit($att['name'] ?: $safe, 250, ''),
                    'mime_type' => Str::limit($att['mime'] ?? 'application/octet-stream', 250, ''),
                    'size' => $att['size'] ?? strlen($att['content']),
                    'disk' => self::DISK,
                    'path' => $path,
                    'content_id' => $att['cid'] ? Str::limit($att['cid'], 250, '') : null,
                    'is_inline' => !empty($att['inline']),
                ]);
            }

            $mirrored = $this->linker->mirrorInbound($account, $message, $parsed);
            $this->threader->refresh($thread);
            return [$message, $mirrored];
        });
    }

    public static function backoffKey(int $accountId): string
    {
        return 'mail:sync:backoff:' . $accountId;
    }

    /** True while an account is paused after a failed sync (10 min after an auth failure, 2 min otherwise). */
    public static function inBackoff(int $accountId): bool
    {
        return Cache::has(self::backoffKey($accountId));
    }

    public function refreshFolderCounts(CrmMailFolder $folder): void
    {
        $base = CrmMailMessage::withoutGlobalScopes()->where('folder_id', $folder->id);
        $folder->forceFill([
            'message_count' => (clone $base)->count(),
            'unread_count' => (clone $base)->where('is_read', false)->count(),
        ])->save();
    }
}
