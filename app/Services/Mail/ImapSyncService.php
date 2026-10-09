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
    public const LOCK_SECONDS = 600;

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
        $lock = Cache::lock('mail:sync:' . $account->id, self::LOCK_SECONDS);
        if (!$lock->get()) {
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

            $account->forceFill([
                'last_synced_at' => now(),
                'last_sync_error' => $stats['errors'] ? Str::limit(implode(' | ', $stats['errors']), 500) : null,
            ])->saveQuietly();
        } catch (\Throwable $e) {
            $stats['status'] = 'error';
            $stats['errors'][] = $e->getMessage();
            $account->forceFill(['last_sync_error' => Str::limit($e->getMessage(), 500)])->saveQuietly();
            Log::warning('Mail sync failed', ['account_id' => $account->id, 'error' => $e->getMessage()]);
        } finally {
            $client->close();
            $lock->release();
        }

        return $stats;
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
        foreach ($uids as $uid) {
            $maxUid = max($maxUid, $uid);
            try {
                $exists = CrmMailMessage::withTrashed()->withoutGlobalScopes()
                    ->where('account_id', $account->id)->where('folder_id', $folder->id)->where('uid', $uid)->exists();
                if ($exists) { $r['skipped']++; continue; }

                $parsed = $client->fetchMessage($folder->path, $uid);

                if (!empty($parsed['message_id'])) {
                    $dupe = CrmMailMessage::withTrashed()->withoutGlobalScopes()
                        ->where('account_id', $account->id)->where('message_id', $parsed['message_id'])->first();
                    if ($dupe) {
                        // Same message already stored from another folder (e.g. Gmail "All Mail", or a Sent
                        // copy we recorded when sending). Prefer the more specific folder for the pointer.
                        if ($dupe->folder_id !== $folder->id && in_array($folder->type, ['inbox', 'sent'], true) && !$dupe->folder()->withoutGlobalScopes()->whereIn('type', ['inbox', 'sent'])->exists()) {
                            $dupe->forceFill(['folder_id' => $folder->id, 'uid' => $uid])->saveQuietly();
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
        $this->refreshFolderCounts($folder);
        return $r;
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

    public function refreshFolderCounts(CrmMailFolder $folder): void
    {
        $base = CrmMailMessage::withoutGlobalScopes()->where('folder_id', $folder->id);
        $folder->forceFill([
            'message_count' => (clone $base)->count(),
            'unread_count' => (clone $base)->where('is_read', false)->count(),
        ])->save();
    }
}
