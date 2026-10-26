<?php

namespace Tests\Feature\Accounting;

use App\CrmDeletionLog;
use App\CrmFixedAsset;
use App\CrmPurchaseInvoice;
use App\Support\CrmWorkspaceContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/** Fixed Assets: registered with a name and rate, bought on a purchase invoice, and how they reach the balance sheet, cash flow and assets report. */
class FixedAssetTest extends AccountingTestCase
{
    use PurchaseInvoiceHelpers;

    public function test_a_new_asset_has_a_name_and_rate_and_waits_for_its_invoice(): void
    {
        $this->post(route('crm.fixed_assets.store'), ['name' => '  Die cutting machine ', 'depreciation_rate' => 12.5])
            ->assertRedirect(route('crm.fixed_assets.index'));
        $asset = $this->latestAsset();
        $this->assertSame($asset->number . ' — Die cutting machine added. Now buy it on Purchase Invoices with "Add Fixed Asset".', session('success'));

        $this->assertSame('FA-' . str_pad($asset->id, 4, '0', STR_PAD_LEFT), $asset->number);
        $this->assertSame([$this->workspace->id, 'Die cutting machine', '12.50', '1.000', '0.00', 'AED', 'Purchase Invoice', null, $this->admin->id, '2026-10-26'],
            [(int) $asset->workspace_id, $asset->name, $asset->depreciation_rate, $asset->quantity, $asset->amount, $asset->currency,
                $asset->payment_method, $asset->purchase_invoice_id, (int) $asset->created_by, $asset->purchase_date->toDateString()]);
        $this->assertTrue($asset->isAwaitingInvoice());
        $this->assertSame('12.5', $asset->rateLabel());
        $this->assertMoney(0, $asset->cost());

        $page = $this->get(route('crm.fixed_assets.index'))->assertOk()->assertSee('Die cutting machine')->assertSee('Not bought yet')->assertSee('12.5% / year')
            ->assertSee(route('crm.purchase_invoices.create', ['asset' => $asset->id]), false);
        $this->assertSame(['count' => 1, 'cost' => 0.0, 'depreciation' => 0.0, 'book_value' => 0.0, 'awaiting' => 1], $page->viewData('totals'));

        // Not bought yet: no cost anywhere in the books.
        $sheet = $this->sheet();
        $this->assertMoney(0, $sheet['fixed']);
        $this->assertMoney(0, $sheet['credit_assets']);
        $this->assertMoney(0, $this->cards($this->report('assets'))['Total Cost']);
        $this->assertCount(0, $this->report('assets')['tables'][0]['rows']);
    }

    public function test_bad_assets_are_rejected(): void
    {
        $cases = [
            'name' => [['name' => ''], ['name' => str_repeat('x', 256)]],
            'depreciation_rate' => [['depreciation_rate' => ''], ['depreciation_rate' => -1], ['depreciation_rate' => 'abc'], ['depreciation_rate' => 101]],
        ];
        foreach ($cases as $field => $variants) {
            foreach ($variants as $variant) {
                $this->from(route('crm.fixed_assets.index'))
                    ->post(route('crm.fixed_assets.store'), array_merge(['name' => 'Laptop', 'depreciation_rate' => 33], $variant))
                    ->assertRedirect(route('crm.fixed_assets.index'))
                    ->assertSessionHasErrors($field);
            }
        }
        $this->post(route('crm.fixed_assets.store'), ['name' => 'Laptop', 'depreciation_rate' => 150])
            ->assertSessionHasErrors(['depreciation_rate' => 'Depreciation rate is a yearly percentage (0–100).']);
        CrmWorkspaceContext::set($this->workspace->id);
        $this->assertSame(0, CrmFixedAsset::count());
    }

    public function test_an_asset_name_and_rate_can_be_changed(): void
    {
        $asset = $this->newAsset('Office chairs', 10);
        $this->from(route('crm.fixed_assets.index'))
            ->put(route('crm.fixed_assets.update', $asset->id), ['name' => 'Office chairs (x6)', 'depreciation_rate' => 15])
            ->assertRedirect(route('crm.fixed_assets.index'));
        $this->assertSame($asset->number . ' updated.', session('success'));

        $asset = $asset->fresh();
        $this->assertSame(['Office chairs (x6)', '15.00', 'Purchase Invoice', '0.00'], [$asset->name, $asset->depreciation_rate, $asset->payment_method, $asset->amount]);
        $this->put(route('crm.fixed_assets.update', $asset->id), ['name' => '', 'depreciation_rate' => 15])->assertSessionHasErrors('name');
        $this->put(route('crm.fixed_assets.update', 999999999), ['name' => 'X', 'depreciation_rate' => 1])->assertNotFound();
    }

    public function test_buying_an_asset_on_a_bill_gives_it_quantity_cost_supplier_and_date(): void
    {
        $asset = $this->newAsset('Die cutting machine', 10);
        $vendor = $this->vendor('Machine Traders LLC');
        $this->assertContains($asset->id, $this->get(route('crm.purchase_invoices.create', ['asset' => $asset->id]))->assertOk()
            ->assertSee($asset->number . ' — Die cutting machine')->viewData('assetOptions')->pluck('id')->all());

        $invoice = $this->createBill($vendor, [
            array_merge($this->assetLine($asset, 2, 1500), ['name' => 'typed over', 'category' => 'Raw Material']),   // the asset decides both
            $this->line('Delivery', 'Transport & Fuel', 1, 100),
        ], ['issue_date' => '2026-10-01', 'reference' => 'MT-55']);

        $invoice = $this->fresh($invoice);
        $this->assertMoney(3100, $invoice->total);
        $line = $invoice->items[0];
        $this->assertSame([$asset->id, 'Die cutting machine', 'Fixed Assets', '3000.00'], [(int) $line->fixed_asset_id, $line->name, $line->category, $line->line_total]);

        $asset = $asset->fresh();
        $this->assertSame([$invoice->id, 'Purchase Invoice', '2.000', '1500.00', '3000.00', 'AED', 'Machine Traders LLC', 'MT-55', '2026-10-01'],
            [(int) $asset->purchase_invoice_id, $asset->payment_method, $asset->quantity, $asset->unit_price, $asset->amount, $asset->currency,
                $asset->supplier_name, $asset->reference, $asset->purchase_date->toDateString()]);
        $this->assertFalse($asset->isAwaitingInvoice());
        $this->assertSame('2', $asset->quantityLabel());
        $this->assertMoney(3000, $asset->cost());

        // Not offered on another bill any more, still offered when editing its own bill.
        $this->assertNotContains($asset->id, $this->get(route('crm.purchase_invoices.create'))->viewData('assetOptions')->pluck('id')->all());
        $this->assertContains($asset->id, $this->get(route('crm.purchase_invoices.edit', $invoice->id))->assertOk()->viewData('assetOptions')->pluck('id')->all());
        $this->get(route('crm.purchase_invoices.show', $invoice->id))->assertOk()->assertSee($asset->number);
        $page = $this->get(route('crm.fixed_assets.index'))->assertOk()->assertSee($invoice->number)->assertSee('Unpaid')->assertDontSee('Not bought yet');
        $this->assertMoney(3000, $page->viewData('totals')['cost']);
        $this->assertSame(0, $page->viewData('totals')['awaiting']);

        // The asset is on the balance sheet, not a cost; only the delivery line is an expense.
        $pl = $this->pl();
        $this->assertEquals([], $pl['cos']);
        $this->assertEquals(['Transport & Fuel' => 100.0], $pl['opex']);
        $sheet = $this->sheet();
        $this->assertMoney(3000, $sheet['fixed']);
        $this->assertMoney(3100, $sheet['invoice']);
        $this->assertMoney(0, $sheet['credit_assets'], 'bought on an invoice, not "on credit"');
        $this->assertMoney(0, $sheet['balancing']);
        $this->assertMoney(0, $this->sheet('2026-09-30')['fixed'], 'not bought yet on that day');
    }

    public function test_an_asset_cannot_go_on_two_bills_or_twice_on_one(): void
    {
        $asset = $this->newAsset('Laminator', 15);
        $vendor = $this->vendor();
        $first = $this->createBill($vendor, [$this->assetLine($asset, 1, 900)]);

        $this->post(route('crm.purchase_invoices.store'), $this->billPayload($vendor, [$this->assetLine($asset, 1, 900)]))
            ->assertSessionHasErrors(['items' => $asset->number . ' (Laminator) is already on purchase invoice ' . $first->number . '.']);

        $other = $this->newAsset('Guillotine', 10);
        $this->post(route('crm.purchase_invoices.store'), $this->billPayload($vendor, [$this->assetLine($other, 1, 100), $this->assetLine($other, 1, 100)]))
            ->assertSessionHasErrors(['items' => 'The same fixed asset is added twice.']);

        $foreign = CrmFixedAsset::create(['workspace_id' => $this->otherWorkspace()->id, 'purchase_date' => '2026-10-01', 'name' => 'Foreign press',
            'depreciation_rate' => 10, 'quantity' => 1, 'unit_price' => 0, 'amount' => 0, 'currency' => 'AED', 'payment_method' => 'Purchase Invoice']);
        $this->post(route('crm.purchase_invoices.store'), $this->billPayload($vendor, [$this->assetLine($foreign, 1, 100)]))
            ->assertSessionHasErrors(['items' => 'A fixed asset on this invoice no longer exists. Remove that line.']);

        CrmWorkspaceContext::set($this->workspace->id);
        $this->assertSame([$first->id], CrmPurchaseInvoice::pluck('id')->all());
        $this->assertNull($foreign->fresh()->purchase_invoice_id);
    }

    public function test_editing_the_bill_updates_the_asset_and_taking_it_off_releases_it(): void
    {
        $asset = $this->newAsset('Forklift', 20);
        $vendor = $this->vendor('Forklift Supplier');
        $invoice = $this->createBill($vendor, [$this->assetLine($asset, 1, 5000), $this->line('Fuel', 'Transport & Fuel', 1, 100)], ['issue_date' => '2026-10-01']);

        // Bill date, quantity and price follow the bill.
        $this->updateBill($invoice, $vendor, [$this->assetLine($asset, 2, 4800), $this->line('Fuel', 'Transport & Fuel', 1, 100)], ['issue_date' => '2026-10-03'])
            ->assertSessionHasNoErrors();
        $asset = $asset->fresh();
        $this->assertSame(['2026-10-03', '2.000', '9600.00'], [$asset->purchase_date->toDateString(), $asset->quantity, $asset->amount]);
        $this->assertMoney(9600, $this->sheet()['fixed']);

        // Taken off the bill: back to "not bought yet" with no cost.
        $this->updateBill($this->fresh($invoice), $vendor, [$this->line('Fuel', 'Transport & Fuel', 1, 100)])->assertSessionHasNoErrors();
        $asset = $asset->fresh();
        $this->assertSame([null, 'Purchase Invoice', '1.000', '0.00', '0.00', null, null],
            [$asset->purchase_invoice_id, $asset->payment_method, $asset->quantity, $asset->unit_price, $asset->amount, $asset->supplier_name, $asset->reference]);
        $this->assertTrue($asset->isAwaitingInvoice());
        $sheet = $this->sheet();
        $this->assertMoney(0, $sheet['fixed']);
        $this->assertMoney(100, $sheet['invoice']);
        $this->assertMoney(0, $sheet['balancing']);

        // Put back on, then the whole bill deleted: the asset stays in the register, not bought.
        $this->updateBill($this->fresh($invoice), $vendor, [$this->assetLine($asset, 1, 5000)])->assertSessionHasNoErrors();
        $this->assertSame($invoice->id, (int) $asset->fresh()->purchase_invoice_id);
        $this->delete(route('crm.purchase_invoices.destroy', $invoice->id))->assertRedirect(route('crm.purchase_invoices.index'));
        $asset = $asset->fresh();
        $this->assertNotNull($asset);
        $this->assertTrue($asset->isAwaitingInvoice());
        $this->assertMoney(0, $asset->amount);
        $this->assertMoney(0, $this->sheet()['fixed']);
    }

    public function test_only_an_asset_that_is_not_on_a_bill_can_be_deleted(): void
    {
        $onBill = $this->newAsset('Printer', 25);
        $invoice = $this->createBill($this->vendor(), [$this->assetLine($onBill, 1, 2000)]);
        $this->from(route('crm.fixed_assets.index'))->delete(route('crm.fixed_assets.destroy', $onBill->id))->assertRedirect(route('crm.fixed_assets.index'));
        $this->assertSame($onBill->number . ' is on purchase invoice ' . $invoice->number . '. Remove it from that invoice first, then delete it here.', session('error'));
        $this->assertDatabaseHas('crm_fixed_assets', ['id' => $onBill->id]);

        $loose = $this->newAsset('Spare desk', 10);
        $this->from(route('crm.fixed_assets.index'))->delete(route('crm.fixed_assets.destroy', $loose->id))->assertRedirect(route('crm.fixed_assets.index'));
        $this->assertSame($loose->number . ' deleted.', session('success'));
        $this->assertDatabaseMissing('crm_fixed_assets', ['id' => $loose->id]);
        $log = CrmDeletionLog::where('entity_type', 'fixed_asset')->where('entity_id', $loose->id)->firstOrFail();
        $this->assertSame($loose->number . ' — Spare desk', $log->entity_label);
        $this->assertSame($this->workspace->id, (int) $log->workspace_id);
    }

    public function test_depreciation_is_straight_line_from_the_bill_date_and_never_more_than_cost(): void
    {
        $van = $this->newAsset('Delivery van', 20);
        $laptop = $this->newAsset('Old laptop', 100);
        $vendor = $this->vendor();
        $this->createBill($vendor, [$this->assetLine($van, 1, 3000)], ['issue_date' => '2025-10-26', 'due_date' => null]);     // exactly one year ago
        $this->createBill($vendor, [$this->assetLine($laptop, 1, 1000)], ['issue_date' => '2024-10-01', 'due_date' => null]);  // over two years ago

        $van = $van->fresh();
        $this->assertMoney(600, $van->yearlyDepreciation());
        $this->assertMoney(600, $van->accumulatedDepreciation());
        $this->assertMoney(2400, $van->bookValue());
        $this->assertMoney(299.18, $van->accumulatedDepreciation(Carbon::parse('2026-04-26')), '182 of 365 days');
        $this->assertMoney(0, $van->accumulatedDepreciation(Carbon::parse('2025-10-26')), 'nothing on the day it is bought');
        $this->assertMoney(1000, $laptop->fresh()->accumulatedDepreciation(), 'capped at cost');
        $this->assertMoney(0, $laptop->fresh()->bookValue());

        $page = $this->get(route('crm.fixed_assets.index'))->assertOk();
        $this->assertSame(['count' => 2, 'cost' => 4000.0, 'depreciation' => 1600.0, 'book_value' => 2400.0, 'awaiting' => 0], $page->viewData('totals'));

        $report = $this->report('assets');
        $this->assertEquals(['Total Cost' => 4000, 'Depreciation to Date' => 1600, 'Book Value' => 2400, 'Bought in Period' => 0], $this->cards($report));
        $register = collect($report['tables'][0]['rows']);
        $this->assertSame([$van->number . ' — Delivery van', $laptop->number . ' — Old laptop'], $register->pluck('0.text')->all());
        $this->assertSame(['20%', '100%'], $register->pluck(3)->all());
        $this->assertEquals([[3000, 600, 2400], [1000, 1000, 0]], $register->map(fn ($r) => [$r[4], $r[5], $r[6]])->all());
        $this->assertEquals(['Total', null, null, null, 4000, 1600, 2400], $report['tables'][0]['footer']);

        // As of the end of 2025: the van's 66 days (108.49) plus the laptop at its cost; the van was bought in that year.
        $lastYear = $this->cards($this->report('assets', '2025-01-01', '2025-12-31'));
        $this->assertMoney(1108.49, $lastYear['Depreciation to Date']);
        $this->assertMoney(3000, $lastYear['Bought in Period']);

        // Depreciation is not posted to Profit & Loss (yet), and the assets are no cost either.
        $this->assertMoney(0, $this->pl()['net']);
        $this->assertMoney(0, $this->pl('2024-01-01', '2025-12-31')['net']);
        $this->assertMoney(4000, $this->sheet()['fixed']);
    }

    public function test_paying_an_asset_bill_splits_the_cash_between_investing_and_operating(): void
    {
        $asset = $this->newAsset('Cutting table', 10);
        $invoice = $this->createBill($this->vendor(), [$this->assetLine($asset, 1, 600), $this->line('Blades', 'Office Supplies', 4, 100)], ['issue_date' => '2026-10-01']);
        $this->pay($invoice, 500, '2026-10-10')->assertSessionHasNoErrors();

        $flow = $this->cashFlow('2026-10-01', '2026-10-26');
        $this->assertMoney(-300, $flow['Purchase of fixed assets'], '60% of the bill is the asset');
        $this->assertMoney(-200, $flow['Paid on purchase invoices']);
        $this->assertMoney(-300, $flow['Net cash from investing activities']);
        $this->assertMoney(-200, $flow['Net cash from operating activities']);

        $sheet = $this->sheet();
        $this->assertMoney(600, $sheet['fixed']);
        $this->assertMoney(500, $sheet['invoice']);
        $this->assertMoney(-500, $sheet['cash']);
        $this->assertMoney(-400, $sheet['profit']);
        $this->assertMoney(0, $sheet['balancing']);
    }

    public function test_older_assets_paid_in_cash_or_bought_on_credit_reach_the_books(): void
    {
        $cash = $this->legacyAsset('Old forklift', 'Cash', 2000, '2026-09-10');
        $credit = $this->legacyAsset('Old server', 'Credit', 1200, '2026-09-20');

        $sheet = $this->sheet();
        $this->assertMoney(3200, $sheet['fixed']);
        $this->assertMoney(1200, $sheet['credit_assets']);
        $this->assertMoney(-2000, $sheet['cash']);
        $this->assertMoney(0, $sheet['balancing']);
        $early = $this->sheet('2026-09-15');
        $this->assertMoney(2000, $early['fixed']);
        $this->assertMoney(0, $early['credit_assets'], 'not bought yet on that day');

        $flow = $this->cashFlow('2026-09-01', '2026-09-30');
        $this->assertMoney(-2000, $flow['Purchase of fixed assets'], 'only the cash one is money out');
        $this->assertMoney(-2000, $flow['Net cash from investing activities']);

        $report = $this->report('assets');
        $this->assertMoney(3200, $this->cards($report)['Total Cost']);
        $this->assertSame(['Credit', 'Cash'], collect($report['tables'][0]['rows'])->pluck(2)->all(), 'no invoice: the payment method is shown');
        $this->get(route('crm.fixed_assets.index'))->assertOk()->assertSee('Old forklift')->assertSee('Old server')->assertDontSee('Not bought yet');
        $this->assertFalse($cash->isAwaitingInvoice() || $credit->isAwaitingInvoice());
    }

    public function test_an_older_asset_put_on_a_bill_and_taken_off_again_loses_its_own_cost(): void
    {
        // Current behaviour (see report): the picker offers older assets too, and releasing one resets it to "not bought yet".
        $old = $this->legacyAsset('Old compressor', 'Cash', 1500, '2026-08-01');
        $this->assertContains($old->id, $this->get(route('crm.purchase_invoices.create'))->viewData('assetOptions')->pluck('id')->all());
        $vendor = $this->vendor();
        $invoice = $this->createBill($vendor, [$this->assetLine($old, 1, 1500), $this->line('Oil', 'Consumables', 1, 20)]);
        $this->updateBill($invoice, $vendor, [$this->line('Oil', 'Consumables', 1, 20)])->assertSessionHasNoErrors();

        $old = $old->fresh();
        $this->assertSame(['Purchase Invoice', '0.00'], [$old->payment_method, $old->amount]);
        $this->assertTrue($old->isAwaitingInvoice());
    }

    public function test_an_older_assets_receipt_can_be_downloaded(): void
    {
        $this->useTempDisk();
        Storage::disk('local')->put('fixed-assets/forklift-receipt.pdf', '%PDF-1.4 test');
        $asset = $this->legacyAsset('Receipt forklift', 'Cash', 900, '2026-09-01', ['receipt_path' => 'fixed-assets/forklift-receipt.pdf', 'receipt_name' => 'forklift-receipt.pdf']);

        $this->get(route('crm.fixed_assets.index'))->assertOk()->assertSee(route('crm.fixed_assets.receipt', $asset->id), false);
        $response = $this->get(route('crm.fixed_assets.receipt', $asset->id))->assertOk();
        $this->assertSame('%PDF-1.4 test', file_get_contents($response->baseResponse->getFile()->getPathname()));

        $noReceipt = $this->newAsset('No receipt', 10);
        $this->get(route('crm.fixed_assets.receipt', $noReceipt->id))->assertNotFound();
        $missingFile = $this->legacyAsset('Lost receipt', 'Cash', 10, '2026-09-01', ['receipt_path' => 'fixed-assets/gone.pdf', 'receipt_name' => 'gone.pdf']);
        $this->get(route('crm.fixed_assets.receipt', $missingFile->id))->assertNotFound();

        $this->delete(route('crm.fixed_assets.destroy', $asset->id))->assertRedirect();
        Storage::disk('local')->assertMissing('fixed-assets/forklift-receipt.pdf');
    }

    public function test_the_register_search_finds_an_asset_by_name_or_number(): void
    {
        $press = $this->newAsset('Offset press', 10);
        $this->newAsset('Stapler', 20);

        $byName = $this->get(route('crm.fixed_assets.index', ['search' => 'offset']))->assertOk();
        $this->assertSame([$press->id], collect($byName->viewData('entries')->items())->pluck('id')->all());
        $byNumber = $this->get(route('crm.fixed_assets.index', ['search' => strtolower($press->number)]))->assertOk();
        $this->assertSame([$press->id], collect($byNumber->viewData('entries')->items())->pluck('id')->all());
        $this->assertSame(1, $byNumber->viewData('totals')['count']);
    }

    public function test_a_sales_agent_cannot_use_fixed_assets(): void
    {
        $asset = $this->newAsset('Guarded asset', 10);
        $this->signIn($this->makeUser('sales'));

        $this->get(route('crm.fixed_assets.index'))->assertForbidden();
        $this->post(route('crm.fixed_assets.store'), ['name' => 'Sneaky', 'depreciation_rate' => 10])->assertForbidden();
        $this->put(route('crm.fixed_assets.update', $asset->id), ['name' => 'Renamed', 'depreciation_rate' => 10])->assertForbidden();
        $this->get(route('crm.fixed_assets.receipt', $asset->id))->assertForbidden();
        $this->delete(route('crm.fixed_assets.destroy', $asset->id))->assertForbidden();
        $this->assertSame('Guarded asset', $asset->fresh()->name);
        CrmWorkspaceContext::set($this->workspace->id);
        $this->assertSame(1, CrmFixedAsset::count());
    }

    public function test_a_sales_manager_can_add_assets_but_only_admin_or_accounts_delete(): void
    {
        $this->signIn($this->makeUser('sales_manager'));
        $this->post(route('crm.fixed_assets.store'), ['name' => 'Manager asset', 'depreciation_rate' => 10])->assertRedirect(route('crm.fixed_assets.index'));
        $asset = $this->latestAsset();
        $this->put(route('crm.fixed_assets.update', $asset->id), ['name' => 'Manager asset 2', 'depreciation_rate' => 12])->assertRedirect();
        $this->get(route('crm.fixed_assets.index'))->assertOk()->assertDontSee(route('crm.fixed_assets.destroy', $asset->id), false);
        $this->delete(route('crm.fixed_assets.destroy', $asset->id))->assertForbidden();
        $this->assertDatabaseHas('crm_fixed_assets', ['id' => $asset->id, 'name' => 'Manager asset 2']);

        $this->signIn($this->makeUser('accounts'));
        $this->delete(route('crm.fixed_assets.destroy', $asset->id))->assertRedirect();
        $this->assertDatabaseMissing('crm_fixed_assets', ['id' => $asset->id]);
    }

    public function test_another_workspaces_assets_stay_out_of_the_register_and_the_books(): void
    {
        $foreign = CrmFixedAsset::create(['workspace_id' => $this->otherWorkspace()->id, 'purchase_date' => '2026-09-01', 'name' => 'Foreign machine',
            'depreciation_rate' => 10, 'quantity' => 1, 'unit_price' => 7000, 'amount' => 7000, 'currency' => 'AED', 'payment_method' => 'Credit']);
        $this->legacyAsset('My machine', 'Cash', 100, '2026-09-01');

        $page = $this->get(route('crm.fixed_assets.index'))->assertOk()->assertDontSee('Foreign machine');
        $this->assertSame(1, $page->viewData('totals')['count']);
        $this->put(route('crm.fixed_assets.update', $foreign->id), ['name' => 'Hijacked', 'depreciation_rate' => 1])->assertNotFound();
        $this->delete(route('crm.fixed_assets.destroy', $foreign->id))->assertNotFound();
        $this->get(route('crm.fixed_assets.receipt', $foreign->id))->assertNotFound();
        $this->assertSame('Foreign machine', $foreign->fresh()->name);

        $sheet = $this->sheet();
        $this->assertMoney(100, $sheet['fixed']);
        $this->assertMoney(0, $sheet['credit_assets']);
        $this->assertMoney(100, $this->cards($this->report('assets'))['Total Cost']);
    }

    protected function newAsset(string $name, float $rate): CrmFixedAsset
    {
        $this->post(route('crm.fixed_assets.store'), ['name' => $name, 'depreciation_rate' => $rate])->assertSessionHasNoErrors();

        return $this->latestAsset();
    }

    protected function latestAsset(): CrmFixedAsset
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return CrmFixedAsset::latest('id')->firstOrFail();
    }

    /** An asset entered before assets were bought through purchase invoices (own price and payment method). */
    protected function legacyAsset(string $name, string $method, float $amount, string $date, array $extra = []): CrmFixedAsset
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return CrmFixedAsset::create(array_merge(['purchase_date' => $date, 'name' => $name, 'depreciation_rate' => 0, 'quantity' => 1, 'unit_price' => $amount,
            'amount' => $amount, 'currency' => 'AED', 'payment_method' => $method, 'supplier_name' => 'Old Supplier', 'created_by' => $this->admin->id], $extra));
    }
}
