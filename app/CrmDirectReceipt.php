<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Money received that is not a payment on one invoice. kind "income": other income (scrap sale, commission, refund...)
 * booked to an income category. kind "on_account": a customer paid without picking an invoice — it reduces what that
 * customer owes (or becomes their advance); it is not income.
 */
class CrmDirectReceipt extends Model
{
    protected $table = 'crm_direct_receipts';

    const ON_ACCOUNT_CATEGORY = 'Customer Payment (on account)';

    /** Suggested income categories; any other can be typed. */
    const CATEGORIES = ['Scrap Sales', 'Commission Received', 'Refund Received', 'Discount Received', 'Interest Income', 'Rental Income', 'Miscellaneous Income'];

    const METHODS = ['Cash', 'Petty Cash', 'Bank Transfer', 'Cheque', 'Card'];

    const PAYER_TYPES = ['customer' => 'Customer', 'other' => 'Other'];

    protected $fillable = [
        'workspace_id', 'receipt_date', 'kind', 'category', 'description', 'payer_type', 'payer_name', 'amount', 'currency',
        'method', 'bank_account_id', 'reference', 'note', 'proof_path', 'proof_name', 'created_by',
    ];

    protected $casts = ['receipt_date' => 'date', 'amount' => 'decimal:2'];

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

    public function getNumberAttribute(): string
    {
        return 'RCT-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    public function isOnAccount(): bool
    {
        return $this->kind === 'on_account';
    }

    /** e.g. "Two Thousand Dirhams Only", for the printed receipt. */
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

    public function formData(): array
    {
        return array_merge($this->only(['id', 'kind', 'category', 'description', 'payer_type', 'payer_name', 'currency', 'method', 'bank_account_id', 'reference', 'note', 'proof_name']), [
            'receipt_date' => $this->receipt_date->toDateString(),
            'amount' => (float) $this->amount,
            'number' => $this->number,
        ]);
    }
}
