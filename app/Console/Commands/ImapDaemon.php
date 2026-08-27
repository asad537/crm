<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\CrmEmail;
use App\CrmMessage;
use App\CrmUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImapDaemon extends Command
{
    protected $signature = 'crm:imap-daemon {--user= : Poll only one CRM user ID}';

    protected $description = 'Poll CRM inboxes every 5 seconds in a short background cycle.';

    public function handle()
    {
        for ($pollNumber = 1; $pollNumber <= 4; $pollNumber++) {
            $this->info("[" . date('H:i:s') . "] IMAP poll {$pollNumber}/4 started");
            $this->poll();
            $this->info("[" . date('H:i:s') . "] IMAP poll {$pollNumber}/4 completed");

            if ($pollNumber < 4) {
                sleep(5);
            }
        }

        return 0;
    }

    private function poll()
    {
        imap_timeout(IMAP_OPENTIMEOUT, 5);
        imap_timeout(IMAP_READTIMEOUT, 10);
        imap_timeout(IMAP_WRITETIMEOUT, 10);
        imap_timeout(IMAP_CLOSETIMEOUT, 5);

        $query = CrmUser::whereNotNull('email_user')
            ->where('email_user', '!=', '')
            ->whereNotNull('email_pass')
            ->where('email_pass', '!=', '');

        if ($this->option('user')) {
            $query->where('id', $this->option('user'));
        } else {
            $query->whereHas('workspaces', function ($workspaceQuery) {
                $workspaceQuery->whereIn('crm_user_workspace.role', ['super_admin', 'admin', 'sales_manager', 'sales']);
            });
        }

        $users = $query->orderBy('last_seen_at', 'desc')->get()
            ->unique(function ($user) {
                return strtolower(trim($user->email_user));
            });

        foreach ($users as $user) {
            $lockPath = storage_path('framework/cache/imap-sync-' . $user->id . '.lock');
            $lock = @fopen($lockPath, 'c');
            if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
                if ($lock) fclose($lock);
                continue;
            }

            $host       = $user->imap_host       ?? env('IMAP_HOST',       'imap.hostinger.com');
            $port       = $user->imap_port       ?? env('IMAP_PORT',       993);
            $encryption = $user->imap_encryption ?? env('IMAP_ENCRYPTION', 'ssl');
            $username   = $user->email_user;
            $password   = $user->email_pass;

            if (!$username || !$password) {
                flock($lock, LOCK_UN);
                fclose($lock);
                continue;
            }

            $mailbox = "{" . $host . ":" . $port . "/imap/" . $encryption . "/novalidate-cert}INBOX";

            imap_errors();
            $connection = @imap_open($mailbox, $username, $password);

            if (!$connection) {
                flock($lock, LOCK_UN);
                fclose($lock);
                continue;
            }

            $cursorKey = 'crm_imap_last_uid_' . sha1(strtolower(trim($username)));
            $lastUid = (int) Cache::get($cursorKey, 0);

            $since = date('d-M-Y', strtotime('-2 days'));
            $messageNumbers = imap_search($connection, 'SINCE "' . $since . '"') ?: [];
            $emailUids = array_values(array_filter(array_map(function ($messageNumber) use ($connection) {
                return imap_uid($connection, $messageNumber);
            }, $messageNumbers), function ($emailUid) use ($lastUid) {
                return $emailUid && $emailUid > $lastUid;
            }));
            $emailUids = array_slice($emailUids, -100);

            if (!$emailUids) {
                imap_close($connection);
                flock($lock, LOCK_UN);
                fclose($lock);
                continue;
            }

            foreach ($emailUids as $emailUid) {
                $msgNum = imap_msgno($connection, $emailUid);
                if (!$msgNum) continue;

                try {
                    $header   = imap_headerinfo($connection, $msgNum);
                    $overview = imap_fetch_overview($connection, $msgNum, 0)[0];

                    $messageId = isset($header->message_id) ? trim($header->message_id) : null;
                    $fromEmail = isset($header->from[0]->mailbox, $header->from[0]->host)
                                 ? $header->from[0]->mailbox . '@' . $header->from[0]->host
                                 : null;
                    $subject   = isset($overview->subject) ? imap_utf8($overview->subject) : '(No Subject)';

                    if (!$fromEmail) continue;

                    // --- Skip own-domain ---
                    $inboxDomain = substr(strrchr($username, '@'), 1);
                    if (stripos($fromEmail, '@' . $inboxDomain) !== false) {
                        imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                        continue;
                    }

                    // --- Strict system/bounce filter (3 layers) ---
                    $rawHeaders = imap_fetchheader($connection, $msgNum);
                    if ($this->isSystemMail($rawHeaders, $fromEmail, $subject)) {
                        imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                        continue;
                    }

                    // --- Skip already-processed ---
                    if ($messageId && CrmEmail::where('imap_message_id', $messageId)->exists()) {
                        imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                        continue;
                    }
                    if ($messageId && CrmMessage::where('message_id', $messageId)->exists()) {
                        imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                        continue;
                    }

                    $body = $this->getBody($connection, $msgNum);

                    // --- Match reply to existing lead ---
                    $inReplyTo  = isset($header->in_reply_to) ? trim($header->in_reply_to) : null;
                    $references = isset($header->references)  ? trim($header->references)  : null;
                    $parentEmail = null;

                    if ($inReplyTo || $references) {
                        $refIds = array_filter(array_map('trim', explode(' ', ($inReplyTo ?? '') . ' ' . ($references ?? ''))));
                        foreach ($refIds as $refId) {
                            if (!$refId) continue;
                            $parentEmail = CrmEmail::where('imap_message_id', $refId)->first();
                            if ($parentEmail) break;

                            $sentMsg = CrmMessage::where('message_id', $refId)
                                                 ->whereIn('sender_type', ['admin', 'agent'])
                                                 ->first();
                            if ($sentMsg) {
                                $parentEmail = CrmEmail::find($sentMsg->crm_email_id);
                                if ($parentEmail) break;
                            }
                        }
                    }

                    if ($parentEmail) {
                        CrmMessage::create([
                            'crm_email_id' => $parentEmail->id,
                            'message_id'   => $messageId,
                            'sender_type'  => 'client',
                            'message_body' => $body,
                        ]);
                        $protectedStatuses = ['Qualified Lead', 'Order Done', 'Closed', 'Rejected'];
                        if (!in_array($parentEmail->status, $protectedStatuses)) {
                            $parentEmail->update(['status' => 'Client Replied']);
                        }
                        $parentEmail->touch();
                        $this->info("[" . date('H:i:s') . "] ↳ Reply → lead #{$parentEmail->id} from {$fromEmail}");
                        Log::info("IMAP Daemon: reply attached to lead #{$parentEmail->id} from {$fromEmail}");
                    }
                    // else: not a reply to any lead → skip silently

                    imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                } catch (\Exception $e) {
                    Log::error("IMAP Daemon [{$username}] msg#{$msgNum}: " . $e->getMessage());
                } finally {
                    Cache::forever($cursorKey, max($lastUid, $emailUid));
                    $lastUid = max($lastUid, $emailUid);
                }
            }

            imap_close($connection);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function isSystemMail($rawHeaders, $fromEmail, $subject)
    {
        // Layer 1: RFC headers
        $autoHeaders = [
            'Auto-Submitted'           => ['auto-generated', 'auto-replied', 'auto-notified'],
            'Precedence'               => ['bulk', 'list', 'junk', 'auto_reply'],
            'X-Auto-Response-Suppress' => ['OOF', 'AutoReply', 'All', 'DR', 'RN', 'NRN'],
            'X-Autoreply'              => ['yes'],
            'X-Autorespond'            => ['*'],
        ];
        foreach ($autoHeaders as $hdrName => $hdrVals) {
            if (preg_match('/^' . preg_quote($hdrName, '/') . ':\s*(.+)$/im', $rawHeaders, $m)) {
                if ($hdrVals === ['*']) return true;
                foreach ($hdrVals as $val) {
                    if (stripos($m[1], $val) !== false) return true;
                }
            }
        }
        if (preg_match('/^List-(Unsubscribe|Id|Post|Help|Archive):/im', $rawHeaders)) return true;

        // Layer 2: Sender
        $bounceSenders = [
            'mailer-daemon', 'postmaster', 'noreply', 'no-reply', 'donotreply',
            'do-not-reply', 'bounce', 'bounces', 'notifications', 'notify',
            'alerts', 'daemon', 'mailerdaemon', 'newsletter', 'unsubscribe',
            'system', 'robot', 'automated',
        ];
        $fromLocal = strtolower(explode('@', $fromEmail)[0] ?? '');
        foreach ($bounceSenders as $b) {
            if (strpos($fromLocal, $b) !== false) return true;
        }

        // Layer 3: Subject
        $bounceSubjects = [
            'undelivered mail', 'undeliverable', 'delivery status', 'delivery failure',
            'mail delivery', 'returned mail', 'failure notice', 'failed delivery',
            'auto-reply', 'autoreply', 'automatic reply', 'automated reply',
            'out of office', 'away from', 'do not reply', 'do not respond',
            'this is an automated', 'newsletter', 'unsubscribe',
        ];
        $subjectLower = strtolower($subject);
        foreach ($bounceSubjects as $bs) {
            if (strpos($subjectLower, $bs) !== false) return true;
        }

        return false;
    }

    private function getBody($connection, $msgNum)
    {
        $structure = imap_fetchstructure($connection, $msgNum);
        $body      = '';

        if (!isset($structure->parts)) {
            $raw  = imap_fetchbody($connection, $msgNum, '1');
            $body = $this->decodeBody($raw, $structure->encoding ?? 0);
        } else {
            foreach ($structure->parts as $i => $part) {
                if ($part->subtype === 'PLAIN') {
                    $raw  = imap_fetchbody($connection, $msgNum, (string)($i + 1));
                    $body = $this->decodeBody($raw, $part->encoding ?? 0);
                    break;
                }
            }
            if (!$body) {
                foreach ($structure->parts as $i => $part) {
                    if ($part->subtype === 'HTML') {
                        $raw  = imap_fetchbody($connection, $msgNum, (string)($i + 1));
                        $body = strip_tags($this->decodeBody($raw, $part->encoding ?? 0));
                        break;
                    }
                }
            }
        }

        return $this->stripQuotedReply(trim($body) ?: '(no body)');
    }

    private function decodeBody($body, $encoding)
    {
        switch ($encoding) {
            case 3: return base64_decode($body);
            case 4: return quoted_printable_decode($body);
            default: return $body;
        }
    }

    private function stripQuotedReply($body)
    {
        $lines  = preg_split('/\r\n|\r|\n/', $body);
        $result = [];
        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if (preg_match('/^On .{10,} wrote:\s*$/i', $trimmed)) break;
            if (preg_match('/^On .{6,}/i', $trimmed)) {
                $next = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';
                if (preg_match('/^wrote:\s*$/i', $next)) break;
            }
            if (preg_match('/^>/', $trimmed)) break;
            $result[] = $line;
        }
        return trim(implode("\n", $result));
    }
}
