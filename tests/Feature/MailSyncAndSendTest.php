<?php

namespace Tests\Feature;

use App\CrmEmail;
use App\CrmMailAccount;
use App\CrmMailAttachment;
use App\CrmMailFolder;
use App\CrmMailMessage;
use App\CrmMessage;
use App\CrmUser;
use App\Services\Mail\ImapClient;
use App\Services\Mail\ImapClientFactory;
use App\Services\Mail\ImapSyncService;
use App\Services\Mail\MailTransportFactory;
use App\Services\Mail\OutgoingMailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/**
 * Phase 8: sync idempotency + UIDVALIDITY reset, header threading, lead linking + legacy mirror
 * (exactly once), send success/failure through a faked transport. IMAP and SMTP are faked — no
 * network. Real DB inside a transaction; attachments on a faked disk.
 */
class MailSyncAndSendTest extends TestCase
{
    private const WS = 1;
    private FakeImap $imap;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        Storage::fake('local');
        $this->imap = new FakeImap();
        $imap = $this->imap;
        $this->app->instance(ImapClientFactory::class, new class($imap) extends ImapClientFactory {
            public function __construct(private FakeImap $fake) {}
            public function make(CrmMailAccount $account): ImapClient { $this->fake->bind($account); return $this->fake; }
        });
    }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    private function owner(): CrmUser
    {
        $u = CrmUser::create(['name' => 'Owner', 'email' => 'o-'.uniqid('', true).'@example.com', 'password' => Hash::make('x'), 'role' => 'sales']);
        $u->workspaces()->attach(self::WS, ['role' => 'sales']);
        return $u;
    }

    private function account(CrmUser $owner): CrmMailAccount
    {
        return CrmMailAccount::create([
            'workspace_id' => self::WS, 'crm_user_id' => $owner->id, 'email_address' => 'me@acme.test', 'display_name' => 'Me',
            'provider' => 'custom', 'imap_host' => 'imap.acme.test', 'imap_port' => 993, 'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.acme.test', 'smtp_port' => 587, 'smtp_encryption' => 'tls', 'email_user' => 'me@acme.test',
            'email_pass' => 'pw', 'is_active' => true, 'is_default' => true, 'signature' => '<p>-- Me</p>',
        ]);
    }

    private function msg(int $uid, array $o = []): array
    {
        return array_merge([
            'uid' => $uid, 'message_id' => "<m{$uid}@client.test>", 'in_reply_to' => null, 'references' => null,
            'subject' => "Subject {$uid}", 'from_name' => 'Client', 'from_email' => 'client@client.test',
            'to' => [['name' => null, 'email' => 'me@acme.test']], 'cc' => [], 'bcc' => [], 'reply_to' => null,
            'date' => new \DateTimeImmutable(sprintf('2026-10-01 10:%02d:00', $uid % 60)), 'text' => "body {$uid}", 'html' => "<p>body <b>{$uid}</b></p><script>alert(1)</script>",
            'attachments' => [], 'seen' => false, 'flagged' => false, 'raw_headers' => "Subject: Subject {$uid}\r\n", 'size' => 100,
        ], $o);
    }

    public function test_sync_is_idempotent_threads_by_headers_and_survives_uidvalidity_reset(): void
    {
        $acc = $this->account($this->owner());
        $this->imap->folders = [['path' => 'INBOX', 'name' => 'INBOX', 'type' => 'inbox', 'delimiter' => '.', 'selectable' => true],
                                ['path' => 'INBOX.Sent', 'name' => 'Sent', 'type' => 'sent', 'delimiter' => '.', 'selectable' => true]];
        $this->imap->status = ['INBOX' => ['uidvalidity' => 111, 'uidnext' => 4, 'messages' => 3, 'unseen' => 3], 'INBOX.Sent' => ['uidvalidity' => 222, 'uidnext' => 1, 'messages' => 0, 'unseen' => 0]];
        $this->imap->messages['INBOX'] = [
            1 => $this->msg(1),
            2 => $this->msg(2, ['in_reply_to' => '<m1@client.test>', 'references' => '<m1@client.test>', 'subject' => 'Re: Subject 1',
                                 'attachments' => [['name' => 'quote.pdf', 'mime' => 'application/pdf', 'content' => '%PDF-1.4 fake', 'size' => 13, 'cid' => null, 'inline' => false]]]),
            3 => $this->msg(3), // unrelated thread
        ];

        $sync = app(ImapSyncService::class);
        $r1 = $sync->syncAccount($acc);
        $this->assertSame('ok', $r1['status'], json_encode($r1));
        $this->assertSame(3, $r1['imported']);

        $msgs = CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->orderBy('uid')->get();
        $this->assertCount(3, $msgs);
        $this->assertSame($msgs[0]->thread_id, $msgs[1]->thread_id, 'reply joins the parent thread');
        $this->assertNotSame($msgs[0]->thread_id, $msgs[2]->thread_id);
        $this->assertStringNotContainsString('<script', (string) $msgs[0]->html_body, 'html is sanitised');
        $this->assertTrue((bool) $msgs[1]->has_attachments);
        $att = CrmMailAttachment::where('message_id', $msgs[1]->id)->first();
        $this->assertNotNull($att);
        Storage::disk('local')->assertExists($att->path);
        $inbox = CrmMailFolder::withoutGlobalScopes()->where('account_id', $acc->id)->where('path', 'INBOX')->first();
        $this->assertSame(3, (int) $inbox->last_uid);
        $this->assertSame(3, (int) $inbox->unread_count);

        // second run: nothing new, nothing duplicated
        $r2 = $sync->syncAccount($acc);
        $this->assertSame(0, $r2['imported']);
        $this->assertSame(3, CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->count());

        // UIDVALIDITY changes: cursor resets, the same Message-IDs are skipped, a genuinely new one is imported
        $this->imap->status['INBOX']['uidvalidity'] = 999;
        $this->imap->messages['INBOX'] = [10 => $this->msg(1, ['uid' => 10]), 11 => $this->msg(2, ['uid' => 11]), 12 => $this->msg(3, ['uid' => 12]), 13 => $this->msg(13)];
        $r3 = $sync->syncAccount($acc);
        $this->assertSame(1, $r3['imported'], json_encode($r3));
        $this->assertSame(4, CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->count());
        $this->assertSame(999, (int) $inbox->fresh()->uidvalidity);
    }

    public function test_inbound_reply_to_a_crm_sent_message_links_the_lead_and_mirrors_once(): void
    {
        $owner = $this->owner();
        $acc = $this->account($owner);
        $lead = CrmEmail::withoutGlobalScopes()->create(['workspace_id' => self::WS, 'client_name' => 'C', 'client_email' => 'client@client.test', 'product_name' => 'Boxes', 'subject' => 'Boxes', 'status' => 'Responded', 'assigned_to' => $owner->id, 'created_by' => $owner->id, 'source' => 'form']);
        CrmMessage::create(['crm_email_id' => $lead->id, 'sender_type' => 'admin', 'crm_user_id' => $owner->id, 'message_body' => 'our quote', 'message_id' => '<crm-abc@acme.test>', 'is_read' => true]);

        $this->imap->folders = [['path' => 'INBOX', 'name' => 'INBOX', 'type' => 'inbox', 'delimiter' => '.', 'selectable' => true]];
        $this->imap->status = ['INBOX' => ['uidvalidity' => 1, 'uidnext' => 2, 'messages' => 1, 'unseen' => 1]];
        $this->imap->messages['INBOX'] = [5 => $this->msg(5, ['in_reply_to' => '<crm-abc@acme.test>', 'text' => "Thanks, we accept.\n\nOn Mon, Client wrote:\n> our quote"])];

        $sync = app(ImapSyncService::class);
        $r = $sync->syncAccount($acc);
        $this->assertSame(1, $r['linked']);
        $this->assertSame(1, $r['mirrored']);
        $m = CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->first();
        $this->assertSame($lead->id, (int) $m->crm_email_id);
        $mirror = CrmMessage::where('crm_email_id', $lead->id)->where('sender_type', 'client')->get();
        $this->assertCount(1, $mirror);
        $this->assertSame('Thanks, we accept.', $mirror[0]->message_body, 'quoted history stripped');
        $this->assertSame('Client Replied', $lead->fresh()->status);

        $sync->syncAccount($acc); // again → still one mirror
        $this->assertSame(1, CrmMessage::where('crm_email_id', $lead->id)->where('sender_type', 'client')->count());
    }

    public function test_send_failure_stores_nothing_and_surfaces_a_safe_message(): void
    {
        $acc = $this->account($this->owner());
        $this->app->instance(MailTransportFactory::class, new class extends MailTransportFactory {
            public function for(CrmMailAccount $a): TransportInterface {
                return new class implements TransportInterface {
                    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage { throw new \RuntimeException('535 5.7.8 Authentication failed: bad password hunter2'); }
                    public function __toString(): string { return 'fail://'; }
                };
            }
        });
        try {
            app(OutgoingMailService::class)->send($acc, ['to' => ['client@client.test'], 'subject' => 'Hi', 'html' => '<p>x</p>']);
            $this->fail('expected failure');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('hunter2', $e->getMessage());
            $this->assertStringContainsString('rejected the login', $e->getMessage());
        }
        $this->assertSame(0, CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->count());
        $this->assertSame(0, CrmMailAttachment::whereIn('message_id', CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->pluck('id'))->count());
    }

    public function test_send_success_records_sent_copy_threads_and_mirrors_lead(): void
    {
        $owner = $this->owner();
        $acc = $this->account($owner);
        $this->app->instance(MailTransportFactory::class, new class extends MailTransportFactory {
            public function for(CrmMailAccount $a): TransportInterface { return new NullTransport(); }
        });
        $lead = CrmEmail::withoutGlobalScopes()->create(['workspace_id' => self::WS, 'client_name' => 'C', 'client_email' => 'client@client.test', 'product_name' => 'Boxes', 'subject' => 'Boxes', 'status' => 'New', 'assigned_to' => $owner->id, 'created_by' => $owner->id, 'source' => 'form']);
        $inbox = CrmMailFolder::create(['account_id' => $acc->id, 'name' => 'Inbox', 'path' => 'INBOX', 'type' => 'inbox']);
        $original = CrmMailMessage::create(['account_id' => $acc->id, 'folder_id' => $inbox->id, 'uid' => 1, 'message_id' => '<orig@client.test>', 'references_header' => '<root@client.test>',
            'subject' => 'Boxes', 'from_email' => 'client@client.test', 'to_json' => [['name' => null, 'email' => 'me@acme.test']], 'text_body' => 'hi', 'received_at' => now(), 'crm_email_id' => $lead->id]);
        app(\App\Services\Mail\MailThreader::class)->assign($acc, $original, ['message_id' => '<orig@client.test>', 'subject' => 'Boxes', 'date' => now()]); $original->save();

        $sent = app(OutgoingMailService::class)->send($acc, ['to' => ['client@client.test'], 'subject' => 'Re: Boxes', 'html' => '<p>Thanks</p>', 'reply_to_message' => $original, 'sent_by' => $owner]);

        $this->assertTrue((bool) $sent->is_outgoing);
        $this->assertSame('<orig@client.test>', $sent->in_reply_to);
        $this->assertStringContainsString('<root@client.test>', (string) $sent->references_header);
        $this->assertStringContainsString('<orig@client.test>', (string) $sent->references_header);
        $this->assertSame($original->thread_id, $sent->thread_id);
        $this->assertSame('sent', $sent->folder()->withoutGlobalScopes()->first()->type);
        $this->assertStringContainsString('-- Me', (string) $sent->html_body, 'account signature appended');
        $this->assertSame($lead->id, (int) $sent->crm_email_id);
        $this->assertSame(1, CrmMessage::where('crm_email_id', $lead->id)->where('sender_type', 'admin')->count(), 'mirrored into the lead thread');
        $this->assertSame('Responded', $lead->fresh()->status);
        $this->assertCount(1, $this->imap->appended, 'a Sent copy was appended to IMAP');
    }
}

/** In-memory IMAP double. */
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
    public function listFolders(): array { return $this->folders; }
    public function status(string $folderPath): array { return $this->status[$folderPath] ?? ['uidvalidity' => 1, 'uidnext' => 1, 'messages' => 0, 'unseen' => 0]; }
    public function searchUids(string $folderPath, int $afterUid, int $sinceDays = 30, int $cap = 200): array
    {
        $uids = array_keys($this->messages[$folderPath] ?? []);
        sort($uids);
        return array_values(array_filter($uids, fn ($u) => $u > $afterUid));
    }
    public function fetchMessage(string $folderPath, int $uid): array { return $this->messages[$folderPath][$uid]; }
    public function setFlag(string $folderPath, int $uid, string $flag, bool $on = true): bool { return true; }
    public function findFolderPathByType(string $type): ?string { foreach ($this->folders as $f) if ($f['type'] === $type) return $f['path']; return null; }
    public function appendMessage(string $mime, ?string $folderPath = null, string $flags = '\\Seen'): ?string { $this->appended[] = $mime; return $folderPath ?: 'INBOX.Sent'; }
}
