<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmProposal extends Model
{
    protected $table = 'crm_proposals';

    protected $fillable = [
        'workspace_id', 'crm_email_id', 'subject', 'client_name', 'product_name', 'quantity', 'size',
        'comment', 'server_path', 'assigned_designer_id', 'attachment_path',
        'attachment_name', 'status', 'change_request_note', 'created_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('workspace', function ($query) {
            if ($id = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $id);
            }
        });
        static::creating(function ($model) {
            if (!$model->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) {
                $model->workspace_id = $id;
            }
        });
    }

    public function designer()
    {
        return $this->belongsTo(CrmUser::class, 'assigned_designer_id');
    }

    public function creator()
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }
}
