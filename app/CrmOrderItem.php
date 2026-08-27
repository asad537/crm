<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmOrderItem extends Model
{
    protected $table = 'crm_order_items';

    protected $fillable = [
        'crm_email_id', 'product_name', 'quantity', 'unit_price', 'line_total',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'line_total' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }
}
