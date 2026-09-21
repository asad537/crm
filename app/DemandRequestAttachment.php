<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandRequestAttachment extends Model
{
    protected $fillable = [
        'demand_request_id', 'path', 'name', 'note', 'amount', 'mime', 'size', 'created_by',
    ];

    public function request()
    {
        return $this->belongsTo(DemandRequest::class, 'demand_request_id');
    }

    public function getUrlAttribute(): string
    {
        return asset(ltrim($this->path, '/'));
    }

    public function getIsImageAttribute(): bool
    {
        return in_array(strtolower(pathinfo($this->path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }
}
