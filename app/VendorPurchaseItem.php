<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendorPurchaseItem extends Model
{
    protected $fillable = [
        'position', 'category', 'item_name', 'material', 'specification', 'size',
        'gsm', 'color', 'quantity', 'unit', 'unit_price', 'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(VendorPurchase::class, 'vendor_purchase_id');
    }
}
