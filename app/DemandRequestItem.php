<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandRequestItem extends Model
{
    protected $fillable = [
        'demand_request_id', 'position', 'category', 'job_no', 'description', 'specification',
        'qty', 'gsm', 'estimated_price', 'vat_percentage', 'estimated_total', 'pay_by',
        'received', 'received_at', 'actual_price', 'actual_total',
        'paid_by', 'paid_amount', 'outstanding', 'vendor_name', 'vendor_invoice_no', 'extra',
    ];

    protected $casts = [
        'received' => 'boolean',
        'received_at' => 'datetime',
        'estimated_price' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'actual_price' => 'decimal:2',
        'actual_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding' => 'decimal:2',
        'extra' => 'array',
    ];

    public function request()
    {
        return $this->belongsTo(DemandRequest::class, 'demand_request_id');
    }

    public function files()
    {
        return $this->hasMany(DemandRequestItemFile::class, 'item_id');
    }
}
