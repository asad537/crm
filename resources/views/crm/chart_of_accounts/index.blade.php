@extends('crm.layout')
@section('title', 'Chart of Accounts')
@section('content')
@php
    $fmt = fn ($v) => ((float) $v < -0.004 ? '(' . number_format(abs((float) $v), 2) . ')' : number_format((float) $v, 2));
    $groups = collect($chart['groups'])->keyBy('key');
    $expenses = $groups['cos']['total'] + $groups['opex']['total'];
    $tones = ['assets' => '#2563eb', 'liabilities' => '#d97706', 'equity' => '#06265e', 'income' => '#16a34a', 'cos' => '#f45a24', 'opex' => '#dc2626'];
@endphp

@include('crm.general_ledger._styles')
<style>
    .coa-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .8rem; margin-bottom: 1.1rem; }
    .coa-sum { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: .85rem 1rem; border-top: 3px solid var(--c); }
    .coa-sum span { display: block; font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--faint); }
    .coa-sum b { display: block; font-size: 1.15rem; color: var(--ink); margin-top: .25rem; font-variant-numeric: tabular-nums; }
    .coa-sum small { font-size: .68rem; color: var(--muted); }
    .coa-group { margin-bottom: 1rem; }
    .coa-head { display: flex; align-items: center; gap: .6rem; padding: .9rem 1.15rem; border-bottom: 1px solid var(--line); }
    .coa-head .ic { width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; color: #fff; background: var(--c); font-size: .8rem; }
    .coa-head h3 { margin: 0; font-size: .95rem; color: var(--ink); }
    .coa-head .chip { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); background: var(--well); border: 1px solid var(--line); padding: .15rem .5rem; border-radius: 999px; }
    .coa-head .tot { margin-left: auto; font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; }
    .coa-head .act { font-size: .74rem; font-weight: 700; color: var(--brand); text-decoration: none; margin-left: .8rem; white-space: nowrap; }
    .coa-table { width: 100%; border-collapse: collapse; }
    .coa-table td { padding: .62rem 1.15rem; border-bottom: 1px solid var(--line-soft); font-size: .86rem; }
    .coa-table tr:last-child td { border-bottom: none; }
    .coa-table tr:hover td { background: color-mix(in srgb, var(--brand) 3%, white); }
    .coa-table td.r { text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: var(--ink); white-space: nowrap; }
    .coa-table td.neg { color: var(--red); }
    .coa-table a { color: var(--ink); text-decoration: none; font-weight: 600; }
    .coa-table a:hover { color: var(--brand); text-decoration: underline; }
    .coa-table .go { color: var(--faint); font-size: .7rem; margin-left: .35rem; }
    .coa-empty { padding: .9rem 1.15rem; font-size: .82rem; color: var(--muted); }
    .coa-note { font-size: .74rem; color: var(--muted); margin-top: .4rem; }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Chart of Accounts</strong> — every account the CRM keeps, with its balance. Assets, Liabilities and Equity are <b>as of today</b>; Income and Expenses are <b>{{ $chart['period'] }}</b>. Click an account to open its entries.</div>
    </div>

    <div class="coa-summary">
        <div class="coa-sum" style="--c:{{ $tones['assets'] }}"><span>Assets</span><b>{{ $fmt($groups['assets']['total']) }}</b><small>what the business has</small></div>
        <div class="coa-sum" style="--c:{{ $tones['liabilities'] }}"><span>Liabilities</span><b>{{ $fmt($groups['liabilities']['total']) }}</b><small>what it owes</small></div>
        <div class="coa-sum" style="--c:{{ $tones['equity'] }}"><span>Equity</span><b>{{ $fmt($groups['equity']['total']) }}</b><small>owners' share = assets − liabilities</small></div>
        <div class="coa-sum" style="--c:{{ $tones['income'] }}"><span>Income (this year)</span><b>{{ $fmt($groups['income']['total']) }}</b></div>
        <div class="coa-sum" style="--c:{{ $tones['opex'] }}"><span>Expenses (this year)</span><b>{{ $fmt($expenses) }}</b></div>
        <div class="coa-sum" style="--c:{{ $chart['net_profit'] < 0 ? '#dc2626' : '#16a34a' }}"><span>Net Profit (this year)</span><b style="color:{{ $chart['net_profit'] < 0 ? '#dc2626' : '#16a34a' }}">{{ $fmt($chart['net_profit']) }}</b></div>
    </div>

    @foreach($chart['groups'] as $group)
        <div class="gl-card coa-group" style="--c:{{ $tones[$group['key']] }}">
            <div class="coa-head">
                <span class="ic"><i class="fas {{ $group['icon'] }}"></i></span>
                <h3>{{ $group['title'] }}</h3>
                <span class="chip">{{ $group['statement'] }}</span>
                @if($group['key'] === 'equity')<a class="act" href="{{ route('crm.owner_capital.index') }}"><i class="fas fa-user-tie"></i> Owner's Capital</a>@endif
                @if($group['key'] === 'assets')<a class="act" href="{{ route('crm.bank_accounts.index') }}"><i class="fas fa-university"></i> Bank &amp; Cash</a>@endif
                <span class="tot">{{ $fmt($group['total']) }}</span>
            </div>
            @if(count($group['accounts']))
                <table class="coa-table">
                    @foreach($group['accounts'] as [$name, $balance, $url])
                        <tr>
                            <td>@if($url)<a href="{{ $url }}">{{ $name }}<i class="fas fa-arrow-right go"></i></a>@else{{ $name }}@endif</td>
                            <td class="r {{ $balance < -0.004 ? 'neg' : '' }}">{{ $fmt($balance) }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div class="coa-empty">Nothing recorded {{ $group['statement'] === 'Profit & Loss' ? 'this year' : 'yet' }}.</div>
            @endif
        </div>
    @endforeach

    <div class="coa-note">Amounts in brackets are negative. Income and expense accounts are the categories used on invoices, sales, purchases, payments and salaries. Equity = Assets − Liabilities: owner's capital and drawings come from Owner's Capital, retained earnings from Profit &amp; Loss, and anything not yet explained (e.g. opening balances) shows as the balancing figure.</div>
</div>
@endsection
