<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorPurchase extends Model
{
    protected $fillable = [
        'workspace_id', 'created_by', 'vendor_id', 'vendor_name', 'vendor_phone', 'vendor_email',
        'purchase_date', 'due_date', 'invoice_number', 'job_id', 'category', 'item_name',
        'material', 'specification', 'size', 'gsm', 'color', 'quantity', 'unit',
        'unit_price', 'subtotal', 'vat_percentage', 'tax_amount', 'shipping_cost', 'total_amount',
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
}
