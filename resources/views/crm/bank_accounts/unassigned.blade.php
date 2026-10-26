@extends('crm.layout')
@section('title', 'Unassigned Receipts & Payments')
@section('content')
@php $fmt = fn ($v) => number_format((float) $v, 2); @endphp

@include('crm.general_ledger._styles')
@include('crm.purchase_invoices._styles')
<style>.ba-assign select { border: 1px solid var(--line); border-radius: 7px; padding: .3rem .4rem; font-size: .76rem; font-family: inherit; background: #fff; }</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Not booked to any account</strong> — no account was chosen for these, and no account picks up their payment method. Pick the account each one went through, or tick "pick up automatically" methods on an account.</div>
        <a class="pi-btn" href="{{ route('crm.bank_accounts.index') }}"><i class="fas fa-arrow-left"></i>&nbsp; All Accounts</a>
    </div>
    <div class="gl-count">{{ $movements->count() }} entr{{ $movements->count() === 1 ? 'y' : 'ies' }} · in {{ $fmt($movements->where('dir', 'in')->sum('amount')) }} · out {{ $fmt($movements->where('dir', 'out')->sum('amount')) }}</div>
    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr><th style="width:100px">Date</th><th style="width:140px">Type</th><th>Paid To / Received From</th><th style="width:140px">Ref</th><th style="width:130px">Method</th><th class="gl-r" style="width:120px">In</th><th class="gl-r" style="width:120px">Out</th>@if($canManage)<th style="width:190px">Book to</th>@endif</tr></thead>
                <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td class="gl-date">{{ $m->date->format('d M Y') }}</td>
                        <td>{{ $m->type }}</td>
                        <td><span class="gl-name">{{ $m->party }}</span></td>
                        <td>@if($m->url)<a href="{{ $m->url }}" style="color:var(--brand);font-weight:700;text-decoration:none">{{ $m->ref ?: 'Open' }}</a>@else{{ $m->ref ?: '—' }}@endif</td>
                        <td class="gl-mutedtext">{{ $m->method ?: '—' }}</td>
                        <td class="gl-r gl-num gl-green">{{ $m->dir === 'in' ? $fmt($m->amount) : '' }}</td>
                        <td class="gl-r gl-num gl-red">{{ $m->dir === 'out' ? $fmt($m->amount) : '' }}</td>
                        @if($canManage)<td>@include('crm.bank_accounts._assign', ['m' => $m])</td>@endif
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="gl-empty"><i class="fas fa-check-circle"></i>Every receipt and payment is booked to an account.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
