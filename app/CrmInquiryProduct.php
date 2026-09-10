<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmInquiryProduct extends Model
{
    protected $table = 'crm_inquiry_products';

    protected $fillable = [
        'crm_email_id', 'product_name', 'printing', 'length', 'width', 'height',
        'unit', 'finish_size', 'open_size', 'flat_size', 'stock',
        'finishing_options', 'quantities', 'price_offered', 'sort_order',
    ];

    protected $casts = [
        'finishing_options' => 'array',
        'quantities' => 'array',
    ];

    public function inquiry()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }

    /** "2 x 5 x 2" style dimension string for display. */
    public function getDimensionLabelAttribute()
    {
        $parts = array_filter([$this->length, $this->width, $this->height], function ($v) {
            return $v !== null && $v !== '' && (float) $v > 0;
        });
        return $parts ? implode(' x ', array_map(function ($v) {
            return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        }, $parts)) . ($this->unit ? ' ' . $this->unit : '') : '—';
    }
}
