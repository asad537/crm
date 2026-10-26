<?php

namespace Tests\Feature\Accounting;

use App\CrmUser;
use App\CrmWorkspace;
use App\Http\Middleware\RequireCrmWorkspaceSlug;
use App\Services\FinancialReportService;
use App\Support\CrmWorkspaceContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Base for the Account Department tests.
 *
 * Every test runs inside a DB transaction (rolled back afterwards) in a brand-new, empty workspace, so the
 * reports only see the rows the test itself creates and no real data is touched. The accounting routes are
 * limited to the Al Massa workspace slug; that middleware is switched off here so the fresh workspace can use them.
 */
abstract class AccountingTestCase extends TestCase
{
    protected CrmWorkspace $workspace;
    protected CrmUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        Carbon::setTestNow(Carbon::parse('2026-10-26 10:00:00'));
        $this->withoutMiddleware(RequireCrmWorkspaceSlug::class);

        $this->workspace = CrmWorkspace::create(['name' => 'Accounts Test', 'slug' => 'accounts-test-'.uniqid(), 'is_active' => true]);
        CrmWorkspaceContext::set($this->workspace->id);
        $this->admin = $this->makeUser('admin');
        $this->signIn($this->admin);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        CrmWorkspaceContext::clear();
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** A CRM user with the given role on the test workspace. */
    protected function makeUser(string $role = 'admin', string $name = null): CrmUser
    {
        $user = CrmUser::create([
            'name' => $name ?: ucfirst($role).' Tester',
            'email' => $role.'-'.uniqid('', true).'@example.test',
            'password' => Hash::make('secret'),
            'role' => $role,
        ]);
        $user->workspaces()->attach($this->workspace->id, ['role' => $role]);

        return $user;
    }

    protected function signIn(CrmUser $user): self
    {
        $this->actingAs($user, 'crm')->withSession(['crm_workspace_id' => $this->workspace->id]);

        return $this;
    }

    /** Build one financial report for a date range (defaults to this year up to "today"). */
    protected function report(string $name, string $from = '2026-01-01', string $to = '2026-10-26', array $params = []): array
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return (new FinancialReportService(Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()))
            ->withParams($params)->build($name);
    }

    /** Assert two money amounts match to the cent. */
    protected function assertMoney(float $expected, $actual, string $message = ''): void
    {
        $this->assertEqualsWithDelta($expected, (float) $actual, 0.005, $message);
    }
}
