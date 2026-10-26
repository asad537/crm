@extends('crm.layout')
@section('title', 'Reconcile ' . $account->name)
@section('content')
@php $fmt = fn ($v) => ((float) $v < -0.004 ? '- ' . number_format(abs((float) $v), 2) : number_format((float) $v, 2)); @endphp

@include('crm.general_ledger._styles')
@include('crm.purchase_invoices._styles')
<style>
    .br-wrap { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1rem; align-items: start; }
    @media (max-width: 1100px) { .br-wrap { grid-template-columns: 1fr; } }
    .br-sum { position: sticky; top: 1rem; background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 1rem 1.1rem; }
    .br-sum h4 { margin: 0 0 .7rem; font-size: .9rem; color: var(--ink); }
    .br-line { display: flex; justify-content: space-between; gap: .6rem; padding: .42rem 0; font-size: .84rem; color: var(--body); font-variant-numeric: tabular-nums; }
    .br-line b { color: var(--ink); }
    .br-line.total { border-top: 2px solid var(--line); margin-top: .3rem; padding-top: .6rem; font-weight: 800; color: var(--ink); }
    .br-diff { margin-top: .7rem; padding: .7rem .8rem; border-radius: 12px; font-weight: 800; display: flex; justify-content: space-between; }
    .br-diff.ok { background: #dcfce7; color: #15803d; }
    .br-diff.bad { background: #fee2e2; color: #b91c1c; }
    .br-sum input { width: 100%; border: 1px solid var(--line); border-radius: 9px; padding: .5rem .6rem; font-family: inherit; font-size: .9rem; text-align: right; }
    .br-row.is-cleared td { background: #f0fdf4; }
    .br-check { width: 18px; height: 18px; accent-color: #16a34a; cursor: pointer; }
    .br-top { display: flex; gap: .7rem; align-items: flex-end; flex-wrap: wrap; }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Reconcile {{ $account->name }}</strong> — tick every entry that appears on the bank statement up to {{ $date->format('d M Y') }}. When the difference is 0, the CRM matches the bank.</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a class="pi-btn" href="{{ route('crm.bank_accounts.show', $account->id) }}"><i class="fas fa-arrow-left"></i>&nbsp; Statement</a>
            <a class="pi-btn" href="{{ route('crm.financial_reports.index', ['report' => 'bank-reconciliation']) }}"><i class="fas fa-check-double"></i>&nbsp; All accounts</a>
        </div>
    </div>

    <div class="gl-filterbar">
        <form class="br-top" method="GET" action="{{ route('crm.bank_accounts.reconcile', $account->id) }}">
            <div class="gl-field"><label>Statement Date</label><input type="date" name="statement_date" value="{{ $date->toDateString() }}" onchange="this.form.submit()"></div>
            <input type="hidden" name="statement_balance" value="{{ $statementBalance }}">
            <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;font-weight:700;color:var(--muted);padding-bottom:.6rem">
                <input type="checkbox" name="show_cleared" value="1" {{ $showCleared ? 'checked' : '' }} onchange="this.form.submit()"> Show entries already cleared</label>
        </form>
    </div>

    <form method="POST" action="{{ route('crm.bank_accounts.reconcile.save', $account->id) }}" id="brForm">
        {{ csrf_field() }}
        <input type="hidden" name="statement_date" value="{{ $date->toDateString() }}">
        <div class="br-wrap">
            <div class="gl-card">
                <div class="fr-card-title" style="padding:.85rem 1.15rem;font-weight:800;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:.6rem">
                    Entries up to {{ $date->format('d M Y') }} @if($hiddenCleared)<span class="gl-mutedtext" style="font-weight:600;font-size:.75rem">({{ $hiddenCleared }} already cleared, hidden)</span>@endif
                    <button type="button" class="pi-btn" style="margin-left:auto" onclick="brTickAll(true)"><i class="fas fa-check"></i>&nbsp; Tick all</button>
                    <button type="button" class="pi-btn" onclick="brTickAll(false)">Untick all</button>
                </div>
                <div class="gl-tablewrap">
                    <table class="gl-table" style="min-width:760px">
                        <thead><tr><th style="width:50px">Cleared</th><th style="width:100px">Date</th><th style="width:130px">Type</th><th>Paid To / Received From</th><th style="width:130px">Ref</th><th class="gl-r" style="width:115px">In</th><th class="gl-r" style="width:115px">Out</th></tr></thead>
                        <tbody>
                        @forelse($rows as $r)
                            <tr class="br-row {{ $r->cleared ? 'is-cleared' : '' }}">
                                <td><input type="hidden" name="shown[]" value="{{ $r->key }}"><input class="br-check" type="checkbox" name="cleared[]" value="{{ $r->key }}" data-in="{{ $r->in }}" data-out="{{ $r->out }}" {{ $r->cleared ? 'checked' : '' }} onchange="brRecalc()" title="{{ $r->cleared ? 'Cleared on ' . $r->cleared->cleared_date->format('d M Y') : 'Tick if it is on the bank statement' }}"></td>
                                <td class="gl-date">{{ $r->date->format('d M Y') }}</td>
                                <td>{{ $r->type }}@if($r->method)<span class="ex-sub" style="display:block;font-size:.68rem;color:var(--faint)">{{ $r->method }}</span>@endif</td>
                                <td><span class="gl-name">{{ $r->party }}</span></td>
                                <td>@if($r->url)<a href="{{ $r->url }}" style="color:var(--brand);font-weight:700;text-decoration:none">{{ $r->ref ?: 'Open' }}</a>@else<span class="gl-mutedtext">{{ $r->ref ?: '—' }}</span>@endif</td>
                                <td class="gl-r gl-num gl-green">{{ $r->in > 0 ? $fmt($r->in) : '' }}</td>
                                <td class="gl-r gl-num gl-red">{{ $r->out > 0 ? $fmt($r->out) : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="gl-empty"><i class="fas fa-check-circle"></i>Nothing left to tick up to this date.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="br-sum">
                <h4><i class="fas fa-balance-scale" style="color:var(--brand)"></i> Reconciliation</h4>
                <div class="br-line"><span>Balance in CRM on {{ $date->format('d M Y') }}</span><b>{{ $fmt($bookBalance) }}</b></div>
                <div class="br-line"><span>Less: money in not on statement</span><b id="brIn">0.00</b></div>
                <div class="br-line"><span>Add: money out not on statement</span><b id="brOut">0.00</b></div>
                <div class="br-line total"><span>Should be on the statement</span><span id="brExpected">0.00</span></div>
                <div style="margin-top:.8rem"><label style="font-size:.7rem;font-weight:800;color:var(--muted)">Balance on the bank statement *</label>
                    <input type="number" step="0.01" name="statement_balance" id="brStatement" value="{{ $statementBalance }}" required oninput="brRecalc()" placeholder="0.00"></div>
                <div class="br-diff" id="brDiff"><span>Difference</span><span id="brDiffValue">—</span></div>
                <div style="margin-top:.8rem"><label style="font-size:.7rem;font-weight:800;color:var(--muted)">Note</label>
                    <input name="note" maxlength="500" style="text-align:left" placeholder="optional"></div>
                <button type="submit" class="ex-add" style="width:100%;justify-content:center;margin-top:.9rem"><i class="fas fa-check"></i> Save Reconciliation</button>
            </div>
        </div>
    </form>

    @if($history->isNotEmpty())
        <div class="gl-card" style="margin-top:1rem">
            <div style="padding:.85rem 1.15rem;font-weight:800;border-bottom:1px solid var(--line)">Past Reconciliations</div>
            <div class="gl-tablewrap">
                <table class="gl-table" style="min-width:700px">
                    <thead><tr><th>Statement Date</th><th class="gl-r">Statement</th><th class="gl-r">CRM Balance</th><th class="gl-r">Uncleared In</th><th class="gl-r">Uncleared Out</th><th class="gl-r">Difference</th><th>By</th></tr></thead>
                    <tbody>
                    @foreach($history as $h)
                        <tr>
                            <td class="gl-date">{{ $h->statement_date->format('d M Y') }}</td>
                            <td class="gl-r gl-num">{{ $fmt($h->statement_balance) }}</td>
                            <td class="gl-r gl-num">{{ $fmt($h->book_balance) }}</td>
                            <td class="gl-r gl-num">{{ $fmt($h->uncleared_in) }}</td>
                            <td class="gl-r gl-num">{{ $fmt($h->uncleared_out) }}</td>
                            <td class="gl-r gl-num" style="font-weight:800;color:{{ abs((float) $h->difference) < 0.01 ? '#15803d' : '#b91c1c' }}">{{ abs((float) $h->difference) < 0.01 ? 'Matched' : $fmt($h->difference) }}</td>
                            <td class="gl-mutedtext">{{ optional($h->creator)->name ?: '—' }}@if($h->note)<span style="display:block;font-size:.7rem">{{ $h->note }}</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
    var brBook = {{ json_encode(round($bookBalance, 2)) }};
    function brMoney(n) { return (n < 0 ? '- ' : '') + Math.abs(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function brRecalc() {
        var unIn = 0, unOut = 0;
        document.querySelectorAll('.br-check').forEach(function (c) {
            c.closest('tr').classList.toggle('is-cleared', c.checked);
            if (!c.checked) { unIn += parseFloat(c.dataset.in) || 0; unOut += parseFloat(c.dataset.out) || 0; }
        });
        var expected = Math.round((brBook - unIn + unOut) * 100) / 100;
        document.getElementById('brIn').textContent = brMoney(unIn);
        document.getElementById('brOut').textContent = brMoney(unOut);
        document.getElementById('brExpected').textContent = brMoney(expected);
        var raw = document.getElementById('brStatement').value;
        var diffBox = document.getElementById('brDiff');
        if (raw === '') { document.getElementById('brDiffValue').textContent = '—'; diffBox.className = 'br-diff'; return; }
        var diff = Math.round((parseFloat(raw) - expected) * 100) / 100;
        document.getElementById('brDiffValue').textContent = Math.abs(diff) < 0.01 ? 'Matched' : brMoney(diff);
        diffBox.className = 'br-diff ' + (Math.abs(diff) < 0.01 ? 'ok' : 'bad');
    }
    function brTickAll(on) { document.querySelectorAll('.br-check').forEach(function (c) { c.checked = on; }); brRecalc(); }
    brRecalc();
</script>
@endsection
