<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/** A supplier's bill entered on the Purchase Invoices tab, with its line items and the payments made against it. */
class CrmPurchaseInvoice extends Model
{
    protected $table = 'crm_purchase_invoices';

    const PAYMENT_METHODS = ['Cash', 'Petty Cash', 'Bank Transfer', 'Company Account', 'Card', 'Cheque'];

    /**
     * Suggested item categories (any other can be typed). Each becomes its own Profit & Loss line;
     * "Raw Material" and "Production" count as Cost of Sales, the rest as Operating Expenses.
     * Fixed-asset lines ("Add Fixed Asset") are not costs: they stay on the balance sheet.
     */
    const ITEM_CATEGORIES = [
        'Raw Material', 'Production', 'Consumables', 'Machinery & Spare Parts', 'Maintenance & Repairs',
        'Office Supplies', 'Transport & Fuel', 'Utilities', 'Rent', 'Marketing', 'Other',
    ];

    const STATUSES = [
        'unpaid' => 'Unpaid',
        'partial' => 'Partially Paid',
        'overdue' => 'Overdue',
        'paid' => 'Paid',
        'overpaid' => 'Overpaid',
    ];

    protected $fillable = [
        'workspace_id', 'vendor_id', 'vendor_name', 'issue_date', 'due_date', 'reference', 'project', 'description',
        'currency', 'total', 'attachment_path', 'attachment_name', 'created_by',
    ];

    protected $casts = ['issue_date' => 'date', 'due_date' => 'date', 'total' => 'decimal:2'];

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

    public function items() { return $this->hasMany(CrmPurchaseInvoiceItem::class, 'purchase_invoice_id')->orderBy('position')->orderBy('id'); }

    public function payments() { return $this->hasMany(CrmPurchaseInvoicePayment::class, 'purchase_invoice_id')->orderBy('paid_at')->orderBy('id'); }

    public function vendor() { return $this->belongsTo(Vendor::class, 'vendor_id'); }

    public function creator() { return $this->belongsTo(CrmUser::class, 'created_by'); }

    public function getNumberAttribute(): string
    {
        return 'PI-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Item totals per category, biggest first, e.g. ['Raw Material' => 800.0, 'Office Supplies' => 220.0]. */
    public function categoryTotals(): array
    {
        return $this->items->groupBy(fn ($item) => $item->category ?: 'Uncategorised')
            ->map(fn ($items) => round((float) $items->sum('line_total'), 2))->sortDesc()->all();
    }

    public function paidTotal(): float
    {
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        return round((float) $payments->sum('amount'), 2);
    }

    /** Negative when more was paid than invoiced. */
    public function balanceDue(): float
    {
        return round((float) $this->total - $this->paidTotal(), 2);
    }

    /** Paid on this bill by the end of $date (all payments when null). */
    public function paidAsOf(?Carbon $date = null): float
    {
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();
        if ($date) $payments = $payments->filter(fn ($p) => $p->paid_at && Carbon::parse($p->paid_at)->lte($date->copy()->endOfDay()));

        return round((float) $payments->sum('amount'), 2);
    }

    /** One key per vendor, so a vendor's bills can be netted (an overpaid bill is an advance against the others). */
    public function vendorKey(): string
    {
        return $this->vendor_id ? 'id:' . $this->vendor_id : 'name:' . mb_strtolower(trim((string) $this->vendor_name));
    }

    public function isFullyPaid(): bool
    {
        return $this->balanceDue() <= 0.009;
    }

    public function status(): string
    {
        $balance = $this->balanceDue();
        if ($balance < -0.009) return 'overpaid';
        if ($balance <= 0.009) return 'paid';
        if ($this->due_date && $this->due_date->lt(now()->startOfDay())) return 'overdue';

        return $this->paidTotal() > 0 ? 'partial' : 'unpaid';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status()];
    }
}
