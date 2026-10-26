@extends('crm.layout')
@section('title', $run->periodLabel() . ' Salaries')
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $num = function ($value) { $v = (float) $value; return $v == 0 ? '' : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.'); };
    $lines = $run->lines;
    $unpaid = $lines->whereNull('paid_at');
    $statusTone = ['draft' => 'unpaid', 'approved' => 'partial', 'paid' => 'paid'][$run->status];
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')
<style>
    .py-table { min-width: 1250px; }
    .py-table input { width: 100%; min-width: 64px; border: 1px solid var(--line); border-radius: 7px; padding: .4rem .45rem; font-size: .82rem; text-align: right; font-family: inherit; }
    .py-table input.py-note { text-align: left; min-width: 120px; }
    .py-table input:focus { border-color: var(--brand); outline: none; }
    .py-table td { padding: .5rem .5rem !important; }
    .py-net { font-weight: 800; color: var(--ink); white-space: nowrap; font-variant-numeric: tabular-nums; }
    .py-net.neg { color: var(--red); }
    .py-foot td { font-weight: 800; background: var(--well); }
    .py-bar { display: flex; justify-content: space-between; align-items: center; gap: .8rem; flex-wrap: wrap; background: #fff; border: 1px solid var(--line);
        border-radius: 14px; padding: .85rem 1.1rem; margin-bottom: 1rem; }
    .py-bar form { display: inline-flex; gap: .45rem; align-items: center; flex-wrap: wrap; }
    .py-bar select, .py-bar input { border: 1px solid var(--line); border-radius: 8px; padding: .45rem .55rem; font-family: inherit; font-size: .82rem; }
    .py-paid { font-size: .74rem; color: var(--green); font-weight: 700; white-space: nowrap; }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>{{ $run->periodLabel() }} Salary Sheet</strong> — {{ $lines->count() }} employee(s)
            <span class="pi-status {{ $statusTone }}" style="margin-left:.4rem">{{ \App\CrmPayrollRun::STATUSES[$run->status] }}</span>
            @if($run->approved_at)<span class="gl-mutedtext" style="margin-left:.4rem">approved by {{ optional($run->approver)->name }} on {{ $run->approved_at->format('d M Y') }}</span>@endif
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a class="pi-btn" href="{{ route('crm.payroll.index') }}"><i class="fas fa-arrow-left"></i>&nbsp; All Months</a>
            <a class="pi-btn" href="{{ route('crm.payroll.sheet_pdf', $run->id) }}"><i class="fas fa-file-pdf"></i>&nbsp; Sheet PDF</a>
            @if($canManage && !$run->isDraft())<button type="button" class="pi-btn" onclick="document.getElementById('pyWpsModal').classList.add('open')"><i class="fas fa-university"></i>&nbsp; WPS File (SIF)</button>@endif
            @if($run->isDraft() && $canManage)
                <form method="POST" action="{{ route('crm.payroll.destroy', $run->id) }}" onsubmit="return confirm('Delete the {{ $run->periodLabel() }} salary sheet?');">
                    {{ csrf_field() }} {{ method_field('DELETE') }}
                    <button type="submit" class="pi-btn danger"><i class="fas fa-trash"></i>&nbsp; Delete</button>
                </form>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="ex-errors" style="margin-bottom:1rem"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="gl-stats">
        <div class="gl-stat"><div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-coins"></i></span><span class="gl-k">Gross</span></div>
            <div class="gl-v" id="pyGross">{{ $fmt($lines->sum('gross')) }} <small>AED</small></div></div>
        <div class="gl-stat red"><div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-minus-circle"></i></span><span class="gl-k">Deductions</span></div>
            <div class="gl-v" id="pyDeductions">{{ $fmt($lines->sum(fn ($l) => $l->totalDeductions())) }} <small>AED</small></div></div>
        <div class="gl-stat green"><div class="gl-stat-top"><span class="gl-ico green"><i class="fas fa-money-check-alt"></i></span><span class="gl-k">Net Salary</span></div>
            <div class="gl-v" id="pyNet">{{ $fmt($lines->sum('net')) }} <small>AED</small></div>
            <div class="gl-note">{{ $lines->count() - $unpaid->count() }} of {{ $lines->count() }} paid</div></div>
    </div>

    @if($run->isDraft())
        <div class="py-bar">
            <div class="gl-mutedtext"><b>Draft</b> — fill the days, overtime and deductions, click <b>Save</b>, then <b>Approve</b>. Monthly staff lose 1/30 of (basic + allowances) per absent day.</div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                @if($missingCount)
                    <form method="POST" action="{{ route('crm.payroll.add_missing', $run->id) }}">{{ csrf_field() }}
                        <button type="submit" class="pi-btn"><i class="fas fa-user-plus"></i>&nbsp; Add {{ $missingCount }} missing employee(s)</button></form>
                @endif
                @if($canManage)
                    <form method="POST" action="{{ route('crm.payroll.approve', $run->id) }}" onsubmit="if (window.pyDirty) { alert('Save your changes first.'); return false; } return confirm('Approve the {{ $run->periodLabel() }} salaries? The sheet will be locked.');">{{ csrf_field() }}
                        <button type="submit" class="ex-add"><i class="fas fa-check-double"></i> Approve</button></form>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('crm.payroll.update', $run->id) }}" id="pyForm">
            {{ csrf_field() }} {{ method_field('PUT') }}
            <div class="gl-card">
                <div class="gl-tablewrap">
                    <table class="gl-table py-table">
                        <thead><tr>
                            <th style="min-width:170px">Employee</th><th class="gl-r">Basic</th><th class="gl-r">Allowances</th>
                            <th class="gl-r" style="width:90px">Days Worked / Absent</th><th class="gl-r" style="width:80px">OT Hours</th><th class="gl-r" style="width:85px">OT Rate</th>
                            <th class="gl-r" style="width:95px">Bonus</th><th class="gl-r" style="width:100px">Advance Cut</th><th class="gl-r" style="width:100px">Other Deduct.</th>
                            <th class="gl-r" style="width:110px">Net</th><th style="width:150px">Note</th><th style="width:40px"></th>
                        </tr></thead>
                        <tbody>
                        @foreach($lines as $line)
                            <tr class="py-row" data-type="{{ $line->salary_type }}" data-rate="{{ (float) $line->basic_rate }}" data-allowances="{{ (float) $line->allowances }}">
                                <td data-label="Employee"><span class="ex-name">{{ $line->employee_name }}</span><span class="ex-sub">{{ $line->isDaily() ? 'Daily wage' : 'Monthly' }}{{ $line->designation ? ' · ' . $line->designation : '' }}</span></td>
                                <td class="gl-r gl-num" data-label="Basic">{{ $fmt($line->basic_rate) }}<span class="ex-sub">{{ $line->isDaily() ? 'per day' : 'per month' }}</span></td>
                                <td class="gl-r gl-num" data-label="Allowances">{{ $fmt($line->allowances) }}</td>
                                <td data-label="Days">
                                    @if($line->isDaily())
                                        <input type="number" name="lines[{{ $line->id }}][days_worked]" value="{{ $num($line->days_worked) }}" step="0.5" min="0" max="31" title="Days worked" placeholder="worked" data-f="days">
                                    @else
                                        <input type="number" name="lines[{{ $line->id }}][absent_days]" value="{{ $num($line->absent_days) }}" step="0.5" min="0" max="30" title="Absent days" placeholder="absent" data-f="absent">
                                    @endif
                                </td>
                                <td><input type="number" name="lines[{{ $line->id }}][overtime_hours]" value="{{ $num($line->overtime_hours) }}" step="0.5" min="0" data-f="ot"></td>
                                <td><input type="number" name="lines[{{ $line->id }}][overtime_rate]" value="{{ $num($line->overtime_rate) }}" step="0.01" min="0" data-f="otrate"></td>
                                <td><input type="number" name="lines[{{ $line->id }}][bonus]" value="{{ $num($line->bonus) }}" step="0.01" min="0" data-f="bonus"></td>
                                <td><input type="number" name="lines[{{ $line->id }}][advance_deduction]" value="{{ $num($line->advance_deduction) }}" step="0.01" min="0" data-f="advance"></td>
                                <td><input type="number" name="lines[{{ $line->id }}][other_deductions]" value="{{ $num($line->other_deductions) }}" step="0.01" min="0" data-f="other"></td>
                                <td class="gl-r"><span class="py-net">{{ $fmt($line->net) }}</span></td>
                                <td><input class="py-note" name="lines[{{ $line->id }}][note]" value="{{ $line->note }}" maxlength="500"></td>
                                <td><button type="submit" form="pyRemove{{ $line->id }}" class="ex-icon danger" title="Remove from this sheet" onclick="return confirm('Remove this employee from this month\'s sheet?')"><i class="fas fa-times"></i></button></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="pi-actions" style="margin-top:1rem"><button type="submit" class="ex-add"><i class="fas fa-save"></i> Save Salary Sheet</button></div>
        </form>
        @foreach($lines as $line)
            <form id="pyRemove{{ $line->id }}" method="POST" action="{{ route('crm.payroll.remove_line', [$run->id, $line->id]) }}">{{ csrf_field() }} {{ method_field('DELETE') }}</form>
        @endforeach
    @else
        @if($canManage)
        <div class="py-bar">
            @if($unpaid->isNotEmpty())
                <form method="POST" action="{{ route('crm.payroll.pay_all', $run->id) }}" onsubmit="return confirm('Mark {{ $unpaid->count() }} salary(ies) totalling {{ $fmt($unpaid->sum('net')) }} as paid?');">
                    {{ csrf_field() }}
                    <b style="font-size:.85rem">Pay all unpaid ({{ $unpaid->count() }}):</b>
                    <input type="date" name="paid_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
                    <select name="paid_method">@foreach($payMethods as $m)<option>{{ $m }}</option>@endforeach</select>
                    @include('crm.bank_accounts._select', ['baPlain' => true])
                    <input name="paid_reference" placeholder="Reference (optional)" maxlength="120">
                    <button type="submit" class="ex-add"><i class="fas fa-check"></i> Pay All</button>
                </form>
            @else
                <div class="py-paid"><i class="fas fa-check-circle"></i> All salaries for {{ $run->periodLabel() }} are paid.</div>
            @endif
            @if($unpaid->count() === $lines->count())
                <form method="POST" action="{{ route('crm.payroll.reopen', $run->id) }}" onsubmit="return confirm('Move this sheet back to draft so it can be edited?');">{{ csrf_field() }}
                    <button type="submit" class="pi-btn"><i class="fas fa-undo"></i>&nbsp; Back to Draft</button></form>
            @endif
        </div>
        @endif

        <div class="gl-card">
            <div class="gl-tablewrap">
                <table class="gl-table">
                    <thead><tr>
                        <th style="min-width:170px">Employee</th><th class="gl-r">Basic Pay</th><th class="gl-r">Allowances</th><th class="gl-r">Overtime</th><th class="gl-r">Bonus</th>
                        <th class="gl-r">Gross</th><th class="gl-r">Deductions</th><th class="gl-r">Net</th><th style="width:170px">Payment</th><th style="width:90px"></th>
                    </tr></thead>
                    <tbody>
                    @foreach($lines as $line)
                        <tr>
                            <td data-label="Employee"><span class="ex-name">{{ $line->employee_name }}</span><span class="ex-sub">{{ $line->isDaily() ? $num($line->days_worked) . ' days × ' . $fmt($line->basic_rate) : ((float) $line->absent_days > 0 ? $num($line->absent_days) . ' day(s) absent' : 'Monthly') }}{{ $line->note ? ' · ' . $line->note : '' }}</span></td>
                            <td class="gl-r gl-num" data-label="Basic Pay">{{ $fmt($line->basicPay()) }}</td>
                            <td class="gl-r gl-num" data-label="Allowances">{{ $fmt($line->allowances) }}</td>
                            <td class="gl-r gl-num" data-label="Overtime">{{ $fmt($line->overtimeAmount()) }}</td>
                            <td class="gl-r gl-num" data-label="Bonus">{{ $fmt($line->bonus) }}</td>
                            <td class="gl-r gl-num" data-label="Gross">{{ $fmt($line->gross) }}</td>
                            <td class="gl-r gl-num gl-red" data-label="Deductions">{{ $fmt($line->totalDeductions()) }}</td>
                            <td class="gl-r gl-num" data-label="Net"><b>{{ $fmt($line->net) }}</b></td>
                            <td data-label="Payment">
                                @if($line->paid_at)
                                    <span class="py-paid"><i class="fas fa-check"></i> Paid {{ $line->paid_at->format('d M') }} · {{ $line->paid_method }}</span>
                                    @if($canManage)
                                        <form method="POST" action="{{ route('crm.payroll.unpay_line', [$run->id, $line->id]) }}" style="display:inline" onsubmit="return confirm('Undo this payment?');">{{ csrf_field() }} {{ method_field('DELETE') }}
                                            <button type="submit" class="ex-icon danger" title="Undo payment" style="width:24px;height:24px"><i class="fas fa-undo"></i></button></form>
                                    @endif
                                @elseif($canManage)
                                    <button type="button" class="pi-btn" data-line="{{ $line->id }}" data-name="{{ $line->employee_name }}" data-net="{{ $fmt($line->net) }}" data-method="{{ $line->payment_method }}" onclick="pyOpenPay(this.dataset)"><i class="fas fa-money-bill-wave"></i>&nbsp; Pay</button>
                                @else
                                    <span class="pi-status unpaid">Unpaid</span>
                                @endif
                            </td>
                            <td data-label=""><a class="pi-btn" href="{{ route('crm.payroll.payslip', [$run->id, $line->id]) }}" target="_blank" title="Payslip PDF"><i class="fas fa-file-pdf"></i>&nbsp; Slip</a></td>
                        </tr>
                    @endforeach
                    <tr class="py-foot">
                        <td>Total</td>
                        <td class="gl-r">{{ $fmt($lines->sum(fn ($l) => $l->basicPay())) }}</td>
                        <td class="gl-r">{{ $fmt($lines->sum('allowances')) }}</td>
                        <td class="gl-r">{{ $fmt($lines->sum(fn ($l) => $l->overtimeAmount())) }}</td>
                        <td class="gl-r">{{ $fmt($lines->sum('bonus')) }}</td>
                        <td class="gl-r">{{ $fmt($lines->sum('gross')) }}</td>
                        <td class="gl-r">{{ $fmt($lines->sum(fn ($l) => $l->totalDeductions())) }}</td>
                        <td class="gl-r">{{ $fmt($lines->sum('net')) }}</td>
                        <td colspan="2"></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pay one salary --}}
        <div class="ex-modal gl-page" id="pyPayModal" onclick="if (event.target === this) this.classList.remove('open')">
            <form class="ex-dialog" id="pyPayForm" method="POST" action="" style="max-width:520px">
                {{ csrf_field() }}
                <div class="ex-dhead">
                    <span class="gl-ico green"><i class="fas fa-money-bill-wave"></i></span>
                    <div><h3 id="pyPayTitle">Pay Salary</h3><p id="pyPayAmount"></p></div>
                    <button type="button" class="ex-close" onclick="document.getElementById('pyPayModal').classList.remove('open')" aria-label="Close"><i class="fas fa-times"></i></button>
                </div>
                <div class="ex-dbody">
                    <div class="ex-f"><label>Paid On <span class="req">*</span></label><input type="date" name="paid_at" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></div>
                    <div class="ex-f"><label>Method <span class="req">*</span></label><select name="paid_method" id="pyPayMethod">@foreach($payMethods as $m)<option>{{ $m }}</option>@endforeach</select></div>
                    @include('crm.bank_accounts._select', ['baWrapClass' => 'ex-f full'])
                    <div class="ex-f full"><label>Reference <span class="hint">(optional)</span></label><input name="paid_reference" maxlength="120" placeholder="Transfer # / voucher #"></div>
                </div>
                <div class="ex-dfoot">
                    <button type="button" class="ex-cancel" onclick="document.getElementById('pyPayModal').classList.remove('open')">Cancel</button>
                    <button type="submit" class="ex-add"><i class="fas fa-check"></i> Mark Paid</button>
                </div>
            </form>
        </div>
    @endif
</div>

<script>
    var pyPayUrl = @json(route('crm.payroll.pay_line', [$run->id, '__LINE__']));
    function pyOpenPay(data) {
        document.getElementById('pyPayForm').action = pyPayUrl.replace('__LINE__', data.line);
        document.getElementById('pyPayTitle').textContent = 'Pay ' + data.name;
        document.getElementById('pyPayAmount').textContent = 'Net salary: ' + data.net + ' AED';
        if (data.method) document.getElementById('pyPayMethod').value = data.method;
        document.getElementById('pyPayModal').classList.add('open');
    }

    // Draft sheet: recalculate each row's net (same rules as the server) while typing.
    (function () {
        var rows = document.querySelectorAll('.py-row');
        if (!rows.length) return;
        var money = function (n) { return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        var val = function (row, f) { var el = row.querySelector('[data-f="' + f + '"]'); return el ? (parseFloat(el.value) || 0) : 0; };
        var round2 = function (n) { return Math.round(n * 100) / 100; };
        function recalc() {
            var gross = 0, deductions = 0, net = 0;
            rows.forEach(function (row) {
                var rate = parseFloat(row.dataset.rate) || 0, allowances = parseFloat(row.dataset.allowances) || 0, daily = row.dataset.type === 'daily';
                var basic = daily ? round2(rate * val(row, 'days')) : rate;
                var absence = daily ? 0 : round2((rate + allowances) / 30 * val(row, 'absent'));
                var g = round2(basic + allowances + round2(val(row, 'ot') * val(row, 'otrate')) + val(row, 'bonus'));
                var d = round2(absence + val(row, 'advance') + val(row, 'other'));
                var n = round2(g - d);
                var cell = row.querySelector('.py-net');
                cell.textContent = money(n);
                cell.classList.toggle('neg', n < 0);
                gross += g; deductions += d; net += n;
            });
            document.getElementById('pyGross').innerHTML = money(gross) + ' <small>AED</small>';
            document.getElementById('pyDeductions').innerHTML = money(deductions) + ' <small>AED</small>';
            document.getElementById('pyNet').innerHTML = money(net) + ' <small>AED</small>';
        }
        document.getElementById('pyForm').addEventListener('input', function () { window.pyDirty = true; recalc(); });
        recalc();
    })();
</script>

@if($canManage && !$run->isDraft())
{{-- WPS salary file for the bank --}}
<div class="ex-modal gl-page" id="pyWpsModal" onclick="if (event.target === this) this.classList.remove('open')">
    <form class="ex-dialog" method="POST" action="{{ route('crm.payroll.wps', $run->id) }}" style="max-width:520px">
        {{ csrf_field() }}
        <div class="ex-dhead">
            <span class="gl-ico brand"><i class="fas fa-university"></i></span>
            <div><h3>WPS Salary File — {{ $run->periodLabel() }}</h3><p>Salary Information File (.SIF) to upload to the bank for employees paid by Bank Transfer.</p></div>
            <button type="button" class="ex-close" onclick="document.getElementById('pyWpsModal').classList.remove('open')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ex-dbody">
            <div class="ex-f full"><label>Company MOL Establishment ID <span class="req">*</span></label><input name="establishment_id" value="{{ \App\CrmSetting::get('wps_establishment_id') }}" maxlength="20" required placeholder="13 digits"></div>
            <div class="ex-f full"><label>Company Bank Routing Code <span class="req">*</span></label><input name="employer_routing_code" value="{{ \App\CrmSetting::get('wps_employer_routing_code') }}" maxlength="20" required placeholder="9 digits"></div>
            <div class="ex-current-proof full" style="margin:0">Each employee paid by Bank Transfer needs <b>IBAN</b>, <b>Bank Routing Code</b> and <b>MOL Person ID</b> on the Employees page. Ask your bank to confirm their exact SIF format before the first upload.</div>
        </div>
        <div class="ex-dfoot">
            <button type="button" class="ex-cancel" onclick="document.getElementById('pyWpsModal').classList.remove('open')">Cancel</button>
            <button type="submit" class="ex-add"><i class="fas fa-download"></i> Download .SIF</button>
        </div>
    </form>
</div>
@endif
@endsection
