<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandRequestPayment extends Model
{
    protected $fillable = [
        'demand_request_id', 'item_id', 'category', 'adjust_from', 'pay_type', 'amount', 'method', 'paid_to', 'vendor_invoice_no', 'note', 'paid_at', 'created_by',
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

    public function files()
    {
        return $this->hasMany(DemandRequestPaymentFile::class, 'payment_id');
    }

    /** All proof files: the new multi-file rows, plus any legacy single attachment. */
    public function allProofs(): array
    {
        $out = [];
        foreach ($this->files as $f) {
            $out[] = ['url' => $f->url, 'name' => $f->name ?: 'file', 'is_image' => $f->is_image];
        }
        if ($this->attachment_path) {
            $out[] = [
                'url' => asset(ltrim($this->attachment_path, '/')),
                'name' => $this->attachment_name ?: 'file',
                'is_image' => (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', $this->attachment_path),
            ];
        }

        return $out;
    }
}
