<?php

namespace Tests\Feature\Accounting;

use App\CrmFixedAsset;
use App\CrmPurchaseInvoice;
use App\CrmWorkspace;
use App\Services\FinancialReportService;
use App\Support\CrmWorkspaceContext;
use App\Vendor;
use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/** Shared steps for the Purchase Invoice and Fixed Asset tests: vendors, bills and payments made through the routes, and report lookups. */
trait PurchaseInvoiceHelpers
{
    protected ?string $diskRoot = null;

    protected function tearDown(): void
    {
        if ($this->diskRoot) (new Filesystem())->deleteDirectory($this->diskRoot);
        parent::tearDown();
    }

    /** The "local" disk swapped for a throw-away folder of this test only (uploads, receipts). */
    protected function useTempDisk(): void
    {
        $this->diskRoot = sys_get_temp_dir() . '/accounting-test-' . uniqid('', true);
        Storage::set('local', Storage::createLocalDriver(['root' => $this->diskRoot]));
    }

    protected function vendor(string $name = null): Vendor
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return Vendor::create(['name' => $name ?: 'Vendor ' . uniqid()]);
    }

    protected function otherWorkspace(): CrmWorkspace
    {
        return CrmWorkspace::create(['name' => 'Other Test', 'slug' => 'other-test-' . uniqid(), 'is_active' => true]);
    }

    /** One plain bill line. */
    protected function line(string $name, string $category, $quantity, $unitPrice): array
    {
        return ['name' => $name, 'category' => $category, 'quantity' => $quantity, 'unit_price' => $unitPrice];
    }

    /** A fixed-asset line for the "Add Fixed Asset" picker. */
    protected function assetLine(CrmFixedAsset $asset, $quantity, $unitPrice): array
    {
        return ['fixed_asset_id' => $asset->id, 'name' => $asset->name, 'category' => 'Fixed Assets', 'quantity' => $quantity, 'unit_price' => $unitPrice];
    }

    protected function billPayload(Vendor $vendor, array $items, array $overrides = []): array
    {
        return array_merge([
            'vendor_id' => $vendor->id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-11-30',
            'reference' => 'SUP-' . uniqid(),
            'project' => null,
            'description' => null,
            'currency' => 'AED',
            'items' => $items,
        ], $overrides);
    }

    /** Create a bill on the New Purchase Invoice form and return it. */
    protected function createBill(Vendor $vendor, array $items, array $overrides = []): CrmPurchaseInvoice
    {
        $response = $this->post(route('crm.purchase_invoices.store'), $this->billPayload($vendor, $items, $overrides));
        $response->assertSessionHasNoErrors();
        CrmWorkspaceContext::set($this->workspace->id);
        $invoice = CrmPurchaseInvoice::latest('id')->firstOrFail();
        $response->assertRedirect(route('crm.purchase_invoices.show', $invoice->id));

        return $invoice;
    }

    protected function updateBill(CrmPurchaseInvoice $invoice, Vendor $vendor, array $items, array $overrides = [])
    {
        return $this->put(route('crm.purchase_invoices.update', $invoice->id), $this->billPayload($vendor, $items, array_merge([
            'issue_date' => $invoice->issue_date->toDateString(),
            'due_date' => optional($invoice->due_date)->toDateString(),
            'reference' => $invoice->reference,
        ], $overrides)));
    }

    /** Record a payment on the invoice page. */
    protected function pay(CrmPurchaseInvoice $invoice, float $amount, string $date = '2026-10-26', array $extra = [])
    {
        return $this->from(route('crm.purchase_invoices.show', $invoice->id))
            ->post(route('crm.purchase_invoices.payments.store', $invoice->id), array_merge(['amount' => $amount, 'paid_at' => $date, 'method' => 'Bank Transfer'], $extra));
    }

    protected function fresh(CrmPurchaseInvoice $invoice): CrmPurchaseInvoice
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return CrmPurchaseInvoice::with(['items', 'payments'])->findOrFail($invoice->id);
    }

    /** Balance sheet as of a day: line kind => amount (cash lines added up; "balancing" is the unexplained figure). */
    protected function sheet(string $asOf = '2026-10-26'): array
    {
        CrmWorkspaceContext::set($this->workspace->id);
        $sheet = (new FinancialReportService(Carbon::parse('2026-01-01'), Carbon::parse($asOf)))->balanceSheetAsOf(Carbon::parse($asOf));
        $lines = [];
        foreach (array_merge($sheet['assets'], $sheet['liabilities'], $sheet['equity']) as [$label, $amount, $url, $kind]) {
            $lines[$kind] = ($lines[$kind] ?? 0) + (float) $amount;
        }

        return $lines;
    }

    /** Profit & Loss totals for a window: ['income' => [...], 'cos' => [...], 'opex' => [...], 'gross', 'net']. */
    protected function pl(string $from = '2026-01-01', string $to = '2026-10-26'): array
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return (new FinancialReportService(Carbon::parse($from), Carbon::parse($to)))->plTotalsFor(Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay());
    }

    /** Cash flow statement for a window: line / subtotal label => this-period amount (0 for an empty line). */
    protected function cashFlow(string $from, string $to): array
    {
        $lines = [];
        foreach ($this->report('cash-flow', $from, $to)['tables'][0]['rows'] as $row) {
            $cells = $row['cells'] ?? $row;
            if (($row['_style'] ?? null) === 'section') continue;
            $label = is_array($cells[0]) ? $cells[0]['text'] : $cells[0];
            $lines[$label] = (float) ($cells[1] ?? 0);
        }

        return $lines;
    }

    /** A report's cards: label => value. */
    protected function cards(array $report): array
    {
        return collect($report['cards'])->mapWithKeys(fn ($card) => [$card['label'] => (float) $card['value']])->all();
    }
}
