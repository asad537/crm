<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorPurchaseItem extends Model
{
    protected $fillable = [
        'position', 'category', 'expense_type', 'item_name', 'material', 'specification', 'size',
        'gsm', 'color', 'quantity', 'unit', 'unit_price', 'line_total', 'vat_percentage', 'extra',
    ];

    protected $casts = [
        'extra' => 'array',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(VendorPurchase::class, 'vendor_purchase_id');
    }
}
