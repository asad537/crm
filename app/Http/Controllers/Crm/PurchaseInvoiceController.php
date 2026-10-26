<?php

namespace App\Http\Controllers\Crm;

use App\CrmPurchaseInvoice;
use App\Http\Controllers\Controller;
use App\Services\InventoryAccounting;
use App\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Al Massa "Purchase Invoices": supplier bills with line items; fully paid ones also show under Payments. */
class PurchaseInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
            'status' => array_key_exists($request->input('status'), CrmPurchaseInvoice::STATUSES) ? $request->input('status') : null,
            'vendor' => $request->input('vendor') ?: null,
        ];

        $query = CrmPurchaseInvoice::with(['items', 'payments']);
        if ($filters['date_from']) $query->whereDate('issue_date', '>=', $filters['date_from']);
        if ($filters['date_to']) $query->whereDate('issue_date', '<=', $filters['date_to']);
        if ($filters['vendor']) $query->where('vendor_name', $filters['vendor']);
        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like) {
                $q->where('vendor_name', 'like', $like)->orWhere('reference', 'like', $like)->orWhere('project', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('items', fn ($i) => $i->where('name', 'like', $like));
            });
        }
        $invoices = $query->orderByDesc('issue_date')->orderByDesc('id')->get();
        if ($filters['status']) {
            $invoices = $invoices->filter(fn ($inv) => $inv->status() === $filters['status'])->values();
        }

        $all = CrmPurchaseInvoice::with('payments')->get();
        $totals = [
            'invoiced' => (float) $invoices->sum('total'),
            'count' => $invoices->count(),
            'paid' => (float) $invoices->sum(fn ($inv) => $inv->paidTotal()),
            'balance' => (float) $invoices->sum(fn ($inv) => max(0, $inv->balanceDue())),
            'overdue' => (float) $all->filter(fn ($inv) => $inv->status() === 'overdue')->sum(fn ($inv) => $inv->balanceDue()),
            'overdue_count' => $all->filter(fn ($inv) => $inv->status() === 'overdue')->count(),
        ];

        $perPage = 25;
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $entries = new \Illuminate\Pagination\LengthAwarePaginator(
            $invoices->forPage($page, $perPage)->values(),
            $invoices->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('crm.purchase_invoices.index', [
            'entries' => $entries,
            'totals' => $totals,
            'filters' => $filters,
            'statuses' => CrmPurchaseInvoice::STATUSES,
            'vendorOptions' => $all->pluck('vendor_name')->unique()->sort()->values(),
        ]);
    }

    public function create()
    {
        $this->authorizeAccess();

        return view('crm.purchase_invoices.form', ['invoice' => new CrmPurchaseInvoice(['issue_date' => now(), 'currency' => 'AED']), 'vendors' => $this->vendors(),
            'categoryOptions' => $this->itemCategories(), 'assetOptions' => $this->assetOptions(), 'stockOptions' => $this->stockOptions(),
            'stockFrom' => InventoryAccounting::from()]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        [$data, $items] = $this->validated($request);

        $invoice = DB::transaction(function () use ($request, $data, $items) {
            $invoice = CrmPurchaseInvoice::create(array_merge($data, $this->storeAttachment($request), [
                'created_by' => Auth::guard('crm')->id(),
            ]));
            $invoice->items()->createMany($items);
            $this->syncAssets($invoice);
            $this->syncStock($invoice, $items);
            return $invoice;
        });

        return redirect()->route('crm.purchase_invoices.show', $invoice->id)
            ->with('success', $invoice->number . ' created for ' . $invoice->vendor_name . ' (' . number_format((float) $invoice->total, 2) . ' ' . $invoice->currency . ').');
    }

    public function show($id)
    {
        $this->authorizeAccess();
        $invoice = CrmPurchaseInvoice::with(['items.fixedAsset', 'items.inventoryItem', 'payments.creator', 'vendor', 'creator'])->findOrFail($id);

        return view('crm.purchase_invoices.show', [
            'invoice' => $invoice,
            'paymentMethods' => CrmPurchaseInvoice::PAYMENT_METHODS,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function edit($id)
    {
        $this->authorizeAccess();

        return view('crm.purchase_invoices.form', ['invoice' => CrmPurchaseInvoice::with('items.fixedAsset')->findOrFail($id), 'vendors' => $this->vendors(),
            'categoryOptions' => $this->itemCategories(), 'assetOptions' => $this->assetOptions((int) $id), 'stockOptions' => $this->stockOptions(),
            'stockFrom' => InventoryAccounting::from()]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $invoice = CrmPurchaseInvoice::findOrFail($id);
        [$data, $items] = $this->validated($request, $invoice);

        DB::transaction(function () use ($request, $invoice, $data, $items) {
            if ($request->hasFile('attachment')) {
                $this->deleteAttachment($invoice);
                $data = array_merge($data, $this->storeAttachment($request));
            }
            $invoice->update($data);
            $invoice->items()->delete();
            $invoice->items()->createMany($items);
            $this->syncAssets($invoice);
            $this->syncStock($invoice, $items);
        });

        return redirect()->route('crm.purchase_invoices.show', $invoice->id)->with('success', $invoice->number . ' updated.');
    }

    public function destroy($id)
    {
        abort_unless($this->canDelete(), 403, 'Only admins and accounts can delete purchase invoices.');
        $invoice = CrmPurchaseInvoice::with(['items', 'payments'])->findOrFail($id);
        if ($problems = InventoryAccounting::billProblems($invoice->id, [])) {
            return back()->with('error', $invoice->number . ' cannot be deleted. ' . implode(' ', $problems));
        }

        \App\CrmDeletionLog::record('purchase_invoice', $invoice, $invoice->number . ' — ' . $invoice->vendor_name, array_merge($invoice->only([
            'vendor_name', 'issue_date', 'due_date', 'reference', 'project', 'description', 'currency', 'total',
        ]), [
            'items' => $invoice->items->map->only(['name', 'quantity', 'unit_price', 'line_total'])->all(),
            'payments' => $invoice->payments->map->only(['amount', 'paid_at', 'method', 'reference'])->all(),
        ]));
        DB::transaction(function () use ($invoice) {
            $invoice->payments()->delete();
            $invoice->items()->delete();
            // Its assets stay in Fixed Assets, back to "not bought yet"; the stock it brought in goes back out.
            $this->releaseAssets(\App\CrmFixedAsset::where('purchase_invoice_id', $invoice->id));
            (new InventoryAccounting())->syncBill($invoice, [], Auth::guard('crm')->id());
            $invoice->delete();
        });
        $this->deleteAttachment($invoice);

        return redirect()->route('crm.purchase_invoices.index')->with('success', $invoice->number . ' deleted.');
    }

    public function addPayment(Request $request, $id)
    {
        $this->authorizeAccess();
        $invoice = CrmPurchaseInvoice::findOrFail($id);
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'paid_at' => 'required|date|before_or_equal:today',
            'method' => ['nullable', Rule::in(CrmPurchaseInvoice::PAYMENT_METHODS)],
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:500',
        ]);
        $invoice->payments()->create(array_merge($data, \App\CrmBankAccount::fromRequest($request), [
            'amount' => round((float) $data['amount'], 2),
            'workspace_id' => $invoice->workspace_id,
            'created_by' => Auth::guard('crm')->id(),
        ]));
        $invoice->unsetRelation('payments');
        $balance = $invoice->balanceDue();
        $result = $balance < -0.009
            ? $invoice->number . ' is fully paid, with ' . number_format(-$balance, 2) . ' extra kept as an advance to the vendor (Balance Sheet: Advances to Vendors).'
            : ($balance <= 0.009 ? $invoice->number . ' is now fully paid.' : 'Balance due: ' . number_format($balance, 2) . '.');

        return back()->with('success', 'Payment of ' . number_format((float) $data['amount'], 2) . ' recorded. ' . $result
            . ' It shows under Payments and in the vendor ledger.');
    }

    public function deletePayment($id, $paymentId)
    {
        abort_unless($this->canDelete(), 403, 'Only admins and accounts can delete payments.');
        $invoice = CrmPurchaseInvoice::findOrFail($id);
        $payment = $invoice->payments()->whereKey($paymentId)->firstOrFail();
        \App\CrmDeletionLog::record('purchase_invoice_payment', $payment, $invoice->number . ' payment ' . number_format((float) $payment->amount, 2),
            $payment->only(['amount', 'paid_at', 'method', 'reference', 'note']));
        $payment->delete();

        return back()->with('success', 'Payment removed from ' . $invoice->number . '.');
    }

    public function attachment($id)
    {
        $this->authorizeAccess();
        $invoice = CrmPurchaseInvoice::findOrFail($id);
        abort_unless($invoice->attachment_path && Storage::disk('local')->exists($invoice->attachment_path), 404);

        return response()->file(Storage::disk('local')->path($invoice->attachment_path));
    }

    /**
     * @param  CrmPurchaseInvoice|null  $invoice  the invoice being edited (its own assets may stay on it)
     * @return array{0: array, 1: array} invoice fields and its normalized line items
     */
    protected function validated(Request $request, ?CrmPurchaseInvoice $invoice = null): array
    {
        $data = $request->validate([
            'vendor_id' => 'required|integer',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'reference' => 'nullable|string|max:120',
            'project' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:2000',
            'currency' => 'required|in:AED,USD,GBP,EUR',
            'items' => 'required|array|min:1|max:100',
            'items.*.fixed_asset_id' => 'nullable|integer',
            'items.*.inventory_item_id' => 'nullable|integer',
            'items.*.name' => 'required|string|max:255',
            'items.*.category' => 'required|string|max:80',
            'items.*.quantity' => 'required|numeric|min:0.001|max:99999999',
            'items.*.unit_price' => 'required|numeric|min:0|max:999999999',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,webp,pdf',
        ], [
            'items.required' => 'Add at least one item.',
            'items.*.name.required' => 'Every item needs a name.',
            'items.*.category.required' => 'Every item needs a category (what was bought).',
        ]);

        $vendor = Vendor::find($data['vendor_id']);
        if (!$vendor) {
            throw ValidationException::withMessages(['vendor_id' => 'Pick a vendor from the Vendors list.']);
        }

        // Fixed-asset lines must point at an asset of this workspace that is not already on another invoice.
        $assetIds = collect($data['items'])->pluck('fixed_asset_id')->filter()->map(fn ($id) => (int) $id);
        if ($assetIds->count() !== $assetIds->unique()->count()) {
            throw ValidationException::withMessages(['items' => 'The same fixed asset is added twice.']);
        }
        $assets = \App\CrmFixedAsset::whereIn('id', $assetIds)->get()->keyBy('id');
        foreach ($assetIds as $assetId) {
            $asset = $assets[$assetId] ?? null;
            if (!$asset) {
                throw ValidationException::withMessages(['items' => 'A fixed asset on this invoice no longer exists. Remove that line.']);
            }
            if ($asset->purchase_invoice_id && (!$invoice || (int) $asset->purchase_invoice_id !== (int) $invoice->id)) {
                throw ValidationException::withMessages(['items' => $asset->number . ' (' . $asset->name . ') is already on purchase invoice PI-'
                    . str_pad($asset->purchase_invoice_id, 4, '0', STR_PAD_LEFT) . '.']);
            }
        }

        // Stock lines must point at an item on the Inventory page, on a bill dated from the day perpetual inventory started
        // (older bills stay as expenses) and not in the future (the stock is on hand now, so its bill must be in the books now).
        $stockIds = collect($data['items'])->filter(fn ($item) => empty($item['fixed_asset_id']))->pluck('inventory_item_id')->filter()->map(fn ($id) => (int) $id);
        $stockItems = \App\CrmInventoryItem::whereIn('id', $stockIds)->get()->keyBy('id');
        if ($stockIds->isNotEmpty()) {
            if ($stockIds->contains(fn ($id) => !$stockItems->has($id))) {
                throw ValidationException::withMessages(['items' => 'A stock item on this invoice no longer exists on the Inventory page. Remove that line.']);
            }
            $from = InventoryAccounting::from();
            if (\Carbon\Carbon::parse($data['issue_date'])->lt($from)) {
                throw ValidationException::withMessages(['items' => 'Stock lines are for bills dated ' . $from->format('d M Y') . ' or later (stock in the books starts that day). '
                    . 'For an older bill, enter the line with a normal category instead.']);
            }
            if (\Carbon\Carbon::parse($data['issue_date'])->gt(now()->endOfDay())) {
                throw ValidationException::withMessages(['issue_date' => 'A bill with stock lines cannot be dated in the future: the stock goes into Inventory now. '
                    . 'Use today\'s date (or the date the goods arrived).']);
            }
        }

        $items = [];
        foreach (array_values($data['items']) as $i => $item) {
            $quantity = round((float) $item['quantity'], 3);
            $unitPrice = round((float) $item['unit_price'], 2);
            $asset = !empty($item['fixed_asset_id']) ? $assets[(int) $item['fixed_asset_id']] : null;
            $stock = !$asset && !empty($item['inventory_item_id']) ? $stockItems[(int) $item['inventory_item_id']] : null;
            $items[] = [
                'fixed_asset_id' => $asset ? $asset->id : null,
                'inventory_item_id' => $stock ? $stock->id : null,
                'name' => $asset ? $asset->name : ($stock ? InventoryAccounting::itemLabel($stock) : trim($item['name'])),
                'category' => $asset ? \App\CrmPurchaseInvoiceItem::ASSET_CATEGORY : ($stock ? \App\CrmPurchaseInvoiceItem::STOCK_CATEGORY : trim($item['category'])),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
                'position' => $i,
            ];
        }
        // Stock this bill already brought in that has been used can't be taken off it any more.
        if ($invoice && ($problems = InventoryAccounting::billProblems($invoice->id, $this->stockWanted($items)))) {
            throw ValidationException::withMessages(['items' => $problems]);
        }

        unset($data['items'], $data['attachment']);
        $data['vendor_name'] = $vendor->name;
        $data['total'] = round(array_sum(array_column($items, 'line_total')), 2);

        return [$data, $items];
    }

    /**
     * Fixed assets that can be put on this invoice (not on another invoice yet), for the "Add Fixed Asset" picker.
     * Newest first; assets marked "Purchase Invoice" (waiting for their supplier bill) come first.
     */
    protected function assetOptions(?int $invoiceId = null)
    {
        return \App\CrmFixedAsset::where(fn ($q) => $q->whereNull('purchase_invoice_id')->when($invoiceId, fn ($q) => $q->orWhere('purchase_invoice_id', $invoiceId)))
            ->orderByRaw("payment_method = 'Purchase Invoice' desc")->orderByDesc('purchase_date')->orderByDesc('id')
            ->get()->map->pickerData()->values();
    }

    /**
     * After saving an invoice: its fixed-asset lines link their assets to it and give each asset its quantity,
     * cost, currency, supplier and date (the bill decides them); assets taken off the invoice are released.
     */
    protected function syncAssets(CrmPurchaseInvoice $invoice): void
    {
        $lines = $invoice->items()->whereNotNull('fixed_asset_id')->get();
        $this->releaseAssets(\App\CrmFixedAsset::where('purchase_invoice_id', $invoice->id)->whereNotIn('id', $lines->pluck('fixed_asset_id')));
        foreach ($lines as $line) {
            \App\CrmFixedAsset::whereKey($line->fixed_asset_id)->update([
                'purchase_invoice_id' => $invoice->id,
                'payment_method' => 'Purchase Invoice',
                'bank_account_id' => null,
                'purchase_date' => $invoice->issue_date->toDateString(),
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'amount' => $line->line_total,
                'currency' => $invoice->currency,
                'supplier_name' => $invoice->vendor_name,
                'reference' => $invoice->reference,
            ]);
        }
    }

    /** Inventory items for the "Add Stock Item" picker, with what is on hand and the current average cost. */
    protected function stockOptions()
    {
        return \App\CrmInventoryItem::orderBy('name')->get()->map(fn ($item) => [
            'id' => $item->id, 'name' => InventoryAccounting::itemLabel($item), 'category' => $item->category, 'unit' => $item->unit,
            'quantity' => (float) $item->quantity, 'unit_cost' => $item->unit_cost !== null ? round((float) $item->unit_cost, 2) : null,
        ])->keyBy('id');
    }

    /** A bill's stock lines per inventory item: [item id => [qty, value]]. */
    protected function stockWanted(array $items): array
    {
        $wanted = [];
        foreach ($items as $item) {
            if (empty($item['inventory_item_id'])) continue;
            $wanted[$item['inventory_item_id']][0] = ($wanted[$item['inventory_item_id']][0] ?? 0) + $item['quantity'];
            $wanted[$item['inventory_item_id']][1] = ($wanted[$item['inventory_item_id']][1] ?? 0) + $item['line_total'];
        }

        return $wanted;
    }

    /** After saving an invoice: its stock lines put the stock in (at the bill price, dated on the bill); removed lines take it back out. */
    protected function syncStock(CrmPurchaseInvoice $invoice, array $items): void
    {
        (new InventoryAccounting())->syncBill($invoice->fresh(), $this->stockWanted($items), Auth::guard('crm')->id());
    }

    /** Assets no longer on an invoice go back to "not bought yet" (no cost until put on another invoice). */
    protected function releaseAssets($query): void
    {
        $query->update(['purchase_invoice_id' => null, 'payment_method' => 'Purchase Invoice', 'quantity' => 1, 'unit_price' => 0, 'amount' => 0,
            'supplier_name' => null, 'reference' => null]);
    }

    /** Suggested categories plus every category already used on this workspace's invoices. */
    protected function itemCategories()
    {
        $used = \App\CrmPurchaseInvoiceItem::whereHas('invoice')->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        return collect(CrmPurchaseInvoice::ITEM_CATEGORIES)->concat($used)->unique()->values();
    }

    protected function vendors()
    {
        return Vendor::orderBy('name')->get(['id', 'name']);
    }

    protected function storeAttachment(Request $request): array
    {
        if (!$request->hasFile('attachment')) return [];
        $file = $request->file('attachment');

        return ['attachment_path' => $file->store('purchase-invoices', 'local'), 'attachment_name' => $file->getClientOriginalName()];
    }

    protected function deleteAttachment(CrmPurchaseInvoice $invoice): void
    {
        if ($invoice->attachment_path) Storage::disk('local')->delete($invoice->attachment_path);
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
            abort(403, 'Only accounts and admins can manage purchase invoices.');
        }
    }
}
