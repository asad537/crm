@extends('crm.layout')
@section('title', 'Purchase Invoices')
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $isFiltered = $filters['search'] !== '' || $filters['date_from'] || $filters['date_to'] || $filters['status'] || $filters['vendor'];
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Purchase Invoices</strong> — supplier bills with their items. Record payments on each invoice; fully paid invoices also show under Payments.</div>
        <a href="{{ route('crm.purchase_invoices.create') }}" class="ex-add" style="text-decoration:none"><i class="fas fa-plus"></i> New Purchase Invoice</a>
    </div>

    <div class="gl-stats">
        <div class="gl-stat">
            <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-file-invoice"></i></span><span class="gl-k">{{ $isFiltered ? 'Invoiced (filtered)' : 'Total Invoiced' }}</span></div>
            <div class="gl-v">{{ $fmt($totals['invoiced']) }} <small>AED</small></div>
            <div class="gl-note">{{ $totals['count'] }} invoice(s)</div>
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
            <div class="gl-note">{{ $totals['overdue_count'] }} invoice(s) past due date</div>
        </a>
    </div>

    <div class="gl-filterbar">
        <form class="gl-filters" method="GET" action="{{ route('crm.purchase_invoices.index') }}" id="piFilterForm">
            <div class="gl-field"><label>From</label><input type="date" name="date_from" value="{{ $filters['date_from'] }}" onchange="this.form.submit()"></div>
            <div class="gl-field"><label>To</label><input type="date" name="date_to" value="{{ $filters['date_to'] }}" onchange="this.form.submit()"></div>
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

    <div class="gl-count">Showing <b>{{ $entries->total() }}</b> invoice{{ $entries->total() === 1 ? '' : 's' }}{{ $filters['search'] !== '' ? ' for "'.$filters['search'].'"' : '' }}</div>

    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="width:64px"></th>
                    <th style="width:64px"></th>
                    <th style="width:105px">Issue Date</th>
                    <th style="width:105px">Due Date</th>
                    <th style="width:120px">Reference</th>
                    <th style="min-width:170px">Supplier</th>
                    <th style="min-width:160px">Description</th>
                    <th style="width:120px">Project</th>
                    <th class="gl-r" style="width:130px">Invoice Amount</th>
                    <th class="gl-r" style="width:120px">Balance Due</th>
                    <th style="width:120px">Status</th>
                </tr></thead>
                <tbody>
                @forelse($entries as $invoice)
                    @php $balance = $invoice->balanceDue(); @endphp
                    <tr>
                        <td data-label=""><a class="pi-btn" href="{{ route('crm.purchase_invoices.edit', $invoice->id) }}">Edit</a></td>
                        <td data-label=""><a class="pi-btn" href="{{ route('crm.purchase_invoices.show', $invoice->id) }}">View</a></td>
                        <td class="gl-date" data-label="Issue Date">{{ $invoice->issue_date->format('d M Y') }}</td>
                        <td class="gl-date" data-label="Due Date">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</td>
                        <td data-label="Reference"><span class="ex-name">{{ $invoice->number }}</span>@if($invoice->reference)<span class="ex-sub">{{ $invoice->reference }}</span>@endif</td>
                        <td data-label="Supplier"><span class="gl-name">{{ $invoice->vendor_name }}</span></td>
                        <td data-label="Description">{{ $invoice->description ? \Illuminate\Support\Str::limit($invoice->description, 60) : \Illuminate\Support\Str::limit($invoice->items->pluck('name')->implode(', '), 60) }}
                            @if($invoice->items->whereNotNull('category')->isNotEmpty())<span class="ex-sub">{{ $invoice->items->pluck('category')->filter()->unique()->implode(', ') }}</span>@endif
                        </td>
                        <td class="gl-mutedtext" data-label="Project">{{ $invoice->project ?: '—' }}</td>
                        <td class="gl-r gl-num" data-label="Invoice Amount">{{ $fmt($invoice->total) }} <small>{{ $invoice->currency }}</small></td>
                        <td class="gl-r gl-num {{ $balance > 0.009 ? 'gl-red' : '' }}" data-label="Balance Due">@if($balance < -0.009){{ $fmt(0) }}<span class="ex-sub" style="color:#b45309">Advance {{ $fmt(-$balance) }}</span>@else{{ $fmt($balance) }}@endif</td>
                        <td data-label="Status"><span class="pi-status {{ $invoice->status() }}">{{ $invoice->statusLabel() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="11"><div class="gl-empty"><i class="fas fa-file-invoice"></i>{{ $isFiltered ? 'No invoices match the selected filters.' : 'No purchase invoices yet — click "New Purchase Invoice" to add the first one.' }}</div></td></tr>
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
