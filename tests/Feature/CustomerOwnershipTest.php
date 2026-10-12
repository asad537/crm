<?php

namespace Tests\Feature;

use App\CrmEmail;
use App\CrmUser;
use App\Services\CustomerOwnership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** "One customer, one CSR": ownership follows the customer's email address inside a workspace. */
class CustomerOwnershipTest extends TestCase
{
    private const WS = 1;

    protected function setUp(): void { parent::setUp(); DB::beginTransaction(); }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    private function user(string $role): CrmUser
    {
        $u = CrmUser::create(['name' => 'O '.$role.' '.uniqid(), 'email' => 'o-'.uniqid('', true).'@example.com', 'password' => Hash::make('x'), 'role' => $role]);
        $u->workspaces()->attach(self::WS, ['role' => $role]);
        return $u;
    }

    private function as(CrmUser $u) { return $this->actingAs($u, 'crm')->withSession(['crm_workspace_id' => self::WS]); }

    private function lead(array $attrs): CrmEmail
    {
        return CrmEmail::withoutGlobalScopes()->create($attrs + [
            'workspace_id' => self::WS, 'client_name' => 'Cust', 'product_name' => 'Boxes', 'subject' => 'Boxes', 'status' => 'New', 'source' => 'form', 'is_spam' => false,
        ]);
    }

    public function test_new_inquiry_from_a_known_customer_auto_assigns_to_the_owning_agent(): void
    {
        $owner = $this->user('sales');
        $email = 'buyer-'.uniqid().'@example.net';
        $this->lead(['client_email' => $email, 'assigned_to' => $owner->id, 'created_by' => $owner->id]);

        $this->assertSame($owner->id, CustomerOwnership::ownerFor(strtoupper($email), self::WS)?->id, 'lookup is case-insensitive');

        $new = $this->lead(['client_email' => $email]); // arrives unassigned (website / API / mail)
        $this->assertSame($owner->id, (int) $new->fresh()->assigned_to);
        $this->assertSame(1, DB::table('crm_assignment_logs')->where('crm_email_id', $new->id)->where('assigned_to', $owner->id)->count());

        $unknown = $this->lead(['client_email' => 'nobody-'.uniqid().'@example.net']);
        $this->assertNull($unknown->fresh()->assigned_to, 'unknown customers stay unassigned for the manager to distribute');
    }

    public function test_another_agent_cannot_create_an_inquiry_for_someone_elses_customer_but_a_manager_hands_it_to_the_owner(): void
    {
        $owner = $this->user('sales'); $other = $this->user('sales'); $manager = $this->user('sales_manager');
        $email = 'buyer-'.uniqid().'@example.net';
        $this->lead(['client_email' => $email, 'assigned_to' => $owner->id, 'created_by' => $owner->id]);
        $payload = ['source' => 'call', 'client_name' => 'Cust', 'client_email' => $email, 'inquiry_currency' => 'USD', 'product_name' => 'Boxes', 'quantities' => [100], 'inquiry_date' => now()->toDateString()];

        $before = CrmEmail::withoutGlobalScopes()->count();
        $this->as($other)->postJson(route('crm.emails.create_manual'), $payload)->assertStatus(422)->assertJsonValidationErrors(['client_email']);
        $this->assertSame($before, CrmEmail::withoutGlobalScopes()->count());

        $this->as($owner)->postJson(route('crm.emails.create_manual'), $payload)->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertSame($owner->id, (int) CrmEmail::withoutGlobalScopes()->latest('id')->first()->assigned_to);

        $this->as($manager)->postJson(route('crm.emails.create_manual'), $payload)->assertStatus(302)->assertSessionHasNoErrors();
        $this->assertSame($owner->id, (int) CrmEmail::withoutGlobalScopes()->latest('id')->first()->assigned_to, 'manager-created inquiry goes to the owning agent');
    }

    public function test_reassigning_an_owned_customer_is_refused_for_managers_and_allowed_for_admins(): void
    {
        $owner = $this->user('sales'); $other = $this->user('sales'); $manager = $this->user('sales_manager'); $admin = $this->user('admin');
        $email = 'buyer-'.uniqid().'@example.net';
        $this->lead(['client_email' => $email, 'assigned_to' => $owner->id, 'created_by' => $owner->id]);
        $lead = $this->lead(['client_email' => $email]);
        $this->assertSame($owner->id, (int) $lead->assigned_to);

        $this->as($manager)->postJson(route('crm.emails.assign', $lead->id), ['assigned_to' => $other->id])->assertStatus(409);
        $this->assertSame($owner->id, (int) $lead->fresh()->assigned_to);

        $r = $this->as($manager)->postJson(route('crm.emails.bulk_assign'), ['email_ids' => [$lead->id], 'assigned_to' => $other->id])->assertOk();
        $this->assertCount(1, $r->json('skipped'));
        $this->assertSame($owner->id, (int) $lead->fresh()->assigned_to);

        $this->as($admin)->postJson(route('crm.emails.assign', $lead->id), ['assigned_to' => $other->id])->assertOk();
        $this->assertSame($other->id, (int) $lead->fresh()->assigned_to);
    }
}
