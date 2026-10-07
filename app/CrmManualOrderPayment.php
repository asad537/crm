<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** A payment received against a TCB manual order. */
class CrmManualOrderPayment extends Model
{
    protected $table = 'crm_manual_order_payments';

    protected $fillable = [
        'manual_order_id', 'workspace_id', 'amount', 'paid_at', 'method', 'reference', 'note', 'created_by',
        'gateway', 'gateway_transaction_id', 'gateway_auth_code', 'card_type', 'card_last4', 'gateway_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(CrmManualOrder::class, 'manual_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }
}
