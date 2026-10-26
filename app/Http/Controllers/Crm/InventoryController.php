<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\CrmInventoryItem;
use App\CrmInventoryMovement;
use App\Services\InventoryAccounting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    private function user()
    {
        return \Auth::guard('crm')->user();
    }

    public function index(Request $request)
    {
        $user = $this->user();
        if (!$user) abort(403);

        $items = CrmInventoryItem::query();
        if ($request->filled('search')) {
            $s = trim($request->search);
            $items->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('paper_size', 'like', "%{$s}%")
                  ->orWhere('stock_type', 'like', "%{$s}%")
                  ->orWhere('gsm', 'like', "%{$s}%");
            });
        }
        if ($request->filled('category')) $items->where('category', $request->category);
        if ($request->input('status') === 'low') $items->where('reorder_level', '>', 0)->whereColumn('quantity', '<=', 'reorder_level');
        if ($request->input('status') === 'out') $items->where('quantity', '<=', 0);
        if ($request->input('status') === 'in') $items->where('quantity', '>', 0);
        $sortable = ['name', 'paper_size', 'gsm', 'stock_type', 'quantity', 'reorder_level', 'unit_cost'];
        $sort = in_array($request->input('sort'), $sortable, true) ? $request->input('sort') : 'name';
        $dir = $request->input('dir') === 'desc' ? 'desc' : 'asc';
        $perPage = in_array((int) $request->input('per_page'), [10, 25, 50, 100], true) ? (int) $request->input('per_page') : 10;
        $items = $items->orderBy($sort, $dir)->orderBy('name')->paginate($perPage)->appends($request->all());

        // Summary tiles
        $all = CrmInventoryItem::all();
        $summary = [
            'items'     => $all->count(),
            'in_stock'  => (float) $all->sum('quantity'),
            'low'       => $all->filter(function ($i) { return $i->is_low; })->count(),
            'value'     => (float) $all->sum(function ($i) { return (float) $i->quantity * (float) ($i->unit_cost ?? 0); }),
        ];

        // For the "consume against job" dropdown.
        // Jobs of THIS workspace, newest first, with a readable label (number · product · client).
        $jobs = \App\DesignJob::where('workspace_id', \App\Support\CrmWorkspaceContext::id())
            ->orderBy('id', 'desc')->limit(300)->get(['id', 'job_number', 'title', 'client_name', 'status'])
            ->map(function ($j) {
                $j->label = trim(($j->job_number ?: '#' . $j->id) . ($j->title ? ' · ' . $j->title : '') . ($j->client_name ? ' · ' . $j->client_name : ''));
                return $j;
            });
        $vendors = class_exists(\App\Vendor::class) ? \App\Vendor::orderBy('name')->get(['id', 'name']) : collect();

        $categories = CrmInventoryItem::whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category')
            ->concat(['Paper', 'Board', 'Ink', 'Finishing Material', 'Other'])->unique()->values();

        // Stock value as it stands in the books (Balance Sheet → Inventory).
        $summary['value'] = (new InventoryAccounting())->valueAsOf(now()->endOfDay());
        $stockFrom = InventoryAccounting::from();
        $lossReasons = InventoryAccounting::LOSS_REASONS;
        $stockInSources = InventoryAccounting::STOCK_IN_SOURCES;

        return view('crm.inventory.index', compact('items', 'summary', 'jobs', 'vendors', 'categories', 'sort', 'dir', 'perPage', 'stockFrom', 'lossReasons', 'stockInSources'));
    }

    /** Edit an item's master data (name, size, GSM, unit, reorder level, cost). Quantity changes only through movements. */
    public function update(Request $request, $id)
    {
        $user = $this->user();
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin() && !$user->isAccounts())) abort(403);
        $item = CrmInventoryItem::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:60',
            'paper_size' => 'nullable|string|max:60',
            'gsm' => 'nullable|string|max:30',
            'stock_type' => 'nullable|string|max:120',
            'unit' => 'required|string|max:30',
            'reorder_level' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'notes' => 'nullable|string|max:2000',
        ]);
        // While there is stock its cost is the average of what was paid (it is in the books at that value).
        if ((float) $item->quantity > 0.0005) unset($data['unit_cost']);
        $item->update($data);
        return back()->with('success', $item->name.' updated.');
    }

    /** Create a new stock item (with an opening "in" movement). */
    public function store(Request $request)
    {
        $user = $this->user();
        if (!$user) abort(403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:60',
            'paper_size' => 'nullable|string|max:60',
            'gsm' => 'nullable|string|max:30',
            'stock_type' => 'nullable|string|max:120',
            'unit' => 'required|string|max:30',
            'quantity' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'vendor_purchase_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($data, $user, $request) {
            $item = CrmInventoryItem::create([
                'name' => $data['name'], 'category' => $data['category'] ?? 'Paper',
                'paper_size' => $data['paper_size'] ?? null, 'gsm' => $data['gsm'] ?? null,
                'stock_type' => $data['stock_type'] ?? null, 'unit' => $data['unit'],
                'quantity' => 0, 'reorder_level' => $data['reorder_level'] ?? 0,
                'unit_cost' => $data['unit_cost'] ?? null, 'currency' => $data['currency'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            if ((float) $data['quantity'] > 0) {
                $this->applyMovement($item, 'in', (float) $data['quantity'], [
                    'unit_cost' => $data['unit_cost'] ?? null,
                    'vendor_purchase_id' => $request->input('vendor_purchase_id'),
                    'reason' => 'opening',
                    'reference' => 'Opening stock',
                    'note' => 'Item created with opening stock.',
                ], $user);
            }
        });

        return redirect()->route('crm.inventory.index')->with('success', 'Inventory item added.');
    }

    /** Add more stock to an existing item (a purchase / stock-in). */
    public function stockIn(Request $request, $id)
    {
        $user = $this->user();
        if (!$user) abort(403);
        $item = CrmInventoryItem::findOrFail($id);
        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'unit_cost' => 'nullable|numeric|min:0',
            'vendor_purchase_id' => 'nullable|integer',
            'reason' => 'nullable|in:' . implode(',', array_keys(InventoryAccounting::STOCK_IN_SOURCES)),
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);
        $this->applyMovement($item, 'in', (float) $data['quantity'], [
            'reason' => $data['reason'] ?? 'expensed',
            'unit_cost' => $data['unit_cost'] ?? $item->unit_cost,
            'vendor_purchase_id' => $data['vendor_purchase_id'] ?? null,
            'reference' => $data['reference'] ?? 'Stock in',
            'note' => $data['note'] ?? null,
        ], $user);
        return back()->with('success', number_format($data['quantity']).' '.$item->unit.' added to '.$item->name.'.');
    }

    /** Consume stock against a job (stock-out). */
    public function consume(Request $request, $id)
    {
        $user = $this->user();
        if (!$user) abort(403);
        $item = CrmInventoryItem::findOrFail($id);
        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'job_id' => 'nullable|integer',
            'job_number' => 'nullable|string|max:120',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);
        if ((float) $data['quantity'] > (float) $item->quantity) {
            return back()->with('error', 'Not enough stock: only '.rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.').' '.$item->unit.' available.');
        }
        $jobNumber = $data['job_number'] ?? null;
        if (!$jobNumber && !empty($data['job_id'])) {
            $jobNumber = optional(\App\DesignJob::find($data['job_id']))->job_number;
        }
        $this->applyMovement($item, 'out', (float) $data['quantity'], [
            'reason' => 'job',
            'job_id' => $data['job_id'] ?? null,
            'job_number' => $jobNumber,
            'reference' => $data['reference'] ?? ('Used on '.($jobNumber ?: 'job')),
            'note' => $data['note'] ?? null,
        ], $user);
        return back()->with('success', number_format($data['quantity']).' '.$item->unit.' consumed'.($jobNumber ? ' for '.$jobNumber : '').'.');
    }

    /**
     * Stock lost or corrected: wastage / damaged / expired take a quantity out; a stock count sets the counted quantity.
     * In the books both go to "Stock Loss & Wastage" at average cost (a count that finds more stock is a gain).
     */
    public function adjust(Request $request, $id)
    {
        $user = $this->user();
        if (!$user) abort(403);
        $item = CrmInventoryItem::findOrFail($id);
        $data = $request->validate([
            'reason' => 'required|in:' . implode(',', array_keys(InventoryAccounting::LOSS_REASONS)),
            'quantity_lost' => 'required_unless:reason,count|nullable|numeric|min:0.001',
            'new_quantity' => 'required_if:reason,count|nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ], ['quantity_lost.required_unless' => 'Enter the quantity lost.', 'new_quantity.required_if' => 'Enter the quantity counted.']);
        $n = fn ($v) => rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');
        $diff = $data['reason'] === 'count' ? (float) $data['new_quantity'] - (float) $item->quantity : -(float) $data['quantity_lost'];
        if (abs($diff) < 0.0005) return back()->with('error', 'Quantity unchanged.');
        if ((float) $item->quantity + $diff < -0.0005) {
            return back()->with('error', 'Only ' . $n((float) $item->quantity) . ' ' . $item->unit . ' in stock.');
        }
        $label = InventoryAccounting::LOSS_REASONS[$data['reason']];
        $this->applyMovement($item, 'adjust', abs($diff), [
            'reason' => $data['reason'],
            'reference' => $label . ($diff > 0 ? ' (+)' : ' (−)'),
            'note' => $data['note'] ?? null,
            '_signed' => $diff, // adjust direction
        ], $user);
        // applyMovement() has already put the new balance on $item.
        return back()->with('success', $label . ': ' . ($diff > 0 ? '+' : '−') . $n(abs($diff)) . ' ' . $item->unit . ' — ' . $item->name . ' now ' . $n((float) $item->quantity) . ' ' . $item->unit . '.');
    }

    public function movements(Request $request, $id)
    {
        $item = CrmInventoryItem::findOrFail($id);
        $search = trim((string) $request->input('q'));
        $query = $item->movements()->with('creator');
        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            // Also find rows by what the page shows: "Stock In" / "Used" / "Adjust" and the date ("23 Oct").
            $types = collect(['in' => 'Stock In', 'out' => 'Used', 'adjust' => 'Adjust'])->filter(fn ($label) => stripos($label, $search) !== false)->keys();
            $query->where(function ($w) use ($like, $types) {
                $w->where('reference', 'like', $like)->orWhere('note', 'like', $like)->orWhere('job_number', 'like', $like)
                    ->orWhere('reason', 'like', $like)->orWhereIn('type', $types->all())
                    ->orWhereRaw("DATE_FORMAT(created_at, '%d %b %Y') LIKE ?", [$like])
                    ->orWhereHas('creator', fn ($u) => $u->where('name', 'like', $like));
            });
        }
        $movements = $query->paginate(50)->withQueryString();
        $books = $movements->getCollection()->mapWithKeys(fn ($m) => [$m->id => InventoryAccounting::booksLabel($m)]);
        $value = (new InventoryAccounting())->itemsAsOf(now()->endOfDay())->get($item->id);
        $stockFrom = InventoryAccounting::from();

        // Whole-history totals: what came in, what went to jobs (per job), what was lost or corrected; and each row's
        // change in stock (adjustments only store the new balance), worked out on the full history so a search keeps them right.
        $totals = ['in' => 0.0, 'used' => 0.0, 'loss' => 0.0];
        $jobs = [];
        $deltas = [];
        $balance = 0.0;
        $history = CrmInventoryMovement::where('inventory_item_id', $item->id)->orderBy('id')->get();
        $reverted = $history->pluck('reversal_of_id')->filter()->flip();
        foreach ($history as $m) {
            $delta = (float) $m->balance_after - $balance;
            $balance = (float) $m->balance_after;
            $deltas[$m->id] = $delta;
            $reason = InventoryAccounting::reasonOf($m);
            if ($reason === 'job') {
                $totals['used'] -= $delta;
                $job = trim((string) $m->job_number) ?: 'Job #' . $m->job_id;
                $jobs[$job] ??= ['qty' => 0.0, 'cost' => 0.0, 'date' => $m->created_at];
                $jobs[$job]['qty'] -= $delta;
                $jobs[$job]['cost'] -= $delta * (float) $m->unit_cost;
                $jobs[$job]['date'] = $m->created_at;
            } elseif (in_array($reason, ['bill', 'opening', 'expensed'], true)) {
                $totals['in'] += $delta;
            } else {
                $totals['loss'] -= $delta;
            }
        }
        $jobs = collect($jobs)->filter(fn ($j) => abs($j['qty']) > 0.0005)->sortByDesc(fn ($j) => $j['date']->timestamp);
        $bills = \App\CrmPurchaseInvoiceItem::where('inventory_item_id', $item->id)->whereHas('invoice')->with('invoice')->get()
            ->sortByDesc(fn ($line) => $line->invoice->issue_date->timestamp)->values();

        $canRevert = $this->canManage();
        $revertProblems = $movements->getCollection()->mapWithKeys(fn ($m) => [$m->id => InventoryAccounting::revertProblem($m, $reverted->has($m->id))]);

        return view('crm.inventory.movements', compact('item', 'movements', 'deltas', 'books', 'value', 'stockFrom', 'totals', 'jobs', 'bills',
            'search', 'canRevert', 'revertProblems', 'reverted'));
    }

    /**
     * Undo one stock entry from the stock history. Nothing is deleted: a reversing entry puts the quantity back the
     * other way at the entry's own price, with the same meaning for the books (a reverted wastage takes the loss back
     * out of Stock Loss, a reverted job use takes it out of that job's materials...).
     */
    public function revert($id, $movementId)
    {
        abort_unless($this->canManage(), 403, 'Only admins and accounts can revert stock entries.');
        $item = CrmInventoryItem::findOrFail($id);
        $m = $item->movements()->whereKey($movementId)->firstOrFail();
        $alreadyReverted = CrmInventoryMovement::where('reversal_of_id', $m->id)->exists();
        if ($problem = InventoryAccounting::revertProblem($m, $alreadyReverted)) {
            return back()->with('error', 'Cannot revert: ' . $problem);
        }

        $previous = (float) CrmInventoryMovement::where('inventory_item_id', $item->id)->where('id', '<', $m->id)->orderByDesc('id')->value('balance_after');
        $undo = round($previous - (float) $m->balance_after, 3);   // the opposite of what the entry changed
        $n = fn ($v) => rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');
        if (abs($undo) < 0.0005) return back()->with('error', 'Cannot revert: this entry did not change the stock.');
        if ((float) $item->quantity + $undo < -0.0005) {
            return back()->with('error', 'Cannot revert: only ' . $n((float) $item->quantity) . ' ' . $item->unit . ' left, and this would take out ' . $n(abs($undo)) . '.');
        }

        $type = $m->type === 'adjust' ? 'adjust' : ($undo > 0 ? 'in' : 'out');
        // An entry from before stock was in the books is already inside the opening stock: undoing it corrects the
        // opening stock (Inventory and equity), not the Profit & Loss.
        $booked = InventoryAccounting::isBooked($m);
        $demandLine = InventoryAccounting::demandLineFor($m);
        DB::transaction(function () use ($item, $m, $type, $undo, $booked, $demandLine) {
            $this->applyMovement($item, $type, abs($undo), [
                'reason' => $booked ? InventoryAccounting::reasonOf($m) : 'opening',
                'reversal_of_id' => $m->id,
                'unit_cost' => $m->unit_cost,
                '_revalue' => $type === 'out',          // stock taken back out at the entry's own price
                '_signed' => $undo,
                'job_id' => $m->job_id,
                'job_number' => $m->job_number,
                'reference' => 'Reverted: ' . ($m->reference ?: 'entry of ' . $m->created_at->format('d M Y')),
                'note' => 'Reversal of the entry of ' . $m->created_at->format('d M Y, h:i A') . ($booked ? '' : ' (opening stock corrected)'),
            ], $this->user());
            // The demand line no longer holds this stock: undoing "received" there won't take it out again,
            // and it can be stocked in again from the demand request.
            if ($demandLine) {
                $extra = (array) $demandLine->extra;
                unset($extra['inventory_item_id'], $extra['inventory_movement_id'], $extra['inventory_qty']);
                $demandLine->forceFill(['extra' => $extra ?: null])->saveQuietly();
            }
        });

        return back()->with('success', 'Entry reverted: ' . ($undo > 0 ? '+' : '−') . $n(abs($undo)) . ' ' . $item->unit . ' — ' . $item->name . ' now ' . $n((float) $item->quantity) . ' ' . $item->unit
            . ($booked ? '.' : ' (opening stock corrected).'));
    }

    private function canManage(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isSuperAdmin() || $user->isAccounts());
    }

    public function destroy($id)
    {
        $user = $this->user();
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin() && !$user->isAccounts())) abort(403);
        $item = CrmInventoryItem::findOrFail($id);
        // Stock that is in the books (bills, jobs, wastage) keeps its history: run it down to zero instead.
        if ((float) $item->quantity > 0.0005 || $item->movements()->where('id', '>', InventoryAccounting::config()['after_id'])->exists()) {
            return back()->with('error', $item->name . ' has stock entries in the books, so it cannot be deleted. Bring its stock to 0 with Adjust if it is no longer used.');
        }
        $item->delete();
        return back()->with('success', 'Inventory item deleted.');
    }

    /** Apply a stock movement and update the running balance atomically. */
    private function applyMovement(CrmInventoryItem $item, $type, $qty, array $extra, $user)
    {
        // One ledger implementation for the whole app (demand receipts, job cards, manual in/out):
        // running balance + weighted-average unit cost live in InventoryService.
        app(\App\Services\InventoryService::class)->move($item, $type, (float) $qty, $extra, $user->id);
    }
}
