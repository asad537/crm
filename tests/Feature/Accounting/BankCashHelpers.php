<?php

namespace Tests\Feature\Accounting;

use App\CrmBankAccount;
use App\CrmCapitalEntry;
use App\CrmCapitalOwner;
use App\CrmExpense;
use App\CrmWorkspace;
use App\Services\BankLedgerService;
use App\Services\FinancialReportService;
use App\Support\CrmWorkspaceContext;
use Carbon\Carbon;

/**
 * Shared steps for the Bank & Cash, Owner's Capital and Expense tests: each one goes through the same HTTP route a
 * user would (the forms on Bank & Cash, Owner's Capital and "Make Payment"), then hands back the saved record.
 */
trait BankCashHelpers
{
    /** Add a bank / cash account through the Bank & Cash form. */
    protected function addAccount(array $data = []): CrmBankAccount
    {
        $data = array_merge([
            'name' => 'Bank ' . uniqid(), 'type' => 'bank', 'currency' => 'AED',
            'opening_balance' => 0, 'opening_date' => '2026-01-01', 'is_active' => 1,
        ], $data);
        $this->post(route('crm.bank_accounts.store'), $data)
            ->assertSessionHasNoErrors()->assertRedirect(route('crm.bank_accounts.index'));

        return CrmBankAccount::where('name', $data['name'])->latest('id')->firstOrFail();
    }

    /** Add an owner / partner through the Owner's Capital form. */
    protected function addOwner(string $name, array $data = []): CrmCapitalOwner
    {
        $this->post(route('crm.owner_capital.owners.store'), array_merge(['name' => $name], $data))->assertSessionHasNoErrors();

        return CrmCapitalOwner::where('name', trim($name))->latest('id')->firstOrFail();
    }

    /** Record capital put in (or a drawing taken out) for an owner, through the Owner's Capital form. */
    protected function addCapital(CrmCapitalOwner $owner, float $amount, string $date, ?CrmBankAccount $account = null,
                                  string $type = 'capital', string $method = 'Bank Transfer', array $extra = []): CrmCapitalEntry
    {
        $data = array_merge([
            'owner_id' => $owner->id, 'entry_date' => $date, 'type' => $type, 'amount' => $amount, 'method' => $method,
            'bank_account_id' => $account ? $account->id : '',
        ], $extra);
        $this->post(route('crm.owner_capital.entries.store'), $data)
            ->assertSessionHasNoErrors()->assertRedirect(route('crm.owner_capital.index', ['owner' => $owner->id]));

        return CrmCapitalEntry::latest('id')->firstOrFail();
    }

    /** Record an expense payment through "Make Payment" (crm.expenses.store). */
    protected function addExpense(array $data = []): CrmExpense
    {
        $data = array_merge([
            'expense_date' => '2026-03-01', 'category' => 'Rent', 'name' => 'Expense ' . uniqid(), 'paid_from' => 'Bank Transfer',
            'payee_type' => 'other', 'amount' => 100, 'currency' => 'AED',
        ], $data);
        if (($data['bank_account_id'] ?? null) instanceof CrmBankAccount) $data['bank_account_id'] = $data['bank_account_id']->id;
        $this->post(route('crm.expenses.store'), $data)
            ->assertSessionHasNoErrors()->assertRedirect(route('crm.payments.index'));

        return CrmExpense::latest('id')->firstOrFail();
    }

    /** Move money between two accounts through the Bank & Cash transfer form. */
    protected function addTransfer(CrmBankAccount $from, CrmBankAccount $to, float $amount, string $date, array $extra = []): \App\CrmBankTransfer
    {
        $this->post(route('crm.bank_accounts.transfers.store'), array_merge([
            'from_account_id' => $from->id, 'to_account_id' => $to->id, 'transfer_date' => $date, 'amount' => $amount,
        ], $extra))->assertSessionHasNoErrors()->assertSessionHas('success');

        return \App\CrmBankTransfer::latest('id')->firstOrFail();
    }

    /** A fresh ledger for the test workspace (the service memoises, so a new one sees the latest rows). */
    protected function ledger(): BankLedgerService
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return new BankLedgerService();
    }

    /** One account's balance today, or at the end of $date. */
    protected function balanceOf(CrmBankAccount $account, ?string $date = null): float
    {
        return (float) $this->ledger()->balances($date ? Carbon::parse($date) : null)['accounts'][$account->id]['balance'];
    }

    /** Balance sheet as of the end of $asOf (lines are [label, amount, url, kind]). */
    protected function sheet(string $asOf = '2026-10-26'): array
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return (new FinancialReportService(Carbon::parse('2026-01-01'), Carbon::parse($asOf)))->balanceSheetAsOf(Carbon::parse($asOf));
    }

    /** The "Opening Balances not recorded (balancing figure)" line of the balance sheet. */
    protected function balancingFigure(string $asOf = '2026-10-26'): float
    {
        return (float) collect($this->sheet($asOf)['equity'])->firstWhere(3, 'balancing')[1];
    }

    /** Trial balance "Difference" card (debit − credit) as of $to. */
    protected function trialDifference(string $to = '2026-10-26'): float
    {
        return (float) collect($this->report('trial-balance', '2026-01-01', $to)['cards'])->firstWhere('label', 'Difference')['value'];
    }

    /** Balance-sheet lines of one section keyed by label: [label => amount]. */
    protected function sheetLines(array $sheet, string $section): array
    {
        return collect($sheet[$section])->mapWithKeys(fn ($l) => [$l[0] => (float) $l[1]])->all();
    }

    /** Report card value by label. */
    protected function card(array $report, string $label): float
    {
        $card = collect($report['cards'])->firstWhere('label', $label);
        $this->assertNotNull($card, 'No "' . $label . '" card in ' . $report['key']);

        return (float) $card['value'];
    }

    /** First column of a report table row as plain text (cells can be links: ['text' => ..., 'url' => ...]). */
    protected function rowLabel(array $row): ?string
    {
        $first = isset($row['_style']) ? $row['cells'][0] : $row[0];

        return is_array($first) ? $first['text'] : $first;
    }

    /** The row of a report table whose first column is $label (or null); section headings are skipped. */
    protected function tableRow(array $table, string $label): ?array
    {
        foreach ($table['rows'] as $row) {
            if (($row['_style'] ?? null) === 'section') continue;
            if ($this->rowLabel($row) === $label) return isset($row['_style']) ? $row['cells'] : $row;
        }

        return null;
    }

    /** Category => amount for one section ("Income", "Cost of Sales", "Operating Expenses") of the P&L statement table. */
    protected function plSection(array $report, string $section): array
    {
        $out = [];
        $in = false;
        foreach ($report['tables'][0]['rows'] as $row) {
            $style = $row['_style'] ?? null;
            if ($style === 'section') { $in = $row['cells'][0] === $section; continue; }
            if ($style) { $in = false; continue; }
            if ($in && $this->rowLabel($row) !== '—') $out[$this->rowLabel($row)] = (float) $row[1];
        }

        return $out;
    }

    /** A second, unrelated workspace (for isolation checks). */
    protected function otherWorkspace(): CrmWorkspace
    {
        return CrmWorkspace::create(['name' => 'Other Books', 'slug' => 'other-books-' . uniqid(), 'is_active' => true]);
    }
}
