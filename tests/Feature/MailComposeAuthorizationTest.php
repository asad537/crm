<?php

namespace Tests\Feature;

use App\CrmMailAccount;
use App\CrmMailFolder;
use App\CrmMailMessage;
use App\CrmUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Only the mailbox owner can send from it; admins (read-only) and other CSRs get 403,
 * disabled mailboxes refuse, and validation runs before any SMTP attempt. No network.
 */
class MailComposeAuthorizationTest extends TestCase
{
    private const WORKSPACE_ID = 1;

    protected function setUp(): void { parent::setUp(); DB::beginTransaction(); }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    private function makeUser(string $role): CrmUser
    {
        $u = CrmUser::create(['name' => 'T '.$role, 'email' => 't-'.uniqid('', true).'@example.com', 'password' => Hash::make('x'), 'role' => $role]);
        $u->workspaces()->attach(self::WORKSPACE_ID, ['role' => $role]);
        return $u;
    }

    private function makeAccount(CrmUser $owner, array $o = []): CrmMailAccount
    {
        return CrmMailAccount::create(array_merge([
            'workspace_id' => self::WORKSPACE_ID, 'crm_user_id' => $owner->id, 'email_address' => 'o-'.uniqid().'@example.org',
            'provider' => 'hostinger', 'imap_host' => 'imap.hostinger.com', 'imap_port' => 993, 'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.hostinger.com', 'smtp_port' => 587, 'smtp_encryption' => 'tls', 'email_user' => 'o@example.org',
            'email_pass' => 'pw', 'is_active' => true, 'is_default' => true,
        ], $o));
    }

    private function makeMessage(CrmMailAccount $a): CrmMailMessage
    {
        $f = CrmMailFolder::create(['account_id' => $a->id, 'name' => 'Inbox', 'path' => 'INBOX', 'type' => 'inbox']);
        return CrmMailMessage::create(['account_id' => $a->id, 'folder_id' => $f->id, 'uid' => 1, 'message_id' => '<m-'.uniqid().'@x>',
            'subject' => 'Hello', 'from_email' => 'client@example.net', 'from_name' => 'Client', 'to_json' => [['name' => null, 'email' => $a->email_address]],
            'text_body' => 'hi', 'received_at' => now()]);
    }

    private function as(CrmUser $u) { return $this->actingAs($u, 'crm')->withSession(['crm_workspace_id' => self::WORKSPACE_ID]); }

    public function test_non_owner_and_admin_cannot_send_from_a_mailbox(): void
    {
        $owner = $this->makeUser('sales'); $other = $this->makeUser('sales'); $admin = $this->makeUser('admin');
        $acc = $this->makeAccount($owner); $msg = $this->makeMessage($acc);

        foreach ([$other, $admin] as $u) {
            $this->as($u)->postJson(route('crm.mail.messages.reply', $msg->id), ['mode' => 'reply', 'body' => 'x'])->assertStatus(403);
            $this->as($u)->postJson(route('crm.mail.compose'), ['account_id' => $acc->id, 'to' => 'a@example.net', 'body' => 'x'])->assertStatus(403);
        }
    }

    public function test_validation_runs_before_sending(): void
    {
        $owner = $this->makeUser('sales'); $acc = $this->makeAccount($owner); $msg = $this->makeMessage($acc);

        $this->as($owner)->postJson(route('crm.mail.compose'), ['account_id' => $acc->id])->assertStatus(422)->assertJsonValidationErrors(['to']);
        $this->as($owner)->postJson(route('crm.mail.messages.reply', $msg->id), ['mode' => 'bogus', 'body' => 'x'])->assertStatus(422);
        $this->as($owner)->postJson(route('crm.mail.messages.reply', $msg->id), ['mode' => 'forward', 'body' => 'x'])->assertStatus(422)->assertJsonValidationErrors(['to']);
    }

    public function test_disabled_mailbox_refuses_to_send(): void
    {
        $owner = $this->makeUser('sales'); $acc = $this->makeAccount($owner, ['is_active' => false]); $msg = $this->makeMessage($acc);
        $this->as($owner)->postJson(route('crm.mail.messages.reply', $msg->id), ['mode' => 'reply', 'body' => 'x'])->assertStatus(403);
    }
}
