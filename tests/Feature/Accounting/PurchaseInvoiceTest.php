<?php

namespace Tests\Feature\Accounting;

use App\CrmDeletionLog;
use App\CrmPurchaseInvoice;
use App\CrmPurchaseInvoiceItem;
use App\CrmPurchaseInvoicePayment;
use App\Support\CrmWorkspaceContext;
use App\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Purchase Invoices (supplier bills): entering them, paying them, and how they reach Payments, the vendor ledger and the reports. */
class PurchaseInvoiceTest extends AccountingTestCase
{
    use PurchaseInvoiceHelpers;

    public function test_a_bill_with_several_lines_is_created_with_its_total(): void
    {
        $vendor = $this->vendor('Gulf Board Trading');
        $response = $this->post(route('crm.purchase_invoices.store'), $this->billPayload($vendor, [
            $this->line('Kraft board 300gsm', 'Raw Material', 2.5, 320.40),   // 801.00
            $this->line('Glue', 'Consumables', 4, 12.5),                      // 50.00
            $this->line('A4 paper', 'Office Supplies', 3, 19.999),            // price rounds to 20.00 → 60.00
        ], ['reference' => 'GBT-7781', 'project' => 'Ramadan boxes', 'description' => 'Board and sundries']));

        CrmWorkspaceContext::set($this->workspace->id);
        $invoice = CrmPurchaseInvoice::with('items')->latest('id')->firstOrFail();
        $response->assertRedirect(route('crm.purchase_invoices.show', $invoice->id));
        $this->assertSame($invoice->number . ' created for Gulf Board Trading (911.00 AED).', session('success'));

        $this->assertSame($this->workspace->id, (int) $invoice->workspace_id);
        $this->assertSame($vendor->id, (int) $invoice->vendor_id);
        $this->assertSame('Gulf Board Trading', $invoice->vendor_name);
        $this->assertSame($this->admin->id, (int) $invoice->created_by);
        $this->assertSame('2026-10-01', $invoice->issue_date->toDateString());
        $this->assertSame('2026-11-30', $invoice->due_date->toDateString());
        $this->assertMoney(911.00, $invoice->total);
        $this->assertSame(['Kraft board 300gsm', 'Glue', 'A4 paper'], $invoice->items->pluck('name')->all());
        $this->assertSame([0, 1, 2], $invoice->items->pluck('position')->map(fn ($p) => (int) $p)->all());
        $this->assertSame(['801.00', '50.00', '60.00'], $invoice->items->pluck('line_total')->all());
        $this->assertSame('20.00', $invoice->items[2]->unit_price);
        $this->assertSame(['Raw Material' => 801.0, 'Office Supplies' => 60.0, 'Consumables' => 50.0], $invoice->categoryTotals());
        $this->assertSame('unpaid', $invoice->status());

        $this->get(route('crm.purchase_invoices.show', $invoice->id))->assertOk()
            ->assertSee($invoice->number)->assertSee('Gulf Board Trading')->assertSee('GBT-7781')->assertSee('Ramadan boxes')
            ->assertSee('Unpaid')->assertSee('911.00 AED')->assertSee('By category:')->assertSee('No payments recorded yet.');
        $this->get(route('crm.purchase_invoices.index'))->assertOk()->assertSee($invoice->number)->assertSee('911.00');
    }

    public function test_lines_removed_on_the_form_still_save_in_order(): void
    {
        // Removing rows in the browser leaves gaps in the item keys.
        $invoice = $this->createBill($this->vendor(), [
            0 => $this->line('First', 'Rent', 1, 100),
            3 => $this->line('Second', 'Rent', 1, 200),
            7 => $this->line('Third', 'Utilities', 1, 300),
        ]);

        $invoice = $this->fresh($invoice);
        $this->assertSame(['First', 'Second', 'Third'], $invoice->items->pluck('name')->all());
        $this->assertSame([0, 1, 2], $invoice->items->pluck('position')->map(fn ($p) => (int) $p)->all());
        $this->assertMoney(600, $invoice->total);
    }

    public function test_invoice_numbers_come_from_the_invoice_id(): void
    {
        $vendor = $this->vendor();
        $first = $this->createBill($vendor, [$this->line('One', 'Rent', 1, 10)]);
        $second = $this->createBill($vendor, [$this->line('Two', 'Rent', 1, 20)]);

        // The number is the database id, so a new workspace does not start at PI-0001 (see report).
        $this->assertSame('PI-' . str_pad($first->id, 4, '0', STR_PAD_LEFT), $first->number);
        $this->assertSame('PI-' . str_pad($second->id, 4, '0', STR_PAD_LEFT), $second->number);
        $this->assertGreaterThan($first->id, $second->id);
        $this->get(route('crm.purchase_invoices.index'))->assertOk()->assertSeeInOrder([$second->number, $first->number]);
    }

    public function test_bad_bills_are_rejected_with_a_message(): void
    {
        $vendor = $this->vendor();
        $foreignVendor = Vendor::create(['name' => 'Elsewhere LLC', 'workspace_id' => $this->otherWorkspace()->id]);
        $good = $this->line('Board', 'Raw Material', 1, 100);
        $cases = [
            'vendor_id' => ['vendor_id' => null],
            'issue_date' => ['issue_date' => null],
            'due_date' => ['issue_date' => '2026-10-10', 'due_date' => '2026-10-09'],
            'currency' => ['currency' => 'PKR'],
            'items' => ['items' => []],
            'items.0.name' => ['items' => [array_merge($good, ['name' => ''])]],
            'items.0.category' => ['items' => [array_merge($good, ['category' => ''])]],
            'items.0.quantity' => ['items' => [array_merge($good, ['quantity' => 0])]],
            'items.1.quantity' => ['items' => [$good, array_merge($good, ['quantity' => -2])]],
            'items.0.unit_price' => ['items' => [array_merge($good, ['unit_price' => -5])]],
            'items.1.unit_price' => ['items' => [$good, array_merge($good, ['unit_price' => 'abc'])]],
        ];
        foreach ($cases as $field => $overrides) {
            $this->from(route('crm.purchase_invoices.create'))
                ->post(route('crm.purchase_invoices.store'), $this->billPayload($vendor, [$good], $overrides))
                ->assertRedirect(route('crm.purchase_invoices.create'))
                ->assertSessionHasErrors($field);
        }
        $this->post(route('crm.purchase_invoices.store'), array_diff_key($this->billPayload($vendor, [$good]), ['items' => 1]))
            ->assertSessionHasErrors(['items' => 'Add at least one item.']);
        // A vendor id that is not on this workspace's Vendors list.
        foreach ([$foreignVendor->id, 999999999] as $vendorId) {
            $this->post(route('crm.purchase_invoices.store'), $this->billPayload($vendor, [$good], ['vendor_id' => $vendorId]))
                ->assertSessionHasErrors(['vendor_id' => 'Pick a vendor from the Vendors list.']);
        }

        CrmWorkspaceContext::set($this->workspace->id);
        $this->assertSame(0, CrmPurchaseInvoice::count());
    }

    public function test_editing_a_bill_replaces_its_lines_vendor_and_total(): void
    {
        $vendor = $this->vendor('First Supplier');
        $newVendor = $this->vendor('Second Supplier');
        $invoice = $this->createBill($vendor, [$this->line('Old line', 'Rent', 1, 500), $this->line('Old two', 'Rent', 1, 50)]);

        $this->get(route('crm.purchase_invoices.edit', $invoice->id))->assertOk()
            ->assertSee('Edit ' . $invoice->number)->assertSee('Old line')->assertSee('Save Changes');

        $this->updateBill($invoice, $newVendor, [
            $this->line('New board', 'Raw Material', 10, 25),
            $this->line('Courier', 'Transport & Fuel', 1, 35.5),
        ], ['issue_date' => '2026-10-05', 'due_date' => '2026-10-31', 'currency' => 'USD', 'description' => 'Corrected bill'])
            ->assertRedirect(route('crm.purchase_invoices.show', $invoice->id));
        $this->assertSame($invoice->number . ' updated.', session('success'));

        $invoice = $this->fresh($invoice);
        $this->assertSame('Second Supplier', $invoice->vendor_name);
        $this->assertSame($newVendor->id, (int) $invoice->vendor_id);
        $this->assertSame('2026-10-05', $invoice->issue_date->toDateString());
        $this->assertSame('USD', $invoice->currency);
        $this->assertMoney(285.50, $invoice->total);
        $this->assertSame(['New board', 'Courier'], $invoice->items->pluck('name')->all());
        $this->assertSame(2, CrmPurchaseInvoiceItem::where('purchase_invoice_id', $invoice->id)->count(), 'old lines are gone');
    }

    public function test_a_failed_edit_leaves_the_bill_as_it_was(): void
    {
        $vendor = $this->vendor();
        $invoice = $this->createBill($vendor, [$this->line('Keep me', 'Rent', 1, 700)]);

        $this->from(route('crm.purchase_invoices.edit', $invoice->id))
            ->updateBill($invoice, $vendor, [$this->line('Broken', 'Rent', 0, 100)])
            ->assertRedirect(route('crm.purchase_invoices.edit', $invoice->id))
            ->assertSessionHasErrors('items.0.quantity');

        $invoice = $this->fresh($invoice);
        $this->assertSame(['Keep me'], $invoice->items->pluck('name')->all());
        $this->assertMoney(700, $invoice->total);
    }

    public function test_deleting_a_bill_removes_its_lines_and_payments_and_logs_it(): void
    {
        $invoice = $this->createBill($this->vendor('Deleted Vendor Co'), [$this->line('Ink', 'Production', 2, 150)]);
        $this->pay($invoice, 100)->assertSessionHasNoErrors();

        $this->delete(route('crm.purchase_invoices.destroy', $invoice->id))->assertRedirect(route('crm.purchase_invoices.index'));
        $this->assertSame($invoice->number . ' deleted.', session('success'));

        $this->assertDatabaseMissing('crm_purchase_invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('crm_purchase_invoice_items', ['purchase_invoice_id' => $invoice->id]);
        $this->assertDatabaseMissing('crm_purchase_invoice_payments', ['purchase_invoice_id' => $invoice->id]);
        $log = CrmDeletionLog::where('entity_type', 'purchase_invoice')->where('entity_id', $invoice->id)->firstOrFail();
        $this->assertSame($this->workspace->id, (int) $log->workspace_id);
        $this->assertSame($invoice->number . ' — Deleted Vendor Co', $log->entity_label);
        $this->assertCount(1, $log->snapshot['payments']);
        $this->get(route('crm.purchase_invoices.show', $invoice->id))->assertNotFound();

        // Nothing of it is left in the books.
        $this->assertMoney(0, $this->sheet()['invoice']);
        $this->assertMoney(0, $this->pl()['net']);
    }

    public function test_part_payments_move_the_status_from_unpaid_to_paid_and_back(): void
    {
        $invoice = $this->createBill($this->vendor(), [$this->line('Board', 'Raw Material', 1, 1000)], ['due_date' => '2026-11-30']);
        $this->assertSame('Unpaid', $this->fresh($invoice)->statusLabel());

        $this->pay($invoice, 400, '2026-10-05', ['method' => 'Cash', 'reference' => 'CHQ-1', 'note' => 'first part'])
            ->assertRedirect(route('crm.purchase_invoices.show', $invoice->id));
        $this->assertSame('Payment of 400.00 recorded. Balance due: 600.00. It shows under Payments and in the vendor ledger.', session('success'));
        $first = CrmPurchaseInvoicePayment::where('purchase_invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame($this->workspace->id, (int) $first->workspace_id);
        $this->assertSame(['400.00', '2026-10-05', 'Cash', 'CHQ-1', 'first part', $this->admin->id],
            [$first->amount, $first->paid_at->toDateString(), $first->method, $first->reference, $first->note, (int) $first->created_by]);
        $this->assertSame('Partially Paid', $this->fresh($invoice)->statusLabel());
        $this->get(route('crm.purchase_invoices.show', $invoice->id))->assertOk()->assertSee('Partially Paid')->assertSee('600.00 AED')->assertSee('first part');

        $this->pay($invoice, 600, '2026-10-20')->assertSessionHasNoErrors();
        $this->assertStringContainsString($invoice->number . ' is now fully paid.', session('success'));
        $paid = $this->fresh($invoice);
        $this->assertSame('paid', $paid->status());
        $this->assertTrue($paid->isFullyPaid());
        $this->assertMoney(0, $paid->balanceDue());
        $this->get(route('crm.purchase_invoices.index', ['status' => 'paid']))->assertOk()->assertSee($invoice->number);
        $this->get(route('crm.purchase_invoices.index', ['status' => 'unpaid']))->assertOk()->assertDontSee($invoice->number);

        $second = $paid->payments->last();
        $this->delete(route('crm.purchase_invoices.payments.destroy', [$invoice->id, $second->id]))->assertRedirect();
        $this->assertSame('Payment removed from ' . $invoice->number . '.', session('success'));
        $this->assertTrue(CrmDeletionLog::where('entity_type', 'purchase_invoice_payment')->where('entity_id', $second->id)->exists());
        $this->assertSame('Partially Paid', $this->fresh($invoice)->statusLabel());
        $this->assertMoney(600, $this->fresh($invoice)->balanceDue());

        $this->delete(route('crm.purchase_invoices.payments.destroy', [$invoice->id, $first->id]))->assertRedirect();
        $this->assertSame('Unpaid', $this->fresh($invoice)->statusLabel());
    }

    public function test_an_unpaid_bill_past_its_due_date_is_overdue(): void
    {
        $vendor = $this->vendor();
        $late = $this->createBill($vendor, [$this->line('Late', 'Rent', 1, 800)], ['issue_date' => '2026-09-01', 'due_date' => '2026-10-25']);
        $dueToday = $this->createBill($vendor, [$this->line('Today', 'Rent', 1, 50)], ['issue_date' => '2026-09-01', 'due_date' => '2026-10-26']);
        $this->pay($late, 300)->assertSessionHasNoErrors();

        $this->assertSame('Overdue', $this->fresh($late)->statusLabel());
        $this->assertSame('Unpaid', $this->fresh($dueToday)->statusLabel(), 'due today is not overdue yet');
        $index = $this->get(route('crm.purchase_invoices.index', ['status' => 'overdue']))->assertOk();
        $this->assertSame([$late->id], collect($index->viewData('entries')->items())->pluck('id')->all());
        $this->assertMoney(500, $index->viewData('totals')['overdue']);
        $this->assertSame(1, $index->viewData('totals')['overdue_count']);

        $this->pay($late, 500)->assertSessionHasNoErrors();
        $this->assertSame('Paid', $this->fresh($late)->statusLabel());
    }

    public function test_bad_payments_are_rejected(): void
    {
        $invoice = $this->createBill($this->vendor(), [$this->line('Board', 'Raw Material', 1, 1000)]);
        $cases = [
            'amount' => [['amount' => 0], ['amount' => -50], ['amount' => ''], ['amount' => 'ten']],
            'paid_at' => [['paid_at' => '2026-10-27'], ['paid_at' => ''], ['paid_at' => 'not a date']],
            'method' => [['method' => 'Bitcoin']],
        ];
        foreach ($cases as $field => $variants) {
            foreach ($variants as $variant) {
                $this->from(route('crm.purchase_invoices.show', $invoice->id))
                    ->post(route('crm.purchase_invoices.payments.store', $invoice->id), array_merge(['amount' => 100, 'paid_at' => '2026-10-26', 'method' => 'Cash'], $variant))
                    ->assertRedirect(route('crm.purchase_invoices.show', $invoice->id))
                    ->assertSessionHasErrors($field);
            }
        }
        $this->assertSame(0, CrmPurchaseInvoicePayment::where('purchase_invoice_id', $invoice->id)->count());
        $this->assertSame('Unpaid', $this->fresh($invoice)->statusLabel());
    }

    public function test_paying_more_than_the_balance_due_is_an_advance_to_the_vendor(): void
    {
        $vendor = $this->vendor('Advance Vendor');
        $invoice = $this->createBill($vendor, [$this->line('Board', 'Raw Material', 1, 1000)], ['issue_date' => '2026-10-01', 'due_date' => null]);
        $this->pay($invoice, 400, '2026-10-05')->assertSessionHasNoErrors();

        $this->pay($invoice, 700, '2026-10-20')->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, '100.00 extra kept as an advance to the vendor'));

        $invoice = $this->fresh($invoice);
        $this->assertMoney(1100, $invoice->paidTotal());
        $this->assertSame('Overpaid', $invoice->statusLabel());
        $this->get(route('crm.purchase_invoices.show', $invoice->id))->assertOk()->assertSee('Advance to Vendor');
        $this->assertSame('Invoice 1,000.00 · Fully paid + advance 100.00', $this->paymentRows()[0]->invoice_status);

        // The extra 100 is an asset (paid ahead), not lost in the balancing figure.
        $sheet = $this->sheet();
        $this->assertMoney(0, $sheet['invoice']);
        $this->assertMoney(100, $sheet['vendor_advances']);
        $this->assertMoney(0, $sheet['balancing']);
        // Before the second payment there was no advance yet.
        $this->assertMoney(600, $this->sheet('2026-10-10')['invoice']);
        $this->assertMoney(0, $this->sheet('2026-10-10')['vendor_advances'] ?? 0);

        // The vendor's next bill uses the advance up: 300 billed − 100 paid ahead = 200 still owed.
        $this->createBill($vendor, [$this->line('Ink', 'Raw Material', 1, 300)], ['issue_date' => '2026-10-22', 'due_date' => null]);
        $sheet = $this->sheet();
        $this->assertMoney(200, $sheet['invoice']);
        $this->assertMoney(0, $sheet['vendor_advances']);
        $this->assertMoney(0, $sheet['balancing']);
        $this->assertMoney(200, $this->cards($this->report('payables'))['Total Outstanding']);
    }

    public function test_a_payment_made_before_the_bill_date_is_an_advance_until_then(): void
    {
        $invoice = $this->createBill($this->vendor(), [$this->line('Board', 'Raw Material', 1, 500)], ['issue_date' => '2026-10-15', 'due_date' => null]);
        $this->pay($invoice, 500, '2026-10-05')->assertSessionHasNoErrors();

        $before = $this->sheet('2026-10-10');
        $this->assertMoney(500, $before['vendor_advances']);
        $this->assertMoney(0, $before['invoice']);
        $this->assertMoney(0, $before['balancing']);
        $after = $this->sheet('2026-10-20');
        $this->assertMoney(0, $after['vendor_advances']);
        $this->assertMoney(0, $after['balancing']);
    }

    public function test_a_payment_can_only_be_removed_from_its_own_bill(): void
    {
        $vendor = $this->vendor();
        $billA = $this->createBill($vendor, [$this->line('A', 'Rent', 1, 100)]);
        $billB = $this->createBill($vendor, [$this->line('B', 'Rent', 1, 100)]);
        $this->pay($billA, 60)->assertSessionHasNoErrors();
        $payment = $this->fresh($billA)->payments->first();

        $this->delete(route('crm.purchase_invoices.payments.destroy', [$billB->id, $payment->id]))->assertNotFound();
        $this->assertDatabaseHas('crm_purchase_invoice_payments', ['id' => $payment->id]);
    }

    public function test_the_payments_page_shows_each_payment_with_the_balance_due_now(): void
    {
        $vendor = $this->vendor('Payments Page Vendor');
        $invoice = $this->createBill($vendor, [
            $this->line('Board', 'Raw Material', 1, 800),
            $this->line('Pens', 'Office Supplies', 4, 50),
        ], ['reference' => 'PPV-12']);

        $this->pay($invoice, 400, '2026-10-05', ['method' => 'Cash'])->assertSessionHasNoErrors();
        $rows = $this->paymentRows();
        $this->assertCount(1, $rows);
        $row = $rows[0];
        $this->assertSame(['purchase', 'Purchase Invoice', 'Payments Page Vendor', $invoice->number . ' · PPV-12', 'Raw Material, Office Supplies', 'Cash', 400.0, 'AED'],
            [$row->source, $row->source_label, $row->party, $row->ref, $row->category, $row->method, $row->amount, $row->currency]);
        $this->assertSame('Invoice 1,000.00 · Balance due 600.00', $row->invoice_status);
        $this->assertMoney(600, $row->invoice_due);
        $this->assertSame(route('crm.purchase_invoices.show', $invoice->id), $row->url);
        $this->get(route('crm.payments.index'))->assertOk()->assertSee('Invoice 1,000.00 · Balance due 600.00');

        $this->pay($invoice, 600, '2026-10-20')->assertSessionHasNoErrors();
        $rows = $this->paymentRows();
        $this->assertSame(['Invoice 1,000.00 · Fully paid', 'Invoice 1,000.00 · Fully paid'], $rows->pluck('invoice_status')->all(), 'every row shows the balance today');
        $this->assertSame([600.0, 400.0], $rows->pluck('amount')->all(), 'newest first');
        $page = $this->get(route('crm.payments.index'))->assertOk()->assertSee('Fully paid');
        $this->assertMoney(1000, $page->viewData('totals')['purchase']);
        $this->assertMoney(1000, $page->viewData('totals')['paid']);

        $this->delete(route('crm.purchase_invoices.payments.destroy', [$invoice->id, $this->fresh($invoice)->payments->last()->id]))->assertRedirect();
        $rows = $this->paymentRows();
        $this->assertCount(1, $rows);
        $this->assertSame('Invoice 1,000.00 · Balance due 600.00', $rows[0]->invoice_status);
    }

    public function test_the_vendor_ledger_shows_the_bill_and_its_payments(): void
    {
        $vendor = $this->vendor('Ledger Vendor');
        $invoice = $this->createBill($vendor, [$this->line('Board', 'Raw Material', 1, 1000)], ['issue_date' => '2026-10-01']);
        $this->pay($invoice, 400, '2026-10-05')->assertSessionHasNoErrors();

        $page = $this->get(route('crm.vendor_purchases.index', ['vendor_id' => $vendor->id]))->assertOk();
        $this->assertMoney(600, $page->viewData('ledgerBalance'));
        $ledger = collect($page->viewData('ledger')->items());
        $this->assertSame(['payment', 'invoice'], $ledger->pluck('kind')->all());
        $this->assertMoney(1000, $ledger[1]->debit);
        $this->assertMoney(400, $ledger[0]->credit);
        $this->assertStringContainsString('Partially Paid', $ledger[1]->sub);
        $directory = collect($page->viewData('vendors')->items())->firstWhere('id', $vendor->id);
        $this->assertMoney(600, $directory->pi_due);

        $this->pay($invoice, 600)->assertSessionHasNoErrors();
        $this->assertMoney(0, $this->get(route('crm.vendor_purchases.index', ['vendor_id' => $vendor->id]))->viewData('ledgerBalance'));
    }

    public function test_bill_lines_reach_profit_and_loss_by_category(): void
    {
        $vendor = $this->vendor('P&L Vendor');
        $this->createBill($vendor, [
            $this->line('Board', 'Raw Material', 2, 400),           // Cost of Sales
            $this->line('Plates', 'Production', 1, 300),            // Cost of Sales
            $this->line('Pens', 'Office Supplies', 4, 55),          // Operating Expenses
            $this->line('Diesel', 'transport & fuel', 1, 80),       // typed in lower case
        ], ['issue_date' => '2026-10-05']);
        $this->createBill($vendor, [$this->line('Last year', 'Rent', 1, 999)], ['issue_date' => '2025-12-15', 'due_date' => null]);

        $pl = $this->pl();
        $this->assertEquals(['Stock & Raw Material' => 800.0, 'Production Purchases' => 300.0], $pl['cos']);
        $this->assertEquals(['Office Supplies' => 220.0, 'Transport & Fuel' => 80.0], $pl['opex']);
        $this->assertMoney(-1100, $pl['gross']);
        $this->assertMoney(-1400, $pl['net']);
        $cards = $this->cards($this->report('profit-loss'));
        $this->assertMoney(1400, $cards['Total Costs']);
        $this->assertMoney(-1400, $cards['Net Profit / (Loss)']);
        $this->assertMoney(1400, $this->cards($this->report('expenses'))['Total Spend']);

        // The 2025 bill is last year's cost: in retained earnings, not in this year's profit.
        $sheet = $this->sheet();
        $this->assertMoney(-999, $sheet['retained']);
        $this->assertMoney(-1400, $sheet['profit']);

        // Accrual: paying the bill does not change the profit.
        CrmWorkspaceContext::set($this->workspace->id);
        $this->pay(CrmPurchaseInvoice::where('issue_date', '2026-10-05')->firstOrFail(), 1400)->assertSessionHasNoErrors();
        $this->assertMoney(-1400, $this->pl()['net']);
    }

    public function test_an_unpaid_bill_is_payable_on_the_balance_sheet_until_it_is_paid(): void
    {
        $invoice = $this->createBill($this->vendor(), [$this->line('Rent Sept', 'Rent', 1, 1000)], ['issue_date' => '2026-09-15', 'due_date' => '2026-09-30']);

        $this->assertMoney(0, $this->sheet('2026-09-14')['invoice'], 'not billed yet');
        $this->assertMoney(1000, $this->sheet('2026-09-30')['invoice']);

        $this->pay($invoice, 400, '2026-10-10')->assertSessionHasNoErrors();
        $this->assertMoney(1000, $this->sheet('2026-09-30')['invoice'], 'paid after that date');
        $sheet = $this->sheet();
        $this->assertMoney(600, $sheet['invoice']);
        $this->assertMoney(-400, $sheet['cash']);
        $this->assertMoney(0, $sheet['balancing'], 'books are fully explained');
        $this->assertMoney(0, $this->cards($this->report('balance-sheet'))['Not yet explained']);
        $this->assertMoney(0, $this->cards($this->report('trial-balance'))['Difference']);

        $this->pay($invoice, 600, '2026-10-20')->assertSessionHasNoErrors();
        $sheet = $this->sheet();
        $this->assertMoney(0, $sheet['invoice']);
        $this->assertMoney(-1000, $sheet['cash']);
        $this->assertMoney(0, $sheet['balancing']);
    }

    public function test_bill_payments_are_operating_cash_out_in_the_cash_flow(): void
    {
        $invoice = $this->createBill($this->vendor(), [$this->line('Board', 'Raw Material', 1, 1000)], ['issue_date' => '2026-09-01']);
        $this->pay($invoice, 250, '2026-09-20')->assertSessionHasNoErrors();
        $this->pay($invoice, 400, '2026-10-10')->assertSessionHasNoErrors();

        $flow = $this->cashFlow('2026-10-01', '2026-10-26');
        $this->assertMoney(-400, $flow['Paid on purchase invoices']);
        $this->assertMoney(0, $flow['Purchase of fixed assets']);
        $this->assertMoney(-400, $flow['Net cash from operating activities']);
        $this->assertMoney(0, $flow['Net cash from investing activities']);
        $cards = $this->cards($this->report('cash-flow', '2026-10-01', '2026-10-26'));
        $this->assertMoney(-250, $cards['Cash & Bank at Start']);
        $this->assertMoney(-650, $cards['Cash & Bank at End']);
    }

    public function test_payables_aging_buckets_open_bills_by_bill_date(): void
    {
        $vendorA = $this->vendor('Aging Vendor A');
        $vendorB = $this->vendor('Aging Vendor B');
        $recent = $this->createBill($vendorA, [$this->line('Recent', 'Rent', 1, 1000)], ['issue_date' => '2026-10-20', 'due_date' => null]);   // 6 days
        $this->createBill($vendorA, [$this->line('Older', 'Rent', 1, 500)], ['issue_date' => '2026-08-01', 'due_date' => null]);              // 86 days
        $this->createBill($vendorB, [$this->line('Oldest', 'Rent', 1, 300)], ['issue_date' => '2026-06-01', 'due_date' => null]);             // 147 days
        $settled = $this->createBill($vendorB, [$this->line('Settled', 'Rent', 1, 200)], ['issue_date' => '2026-07-01', 'due_date' => null]);
        $this->pay($recent, 250)->assertSessionHasNoErrors();
        $this->pay($settled, 200)->assertSessionHasNoErrors();

        $report = $this->report('payables');
        $cards = $this->cards($report);
        $this->assertMoney(1550, $cards['Total Outstanding']);
        $this->assertMoney(750, $cards['0–30 days']);
        $this->assertMoney(500, $cards['31–90 days']);
        $this->assertMoney(300, $cards['Over 90 days']);
        $byVendor = collect($report['tables'][0]['rows'])->keyBy(0);
        $this->assertEquals(['Aging Vendor A', 750, 0, 500, 0, 0, 1250], $byVendor['Aging Vendor A']);
        $this->assertEquals(['Aging Vendor B', 0, 0, 0, 300, 0, 300], $byVendor['Aging Vendor B']);
        $this->assertCount(3, $report['tables'][1]['rows'], 'the paid bill is not an open item');
        $this->assertMoney(1550, $this->sheet()['invoice']);
    }

    public function test_payables_aging_on_an_earlier_date_ignores_later_payments(): void
    {
        $invoice = $this->createBill($this->vendor('As Of Vendor'), [$this->line('Board', 'Raw Material', 1, 1000)], ['issue_date' => '2026-09-01', 'due_date' => null]);
        $this->pay($invoice, 1000, '2026-10-10')->assertSessionHasNoErrors();

        // On 30 Sep nothing had been paid: the balance sheet says 1,000 was owed, the aging must agree.
        $this->assertMoney(1000, $this->sheet('2026-09-30')['invoice']);
        $this->assertMoney(1000, $this->cards($this->report('payables', '2026-09-01', '2026-09-30'))['Total Outstanding']);
    }

    public function test_a_sales_agent_cannot_open_or_change_purchase_invoices(): void
    {
        $vendor = $this->vendor();
        $invoice = $this->createBill($vendor, [$this->line('Board', 'Raw Material', 1, 100)]);
        $this->pay($invoice, 10)->assertSessionHasNoErrors();
        $payment = $this->fresh($invoice)->payments->first();

        $this->signIn($this->makeUser('sales'));
        $this->get(route('crm.purchase_invoices.index'))->assertForbidden();
        $this->get(route('crm.purchase_invoices.create'))->assertForbidden();
        $this->post(route('crm.purchase_invoices.store'), $this->billPayload($vendor, [$this->line('X', 'Rent', 1, 1)]))->assertForbidden();
        $this->get(route('crm.purchase_invoices.show', $invoice->id))->assertForbidden();
        $this->get(route('crm.purchase_invoices.edit', $invoice->id))->assertForbidden();
        $this->updateBill($invoice, $vendor, [$this->line('X', 'Rent', 1, 1)])->assertForbidden();
        $this->get(route('crm.purchase_invoices.attachment', $invoice->id))->assertForbidden();
        $this->pay($invoice, 10)->assertForbidden();
        $this->delete(route('crm.purchase_invoices.payments.destroy', [$invoice->id, $payment->id]))->assertForbidden();
        $this->delete(route('crm.purchase_invoices.destroy', $invoice->id))->assertForbidden();
        $this->get(route('crm.payments.index'))->assertForbidden();

        $invoice = $this->fresh($invoice);
        $this->assertCount(1, $invoice->items);
        $this->assertMoney(10, $invoice->paidTotal());
    }

    public function test_a_sales_manager_can_enter_and_pay_bills_but_only_admin_or_accounts_delete(): void
    {
        $vendor = $this->vendor();
        $this->signIn($this->makeUser('sales_manager'));
        $invoice = $this->createBill($vendor, [$this->line('Board', 'Raw Material', 1, 300)]);
        $this->pay($invoice, 100)->assertSessionHasNoErrors();
        $payment = $this->fresh($invoice)->payments->first();
        $this->get(route('crm.purchase_invoices.show', $invoice->id))->assertOk()->assertDontSee('Remove payment')
            ->assertDontSee('Delete ' . $invoice->number . ' and its');

        $this->delete(route('crm.purchase_invoices.payments.destroy', [$invoice->id, $payment->id]))->assertForbidden();
        $this->delete(route('crm.purchase_invoices.destroy', $invoice->id))->assertForbidden();
        $this->assertDatabaseHas('crm_purchase_invoice_payments', ['id' => $payment->id]);

        $this->signIn($this->makeUser('accounts'));
        $this->get(route('crm.purchase_invoices.show', $invoice->id))->assertOk()->assertSee('Remove payment');
        $this->delete(route('crm.purchase_invoices.payments.destroy', [$invoice->id, $payment->id]))->assertRedirect();
        $this->delete(route('crm.purchase_invoices.destroy', $invoice->id))->assertRedirect(route('crm.purchase_invoices.index'));
        $this->assertDatabaseMissing('crm_purchase_invoices', ['id' => $invoice->id]);
    }

    public function test_another_workspaces_bills_stay_out_of_sight_and_out_of_the_books(): void
    {
        $other = $this->otherWorkspace();
        $foreignVendor = Vendor::create(['name' => 'Foreign Vendor', 'workspace_id' => $other->id]);
        $foreign = CrmPurchaseInvoice::create(['workspace_id' => $other->id, 'vendor_id' => $foreignVendor->id, 'vendor_name' => 'Foreign Vendor',
            'issue_date' => '2026-10-01', 'currency' => 'AED', 'total' => 5000]);
        $foreign->items()->create(['name' => 'Foreign board', 'category' => 'Foreign Category', 'quantity' => 1, 'unit_price' => 5000, 'line_total' => 5000, 'position' => 0]);
        CrmPurchaseInvoicePayment::create(['purchase_invoice_id' => $foreign->id, 'workspace_id' => $other->id, 'amount' => 1000, 'paid_at' => '2026-10-02']);
        $mine = $this->createBill($this->vendor(), [$this->line('Mine', 'Rent', 1, 100)]);

        $this->get(route('crm.purchase_invoices.show', $foreign->id))->assertNotFound();
        $this->get(route('crm.purchase_invoices.edit', $foreign->id))->assertNotFound();
        $this->pay($foreign, 10)->assertNotFound();
        $this->delete(route('crm.purchase_invoices.destroy', $foreign->id))->assertNotFound();
        $index = $this->get(route('crm.purchase_invoices.index'))->assertOk();
        $this->assertSame([$mine->id], collect($index->viewData('entries')->items())->pluck('id')->all());
        $this->assertMoney(100, $index->viewData('totals')['invoiced']);
        $this->assertNotContains('Foreign Category', $this->get(route('crm.purchase_invoices.create'))->viewData('categoryOptions')->all());
        $this->assertCount(0, $this->paymentRows());

        $this->assertMoney(100, $this->sheet()['invoice']);
        $this->assertMoney(-100, $this->pl()['net']);
        $this->assertMoney(100, $this->cards($this->report('payables'))['Total Outstanding']);
    }

    public function test_categories_already_used_are_suggested_on_the_form(): void
    {
        $this->createBill($this->vendor(), [$this->line('Stickers', 'Labels & Stickers', 1, 10)]);

        $options = $this->get(route('crm.purchase_invoices.create'))->assertOk()->viewData('categoryOptions')->all();
        $this->assertContains('Raw Material', $options);
        $this->assertContains('Labels & Stickers', $options);
        $this->assertSame(count($options), count(array_unique($options)));
    }

    public function test_a_bill_image_is_stored_replaced_and_removed_with_the_bill(): void
    {
        $this->useTempDisk();
        $vendor = $this->vendor();
        $invoice = $this->createBill($vendor, [$this->line('Board', 'Raw Material', 1, 100)], [
            'attachment' => UploadedFile::fake()->create('supplier-bill.pdf', 40, 'application/pdf'),
        ]);
        $invoice = $this->fresh($invoice);
        $this->assertSame('supplier-bill.pdf', $invoice->attachment_name);
        Storage::disk('local')->assertExists($invoice->attachment_path);
        $this->get(route('crm.purchase_invoices.show', $invoice->id))->assertOk()->assertSee('supplier-bill.pdf');
        $this->get(route('crm.purchase_invoices.attachment', $invoice->id))->assertOk();
        $oldPath = $invoice->attachment_path;

        $this->updateBill($invoice, $vendor, [$this->line('Board', 'Raw Material', 1, 100)], [
            'attachment' => UploadedFile::fake()->create('corrected.pdf', 40, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $invoice = $this->fresh($invoice);
        $this->assertSame('corrected.pdf', $invoice->attachment_name);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($invoice->attachment_path);

        // Saving again without a file keeps the current one.
        $this->updateBill($invoice, $vendor, [$this->line('Board', 'Raw Material', 1, 120)])->assertSessionHasNoErrors();
        $this->assertSame('corrected.pdf', $this->fresh($invoice)->attachment_name);

        $this->from(route('crm.purchase_invoices.edit', $invoice->id))
            ->updateBill($invoice, $vendor, [$this->line('Board', 'Raw Material', 1, 100)], ['attachment' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('attachment');

        $this->delete(route('crm.purchase_invoices.destroy', $invoice->id))->assertRedirect();
        Storage::disk('local')->assertMissing($invoice->attachment_path);

        $plain = $this->createBill($vendor, [$this->line('No image', 'Rent', 1, 1)]);
        $this->get(route('crm.purchase_invoices.attachment', $plain->id))->assertNotFound();
    }

    /** Purchase-invoice rows on the Payments page, newest first. */
    protected function paymentRows()
    {
        return collect($this->get(route('crm.payments.index', ['source' => 'purchase']))->assertOk()->viewData('entries')->items())->values();
    }
}
