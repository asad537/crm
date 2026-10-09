<?php

namespace Tests\Feature;

use App\CrmMailAccount;
use App\Services\Mail\ImapClient;

/** In-memory IMAP double shared by the mail test suites (no network). */
class FakeImap extends ImapClient
{
    public array $folders = [];
    public array $status = [];
    /** @var array<string, array<int, array>> folder path => uid => parsed message */
    public array $messages = [];
    public array $appended = [];

    public function __construct() { /* no account, no connection */ }
    public function bind(CrmMailAccount $account): void {}
    public function __destruct() {}
    public function open(string $folderPath = 'INBOX'): void {}
    public function close(): void {}
    public function listFolders(bool $fresh = false): array { return $this->folders; }
    public function status(string $folderPath): array
    {
        // Derived from the in-memory messages like a real server (uidvalidity may be pinned by the test).
        $msgs = $this->messages[$folderPath] ?? [];
        return [
            'uidvalidity' => (int) ($this->status[$folderPath]['uidvalidity'] ?? 1),
            'uidnext' => ($msgs ? max(array_keys($msgs)) : 0) + 1,
            'messages' => count($msgs),
            'unseen' => count(array_filter($msgs, fn ($m) => empty($m['seen']))),
        ];
    }
    public function searchUids(string $folderPath, int $afterUid, int $sinceDays = 30, int $cap = 200): array
    {
        $uids = array_keys($this->messages[$folderPath] ?? []);
        sort($uids);
        return array_values(array_filter($uids, fn ($u) => $u > $afterUid));
    }
    public function fetchMessage(string $folderPath, int $uid): array { return $this->messages[$folderPath][$uid]; }
    public function findFolderPathByType(string $type): ?string { foreach ($this->folders as $f) if ($f['type'] === $type) return $f['path']; return null; }
    public function appendMessage(string $mime, ?string $folderPath = null, string $flags = '\\Seen'): ?string { $this->appended[] = $mime; return $folderPath ?: 'INBOX.Sent'; }

    // ---- Phase 6 ----
    public array $flagCalls = [];
    public array $moves = [];
    public array $deletes = [];
    public array $created = [];
    public bool $refuseMoves = false;

    public function allUids(string $folderPath): array { $u = array_keys($this->messages[$folderPath] ?? []); sort($u); return $u; }
    public function overview(string $folderPath): array
    {
        $out = [];
        foreach ($this->messages[$folderPath] ?? [] as $uid => $m) { $out[(int) $uid] = ['seen' => !empty($m['seen']), 'flagged' => !empty($m['flagged'])]; }
        return $out;
    }
    public function setFlag(string $folderPath, int $uid, string $flag, bool $on = true): bool
    {
        $this->flagCalls[] = [$folderPath, $uid, $flag, $on];
        if (isset($this->messages[$folderPath][$uid])) { $key = $flag === '\\Flagged' ? 'flagged' : 'seen'; $this->messages[$folderPath][$uid][$key] = $on; }
        return true;
    }
    public function moveMessage(string $fromFolder, int $uid, string $toFolder): bool
    {
        if ($this->refuseMoves) return false;
        $this->moves[] = [$fromFolder, $uid, $toFolder];
        if (isset($this->messages[$fromFolder][$uid])) {
            $m = $this->messages[$fromFolder][$uid]; unset($this->messages[$fromFolder][$uid]);
            $newUid = (max(array_keys($this->messages[$toFolder] ?? [0 => null])) ?: 0) + 1000;
            $m['uid'] = $newUid; $this->messages[$toFolder][$newUid] = $m;
        }
        return true;
    }
    public function deleteMessage(string $folderPath, int $uid): bool { $this->deletes[] = [$folderPath, $uid]; unset($this->messages[$folderPath][$uid]); return true; }
    public function ensureFolder(string $type, ?string $name = null): string
    {
        if ($p = $this->findFolderPathByType($type)) return $p;
        $path = 'INBOX.' . ($name ?: ucfirst($type));
        $this->folders[] = ['path' => $path, 'name' => $name ?: ucfirst($type), 'type' => $type, 'delimiter' => '.', 'selectable' => true];
        $this->created[] = $path;
        return $path;
    }
}
