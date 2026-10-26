<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** Yearly budget for one Profit & Loss category (group: income | cos | opex), used by the Budget vs Actual report. */
class CrmBudget extends Model
{
    protected $table = 'crm_budgets';

    const GROUPS = ['income' => 'Income', 'cos' => 'Cost of Sales', 'opex' => 'Operating Expenses'];

    protected $fillable = ['workspace_id', 'year', 'group', 'category', 'amount', 'updated_by'];

    protected $casts = ['amount' => 'decimal:2'];

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
}
