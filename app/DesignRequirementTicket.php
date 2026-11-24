<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DesignRequirementTicket extends Model
{
    protected $fillable = [
        'workspace_id', 'ticket_number', 'crm_email_id', 'requested_by', 'claimed_by',
        'estimate_ticket_id', 'status', 'quantities', 'open_size', 'unit',
        'designer_notes', 'return_note', 'returned_by', 'returned_at',
        'opened_at', 'completed_at', 'forwarded_at',
    ];

    protected $casts = [
        'quantities' => 'array', 'returned_at' => 'datetime', 'opened_at' => 'datetime',
        'completed_at' => 'datetime', 'forwarded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('workspace', function ($query) {
            if ($id = \App\Support\CrmWorkspaceContext::id()) $query->where('workspace_id', $id);
        });
        static::creating(function ($model) {
            if (!$model->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) $model->workspace_id = $id;
        });
        // Keep the sidebar "Design" badge live: the moment a ticket is created, claimed,
        // released, completed or returned, clear the 30-second cached count for every
        // designer/admin in its workspace so the number updates immediately instead of
        // lingering for up to half a minute.
        static::saved(function ($model) {
            if ($model->wasRecentlyCreated || $model->wasChanged(['status', 'claimed_by'])) {
                static::flushSidebarBadge($model->workspace_id);
            }
        });
        static::deleted(function ($model) {
            static::flushSidebarBadge($model->workspace_id);
        });
    }

    /** Forget the cached sidebar design-ticket count for every designer/admin in this workspace. */
    public static function flushSidebarBadge($workspaceId): void
    {
        $workspaceId = (int) $workspaceId;
        if (!$workspaceId) return;
        try {
            $userIds = \Illuminate\Support\Facades\DB::table('crm_user_workspace')
                ->where('workspace_id', $workspaceId)
                ->whereIn('role', ['designer', 'admin', 'super_admin'])
                ->pluck('crm_user_id');
            foreach ($userIds as $uid) {
                \Illuminate\Support\Facades\Cache::forget('crm:sidebar:' . $workspaceId . ':' . $uid . ':design_tickets');
            }
        } catch (\Throwable $e) {
            // badge is a convenience; never let a cache miss break a ticket save
        }
    }

    public function inquiry() { return $this->belongsTo(CrmEmail::class, 'crm_email_id'); }
    public function requester() { return $this->belongsTo(CrmUser::class, 'requested_by'); }
    public function designer() { return $this->belongsTo(CrmUser::class, 'claimed_by'); }
    public function estimateTicket() { return $this->belongsTo(EstimateTicket::class, 'estimate_ticket_id'); }
    public function attachments() { return $this->hasMany(InquiryAttachment::class, 'design_ticket_id'); }
}
