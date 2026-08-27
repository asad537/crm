<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QualityControl extends Model
{
    use \App\Scopes\ScopesCrmWorkspaceThroughRelation;
    protected static function crmWorkspaceRelation() { return 'email'; }
    protected $table = 'quality_controls';

    protected $fillable = [
        'production_order_id',
        'crm_email_id',
        'qc_agent_id',
        'dimension_passed',
        'fold_color_passed',
        'quantity_passed',
        'glue_strength_passed',
        'barcode_scan_passed',
        'packaging_passed',
        'notes',
        'photo_defect_path'
    ];

    protected $casts = [
        'dimension_passed' => 'boolean',
        'fold_color_passed' => 'boolean',
        'quantity_passed' => 'boolean',
        'glue_strength_passed' => 'boolean',
        'barcode_scan_passed' => 'boolean',
        'packaging_passed' => 'boolean',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function email()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }

    public function agent()
    {
        return $this->belongsTo(CrmUser::class, 'qc_agent_id');
    }
}
