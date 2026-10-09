<?php

namespace Tests\Feature;

use App\CrmEmail;
use App\CrmMailAccount;
use App\CrmMailFolder;
use App\CrmMailMessage;
use App\CrmUser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** crm:mail-migrate-legacy-accounts: creates encrypted accounts, links leads, and rolls back safely. */
class LegacyMailboxMigrationTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); DB::beginTransaction(); }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    public function test_legacy_credentials_become_one_encrypted_account_and_rollback_is_safe(): void
    {
        $email = 'legacy-'.uniqid().'@acme.test';
        $u = CrmUser::create(['name' => 'Legacy', 'email' => 'l-'.uniqid('', true).'@example.com', 'password' => Hash::make('x'), 'role' => 'sales',
            'email_user' => $email, 'email_pass' => 'mailbox-secret', 'imap_host' => 'imap.hostinger.com', 'imap_port' => '993', 'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.hostinger.com', 'smtp_port' => '587', 'smtp_encryption' => 'tls', 'signature' => 'sig']);
        $u->workspaces()->attach(1, ['role' => 'sales']);
        $lead = CrmEmail::withoutGlobalScopes()->create(['workspace_id' => 1, 'client_name' => 'C', 'client_email' => 'c@c.test', 'product_name' => 'P', 'subject' => 'S', 'status' => 'New', 'assigned_to' => $u->id, 'created_by' => $u->id, 'source' => 'form']);

        Artisan::call('crm:mail-migrate-legacy-accounts', ['--dry-run' => true]);
        $this->assertNull(CrmMailAccount::withoutGlobalScopes()->where('email_address', $email)->first(), 'dry run writes nothing');

        Artisan::call('crm:mail-migrate-legacy-accounts');
        $acc = CrmMailAccount::withoutGlobalScopes()->where('email_address', $email)->first();
        $this->assertNotNull($acc);
        $this->assertSame('hostinger', $acc->provider);
        $this->assertTrue((bool) $acc->is_default);
        $this->assertSame('mailbox-secret', $acc->email_pass, 'decrypts through the cast');
        $this->assertStringNotContainsString('mailbox-secret', (string) DB::table('crm_mail_accounts')->where('id', $acc->id)->value('email_pass'), 'encrypted at rest');
        $this->assertSame($acc->id, (int) $lead->fresh()->mail_account_id);
        $this->assertSame('mailbox-secret', $u->fresh()->email_pass, 'legacy columns untouched');

        Artisan::call('crm:mail-migrate-legacy-accounts'); // idempotent
        $this->assertSame(1, CrmMailAccount::withoutGlobalScopes()->where('crm_user_id', $u->id)->count());

        // rollback removes it and unlinks the lead when no mail was synced yet
        Artisan::call('crm:mail-migrate-legacy-accounts', ['--rollback' => true]);
        $this->assertNull(CrmMailAccount::withTrashed()->withoutGlobalScopes()->where('email_address', $email)->first());
        $this->assertNull($lead->fresh()->mail_account_id);

        // …but keeps an account that already holds synced mail
        Artisan::call('crm:mail-migrate-legacy-accounts');
        $acc = CrmMailAccount::withoutGlobalScopes()->where('email_address', $email)->first();
        $f = CrmMailFolder::create(['account_id' => $acc->id, 'name' => 'Inbox', 'path' => 'INBOX', 'type' => 'inbox']);
        CrmMailMessage::create(['account_id' => $acc->id, 'folder_id' => $f->id, 'uid' => 1, 'subject' => 'x', 'received_at' => now()]);
        Artisan::call('crm:mail-migrate-legacy-accounts', ['--rollback' => true]);
        $this->assertNotNull(CrmMailAccount::withoutGlobalScopes()->find($acc->id), 'kept because it holds mail');
    }
}
