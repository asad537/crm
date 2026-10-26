<?php

namespace Tests\Feature\Accounting;

use App\CrmBankAccount;
use App\CrmDeletionLog;
use App\CrmExpense;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Expense payments ("Make Payment" on the Payments page): saving, the voucher, proof files, and the books. */
class ExpenseTest extends AccountingTestCase
{
    use BankCashHelpers;

    /** Proof uploads go to a throw-away disk of this test only (never storage/app). */
    protected string $diskRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->diskRoot = storage_path('framework/testing/disks/accounting-expenses-' . uniqid());
        Storage::set('local', Storage::createLocalDriver(['root' => $this->diskRoot]));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->diskRoot);
        parent::tearDown();
    }

    private function payload(array $data = []): array
    {
        return array_merge([
            'expense_date' => '2026-09-15', 'category' => 'Rent', 'name' => 'Warehouse rent', 'paid_from' => 'Bank Transfer',
            'payee_type' => 'supplier', 'payee_name' => 'Al Quoz Properties', 'amount' => '4500.75', 'currency' => 'AED',
        ], $data);
    }

    public function test_make_payment_saves_an_expense_with_its_proof_and_bank_account(): void
    {
        $bank = $this->addAccount(['name' => 'Expense Bank', 'opening_balance' => 10000]);

        $response = $this->post(route('crm.expenses.store'), $this->payload([
            'category' => '  Rent ', 'reference' => 'INV-889', 'note' => 'Quarterly', 'bank_account_id' => $bank->id,
            'proof' => UploadedFile::fake()->createWithContent('rent-receipt.pdf', '%PDF-1.4 rent receipt'),
        ]));
        $expense = CrmExpense::firstOrFail();
        $response->assertRedirect(route('crm.payments.index'))
            ->assertSessionHas('success', 'Payment ' . $expense->number . ' — Rent · Warehouse rent (4,500.75 AED) saved.');

        $this->assertSame('EXP-' . str_pad($expense->id, 4, '0', STR_PAD_LEFT), $expense->number);
        $this->assertSame($this->workspace->id, (int) $expense->workspace_id);
        $this->assertSame($this->admin->id, (int) $expense->created_by);
        $this->assertSame('Rent', $expense->category, 'category is trimmed');
        $this->assertSame('2026-09-15', $expense->expense_date->toDateString());
        $this->assertMoney(4500.75, $expense->amount);
        $this->assertSame($bank->id, (int) $expense->bank_account_id);
        $this->assertSame('Al Quoz Properties', $expense->payee_name);
        $this->assertSame('rent-receipt.pdf', $expense->proof_name);
        $this->assertStringStartsWith('expense-proofs/', $expense->proof_path);
        Storage::disk('local')->assertExists($expense->proof_path);

        // The money left the chosen account.
        $this->assertMoney(5499.25, $this->balanceOf($bank));
        $this->get(route('crm.bank_accounts.show', $bank->id))->assertOk()
            ->assertViewHas('statement', fn ($s) => count($s['rows']) === 1 && $s['rows'][0]->ref === $expense->number && abs($s['closing'] - 5499.25) < 0.005);
    }

    public function test_an_expense_needs_valid_details(): void
    {
        $this->post(route('crm.expenses.store'), [])
            ->assertSessionHasErrors(['expense_date', 'category', 'name', 'paid_from', 'payee_type', 'amount', 'currency']);
        $this->post(route('crm.expenses.store'), $this->payload(['paid_from' => 'Crypto']))->assertSessionHasErrors('paid_from');
        $this->post(route('crm.expenses.store'), $this->payload(['payee_type' => 'friend']))->assertSessionHasErrors('payee_type');
        $this->post(route('crm.expenses.store'), $this->payload(['amount' => '0']))->assertSessionHasErrors('amount');
        $this->post(route('crm.expenses.store'), $this->payload(['currency' => 'PKR']))->assertSessionHasErrors('currency');
        $this->post(route('crm.expenses.store'), $this->payload(['expense_date' => '2026-10-27']))->assertSessionHasErrors('expense_date');
        $this->post(route('crm.expenses.store'), $this->payload(['proof' => UploadedFile::fake()->createWithContent('notes.txt', 'hello')]))
            ->assertSessionHasErrors('proof');
        $this->assertSame(0, CrmExpense::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_payment_voucher_shows_the_account_the_money_left_from(): void
    {
        $bank = $this->addAccount(['name' => 'Voucher Bank']);
        $cash = $this->addAccount(['name' => 'Voucher Cash', 'type' => 'cash', 'auto_methods' => ['Cash']]);
        $explicit = $this->addExpense(['bank_account_id' => $bank, 'amount' => 250, 'name' => 'Printer toner']);
        $auto = $this->addExpense(['paid_from' => 'Cash', 'amount' => 75]);
        $loose = $this->addExpense(['paid_from' => 'Cheque', 'amount' => 30]);

        $this->get(route('crm.expenses.show', $explicit->id))->assertOk()->assertSee($explicit->number)->assertSee('Printer toner')
            ->assertViewHas('account', fn ($a) => $a && $a->id === $bank->id)->assertViewHas('autoBooked', false)->assertViewHas('canDelete', true);
        $this->get(route('crm.expenses.show', $auto->id))->assertOk()
            ->assertViewHas('account', fn ($a) => $a && $a->id === $cash->id)->assertViewHas('autoBooked', true);
        $this->get(route('crm.expenses.show', $loose->id))->assertOk()->assertViewHas('account', null);

        $theirs = CrmExpense::create(['workspace_id' => $this->otherWorkspace()->id, 'expense_date' => '2026-03-01', 'category' => 'Rent', 'name' => 'Not ours',
            'paid_from' => 'Cash', 'payee_type' => 'other', 'amount' => 1, 'currency' => 'AED']);
        $this->get(route('crm.expenses.show', $theirs->id))->assertNotFound();
    }

    public function test_editing_an_expense_updates_amount_account_and_proof(): void
    {
        $bank = $this->addAccount(['name' => 'Edit Bank', 'opening_balance' => 5000]);
        $cash = $this->addAccount(['name' => 'Edit Cash', 'type' => 'cash', 'opening_balance' => 1000]);
        $this->post(route('crm.expenses.store'), $this->payload(['amount' => 800, 'bank_account_id' => $bank->id,
            'proof' => UploadedFile::fake()->createWithContent('old.pdf', '%PDF-1.4 old')]))->assertSessionHasNoErrors();
        $expense = CrmExpense::firstOrFail();
        $oldProof = $expense->proof_path;
        $this->assertMoney(4200, $this->balanceOf($bank));

        $this->from(route('crm.expenses.show', $expense->id))->put(route('crm.expenses.update', $expense->id), $this->payload([
            'category' => 'Maintenance & Repairs', 'name' => 'AC repair', 'amount' => '650', 'paid_from' => 'Cash', 'bank_account_id' => $cash->id,
            'proof' => UploadedFile::fake()->createWithContent('new.pdf', '%PDF-1.4 new'),
        ]))->assertRedirect(route('crm.expenses.show', $expense->id))->assertSessionHas('success', 'Payment ' . $expense->number . ' updated.');

        $expense->refresh();
        $this->assertSame('Maintenance & Repairs', $expense->category);
        $this->assertSame('AC repair', $expense->name);
        $this->assertMoney(650, $expense->amount);
        $this->assertSame($cash->id, (int) $expense->bank_account_id);
        $this->assertSame('new.pdf', $expense->proof_name);
        Storage::disk('local')->assertMissing($oldProof);
        Storage::disk('local')->assertExists($expense->proof_path);
        $this->assertMoney(5000, $this->balanceOf($bank));
        $this->assertMoney(350, $this->balanceOf($cash));

        // A form without the account field keeps the account and the proof; an empty account means "automatic".
        $this->put(route('crm.expenses.update', $expense->id), $this->payload(['amount' => 600, 'paid_from' => 'Cash']))->assertSessionHasNoErrors();
        $expense->refresh();
        $this->assertSame($cash->id, (int) $expense->bank_account_id);
        $this->assertSame('new.pdf', $expense->proof_name);
        $this->assertMoney(400, $this->balanceOf($cash));
        $this->put(route('crm.expenses.update', $expense->id), $this->payload(['amount' => 600, 'paid_from' => 'Cash', 'bank_account_id' => '']))->assertSessionHasNoErrors();
        $this->assertNull($expense->fresh()->bank_account_id);
        $this->assertMoney(1000, $this->balanceOf($cash));

        // Another workspace's account cannot be picked.
        $foreign = CrmBankAccount::create(['workspace_id' => $this->otherWorkspace()->id, 'name' => 'Foreign', 'type' => 'bank', 'currency' => 'AED',
            'opening_balance' => 0, 'opening_date' => '2026-01-01', 'is_active' => true]);
        $this->put(route('crm.expenses.update', $expense->id), $this->payload(['amount' => 600, 'bank_account_id' => $foreign->id]))->assertSessionHasNoErrors();
        $this->assertNull($expense->fresh()->bank_account_id);
    }

    public function test_deleting_an_expense_removes_it_its_proof_and_its_bank_movement(): void
    {
        $bank = $this->addAccount(['name' => 'Delete Bank', 'opening_balance' => 3000]);
        $this->post(route('crm.expenses.store'), $this->payload(['amount' => 1000, 'bank_account_id' => $bank->id,
            'proof' => UploadedFile::fake()->createWithContent('proof.pdf', '%PDF-1.4 proof')]))->assertSessionHasNoErrors();
        $first = CrmExpense::firstOrFail();
        $second = $this->addExpense(['amount' => 200, 'bank_account_id' => $bank]);
        $this->assertMoney(1800, $this->balanceOf($bank));

        // From its own voucher the page is gone, so it goes back to the Payments list.
        $this->from(route('crm.expenses.show', $first->id))->delete(route('crm.expenses.destroy', $first->id))
            ->assertRedirect(route('crm.payments.index'))->assertSessionHas('success', 'Payment ' . $first->number . ' deleted.');
        $this->assertNull(CrmExpense::find($first->id));
        Storage::disk('local')->assertMissing($first->proof_path);
        $log = CrmDeletionLog::where('entity_type', 'expense')->where('entity_id', $first->id)->firstOrFail();
        $this->assertSame($this->workspace->id, (int) $log->workspace_id);
        $this->assertSame('Rent', $log->snapshot['category']);
        $this->assertMoney(2800, $this->balanceOf($bank));

        // From the list it goes back to the list.
        $list = route('crm.payments.index', ['source' => 'expense']);
        $this->from($list)->delete(route('crm.expenses.destroy', $second->id))->assertRedirect($list);
        $this->assertSame(0, CrmExpense::count());
        $this->assertMoney(3000, $this->balanceOf($bank));
    }

    public function test_the_proof_file_downloads(): void
    {
        $this->post(route('crm.expenses.store'), $this->payload(['proof' => UploadedFile::fake()->createWithContent('bill.pdf', '%PDF-1.4 the bill')]))->assertSessionHasNoErrors();
        $expense = CrmExpense::firstOrFail();

        $response = $this->get(route('crm.expenses.proof', $expense->id))->assertOk();
        $this->assertSame('%PDF-1.4 the bill', file_get_contents($response->baseResponse->getFile()->getPathname()));

        $noProof = $this->addExpense();
        $this->get(route('crm.expenses.proof', $noProof->id))->assertNotFound();
        Storage::disk('local')->delete($expense->proof_path);
        $this->get(route('crm.expenses.proof', $expense->id))->assertNotFound();
    }

    public function test_the_expense_is_listed_on_the_payments_page(): void
    {
        $this->get(route('crm.expenses.index'))->assertRedirect(route('crm.payments.index', ['source' => 'expense']));
        $this->post(route('crm.expenses.store'), $this->payload(['amount' => '1234.50', 'proof' => UploadedFile::fake()->createWithContent('p.pdf', '%PDF-1.4 p')]))->assertSessionHasNoErrors();
        $expense = CrmExpense::firstOrFail();
        $this->addExpense(['amount' => 66, 'paid_from' => 'Cash', 'payee_type' => 'other', 'expense_date' => '2026-10-01']);
        CrmExpense::create(['workspace_id' => $this->otherWorkspace()->id, 'expense_date' => '2026-09-01', 'category' => 'Rent', 'name' => 'Someone else',
            'paid_from' => 'Cash', 'payee_type' => 'other', 'amount' => 777, 'currency' => 'AED']);

        $this->get(route('crm.payments.index', ['source' => 'expense']))->assertOk()->assertSee($expense->number)->assertDontSee('Someone else')
            ->assertViewHas('entries', function ($entries) use ($expense) {
                $this->assertSame(2, $entries->total());
                $row = $entries->getCollection()->firstWhere('ref', $expense->number);
                $this->assertSame('expense', $row->source);
                $this->assertSame('Expense Payment', $row->source_label);
                $this->assertSame('Al Quoz Properties', $row->party);
                $this->assertSame('Bank Transfer', $row->method);
                $this->assertSame('Rent', $row->category);
                $this->assertMoney(1234.50, $row->amount);
                $this->assertSame(route('crm.expenses.show', $expense->id), $row->url);
                $this->assertSame(route('crm.expenses.proof', $expense->id), $row->proof_url);
                $this->assertSame('Other', $entries->getCollection()->firstWhere('ref', '!=', $expense->number)->party, 'no payee name → payee type');
                return true;
            })
            ->assertViewHas('totals', fn ($t) => abs($t['expense'] - 1300.50) < 0.005 && abs($t['paid'] - 1300.50) < 0.005 && $t['count'] === 2 && abs($t['month'] - 66) < 0.005);

        $this->get(route('crm.payments.index', ['method' => 'Cash']))->assertOk()->assertViewHas('entries', fn ($e) => $e->total() === 1);
        $this->get(route('crm.payments.index', ['source' => 'vendor']))->assertOk()->assertViewHas('entries', fn ($e) => $e->total() === 0);
    }

    public function test_expenses_land_in_the_profit_and_loss_under_their_category(): void
    {
        $this->addExpense(['expense_date' => '2026-02-01', 'category' => 'Rent', 'amount' => 2000]);
        $this->addExpense(['expense_date' => '2026-03-01', 'category' => 'Raw Material', 'amount' => 3000]);           // → Stock & Raw Material (cost of sales)
        $this->addExpense(['expense_date' => '2026-04-01', 'category' => 'Government Fees & Visa', 'amount' => 400]);  // → Government Fees & Taxes
        $this->addExpense(['expense_date' => '2026-05-01', 'category' => 'office party', 'amount' => 150]);            // typed category, capitalised
        $this->addExpense(['expense_date' => '2025-12-20', 'category' => 'Rent', 'amount' => 999]);                    // last year

        $pl = $this->report('profit-loss');
        $this->assertEquals(['Stock & Raw Material' => 3000.0], $this->plSection($pl, 'Cost of Sales'));
        $this->assertEquals(['Government Fees & Taxes' => 400.0, 'Office Party' => 150.0, 'Rent' => 2000.0], $this->plSection($pl, 'Operating Expenses'));
        $this->assertSame([], $this->plSection($pl, 'Income'));
        $this->assertMoney(5550, $this->card($pl, 'Total Costs'));
        $this->assertMoney(-3000, $this->card($pl, 'Gross Profit'));
        $this->assertMoney(-5550, $this->card($pl, 'Net Profit / (Loss)'));
        // Last year's rent is in the previous-period column.
        $this->assertMoney(999, $this->tableRow($pl['tables'][0], 'Rent')[2]);
        $this->assertContains('Stock & Raw Material', \App\Services\FinancialReportService::COST_OF_SALES);

        // The drill-down lists the entry behind the line.
        $rent = CrmExpense::where('expense_date', '2026-02-01')->firstOrFail();
        $this->get(route('crm.financial_reports.entries', ['group' => 'opex', 'category' => 'Rent', 'period' => 'custom', 'date_from' => '2026-01-01', 'date_to' => '2026-10-26']))
            ->assertOk()->assertSee($rent->number)
            ->assertViewHas('report', fn ($r) => abs($r['cards'][0]['value'] - 2000) < 0.005 && $r['cards'][1]['value'] === 1);

        // Trial balance: each category on the debit side, and it balances.
        $trial = $this->report('trial-balance');
        $this->assertMoney(2000, $this->tableRow($trial['tables'][0], 'Rent')[1]);
        $this->assertMoney(3000, $this->tableRow($trial['tables'][0], 'Stock & Raw Material')[1]);
        $this->assertMoney(0, $this->card($trial, 'Difference'));
    }

    public function test_an_expense_reduces_the_bank_balance_and_shows_in_the_cash_flow(): void
    {
        $bank = $this->addAccount(['name' => 'Flow Bank', 'opening_balance' => 10000, 'auto_methods' => ['Bank']]);
        $before = $this->balancingFigure();
        $this->assertMoney(10000, $before);

        $this->addExpense(['expense_date' => '2026-07-01', 'category' => 'Utilities', 'amount' => 1250, 'paid_from' => 'Company Account']);   // auto-booked (Bank group)
        $this->assertMoney(8750, $this->balanceOf($bank));
        $this->assertMoney(10000, $this->balanceOf($bank, '2026-06-30'));

        $flow = $this->report('cash-flow');
        $table = $flow['tables'][0];
        $this->assertMoney(-1250, $this->tableRow($table, 'Paid for expenses')[1]);
        $this->assertMoney(-1250, $this->tableRow($table, 'Net cash from operating activities')[1]);
        $this->assertMoney(10000, $this->card($flow, 'Cash & Bank at Start'));
        $this->assertMoney(8750, $this->card($flow, 'Cash & Bank at End'));

        // Cash went down by what profit went down: nothing new is unexplained.
        $sheet = $this->sheet();
        $this->assertMoney(8750, $this->sheetLines($sheet, 'assets')['Flow Bank']);
        $this->assertMoney(-1250, collect($sheet['equity'])->firstWhere(3, 'profit')[1]);
        $this->assertMoney($before, $this->balancingFigure());
        $this->assertMoney(0, $this->trialDifference());
    }

    public function test_who_can_make_and_delete_payments(): void
    {
        $expense = $this->addExpense(['amount' => 10]);

        $this->signIn($this->makeUser('sales'));
        $this->get(route('crm.expenses.index'))->assertForbidden();
        $this->get(route('crm.payments.index'))->assertForbidden();
        $this->post(route('crm.expenses.store'), $this->payload())->assertForbidden();
        $this->get(route('crm.expenses.show', $expense->id))->assertForbidden();
        $this->put(route('crm.expenses.update', $expense->id), $this->payload())->assertForbidden();
        $this->get(route('crm.expenses.proof', $expense->id))->assertForbidden();
        $this->delete(route('crm.expenses.destroy', $expense->id))->assertForbidden();

        // A sales manager can make and edit payments, but not delete them.
        $this->signIn($this->makeUser('sales_manager'));
        $this->post(route('crm.expenses.store'), $this->payload(['amount' => 20]))->assertRedirect(route('crm.payments.index'));
        $this->get(route('crm.expenses.show', $expense->id))->assertOk()->assertViewHas('canDelete', false);
        $this->put(route('crm.expenses.update', $expense->id), $this->payload(['amount' => 15]))->assertSessionHasNoErrors();
        $this->assertMoney(15, $expense->fresh()->amount);
        $this->delete(route('crm.expenses.destroy', $expense->id))->assertForbidden();
        $this->assertSame(2, CrmExpense::count());

        $this->signIn($this->makeUser('accounts'));
        $this->delete(route('crm.expenses.destroy', $expense->id))->assertSessionHas('success');
        $this->assertSame(1, CrmExpense::count());
    }
}
