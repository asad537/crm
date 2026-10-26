<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** A bank account or cash box (Cash in Hand, Petty Cash) with an opening balance; money movements are booked to it. */
class CrmBankAccount extends Model
{
    protected $table = 'crm_bank_accounts';

    const TYPES = ['bank' => 'Bank', 'cash' => 'Cash'];

    /** Payment-method groups an account can pick up automatically (see BankLedgerService::methodGroup). */
    const METHOD_GROUPS = [
        'Cash' => 'Cash',
        'Petty Cash' => 'Petty Cash',
        'Bank' => 'Bank Transfer / Company Account',
        'Card' => 'Card',
        'Cheque' => 'Cheque',
        'Other' => 'Other methods',
        'Not specified' => 'No method written',
    ];

    protected $fillable = [
        'workspace_id', 'name', 'type', 'bank_name', 'account_number', 'currency', 'opening_balance', 'opening_date',
        'auto_methods', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = ['opening_balance' => 'decimal:2', 'opening_date' => 'date', 'auto_methods' => 'array', 'is_active' => 'boolean'];

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

    /** The id if it is an account of this workspace, otherwise null — for "Paid from / Received in" form fields. */
    public static function validIdOrNull($value): ?int
    {
        return $value && static::whereKey((int) $value)->exists() ? (int) $value : null;
    }

    /** ['bank_account_id' => ...] when the form sent the "Bank / Cash Account" field, otherwise [] so an edit keeps the old account. */
    public static function fromRequest(\Illuminate\Http\Request $request): array
    {
        return $request->has('bank_account_id') ? ['bank_account_id' => static::validIdOrNull($request->input('bank_account_id'))] : [];
    }

    /** Accounts for the "Bank / Cash Account" dropdowns: active first, then closed ones (so an old entry keeps its account on edit). */
    public static function options()
    {
        static $cache = [];
        $key = \App\Support\CrmWorkspaceContext::id() ?: 0;

        return $cache[$key] ??= static::orderByDesc('is_active')->orderBy('type')->orderBy('name')->get(['id', 'name', 'type', 'is_active', 'auto_methods']);
    }

    public function formData(): array
    {
        return array_merge($this->only(['id', 'name', 'type', 'bank_name', 'account_number', 'currency', 'notes']), [
            'opening_balance' => (float) $this->opening_balance,
            'opening_date' => $this->opening_date->toDateString(),
            'auto_methods' => $this->auto_methods ?: [],
            'is_active' => (bool) $this->is_active,
        ]);
    }
}
