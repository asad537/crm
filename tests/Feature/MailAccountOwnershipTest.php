<?php

namespace Tests\Feature;

use App\CrmMailAccount;
use App\CrmUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Mailboxes are private: another CSR can never read, change, test or delete them,
 * secrets never appear in API responses, and SSRF-style hosts are rejected.
 * Runs against the real DB inside a transaction (project convention). No network.
 */
class MailAccountOwnershipTest extends TestCase
{
    private const WORKSPACE_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function makeUser(string $role): CrmUser
    {
        $user = CrmUser::create([
            'name' => 'Test ' . $role,
            'email' => 'test-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('secret-login'),
            'role' => $role,
        ]);
        $user->workspaces()->attach(self::WORKSPACE_ID, ['role' => $role]);
        return $user;
    }

    private function makeAccount(CrmUser $owner, array $overrides = []): CrmMailAccount
    {
        return CrmMailAccount::create(array_merge([
            'workspace_id' => self::WORKSPACE_ID,
            'crm_user_id' => $owner->id,
            'email_address' => 'owner-' . uniqid() . '@example.org',
            'provider' => 'hostinger',
            'imap_host' => 'imap.hostinger.com', 'imap_port' => 993, 'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.hostinger.com', 'smtp_port' => 587, 'smtp_encryption' => 'tls',
            'email_user' => 'owner@example.org',
            'email_pass' => 'SuperSecretMailboxPass!',
            'is_active' => true, 'is_default' => true,
        ], $overrides));
    }

    private function as(CrmUser $user)
    {
        return $this->actingAs($user, 'crm')->withSession(['crm_workspace_id' => self::WORKSPACE_ID]);
    }

    public function test_another_csr_cannot_touch_someone_elses_account(): void
    {
        $owner = $this->makeUser('sales');
        $other = $this->makeUser('sales');
        $account = $this->makeAccount($owner);

        $this->as($other)->putJson(route('crm.mail.accounts.update', $account->id), [
            'email_address' => $account->email_address, 'provider' => 'hostinger', 'verify' => false,
        ])->assertStatus(403);
        $this->as($other)->postJson(route('crm.mail.accounts.default', $account->id))->assertStatus(403);
        $this->as($other)->postJson(route('crm.mail.accounts.toggle', $account->id), ['field' => 'is_active'])->assertStatus(403);
        $this->as($other)->postJson(route('crm.mail.accounts.test_existing', $account->id))->assertStatus(403);
        $this->as($other)->deleteJson(route('crm.mail.accounts.destroy', $account->id))->assertStatus(403);

        // and it is not even listed for them
        $ids = collect($this->as($other)->getJson(route('crm.mail.accounts.index'))->assertOk()->json('accounts'))->pluck('id');
        $this->assertFalse($ids->contains($account->id));
    }

    public function test_secrets_never_leave_the_server(): void
    {
        $owner = $this->makeUser('sales');
        $account = $this->makeAccount($owner);

        $response = $this->as($owner)->getJson(route('crm.mail.accounts.index'))->assertOk();
        $json = $response->json('accounts');
        $this->assertCount(1, $json);
        $this->assertArrayNotHasKey('email_pass', $json[0]);
        $this->assertArrayNotHasKey('oauth_access_token', $json[0]);
        $this->assertTrue($json[0]['is_own']);
        $this->assertTrue($json[0]['has_password']);
        $this->assertStringNotContainsString('SuperSecretMailboxPass', $response->getContent());

        // stored encrypted at rest, decrypts via the model
        $raw = DB::table('crm_mail_accounts')->where('id', $account->id)->value('email_pass');
        $this->assertStringNotContainsString('SuperSecretMailboxPass', (string) $raw);
        $this->assertSame('SuperSecretMailboxPass!', $account->fresh()->email_pass);
    }

    public function test_admin_sees_shared_accounts_read_only(): void
    {
        $owner = $this->makeUser('sales');
        $admin = $this->makeUser('admin');
        $shared = $this->makeAccount($owner);
        $private = $this->makeAccount($owner, ['is_default' => false, 'share_with_admin' => false]);

        $list = collect($this->as($admin)->getJson(route('crm.mail.accounts.index'))->assertOk()->json('accounts'));
        $this->assertTrue($list->pluck('id')->contains($shared->id));
        $this->assertFalse($list->pluck('id')->contains($private->id));
        $this->assertFalse($list->firstWhere('id', $shared->id)['is_own']);

        $this->as($admin)->putJson(route('crm.mail.accounts.update', $shared->id), [
            'email_address' => $shared->email_address, 'provider' => 'hostinger', 'verify' => false,
        ])->assertStatus(403);
        $this->as($admin)->deleteJson(route('crm.mail.accounts.destroy', $shared->id))->assertStatus(403);
    }

    public function test_store_rejects_private_hosts_and_bad_ports_without_network(): void
    {
        $owner = $this->makeUser('sales');

        $this->as($owner)->postJson(route('crm.mail.accounts.store'), [
            'email_address' => 'me@example.org', 'provider' => 'custom',
            'imap_host' => '127.0.0.1', 'imap_port' => 993, 'smtp_host' => '10.0.0.5', 'smtp_port' => 587,
            'email_pass' => 'x', 'verify' => false,
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['imap_host', 'smtp_host']]);

        $this->as($owner)->postJson(route('crm.mail.accounts.store'), [
            'email_address' => 'me@example.org', 'provider' => 'custom',
            'imap_host' => 'imap.example.org', 'imap_port' => 8080, 'smtp_host' => 'smtp.example.org', 'smtp_port' => 587,
            'email_pass' => 'x', 'verify' => false,
        ])->assertStatus(422)->assertJsonValidationErrors(['imap_port']);
    }

    public function test_owner_can_create_without_verification_and_first_account_becomes_default(): void
    {
        $owner = $this->makeUser('sales');

        $created = $this->as($owner)->postJson(route('crm.mail.accounts.store'), [
            'email_address' => 'new-' . uniqid() . '@gmail.com', 'provider' => 'gmail',
            'email_pass' => 'app-password', 'verify' => false,
        ])->assertStatus(201)->json('account');

        $this->assertTrue($created['is_default']);
        $this->assertSame('imap.gmail.com', $created['imap_host']);
        $this->assertSame(993, $created['imap_port']);
        $this->assertArrayNotHasKey('email_pass', $created);

        // second account is not default; set-default flips exactly one
        $second = $this->as($owner)->postJson(route('crm.mail.accounts.store'), [
            'email_address' => 'second-' . uniqid() . '@gmail.com', 'provider' => 'gmail',
            'email_pass' => 'app-password', 'verify' => false,
        ])->assertStatus(201)->json('account');
        $this->assertFalse($second['is_default']);

        $this->as($owner)->postJson(route('crm.mail.accounts.default', $second['id']))->assertOk();
        $this->assertSame(1, CrmMailAccount::withoutGlobalScopes()->where('crm_user_id', $owner->id)->where('is_default', true)->count());
        $this->assertTrue((bool) CrmMailAccount::withoutGlobalScopes()->find($second['id'])->is_default);
    }
}
