@extends('crm.layout')
@section('title', 'Purchase Invoices')
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $isFiltered = $filters['search'] !== '' || $filters['date_from'] || $filters['date_to'] || $filters['status'] || $filters['vendor'] || $filters['type'];
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')
<style>
    .pi-type { display: inline-block; font-size: .66rem; font-weight: 800; border-radius: 99px; padding: .15rem .55rem; white-space: nowrap; }
    .pi-type.invoice { background: #eef2ff; color: #4338ca; }
    .pi-type.vendor { background: #fff4ef; color: #c2410c; }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Purchase Invoices</strong> — every purchase by date: supplier bills entered here and the purchases entered on each vendor's page (Vendor Purchases). Each one opens, is paid and is edited where it was entered.</div>
        <a href="{{ route('crm.purchase_invoices.create') }}" class="ex-add" style="text-decoration:none"><i class="fas fa-plus"></i> New Purchase Invoice</a>
    </div>

    <div class="gl-stats">
        <div class="gl-stat">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-file-invoice"></i></span><span class="gl-k">{{ $isFiltered ? 'Purchases (filtered)' : 'Total Purchases' }}</span></div>
            <div class="gl-v">{{ $fmt($totals['invoiced']) }} <small>AED</small></div>
            <div class="gl-note">{{ $totals['invoice_count'] }} purchase invoice(s) · {{ $totals['vendor_count'] }} vendor purchase(s)</div>
        </div>
        <a href="{{ route('crm.purchase_invoices.index', ['status' => 'paid']) }}" class="gl-stat green gl-stat-link" title="Show paid invoices">
            <div class="gl-stat-top"><span class="gl-ico green"><i class="fas fa-check-circle"></i></span><span class="gl-k">Paid</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
            <div class="gl-v">{{ $fmt($totals['paid']) }} <small>AED</small></div>
        </a>
        <div class="gl-stat red">
            <div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-hourglass-half"></i></span><span class="gl-k">Balance Due</span></div>
            <div class="gl-v">{{ $fmt($totals['balance']) }} <small>AED</small></div>
        </div>
        <a href="{{ route('crm.purchase_invoices.index', ['status' => 'overdue']) }}" class="gl-stat red gl-stat-link" title="Show overdue invoices">
            <div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-exclamation-circle"></i></span><span class="gl-k">Overdue</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
            <div class="gl-v">{{ $fmt($totals['overdue']) }} <small>AED</small></div>
            <div class="gl-note">{{ $totals['overdue_count'] }} past due date</div>
        </a>
    </div>

    <div class="gl-filterbar">
        <form class="gl-filters" method="GET" action="{{ route('crm.purchase_invoices.index') }}" id="piFilterForm">
            <div class="gl-field"><label>From</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" onchange="this.form.submit()"></div>
            <div class="gl-field"><label>To</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" onchange="this.form.submit()"></div>
            <div class="gl-field"><label>Type</label>
                <select name="type" onchange="this.form.submit()">
                    <option value="">All Purchases</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ $filters['type'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="gl-field"><label>Status</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ $filters['status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($vendorOptions->count())
            <div class="gl-field"><label>Supplier</label>
                <select name="vendor" onchange="this.form.submit()">
                    <option value="">All Suppliers</option>
                    @foreach($vendorOptions as $name)
                        <option value="{{ $name }}" {{ $filters['vendor'] === $name ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="gl-field"><label>Search</label><input name="search" id="piSearchInput" value="{{ $filters['search'] }}" placeholder="Supplier, reference, item..." autocomplete="off"></div>
            @if($isFiltered)
                <a class="gl-reset" href="{{ route('crm.purchase_invoices.index') }}">Reset</a>
            @endif
        </form>
    </div>

    <div class="gl-count">Showing <b>{{ $entries->total() }}</b> purchase{{ $entries->total() === 1 ? '' : 's' }}{{ $filters['search'] !== '' ? ' for "'.$filters['search'].'"' : '' }}</div>

    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="width:64px"></th>
                    <th style="width:64px"></th>
                    <th style="width:105px">Date</th>
                    <th style="width:120px">Type</th>
                    <th style="width:105px">Due Date</th>
                    <th style="width:120px">Reference</th>
                    <th style="min-width:170px">Supplier</th>
                    <th style="min-width:160px">Description</th>
                    <th style="width:120px">Project / Job</th>
                    <th class="gl-r" style="width:130px">Amount</th>
                    <th class="gl-r" style="width:120px">Balance Due</th>
                    <th style="width:120px">Status</th>
                </tr></thead>
                <tbody>
                @forelse($entries as $row)
                    <tr>
                        <td data-label=""><a class="pi-btn" href="{{ $row->edit_url }}">Edit</a></td>
                        <td data-label=""><a class="pi-btn" href="{{ $row->view_url }}">View</a></td>
                        <td class="gl-date" data-label="Date">{{ $row->date->format('d M Y') }}</td>
                        <td data-label="Type"><span class="pi-type {{ $row->kind }}">{{ $row->kind === 'vendor' ? 'Vendor Purchase' : 'Purchase Invoice' }}</span></td>
                        <td class="gl-date" data-label="Due Date">{{ $row->due ? $row->due->format('d M Y') : '—' }}</td>
                        <td data-label="Reference"><span class="ex-name">{{ $row->number }}</span>@if($row->reference)<span class="ex-sub">{{ $row->reference }}</span>@endif</td>
                        <td data-label="Supplier">@if($row->vendor_id)<a class="gl-name" href="{{ route('crm.vendor_purchases.index', ['vendor_id' => $row->vendor_id]) }}" title="Open this vendor's ledger">{{ $row->vendor_name }}</a>@else<span class="gl-name">{{ $row->vendor_name }}</span>@endif</td>
                        <td data-label="Description">{{ \Illuminate\Support\Str::limit($row->description, 60) ?: '—' }}
                            @if($row->categories)<span class="ex-sub">{{ $row->categories }}</span>@endif
                        </td>
                        <td class="gl-mutedtext" data-label="Project / Job">{{ $row->project ?: '—' }}</td>
                        <td class="gl-r gl-num" data-label="Amount">{{ $fmt($row->total) }} <small>{{ $row->currency }}</small></td>
                        <td class="gl-r gl-num {{ $row->balance > 0.009 ? 'gl-red' : '' }}" data-label="Balance Due">@if($row->balance < -0.009){{ $fmt(0) }}<span class="ex-sub" style="color:#b45309">Advance {{ $fmt(-$row->balance) }}</span>@else{{ $fmt($row->balance) }}@endif</td>
                        <td data-label="Status"><span class="pi-status {{ $row->status }}">{{ $statuses[$row->status] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="12"><div class="gl-empty"><i class="fas fa-file-invoice"></i>{{ $isFiltered ? 'No purchases match the selected filters.' : 'No purchases yet — click "New Purchase Invoice", or add one on a vendor\'s page.' }}</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="gl-pagination">{{ $entries->links() }}</div>
        @endif
    </div>
</div>

<script>
    // Live search: submit the filter form shortly after the user stops typing.
    (function () {
        var input = document.getElementById('piSearchInput');
        var form = document.getElementById('piFilterForm');
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
