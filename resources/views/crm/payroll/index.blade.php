@extends('crm.layout')
@section('title', 'Salaries')
@section('content')
@php $fmt = function ($value) { return number_format((float) $value, 2); }; @endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Salaries</strong> — one salary sheet per month: fill days, overtime and deductions, approve, then mark salaries paid. Paid salaries show under Payments.</div>
        <form method="POST" action="{{ route('crm.payroll.store') }}" style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            {{ csrf_field() }}
            <input type="month" name="period" value="{{ old('period', $nextPeriod) }}" required style="border:1px solid #e2e8f0;border-radius:9px;padding:.55rem .7rem;font-family:inherit">
            <button type="submit" class="ex-add"><i class="fas fa-plus"></i> New Salary Sheet</button>
        </form>
    </div>

    <div class="gl-stats">
        <div class="gl-stat">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-calendar-check"></i></span><span class="gl-k">Net Salaries {{ now()->year }}</span></div>
            <div class="gl-v">{{ $fmt($totals['year']) }} <small>AED</small></div>
        </div>
        <div class="gl-stat red">
            <div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-hourglass-half"></i></span><span class="gl-k">Approved, Not Paid</span></div>
            <div class="gl-v">{{ $fmt($totals['unpaid']) }} <small>AED</small></div>
        </div>
        <a href="{{ route('crm.employees.index') }}" class="gl-stat gl-stat-link">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-users"></i></span><span class="gl-k">Active Employees</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
            <div class="gl-v">{{ $totals['employees'] }}</div>
        </a>
    </div>

    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="width:160px">Month</th><th style="width:100px">Employees</th><th class="gl-r">Gross</th><th class="gl-r">Deductions</th>
                    <th class="gl-r">Net Salary</th><th style="width:120px">Paid</th><th style="width:110px">Status</th><th style="width:80px"></th>
                </tr></thead>
                <tbody>
                @forelse($runs as $run)
                    @php $lines = $run->lines; $paidCount = $lines->whereNotNull('paid_at')->count(); @endphp
                    <tr>
                        <td data-label="Month"><span class="ex-name">{{ $run->periodLabel() }}</span></td>
                        <td data-label="Employees">{{ $lines->count() }}</td>
                        <td class="gl-r gl-num" data-label="Gross">{{ $fmt($lines->sum('gross')) }}</td>
                        <td class="gl-r gl-num gl-red" data-label="Deductions">{{ $fmt($lines->sum(fn ($l) => $l->totalDeductions())) }}</td>
                        <td class="gl-r gl-num" data-label="Net Salary">{{ $fmt($lines->sum('net')) }} <small>AED</small></td>
                        <td data-label="Paid">{{ $paidCount }} / {{ $lines->count() }}</td>
                        <td data-label="Status"><span class="pi-status {{ ['draft' => 'unpaid', 'approved' => 'partial', 'paid' => 'paid'][$run->status] }}">{{ \App\CrmPayrollRun::STATUSES[$run->status] }}</span></td>
                        <td data-label=""><a class="pi-btn" href="{{ route('crm.payroll.show', $run->id) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="gl-empty"><i class="fas fa-money-check-alt"></i>No salary sheets yet — pick a month above and click "New Salary Sheet".</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
