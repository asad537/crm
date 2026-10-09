<?php

namespace Tests\Feature;

use App\CrmMailAccount;
use App\CrmMailFolder;
use App\CrmMailMessage;
use App\CrmUser;
use App\Services\Mail\ImapClient;
use App\Services\Mail\ImapClientFactory;
use App\Services\Mail\ImapSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 6: CRM → server actions (archive/trash/junk/move/delete, read/star flags) and
 * server → CRM reconciliation (flags, moves done in another client, deletions). Faked IMAP.
 */
class MailActionsAndReconcileTest extends TestCase
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
            public function make(CrmMailAccount $account): ImapClient { return $this->fake; }
        });
        $this->imap->folders = [
            ['path' => 'INBOX', 'name' => 'INBOX', 'type' => 'inbox', 'delimiter' => '.', 'selectable' => true],
            ['path' => 'INBOX.Trash', 'name' => 'Trash', 'type' => 'trash', 'delimiter' => '.', 'selectable' => true],
            ['path' => 'INBOX.Junk', 'name' => 'Junk', 'type' => 'junk', 'delimiter' => '.', 'selectable' => true],
        ];
        $this->imap->status = ['INBOX' => ['uidvalidity' => 1, 'uidnext' => 10, 'messages' => 2, 'unseen' => 2],
            'INBOX.Trash' => ['uidvalidity' => 2, 'uidnext' => 1, 'messages' => 0, 'unseen' => 0], 'INBOX.Junk' => ['uidvalidity' => 3, 'uidnext' => 1, 'messages' => 0, 'unseen' => 0]];
    }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    private function user(string $role): CrmUser
    {
        $u = CrmUser::create(['name' => 'U', 'email' => 'u-'.uniqid('', true).'@example.com', 'password' => Hash::make('x'), 'role' => $role]);
        $u->workspaces()->attach(self::WS, ['role' => $role]);
        return $u;
    }
    private function account(CrmUser $owner): CrmMailAccount
    {
        return CrmMailAccount::create(['workspace_id' => self::WS, 'crm_user_id' => $owner->id, 'email_address' => 'me@acme.test', 'provider' => 'custom',
            'imap_host' => 'imap.acme.test', 'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.acme.test', 'smtp_port' => 587, 'smtp_encryption' => 'tls',
            'email_user' => 'me@acme.test', 'email_pass' => 'pw', 'is_active' => true, 'is_default' => true, 'share_with_admin' => true]);
    }
    private function msg(int $uid, array $o = []): array
    {
        return array_merge(['uid' => $uid, 'message_id' => "<m{$uid}@c.test>", 'in_reply_to' => null, 'references' => null, 'subject' => "S{$uid}", 'from_name' => 'C', 'from_email' => 'c@c.test',
            'to' => [['name' => null, 'email' => 'me@acme.test']], 'cc' => [], 'bcc' => [], 'reply_to' => null, 'date' => new \DateTimeImmutable(sprintf('2026-10-01 09:%02d:00', $uid % 60)),
            'text' => "t{$uid}", 'html' => null, 'attachments' => [], 'seen' => false, 'flagged' => false, 'raw_headers' => '', 'size' => 10], $o);
    }
    private function as(CrmUser $u) { return $this->actingAs($u, 'crm')->withSession(['crm_workspace_id' => self::WS]); }

    private function seedInbox(CrmMailAccount $acc): void
    {
        $this->imap->messages['INBOX'] = [1 => $this->msg(1), 2 => $this->msg(2)];
        app(ImapSyncService::class)->syncAccount($acc);
    }

    public function test_owner_actions_update_db_and_push_to_imap_and_archive_is_created_on_demand(): void
    {
        $owner = $this->user('sales'); $acc = $this->account($owner); $this->seedInbox($acc);
        $m1 = CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->where('uid', 1)->first();
        $m2 = CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->where('uid', 2)->first();

        // star + read push flags
        $this->as($owner)->postJson(route('crm.mail.messages.star', $m1->id))->assertOk()->assertJsonPath('is_starred', true);
        $this->as($owner)->getJson(route('crm.mail.messages.show', $m1->id))->assertOk()->assertJsonPath('message.is_read', true)->assertJsonPath('message.can_act', true);
        $flags = array_map(fn ($c) => $c[2].'='.var_export($c[3], true), $this->imap->flagCalls);
        $this->assertContains('\\Flagged=true', $flags);
        $this->assertContains('\\Seen=true', $flags);

        // archive: no Archive folder on the server → created, message moved there, uid cleared until re-found
        $this->as($owner)->postJson(route('crm.mail.messages.move', $m1->id), ['to' => 'archive'])->assertOk()->assertJsonPath('folder_type', 'archive');
        $this->assertSame(['INBOX.Archive'], $this->imap->created);
        $this->assertSame([['INBOX', 1, 'INBOX.Archive']], $this->imap->moves);
        $this->assertNull($m1->fresh()->uid);
        $this->assertSame('archive', $m1->fresh()->folder()->withoutGlobalScopes()->first()->type);

        // next sync re-finds it in Archive by Message-ID: pointer updated, no duplicate
        $this->imap->status['INBOX.Archive'] = ['uidvalidity' => 4, 'uidnext' => 2000, 'messages' => 1, 'unseen' => 0];
        app(ImapSyncService::class)->syncAccount($acc);
        $this->assertSame(2, CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->count());
        $this->assertNotNull($m1->fresh()->uid);

        // trash then permanent delete
        $this->as($owner)->postJson(route('crm.mail.messages.move', $m2->id), ['to' => 'trash'])->assertOk()->assertJsonPath('folder_type', 'trash');
        $this->as($owner)->deleteJson(route('crm.mail.messages.destroy', $m2->id))->assertOk();
        $this->assertNotNull(CrmMailMessage::withTrashed()->withoutGlobalScopes()->find($m2->id)->deleted_at);

        // a refused server move leaves the DB untouched
        $this->imap->refuseMoves = true;
        $this->as($owner)->postJson(route('crm.mail.messages.move', $m1->id), ['to' => 'junk'])->assertStatus(422);
        $this->assertSame('archive', $m1->fresh()->folder()->withoutGlobalScopes()->first()->type);
    }

    public function test_admin_read_only_view_never_changes_mailbox_state(): void
    {
        $owner = $this->user('sales'); $admin = $this->user('admin'); $acc = $this->account($owner); $this->seedInbox($acc);
        $m1 = CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->where('uid', 1)->first();

        $this->as($admin)->getJson(route('crm.mail.messages.show', $m1->id))->assertOk()->assertJsonPath('message.can_act', false)->assertJsonPath('message.is_read', false);
        $this->assertFalse((bool) $m1->fresh()->is_read);
        $this->assertSame([], $this->imap->flagCalls);
        $this->as($admin)->postJson(route('crm.mail.messages.move', $m1->id), ['to' => 'trash'])->assertStatus(403);
        $this->as($admin)->deleteJson(route('crm.mail.messages.destroy', $m1->id))->assertStatus(403);
        $this->as($admin)->postJson(route('crm.mail.messages.bulk'), ['ids' => [$m1->id], 'action' => 'archive'])->assertOk()->assertJsonPath('done', 0);
    }

    public function test_reconcile_applies_server_flags_moves_and_deletions(): void
    {
        $owner = $this->user('sales'); $acc = $this->account($owner); $this->seedInbox($acc);
        $m1 = CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->where('uid', 1)->first();
        $m2 = CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->where('uid', 2)->first();

        // in Outlook: m1 read + flagged, m2 moved to Junk, and a third message deleted after we stored it
        $this->imap->messages['INBOX'][1]['seen'] = true; $this->imap->messages['INBOX'][1]['flagged'] = true;
        $moved = $this->imap->messages['INBOX'][2]; unset($this->imap->messages['INBOX'][2]); $moved['uid'] = 77; $this->imap->messages['INBOX.Junk'] = [77 => $moved];
        $this->imap->messages['INBOX'][3] = $this->msg(3);
        app(ImapSyncService::class)->syncAccount($acc);
        $m3 = CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->where('uid', 3)->first();
        $this->assertNotNull($m3);
        unset($this->imap->messages['INBOX'][3]);
        CrmMailMessage::withoutGlobalScopes()->where('id', $m3->id)->update(['created_at' => now()->subHours(2)]); // past the orphan grace period
        app(ImapSyncService::class)->syncAccount($acc);

        $this->assertTrue((bool) $m1->fresh()->is_read);
        $this->assertTrue((bool) $m1->fresh()->is_starred);
        $this->assertSame('junk', $m2->fresh()->folder()->withoutGlobalScopes()->first()->type, 'moved on the server → pointer follows');
        $this->assertSame(77, (int) $m2->fresh()->uid);
        $this->assertSame(3, CrmMailMessage::withoutGlobalScopes()->where('account_id', $acc->id)->count(), 'no duplicates');
        $this->assertSame('trash', $m3->fresh()->folder()->withoutGlobalScopes()->first()->type, 'deleted on the server → local Trash');
        $inbox = CrmMailFolder::withoutGlobalScopes()->where('account_id', $acc->id)->where('path', 'INBOX')->first();
        $this->assertSame(1, (int) $inbox->message_count);
        $this->assertSame(0, (int) $inbox->unread_count);
    }
}
