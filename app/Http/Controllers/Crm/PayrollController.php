<?php

namespace App\Http\Controllers\Crm;

use App\CrmEmployee;
use App\CrmPayrollLine;
use App\CrmPayrollRun;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Al Massa "Salaries": monthly salary sheets (draft → approved → paid), payments and payslips. */
class PayrollController extends Controller
{
    /** Pre-filled days for daily-wage staff on a new sheet (6-day weeks); edited per person. */
    const DEFAULT_DAILY_DAYS = 26;

    const PAY_METHODS = ['Cash', 'Bank Transfer'];

    public function index()
    {
        $this->authorizeAccess();
        $runs = CrmPayrollRun::with('lines')->orderByDesc('period')->get();

        return view('crm.payroll.index', [
            'runs' => $runs,
            'nextPeriod' => now()->format('Y-m'),
            'canManage' => $this->canManage(),
            'totals' => [
                'year' => (float) $runs->filter(fn ($r) => str_starts_with($r->period, now()->format('Y')))->sum(fn ($r) => $r->lines->sum('net')),
                'unpaid' => (float) $runs->where('status', '!=', 'draft')->sum(fn ($r) => $r->lines->whereNull('paid_at')->sum('net')),
                'employees' => CrmEmployee::where('status', 'active')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();
        $data = $request->validate(['period' => 'required|date_format:Y-m']);
        if (CrmPayrollRun::where('period', $data['period'])->exists()) {
            $existing = CrmPayrollRun::where('period', $data['period'])->first();
            return redirect()->route('crm.payroll.show', $existing->id)->with('error', 'The ' . $existing->periodLabel() . ' salary sheet already exists.');
        }

        $run = DB::transaction(function () use ($data) {
            $run = CrmPayrollRun::create(['period' => $data['period'], 'status' => 'draft', 'created_by' => Auth::guard('crm')->id()]);
            $this->addEmployees($run);
            return $run;
        });

        return redirect()->route('crm.payroll.show', $run->id)
            ->with('success', $run->periodLabel() . ' salary sheet created with ' . $run->lines()->count() . ' employee(s). Check days, overtime and deductions, then approve.');
    }

    public function show($id)
    {
        $this->authorizeAccess();
        $run = CrmPayrollRun::with(['lines.payer', 'approver', 'creator'])->findOrFail($id);

        return view('crm.payroll.show', [
            'run' => $run,
            'payMethods' => self::PAY_METHODS,
            'canManage' => $this->canManage(),
            'missingCount' => $run->isDraft() ? $this->eligibleEmployees($run)->whereNotIn('id', $run->lines->pluck('employee_id'))->count() : 0,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAccess();
        $run = CrmPayrollRun::with('lines')->findOrFail($id);
        abort_unless($run->isDraft(), 422, 'Only a draft salary sheet can be edited.');

        $data = $request->validate([
            'lines' => 'required|array',
            'lines.*.days_worked' => 'nullable|numeric|min:0|max:31',
            'lines.*.absent_days' => 'nullable|numeric|min:0|max:30',
            'lines.*.overtime_hours' => 'nullable|numeric|min:0|max:500',
            'lines.*.overtime_rate' => 'nullable|numeric|min:0|max:99999',
            'lines.*.bonus' => 'nullable|numeric|min:0|max:9999999',
            'lines.*.other_deductions' => 'nullable|numeric|min:0|max:9999999',
            'lines.*.advance_deduction' => 'nullable|numeric|min:0|max:9999999',
            'lines.*.note' => 'nullable|string|max:500',
        ]);

        $negative = [];
        foreach ($run->lines as $line) {
            $input = $data['lines'][$line->id] ?? null;
            if (!$input) continue;
            foreach (['days_worked', 'absent_days', 'overtime_hours', 'overtime_rate', 'bonus', 'other_deductions', 'advance_deduction'] as $field) {
                $line->$field = round((float) ($input[$field] ?? 0), 2);
            }
            $line->note = $input['note'] ?? null;
            $line->recalculate();
            if ((float) $line->net < 0) $negative[] = $line->employee_name;
        }
        if ($negative) {
            throw ValidationException::withMessages(['lines' => 'Net salary cannot be below zero for: ' . implode(', ', $negative) . '. Lower the deductions.']);
        }
        DB::transaction(fn () => $run->lines->each->save());

        return back()->with('success', 'Salary sheet saved.');
    }

    public function addMissing($id)
    {
        $this->authorizeAccess();
        $run = CrmPayrollRun::findOrFail($id);
        abort_unless($run->isDraft(), 422, 'Only a draft salary sheet can be changed.');
        $added = $this->addEmployees($run);

        return back()->with('success', $added . ' employee(s) added to the sheet.');
    }

    public function removeLine($id, $lineId)
    {
        $this->authorizeAccess();
        $run = CrmPayrollRun::findOrFail($id);
        abort_unless($run->isDraft(), 422, 'Only a draft salary sheet can be changed.');
        $line = $run->lines()->whereKey($lineId)->firstOrFail();
        $line->delete();

        return back()->with('success', $line->employee_name . ' removed from this sheet.');
    }

    public function approve($id)
    {
        abort_unless($this->canManage(), 403, 'Only admins and accounts can approve salaries.');
        $run = CrmPayrollRun::with('lines.employee.advances')->findOrFail($id);
        abort_unless($run->isDraft(), 422, 'This salary sheet is already approved.');
        if ($run->lines->isEmpty()) return back()->with('error', 'The sheet has no employees.');

        $tooMuch = $run->lines->filter(fn ($line) => $line->employee && (float) $line->advance_deduction > $line->employee->advanceBalance() + 0.009);
        if ($tooMuch->isNotEmpty()) {
            return back()->with('error', 'Advance deduction is more than the open advance for: ' . $tooMuch->pluck('employee_name')->implode(', ') . '.');
        }
        $run->update(['status' => 'approved', 'approved_by' => Auth::guard('crm')->id(), 'approved_at' => now()]);

        return back()->with('success', $run->periodLabel() . ' salaries approved. You can now mark them paid.');
    }

    public function reopen($id)
    {
        abort_unless($this->canManage(), 403);
        $run = CrmPayrollRun::with('lines')->findOrFail($id);
        if ($run->lines->whereNotNull('paid_at')->isNotEmpty()) {
            return back()->with('error', 'Some salaries are already paid. Undo those payments first.');
        }
        $run->update(['status' => 'draft', 'approved_by' => null, 'approved_at' => null]);

        return back()->with('success', 'Salary sheet is back to draft and can be edited.');
    }

    public function payLine(Request $request, $id, $lineId)
    {
        abort_unless($this->canManage(), 403, 'Only admins and accounts can pay salaries.');
        $run = CrmPayrollRun::findOrFail($id);
        abort_if($run->isDraft(), 422, 'Approve the salary sheet before paying.');
        $line = $run->lines()->whereKey($lineId)->whereNull('paid_at')->firstOrFail();
        $line->update($this->paymentFields($request));
        $run->refreshPaidStatus();

        return back()->with('success', $line->employee_name . ' — ' . number_format((float) $line->net, 2) . ' marked paid. It now shows under Payments.');
    }

    public function payAll(Request $request, $id)
    {
        abort_unless($this->canManage(), 403, 'Only admins and accounts can pay salaries.');
        $run = CrmPayrollRun::findOrFail($id);
        abort_if($run->isDraft(), 422, 'Approve the salary sheet before paying.');
        $fields = $this->paymentFields($request);
        $lines = $run->lines()->whereNull('paid_at')->get();
        DB::transaction(fn () => $lines->each->update($fields));
        $run->refreshPaidStatus();

        return back()->with('success', $lines->count() . ' salary(ies) totalling ' . number_format((float) $lines->sum('net'), 2) . ' marked paid.');
    }

    public function unpayLine($id, $lineId)
    {
        abort_unless($this->canManage(), 403);
        $run = CrmPayrollRun::findOrFail($id);
        $line = $run->lines()->whereKey($lineId)->firstOrFail();
        \App\CrmDeletionLog::record('salary_payment', $line, $line->employee_name . ' salary ' . $run->periodLabel(),
            $line->only(['net', 'paid_at', 'paid_method', 'paid_reference']));
        $line->update(['paid_at' => null, 'paid_method' => null, 'paid_reference' => null, 'paid_by' => null, 'bank_account_id' => null]);
        $run->refreshPaidStatus();

        return back()->with('success', 'Payment undone for ' . $line->employee_name . '.');
    }

    public function destroy($id)
    {
        abort_unless($this->canManage(), 403);
        $run = CrmPayrollRun::with('lines')->findOrFail($id);
        abort_unless($run->isDraft(), 422, 'Only a draft salary sheet can be deleted.');
        \App\CrmDeletionLog::record('salary_sheet', $run, $run->periodLabel() . ' salary sheet', ['period' => $run->period, 'employees' => $run->lines->count(), 'net' => (float) $run->lines->sum('net')]);
        DB::transaction(function () use ($run) {
            $run->lines()->delete();
            $run->delete();
        });

        return redirect()->route('crm.payroll.index')->with('success', $run->periodLabel() . ' salary sheet deleted.');
    }

    public function payslip(Request $request, $id, $lineId)
    {
        $this->authorizeAccess();
        $run = CrmPayrollRun::findOrFail($id);
        $line = $run->lines()->with('employee')->whereKey($lineId)->firstOrFail();
        $meta = $this->pdfMeta($request);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.payroll.payslip_pdf', compact('run', 'line', 'meta'))
            ->setPaper('a4', 'portrait')
            ->stream('payslip-' . \Illuminate\Support\Str::slug($line->employee_name) . '-' . $run->period . '.pdf');
    }

    public function sheetPdf(Request $request, $id)
    {
        $this->authorizeAccess();
        $run = CrmPayrollRun::with(['lines', 'approver'])->findOrFail($id);
        $meta = $this->pdfMeta($request);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.payroll.sheet_pdf', compact('run', 'meta'))
            ->setPaper('a4', 'landscape')
            ->download('salary-sheet-' . $run->period . '.pdf');
    }

    /**
     * WPS Salary Information File (.SIF) for the bank: one EDR line per employee paid by bank transfer and an SCR
     * control line. Fixed income = net pay less overtime and bonus (the variable part); leave days = absent days.
     */
    public function wps(Request $request, $id)
    {
        abort_unless($this->canManage(), 403, 'Only admins and accounts can make the WPS file.');
        $run = CrmPayrollRun::with('lines.employee')->findOrFail($id);
        $data = $request->validate([
            'establishment_id' => 'required|string|max:20',
            'employer_routing_code' => 'required|string|max:20',
        ], ['establishment_id.required' => 'Enter the company\'s MOL establishment ID.', 'employer_routing_code.required' => 'Enter the company bank\'s routing code.']);
        \App\CrmSetting::put('wps_establishment_id', trim($data['establishment_id']));
        \App\CrmSetting::put('wps_employer_routing_code', trim($data['employer_routing_code']));

        $lines = $run->lines->filter(fn ($l) => ($l->payment_method ?: optional($l->employee)->payment_method) === 'Bank Transfer' && (float) $l->net > 0);
        $missing = $lines->filter(fn ($l) => !$l->employee || !$l->employee->iban || !$l->employee->bank_routing_code || !$l->employee->mol_person_id)
            ->map(fn ($l) => $l->employee_name)->values();
        if ($lines->isEmpty()) {
            return back()->with('error', 'No one on the ' . $run->periodLabel() . ' sheet is paid by Bank Transfer, so there is nothing for the WPS file.');
        }
        if ($missing->isNotEmpty()) {
            return back()->with('error', 'Add IBAN, Bank Routing Code and MOL Person ID on Employees for: ' . $missing->implode(', ') . '.');
        }

        $start = $run->periodStart();
        $end = $start->copy()->endOfMonth();
        $clean = fn ($v) => preg_replace('/\s+/', '', (string) $v);
        $money = fn ($v) => number_format((float) $v, 2, '.', '');
        $out = [];
        foreach ($lines as $l) {
            $variable = min((float) $l->net, round((float) $l->overtime_hours * (float) $l->overtime_rate + (float) $l->bonus, 2));
            $out[] = implode(',', ['EDR', $clean($l->employee->mol_person_id), $clean($l->employee->bank_routing_code), strtoupper($clean($l->employee->iban)),
                $start->toDateString(), $end->toDateString(), $start->daysInMonth, $money((float) $l->net - $variable), $money($variable), (int) round((float) $l->absent_days)]);
        }
        $now = now();
        $out[] = implode(',', ['SCR', $clean($data['establishment_id']), $clean($data['employer_routing_code']), $now->toDateString(), $now->format('Hi'),
            $start->format('mY'), count($out), $money($lines->sum('net')), 'AED', 'Salary ' . $start->format('M Y')]);

        return response(implode("\r\n", $out) . "\r\n", 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $clean($data['establishment_id']) . $now->format('ymdHis') . '.SIF"',
        ]);
    }

    /** Active employees (and those who left during the month) who had joined by the month's end. */
    protected function eligibleEmployees(CrmPayrollRun $run)
    {
        $start = $run->periodStart();
        $end = $start->copy()->endOfMonth();

        return CrmEmployee::with('advances')->get()->filter(function ($employee) use ($start, $end) {
            if ($employee->joining_date && $employee->joining_date->gt($end)) return false;
            if ($employee->status === 'left') return $employee->left_date && $employee->left_date->gte($start);
            return true;
        });
    }

    /** Add a pre-filled line for every eligible employee not yet on the sheet; returns how many were added. */
    protected function addEmployees(CrmPayrollRun $run): int
    {
        $onSheet = $run->lines()->pluck('employee_id');
        $added = 0;
        foreach ($this->eligibleEmployees($run)->whereNotIn('id', $onSheet) as $employee) {
            $line = new CrmPayrollLine([
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'designation' => $employee->designation,
                'department' => $employee->department,
                'salary_type' => $employee->salary_type,
                'basic_rate' => (float) $employee->basic_salary,
                'allowances' => $employee->allowancesTotal(),
                'days_worked' => $employee->isDaily() ? self::DEFAULT_DAILY_DAYS : 0,
                'overtime_rate' => (float) $employee->overtime_rate,
                'advance_deduction' => $employee->suggestedAdvanceDeduction(),
                'payment_method' => $employee->payment_method,
            ]);
            $line->recalculate();
            if ((float) $line->net < 0) {
                // Never pre-fill a negative salary: cap the advance cut at what the salary can cover.
                $line->advance_deduction = max(0, round((float) $line->advance_deduction + (float) $line->net, 2));
                $line->recalculate();
            }
            $line->save();
            $added++;
        }

        return $added;
    }

    protected function paymentFields(Request $request): array
    {
        $data = $request->validate([
            'paid_at' => 'required|date|before_or_equal:today',
            'paid_method' => ['required', Rule::in(self::PAY_METHODS)],
            'paid_reference' => 'nullable|string|max:120',
        ]);

        return array_merge($data, \App\CrmBankAccount::fromRequest($request), ['paid_by' => Auth::guard('crm')->id()]);
    }

    protected function pdfMeta(Request $request): array
    {
        $workspace = optional($request->attributes->get('crm_workspace'));

        return ['isAlMassa' => $workspace->slug === 'mybox-packaging-app', 'generatedAt' => now()->format('d M Y, h:i A')];
    }

    protected function canManage(): bool
    {
        $user = Auth::guard('crm')->user();

        return $user->isAdmin() || $user->isAccounts();
    }

    protected function authorizeAccess(): void
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAccounts() && !$user->isSalesManager() && !$user->isAdmin()) {
            abort(403, 'Only accounts and admins can manage salaries.');
        }
    }
}
