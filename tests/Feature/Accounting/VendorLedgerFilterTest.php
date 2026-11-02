<?php

namespace Tests\Feature\Accounting;

use App\Support\CrmWorkspaceContext;
use App\Vendor;
use App\VendorPurchase;

/** The Paid / Partial / Unpaid, category and expense type filters on one vendor's page (its ledger). */
class VendorLedgerFilterTest extends AccountingTestCase
{
    use PurchaseInvoiceHelpers;

    private function purchase(Vendor $vendor, string $item, float $total, float $paid, array $extra = []): VendorPurchase
    {
        CrmWorkspaceContext::set($this->workspace->id);
        $status = $paid <= 0 ? 'Unpaid' : ($paid < $total ? 'Partial' : 'Paid');

        return VendorPurchase::create(array_merge([
            'vendor_id' => $vendor->id, 'vendor_name' => $vendor->name, 'purchase_date' => '2026-10-10', 'item_name' => $item,
            'category' => 'Paper', 'expense_type' => 'Production Expense', 'currency' => 'AED', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => $total, 'subtotal' => $total,
            'total_amount' => $total, 'paid_amount' => $paid, 'balance_amount' => $total - $paid, 'payment_status' => $status,
        ], $extra));
    }

    /** Ledger rows on the vendor page for these filters: [kind, description, balance]. */
    private function ledger(Vendor $vendor, array $filters = []): array
    {
        $ledger = $this->get(route('crm.vendor_purchases.index', array_merge(['vendor_id' => $vendor->id], $filters)))->assertOk()->viewData('ledger');

        return collect($ledger->items())->map(fn ($e) => [$e->kind, $e->desc, $e->balance])->all();
    }

    public function test_the_payment_filter_keeps_only_matching_purchases_and_their_payments(): void
    {
        $vendor = $this->vendor('Filter Vendor');
        $this->purchase($vendor, 'Paid Board', 100, 100, ['purchase_date' => '2026-10-01']);
        $partial = $this->purchase($vendor, 'Partial Board', 200, 50, ['purchase_date' => '2026-10-02']);
        $this->purchase($vendor, 'Unpaid Board', 300, 0, ['purchase_date' => '2026-10-03']);
        $partial->payments()->create(['vendor_id' => $vendor->id, 'amount' => 50, 'paid_at' => '2026-10-04', 'method' => 'Cash']);
        // A bill on the Purchase Invoices tab, half paid.
        $invoice = $this->createBill($vendor, [$this->line('Ink', 'Ink', 1, 400)], ['issue_date' => '2026-10-05']);
        $this->pay($invoice, 100, '2026-10-06')->assertSessionHasNoErrors();

        $this->assertCount(6, $this->ledger($vendor), 'no filter: 3 purchases, the invoice and 2 payments');

        $paid = $this->ledger($vendor, ['payment_status' => 'Paid']);
        $this->assertSame([['purchase', 'Paid Board', 100.0]], $paid);

        // Balances stay the true running balance of the whole account.
        $partialRows = $this->ledger($vendor, ['payment_status' => 'Partial']);
        $this->assertSame(['payment', 'invoice', 'payment', 'purchase'], array_column($partialRows, 0));
        $this->assertSame([850.0, 950.0, 550.0, 300.0], array_column($partialRows, 2));

        $this->assertSame([['purchase', 'Unpaid Board', 600.0]], $this->ledger($vendor, ['payment_status' => 'Unpaid']));

        // Export gives the same rows as the screen.
        $export = $this->post(route('crm.vendor_purchases.export'), ['format' => 'excel', 'vendor_id' => $vendor->id, 'payment_status' => 'Unpaid'])->assertOk();
        $this->assertSame([['Unpaid Board', 600.0]], collect($export->viewData('ledger'))->map(fn ($e) => [$e->desc, $e->balance])->all());
        $this->assertMoney(300, $export->viewData('totalDebit'));

        $this->pay($invoice, 300, '2026-10-07')->assertSessionHasNoErrors();
        $this->assertSame(['payment', 'payment', 'invoice', 'purchase'], array_column($this->ledger($vendor, ['payment_status' => 'Paid']), 0), 'a fully paid bill counts as Paid');
    }

    public function test_the_category_and_expense_type_filters_work_on_the_vendor_page(): void
    {
        $vendor = $this->vendor('Type Vendor');
        $this->purchase($vendor, 'Board', 100, 0);
        $this->purchase($vendor, 'Glue', 40, 0, ['category' => 'Consumables', 'expense_type' => 'Consumable Expense']);
        $this->createBill($vendor, [$this->line('Ink', 'Consumables', 1, 60)], ['issue_date' => '2026-10-11']);

        $this->assertSame(['Board'], array_column($this->ledger($vendor, ['expense_type' => 'Production Expense']), 1));
        $this->assertSame(['Glue'], array_column($this->ledger($vendor, ['expense_type' => 'Consumable Expense']), 1), 'invoices have no expense type');

        $consumables = $this->ledger($vendor, ['category' => 'Consumables']);
        $this->assertSame(['invoice', 'purchase'], array_column($consumables, 0));
        $this->assertStringContainsString('Ink', $consumables[0][1]);
        $this->assertSame('Glue', $consumables[1][1]);
    }
}
