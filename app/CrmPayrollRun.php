<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/** One month's salary sheet: draft (editable) → approved (locked, payable) → paid (every line paid). */
class CrmPayrollRun extends Model
{
    protected $table = 'crm_payroll_runs';

    const STATUSES = ['draft' => 'Draft', 'approved' => 'Approved', 'paid' => 'Paid'];

    protected $fillable = ['workspace_id', 'period', 'status', 'approved_by', 'approved_at', 'notes', 'created_by'];

    protected $casts = ['approved_at' => 'datetime'];

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

    public function lines() { return $this->hasMany(CrmPayrollLine::class, 'payroll_run_id')->orderBy('employee_name'); }

    public function approver() { return $this->belongsTo(CrmUser::class, 'approved_by'); }

    public function creator() { return $this->belongsTo(CrmUser::class, 'created_by'); }

    public function periodStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->period . '-01')->startOfDay();
    }

    public function periodLabel(): string
    {
        return $this->periodStart()->format('F Y');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /** Approved sheets become "paid" once every line is paid, and drop back if a payment is undone. */
    public function refreshPaidStatus(): void
    {
        if ($this->isDraft()) return;
        $lines = $this->lines()->get();
        $allPaid = $lines->isNotEmpty() && $lines->every(fn ($line) => $line->paid_at !== null);
        $status = $allPaid ? 'paid' : 'approved';
        if ($status !== $this->status) $this->update(['status' => $status]);
    }
}
