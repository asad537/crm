@extends('crm.layout')
@section('title', $employee->name . ' - Ledger')
@section('content')
@php
    $fmt = fn ($v) => ((float) $v < -0.004 ? '- ' . number_format(abs((float) $v), 2) : number_format((float) $v, 2));
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>{{ $employee->name }}</strong> — ledger{{ $employee->designation ? ' · ' . $employee->designation : '' }}{{ $employee->employee_code ? ' · ' . $employee->employee_code : '' }}</div>
        <a class="pi-btn" href="{{ route('crm.employees.index') }}"><i class="fas fa-arrow-left"></i>&nbsp; All Employees</a>
    </div>

    <div class="gl-stats">
        <div class="gl-stat"><div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-money-check-alt"></i></span><span class="gl-k">Salary Earned</span></div>
            <div class="gl-v">{{ $fmt($earned) }} <small>AED</small></div><div class="gl-note">approved salary sheets</div></div>
        <div class="gl-stat green"><div class="gl-stat-top"><span class="gl-ico green"><i class="fas fa-hand-holding-usd"></i></span><span class="gl-k">Salary Paid</span></div>
            <div class="gl-v">{{ $fmt($paid) }} <small>AED</small></div></div>
        <div class="gl-stat red"><div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-arrow-up"></i></span><span class="gl-k">Advances Given</span></div>
            <div class="gl-v">{{ $fmt($advanced) }} <small>AED</small></div><div class="gl-note">open advance {{ $fmt($employee->advanceBalance()) }}</div></div>
        <div class="gl-stat {{ $balance < -0.004 ? 'red' : '' }}"><div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-wallet"></i></span><span class="gl-k">Balance</span></div>
            <div class="gl-v">{{ number_format(abs($balance), 2) }} <small>AED</small></div>
            <div class="gl-note">{{ abs($balance) <= 0.004 ? 'settled' : ($balance > 0 ? 'company owes the employee' : 'employee owes the company') }}</div></div>
    </div>

    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table" style="min-width:800px">
                <thead><tr>
                    <th style="width:110px">Date</th><th style="width:130px">Type</th><th style="width:140px">Ref</th><th>Details</th>
                    <th class="gl-r" style="width:120px">Earned</th><th class="gl-r" style="width:120px">Paid / Given</th><th class="gl-r" style="width:130px">Balance</th>
                </tr></thead>
                <tbody>
                @forelse($rows as $e)
                    <tr>
                        <td class="gl-date" data-label="Date">{{ $e->date->format('d M Y') }}</td>
                        <td data-label="Type">{{ $e->type }}</td>
                        <td data-label="Ref"><a href="{{ $e->url }}" style="color:var(--brand);font-weight:700;text-decoration:none">{{ $e->ref }}</a></td>
                        <td class="gl-mutedtext" data-label="Details">{{ $e->detail ?: '—' }}</td>
                        <td class="gl-r gl-num gl-green" data-label="Earned">{{ $e->earned > 0 ? $fmt($e->earned) : '' }}</td>
                        <td class="gl-r gl-num gl-red" data-label="Paid / Given">{{ $e->paid > 0 ? $fmt($e->paid) : '' }}</td>
                        <td class="gl-r gl-num" data-label="Balance"><b>{{ $fmt($e->balance) }}</b></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="gl-empty"><i class="fas fa-id-badge"></i>No approved salaries or advances for this employee yet.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
