<?php

namespace Tests\Feature\Accounting;

use App\CrmCapitalEntry;
use App\CrmCapitalOwner;
use App\CrmDeletionLog;

/** Owner's Capital: owners, capital put in and drawings taken out, and how they show in the statements. */
class OwnerCapitalTest extends AccountingTestCase
{
    use BankCashHelpers;

    public function test_owners_are_added_edited_and_deleted(): void
    {
        $this->post(route('crm.owner_capital.owners.store'), ['name' => '  Hassan Ali  ', 'share_percent' => '60', 'notes' => 'Founder'])
            ->assertSessionHas('success', 'Hassan Ali added. Now record the capital they put in.');
        $owner = CrmCapitalOwner::where('name', 'Hassan Ali')->firstOrFail();
        $this->assertSame($this->workspace->id, (int) $owner->workspace_id);
        $this->assertSame($this->admin->id, (int) $owner->created_by);
        $this->assertMoney(60, $owner->share_percent);
        $this->assertTrue($owner->is_active, 'a new owner is active unless unticked');

        $this->post(route('crm.owner_capital.owners.store'), ['name' => '', 'share_percent' => '150'])->assertSessionHasErrors(['name', 'share_percent']);
        $this->assertSame(1, CrmCapitalOwner::count());

        $this->from(route('crm.owner_capital.index'))->put(route('crm.owner_capital.owners.update', $owner->id), [
            'name' => 'Hassan Ali Khan', 'share_percent' => '55', 'notes' => 'Managing partner', 'is_active' => '0',
        ])->assertRedirect(route('crm.owner_capital.index'))->assertSessionHas('success', 'Hassan Ali Khan updated.');
        $owner->refresh();
        $this->assertSame('Hassan Ali Khan', $owner->name);
        $this->assertMoney(55, $owner->share_percent);
        $this->assertFalse($owner->is_active);

        // An owner with entries is kept; one without entries can be deleted.
        $this->addCapital($owner, 1000, '2026-02-01', null, 'capital', CrmCapitalEntry::NON_CASH);
        $this->delete(route('crm.owner_capital.owners.destroy', $owner->id))->assertSessionHas('error');
        $this->assertNotNull(CrmCapitalOwner::find($owner->id));

        $spare = $this->addOwner('Spare Partner');
        $this->delete(route('crm.owner_capital.owners.destroy', $spare->id))
            ->assertRedirect(route('crm.owner_capital.index'))->assertSessionHas('success', 'Spare Partner deleted.');
        $this->assertNull(CrmCapitalOwner::find($spare->id));
        $this->assertTrue(CrmDeletionLog::where('entity_type', 'capital_owner')->where('entity_id', $spare->id)->where('workspace_id', $this->workspace->id)->exists());

        $this->get(route('crm.owner_capital.index'))->assertOk()->assertSee('Hassan Ali Khan')
            ->assertViewHas('owners', fn ($owners) => $owners->pluck('name')->all() === ['Hassan Ali Khan']);
    }

    public function test_capital_and_drawings_move_money_through_the_chosen_account(): void
    {
        $bank = $this->addAccount(['name' => 'Capital Bank']);
        $cash = $this->addAccount(['name' => 'Capital Cash', 'type' => 'cash']);
        $ali = $this->addOwner('Ali Raza');

        $opening = $this->addCapital($ali, 20000, '2026-01-01', $bank, 'capital', CrmCapitalEntry::NON_CASH);
        $this->assertNull($opening->bank_account_id, 'opening / non-cash capital goes through no account');

        $this->post(route('crm.owner_capital.entries.store'), [
            'owner_id' => $ali->id, 'entry_date' => '2026-02-01', 'type' => 'capital', 'amount' => '100000', 'method' => 'Bank Transfer',
            'bank_account_id' => $bank->id, 'reference' => 'TT-1001', 'note' => 'Initial funding',
        ])->assertRedirect(route('crm.owner_capital.index', ['owner' => $ali->id]));
        $capital = CrmCapitalEntry::latest('id')->firstOrFail();
        $this->assertSame('CAP-' . str_pad($capital->id, 4, '0', STR_PAD_LEFT), $capital->number);
        $this->assertSame($this->workspace->id, (int) $capital->workspace_id);
        $this->assertSame($bank->id, (int) $capital->bank_account_id);
        $this->assertSame('TT-1001', $capital->reference);

        $drawing = $this->addCapital($ali, 5000, '2026-03-01', $cash, 'drawing', 'Cash');
        $this->assertSame('DRW-' . str_pad($drawing->id, 4, '0', STR_PAD_LEFT), $drawing->number);

        $this->assertMoney(100000, $this->balanceOf($bank));
        $this->assertMoney(-5000, $this->balanceOf($cash));
        $ali->load('entries');
        $this->assertMoney(120000, $ali->capitalTotal());
        $this->assertMoney(5000, $ali->drawingsTotal());
        $this->assertMoney(115000, $ali->balance());

        // The page shows the running balance per owner, newest first.
        $this->get(route('crm.owner_capital.index', ['owner' => $ali->id]))->assertOk()->assertSee($capital->number)
            ->assertViewHas('totals', ['capital' => 120000.0, 'drawings' => 5000.0])
            ->assertViewHas('entries', fn ($entries) => $entries->pluck('owner_balance')->all() === [115000.0, 120000.0, 20000.0]);

        // Editing the amount (or the account) moves the bank balance with it.
        $this->from(route('crm.owner_capital.index'))->put(route('crm.owner_capital.entries.update', $capital->id), [
            'owner_id' => $ali->id, 'entry_date' => '2026-02-01', 'type' => 'capital', 'amount' => '90000', 'method' => 'Bank Transfer', 'bank_account_id' => $bank->id,
        ])->assertSessionHas('success', $capital->number . ' updated.');
        $this->assertMoney(90000, $this->balanceOf($bank));
        $this->put(route('crm.owner_capital.entries.update', $drawing->id), [
            'owner_id' => $ali->id, 'entry_date' => '2026-03-01', 'type' => 'drawing', 'amount' => '5000', 'method' => 'Bank Transfer', 'bank_account_id' => $bank->id,
        ])->assertSessionHas('success');
        $this->assertMoney(85000, $this->balanceOf($bank));
        $this->assertMoney(0, $this->balanceOf($cash));

        $this->delete(route('crm.owner_capital.entries.destroy', $drawing->id))->assertSessionHas('success', $drawing->number . ' deleted.');
        $this->assertNull(CrmCapitalEntry::find($drawing->id));
        $this->assertTrue(CrmDeletionLog::where('entity_type', 'capital_entry')->where('entity_id', $drawing->id)->exists());
        $this->assertMoney(90000, $this->balanceOf($bank));

        // Filtering by owner shows only that owner's entries and totals.
        $sara = $this->addOwner('Sara Khan');
        $this->addCapital($sara, 10000, '2026-04-01', $bank);
        $this->get(route('crm.owner_capital.index', ['owner' => $sara->id]))->assertOk()
            ->assertViewHas('totals', ['capital' => 10000.0, 'drawings' => 0.0])
            ->assertViewHas('entries', fn ($entries) => $entries->count() === 1);
        $this->get(route('crm.owner_capital.index'))->assertOk()
            ->assertViewHas('totals', ['capital' => 120000.0, 'drawings' => 0.0]);
    }

    public function test_an_entry_needs_an_owner_of_this_workspace_and_valid_details(): void
    {
        $owner = $this->addOwner('Valid Owner');
        $other = $this->otherWorkspace();
        $foreign = CrmCapitalOwner::create(['workspace_id' => $other->id, 'name' => 'Foreign Owner', 'is_active' => true]);
        $route = route('crm.owner_capital.entries.store');
        $ok = ['owner_id' => $owner->id, 'entry_date' => '2026-02-01', 'type' => 'capital', 'amount' => 100, 'method' => 'Cash'];

        $this->post($route, array_merge($ok, ['owner_id' => $foreign->id]))->assertSessionHasErrors(['owner_id' => 'Pick an owner from the list.']);
        $this->post($route, array_merge($ok, ['entry_date' => '2026-10-27']))->assertSessionHasErrors('entry_date');
        $this->post($route, array_merge($ok, ['type' => 'loan']))->assertSessionHasErrors('type');
        $this->post($route, array_merge($ok, ['amount' => 0]))->assertSessionHasErrors('amount');
        $this->post($route, array_merge($ok, ['method' => 'Bitcoin']))->assertSessionHasErrors('method');
        $this->assertSame(0, CrmCapitalEntry::count());

        // Another workspace's owner / entry cannot be edited or deleted from here.
        $theirs = CrmCapitalEntry::create(['workspace_id' => $other->id, 'owner_id' => $foreign->id, 'entry_date' => '2026-02-01', 'type' => 'capital', 'amount' => 5, 'method' => 'Cash']);
        $this->put(route('crm.owner_capital.entries.update', $theirs->id), $ok)->assertNotFound();
        $this->delete(route('crm.owner_capital.entries.destroy', $theirs->id))->assertNotFound();
        $this->put(route('crm.owner_capital.owners.update', $foreign->id), ['name' => 'Hijacked'])->assertNotFound();
        $this->delete(route('crm.owner_capital.owners.destroy', $foreign->id))->assertNotFound();
        $this->assertSame('Foreign Owner', CrmCapitalOwner::withoutGlobalScopes()->find($foreign->id)->name);
    }

    public function test_the_balance_sheet_shows_capital_and_drawings_per_owner(): void
    {
        $bank = $this->addAccount(['name' => 'Equity Bank']);
        $ali = $this->addOwner('Ali Equity');
        $sara = $this->addOwner('Sara Equity');
        $this->addCapital($ali, 80000, '2026-02-01', $bank);
        $this->addCapital($sara, 40000, '2026-02-15', $bank);
        $this->addCapital($ali, 6000, '2026-06-01', $bank, 'drawing');

        $sheet = $this->sheet();
        $equity = $this->sheetLines($sheet, 'equity');
        $this->assertMoney(80000, $equity["Owner's Capital — Ali Equity"]);
        $this->assertMoney(-6000, $equity['Less: Drawings — Ali Equity']);
        $this->assertMoney(40000, $equity["Owner's Capital — Sara Equity"]);
        $this->assertArrayNotHasKey('Less: Drawings — Sara Equity', $equity);
        $this->assertMoney(114000, $this->sheetLines($sheet, 'assets')['Equity Bank']);
        $this->assertMoney(114000, $sheet['equity_total']);
        $this->assertMoney(0, $this->balancingFigure());
        $this->assertMoney(0, $this->trialDifference());

        // Before the drawing there is no drawings line; before Sara's capital only Ali shows.
        $may = $this->sheetLines($this->sheet('2026-05-31'), 'equity');
        $this->assertArrayNotHasKey('Less: Drawings — Ali Equity', $may);
        $feb = $this->sheetLines($this->sheet('2026-02-10'), 'equity');
        $this->assertArrayHasKey("Owner's Capital — Ali Equity", $feb);
        $this->assertArrayNotHasKey("Owner's Capital — Sara Equity", $feb);

        // The Balance Sheet report shows the same lines, and nothing is left unexplained.
        $report = $this->report('balance-sheet');
        $row = $this->tableRow($report['tables'][0], 'Less: Drawings — Ali Equity');
        $this->assertNotNull($row);
        $this->assertMoney(-6000, $row[1]);
        $this->assertMoney(0, $this->card($report, 'Not yet explained'));
        $this->assertMoney(114000, $this->card($report, 'Equity (Net Worth)'));
        // The Chart of Accounts lists the same equity accounts.
        $this->get(route('crm.chart_of_accounts.index'))->assertOk()
            ->assertSee("Owner's Capital — Ali Equity")->assertSee('Less: Drawings — Ali Equity')->assertSee("Owner's Capital — Sara Equity");

        // An opening bank balance is unexplained until matching opening capital is recorded.
        $this->addAccount(['name' => 'Old Savings', 'opening_balance' => 25000, 'opening_date' => '2026-01-01']);
        $this->assertMoney(25000, $this->balancingFigure());
        $this->addCapital($ali, 25000, '2026-01-01', null, 'capital', CrmCapitalEntry::NON_CASH);
        $this->assertMoney(0, $this->balancingFigure());
        $this->assertMoney(105000, $this->sheetLines($this->sheet(), 'equity')["Owner's Capital — Ali Equity"]);
    }

    /** Ali: 50,000 in before the year, 30,000 more in Feb, 4,000 drawn in May; 6,000 rent paid in June. */
    private function seedEquityYear(): void
    {
        $bank = $this->addAccount(['name' => 'Year Bank', 'opening_date' => '2025-01-01', 'auto_methods' => ['Bank']]);
        $ali = $this->addOwner('Ali Year');
        $this->addCapital($ali, 50000, '2025-12-01', $bank);
        $this->addCapital($ali, 30000, '2026-02-01', $bank);
        $this->addCapital($ali, 4000, '2026-05-01', $bank, 'drawing');
        $this->addExpense(['expense_date' => '2026-06-01', 'category' => 'Rent', 'amount' => 6000, 'bank_account_id' => $bank]);
    }

    public function test_the_owners_equity_report_shows_capital_drawings_and_profit(): void
    {
        $this->seedEquityYear();
        $report = $this->report('owners-equity');
        $table = $report['tables'][0];

        // [Opening, Capital In, Drawings, Profit / (Loss), Other Changes, Closing]
        $this->assertEquals([50000.0, 30000.0, -4000.0, null, null, 76000.0], array_slice($this->tableRow($table, 'Ali Year'), 1));
        $this->assertEquals([null, null, null, -6000.0, null, -6000.0], array_slice($this->tableRow($table, 'Retained Earnings'), 1));
        $this->assertEquals([null, null, null, null, null, null], array_slice($this->tableRow($table, 'Opening Balances not recorded (balancing figure)'), 1));
        $this->assertEquals([50000.0, 30000.0, -4000.0, -6000.0, 0.0, 70000.0], array_map('floatval', array_slice($this->tableRow($table, 'Total Equity'), 1)));

        $this->assertMoney(50000, $this->card($report, 'Opening Equity'));
        $this->assertMoney(30000, $this->card($report, 'Capital Put In'));
        $this->assertMoney(4000, $this->card($report, 'Drawings'));
        $this->assertMoney(-6000, $this->card($report, 'Profit / (Loss)'));
        $this->assertMoney(70000, $this->card($report, 'Closing Equity'));
        $this->assertMoney(0, $this->balancingFigure());
        $this->assertMoney(0, $this->balancingFigure('2025-12-31'));
    }

    public function test_the_cash_flow_shows_capital_and_drawings_as_financing(): void
    {
        $this->seedEquityYear();
        // Opening / non-cash capital moves no cash.
        $this->addCapital($this->addOwner('Sara Year'), 10000, '2026-03-01', null, 'capital', CrmCapitalEntry::NON_CASH);

        $flow = $this->report('cash-flow');
        $table = $flow['tables'][0];
        $this->assertMoney(30000, $this->tableRow($table, "Owner's capital introduced")[1]);
        $this->assertMoney(-4000, $this->tableRow($table, "Owner's drawings")[1]);
        $this->assertMoney(26000, $this->tableRow($table, 'Net cash from financing activities')[1]);
        $this->assertMoney(-6000, $this->tableRow($table, 'Paid for expenses')[1]);
        $this->assertMoney(-6000, $this->tableRow($table, 'Net cash from operating activities')[1]);
        $this->assertMoney(20000, $this->tableRow($table, 'Net increase / (decrease) in cash (A + B + C)')[1]);
        $this->assertNull($this->tableRow($table, "Opening balances entered / entries outside an account's dates"), 'every movement is explained');

        $this->assertMoney(50000, $this->card($flow, 'Cash & Bank at Start'));
        $this->assertMoney(20000, $this->card($flow, 'Net Change in Cash'));
        $this->assertMoney(70000, $this->card($flow, 'Cash & Bank at End'));
        $this->assertMoney(70000, $this->ledger()->balances()['total'], 'end of the cash flow = Bank & Cash total');
        // The previous period had the 2025 capital.
        $this->assertMoney(50000, $this->tableRow($table, "Owner's capital introduced")[2]);
    }

    public function test_the_books_stay_balanced_from_capital_through_expenses_transfers_and_drawings(): void
    {
        $balanced = function (string $step, string $asOf = '2026-10-26') {
            $this->assertMoney(0, $this->balancingFigure($asOf), 'balancing figure after: ' . $step);
            $this->assertMoney(0, $this->trialDifference($asOf), 'trial balance difference after: ' . $step);
        };
        $bank = $this->addAccount(['name' => 'Main Bank', 'auto_methods' => ['Bank']]);
        $cash = $this->addAccount(['name' => 'Cash Box', 'type' => 'cash', 'auto_methods' => ['Cash']]);
        $balanced('two empty accounts');

        $owner = $this->addOwner('Balanced Owner');
        $this->addCapital($owner, 120000, '2026-01-05', $bank);
        $balanced('capital into the bank');

        $rent = $this->addExpense(['expense_date' => '2026-02-01', 'category' => 'Rent', 'name' => 'Office rent', 'amount' => 8000, 'bank_account_id' => $bank]);
        $balanced('rent paid from the bank');

        $transfer = $this->addTransfer($bank, $cash, 15000, '2026-02-10');
        $balanced('bank to cash transfer');

        $this->addExpense(['expense_date' => '2026-03-01', 'category' => 'Raw Material', 'paid_from' => 'Cash', 'amount' => 2500]);   // auto-booked to Cash Box
        $balanced('raw material paid in cash');

        $this->addCapital($owner, 3000, '2026-04-01', $cash, 'drawing', 'Cash');
        $balanced('drawing from cash');

        $this->addExpense(['expense_date' => '2026-05-01', 'category' => 'Utilities', 'paid_from' => 'Cheque', 'amount' => 700]);     // no account picks up cheques
        $balanced('cheque not booked to any account');

        $this->put(route('crm.expenses.update', $rent->id), [
            'expense_date' => '2026-02-01', 'category' => 'Rent', 'name' => 'Office rent', 'paid_from' => 'Bank Transfer', 'payee_type' => 'other',
            'amount' => 8500, 'currency' => 'AED', 'bank_account_id' => $bank->id,
        ])->assertSessionHasNoErrors();
        $balanced('rent edited');

        $this->delete(route('crm.bank_accounts.transfers.destroy', $transfer->id))->assertSessionHas('success');
        $this->addTransfer($bank, $cash, 10000, '2026-02-10');
        $balanced('transfer replaced');
        $balanced('mid-February', '2026-02-15');
        $balanced('end of March', '2026-03-31');

        // Assets: Main Bank 120,000 − 8,500 − 10,000; Cash Box 10,000 − 2,500 − 3,000; unbooked cheque −700.
        $sheet = $this->sheet();
        $assets = $this->sheetLines($sheet, 'assets');
        $this->assertMoney(101500, $assets['Main Bank']);
        $this->assertMoney(4500, $assets['Cash Box']);
        $this->assertMoney(-700, $assets['Not booked to any account (1)']);
        $this->assertMoney(105300, $sheet['assets_total']);
        $equity = collect($sheet['equity'])->keyBy(3);
        $this->assertMoney(120000, $equity['capital'][1]);
        $this->assertMoney(-3000, $equity['drawings'][1]);
        $this->assertMoney(-11700, $equity['profit'][1]);
        $this->assertMoney(105300, $sheet['equity_total']);

        $report = $this->report('balance-sheet');
        $this->assertMoney(105300, $this->card($report, 'Total Assets'));
        $this->assertMoney(105300, $this->card($report, 'Equity (Net Worth)'));
        $this->assertMoney(0, $this->card($report, 'Not yet explained'));
        $trial = $this->report('trial-balance');
        $this->assertMoney($this->card($trial, 'Total Debit'), $this->card($trial, 'Total Credit'));
        $this->assertMoney(0, $this->card($trial, 'Difference'));
    }

    public function test_the_books_stay_balanced_without_any_bank_accounts(): void
    {
        $owner = $this->addOwner('No Account Owner');
        $this->addCapital($owner, 10000, '2026-01-10', null, 'capital', 'Cash');
        $this->addExpense(['expense_date' => '2026-02-01', 'paid_from' => 'Cash', 'amount' => 2000]);
        $this->addCapital($owner, 500, '2026-03-01', null, 'drawing', 'Cash');

        $sheet = $this->sheet();
        $this->assertFalse($sheet['has_bank_accounts']);
        $this->assertMoney(7500, $this->sheetLines($sheet, 'assets')['Cash & Bank (net movement*)']);
        $this->assertSame('Opening Balances not recorded (balancing figure)', collect($sheet['equity'])->firstWhere(3, 'balancing')[0]);
        $this->assertMoney(0, $this->balancingFigure());
        $this->assertMoney(0, $this->trialDifference());
        $flow = $this->report('cash-flow');
        $this->assertMoney(7500, $this->card($flow, 'Cash & Bank at End'));
        $this->assertMoney(7500, $this->card($flow, 'Net Change in Cash'));
    }

    public function test_only_admins_and_accounts_can_open_owners_capital(): void
    {
        $owner = $this->addOwner('Private Owner');
        $entry = $this->addCapital($owner, 500, '2026-02-01', null, 'capital', 'Cash');

        foreach (['sales_manager', 'sales'] as $role) {
            $this->signIn($this->makeUser($role));
            $this->get(route('crm.owner_capital.index'))->assertForbidden();
            $this->post(route('crm.owner_capital.owners.store'), ['name' => 'Intruder'])->assertForbidden();
            $this->put(route('crm.owner_capital.owners.update', $owner->id), ['name' => 'Renamed'])->assertForbidden();
            $this->delete(route('crm.owner_capital.owners.destroy', $owner->id))->assertForbidden();
            $this->post(route('crm.owner_capital.entries.store'), ['owner_id' => $owner->id, 'entry_date' => '2026-02-01', 'type' => 'capital', 'amount' => 1, 'method' => 'Cash'])->assertForbidden();
            $this->delete(route('crm.owner_capital.entries.destroy', $entry->id))->assertForbidden();
        }
        $this->assertSame(1, CrmCapitalOwner::count());
        $this->assertSame(1, CrmCapitalEntry::count());

        $this->signIn($this->makeUser('accounts'));
        $this->get(route('crm.owner_capital.index'))->assertOk()->assertSee('Private Owner');
        $this->addCapital($owner, 200, '2026-03-01', null, 'drawing', 'Cash');
        $this->delete(route('crm.owner_capital.entries.destroy', $entry->id))->assertSessionHas('success');
        $this->assertSame(1, CrmCapitalEntry::count());
    }
}
