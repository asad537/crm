<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** A general company expense (rent, fuel, repairs...) recorded directly by accounts. */
class CrmExpense extends Model
{
    protected $table = 'crm_expenses';

    /** Suggested "what is it for" options; any other category can be typed. */
    const CATEGORIES = [
        'Rent', 'Salaries & Wages', 'Utilities', 'Transport & Fuel', 'Maintenance & Repairs',
        'Office Supplies', 'Raw Material', 'Telephone & Internet', 'Government Fees & Visa',
        'Food & Refreshments', 'Marketing', 'Other',
    ];

    const PAID_FROM = ['Cash', 'Petty Cash', 'Bank Transfer', 'Company Account', 'Card', 'Cheque'];

    const PAYEE_TYPES = ['supplier' => 'Supplier', 'customer' => 'Customer', 'other' => 'Other'];

    protected $fillable = [
        'workspace_id', 'expense_date', 'category', 'name', 'paid_from', 'payee_type', 'payee_name',
        'amount', 'currency', 'reference', 'note', 'proof_path', 'proof_name', 'created_by',
        'bank_account_id',
    ];

    protected $casts = ['expense_date' => 'date', 'amount' => 'decimal:2'];

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

    public function creator() { return $this->belongsTo(CrmUser::class, 'created_by'); }

    public function bankAccount() { return $this->belongsTo(CrmBankAccount::class, 'bank_account_id'); }

    /** e.g. "Twenty Thousand Dirhams Only", for the printed payment voucher. */
    public function amountInWords(): ?string
    {
        if (!class_exists(\NumberFormatter::class)) return null;
        [$major, $minor] = ['AED' => ['Dirhams', 'Fils'], 'USD' => ['Dollars', 'Cents'], 'GBP' => ['Pounds', 'Pence'], 'EUR' => ['Euros', 'Cents']][$this->currency] ?? [$this->currency, 'Cents'];
        $speller = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $whole = (int) floor((float) $this->amount);
        $cents = (int) round(((float) $this->amount - $whole) * 100);
        $words = ucwords(str_replace('-', ' ', $speller->format($whole))) . ' ' . $major;
        if ($cents > 0) $words .= ' and ' . ucwords(str_replace('-', ' ', $speller->format($cents))) . ' ' . $minor;

        return $words . ' Only';
    }

    public function getNumberAttribute(): string
    {
        return 'EXP-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getPayeeTypeLabelAttribute(): string
    {
        return self::PAYEE_TYPES[$this->payee_type] ?? 'Other';
    }

    /** Values the Expenses page needs to prefill its edit form. */
    public function formData(): array
    {
        return array_merge($this->only([
            'id', 'category', 'name', 'paid_from', 'payee_type', 'payee_name', 'currency', 'reference', 'note', 'proof_name', 'bank_account_id',
        ]), [
            'expense_date' => $this->expense_date->toDateString(),
            'amount' => (float) $this->amount,
            'number' => $this->number,
        ]);
    }
}
