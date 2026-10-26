<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** One bank reconciliation: the bank statement balance on a date against the CRM balance and uncleared items. */
class CrmBankReconciliation extends Model
{
    protected $table = 'crm_bank_reconciliations';

    protected $fillable = [
        'workspace_id', 'bank_account_id', 'statement_date', 'statement_balance', 'book_balance',
        'uncleared_in', 'uncleared_out', 'difference', 'note', 'created_by',
    ];

    protected $casts = [
        'statement_date' => 'date', 'statement_balance' => 'decimal:2', 'book_balance' => 'decimal:2',
        'uncleared_in' => 'decimal:2', 'uncleared_out' => 'decimal:2', 'difference' => 'decimal:2',
    ];

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

    public function account() { return $this->belongsTo(CrmBankAccount::class, 'bank_account_id'); }

    public function creator() { return $this->belongsTo(CrmUser::class, 'created_by'); }
}
