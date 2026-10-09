<?php

namespace Tests\Feature;

use App\CrmUser;
use App\Notifications\CrmResetPassword;
use App\Services\Auth\MailboxPasswordVerifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Login step B: local password first; the legacy mailbox check is only a guarded fallback for
 * sales-only accounts (and re-hashes on success); forgot/reset works through the crm broker;
 * change-password is now effective for sales users. The IMAP verifier is faked — no network.
 */
class CrmLoginStepBTest extends TestCase
{
    private const WS = 1;
    private $fakeVerifier;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->fakeVerifier = new class extends MailboxPasswordVerifier {
            public bool $answer = false;
            public array $calls = [];
            public function verify(CrmUser $user, string $email, string $password): bool { $this->calls[] = $email; return $this->answer; }
        };
        $this->app->instance(MailboxPasswordVerifier::class, $this->fakeVerifier);
        config(['crm.auth.imap_login_fallback' => true]);
    }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    private function user(string $role, string $password = 'Local#Pass1'): CrmUser
    {
        // real-looking domain so the fallback is eligible (example.com is excluded by design)
        $u = CrmUser::create(['name' => 'B '.$role, 'email' => 'b-'.uniqid('', true).'@stepb-test.org', 'password' => Hash::make($password), 'role' => $role]);
        $u->workspaces()->attach(self::WS, ['role' => $role]);
        return $u;
    }

    public function test_sales_user_logs_in_with_local_password_without_touching_imap(): void
    {
        $u = $this->user('sales');
        $this->post(route('crm.login'), ['email' => $u->email, 'password' => 'Local#Pass1'])->assertRedirect();
        $this->assertAuthenticatedAs($u, 'crm');
        $this->assertSame([], $this->fakeVerifier->calls);
        $this->assertNotNull($u->fresh()->last_login_at);
    }

    public function test_sales_user_with_stale_hash_falls_back_to_mailbox_and_self_heals(): void
    {
        $u = $this->user('sales', 'OldLocal#1');
        $this->fakeVerifier->answer = true; // mailbox accepts the new password
        $this->post(route('crm.login'), ['email' => $u->email, 'password' => 'NewMailbox#2'])->assertRedirect();
        $this->assertAuthenticatedAs($u, 'crm');
        $this->assertCount(1, $this->fakeVerifier->calls);
        $fresh = $u->fresh();
        $this->assertTrue(Hash::check('NewMailbox#2', $fresh->password), 'fallback must re-hash locally');
        $this->assertNotNull($fresh->imap_login_fallback_at);
    }

    public function test_fallback_is_disabled_by_config_and_never_used_for_non_sales(): void
    {
        $sales = $this->user('sales');
        config(['crm.auth.imap_login_fallback' => false]);
        $this->fakeVerifier->answer = true;
        $this->post(route('crm.login'), ['email' => $sales->email, 'password' => 'wrong'])->assertRedirect()->assertSessionHas('error');
        $this->assertGuest('crm');
        $this->assertSame([], $this->fakeVerifier->calls);

        config(['crm.auth.imap_login_fallback' => true]);
        $designer = $this->user('designer');
        $this->post(route('crm.login'), ['email' => $designer->email, 'password' => 'wrong'])->assertRedirect()->assertSessionHas('error');
        $this->assertGuest('crm');
        $this->assertSame([], $this->fakeVerifier->calls, 'non-sales roles never hit the mailbox');
    }

    public function test_forgot_and_reset_password_flow_uses_the_crm_broker(): void
    {
        Notification::fake();
        $u = $this->user('sales');
        $this->post(route('crm.password.email'), ['email' => $u->email])->assertRedirect()->assertSessionHas('status');
        Notification::assertSentTo($u, CrmResetPassword::class);

        $token = Password::broker('crm')->createToken($u);
        $this->get(route('crm.password.reset', ['token' => $token, 'email' => $u->email]))->assertOk()->assertSee('Set a new password');
        $this->post(route('crm.password.update'), ['token' => $token, 'email' => $u->email, 'password' => 'Fresh#Pass9', 'password_confirmation' => 'Fresh#Pass9'])
            ->assertRedirect(route('crm.login'))->assertSessionHas('status');
        $this->assertTrue(Hash::check('Fresh#Pass9', $u->fresh()->password));
        $this->assertNotNull($u->fresh()->password_changed_at);

        // unknown email gets the same neutral response
        $this->post(route('crm.password.email'), ['email' => 'nobody-'.uniqid().'@stepb-test.org'])->assertRedirect()->assertSessionHas('status');
    }

    public function test_change_password_is_now_effective_for_sales_users(): void
    {
        $u = $this->user('sales');
        $this->actingAs($u, 'crm')->withSession(['crm_workspace_id' => self::WS])
            ->post(route('crm.change_password.update'), ['current_password' => 'Local#Pass1', 'password' => 'Changed#Pass2', 'password_confirmation' => 'Changed#Pass2'])
            ->assertRedirect();
        auth('crm')->logout();
        $this->post(route('crm.login'), ['email' => $u->email, 'password' => 'Changed#Pass2'])->assertRedirect();
        $this->assertAuthenticatedAs($u, 'crm');
        $this->assertSame([], $this->fakeVerifier->calls);
    }
}
