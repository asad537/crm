<?php

namespace Tests\Feature\Accounting;

use App\CrmBankAccount;
use App\CrmCustomer;
use App\CrmDirectReceipt;
use App\CustomerSale;
use App\Vendor;

/**
 * Helpers for the money-in / statements tests: each one goes through the same HTTP route a user would
 * (Add Customer, Add Sale, Make Receipt, Bank & Cash...) and returns the saved record.
 */
trait BuildsMoneyIn
{
    protected function createCustomer(string $name, array $extra = []): CrmCustomer
    {
        $this->post(route('crm.customers.store'), array_merge(['name' => $name, 'country' => 'United Arab Emirates', 'currency' => 'AED'], $extra))
            ->assertSessionHasNoErrors()->assertRedirect();

        return CrmCustomer::where('name', $name)->latest('id')->firstOrFail();
    }

    /** A customer sale through "Add Sale" (defaults: 10 × 100 = 1,000, no VAT, unpaid). */
    protected function createSale(CrmCustomer $customer, array $data = []): CustomerSale
    {
        $payload = array_merge([
            'customer_id' => $customer->id,
            'order_number' => 'CS-' . strtoupper(substr(uniqid(), -6)),
            'order_date' => '2026-09-10',
            'item_name' => 'Printed Boxes',
            'quantity' => 10,
            'unit' => 'Pieces',
            'unit_price' => 100,
            'currency' => 'AED',
            'order_status' => 'Confirmed',
        ], $data);
        $this->post(route('crm.customer_sales.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('crm.customer_sales.index', ['customer_id' => $customer->id]));

        return CustomerSale::where('order_number', $payload['order_number'])->firstOrFail();
    }

    protected function createBankAccount(string $name, array $extra = []): CrmBankAccount
    {
        $this->post(route('crm.bank_accounts.store'), array_merge([
            'name' => $name, 'type' => 'bank', 'bank_name' => 'Test Bank', 'currency' => 'AED',
            'opening_balance' => 0, 'opening_date' => '2026-01-01', 'is_active' => 1,
        ], $extra))->assertSessionHasNoErrors()->assertRedirect(route('crm.bank_accounts.index'));

        return CrmBankAccount::where('name', $name)->firstOrFail();
    }

    protected function createVendor(string $name): Vendor
    {
        $this->post(route('crm.vendors.store'), ['name' => $name])->assertSessionHasNoErrors()->assertRedirect();

        return Vendor::where('name', $name)->firstOrFail();
    }

    /** "Make Payment" on the Payments page (an expense payment). */
    protected function createExpense(array $data = []): \App\CrmExpense
    {
        $before = \App\CrmExpense::max('id') ?: 0;
        $this->post(route('crm.expenses.store'), array_merge([
            'expense_date' => '2026-09-05', 'category' => 'Rent', 'name' => 'Warehouse rent', 'paid_from' => 'Bank Transfer',
            'payee_type' => 'other', 'payee_name' => 'Landlord LLC', 'amount' => 2000, 'currency' => 'AED',
        ], $data))->assertSessionHasNoErrors()->assertRedirect(route('crm.payments.index'));

        return \App\CrmExpense::where('id', '>', $before)->latest('id')->firstOrFail();
    }

    /** A purchase invoice (supplier bill) with the given lines [name, category, qty, unit price], not paid. */
    protected function createPurchaseInvoice(Vendor $vendor, array $lines, array $data = []): \App\CrmPurchaseInvoice
    {
        $before = \App\CrmPurchaseInvoice::max('id') ?: 0;
        $items = array_map(fn ($l) => ['name' => $l[0], 'category' => $l[1], 'quantity' => $l[2], 'unit_price' => $l[3]], $lines);
        $this->post(route('crm.purchase_invoices.store'), array_merge([
            'vendor_id' => $vendor->id, 'issue_date' => '2026-08-15', 'currency' => 'AED', 'items' => $items,
        ], $data))->assertSessionHasNoErrors()->assertRedirect();

        return \App\CrmPurchaseInvoice::where('id', '>', $before)->latest('id')->firstOrFail();
    }

    /** An owner and their capital put in (cash / bank), through Owner's Capital. */
    protected function addCapital(string $owner, float $amount, array $entry = []): \App\CrmCapitalEntry
    {
        $this->post(route('crm.owner_capital.owners.store'), ['name' => $owner, 'share_percent' => 100])->assertSessionHasNoErrors();
        $ownerId = \App\CrmCapitalOwner::where('name', $owner)->value('id');
        $this->post(route('crm.owner_capital.entries.store'), array_merge([
            'owner_id' => $ownerId, 'entry_date' => '2026-02-01', 'type' => 'capital', 'amount' => $amount, 'method' => 'Bank Transfer',
        ], $entry))->assertSessionHasNoErrors()->assertRedirect();

        return \App\CrmCapitalEntry::where('owner_id', $ownerId)->latest('id')->firstOrFail();
    }

    /** "Make Receipt → Other income" (money received without an invoice). */
    protected function makeDirectReceipt(array $data = []): CrmDirectReceipt
    {
        $before = CrmDirectReceipt::max('id') ?: 0;
        $this->post(route('crm.direct_receipts.store'), array_merge([
            'receipt_date' => '2026-10-20', 'category' => 'Scrap Sales', 'description' => 'Scrap paper sold',
            'payer_type' => 'other', 'payer_name' => 'Scrap Dealer LLC', 'amount' => 500, 'currency' => 'AED', 'method' => 'Cash',
        ], $data))->assertSessionHasNoErrors()->assertRedirect(route('crm.receipts.index'));

        return CrmDirectReceipt::where('id', '>', $before)->latest('id')->firstOrFail();
    }

    /** "Make Receipt" from a customer: against a sale / income due, or on account. */
    protected function receive(array $data)
    {
        return $this->post(route('crm.receipts.receive'), array_merge(['paid_at' => '2026-10-01', 'method' => 'Bank Transfer'], $data));
    }

    /** Receipts page rows (all pages), as the controller hands them to the view. */
    protected function receiptRows(array $query = [])
    {
        $response = $this->get(route('crm.receipts.index', $query))->assertOk();

        return collect($response->viewData('entries')->items());
    }

    /** A report's cards keyed by label. */
    protected function cards(array $report)
    {
        return collect($report['cards'])->keyBy('label')->map(fn ($c) => $c['value']);
    }

    /** Plain rows of a report table (style rows unwrapped; link cells turned into their text). */
    protected function tableRows(array $report, int $table = 0): array
    {
        return array_map(function ($row) {
            $cells = isset($row['_style']) ? $row['cells'] : $row;
            return array_map(fn ($c) => is_array($c) && array_key_exists('text', $c) ? $c['text'] : $c, $cells);
        }, $report['tables'][$table]['rows']);
    }

    /** The row of a report table whose first cell (or the given column) is $label. */
    protected function rowNamed(array $report, string $label, int $table = 0, int $column = 0): ?array
    {
        foreach ($this->tableRows($report, $table) as $row) {
            if (($row[$column] ?? null) === $label) return $row;
        }

        return null;
    }
}
