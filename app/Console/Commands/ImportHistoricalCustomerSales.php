<?php

namespace App\Console\Commands;

use App\CrmCustomer;
use App\CrmIncomeEntry;
use App\CrmWorkspace;
use App\CustomerSale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHistoricalCustomerSales extends Command
{
    protected $signature = 'crm:import-historical-sales {file} {--workspace=} {--dry-run}';
    protected $description = 'Import reviewed Sales Record and Customers rows without overwriting existing data';

    private const SOURCE_SHA = '632a9ad03e4724d18ae8879d486413eb3ab7fb8d337e18d41d9fb3976d0994e7';

    public function handle(): int
    {
        $workspaceId = (int) $this->option('workspace');
        $workspace = CrmWorkspace::find($workspaceId);
        if (!$workspace || $workspace->name !== 'Al Massa Packaging') {
            $this->error('Choose the Al Massa Packaging workspace explicitly.');
            return 1;
        }
        try {
            $source = json_decode(file_get_contents($this->argument('file')), true, 512, JSON_THROW_ON_ERROR);
            $this->validateSource($source);
            $customers = $source['customers'];
            $rows = $source['sales'];
            $rents = array_filter($rows, fn ($row) => $this->isRent($row));
            $sales = array_filter($rows, fn ($row) => !$this->isRent($row));
            $alreadySales = CustomerSale::withoutGlobalScopes()->where('workspace_id', $workspaceId)
                ->whereIn('import_source_key', array_map(fn ($r) => $this->key($r), $sales))->count();
            $alreadyRents = CrmIncomeEntry::withoutGlobalScopes()->where('workspace_id', $workspaceId)
                ->whereIn('import_source_key', array_map(fn ($r) => $this->key($r), $rents))->count();
            $this->info(sprintf('%s: %d customers, %d sales (%d present), %d rent entries (%d present), %d unpriced sales.',
                $workspace->name, count($customers), count($sales), $alreadySales, count($rents), $alreadyRents,
                count(array_filter($sales, fn ($r) => $r['unit_price'] === null))));
            if ($this->option('dry-run')) return 0;

            DB::transaction(function () use ($customers, $rows, $workspaceId) {
                $ids = [];
                foreach ($customers as $sourceCustomer) {
                    $marker = 'Sales Record customer ID: ' . $sourceCustomer['excel_id'];
                    $customer = CrmCustomer::withoutGlobalScopes()->where('workspace_id', $workspaceId)
                        ->where('notes', 'like', '%' . $marker . '%')->first();
                    if (!$customer) {
                        if (CrmCustomer::withoutGlobalScopes()->where('workspace_id', $workspaceId)
                            ->where('name', $sourceCustomer['name'])->exists()) {
                            throw new \RuntimeException('Existing customer name needs manual mapping: ' . $sourceCustomer['name']);
                        }
                        $customer = CrmCustomer::create([
                            'workspace_id' => $workspaceId,
                            'name' => $sourceCustomer['name'],
                            'contact_person' => $sourceCustomer['contact_person'] ?: null,
                            'phone' => $sourceCustomer['phone'] ? (string) $sourceCustomer['phone'] : null,
                            'billing_address' => $sourceCustomer['address'] ?: null,
                            'currency' => 'AED',
                            'customer_type' => $sourceCustomer['excel_id'] === 8 ? 'income' : 'sales',
                            'income_head' => in_array($sourceCustomer['excel_id'], [4, 8], true) ? 'Rent' : null,
                            'recurring_active' => false,
                            'notes' => 'Imported from Sales Record & Ledger.xlsx. ' . $marker,
                        ]);
                    }
                    $ids[$sourceCustomer['excel_id']] = $customer->id;
                }

                foreach ($rows as $row) {
                    $key = $this->key($row);
                    $customerId = $ids[$row['customer_excel_id']];
                    $total = $this->money($row['invoice_total']);
                    $paid = $this->money($row['amount_received']);
                    if ($this->isRent($row)) {
                        if (CrmIncomeEntry::withoutGlobalScopes()->where('import_source_key', $key)->exists()) continue;
                        $period = substr($row['date'], 0, 7);
                        if (CrmIncomeEntry::withoutGlobalScopes()->where('customer_id', $customerId)->where('period', $period)->exists()) {
                            throw new \RuntimeException("Rent due already exists for customer {$customerId} in {$period}.");
                        }
                        CrmIncomeEntry::create([
                            'workspace_id' => $workspaceId, 'customer_id' => $customerId,
                            'import_source_key' => $key, 'income_head' => 'Rent', 'period' => $period,
                            'due_date' => $row['due_date'] ?: $row['date'],
                            'amount_due' => $total, 'amount_paid' => $paid,
                            'payment_method' => $row['payment_method'] ?: null,
                            'note' => 'Historical ' . trim($row['description']) . ' from Sales Record row ' . $row['excel_row'] . '. Due and payment dates not supplied; source date used for due.',
                        ]);
                        continue;
                    }
                    if (CustomerSale::withoutGlobalScopes()->where('import_source_key', $key)->exists()) continue;
                    $subtotal = $this->money($row['sales_amount']);
                    $tax = $this->money($row['vat_amount']);
                    if (abs($subtotal + $tax - $total) > 0.02) {
                        throw new \RuntimeException('Invoice total does not reconcile at row ' . $row['excel_row']);
                    }
                    $invoice = $row['invoice_no'] ? 'INV-' . $row['invoice_no'] : null;
                    $orderNumber = $invoice ?: 'HIST-2026-' . str_pad($row['excel_row'], 3, '0', STR_PAD_LEFT);
                    CustomerSale::create([
                        'workspace_id' => $workspaceId, 'customer_id' => $customerId,
                        'import_source_key' => $key, 'order_number' => $orderNumber,
                        'order_date' => $row['date'], 'due_date' => $row['due_date'] ?: null,
                        'item_name' => trim($row['description']) ?: ($row['job_no'] ?: 'Historical sale'),
                        'description' => $row['job_no'] ? 'Job: ' . $row['job_no'] : null,
                        'quantity' => $row['quantity'] === null ? null : $row['quantity'],
                        'unit' => 'Pieces', 'unit_price' => $row['unit_price'] === null ? 0 : $row['unit_price'],
                        'subtotal' => $subtotal, 'tax_amount' => $tax, 'total_amount' => $total,
                        'paid_amount' => $paid, 'balance_amount' => max(0, round($total - $paid, 2)),
                        'currency' => 'AED',
                        'payment_status' => $paid <= 0 ? 'Unpaid' : ($paid >= $total ? 'Paid' : 'Partial'),
                        'order_status' => $row['unit_price'] === null ? 'Draft' : 'Confirmed',
                        'payment_method' => $row['payment_method'] ?: null,
                        'notes' => 'Imported from Sales Record & Ledger.xlsx, Sales Record row ' . $row['excel_row']
                            . ($row['job_no'] ? '; job ' . $row['job_no'] : '')
                            . ($row['notes'] ? '; ' . $row['notes'] : '')
                            . ($paid > $total ? '; credit AED ' . number_format($paid - $total, 2, '.', '') : ''),
                    ]);
                }
            });
            $this->info('Import complete; existing imported records and later edits were left unchanged.');
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function validateSource($source): void
    {
        if (!is_array($source) || ($source['source_file_sha256'] ?? null) !== self::SOURCE_SHA
            || count($source['customers'] ?? []) !== 9 || count($source['sales'] ?? []) !== 42) {
            throw new \RuntimeException('Source checksum or expected row count does not match reviewed workbook.');
        }
        $customerIds = [];
        foreach ($source['customers'] as $customer) {
            $id = $customer['excel_id'] ?? null;
            if (!is_int($id) || isset($customerIds[$id]) || !is_string($customer['name'] ?? null) || trim($customer['name']) === '') {
                throw new \RuntimeException('Invalid or duplicate customer in source.');
            }
            $customerIds[$id] = $customer['name'];
        }
        $rows = [];
        foreach ($source['sales'] as $row) {
            $n = $row['excel_row'] ?? null;
            if (!is_int($n) || $n < 11 || $n > 52 || isset($rows[$n])
                || !isset($customerIds[$row['customer_excel_id'] ?? null])
                || $row['customer_name'] !== $customerIds[$row['customer_excel_id']]
                || !is_string($row['date'] ?? null) || !preg_match('/^2026-\d{2}-\d{2}$/', $row['date'])) {
                throw new \RuntimeException('Invalid sales source row.');
            }
            foreach (['sales_amount', 'vat_amount', 'invoice_total', 'amount_received'] as $amount) {
                if ($row[$amount] !== null && (!is_numeric($row[$amount]) || $row[$amount] < 0)) {
                    throw new \RuntimeException("Invalid {$amount} at row {$n}.");
                }
            }
            $rows[$n] = true;
        }
        if (count($rows) !== 42 || !isset($rows[11], $rows[52])) throw new \RuntimeException('Incomplete source rows.');
    }

    private function money($value): float { return round((float) ($value ?? 0), 2); }
    private function key(array $row): string { return 'sale-record-632a9ad0-row-' . $row['excel_row']; }
    private function isRent(array $row): bool { return in_array($row['excel_row'], [36, 48], true); }
}
