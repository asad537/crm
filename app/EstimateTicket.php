<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstimateTicket extends Model
{
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('crm_workspace', function ($query) {
            if ($workspaceId = \App\Support\CrmWorkspaceContext::id()) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('estimate_tickets', 'workspace_id')) {
                    $query->where('estimate_tickets.workspace_id', $workspaceId);
                } else {
                    // Safe deployment fallback while the workspace migration is pending.
                    $query->whereHas('lead');
                }
            }
        });
        static::creating(function ($ticket) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('estimate_tickets', 'workspace_id')
                && !$ticket->workspace_id
                && ($workspaceId = \App\Support\CrmWorkspaceContext::id())) {
                $ticket->workspace_id = $workspaceId;
            }
        });
    }

    protected $fillable = [
        'workspace_id', 'ticket_number', 'crm_email_id', 'client_name', 'client_email', 'product_style',
        'length', 'width', 'height', 'unit', 'stock', 'colors', 'coating',
        'printing', 'finish_size', 'flat_size', 'shipping', 'weight',
        'lamination', 'die_cutting', 'gluing', 'shipping_region',
        'cost_breakdown', 'waste_material_percentage', 'waste_material_amount', 'estimator_notes',
        'currency', 'team_lead_id', 'team_lead_notes', 'team_lead_reviewed_at',
        'returned_to', 'return_note', 'returned_by', 'returned_at',
        'requirements', 'attachments', 'estimator_id', 'requested_by', 'status',
        'submitted_at', 'completed_at',
    ];

    public function workspace()
    {
        return $this->belongsTo(CrmWorkspace::class, 'workspace_id');
    }

    protected $casts = [
        'attachments' => 'array',
        'cost_breakdown' => 'array',
        'waste_material_percentage' => 'float',
        'waste_material_amount' => 'float',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'team_lead_reviewed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function options() { return $this->hasMany(EstimateTicketOption::class)->orderBy('id'); }
    public function estimator() { return $this->belongsTo(CrmUser::class, 'estimator_id'); }
    public function requester() { return $this->belongsTo(CrmUser::class, 'requested_by'); }
    public function teamLead() { return $this->belongsTo(CrmUser::class, 'team_lead_id'); }
    public function lead() { return $this->belongsTo(CrmEmail::class, 'crm_email_id'); }
}
