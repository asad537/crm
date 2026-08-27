<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionFirstSheetCheck extends Model
{
    use \App\Scopes\ScopesCrmWorkspaceThroughRelation;
    protected static function crmWorkspaceRelation() { return 'job'; }
    protected $fillable = [
        'production_job_id', 'qc_inspector_id', 'attempt_number',
        'proof_match_passed', 'cmyk_density_passed', 'spot_color_passed',
        'registration_passed', 'print_defect_passed', 'supervisor_approved',
        'status', 'notes',
    ];

    protected $casts = [
        'proof_match_passed' => 'boolean',
        'cmyk_density_passed' => 'boolean',
        'spot_color_passed' => 'boolean',
        'registration_passed' => 'boolean',
        'print_defect_passed' => 'boolean',
        'supervisor_approved' => 'boolean',
    ];

    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }

    public function inspector()
    {
        return $this->belongsTo(CrmUser::class, 'qc_inspector_id');
    }
}
