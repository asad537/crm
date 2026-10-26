<?php

namespace App\Http\Controllers\Crm;

use App\CrmExpense;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Al Massa expense payments: saved from "Make Payment" on the Payments page, against an expense category. */
class ExpenseController extends Controller
{
    /** The Expenses tab was folded into Payments ("Make Payment"); old links land on the expense payments there. */
    public function index()
    {
        $this->authorizeAccess();

        return redirect()->route('crm.payments.index', ['source' => 'expense']);
    }

    /** Payment voucher for one expense payment (printable; edit / delete from here too). */
    public function show($id)
    {
        $this->authorizeAccess();
        $expense = CrmExpense::with(['creator', 'bankAccount'])->findOrFail($id);
        $user = Auth::guard('crm')->user();

        // Account the money left from: the one chosen on the form, else the one its payment method is auto-booked to.
        $account = $expense->bankAccount;
        $autoBooked = false;
        if (!$account && ($movement = (new \App\Services\BankLedgerService())->findMovement('expense', $expense->id)) && $movement->account_id) {
            $account = \App\CrmBankAccount::find($movement->account_id);
            $autoBooked = true;
        }

        return view('crm.payments.show', [
            'expense' => $expense,
            'account' => $account,
            'autoBooked' => $autoBooked,
            'canDelete' => $user->isAdmin() || $user->isAccounts(),
        ] + PaymentController::makePaymentOptions());
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $data = $this->validated($request);
        $data['created_by'] = Auth::guard('crm')->id();
        $data = array_merge($data, $this->storeProof($request), \App\CrmBankAccount::fromRequest($request));
        $expense = CrmExpense::create($data);

        return redirect()->route('crm.payments.index')
            ->with('success', 'Payment ' . $expense->number . ' — ' . $expense->category . ' · ' . $expense->name . ' (' . number_format((float) $expense->amount, 2) . ' ' . $expense->currency . ') saved.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $expense = CrmExpense::findOrFail($id);
        $data = $this->validated($request);
        if ($request->hasFile('proof')) {
            $this->deleteProof($expense);
            $data = array_merge($data, $this->storeProof($request));
        }
        $expense->update(array_merge($data, \App\CrmBankAccount::fromRequest($request)));

        return back()->with('success', 'Payment ' . $expense->number . ' updated.');
    }

    public function destroy($id)
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAdmin() && !$user->isAccounts()) {
            abort(403, 'Only admins and accounts can delete payments.');
        }
        $expense = CrmExpense::findOrFail($id);
        \App\CrmDeletionLog::record('expense', $expense, $expense->number . ' — ' . $expense->name, $expense->only([
            'expense_date', 'category', 'name', 'paid_from', 'payee_type', 'payee_name', 'amount', 'currency', 'reference', 'note',
        ]));
        $this->deleteProof($expense);
        $expense->delete();

        // Deleted from its own voucher page: that page is gone, so go to the list.
        $fromVoucher = url()->previous() === route('crm.expenses.show', $expense->id);

        return ($fromVoucher ? redirect()->route('crm.payments.index') : back())->with('success', 'Payment ' . $expense->number . ' deleted.');
    }

    public function proof($id)
    {
        $this->authorizeAccess();
        $expense = CrmExpense::findOrFail($id);
        abort_unless($expense->proof_path && Storage::disk('local')->exists($expense->proof_path), 404);

        return response()->file(Storage::disk('local')->path($expense->proof_path));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'expense_date' => 'required|date|before_or_equal:today',
            'category' => 'required|string|max:80',
            'name' => 'required|string|max:255',
            'paid_from' => ['required', Rule::in(CrmExpense::PAID_FROM)],
            'payee_type' => ['required', Rule::in(array_keys(CrmExpense::PAYEE_TYPES))],
            'payee_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'currency' => 'required|in:AED,USD,GBP,EUR',
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:500',
            'proof' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,webp,pdf',
        ]);
        unset($data['proof']);
        $data['category'] = trim($data['category']);
        $data['amount'] = round((float) $data['amount'], 2);

        return $data;
    }

    protected function storeProof(Request $request): array
    {
        if (!$request->hasFile('proof')) return [];
        $file = $request->file('proof');

        return ['proof_path' => $file->store('expense-proofs', 'local'), 'proof_name' => $file->getClientOriginalName()];
    }

    protected function deleteProof(CrmExpense $expense): void
    {
        if ($expense->proof_path) Storage::disk('local')->delete($expense->proof_path);
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can make payments.');
        }
    }
}
