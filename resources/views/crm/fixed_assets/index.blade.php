@extends('crm.layout')
@section('title', 'Fixed Assets')
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $isFiltered = $filters['search'] !== '';
    $canDelete = Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isAccounts();
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
<style>
    .fa-inv { color: var(--brand); font-weight: 800; text-decoration: none; white-space: nowrap; }
    .fa-inv:hover { text-decoration: underline; }
    .fa-rate { display: inline-block; padding: .2rem .55rem; border-radius: 6px; background: #ede9fe; color: #6d28d9; font-size: .78rem; font-weight: 800; }
    .fa-book { font-weight: 800; color: #2563eb; }
    .fa-dialog { max-width: 520px; }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Fixed Assets</strong> — things the company keeps and uses (machines, laptops, furniture, vehicles). Add the asset here, then buy it on <a href="{{ route('crm.purchase_invoices.create') }}" style="color:var(--brand);font-weight:700">Purchase Invoices</a> with "Add Fixed Asset".</div>
        <button type="button" class="ex-add" onclick="faOpenCreate()"><i class="fas fa-plus"></i> Add Asset</button>
    </div>

    <div class="gl-stats">
        <div class="gl-stat">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-boxes"></i></span><span class="gl-k">{{ $isFiltered ? 'Cost (filtered)' : 'Total Cost' }}</span></div>
            <div class="gl-v">{{ $fmt($totals['cost']) }} <small>AED</small></div>
            <div class="gl-note">{{ $totals['count'] }} asset(s){{ $totals['awaiting'] ? ' · ' . $totals['awaiting'] . ' not on an invoice yet' : '' }}</div>
        </div>
        <div class="gl-stat red">
            <div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-chart-line"></i></span><span class="gl-k">Depreciation to Date</span></div>
            <div class="gl-v">{{ $fmt($totals['depreciation']) }} <small>AED</small></div>
            <div class="gl-note">straight-line, from the invoice date</div>
        </div>
        <div class="gl-stat">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-balance-scale"></i></span><span class="gl-k">Book Value Today</span></div>
            <div class="gl-v">{{ $fmt($totals['book_value']) }} <small>AED</small></div>
            <div class="gl-note">cost − depreciation</div>
        </div>
    </div>

    <div class="gl-filterbar">
        <form class="gl-filters" method="GET" action="{{ route('crm.fixed_assets.index') }}" id="faFilterForm">
            <div class="gl-field"><label>Search</label><input name="search" id="faSearchInput" value="{{ $filters['search'] }}" placeholder="Asset name or number..." autocomplete="off"></div>
            @if($isFiltered)
                <a class="gl-reset" href="{{ route('crm.fixed_assets.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <div class="gl-count">Showing <b>{{ $entries->total() }}</b> asset{{ $entries->total() === 1 ? '' : 's' }}{{ $filters['search'] !== '' ? ' for "'.$filters['search'].'"' : '' }}</div>

    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="min-width:200px">Asset</th>
                    <th style="width:110px">Depreciation</th>
                    <th style="width:150px">Purchase Invoice</th>
                    <th style="width:110px">Date</th>
                    <th class="gl-r" style="width:130px">Cost</th>
                    <th class="gl-r" style="width:130px">Depreciated</th>
                    <th class="gl-r" style="width:130px">Book Value</th>
                    <th style="width:120px"></th>
                </tr></thead>
                <tbody>
                @forelse($entries as $asset)
                    @php $awaiting = $asset->isAwaitingInvoice(); @endphp
                    <tr>
                        <td data-label="Asset"><span class="ex-name">{{ $asset->name }}</span><span class="ex-sub">{{ $asset->number }} · added by {{ optional($asset->creator)->name ?: '—' }}</span></td>
                        <td data-label="Depreciation"><span class="fa-rate">{{ $asset->rateLabel() }}% / year</span></td>
                        <td data-label="Purchase Invoice">
                            @if($asset->purchaseInvoice)
                                <a class="fa-inv" href="{{ route('crm.purchase_invoices.show', $asset->purchase_invoice_id) }}"><i class="fas fa-file-invoice"></i> {{ $asset->purchaseInvoice->number }}</a>
                                <span class="ex-sub">{{ $asset->purchaseInvoice->statusLabel() }}</span>
                            @elseif($awaiting)
                                <span class="gl-badge part" title="Buy it on Purchase Invoices with Add Fixed Asset">Not bought yet</span>
                            @else
                                <span class="gl-mutedtext" title="Entered before assets were bought through purchase invoices">{{ $asset->payment_method }}</span>
                                @if($asset->receipt_path)<a class="ex-proof" href="{{ route('crm.fixed_assets.receipt', $asset->id) }}" target="_blank" style="display:block"><i class="fas fa-paperclip"></i> Receipt</a>@endif
                            @endif
                        </td>
                        <td class="gl-date" data-label="Date">{{ $awaiting ? '—' : $asset->purchase_date->format('d M Y') }}</td>
                        <td class="gl-r gl-num" data-label="Cost">@if($awaiting)<span class="gl-mutedtext">—</span>@else{{ $fmt($asset->cost()) }} <small>{{ $asset->currency }}</small>@endif</td>
                        <td class="gl-r gl-num gl-red" data-label="Depreciated">{{ $awaiting ? '' : $fmt($asset->accumulatedDepreciation()) }}</td>
                        <td class="gl-r gl-num" data-label="Book Value">@if(!$awaiting)<span class="fa-book">{{ $fmt($asset->bookValue()) }}</span>@endif</td>
                        <td data-label="">
                            <div class="ex-actions">
                                @if($awaiting)
                                    <a class="ex-icon" style="color:#6d28d9" href="{{ route('crm.purchase_invoices.create', ['asset' => $asset->id]) }}" title="Buy it: create the supplier's purchase invoice"><i class="fas fa-file-invoice"></i></a>
                                @endif
                                <button type="button" class="ex-icon" title="Edit" data-asset="{{ json_encode($asset->formData()) }}" onclick="faOpenEdit(JSON.parse(this.dataset.asset))"><i class="fas fa-pen"></i></button>
                                @if($canDelete && !$asset->purchase_invoice_id)
                                <form method="POST" action="{{ route('crm.fixed_assets.destroy', $asset->id) }}" onsubmit="return confirm('Delete {{ $asset->number }}?');">
                                    {{ csrf_field() }} {{ method_field('DELETE') }}
                                    <button type="submit" class="ex-icon danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="gl-empty"><i class="fas fa-boxes"></i>{{ $isFiltered ? 'No assets match the search.' : 'No fixed assets yet — click "Add Asset" to add the first one.' }}</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="gl-pagination">{{ $entries->links() }}</div>
        @endif
    </div>
</div>

{{-- Add / edit asset: just its name and depreciation rate; the rest comes from its purchase invoice. --}}
<div class="ex-modal gl-page" id="faModal" onclick="if (event.target === this) faClose()">
    <form class="ex-dialog fa-dialog" id="faForm" method="POST" action="{{ route('crm.fixed_assets.store') }}">
        {{ csrf_field() }}
        <input type="hidden" name="_method" id="faMethod" value="POST">
        <input type="hidden" name="_asset_id" id="faId" value="">
        <div class="ex-dhead">
            <span class="gl-ico brand"><i class="fas fa-boxes"></i></span>
            <div><h3 id="faTitle">Add Fixed Asset</h3><p>Quantity, cost and supplier come from the purchase invoice you buy it on.</p></div>
            <button type="button" class="ex-close" onclick="faClose()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ex-dbody">
            @if($errors->any())
                <div class="ex-errors"><strong>Please fix the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="ex-f full"><label>Item Name <span class="req">*</span></label><input name="name" id="faName" placeholder="e.g. Dell laptop, Die cutting machine, Office chairs" required maxlength="255"></div>
            <div class="ex-f full"><label>Depreciation Rate <span class="req">*</span> <span class="hint">(% of cost per year, straight-line)</span></label>
                <div class="ex-amount" style="grid-template-columns:1fr 70px">
                    <input type="number" name="depreciation_rate" id="faRate" step="0.01" min="0" max="100" placeholder="e.g. 20" required>
                    <span style="display:grid;place-items:center;border:1px solid var(--line);border-radius:9px;background:var(--well);font-weight:800;color:var(--muted)">% / yr</span>
                </div>
                <div class="ex-current-proof">e.g. computers 33%, furniture 10–20%, machinery 10–15%, vehicles 20–25%.</div>
            </div>
        </div>
        <div class="ex-dfoot">
            <button type="button" class="ex-cancel" onclick="faClose()">Cancel</button>
            <button type="submit" class="ex-add"><i class="fas fa-check"></i> Save Asset</button>
        </div>
    </form>
</div>

<script>
    var faStoreUrl = @json(route('crm.fixed_assets.store'));
    var faUpdateUrl = @json(route('crm.fixed_assets.update', '__ID__'));
    var faModal = document.getElementById('faModal');
    var faForm = document.getElementById('faForm');

    function faFill(data) {
        document.getElementById('faName').value = data.name || '';
        document.getElementById('faRate').value = data.depreciation_rate != null ? data.depreciation_rate : '';
    }

    function faOpenCreate() {
        faForm.action = faStoreUrl;
        document.getElementById('faMethod').value = 'POST';
        document.getElementById('faId').value = '';
        document.getElementById('faTitle').textContent = 'Add Fixed Asset';
        faFill({});
        faModal.classList.add('open');
        document.getElementById('faName').focus();
    }

    function faOpenEdit(data) {
        faForm.action = faUpdateUrl.replace('__ID__', data.id);
        document.getElementById('faMethod').value = 'PUT';
        document.getElementById('faId').value = data.id;
        document.getElementById('faTitle').textContent = 'Edit ' + data.number;
        faFill(data);
        faModal.classList.add('open');
    }

    function faClose() { faModal.classList.remove('open'); }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') faClose(); });

    // After a failed save, reopen the form with what the user typed.
    @if($errors->any())
        (function () {
            var old = @json(old());
            if (old._asset_id) {
                faOpenEdit(Object.assign({}, old, { id: old._asset_id, number: 'asset' }));
            } else {
                faOpenCreate();
                faFill(old);
            }
        })();
    @endif

    // Live search: submit the filter form shortly after the user stops typing.
    (function () {
        var input = document.getElementById('faSearchInput');
        var form = document.getElementById('faFilterForm');
        if (!input || !form) return;
        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 450);
        });
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter') clearTimeout(timer); });
        if (input.value) { input.focus(); var v = input.value; input.value = ''; input.value = v; }
    })();
</script>
@endsection
