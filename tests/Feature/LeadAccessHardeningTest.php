<?php

namespace Tests\Feature;

use App\CrmEmail;
use App\CrmMailAccount;
use App\CrmMessage;
use App\CrmUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 7 hardening: lead messages/attachments/sending are limited to people who may
 * access the lead; user management is isolated per workspace; mailbox lists are
 * isolated per workspace. Real DB inside a transaction; no network.
 */
class LeadAccessHardeningTest extends TestCase
{
    private const WS1 = 1;
    private const WS2 = 2;

    protected function setUp(): void { parent::setUp(); DB::beginTransaction(); }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    private function user(string $role, int $ws = self::WS1): CrmUser
    {
        $u = CrmUser::create(['name' => 'H '.$role, 'email' => 'h-'.uniqid('', true).'@example.com', 'password' => Hash::make('x'), 'role' => $role]);
        $u->workspaces()->attach($ws, ['role' => $role]);
        return $u;
    }

    private function lead(CrmUser $owner): CrmEmail
    {
        $lead = CrmEmail::withoutGlobalScopes()->create([
            'workspace_id' => self::WS1, 'client_name' => 'Client', 'client_email' => 'c-'.uniqid().'@example.net',
            'product_name' => 'Boxes', 'subject' => 'Boxes', 'status' => 'New', 'assigned_to' => $owner->id, 'created_by' => $owner->id, 'source' => 'form',
        ]);
        CrmMessage::create(['crm_email_id' => $lead->id, 'sender_type' => 'client', 'message_body' => 'hello', 'is_read' => false]);
        return $lead;
    }

    private function as(CrmUser $u, int $ws = self::WS1) { return $this->actingAs($u, 'crm')->withSession(['crm_workspace_id' => $ws]); }

    public function test_lead_messages_and_sending_require_access_to_the_lead(): void
    {
        $owner = $this->user('sales'); $other = $this->user('sales'); $manager = $this->user('sales_manager');
        $lead = $this->lead($owner);

        $this->as($other)->getJson(route('crm.messages.fetch', $lead->id))->assertStatus(403);
        $this->as($other)->postJson(route('crm.messages.send', $lead->id), ['message_body' => 'hi'])->assertStatus(403);

        $this->as($owner)->getJson(route('crm.messages.fetch', $lead->id))->assertOk()->assertJsonCount(1);
        $this->as($manager)->getJson(route('crm.messages.fetch', $lead->id))->assertOk();

        // nothing was recorded for the rejected send
        $this->assertSame(1, CrmMessage::where('crm_email_id', $lead->id)->count());
    }

    public function test_lead_attachments_in_private_storage_are_served_only_to_authorised_users(): void
    {
        $owner = $this->user('sales'); $other = $this->user('sales');
        $lead = $this->lead($owner);
        $dir = storage_path('app/crm_attachments/'.$lead->id);
        @mkdir($dir, 0755, true);
        $name = 'secret-'.uniqid().'.txt';
        file_put_contents($dir.'/'.$name, 'top secret');
        try {
            $this->as($other)->get(route('crm.attachments.show', [$lead->id, $name]))->assertStatus(403);
            $resp = $this->as($owner)->get(route('crm.attachments.show', [$lead->id, $name]))->assertOk();
            $this->assertStringContainsString('nosniff', (string) $resp->headers->get('X-Content-Type-Options'));
            $this->assertStringContainsString('attachment', (string) $resp->headers->get('Content-Disposition'));
        } finally {
            @unlink($dir.'/'.$name);
            @rmdir($dir);
        }
    }

    public function test_user_management_is_isolated_per_workspace(): void
    {
        $adminWs2 = $this->user('admin', self::WS2);
        $salesWs1 = $this->user('sales', self::WS1);

        $this->as($adminWs2, self::WS2)->get(route('crm.users.edit', $salesWs1->id))->assertStatus(403);
        $this->as($adminWs2, self::WS2)->put(route('crm.users.update', $salesWs1->id), ['name' => 'X', 'email' => $salesWs1->email, 'role' => 'sales'])->assertStatus(403);
        $this->as($adminWs2, self::WS2)->delete(route('crm.users.destroy', $salesWs1->id))->assertStatus(403);
        $this->assertNotNull(CrmUser::find($salesWs1->id));

        // a sales manager may not delete a non-sales user even if the legacy role column says "sales"
        $manager = $this->user('sales_manager');
        $designer = CrmUser::create(['name' => 'D', 'email' => 'd-'.uniqid().'@example.com', 'password' => Hash::make('x'), 'role' => 'sales']);
        $designer->workspaces()->attach(self::WS1, ['role' => 'designer']);
        $this->as($manager)->delete(route('crm.users.destroy', $designer->id))->assertRedirect();
        $this->assertNotNull(CrmUser::find($designer->id));
    }

    public function test_legacy_test_connection_rejects_private_hosts(): void
    {
        $admin = $this->user('admin');
        $this->as($admin)->postJson(route('crm.users.test_connection'), ['imap_host' => '127.0.0.1', 'imap_port' => 993, 'email_user' => 'a@b.c', 'email_pass' => 'x'])
            ->assertOk()->assertJsonPath('success', false)->assertJsonFragment(['message' => 'Mail server host points to a private or reserved network address.']);
    }

    public function test_mailboxes_are_isolated_per_workspace(): void
    {
        $admin = $this->user('admin');            // admin in workspace 1
        $ownerWs2 = $this->user('sales', self::WS2);
        CrmMailAccount::create([
            'workspace_id' => self::WS2, 'crm_user_id' => $ownerWs2->id, 'email_address' => 'ws2-'.uniqid().'@example.org', 'provider' => 'hostinger',
            'imap_host' => 'imap.hostinger.com', 'imap_port' => 993, 'imap_encryption' => 'ssl', 'smtp_host' => 'smtp.hostinger.com', 'smtp_port' => 587,
            'smtp_encryption' => 'tls', 'email_user' => 'x', 'email_pass' => 'y', 'is_active' => true, 'is_default' => true, 'share_with_admin' => true,
        ]);
        $ids = collect($this->as($admin)->getJson(route('crm.mail.accounts.index'))->assertOk()->json('accounts'))->pluck('workspace_id')->unique()->all();
        $this->assertNotContains(self::WS2, $ids, 'A workspace-1 admin must not see workspace-2 mailboxes');
        $counts = $this->as($admin)->getJson(route('crm.mail.folders'))->assertOk()->json('counts');
        $this->assertIsArray($counts);
    }
}
