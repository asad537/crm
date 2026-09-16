<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandRequestPayment extends Model
{
    protected $fillable = [
        'demand_request_id', 'item_id', 'amount', 'method', 'paid_to', 'note', 'paid_at', 'created_by',
        'attachment_path', 'attachment_name', 'attachment_mime',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? asset(ltrim($this->attachment_path, '/')) : null;
    }

    public function request()
    {
        return $this->belongsTo(DemandRequest::class, 'demand_request_id');
    }

    public function item()
    {
        return $this->belongsTo(DemandRequestItem::class, 'item_id');
    }
}
