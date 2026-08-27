<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmApproval extends Model
{
    protected $table = 'crm_approvals';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'stage',
        'status',
        'approver_id',
        'comments'
    ];

    public function approvable()
    {
        return $this->morphTo();
    }

    public function approver()
    {
        return $this->belongsTo(CrmUser::class, 'approver_id');
    }
}
