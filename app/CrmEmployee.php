<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** A staff member on Al Massa's payroll (factory workers included — not a CRM login). */
class CrmEmployee extends Model
{
    protected $table = 'crm_employees';

    const DEPARTMENTS = ['Production', 'Printing', 'Finishing', 'Warehouse', 'Office', 'Accounts', 'Sales', 'Design', 'Driver', 'Other'];

    const PAYMENT_METHODS = ['Cash', 'Bank Transfer'];

    const SALARY_TYPES = ['monthly' => 'Monthly', 'daily' => 'Daily Wage'];

    protected $fillable = [
        'workspace_id', 'employee_code', 'name', 'designation', 'department', 'phone', 'nationality', 'joining_date',
        'salary_type', 'basic_salary', 'housing_allowance', 'transport_allowance', 'food_allowance', 'other_allowance',
        'overtime_rate', 'payment_method', 'bank_name', 'iban', 'bank_routing_code', 'mol_person_id', 'emirates_id', 'emirates_id_expiry', 'passport_no',
        'visa_expiry', 'status', 'left_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'joining_date' => 'date', 'emirates_id_expiry' => 'date', 'visa_expiry' => 'date', 'left_date' => 'date',
        'basic_salary' => 'decimal:2', 'housing_allowance' => 'decimal:2', 'transport_allowance' => 'decimal:2',
        'food_allowance' => 'decimal:2', 'other_allowance' => 'decimal:2', 'overtime_rate' => 'decimal:2',
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

    public function advances() { return $this->hasMany(CrmEmployeeAdvance::class, 'employee_id')->orderBy('given_date')->orderBy('id'); }

    public function payrollLines() { return $this->hasMany(CrmPayrollLine::class, 'employee_id'); }

    public function isDaily(): bool
    {
        return $this->salary_type === 'daily';
    }

    public function allowancesTotal(): float
    {
        return round((float) $this->housing_allowance + (float) $this->transport_allowance + (float) $this->food_allowance + (float) $this->other_allowance, 2);
    }

    /** Full monthly package (basic + allowances); for daily-wage staff this is just the daily rate. */
    public function packageLabel(): string
    {
        return $this->isDaily()
            ? number_format((float) $this->basic_salary, 2) . ' / day'
            : number_format((float) $this->basic_salary + $this->allowancesTotal(), 2) . ' / month';
    }

    public function advanceGiven(): float
    {
        return round((float) $this->advances()->sum('amount'), 2);
    }

    /** Only deductions on approved or paid salary sheets count as recovered. */
    public function advanceRecovered(): float
    {
        return round((float) $this->payrollLines()
            ->whereHas('run', fn ($q) => $q->whereIn('status', ['approved', 'paid']))
            ->sum('advance_deduction'), 2);
    }

    public function advanceBalance(): float
    {
        return max(0, round($this->advanceGiven() - $this->advanceRecovered(), 2));
    }

    /**
     * What to cut from this month's salary: recovered amounts are applied to the oldest advances first,
     * then each still-open advance contributes its monthly installment (or its whole remainder).
     */
    public function suggestedAdvanceDeduction(): float
    {
        $recovered = $this->advanceRecovered();
        $suggested = 0.0;
        foreach ($this->advances as $advance) {
            $remaining = (float) $advance->amount - min($recovered, (float) $advance->amount);
            $recovered = max(0, $recovered - (float) $advance->amount);
            if ($remaining > 0.009) {
                $suggested += $advance->installment ? min($remaining, (float) $advance->installment) : $remaining;
            }
        }

        return round($suggested, 2);
    }

    /** Values the Employees page needs to prefill its edit form. */
    public function formData(): array
    {
        $data = $this->only([
            'id', 'employee_code', 'name', 'designation', 'department', 'phone', 'nationality', 'salary_type', 'payment_method',
            'bank_name', 'iban', 'bank_routing_code', 'mol_person_id', 'emirates_id', 'passport_no', 'status', 'notes',
        ]);
        foreach (['basic_salary', 'housing_allowance', 'transport_allowance', 'food_allowance', 'other_allowance', 'overtime_rate'] as $field) {
            $data[$field] = (float) $this->$field;
        }
        foreach (['joining_date', 'emirates_id_expiry', 'visa_expiry', 'left_date'] as $field) {
            $data[$field] = $this->$field ? $this->$field->toDateString() : '';
        }

        return $data;
    }
}
