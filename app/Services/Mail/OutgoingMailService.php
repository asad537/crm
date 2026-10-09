<?php

namespace App\Services\Mail;

use App\CrmEmail;
use App\CrmMailAccount;
use App\CrmMailAttachment;
use App\CrmMailFolder;
use App\CrmMailMessage;
use App\CrmMessage;
use App\CrmStatusLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Sends mail FROM a connected mailbox (its own SMTP), never from the global mailer:
 *  - From / Reply-To = the account; real In-Reply-To / References when replying
 *  - stores the message in the account's Sent folder (DB) and appends a copy to IMAP Sent
 *  - links to the CRM lead when replying to a linked message and mirrors it into
 *    crm_messages so the lead workflow (status "Responded") stays consistent
 *  - attachments live on the private disk
 * Failures throw \RuntimeException with a user-safe message; nothing is stored on failure.
 */
class OutgoingMailService
{
    private const PROTECTED_STATUSES = ['Qualified Lead', 'Order Done', 'Closed', 'Rejected'];

    public function __construct(private HtmlSanitizerService $sanitizer, private MailThreader $threader)
    {
    }

    /**
     * @param array{
     *   to:array<int,string>, cc?:array<int,string>, bcc?:array<int,string>, subject:string,
     *   html:string, attachments?:array<int,UploadedFile>, reply_to_message?:?CrmMailMessage,
     *   forward_of?:?CrmMailMessage, crm_email_id?:?int, sent_by?:?\App\CrmUser
     * } $draft
     */
    public function send(CrmMailAccount $account, array $draft): CrmMailMessage
    {
        if (!$account->is_active) {
            throw new \RuntimeException('This mailbox is disabled.');
        }
        $to = $this->cleanAddresses($draft['to'] ?? []);
        $cc = $this->cleanAddresses($draft['cc'] ?? []);
        $bcc = $this->cleanAddresses($draft['bcc'] ?? []);
        if (!$to && !$cc && !$bcc) {
            throw new \RuntimeException('Add at least one recipient.');
        }

        $replyTo = $draft['reply_to_message'] ?? null;
        $forwardOf = $draft['forward_of'] ?? null;
        $subject = trim((string) ($draft['subject'] ?? '')) ?: '(no subject)';
        $bodyHtml = $this->sanitizer->sanitize((string) ($draft['html'] ?? '')) ?? '';
        $signature = trim((string) $account->signature);
        $fullHtml = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#1f2937">' . $bodyHtml
            . ($signature !== '' ? '<br><br>' . $signature : '') . '</div>';
        $text = trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>|</div>#i', "\n", $fullHtml)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $domain = substr(strrchr((string) $account->email_address, '@'), 1) ?: 'crm.local';
        $messageId = '<crm-' . Str::uuid()->toString() . '@' . $domain . '>';

        // Real threading headers (never self-referential).
        $inReplyTo = $replyTo?->message_id;
        $references = null;
        if ($replyTo) {
            $refs = MailThreader::referenceIds($replyTo->in_reply_to, $replyTo->references_header);
            if ($replyTo->message_id) $refs[] = $replyTo->message_id;
            $references = implode(' ', array_values(array_unique(array_filter($refs))));
        }

        $email = (new Email())
            ->from(new Address($account->email_address, (string) ($account->display_name ?: $account->email_address)))
            ->replyTo(new Address($account->email_address))
            ->subject($subject)
            ->html($fullHtml)
            ->text($text !== '' ? $text : ' ');
        foreach ($to as $a) $email->addTo($a);
        foreach ($cc as $a) $email->addCc($a);
        foreach ($bcc as $a) $email->addBcc($a);
        $headers = $email->getHeaders();
        $headers->addIdHeader('Message-ID', trim($messageId, '<>'));
        if ($inReplyTo) $headers->addTextHeader('In-Reply-To', $inReplyTo);
        if ($references) $headers->addTextHeader('References', $references);

        /** @var array<int, array{name:string,mime:string,size:int,tmp:string}> $files */
        $files = [];
        foreach (($draft['attachments'] ?? []) as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $email->attachFromPath($file->getRealPath(), $file->getClientOriginalName(), $file->getMimeType() ?: 'application/octet-stream');
                $files[] = ['name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType() ?: 'application/octet-stream', 'size' => (int) $file->getSize(), 'tmp' => $file->getRealPath()];
            }
        }
        // Forward: carry the original (non-inline) attachments along.
        if ($forwardOf) {
            foreach (CrmMailAttachment::where('message_id', $forwardOf->id)->where('is_inline', false)->get() as $att) {
                $disk = Storage::disk($att->disk ?: 'local');
                if ($disk->exists($att->path)) {
                    $email->attach($disk->get($att->path), $att->original_name, $att->mime_type ?: 'application/octet-stream');
                    $files[] = ['name' => $att->original_name, 'mime' => $att->mime_type ?: 'application/octet-stream', 'size' => (int) $att->size, 'copy_from' => [$att->disk ?: 'local', $att->path]];
                }
            }
        }

        // ---- deliver through THIS mailbox's SMTP ------------------------------------------
        try {
            (new Mailer($account->smtpTransport()))->send($email);
        } catch (\Throwable $e) {
            Log::warning('Mail send failed', ['account_id' => $account->id, 'to' => $to, 'error' => $e->getMessage()]);
            throw new \RuntimeException($this->safeSmtpError($e->getMessage()));
        }

        // ---- record (Sent folder) + attachments + lead link ---------------------------------
        $leadId = $draft['crm_email_id'] ?? $replyTo?->crm_email_id ?? null;
        $sentBy = $draft['sent_by'] ?? null;

        $message = DB::transaction(function () use ($account, $replyTo, $to, $cc, $bcc, $subject, $fullHtml, $text, $messageId, $inReplyTo, $references, $files, $leadId, $sentBy) {
            $sentFolder = CrmMailFolder::withoutGlobalScopes()->where('account_id', $account->id)->where('type', 'sent')->first()
                ?: CrmMailFolder::withoutGlobalScopes()->firstOrCreate(['account_id' => $account->id, 'path' => 'INBOX.Sent'], ['name' => 'Sent', 'type' => 'sent']);

            $message = new CrmMailMessage([
                'account_id' => $account->id,
                'folder_id' => $sentFolder->id,
                'crm_email_id' => $leadId,
                'uid' => null,
                'message_id' => $messageId,
                'in_reply_to' => $inReplyTo,
                'references_header' => $references,
                'subject' => $subject,
                'from_name' => $account->display_name ?: $account->email_address,
                'from_email' => $account->email_address,
                'to_json' => array_map(fn ($e) => ['name' => null, 'email' => $e], $to),
                'cc_json' => $cc ? array_map(fn ($e) => ['name' => null, 'email' => $e], $cc) : null,
                'bcc_json' => $bcc ? array_map(fn ($e) => ['name' => null, 'email' => $e], $bcc) : null,
                'reply_to' => $account->email_address,
                'text_body' => $text,
                'html_body' => $fullHtml,
                'snippet' => HtmlSanitizerService::snippet($text, $fullHtml),
                'has_attachments' => count($files) > 0,
                'is_read' => true,
                'is_starred' => false,
                'is_draft' => false,
                'is_outgoing' => true,
                'received_at' => now(),
                'sent_at' => now(),
            ]);
            if ($replyTo && $replyTo->thread_id) {
                $message->thread_id = $replyTo->thread_id;
            } else {
                $this->threader->assign($account, $message, ['in_reply_to' => $inReplyTo, 'references' => $references, 'message_id' => $messageId, 'subject' => $subject, 'date' => now()]);
            }
            $message->save();

            foreach ($files as $i => $f) {
                $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $f['name']) ?: ('file-' . $i);
                $path = sprintf('mail/%d/%d/%d-%s', $account->id, $message->id, $i, Str::limit($safe, 110, ''));
                if (isset($f['copy_from'])) {
                    Storage::disk(ImapSyncService::DISK)->put($path, Storage::disk($f['copy_from'][0])->get($f['copy_from'][1]));
                } else {
                    Storage::disk(ImapSyncService::DISK)->put($path, file_get_contents($f['tmp']));
                }
                CrmMailAttachment::create([
                    'message_id' => $message->id, 'original_name' => Str::limit($f['name'], 250, ''), 'mime_type' => $f['mime'],
                    'size' => $f['size'], 'disk' => ImapSyncService::DISK, 'path' => $path, 'content_id' => null, 'is_inline' => false,
                ]);
            }

            if ($message->thread_id && ($thread = $message->thread()->withoutGlobalScopes()->first())) {
                $this->threader->refresh($thread);
            }
            app(ImapSyncService::class)->refreshFolderCounts($sentFolder);

            // Mirror into the legacy lead thread so the CRM workflow stays in sync.
            if ($leadId && ($lead = CrmEmail::withoutGlobalScopes()->find($leadId))) {
                CrmMessage::create([
                    'crm_email_id' => $lead->id,
                    'sender_type' => 'admin',
                    'crm_user_id' => $sentBy?->id ?? $account->crm_user_id,
                    'message_body' => $fullHtml,
                    'message_id' => $messageId,
                    'is_read' => true,
                ]);
                if (!in_array($lead->status, self::PROTECTED_STATUSES, true) && $lead->status !== 'Responded') {
                    $old = $lead->status;
                    $lead->forceFill(['status' => 'Responded'])->saveQuietly();
                    try {
                        CrmStatusLog::create(['crm_email_id' => $lead->id, 'user_name' => $sentBy?->name ?? ($account->display_name ?: $account->email_address), 'old_status' => $old, 'new_status' => 'Responded']);
                    } catch (\Throwable $e) { /* log table optional */ }
                }
                if (!$lead->mail_account_id) $lead->forceFill(['mail_account_id' => $account->id])->saveQuietly();
                $lead->touch();
            }
            return $message;
        });

        // ---- IMAP Sent copy (best effort; the Sent-folder sync dedupes by Message-ID) --------
        try {
            $client = new ImapClient($account);
            $client->appendMessage($email->toString());
            $client->close();
        } catch (\Throwable $e) {
            Log::info('Sent copy not appended to IMAP', ['account_id' => $account->id, 'error' => $e->getMessage()]);
        }

        return $message;
    }

    /** @return string[] valid, unique, lower-cased addresses */
    public function cleanAddresses(array|string $list): array
    {
        if (is_string($list)) $list = preg_split('/[,;\s]+/', $list) ?: [];
        $out = [];
        foreach ($list as $raw) {
            $raw = trim((string) $raw, " \t\n\r<>");
            if ($raw === '') continue;
            if (preg_match('/<([^>]+)>/', $raw, $m)) $raw = $m[1];
            $addr = strtolower($raw);
            if (filter_var($addr, FILTER_VALIDATE_EMAIL)) $out[$addr] = $addr;
        }
        return array_values($out);
    }

    private function safeSmtpError(string $raw): string
    {
        $r = strtolower($raw);
        if (str_contains($r, '535') || str_contains($r, 'auth') || str_contains($r, 'password')) return 'The mailbox rejected the login — update its password in mailbox settings.';
        if (str_contains($r, '550') || str_contains($r, '553') || str_contains($r, '554') || str_contains($r, 'reject')) return 'The mail server rejected the message (sender/recipient not allowed).';
        if (str_contains($r, 'timed out') || str_contains($r, 'timeout') || str_contains($r, 'connect')) return 'Could not reach the mail server — try again in a moment.';
        if (str_contains($r, 'certificate') || str_contains($r, 'tls') || str_contains($r, 'ssl')) return 'Secure connection to the mail server failed — check the SMTP encryption setting.';
        return 'Sending failed. Please try again.';
    }
}
