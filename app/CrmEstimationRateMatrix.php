<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmEstimationRateMatrix extends Model
{
    protected $table = 'crm_estimation_rate_matrices';

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('crm_workspace', function ($query) {
            if ($workspaceId = \App\Support\CrmWorkspaceContext::id()) {
                $query->where('crm_estimation_rate_matrices.workspace_id', $workspaceId);
            }
        });
        static::creating(function ($matrix) {
            if (!$matrix->workspace_id && ($workspaceId = \App\Support\CrmWorkspaceContext::id())) {
                $matrix->workspace_id = $workspaceId;
            }
        });
    }

    protected $fillable = [
        'workspace_id',
        'type',
        'paper_type',
        'paper_size',
        'gsm',
        'thickness_unit',
        'name',
        'ctp_plate_name',
        'currency',
        'rate',
        'rate_large',
        'threshold_short',
        'threshold_long',
        'weight_divisor',
        'weight_sheets',
        'created_by',
    ];

    protected $casts = [
        'rate' => 'float',
        'rate_large' => 'float',
    ];
}
