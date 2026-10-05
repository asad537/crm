<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** Money the company paid back to the accountant against a demand's negative account balance. */
class DemandRequestSettlement extends Model
{
    protected $fillable = [
        'demand_request_id', 'amount', 'method', 'note', 'paid_at', 'proof_path', 'proof_name', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function request()
    {
        return $this->belongsTo(DemandRequest::class, 'demand_request_id');
    }

    public function creator()
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_path ? asset(ltrim($this->proof_path, '/')) : null;
    }
}
