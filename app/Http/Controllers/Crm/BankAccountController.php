<?php

namespace App\Http\Controllers\Crm;

use App\CrmBankAccount;
use App\CrmBankTransfer;
use App\Http\Controllers\Controller;
use App\Services\BankLedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Al Massa "Bank & Cash": accounts with opening balances, statements, transfers and assigning movements to accounts. */
class BankAccountController extends Controller
{
    public function index(BankLedgerService $ledger)
    {
        $this->authorizeView();

        return view('crm.bank_accounts.index', [
            'balances' => $ledger->balances(),
            'accounts' => $ledger->accounts(),
            'transfers' => $ledger->transfers()->take(10),
            'types' => CrmBankAccount::TYPES,
            'methodGroups' => CrmBankAccount::METHOD_GROUPS,
            'canManage' => $this->canManage(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage();
        $data = $this->validated($request);
        $account = DB::transaction(function () use ($data) {
            $account = CrmBankAccount::create(array_merge($data, ['created_by' => Auth::guard('crm')->id()]));
            $this->releaseMethods($account);
            return $account;
        });

        return redirect()->route('crm.bank_accounts.index')->with('success', $account->name . ' added with an opening balance of ' . number_format((float) $account->opening_balance, 2) . '.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeManage();
        $account = CrmBankAccount::findOrFail($id);
        $data = $this->validated($request);
        DB::transaction(function () use ($account, $data) {
            $account->update($data);
            $this->releaseMethods($account);
        });

        return back()->with('success', $account->name . ' updated.');
    }

    public function destroy($id, BankLedgerService $ledger)
    {
        $this->authorizeManage();
        $account = CrmBankAccount::findOrFail($id);
        $tagged = $ledger->movements()->filter(fn ($m) => !$m->auto && $m->account_id === $account->id)->count();
        $transfers = CrmBankTransfer::where('from_account_id', $account->id)->orWhere('to_account_id', $account->id)->count();
        if ($tagged || $transfers) {
            return back()->with('error', $account->name . ' has ' . $tagged . ' payment(s) and ' . $transfers . ' transfer(s) booked to it — edit it and untick "Active" instead of deleting.');
        }
        \App\CrmDeletionLog::record('bank_account', $account, $account->name, $account->only(['name', 'type', 'bank_name', 'account_number', 'opening_balance', 'opening_date']));
        $account->delete();

        return redirect()->route('crm.bank_accounts.index')->with('success', $account->name . ' deleted.');
    }

    public function show(Request $request, $id, BankLedgerService $ledger)
    {
        $this->authorizeView();
        $account = CrmBankAccount::findOrFail($id);
        $from = $request->filled('date_from') ? Carbon::parse($request->input('date_from')) : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->input('date_to')) : null;

        return view('crm.bank_accounts.show', [
            'account' => $account,
            'statement' => $ledger->statement($account, $from, $to),
            'accounts' => $ledger->accounts()->where('is_active', true),
            'filters' => ['date_from' => $request->input('date_from'), 'date_to' => $request->input('date_to')],
            'canManage' => $this->canManage(),
            'methodGroups' => CrmBankAccount::METHOD_GROUPS,
        ]);
    }

    /**
     * Bank reconciliation: every entry of the account up to the statement date, with a "cleared" tick for the ones
     * seen on the bank statement. CRM balance − uncleared money in + uncleared money out should equal the statement.
     */
    public function reconcile(Request $request, $id, BankLedgerService $ledger)
    {
        $this->authorizeManage();
        $account = CrmBankAccount::findOrFail($id);
        $last = \App\CrmBankReconciliation::where('bank_account_id', $account->id)->orderByDesc('statement_date')->orderByDesc('id')->first();
        $date = $request->filled('statement_date') ? Carbon::parse($request->input('statement_date')) : now();
        $statement = $ledger->statement($account, null, $date);
        $cleared = \App\CrmBankClearance::where('bank_account_id', $account->id)->get()->keyBy('movement_key');
        $showCleared = $request->boolean('show_cleared');
        // Oldest first, so it reads like the bank statement; cleared ones are hidden unless asked for.
        $rows = collect(array_reverse($statement['rows']))
            ->map(function ($r) use ($cleared) { $r->cleared = $cleared[$r->key] ?? null; return $r; })
            ->filter(fn ($r) => $showCleared || !$r->cleared)->values();

        return view('crm.bank_accounts.reconcile', [
            'account' => $account,
            'date' => $date,
            'rows' => $rows,
            'bookBalance' => (float) $statement['closing'],
            // Cleared entries that are hidden still count as cleared in the totals.
            'hiddenCleared' => $showCleared ? 0 : collect($statement['rows'])->filter(fn ($r) => isset($cleared[$r->key]))->count(),
            'statementBalance' => $request->input('statement_balance', $last ? (float) $last->statement_balance : null),
            'showCleared' => $showCleared,
            'history' => \App\CrmBankReconciliation::with('creator')->where('bank_account_id', $account->id)->orderByDesc('statement_date')->orderByDesc('id')->take(10)->get(),
        ]);
    }

    public function saveReconciliation(Request $request, $id, BankLedgerService $ledger)
    {
        $this->authorizeManage();
        $account = CrmBankAccount::findOrFail($id);
        $data = $request->validate([
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric|min:-999999999|max:999999999',
            'shown' => 'array', 'shown.*' => 'string|max:80',
            'cleared' => 'array', 'cleared.*' => 'string|max:80',
            'note' => 'nullable|string|max:500',
        ]);
        $date = Carbon::parse($data['statement_date']);
        $shown = collect($data['shown'] ?? []);
        $ticked = collect($data['cleared'] ?? [])->intersect($shown);

        DB::transaction(function () use ($account, $date, $shown, $ticked, $data, $ledger) {
            // Entries shown on the page: ticked ones are cleared (keeping their first cleared date), unticked ones are not.
            \App\CrmBankClearance::where('bank_account_id', $account->id)->whereIn('movement_key', $shown->diff($ticked)->all())->delete();
            $existing = \App\CrmBankClearance::where('bank_account_id', $account->id)->whereIn('movement_key', $ticked->all())->pluck('movement_key');
            foreach ($ticked->diff($existing) as $key) {
                \App\CrmBankClearance::create(['bank_account_id' => $account->id, 'movement_key' => $key, 'cleared_date' => $date->toDateString(), 'created_by' => Auth::guard('crm')->id()]);
            }

            $statement = $ledger->statement($account, null, $date);
            $cleared = \App\CrmBankClearance::where('bank_account_id', $account->id)->pluck('movement_key')->flip();
            $pending = collect($statement['rows'])->reject(fn ($r) => $cleared->has($r->key));
            $book = (float) $statement['closing'];
            $unclearedIn = (float) $pending->sum('in');
            $unclearedOut = (float) $pending->sum('out');
            \App\CrmBankReconciliation::create([
                'bank_account_id' => $account->id, 'statement_date' => $date->toDateString(), 'statement_balance' => round((float) $data['statement_balance'], 2),
                'book_balance' => round($book, 2), 'uncleared_in' => round($unclearedIn, 2), 'uncleared_out' => round($unclearedOut, 2),
                'difference' => round((float) $data['statement_balance'] - ($book - $unclearedIn + $unclearedOut), 2),
                'note' => $data['note'] ?? null, 'created_by' => Auth::guard('crm')->id(),
            ]);
        });

        $rec = \App\CrmBankReconciliation::where('bank_account_id', $account->id)->latest('id')->first();

        return redirect()->route('crm.bank_accounts.reconcile', ['id' => $account->id, 'statement_date' => $date->toDateString()])
            ->with(abs((float) $rec->difference) < 0.01 ? 'success' : 'error', abs((float) $rec->difference) < 0.01
                ? $account->name . ' reconciled to ' . $date->format('d M Y') . ' — it matches the bank statement.'
                : $account->name . ' saved, but it is ' . number_format((float) $rec->difference, 2) . ' off the bank statement. Look for entries missing in the CRM or not ticked.');
    }

    /** Movements that no account picked up (no account chosen and no account set for their payment method). */
    public function unassigned(BankLedgerService $ledger)
    {
        $this->authorizeView();

        return view('crm.bank_accounts.unassigned', [
            'movements' => $ledger->movements()->whereNull('account_id')->values(),
            'accounts' => $ledger->accounts()->where('is_active', true),
            'canManage' => $this->canManage(),
        ]);
    }

    /** Book one movement to an account (or back to automatic) by writing bank_account_id on its source record. */
    public function assign(Request $request, BankLedgerService $ledger)
    {
        $this->authorizeManage();
        $data = $request->validate([
            'link_type' => ['required', Rule::in(array_keys(BankLedgerService::LINK_TABLES))],
            'link_id' => 'required|integer',
            'bank_account_id' => 'nullable|integer',
        ]);
        abort_unless($ledger->findMovement($data['link_type'], (int) $data['link_id']), 404);
        $accountId = CrmBankAccount::validIdOrNull($data['bank_account_id'] ?? null);
        DB::table(BankLedgerService::LINK_TABLES[$data['link_type']])->where('id', $data['link_id'])->update(['bank_account_id' => $accountId]);

        return back()->with('success', $accountId ? 'Entry booked to ' . CrmBankAccount::find($accountId)->name . '.' : 'Entry set back to automatic (by payment method).');
    }

    public function storeTransfer(Request $request)
    {
        $this->authorizeManage();
        $data = $request->validate([
            'from_account_id' => 'required|integer|different:to_account_id',
            'to_account_id' => 'required|integer',
            'transfer_date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:500',
        ], ['from_account_id.different' => 'Pick two different accounts.']);
        abort_unless(CrmBankAccount::validIdOrNull($data['from_account_id']) && CrmBankAccount::validIdOrNull($data['to_account_id']), 422, 'Unknown account.');
        $transfer = CrmBankTransfer::create(array_merge($data, ['amount' => round((float) $data['amount'], 2), 'created_by' => Auth::guard('crm')->id()]));
        $transfer->load(['from', 'to']);

        return back()->with('success', number_format((float) $transfer->amount, 2) . ' moved from ' . $transfer->from->name . ' to ' . $transfer->to->name . '.');
    }

    public function destroyTransfer($id)
    {
        $this->authorizeManage();
        $transfer = CrmBankTransfer::with(['from', 'to'])->findOrFail($id);
        \App\CrmDeletionLog::record('bank_transfer', $transfer, optional($transfer->from)->name . ' → ' . optional($transfer->to)->name . ' ' . number_format((float) $transfer->amount, 2),
            $transfer->only(['transfer_date', 'amount', 'reference', 'note']));
        $transfer->delete();

        return back()->with('success', 'Transfer deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(CrmBankAccount::TYPES))],
            'bank_name' => 'nullable|string|max:120',
            'account_number' => 'nullable|string|max:60',
            'currency' => 'required|in:AED,USD,GBP,EUR,PKR',
            'opening_balance' => 'required|numeric|min:-999999999|max:999999999',
            'opening_date' => 'required|date',
            'auto_methods' => 'nullable|array',
            'auto_methods.*' => Rule::in(array_keys(CrmBankAccount::METHOD_GROUPS)),
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);
        $data['auto_methods'] = array_values(array_unique($data['auto_methods'] ?? []));
        $data['is_active'] = $request->boolean('is_active');
        $data['opening_balance'] = round((float) $data['opening_balance'], 2);

        return $data;
    }

    /** A payment method can feed only one account: take this account's methods away from the others. */
    protected function releaseMethods(CrmBankAccount $account): void
    {
        $mine = $account->auto_methods ?: [];
        if (!$mine) return;
        foreach (CrmBankAccount::where('id', '!=', $account->id)->get() as $other) {
            $left = array_values(array_diff($other->auto_methods ?: [], $mine));
            if ($left !== ($other->auto_methods ?: [])) $other->update(['auto_methods' => $left]);
        }
    }

    protected function canManage(): bool
    {
        $user = Auth::guard('crm')->user();

        return $user->isAdmin() || $user->isAccounts();
    }

    protected function authorizeManage(): void
    {
        abort_unless($this->canManage(), 403, 'Only admins and accounts can change bank and cash accounts.');
    }

    protected function authorizeView(): void
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can view bank and cash accounts.');
        }
    }
}
