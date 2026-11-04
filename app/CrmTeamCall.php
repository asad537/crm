<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** One voice / video call between two teammates (the media itself goes browser to browser over WebRTC). */
class CrmTeamCall extends Model
{
    protected $table = 'crm_team_calls';

    protected $fillable = [
        'workspace_id', 'caller_id', 'callee_id', 'type', 'status', 'offer', 'answer',
        'answered_at', 'ended_at', 'ended_by', 'caller_seen_at', 'callee_seen_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'caller_seen_at' => 'datetime',
        'callee_seen_at' => 'datetime',
    ];

    public function caller() { return $this->belongsTo(CrmUser::class, 'caller_id'); }

    public function callee() { return $this->belongsTo(CrmUser::class, 'callee_id'); }

    public function scopeLive($query)
    {
        return $query->whereIn('status', ['ringing', 'active']);
    }

    public function isParticipant($userId): bool
    {
        return (int) $userId === (int) $this->caller_id || (int) $userId === (int) $this->callee_id;
    }
}
