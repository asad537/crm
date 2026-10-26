@extends('crm.layout')
@section('title', 'Receipts')
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $isFiltered = $filters['search'] !== '' || $filters['date_from'] || $filters['date_to'] || $filters['source'] || $filters['method'] || $filters['customer'];
    $sourceTone = ['invoice' => 't-rec', 'opening' => 'part', 'sale' => 'ok', 'income' => 't-pay', 'direct' => 'ok', 'account' => 't-rec'];
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
<style>
    .rc-ref a { color: var(--brand); font-weight: 700; text-decoration: none; }
    .rc-ref a:hover { text-decoration: underline; }
    .rc-datenote { display: block; font-size: .64rem; color: var(--faint); margin-top: .15rem; white-space: normal; }
    .rc-proof { font-size: .72rem; font-weight: 700; color: var(--brand); text-decoration: none; margin-left: .35rem; }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Receipts</strong> — every payment received: invoice payments, customer sales, rent / other income and receipts made here, newest first</div>
        <button type="button" class="ex-add" onclick="mrOpenCreate()"><i class="fas fa-plus"></i> Make Receipt</button>
    </div>

    <div class="gl-stats">
        <div class="gl-stat green">
            <div class="gl-stat-top"><span class="gl-ico green"><i class="fas fa-hand-holding-usd"></i></span><span class="gl-k">{{ $isFiltered ? 'Received (filtered)' : 'Total Received' }}</span></div>
            <div class="gl-v">{{ $fmt($totals['received']) }} <small>AED</small></div>
            <div class="gl-note">{{ $totals['count'] }} receipt(s)</div>
        </div>
        <a href="{{ route('crm.receipts.index', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="gl-stat gl-stat-link" title="Show this month's receipts">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-calendar-alt"></i></span><span class="gl-k">This Month ({{ now()->format('M Y') }})</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
            <div class="gl-v">{{ $fmt($totals['month']) }} <small>AED</small></div>
            <div class="gl-note">{{ $totals['month_count'] }} receipt(s) this month</div>
        </a>
        <div class="gl-stat">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-receipt"></i></span><span class="gl-k">Latest Receipt</span></div>
            @if($totals['last'])
                <div class="gl-v">{{ $fmt($totals['last']->amount) }} <small>{{ $totals['last']->currency }}</small></div>
                <div class="gl-note">{{ $totals['last']->customer }} · {{ $totals['last']->date->format('d M Y') }}</div>
            @else
                <div class="gl-v">—</div>
                <div class="gl-note">No payments received yet.</div>
            @endif
        </div>
    </div>

    <div class="gl-filterbar">
        <form class="gl-filters" method="GET" action="{{ route('crm.receipts.index') }}" id="rcFilterForm">
            <div class="gl-field"><label>From</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" onchange="this.form.submit()"></div>
            <div class="gl-field"><label>To</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" onchange="this.form.submit()"></div>
            <div class="gl-field"><label>Type</label>
                <select name="source" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach($sources as $value => $label)
                        <option value="{{ $value }}" {{ $filters['source'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($methodOptions->count())
            <div class="gl-field"><label>Method</label>
                <select name="method" onchange="this.form.submit()">
                    <option value="">All Methods</option>
                    @foreach($methodOptions as $method)
                        <option value="{{ $method }}" {{ $filters['method'] === $method ? 'selected' : '' }}>{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($customerOptions->count())
            <div class="gl-field"><label>Customer</label>
                <select name="customer" onchange="this.form.submit()">
                    <option value="">All Customers</option>
                    @foreach($customerOptions as $name)
                        <option value="{{ $name }}" {{ $filters['customer'] === $name ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="gl-field"><label>Search</label><input name="search" id="rcSearchInput" value="{{ $filters['search'] }}" placeholder="Customer, invoice, reference..." autocomplete="off"></div>
            @if($isFiltered)
                <a class="gl-reset" href="{{ route('crm.receipts.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <div class="gl-count">Showing <b>{{ $entries->total() }}</b> receipt{{ $entries->total() === 1 ? '' : 's' }}{{ $filters['search'] !== '' ? ' for "'.$filters['search'].'"' : '' }}{{ $filters['date_from'] || $filters['date_to'] ? ' · '.($filters['date_from'] ?: 'start').' → '.($filters['date_to'] ?: 'today') : '' }}</div>

    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="width:110px">Date</th>
                    <th style="min-width:200px">Customer</th>
                    <th style="width:130px">Invoice / Ref</th>
                    <th style="width:140px">Type</th>
                    <th style="width:120px">Method</th>
                    <th style="min-width:140px">Reference / Note</th>
                    <th style="width:120px">Received By</th>
                    <th class="gl-r" style="width:140px">Amount</th>
                    <th style="width:120px"></th>
                </tr></thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td class="gl-date" data-label="Date">{{ $entry->date->format('d M Y') }}@if($entry->date_note)<span class="rc-datenote">{{ $entry->date_note }}</span>@endif</td>
                        <td data-label="Customer"><div class="gl-party"><span class="gl-avatar">{{ strtoupper(substr($entry->customer, 0, 1)) }}</span><span class="gl-name">{{ $entry->customer }}</span></div></td>
                        <td class="gl-mutedtext rc-ref" data-label="Invoice / Ref">@if($entry->url)<a href="{{ $entry->url }}">{{ $entry->ref }}</a>@else{{ $entry->ref }}@endif</td>
                        <td data-label="Type"><span class="gl-badge {{ $sourceTone[$entry->source] }}">{{ $entry->source_label }}</span></td>
                        <td class="gl-mutedtext" data-label="Method">{{ $entry->method ?: '—' }}</td>
                        <td data-label="Reference / Note">{{ $entry->reference ?: ($entry->detail ?: '—') }}@if($entry->proof_url)<a class="rc-proof" href="{{ $entry->proof_url }}" target="_blank"><i class="fas fa-paperclip"></i> Proof</a>@endif</td>
                        <td class="gl-mutedtext" data-label="Received By">{{ $entry->received_by ?: '—' }}</td>
                        <td class="gl-r gl-num gl-green" data-label="Amount">{{ $fmt($entry->amount) }} <small>{{ $entry->currency }}</small></td>
                        <td data-label="">
                            <div class="ex-actions">
                                @if($entry->url)<a class="ex-icon" href="{{ $entry->url }}" title="View"><i class="fas fa-eye"></i></a>@endif
                                @if(in_array($entry->source, ['direct', 'account'], true) && $entry->form)
                                    @if($entry->source === 'direct')<button type="button" class="ex-icon" title="Edit" data-receipt="{{ json_encode($entry->form) }}" onclick="mrOpenEdit(JSON.parse(this.dataset.receipt))"><i class="fas fa-pen"></i></button>@endif
                                    @if($canDelete)
                                    <form method="POST" action="{{ route('crm.direct_receipts.destroy', $entry->link_id) }}" onsubmit="return confirm('Delete this receipt? It will be removed from Receipts and its bank / cash account.');">
                                        {{ csrf_field() }} {{ method_field('DELETE') }}
                                        <button type="submit" class="ex-icon danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="gl-empty"><i class="fas fa-receipt"></i>{{ $isFiltered ? 'No receipts match the selected filters.' : 'No payments received yet.' }}</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="gl-pagination">{{ $entries->links() }}</div>
        @endif
    </div>
</div>

@include('crm.receipts._make_receipt')

<script>
    // Live search: submit the filter form shortly after the user stops typing.
    (function () {
        var input = document.getElementById('rcSearchInput');
        var form = document.getElementById('rcFilterForm');
        if (!input || !form) return;
        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 450);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { clearTimeout(timer); }
        });
        if (input.value) {
            input.focus();
            var v = input.value; input.value = ''; input.value = v;
        }
    })();
</script>
@endsection
