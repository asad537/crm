@extends('crm.layout')

@section('title', 'Invoice')

@section('styles')
    /* ── PAGE WRAPPER ── */
    .orders-page { display: flex; flex-direction: column; gap: 1rem; }

    /* ── STATS ROW ── */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 0;
    }
    .stat-card {
        background: white;
        border-radius: 18px;
        padding: 1.05rem 1.3rem;
        border: 1px solid #f0f0f8;
        box-shadow: 0 2px 12px rgba(var(--primary-rgb), 0.06);
        display: flex;
        align-items: center;
        gap: 1.1rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.12);
    }
    .stat-icon-wrap {
        width: 46px; height: 46px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .stat-icon-wrap.purple { background: var(--primary-soft); color: var(--primary-purple); }
    .stat-icon-wrap.green  { background: linear-gradient(135deg,#d1fae5,#a7f3d0); color: #059669; }
    .stat-icon-wrap.blue   { background: linear-gradient(135deg,#dbeafe,#bfdbfe); color: #2563eb; }
    .stat-icon-wrap.amber  { background: linear-gradient(135deg,#fef3c7,#fde68a); color: #b45309; }
    .stat-text { display: flex; flex-direction: column; gap: 3px; }
    .stat-label {
        font-size: 0.7rem; font-weight: 700;
        color: #94a3b8; text-transform: uppercase; letter-spacing: 0.08em;
    }
    .stat-value {
        font-size: 1.55rem; font-weight: 800; color: #0f172a; line-height: 1;
    }
    .stat-sub {
        font-size: 0.72rem; color: #10b981; font-weight: 600; margin-top: 2px;
    }

    /* ── FILTER BAR ── */
    .filter-card {
        background: white;
        border-radius: 18px;
        padding: 0.95rem 1.3rem;
        border: 1px solid #f0f0f8;
        box-shadow: 0 2px 12px rgba(var(--primary-rgb), 0.05);
        margin-bottom: 0;
    }
    .filter-card-title {
        font-size: 0.7rem; font-weight: 800; color: #94a3b8;
        text-transform: uppercase; letter-spacing: 0.08em;
        margin-bottom: 0.6rem;
        display: flex; align-items: center; gap: 6px;
    }
    .filter-row {
        display: grid;
        grid-template-columns: 2fr 1.2fr 1.5fr auto;
        gap: 0.85rem;
        align-items: end;
    }
    .filter-group { display: flex; flex-direction: column; gap: 0.35rem; }
    .filter-group label {
        font-size: 0.72rem; font-weight: 700; color: #64748b;
        display: flex; align-items: center; gap: 5px;
    }
    .filter-group input,
    .filter-group select {
        padding: 0.65rem 0.9rem;
        border: 1.5px solid #e8eaf6;
        border-radius: 11px;
        font-size: 0.875rem; color: #1e293b;
        outline: none; background: #fbfbff;
        transition: border 0.2s, box-shadow 0.2s;
        width: 100%;
    }
    .filter-group input:focus,
    .filter-group select:focus {
        border-color: var(--primary-purple);
        box-shadow: 0 0 0 3px var(--primary-shadow);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }
    .filter-actions { display: flex; gap: 0.5rem; align-items: flex-end; }
    .btn-apply {
        padding: 0.65rem 1.35rem;
        background: var(--primary-purple);
        color: white; border: none; border-radius: 11px;
        font-weight: 700; font-size: 0.875rem; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
        box-shadow: 0 3px 10px rgba(var(--primary-rgb), 0.3);
        transition: all 0.2s; white-space: nowrap;
    }
    .btn-apply:hover {
        background: var(--primary-hover);
        box-shadow: 0 6px 16px rgba(var(--primary-rgb), 0.4);
        transform: translateY(-1px);
    }
    /* Clean theme styling for the date-range popover buttons (override library defaults). */
    .daterangepicker .drp-buttons .btn {
        border-radius: 8px; font-weight: 700; font-size: .8rem;
        padding: .45rem 1.1rem; border: 1px solid transparent;
        box-shadow: none; text-shadow: none; transition: all .15s;
    }
    .daterangepicker .drp-buttons .applyBtn {
        background: var(--primary-purple); border-color: var(--primary-purple); color: #fff;
    }
    .daterangepicker .drp-buttons .applyBtn:hover,
    .daterangepicker .drp-buttons .applyBtn:focus {
        background: var(--primary-hover); border-color: var(--primary-hover); color: #fff;
    }
    .daterangepicker .drp-buttons .cancelBtn {
        background: #f1f5f9; border-color: #e2e8f0; color: #475569;
    }
    .daterangepicker .drp-buttons .cancelBtn:hover { background: #e2e8f0; color: #334155; }
    .daterangepicker td.active, .daterangepicker td.active:hover,
    .daterangepicker .ranges li.active { background-color: var(--primary-purple); }
    .btn-reset {
        padding: 0.65rem 1rem;
        background: #f1f5f9; color: #64748b;
        border: 1.5px solid #e2e8f0; border-radius: 11px;
        font-weight: 700; font-size: 0.875rem; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
        text-decoration: none; transition: all 0.2s; white-space: nowrap;
    }
    .btn-reset:hover { background: #e2e8f0; color: #374151; }

    /* ── TABLE CARD ── */
    .content-card {
        background: white;
        border-radius: 18px;
        border: 1px solid #f0f0f8;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(var(--primary-rgb), 0.05);
    }
    .table-header {
        padding: 1.1rem 1.5rem;
        border-bottom: 1px solid #f8fafc;
        display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(to right, #fafafa, white);
    }
    .table-header-title {
        font-size: 0.85rem; font-weight: 800; color: #1e293b;
        display: flex; align-items: center; gap: 8px;
    }
    .table-header-title .count-pill {
        background: var(--primary-soft); color: var(--primary-purple);
        font-size: 0.72rem; font-weight: 800;
        padding: 3px 9px; border-radius: 99px;
    }

    .orders-table { width: 100%; border-collapse: collapse; }
    .orders-table thead tr {
        background: #f8f9ff;
    }
    .orders-table th {
        padding: 0.75rem 0.85rem;
        text-align: left;
        font-size: 0.65rem; font-weight: 800;
        color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em;
        border-bottom: 1px solid #f0f0f8;
        white-space: nowrap;
    }
    .orders-table th:last-child { text-align: center; }
    .orders-table td {
        padding: 0.85rem 0.85rem;
        border-bottom: 1px solid #f8f9ff;
        vertical-align: middle;
    }
    .orders-table tr:last-child td { border-bottom: none; }
    .orders-table tbody tr {
        transition: background 0.15s;
    }
    .orders-table tbody tr:hover td { background: #f9f8ff; }

    /* Cell helpers */
    .id-chip {
        display: inline-block;
        background: #f1f5f9; color: #64748b;
        font-size: 0.72rem; font-weight: 700;
        padding: 2px 7px; border-radius: 7px;
    }
    .client-name { font-weight: 700; color: #0f172a; font-size: 0.82rem; }
    .client-email { font-size: 0.72rem; color: #94a3b8; margin-top: 2px; }
    .product-tag {
        display: inline-flex; align-items: center; gap: 4px;
        background: #f0f9ff; color: #0369a1;
        padding: 3px 8px; border-radius: 7px;
        font-size: 0.72rem; font-weight: 600;
        max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .price-val { font-weight: 700; color: #1e293b; font-size: 0.82rem; }
    .qty-badge {
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--primary-soft); color: var(--primary-purple);
        font-size: 0.75rem; font-weight: 800;
        min-width: 36px; padding: 3px 8px; border-radius: 7px;
    }
    .total-val {
        font-weight: 900; color: var(--primary-purple); font-size: 0.875rem;
    }
    .agent-pill {
        display: inline-flex; align-items: center; gap: 4px;
        background: #f8fafc; color: #475569;
        border: 1px solid #e2e8f0;
        padding: 3px 8px; border-radius: 99px;
        font-size: 0.72rem; font-weight: 600;
    }
    .agent-pill .ag-dot {
        width: 7px; height: 7px;
        background: #10b981; border-radius: 50%; flex-shrink: 0;
    }
    .date-val { font-size: 0.72rem; color: #94a3b8; white-space: nowrap; }

    .btn-invoice {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 0.35rem 0.75rem;
        background: linear-gradient(135deg,#ecfdf5,#d1fae5);
        color: #065f46; border: 1px solid #a7f3d0;
        border-radius: 8px; font-size: 0.72rem; font-weight: 700;
        text-decoration: none; transition: all 0.2s;
        box-shadow: 0 2px 6px rgba(16,185,129,0.1);
    }
    .btn-invoice:hover {
        background: linear-gradient(135deg,#d1fae5,#a7f3d0);
        box-shadow: 0 4px 10px rgba(16,185,129,0.2);
        transform: translateY(-1px);
        color: #064e3b;
    }

    /* Table footer / totals row */
    .tfoot-row td {
        background: linear-gradient(to right,#f8f9ff,#f0f0fd);
        padding: 0.9rem 1.25rem;
        border-top: 1.5px solid var(--primary-soft);
        font-size: 0.82rem; color: #64748b; font-weight: 600;
    }
    .tfoot-total { font-weight: 900; color: var(--primary-purple); font-size: 1.05rem; }

    /* Pagination */
    .pag-wrap { padding: 1rem 1.5rem; border-top: 1px solid #f0f0f8; }

    /* Empty state */
    .empty-state {
        padding: 5rem 2rem; text-align: center;
    }
    .empty-icon-wrap {
        width: 72px; height: 72px;
        background: var(--primary-soft);
        border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.25rem;
        font-size: 2rem; color: var(--primary-purple);
    }
    .empty-title { font-size: 1.1rem; font-weight: 800; color: #334155; margin-bottom: 0.5rem; }
    .empty-sub { font-size: 0.875rem; color: #94a3b8; max-width: 320px; margin: 0 auto; line-height: 1.6; }
    .btn-clear-filters {
        display: inline-flex; align-items: center; gap: 6px;
        margin-top: 1.5rem; padding: 0.65rem 1.35rem;
        background: var(--primary-purple); color: white;
        border-radius: 11px; font-weight: 700; font-size: 0.875rem;
        text-decoration: none; transition: all 0.2s;
    }
    .btn-clear-filters:hover { background: var(--primary-hover); }

    /* Applied filter chips */
    .filter-chips {
        display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;
    }
    .filter-chip {
        display: inline-flex; align-items: center; gap: 5px;
        background: var(--primary-soft); color: var(--primary-purple);
        border: 1px solid color-mix(in srgb,var(--primary-purple) 18%,#fff);
        padding: 4px 10px; border-radius: 99px;
        font-size: 0.72rem; font-weight: 700;
    }

    @media (max-width: 900px) {
        .stats-row { grid-template-columns: 1fr; }
        .filter-row { grid-template-columns: 1fr 1fr; }
        .filter-actions { grid-column: span 2; }
        .orders-table th:nth-child(5),
        .orders-table td:nth-child(5) { display: none; }
    }
    @media (max-width: 600px) {
        .stats-row { grid-template-columns: 1fr; }
        .filter-row { grid-template-columns: 1fr; }
        .filter-actions { grid-column: span 1; }
        .orders-table th:nth-child(7),
        .orders-table td:nth-child(7),
        .orders-table th:nth-child(8),
        .orders-table td:nth-child(8) { display: none; }
    }
    .btn-edit-invoice:hover { border-color: var(--primary-purple) !important; color: var(--primary-purple) !important; background: var(--primary-soft) !important; }
    .btn-delete-invoice:hover { border-color: #dc2626 !important; background: #fef2f2 !important; color: #991b1b !important; }
@endsection

@section('header_actions')
    <div style="display:flex;align-items:center;gap:0.75rem;">
        @if($orders->count() > 0)
        <div style="font-size:0.8rem;color:#94a3b8;">
            <i class="fas fa-circle" style="color:#10b981;font-size:0.5rem;vertical-align:middle;margin-right:4px;"></i>
            Live · {{ now()->format('h:i A') }}
        </div>
        @endif
        @if(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || Auth::guard('crm')->user()->isSales() || Auth::guard('crm')->user()->isAccounts())
        <a href="{{ route('crm.orders.create') }}" class="btn-apply" style="text-decoration:none;">
            <i class="fas fa-plus"></i> Create Invoice
        </a>
        @endif
    </div>
@endsection

@section('content')
@php
    // Workspace default currency for aggregate figures (Al Massa bills in AED).
    $__wsCur = (isset($activeCrmWorkspace) && $activeCrmWorkspace && $activeCrmWorkspace->slug === 'mybox-packaging-app') ? 'AED' : 'USD';
    $__u = Auth::guard('crm')->user();
@endphp
<div class="orders-page">

{{-- ── STATS ROW ── --}}
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon-wrap purple">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <div class="stat-text">
            <span class="stat-label">Total Invoices</span>
            <span class="stat-value">{{ number_format($totalOrders) }}</span>
            <span class="stat-sub">Confirmed deals</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-text">
            <span class="stat-label">Total Sale</span>
            <span class="stat-value">{{ $__wsCur }} {{ number_format($totalRevenue, 0) }}</span>
            <span class="stat-sub">Order value sum</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap amber">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="stat-text">
            <span class="stat-label">Unpaid</span>
            <span class="stat-value">{{ $__wsCur }} {{ number_format($unpaidTotal ?? 0, 0) }}</span>
            <span class="stat-sub" style="color:#b45309">{{ $unpaidCount ?? 0 }} pending invoice{{ ($unpaidCount ?? 0) === 1 ? '' : 's' }}</span>
        </div>
    </div>
</div>

{{-- ── FILTER CARD ── --}}
<div class="filter-card">
    <div class="filter-card-title">
        <i class="fas fa-sliders-h" style="color:var(--primary-purple);"></i>
        Filters
    </div>
    <form method="GET" action="{{ route('crm.orders.index') }}">
    <div class="filter-row">
        <div class="filter-group">
            <label><i class="fas fa-calendar-alt" style="color:var(--primary-purple);font-size:0.7rem;"></i> Date Range</label>
            <div style="position:relative">
                <i class="fas fa-calendar-alt" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.82rem;pointer-events:none;"></i>
                <input type="text" id="dateRangePicker" name="date_range_display"
                       placeholder="Select date range..."
                       value="{{ request('start_date') && request('end_date') ? request('start_date') . ' — ' . request('end_date') : '' }}"
                       readonly style="cursor:pointer;padding-left:36px;width:100%;box-sizing:border-box;">
            </div>
            <input type="hidden" name="start_date" id="startDate" value="{{ request('start_date') }}">
            <input type="hidden" name="end_date" id="endDate" value="{{ request('end_date') }}">
        </div>
        <div class="filter-group">
            <label><i class="fas fa-user-tie" style="color:var(--primary-purple);font-size:0.7rem;"></i> Agent</label>
            <select name="agent" onchange="this.form.submit()">
                <option value="">All Agents</option>
                @foreach($agents as $agent)
                <option value="{{ $agent }}" {{ request('agent') == $agent ? 'selected' : '' }}>{{ $agent }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label><i class="fas fa-search" style="color:var(--primary-purple);font-size:0.7rem;"></i> Search</label>
            <input type="text" name="search" id="orderSearch" autocomplete="off" placeholder="Type to filter instantly..." value="{{ request('search') }}" oninput="oiLiveSearch(this.value)">
        </div>
        <div class="filter-actions">
            <a href="{{ route('crm.orders.index') }}" class="btn-reset" title="Reset filters">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </div>

    {{-- Applied chips --}}
    @if(request()->hasAny(['start_date','end_date','agent','search']))
    <div class="filter-chips" style="margin-top:0.85rem; margin-bottom:0;">
        @if(request('start_date') && request('end_date'))
        <span class="filter-chip"><i class="fas fa-calendar-alt"></i> {{ request('start_date') }} → {{ request('end_date') }}</span>
        @endif
        @if(request('agent'))
        <span class="filter-chip"><i class="fas fa-user"></i> {{ request('agent') }}</span>
        @endif
        @if(request('search'))
        <span class="filter-chip"><i class="fas fa-search"></i> "{{ request('search') }}"</span>
        @endif
    </div>
    @endif
    </form>
</div>

{{-- ── ORDERS TABLE ── --}}
<div class="content-card">
    <div class="table-header">
        <div class="table-header-title">
            <i class="fas fa-list-ul" style="color:var(--primary-purple);"></i> Invoice List <span class="count-pill">{{ $orders->total() }}</span>
        </div>
        <div style="display:flex; align-items:center; gap:0.75rem;">
            @if($orders->total() > 0)
            <div style="font-size:0.78rem;color:#94a3b8;">
                Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }}
            </div>
            <span id="oiSelectedCount" style="display:none;font-size:0.72rem;font-weight:700;color:var(--primary-purple);background:var(--primary-soft);padding:0.25rem 0.6rem;border-radius:999px;">0 selected</span>
            @if(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager())
            <a href="{{ route('crm.orders.bulk_invoice') }}{{ request('start_date') ? '?start_date='.request('start_date').'&end_date='.request('end_date') : '' }}"
               id="printAllBtn"
               target="_blank"
               style="display:inline-flex;align-items:center;gap:5px;padding:0.4rem 0.9rem;background:var(--primary-purple);color:white;border-radius:9px;font-size:0.75rem;font-weight:700;text-decoration:none;box-shadow:0 2px 8px var(--primary-shadow);transition:all 0.2s;"
               onmouseover="this.style.transform='translateY(-1px)'"
               onmouseout="this.style.transform='none'">
                <i class="fas fa-print"></i> Print All
            </a>
            @endif
            @if(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || Auth::guard('crm')->user()->isAccounts())
            @php $__exportParams = array_filter(request()->only(['start_date','end_date','agent','search'])); @endphp
            <a href="{{ route('crm.orders.export', array_merge($__exportParams, ['format'=>'excel'])) }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:0.4rem 0.85rem;background:#fff;color:#15803d;border:1px solid #e2e8f0;border-radius:9px;font-size:0.75rem;font-weight:700;text-decoration:none;">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            <a href="{{ route('crm.orders.export', array_merge($__exportParams, ['format'=>'pdf'])) }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:0.4rem 0.85rem;background:#fff;color:#b91c1c;border:1px solid #e2e8f0;border-radius:9px;font-size:0.75rem;font-weight:700;text-decoration:none;">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            @endif
            @endif
        </div>
    </div>

    @if($orders->count() > 0)
    <table class="orders-table">
        <thead>
            <tr>
                <th style="width:36px;text-align:center;"><input type="checkbox" id="oiCheckAll" onclick="oiToggleAll(this)" style="cursor:pointer;"></th>
                <th>#</th>
                <th>Client</th>
                <th>Product</th>
                <th>Payment</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Agent</th>
                <th>Date</th>
                <th style="text-align:center;">Invoice</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            @php $__paid = in_array(strtolower($order->payment_status ?? ''), ['paid','received','approved']); @endphp
            <tr data-search="{{ strtolower($order->client_name.' '.$order->client_email.' '.$order->product_name) }}">
                <td style="text-align:center;">
                    <input type="checkbox" class="oi-check" value="{{ $order->id }}" onclick="oiSyncSelected()" style="cursor:pointer;">
                </td>
                <td>
                    <span class="id-chip">{{ $order->order_invoice_number ?: '#'.str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>
                </td>
                <td>
                    <div class="client-name">{{ $order->client_name }}</div>
                    <div class="client-email">{{ $order->client_email }}</div>
                </td>
                <td>
                    <span class="product-tag">
                        <i class="fas fa-box" style="font-size:0.65rem;opacity:0.7;"></i>
                        {{ $order->product_name ?: 'General' }}
                    </span>
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:0.3rem 0.65rem;border-radius:999px;font-size:0.72rem;font-weight:700;{{ $__paid ? 'color:#15803d;background:#dcfce7;' : 'color:#b45309;background:#fef3c7;' }}">
                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span>{{ $__paid ? 'Paid' : 'Unpaid' }}
                    </span>
                </td>
                <td>
                    <span class="qty-badge">{{ number_format($order->order_quantity ?? 0) }}</span>
                </td>
                <td>
                    <span class="total-val">
                        {{ $order->invoice_currency ?: $__wsCur }} {{ number_format(($order->order_price ?? 0) * ($order->order_quantity ?? 0), 2) }}
                    </span>
                </td>
                <td>
                    <span class="agent-pill">
                        <span class="ag-dot"></span>
                        {{ $order->order_marked_by ?? '—' }}
                    </span>
                </td>
                <td>
                    <span class="date-val">
                        {{ $order->order_marked_at
                            ? \Carbon\Carbon::parse($order->order_marked_at)->format('M d, Y')
                            : $order->created_at->format('M d, Y') }}
                    </span>
                </td>
                <td style="text-align:center;">
                    @php
                        $__canEdit = $__u && ($__u->isAdmin() || $__u->isSalesManager() || $__u->isAccounts() || ($__u->isSales() && ($order->assigned_to == $__u->id || $order->order_marked_by === $__u->name)));
                        $__canDelete = $__u && ($__u->isAdmin() || $__u->isSuperAdmin() || $__u->isAccounts());
                    @endphp
                    <div style="display:inline-flex;align-items:center;gap:6px;">
                        <a href="{{ route('crm.orders.invoice', $order->id) }}" target="_blank" class="btn-invoice">
                            <i class="fas fa-file-invoice"></i> Invoice
                        </a>
                        @if($__canEdit)
                        <a href="{{ route('crm.orders.invoice.edit', $order->id) }}" class="btn-edit-invoice" title="Edit invoice" style="display:inline-flex;align-items:center;gap:5px;padding:0.4rem 0.7rem;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;font-size:0.72rem;font-weight:700;text-decoration:none;transition:all .15s;">
                            <i class="fas fa-pen"></i> Edit
                        </a>
                        @endif
                        @if($__canDelete)
                        <form method="POST" action="{{ route('crm.orders.destroy', $order->id) }}" style="display:inline;margin:0;"
                            onsubmit="return confirm('Delete invoice {{ $order->order_invoice_number ?: '#'.str_pad($order->id, 4, '0', STR_PAD_LEFT) }}? This is logged permanently.');">
                            {{ csrf_field() }}{{ method_field('DELETE') }}
                            <button type="submit" class="btn-delete-invoice" title="Delete invoice (logged)"
                                style="display:inline-flex;align-items:center;gap:5px;padding:0.4rem 0.7rem;border:1px solid #fecaca;border-radius:8px;background:#fff;color:#b91c1c;font-size:0.72rem;font-weight:700;cursor:pointer;transition:all .15s;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
            <tr id="oiNoMatch" style="display:none;"><td colspan="10" style="text-align:center;padding:2rem;color:#94a3b8;">No invoices match your search.</td></tr>
        </tbody>
        <tfoot>
            <tr class="tfoot-row">
                <td colspan="6" style="font-weight:700; color:#94a3b8;">
                    Page Total ({{ $orders->count() }} orders)
                </td>
                <td>
                    <span class="tfoot-total">
                        {{ $__wsCur }} {{ number_format($orders->sum(fn($o) => ($o->order_price ?? 0) * ($o->order_quantity ?? 0)), 2) }}
                    </span>
                </td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <div class="pag-wrap">
        {{ $orders->appends(request()->all())->links() }}
    </div>

    @else
    <div class="empty-state">
        <div class="empty-icon-wrap">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <div class="empty-title">No Orders Found</div>
        <div class="empty-sub">
            Confirmed online and offline orders will appear here.
        </div>
        @if(request()->hasAny(['start_date', 'end_date', 'agent', 'search']))
        <a href="{{ route('crm.orders.index') }}" class="btn-clear-filters">
            <i class="fas fa-times"></i> Clear Filters
        </a>
        @endif
    </div>
    @endif
</div>

</div>
@endsection

@section('scripts')
<script>
$(function() {
    const startVal = $('#startDate').val();
    const endVal   = $('#endDate').val();

    $('#dateRangePicker').daterangepicker({
        autoUpdateInput: false,
        startDate: startVal ? moment(startVal) : moment().subtract(29, 'days'),
        endDate:   endVal   ? moment(endVal)   : moment(),
        locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' },
        ranges: {
            'Today':        [moment(), moment()],
            'Yesterday':    [moment().subtract(1,'days'), moment().subtract(1,'days')],
            'Last 7 Days':  [moment().subtract(6,'days'), moment()],
            'Last 30 Days': [moment().subtract(29,'days'), moment()],
            'This Month':   [moment().startOf('month'), moment().endOf('month')],
            'Last Month':   [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')]
        }
    });

    if (startVal && endVal) {
        $('#dateRangePicker').val(startVal + ' — ' + endVal);
    }

    $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' — ' + picker.endDate.format('YYYY-MM-DD'));
        $('#startDate').val(picker.startDate.format('YYYY-MM-DD'));
        $('#endDate').val(picker.endDate.format('YYYY-MM-DD'));
        // No Apply button anymore — submit the filter form automatically.
        $(this).closest('form').submit();
    });

    $('#dateRangePicker').on('cancel.daterangepicker', function() {
        $(this).val('');
        $('#startDate').val('');
        $('#endDate').val('');
        // Reload without the date filter.
        $(this).closest('form').submit();
    });

    // Keep Print All URL in sync with current filter values
    function updatePrintAllBtn() {
        var base   = '{{ route('crm.orders.bulk_invoice') }}';
        var start  = $('#startDate').val();
        var end    = $('#endDate').val();
        var agent  = $('select[name="agent"]').val();
        var search = $('input[name="search"]').val();
        var params = [];
        if (start && end) { params.push('start_date=' + start, 'end_date=' + end); }
        if (agent)        { params.push('agent='  + encodeURIComponent(agent)); }
        if (search)       { params.push('search=' + encodeURIComponent(search)); }
        var url = base + (params.length ? '?' + params.join('&') : '');
        $('#printAllBtn').attr('href', url);
    }

    // Update on date pick
    $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
        updatePrintAllBtn();
    });

    // Update on agent change
    $('select[name="agent"]').on('change', function() {
        updatePrintAllBtn();
    });

    // Update on search input
    $('input[name="search"]').on('input', function() {
        updatePrintAllBtn();
    });

    // Init on page load (in case URL already has params)
    updatePrintAllBtn();
});

// ── Live client-side search (no page reload) ──
function oiLiveSearch(term) {
    term = (term || '').trim().toLowerCase();
    var shown = 0;
    document.querySelectorAll('.orders-table tbody tr').forEach(function (row) {
        var hay = row.getAttribute('data-search') || '';
        var match = term === '' || hay.indexOf(term) !== -1;
        row.style.display = match ? '' : 'none';
        if (match) shown++;
    });
    var empty = document.getElementById('oiNoMatch');
    if (empty) empty.style.display = shown === 0 ? '' : 'none';
    var allBox = document.getElementById('oiCheckAll');
    if (allBox) allBox.checked = false;
    oiSyncSelected();
}

// ── Row selection ──
function oiToggleAll(box) {
    document.querySelectorAll('.orders-table tbody tr').forEach(function (row) {
        if (row.style.display === 'none') return;
        var cb = row.querySelector('.oi-check');
        if (cb) cb.checked = box.checked;
    });
    oiSyncSelected();
}
function oiSyncSelected() {
    var checked = document.querySelectorAll('.oi-check:checked').length;
    var badge = document.getElementById('oiSelectedCount');
    if (badge) {
        badge.textContent = checked + ' selected';
        badge.style.display = checked > 0 ? '' : 'none';
    }
}
</script>
@endsection
