@extends('crm.layout')
@section('title', 'Payments')
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $isFiltered = $filters['search'] !== '' || $filters['date_from'] || $filters['date_to'] || $filters['source'] || $filters['method'] || $filters['party'];
    $sourceTone = ['vendor' => 't-pay', 'direct' => 'due', 'account' => 'part', 'expense' => 'ok', 'purchase' => 't-rec', 'salary' => 'ok', 'advance' => 'due'];
    $canDelete = Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isAccounts();
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
<style>
    .rc-ref a { color: var(--brand); font-weight: 700; text-decoration: none; }
    .rc-ref a:hover { text-decoration: underline; }
    .rc-sub { display: block; font-size: .68rem; color: var(--faint); margin-top: .15rem; }
    .rc-proof { font-size: .72rem; font-weight: 700; color: var(--brand); text-decoration: none; margin-left: .35rem; white-space: nowrap; }
    .pm-dup td { background: var(--well); }
    .pm-dup .gl-num { color: var(--faint) !important; text-decoration: line-through; }
    .pm-dupnote { display: inline-block; margin-top: .25rem; font-size: .62rem; font-weight: 800; letter-spacing: .03em;
        color: var(--amber); background: var(--amber-soft); border-radius: 6px; padding: .12rem .45rem; }
    .gl-stat.red .gl-v { color: var(--red); }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Payments</strong> — every payment the company made: vendor payments, demand-request expenses, expense payments, fully paid purchase invoices, salaries and salary advances, newest first</div>
        <button type="button" class="ex-add" onclick="exOpenCreate()"><i class="fas fa-plus"></i> Make Payment</button>
    </div>

    <div class="gl-stats">
        <div class="gl-stat red">
            <div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-money-bill-wave"></i></span><span class="gl-k">{{ $isFiltered ? 'Paid (filtered)' : 'Total Paid' }}</span></div>
            <div class="gl-v">{{ $fmt($totals['paid']) }} <small>AED</small></div>
            <div class="gl-note">{{ $totals['count'] }} payment(s)@if($totals['duplicate_count']) · {{ $totals['duplicate_count'] }} duplicate(s) of {{ $fmt($totals['duplicate_amount']) }} not counted twice @endif</div>
        </div>
        <a href="{{ route('crm.payments.index', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="gl-stat gl-stat-link" title="Show this month's payments">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-calendar-alt"></i></span><span class="gl-k">This Month ({{ now()->format('M Y') }})</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
            <div class="gl-v">{{ $fmt($totals['month']) }} <small>AED</small></div>
            <div class="gl-note">{{ $totals['month_count'] }} payment(s) this month</div>
        </a>
        <a href="{{ route('crm.payments.index', array_merge(request()->except(['source', 'page']), ['source' => 'vendor'])) }}" class="gl-stat gl-stat-link" title="Show vendor payments">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-truck-loading"></i></span><span class="gl-k">Vendor Payments</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
            <div class="gl-v">{{ $fmt($totals['vendor']) }} <small>AED</small></div>
        </a>
        <div class="gl-stat">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-clipboard-list"></i></span><span class="gl-k">Expenses (Demand + Direct)</span></div>
            <div class="gl-v">{{ $fmt($totals['expense']) }} <small>AED</small></div>
        </div>
        <a href="{{ route('crm.payments.index', array_merge(request()->except(['source', 'page']), ['source' => 'purchase'])) }}" class="gl-stat gl-stat-link" title="Show purchase invoice payments">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-file-invoice"></i></span><span class="gl-k">Purchase Invoice Payments</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
            <div class="gl-v">{{ $fmt($totals['purchase']) }} <small>AED</small></div>
        </a>
        <a href="{{ route('crm.payments.index', array_merge(request()->except(['source', 'page']), ['source' => 'salary'])) }}" class="gl-stat gl-stat-link" title="Show paid salaries">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-money-check-alt"></i></span><span class="gl-k">Salaries + Advances</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
            <div class="gl-v">{{ $fmt($totals['salary']) }} <small>AED</small></div>
        </a>
    </div>

    <div class="gl-filterbar">
        <form class="gl-filters" method="GET" action="{{ route('crm.payments.index') }}" id="pmFilterForm">
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
            @if($partyOptions->count())
            <div class="gl-field"><label>Paid To</label>
                <select name="party" onchange="this.form.submit()">
                    <option value="">All Vendors / Payees</option>
                    @foreach($partyOptions as $name)
                        <option value="{{ $name }}" {{ $filters['party'] === $name ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="gl-field"><label>Search</label><input name="search" id="pmSearchInput" value="{{ $filters['search'] }}" placeholder="Vendor, invoice, demand, note..." autocomplete="off"></div>
            @if($isFiltered)
                <a class="gl-reset" href="{{ route('crm.payments.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <div class="gl-count">Showing <b>{{ $entries->total() }}</b> payment{{ $entries->total() === 1 ? '' : 's' }}{{ $filters['search'] !== '' ? ' for "'.$filters['search'].'"' : '' }}{{ $filters['date_from'] || $filters['date_to'] ? ' · '.($filters['date_from'] ?: 'start').' → '.($filters['date_to'] ?: 'today') : '' }}</div>

    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="width:110px">Date</th>
                    <th style="min-width:190px">Paid To</th>
                    <th style="width:150px">Invoice / Ref</th>
                    <th style="width:150px">Type</th>
                    <th style="min-width:170px">Detail</th>
                    <th style="width:120px">Method</th>
                    <th style="width:120px">Paid By</th>
                    <th class="gl-r" style="width:140px">Amount</th>
                    <th style="width:120px"></th>
                </tr></thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr class="{{ $entry->duplicate_of ? 'pm-dup' : '' }}">
                        <td class="gl-date" data-label="Date">{{ $entry->date->format('d M Y') }}</td>
                        <td data-label="Paid To"><div class="gl-party"><span class="gl-avatar">{{ strtoupper(substr($entry->party, 0, 1)) }}</span><span class="gl-name">{{ $entry->party }}</span></div></td>
                        <td class="gl-mutedtext rc-ref" data-label="Invoice / Ref">@if($entry->url)<a href="{{ $entry->url }}">{{ $entry->ref }}</a>@else{{ $entry->ref }}@endif</td>
                        <td data-label="Type"><span class="gl-badge {{ $sourceTone[$entry->source] }}">{{ $entry->source_label }}</span></td>
                        <td data-label="Detail">{{ $entry->detail ?: '—' }}@if($entry->category)<span class="rc-sub">{{ $entry->category }}</span>@endif
                            @if($entry->proof_url)<a class="rc-proof" href="{{ $entry->proof_url }}" target="_blank"><i class="fas fa-paperclip"></i> Proof{{ $entry->proof_count > 1 ? ' ('.$entry->proof_count.')' : '' }}</a>@endif
                            @if($entry->invoice_status)<span class="rc-sub" style="font-weight:700;color:{{ $entry->invoice_due > 0.009 ? 'var(--amber)' : 'var(--green)' }}">{{ $entry->invoice_status }}</span>@endif
                            @if($entry->duplicate_of)<br><span class="pm-dupnote" title="This spend is also recorded as vendor purchase {{ $entry->duplicate_of }}, so it is counted once (under Vendors).">Also in Vendors · Inv {{ $entry->duplicate_of }} — not counted</span>@endif
                        </td>
                        <td class="gl-mutedtext" data-label="Method">{{ $entry->method ?: '—' }}</td>
                        <td class="gl-mutedtext" data-label="Paid By">{{ $entry->paid_by ?: '—' }}</td>
                        <td class="gl-r gl-num gl-red" data-label="Amount">{{ $fmt($entry->amount) }} <small>{{ $entry->currency }}</small></td>
                        <td data-label="">
                            <div class="ex-actions">
                                @if($entry->url)
                                    <a class="ex-icon" href="{{ $entry->url }}" title="View"><i class="fas fa-eye"></i></a>
                                @endif
                                @if($entry->source === 'expense' && $entry->form)
                                    <button type="button" class="ex-icon" title="Edit" data-payment="{{ json_encode($entry->form) }}" onclick="exOpenEdit(JSON.parse(this.dataset.payment))"><i class="fas fa-pen"></i></button>
                                    @if($canDelete)
                                    <form method="POST" action="{{ route('crm.expenses.destroy', $entry->link_id) }}" onsubmit="return confirm('Delete this payment? It will be removed from Payments and its bank / cash account.');">
                                        {{ csrf_field() }} {{ method_field('DELETE') }}
                                        <button type="submit" class="ex-icon danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="gl-empty"><i class="fas fa-money-bill-wave"></i>{{ $isFiltered ? 'No payments match the selected filters.' : 'No payments made yet.' }}</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="gl-pagination">{{ $entries->links() }}</div>
        @endif
    </div>
</div>

@include('crm.payments._make_payment')

<script>
    // Live search: submit the filter form shortly after the user stops typing.
    (function () {
        var input = document.getElementById('pmSearchInput');
        var form = document.getElementById('pmFilterForm');
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
