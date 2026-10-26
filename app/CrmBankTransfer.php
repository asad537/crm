<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** Money moved between two of the company's own accounts (e.g. bank → petty cash); not income or expense. */
class CrmBankTransfer extends Model
{
    protected $table = 'crm_bank_transfers';

    protected $fillable = ['workspace_id', 'from_account_id', 'to_account_id', 'transfer_date', 'amount', 'reference', 'note', 'created_by'];

    protected $casts = ['transfer_date' => 'date', 'amount' => 'decimal:2'];

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

    public function from() { return $this->belongsTo(CrmBankAccount::class, 'from_account_id'); }

    public function to() { return $this->belongsTo(CrmBankAccount::class, 'to_account_id'); }

    public function creator() { return $this->belongsTo(CrmUser::class, 'created_by'); }
}
