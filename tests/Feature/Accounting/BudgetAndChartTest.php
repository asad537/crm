<?php

namespace Tests\Feature\Accounting;

use App\CrmBudget;

/** Budget editor and Budget vs Actual, the Chart of Accounts page and the General Ledger (page + Excel / PDF export). */
class BudgetAndChartTest extends AccountingTestCase
{
    use BuildsMoneyIn;

    // ───────────── Budget ─────────────

    public function test_a_yearly_budget_is_saved_per_group_and_category(): void
    {
        $this->post(route('crm.budgets.update'), [
            'year' => 2026,
            'budget' => ['income' => ['Customer Sales' => 12000], 'opex' => ['Rent' => 3000], 'cos' => ['Stock & Raw Material' => '']],
            'new_group' => 'opex', 'new_category' => '  Marketing ', 'new_amount' => 1000,
        ])->assertSessionHasNoErrors()->assertRedirect(route('crm.budgets.edit', ['year' => 2026]))
            ->assertSessionHas('success', 'Budget 2026 saved (3 categories).');

        $saved = CrmBudget::orderBy('category')->get();
        $this->assertSame([['Customer Sales', 'income', 12000.0], ['Marketing', 'opex', 1000.0], ['Rent', 'opex', 3000.0]],
            $saved->map(fn ($b) => [$b->category, $b->group, (float) $b->amount])->all());
        $this->assertSame([$this->workspace->id], $saved->pluck('workspace_id')->map(fn ($v) => (int) $v)->unique()->values()->all());
        $this->assertSame([$this->admin->id], $saved->pluck('updated_by')->map(fn ($v) => (int) $v)->unique()->values()->all());

        // Saving again updates in place; an amount of 0 removes the line.
        $this->post(route('crm.budgets.update'), ['year' => 2026, 'budget' => ['income' => ['Customer Sales' => 15000.456], 'opex' => ['Rent' => 0]]])
            ->assertSessionHas('success', 'Budget 2026 saved (1 category).');
        $this->assertSame(['Customer Sales' => 15000.46, 'Marketing' => 1000.0], CrmBudget::orderBy('category')->get()->mapWithKeys(fn ($b) => [$b->category => (float) $b->amount])->all());

        // The editor shows the saved amounts.
        $groups = $this->get(route('crm.budgets.edit', ['year' => 2026]))->assertOk()->viewData('groups');
        $this->assertSame(15000.46, collect($groups['income']['rows'])->firstWhere('category', 'Customer Sales')['budget']);
        $this->assertSame(1000.0, collect($groups['opex']['rows'])->firstWhere('category', 'Marketing')['budget']);
        $this->assertNull(collect($groups['opex']['rows'])->firstWhere('category', 'Rent'));

        // Another year is kept apart.
        $this->post(route('crm.budgets.update'), ['year' => 2027, 'budget' => ['opex' => ['Rent' => 4000]]]);
        $this->assertSame(2, CrmBudget::where('year', 2026)->count());
        $this->assertSame(1, CrmBudget::where('year', 2027)->count());
    }

    public function test_a_budget_category_with_a_dot_in_its_name_is_saved_as_typed(): void
    {
        $this->post(route('crm.budgets.update'), ['year' => 2026, 'budget' => ['opex' => ['Govt. Fees & Visa' => 500]],
            'new_group' => 'opex', 'new_category' => 'Misc. Expenses', 'new_amount' => 250])->assertSessionHasNoErrors();

        $this->assertSame(['Govt. Fees & Visa' => 500.0, 'Misc. Expenses' => 250.0],
            CrmBudget::orderBy('category')->get()->mapWithKeys(fn ($b) => [$b->category => (float) $b->amount])->all());
    }

    public function test_budget_input_is_validated_and_the_year_is_kept_in_range(): void
    {
        $this->post(route('crm.budgets.update'), ['budget' => ['opex' => ['Rent' => -1]]])->assertSessionHasErrors('budget.opex.Rent');
        $this->post(route('crm.budgets.update'), ['new_group' => 'assets', 'new_category' => 'Cash'])->assertSessionHasErrors('new_group');
        $this->post(route('crm.budgets.update'), ['budget' => ['assets' => ['Cash' => 100]]])->assertSessionHasNoErrors();   // unknown group ignored
        $this->assertSame(0, CrmBudget::count());

        $this->assertSame(2026, $this->get(route('crm.budgets.edit', ['year' => 1999]))->assertOk()->viewData('year'));
        $this->assertSame(2026, $this->get(route('crm.budgets.edit', ['year' => 2032]))->assertOk()->viewData('year'));
        $this->assertSame(2031, $this->get(route('crm.budgets.edit', ['year' => 2031]))->assertOk()->viewData('year'));
    }

    public function test_the_budget_editor_lists_every_category_used_with_last_years_actual(): void
    {
        $customer = $this->createCustomer('Budget Customer');
        $this->createSale($customer, ['order_date' => '2025-03-01', 'quantity' => 10, 'unit_price' => 100]);
        $this->createExpense(['expense_date' => '2025-06-01', 'category' => 'Utilities', 'amount' => 500]);
        $this->createExpense(['expense_date' => '2026-09-05', 'category' => 'Rent', 'amount' => 2000]);

        $groups = $this->get(route('crm.budgets.edit', ['year' => 2026]))->assertOk()->assertSee('Utilities')->viewData('groups');
        $this->assertSame(['category' => 'Customer Sales', 'budget' => null, 'last_year' => 1000.0], collect($groups['income']['rows'])->firstWhere('category', 'Customer Sales'));
        $this->assertSame(['category' => 'Utilities', 'budget' => null, 'last_year' => 500.0], collect($groups['opex']['rows'])->firstWhere('category', 'Utilities'));
        $this->assertSame(['category' => 'Rent', 'budget' => null, 'last_year' => 0.0], collect($groups['opex']['rows'])->firstWhere('category', 'Rent'));
        $this->assertSame([], $groups['cos']['rows']);
    }

    public function test_budget_vs_actual_compares_the_budget_with_the_profit_and_loss(): void
    {
        $customer = $this->createCustomer('BvA Customer');
        $this->createSale($customer, ['order_date' => '2026-09-10', 'quantity' => 10, 'unit_price' => 100, 'tax_amount' => 50]);
        $this->createExpense(['expense_date' => '2026-09-05', 'category' => 'Rent', 'amount' => 2000]);

        $empty = $this->report('budget', '2026-01-01', '2026-12-31');
        $this->assertSame('Set Budget 2026', $empty['actions'][0]['label']);
        $this->assertStringContainsString('No budget is set for 2026', $empty['notes'][0]);

        $this->post(route('crm.budgets.update'), ['year' => 2026, 'budget' => [
            'income' => ['Customer Sales' => 12000], 'opex' => ['Rent' => 3000, 'Marketing' => 1000],
        ]])->assertSessionHasNoErrors();
        $this->post(route('crm.budgets.update'), ['year' => 2025, 'budget' => ['opex' => ['Rent' => 99999]]]);   // other year: not counted

        // Whole year: the full yearly budget.
        $report = $this->get(route('crm.financial_reports.index', ['report' => 'budget', 'period' => 'custom', 'date_from' => '2026-01-01', 'date_to' => '2026-12-31']))
            ->assertOk()->viewData('report');
        $this->assertSame('Edit Budget 2026', $report['actions'][0]['label']);
        $cards = $this->cards($report);
        $this->assertMoney(12000, $cards['Budgeted Income']);
        $this->assertMoney(4000, $cards['Budgeted Costs']);
        $this->assertMoney(8000, $cards['Budgeted Profit']);
        $this->assertMoney(-9000, $cards['Profit vs Budget']);

        $sales = $this->rowNamed($report, 'Customer Sales');
        $this->assertMoney(12000, $sales[1]);
        $this->assertMoney(1000, $sales[2], 'actual income is without VAT');
        $this->assertMoney(-11000, $sales[3]);
        $this->assertEqualsWithDelta(8.333, $sales[4], 0.001);
        $rent = $this->rowNamed($report, 'Rent');
        $this->assertSame([3000.0, 2000.0, 1000.0], [(float) $rent[1], (float) $rent[2], (float) $rent[3]], 'cost below budget is a positive variance');
        $this->assertEqualsWithDelta(66.667, $rent[4], 0.001);
        $marketing = $this->rowNamed($report, 'Marketing');
        $this->assertSame([1000.0, 0.0, 1000.0, 0.0], [(float) $marketing[1], (float) $marketing[2], (float) $marketing[3], (float) $marketing[4]]);
        $this->assertSame(['Total Operating Expenses', 4000.0, 2000.0, 2000.0, 50.0], array_map(fn ($v) => is_string($v) ? $v : (float) $v, $this->rowNamed($report, 'Total Operating Expenses')));
        $this->assertSame(['Net Profit / (Loss)', 8000.0, -1000.0, -9000.0, null], array_map(fn ($v) => is_string($v) || $v === null ? $v : (float) $v, $this->rowNamed($report, 'Net Profit / (Loss)')));

        // One month: that month's share of the year.
        $march = $this->cards($this->report('budget', '2026-03-01', '2026-03-31'));
        $this->assertMoney(12000 * 31 / 365, $march['Budgeted Income']);
        $this->assertMoney(4000 * 31 / 365, $march['Budgeted Costs']);
    }

    public function test_budget_vs_actual_across_two_years_uses_each_years_share(): void
    {
        $this->post(route('crm.budgets.update'), ['year' => 2025, 'budget' => ['opex' => ['Rent' => 3650]]]);
        $this->post(route('crm.budgets.update'), ['year' => 2026, 'budget' => ['opex' => ['Rent' => 7300]]]);

        $report = $this->report('budget', '2025-07-01', '2026-06-30');
        $this->assertMoney(3650 * 184 / 365 + 7300 * 181 / 365, $this->rowNamed($report, 'Rent')[1]);
    }

    public function test_only_admins_and_accounts_set_the_budget(): void
    {
        $this->signIn($this->makeUser('accounts'));
        $this->get(route('crm.budgets.edit'))->assertOk();
        $this->post(route('crm.budgets.update'), ['budget' => ['opex' => ['Rent' => 100]]])->assertRedirect();

        foreach (['sales_manager', 'sales'] as $role) {
            $this->signIn($this->makeUser($role));
            $this->get(route('crm.budgets.edit'))->assertForbidden();
            $this->post(route('crm.budgets.update'), ['budget' => ['opex' => ['Rent' => 999]]])->assertForbidden();
        }
        $this->assertMoney(100, CrmBudget::firstOrFail()->amount);

        // A sales manager may still read the Budget vs Actual report.
        $this->signIn($this->makeUser('sales_manager'));
        $this->get(route('crm.financial_reports.index', ['report' => 'budget']))->assertOk();
    }

    // ───────────── Chart of Accounts ─────────────

    public function test_the_chart_of_accounts_lists_the_accounts_the_reports_use(): void
    {
        $bank = $this->createBankAccount('Chart Bank', ['opening_balance' => 1000]);
        $customer = $this->createCustomer('Chart Customer');
        $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100, 'tax_amount' => 50]);              // 1,050 owed
        $this->createExpense(['category' => 'Rent', 'amount' => 2000, 'bank_account_id' => $bank->id]);
        $this->createPurchaseInvoice($this->createVendor('Chart Vendor'), [['Kraft paper', 'Raw Material', 5, 80]]);   // 400 on credit
        $this->makeDirectReceipt(['category' => 'Scrap Sales', 'amount' => 300, 'bank_account_id' => $bank->id]);
        $this->addCapital('Chart Owner', 5000, ['bank_account_id' => $bank->id]);

        $response = $this->get(route('crm.chart_of_accounts.index'))->assertOk()
            ->assertSee('Chart Bank')->assertSee('Customers (Accounts Receivable)')->assertSee('Scrap Sales')->assertSee('Stock &amp; Raw Material', false);
        $chart = $response->viewData('chart');
        $groups = collect($chart['groups'])->keyBy('key');
        $accounts = fn ($key) => collect($groups[$key]['accounts'])->mapWithKeys(fn ($a) => [$a[0] => round((float) $a[1], 2)])->filter(fn ($v) => abs($v) > 0.004)->all();

        $this->assertSame(['Chart Bank' => 4300.0, 'Customers (Accounts Receivable)' => 1050.0], $accounts('assets'));
        $this->assertSame(['Purchase Invoices Payable' => 400.0, 'VAT Payable / (Refundable)' => 50.0], $accounts('liabilities'));
        $this->assertSame(["Owner's Capital — Chart Owner" => 5000.0, 'Profit / (Loss) 2026 to date' => -1100.0,
            'Opening Balances not recorded (balancing figure)' => 1000.0], $accounts('equity'), 'the bank opening balance is not yet explained');
        $this->assertSame(['Customer Sales' => 1000.0, 'Scrap Sales' => 300.0], $accounts('income'));
        $this->assertSame(['Stock & Raw Material' => 400.0], $accounts('cos'));
        $this->assertSame(['Rent' => 2000.0], $accounts('opex'));
        $this->assertMoney(-1100, $chart['net_profit']);
        $this->assertTrue($chart['has_bank_accounts']);
        $this->assertSame('01 Jan 2026 – 26 Oct 2026', $chart['period']);

        // Each account opens its entries: bank → its statement, P&L categories → the drill-down.
        $this->assertSame(route('crm.bank_accounts.show', $bank->id), $groups['assets']['accounts'][0][2]);
        $rentUrl = collect($groups['opex']['accounts'])->firstWhere(0, 'Rent')[2];
        $this->assertStringContainsString('group=opex', $rentUrl);
        $this->assertStringContainsString('category=Rent', $rentUrl);
        $this->get($rentUrl)->assertOk()->assertSee('Landlord LLC');

        // Same totals as the Balance Sheet report.
        $sheet = $this->cards($this->report('balance-sheet'));
        $this->assertMoney($sheet['Total Assets'], $groups['assets']['total']);
        $this->assertMoney($sheet['Total Liabilities'], $groups['liabilities']['total']);
        $this->assertMoney($sheet['Equity (Net Worth)'], $groups['equity']['total']);
        $this->assertMoney(5350, $groups['assets']['total']);
    }

    public function test_only_admins_and_accounts_open_the_chart_of_accounts(): void
    {
        $this->signIn($this->makeUser('accounts'));
        $this->get(route('crm.chart_of_accounts.index'))->assertOk();
        foreach (['sales_manager', 'sales'] as $role) {
            $this->signIn($this->makeUser($role));
            $this->get(route('crm.chart_of_accounts.index'))->assertForbidden();
        }
    }

    // ───────────── General Ledger ─────────────

    /** Two customer sales (one part paid) and a purchase invoice with a part payment. */
    protected function ledgerEntries(): array
    {
        $a = $this->createCustomer('GL Customer A');
        $b = $this->createCustomer('GL Customer B');
        $saleA = $this->createSale($a, ['order_date' => '2026-09-10', 'item_name' => 'Printed Boxes', 'quantity' => 10, 'unit_price' => 100, 'tax_amount' => 50, 'paid_amount' => 400]);
        $saleB = $this->createSale($b, ['order_date' => '2026-07-15', 'item_name' => 'Paper Bags', 'quantity' => 2, 'unit_price' => 250]);
        $invoice = $this->createPurchaseInvoice($this->createVendor('GL Vendor'), [['Kraft paper', 'Raw Material', 5, 80]], ['issue_date' => '2026-08-15', 'reference' => 'SUP-77']);
        $this->post(route('crm.purchase_invoices.payments.store', $invoice->id), ['amount' => 100, 'paid_at' => '2026-09-01', 'method' => 'Cash'])->assertSessionHasNoErrors();

        return [$saleA, $saleB, $invoice];
    }

    protected function ledgerRows(array $query = [])
    {
        return collect($this->get(route('crm.general_ledger.index', $query))->assertOk()->viewData('entries')->items());
    }

    public function test_the_general_ledger_lists_receivables_and_payables_with_totals(): void
    {
        [$saleA, $saleB, $invoice] = $this->ledgerEntries();

        $response = $this->get(route('crm.general_ledger.index'))->assertOk()->assertSee('GL Customer A')->assertSee('GL Vendor');
        $rows = collect($response->viewData('entries')->items());
        $this->assertSame([$saleA->order_number, $invoice->number . ' · SUP-77', $saleB->order_number], $rows->pluck('ref')->all(), 'newest first');
        $this->assertSame(['receivable', 'payable', 'receivable'], $rows->pluck('type')->all());
        $this->assertSame([[1050.0, 400.0, 650.0, 'partial'], [400.0, 100.0, 300.0, 'partial'], [500.0, 0.0, 500.0, 'pending']],
            $rows->map(fn ($r) => [(float) $r->total, (float) $r->paid, (float) $r->balance, $r->status])->all());
        $this->assertSame('Purchase Invoice — Kraft paper', $rows[1]->detail);
        $this->assertSame('Cash', $rows[1]->term);

        $this->assertSame(['total' => 1550.0, 'received' => 400.0, 'pending' => 1150.0, 'count' => 2],
            array_map(fn ($v) => is_int($v) ? $v : (float) $v, $response->viewData('receivableTotals')));
        $this->assertSame(['total' => 400.0, 'paid' => 100.0, 'balance' => 300.0, 'count' => 1],
            array_map(fn ($v) => is_int($v) ? $v : (float) $v, $response->viewData('payableTotals')));
        $this->assertSame(['GL Customer A', 'GL Customer B', 'GL Vendor'], $response->viewData('partyOptions')->values()->all());

        // Tabs and filters.
        $this->assertSame(2, $this->ledgerRows(['tab' => 'receivable'])->count());
        $this->assertSame(['GL Vendor'], $this->ledgerRows(['tab' => 'payable'])->pluck('party')->all());
        $this->assertSame(['GL Vendor'], $this->ledgerRows(['tab' => 'nonsense'])->pluck('party')->unique()->values()->all(), 'unknown tab → payables');
        $this->assertSame(['GL Customer A', 'GL Customer B'], $this->ledgerRows(['tab' => 'receivable', 'status' => 'pending'])->pluck('party')->all(), 'pending = anything with a balance');
        $this->assertSame([$saleA->order_number], $this->ledgerRows(['tab' => 'receivable', 'status' => 'partial'])->pluck('ref')->all());
        $this->assertSame(['payable'], $this->ledgerRows(['status' => 'payable'])->pluck('type')->unique()->values()->all());
        $this->assertSame([$saleB->order_number], $this->ledgerRows(['party' => 'GL Customer B'])->pluck('ref')->all());
        $this->assertSame([$saleB->order_number], $this->ledgerRows(['search' => 'paper bags'])->pluck('ref')->all());
        $this->assertSame(2, $this->ledgerRows(['date_from' => '2026-08-01'])->count());
        $this->assertSame(2, $this->ledgerRows(['date_to' => '2026-08-31'])->count());
    }

    public function test_the_general_ledger_exports_to_excel_and_pdf(): void
    {
        [$saleA] = $this->ledgerEntries();

        // No party picked: one summary line per customer / vendor.
        $excel = $this->get(route('crm.general_ledger.export', ['tab' => 'all']))->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.ms-excel')
            ->assertHeader('Content-Disposition', 'attachment; filename="general-ledger-all-parties-summary-2026-10-26.xls"');
        $html = $excel->getContent();
        $this->assertStringContainsString('<td>GL Customer A</td>', $html);
        $this->assertStringContainsString('<td>1050.00</td>', $html);
        $this->assertStringContainsString('<td>1950.00</td>', $html, 'grand total of all entries');
        $this->assertStringContainsString('<td>1450.00</td>', $html, 'grand total balance');

        // One party: every entry.
        $detail = $this->get(route('crm.general_ledger.export', ['tab' => 'receivable', 'party' => 'GL Customer A']))->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="general-ledger-receivable-2026-10-26.xls"');
        $this->assertStringContainsString($saleA->order_number, $detail->getContent());
        $this->assertStringContainsString('Total: 1,050.00', $detail->getContent());
        $this->assertStringNotContainsString('GL Customer B', $detail->getContent());

        $this->get(route('crm.general_ledger.export', ['tab' => 'payable', 'format' => 'pdf']))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="general-ledger-payable-vendors-summary-2026-10-26.pdf"');
        $this->get(route('crm.general_ledger.export', ['tab' => 'receivable', 'party' => 'GL Customer A', 'format' => 'pdf']))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Nothing matches: back to the ledger with a message.
        $this->get(route('crm.general_ledger.export', ['tab' => 'receivable', 'party' => 'Nobody', 'format' => 'pdf']))
            ->assertRedirect(route('crm.general_ledger.index', ['tab' => 'receivable', 'party' => 'Nobody']))
            ->assertSessionHas('error', 'No ledger entries match the selected filters.');
    }

    public function test_a_sales_agent_cannot_open_the_general_ledger(): void
    {
        $this->signIn($this->makeUser('sales_manager'));
        $this->get(route('crm.general_ledger.index'))->assertOk();
        $this->signIn($this->makeUser('sales'));
        $this->get(route('crm.general_ledger.index'))->assertForbidden();
        $this->get(route('crm.general_ledger.export'))->assertForbidden();
    }
}
