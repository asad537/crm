<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionJobLog extends Model
{
    use \App\Scopes\ScopesCrmWorkspaceThroughRelation;
    protected static function crmWorkspaceRelation() { return 'job'; }
    protected $fillable = [
        'production_job_id', 'crm_user_id', 'from_status', 'to_status', 'notes',
    ];

    public function user()
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }
}
