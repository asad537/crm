<?php

namespace Tests\Feature\Accounting;

use App\CrmDeletionLog;
use App\CrmDirectReceipt;
use App\CrmIncomeEntry;
use App\CrmWorkspace;
use App\CustomerSale;
use App\CustomerSalePayment;
use App\Services\BankLedgerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Money in: "Make Receipt" (other income, on account, against a sale / income due), customer sales and the Receipts page. */
class ReceiptsAndSalesTest extends AccountingTestCase
{
    use BuildsMoneyIn;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    // ───────────── Other income ("Make Receipt → Other income") ─────────────

    public function test_other_income_receipt_is_saved_with_its_proof_and_counts_as_income(): void
    {
        $bank = $this->createBankAccount('ENBD Current');
        $receipt = $this->makeDirectReceipt([
            'receipt_date' => '2026-10-20', 'category' => '  Scrap Sales ', 'description' => 'Scrap paper sold', 'payer_type' => 'other',
            'payer_name' => 'Scrap Dealer LLC', 'amount' => 1250.5, 'method' => 'Cash', 'bank_account_id' => $bank->id, 'reference' => 'SCR-77',
            'proof' => UploadedFile::fake()->create('scrap-slip.pdf', 12, 'application/pdf'),
        ]);

        $this->assertSame('income', $receipt->kind);
        $this->assertSame('Scrap Sales', $receipt->category, 'category is trimmed');
        $this->assertMoney(1250.5, $receipt->amount);
        $this->assertSame($bank->id, (int) $receipt->bank_account_id);
        $this->assertSame($this->workspace->id, (int) $receipt->workspace_id);
        $this->assertSame($this->admin->id, (int) $receipt->created_by);
        $this->assertSame('scrap-slip.pdf', $receipt->proof_name);
        Storage::disk('local')->assertExists($receipt->proof_path);
        $this->assertSame('RCT-' . str_pad($receipt->id, 4, '0', STR_PAD_LEFT), $receipt->number);

        // Voucher page and proof download.
        $show = $this->get(route('crm.direct_receipts.show', $receipt->id))->assertOk()
            ->assertSee($receipt->number)->assertSee('Scrap Dealer LLC')->assertSee('1,250.50');
        $this->assertSame($bank->id, $show->viewData('account')->id);
        $this->assertFalse($show->viewData('autoBooked'));
        $this->get(route('crm.direct_receipts.proof', $receipt->id))->assertOk();

        // Profit & Loss: income in its own category; the drill-down lists it.
        $pl = $this->report('profit-loss');
        $this->assertMoney(1250.5, $this->cards($pl)['Total Income']);
        $this->assertMoney(1250.5, $this->rowNamed($pl, 'Scrap Sales')[1]);
        $entries = $this->get(route('crm.financial_reports.entries', ['group' => 'income', 'category' => 'Scrap Sales']))->assertOk()->viewData('report');
        $this->assertCount(1, $entries['tables'][0]['rows']);
        $this->assertSame($receipt->number, $entries['tables'][0]['rows'][0][2]['text']);

        // Cash flow: other income received; the bank account it was put in goes up.
        $cash = $this->report('cash-flow');
        $this->assertMoney(1250.5, $this->rowNamed($cash, 'Rent & other income received')[1]);
        $this->assertMoney(1250.5, (new BankLedgerService())->balances()['accounts'][$bank->id]['balance']);

        // Receipts page.
        $row = $this->receiptRows()->firstWhere('ref', $receipt->number);
        $this->assertNotNull($row);
        $this->assertSame('direct', $row->source);
        $this->assertSame('Scrap Dealer LLC', $row->customer);
        $this->assertMoney(1250.5, $row->amount);
        $this->assertSame(route('crm.direct_receipts.proof', $receipt->id), $row->proof_url);
        $this->assertSame('Scrap Sales · Scrap paper sold', $row->detail);
    }

    public function test_a_receipt_without_a_chosen_account_is_auto_booked_by_its_method(): void
    {
        $cashBox = $this->createBankAccount('Cash in Hand', ['type' => 'cash', 'auto_methods' => ['Cash']]);
        $receipt = $this->makeDirectReceipt(['amount' => 300, 'method' => 'Cash']);

        $this->assertNull($receipt->bank_account_id);
        $show = $this->get(route('crm.direct_receipts.show', $receipt->id))->assertOk();
        $this->assertSame($cashBox->id, $show->viewData('account')->id);
        $this->assertTrue($show->viewData('autoBooked'));
        $this->assertMoney(300, (new BankLedgerService())->balances()['accounts'][$cashBox->id]['balance']);
    }

    public function test_a_receipt_can_be_edited_its_proof_replaced_and_then_deleted(): void
    {
        $receipt = $this->makeDirectReceipt(['amount' => 500, 'proof' => UploadedFile::fake()->image('first.jpg')]);
        $oldProof = $receipt->proof_path;

        $form = ['receipt_date' => '2026-10-21', 'category' => 'Commission Received', 'description' => 'Agent commission', 'payer_type' => 'other',
            'payer_name' => 'Agent Co', 'amount' => 900, 'currency' => 'AED', 'method' => 'Bank Transfer', 'reference' => 'COM-1'];
        $this->from(route('crm.receipts.index'))
            ->put(route('crm.direct_receipts.update', $receipt->id), $form + ['proof' => UploadedFile::fake()->create('second.pdf', 5, 'application/pdf')])
            ->assertSessionHasNoErrors()->assertRedirect(route('crm.receipts.index'))->assertSessionHas('success');
        $receipt->refresh();
        $this->assertMoney(900, $receipt->amount);
        $this->assertSame('Commission Received', $receipt->category);
        $this->assertSame('2026-10-21', $receipt->receipt_date->toDateString());
        $this->assertSame('second.pdf', $receipt->proof_name);
        Storage::disk('local')->assertMissing($oldProof);
        Storage::disk('local')->assertExists($receipt->proof_path);

        // Editing without a new file keeps the proof.
        $this->put(route('crm.direct_receipts.update', $receipt->id), array_merge($form, ['amount' => 950]))->assertSessionHasNoErrors();
        $receipt->refresh();
        $this->assertMoney(950, $receipt->amount);
        $this->assertSame('second.pdf', $receipt->proof_name);
        $this->assertMoney(950, $this->cards($this->report('profit-loss'))['Total Income']);

        // Deleting from its own voucher page goes back to the list; the proof and the row are gone and the deletion is logged.
        $proof = $receipt->proof_path;
        $this->from(route('crm.direct_receipts.show', $receipt->id))->delete(route('crm.direct_receipts.destroy', $receipt->id))
            ->assertRedirect(route('crm.receipts.index'));
        $this->assertNull(CrmDirectReceipt::find($receipt->id));
        Storage::disk('local')->assertMissing($proof);
        $log = CrmDeletionLog::where('entity_type', 'direct_receipt')->where('entity_id', $receipt->id)->first();
        $this->assertNotNull($log);
        $this->assertSame($this->workspace->id, (int) $log->workspace_id);
        $this->assertMoney(0, $this->cards($this->report('profit-loss'))['Total Income']);
        $this->get(route('crm.direct_receipts.show', $receipt->id))->assertNotFound();
        $this->get(route('crm.direct_receipts.proof', $receipt->id))->assertNotFound();

        // Deleting from the list stays on the list.
        $other = $this->makeDirectReceipt();
        $this->from(route('crm.receipts.index', ['source' => 'direct']))->delete(route('crm.direct_receipts.destroy', $other->id))
            ->assertRedirect(route('crm.receipts.index', ['source' => 'direct']));
    }

    public function test_receipt_without_proof_has_no_download(): void
    {
        $receipt = $this->makeDirectReceipt();
        $this->assertNull($receipt->proof_path);
        $this->get(route('crm.direct_receipts.proof', $receipt->id))->assertNotFound();
        $this->assertNull($this->receiptRows()->firstWhere('ref', $receipt->number)->proof_url);
    }

    public function test_other_income_receipt_is_validated(): void
    {
        $valid = ['receipt_date' => '2026-10-20', 'category' => 'Scrap Sales', 'description' => 'Scrap', 'payer_type' => 'other',
            'amount' => 100, 'currency' => 'AED', 'method' => 'Cash'];
        $cases = [
            'receipt_date' => ['receipt_date' => '2026-10-27'],       // after today
            'amount' => ['amount' => 0],
            'method' => ['method' => 'Bitcoin'],
            'payer_type' => ['payer_type' => 'vendor'],
            'currency' => ['currency' => 'PKR'],
            'proof' => ['proof' => UploadedFile::fake()->create('virus.exe', 5, 'application/x-msdownload')],
        ];
        foreach ($cases as $field => $override) {
            $this->post(route('crm.direct_receipts.store'), array_merge($valid, $override))->assertSessionHasErrors($field);
        }
        $this->assertSame(0, CrmDirectReceipt::count());
    }

    public function test_who_can_record_and_delete_receipts(): void
    {
        $receipt = $this->makeDirectReceipt();

        // Sales manager: may record and view, may not delete.
        $this->signIn($this->makeUser('sales_manager'));
        $this->get(route('crm.receipts.index'))->assertOk();
        $this->makeDirectReceipt(['amount' => 10]);
        $this->delete(route('crm.direct_receipts.destroy', $receipt->id))->assertForbidden();

        // Accounts: may delete.
        $this->signIn($this->makeUser('accounts'));
        $this->delete(route('crm.direct_receipts.destroy', $receipt->id))->assertRedirect();
        $this->assertNull(CrmDirectReceipt::find($receipt->id));

        // Sales agent: blocked everywhere.
        $other = CrmDirectReceipt::firstOrFail();
        $this->signIn($this->makeUser('sales'));
        $this->get(route('crm.receipts.index'))->assertForbidden();
        $this->post(route('crm.direct_receipts.store'), ['receipt_date' => '2026-10-20'])->assertForbidden();
        $this->get(route('crm.direct_receipts.show', $other->id))->assertForbidden();
        $this->put(route('crm.direct_receipts.update', $other->id), [])->assertForbidden();
        $this->get(route('crm.direct_receipts.proof', $other->id))->assertForbidden();
        $this->delete(route('crm.direct_receipts.destroy', $other->id))->assertForbidden();
        $this->post(route('crm.receipts.receive'), ['doc_type' => 'account', 'customer' => 'X', 'amount' => 1, 'paid_at' => '2026-10-01'])->assertForbidden();
        $this->assertSame(1, CrmDirectReceipt::count());
    }

    // ───────────── Customer sales ─────────────

    public function test_a_customer_sale_is_added_with_its_totals_worked_out(): void
    {
        $customer = $this->createCustomer('Gulf Foods LLC');
        $sale = $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100, 'discount_amount' => 50, 'tax_amount' => 47.5,
            'shipping_cost' => 20, 'paid_amount' => 300, 'payment_method' => 'Cash']);

        $this->assertMoney(1000, $sale->subtotal);
        $this->assertMoney(1017.5, $sale->total_amount);
        $this->assertMoney(300, $sale->paid_amount);
        $this->assertMoney(717.5, $sale->balance_amount);
        $this->assertSame('Partial', $sale->payment_status);
        $this->assertSame($this->admin->id, (int) $sale->created_by);
        $this->assertSame($this->workspace->id, (int) $sale->workspace_id);

        $unpaid = $this->createSale($customer, ['quantity' => 2, 'unit_price' => 50]);
        $this->assertSame('Unpaid', $unpaid->payment_status);
        $paid = $this->createSale($customer, ['quantity' => 1, 'unit_price' => 80, 'paid_amount' => 80]);
        $this->assertSame('Paid', $paid->payment_status);

        $page = $this->get(route('crm.customer_sales.index', ['customer_id' => $customer->id]))->assertOk()->assertSee($sale->order_number);
        $summary = $page->viewData('summary');
        $this->assertMoney(1017.5 + 100 + 80, $summary['total']);
        $this->assertMoney(380, $summary['paid']);
        $this->assertMoney(817.5, $summary['balance']);
        $this->assertMoney(0, $summary['credit']);
        $this->assertSame(3, $summary['open_count']);

        // A sales agent can add sales too.
        $this->signIn($this->makeUser('sales'));
        $this->createSale($customer, ['quantity' => 1, 'unit_price' => 10]);

        // The money paid on the sale date is a receipt on that date.
        $this->signIn($this->admin);
        $row = $this->receiptRows()->firstWhere('ref', $sale->order_number);
        $this->assertSame('sale', $row->source);
        $this->assertSame('2026-09-10', $row->date->toDateString());
        $this->assertSame('Sale date', $row->date_note);
        $this->assertMoney(300, $row->amount);
        $this->assertSame('Cash', $row->method);
    }

    public function test_a_customer_sale_is_validated(): void
    {
        $customer = $this->createCustomer('Validation Customer');
        $base = ['customer_id' => $customer->id, 'order_number' => 'CS-V1', 'order_date' => '2026-09-10', 'item_name' => 'Box', 'quantity' => 1,
            'unit' => 'Pieces', 'unit_price' => 10, 'currency' => 'AED', 'order_status' => 'Confirmed'];
        $this->post(route('crm.customer_sales.store'), array_merge($base, ['due_date' => '2026-09-01']))->assertSessionHasErrors('due_date');
        $this->post(route('crm.customer_sales.store'), array_merge($base, ['unit' => 'Litres']))->assertSessionHasErrors('unit');
        $this->post(route('crm.customer_sales.store'), array_merge($base, ['quantity' => 0]))->assertSessionHasErrors('quantity');
        $this->post(route('crm.customer_sales.store'), array_merge($base, ['payment_method' => 'Crypto']))->assertSessionHasErrors('payment_method');
        $this->post(route('crm.customer_sales.store'), array_merge($base, ['customer_id' => 999999999]))->assertSessionHasErrors('customer_id');
        $this->assertSame(0, CustomerSale::count());
    }

    public function test_a_customer_sale_can_be_edited_and_its_totals_are_worked_out_again(): void
    {
        $customer = $this->createCustomer('Edit Customer');
        $sale = $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100, 'paid_amount' => 200]);

        $this->put(route('crm.customer_sales.update', $sale->id), [
            'customer_id' => $customer->id, 'order_number' => $sale->order_number, 'order_date' => '2026-09-12', 'item_name' => 'Printed Boxes (revised)',
            'quantity' => 12, 'unit' => 'Boxes', 'unit_price' => 100, 'tax_amount' => 60, 'paid_amount' => 200, 'currency' => 'AED', 'order_status' => 'Delivered',
        ])->assertSessionHasNoErrors()->assertRedirect(route('crm.customer_sales.index', ['customer_id' => $customer->id]));

        $sale->refresh();
        $this->assertSame('Printed Boxes (revised)', $sale->item_name);
        $this->assertSame('2026-09-12', $sale->order_date->toDateString());
        $this->assertMoney(1200, $sale->subtotal);
        $this->assertMoney(1260, $sale->total_amount);
        $this->assertMoney(1060, $sale->balance_amount);
        $this->assertSame('Partial', $sale->payment_status);

        $pl = $this->report('profit-loss');
        $this->assertMoney(1200, $this->rowNamed($pl, 'Customer Sales')[1], 'income is the sale without VAT');
        $this->assertMoney(60, $this->cards($this->report('vat'))['Output VAT (on sales)']);
    }

    public function test_updating_the_paid_amount_sets_the_status_and_an_overpayment_is_customer_credit(): void
    {
        $customer = $this->createCustomer('Credit Customer');
        $sale = $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100, 'tax_amount' => 50]);   // 1,050
        $pay = fn ($amount) => $this->from(route('crm.customer_sales.index', ['customer_id' => $customer->id]))
            ->patch(route('crm.customer_sales.update_payment', $sale->id), ['paid_amount' => $amount])
            ->assertSessionHasNoErrors()->assertRedirect(route('crm.customer_sales.index', ['customer_id' => $customer->id]));

        $pay(400);
        $sale->refresh();
        $this->assertSame(['Partial', 400.0, 650.0], [$sale->payment_status, (float) $sale->paid_amount, (float) $sale->balance_amount]);

        $pay(1050);
        $sale->refresh();
        $this->assertSame(['Paid', 1050.0, 0.0], [$sale->payment_status, (float) $sale->paid_amount, (float) $sale->balance_amount]);

        // Paid more than owed: balance stays 0, the extra is the customer's credit.
        $pay(1200);
        $sale->refresh();
        $this->assertSame(['Paid', 1200.0, 0.0], [$sale->payment_status, (float) $sale->paid_amount, (float) $sale->balance_amount]);
        $page = $this->get(route('crm.customer_sales.index', ['customer_id' => $customer->id]))->assertOk()->assertSee('Credit 150.00');
        $this->assertMoney(150, $page->viewData('summary')['credit']);
        $this->assertMoney(0, $page->viewData('summary')['balance']);

        $pay(0);
        $this->assertSame('Unpaid', $sale->fresh()->payment_status);

        $this->patch(route('crm.customer_sales.update_payment', $sale->id), ['paid_amount' => -5])->assertSessionHasErrors('paid_amount');
    }

    /**
     * A customer who paid 150 more than the sale is owed that money back: on the balance sheet it must be a
     * Customer Advance (liability), not vanish into the "not yet explained" balancing figure.
     */
    public function test_an_overpaid_customer_sale_is_a_customer_advance_on_the_balance_sheet(): void
    {
        $customer = $this->createCustomer('Overpaying Customer');
        $sale = $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100, 'tax_amount' => 50]);   // 1,050
        $this->patch(route('crm.customer_sales.update_payment', $sale->id), ['paid_amount' => 1200])->assertSessionHasNoErrors();

        // The customer statement and the sales page already show the 150 credit...
        $this->assertMoney(-150, $this->cards($this->report('customer-statement', '2026-01-01', '2026-10-26', ['customer' => 'Overpaying Customer']))['Balance Due']);

        // ...the balance sheet should too: cash 1,200 = VAT 50 + customer advance 150 + profit 1,000, nothing unexplained.
        $sheet = $this->report('balance-sheet');
        $this->assertMoney(0, $this->cards($sheet)['Not yet explained'], 'overpayment ends up in the balancing figure');
        $advances = $this->rowNamed($sheet, 'Customer Advances (paid ahead / on account)');
        $this->assertNotNull($advances, 'customer advance line is shown');
        $this->assertMoney(150, $advances[1]);
    }

    // ───────────── Make Receipt against a customer sale ─────────────

    public function test_part_and_full_receipts_against_a_customer_sale(): void
    {
        $bank = $this->createBankAccount('ADCB Current');
        $customer = $this->createCustomer('Receipt Customer');
        $sale = $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100, 'tax_amount' => 50]);   // 1,050

        // The sale is offered in the Make Receipt dialog with what is due.
        $options = $this->get(route('crm.receipts.index'))->viewData('openInvoices');
        $open = collect($options)->firstWhere('ref', $sale->order_number);
        $this->assertSame(['sale', $sale->id, 'Receipt Customer'], [$open['type'], $open['id'], $open['customer']]);
        $this->assertMoney(1050, $open['due']);

        $this->receive(['doc_type' => 'sale', 'doc_id' => $sale->id, 'amount' => 400, 'paid_at' => '2026-09-20', 'reference' => 'TRX-1',
            'note' => 'First part', 'bank_account_id' => $bank->id])
            ->assertSessionHasNoErrors()->assertRedirect(route('crm.receipts.index'))->assertSessionHas('success');
        $sale->refresh();
        $this->assertSame(['Partial', 400.0, 650.0], [$sale->payment_status, (float) $sale->paid_amount, (float) $sale->balance_amount]);
        $payment = CustomerSalePayment::where('customer_sale_id', $sale->id)->firstOrFail();
        $this->assertSame(['2026-09-20', 'Bank Transfer', 'TRX-1', $bank->id, $this->workspace->id],
            [$payment->paid_at->toDateString(), $payment->method, $payment->reference, (int) $payment->bank_account_id, (int) $payment->workspace_id]);

        // More than is due is refused and changes nothing.
        $this->receive(['doc_type' => 'sale', 'doc_id' => $sale->id, 'amount' => 650.01])->assertSessionHasErrors('amount');
        $this->assertMoney(400, $sale->fresh()->paid_amount);
        $this->assertSame(1, CustomerSalePayment::count());

        $this->receive(['doc_type' => 'sale', 'doc_id' => $sale->id, 'amount' => 650, 'paid_at' => '2026-10-05', 'bank_account_id' => $bank->id])
            ->assertSessionHasNoErrors();
        $sale->refresh();
        $this->assertSame(['Paid', 1050.0, 0.0], [$sale->payment_status, (float) $sale->paid_amount, (float) $sale->balance_amount]);
        $this->assertCount(2, $sale->payments);

        // Receipts page: one row per payment on its own date (nothing left "on the sale date").
        $rows = $this->receiptRows()->where('ref', $sale->order_number)->values();
        $this->assertCount(2, $rows);
        $this->assertSame(['2026-10-05', '2026-09-20'], $rows->map(fn ($r) => $r->date->toDateString())->all());
        $this->assertSame(['sale_payment', 'sale_payment'], $rows->pluck('link_type')->all());
        $this->assertSame('First part', $rows[1]->detail);
        $this->assertSame('Admin Tester', $rows[1]->received_by);

        // No longer offered in Make Receipt; nothing is receivable; the bank has the money.
        $this->assertNull(collect($this->get(route('crm.receipts.index'))->viewData('openInvoices'))->firstWhere('ref', $sale->order_number));
        $this->assertMoney(0, $this->cards($this->report('receivables'))['Total Outstanding']);
        $this->assertMoney(1050, (new BankLedgerService())->balances()['accounts'][$bank->id]['balance']);

        // The customer statement shows the sale and both payments on their dates.
        $statement = $this->report('customer-statement', '2026-01-01', '2026-10-26', ['customer' => 'Receipt Customer']);
        $cards = $this->cards($statement);
        $this->assertMoney(1050, $cards['Invoiced']);
        $this->assertMoney(1050, $cards['Paid']);
        $this->assertMoney(0, $cards['Balance Due']);
        $rows = $this->tableRows($statement);
        $this->assertSame(['10 Sep 2026', 'Sale'], array_slice($rows[1], 0, 2));
        $this->assertSame(['20 Sep 2026', 'Payment'], array_slice($rows[2], 0, 2));
        $this->assertMoney(650, $rows[2][6]);
        $this->assertSame(['05 Oct 2026', 'Payment'], array_slice($rows[3], 0, 2));
        $this->assertMoney(0, $rows[3][6]);

        // Cash flow: cash received from customers.
        $this->assertMoney(1050, $this->rowNamed($this->report('cash-flow'), 'Cash received from customers')[1]);
    }

    /**
     * CURRENT BEHAVIOUR (question for the owner): the sales page's paid box can be set below what Make Receipt already
     * recorded on the sale. The receipts stay, so the Balance Sheet and Receivables Aging (both from the receipts, by
     * date) say 450 is owed while the sale's own balance box says 750.
     */
    public function test_lowering_the_paid_amount_below_recorded_receipts_leaves_the_sale_box_disagreeing(): void
    {
        $customer = $this->createCustomer('Lowered Payer');
        $sale = $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100, 'tax_amount' => 50]);   // 1,050
        $this->receive(['doc_type' => 'sale', 'doc_id' => $sale->id, 'amount' => 600, 'paid_at' => '2026-09-20'])->assertSessionHasNoErrors();
        $this->patch(route('crm.customer_sales.update_payment', $sale->id), ['paid_amount' => 300])->assertSessionHasNoErrors();

        $sale->refresh();
        $this->assertSame([300.0, 750.0, 'Partial'], [(float) $sale->paid_amount, (float) $sale->balance_amount, $sale->payment_status]);
        $this->assertMoney(600, $this->receiptRows()->where('ref', $sale->order_number)->sum('amount'), 'Receipts page still lists the 600');
        $this->assertMoney(450, $this->cards($this->report('receivables'))['Total Outstanding']);
        $this->assertMoney(450, $this->rowNamed($this->report('balance-sheet'), 'Customers (Accounts Receivable)')[1]);
    }

    public function test_receivables_aging_on_an_earlier_date_ignores_later_receipts_and_nets_overpayments(): void
    {
        $customer = $this->createCustomer('Aging Payer');
        $first = $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100]);                                  // 1,000 on 10 Sep
        $this->receive(['doc_type' => 'sale', 'doc_id' => $first->id, 'amount' => 1000, 'paid_at' => '2026-10-05'])->assertSessionHasNoErrors();

        // On 30 Sep nothing had been received yet: the aging agrees with that day's balance sheet.
        $this->assertMoney(1000, $this->cards($this->report('receivables', '2026-09-01', '2026-09-30'))['Total Outstanding']);
        $this->assertMoney(1000, $this->rowNamed($this->report('balance-sheet', '2026-09-01', '2026-09-30'), 'Customers (Accounts Receivable)')[1]);
        $this->assertMoney(0, $this->cards($this->report('receivables'))['Total Outstanding']);

        // Another customer pays 150 beyond one sale: it is taken off what they still owe on their other sale.
        $other = $this->createCustomer('Netting Payer');
        $this->createSale($other, ['order_number' => 'SO-NET-1', 'quantity' => 10, 'unit_price' => 100]);                   // 1,000 unpaid
        $paidAhead = $this->createSale($other, ['order_number' => 'SO-NET-2', 'quantity' => 5, 'unit_price' => 100]);        // 500
        $this->patch(route('crm.customer_sales.update_payment', $paidAhead->id), ['paid_amount' => 650])->assertSessionHasNoErrors();

        $sheet = $this->report('balance-sheet');
        $this->assertMoney(850, $this->rowNamed($sheet, 'Customers (Accounts Receivable)')[1]);
        $this->assertMoney(0, $this->rowNamed($sheet, 'Customer Advances (paid ahead / on account)')[1] ?? 0);
        $this->assertMoney(850, $this->cards($this->report('receivables'))['Total Outstanding'], 'aging agrees with the balance sheet');
        $this->assertMoney(0, $this->cards($sheet)['Not yet explained']);
    }

    public function test_a_sale_paid_partly_on_the_sale_date_then_by_receipt_lists_both(): void
    {
        $customer = $this->createCustomer('Mixed Payer');
        $sale = $this->createSale($customer, ['quantity' => 5, 'unit_price' => 100, 'paid_amount' => 200, 'payment_method' => 'Cash']);
        $this->receive(['doc_type' => 'sale', 'doc_id' => $sale->id, 'amount' => 300, 'paid_at' => '2026-10-02'])->assertSessionHasNoErrors();

        $rows = $this->receiptRows()->where('ref', $sale->order_number)->values();
        $this->assertCount(2, $rows);
        $this->assertSame(['2026-10-02', 300.0, null], [$rows[0]->date->toDateString(), $rows[0]->amount, $rows[0]->date_note]);
        $this->assertSame(['2026-09-10', 200.0, 'Sale date'], [$rows[1]->date->toDateString(), $rows[1]->amount, $rows[1]->date_note]);
        $this->assertSame('Paid', $sale->fresh()->payment_status);
    }

    public function test_receive_is_validated(): void
    {
        $this->receive(['doc_type' => 'sale', 'amount' => 10])->assertSessionHasErrors('doc_id');
        $this->receive(['doc_type' => 'account', 'amount' => 10])->assertSessionHasErrors('customer');
        $this->receive(['doc_type' => 'invoice', 'doc_id' => 1, 'amount' => 10])->assertSessionHasErrors('doc_type');
        $this->receive(['doc_type' => 'account', 'customer' => 'X', 'amount' => 10, 'paid_at' => '2026-10-27'])->assertSessionHasErrors('paid_at');
        $this->receive(['doc_type' => 'sale', 'doc_id' => 999999999, 'amount' => 10])->assertNotFound();
        $this->assertSame(0, CrmDirectReceipt::count() + CustomerSalePayment::count());
    }

    public function test_a_sale_in_another_workspace_cannot_be_received_against(): void
    {
        $customer = $this->createCustomer('Own Customer');
        $sale = $this->createSale($customer);
        $other = CrmWorkspace::create(['name' => 'Other Test', 'slug' => 'other-test-' . uniqid(), 'is_active' => true]);
        CustomerSale::whereKey($sale->id)->update(['workspace_id' => $other->id]);

        $this->receive(['doc_type' => 'sale', 'doc_id' => $sale->id, 'amount' => 10])->assertNotFound();
        $this->assertNull($this->receiptRows()->firstWhere('ref', $sale->order_number));
    }

    // ───────────── Make Receipt on account / against an income due ─────────────

    public function test_payment_on_account_reduces_what_the_customer_owes_and_an_overpayment_becomes_an_advance(): void
    {
        $customer = $this->createCustomer('Account Customer');
        $this->createSale($customer, ['order_date' => '2026-09-01', 'quantity' => 10, 'unit_price' => 100]);   // 1,000

        $this->receive(['doc_type' => 'account', 'customer' => ' Account Customer ', 'amount' => 300, 'paid_at' => '2026-10-03', 'method' => 'Cash', 'note' => 'Cash at counter'])
            ->assertSessionHasNoErrors()->assertRedirect(route('crm.receipts.index'));
        $receipt = CrmDirectReceipt::firstOrFail();
        $this->assertSame(['on_account', CrmDirectReceipt::ON_ACCOUNT_CATEGORY, 'customer', 'Account Customer'],
            [$receipt->kind, $receipt->category, $receipt->payer_type, $receipt->payer_name]);
        $this->assertSame('Payment on account — Cash at counter', $receipt->description);

        // Not income.
        $this->assertMoney(1000, $this->cards($this->report('profit-loss'))['Total Income']);
        $this->assertNull($this->rowNamed($this->report('profit-loss'), CrmDirectReceipt::ON_ACCOUNT_CATEGORY));

        // Taken off what the customer owes.
        $aging = $this->report('receivables');
        $this->assertMoney(700, $this->cards($aging)['Total Outstanding']);
        $this->assertSame(['Account Customer', 0.0, 1000.0, 0.0, 0.0, 300.0, 700.0], array_map(fn ($v) => is_float($v) || is_int($v) ? (float) $v : $v, $this->tableRows($aging)[0]));
        $sheet = $this->report('balance-sheet');
        $this->assertMoney(700, $this->rowNamed($sheet, 'Customers (Accounts Receivable)')[1]);
        $this->assertMoney(0, $this->cards($sheet)['Not yet explained']);

        // Pays 1,000 more on account: owes nothing and has a 300 advance.
        $this->receive(['doc_type' => 'account', 'customer' => 'Account Customer', 'amount' => 1000, 'paid_at' => '2026-10-10', 'method' => 'Cash'])->assertSessionHasNoErrors();
        $sheet = $this->report('balance-sheet');
        $this->assertMoney(0, $this->rowNamed($sheet, 'Customers (Accounts Receivable)')[1] ?? 0);
        $this->assertMoney(300, $this->rowNamed($sheet, 'Customer Advances (paid ahead / on account)')[1]);
        $this->assertMoney(0, $this->cards($sheet)['Not yet explained']);
        $this->assertMoney(0, $this->cards($this->report('receivables'))['Total Outstanding']);

        $statement = $this->cards($this->report('customer-statement', '2026-01-01', '2026-10-26', ['customer' => 'Account Customer']));
        $this->assertMoney(1000, $statement['Invoiced']);
        $this->assertMoney(1300, $statement['Paid']);
        $this->assertMoney(-300, $statement['Balance Due']);

        // Receipts page lists them as "On Account".
        $rows = $this->receiptRows(['source' => 'account']);
        $this->assertCount(2, $rows);
        $this->assertSame(['On Account', 'Account Customer'], [$rows[0]->source_label, $rows[0]->customer]);
    }

    public function test_receipt_against_a_rent_due_from_make_receipt(): void
    {
        $this->post(route('crm.customers.store'), ['name' => 'Tenant Receipt', 'country' => 'UAE', 'currency' => 'AED', 'customer_type' => 'income',
            'income_head' => 'Rent', 'recurring_amount' => 5000, 'recurring_day' => 5, 'recurring_start' => '2026-10'])->assertRedirect();
        $due = CrmIncomeEntry::firstOrFail();
        $this->assertMoney(5000, $due->amount_due);

        $this->receive(['doc_type' => 'income', 'doc_id' => $due->id, 'amount' => 5000.01])->assertSessionHasErrors('amount');
        $this->receive(['doc_type' => 'income', 'doc_id' => $due->id, 'amount' => 2000, 'paid_at' => '2026-10-06', 'reference' => 'RENT-OCT'])->assertSessionHasNoErrors();
        $due->refresh();
        $this->assertMoney(2000, $due->amount_paid);
        $this->assertSame('2026-10-06', \Carbon\Carbon::parse($due->paid_at)->toDateString());
        $this->assertSame('RENT-OCT', $due->reference);

        $row = $this->receiptRows(['source' => 'income'])->first();
        $this->assertSame(['Tenant Receipt', 2000.0, 'Bank Transfer'], [$row->customer, $row->amount, $row->method]);
        $this->assertMoney(5000, $this->rowNamed($this->report('profit-loss'), 'Rent Income')[1]);
        $this->assertMoney(3000, $this->cards($this->report('receivables'))['Total Outstanding']);
    }

    /** "Collected" in the Sales report is every customer receipt in the period — a customer paying on account is one. */
    public function test_sales_report_collected_includes_customer_payments_on_account(): void
    {
        $customer = $this->createCustomer('Collected Customer');
        $sale = $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100]);
        $this->receive(['doc_type' => 'sale', 'doc_id' => $sale->id, 'amount' => 400, 'paid_at' => '2026-09-20'])->assertSessionHasNoErrors();
        $this->receive(['doc_type' => 'account', 'customer' => 'Collected Customer', 'amount' => 300, 'paid_at' => '2026-10-01'])->assertSessionHasNoErrors();

        // Cash flow counts both as cash received from customers...
        $this->assertMoney(700, $this->rowNamed($this->report('cash-flow'), 'Cash received from customers')[1]);
        // ...the Sales report should too.
        $this->assertMoney(700, $this->cards($this->report('sales'))['Collected']);
    }

    /** The "Other income" category suggestions should not offer the on-account bucket (that money is not income). */
    public function test_the_on_account_category_is_not_offered_as_an_other_income_category(): void
    {
        $this->receive(['doc_type' => 'account', 'customer' => 'Some Customer', 'amount' => 100])->assertSessionHasNoErrors();
        $this->makeDirectReceipt(['category' => 'Sponsorship Income']);

        $categories = $this->get(route('crm.receipts.index'))->assertOk()->viewData('receiptCategories');
        $this->assertContains('Sponsorship Income', $categories->all(), 'categories typed before are offered again');
        $this->assertNotContains(CrmDirectReceipt::ON_ACCOUNT_CATEGORY, $categories->all());
    }

    public function test_records_of_another_workspace_are_out_of_reach(): void
    {
        $other = CrmWorkspace::create(['name' => 'Other Test', 'slug' => 'other-test-' . uniqid(), 'is_active' => true]);
        $receipt = $this->makeDirectReceipt(['proof' => UploadedFile::fake()->create('slip.pdf', 5, 'application/pdf')]);
        $customer = $this->createCustomer('Foreign Customer');
        $sale = $this->createSale($customer);
        CrmDirectReceipt::whereKey($receipt->id)->update(['workspace_id' => $other->id]);
        CustomerSale::whereKey($sale->id)->update(['workspace_id' => $other->id]);

        $this->get(route('crm.direct_receipts.show', $receipt->id))->assertNotFound();
        $this->get(route('crm.direct_receipts.proof', $receipt->id))->assertNotFound();
        $this->put(route('crm.direct_receipts.update', $receipt->id), ['receipt_date' => '2026-10-20', 'category' => 'X', 'description' => 'X', 'payer_type' => 'other',
            'amount' => 1, 'currency' => 'AED', 'method' => 'Cash'])->assertNotFound();
        $this->delete(route('crm.direct_receipts.destroy', $receipt->id))->assertNotFound();
        $this->patch(route('crm.customer_sales.update_payment', $sale->id), ['paid_amount' => 5])->assertNotFound();
        $this->assertDatabaseHas('crm_direct_receipts', ['id' => $receipt->id, 'amount' => 500]);
        $this->assertDatabaseHas('customer_sales', ['id' => $sale->id, 'paid_amount' => 0]);
    }

    /** Editing a sale must not hand it to a customer of another workspace (the "exists" rule is not workspace-scoped). */
    public function test_a_sale_cannot_be_moved_to_a_customer_of_another_workspace(): void
    {
        $customer = $this->createCustomer('Home Customer');
        $sale = $this->createSale($customer);
        $other = CrmWorkspace::create(['name' => 'Other Test', 'slug' => 'other-test-' . uniqid(), 'is_active' => true]);
        $foreign = \App\CrmCustomer::create(['workspace_id' => $other->id, 'name' => 'Foreign Workspace Customer', 'country' => 'UAE', 'currency' => 'AED', 'customer_type' => 'sales']);

        $this->put(route('crm.customer_sales.update', $sale->id), [
            'customer_id' => $foreign->id, 'order_number' => $sale->order_number, 'order_date' => '2026-09-10', 'item_name' => 'Printed Boxes',
            'quantity' => 10, 'unit' => 'Pieces', 'unit_price' => 100, 'currency' => 'AED', 'order_status' => 'Confirmed',
        ]);

        $this->assertSame($customer->id, (int) $sale->fresh()->customer_id, 'the sale stays with its own customer');
    }

    /**
     * CURRENT BEHAVIOUR (question for the owner): a rent / income due keeps one paid date, so a second Make Receipt
     * on it moves the first payment to the new date — Receipts and Cash Flow then show it on the wrong day.
     */
    public function test_a_second_receipt_on_an_income_due_moves_the_first_payment_to_its_date(): void
    {
        $this->post(route('crm.customers.store'), ['name' => 'Tenant Twice', 'country' => 'UAE', 'currency' => 'AED', 'customer_type' => 'income',
            'income_head' => 'Rent', 'recurring_amount' => 5000, 'recurring_day' => 5, 'recurring_start' => '2026-10'])->assertRedirect();
        $due = CrmIncomeEntry::firstOrFail();
        $this->receive(['doc_type' => 'income', 'doc_id' => $due->id, 'amount' => 2000, 'paid_at' => '2026-10-06'])->assertSessionHasNoErrors();
        $this->receive(['doc_type' => 'income', 'doc_id' => $due->id, 'amount' => 1000, 'paid_at' => '2026-10-20'])->assertSessionHasNoErrors();

        $rows = $this->receiptRows(['source' => 'income']);
        $this->assertCount(1, $rows);
        $this->assertSame(['2026-10-20', 3000.0], [$rows[0]->date->toDateString(), $rows[0]->amount]);
        $this->assertNull($this->rowNamed($this->report('cash-flow', '2026-10-01', '2026-10-10'), 'Rent & other income received')[1],
            'the 2,000 received on 6 Oct is not in a cash flow ending 10 Oct');
    }

    // ───────────── Receipts page ─────────────

    public function test_receipts_page_lists_every_kind_newest_first_and_filters_them(): void
    {
        $customer = $this->createCustomer('Filter Customer');
        $sale = $this->createSale($customer, ['quantity' => 10, 'unit_price' => 100]);
        $this->receive(['doc_type' => 'sale', 'doc_id' => $sale->id, 'amount' => 400, 'paid_at' => '2026-09-15', 'method' => 'Cheque', 'reference' => 'CHQ-9']);
        $scrap = $this->makeDirectReceipt(['receipt_date' => '2026-10-20', 'amount' => 250, 'method' => 'Cash']);
        $this->receive(['doc_type' => 'account', 'customer' => 'Filter Customer', 'amount' => 100, 'paid_at' => '2026-10-02', 'method' => 'Card']);

        // A receipt in another workspace never shows here.
        $other = CrmWorkspace::create(['name' => 'Other Test', 'slug' => 'other-test-' . uniqid(), 'is_active' => true]);
        CrmDirectReceipt::create(['workspace_id' => $other->id, 'receipt_date' => '2026-10-21', 'category' => 'Scrap Sales', 'description' => 'Other workspace',
            'payer_type' => 'other', 'payer_name' => 'Elsewhere', 'amount' => 999, 'currency' => 'AED', 'method' => 'Cash']);

        $response = $this->get(route('crm.receipts.index'))->assertOk()->assertSee($scrap->number)->assertSee($sale->order_number)->assertDontSee('Elsewhere');
        $rows = collect($response->viewData('entries')->items());
        $this->assertSame(['direct', 'account', 'sale'], $rows->pluck('source')->all(), 'newest first');
        $totals = $response->viewData('totals');
        $this->assertMoney(750, $totals['received']);
        $this->assertSame(3, $totals['count']);
        $this->assertMoney(350, $totals['month'], 'October receipts');
        $this->assertSame(2, $totals['month_count']);
        $this->assertSame($scrap->number, $totals['last']->ref);
        $this->assertSame(['Card', 'Cash', 'Cheque'], $response->viewData('methodOptions')->all());

        $this->assertSame([$scrap->number], $this->receiptRows(['source' => 'direct'])->pluck('ref')->all());
        $this->assertSame([$sale->order_number], $this->receiptRows(['method' => 'cheque'])->pluck('ref')->all());
        $this->assertSame(2, $this->receiptRows(['customer' => 'Filter Customer'])->count());
        $this->assertSame([$sale->order_number], $this->receiptRows(['search' => 'CHQ-9'])->pluck('ref')->all());
        $this->assertSame(2, $this->receiptRows(['date_from' => '2026-10-01', 'date_to' => '2026-10-31'])->count());
        $this->assertSame(1, $this->receiptRows(['date_to' => '2026-09-30'])->count());
    }
}
