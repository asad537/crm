<?php

namespace Tests\Feature\Accounting;

use App\CrmBankAccount;
use App\CrmPurchaseInvoice;
use App\CustomerSale;

/**
 * The financial statements and reports for one known set of books, built through the real pages:
 *
 *   01 Feb  Owner A puts 50,000 capital into Main Bank (bank transfer)
 *   15 Jul  Customer sale to Customer Y: 2 × 250 = 500, no VAT, unpaid
 *   15 Aug  Purchase invoice from Statement Vendor: 5 × 80 Raw Material = 400, on credit
 *   05 Sep  Rent paid from Main Bank: 2,000
 *   10 Sep  Customer sale to Customer X: 10 × 100 = 1,000 + 50 VAT = 1,050
 *   20 Sep  Customer X pays 600 into Main Bank (Make Receipt)
 *   20 Oct  Scrap sold for 300 cash, put into Main Bank (Make Receipt → Other income)
 *
 * Income 1,800 (sales 1,500 + scrap 300), cost of sales 400, rent 2,000 → net loss 600.
 * Main Bank 50,000 + 600 + 300 − 2,000 = 48,900; customers owe 950; we owe the vendor 400 and VAT 50.
 */
class FinancialStatementsTest extends AccountingTestCase
{
    use BuildsMoneyIn;

    protected CrmBankAccount $bank;
    protected CustomerSale $saleX;
    protected CustomerSale $saleY;
    protected CrmPurchaseInvoice $invoice;
    protected \App\CrmExpense $rent;

    const X = 'Statement Customer X';
    const Y = 'Statement Customer Y';
    const VENDOR = 'Statement Vendor';

    protected function setUp(): void
    {
        parent::setUp();
        $this->bank = $this->createBankAccount('Main Bank');
        $this->addCapital('Owner A', 50000, ['entry_date' => '2026-02-01', 'bank_account_id' => $this->bank->id]);

        $this->saleY = $this->createSale($this->createCustomer(self::Y), ['order_date' => '2026-07-15', 'item_name' => 'Paper Bags', 'quantity' => 2, 'unit_price' => 250]);
        $this->invoice = $this->createPurchaseInvoice($this->createVendor(self::VENDOR), [['Kraft paper', 'Raw Material', 5, 80]], ['issue_date' => '2026-08-15']);
        $this->rent = $this->createExpense(['expense_date' => '2026-09-05', 'category' => 'Rent', 'amount' => 2000, 'payee_name' => 'Landlord LLC', 'bank_account_id' => $this->bank->id]);
        $this->saleX = $this->createSale($this->createCustomer(self::X), ['order_date' => '2026-09-10', 'item_name' => 'Printed Boxes', 'quantity' => 10, 'unit_price' => 100, 'tax_amount' => 50]);
        $this->receive(['doc_type' => 'sale', 'doc_id' => $this->saleX->id, 'amount' => 600, 'paid_at' => '2026-09-20', 'method' => 'Bank Transfer', 'bank_account_id' => $this->bank->id])
            ->assertSessionHasNoErrors();
        $this->makeDirectReceipt(['receipt_date' => '2026-10-20', 'category' => 'Scrap Sales', 'amount' => 300, 'method' => 'Cash', 'bank_account_id' => $this->bank->id]);
    }

    /** Numbers in a row as floats (text and nulls kept). */
    protected function nums(?array $row): ?array
    {
        return $row === null ? null : array_map(fn ($v) => is_int($v) || is_float($v) ? round((float) $v, 2) : $v, $row);
    }

    public function test_profit_and_loss(): void
    {
        $pl = $this->get(route('crm.financial_reports.index', ['report' => 'profit-loss']))->assertOk()->viewData('report');
        $cards = $this->cards($pl);
        $this->assertMoney(1800, $cards['Total Income']);
        $this->assertMoney(2400, $cards['Total Costs']);
        $this->assertMoney(1400, $cards['Gross Profit']);
        $this->assertMoney(-600, $cards['Net Profit / (Loss)']);
        $this->assertSame('Net margin -33.3%', collect($pl['cards'])->firstWhere('label', 'Net Profit / (Loss)')['note']);

        $this->assertMoney(1500, $this->rowNamed($pl, 'Customer Sales')[1], 'sales without VAT');
        $this->assertMoney(300, $this->rowNamed($pl, 'Scrap Sales')[1]);
        $this->assertMoney(1800, $this->rowNamed($pl, 'Total Income')[1]);
        $this->assertMoney(400, $this->rowNamed($pl, 'Stock & Raw Material')[1], 'raw material on a purchase invoice is cost of sales');
        $this->assertMoney(400, $this->rowNamed($pl, 'Total Cost of Sales')[1]);
        $this->assertMoney(2000, $this->rowNamed($pl, 'Rent')[1]);
        $this->assertMoney(2000, $this->rowNamed($pl, 'Total Operating Expenses')[1]);
        $this->assertMoney(-600, $this->rowNamed($pl, 'Net Profit / (Loss)')[1]);

        // Month by month.
        $months = collect($this->tableRows($pl, 1))->keyBy(0);
        $this->assertSame(['Jul 2026', 500.0, 0.0, 0.0, 500.0], $this->nums($months['Jul 2026']));
        $this->assertSame(['Aug 2026', 0.0, 400.0, 0.0, -400.0], $this->nums($months['Aug 2026']));
        $this->assertSame(['Sep 2026', 1000.0, 0.0, 2000.0, -1000.0], $this->nums($months['Sep 2026']));
        $this->assertSame(['Oct 2026', 300.0, 0.0, 0.0, 300.0], $this->nums($months['Oct 2026']));
        $this->assertSame(['Total', 1800.0, 400.0, 2000.0, -600.0], $this->nums($pl['tables'][1]['footer']));

        // September compared with the 30 days before it.
        $sep = $this->report('profit-loss', '2026-09-01', '2026-09-30');
        $this->assertSame([1000.0, 0.0], array_slice($this->nums($this->rowNamed($sep, 'Total Income')), 1, 2));
        $this->assertSame([0.0, 400.0], array_slice($this->nums($this->rowNamed($sep, 'Total Cost of Sales')), 1, 2));
        $this->assertSame([-1000.0, -400.0], array_slice($this->nums($this->rowNamed($sep, 'Net Profit / (Loss)')), 1, 2));
    }

    public function test_balance_sheet_balances_with_nothing_unexplained(): void
    {
        $sheet = $this->get(route('crm.financial_reports.index', ['report' => 'balance-sheet']))->assertOk()->viewData('report');
        $cards = $this->cards($sheet);
        $this->assertMoney(49850, $cards['Total Assets']);
        $this->assertMoney(450, $cards['Total Liabilities']);
        $this->assertMoney(49400, $cards['Equity (Net Worth)']);
        $this->assertMoney(0, $cards['Not yet explained'], 'everything is recorded, so nothing is left to the balancing figure');

        $this->assertSame(48900.0, $this->nums($this->rowNamed($sheet, 'Main Bank'))[1]);
        $this->assertSame(950.0, $this->nums($this->rowNamed($sheet, 'Customers (Accounts Receivable)'))[1]);
        $this->assertSame(400.0, $this->nums($this->rowNamed($sheet, 'Purchase Invoices Payable'))[1]);
        $this->assertSame(50.0, $this->nums($this->rowNamed($sheet, 'VAT Payable / (Refundable)'))[1]);
        $this->assertSame(50000.0, $this->nums($this->rowNamed($sheet, "Owner's Capital — Owner A"))[1]);
        $this->assertSame(-600.0, $this->nums($this->rowNamed($sheet, 'Retained Earnings (all profit to date)'))[1], 'compared across years: one retained-earnings line');
        $this->assertSame(['Total Liabilities + Equity', 49850.0, 0.0, 49850.0], $this->nums($this->rowNamed($sheet, 'Total Liabilities + Equity')));
        $this->assertNull($this->rowNamed($sheet, 'Vendors (Accounts Payable)'), 'zero lines are left out');

        // Within one year the profit has its own line.
        $sheet = $this->service('2026-10-01', '2026-10-26')->balanceSheetAsOf(\Carbon\Carbon::parse('2026-10-26'));
        $equity = collect($sheet['equity'])->keyBy(3);
        $this->assertMoney(-600, $equity['profit'][1]);
        $this->assertMoney(0, $equity['retained'][1]);
        $this->assertMoney(0, $equity['balancing'][1]);
        $this->assertSame('Opening Balances not recorded (balancing figure)', $equity['balancing'][0]);

        // On an earlier date: rebuilt from the dates of each entry (before Customer X paid and the scrap sale).
        $aug = $this->service('2026-01-01', '2026-08-31')->balanceSheetAsOf(\Carbon\Carbon::parse('2026-08-31'));
        $assets = collect($aug['assets'])->mapWithKeys(fn ($l) => [$l[0] => round((float) $l[1], 2)]);
        $this->assertSame(50000.0, $assets['Main Bank']);
        $this->assertSame(500.0, $assets['Customers (Accounts Receivable)']);
        $this->assertMoney(50500, $aug['assets_total']);
        $this->assertMoney(400, $aug['liabilities_total']);
        $this->assertMoney(0, collect($aug['equity'])->firstWhere(3, 'balancing')[1]);
    }

    public function test_trial_balance_debits_equal_credits(): void
    {
        $trial = $this->report('trial-balance');
        $cards = $this->cards($trial);
        $this->assertMoney(52250, $cards['Total Debit']);
        $this->assertMoney(52250, $cards['Total Credit']);
        $this->assertMoney(0, $cards['Difference']);

        $this->assertSame(['Main Bank', 48900.0, null], $this->nums($this->rowNamed($trial, 'Main Bank')));
        $this->assertSame(['Customers (Accounts Receivable)', 950.0, null], $this->nums($this->rowNamed($trial, 'Customers (Accounts Receivable)')));
        $this->assertSame(['Purchase Invoices Payable', null, 400.0], $this->nums($this->rowNamed($trial, 'Purchase Invoices Payable')));
        $this->assertSame(["Owner's Capital — Owner A", null, 50000.0], $this->nums($this->rowNamed($trial, "Owner's Capital — Owner A")));
        $this->assertSame(['Customer Sales', null, 1500.0], $this->nums($this->rowNamed($trial, 'Customer Sales')));
        $this->assertSame(['Rent', 2000.0, null], $this->nums($this->rowNamed($trial, 'Rent')));
        $this->assertSame(['Stock & Raw Material', 400.0, null], $this->nums($this->rowNamed($trial, 'Stock & Raw Material')));
        $this->assertNull($this->rowNamed($trial, 'Opening Balances not recorded (balancing figure)'), 'nothing to balance');
    }

    public function test_cash_flow(): void
    {
        $flow = $this->report('cash-flow');
        $cards = $this->cards($flow);
        $this->assertMoney(0, $cards['Cash & Bank at Start']);
        $this->assertMoney(-1100, $cards['Net Cash from Operations']);
        $this->assertMoney(48900, $cards['Net Change in Cash']);
        $this->assertMoney(48900, $cards['Cash & Bank at End']);

        $line = fn ($name) => $this->nums($this->rowNamed($flow, $name))[1];
        $this->assertSame(600.0, $line('Cash received from customers'));
        $this->assertSame(300.0, $line('Rent & other income received'));
        $this->assertSame(-2000.0, $line('Paid for expenses'));
        $this->assertNull($line('Paid on purchase invoices'), 'the purchase invoice is not paid');
        $this->assertSame(50000.0, $line("Owner's capital introduced"));
        $this->assertSame(-1100.0, $line('Net cash from operating activities'));
        $this->assertSame(0.0, $line('Net cash from investing activities'));
        $this->assertSame(50000.0, $line('Net cash from financing activities'));
        $this->assertSame(48900.0, $line('Net increase / (decrease) in cash (A + B + C)'));
        $this->assertNull($this->rowNamed($flow, "Opening balances entered / entries outside an account's dates"), 'the movements explain all the cash');

        $byMethod = collect($this->tableRows($flow, 2))->keyBy(0)->map(fn ($r) => $this->nums($r));
        $this->assertSame(['Bank', 50600.0, 2000.0, 48600.0], $byMethod['Bank']);
        $this->assertSame(['Cash', 300.0, 0.0, 300.0], $byMethod['Cash']);

        $months = collect($this->tableRows($flow, 1))->keyBy(0)->map(fn ($r) => $this->nums($r));
        $this->assertSame(['Feb 2026', 0.0, 0.0, 50000.0, 50000.0, 50000.0], $months['Feb 2026']);
        $this->assertSame(['Sep 2026', -1400.0, 0.0, 0.0, -1400.0, 48600.0], $months['Sep 2026']);
        $this->assertSame(['Oct 2026', 300.0, 0.0, 0.0, 300.0, 48900.0], $months['Oct 2026']);
    }

    public function test_day_book_lists_every_entry_once(): void
    {
        $book = $this->report('day-book');
        $cards = $this->cards($book);
        $this->assertSame(7, $cards['Entries']);
        $this->assertMoney(1950, $cards['Invoiced / Billed'], 'two sales and the purchase invoice; the scrap receipt is money in, not a bill');
        $this->assertMoney(50900, $cards['Money In']);
        $this->assertMoney(2000, $cards['Money Out']);

        $rows = $this->tableRows($book);
        $this->assertSame('Sunday, 01 Feb 2026', $rows[0][0]);
        $this->assertSame(["Owner's Capital", null, null, 50000.0], [$rows[1][0], $rows[1][3], $rows[1][5], $this->nums($rows[1])[4]]);
        $days = collect($rows)->filter(fn ($r) => $r[1] === null && $r[2] === null && preg_match('/\d{4}$/', (string) $r[0]))->pluck(0)->values()->all();
        $this->assertSame(['Sunday, 01 Feb 2026', 'Wednesday, 15 Jul 2026', 'Saturday, 15 Aug 2026', 'Saturday, 05 Sep 2026',
            'Thursday, 10 Sep 2026', 'Sunday, 20 Sep 2026', 'Tuesday, 20 Oct 2026'], $days);
        $types = collect($rows)->pluck(0)->all();
        foreach (['Customer Sale', 'Purchase Invoice (bill)', 'Expense Payment', 'Customer Sale', 'Direct Receipt'] as $type) {
            $this->assertContains($type, $types);
        }
    }

    public function test_vat_report_and_vat_return(): void
    {
        $vat = $this->report('vat');
        $cards = $this->cards($vat);
        $this->assertMoney(50, $cards['Output VAT (on sales)']);
        $this->assertMoney(0, $cards['Input VAT (on purchases)']);
        $this->assertMoney(50, $cards['Net VAT Payable']);
        $this->assertSame(['Total', 1500.0, 50.0, 0.0, 0.0, 50.0], $this->nums($vat['tables'][0]['footer']));
        $this->assertSame(['Sep 2026', 1000.0, 50.0, 0.0, 0.0, 50.0], $this->nums($this->rowNamed($vat, 'Sep 2026')));

        $return = $this->report('vat-return', '2026-07-01', '2026-09-30');
        $boxes = collect($this->tableRows($return))->filter(fn ($r) => count($r) === 4 && $r[0] !== null && !str_contains((string) $r[0], 'VAT'))->keyBy(0)->map(fn ($r) => $this->nums($r));
        $this->assertSame(['1c', 'Standard rated supplies in Sharjah', 1000.0, 50.0], $boxes['1c'], 'all standard rated sales in the default emirate');
        $this->assertSame([0.0, 0.0], array_slice($boxes['1b'], 2));
        $this->assertSame([500.0, null], array_slice($boxes['4'], 2), 'sale without VAT is zero rated');
        $this->assertSame([1500.0, 50.0], array_slice($boxes['8'], 2));
        $this->assertSame([0.0, 0.0], array_slice($boxes['9'], 2), 'purchase invoices carry no VAT');
        $this->assertSame([null, 50.0], array_slice($boxes['12'], 2));
        $this->assertSame([null, 0.0], array_slice($boxes['13'], 2));
        $this->assertSame(['14', 'Payable tax for the period', null, 50.0], $boxes['14']);
        $cards = $this->cards($return);
        $this->assertMoney(1000, $cards['Box 1 · Standard Rated Sales']);
        $this->assertMoney(50, $cards['Box 14 · VAT Payable']);

        // Supplies made from Dubai.
        $dubai = $this->get(route('crm.financial_reports.index', ['report' => 'vat-return', 'emirate' => 'Dubai']))->assertOk()->viewData('report');
        $this->assertSame([1000.0, 50.0], array_slice($this->nums($this->rowNamed($dubai, '1b')), 2));
        $this->assertSame([0.0, 0.0], array_slice($this->nums($this->rowNamed($dubai, '1c')), 2));
    }

    public function test_receivables_and_payables_aging(): void
    {
        $aging = $this->report('receivables');
        $cards = $this->cards($aging);
        $this->assertMoney(950, $cards['Total Outstanding']);
        $this->assertMoney(0, $cards['0–30 days']);
        $this->assertMoney(450, $cards['31–90 days'], 'Customer X: 46 days');
        $this->assertMoney(500, $cards['Over 90 days'], 'Customer Y: 103 days');
        $this->assertSame([[self::Y, 0.0, 0.0, 0.0, 500.0, 500.0], [self::X, 0.0, 450.0, 0.0, 0.0, 450.0]], array_map(fn ($r) => $this->nums($r), $this->tableRows($aging)));
        $open = array_map(fn ($r) => $this->nums($r), $this->tableRows($aging, 1));
        $this->assertSame(['15 Jul 2026', self::Y, 'Customer Sale', $this->saleY->order_number, 500.0, 0.0, 500.0, 103.0], array_slice($open[0], 0, 8));
        $this->assertSame(['10 Sep 2026', self::X, 'Customer Sale', $this->saleX->order_number, 1050.0, 600.0, 450.0, 46.0], array_slice($open[1], 0, 8));

        // As of 31 Aug: only Customer Y (47 days old then).
        $aug = $this->report('receivables', '2026-01-01', '2026-08-31');
        $this->assertSame([[self::Y, 0.0, 500.0, 0.0, 0.0, 500.0]], array_map(fn ($r) => $this->nums($r), $this->tableRows($aug)));

        $payables = $this->report('payables');
        $cards = $this->cards($payables);
        $this->assertMoney(400, $cards['Total Outstanding']);
        $this->assertMoney(400, $cards['31–90 days'], 'bill of 15 Aug: 72 days');
        $this->assertSame([[self::VENDOR, 0.0, 0.0, 400.0, 0.0, 0.0, 400.0]], array_map(fn ($r) => $this->nums($r), $this->tableRows($payables)));
        $this->assertSame([$this->invoice->number, 'Purchase Invoice'], [$this->tableRows($payables, 1)[0][3], $this->tableRows($payables, 1)[0][2]]);

        // Paying the bill clears it.
        $this->post(route('crm.purchase_invoices.payments.store', $this->invoice->id), ['amount' => 400, 'paid_at' => '2026-10-25', 'method' => 'Bank Transfer', 'bank_account_id' => $this->bank->id])
            ->assertSessionHasNoErrors();
        $this->assertMoney(0, $this->cards($this->report('payables'))['Total Outstanding']);
        $sheet = $this->cards($this->report('balance-sheet'));
        $this->assertMoney(0, $sheet['Not yet explained']);
        $this->assertMoney(50, $sheet['Total Liabilities']);
    }

    public function test_customer_statement(): void
    {
        $statement = $this->get(route('crm.financial_reports.index', ['report' => 'customer-statement', 'customer' => self::X]))->assertOk()->viewData('report');
        $cards = $this->cards($statement);
        $this->assertMoney(0, $cards['Opening Balance']);
        $this->assertMoney(1050, $cards['Invoiced']);
        $this->assertMoney(600, $cards['Paid']);
        $this->assertMoney(450, $cards['Balance Due']);

        $rows = array_map(fn ($r) => $this->nums($r), $this->tableRows($statement));
        $this->assertSame(['01 Jan 2026', 'Opening balance', null, 'brought forward', null, null, 0.0], $rows[0]);
        $this->assertSame(['10 Sep 2026', 'Sale', $this->saleX->order_number, 'incl. VAT 50.00', 1050.0, null, 1050.0], $rows[1]);
        $this->assertSame(['20 Sep 2026', 'Payment', $this->saleX->order_number, 'Payment · Bank Transfer', null, 600.0, 450.0], $rows[2]);
        $this->assertCount(3, $rows);
        $this->assertSame([[$this->saleX->order_number, '10 Sep 2026', 46.0, 1050.0, 600.0, 450.0]], array_map(fn ($r) => $this->nums($r), $this->tableRows($statement, 1)));
        $this->assertSame([0.0, 450.0, 0.0, 0.0, null, 450.0], $this->nums($this->tableRows($statement, 2)[0]));

        // Customers to pick from: the two customers (not the scrap buyer), with what they owe.
        $options = $statement['filters'][0]['options'];
        $this->assertSame([self::X, self::Y], array_keys($options));
        $this->assertSame(self::X . ' · due 450.00', $options[self::X]);

        // No customer picked: the one who owes the most.
        $this->assertSame(self::Y, $this->report('customer-statement')['filters'][0]['value']);

        // A period starting after the sale: the sale is in the opening balance.
        $late = $this->cards($this->report('customer-statement', '2026-09-15', '2026-10-26', ['customer' => self::X]));
        $this->assertMoney(1050, $late['Opening Balance']);
        $this->assertMoney(0, $late['Invoiced']);
        $this->assertMoney(600, $late['Paid']);
        $this->assertMoney(450, $late['Balance Due']);

        // Exported as PDF to send to the customer.
        $this->get(route('crm.financial_reports.export', ['report' => 'customer-statement', 'customer' => self::X, 'format' => 'pdf']))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="customer-statement-statement-customer-x-20260101-20261026.pdf"');
    }

    public function test_sales_and_sales_by_product_reports(): void
    {
        $sales = $this->report('sales');
        $cards = $this->cards($sales);
        $this->assertMoney(1550, $cards['Invoiced'], 'with VAT; the scrap receipt is not a sale');
        $this->assertMoney(600, $cards['Collected'], 'customer receipts only');
        $this->assertMoney(775, $cards['Average Invoice']);
        $this->assertSame(2, $cards['Customers']);
        $this->assertSame(['Total', 2.0, 1500.0, 50.0, 1550.0, 600.0], $this->nums($sales['tables'][0]['footer']));
        $this->assertSame([[self::X, 1.0, 1050.0, 600.0, 450.0], [self::Y, 1.0, 500.0, 0.0, 500.0]], array_map(fn ($r) => $this->nums($r), $this->tableRows($sales, 1)));

        $products = $this->report('sales-products');
        $cards = collect($products['cards'])->keyBy('label');
        $this->assertMoney(1500, $cards['Sales (without VAT)']['value']);
        $this->assertSame(2, $cards['Products Sold']['value']);
        $this->assertSame([1000.0, 'Printed Boxes'], [(float) $cards['Top Product']['value'], $cards['Top Product']['note']]);
        $this->assertSame([1500.0, 'Admin Tester'], [(float) $cards['Top Salesperson']['value'], $cards['Top Salesperson']['note']]);
        $this->assertSame([['Printed Boxes', 10.0, 1.0, 1000.0, 66.67], ['Paper Bags', 2.0, 1.0, 500.0, 33.33]], array_map(fn ($r) => $this->nums($r), $this->tableRows($products)));
        $this->assertSame([['Admin Tester', 2.0, 1500.0, 750.0, 100.0]], array_map(fn ($r) => $this->nums($r), $this->tableRows($products, 1)));
    }

    public function test_expense_analysis(): void
    {
        $report = $this->report('expenses');
        $cards = collect($report['cards'])->keyBy('label');
        $this->assertMoney(2400, $cards['Total Spend']['value']);
        $this->assertSame('2 entries', $cards['Total Spend']['note']);
        $this->assertMoney(240, $cards['Average per Month']['value'], '10 months this year');
        $this->assertSame([2000.0, 'Rent'], [(float) $cards['Biggest Category']['value'], $cards['Biggest Category']['note']]);
        $this->assertSame([['Rent', 2000.0, 83.33, 1.0], ['Stock & Raw Material', 400.0, 16.67, 1.0]], array_map(fn ($r) => $this->nums($r), $this->tableRows($report)));
        $this->assertSame([['Landlord LLC', 1.0, 2000.0], [self::VENDOR, 1.0, 400.0]], array_map(fn ($r) => $this->nums($r), $this->tableRows($report, 2)));
    }

    public function test_owners_equity(): void
    {
        $cards = $this->cards($this->report('owners-equity'));
        $this->assertMoney(0, $cards['Opening Equity']);
        $this->assertMoney(50000, $cards['Capital Put In']);
        $this->assertMoney(0, $cards['Drawings']);
        $this->assertMoney(-600, $cards['Profit / (Loss)']);
        $this->assertMoney(49400, $cards['Closing Equity']);
    }

    public function test_profit_and_loss_drill_down_lists_the_entries_behind_each_line(): void
    {
        $entries = fn (array $q) => $this->get(route('crm.financial_reports.entries', $q))->assertOk()->viewData('report');

        $income = $entries(['group' => 'income']);
        $this->assertSame('Income — Entries', $income['title']);
        $this->assertMoney(1800, $this->cards($income)['Total']);
        $this->assertSame(3, $this->cards($income)['Entries']);
        $rows = array_map(fn ($r) => $this->nums($r), $this->tableRows($income));
        $this->assertSame(['20 Oct 2026', 'Scrap Dealer LLC'], array_slice($rows[0], 0, 2));
        $this->assertSame(['10 Sep 2026', self::X, $this->saleX->order_number, 'Customer Sales', 1000.0, 50.0, 1050.0, 600.0], $rows[1]);
        $this->assertSame(['15 Jul 2026', self::Y, $this->saleY->order_number, 'Customer Sales', 500.0, 0.0, 500.0, 0.0], $rows[2]);

        $sales = $entries(['group' => 'income', 'category' => 'Customer Sales']);
        $this->assertSame('Customer Sales — Entries', $sales['title']);
        $this->assertSame(['Total', null, null, null, 1500.0, 50.0, 1550.0, 600.0], $this->nums($sales['tables'][0]['footer']));

        $cos = $this->tableRows($entries(['group' => 'cos']));
        $this->assertSame([['15 Aug 2026', self::VENDOR, $this->invoice->number, 'Purchase Invoice', 'Stock & Raw Material', 400.0]], array_map(fn ($r) => $this->nums($r), $cos));

        $rent = $this->tableRows($entries(['group' => 'opex', 'category' => 'Rent']));
        $this->assertSame([['05 Sep 2026', 'Landlord LLC', $this->rent->number, 'Expense', 'Rent', 2000.0]], array_map(fn ($r) => $this->nums($r), $rent));

        $all = $this->get(route('crm.financial_reports.entries', ['group' => 'costs']))->assertOk();
        $this->assertSame('expenses', $all->viewData('activeTab'));
        $this->assertMoney(2400, $this->cards($all->viewData('report'))['Total']);
        $this->assertSame(0, $this->cards($entries(['group' => 'income', 'category' => 'No Such Category']))['Entries']);

        // Only September: the purchase invoice (August) and the July sale drop out.
        $sep = $entries(['group' => 'costs', 'period' => 'custom', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30']);
        $this->assertSame(['Rent'], array_column($this->tableRows($sep), 4));

        // The P&L lines link to these lists.
        $pl = $this->report('profit-loss');
        $link = collect($pl['tables'][0]['rows'])->first(fn ($r) => !isset($r['_style']) && is_array($r[0]) && $r[0]['text'] === 'Rent')[0]['url'];
        $this->assertStringContainsString(route('crm.financial_reports.entries'), $link);
        $this->assertSame([['05 Sep 2026', 'Landlord LLC', $this->rent->number, 'Expense', 'Rent', 2000.0]],
            array_map(fn ($r) => $this->nums($r), $this->tableRows($this->get($link)->assertOk()->viewData('report'))));

        // Bad group; export of the list.
        $this->get(route('crm.financial_reports.entries', ['group' => 'assets']))->assertSessionHasErrors('group');
        $this->get(route('crm.financial_reports.export', ['report' => 'entries', 'group' => 'opex', 'category' => 'Rent']))->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.ms-excel')->assertSee('Landlord LLC');
    }

    public function test_every_report_still_opens_with_the_books_filled_in(): void
    {
        foreach (array_keys(\App\Services\FinancialReportService::REPORTS) as $report) {
            $this->get(route('crm.financial_reports.index', ['report' => $report]))->assertOk();
        }
        $this->get(route('crm.chart_of_accounts.index'))->assertOk();
    }

    public function test_who_can_open_the_reports(): void
    {
        $this->signIn($this->makeUser('sales_manager'));
        $this->get(route('crm.financial_reports.index', ['report' => 'balance-sheet']))->assertOk();
        $this->get(route('crm.financial_reports.entries', ['group' => 'income']))->assertOk();

        $this->signIn($this->makeUser('sales'));
        $this->get(route('crm.financial_reports.index', ['report' => 'profit-loss']))->assertForbidden();
        $this->get(route('crm.financial_reports.entries', ['group' => 'income']))->assertForbidden();
        $this->get(route('crm.financial_reports.export', ['report' => 'profit-loss']))->assertForbidden();
    }

    protected function service(string $from, string $to): \App\Services\FinancialReportService
    {
        \App\Support\CrmWorkspaceContext::set($this->workspace->id);

        return new \App\Services\FinancialReportService(\Carbon\Carbon::parse($from), \Carbon\Carbon::parse($to));
    }
}
