<?php

namespace Tests\Feature;

use App\CrmCustomer;
use App\CrmIncomeEntry;
use App\CrmUser;
use App\Services\IncomeDueService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Rent / other-income customers: monthly dues are generated, Accounts/Admin record receipts, sales agents never see them. */
class OtherIncomeTest extends TestCase
{
    private const WS = 1;

    protected function setUp(): void { parent::setUp(); DB::beginTransaction(); }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    private function user(string $role): CrmUser
    {
        $u = CrmUser::create(['name' => 'OI '.$role, 'email' => 'oi-'.uniqid('', true).'@example.com', 'password' => Hash::make('x'), 'role' => $role]);
        $u->workspaces()->attach(self::WS, ['role' => $role]);
        return $u;
    }

    private function as(CrmUser $u) { return $this->actingAs($u, 'crm')->withSession(['crm_workspace_id' => self::WS]); }

    public function test_income_customer_gets_a_due_for_every_month_and_accounts_records_receipts(): void
    {
        $accounts = $this->user('accounts'); $sales = $this->user('sales');
        $start = now()->subMonths(2)->format('Y-m');

        // a sales agent may not create an income customer
        $this->as($sales)->post(route('crm.customers.store'), ['name' => 'Tenant X', 'country' => 'UAE', 'currency' => 'AED', 'customer_type' => 'income', 'income_head' => 'Rent', 'recurring_amount' => 5000, 'recurring_day' => 5, 'recurring_start' => $start])->assertStatus(403);

        $this->as($accounts)->post(route('crm.customers.store'), ['name' => 'Tenant X', 'country' => 'UAE', 'currency' => 'AED', 'customer_type' => 'income', 'income_head' => 'Rent', 'recurring_amount' => 5000, 'recurring_day' => 5, 'recurring_start' => $start])->assertRedirect();
        $c = CrmCustomer::withoutGlobalScopes()->where('name', 'Tenant X')->firstOrFail();
        $this->assertSame('income', $c->customer_type);
        $dues = CrmIncomeEntry::withoutGlobalScopes()->where('customer_id', $c->id)->orderBy('period')->get();
        $this->assertCount(3, $dues, 'start month, last month and this month are due');
        $this->assertSame($start, $dues[0]->period);
        $this->assertSame(5, (int) $dues[0]->due_date->day);
        $this->assertSame('overdue', $dues[0]->status);
        $this->assertSame(5000.0, (float) $dues[0]->amount_due);

        // idempotent: running the generator again adds nothing
        $this->assertSame(0, app(IncomeDueService::class)->ensureDues($c->fresh()));

        // full payment, then a partial on the next month
        $this->as($accounts)->post(route('crm.income.pay', $dues[0]->id), ['amount_paid' => 5000, 'paid_at' => now()->toDateString(), 'payment_method' => 'Bank Transfer', 'reference' => 'TRF-1'])->assertRedirect();
        $this->assertSame('paid', $dues[0]->fresh()->status);
        $this->as($accounts)->post(route('crm.income.pay', $dues[1]->id), ['amount_paid' => 2000])->assertRedirect();
        $this->assertSame('partial', $dues[1]->fresh()->status);
        $this->assertSame(3000.0, $dues[1]->fresh()->balance);

        // sales agent: cannot pay, cannot even see the customer
        $this->as($sales)->post(route('crm.income.pay', $dues[2]->id), ['amount_paid' => 1])->assertStatus(403);
        $this->as($sales)->get(route('crm.customer_sales.index', ['customer_id' => $c->id]))->assertStatus(403);
        $this->as($sales)->get(route('crm.customer_sales.index'))->assertOk()->assertDontSee('Tenant X');

        // accounts sees the monthly dues page with the outstanding figure
        $this->as($accounts)->get(route('crm.customer_sales.index', ['customer_id' => $c->id]))->assertOk()->assertSee('Overdue months')->assertSee('8,000.00');

        // one-off extra month in advance
        $next = now()->addMonth()->format('Y-m');
        $this->as($accounts)->post(route('crm.income.add_period', $c->id), ['period' => $next, 'amount_due' => 5500])->assertRedirect();
        $this->assertSame(5500.0, (float) CrmIncomeEntry::withoutGlobalScopes()->where('customer_id', $c->id)->where('period', $next)->value('amount_due'));

        // the ledger lists them as receivables
        $this->as($accounts)->get(route('crm.general_ledger.index', ['tab' => 'receivable']))->assertOk()->assertSee('Tenant X')->assertSee('Rent');
    }
}
