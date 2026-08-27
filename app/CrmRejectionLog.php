<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmRejectionLog extends Model
{
    protected $table = 'crm_rejection_logs';

    protected $fillable = [
        'crm_email_id',
        'rejection_reason',
        'retention_agent_id',
        'status',
        'offered_options',
        'follow_up_notes'
    ];

    protected $casts = [
        'offered_options' => 'array'
    ];

    public function lead()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }

    public function agent()
    {
        return $this->belongsTo(CrmUser::class, 'retention_agent_id');
    }
}
