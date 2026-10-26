@extends('crm.layout')
@section('title', 'Employees')
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $isFiltered = $filters['search'] !== '' || $filters['department'] || $filters['status'] !== 'active';
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')
<style>
    .em-dialog { max-width: 820px; }
    .em-section { grid-column: 1 / -1; font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--brand);
        border-bottom: 1px solid var(--line); padding-bottom: .35rem; margin-top: .4rem; }
    .em-dbody { grid-template-columns: repeat(3, 1fr); }
    .em-left { color: var(--faint); }
    @media (max-width: 760px) { .em-dbody { grid-template-columns: 1fr; } }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Employees</strong> — staff profiles with their salary setup, and salary advances. Monthly salaries are made on the Salaries tab.</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <button type="button" class="pi-btn" onclick="emOpenAdvance()"><i class="fas fa-hand-holding-usd"></i>&nbsp; Give Advance</button>
            <button type="button" class="ex-add" onclick="emOpenCreate()"><i class="fas fa-plus"></i> Add Employee</button>
        </div>
    </div>

    <div class="gl-tabs" style="margin-bottom:1rem">
        <a class="gl-tab {{ $tab === 'employees' ? 'active' : '' }}" href="{{ route('crm.employees.index') }}"><i class="fas fa-id-badge"></i> Employees</a>
        <a class="gl-tab {{ $tab === 'advances' ? 'active' : '' }}" href="{{ route('crm.employees.index', ['tab' => 'advances']) }}"><i class="fas fa-hand-holding-usd"></i> Advances</a>
    </div>

    <div class="gl-stats">
        <div class="gl-stat">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-users"></i></span><span class="gl-k">Active Employees</span></div>
            <div class="gl-v">{{ $totals['active'] }}</div>
            <div class="gl-note">{{ $totals['daily_count'] }} on daily wage</div>
        </div>
        <div class="gl-stat">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-money-check-alt"></i></span><span class="gl-k">Monthly Salaries (fixed)</span></div>
            <div class="gl-v">{{ $fmt($totals['monthly_payroll']) }} <small>AED</small></div>
            <div class="gl-note">basic + allowances of monthly staff</div>
        </div>
        <a href="{{ route('crm.employees.index', ['tab' => 'advances']) }}" class="gl-stat red gl-stat-link">
            <div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-hand-holding-usd"></i></span><span class="gl-k">Open Advances</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
            <div class="gl-v">{{ $fmt($totals['advance_balance']) }} <small>AED</small></div>
            <div class="gl-note">still to be cut from salaries</div>
        </a>
    </div>

    @if($tab === 'employees')
    <div class="gl-filterbar">
        <form class="gl-filters" method="GET" action="{{ route('crm.employees.index') }}" id="emFilterForm">
            <div class="gl-field"><label>Status</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="left" {{ $filters['status'] === 'left' ? 'selected' : '' }}>Left</option>
                    <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div class="gl-field"><label>Department</label>
                <select name="department" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department }}" {{ $filters['department'] === $department ? 'selected' : '' }}>{{ $department }}</option>
                    @endforeach
                </select>
            </div>
            <div class="gl-field"><label>Search</label><input name="search" value="{{ $filters['search'] }}" placeholder="Name, code, phone..." autocomplete="off"></div>
            <button type="submit" class="gl-apply">Search</button>
            @if($isFiltered)<a class="gl-reset" href="{{ route('crm.employees.index') }}">Reset</a>@endif
        </form>
    </div>

    <div class="gl-count">Showing <b>{{ $employees->count() }}</b> employee{{ $employees->count() === 1 ? '' : 's' }}</div>
    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="min-width:200px">Employee</th><th style="width:130px">Department</th><th style="width:110px">Salary Type</th>
                    <th class="gl-r" style="width:150px">Salary</th><th style="width:120px">Paid By</th><th class="gl-r" style="width:130px">Open Advance</th>
                    <th style="width:90px">Status</th><th style="width:120px"></th>
                </tr></thead>
                <tbody>
                @forelse($employees as $employee)
                    @php $balance = $employee->advanceBalance(); @endphp
                    <tr class="{{ $employee->status === 'left' ? 'em-left' : '' }}">
                        <td data-label="Employee"><span class="ex-name">{{ $employee->name }}</span><span class="ex-sub">{{ trim(($employee->employee_code ? $employee->employee_code . ' · ' : '') . ($employee->designation ?: '')) ?: '—' }}{{ $employee->phone ? ' · ' . $employee->phone : '' }}</span></td>
                        <td data-label="Department">{{ $employee->department ?: '—' }}</td>
                        <td data-label="Salary Type">{{ $salaryTypes[$employee->salary_type] ?? 'Monthly' }}</td>
                        <td class="gl-r gl-num" data-label="Salary">{{ $employee->packageLabel() }}@if(!$employee->isDaily() && $employee->allowancesTotal() > 0)<span class="ex-sub">basic {{ $fmt($employee->basic_salary) }} + allow. {{ $fmt($employee->allowancesTotal()) }}</span>@endif</td>
                        <td class="gl-mutedtext" data-label="Paid By">{{ $employee->payment_method }}</td>
                        <td class="gl-r gl-num {{ $balance > 0 ? 'gl-red' : '' }}" data-label="Open Advance">{{ $fmt($balance) }}</td>
                        <td data-label="Status"><span class="pi-status {{ $employee->status === 'left' ? 'overpaid' : 'paid' }}">{{ $employee->status === 'left' ? 'Left' : 'Active' }}</span></td>
                        <td data-label="">
                            <div class="ex-actions">
                                <button type="button" class="ex-icon" title="Edit" data-employee="{{ json_encode($employee->formData()) }}" onclick="emOpenEdit(JSON.parse(this.dataset.employee))"><i class="fas fa-pen"></i></button>
                                @if($employee->status === 'active')
                                <button type="button" class="ex-icon" title="Give advance" onclick="emOpenAdvance({{ $employee->id }})"><i class="fas fa-hand-holding-usd"></i></button>
                                @endif
                                @if($canDelete)
                                <form method="POST" action="{{ route('crm.employees.destroy', $employee->id) }}" onsubmit="return confirm('Delete this employee?');">
                                    {{ csrf_field() }} {{ method_field('DELETE') }}
                                    <button type="submit" class="ex-icon danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="gl-empty"><i class="fas fa-users"></i>{{ $isFiltered ? 'No employees match the filters.' : 'No employees yet — click "Add Employee".' }}</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="gl-count">Showing <b>{{ $advances->count() }}</b> advance{{ $advances->count() === 1 ? '' : 's' }} · given {{ $fmt($advances->sum('amount')) }} AED</div>
    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="width:110px">Date</th><th style="min-width:180px">Employee</th><th class="gl-r" style="width:130px">Amount</th>
                    <th class="gl-r" style="width:150px">Cut Per Month</th><th style="width:120px">Given As</th><th style="min-width:150px">Note</th><th style="width:120px">Given By</th><th style="width:50px"></th>
                </tr></thead>
                <tbody>
                @forelse($advances as $advance)
                    <tr>
                        <td class="gl-date" data-label="Date">{{ $advance->given_date->format('d M Y') }}</td>
                        <td data-label="Employee"><span class="gl-name">{{ optional($advance->employee)->name ?: '—' }}</span>@if($advance->employee)<span class="ex-sub">open balance {{ $fmt($advance->employee->advanceBalance()) }}</span>@endif</td>
                        <td class="gl-r gl-num" data-label="Amount">{{ $fmt($advance->amount) }}</td>
                        <td class="gl-r gl-mutedtext" data-label="Cut Per Month">{{ $advance->installment ? $fmt($advance->installment) : 'All at once' }}</td>
                        <td class="gl-mutedtext" data-label="Given As">{{ $advance->method ?: '—' }}</td>
                        <td data-label="Note">{{ $advance->note ?: '—' }}</td>
                        <td class="gl-mutedtext" data-label="Given By">{{ optional($advance->creator)->name ?: '—' }}</td>
                        <td data-label="">
                            @if($canDelete)
                            <form method="POST" action="{{ route('crm.employees.advances.destroy', $advance->id) }}" onsubmit="return confirm('Delete this advance of {{ $fmt($advance->amount) }}?');">
                                {{ csrf_field() }} {{ method_field('DELETE') }}
                                <button type="submit" class="ex-icon danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="gl-empty"><i class="fas fa-hand-holding-usd"></i>No advances given yet.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- Add / edit employee --}}
<div class="ex-modal gl-page" id="emModal" onclick="if (event.target === this) emClose('emModal')">
    <form class="ex-dialog em-dialog" id="emForm" method="POST" action="{{ route('crm.employees.store') }}">
        {{ csrf_field() }}
        <input type="hidden" name="_method" id="emMethod" value="POST">
        <input type="hidden" name="_employee_id" id="emId" value="">
        <div class="ex-dhead">
            <span class="gl-ico brand"><i class="fas fa-id-badge"></i></span>
            <div><h3 id="emTitle">Add Employee</h3><p>Personal details, salary setup and how the salary is paid.</p></div>
            <button type="button" class="ex-close" onclick="emClose('emModal')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ex-dbody em-dbody">
            @if($errors->any() && !old('_advance'))
                <div class="ex-errors"><strong>Please fix the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="em-section">Employee</div>
            <div class="ex-f"><label>Full Name <span class="req">*</span></label><input name="name" required maxlength="255"></div>
            <div class="ex-f"><label>Employee Code</label><input name="employee_code" maxlength="40" placeholder="e.g. AM-014"></div>
            <div class="ex-f"><label>Designation</label><input name="designation" maxlength="120" placeholder="e.g. Machine Operator"></div>
            <div class="ex-f"><label>Department</label><input name="department" list="emDepartments" maxlength="80"><datalist id="emDepartments">@foreach($departments as $d)<option value="{{ $d }}">@endforeach</datalist></div>
            <div class="ex-f"><label>Phone</label><input name="phone" maxlength="40"></div>
            <div class="ex-f"><label>Nationality</label><input name="nationality" maxlength="60"></div>
            <div class="ex-f"><label>Joining Date</label><input type="date" name="joining_date"></div>

            <div class="em-section">Salary</div>
            <div class="ex-f"><label>Salary Type <span class="req">*</span></label>
                <select name="salary_type" onchange="emSalaryType(this.value)">@foreach($salaryTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
            </div>
            <div class="ex-f"><label id="emBasicLabel">Basic Salary (per month) <span class="req">*</span></label><input type="number" name="basic_salary" step="0.01" min="0" required></div>
            <div class="ex-f"><label>Overtime Rate <span class="hint">(per hour)</span></label><input type="number" name="overtime_rate" step="0.01" min="0"></div>
            <div class="ex-f"><label>Housing Allowance</label><input type="number" name="housing_allowance" step="0.01" min="0"></div>
            <div class="ex-f"><label>Transport Allowance</label><input type="number" name="transport_allowance" step="0.01" min="0"></div>
            <div class="ex-f"><label>Food Allowance</label><input type="number" name="food_allowance" step="0.01" min="0"></div>
            <div class="ex-f"><label>Other Allowance</label><input type="number" name="other_allowance" step="0.01" min="0"></div>

            <div class="em-section">Payment</div>
            <div class="ex-f"><label>Salary Paid By <span class="req">*</span></label>
                <select name="payment_method">@foreach($paymentMethods as $method)<option>{{ $method }}</option>@endforeach</select>
            </div>
            <div class="ex-f"><label>Bank Name</label><input name="bank_name" maxlength="120"></div>
            <div class="ex-f"><label>IBAN</label><input name="iban" maxlength="60"></div>
            <div class="ex-f"><label>Bank Routing Code <span class="hint">(WPS agent ID, 9 digits)</span></label><input name="bank_routing_code" maxlength="20"></div>
            <div class="ex-f"><label>MOL Person ID <span class="hint">(WPS, 14 digits)</span></label><input name="mol_person_id" maxlength="30"></div>

            <div class="em-section">Documents <span style="text-transform:none;font-weight:600;color:var(--faint)">(optional)</span></div>
            <div class="ex-f"><label>Emirates ID</label><input name="emirates_id" maxlength="40"></div>
            <div class="ex-f"><label>Emirates ID Expiry</label><input type="date" name="emirates_id_expiry"></div>
            <div class="ex-f"><label>Passport No.</label><input name="passport_no" maxlength="40"></div>
            <div class="ex-f"><label>Visa Expiry</label><input type="date" name="visa_expiry"></div>

            <div class="em-section">Status</div>
            <div class="ex-f"><label>Status <span class="req">*</span></label>
                <select name="status" onchange="document.getElementById('emLeftDate').style.display = this.value === 'left' ? 'block' : 'none'"><option value="active">Active</option><option value="left">Left</option></select>
            </div>
            <div class="ex-f" id="emLeftDate" style="display:none"><label>Left On</label><input type="date" name="left_date"></div>
            <div class="ex-f full"><label>Notes</label><textarea name="notes" rows="2" maxlength="500"></textarea></div>
        </div>
        <div class="ex-dfoot">
            <button type="button" class="ex-cancel" onclick="emClose('emModal')">Cancel</button>
            <button type="submit" class="ex-add"><i class="fas fa-check"></i> Save Employee</button>
        </div>
    </form>
</div>

{{-- Give advance --}}
<div class="ex-modal gl-page" id="advModal" onclick="if (event.target === this) emClose('advModal')">
    <form class="ex-dialog" id="advForm" method="POST" action="">
        {{ csrf_field() }}
        <input type="hidden" name="_advance" value="1">
        <input type="hidden" name="_advance_employee" id="advEmployeeHidden" value="">
        <div class="ex-dhead">
            <span class="gl-ico red"><i class="fas fa-hand-holding-usd"></i></span>
            <div><h3>Give Salary Advance</h3><p>It is cut from the next salary sheets automatically.</p></div>
            <button type="button" class="ex-close" onclick="emClose('advModal')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ex-dbody">
            @if($errors->any() && old('_advance'))
                <div class="ex-errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="ex-f full"><label>Employee <span class="req">*</span></label>
                <select id="advEmployee" required onchange="emAdvanceTarget(this.value)">
                    <option value="">Select employee...</option>
                    @foreach($activeEmployees as $e)<option value="{{ $e->id }}">{{ $e->name }}{{ $e->designation ? ' — ' . $e->designation : '' }}</option>@endforeach
                </select>
            </div>
            <div class="ex-f"><label>Date <span class="req">*</span></label><input type="date" name="given_date" value="{{ old('given_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
            <div class="ex-f"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required></div>
            <div class="ex-f"><label>Cut Per Month <span class="hint">(empty = all next month)</span></label><input type="number" name="installment" step="0.01" min="0.01" value="{{ old('installment') }}"></div>
            <div class="ex-f"><label>Given As</label><select name="method"><option value="">Select...</option>@foreach(['Cash', 'Petty Cash', 'Bank Transfer'] as $m)<option {{ old('method') === $m ? 'selected' : '' }}>{{ $m }}</option>@endforeach</select></div>
            @include('crm.bank_accounts._select', ['baSelected' => old('bank_account_id'), 'baWrapClass' => 'ex-f full'])
            <div class="ex-f full"><label>Note</label><input name="note" maxlength="500" value="{{ old('note') }}"></div>
        </div>
        <div class="ex-dfoot">
            <button type="button" class="ex-cancel" onclick="emClose('advModal')">Cancel</button>
            <button type="submit" class="ex-add"><i class="fas fa-check"></i> Give Advance</button>
        </div>
    </form>
</div>

<script>
    var emStoreUrl = @json(route('crm.employees.store'));
    var emUpdateUrl = @json(route('crm.employees.update', '__ID__'));
    var advStoreUrl = @json(route('crm.employees.advances.store', '__ID__'));
    var emForm = document.getElementById('emForm');

    function emClose(id) { document.getElementById(id).classList.remove('open'); }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { emClose('emModal'); emClose('advModal'); } });

    function emSalaryType(type) {
        document.getElementById('emBasicLabel').innerHTML = (type === 'daily' ? 'Daily Rate (per day)' : 'Basic Salary (per month)') + ' <span class="req">*</span>';
    }

    function emFill(data) {
        emForm.querySelectorAll('input:not([type=hidden]), select, textarea').forEach(function (field) {
            var value = data[field.name];
            if (field.tagName === 'SELECT') {
                field.value = value || field.options[0].value;
            } else {
                field.value = value === undefined || value === null || value === 0 ? '' : value;
            }
        });
        if (data.basic_salary === 0) emForm.querySelector('[name=basic_salary]').value = '0';
        emSalaryType(emForm.querySelector('[name=salary_type]').value);
        document.getElementById('emLeftDate').style.display = emForm.querySelector('[name=status]').value === 'left' ? 'block' : 'none';
    }

    function emOpenCreate() {
        emForm.action = emStoreUrl;
        document.getElementById('emMethod').value = 'POST';
        document.getElementById('emId').value = '';
        document.getElementById('emTitle').textContent = 'Add Employee';
        emFill({});
        document.getElementById('emModal').classList.add('open');
        emForm.querySelector('[name=name]').focus();
    }

    function emOpenEdit(data) {
        emForm.action = emUpdateUrl.replace('__ID__', data.id);
        document.getElementById('emMethod').value = 'PUT';
        document.getElementById('emId').value = data.id;
        document.getElementById('emTitle').textContent = 'Edit ' + data.name;
        emFill(data);
        document.getElementById('emModal').classList.add('open');
    }

    function emAdvanceTarget(id) {
        document.getElementById('advForm').action = id ? advStoreUrl.replace('__ID__', id) : '';
        document.getElementById('advEmployeeHidden').value = id || '';
    }

    function emOpenAdvance(id) {
        var select = document.getElementById('advEmployee');
        select.value = id || '';
        emAdvanceTarget(select.value);
        document.getElementById('advModal').classList.add('open');
    }

    // After a failed save, reopen the right form with what was typed.
    @if($errors->any())
        (function () {
            var old = @json(old());
            if (old._advance) {
                emOpenAdvance(old._advance_employee || '');
            } else if (old._employee_id) {
                emOpenEdit(Object.assign({}, old, { id: old._employee_id }));
            } else {
                emOpenCreate();
                emFill(old);
            }
        })();
    @endif
</script>
@endsection
