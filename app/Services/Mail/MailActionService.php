<?php

namespace App\Services\Mail;

use App\CrmMailAccount;
use App\CrmMailFolder;
use App\CrmMailMessage;
use App\CrmUser;
use Illuminate\Support\Facades\Log;

/**
 * CRM → server actions on synced messages: move (archive / trash / junk / inbox / custom folder),
 * permanent delete, read/unread, star. The DB is updated and the same change is pushed to the
 * mailbox over IMAP so Outlook/webmail stay consistent. Moves require the IMAP step to succeed
 * when the message exists on the server (uid set); flag pushes are best-effort.
 * Callers must already have authorised the actor as the mailbox OWNER.
 */
class MailActionService
{
    public const TARGETS = ['inbox', 'archive', 'trash', 'junk'];

    public function __construct(private ImapClientFactory $clients, private ImapSyncService $sync)
    {
    }

    /** @param string $target inbox|archive|trash|junk|folder:<id> */
    public function move(CrmMailMessage $message, string $target, CrmUser $actor): CrmMailMessage
    {
        $account = $message->account()->withoutGlobalScopes()->firstOrFail();
        $from = $message->folder()->withoutGlobalScopes()->firstOrFail();
        $to = $this->targetFolder($account, $target);
        if ((int) $to->id === (int) $from->id) {
            return $message;
        }

        if ($message->uid !== null) {
            $client = $this->clients->make($account);
            try {
                if (!$client->moveMessage($from->path, (int) $message->uid, $to->path)) {
                    throw new \RuntimeException('The mail server refused to move the message.');
                }
            } finally {
                $client->close();
            }
        }
        // The server assigns a new UID in the target folder; the next sync re-finds it by Message-ID.
        $message->forceFill(['folder_id' => $to->id, 'uid' => null, 'is_draft' => $to->type === 'drafts'])->saveQuietly();
        $this->sync->refreshFolderCounts($from);
        $this->sync->refreshFolderCounts($to);
        Log::info('Mail moved', ['actor_id' => $actor->id, 'message_id' => $message->id, 'from' => $from->path, 'to' => $to->path]);
        return $message->fresh();
    }

    public function delete(CrmMailMessage $message, CrmUser $actor): void
    {
        $account = $message->account()->withoutGlobalScopes()->firstOrFail();
        $folder = $message->folder()->withoutGlobalScopes()->first();
        if ($message->uid !== null && $folder) {
            $client = $this->clients->make($account);
            try {
                if (!$client->deleteMessage($folder->path, (int) $message->uid)) {
                    throw new \RuntimeException('The mail server refused to delete the message.');
                }
            } finally {
                $client->close();
            }
        }
        $message->forceFill(['uid' => null])->saveQuietly();
        $message->delete(); // soft delete; attachments stay on disk until a cleanup job
        if ($folder) $this->sync->refreshFolderCounts($folder);
        Log::info('Mail deleted', ['actor_id' => $actor->id, 'message_id' => $message->id]);
    }

    public function setRead(CrmMailMessage $message, bool $read, bool $pushToServer = true): void
    {
        if ((bool) $message->is_read !== $read) {
            $message->forceFill(['is_read' => $read])->saveQuietly();
        }
        if ($pushToServer) $this->pushFlag($message, '\\Seen', $read);
        if ($folder = $message->folder()->withoutGlobalScopes()->first()) $this->sync->refreshFolderCounts($folder);
        if ($thread = $message->thread()->withoutGlobalScopes()->first()) app(MailThreader::class)->refresh($thread);
    }

    public function setStar(CrmMailMessage $message, bool $star, bool $pushToServer = true): void
    {
        if ((bool) $message->is_starred !== $star) {
            $message->forceFill(['is_starred' => $star])->saveQuietly();
        }
        if ($pushToServer) $this->pushFlag($message, '\\Flagged', $star);
    }

    private function pushFlag(CrmMailMessage $message, string $flag, bool $on): void
    {
        if ($message->uid === null) return;
        $account = $message->account()->withoutGlobalScopes()->first();
        $folder = $message->folder()->withoutGlobalScopes()->first();
        if (!$account || !$folder) return;
        try {
            $client = $this->clients->make($account);
            $client->setFlag($folder->path, (int) $message->uid, $flag, $on);
            $client->close();
        } catch (\Throwable $e) {
            Log::info('Mail flag push failed', ['message_id' => $message->id, 'flag' => $flag, 'error' => $e->getMessage()]);
        }
    }

    /** Resolve (and create on the server if needed) the destination folder. */
    private function targetFolder(CrmMailAccount $account, string $target): CrmMailFolder
    {
        if (str_starts_with($target, 'folder:')) {
            $folder = CrmMailFolder::withoutGlobalScopes()->where('account_id', $account->id)->find((int) substr($target, 7));
            if (!$folder) throw new \RuntimeException('Unknown folder.');
            return $folder;
        }
        if (!in_array($target, self::TARGETS, true)) {
            throw new \RuntimeException('Unknown destination.');
        }
        $folder = CrmMailFolder::withoutGlobalScopes()->where('account_id', $account->id)->where('type', $target)->first();
        if ($folder) return $folder;

        // Not known locally: create it on the server (or discover it) and record it.
        $client = $this->clients->make($account);
        try {
            $path = $client->ensureFolder($target);
        } finally {
            $client->close();
        }
        return CrmMailFolder::withoutGlobalScopes()->firstOrCreate(
            ['account_id' => $account->id, 'path' => $path],
            ['name' => ucfirst($target), 'type' => $target]
        );
    }
}
