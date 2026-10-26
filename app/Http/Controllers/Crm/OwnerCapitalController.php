<?php

namespace App\Http\Controllers\Crm;

use App\CrmBankAccount;
use App\CrmCapitalEntry;
use App\CrmCapitalOwner;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Al Massa "Owner's Capital": the owners / partners, the capital each put into the business and the drawings each
 * took out. Cash / bank entries move money in the chosen Bank & Cash account; all of it shows under Equity in the
 * Balance Sheet and the Chart of Accounts. Owners' money is private, so only admins and accounts can open it.
 */
class OwnerCapitalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $owners = CrmCapitalOwner::with('entries')->orderByDesc('is_active')->orderBy('name')->get();
        $ownerId = $request->filled('owner') ? (int) $request->input('owner') : null;
        $selected = $ownerId ? $owners->firstWhere('id', $ownerId) : null;

        $all = CrmCapitalEntry::with(['owner', 'bankAccount', 'creator'])->when($selected, fn ($q) => $q->where('owner_id', $selected->id))
            ->orderBy('entry_date')->orderBy('id')->get();
        // Running balance per owner (capital adds, drawings subtract), shown newest first.
        $running = [];
        foreach ($all as $entry) {
            $running[$entry->owner_id] = ($running[$entry->owner_id] ?? 0) + ($entry->type === 'drawing' ? -1 : 1) * (float) $entry->amount;
            $entry->owner_balance = round($running[$entry->owner_id], 2);
        }
        $entries = $all->reverse()->values();

        return view('crm.owner_capital.index', [
            'owners' => $owners,
            'selected' => $selected,
            'entries' => $entries,
            'totals' => [
                'capital' => (float) $all->where('type', 'capital')->sum('amount'),
                'drawings' => (float) $all->where('type', 'drawing')->sum('amount'),
            ],
            'types' => CrmCapitalEntry::TYPES,
            'methods' => CrmCapitalEntry::METHODS,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function storeOwner(Request $request)
    {
        $this->authorizeAccess();
        $owner = CrmCapitalOwner::create(array_merge($this->ownerData($request), ['created_by' => Auth::guard('crm')->id()]));

        return redirect()->route('crm.owner_capital.index', ['owner' => $owner->id])
            ->with('success', $owner->name . ' added. Now record the capital they put in.');
    }

    public function updateOwner(Request $request, $id)
    {
        $this->authorizeAccess();
        $owner = CrmCapitalOwner::findOrFail($id);
        $owner->update($this->ownerData($request));

        return back()->with('success', $owner->name . ' updated.');
    }

    public function destroyOwner($id)
    {
        abort_unless($this->canDelete(), 403, 'Only admins and accounts can delete owners.');
        $owner = CrmCapitalOwner::withCount('entries')->findOrFail($id);
        if ($owner->entries_count) {
            return back()->with('error', $owner->name . ' has ' . $owner->entries_count . ' capital / drawing entr' . ($owner->entries_count === 1 ? 'y' : 'ies')
                . '. Delete those first, or edit the owner and untick "Active".');
        }
        \App\CrmDeletionLog::record('capital_owner', $owner, $owner->name, $owner->only(['name', 'share_percent', 'notes']));
        $owner->delete();

        return redirect()->route('crm.owner_capital.index')->with('success', $owner->name . ' deleted.');
    }

    public function storeEntry(Request $request)
    {
        $this->authorizeAccess();
        $entry = CrmCapitalEntry::create(array_merge($this->entryData($request), ['created_by' => Auth::guard('crm')->id()]));

        return redirect()->route('crm.owner_capital.index', ['owner' => $entry->owner_id])
            ->with('success', $entry->number . ' — ' . CrmCapitalEntry::TYPES[$entry->type] . ' of ' . number_format((float) $entry->amount, 2)
                . ' for ' . optional($entry->owner)->name . ' saved.');
    }

    public function updateEntry(Request $request, $id)
    {
        $this->authorizeAccess();
        $entry = CrmCapitalEntry::findOrFail($id);
        $entry->update($this->entryData($request));

        return back()->with('success', $entry->number . ' updated.');
    }

    public function destroyEntry($id)
    {
        abort_unless($this->canDelete(), 403, 'Only admins and accounts can delete capital entries.');
        $entry = CrmCapitalEntry::with('owner')->findOrFail($id);
        \App\CrmDeletionLog::record('capital_entry', $entry, $entry->number . ' — ' . optional($entry->owner)->name,
            $entry->only(['entry_date', 'type', 'amount', 'method', 'reference', 'note']));
        $entry->delete();

        return back()->with('success', $entry->number . ' deleted.');
    }

    protected function ownerData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'share_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:500',
        ]);
        $data['name'] = trim($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    protected function entryData(Request $request): array
    {
        $data = $request->validate([
            'owner_id' => ['required', 'integer', Rule::in(CrmCapitalOwner::pluck('id')->all())],
            'entry_date' => 'required|date|before_or_equal:today',
            'type' => ['required', Rule::in(array_keys(CrmCapitalEntry::TYPES))],
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'method' => ['required', Rule::in(CrmCapitalEntry::METHODS)],
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:500',
        ], ['owner_id.in' => 'Pick an owner from the list.']);
        $data['amount'] = round((float) $data['amount'], 2);
        $data = array_merge($data, CrmBankAccount::fromRequest($request));
        // Opening / non-cash capital does not go through any bank or cash account.
        if ($data['method'] === CrmCapitalEntry::NON_CASH) $data['bank_account_id'] = null;

        return $data;
    }

    protected function canDelete(): bool
    {
        $user = Auth::guard('crm')->user();

        return $user->isAdmin() || $user->isAccounts();
    }

    protected function authorizeAccess(): void
    {
        abort_unless($this->canDelete(), 403, "Only admins and accounts can open Owner's Capital.");
    }
}
