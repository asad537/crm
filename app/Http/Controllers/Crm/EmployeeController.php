<?php

namespace App\Http\Controllers\Crm;

use App\CrmEmployee;
use App\CrmEmployeeAdvance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Al Massa "Employees": staff profiles with salary setup, plus salary advances given to them. */
class EmployeeController extends Controller
{
    const ALLOWANCE_FIELDS = ['housing_allowance', 'transport_allowance', 'food_allowance', 'other_allowance', 'overtime_rate'];

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $tab = $request->input('tab') === 'advances' ? 'advances' : 'employees';
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'department' => $request->input('department') ?: null,
            'status' => in_array($request->input('status'), ['active', 'left', 'all'], true) ? $request->input('status') : 'active',
        ];

        $query = CrmEmployee::with('advances')->orderBy('name');
        if ($filters['status'] !== 'all') $query->where('status', $filters['status']);
        if ($filters['department']) $query->where('department', $filters['department']);
        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('employee_code', 'like', $like)
                ->orWhere('designation', 'like', $like)->orWhere('phone', 'like', $like));
        }
        $employees = $query->get();

        $active = CrmEmployee::where('status', 'active')->get();
        $totals = [
            'active' => $active->count(),
            'monthly_payroll' => (float) $active->where('salary_type', 'monthly')->sum(fn ($e) => (float) $e->basic_salary + $e->allowancesTotal()),
            'daily_count' => $active->where('salary_type', 'daily')->count(),
            'advance_balance' => (float) CrmEmployee::with('advances')->get()->sum(fn ($e) => $e->advanceBalance()),
        ];

        $advances = $tab === 'advances'
            ? CrmEmployeeAdvance::with(['employee', 'creator'])->orderByDesc('given_date')->orderByDesc('id')->get()
            : collect();

        return view('crm.employees.index', [
            'tab' => $tab,
            'employees' => $employees,
            'advances' => $advances,
            'totals' => $totals,
            'filters' => $filters,
            'departments' => collect(CrmEmployee::DEPARTMENTS)->concat(CrmEmployee::distinct()->pluck('department')->filter())->unique()->values(),
            'paymentMethods' => CrmEmployee::PAYMENT_METHODS,
            'salaryTypes' => CrmEmployee::SALARY_TYPES,
            'activeEmployees' => $active->sortBy('name')->values(),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $employee = CrmEmployee::create(array_merge($this->validated($request), ['created_by' => Auth::guard('crm')->id()]));

        return redirect()->route('crm.employees.index')->with('success', $employee->name . ' added.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $employee = CrmEmployee::findOrFail($id);
        $employee->update($this->validated($request));

        return back()->with('success', $employee->name . ' updated.');
    }

    public function destroy($id)
    {
        abort_unless($this->canDelete(), 403, 'Only admins and accounts can delete employees.');
        $employee = CrmEmployee::findOrFail($id);
        if ($employee->payrollLines()->exists() || $employee->advances()->exists()) {
            return back()->with('error', $employee->name . ' has salary or advance history — edit the employee and set the status to "Left" instead.');
        }
        \App\CrmDeletionLog::record('employee', $employee, $employee->name, $employee->only(['employee_code', 'name', 'designation', 'department', 'salary_type', 'basic_salary']));
        $employee->delete();

        return back()->with('success', $employee->name . ' deleted.');
    }

    public function storeAdvance(Request $request, $id)
    {
        $this->authorizeAccess();
        $employee = CrmEmployee::findOrFail($id);
        $data = $request->validate([
            'given_date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01|max:9999999',
            'installment' => 'nullable|numeric|min:0.01|max:9999999',
            'method' => ['nullable', Rule::in(['Cash', 'Petty Cash', 'Bank Transfer'])],
            'note' => 'nullable|string|max:500',
        ]);
        $employee->advances()->create(array_merge($data, \App\CrmBankAccount::fromRequest($request), ['created_by' => Auth::guard('crm')->id()]));

        return back()->with('success', 'Advance of ' . number_format((float) $data['amount'], 2) . ' given to ' . $employee->name
            . '. It will be cut from the next salary sheet' . (!empty($data['installment']) ? ' at ' . number_format((float) $data['installment'], 2) . ' per month.' : '.'));
    }

    public function destroyAdvance($id)
    {
        abort_unless($this->canDelete(), 403, 'Only admins and accounts can delete advances.');
        $advance = CrmEmployeeAdvance::with('employee')->findOrFail($id);
        $employee = $advance->employee;
        if ($employee && $employee->advanceRecovered() > $employee->advanceGiven() - (float) $advance->amount + 0.009) {
            return back()->with('error', 'Part of this advance was already cut from an approved salary — it cannot be deleted.');
        }
        \App\CrmDeletionLog::record('employee_advance', $advance, optional($employee)->name . ' advance ' . number_format((float) $advance->amount, 2),
            $advance->only(['given_date', 'amount', 'installment', 'method', 'note']));
        $advance->delete();

        return back()->with('success', 'Advance deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'employee_code' => 'nullable|string|max:40',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:120',
            'department' => 'nullable|string|max:80',
            'phone' => 'nullable|string|max:40',
            'nationality' => 'nullable|string|max:60',
            'joining_date' => 'nullable|date',
            'salary_type' => ['required', Rule::in(array_keys(CrmEmployee::SALARY_TYPES))],
            'basic_salary' => 'required|numeric|min:0|max:9999999',
            'housing_allowance' => 'nullable|numeric|min:0|max:9999999',
            'transport_allowance' => 'nullable|numeric|min:0|max:9999999',
            'food_allowance' => 'nullable|numeric|min:0|max:9999999',
            'other_allowance' => 'nullable|numeric|min:0|max:9999999',
            'overtime_rate' => 'nullable|numeric|min:0|max:99999',
            'payment_method' => ['required', Rule::in(CrmEmployee::PAYMENT_METHODS)],
            'bank_name' => 'nullable|string|max:120',
            'iban' => 'nullable|string|max:60',
            'bank_routing_code' => 'nullable|string|max:20',
            'mol_person_id' => 'nullable|string|max:30',
            'emirates_id' => 'nullable|string|max:40',
            'emirates_id_expiry' => 'nullable|date',
            'passport_no' => 'nullable|string|max:40',
            'visa_expiry' => 'nullable|date',
            'status' => ['required', Rule::in(['active', 'left'])],
            'left_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);
        foreach (self::ALLOWANCE_FIELDS as $field) {
            $data[$field] = round((float) ($data[$field] ?? 0), 2);
        }
        if ($data['status'] === 'active') $data['left_date'] = null;

        return $data;
    }

    protected function canDelete(): bool
    {
        $user = Auth::guard('crm')->user();

        return $user->isAdmin() || $user->isAccounts();
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can manage employees.');
        }
    }
}
