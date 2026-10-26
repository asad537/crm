<?php

namespace Tests\Feature\Accounting;

use App\CrmBankAccount;
use App\CrmBankClearance;
use App\CrmBankReconciliation;
use App\CrmBankTransfer;
use App\CrmDeletionLog;
use App\CrmExpense;
use Carbon\Carbon;

/** Bank & Cash: accounts with opening balances, statements, transfers, assigning entries and bank reconciliation. */
class BankAccountTest extends AccountingTestCase
{
    use BankCashHelpers;

    public function test_an_admin_adds_a_bank_account_with_an_opening_balance(): void
    {
        $this->post(route('crm.bank_accounts.store'), [
            'name' => 'Emirates NBD Current', 'type' => 'bank', 'bank_name' => 'Emirates NBD', 'account_number' => 'AE070331234567890123456',
            'currency' => 'AED', 'opening_balance' => '25000.50', 'opening_date' => '2026-01-01', 'auto_methods' => ['Bank', 'Bank', 'Cheque'],
            'is_active' => '1', 'notes' => 'Main account',
        ])->assertRedirect(route('crm.bank_accounts.index'))->assertSessionHas('success', 'Emirates NBD Current added with an opening balance of 25,000.50.');

        $account = CrmBankAccount::where('name', 'Emirates NBD Current')->firstOrFail();
        $this->assertSame($this->workspace->id, (int) $account->workspace_id);
        $this->assertSame($this->admin->id, (int) $account->created_by);
        $this->assertSame('2026-01-01', $account->opening_date->toDateString());
        $this->assertSame(['Bank', 'Cheque'], $account->auto_methods, 'duplicate methods are dropped');
        $this->assertTrue($account->is_active);

        $balances = $this->ledger()->balances();
        $this->assertMoney(25000.50, $balances['accounts'][$account->id]['opening']);
        $this->assertMoney(25000.50, $balances['accounts'][$account->id]['balance']);
        $this->assertMoney(25000.50, $balances['total']);
        $this->assertSame(0, $balances['unassigned']['count']);

        $this->get(route('crm.bank_accounts.index'))->assertOk()->assertSee('Emirates NBD Current')
            ->assertViewHas('balances', fn ($b) => abs($b['total'] - 25000.50) < 0.005);
    }

    public function test_an_account_needs_valid_details(): void
    {
        $this->post(route('crm.bank_accounts.store'), [
            'name' => '', 'type' => 'savings', 'currency' => 'JPY', 'opening_balance' => 'lots', 'opening_date' => 'someday', 'auto_methods' => ['Crypto'],
        ])->assertSessionHasErrors(['name', 'type', 'currency', 'opening_balance', 'opening_date', 'auto_methods.0']);

        $this->assertSame(0, CrmBankAccount::count());
    }

    public function test_updating_an_account_and_a_payment_method_feeds_only_one_account(): void
    {
        $cash = $this->addAccount(['name' => 'Cash in Hand', 'type' => 'cash', 'auto_methods' => ['Cash', 'Petty Cash']]);
        $petty = $this->addAccount(['name' => 'Petty Cash Box', 'type' => 'cash', 'auto_methods' => ['Petty Cash']]);
        $this->assertSame(['Cash'], $cash->fresh()->auto_methods, 'Petty Cash moved to the new account');
        $this->assertSame(['Petty Cash'], $petty->fresh()->auto_methods);

        $this->from(route('crm.bank_accounts.index'))->put(route('crm.bank_accounts.update', $cash->id), [
            'name' => 'Cash in Hand (Office)', 'type' => 'cash', 'currency' => 'AED', 'opening_balance' => '750',
            'opening_date' => '2026-02-01', 'auto_methods' => ['Cash', 'Petty Cash'], 'notes' => 'Drawer',
        ])->assertRedirect(route('crm.bank_accounts.index'))->assertSessionHas('success', 'Cash in Hand (Office) updated.');

        $cash->refresh();
        $this->assertSame('Cash in Hand (Office)', $cash->name);
        $this->assertMoney(750, $cash->opening_balance);
        $this->assertSame('2026-02-01', $cash->opening_date->toDateString());
        $this->assertFalse($cash->is_active, 'unticked "Active" closes the account');
        $this->assertSame(['Cash', 'Petty Cash'], $cash->auto_methods);
        $this->assertSame([], $petty->fresh()->auto_methods, 'the edited account took Petty Cash back');

        // A closed account no longer picks up its methods automatically.
        $expense = $this->addExpense(['paid_from' => 'Cash', 'amount' => 40]);
        $movement = $this->ledger()->findMovement('expense', $expense->id);
        $this->assertNull($movement->account_id);
        $this->assertMoney(750, $this->balanceOf($cash));
    }

    public function test_an_account_with_entries_or_transfers_cannot_be_deleted(): void
    {
        $empty = $this->addAccount(['name' => 'Unused Account']);
        // Another workspace's (inconsistent) transfer that points at this id does not block the delete.
        $other = $this->otherWorkspace();
        CrmBankTransfer::create(['workspace_id' => $other->id, 'from_account_id' => $empty->id, 'to_account_id' => $empty->id, 'transfer_date' => '2026-03-01', 'amount' => 1]);
        $this->delete(route('crm.bank_accounts.destroy', $empty->id))
            ->assertRedirect(route('crm.bank_accounts.index'))->assertSessionHas('success', 'Unused Account deleted.');
        $this->assertNull(CrmBankAccount::find($empty->id));
        $this->assertTrue(CrmDeletionLog::where('entity_type', 'bank_account')->where('entity_id', $empty->id)->where('workspace_id', $this->workspace->id)->exists());

        $booked = $this->addAccount(['name' => 'Booked Account']);
        $this->addExpense(['bank_account_id' => $booked, 'amount' => 10]);
        $this->delete(route('crm.bank_accounts.destroy', $booked->id))->assertSessionHas('error');
        $this->assertNotNull(CrmBankAccount::find($booked->id));

        $from = $this->addAccount(['name' => 'Transfer From']);
        $to = $this->addAccount(['name' => 'Transfer To']);
        $this->addTransfer($from, $to, 50, '2026-03-01');
        $this->delete(route('crm.bank_accounts.destroy', $to->id))->assertSessionHas('error');
        $this->assertNotNull(CrmBankAccount::find($to->id));

        // Entries picked up only automatically (by payment method) do not block a delete; they become unassigned.
        $auto = $this->addAccount(['name' => 'Auto Cash', 'type' => 'cash', 'auto_methods' => ['Cash']]);
        $expense = $this->addExpense(['paid_from' => 'Cash', 'amount' => 25]);
        $this->assertSame($auto->id, $this->ledger()->findMovement('expense', $expense->id)->account_id);
        $this->delete(route('crm.bank_accounts.destroy', $auto->id))->assertSessionHas('success');
        $this->assertNull($this->ledger()->findMovement('expense', $expense->id)->account_id);
    }

    public function test_the_statement_shows_a_running_balance(): void
    {
        $bank = $this->addAccount(['name' => 'ADCB Current', 'opening_balance' => 10000, 'opening_date' => '2026-01-01']);
        $savings = $this->addAccount(['name' => 'ADCB Savings']);
        $owner = $this->addOwner('Statement Owner');
        $this->addCapital($owner, 5000, '2026-02-01', $bank);
        $this->addExpense(['expense_date' => '2026-03-10', 'amount' => 1200, 'bank_account_id' => $bank, 'name' => 'March rent']);
        $this->addTransfer($bank, $savings, 2000, '2026-04-01', ['reference' => 'TRF-APR']);
        // Before the opening date: already inside the opening balance, so not on the statement.
        $this->addExpense(['expense_date' => '2025-12-15', 'amount' => 999, 'bank_account_id' => $bank]);

        $this->get(route('crm.bank_accounts.show', $bank->id))->assertOk()->assertSee('ADCB Current')->assertSee('11,800.00')
            ->assertViewHas('statement', function ($s) {
                $this->assertSame(['Transfer', 'Expense Payment', "Owner's Capital"], array_map(fn ($r) => $r->type, $s['rows']));
                $this->assertSame([11800.0, 13800.0, 15000.0], array_map(fn ($r) => (float) $r->balance, $s['rows']));
                $this->assertSame('To ADCB Savings', $s['rows'][0]->party);
                $this->assertMoney(10000, $s['opening']);
                $this->assertMoney(10000, $s['brought_forward']);
                $this->assertMoney(5000, $s['in']);
                $this->assertMoney(3200, $s['out']);
                $this->assertMoney(11800, $s['closing']);
                return true;
            });

        // Limited to March: brought forward is the balance on 28 Feb.
        $this->get(route('crm.bank_accounts.show', ['id' => $bank->id, 'date_from' => '2026-03-01', 'date_to' => '2026-03-31']))->assertOk()
            ->assertViewHas('statement', function ($s) {
                $this->assertCount(1, $s['rows']);
                $this->assertSame('Expense Payment', $s['rows'][0]->type);
                $this->assertMoney(15000, $s['brought_forward']);
                $this->assertMoney(0, $s['in']);
                $this->assertMoney(1200, $s['out']);
                $this->assertMoney(13800, $s['closing']);
                return true;
            });

        $this->get(route('crm.bank_accounts.show', $savings->id))->assertOk()
            ->assertViewHas('statement', fn ($s) => count($s['rows']) === 1 && $s['rows'][0]->party === 'From ADCB Current' && abs($s['closing'] - 2000) < 0.005);
        $this->assertMoney(11800, $this->balanceOf($bank));
    }

    public function test_a_transfer_moves_money_between_accounts_without_changing_the_total(): void
    {
        $bank = $this->addAccount(['name' => 'FAB Current', 'opening_balance' => 50000]);
        $cash = $this->addAccount(['name' => 'Office Cash', 'type' => 'cash', 'opening_balance' => 1000]);
        $balancingBefore = $this->balancingFigure();
        $this->assertMoney(51000, $balancingBefore, 'opening balances without capital are the balancing figure');

        $this->post(route('crm.bank_accounts.transfers.store'), [
            'from_account_id' => $bank->id, 'to_account_id' => $cash->id, 'transfer_date' => '2026-05-01', 'amount' => '3000', 'reference' => 'CHQ-77', 'note' => 'Cash for the week',
        ])->assertSessionHas('success', '3,000.00 moved from FAB Current to Office Cash.');
        $transfer = CrmBankTransfer::firstOrFail();
        $this->assertSame($this->workspace->id, (int) $transfer->workspace_id);
        $this->assertSame('CHQ-77', $transfer->reference);

        $balances = $this->ledger()->balances();
        $this->assertMoney(47000, $balances['accounts'][$bank->id]['balance']);
        $this->assertMoney(3000, $balances['accounts'][$bank->id]['transfers_out']);
        $this->assertMoney(4000, $balances['accounts'][$cash->id]['balance']);
        $this->assertMoney(3000, $balances['accounts'][$cash->id]['transfers_in']);
        $this->assertMoney(51000, $balances['total']);
        // The day before, nothing moved yet.
        $this->assertMoney(50000, $this->balanceOf($bank, '2026-04-30'));
        $this->assertMoney(1000, $this->balanceOf($cash, '2026-04-30'));

        // Balance sheet: both accounts change, total assets and the balancing figure do not.
        $sheet = $this->sheet();
        $cashLines = $this->sheetLines($sheet, 'assets');
        $this->assertMoney(47000, $cashLines['FAB Current']);
        $this->assertMoney(4000, $cashLines['Office Cash']);
        $this->assertMoney(51000, $sheet['assets_total']);
        $this->assertMoney($balancingBefore, $this->balancingFigure());
        $this->assertMoney(0, $this->trialDifference());
        // The cash flow statement does not count a transfer as money in or out.
        $flow = $this->report('cash-flow');
        $this->assertMoney(0, $this->card($flow, 'Net Change in Cash'));

        $this->from(route('crm.bank_accounts.index'))->delete(route('crm.bank_accounts.transfers.destroy', $transfer->id))
            ->assertRedirect(route('crm.bank_accounts.index'))->assertSessionHas('success', 'Transfer deleted.');
        $this->assertSame(0, CrmBankTransfer::count());
        $this->assertTrue(CrmDeletionLog::where('entity_type', 'bank_transfer')->where('entity_id', $transfer->id)->exists());
        $this->assertMoney(50000, $this->balanceOf($bank));
        $this->assertMoney(1000, $this->balanceOf($cash));
    }

    public function test_a_transfer_needs_two_different_accounts_of_this_workspace(): void
    {
        $bank = $this->addAccount(['name' => 'RAK Bank']);
        $cash = $this->addAccount(['name' => 'RAK Cash', 'type' => 'cash']);
        $route = route('crm.bank_accounts.transfers.store');

        $this->post($route, ['from_account_id' => $bank->id, 'to_account_id' => $bank->id, 'transfer_date' => '2026-05-01', 'amount' => 10])
            ->assertSessionHasErrors(['from_account_id' => 'Pick two different accounts.']);
        $this->post($route, ['from_account_id' => $bank->id, 'to_account_id' => $cash->id, 'transfer_date' => '2026-10-27', 'amount' => 10])
            ->assertSessionHasErrors('transfer_date');
        $this->post($route, ['from_account_id' => $bank->id, 'to_account_id' => $cash->id, 'transfer_date' => '2026-05-01', 'amount' => 0])
            ->assertSessionHasErrors('amount');

        $foreign = CrmBankAccount::create(['workspace_id' => $this->otherWorkspace()->id, 'name' => 'Not Ours', 'type' => 'bank', 'currency' => 'AED',
            'opening_balance' => 0, 'opening_date' => '2026-01-01', 'is_active' => true]);
        $this->post($route, ['from_account_id' => $bank->id, 'to_account_id' => $foreign->id, 'transfer_date' => '2026-05-01', 'amount' => 10])
            ->assertStatus(422);
        $this->assertSame(0, CrmBankTransfer::count());

        // Another workspace's transfer cannot be deleted from here.
        $theirs = CrmBankTransfer::create(['workspace_id' => $foreign->workspace_id, 'from_account_id' => $foreign->id, 'to_account_id' => $foreign->id,
            'transfer_date' => '2026-05-01', 'amount' => 5]);
        $this->delete(route('crm.bank_accounts.transfers.destroy', $theirs->id))->assertNotFound();
    }

    public function test_unassigned_entries_are_listed_and_can_be_booked_to_an_account(): void
    {
        $bank = $this->addAccount(['name' => 'Mashreq Current', 'auto_methods' => ['Bank']]);
        $cash = $this->addAccount(['name' => 'Mashreq Cash', 'type' => 'cash']);
        $cheque = $this->addExpense(['paid_from' => 'Cheque', 'amount' => 450, 'name' => 'Cheque to landlord']);
        $transfer = $this->addExpense(['paid_from' => 'Bank Transfer', 'amount' => 300]);

        // Bank Transfer is picked up by Mashreq Current; nobody picks up cheques.
        $this->assertSame($bank->id, $this->ledger()->findMovement('expense', $transfer->id)->account_id);
        $this->get(route('crm.bank_accounts.unassigned'))->assertOk()->assertSee($cheque->number)
            ->assertViewHas('movements', fn ($m) => $m->count() === 1 && $m->first()->link_type === 'expense' && (int) $m->first()->link_id === $cheque->id);
        $balances = $this->ledger()->balances();
        $this->assertSame(1, $balances['unassigned']['count']);
        $this->assertMoney(450, $balances['unassigned']['out']);
        $this->assertMoney(-300, $balances['accounts'][$bank->id]['balance']);
        $this->assertMoney(-450, $this->sheetLines($this->sheet(), 'assets')['Not booked to any account (1)']);

        // Book the cheque to the cash account.
        $this->from(route('crm.bank_accounts.unassigned'))->post(route('crm.bank_accounts.assign'), ['link_type' => 'expense', 'link_id' => $cheque->id, 'bank_account_id' => $cash->id])
            ->assertRedirect(route('crm.bank_accounts.unassigned'))->assertSessionHas('success', 'Entry booked to Mashreq Cash.');
        $this->assertSame($cash->id, (int) $cheque->fresh()->bank_account_id);
        $this->get(route('crm.bank_accounts.unassigned'))->assertOk()->assertViewHas('movements', fn ($m) => $m->isEmpty());
        $movement = $this->ledger()->findMovement('expense', $cheque->id);
        $this->assertSame($cash->id, $movement->account_id);
        $this->assertFalse($movement->auto);
        $this->assertMoney(-450, $this->balanceOf($cash));
        $this->assertArrayNotHasKey('Not booked to any account (1)', $this->sheetLines($this->sheet(), 'assets'));

        // An auto-booked entry can be moved to another account, and back to automatic.
        $this->post(route('crm.bank_accounts.assign'), ['link_type' => 'expense', 'link_id' => $transfer->id, 'bank_account_id' => $cash->id])->assertSessionHas('success');
        $this->assertMoney(0, $this->balanceOf($bank));
        $this->assertMoney(-750, $this->balanceOf($cash));
        $this->post(route('crm.bank_accounts.assign'), ['link_type' => 'expense', 'link_id' => $transfer->id, 'bank_account_id' => ''])
            ->assertSessionHas('success', 'Entry set back to automatic (by payment method).');
        $this->assertNull($transfer->fresh()->bank_account_id);
        $this->assertMoney(-300, $this->balanceOf($bank));

        // Owner's capital is an entry like any other.
        $capital = $this->addCapital($this->addOwner('Assign Owner'), 1000, '2026-04-01', null, 'capital', 'Cheque');
        $this->assertNull($this->ledger()->findMovement('capital', $capital->id)->account_id);
        $this->post(route('crm.bank_accounts.assign'), ['link_type' => 'capital', 'link_id' => $capital->id, 'bank_account_id' => $bank->id])->assertSessionHas('success');
        $this->assertMoney(700, $this->balanceOf($bank));
    }

    public function test_assigning_rejects_unknown_entries_and_foreign_accounts(): void
    {
        $bank = $this->addAccount(['name' => 'Assign Guard Bank']);
        $other = $this->otherWorkspace();
        $theirExpense = CrmExpense::create(['workspace_id' => $other->id, 'expense_date' => '2026-03-01', 'category' => 'Rent', 'name' => 'Their rent',
            'paid_from' => 'Cash', 'payee_type' => 'other', 'amount' => 99, 'currency' => 'AED']);
        $theirAccount = CrmBankAccount::create(['workspace_id' => $other->id, 'name' => 'Their Bank', 'type' => 'bank', 'currency' => 'AED',
            'opening_balance' => 0, 'opening_date' => '2026-01-01', 'is_active' => true]);

        $this->post(route('crm.bank_accounts.assign'), ['link_type' => 'expense', 'link_id' => $theirExpense->id, 'bank_account_id' => $bank->id])->assertNotFound();
        $this->assertNull(CrmExpense::withoutGlobalScopes()->find($theirExpense->id)->bank_account_id);
        $this->post(route('crm.bank_accounts.assign'), ['link_type' => 'nonsense', 'link_id' => 1])->assertSessionHasErrors('link_type');

        // Our entry pointed at another workspace's account is stored as "automatic" instead.
        $mine = $this->addExpense(['paid_from' => 'Cash', 'amount' => 20, 'bank_account_id' => $bank]);
        $this->post(route('crm.bank_accounts.assign'), ['link_type' => 'expense', 'link_id' => $mine->id, 'bank_account_id' => $theirAccount->id])->assertSessionHas('success');
        $this->assertNull($mine->fresh()->bank_account_id);
    }

    public function test_bank_reconciliation_marks_cleared_entries_and_shows_the_difference(): void
    {
        $bank = $this->addAccount(['name' => 'ENBD Reconcile', 'opening_balance' => 1000, 'opening_date' => '2026-01-01']);
        $capital = $this->addCapital($this->addOwner('Recon Owner'), 4000, '2026-09-01', $bank);
        $rent = $this->addExpense(['expense_date' => '2026-09-10', 'amount' => 500, 'bank_account_id' => $bank]);
        $fuel = $this->addExpense(['expense_date' => '2026-09-20', 'amount' => 300, 'bank_account_id' => $bank, 'category' => 'Transport & Fuel']);
        $late = $this->addExpense(['expense_date' => '2026-10-05', 'amount' => 50, 'bank_account_id' => $bank]);
        $keys = ['capital:' . $capital->id, 'expense:' . $rent->id, 'expense:' . $fuel->id];

        // Up to 30 Sep: three entries, oldest first, nothing cleared yet.
        $this->get(route('crm.bank_accounts.reconcile', ['id' => $bank->id, 'statement_date' => '2026-09-30']))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('key')->all() === $keys)
            ->assertViewHas('bookBalance', 4200.0)
            ->assertViewHas('hiddenCleared', 0);

        // The bank statement shows 4,500: the 300 fuel payment has not cleared yet.
        $this->post(route('crm.bank_accounts.reconcile.save', $bank->id), [
            'statement_date' => '2026-09-30', 'statement_balance' => '4500', 'shown' => $keys, 'cleared' => [$keys[0], $keys[1]], 'note' => 'September',
        ])->assertRedirect(route('crm.bank_accounts.reconcile', ['id' => $bank->id, 'statement_date' => '2026-09-30']))->assertSessionHas('success');

        $this->assertEqualsCanonicalizing([$keys[0], $keys[1]], CrmBankClearance::where('bank_account_id', $bank->id)->pluck('movement_key')->all());
        $rec = CrmBankReconciliation::where('bank_account_id', $bank->id)->firstOrFail();
        $this->assertSame('2026-09-30', $rec->statement_date->toDateString());
        $this->assertMoney(4500, $rec->statement_balance);
        $this->assertMoney(4200, $rec->book_balance);
        $this->assertMoney(0, $rec->uncleared_in);
        $this->assertMoney(300, $rec->uncleared_out);
        $this->assertMoney(0, $rec->difference);
        $this->assertSame('September', $rec->note);

        // Cleared entries are hidden unless asked for, and the last statement balance is remembered.
        $this->get(route('crm.bank_accounts.reconcile', ['id' => $bank->id, 'statement_date' => '2026-09-30']))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->pluck('key')->all() === [$keys[2]])
            ->assertViewHas('hiddenCleared', 2)
            ->assertViewHas('statementBalance', 4500.0);
        $this->get(route('crm.bank_accounts.reconcile', ['id' => $bank->id, 'statement_date' => '2026-09-30', 'show_cleared' => 1]))->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 3 && $rows->first()->cleared !== null && $rows->last()->cleared === null);

        // A statement balance that does not match is saved with its difference; unticking an entry un-clears it.
        $this->post(route('crm.bank_accounts.reconcile.save', $bank->id), [
            'statement_date' => '2026-09-30', 'statement_balance' => '4000', 'shown' => $keys, 'cleared' => [$keys[0], $keys[2]],
        ])->assertSessionHas('error');
        $this->assertEqualsCanonicalizing([$keys[0], $keys[2]], CrmBankClearance::where('bank_account_id', $bank->id)->pluck('movement_key')->all());
        $rec = CrmBankReconciliation::where('bank_account_id', $bank->id)->latest('id')->firstOrFail();
        // CRM 4,200 − uncleared out (rent 500) → bank should show 4,700; it shows 4,000.
        $this->assertMoney(500, $rec->uncleared_out);
        $this->assertMoney(-700, $rec->difference);
        $this->assertSame(2, CrmBankReconciliation::where('bank_account_id', $bank->id)->count());

        // The report lists the account with what is still open today (rent and the October payment).
        $report = $this->report('bank-reconciliation');
        $row = $this->tableRow($report['tables'][0], 'ENBD Reconcile');
        $this->assertMoney(4150, $row[1]);
        $this->assertSame(2, $row[2]);
        $this->assertMoney(0, $row[3]);
        $this->assertMoney(550, $row[4]);
        $this->assertSame('30 Sep 2026', $row[5]);
        $this->assertMoney(4000, $row[6]);
        $this->assertMoney(-700, $row[7]);
        $this->assertSame(1.0, $this->card($report, 'Accounts'));
        $this->assertSame(2.0, $this->card($report, 'Entries Not Cleared'));
        $this->assertMoney(4150, $this->card($report, 'Book Balance (all accounts)'));
        $this->assertNotNull($late);
    }

    public function test_balances_on_different_dates(): void
    {
        $a = $this->addAccount(['name' => 'Dated Bank', 'opening_balance' => 1000, 'opening_date' => '2026-01-01']);
        $b = $this->addAccount(['name' => 'Dated Cash', 'type' => 'cash', 'opening_balance' => 500, 'opening_date' => '2026-06-01']);
        $this->addCapital($this->addOwner('Dated Owner'), 2000, '2026-03-01', $a);
        $this->addExpense(['expense_date' => '2026-07-01', 'amount' => 300, 'bank_account_id' => $a]);
        $this->addTransfer($a, $b, 200, '2026-08-01');

        $expected = [
            '2025-12-31' => [0, 0],         // neither account open yet
            '2026-01-01' => [1000, 0],
            '2026-03-31' => [3000, 0],
            '2026-06-30' => [3000, 500],
            '2026-07-31' => [2700, 500],
            '2026-08-31' => [2500, 700],
        ];
        foreach ($expected as $date => [$bankBalance, $cashBalance]) {
            $balances = $this->ledger()->balances(Carbon::parse($date));
            $this->assertMoney($bankBalance, $balances['accounts'][$a->id]['balance'], 'Dated Bank on ' . $date);
            $this->assertMoney($cashBalance, $balances['accounts'][$b->id]['balance'], 'Dated Cash on ' . $date);
            $this->assertMoney($bankBalance + $cashBalance, $balances['total'], 'total on ' . $date);
        }
        $today = $this->ledger()->balances();
        $this->assertMoney(2500, $today['accounts'][$a->id]['balance']);
        $this->assertMoney(700, $today['accounts'][$b->id]['balance']);
        $this->assertSame(2, $today['accounts'][$a->id]['entries']);

        // The balance sheet on a date uses the same balances.
        $this->assertMoney(3000, $this->sheetLines($this->sheet('2026-06-30'), 'assets')['Dated Bank']);
        $this->assertMoney(500, $this->sheetLines($this->sheet('2026-06-30'), 'assets')['Dated Cash']);
    }

    public function test_the_balance_sheet_shows_each_account_and_hides_closed_empty_ones(): void
    {
        $open = $this->addAccount(['name' => 'Sheet Bank', 'opening_balance' => 800]);
        $closedEmpty = $this->addAccount(['name' => 'Closed Empty', 'is_active' => 0]);
        $closedFull = $this->addAccount(['name' => 'Closed With Money', 'opening_balance' => 120, 'is_active' => 0]);
        $this->assertFalse($closedEmpty->is_active);

        $sheet = $this->sheet();
        $lines = $this->sheetLines($sheet, 'assets');
        $this->assertMoney(800, $lines['Sheet Bank']);
        $this->assertMoney(120, $lines['Closed With Money']);
        $this->assertArrayNotHasKey('Closed Empty', $lines);
        $this->assertTrue($sheet['has_bank_accounts']);
        $this->assertMoney(920, $sheet['assets_total']);
        // No capital recorded: the opening balances are the unexplained (balancing) figure.
        $this->assertSame('Opening Balances not recorded (balancing figure)', collect($sheet['equity'])->firstWhere(3, 'balancing')[0]);
        $this->assertMoney(920, $this->balancingFigure());
        $this->assertMoney(0, $this->trialDifference());
        $this->assertNotNull($open);
    }

    public function test_every_paid_from_and_capital_method_can_be_picked_up_by_an_account(): void
    {
        $expected = ['Cash' => 'Cash', 'Petty Cash' => 'Petty Cash', 'Bank Transfer' => 'Bank', 'Company Account' => 'Bank', 'Card' => 'Card', 'Cheque' => 'Cheque'];
        foreach (array_unique(array_merge(CrmExpense::PAID_FROM, \App\CrmCapitalEntry::METHODS)) as $method) {
            if ($method === \App\CrmCapitalEntry::NON_CASH) continue;
            $group = \App\Services\BankLedgerService::methodGroup($method);
            $this->assertSame($expected[$method], $group, $method);
            $this->assertArrayHasKey($group, CrmBankAccount::METHOD_GROUPS, $method . ' maps to a group an account can claim');
        }
        $this->assertSame('Not specified', \App\Services\BankLedgerService::methodGroup(' '));
    }

    public function test_a_sales_agent_cannot_open_bank_and_cash(): void
    {
        $bank = $this->addAccount(['name' => 'Private Bank']);
        $this->signIn($this->makeUser('sales'));

        $this->get(route('crm.bank_accounts.index'))->assertForbidden();
        $this->get(route('crm.bank_accounts.show', $bank->id))->assertForbidden();
        $this->get(route('crm.bank_accounts.unassigned'))->assertForbidden();
        $this->post(route('crm.bank_accounts.store'), ['name' => 'Sneaky', 'type' => 'bank', 'currency' => 'AED', 'opening_balance' => 1, 'opening_date' => '2026-01-01'])
            ->assertForbidden();
        $this->assertSame(1, CrmBankAccount::count());
    }

    public function test_a_sales_manager_can_view_but_not_change_bank_and_cash(): void
    {
        $bank = $this->addAccount(['name' => 'Viewable Bank']);
        $cash = $this->addAccount(['name' => 'Viewable Cash', 'type' => 'cash']);
        $expense = $this->addExpense(['paid_from' => 'Cheque', 'amount' => 10]);
        $transfer = $this->addTransfer($bank, $cash, 5, '2026-03-01');
        $this->signIn($this->makeUser('sales_manager'));

        $this->get(route('crm.bank_accounts.index'))->assertOk()->assertViewHas('canManage', false);
        $this->get(route('crm.bank_accounts.show', $bank->id))->assertOk();
        $this->get(route('crm.bank_accounts.unassigned'))->assertOk();

        $account = ['name' => 'Changed', 'type' => 'bank', 'currency' => 'AED', 'opening_balance' => 1, 'opening_date' => '2026-01-01'];
        $this->post(route('crm.bank_accounts.store'), $account)->assertForbidden();
        $this->put(route('crm.bank_accounts.update', $bank->id), $account)->assertForbidden();
        $this->delete(route('crm.bank_accounts.destroy', $bank->id))->assertForbidden();
        $this->post(route('crm.bank_accounts.transfers.store'), ['from_account_id' => $bank->id, 'to_account_id' => $cash->id, 'transfer_date' => '2026-03-01', 'amount' => 1])->assertForbidden();
        $this->delete(route('crm.bank_accounts.transfers.destroy', $transfer->id))->assertForbidden();
        $this->post(route('crm.bank_accounts.assign'), ['link_type' => 'expense', 'link_id' => $expense->id, 'bank_account_id' => $bank->id])->assertForbidden();
        $this->get(route('crm.bank_accounts.reconcile', $bank->id))->assertForbidden();
        $this->post(route('crm.bank_accounts.reconcile.save', $bank->id), ['statement_date' => '2026-03-01', 'statement_balance' => 0])->assertForbidden();

        $this->assertSame('Viewable Bank', $bank->fresh()->name);
        $this->assertSame(1, CrmBankTransfer::count());
        $this->assertNull($expense->fresh()->bank_account_id);
    }

    public function test_the_accounts_role_manages_bank_and_cash(): void
    {
        $this->signIn($this->makeUser('accounts'));
        $bank = $this->addAccount(['name' => 'Accounts Bank', 'opening_balance' => 100]);
        $cash = $this->addAccount(['name' => 'Accounts Cash', 'type' => 'cash']);
        $this->addTransfer($bank, $cash, 40, '2026-06-01');

        $this->get(route('crm.bank_accounts.index'))->assertOk()->assertViewHas('canManage', true);
        $this->get(route('crm.bank_accounts.reconcile', $bank->id))->assertOk();
        $this->assertMoney(60, $this->balanceOf($bank));
        $this->assertMoney(40, $this->balanceOf($cash));
    }

    public function test_another_workspaces_account_is_not_reachable(): void
    {
        $other = $this->otherWorkspace();
        $theirs = CrmBankAccount::create(['workspace_id' => $other->id, 'name' => 'Foreign Bank', 'type' => 'bank', 'currency' => 'AED',
            'opening_balance' => 99999, 'opening_date' => '2026-01-01', 'is_active' => true, 'auto_methods' => ['Cash']]);
        $mine = $this->addAccount(['name' => 'Home Bank', 'opening_balance' => 10]);

        $this->get(route('crm.bank_accounts.show', $theirs->id))->assertNotFound();
        $this->get(route('crm.bank_accounts.reconcile', $theirs->id))->assertNotFound();
        $this->put(route('crm.bank_accounts.update', $theirs->id), ['name' => 'Hijacked', 'type' => 'bank', 'currency' => 'AED', 'opening_balance' => 0, 'opening_date' => '2026-01-01'])
            ->assertNotFound();
        $this->delete(route('crm.bank_accounts.destroy', $theirs->id))->assertNotFound();
        $this->assertSame('Foreign Bank', CrmBankAccount::withoutGlobalScopes()->find($theirs->id)->name);

        $balances = $this->ledger()->balances();
        $this->assertSame([$mine->id], array_keys($balances['accounts']));
        $this->assertMoney(10, $balances['total']);
        // Its "Cash" auto-method does not pick up our cash payments.
        $expense = $this->addExpense(['paid_from' => 'Cash', 'amount' => 5]);
        $this->assertNull($this->ledger()->findMovement('expense', $expense->id)->account_id);
    }
}
