<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandRequestItemFile extends Model
{
    protected $fillable = ['item_id', 'path', 'name', 'mime', 'size'];

    public function getUrlAttribute(): ?string
    {
        return $this->path ? asset(ltrim($this->path, '/')) : null;
    }

    public function item()
    {
        return $this->belongsTo(DemandRequestItem::class, 'item_id');
    }
}
