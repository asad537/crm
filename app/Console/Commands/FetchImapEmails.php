<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\CrmEmail;
use App\CrmMessage;
use App\CrmUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchImapEmails extends Command
{
    protected $signature = 'crm:fetch-emails {--mark-read : Mark fetched emails as read on server} {--user= : Fetch only for specific CRM user ID}';

    protected $description = 'Fetch emails from each CRM user IMAP inbox and import into CRM';

    public function handle()
    {
        // Get ALL CRM users — fallback to .env if user has no own credentials
        $query = CrmUser::query();

        if ($this->option('user')) {
            $query->where('id', $this->option('user'));
        } else {
            $query->whereNotNull('email_user')
                ->where('email_user', '!=', '')
                ->whereNotNull('email_pass')
                ->where('email_pass', '!=', '')
                ->whereHas('workspaces', function ($workspaceQuery) {
                    $workspaceQuery->whereIn('crm_user_workspace.role', ['super_admin', 'admin', 'sales_manager', 'sales']);
                });
        }

        $users = $query->get()->unique(function ($user) {
            return strtolower(trim((string) $user->email_user));
        });

        if ($users->isEmpty()) {
            $this->warn('No CRM users found.');
            return 1;
        }

        $this->info("Processing {$users->count()} user(s)...");

        foreach ($users as $user) {
            $this->line('');
            $label = $user->email_user ?? env('IMAP_USERNAME', env('MAIL_USERNAME'));
            $this->info("── Fetching for: {$user->name} <{$label}>");
            $this->fetchForUser($user);
        }

        return 0;
    }

    private function fetchForUser(CrmUser $user)
    {
        imap_timeout(IMAP_OPENTIMEOUT, 5);
        imap_timeout(IMAP_READTIMEOUT, 10);
        imap_timeout(IMAP_WRITETIMEOUT, 10);
        imap_timeout(IMAP_CLOSETIMEOUT, 5);

        // Use user's own credentials — fallback to .env if not set
        $host       = $user->imap_host       ?? env('IMAP_HOST', 'imap.hostinger.com');
        $port       = $user->imap_port       ?? env('IMAP_PORT', 993);
        $encryption = $user->imap_encryption ?? env('IMAP_ENCRYPTION', 'ssl');
        $username   = $user->email_user;
        $password   = $user->email_pass;

        if (!$username || !$password) {
            $this->warn("  SKIP: No credentials available (user has none, .env also empty).");
            return;
        }

        // Build IMAP connection string
        $mailbox = "{" . $host . ":" . $port . "/imap/" . $encryption . "/novalidate-cert}INBOX";

        $this->line("  Connecting to {$host}:{$port} as {$username}...");

        // Suppress PHP warnings — handle errors manually
        imap_errors(); // clear error stack
        $connection = @imap_open($mailbox, $username, $password);

        if (!$connection) {
            $error = imap_last_error() ?: 'Authentication failed';
            $this->error("  ✗ Connection failed: {$error}");
            Log::error("CRM IMAP [{$user->email_user}] failed: {$error}");
            imap_errors(); // clear to prevent fatal
            return;
        }

        $this->line("  Connected. Searching recent emails...");

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
            $this->line("  No recent emails found.");
            imap_close($connection);
            return;
        }

        $this->info("  Found " . count($emailUids) . " new email(s).");

        $imported = 0; $skipped = 0; $replied = 0;

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
                $fromName  = isset($header->from[0]->personal)
                             ? imap_utf8($header->from[0]->personal)
                             : $fromEmail;
                $subject   = isset($overview->subject) ? imap_utf8($overview->subject) : '(No Subject)';
                $date      = isset($overview->date) ? date('Y-m-d H:i:s', strtotime($overview->date)) : now();

                if (!$fromEmail) {
                    $this->warn("  SKIP msg#{$msgNum}: no sender address found");
                    $skipped++; continue;
                }

                // Skip own-domain emails (prevent loops)
                $inboxDomain = substr(strrchr($username, '@'), 1);
                if (stripos($fromEmail, '@' . $inboxDomain) !== false) {
                    $this->line("  SKIP msg#{$msgNum}: own-domain ({$fromEmail}) — domain={$inboxDomain}");
                    $skipped++;
                    if ($this->option('mark-read')) imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                    continue;
                }

                // ════════════════════════════════════════════════════════
                // STRICT SYSTEM-EMAIL FILTER
                // Checks 3 layers: headers → sender → subject
                // Any match = silently skip & mark read
                // ════════════════════════════════════════════════════════
                $rawHeaders   = imap_fetchheader($connection, $msgNum);
                $isSystemMail = false;
                $skipReason   = '';

                // --- Layer 1: Standard RFC headers that mark automated mail ---
                $autoHeaders = [
                    'Auto-Submitted'             => ['auto-generated', 'auto-replied', 'auto-notified'],
                    'Precedence'                 => ['bulk', 'list', 'junk', 'auto_reply'],
                    'X-Auto-Response-Suppress'   => ['OOF', 'AutoReply', 'All', 'DR', 'RN', 'NRN'],
                    'X-Autoreply'                => ['yes'],
                    'X-Autorespond'              => ['*'],   // any value
                ];
                foreach ($autoHeaders as $hdrName => $hdrVals) {
                    if (preg_match('/^' . preg_quote($hdrName, '/') . ':\s*(.+)$/im', $rawHeaders, $m)) {
                        if ($hdrVals === ['*']) { // any value counts
                            $isSystemMail = true;
                            $skipReason   = "header:{$hdrName}";
                            break;
                        }
                        foreach ($hdrVals as $val) {
                            if (stripos($m[1], $val) !== false) {
                                $isSystemMail = true;
                                $skipReason   = "header:{$hdrName}={$val}";
                                break 2;
                            }
                        }
                    }
                }

                // List-* headers = mailing lists / newsletters
                if (!$isSystemMail && preg_match('/^List-(Unsubscribe|Id|Post|Help|Archive):/im', $rawHeaders)) {
                    $isSystemMail = true;
                    $skipReason   = 'header:List-*';
                }

                // --- Layer 2: Sender address keywords ---
                if (!$isSystemMail) {
                    $bounceSenders = [
                        'mailer-daemon', 'postmaster', 'noreply', 'no-reply',
                        'donotreply', 'do-not-reply', 'bounce', 'bounces',
                        'notifications', 'notify', 'alerts', 'daemon',
                        'mailerdaemon', 'automailer', 'auto-mailer',
                        'newsletter', 'news-letter', 'unsubscribe',
                        'support-noreply', 'system', 'robot', 'automated',
                    ];
                    $fromLocal = strtolower(explode('@', $fromEmail)[0] ?? '');
                    foreach ($bounceSenders as $b) {
                        if (strpos($fromLocal, $b) !== false) {
                            $isSystemMail = true;
                            $skipReason   = "sender:{$fromEmail}";
                            break;
                        }
                    }
                }

                // --- Layer 3: Subject line keywords ---
                if (!$isSystemMail) {
                    $bounceSubjects = [
                        'undelivered mail', 'undeliverable', 'delivery status',
                        'delivery failure', 'delivery notification', 'mail delivery',
                        'returned mail', 'mail returned', 'bounced mail',
                        'failure notice', 'failed delivery', 'could not deliver',
                        'auto-reply', 'autoreply', 'automatic reply', 'automated reply',
                        'out of office', 'away from', 'on vacation', 'i am away',
                        'i am out', 'i\'m out', 'i\'m away',
                        'do not reply', 'do not respond', 'please do not reply',
                        'this is an automated', 'this message is automatically',
                        'spam', 'newsletter', 'unsubscribe',
                    ];
                    $subjectLower = strtolower($subject);
                    foreach ($bounceSubjects as $bs) {
                        if (strpos($subjectLower, $bs) !== false) {
                            $isSystemMail = true;
                            $skipReason   = "subject:{$bs}";
                            break;
                        }
                    }
                }

                if ($isSystemMail) {
                    $this->line("  SKIP msg#{$msgNum}: system/bounce [{$skipReason}] ({$fromEmail}) — \"{$subject}\"");
                    $skipped++;
                    if ($this->option('mark-read')) imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                    continue;
                }
                // ════════════════════════════════════════════════════════


                // Skip duplicates by message_id in crm_emails
                if ($messageId && CrmEmail::where('imap_message_id', $messageId)->exists()) {
                    $this->line("  SKIP msg#{$msgNum}: already in CRM leads (imap_message_id match)");
                    $skipped++;
                    if ($this->option('mark-read')) imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                    continue;
                }

                // Skip duplicates in crm_messages
                if ($messageId && CrmMessage::where('message_id', $messageId)->exists()) {
                    $this->line("  SKIP msg#{$msgNum}: already in CRM messages (message_id match)");
                    $skipped++;
                    if ($this->option('mark-read')) imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                    continue;
                }

                $body = $this->getBody($connection, $msgNum);

                // Check if this is a REPLY to existing lead
                $inReplyTo  = isset($header->in_reply_to) ? trim($header->in_reply_to) : null;
                $references = isset($header->references)  ? trim($header->references)  : null;

                $parentEmail = null;

                // Match reply via In-Reply-To / References headers
                // Check BOTH crm_emails.imap_message_id (original inbound lead)
                // AND crm_messages.message_id (CRM-sent reply whose message_id is stored there)
                if ($inReplyTo || $references) {
                    $refIds = array_filter(array_map('trim', explode(' ', ($inReplyTo ?? '') . ' ' . ($references ?? ''))));
                    foreach ($refIds as $refId) {
                        if (!$refId) continue;

                        // 1. Check original inbound lead
                        $parentEmail = CrmEmail::where('imap_message_id', $refId)->first();
                        if ($parentEmail) break;

                        // 2. Check CRM-sent messages (admin/agent replies sent via CRM)
                        //    Their Message-IDs are stored in crm_messages.message_id
                        $sentMsg = CrmMessage::where('message_id', $refId)
                                             ->whereIn('sender_type', ['admin', 'agent'])
                                             ->first();
                        if ($sentMsg) {
                            $parentEmail = CrmEmail::find($sentMsg->crm_email_id);
                            if ($parentEmail) break;
                        }
                    }
                }

                // NO subject/email fallback — if headers don't match, treat as NEW lead
                // This prevents replies mixing across multiple leads of the same client

                if ($parentEmail) {
                    // Client REPLY → attach as chat message to existing lead
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
                    $this->info("  ↳ Reply → lead #{$parentEmail->id} from {$fromEmail}: " . substr($body, 0, 60));
                    $replied++;
                } else {
                    // No matching lead found — skip silently
                    // New leads come ONLY from the website contact form, NOT from IMAP
                    $this->line("  SKIP msg#{$msgNum}: no matching lead found (not a reply) — {$fromEmail} \"{$subject}\"");
                    $skipped++;
                }

                if ($this->option('mark-read')) {
                    imap_setflag_full($connection, (string)$msgNum, '\\Seen');
                }

            } catch (\Exception $e) {
                $this->error("  Error msg #{$msgNum}: " . $e->getMessage());
                Log::error("CRM IMAP [{$username}] msg#{$msgNum}: " . $e->getMessage());
            } finally {
                Cache::forever($cursorKey, max($lastUid, $emailUid));
                $lastUid = max($lastUid, $emailUid);
            }
        }

        imap_close($connection);
        $this->line("  Done → Imported: {$imported} | Replies: {$replied} | Skipped: {$skipped}");
        Log::info("CRM IMAP [{$username}]: imported={$imported} replies={$replied} skipped={$skipped}");
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

    /**
     * Strip quoted reply history from email body.
     * Removes "On [date] [name] wrote:" and everything after it.
     */
    private function stripQuotedReply($body)
    {
        $lines  = preg_split('/\r\n|\r|\n/', $body);
        $result = [];
        $total  = count($lines);

        for ($i = 0; $i < $total; $i++) {
            $trimmed = trim($lines[$i]);

            // Pattern 1: single-line "On ... wrote:"  (e.g. Outlook)
            if (preg_match('/^On .{10,} wrote:\s*$/i', $trimmed)) {
                break;
            }

            // Pattern 2: Gmail two-line format
            // Line N:   "On Fri, May 8, 2026 at 5:08 PM Name <email>"
            // Line N+1: "wrote:"
            if (preg_match('/^On .{6,}/i', $trimmed)) {
                $nextTrimmed = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';
                if (preg_match('/^wrote:\s*$/i', $nextTrimmed)) {
                    break;
                }
            }

            // Pattern 3: lines starting with ">" (inline quoted text)
            if (preg_match('/^>/', $trimmed)) {
                break;
            }

            $result[] = $lines[$i];
        }

        return trim(implode("\n", $result));
    }
}
