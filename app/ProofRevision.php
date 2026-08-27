<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProofRevision extends Model
{
    use \App\Scopes\ScopesCrmWorkspaceThroughRelation;
    protected static function crmWorkspaceRelation() { return 'email'; }
    protected $table = 'proof_revisions';

    protected $fillable = [
        'custom_project_id',
        'crm_email_id',
        'version_number',
        'file_path',
        'feedback_notes',
        'status',
        'uploaded_by'
    ];

    public function project()
    {
        return $this->belongsTo(CustomProject::class, 'custom_project_id');
    }

    public function email()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }

    public function uploader()
    {
        return $this->belongsTo(CrmUser::class, 'uploaded_by');
    }
}
