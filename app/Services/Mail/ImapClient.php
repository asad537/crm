<?php

namespace App\Services\Mail;

use App\CrmMailAccount;

/**
 * Thin, account-bound wrapper over ext-imap used by the per-account sync.
 *
 * - Folder discovery with type mapping (inbox/sent/drafts/archive/junk/trash/custom)
 * - UIDVALIDITY / UIDNEXT status, UID search after a cursor
 * - Full message parse: headers, addresses, text + HTML bodies (nested multipart,
 *   charset conversion, base64/QP), attachments and inline (cid) parts, flags
 *
 * Never logs or returns the password. Errors throw \RuntimeException with a
 * short, non-sensitive message (the ext-imap error stack is drained each time).
 */
class ImapClient
{
    /** @var resource|\IMAP\Connection|null */
    private $conn = null;
    private ?string $openPath = null;

    public function __construct(private CrmMailAccount $account)
    {
    }

    public function __destruct()
    {
        $this->close();
    }

    // ---- connection ---------------------------------------------------------

    private function root(): string
    {
        return $this->account->imapMailbox('');
    }

    public function open(string $folderPath = 'INBOX'): void
    {
        if ($this->conn && $this->openPath === $folderPath) {
            return;
        }
        if ($this->conn) {
            if (@imap_reopen($this->conn, $this->root() . $folderPath)) {
                $this->openPath = $folderPath;
                imap_errors();
                return;
            }
            $this->close();
        }
        imap_timeout(IMAP_OPENTIMEOUT, 10);
        imap_timeout(IMAP_READTIMEOUT, 20);
        imap_timeout(IMAP_WRITETIMEOUT, 20);
        imap_timeout(IMAP_CLOSETIMEOUT, 5);
        imap_errors();
        $conn = @imap_open($this->root() . $folderPath, (string) $this->account->email_user, (string) $this->account->email_pass, 0, 1);
        $errors = imap_errors() ?: [];
        if (!$conn) {
            \Illuminate\Support\Facades\Log::warning('IMAP open failed', ['account_id' => $this->account->id, 'host' => $this->account->imap_host, 'port' => $this->account->imap_port, 'folder' => $folderPath, 'raw' => $errors]);
            throw new \RuntimeException($this->safeError($errors, 'IMAP connection failed'));
        }
        $this->conn = $conn;
        $this->openPath = $folderPath;
    }

    public function close(): void
    {
        if ($this->conn) {
            @imap_close($this->conn);
            imap_errors();
        }
        $this->conn = null;
        $this->openPath = null;
    }

    // ---- folders ------------------------------------------------------------

    /**
     * @return array<int, array{path:string,name:string,type:string,delimiter:string,selectable:bool}>
     */
    public function listFolders(): array
    {
        $this->open('INBOX');
        $boxes = @imap_getmailboxes($this->conn, $this->root(), '*') ?: [];
        imap_errors();
        $out = [];
        foreach ($boxes as $box) {
            $full = imap_utf7_decode($box->name);
            $path = preg_replace('/^\{[^}]+\}/', '', $full);
            if ($path === '' || $path === null) {
                continue;
            }
            $delim = $box->delimiter ?: '.';
            $selectable = !($box->attributes & LATT_NOSELECT);
            $parts = explode($delim, $path);
            $name = end($parts);
            $out[] = [
                'path' => $path,
                'name' => $name,
                'type' => self::folderType($path, $box->attributes),
                'delimiter' => $delim,
                'selectable' => $selectable,
            ];
        }
        return $out;
    }

    /** Map an IMAP folder to one of CrmMailFolder::TYPES using SPECIAL-USE flags, then common names. */
    public static function folderType(string $path, int $attributes = 0): string
    {
        if (defined('LATT_SENT') && ($attributes & LATT_SENT)) return 'sent';
        if (defined('LATT_DRAFTS') && ($attributes & LATT_DRAFTS)) return 'drafts';
        if (defined('LATT_TRASH') && ($attributes & LATT_TRASH)) return 'trash';
        if (defined('LATT_JUNK') && ($attributes & LATT_JUNK)) return 'junk';
        if (defined('LATT_ARCHIVE') && ($attributes & LATT_ARCHIVE)) return 'archive';

        $n = strtolower(trim($path));
        $leaf = preg_replace('/^.*[.\/]/', '', $n);
        if ($n === 'inbox') return 'inbox';
        if (preg_match('/^(sent|sent items|sent mail|sent messages|\[gmail\]\/sent mail)$/', $leaf) || str_contains($n, '[gmail]/sent')) return 'sent';
        if (preg_match('/^(drafts?|\[gmail\]\/drafts)$/', $leaf) || str_contains($n, '[gmail]/drafts')) return 'drafts';
        if (preg_match('/^(trash|deleted|deleted items|deleted messages|bin|\[gmail\]\/trash|\[gmail\]\/bin)$/', $leaf) || str_contains($n, '[gmail]/trash') || str_contains($n, '[gmail]/bin')) return 'trash';
        if (preg_match('/^(junk|junk e-mail|junk email|spam|bulk mail|\[gmail\]\/spam)$/', $leaf) || str_contains($n, '[gmail]/spam')) return 'junk';
        if (preg_match('/^(archive|archives|all mail|\[gmail\]\/all mail)$/', $leaf) || str_contains($n, '[gmail]/all mail')) return 'archive';
        return 'custom';
    }

    /** @return array{uidvalidity:int,uidnext:int,messages:int,unseen:int} */
    public function status(string $folderPath): array
    {
        $this->open($folderPath);
        $st = @imap_status($this->conn, $this->root() . $folderPath, SA_UIDVALIDITY | SA_UIDNEXT | SA_MESSAGES | SA_UNSEEN);
        imap_errors();
        return [
            'uidvalidity' => (int) ($st->uidvalidity ?? 0),
            'uidnext' => (int) ($st->uidnext ?? 0),
            'messages' => (int) ($st->messages ?? 0),
            'unseen' => (int) ($st->unseen ?? 0),
        ];
    }

    /**
     * UIDs greater than $afterUid. On the very first sync (afterUid=0) limit to the
     * last $sinceDays days so a huge mailbox does not get pulled in at once.
     * @return int[] ascending
     */
    public function searchUids(string $folderPath, int $afterUid, int $sinceDays = 30, int $cap = 200): array
    {
        $this->open($folderPath);
        if ($afterUid > 0) {
            $uids = @imap_search($this->conn, 'UID ' . ($afterUid + 1) . ':*', SE_UID) ?: [];
            // Some servers return the last UID itself for "n:*" when nothing is newer.
            $uids = array_values(array_filter($uids, fn ($u) => (int) $u > $afterUid));
        } else {
            $since = date('d-M-Y', strtotime('-' . max(1, $sinceDays) . ' days'));
            $uids = @imap_search($this->conn, 'SINCE "' . $since . '"', SE_UID) ?: [];
        }
        imap_errors();
        $uids = array_map('intval', $uids);
        sort($uids);
        if ($cap > 0 && count($uids) > $cap) {
            $uids = array_slice($uids, -$cap);
        }
        return $uids;
    }

    // ---- messages -----------------------------------------------------------

    /**
     * Parse one message by UID.
     * @return array{
     *   uid:int, message_id:?string, in_reply_to:?string, references:?string, subject:?string,
     *   from_name:?string, from_email:?string, to:array, cc:array, bcc:array, reply_to:?string,
     *   date:?\DateTimeInterface, text:?string, html:?string, attachments:array, seen:bool, flagged:bool,
     *   raw_headers:string, size:int
     * }
     */
    public function fetchMessage(string $folderPath, int $uid): array
    {
        $this->open($folderPath);
        $rawHeaders = (string) @imap_fetchheader($this->conn, $uid, FT_UID);
        $header = @imap_rfc822_parse_headers($rawHeaders);
        $overview = @imap_fetch_overview($this->conn, (string) $uid, FT_UID);
        $ov = $overview[0] ?? null;
        $structure = @imap_fetchstructure($this->conn, $uid, FT_UID);
        imap_errors();

        $parsed = ['text' => '', 'html' => '', 'attachments' => []];
        if ($structure) {
            $this->walkParts($uid, $structure, '', $parsed);
        }

        $msgId = isset($header->message_id) ? trim($header->message_id) : null;
        $date = null;
        if (!empty($header->date)) {
            try { $date = new \DateTimeImmutable($header->date); } catch (\Throwable $e) { $date = null; }
        }
        if (!$date && !empty($ov->udate)) {
            $date = (new \DateTimeImmutable())->setTimestamp((int) $ov->udate);
        }

        $from = $this->addressList($header->from ?? []);
        return [
            'uid' => $uid,
            'message_id' => $msgId ?: null,
            'in_reply_to' => isset($header->in_reply_to) ? trim($header->in_reply_to) : null,
            'references' => isset($header->references) ? trim($header->references) : null,
            'subject' => isset($header->subject) ? $this->decodeHeader($header->subject) : null,
            'from_name' => $from[0]['name'] ?? null,
            'from_email' => $from[0]['email'] ?? null,
            'to' => $this->addressList($header->to ?? []),
            'cc' => $this->addressList($header->cc ?? []),
            'bcc' => $this->addressList($header->bcc ?? []),
            'reply_to' => $this->addressList($header->reply_to ?? [])[0]['email'] ?? null,
            'date' => $date,
            'text' => trim($parsed['text']) !== '' ? $parsed['text'] : null,
            'html' => trim($parsed['html']) !== '' ? $parsed['html'] : null,
            'attachments' => $parsed['attachments'],
            'seen' => !empty($ov->seen),
            'flagged' => !empty($ov->flagged),
            'raw_headers' => $rawHeaders,
            'size' => (int) ($ov->size ?? 0),
        ];
    }

    /** Set or clear a flag on a message (used by Phase 6 actions). */
    public function setFlag(string $folderPath, int $uid, string $flag, bool $on = true): bool
    {
        $this->open($folderPath);
        $fn = $on ? 'imap_setflag_full' : 'imap_clearflag_full';
        $ok = @$fn($this->conn, (string) $uid, $flag, ST_UID);
        imap_errors();
        return (bool) $ok;
    }

    // ---- MIME walking -------------------------------------------------------

    private function walkParts(int $uid, object $part, string $prefix, array &$out): void
    {
        if (!empty($part->parts)) {
            // multipart container: recurse
            foreach ($part->parts as $i => $sub) {
                $section = $prefix === '' ? (string) ($i + 1) : $prefix . '.' . ($i + 1);
                // A message/rfc822 part has its own structure; treat its body as a sub-tree.
                $this->walkParts($uid, $sub, $section, $out);
            }
            return;
        }

        $section = $prefix === '' ? '1' : $prefix;
        $type = (int) ($part->type ?? 0);          // 0 text, 1 multipart, 2 message, 3 app, 4 audio, 5 image, 6 video, 7 other
        $subtype = strtoupper((string) ($part->subtype ?? ''));
        $params = $this->params($part);
        $disposition = strtolower((string) ($part->disposition ?? ''));
        $filename = $params['filename'] ?? $params['name'] ?? null;
        $cid = isset($part->id) ? trim($part->id, '<> ') : null;
        $isAttachment = $disposition === 'attachment' || ($filename && $type !== 0) || ($type === 5 && $filename);

        if ($type === 0 && !$isAttachment) {
            $body = $this->fetchDecoded($uid, $section, (int) ($part->encoding ?? 0), $params['charset'] ?? null);
            if ($subtype === 'PLAIN') {
                $out['text'] .= ($out['text'] === '' ? '' : "\n") . $body;
            } elseif ($subtype === 'HTML') {
                $out['html'] .= $body;
            } else {
                $out['text'] .= ($out['text'] === '' ? '' : "\n") . $body;
            }
            return;
        }

        if ($type === 2) { // message/rfc822 without parsed sub-parts: keep as attachment
            $filename = $filename ?: 'message.eml';
        }

        $content = $this->fetchDecoded($uid, $section, (int) ($part->encoding ?? 0), null);
        $mime = strtolower($this->mimeType($type) . '/' . strtolower($subtype ?: 'octet-stream'));
        $out['attachments'][] = [
            'name' => $filename ? $this->decodeHeader($filename) : ('part-' . $section . '.' . $this->guessExt($mime)),
            'mime' => $mime,
            'content' => $content,
            'size' => strlen($content),
            'cid' => $cid,
            'inline' => $disposition === 'inline' || ($cid && $type === 5),
        ];
    }

    private function fetchDecoded(int $uid, string $section, int $encoding, ?string $charset): string
    {
        $raw = (string) @imap_fetchbody($this->conn, $uid, $section, FT_UID | FT_PEEK); // PEEK: never mark \Seen
        imap_errors();
        switch ($encoding) {
            case ENCBASE64: $raw = base64_decode($raw) ?: ''; break;
            case ENCQUOTEDPRINTABLE: $raw = quoted_printable_decode($raw); break;
            default: break;
        }
        if ($charset) {
            $cs = strtoupper($charset);
            if ($cs !== 'UTF-8' && $cs !== 'UTF8' && $cs !== 'US-ASCII') {
                $converted = @mb_convert_encoding($raw, 'UTF-8', $cs === 'KS_C_5601-1987' ? 'CP949' : $cs);
                if ($converted !== false && $converted !== null) {
                    $raw = $converted;
                }
            }
        }
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = @mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1') ?: $raw;
        }
        return $raw;
    }

    private function params(object $part): array
    {
        $out = [];
        foreach (['parameters', 'dparameters'] as $key) {
            if (!empty($part->{$key}) && is_array($part->{$key})) {
                foreach ($part->{$key} as $p) {
                    if (isset($p->attribute, $p->value)) {
                        $out[strtolower($p->attribute)] = $p->value;
                    }
                }
            }
        }
        // RFC 2231 split names (filename*0, filename*1 …)
        foreach (['filename', 'name'] as $k) {
            if (!isset($out[$k])) {
                $joined = '';
                for ($i = 0; isset($out[$k . '*' . $i]) || isset($out[$k . '*' . $i . '*']); $i++) {
                    $joined .= $out[$k . '*' . $i] ?? $out[$k . '*' . $i . '*'];
                }
                if ($joined !== '') {
                    $out[$k] = preg_replace('/^[^\']*\'[^\']*\'/', '', rawurldecode($joined));
                }
            }
        }
        return $out;
    }

    private function mimeType(int $type): string
    {
        return ['text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'other'][$type] ?? 'application';
    }

    private function guessExt(string $mime): string
    {
        return ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'application/pdf' => 'pdf', 'text/plain' => 'txt', 'text/html' => 'html'][$mime] ?? 'bin';
    }

    private function decodeHeader(?string $value): ?string
    {
        if ($value === null) return null;
        $decoded = '';
        foreach (imap_mime_header_decode($value) ?: [] as $piece) {
            $cs = strtoupper($piece->charset ?? 'default');
            $txt = $piece->text ?? '';
            if ($cs !== 'DEFAULT' && $cs !== 'UTF-8' && $cs !== 'US-ASCII') {
                $txt = @mb_convert_encoding($txt, 'UTF-8', $cs) ?: $txt;
            }
            $decoded .= $txt;
        }
        return trim($decoded) !== '' ? trim($decoded) : trim(imap_utf8($value));
    }

    /** @return array<int, array{name:?string,email:string}> */
    private function addressList($list): array
    {
        $out = [];
        foreach ((array) $list as $a) {
            if (empty($a->mailbox) || empty($a->host) || $a->host === '.SYNTAX-ERROR.') continue;
            $out[] = [
                'name' => isset($a->personal) ? $this->decodeHeader($a->personal) : null,
                'email' => strtolower($a->mailbox . '@' . $a->host),
            ];
        }
        return $out;
    }

    private function safeError(array $errors, string $fallback): string
    {
        $raw = strtolower(implode(' | ', $errors));
        if (str_contains($raw, 'auth') || str_contains($raw, 'login') || str_contains($raw, 'password')) return 'IMAP login rejected (check password / app password)';
        if (str_contains($raw, 'certificate') || str_contains($raw, 'tls') || str_contains($raw, 'ssl')) return 'IMAP secure connection failed (check encryption/port)';
        if (str_contains($raw, 'timed out') || str_contains($raw, 'timeout') || str_contains($raw, 'connect')) return 'IMAP server unreachable (host/port)';
        return $fallback;
    }
}
