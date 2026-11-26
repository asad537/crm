<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** One follow-up call in the Sara voice-call queue (processed one at a time by voice:process-queue). */
class VoiceCallQueue extends Model
{
    protected $table = 'voice_call_queue';

    protected $fillable = [
        'crm_email_id', 'phone', 'status', 'conversation_id',
        'duration_secs', 'note', 'queued_by',
    ];

    public function lead()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }
}
