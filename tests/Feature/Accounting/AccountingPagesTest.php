<?php

namespace Tests\Feature\Accounting;

use App\Services\FinancialReportService;

/** Every Account Department page and report opens, for an empty workspace, and the empty books are all zero. */
class AccountingPagesTest extends AccountingTestCase
{
    public function test_every_accounting_page_opens_for_an_admin(): void
    {
        $pages = [
            'crm.payments.index', 'crm.receipts.index', 'crm.fixed_assets.index',
            'crm.purchase_invoices.index', 'crm.purchase_invoices.create', 'crm.employees.index', 'crm.payroll.index',
            'crm.bank_accounts.index', 'crm.bank_accounts.unassigned', 'crm.budgets.edit', 'crm.chart_of_accounts.index',
            'crm.owner_capital.index', 'crm.general_ledger.index', 'crm.inventory.index', 'crm.customer_sales.index',
        ];
        foreach ($pages as $name) {
            $response = $this->get(route($name));
            $this->assertSame(200, $response->status(), $name.' → '.$response->headers->get('Location').' '.session('error'));
        }
        // Expenses live on the Payments page.
        $this->get(route('crm.expenses.index'))->assertRedirect(route('crm.payments.index', ['source' => 'expense']));
    }

    public function test_every_financial_report_opens_and_exports(): void
    {
        foreach (array_keys(FinancialReportService::REPORTS) as $report) {
            $this->get(route('crm.financial_reports.index', ['report' => $report]))->assertOk();
            $this->get(route('crm.financial_reports.export', ['report' => $report]))->assertOk()
                ->assertHeader('Content-Type', 'application/vnd.ms-excel');
        }
        foreach (array_keys(\App\Http\Controllers\Crm\FinancialReportController::PERIODS) as $period) {
            if ($period === 'custom') continue;
            $this->get(route('crm.financial_reports.index', ['report' => 'profit-loss', 'period' => $period]))->assertOk();
        }
        $this->get(route('crm.financial_reports.index', ['report' => 'balance-sheet', 'period' => 'custom', 'date_from' => '2026-01-01', 'date_to' => '2026-06-30']))->assertOk();
        $this->get(route('crm.financial_reports.index', ['report' => 'nope']))->assertNotFound();
    }

    public function test_a_pdf_export_downloads(): void
    {
        $this->get(route('crm.financial_reports.export', ['report' => 'profit-loss', 'format' => 'pdf']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_empty_books_are_all_zero_and_balance(): void
    {
        $sheet = $this->report('balance-sheet');
        foreach ($sheet['cards'] as $card) {
            $this->assertMoney(0, $card['value'], $card['label']);
        }
        $trial = collect($this->report('trial-balance')['cards'])->keyBy('label');
        $this->assertMoney(0, $trial['Difference']['value']);
    }

    public function test_a_sales_agent_cannot_open_financial_reports(): void
    {
        $this->signIn($this->makeUser('sales'));
        $this->get(route('crm.financial_reports.index'))->assertForbidden();
    }
}
