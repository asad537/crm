<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Capital an owner put into the business, or a drawing they took out. Cash / bank entries move money in that
 * bank or cash account; "Non-cash / Opening" ones (opening capital, assets brought in) move no money.
 */
class CrmCapitalEntry extends Model
{
    protected $table = 'crm_capital_entries';

    const TYPES = ['capital' => 'Capital In', 'drawing' => 'Drawing (Out)'];

    const NON_CASH = 'Non-cash / Opening';

    const METHODS = ['Cash', 'Petty Cash', 'Bank Transfer', 'Cheque', self::NON_CASH];

    protected $fillable = ['workspace_id', 'owner_id', 'entry_date', 'type', 'amount', 'method', 'bank_account_id', 'reference', 'note', 'created_by'];

    protected $casts = ['entry_date' => 'date', 'amount' => 'decimal:2'];

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

    public function owner() { return $this->belongsTo(CrmCapitalOwner::class, 'owner_id'); }

    public function creator() { return $this->belongsTo(CrmUser::class, 'created_by'); }

    public function bankAccount() { return $this->belongsTo(CrmBankAccount::class, 'bank_account_id'); }

    public function getNumberAttribute(): string
    {
        return ($this->type === 'drawing' ? 'DRW-' : 'CAP-') . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Moves money through a bank / cash account (not an opening or non-cash entry). */
    public function isCash(): bool
    {
        return $this->method !== self::NON_CASH;
    }

    public function formData(): array
    {
        return array_merge($this->only(['id', 'owner_id', 'type', 'method', 'bank_account_id', 'reference', 'note']), [
            'entry_date' => $this->entry_date->toDateString(),
            'amount' => (float) $this->amount,
            'number' => $this->number,
        ]);
    }
}
