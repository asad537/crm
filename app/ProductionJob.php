<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionJob extends Model
{
    use \App\Scopes\ScopesCrmWorkspaceThroughRelation;
    protected static function crmWorkspaceRelation() { return 'salesOrder'; }
    protected $fillable = [
        'sales_order_id', 'production_facility_id', 'production_machine_id',
        'production_manager_id', 'production_supervisor_id', 'press_operator_id',
        'qc_inspector_id', 'printing_method', 'gluing_type',
        'priority', 'status', 'planned_quantity', 'good_quantity', 'waste_quantity',
        'scheduled_start_at', 'scheduled_due_at', 'actual_start_at', 'completed_at',
        'planning_notes', 'press_setup_notes', 'adjustment_notes',
    ];

    protected $dates = [
        'scheduled_start_at', 'scheduled_due_at', 'actual_start_at', 'completed_at',
    ];

    protected $casts = [
        'completed_finishing_stages' => 'array',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function facility()
    {
        return $this->belongsTo(ProductionFacility::class, 'production_facility_id');
    }

    public function machine()
    {
        return $this->belongsTo(ProductionMachine::class, 'production_machine_id');
    }

    public function manager()
    {
        return $this->belongsTo(CrmUser::class, 'production_manager_id');
    }

    public function operator()
    {
        return $this->belongsTo(CrmUser::class, 'press_operator_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(CrmUser::class, 'production_supervisor_id');
    }

    public function qcInspector()
    {
        return $this->belongsTo(CrmUser::class, 'qc_inspector_id');
    }

    public function firstSheetChecks()
    {
        return $this->hasMany(ProductionFirstSheetCheck::class)->orderBy('attempt_number', 'desc');
    }

    public function logs()
    {
        return $this->hasMany(ProductionJobLog::class)->orderBy('created_at', 'desc');
    }
}
