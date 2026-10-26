<?php

namespace App\Http\Controllers\Crm;

use App\CrmBankAccount;
use App\CrmDirectReceipt;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Al Massa "Make Receipt → Other income": money received without an invoice, booked to an income category. */
class DirectReceiptController extends Controller
{
    public function store(Request $request)
    {
        $this->authorizeAccess();
        $receipt = CrmDirectReceipt::create(array_merge($this->validated($request), $this->storeProof($request),
            CrmBankAccount::fromRequest($request), ['created_by' => Auth::guard('crm')->id()]));

        return redirect()->route('crm.receipts.index')
            ->with('success', 'Receipt ' . $receipt->number . ' — ' . $receipt->category . ' (' . number_format((float) $receipt->amount, 2) . ' ' . $receipt->currency . ') saved.');
    }

    /** Printable receipt voucher. */
    public function show($id)
    {
        $this->authorizeAccess();
        $receipt = CrmDirectReceipt::with(['creator', 'bankAccount'])->findOrFail($id);

        // Account the money went into: the one chosen, else the one its method is auto-booked to.
        $account = $receipt->bankAccount;
        $autoBooked = false;
        if (!$account && ($movement = (new \App\Services\BankLedgerService())->findMovement('direct_receipt', $receipt->id)) && $movement->account_id) {
            $account = CrmBankAccount::find($movement->account_id);
            $autoBooked = true;
        }

        return view('crm.receipts.show', ['receipt' => $receipt, 'account' => $account, 'autoBooked' => $autoBooked, 'canDelete' => $this->canDelete()]
            + ReceiptController::makeReceiptOptions());
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $receipt = CrmDirectReceipt::findOrFail($id);
        $data = $this->validated($request);
        if ($request->hasFile('proof')) {
            if ($receipt->proof_path) Storage::disk('local')->delete($receipt->proof_path);
            $data = array_merge($data, $this->storeProof($request));
        }
        $receipt->update(array_merge($data, CrmBankAccount::fromRequest($request)));

        return back()->with('success', 'Receipt ' . $receipt->number . ' updated.');
    }

    public function destroy($id)
    {
        abort_unless($this->canDelete(), 403, 'Only admins and accounts can delete receipts.');
        $receipt = CrmDirectReceipt::findOrFail($id);
        \App\CrmDeletionLog::record('direct_receipt', $receipt, $receipt->number . ' — ' . $receipt->description, $receipt->only([
            'receipt_date', 'category', 'description', 'payer_type', 'payer_name', 'amount', 'currency', 'method', 'reference', 'note',
        ]));
        if ($receipt->proof_path) Storage::disk('local')->delete($receipt->proof_path);
        $receipt->delete();

        // Deleted from its own voucher page: that page is gone, so go to the list.
        $fromVoucher = url()->previous() === route('crm.direct_receipts.show', $receipt->id);

        return ($fromVoucher ? redirect()->route('crm.receipts.index') : back())->with('success', 'Receipt ' . $receipt->number . ' deleted.');
    }

    public function proof($id)
    {
        $this->authorizeAccess();
        $receipt = CrmDirectReceipt::findOrFail($id);
        abort_unless($receipt->proof_path && Storage::disk('local')->exists($receipt->proof_path), 404);

        return response()->file(Storage::disk('local')->path($receipt->proof_path));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'receipt_date' => 'required|date|before_or_equal:today',
            'category' => 'required|string|max:80',
            'description' => 'required|string|max:255',
            'payer_type' => ['required', Rule::in(array_keys(CrmDirectReceipt::PAYER_TYPES))],
            'payer_name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'currency' => 'required|in:AED,USD,GBP,EUR',
            'method' => ['required', Rule::in(CrmDirectReceipt::METHODS)],
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

        return ['proof_path' => $file->store('receipt-proofs', 'local'), 'proof_name' => $file->getClientOriginalName()];
    }

    protected function canDelete(): bool
    {
        $user = Auth::guard('crm')->user();

        return $user->isAdmin() || $user->isAccounts();
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can record receipts.');
        }
    }
}
