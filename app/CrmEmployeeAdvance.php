<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** Money given to an employee ahead of salary; recovered through payroll advance deductions. */
class CrmEmployeeAdvance extends Model
{
    protected $table = 'crm_employee_advances';

    protected $fillable = ['workspace_id', 'employee_id', 'given_date', 'amount', 'installment', 'method', 'note', 'created_by', 'bank_account_id'];

    protected $casts = ['given_date' => 'date', 'amount' => 'decimal:2', 'installment' => 'decimal:2'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('crm_workspace', function ($query) {
            if ($id = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $id);
            }
        });
        static::creating(function ($m) {
            if (!$m->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) $m->workspace_id = $id;
        });
    }

    public function employee() { return $this->belongsTo(CrmEmployee::class, 'employee_id'); }

    public function creator() { return $this->belongsTo(CrmUser::class, 'created_by'); }
}
