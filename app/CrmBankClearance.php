<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** A bank / cash movement ticked as "cleared" (seen on the bank statement) during bank reconciliation. */
class CrmBankClearance extends Model
{
    protected $table = 'crm_bank_clearances';

    protected $fillable = ['workspace_id', 'bank_account_id', 'movement_key', 'cleared_date', 'created_by'];

    protected $casts = ['cleared_date' => 'date'];

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
