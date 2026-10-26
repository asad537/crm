<?php

namespace Tests\Feature\Accounting;

use App\CrmDeletionLog;
use App\CrmEmployee;
use App\CrmEmployeeAdvance;
use App\CrmPayrollLine;
use App\CrmPayrollRun;
use App\CrmSetting;
use App\CrmWorkspace;
use App\Http\Controllers\Crm\PaymentController;
use App\Services\FinancialReportService;
use Carbon\Carbon;

/**
 * Salaries: monthly salary sheets (draft → approved → paid), line maths, payments, payslip / sheet / WPS files,
 * permissions, and how a sheet reaches the books (P&L, balance sheet, cash flow, Salary and Gratuity reports).
 */
class PayrollTest extends AccountingTestCase
{
    /** An employee on the test workspace (employee forms are covered by EmployeeTest). */
    protected function employee(string $name, array $attributes = []): CrmEmployee
    {
        return CrmEmployee::create(array_merge([
            'name' => $name.' '.uniqid(), 'designation' => 'Operator', 'department' => 'Production', 'salary_type' => 'monthly',
            'basic_salary' => 3000, 'housing_allowance' => 1000, 'transport_allowance' => 200, 'overtime_rate' => 15,
            'payment_method' => 'Cash', 'status' => 'active', 'joining_date' => '2024-01-01',
        ], $attributes));
    }

    protected function createSheet(string $period = '2026-10'): CrmPayrollRun
    {
        $this->post(route('crm.payroll.store'), ['period' => $period])->assertSessionHasNoErrors();

        return CrmPayrollRun::where('period', $period)->firstOrFail();
    }

    protected function lineOf(CrmPayrollRun $run, CrmEmployee $employee): CrmPayrollLine
    {
        return CrmPayrollLine::where('payroll_run_id', $run->id)->where('employee_id', $employee->id)->firstOrFail();
    }

    /** Save the draft sheet form: line id => fields (fields left out are sent empty, like the page does). */
    protected function saveSheet(CrmPayrollRun $run, array $lines)
    {
        $payload = [];
        foreach ($run->lines()->get() as $line) {
            $payload[$line->id] = array_merge([
                'days_worked' => (float) $line->days_worked ?: '', 'absent_days' => '', 'overtime_hours' => '', 'overtime_rate' => (float) $line->overtime_rate ?: '',
                'bonus' => '', 'other_deductions' => '', 'advance_deduction' => (float) $line->advance_deduction ?: '', 'note' => '',
            ], $lines[$line->id] ?? []);
        }

        return $this->from(route('crm.payroll.show', $run->id))->put(route('crm.payroll.update', $run->id), ['lines' => $payload]);
    }

    protected function approve(CrmPayrollRun $run)
    {
        return $this->from(route('crm.payroll.show', $run->id))->post(route('crm.payroll.approve', $run->id));
    }

    protected function payLine(CrmPayrollRun $run, CrmPayrollLine $line, array $overrides = [])
    {
        return $this->from(route('crm.payroll.show', $run->id))->post(route('crm.payroll.pay_line', [$run->id, $line->id]),
            array_merge(['paid_at' => '2026-10-26', 'paid_method' => 'Bank Transfer', 'paid_reference' => 'TRX-'.$line->id], $overrides));
    }

    protected function payAll(CrmPayrollRun $run, array $overrides = [])
    {
        return $this->from(route('crm.payroll.show', $run->id))->post(route('crm.payroll.pay_all', $run->id),
            array_merge(['paid_at' => '2026-10-26', 'paid_method' => 'Cash', 'paid_reference' => ''], $overrides));
    }

    /** One balance-sheet line (by its kind: "advances", "salary", "cash"...) as of the end of a day. */
    protected function sheetLine(string $kind, string $asOf = '2026-10-26'): float
    {
        $sheet = (new FinancialReportService(Carbon::parse('2026-01-01'), Carbon::parse($asOf)))->balanceSheetAsOf(Carbon::parse($asOf));

        return (float) collect(array_merge($sheet['assets'], $sheet['liabilities']))->where(3, $kind)->sum(1);
    }

    protected function card(array $report, string $label): float
    {
        $card = collect($report['cards'])->firstWhere('label', $label);
        $this->assertNotNull($card, 'No card '.$label);

        return (float) $card['value'];
    }

    /** A row of a report table by its first-column label (null when the report leaves the row out). */
    protected function reportRow(array $report, string $label, int $table = 0): ?array
    {
        foreach ($report['tables'][$table]['rows'] as $row) {
            $cells = $row['cells'] ?? $row;
            $text = is_array($cells[0]) ? $cells[0]['text'] : (string) $cells[0];
            if ($text === $label) return $cells;
        }

        return null;
    }

    /** Salaries & Wages on the P&L for a window (0 when the line is not there). */
    protected function salaryCost(string $from = '2026-10-01', string $to = '2026-10-26'): float
    {
        $row = $this->reportRow($this->report('profit-loss', $from, $to), 'Salaries & Wages');

        return $row ? (float) $row[1] : 0.0;
    }

    // ───────────────────────────── Creating a sheet ─────────────────────────────

    public function test_a_new_sheet_has_a_prefilled_line_for_each_eligible_employee(): void
    {
        $monthly = $this->employee('Monthly Ali', ['payment_method' => 'Bank Transfer', 'designation' => 'Printer', 'department' => 'Printing']);
        $daily = $this->employee('Daily Babar', ['salary_type' => 'daily', 'basic_salary' => 100, 'housing_allowance' => 0, 'transport_allowance' => 0, 'overtime_rate' => 10]);
        $midMonth = $this->employee('Joined Mid', ['basic_salary' => 2000, 'housing_allowance' => 0, 'transport_allowance' => 0, 'joining_date' => '2026-10-15']);
        $leftDuring = $this->employee('Left During', ['status' => 'left', 'left_date' => '2026-10-10']);
        $noJoining = $this->employee('No Joining Date', ['joining_date' => null]);
        $notYet = $this->employee('Joins Next Month', ['joining_date' => '2026-11-01']);
        $leftBefore = $this->employee('Left Before', ['status' => 'left', 'left_date' => '2026-09-20']);
        $leftNoDate = $this->employee('Left No Date', ['status' => 'left', 'left_date' => null]);
        $other = CrmWorkspace::create(['name' => 'Other Co', 'slug' => 'other-co-'.uniqid(), 'is_active' => true]);
        $stranger = CrmEmployee::create(['workspace_id' => $other->id, 'name' => 'Stranger '.uniqid(), 'salary_type' => 'monthly', 'basic_salary' => 5000,
            'payment_method' => 'Cash', 'status' => 'active']);

        $response = $this->post(route('crm.payroll.store'), ['period' => '2026-10']);
        $run = CrmPayrollRun::where('period', '2026-10')->firstOrFail();
        $response->assertRedirect(route('crm.payroll.show', $run->id))
            ->assertSessionHas('success', 'October 2026 salary sheet created with 5 employee(s). Check days, overtime and deductions, then approve.');

        $this->assertSame('draft', $run->status);
        $this->assertSame($this->workspace->id, (int) $run->workspace_id);
        $this->assertSame($this->admin->id, (int) $run->created_by);
        $this->assertNull($run->approved_at);

        $onSheet = $run->lines()->pluck('employee_id')->sort()->values()->all();
        $expected = collect([$monthly, $daily, $midMonth, $leftDuring, $noJoining])->pluck('id')->sort()->values()->all();
        $this->assertSame($expected, $onSheet, 'joined after the month, left before it, left without a date and other workspaces are not on the sheet');
        $this->assertFalse(CrmPayrollLine::where('payroll_run_id', $run->id)->whereIn('employee_id', [$notYet->id, $leftBefore->id, $leftNoDate->id, $stranger->id])->exists());

        // Monthly: basic + allowances, snapshots of name / designation / department / payment method.
        $line = $this->lineOf($run, $monthly);
        $this->assertSame($monthly->name, $line->employee_name);
        $this->assertSame('Printer', $line->designation);
        $this->assertSame('Printing', $line->department);
        $this->assertSame('monthly', $line->salary_type);
        $this->assertSame('Bank Transfer', $line->payment_method);
        $this->assertMoney(3000, $line->basic_rate);
        $this->assertMoney(1200, $line->allowances);
        $this->assertMoney(0, $line->days_worked);
        $this->assertMoney(15, $line->overtime_rate);
        $this->assertMoney(4200, $line->gross);
        $this->assertMoney(0, $line->absence_deduction);
        $this->assertMoney(4200, $line->net);
        $this->assertNull($line->paid_at);

        // Daily wage: 26 days pre-filled at the day rate.
        $line = $this->lineOf($run, $daily);
        $this->assertSame('daily', $line->salary_type);
        $this->assertMoney(26, $line->days_worked);
        $this->assertMoney(100, $line->basic_rate);
        $this->assertMoney(2600, $line->gross);
        $this->assertMoney(2600, $line->net);

        // No pro-rata: someone who joined on the 15th (or left on the 10th) starts with a full month; absent days are entered by hand.
        $this->assertMoney(2000, $this->lineOf($run, $midMonth)->net);
        $this->assertMoney(4200, $this->lineOf($run, $leftDuring)->net);

        $show = $this->get(route('crm.payroll.show', $run->id))->assertOk()->assertSee($monthly->name)->assertSee($daily->name)
            ->assertViewHas('missingCount', 0)->assertViewHas('canManage', true);
        $this->assertSame(5, $show->viewData('run')->lines->count());

        $index = $this->get(route('crm.payroll.index'))->assertOk()->assertSee('October 2026');
        $this->assertMoney(4200 + 2600 + 2000 + 4200 + 4200, $index->viewData('totals')['year']);
        $this->assertMoney(0, $index->viewData('totals')['unpaid'], 'a draft is not owed yet');
        $this->assertSame(5, $index->viewData('totals')['employees'], 'active staff, including the one joining next month');
    }

    public function test_only_one_sheet_per_month(): void
    {
        $this->employee('Only Once');
        $run = $this->createSheet('2026-10');

        $this->post(route('crm.payroll.store'), ['period' => '2026-10'])
            ->assertRedirect(route('crm.payroll.show', $run->id))
            ->assertSessionHas('error', 'The October 2026 salary sheet already exists.');
        $this->assertSame(1, CrmPayrollRun::where('period', '2026-10')->count());
        $this->assertSame(1, CrmPayrollLine::where('payroll_run_id', $run->id)->count());

        foreach (['', '2026-13', '10-2026', '2026/10', 'October'] as $bad) {
            $this->from(route('crm.payroll.index'))->post(route('crm.payroll.store'), ['period' => $bad])->assertSessionHasErrors('period');
        }
        $this->assertSame(1, CrmPayrollRun::count());

        // Another month is fine, and another workspace's sheet for the same month does not block ours.
        $this->createSheet('2026-09');
        $other = CrmWorkspace::create(['name' => 'Other Co', 'slug' => 'other-co-'.uniqid(), 'is_active' => true]);
        CrmPayrollRun::create(['workspace_id' => $other->id, 'period' => '2026-08', 'status' => 'draft']);
        $this->createSheet('2026-08');
        $this->assertSame(['2026-08', '2026-09', '2026-10'], CrmPayrollRun::orderBy('period')->pluck('period')->all());
    }

    // ───────────────────────────── Editing a draft ─────────────────────────────

    public function test_editing_a_draft_recalculates_each_line(): void
    {
        $monthly = $this->employee('Edit Monthly');
        $daily = $this->employee('Edit Daily', ['salary_type' => 'daily', 'basic_salary' => 100, 'housing_allowance' => 0, 'transport_allowance' => 0, 'overtime_rate' => 0]);
        $run = $this->createSheet();
        $m = $this->lineOf($run, $monthly);
        $d = $this->lineOf($run, $daily);

        $this->saveSheet($run, [
            $m->id => ['absent_days' => '3', 'overtime_hours' => '10', 'overtime_rate' => '15', 'bonus' => '200', 'other_deductions' => '50', 'note' => 'Late 3 days'],
            $d->id => ['days_worked' => '20', 'absent_days' => '2', 'overtime_hours' => '4', 'overtime_rate' => '12.5'],
        ])->assertRedirect(route('crm.payroll.show', $run->id))->assertSessionHas('success', 'Salary sheet saved.');

        // Monthly: 1/30 of (basic + allowances) per absent day.
        $m->refresh();
        $this->assertMoney(3, $m->absent_days);
        $this->assertMoney(150, $m->overtimeAmount());
        $this->assertMoney(4550, $m->gross);                   // 3000 + 1200 + 150 OT + 200 bonus
        $this->assertMoney(420, $m->absence_deduction);        // 4200 / 30 × 3
        $this->assertMoney(4080, $m->net);                     // 4550 − 420 − 50
        $this->assertMoney(470, $m->totalDeductions());
        $this->assertSame('Late 3 days', $m->note);

        // Daily: day rate × days worked + overtime; absent days do not cut a daily wage.
        $d->refresh();
        $this->assertMoney(2000, $d->basicPay());
        $this->assertMoney(2050, $d->gross);
        $this->assertMoney(0, $d->absence_deduction);
        $this->assertMoney(2050, $d->net);

        $show = $this->get(route('crm.payroll.show', $run->id))->assertOk()->assertSee('4,080.00')->assertSee('6,130.00');
        $this->assertMoney(6130, $show->viewData('run')->lines->sum('net'));

        // A deduction that would make the net negative is refused and nothing is saved.
        $this->saveSheet($run, [$m->id => ['other_deductions' => '99999'], $d->id => ['days_worked' => '5']])
            ->assertSessionHasErrors('lines');
        $this->assertMoney(4080, $m->fresh()->net);
        $this->assertMoney(20, $d->fresh()->days_worked, 'the other line is not saved either');

        // Field limits.
        $this->saveSheet($run, [$d->id => ['days_worked' => '32']])->assertSessionHasErrors('lines.'.$d->id.'.days_worked');
        $this->saveSheet($run, [$m->id => ['bonus' => '-1']])->assertSessionHasErrors('lines.'.$m->id.'.bonus');
        $this->from(route('crm.payroll.show', $run->id))->put(route('crm.payroll.update', $run->id), [])->assertSessionHasErrors('lines');
        $this->assertMoney(4080, $m->fresh()->net);

        // Clearing the fields puts the line back to the plain salary.
        $this->saveSheet($run, [$m->id => ['overtime_rate' => '']])->assertSessionHasNoErrors();
        $this->assertMoney(4200, $m->fresh()->net);
        $this->assertNull($m->fresh()->note);
    }

    public function test_lines_can_be_removed_and_missing_employees_added_back(): void
    {
        $a = $this->employee('Stays');
        $b = $this->employee('Removed');
        $run = $this->createSheet();
        $line = $this->lineOf($run, $b);

        $this->from(route('crm.payroll.show', $run->id))->delete(route('crm.payroll.remove_line', [$run->id, $line->id]))
            ->assertSessionHas('success', $b->name.' removed from this sheet.');
        $this->assertNull(CrmPayrollLine::find($line->id));
        $this->assertSame([$a->id], $run->lines()->pluck('employee_id')->all());
        $this->get(route('crm.payroll.show', $run->id))->assertOk()->assertViewHas('missingCount', 1);

        // Someone added after the sheet was made, then "Add missing" brings in both.
        $c = $this->employee('Hired Later', ['basic_salary' => 2500, 'housing_allowance' => 0, 'transport_allowance' => 0]);
        $this->from(route('crm.payroll.show', $run->id))->post(route('crm.payroll.add_missing', $run->id))
            ->assertSessionHas('success', '2 employee(s) added to the sheet.');
        $this->assertSame(3, $run->lines()->count());
        $this->assertMoney(2500, $this->lineOf($run, $c)->net);
        $this->from(route('crm.payroll.show', $run->id))->post(route('crm.payroll.add_missing', $run->id))
            ->assertSessionHas('success', '0 employee(s) added to the sheet.');
        $this->assertSame(3, $run->lines()->count());

        // A line of another sheet cannot be removed through this one.
        $september = $this->createSheet('2026-09');
        $otherLine = $this->lineOf($september, $a);
        $this->delete(route('crm.payroll.remove_line', [$run->id, $otherLine->id]))->assertNotFound();
        $this->assertNotNull(CrmPayrollLine::find($otherLine->id));
    }

    public function test_a_draft_sheet_can_be_deleted(): void
    {
        $this->employee('Gone With Sheet');
        $run = $this->createSheet();
        $lineIds = $run->lines()->pluck('id');

        $this->delete(route('crm.payroll.destroy', $run->id))
            ->assertRedirect(route('crm.payroll.index'))->assertSessionHas('success', 'October 2026 salary sheet deleted.');
        $this->assertNull(CrmPayrollRun::find($run->id));
        $this->assertSame(0, CrmPayrollLine::whereIn('id', $lineIds)->count());
        $log = CrmDeletionLog::where('entity_type', 'salary_sheet')->where('entity_id', $run->id)->firstOrFail();
        $this->assertSame(1, $log->snapshot['employees']);
        $this->assertMoney(4200, $log->snapshot['net']);
    }

    // ───────────────────────────── Approve / reopen ─────────────────────────────

    public function test_approving_locks_the_sheet(): void
    {
        $a = $this->employee('Locked');
        $run = $this->createSheet();
        $line = $this->lineOf($run, $a);

        $this->approve($run)->assertRedirect(route('crm.payroll.show', $run->id))
            ->assertSessionHas('success', 'October 2026 salaries approved. You can now mark them paid.');
        $run->refresh();
        $this->assertSame('approved', $run->status);
        $this->assertSame($this->admin->id, (int) $run->approved_by);
        $this->assertSame('2026-10-26 10:00:00', $run->approved_at->toDateTimeString());
        $this->get(route('crm.payroll.show', $run->id))->assertOk()->assertSee('approved by '.$this->admin->name.' on 26 Oct 2026');

        // Nothing on an approved sheet can change.
        $this->saveSheet($run, [$line->id => ['bonus' => '500']])->assertStatus(422);
        $this->post(route('crm.payroll.add_missing', $run->id))->assertStatus(422);
        $this->delete(route('crm.payroll.remove_line', [$run->id, $line->id]))->assertStatus(422);
        $this->delete(route('crm.payroll.destroy', $run->id))->assertStatus(422);
        $this->approve($run)->assertStatus(422);
        $this->assertMoney(4200, $line->fresh()->net);
        $this->assertNotNull(CrmPayrollRun::find($run->id));
    }

    public function test_a_sheet_without_employees_cannot_be_approved(): void
    {
        $run = $this->createSheet();
        $this->assertSame(0, $run->lines()->count());

        $this->approve($run)->assertSessionHas('error', 'The sheet has no employees.');
        $this->assertSame('draft', $run->fresh()->status);
    }

    public function test_an_advance_cut_above_the_open_advance_blocks_approval(): void
    {
        $a = $this->employee('Over Cut');
        $a->advances()->create(['given_date' => '2026-10-02', 'amount' => 300, 'method' => 'Cash']);
        $run = $this->createSheet();
        $line = $this->lineOf($run, $a);
        $this->assertMoney(300, $line->advance_deduction, 'the open advance is pre-filled');

        $this->saveSheet($run, [$line->id => ['advance_deduction' => '500']])->assertSessionHasNoErrors();
        $this->approve($run)->assertSessionHas('error', 'Advance deduction is more than the open advance for: '.$a->name.'.');
        $this->assertSame('draft', $run->fresh()->status);

        $this->saveSheet($run, [$line->id => ['advance_deduction' => '300']]);
        $this->approve($run)->assertSessionHas('success');
        $this->assertSame('approved', $run->fresh()->status);
        $this->assertMoney(0, $a->fresh()->advanceBalance());
    }

    public function test_reopening_returns_an_unpaid_sheet_to_draft(): void
    {
        $a = $this->employee('Reopen A');
        $b = $this->employee('Reopen B');
        $run = $this->createSheet();
        $this->approve($run);

        $this->from(route('crm.payroll.show', $run->id))->post(route('crm.payroll.reopen', $run->id))
            ->assertSessionHas('success', 'Salary sheet is back to draft and can be edited.');
        $run->refresh();
        $this->assertSame('draft', $run->status);
        $this->assertNull($run->approved_by);
        $this->assertNull($run->approved_at);
        $this->saveSheet($run, [$this->lineOf($run, $a)->id => ['bonus' => '100']])->assertSessionHasNoErrors();
        $this->assertMoney(4300, $this->lineOf($run, $a)->net);

        // Once a salary is paid the sheet stays approved until that payment is undone.
        $this->approve($run);
        $this->payLine($run, $this->lineOf($run, $a))->assertSessionHasNoErrors();
        $this->from(route('crm.payroll.show', $run->id))->post(route('crm.payroll.reopen', $run->id))
            ->assertSessionHas('error', 'Some salaries are already paid. Undo those payments first.');
        $this->assertSame('approved', $run->fresh()->status);
        $this->assertNotNull($run->fresh()->approved_at);
        $this->assertNull($this->lineOf($run, $b)->paid_at);
    }

    // ───────────────────────────── Paying ─────────────────────────────

    public function test_salaries_are_paid_one_by_one_or_all_at_once_and_can_be_undone(): void
    {
        $a = $this->employee('Pay A');
        $b = $this->employee('Pay B', ['salary_type' => 'daily', 'basic_salary' => 100, 'housing_allowance' => 0, 'transport_allowance' => 0]);
        $run = $this->createSheet();
        $la = $this->lineOf($run, $a);
        $lb = $this->lineOf($run, $b);

        // A draft cannot be paid.
        $this->payLine($run, $la)->assertStatus(422);
        $this->payAll($run)->assertStatus(422);
        $this->assertNull($la->fresh()->paid_at);

        $this->approve($run);
        $this->payLine($run, $la, ['paid_at' => '2026-10-27'])->assertSessionHasErrors('paid_at');     // tomorrow
        $this->payLine($run, $la, ['paid_at' => ''])->assertSessionHasErrors('paid_at');
        $this->payLine($run, $la, ['paid_method' => 'Cheque'])->assertSessionHasErrors('paid_method');
        $this->payLine($run, $la, ['paid_reference' => str_repeat('x', 121)])->assertSessionHasErrors('paid_reference');
        $this->assertNull($la->fresh()->paid_at);

        // Pay one line.
        $this->payLine($run, $la, ['paid_at' => '2026-10-25', 'paid_method' => 'Bank Transfer', 'paid_reference' => 'TRX-991'])
            ->assertSessionHas('success', $la->employee_name.' — 4,200.00 marked paid. It now shows under Payments.');
        $la->refresh();
        $this->assertSame('2026-10-25', $la->paid_at->toDateString());
        $this->assertSame('Bank Transfer', $la->paid_method);
        $this->assertSame('TRX-991', $la->paid_reference);
        $this->assertSame($this->admin->id, (int) $la->paid_by);
        $this->assertSame('approved', $run->fresh()->status, 'still approved while someone is unpaid');
        $this->payLine($run, $la)->assertNotFound();                                               // already paid

        $salaryRows = app(PaymentController::class)->payments()->where('source', 'salary');
        $this->assertCount(1, $salaryRows);
        $this->assertMoney(4200, $salaryRows->first()->amount);
        $this->assertSame('2026-10-25', $salaryRows->first()->date->toDateString());
        $this->assertSame('Bank Transfer', $salaryRows->first()->method);
        $index = $this->get(route('crm.payroll.index'))->assertOk();
        $this->assertMoney(2600, $index->viewData('totals')['unpaid']);

        // Pay the rest.
        $this->payAll($run)->assertSessionHas('success', '1 salary(ies) totalling 2,600.00 marked paid.');
        $lb->refresh();
        $this->assertSame('2026-10-26', $lb->paid_at->toDateString());
        $this->assertSame('Cash', $lb->paid_method);
        $this->assertSame('TRX-991', $la->fresh()->paid_reference, 'pay all does not touch lines already paid');
        $this->assertSame('paid', $run->fresh()->status);
        $this->assertCount(2, app(PaymentController::class)->payments()->where('source', 'salary'));
        $this->get(route('crm.payroll.show', $run->id))->assertOk();

        // Undo one payment: the sheet drops back to approved and the payment leaves the register.
        $this->from(route('crm.payroll.show', $run->id))->delete(route('crm.payroll.unpay_line', [$run->id, $lb->id]))
            ->assertSessionHas('success', 'Payment undone for '.$lb->employee_name.'.');
        $lb->refresh();
        $this->assertNull($lb->paid_at);
        $this->assertNull($lb->paid_method);
        $this->assertNull($lb->paid_by);
        $this->assertSame('approved', $run->fresh()->status);
        $this->assertCount(1, app(PaymentController::class)->payments()->where('source', 'salary'));
        $log = CrmDeletionLog::where('entity_type', 'salary_payment')->where('entity_id', $lb->id)->firstOrFail();
        $this->assertMoney(2600, $log->snapshot['net']);
    }

    // ───────────────────────────── Files ─────────────────────────────

    public function test_payslip_and_salary_sheet_pdfs_download(): void
    {
        $a = $this->employee('Slip Person');
        $run = $this->createSheet();
        $line = $this->lineOf($run, $a);

        $slip = $this->get(route('crm.payroll.payslip', [$run->id, $line->id]));
        $slip->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $slip->getContent());
        $this->assertStringContainsString('payslip-'.\Illuminate\Support\Str::slug($line->employee_name).'-2026-10.pdf', $slip->headers->get('Content-Disposition'));

        $this->approve($run);
        $sheet = $this->get(route('crm.payroll.sheet_pdf', $run->id));
        $sheet->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $sheet->getContent());
        $this->assertStringStartsWith('attachment;', $sheet->headers->get('Content-Disposition'));
        $this->assertStringContainsString('salary-sheet-2026-10.pdf', $sheet->headers->get('Content-Disposition'));

        // A line of another sheet is not reachable through this one.
        $september = $this->createSheet('2026-09');
        $this->get(route('crm.payroll.payslip', [$run->id, $this->lineOf($september, $a)->id]))->assertNotFound();
    }

    public function test_the_wps_salary_file_lists_bank_transfer_staff(): void
    {
        $bank = $this->employee('Wps Bank', ['payment_method' => 'Bank Transfer', 'iban' => 'ae07 0331 2345 6789 0123 456',
            'bank_routing_code' => '302620122', 'mol_person_id' => '1000 1234 5678 90']);
        $cash = $this->employee('Wps Cash', ['payment_method' => 'Cash']);
        $zero = $this->employee('Wps Zero', ['payment_method' => 'Bank Transfer', 'basic_salary' => 1000, 'housing_allowance' => 0, 'transport_allowance' => 0]);
        $run = $this->createSheet();
        $lb = $this->lineOf($run, $bank);
        $this->saveSheet($run, [
            $lb->id => ['absent_days' => '2', 'overtime_hours' => '10', 'overtime_rate' => '15', 'bonus' => '200'],
            $this->lineOf($run, $zero)->id => ['other_deductions' => '1000'],
        ])->assertSessionHasNoErrors();
        $this->assertMoney(4270, $lb->fresh()->net);              // 4200 + 150 + 200 − 280 absence
        $this->approve($run)->assertSessionHas('success');

        $wps = fn (array $data) => $this->from(route('crm.payroll.show', $run->id))->post(route('crm.payroll.wps', $run->id), $data);
        $wps([])->assertSessionHasErrors(['establishment_id', 'employer_routing_code']);

        $response = $wps(['establishment_id' => ' 1234567890123 ', 'employer_routing_code' => '803320101']);
        $response->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame('attachment; filename="1234567890123261026100000.SIF"', $response->headers->get('Content-Disposition'));
        // One EDR per bank-transfer employee with pay (cash and zero-net staff left out), then the SCR control line.
        $this->assertSame(
            "EDR,10001234567890,302620122,AE070331234567890123456,2026-10-01,2026-10-31,31,3920.00,350.00,2\r\n"
            ."SCR,1234567890123,803320101,2026-10-26,1000,102026,1,4270.00,AED,Salary Oct 2026\r\n",
            $response->getContent()
        );
        $this->assertSame('1234567890123', CrmSetting::get('wps_establishment_id'), 'remembered for next month');
        $this->assertSame('803320101', CrmSetting::get('wps_employer_routing_code'));

        // A bank-transfer employee without IBAN / routing / MOL ID blocks the file.
        $bank->update(['iban' => null]);
        $wps(['establishment_id' => '1234567890123', 'employer_routing_code' => '803320101'])
            ->assertRedirect(route('crm.payroll.show', $run->id))
            ->assertSessionHas('error', 'Add IBAN, Bank Routing Code and MOL Person ID on Employees for: '.$bank->name.'.');

        // Nobody paid by bank: nothing to send.
        $cashOnly = $this->createSheet('2026-09');
        CrmPayrollLine::where('payroll_run_id', $cashOnly->id)->update(['payment_method' => 'Cash']);
        $this->from(route('crm.payroll.show', $cashOnly->id))->post(route('crm.payroll.wps', $cashOnly->id), ['establishment_id' => '1', 'employer_routing_code' => '2'])
            ->assertSessionHas('error', 'No one on the September 2026 sheet is paid by Bank Transfer, so there is nothing for the WPS file.');
        $this->assertNotNull($cash);
    }

    /** Current behaviour (open question): the page only offers the WPS file on an approved sheet, but the route also accepts a draft. */
    public function test_the_wps_file_can_currently_be_made_from_a_draft_sheet(): void
    {
        $this->employee('Wps Draft', ['payment_method' => 'Bank Transfer', 'iban' => 'AE070331234567890123456', 'bank_routing_code' => '302620122', 'mol_person_id' => '10001234567890']);
        $run = $this->createSheet();
        $this->assertSame('draft', $run->status);

        $this->post(route('crm.payroll.wps', $run->id), ['establishment_id' => '1234567890123', 'employer_routing_code' => '803320101'])
            ->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="1234567890123261026100000.SIF"');
    }

    // ───────────────────────────── Permissions ─────────────────────────────

    public function test_a_sales_agent_cannot_open_or_change_salaries(): void
    {
        $a = $this->employee('Secret Salary');
        $run = $this->createSheet();
        $line = $this->lineOf($run, $a);

        $this->signIn($this->makeUser('sales'));
        $this->get(route('crm.payroll.index'))->assertForbidden();
        $this->get(route('crm.payroll.show', $run->id))->assertForbidden();
        $this->post(route('crm.payroll.store'), ['period' => '2026-09'])->assertForbidden();
        $this->put(route('crm.payroll.update', $run->id), ['lines' => [$line->id => ['bonus' => 9999]]])->assertForbidden();
        $this->post(route('crm.payroll.add_missing', $run->id))->assertForbidden();
        $this->delete(route('crm.payroll.remove_line', [$run->id, $line->id]))->assertForbidden();
        $this->post(route('crm.payroll.approve', $run->id))->assertForbidden();
        $this->get(route('crm.payroll.payslip', [$run->id, $line->id]))->assertForbidden();
        $this->get(route('crm.payroll.sheet_pdf', $run->id))->assertForbidden();
        $this->delete(route('crm.payroll.destroy', $run->id))->assertForbidden();

        $this->assertSame(1, CrmPayrollRun::count());
        $this->assertMoney(4200, $line->fresh()->net);
        $this->assertSame('draft', $run->fresh()->status);
    }

    public function test_a_sales_manager_prepares_sheets_but_only_admin_or_accounts_approve_and_pay(): void
    {
        $a = $this->employee('Managed');
        $this->signIn($this->makeUser('sales_manager'));
        $this->get(route('crm.payroll.index'))->assertOk()->assertViewHas('canManage', false);
        $run = $this->createSheet();
        $line = $this->lineOf($run, $a);
        $this->saveSheet($run, [$line->id => ['bonus' => '100']])->assertSessionHasNoErrors();
        $this->get(route('crm.payroll.show', $run->id))->assertOk()->assertViewHas('canManage', false);
        $this->get(route('crm.payroll.payslip', [$run->id, $line->id]))->assertOk();

        $this->approve($run)->assertForbidden();
        $this->delete(route('crm.payroll.destroy', $run->id))->assertForbidden();
        $this->assertSame('draft', $run->fresh()->status);

        // Accounts approves; the manager still cannot pay, undo, reopen or make the WPS file.
        $this->signIn($this->makeUser('accounts'));
        $this->approve($run)->assertSessionHas('success');
        $this->signIn($this->makeUser('sales_manager'));
        $this->payLine($run, $line)->assertForbidden();
        $this->payAll($run)->assertForbidden();
        $this->post(route('crm.payroll.reopen', $run->id))->assertForbidden();
        $this->post(route('crm.payroll.wps', $run->id), ['establishment_id' => '1', 'employer_routing_code' => '2'])->assertForbidden();
        $this->assertNull($line->fresh()->paid_at);

        $this->signIn($this->makeUser('accounts'));
        $this->payAll($run)->assertSessionHas('success');
        $this->assertSame('paid', $run->fresh()->status);
        $this->signIn($this->makeUser('sales_manager'));
        $this->delete(route('crm.payroll.unpay_line', [$run->id, $line->id]))->assertForbidden();
        $this->assertNotNull($line->fresh()->paid_at);
    }

    // ───────────────────────────── The books ─────────────────────────────

    public function test_an_approved_sheet_reaches_the_profit_and_loss_balance_sheet_cash_flow_and_salary_report(): void
    {
        $a = $this->employee('Books A', ['department' => 'Printing']);
        $b = $this->employee('Books B', ['salary_type' => 'daily', 'basic_salary' => 100, 'housing_allowance' => 0, 'transport_allowance' => 0, 'department' => 'Production']);
        $this->from(route('crm.employees.index'))->post(route('crm.employees.advances.store', $a->id),
            ['given_date' => '2026-10-05', 'amount' => '1000', 'method' => 'Cash'])->assertSessionHasNoErrors();
        $this->assertMoney(1000, $this->sheetLine('advances'));

        $run = $this->createSheet();
        $la = $this->lineOf($run, $a);
        $lb = $this->lineOf($run, $b);
        $this->assertMoney(1000, $la->advance_deduction, 'the open advance is cut from the next salary');
        $this->saveSheet($run, [$la->id => ['absent_days' => '1', 'other_deductions' => '100', 'advance_deduction' => '1000']])->assertSessionHasNoErrors();
        $la->refresh();
        $this->assertMoney(140, $la->absence_deduction);
        $this->assertMoney(2960, $la->net);                       // 4200 − 140 − 100 − 1000 advance
        $this->assertMoney(2600, $lb->fresh()->net);

        // Draft: not in the books yet.
        $this->assertMoney(0, $this->salaryCost());
        $this->assertMoney(0, $this->sheetLine('salary'));
        $this->assertMoney(1000, $this->sheetLine('advances'));
        $this->assertMoney(0, $this->card($this->report('salaries'), 'Net Salaries'));

        // Approved: the cost is gross less absence and other deductions (the advance part is the loan being repaid, still salary).
        $this->approve($run)->assertSessionHas('success');
        $this->assertMoney(6560, $this->salaryCost());                                    // (2960 + 1000) + 2600
        $this->assertMoney(0, $this->salaryCost('2026-09-01', '2026-09-30'), 'October salaries are not in September');
        $pl = $this->report('profit-loss', '2026-10-01', '2026-10-26');
        $this->assertMoney(6560, $this->card($pl, 'Total Costs'));
        $this->assertMoney(-6560, $this->card($pl, 'Net Profit / (Loss)'));
        $this->assertMoney(5560, $this->sheetLine('salary'), 'Salaries Payable = net of the approved sheet');
        $this->assertMoney(0, $this->sheetLine('advances'), 'the advance is recovered through the approved salary');
        $sheet = $this->report('balance-sheet');
        $this->assertMoney(5560, $this->reportRow($sheet, 'Salaries Payable (approved, unpaid)')[1]);
        $this->assertMoney(0, $this->card($sheet, 'Not yet explained'), 'salary entries keep the balance sheet balanced');

        $salaries = $this->report('salaries');
        $this->assertMoney(5560, $this->card($salaries, 'Net Salaries'));
        $this->assertMoney(0, $this->card($salaries, 'Paid'));
        $this->assertMoney(5560, $this->card($salaries, 'Approved, Not Paid'));
        $this->assertMoney(0, $this->card($salaries, 'Open Advances'));
        $month = $salaries['tables'][0]['rows'];
        $this->assertCount(1, $month);
        $this->assertSame('Oct 2026', $month[0][0]);
        $this->assertSame(2, $month[0][1]);
        $this->assertMoney(6800, $month[0][2]);                  // gross 4200 + 2600
        $this->assertMoney(1240, $month[0][3]);                  // 140 + 100 + 1000
        $this->assertMoney(5560, $month[0][4]);
        $this->assertMoney(5560, $month[0][6]);
        $departments = collect($salaries['tables'][1]['rows'])->keyBy(0);
        $this->assertMoney(2960, $departments['Printing'][3]);
        $this->assertMoney(2600, $departments['Production'][3]);
        $advances = $salaries['tables'][2]['rows'];
        $this->assertSame([$a->name, 1000.0, 1000.0, 0.0], $advances[0]);

        // Paying moves it from Salaries Payable to cash out.
        $this->payLine($run, $la, ['paid_at' => '2026-10-20'])->assertSessionHasNoErrors();
        $this->assertMoney(2600, $this->sheetLine('salary'));
        $flow = $this->report('cash-flow', '2026-10-01', '2026-10-26');
        $this->assertMoney(-2960, $this->reportRow($flow, 'Salaries paid')[1]);
        $this->assertMoney(-1000, $this->reportRow($flow, 'Salary advances given')[1]);

        $this->payAll($run)->assertSessionHasNoErrors();
        $this->assertMoney(0, $this->sheetLine('salary'));
        $flow = $this->report('cash-flow', '2026-10-01', '2026-10-26');
        $this->assertMoney(-5560, $this->reportRow($flow, 'Salaries paid')[1]);
        $this->assertMoney(-6560, $this->card($flow, 'Net Cash from Operations'));
        $this->assertMoney(-6560, $this->card($flow, 'Net Change in Cash'));
        $this->assertMoney(6560, $this->salaryCost(), 'paying does not change the cost');
        $salaries = $this->report('salaries');
        $this->assertMoney(5560, $this->card($salaries, 'Paid'));
        $this->assertMoney(0, $this->card($salaries, 'Approved, Not Paid'));
        $this->assertMoney(0, $this->card($this->report('balance-sheet'), 'Not yet explained'));
        $this->assertMoney(0, $this->card($this->report('trial-balance'), 'Difference'));

        // Expense Analysis shows the same salary cost; the Day Book lists the advance and both salary payments as money out.
        $expenses = $this->report('expenses', '2026-10-01', '2026-10-26');
        $this->assertMoney(6560, $this->card($expenses, 'Total Spend'));
        $this->assertMoney(6560, $this->reportRow($expenses, 'Salaries & Wages')[1]);
        $dayBook = $this->report('day-book', '2026-10-01', '2026-10-26');
        $this->assertSame(3, (int) $this->card($dayBook, 'Entries'));
        $this->assertMoney(6560, $this->card($dayBook, 'Money Out'));
        $this->assertMoney(0, $this->card($dayBook, 'Money In'));
    }

    public function test_salaries_and_advances_paid_from_a_bank_account_come_off_its_balance(): void
    {
        $bank = \App\CrmBankAccount::create(['name' => 'Salary Account '.uniqid(), 'type' => 'bank', 'currency' => 'AED', 'opening_balance' => 50000,
            'opening_date' => '2026-01-01', 'is_active' => true]);
        $a = $this->employee('Bank Paid');
        $this->from(route('crm.employees.index'))->post(route('crm.employees.advances.store', $a->id),
            ['given_date' => '2026-10-05', 'amount' => '500', 'method' => 'Bank Transfer', 'bank_account_id' => $bank->id])->assertSessionHasNoErrors();
        $this->assertSame($bank->id, (int) CrmEmployeeAdvance::where('employee_id', $a->id)->value('bank_account_id'));

        $run = $this->createSheet();
        $line = $this->lineOf($run, $a);
        $this->approve($run);
        $this->payLine($run, $line, ['bank_account_id' => $bank->id])->assertSessionHasNoErrors();
        $this->assertSame($bank->id, (int) $line->fresh()->bank_account_id);

        $balance = fn () => (new \App\Services\BankLedgerService())->balances(Carbon::parse('2026-10-26'))['accounts'][$bank->id]['balance'];
        $this->assertMoney(50000 - 500 - 3700, $balance());                   // net 4200 − 500 advance cut
        $this->assertMoney(50000 - 500 - 3700, $this->sheetLine('cash'));

        // Undoing the payment puts the money back on the account.
        $this->delete(route('crm.payroll.unpay_line', [$run->id, $line->id]))->assertSessionHas('success');
        $this->assertNull($line->fresh()->bank_account_id);
        $this->assertMoney(50000 - 500, $balance());
    }

    public function test_reopening_takes_the_sheet_out_of_the_books(): void
    {
        $a = $this->employee('Reopen Books');
        $run = $this->createSheet();
        $this->approve($run);
        $this->assertMoney(4200, $this->salaryCost());
        $this->assertMoney(4200, $this->sheetLine('salary'));

        $this->post(route('crm.payroll.reopen', $run->id))->assertSessionHas('success');
        $this->assertMoney(0, $this->salaryCost());
        $this->assertMoney(0, $this->sheetLine('salary'));
        $this->assertMoney(0, $this->card($this->report('salaries'), 'Net Salaries'));
        $this->assertNotNull($a);
    }

    public function test_an_advance_in_installments_is_recovered_over_several_sheets(): void
    {
        $a = $this->employee('Installments', ['joining_date' => '2025-01-01']);
        $this->from(route('crm.employees.index'))->post(route('crm.employees.advances.store', $a->id),
            ['given_date' => '2026-07-20', 'amount' => '900', 'installment' => '400', 'method' => 'Bank Transfer'])->assertSessionHasNoErrors();
        $this->assertMoney(900, $this->sheetLine('advances'));

        foreach ([['2026-08', 400, 500], ['2026-09', 400, 100], ['2026-10', 100, 0]] as [$period, $cut, $left]) {
            $run = $this->createSheet($period);
            $line = $this->lineOf($run, $a);
            $this->assertMoney($cut, $line->advance_deduction, $period.' cut');
            $this->assertMoney(4200 - $cut, $line->net);
            $this->assertMoney($left + $cut, $this->sheetLine('advances'), 'a draft does not recover anything yet');
            $this->approve($run)->assertSessionHas('success');
            $this->assertMoney($left, $this->sheetLine('advances'), $period.' approved');
            $this->assertMoney($left, $a->fresh()->advanceBalance());
        }

        // Fully recovered: nothing more is suggested and the whole 900 stays a cost through the salaries, not as an extra expense.
        $this->assertMoney(0, $a->fresh()->suggestedAdvanceDeduction());
        $this->assertMoney(4200 * 3, $this->salaryCost('2026-08-01', '2026-10-26'));
        $this->assertSame([$a->name, 900.0, 900.0, 0.0], $this->report('salaries')['tables'][2]['rows'][0]);
        $this->assertMoney(0, $this->card($this->report('balance-sheet'), 'Not yet explained'));
    }

    public function test_a_month_approved_after_it_ended_still_balances_at_that_month_end(): void
    {
        // September's sheet is approved (today, 26 Oct) after September ended — the usual way salaries are run.
        $a = $this->employee('Late Approval');
        $this->from(route('crm.employees.index'))->post(route('crm.employees.advances.store', $a->id),
            ['given_date' => '2026-09-05', 'amount' => '1000', 'method' => 'Cash'])->assertSessionHasNoErrors();
        $run = $this->createSheet('2026-09');
        $this->assertMoney(3200, $this->lineOf($run, $a)->net);
        $this->approve($run)->assertSessionHas('success');

        // The P&L puts September's salary in September...
        $this->assertMoney(4200, $this->salaryCost('2026-09-01', '2026-09-30'));
        // ...so the 30 Sep balance sheet must owe the salary and no longer hold the advance it recovered,
        // leaving nothing unexplained in a workspace that has only salary entries.
        $sheet = $this->report('balance-sheet', '2026-09-01', '2026-09-30');
        $this->assertEquals(
            ['Salaries Payable' => 3200.0, 'Salary Advances' => 0.0, 'Not yet explained' => 0.0],
            ['Salaries Payable' => round($this->sheetLine('salary', '2026-09-30'), 2), 'Salary Advances' => round($this->sheetLine('advances', '2026-09-30'), 2),
                'Not yet explained' => round($this->card($sheet, 'Not yet explained'), 2)],
            'balance sheet as of 30 Sep 2026'
        );
        // Today (after approval) it is right either way.
        $this->assertMoney(3200, $this->sheetLine('salary'));
        $this->assertMoney(0, $this->sheetLine('advances'));
    }

    // ───────────────────────────── Gratuity ─────────────────────────────

    public function test_gratuity_follows_the_uae_formula(): void
    {
        // Report date 26 Oct 2026. Daily wage = basic × 12 / 365 = 120 for a 3,650 basic; allowances are left out.
        $five = $this->employee('Grat Five Years', ['basic_salary' => 3650, 'housing_allowance' => 2000, 'joining_date' => '2021-10-27']);  // 1825 days = 5 years
        $seven = $this->employee('Grat Seven Years', ['basic_salary' => 3650, 'joining_date' => '2019-10-28']);                                // 2555 days = 7 years
        $daily = $this->employee('Grat Daily', ['salary_type' => 'daily', 'basic_salary' => 100, 'housing_allowance' => 0, 'transport_allowance' => 0,
            'joining_date' => '2024-10-26']);                                                                                                 // 730 days = 2 years
        $new = $this->employee('Grat New Joiner', ['basic_salary' => 3650, 'joining_date' => '2026-04-26']);                                  // 183 days
        $veteran = $this->employee('Grat Veteran', ['basic_salary' => 3650, 'joining_date' => '1996-10-26']);                                 // 30 years → capped
        $leaving = $this->employee('Grat Leaving', ['basic_salary' => 3650, 'joining_date' => '2024-10-26', 'status' => 'left', 'left_date' => '2026-12-31']);
        $gone = $this->employee('Grat Gone', ['basic_salary' => 3650, 'joining_date' => '2020-01-01', 'status' => 'left', 'left_date' => '2026-09-30']);
        $noDate = $this->employee('Grat No Date', ['joining_date' => null]);
        $future = $this->employee('Grat Future', ['joining_date' => '2026-11-01']);

        $report = $this->report('gratuity');
        $rows = collect($report['tables'][0]['rows'])->keyBy(fn ($r) => $r[0]['text']);
        $this->assertSame([$daily->name, $five->name, $leaving->name, $new->name, $seven->name, $veteran->name], $rows->keys()->all(),
            'left before the date, no joining date and not joined yet are not listed');

        // [years, basic / month, daily wage, gratuity days, gratuity, payable now]
        $expect = [
            $five->name => [5.0, 3650, 120, 105.0, 12600, 'Yes'],                    // 5 × 21 days
            $seven->name => [7.0, 3650, 120, 165.0, 19800, 'Yes'],                   // 5 × 21 + 2 × 30 days
            $daily->name => [2.0, 3000, 100, 42.0, 4200, 'Yes'],                     // daily rate; monthly shown as rate × 30
            $new->name => [0.5, 3650, 120, 10.5, 1263.45, 'Not yet (under 1 year)'], // 183 / 365 × 21 days
            $veteran->name => [30.02, 3650, 120, 855.6, 87600, 'Yes'],               // capped at 24 months' basic
            $leaving->name => [2.0, 3650, 120, 42.0, 5040, 'Yes'],                   // still serving notice on the report date
        ];
        foreach ($expect as $name => [$years, $monthly, $dailyWage, $days, $amount, $payable]) {
            $row = $rows[$name];
            $this->assertEqualsWithDelta($years, $row[3], 0.005, $name.' years');
            $this->assertMoney($monthly, $row[4], $name.' basic');
            $this->assertMoney($dailyWage, $row[5], $name.' daily wage');
            $this->assertEqualsWithDelta($days, $row[6], 0.05, $name.' days');
            $this->assertMoney($amount, $row[7], $name.' gratuity');
            $this->assertSame($payable, $row[8], $name.' payable');
        }

        $this->assertMoney(130503.45, $this->card($report, 'Total Gratuity Earned'));
        $this->assertMoney(129240.00, $this->card($report, 'Payable Now (1+ year service)'));
        $this->assertMoney(1263.45, $this->card($report, 'Still Building (under 1 year)'));
        $this->assertSame('6 employee(s)', collect($report['cards'])->firstWhere('label', 'Total Gratuity Earned')['note']);
        $this->assertMoney(130503.45, $report['tables'][0]['footer'][7]);
        $this->assertContains('Joining date missing for: '.$noDate->name.' — add it on Employees to include them.', $report['notes']);
        $this->assertNotNull($gone);
        $this->assertNotNull($future);

        // As of an earlier date the same people have earned less.
        $earlier = $this->report('gratuity', '2026-01-01', '2026-04-25');
        $this->assertFalse(collect($earlier['tables'][0]['rows'])->contains(fn ($r) => $r[0]['text'] === $new->name), 'not joined yet on 25 Apr');
        $this->assertTrue(collect($earlier['tables'][0]['rows'])->contains(fn ($r) => $r[0]['text'] === $gone->name), 'still employed on 25 Apr');
    }
}
