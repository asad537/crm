<?php

namespace Database\Seeders;

use App\VendorPurchase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates ONE vendor invoice that mixes all three expense types on its line items
 * (Production + Consumable + Admin/General) so we can confirm the per-item expense
 * split flows correctly into the overview cards / donut.
 *
 * Run: php artisan db:seed --class=Database\\Seeders\\MixedInvoiceTestSeeder
 */
class MixedInvoiceTestSeeder extends Seeder
{
    public function run(): void
    {
        $workspaceId = 1; // TCB CRM

        // Reuse an existing creator for this workspace (created_by is not nullable in practice).
        $createdBy = optional(
            VendorPurchase::withoutGlobalScopes()->where('workspace_id', $workspaceId)->first()
        )->created_by;

        // Attach to a real vendor so the invoice shows inside that vendor's drill-in table.
        $vendor = DB::table('vendors')->where('workspace_id', $workspaceId)->orderBy('id')->first();

        // Remove any previous run so re-seeding does not pile up duplicates.
        VendorPurchase::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('invoice_number', 'like', 'MIX-TEST%')
            ->get()
            ->each(function ($p) {
                $p->items()->delete();
                $p->delete();
            });

        // The three mixed line items — one of each expense type on the SAME invoice.
        $items = [
            [
                'expense_type' => 'Production Expense',
                'category' => 'Paper & Board Stock',
                'item_name' => 'Art paper 250gsm sheets',
                'quantity' => 100,
                'unit' => 'Items',
                'unit_price' => 50,
                'vat_percentage' => 5,
                'extra' => ['job_id' => 'JOB-5501', 'paper_type' => 'Art Paper Gloss', 'gsm' => '250'],
            ],
            [
                'expense_type' => 'Consumable Expense',
                'category' => 'Offset Inks',
                'item_name' => 'Process cyan ink 1kg',
                'quantity' => 10,
                'unit' => 'Items',
                'unit_price' => 200,
                'vat_percentage' => 5,
                'extra' => ['job_id' => 'JOB-5501', 'specification' => 'CMYK process ink'],
            ],
            [
                'expense_type' => 'Admin/General Expense',
                'category' => 'Salaries & Wages',
                'item_name' => 'Bindery staff monthly wages',
                'quantity' => 1,
                'unit' => 'Items',
                'unit_price' => 3000,
                'vat_percentage' => 0,
                'extra' => null,
            ],
        ];

        // Totals exactly as VendorPurchaseController::calculatePurchaseTotals() computes them.
        $subtotal = 0.0;
        $tax = 0.0;
        $position = 1;
        $rows = [];
        foreach ($items as $it) {
            $lineTotal = round($it['quantity'] * $it['unit_price'], 2);
            $subtotal += $lineTotal;
            $tax += (float) $lineTotal * (float) $it['vat_percentage'] / 100;
            $rows[] = array_merge($it, ['position' => $position++, 'line_total' => $lineTotal]);
        }
        $subtotal = round($subtotal, 2);
        $tax = round($tax, 2);
        $shipping = 0.0;
        $total = round($subtotal + $tax + $shipping, 2);
        $vatPercentage = $subtotal > 0 ? round($tax / $subtotal * 100, 2) : 0;
        $paid = round($total * 0.4, 2);
        $balance = round($total - $paid, 2);

        // Header expense_type = dominant item type (controller behaviour); here it defaults
        // to the first type since each appears once.
        $purchase = VendorPurchase::create([
            'workspace_id' => $workspaceId,
            'created_by' => $createdBy,
            'vendor_id' => $vendor->id ?? null,
            'vendor_name' => $vendor->name ?? 'Mixed Test Supplier',
            'vendor_phone' => $vendor->phone ?? null,
            'vendor_email' => $vendor->email ?? null,
            'invoice_number' => 'MIX-TEST-' . Carbon::now()->format('ymd-His'),
            'purchase_date' => Carbon::today()->toDateString(),
            'due_date' => Carbon::today()->addDays(2)->toDateString(),
            'currency' => 'AED',
            'expense_type' => 'Production Expense',
            // Legacy single-item header columns are NOT NULL — fill with an invoice-level summary.
            'category' => 'Mixed Expenses',
            'item_name' => 'Mixed expense invoice (3 items)',
            'quantity' => 1,
            'unit' => 'Items',
            'unit_price' => 0,
            'subtotal' => $subtotal,
            'vat_mode' => 'exclusive',
            'vat_percentage' => $vatPercentage,
            'tax_amount' => $tax,
            'shipping_cost' => $shipping,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'payment_status' => 'Partial',
            'payment_method' => 'Bank Transfer',
            'notes' => 'Seeded mixed-expense invoice for per-item split testing.',
        ]);

        $purchase->items()->createMany($rows);

        optional($this->command)->info("Created mixed invoice #{$purchase->id} ({$purchase->invoice_number}) — total {$total}, 3 items: Production/Consumable/Admin.");
    }
}
