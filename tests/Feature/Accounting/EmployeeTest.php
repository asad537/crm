<?php

namespace Tests\Feature\Accounting;

use App\CrmDeletionLog;
use App\CrmEmployee;
use App\CrmEmployeeAdvance;
use App\CrmPayrollLine;
use App\CrmPayrollRun;
use App\CrmWorkspace;
use App\Http\Controllers\Crm\PaymentController;
use App\Services\FinancialReportService;
use Carbon\Carbon;

/** Employees page: staff profiles with their salary setup, delete rules, salary advances and who may do what. */
class EmployeeTest extends AccountingTestCase
{
    /** Form fields for a new employee, as the Employees page posts them. */
    protected function employeeData(array $overrides = []): array
    {
        return array_merge([
            'employee_code' => 'EMP-'.uniqid(),
            'name' => 'Worker '.uniqid(),
            'designation' => 'Machine Operator',
            'department' => 'Production',
            'phone' => '+971 50 000 0000',
            'nationality' => 'Pakistan',
            'joining_date' => '2024-03-01',
            'salary_type' => 'monthly',
            'basic_salary' => '3000',
            'housing_allowance' => '1000',
            'transport_allowance' => '250.50',
            'food_allowance' => '',
            'other_allowance' => '',
            'overtime_rate' => '15',
            'payment_method' => 'Cash',
            'status' => 'active',
            'left_date' => '',
            'notes' => '',
        ], $overrides);
    }

    /** Add an employee through the page and return it. */
    protected function addEmployee(array $overrides = []): CrmEmployee
    {
        $data = $this->employeeData($overrides);
        $this->post(route('crm.employees.store'), $data)->assertRedirect(route('crm.employees.index'))->assertSessionHasNoErrors();

        return CrmEmployee::where('name', $data['name'])->firstOrFail();
    }

    protected function giveAdvance(CrmEmployee $employee, array $overrides = [])
    {
        return $this->from(route('crm.employees.index', ['tab' => 'advances']))
            ->post(route('crm.employees.advances.store', $employee->id), array_merge([
                'given_date' => '2026-10-05', 'amount' => '1200', 'installment' => '', 'method' => 'Cash', 'note' => 'Family emergency',
            ], $overrides));
    }

    /** One balance-sheet line (by its kind, e.g. "advances", "salary") as of a date. */
    protected function sheetLine(string $kind, string $asOf = '2026-10-26'): float
    {
        $sheet = (new FinancialReportService(Carbon::parse('2026-01-01'), Carbon::parse($asOf)))->balanceSheetAsOf(Carbon::parse($asOf));
        foreach (array_merge($sheet['assets'], $sheet['liabilities']) as $line) {
            if ($line[3] === $kind) return (float) $line[1];
        }
        $this->fail('No balance sheet line of kind '.$kind);
    }

    /** Names in the Employees list for the given filters (the page also names active staff in its advance dialog, so assertSee is not enough). */
    protected function listed(array $filters = []): array
    {
        return $this->get(route('crm.employees.index', $filters))->assertOk()->viewData('employees')->pluck('name')->all();
    }

    /** First-column label → row, for a report table (rows are [label|link, values...] or styled rows with "cells"). */
    protected function reportRow(array $report, string $label, int $table = 0): ?array
    {
        foreach ($report['tables'][$table]['rows'] as $row) {
            $cells = $row['cells'] ?? $row;
            $text = is_array($cells[0]) ? $cells[0]['text'] : (string) $cells[0];
            if ($text === $label) return $cells;
        }

        return null;
    }

    // ───────────────────────────── Employees ─────────────────────────────

    public function test_admin_adds_an_employee_with_salary_parts(): void
    {
        $data = $this->employeeData(['name' => 'Asif Khan '.uniqid(), 'food_allowance' => '199.999', 'iban' => 'AE070331234567890123456',
            'bank_name' => 'Emirates NBD', 'bank_routing_code' => '302620122', 'mol_person_id' => '10012345678901', 'payment_method' => 'Bank Transfer']);
        $this->post(route('crm.employees.store'), $data)
            ->assertRedirect(route('crm.employees.index'))
            ->assertSessionHas('success', $data['name'].' added.');

        $employee = CrmEmployee::where('name', $data['name'])->firstOrFail();
        $this->assertSame($this->workspace->id, (int) $employee->workspace_id);
        $this->assertSame($this->admin->id, (int) $employee->created_by);
        $this->assertSame('2024-03-01', $employee->joining_date->toDateString());
        $this->assertSame('monthly', $employee->salary_type);
        $this->assertSame('active', $employee->status);
        $this->assertNull($employee->left_date);
        $this->assertMoney(3000, $employee->basic_salary);
        $this->assertMoney(1000, $employee->housing_allowance);
        $this->assertMoney(250.50, $employee->transport_allowance);
        $this->assertMoney(200.00, $employee->food_allowance, 'allowances are rounded to the fils');
        $this->assertMoney(0, $employee->other_allowance, 'an empty allowance is saved as 0');
        $this->assertMoney(15, $employee->overtime_rate);
        $this->assertMoney(1450.50, $employee->allowancesTotal());
        $this->assertSame('4,450.50 / month', $employee->packageLabel());
        $this->assertSame('Bank Transfer', $employee->payment_method);
        $this->assertSame('AE070331234567890123456', $employee->iban);

        // A daily-wage worker: the basic is the day rate.
        $daily = $this->addEmployee(['name' => 'Daily Helper '.uniqid(), 'salary_type' => 'daily', 'basic_salary' => '85', 'housing_allowance' => '', 'transport_allowance' => '']);
        $this->assertTrue($daily->isDaily());
        $this->assertSame('85.00 / day', $daily->packageLabel());

        // The list and its totals: monthly payroll counts monthly staff only.
        $response = $this->get(route('crm.employees.index'))->assertOk()->assertSee($data['name'])->assertSee($daily->name);
        $totals = $response->viewData('totals');
        $this->assertSame(2, $totals['active']);
        $this->assertSame(1, $totals['daily_count']);
        $this->assertMoney(4450.50, $totals['monthly_payroll']);
        $this->assertMoney(0, $totals['advance_balance']);
    }

    public function test_employee_form_is_validated(): void
    {
        $this->from(route('crm.employees.index'))->post(route('crm.employees.store'), $this->employeeData([
            'name' => '', 'salary_type' => 'hourly', 'basic_salary' => '-5', 'housing_allowance' => '-1', 'payment_method' => 'Cheque',
            'status' => 'fired', 'joining_date' => 'not-a-date', 'overtime_rate' => '100000',
        ]))->assertRedirect(route('crm.employees.index'))
            ->assertSessionHasErrors(['name', 'salary_type', 'basic_salary', 'housing_allowance', 'payment_method', 'status', 'joining_date', 'overtime_rate']);

        $this->post(route('crm.employees.store'), $this->employeeData(['basic_salary' => '']))->assertSessionHasErrors('basic_salary');
        $this->assertSame(0, CrmEmployee::count());
    }

    public function test_updating_an_employee_changes_salary_and_status(): void
    {
        $employee = $this->addEmployee(['name' => 'Bilal '.uniqid()]);

        $this->from(route('crm.employees.index'))->put(route('crm.employees.update', $employee->id), $this->employeeData([
            'name' => $employee->name, 'basic_salary' => '3500', 'housing_allowance' => '1200', 'transport_allowance' => '', 'other_allowance' => '75.25',
            'status' => 'left', 'left_date' => '2026-09-15', 'joining_date' => '2023-01-10',
        ]))->assertRedirect(route('crm.employees.index'))->assertSessionHas('success', $employee->name.' updated.');

        $employee->refresh();
        $this->assertMoney(3500, $employee->basic_salary);
        $this->assertMoney(1275.25, $employee->allowancesTotal());
        $this->assertSame('left', $employee->status);
        $this->assertSame('2026-09-15', $employee->left_date->toDateString());
        $this->assertSame('2023-01-10', $employee->joining_date->toDateString());

        // A "left" employee drops off the default (active) list but shows under Left / All.
        $this->assertSame([], $this->listed());
        $this->assertSame([$employee->name], $this->listed(['status' => 'left']));
        $this->assertSame([$employee->name], $this->listed(['status' => 'all', 'search' => 'Bilal']));

        // Re-activating clears the left date even if the form still sends one.
        $this->from(route('crm.employees.index'))->put(route('crm.employees.update', $employee->id), $this->employeeData([
            'name' => $employee->name, 'status' => 'active', 'left_date' => '2026-09-15',
        ]))->assertSessionHasNoErrors();
        $employee->refresh();
        $this->assertSame('active', $employee->status);
        $this->assertNull($employee->left_date);

        // Validation on update too; nothing changes.
        $this->from(route('crm.employees.index'))->put(route('crm.employees.update', $employee->id), $this->employeeData(['name' => '', 'basic_salary' => 'abc']))
            ->assertSessionHasErrors(['name', 'basic_salary']);
        $this->assertSame('active', $employee->fresh()->status);
    }

    public function test_index_filters_by_department_and_search(): void
    {
        $printer = $this->addEmployee(['name' => 'Printer Person '.uniqid(), 'department' => 'Printing', 'designation' => 'Offset Printer']);
        $driver = $this->addEmployee(['name' => 'Driver Person '.uniqid(), 'department' => 'Driver', 'designation' => 'Van Driver', 'employee_code' => 'DRV-777']);

        $this->assertSame([$printer->name], $this->listed(['department' => 'Printing']));
        $this->assertSame([$driver->name], $this->listed(['search' => 'DRV-777']));
        $this->assertSame([$printer->name], $this->listed(['search' => 'Offset']));
        $this->assertSame([$driver->name, $printer->name], $this->listed(), 'sorted by name');
    }

    public function test_an_employee_without_history_can_be_deleted(): void
    {
        $employee = $this->addEmployee(['name' => 'Temp Staff '.uniqid()]);

        $this->from(route('crm.employees.index'))->delete(route('crm.employees.destroy', $employee->id))
            ->assertRedirect(route('crm.employees.index'))->assertSessionHas('success', $employee->name.' deleted.');

        $this->assertNull(CrmEmployee::find($employee->id));
        $log = CrmDeletionLog::where('entity_type', 'employee')->where('entity_id', $employee->id)->firstOrFail();
        $this->assertSame($employee->name, $log->entity_label);
        $this->assertSame($this->workspace->id, (int) $log->workspace_id);
        $this->assertMoney(3000, $log->snapshot['basic_salary']);
    }

    public function test_an_employee_with_advance_or_salary_history_cannot_be_deleted(): void
    {
        $withAdvance = $this->addEmployee(['name' => 'Has Advance '.uniqid()]);
        $this->giveAdvance($withAdvance)->assertSessionHasNoErrors();
        $this->from(route('crm.employees.index'))->delete(route('crm.employees.destroy', $withAdvance->id))
            ->assertRedirect(route('crm.employees.index'))->assertSessionHas('error');
        $this->assertNotNull(CrmEmployee::find($withAdvance->id));

        $withSalary = $this->addEmployee(['name' => 'Has Salary '.uniqid()]);
        $this->post(route('crm.payroll.store'), ['period' => '2026-09'])->assertSessionHasNoErrors();
        $this->assertTrue(CrmPayrollLine::where('employee_id', $withSalary->id)->exists());
        $this->from(route('crm.employees.index'))->delete(route('crm.employees.destroy', $withSalary->id))->assertSessionHas('error');
        $this->assertNotNull(CrmEmployee::find($withSalary->id));
        $this->assertSame(0, CrmDeletionLog::where('entity_type', 'employee')->whereIn('entity_id', [$withAdvance->id, $withSalary->id])->count());
    }

    public function test_an_employee_of_another_workspace_cannot_be_reached(): void
    {
        $other = CrmWorkspace::create(['name' => 'Other Co', 'slug' => 'other-co-'.uniqid(), 'is_active' => true]);
        $stranger = CrmEmployee::create(['workspace_id' => $other->id, 'name' => 'Stranger '.uniqid(), 'salary_type' => 'monthly',
            'basic_salary' => 9999, 'payment_method' => 'Cash', 'status' => 'active']);

        $this->assertSame([], $this->listed(['status' => 'all']));
        $this->get(route('crm.employees.index', ['status' => 'all']))->assertDontSee($stranger->name);
        $this->put(route('crm.employees.update', $stranger->id), $this->employeeData(['name' => 'Hijacked']))->assertNotFound();
        $this->delete(route('crm.employees.destroy', $stranger->id))->assertNotFound();
        $this->post(route('crm.employees.advances.store', $stranger->id), ['given_date' => '2026-10-01', 'amount' => 100])->assertNotFound();
        $this->assertSame($stranger->name, CrmEmployee::withoutGlobalScopes()->find($stranger->id)->name);
    }

    // ───────────────────────────── Permissions ─────────────────────────────

    public function test_a_sales_agent_cannot_open_or_change_employees(): void
    {
        $employee = $this->addEmployee();
        $this->giveAdvance($employee)->assertSessionHasNoErrors();
        $advance = CrmEmployeeAdvance::where('employee_id', $employee->id)->firstOrFail();

        $this->signIn($this->makeUser('sales'));
        $this->get(route('crm.employees.index'))->assertForbidden();
        $this->post(route('crm.employees.store'), $this->employeeData())->assertForbidden();
        $this->put(route('crm.employees.update', $employee->id), $this->employeeData(['name' => 'Changed']))->assertForbidden();
        $this->delete(route('crm.employees.destroy', $employee->id))->assertForbidden();
        $this->post(route('crm.employees.advances.store', $employee->id), ['given_date' => '2026-10-01', 'amount' => 100])->assertForbidden();
        $this->delete(route('crm.employees.advances.destroy', $advance->id))->assertForbidden();

        $this->assertSame(1, CrmEmployee::count());
        $this->assertSame(1, CrmEmployeeAdvance::count());
    }

    public function test_a_sales_manager_manages_employees_but_cannot_delete(): void
    {
        $this->signIn($this->makeUser('sales_manager'));
        $this->get(route('crm.employees.index'))->assertOk()->assertViewHas('canDelete', false);
        $employee = $this->addEmployee(['name' => 'By Manager '.uniqid()]);
        $this->giveAdvance($employee)->assertSessionHasNoErrors();
        $advance = CrmEmployeeAdvance::where('employee_id', $employee->id)->firstOrFail();

        $this->delete(route('crm.employees.advances.destroy', $advance->id))->assertForbidden();
        $this->delete(route('crm.employees.destroy', $employee->id))->assertForbidden();
        $this->assertNotNull(CrmEmployeeAdvance::find($advance->id));

        // Accounts can delete.
        $this->signIn($this->makeUser('accounts'));
        $this->get(route('crm.employees.index'))->assertOk()->assertViewHas('canDelete', true);
        $this->from(route('crm.employees.index'))->delete(route('crm.employees.advances.destroy', $advance->id))->assertSessionHas('success', 'Advance deleted.');
        $this->from(route('crm.employees.index'))->delete(route('crm.employees.destroy', $employee->id))->assertSessionHas('success');
        $this->assertSame(0, CrmEmployee::count());
    }

    // ───────────────────────────── Advances ─────────────────────────────

    public function test_giving_an_advance_records_it_and_shows_in_payments_and_the_books(): void
    {
        $employee = $this->addEmployee(['name' => 'Advance Taker '.uniqid(), 'department' => 'Printing']);

        $this->giveAdvance($employee, ['amount' => '1200', 'installment' => '400', 'method' => 'Petty Cash'])
            ->assertRedirect(route('crm.employees.index', ['tab' => 'advances']))
            ->assertSessionHas('success', 'Advance of 1,200.00 given to '.$employee->name.'. It will be cut from the next salary sheet at 400.00 per month.');

        $advance = CrmEmployeeAdvance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame($this->workspace->id, (int) $advance->workspace_id);
        $this->assertSame('2026-10-05', $advance->given_date->toDateString());
        $this->assertMoney(1200, $advance->amount);
        $this->assertMoney(400, $advance->installment);
        $this->assertSame('Petty Cash', $advance->method);
        $this->assertSame($this->admin->id, (int) $advance->created_by);

        $employee->refresh();
        $this->assertMoney(1200, $employee->advanceGiven());
        $this->assertMoney(0, $employee->advanceRecovered());
        $this->assertMoney(1200, $employee->advanceBalance());
        $this->assertMoney(400, $employee->suggestedAdvanceDeduction());

        // Advances tab and page totals.
        $page = $this->get(route('crm.employees.index', ['tab' => 'advances']))->assertOk()->assertSee($employee->name)->assertSee('Family emergency');
        $this->assertMoney(1200, $page->viewData('totals')['advance_balance']);

        // Payments register: one "advance" row for the amount on the given date.
        $rows = app(PaymentController::class)->payments()->where('source', 'advance');
        $this->assertCount(1, $rows);
        $this->assertMoney(1200, $rows->first()->amount);
        $this->assertSame($employee->name, $rows->first()->party);
        $this->assertSame('2026-10-05', Carbon::parse($rows->first()->date)->toDateString());

        // Balance sheet: an asset (loan to the employee), not on the books before it was given. Not an expense.
        $this->assertMoney(1200, $this->sheetLine('advances'));
        $this->assertMoney(0, $this->sheetLine('advances', '2026-10-04'));
        $pl = $this->report('profit-loss');
        $this->assertMoney(0, collect($pl['cards'])->firstWhere('label', 'Total Costs')['value'], 'an advance is not a cost');
        $sheet = $this->report('balance-sheet');
        $this->assertMoney(0, collect($sheet['cards'])->firstWhere('label', 'Not yet explained')['value']);

        // Cash flow: money out under operating activities.
        $flow = $this->report('cash-flow');
        $this->assertMoney(-1200, $this->reportRow($flow, 'Salary advances given')[1]);
        $this->assertMoney(-1200, collect($flow['cards'])->firstWhere('label', 'Net Cash from Operations')['value']);

        // Salary report lists the open advance.
        $salaries = collect($this->report('salaries')['cards'])->keyBy('label');
        $this->assertMoney(1200, $salaries['Open Advances']['value']);
    }

    public function test_advance_form_is_validated(): void
    {
        $employee = $this->addEmployee();

        $this->giveAdvance($employee, ['given_date' => '2026-10-27'])->assertSessionHasErrors('given_date');     // tomorrow
        $this->giveAdvance($employee, ['given_date' => ''])->assertSessionHasErrors('given_date');
        $this->giveAdvance($employee, ['amount' => '0'])->assertSessionHasErrors('amount');
        $this->giveAdvance($employee, ['amount' => '-50'])->assertSessionHasErrors('amount');
        $this->giveAdvance($employee, ['installment' => '0'])->assertSessionHasErrors('installment');
        $this->giveAdvance($employee, ['method' => 'Cheque'])->assertSessionHasErrors('method');
        $this->giveAdvance($employee, ['note' => str_repeat('x', 501)])->assertSessionHasErrors('note');
        $this->assertSame(0, CrmEmployeeAdvance::count());

        // Without an installment the whole advance is cut from the next salary.
        $this->giveAdvance($employee, ['amount' => '750', 'installment' => '', 'method' => ''])
            ->assertSessionHas('success', 'Advance of 750.00 given to '.$employee->name.'. It will be cut from the next salary sheet.');
        $this->assertMoney(750, $employee->fresh()->suggestedAdvanceDeduction());
    }

    public function test_an_unrecovered_advance_can_be_deleted(): void
    {
        $employee = $this->addEmployee(['name' => 'Oops Advance '.uniqid()]);
        $this->giveAdvance($employee, ['amount' => '500'])->assertSessionHasNoErrors();
        $advance = CrmEmployeeAdvance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertMoney(500, $this->sheetLine('advances'));

        $this->from(route('crm.employees.index', ['tab' => 'advances']))->delete(route('crm.employees.advances.destroy', $advance->id))
            ->assertSessionHas('success', 'Advance deleted.');

        $this->assertNull(CrmEmployeeAdvance::find($advance->id));
        $this->assertMoney(0, $employee->fresh()->advanceBalance());
        $this->assertMoney(0, $this->sheetLine('advances'));
        $this->assertCount(0, app(PaymentController::class)->payments()->where('source', 'advance'));
        $log = CrmDeletionLog::where('entity_type', 'employee_advance')->where('entity_id', $advance->id)->firstOrFail();
        $this->assertMoney(500, $log->snapshot['amount']);
    }

    public function test_an_advance_already_cut_from_an_approved_salary_cannot_be_deleted(): void
    {
        $employee = $this->addEmployee(['name' => 'Recovered '.uniqid()]);
        $this->giveAdvance($employee, ['given_date' => '2026-09-03', 'amount' => '600', 'installment' => '200'])->assertSessionHasNoErrors();
        $first = CrmEmployeeAdvance::where('employee_id', $employee->id)->firstOrFail();

        $this->post(route('crm.payroll.store'), ['period' => '2026-09'])->assertSessionHasNoErrors();
        $run = CrmPayrollRun::where('period', '2026-09')->firstOrFail();
        $this->assertMoney(200, $run->lines()->first()->advance_deduction);

        // While the sheet is a draft nothing is recovered yet, so the advance could still go — approve it instead.
        $this->from(route('crm.payroll.show', $run->id))->post(route('crm.payroll.approve', $run->id))->assertSessionHas('success');
        $this->assertMoney(200, $employee->fresh()->advanceRecovered());

        $this->from(route('crm.employees.index', ['tab' => 'advances']))->delete(route('crm.employees.advances.destroy', $first->id))
            ->assertSessionHas('error', 'Part of this advance was already cut from an approved salary — it cannot be deleted.');
        $this->assertNotNull(CrmEmployeeAdvance::find($first->id));

        // A second advance that nothing was cut from can be deleted (the recovered 200 is still covered by the first one).
        $this->giveAdvance($employee, ['given_date' => '2026-10-10', 'amount' => '300'])->assertSessionHasNoErrors();
        $second = CrmEmployeeAdvance::where('employee_id', $employee->id)->where('amount', 300)->firstOrFail();
        $this->from(route('crm.employees.index', ['tab' => 'advances']))->delete(route('crm.employees.advances.destroy', $second->id))
            ->assertSessionHas('success', 'Advance deleted.');
        $this->assertMoney(400, $employee->fresh()->advanceBalance());
    }
}
