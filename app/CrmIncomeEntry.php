<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** One month's due for an "other income" customer (rent, service charges...) and what was paid against it. */
class CrmIncomeEntry extends Model
{
    protected $table = 'crm_income_entries';

    protected $fillable = [
        'workspace_id', 'customer_id', 'income_head', 'period', 'due_date', 'amount_due', 'amount_paid',
        'paid_at', 'payment_method', 'reference', 'proof_path', 'note', 'created_by', 'paid_recorded_by',
    ];

    protected $casts = ['due_date' => 'date', 'paid_at' => 'date', 'amount_due' => 'decimal:2', 'amount_paid' => 'decimal:2'];

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

    public function customer() { return $this->belongsTo(CrmCustomer::class, 'customer_id'); }

    public function getBalanceAttribute(): float { return round(max(0, (float) $this->amount_due - (float) $this->amount_paid), 2); }

    /** paid | partial | overdue | due */
    public function getStatusAttribute(): string
    {
        if ($this->balance <= 0 && (float) $this->amount_due > 0) return 'paid';
        if ((float) $this->amount_paid > 0) return 'partial';
        return $this->due_date && $this->due_date->isPast() ? 'overdue' : 'due';
    }

    public function getPeriodLabelAttribute(): string
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $this->period)->format('M Y');
    }
}
