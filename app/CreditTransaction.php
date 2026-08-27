<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'credit_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firebase_uid',
        'change',
        'balance_after',
        'reason',
        'platform',
        'product_id',
        'transaction_id',
        'raw_payload',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'raw_payload' => 'array',
        'change' => 'integer',
        'balance_after' => 'integer',
    ];
}
