<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Something the company keeps and uses (machine, laptop, furniture, vehicle...). It is registered here with a name
 * and a depreciation rate; its quantity, cost, supplier and date come from the purchase invoice it is added to
 * ("Add Fixed Asset" on Purchase Invoices). Older assets entered with their own price and payment keep them.
 */
class CrmFixedAsset extends Model
{
    protected $table = 'crm_fixed_assets';

    /**
     * How the asset was paid. "Purchase Invoice" = billed on a supplier invoice and paid through it (every new asset);
     * the others are on older assets that were entered with their own payment ("Credit" = not paid yet).
     */
    const PAYMENT_METHODS = ['Cash', 'Petty Cash', 'Bank Transfer', 'Company Account', 'Card', 'Cheque', 'Credit', 'Purchase Invoice'];

    /** Methods where the asset itself is not money out (it is owed, or paid through its invoice). */
    const NOT_PAID_DIRECTLY = ['Credit', 'Purchase Invoice'];

    protected $fillable = [
        'workspace_id', 'purchase_date', 'name', 'depreciation_rate', 'quantity', 'unit_price', 'category', 'supplier_name', 'amount', 'currency',
        'payment_method', 'purchase_invoice_id', 'reference', 'receipt_path', 'receipt_name', 'note', 'created_by',
        'bank_account_id',
    ];

    protected $casts = ['purchase_date' => 'date', 'amount' => 'decimal:2', 'quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'depreciation_rate' => 'decimal:2'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('crm_workspace', function ($query) {
            if ($id = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $id);
            }
        });
        static::creating(function ($m) {
            if (!$m->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) $m->workspace_id = $id;
        });
    }

    public function creator() { return $this->belongsTo(CrmUser::class, 'created_by'); }

    public function getNumberAttribute(): string
    {
        return 'FA-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public function purchaseInvoice() { return $this->belongsTo(CrmPurchaseInvoice::class, 'purchase_invoice_id'); }

    /** Not on a purchase invoice yet, so it has no cost yet (older assets with their own payment excepted). */
    public function isAwaitingInvoice(): bool
    {
        return $this->payment_method === 'Purchase Invoice' && !$this->purchase_invoice_id;
    }

    /** "2" or "2.5" — quantity without trailing zeros. */
    public function quantityLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->quantity, 3, '.', ''), '0'), '.');
    }

    public function cost(): float
    {
        return $this->isAwaitingInvoice() ? 0.0 : (float) $this->amount;
    }

    /** Straight-line: rate % of cost per year. */
    public function yearlyDepreciation(): float
    {
        return round($this->cost() * (float) $this->depreciation_rate / 100, 2);
    }

    /** Depreciation from the purchase (invoice) date up to $asOf, never more than the cost. */
    public function accumulatedDepreciation(?Carbon $asOf = null): float
    {
        if ($this->cost() <= 0 || (float) $this->depreciation_rate <= 0 || !$this->purchase_date) return 0.0;
        $asOf = ($asOf ?: now())->copy()->startOfDay();
        if ($asOf->lte($this->purchase_date)) return 0.0;
        $years = $this->purchase_date->diffInDays($asOf) / 365;

        return round(min($this->cost(), $this->yearlyDepreciation() * $years), 2);
    }

    public function bookValue(?Carbon $asOf = null): float
    {
        return round($this->cost() - $this->accumulatedDepreciation($asOf), 2);
    }

    /** "20" or "12.5" — rate without trailing zeros. */
    public function rateLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->depreciation_rate, 2, '.', ''), '0'), '.');
    }

    /** Values the Fixed Assets page needs to prefill its edit form. */
    public function formData(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'depreciation_rate' => (float) $this->depreciation_rate, 'number' => $this->number];
    }

    /** Row data for the "Add Fixed Asset" picker on the purchase invoice form. */
    public function pickerData(): array
    {
        return [
            'id' => $this->id, 'number' => $this->number, 'name' => $this->name, 'rate' => $this->rateLabel(),
            'quantity' => (float) $this->quantity, 'unit_price' => $this->isAwaitingInvoice() ? null : (float) ($this->unit_price ?? $this->amount),
            'currency' => $this->currency, 'supplier' => $this->isAwaitingInvoice() ? null : $this->supplier_name,
        ];
    }
}
