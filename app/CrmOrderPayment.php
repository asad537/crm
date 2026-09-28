<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmOrderPayment extends Model
{
    protected $fillable = [
        'workspace_id', 'amount', 'paid_at', 'method', 'reference', 'note', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }
}
