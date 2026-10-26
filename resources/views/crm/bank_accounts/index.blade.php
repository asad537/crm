@extends('crm.layout')
@section('title', 'Bank & Cash Accounts')
@section('content')
@php
    $fmt = fn ($v) => ((float) $v < -0.004 ? '- ' . number_format(abs((float) $v), 2) : number_format((float) $v, 2));
    $rows = $balances['accounts'];
    $unassigned = $balances['unassigned'];
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')
<style>
    .ba-name { font-weight: 800; color: var(--ink); }
    .ba-type { display: inline-block; margin-left: .4rem; padding: .12rem .5rem; border-radius: 6px; font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; }
    .ba-type.bank { background: #eff6ff; color: #1d4ed8; } .ba-type.cash { background: #ecfdf3; color: #15803d; }
    .ba-chip { display: inline-block; margin: .2rem .25rem 0 0; padding: .1rem .45rem; border-radius: 6px; background: var(--well); border: 1px solid var(--line); font-size: .62rem; color: var(--muted); font-weight: 700; }
    .ba-balance { font-size: .98rem; font-weight: 800; color: #2563eb; font-variant-numeric: tabular-nums; }
    .ba-balance.neg { color: var(--red); }
    .ba-inactive td { opacity: .55; }
    .ba-alert { display: flex; align-items: center; gap: .8rem; padding: .85rem 1.1rem; margin-bottom: 1rem; border-radius: 12px; background: #fffbeb; border: 1px solid #fde68a; color: #78350f; font-size: .84rem; }
    .ba-alert a { margin-left: auto; font-weight: 800; color: #b45309; white-space: nowrap; }
    .ba-methods { display: grid; grid-template-columns: repeat(2, 1fr); gap: .35rem .8rem; }
    .ba-methods label { display: flex; align-items: center; gap: .45rem; font-size: .8rem; font-weight: 600; color: var(--body); margin: 0; }
    .ba-methods input { width: auto !important; }
    .ba-empty { text-align: center; padding: 2.5rem 1rem; color: var(--muted); }
    .ba-empty b { display: block; color: var(--ink); font-size: 1rem; margin-bottom: .35rem; }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Bank &amp; Cash Accounts</strong> — opening balance plus every receipt and payment booked to each account, and transfers between them.</div>
        @if($canManage)
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            @if($accounts->count() > 1)<button type="button" class="pi-btn" onclick="baOpen('baTransferModal')"><i class="fas fa-exchange-alt"></i>&nbsp; Transfer Money</button>@endif
            <button type="button" class="ex-add" onclick="baOpenCreate()"><i class="fas fa-plus"></i> New Bank or Cash Account</button>
        </div>
        @endif
    </div>

    @if($accounts->isNotEmpty() && $unassigned['count'] > 0)
        <div class="ba-alert"><i class="fas fa-exclamation-triangle"></i>
            <span><b>{{ $unassigned['count'] }}</b> receipt(s) / payment(s) are not booked to any account yet (in {{ $fmt($unassigned['in']) }} · out {{ $fmt($unassigned['out']) }}). Set "pick up automatically" methods on your accounts, or assign them one by one.</span>
            <a href="{{ route('crm.bank_accounts.unassigned') }}">Review →</a>
        </div>
    @endif

    <div class="gl-card">
        @if($accounts->isEmpty())
            <div class="ba-empty"><b>No bank or cash accounts yet</b>Click "New Bank or Cash Account" to add your bank accounts, Cash in Hand and Petty Cash with their opening balances.</div>
        @else
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="width:64px"></th><th style="width:64px"></th>
                    <th style="min-width:230px">Name</th>
                    <th class="gl-r" style="width:150px">Opening Balance</th>
                    <th class="gl-r" style="width:130px">Money In</th>
                    <th class="gl-r" style="width:130px">Money Out</th>
                    <th class="gl-r" style="width:120px">Transfers</th>
                    <th class="gl-r" style="width:160px">Actual Balance</th>
                </tr></thead>
                <tbody>
                @foreach($rows as $row)
                    @php $a = $row['account']; $net = $row['transfers_in'] - $row['transfers_out']; @endphp
                    <tr class="{{ $a->is_active ? '' : 'ba-inactive' }}">
                        <td data-label="">@if($canManage)<button type="button" class="pi-btn" data-account="{{ json_encode($a->formData()) }}" onclick="baOpenEdit(JSON.parse(this.dataset.account))">Edit</button>@endif</td>
                        <td data-label=""><a class="pi-btn" href="{{ route('crm.bank_accounts.show', $a->id) }}">View</a></td>
                        <td data-label="Name">
                            <span class="ba-name">{{ $a->name }}</span><span class="ba-type {{ $a->type }}">{{ $types[$a->type] }}</span>@if(!$a->is_active)<span class="ba-chip">inactive</span>@endif
                            @if($a->bank_name || $a->account_number)<span class="ex-sub">{{ trim($a->bank_name . ' · ' . $a->account_number, ' ·') }}</span>@endif
                            @if($a->auto_methods)<div>@foreach($a->auto_methods as $m)<span class="ba-chip" title="Payments with this method are booked here automatically">auto: {{ $methodGroups[$m] ?? $m }}</span>@endforeach</div>@endif
                        </td>
                        <td class="gl-r gl-num" data-label="Opening Balance">{{ $fmt($row['opening']) }}<span class="ex-sub">{{ $a->opening_date->format('d M Y') }}</span></td>
                        <td class="gl-r gl-num gl-green" data-label="Money In">{{ $fmt($row['in']) }}</td>
                        <td class="gl-r gl-num gl-red" data-label="Money Out">{{ $fmt($row['out']) }}</td>
                        <td class="gl-r gl-num" data-label="Transfers">{{ abs($net) < 0.005 ? '—' : ($net > 0 ? '+' : '') . $fmt($net) }}</td>
                        <td class="gl-r" data-label="Actual Balance"><a href="{{ route('crm.bank_accounts.show', $a->id) }}" class="ba-balance {{ $row['balance'] < 0 ? 'neg' : '' }}" style="text-decoration:none">{{ $fmt($row['balance']) }}</a> <small class="gl-mutedtext">{{ $a->currency }}</small></td>
                    </tr>
                @endforeach
                <tr class="py-foot" style="font-weight:800;background:var(--well)">
                    <td colspan="7" style="text-align:right">Total of all accounts</td>
                    <td class="gl-r"><span class="ba-balance {{ $balances['total'] < 0 ? 'neg' : '' }}">{{ $fmt($balances['total']) }}</span></td>
                </tr>
                </tbody>
            </table>
        </div>
        @endif
    </div>

    @if($transfers->isNotEmpty())
        <div class="gl-count" style="margin-top:1.2rem">Recent transfers between accounts</div>
        <div class="gl-card">
            <div class="gl-tablewrap">
                <table class="gl-table">
                    <thead><tr><th style="width:110px">Date</th><th>From</th><th>To</th><th>Reference / Note</th><th class="gl-r" style="width:140px">Amount</th><th style="width:50px"></th></tr></thead>
                    <tbody>
                    @foreach($transfers as $t)
                        <tr>
                            <td class="gl-date">{{ $t->transfer_date->format('d M Y') }}</td>
                            <td>{{ optional($t->from)->name }}</td><td>{{ optional($t->to)->name }}</td>
                            <td class="gl-mutedtext">{{ trim($t->reference . ' ' . $t->note) ?: '—' }}</td>
                            <td class="gl-r gl-num">{{ $fmt($t->amount) }}</td>
                            <td>@if($canManage)<form method="POST" action="{{ route('crm.bank_accounts.transfers.destroy', $t->id) }}" onsubmit="return confirm('Delete this transfer?');">{{ csrf_field() }} {{ method_field('DELETE') }}<button type="submit" class="ex-icon danger" title="Delete"><i class="fas fa-trash"></i></button></form>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@if($canManage)
{{-- Add / edit account --}}
<div class="ex-modal gl-page" id="baModal" onclick="if (event.target === this) baClose('baModal')">
    <form class="ex-dialog" id="baForm" method="POST" action="{{ route('crm.bank_accounts.store') }}" style="max-width:680px">
        {{ csrf_field() }}
        <input type="hidden" name="_method" id="baMethod" value="POST">
        <input type="hidden" name="_account_id" id="baId" value="">
        <div class="ex-dhead">
            <span class="gl-ico brand"><i class="fas fa-university"></i></span>
            <div><h3 id="baTitle">New Bank or Cash Account</h3><p>The opening balance is what the account held on the opening date.</p></div>
            <button type="button" class="ex-close" onclick="baClose('baModal')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ex-dbody">
            @if($errors->any() && !old('_transfer'))
                <div class="ex-errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="ex-f full"><label>Account Name <span class="req">*</span></label><input name="name" required maxlength="120" placeholder="e.g. Emirates NBD – Current, Cash in Hand, Petty Cash"></div>
            <div class="ex-f"><label>Type <span class="req">*</span></label><select name="type">@foreach($types as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
            <div class="ex-f"><label>Currency <span class="req">*</span></label><select name="currency">@foreach(['AED', 'USD', 'GBP', 'EUR', 'PKR'] as $c)<option>{{ $c }}</option>@endforeach</select></div>
            <div class="ex-f"><label>Bank Name <span class="hint">(bank only)</span></label><input name="bank_name" maxlength="120"></div>
            <div class="ex-f"><label>Account # / IBAN</label><input name="account_number" maxlength="60"></div>
            <div class="ex-f"><label>Opening Balance <span class="req">*</span></label><input type="number" name="opening_balance" step="0.01" required placeholder="0.00"></div>
            <div class="ex-f"><label>As of Date <span class="req">*</span></label><input type="date" name="opening_date" required></div>
            <div class="ex-f full"><label>Pick up automatically <span class="hint">(receipts / payments with these methods and no account chosen are booked here)</span></label>
                <div class="ba-methods">@foreach($methodGroups as $v => $l)<label><input type="checkbox" name="auto_methods[]" value="{{ $v }}"> {{ $l }}</label>@endforeach</div>
            </div>
            <div class="ex-f"><label style="display:flex;align-items:center;gap:.45rem"><input type="checkbox" name="is_active" value="1" style="width:auto" checked> Active</label></div>
            <div class="ex-f full"><label>Notes</label><input name="notes" maxlength="500"></div>
        </div>
        <div class="ex-dfoot" style="justify-content:space-between">
            <div id="baDeleteWrap"></div>
            <div style="display:flex;gap:.6rem"><button type="button" class="ex-cancel" onclick="baClose('baModal')">Cancel</button><button type="submit" class="ex-add"><i class="fas fa-check"></i> Save Account</button></div>
        </div>
    </form>
</div>
<form id="baDeleteForm" method="POST" action="" onsubmit="return confirm('Delete this account?');" style="display:none">{{ csrf_field() }} {{ method_field('DELETE') }}</form>

{{-- Transfer between accounts --}}
<div class="ex-modal gl-page" id="baTransferModal" onclick="if (event.target === this) baClose('baTransferModal')">
    <form class="ex-dialog" method="POST" action="{{ route('crm.bank_accounts.transfers.store') }}" style="max-width:560px">
        {{ csrf_field() }}
        <input type="hidden" name="_transfer" value="1">
        <div class="ex-dhead">
            <span class="gl-ico brand"><i class="fas fa-exchange-alt"></i></span>
            <div><h3>Transfer Money</h3><p>Move money between your own accounts (e.g. bank → petty cash). Not income or expense.</p></div>
            <button type="button" class="ex-close" onclick="baClose('baTransferModal')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ex-dbody">
            @if($errors->any() && old('_transfer'))
                <div class="ex-errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="ex-f"><label>From <span class="req">*</span></label><select name="from_account_id" required>@foreach($accounts->where('is_active', true) as $a)<option value="{{ $a->id }}" {{ (string) old('from_account_id') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>@endforeach</select></div>
            <div class="ex-f"><label>To <span class="req">*</span></label><select name="to_account_id" required>@foreach($accounts->where('is_active', true) as $i => $a)<option value="{{ $a->id }}" {{ (string) old('to_account_id', $accounts->where('is_active', true)->values()->get(1)->id ?? '') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>@endforeach</select></div>
            <div class="ex-f"><label>Date <span class="req">*</span></label><input type="date" name="transfer_date" value="{{ old('transfer_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
            <div class="ex-f"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required></div>
            <div class="ex-f"><label>Reference</label><input name="reference" maxlength="120" value="{{ old('reference') }}"></div>
            <div class="ex-f"><label>Note</label><input name="note" maxlength="500" value="{{ old('note') }}"></div>
        </div>
        <div class="ex-dfoot">
            <button type="button" class="ex-cancel" onclick="baClose('baTransferModal')">Cancel</button>
            <button type="submit" class="ex-add"><i class="fas fa-check"></i> Transfer</button>
        </div>
    </form>
</div>

<script>
    var baStoreUrl = @json(route('crm.bank_accounts.store'));
    var baUpdateUrl = @json(route('crm.bank_accounts.update', '__ID__'));
    var baDestroyUrl = @json(route('crm.bank_accounts.destroy', '__ID__'));
    var baForm = document.getElementById('baForm');
    function baOpen(id) { document.getElementById(id).classList.add('open'); }
    function baClose(id) { document.getElementById(id).classList.remove('open'); }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { baClose('baModal'); baClose('baTransferModal'); } });

    function baFill(d) {
        baForm.querySelector('[name=name]').value = d.name || '';
        baForm.querySelector('[name=type]').value = d.type || 'bank';
        baForm.querySelector('[name=currency]').value = d.currency || 'AED';
        baForm.querySelector('[name=bank_name]').value = d.bank_name || '';
        baForm.querySelector('[name=account_number]').value = d.account_number || '';
        baForm.querySelector('[name=opening_balance]').value = d.opening_balance !== undefined && d.opening_balance !== null ? d.opening_balance : '';
        baForm.querySelector('[name=opening_date]').value = d.opening_date || @json(now()->startOfYear()->toDateString());
        baForm.querySelector('[name=notes]').value = d.notes || '';
        baForm.querySelector('[name=is_active]').checked = d.is_active === undefined ? true : !!d.is_active;
        var methods = d.auto_methods || [];
        baForm.querySelectorAll('[name="auto_methods[]"]').forEach(function (c) { c.checked = methods.indexOf(c.value) !== -1; });
    }
    function baOpenCreate() {
        baForm.action = baStoreUrl;
        document.getElementById('baMethod').value = 'POST';
        document.getElementById('baId').value = '';
        document.getElementById('baTitle').textContent = 'New Bank or Cash Account';
        document.getElementById('baDeleteWrap').innerHTML = '';
        baFill({});
        baOpen('baModal');
    }
    function baOpenEdit(d) {
        baForm.action = baUpdateUrl.replace('__ID__', d.id);
        document.getElementById('baMethod').value = 'PUT';
        document.getElementById('baId').value = d.id;
        document.getElementById('baTitle').textContent = 'Edit ' + d.name;
        document.getElementById('baDeleteForm').action = baDestroyUrl.replace('__ID__', d.id);
        document.getElementById('baDeleteWrap').innerHTML = '<button type="button" class="pi-btn danger" onclick="document.getElementById(\'baDeleteForm\').requestSubmit()"><i class="fas fa-trash"></i>&nbsp; Delete</button>';
        baFill(d);
        baOpen('baModal');
    }
    @if($errors->any())
        (function () {
            var old = @json(old());
            if (old._transfer) { baOpen('baTransferModal'); return; }
            if (old._account_id) { baOpenEdit(Object.assign({}, old, { id: old._account_id, auto_methods: old.auto_methods || [], is_active: !!old.is_active })); }
            else { baOpenCreate(); baFill(Object.assign({}, old, { auto_methods: old.auto_methods || [], is_active: !!old.is_active })); }
        })();
    @endif
</script>
@endif
@endsection
