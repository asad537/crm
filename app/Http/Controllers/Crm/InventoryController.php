<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\CrmInventoryItem;
use App\CrmInventoryMovement;
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
        $items = $items->orderBy('name')->paginate(30)->appends($request->all());

        // Summary tiles
        $all = CrmInventoryItem::all();
        $summary = [
            'items'     => $all->count(),
            'in_stock'  => (float) $all->sum('quantity'),
            'low'       => $all->filter(function ($i) { return $i->is_low; })->count(),
            'value'     => (float) $all->sum(function ($i) { return (float) $i->quantity * (float) ($i->unit_cost ?? 0); }),
        ];

        // For the "consume against job" dropdown.
        $jobs = \App\DesignJob::orderBy('id', 'desc')->limit(200)->get(['id', 'job_number']);
        $vendors = class_exists(\App\Vendor::class) ? \App\Vendor::orderBy('name')->get(['id', 'name']) : collect();

        return view('crm.inventory.index', compact('items', 'summary', 'jobs', 'vendors'));
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
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);
        $this->applyMovement($item, 'in', (float) $data['quantity'], [
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
            'job_id' => $data['job_id'] ?? null,
            'job_number' => $jobNumber,
            'reference' => $data['reference'] ?? ('Used on '.($jobNumber ?: 'job')),
            'note' => $data['note'] ?? null,
        ], $user);
        return back()->with('success', number_format($data['quantity']).' '.$item->unit.' consumed'.($jobNumber ? ' for '.$jobNumber : '').'.');
    }

    /** Manual balance correction. */
    public function adjust(Request $request, $id)
    {
        $user = $this->user();
        if (!$user) abort(403);
        $item = CrmInventoryItem::findOrFail($id);
        $data = $request->validate([
            'new_quantity' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);
        $diff = (float) $data['new_quantity'] - (float) $item->quantity;
        if ($diff == 0) return back()->with('error', 'Quantity unchanged.');
        $this->applyMovement($item, 'adjust', abs($diff), [
            'reference' => $diff > 0 ? 'Adjustment (+)' : 'Adjustment (−)',
            'note' => $data['note'] ?? null,
            '_signed' => $diff, // adjust direction
        ], $user);
        return back()->with('success', 'Stock adjusted to '.rtrim(rtrim(number_format($data['new_quantity'], 3, '.', ''), '0'), '.').' '.$item->unit.'.');
    }

    public function movements($id)
    {
        $item = CrmInventoryItem::findOrFail($id);
        $movements = $item->movements()->with('creator')->paginate(50);
        return view('crm.inventory.movements', compact('item', 'movements'));
    }

    public function destroy($id)
    {
        $user = $this->user();
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin() && !$user->isAccounts())) abort(403);
        CrmInventoryItem::findOrFail($id)->delete();
        return back()->with('success', 'Inventory item deleted.');
    }

    /** Apply a stock movement and update the running balance atomically. */
    private function applyMovement(CrmInventoryItem $item, $type, $qty, array $extra, $user)
    {
        DB::transaction(function () use ($item, $type, $qty, $extra, $user) {
            $locked = CrmInventoryItem::withoutGlobalScopes()->where('id', $item->id)->lockForUpdate()->first();
            $signed = $extra['_signed'] ?? ($type === 'out' ? -$qty : $qty);
            $balance = (float) $locked->quantity + $signed;
            $locked->quantity = $balance;
            $locked->save();
            CrmInventoryMovement::create([
                'workspace_id' => $locked->workspace_id,
                'inventory_item_id' => $locked->id,
                'type' => $type,
                'quantity' => $qty,
                'balance_after' => $balance,
                'job_id' => $extra['job_id'] ?? null,
                'job_number' => $extra['job_number'] ?? null,
                'vendor_purchase_id' => $extra['vendor_purchase_id'] ?? null,
                'unit_cost' => $extra['unit_cost'] ?? null,
                'reference' => $extra['reference'] ?? null,
                'note' => $extra['note'] ?? null,
                'created_by' => $user->id,
            ]);
        });
    }
}
