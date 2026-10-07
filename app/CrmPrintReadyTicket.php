<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** Print Ready ticket: created when a paid order is sent to production; worked by designers. */
class CrmPrintReadyTicket extends Model
{
    protected $table = 'crm_print_ready_tickets';

    protected $fillable = [
        'workspace_id', 'manual_order_id', 'production_brief_id', 'ticket_number', 'job_number', 'client_name', 'products',
        'folder_path', 'printers_deadline', 'clients_deadline', 'brief_notes', 'status', 'assigned_designer_id',
        'output_path', 'designer_note', 'change_request_note', 'created_by', 'claimed_at', 'completed_at',
    ];

    protected $casts = [
        'products' => 'array',
        'printers_deadline' => 'date',
        'clients_deadline' => 'date',
        'claimed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUSES = [
        'requested' => 'Requested',
        'in_progress' => 'In Progress',
        'change_requested' => 'Change Requested',
        'completed' => 'Completed',
    ];

    /** Status buckets behind the Active / My Tickets / History tabs. */
    public const TAB_STATUSES = [
        'active' => ['requested'],
        'mine' => ['in_progress', 'change_requested'],
        'history' => ['completed'],
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('workspace', function ($query) {
            if ($id = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $id);
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(CrmManualOrder::class, 'manual_order_id');
    }

    public function brief()
    {
        return $this->belongsTo(CrmManualOrderProductionBrief::class, 'production_brief_id');
    }

    public function designer()
    {
        return $this->belongsTo(CrmUser::class, 'assigned_designer_id');
    }

    public function creator()
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(CrmPrintReadyFile::class, 'ticket_id')->latest();
    }

    public function notes()
    {
        return $this->hasMany(CrmPrintReadyNote::class, 'ticket_id')->latest();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function addNote(?int $userId, string $type, string $body): void
    {
        $this->notes()->create(['user_id' => $userId, 'type' => $type, 'body' => $body]);
    }
}
