<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InquiryAttachment extends Model
{
    protected $fillable = [
        'workspace_id', 'crm_email_id', 'design_ticket_id', 'uploaded_by', 'stage',
        'original_name', 'file_path', 'mime_type', 'file_size',
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
    }

    public function inquiry() { return $this->belongsTo(CrmEmail::class, 'crm_email_id'); }
    public function designTicket() { return $this->belongsTo(DesignRequirementTicket::class, 'design_ticket_id'); }
}
