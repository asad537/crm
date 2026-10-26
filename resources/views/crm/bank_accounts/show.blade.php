@extends('crm.layout')
@section('title', $account->name)
@section('content')
@php
    $fmt = fn ($v) => ((float) $v < -0.004 ? '- ' . number_format(abs((float) $v), 2) : number_format((float) $v, 2));
    $isFiltered = $filters['date_from'] || $filters['date_to'];
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')
<style>
    .ba-assign select { border: 1px solid var(--line); border-radius: 7px; padding: .3rem .4rem; font-size: .74rem; font-family: inherit; max-width: 170px; background: #fff; }
    .ba-bal { font-weight: 800; color: #2563eb; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ba-bal.neg { color: var(--red); }
    .ba-bf td { background: var(--well); font-weight: 700; color: var(--muted); }
    .ba-transfer td:first-child { border-left: 3px solid #7c3aed; }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>{{ $account->name }}</strong> — {{ $account->type === 'cash' ? 'Cash' : 'Bank' }} account{{ $account->bank_name ? ' · ' . $account->bank_name : '' }}{{ $account->account_number ? ' · ' . $account->account_number : '' }} · opening {{ $fmt($account->opening_balance) }} on {{ $account->opening_date->format('d M Y') }}</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            @if($canManage)<a class="pi-btn" href="{{ route('crm.bank_accounts.reconcile', $account->id) }}"><i class="fas fa-check-double"></i>&nbsp; Reconcile</a>@endif
            <a class="pi-btn" href="{{ route('crm.bank_accounts.index') }}"><i class="fas fa-arrow-left"></i>&nbsp; All Accounts</a>
        </div>
    </div>

    <div class="gl-stats">
        <div class="gl-stat"><div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-flag"></i></span><span class="gl-k">{{ $isFiltered ? 'Balance Brought Forward' : 'Opening Balance' }}</span></div>
            <div class="gl-v">{{ $fmt($statement['brought_forward']) }} <small>{{ $account->currency }}</small></div></div>
        <div class="gl-stat green"><div class="gl-stat-top"><span class="gl-ico green"><i class="fas fa-arrow-down"></i></span><span class="gl-k">Money In</span></div>
            <div class="gl-v">{{ $fmt($statement['in']) }} <small>{{ $account->currency }}</small></div></div>
        <div class="gl-stat red"><div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-arrow-up"></i></span><span class="gl-k">Money Out</span></div>
            <div class="gl-v">{{ $fmt($statement['out']) }} <small>{{ $account->currency }}</small></div></div>
        <div class="gl-stat"><div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-wallet"></i></span><span class="gl-k">{{ $isFiltered ? 'Closing Balance' : 'Actual Balance' }}</span></div>
            <div class="gl-v {{ $statement['closing'] < 0 ? 'fr-neg' : '' }}" style="{{ $statement['closing'] < 0 ? 'color:#dc2626' : '' }}">{{ $fmt($statement['closing']) }} <small>{{ $account->currency }}</small></div></div>
    </div>

    <div class="gl-filterbar">
        <form class="gl-filters" method="GET" action="{{ route('crm.bank_accounts.show', $account->id) }}">
            <div class="gl-field"><label>From</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" onchange="this.form.submit()"></div>
            <div class="gl-field"><label>To</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" onchange="this.form.submit()"></div>
            @if($isFiltered)<a class="gl-reset" href="{{ route('crm.bank_accounts.show', $account->id) }}">Reset</a>@endif
        </form>
        @if($canManage)<div class="gl-mutedtext" style="font-size:.75rem;max-width:360px">Wrong account? Use the <b>Booked to</b> list on a row to move that receipt / payment to another account.</div>@endif
    </div>

    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table" style="min-width:1000px">
                <thead><tr>
                    <th style="width:100px">Date</th><th style="width:130px">Type</th><th style="min-width:180px">Paid To / Received From</th><th style="width:140px">Ref</th>
                    <th class="gl-r" style="width:120px">In</th><th class="gl-r" style="width:120px">Out</th><th class="gl-r" style="width:140px">Balance</th>
                    @if($canManage)<th style="width:180px">Booked to</th>@endif
                </tr></thead>
                <tbody>
                @forelse($statement['rows'] as $e)
                    <tr class="{{ $e->transfer_id ? 'ba-transfer' : '' }}">
                        <td class="gl-date" data-label="Date">{{ $e->date->format('d M Y') }}</td>
                        <td data-label="Type">{{ $e->type }}@if($e->method)<span class="ex-sub">{{ $e->method }}</span>@endif</td>
                        <td data-label="Party"><span class="gl-name">{{ $e->party }}</span>@if(!empty($e->note))<span class="ex-sub">{{ $e->note }}</span>@endif</td>
                        <td data-label="Ref">@if($e->url)<a href="{{ $e->url }}" style="color:var(--brand);font-weight:700;text-decoration:none">{{ $e->ref ?: 'Open' }}</a>@else<span class="gl-mutedtext">{{ $e->ref ?: '—' }}</span>@endif</td>
                        <td class="gl-r gl-num gl-green" data-label="In">{{ $e->in > 0 ? $fmt($e->in) : '' }}</td>
                        <td class="gl-r gl-num gl-red" data-label="Out">{{ $e->out > 0 ? $fmt($e->out) : '' }}</td>
                        <td class="gl-r" data-label="Balance"><span class="ba-bal {{ $e->balance < 0 ? 'neg' : '' }}">{{ $fmt($e->balance) }}</span></td>
                        @if($canManage)
                            <td data-label="Booked to">
                                @if($e->transfer_id)
                                    <form method="POST" action="{{ route('crm.bank_accounts.transfers.destroy', $e->transfer_id) }}" onsubmit="return confirm('Delete this transfer?');">{{ csrf_field() }} {{ method_field('DELETE') }}<button type="submit" class="pi-btn danger" style="padding:.25rem .6rem;font-size:.72rem"><i class="fas fa-trash"></i>&nbsp; Transfer</button></form>
                                @else
                                    @include('crm.bank_accounts._assign', ['m' => (object) ['link_type' => $e->link_type, 'link_id' => $e->link_id, 'account_id' => $account->id, 'auto' => $e->auto]])
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManage ? 8 : 7 }}"><div class="gl-empty"><i class="fas fa-university"></i>No receipts, payments or transfers in this account {{ $isFiltered ? 'for these dates' : 'since the opening date' }}.</div></td></tr>
                @endforelse
                <tr class="ba-bf">
                    <td>{{ $isFiltered && $filters['date_from'] ? \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') : $account->opening_date->format('d M Y') }}</td>
                    <td colspan="5">{{ $isFiltered ? 'Balance brought forward' : 'Opening balance' }}</td>
                    <td class="gl-r"><span class="ba-bal">{{ $fmt($statement['brought_forward']) }}</span></td>
                    @if($canManage)<td></td>@endif
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
