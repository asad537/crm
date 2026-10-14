<?php

namespace App\Http\Controllers\Crm;

use App\CrmCustomer;
use App\CrmIncomeEntry;
use App\Http\Controllers\Controller;
use App\Services\IncomeDueService;
use Illuminate\Http\Request;

/**
 * "Other income" customers (rent, service charges...). Every month a due row is generated;
 * Accounts / Admin record what was received against it. Nothing here touches sales.
 */
class OtherIncomeController extends Controller
{
    private function requireAccounts()
    {
        $u = \Auth::guard('crm')->user();
        if (!$u || (!$u->isAdmin() && !$u->isSuperAdmin() && !$u->isAccounts())) {
            abort(403, 'Only Accounts / Admin can record other income.');
        }
        return $u;
    }

    /** Record (or correct) a payment against one month's due. */
    public function pay(Request $request, $entryId)
    {
        $u = $this->requireAccounts();
        $entry = CrmIncomeEntry::findOrFail($entryId);
        $data = $request->validate([
            'amount_paid' => 'required|numeric|min:0',
            'paid_at' => 'nullable|date',
            'payment_method' => 'nullable|string|max:60',
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:500',
            'proof' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,webp',
        ]);
        if ($request->hasFile('proof')) {
            $data['proof_path'] = $request->file('proof')->store('income-proofs', 'local');
        }
        unset($data['proof']);
        $data['paid_at'] = $data['paid_at'] ?? ((float) $data['amount_paid'] > 0 ? now()->toDateString() : null);
        $data['paid_recorded_by'] = $u->id;
        $entry->update($data);
        return back()->with('success', $entry->customer->name . ' — ' . $entry->period_label . ' ' . ($entry->status === 'paid' ? 'marked paid.' : 'payment recorded (' . number_format($entry->balance, 2) . ' still due).'));
    }

    /** Change one month's due amount (e.g. a one-off discount or increase). */
    public function updateDue(Request $request, $entryId)
    {
        $this->requireAccounts();
        $entry = CrmIncomeEntry::findOrFail($entryId);
        $data = $request->validate(['amount_due' => 'required|numeric|min:0', 'due_date' => 'nullable|date', 'note' => 'nullable|string|max:500']);
        $entry->update($data);
        return back()->with('success', $entry->period_label . ' due updated.');
    }

    /** Add a month that the schedule would not create (e.g. a one-time charge) or regenerate dues. */
    public function addPeriod(Request $request, $customerId)
    {
        $this->requireAccounts();
        $customer = CrmCustomer::findOrFail($customerId);
        $data = $request->validate(['period' => 'required|date_format:Y-m', 'amount_due' => 'required|numeric|min:0', 'income_head' => 'nullable|string|max:80']);
        $day = min(max(1, (int) ($customer->recurring_day ?: 1)), \Carbon\Carbon::createFromFormat('Y-m', $data['period'])->daysInMonth);
        $entry = CrmIncomeEntry::firstOrCreate(['customer_id' => $customer->id, 'period' => $data['period']], [
            'income_head' => ($data['income_head'] ?? null) ?: ($customer->income_head ?: 'Rent'),
            'due_date' => \Carbon\Carbon::createFromFormat('Y-m', $data['period'])->day($day)->toDateString(),
            'amount_due' => $data['amount_due'],
            'created_by' => \Auth::guard('crm')->id(),
        ]);
        return back()->with('success', $entry->wasRecentlyCreated ? $entry->period_label . ' added.' : $entry->period_label . ' already exists.');
    }

    /** Update the recurring schedule of an income customer. */
    public function updateSchedule(Request $request, $customerId)
    {
        $this->requireAccounts();
        $customer = CrmCustomer::findOrFail($customerId);
        $data = $request->validate([
            'income_head' => 'required|string|max:80', 'recurring_amount' => 'required|numeric|min:0',
            'recurring_day' => 'required|integer|min:1|max:31', 'recurring_start' => 'required|date_format:Y-m', 'recurring_active' => 'nullable|boolean',
        ]);
        $data['recurring_start'] = $data['recurring_start'] . '-01';
        $data['recurring_active'] = (bool) ($data['recurring_active'] ?? false);
        $customer->update($data);
        app(IncomeDueService::class)->ensureDues($customer);
        return back()->with('success', 'Schedule updated.');
    }

    public function proof($entryId)
    {
        $u = \Auth::guard('crm')->user();
        if (!$u || (!$u->isAdmin() && !$u->isSuperAdmin() && !$u->isAccounts() && !$u->isSalesManager())) abort(403);
        $entry = CrmIncomeEntry::findOrFail($entryId);
        abort_unless($entry->proof_path && \Storage::disk('local')->exists($entry->proof_path), 404);
        return response()->file(\Storage::disk('local')->path($entry->proof_path));
    }
}
