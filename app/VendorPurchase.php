<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorPurchase extends Model
{
    protected $fillable = [
        'workspace_id', 'created_by', 'vendor_id', 'vendor_name', 'vendor_phone', 'vendor_email',
        'purchase_date', 'due_date', 'invoice_number', 'job_id', 'demand_id', 'demand_no', 'category', 'expense_type', 'item_name',
        'material', 'specification', 'size', 'gsm', 'up_imposition', 'gp_status', 'color', 'quantity', 'unit',
        'unit_price', 'subtotal', 'vat_percentage', 'tax_amount', 'shipping_cost', 'deduction', 'total_amount',
        'paid_amount', 'balance_amount', 'payment_status', 'payment_method',
        'currency', 'notes', 'attachment_path', 'attachment_name', 'attachment_mime',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('crm_workspace', function ($query) {
            if ($workspaceId = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable().'.workspace_id', $workspaceId);
            }
        });

        static::creating(function ($purchase) {
            if (!$purchase->workspace_id && ($workspaceId = \App\Support\CrmWorkspaceContext::id())) {
                $purchase->workspace_id = $workspaceId;
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }
    public function vendor(){ return $this->belongsTo(Vendor::class); }
    public function items(){ return $this->hasMany(VendorPurchaseItem::class)->orderBy('position'); }
    public function payments(){ return $this->hasMany(VendorPurchasePayment::class)->orderByDesc('paid_at')->orderByDesc('id'); }
    public function demand(){ return $this->belongsTo(DemandRequest::class, 'demand_id'); }

    /** Recompute paid (sum of payment records) and balance (= total − paid − deduction); persist. */
    public function recomputePayments(): void
    {
        $paid = round((float) $this->payments()->sum('amount'), 2);
        $deduction = round((float) $this->deduction, 2);
        $balance = round((float) $this->total_amount - $paid - $deduction, 2);
        if ($balance < 0) { $balance = 0.0; }
        $status = $paid + $deduction <= 0.009 ? 'Unpaid' : ($balance > 0.009 ? 'Partial' : 'Paid');
        $this->forceFill([
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'payment_status' => $status,
        ])->save();
    }
}
