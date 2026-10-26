<?php

namespace App\Http\Controllers\Crm;

use App\CrmFixedAsset;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Al Massa "Fixed Assets": the register of things the company keeps (machines, laptops...), each with a name and a
 * depreciation rate. An asset is bought by adding it to the supplier's purchase invoice ("Add Fixed Asset"); the invoice
 * sets its quantity, cost, supplier and date, goes to the vendor ledger, and its payments show under Payments.
 */
class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $filters = ['search' => trim((string) $request->input('search', ''))];

        $query = CrmFixedAsset::with(['creator', 'purchaseInvoice.payments']);
        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $number = preg_match('/^\s*FA-?0*(\d+)\s*$/i', $filters['search'], $m) ? (int) $m[1] : null;
            $query->where(function ($q) use ($like, $number) {
                $q->where('name', 'like', $like);
                if ($number) $q->orWhere('id', $number);
            });
        }

        $all = (clone $query)->get();
        $totals = [
            'count' => $all->count(),
            'cost' => (float) $all->sum(fn ($a) => $a->cost()),
            'depreciation' => (float) $all->sum(fn ($a) => $a->accumulatedDepreciation()),
            'book_value' => (float) $all->sum(fn ($a) => $a->bookValue()),
            'awaiting' => $all->filter->isAwaitingInvoice()->count(),
        ];
        $entries = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('crm.fixed_assets.index', ['entries' => $entries, 'totals' => $totals, 'filters' => $filters]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        // Cost, quantity, supplier and date are filled in when the asset is added to a purchase invoice.
        $asset = CrmFixedAsset::create(array_merge($this->validated($request), [
            'purchase_date' => now()->toDateString(),
            'quantity' => 1,
            'unit_price' => 0,
            'amount' => 0,
            'currency' => 'AED',
            'payment_method' => 'Purchase Invoice',
            'created_by' => Auth::guard('crm')->id(),
        ]));

        return redirect()->route('crm.fixed_assets.index')
            ->with('success', $asset->number . ' — ' . $asset->name . ' added. Now buy it on Purchase Invoices with "Add Fixed Asset".');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $asset = CrmFixedAsset::findOrFail($id);
        $asset->update($this->validated($request));

        return back()->with('success', $asset->number . ' updated.');
    }

    public function destroy($id)
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAdmin() && !$user->isAccounts()) {
            abort(403, 'Only admins and accounts can delete fixed assets.');
        }
        $asset = CrmFixedAsset::findOrFail($id);
        if ($asset->purchase_invoice_id) {
            $invoiceNo = 'PI-' . str_pad($asset->purchase_invoice_id, 4, '0', STR_PAD_LEFT);
            return back()->with('error', $asset->number . ' is on purchase invoice ' . $invoiceNo . '. Remove it from that invoice first, then delete it here.');
        }
        \App\CrmDeletionLog::record('fixed_asset', $asset, $asset->number . ' — ' . $asset->name, $asset->only([
            'purchase_date', 'name', 'depreciation_rate', 'quantity', 'supplier_name', 'amount', 'currency', 'payment_method', 'reference', 'note',
        ]));
        if ($asset->receipt_path) Storage::disk('local')->delete($asset->receipt_path);
        $asset->delete();

        return back()->with('success', $asset->number . ' deleted.');
    }

    /** Receipt uploaded on older assets (before assets were bought through purchase invoices). */
    public function receipt($id)
    {
        $this->authorizeAccess();
        $asset = CrmFixedAsset::findOrFail($id);
        abort_unless($asset->receipt_path && Storage::disk('local')->exists($asset->receipt_path), 404);

        return response()->file(Storage::disk('local')->path($asset->receipt_path));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'depreciation_rate' => 'required|numeric|min:0|max:100',
        ], [
            'depreciation_rate.max' => 'Depreciation rate is a yearly percentage (0–100).',
        ]);
        $data['name'] = trim($data['name']);
        $data['depreciation_rate'] = round((float) $data['depreciation_rate'], 2);

        return $data;
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can manage fixed assets.');
        }
    }
}
