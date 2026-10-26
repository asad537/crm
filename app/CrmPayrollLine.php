<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** One employee's salary on a monthly sheet, with everything needed to print the payslip. */
class CrmPayrollLine extends Model
{
    protected $table = 'crm_payroll_lines';

    /** Monthly salaries are pro-rated on a 30-day month for absences. */
    const DAYS_PER_MONTH = 30;

    protected $fillable = [
        'payroll_run_id', 'employee_id', 'employee_name', 'designation', 'department', 'salary_type', 'basic_rate',
        'allowances', 'days_worked', 'absent_days', 'overtime_hours', 'overtime_rate', 'bonus', 'other_deductions',
        'advance_deduction', 'gross', 'absence_deduction', 'net', 'payment_method', 'note', 'paid_at', 'paid_method',
        'paid_reference', 'paid_by',
        'bank_account_id',
    ];

    protected $casts = [
        'basic_rate' => 'decimal:2', 'allowances' => 'decimal:2', 'days_worked' => 'decimal:2', 'absent_days' => 'decimal:2',
        'overtime_hours' => 'decimal:2', 'overtime_rate' => 'decimal:2', 'bonus' => 'decimal:2', 'other_deductions' => 'decimal:2',
        'advance_deduction' => 'decimal:2', 'gross' => 'decimal:2', 'absence_deduction' => 'decimal:2', 'net' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function run() { return $this->belongsTo(CrmPayrollRun::class, 'payroll_run_id'); }

    public function employee() { return $this->belongsTo(CrmEmployee::class, 'employee_id'); }

    public function payer() { return $this->belongsTo(CrmUser::class, 'paid_by'); }

    public function isDaily(): bool
    {
        return $this->salary_type === 'daily';
    }

    /** Basic pay for the month: the monthly basic, or daily rate × days worked. */
    public function basicPay(): float
    {
        return $this->isDaily() ? round((float) $this->basic_rate * (float) $this->days_worked, 2) : (float) $this->basic_rate;
    }

    public function overtimeAmount(): float
    {
        return round((float) $this->overtime_hours * (float) $this->overtime_rate, 2);
    }

    /** Fill gross, absence deduction and net from the inputs. */
    public function recalculate(): self
    {
        $absence = $this->isDaily() ? 0.0
            : round(((float) $this->basic_rate + (float) $this->allowances) / self::DAYS_PER_MONTH * (float) $this->absent_days, 2);
        $gross = round($this->basicPay() + (float) $this->allowances + $this->overtimeAmount() + (float) $this->bonus, 2);

        $this->gross = $gross;
        $this->absence_deduction = $absence;
        $this->net = round($gross - $absence - (float) $this->other_deductions - (float) $this->advance_deduction, 2);

        return $this;
    }

    public function totalDeductions(): float
    {
        return round((float) $this->absence_deduction + (float) $this->other_deductions + (float) $this->advance_deduction, 2);
    }

    /** Net pay in words for the payslip, e.g. "Twenty Thousand Dirhams and Fifty Fils Only"; null without the intl extension. */
    public function netInWords(): ?string
    {
        if (!class_exists(\NumberFormatter::class)) return null;
        $speller = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $dirhams = (int) floor((float) $this->net);
        $fils = (int) round(((float) $this->net - $dirhams) * 100);
        $words = ucwords(str_replace('-', ' ', $speller->format($dirhams))) . ' Dirhams';
        if ($fils > 0) $words .= ' and ' . ucwords(str_replace('-', ' ', $speller->format($fils))) . ' Fils';

        return $words . ' Only';
    }
}
