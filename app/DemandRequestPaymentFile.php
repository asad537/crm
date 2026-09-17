<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandRequestPaymentFile extends Model
{
    protected $fillable = ['payment_id', 'path', 'name', 'mime', 'size'];

    public function getUrlAttribute(): ?string
    {
        return $this->path ? asset(ltrim($this->path, '/')) : null;
    }

    public function getIsImageAttribute(): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) $this->path);
    }

    public function payment()
    {
        return $this->belongsTo(DemandRequestPayment::class, 'payment_id');
    }
}
