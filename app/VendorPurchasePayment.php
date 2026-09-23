<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorPurchasePayment extends Model
{
    protected $fillable = [
        'workspace_id', 'vendor_id', 'vendor_purchase_id', 'demand_id', 'demand_no', 'amount',
        'method', 'paid_at', 'note', 'receipt_path', 'receipt_name', 'receipt_mime', 'created_by',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($p) {
            if (!$p->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) {
                $p->workspace_id = $id;
            }
            // Keep vendor_id in sync from the linked purchase (for the vendor ledger).
            if (!$p->vendor_id && $p->vendor_purchase_id) {
                $p->vendor_id = optional(VendorPurchase::find($p->vendor_purchase_id))->vendor_id;
            }
        });
    }

    public function purchase(){ return $this->belongsTo(VendorPurchase::class, 'vendor_purchase_id'); }
    public function vendor(){ return $this->belongsTo(Vendor::class, 'vendor_id'); }
    public function demand(){ return $this->belongsTo(DemandRequest::class, 'demand_id'); }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path ? asset($this->receipt_path) : null;
    }
}
