@extends('crm.layout')
@section('title', "Owner's Capital")
@section('content')
@php
    $fmt = fn ($v) => ((float) $v < -0.004 ? '- ' . number_format(abs((float) $v), 2) : number_format((float) $v, 2));
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')
<style>
    .oc-owners { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: .9rem; margin-bottom: 1.1rem; }
    .oc-owner { position: relative; display: block; background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 1rem 1.1rem;
        text-decoration: none; color: inherit; box-shadow: 0 1px 2px rgba(15,23,42,.04); transition: border-color .15s, box-shadow .15s; }
    .oc-owner:hover { border-color: var(--brand); }
    .oc-owner.active { border-color: var(--brand); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand) 14%, transparent); }
    .oc-owner.off { opacity: .6; }
    .oc-owner-top { display: flex; align-items: center; gap: .65rem; margin-bottom: .8rem; }
    .oc-avatar { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; background: var(--brand-soft); color: var(--brand); font-weight: 800; }
    .oc-owner h4 { margin: 0; font-size: .95rem; color: var(--ink); }
    .oc-owner small { color: var(--muted); font-size: .72rem; }
    .oc-owner-figs { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; }
    .oc-owner-figs div span { display: block; font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--faint); }
    .oc-owner-figs div b { font-size: .88rem; font-variant-numeric: tabular-nums; }
    .oc-edit { position: absolute; top: .7rem; right: .7rem; }
    .oc-type { display: inline-block; padding: .22rem .55rem; border-radius: 6px; font-size: .7rem; font-weight: 800; }
    .oc-type.capital { background: #dcfce7; color: #15803d; }
    .oc-type.drawing { background: #fee2e2; color: #b91c1c; }
    .oc-bal { font-weight: 800; color: #2563eb; font-variant-numeric: tabular-nums; }
    .oc-note { font-size: .74rem; color: var(--muted); }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Owner's Capital</strong> — money the owners put into the business (capital) and took out for themselves (drawings). It shows under <b>Equity</b> in the Balance Sheet and the <a href="{{ route('crm.chart_of_accounts.index') }}" style="color:var(--brand);font-weight:700">Chart of Accounts</a>.</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <button type="button" class="pi-btn" onclick="ocOwnerOpen({})"><i class="fas fa-user-plus"></i>&nbsp; Add Owner</button>
            @if($owners->isNotEmpty())<button type="button" class="ex-add" onclick="ocEntryOpen({})"><i class="fas fa-plus"></i> Record Capital / Drawing</button>@endif
        </div>
    </div>

    @if($owners->isEmpty())
        <div class="gl-card"><div class="gl-empty"><i class="fas fa-user-tie"></i>No owners yet. Click <b>Add Owner</b> to add the owner (or each partner), then record the capital they put in.</div></div>
    @else
        <div class="oc-owners">
            @foreach($owners as $owner)
                <a class="oc-owner {{ $selected && $selected->id === $owner->id ? 'active' : '' }} {{ $owner->is_active ? '' : 'off' }}"
                   href="{{ $selected && $selected->id === $owner->id ? route('crm.owner_capital.index') : route('crm.owner_capital.index', ['owner' => $owner->id]) }}"
                   title="{{ $selected && $selected->id === $owner->id ? 'Show all owners' : 'Show only ' . $owner->name }}">
                    <button type="button" class="ex-icon oc-edit" title="Edit owner" data-owner="{{ json_encode($owner->formData()) }}" onclick="event.preventDefault(); ocOwnerOpen(JSON.parse(this.dataset.owner))"><i class="fas fa-pen"></i></button>
                    <div class="oc-owner-top">
                        <span class="oc-avatar">{{ strtoupper(mb_substr($owner->name, 0, 1)) }}</span>
                        <div><h4>{{ $owner->name }}</h4><small>{{ $owner->share_percent !== null ? rtrim(rtrim(number_format((float) $owner->share_percent, 2), '0'), '.') . '% share' : 'Owner' }}{{ $owner->is_active ? '' : ' · inactive' }}</small></div>
                    </div>
                    <div class="oc-owner-figs">
                        <div><span>Capital In</span><b style="color:#15803d">{{ $fmt($owner->capitalTotal()) }}</b></div>
                        <div><span>Drawings</span><b style="color:#b91c1c">{{ $fmt($owner->drawingsTotal()) }}</b></div>
                        <div><span>Balance</span><b class="oc-bal">{{ $fmt($owner->balance()) }}</b></div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="gl-stats">
            <div class="gl-stat green"><div class="gl-stat-top"><span class="gl-ico green"><i class="fas fa-arrow-down"></i></span><span class="gl-k">Capital Put In{{ $selected ? ' — ' . $selected->name : '' }}</span></div>
                <div class="gl-v">{{ $fmt($totals['capital']) }} <small>AED</small></div></div>
            <div class="gl-stat red"><div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-arrow-up"></i></span><span class="gl-k">Drawings Taken Out</span></div>
                <div class="gl-v">{{ $fmt($totals['drawings']) }} <small>AED</small></div></div>
            <div class="gl-stat"><div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-user-tie"></i></span><span class="gl-k">Owner's Equity (Capital − Drawings)</span></div>
                <div class="gl-v">{{ $fmt($totals['capital'] - $totals['drawings']) }} <small>AED</small></div>
                <div class="gl-note">profit / loss is added in the Balance Sheet as Retained Earnings</div></div>
        </div>

        <div class="gl-count">{{ $entries->count() }} entr{{ $entries->count() === 1 ? 'y' : 'ies' }}{{ $selected ? ' for ' . $selected->name : '' }}@if($selected) · <a href="{{ route('crm.owner_capital.index') }}" style="color:var(--brand);font-weight:700">show all owners</a>@endif</div>
        <div class="gl-card">
            <div class="gl-tablewrap">
                <table class="gl-table">
                    <thead><tr>
                        <th style="width:105px">Date</th><th style="width:95px">No.</th><th style="min-width:150px">Owner</th><th style="width:120px">Type</th>
                        <th style="min-width:160px">Method / Account</th><th style="min-width:160px">Reference / Note</th>
                        <th class="gl-r" style="width:120px">In</th><th class="gl-r" style="width:120px">Out</th><th class="gl-r" style="width:130px">Owner Balance</th><th style="width:84px"></th>
                    </tr></thead>
                    <tbody>
                    @forelse($entries as $e)
                        <tr>
                            <td class="gl-date" data-label="Date">{{ $e->entry_date->format('d M Y') }}</td>
                            <td data-label="No." class="gl-mutedtext">{{ $e->number }}</td>
                            <td data-label="Owner"><span class="gl-name">{{ optional($e->owner)->name ?: '—' }}</span></td>
                            <td data-label="Type"><span class="oc-type {{ $e->type }}">{{ $types[$e->type] }}</span></td>
                            <td data-label="Method / Account">{{ $e->method ?: '—' }}
                                <span class="ex-sub">@if(!$e->isCash())no money moved (opening / non-cash)@elseif($e->bankAccount){{ $e->bankAccount->name }}@else auto: account that picks up "{{ $e->method }}"@endif</span></td>
                            <td data-label="Reference / Note">{{ $e->reference ?: '—' }}@if($e->note)<span class="ex-sub">{{ $e->note }}</span>@endif</td>
                            <td class="gl-r gl-num gl-green" data-label="In">{{ $e->type === 'capital' ? $fmt($e->amount) : '' }}</td>
                            <td class="gl-r gl-num gl-red" data-label="Out">{{ $e->type === 'drawing' ? $fmt($e->amount) : '' }}</td>
                            <td class="gl-r" data-label="Owner Balance"><span class="oc-bal">{{ $fmt($e->owner_balance) }}</span></td>
                            <td data-label="">
                                <div class="ex-actions">
                                    <button type="button" class="ex-icon" title="Edit" data-entry="{{ json_encode($e->formData()) }}" onclick="ocEntryOpen(JSON.parse(this.dataset.entry))"><i class="fas fa-pen"></i></button>
                                    @if($canDelete)
                                    <form method="POST" action="{{ route('crm.owner_capital.entries.destroy', $e->id) }}" onsubmit="return confirm('Delete this entry?');">
                                        {{ csrf_field() }} {{ method_field('DELETE') }}
                                        <button type="submit" class="ex-icon danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><div class="gl-empty"><i class="fas fa-coins"></i>No capital or drawings recorded yet. Click <b>Record Capital / Drawing</b>.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

{{-- Add / edit owner --}}
<div class="ex-modal gl-page" id="ocOwnerModal" onclick="if (event.target === this) this.classList.remove('open')">
    <form class="ex-dialog" id="ocOwnerForm" method="POST" action="{{ route('crm.owner_capital.owners.store') }}" style="max-width:520px">
        {{ csrf_field() }}
        <input type="hidden" name="_method" id="ocOwnerMethod" value="POST">
        <div class="ex-dhead">
            <span class="gl-ico brand"><i class="fas fa-user-tie"></i></span>
            <div><h3 id="ocOwnerTitle">Add Owner</h3><p>The owner, or each partner, gets their own capital and drawings account.</p></div>
            <button type="button" class="ex-close" onclick="document.getElementById('ocOwnerModal').classList.remove('open')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ex-dbody">
            <div class="ex-f full"><label>Owner / Partner Name <span class="req">*</span></label><input name="name" id="ocOwnerName" required maxlength="255"></div>
            <div class="ex-f"><label>Ownership Share <span class="hint">(%, optional)</span></label><input type="number" name="share_percent" id="ocOwnerShare" step="0.01" min="0" max="100" placeholder="e.g. 50"></div>
            <div class="ex-f" id="ocOwnerActiveWrap" style="display:none"><label>Status</label>
                <label style="display:flex;align-items:center;gap:.4rem;font-weight:600;margin-top:.55rem"><input type="checkbox" name="is_active" id="ocOwnerActive" value="1" style="width:auto"> Active</label></div>
            <div class="ex-f full"><label>Notes</label><input name="notes" id="ocOwnerNotes" maxlength="500"></div>
        </div>
        <div class="ex-dfoot">
            <button type="button" class="pi-btn danger" id="ocOwnerDelete" style="margin-right:auto;display:none" onclick="if (confirm('Delete this owner?')) document.getElementById('ocOwnerDeleteForm').submit()"><i class="fas fa-trash"></i>&nbsp; Delete</button>
            <button type="button" class="ex-cancel" onclick="document.getElementById('ocOwnerModal').classList.remove('open')">Cancel</button>
            <button type="submit" class="ex-add"><i class="fas fa-check"></i> Save Owner</button>
        </div>
    </form>
    <form id="ocOwnerDeleteForm" method="POST" action="">{{ csrf_field() }} {{ method_field('DELETE') }}</form>
</div>

{{-- Record / edit capital or drawing --}}
<div class="ex-modal gl-page" id="ocEntryModal" onclick="if (event.target === this) this.classList.remove('open')">
    <form class="ex-dialog" id="ocEntryForm" method="POST" action="{{ route('crm.owner_capital.entries.store') }}">
        {{ csrf_field() }}
        <input type="hidden" name="_method" id="ocEntryMethod" value="POST">
        <input type="hidden" name="_entry_id" id="ocEntryId" value="">
        <div class="ex-dhead">
            <span class="gl-ico green"><i class="fas fa-coins"></i></span>
            <div><h3 id="ocEntryTitle">Record Capital / Drawing</h3><p>Capital = owner put money in. Drawing = owner took money out for themselves.</p></div>
            <button type="button" class="ex-close" onclick="document.getElementById('ocEntryModal').classList.remove('open')" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ex-dbody">
            @if($errors->any())
                <div class="ex-errors"><strong>Please fix the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="ex-f full"><label>Type <span class="req">*</span></label>
                <div class="ex-seg" style="grid-template-columns:1fr 1fr">
                    <label><input type="radio" name="type" value="capital" checked><span><i class="fas fa-arrow-down"></i> Capital In</span></label>
                    <label><input type="radio" name="type" value="drawing"><span><i class="fas fa-arrow-up"></i> Drawing (Out)</span></label>
                </div>
            </div>
            <div class="ex-f"><label>Owner <span class="req">*</span></label>
                <select name="owner_id" id="ocEntryOwner" required>@foreach($owners as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>@endforeach</select></div>
            <div class="ex-f"><label>Date <span class="req">*</span></label><input type="date" name="entry_date" id="ocEntryDate" max="{{ now()->toDateString() }}" required></div>
            <div class="ex-f"><label>Amount <span class="req">*</span> <span class="hint">(AED)</span></label><input type="number" name="amount" id="ocEntryAmount" step="0.01" min="0.01" required></div>
            <div class="ex-f"><label>Method <span class="req">*</span></label>
                <select name="method" id="ocEntryPayMethod" required onchange="ocMethodChanged()">@foreach($methods as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></div>
            <div class="ex-current-proof full" id="ocNonCashHint" style="display:none;margin:0">No money moves: use this for <b>opening capital</b> that is already inside your Bank &amp; Cash opening balances, or for assets the owner brought in.</div>
            @include('crm.bank_accounts._select', ['baId' => 'ocEntryAccount', 'baWrapClass' => 'ex-f full', 'baWrapId' => 'ocEntryAccountWrap', 'baLabel' => 'Bank / Cash Account (money went into / came out of)'])
            <div class="ex-f"><label>Reference</label><input name="reference" id="ocEntryRef" maxlength="120" placeholder="Transfer # / voucher #"></div>
            <div class="ex-f"><label>Note</label><input name="note" id="ocEntryNote" maxlength="500"></div>
        </div>
        <div class="ex-dfoot">
            <button type="button" class="ex-cancel" onclick="document.getElementById('ocEntryModal').classList.remove('open')">Cancel</button>
            <button type="submit" class="ex-add"><i class="fas fa-check"></i> Save</button>
        </div>
    </form>
</div>

<script>
    var ocOwnerUrls = { store: @json(route('crm.owner_capital.owners.store')), update: @json(route('crm.owner_capital.owners.update', '__ID__')), destroy: @json(route('crm.owner_capital.owners.destroy', '__ID__')) };
    var ocEntryUrls = { store: @json(route('crm.owner_capital.entries.store')), update: @json(route('crm.owner_capital.entries.update', '__ID__')) };

    function ocOwnerOpen(data) {
        var editing = !!data.id;
        var form = document.getElementById('ocOwnerForm');
        form.action = editing ? ocOwnerUrls.update.replace('__ID__', data.id) : ocOwnerUrls.store;
        document.getElementById('ocOwnerMethod').value = editing ? 'PUT' : 'POST';
        document.getElementById('ocOwnerTitle').textContent = editing ? 'Edit ' + data.name : 'Add Owner';
        document.getElementById('ocOwnerName').value = data.name || '';
        document.getElementById('ocOwnerShare').value = data.share_percent != null ? data.share_percent : '';
        document.getElementById('ocOwnerNotes').value = data.notes || '';
        document.getElementById('ocOwnerActive').checked = editing ? !!data.is_active : true;
        document.getElementById('ocOwnerActiveWrap').style.display = editing ? '' : 'none';
        document.getElementById('ocOwnerDelete').style.display = editing && @json($canDelete) ? '' : 'none';
        document.getElementById('ocOwnerDeleteForm').action = editing ? ocOwnerUrls.destroy.replace('__ID__', data.id) : '';
        document.getElementById('ocOwnerModal').classList.add('open');
        document.getElementById('ocOwnerName').focus();
    }

    function ocMethodChanged() {
        var nonCash = document.getElementById('ocEntryPayMethod').value === @json(\App\CrmCapitalEntry::NON_CASH);
        document.getElementById('ocNonCashHint').style.display = nonCash ? 'block' : 'none';
        var account = document.getElementById('ocEntryAccountWrap');
        if (account) account.style.display = nonCash ? 'none' : '';
    }

    function ocEntryOpen(data) {
        var editing = !!data.id;
        var form = document.getElementById('ocEntryForm');
        form.action = editing ? ocEntryUrls.update.replace('__ID__', data.id) : ocEntryUrls.store;
        document.getElementById('ocEntryMethod').value = editing ? 'PUT' : 'POST';
        document.getElementById('ocEntryId').value = data.id || '';
        document.getElementById('ocEntryTitle').textContent = editing ? 'Edit ' + (data.number || 'entry') : 'Record Capital / Drawing';
        var type = data.type || 'capital';
        form.querySelectorAll('input[name=type]').forEach(function (r) { r.checked = r.value === type; });
        if (data.owner_id || @json(optional($selected)->id)) document.getElementById('ocEntryOwner').value = data.owner_id || @json(optional($selected)->id);
        document.getElementById('ocEntryDate').value = data.entry_date || @json(now()->toDateString());
        document.getElementById('ocEntryAmount').value = data.amount || '';
        document.getElementById('ocEntryPayMethod').value = data.method || 'Bank Transfer';
        if (document.getElementById('ocEntryAccount')) document.getElementById('ocEntryAccount').value = data.bank_account_id || '';
        document.getElementById('ocEntryRef').value = data.reference || '';
        document.getElementById('ocEntryNote').value = data.note || '';
        ocMethodChanged();
        document.getElementById('ocEntryModal').classList.add('open');
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') document.querySelectorAll('#ocOwnerModal, #ocEntryModal').forEach(function (m) { m.classList.remove('open'); });
    });

    // After a failed save, reopen the entry form with what was typed.
    @if($errors->any())
        (function () {
            var old = @json(old());
            if (old.owner_id !== undefined) ocEntryOpen(Object.assign({}, old, { id: old._entry_id || null }));
            else ocOwnerOpen({});
        })();
    @endif
</script>
@endsection
