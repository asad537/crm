@extends('crm.layout')

@section('title', 'Admin Reports')

@section('styles')
    .admin-report-page,
    .admin-report-page * {
        box-sizing: border-box;
    }

    .admin-report-page {
        --ar-bg: var(--bg-body);
        --ar-card: #ffffff;
        --ar-ink: var(--text-dark);
        --ar-text: #475569;
        --ar-muted: #76859a;
        --ar-line: #e2e8f0;
        --ar-soft: var(--primary-soft);
        --ar-primary: var(--primary-purple);
        --ar-primary-dark: var(--primary-hover);
        --ar-green-bg: #ecfdf5;
        --ar-green-text: #059669;
        --ar-yellow-bg: #fffbeb;
        --ar-yellow-text: #d97706;
        --ar-red-bg: #fef2f2;
        --ar-red-text: #dc2626;
        --ar-shadow: 0 4px 24px rgba(15, 23, 42, 0.06);
        --ar-shadow-hover: 0 8px 32px var(--primary-shadow);

        display: flex;
        flex-direction: column;
        gap: 14px;
        width: 100%;
        color: var(--ar-text);
        font-size: 13px;
        padding: 4px 0 20px;
    }

    .ar-page-intro {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 24px;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        background: var(--ar-card);
        box-shadow: var(--ar-shadow);
        position: relative;
    }
    
    .ar-page-intro h2 {
        margin: 0;
        color: var(--ar-ink);
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }

    .ar-page-intro p {
        margin: 5px 0 0;
        max-width: 720px;
        color: var(--ar-text);
        font-size: 13px;
        font-weight: 500;
        line-height: 1.4;
    }

    .ar-date-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 36px;
        padding: 0 16px;
        border-radius: 8px;
        background: var(--ar-soft);
        border: 1px solid color-mix(in srgb, var(--ar-primary) 18%, #ffffff);
        color: var(--ar-primary);
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        position: relative;
    }

    .ar-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 14px;
    }

    .ar-kpi-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px;
        border-radius: 16px;
        border: none;
        background: var(--ar-card);
        box-shadow: var(--ar-shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .ar-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--ar-shadow-hover);
    }

    .ar-kpi-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 18px;
        background: var(--ar-soft);
        color: var(--ar-primary);
        flex-shrink: 0;
    }

    .ar-kpi-label {
        color: var(--ar-muted);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 4px;
    }

    .ar-kpi-value {
        color: var(--ar-ink);
        font-size: 24px;
        font-weight: 900;
        line-height: 1;
        letter-spacing: -0.03em;
        white-space: nowrap;
    }

    .ar-panel {
        width: 100%;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.6);
        background: var(--ar-card);
        box-shadow: var(--ar-shadow);
    }

    .ar-panel-head {
        min-height: 56px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 20px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        background: rgba(255, 255, 255, 0.9);
        border-radius: 16px 16px 0 0;
        backdrop-filter: blur(10px);
    }

    .ar-panel-title {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: var(--ar-ink);
        font-size: 15px;
        font-weight: 700;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }

    .ar-panel-title i {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: var(--ar-soft);
        color: var(--ar-primary);
        font-size: 12px;
    }

    .ar-panel-meta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 26px;
        padding: 0 10px;
        border-radius: 999px;
        background: var(--ar-soft);
        color: var(--ar-muted);
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .ar-filter-body {
        padding: 16px 18px;
        background: color-mix(in srgb, var(--ar-soft) 36%, #f8fafc);
        border-radius: 0 0 12px 12px;
    }

    .ar-filter-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: end;
    }

    .ar-field {
        flex: 1 1 160px;
        min-width: 0;
    }

    .ar-field.ar-wide {
        flex: 2 1 220px;
    }

    .ar-actions {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }
    .ar-export-menu{position:relative}.ar-export-options{display:none;position:absolute;right:0;top:calc(100% + 6px);z-index:30;min-width:140px;padding:6px;background:#fff;border:1px solid var(--ar-line);border-radius:9px;box-shadow:0 12px 28px rgba(15,23,42,.15)}.ar-export-options.show{display:block}.ar-export-options a{display:block;padding:9px 11px;border-radius:6px;color:#334155;text-decoration:none;font-weight:700}.ar-export-options a:hover{background:var(--ar-soft);color:var(--ar-primary)}

    .ar-field label {
        display: block;
        margin: 0 0 6px;
        color: var(--ar-text);
        font-size: 12px;
        font-weight: 600;
    }

    .ar-field input,
    .ar-field select {
        width: 100%;
        height: 38px;
        border: 1px solid var(--ar-line);
        border-radius: 8px;
        background: #ffffff;
        color: var(--ar-ink);
        padding: 0 12px;
        font-size: 13px;
        font-weight: 500;
        outline: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.01);
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .ar-field input::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    .ar-field input:focus,
    .ar-field select:focus {
        border-color: var(--ar-primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }

    .ar-btn {
        height: 38px;
        min-width: 90px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 14px;
        border-radius: 8px;
        border: 1px solid transparent;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        white-space: nowrap;
        line-height: 1;
        transition: all 0.2s ease;
    }

    .ar-btn-primary {
        background: var(--ar-primary);
        color: #ffffff !important;
        border: none;
    }

    .ar-btn-primary:hover {
        background: var(--ar-primary-dark);
        transform: translateY(-1px);
    }

    .ar-btn-light {
        background: #ffffff;
        color: var(--ar-text) !important;
        border-color: var(--ar-line);
    }

    .ar-btn-light:hover {
        background: var(--ar-soft);
    }

    .ar-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .ar-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .ar-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .ar-table th {
        padding: 12px 20px;
        background: #f8fafc;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        color: #475569;
        text-align: left;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .ar-table td {
        padding: 14px 20px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.4);
        background: #ffffff;
        color: var(--ar-ink);
        font-size: 13px;
        font-weight: 500;
        vertical-align: middle;
        white-space: nowrap;
        transition: background 0.2s ease;
    }

    .ar-table tbody tr {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .ar-table tbody tr:hover td {
        background: #fbfdff;
    }

    .ar-table tbody tr:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        position: relative;
        z-index: 10;
    }

    .ar-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .ar-table-master {
        min-width: 1100px;
    }

    .ar-order-id {
        color: var(--ar-ink);
        font-size: 14px;
        font-weight: 700;
    }

    .ar-client-name {
        color: var(--ar-ink);
        font-weight: 600;
        margin-bottom: 2px;
    }

    .ar-muted {
        color: var(--ar-muted);
        font-size: 12px;
        font-weight: 400;
        white-space: normal;
    }

    .ar-amount {
        color: var(--ar-ink);
        font-weight: 950;
    }

    .ar-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 26px;
        padding: 0 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
        background: var(--ar-soft);
        color: var(--ar-text);
        letter-spacing: 0.02em;
    }

    .ar-badge.ok {
        background: var(--ar-green-bg);
        color: var(--ar-green-text);
        border: 1px solid rgba(5, 150, 105, 0.1);
    }

    .ar-badge.warn {
        background: var(--ar-yellow-bg);
        color: var(--ar-yellow-text);
        border: 1px solid rgba(217, 119, 6, 0.1);
    }

    .ar-badge.bad {
        background: var(--ar-red-bg);
        color: var(--ar-red-text);
        border: 1px solid rgba(220, 38, 38, 0.1);
    }

    .ar-empty-row {
        padding: 34px 20px !important;
        color: #94a3b8 !important;
        text-align: center;
        font-weight: 900 !important;
    }

    .ar-pagination {
        padding: 16px 20px;
        border-top: 1px solid var(--ar-line);
        background: #ffffff;
    }

    @media (max-width: 1024px) {
        .ar-table-wrap {
            overflow: visible;
        }
        .ar-table, .ar-table tbody, .ar-table tr, .ar-table td {
            display: block;
            width: 100%;
        }
        .ar-table thead {
            display: none;
        }
        .ar-table tr {
            margin-bottom: 16px;
            border: 1px solid var(--ar-line);
            border-radius: 16px;
            padding: 8px 0;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }
        .ar-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            border-bottom: 1px solid var(--ar-line);
            text-align: right;
            white-space: normal;
        }
        .ar-table td:last-child {
            border-bottom: none;
        }
        .ar-table td:not(.ar-empty-row)::before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--ar-muted);
            text-transform: uppercase;
            font-size: 11px;
            margin-right: 16px;
            text-align: left;
            letter-spacing: 0.03em;
        }
        .ar-empty-row {
            justify-content: center !important;
        }
        .ar-table-master {
            min-width: 0;
        }
    }

    @media (max-width: 1280px) {
        /* Filter layout handles itself via flex */
    }

    @media (max-width: 900px) {
        .ar-page-intro,
        .ar-panel-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .ar-summary-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .admin-report-page {
            gap: 14px;
        }

        .ar-page-intro {
            padding: 18px;
        }

        .ar-page-intro h2 {
            font-size: 21px;
        }

        .ar-kpi-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .ar-kpi-card {
            min-height: 104px;
            padding: 16px;
        }

        .ar-kpi-value {
            font-size: 25px;
        }

        .ar-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .ar-btn {
            width: 100%;
        }
    }
@endsection

@section('content')
@php
    $activeFilters = collect(['start_date', 'end_date', 'status', 'agent', 'city', 'zip', 'search'])
        ->filter(fn ($key) => filled(request($key)))
        ->count();

    $reportCards = [
        ['label' => 'Total Orders', 'value' => number_format($totalOrders), 'icon' => 'fa-clipboard-list'],
        ['label' => 'Pending', 'value' => number_format($pendingOrders), 'icon' => 'fa-hourglass-half'],
        ['label' => 'Delivered', 'value' => number_format($deliveredOrders), 'icon' => 'fa-truck'],
        ['label' => 'Rejected', 'value' => number_format($rejectedOrders), 'icon' => 'fa-ban'],
        ['label' => 'Sales Amount', 'value' => '$' . number_format($totalSales, 2), 'icon' => 'fa-dollar-sign'],
        ['label' => 'Avg Order', 'value' => '$' . number_format($avgOrderValue, 2), 'icon' => 'fa-chart-line'],
    ];
@endphp

<div class="admin-report-page" id="admin-report-page">

    <div class="ar-page-intro">
        <div>
            <h2>Reports Overview</h2>
            <p>View sales totals, order status, client records, agent activity, and filtered report data from one clean dashboard.</p>
        </div>

        <div class="ar-date-pill">
            <i class="fas fa-calendar-alt"></i>
            {{ now()->format('M d, Y') }}
        </div>
    </div>

    <div class="ar-kpi-grid">
        @foreach($reportCards as $card)
            <div class="ar-kpi-card">
                <div class="ar-kpi-icon">
                    <i class="fas {{ $card['icon'] }}"></i>
                </div>

                <div>
                    <div class="ar-kpi-label">{{ $card['label'] }}</div>
                    <div class="ar-kpi-value">{{ $card['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="ar-panel">
        <div class="ar-panel-head">
            <h3 class="ar-panel-title">
                <i class="fas fa-filter"></i>
                Report Filters
            </h3>

            <div class="ar-panel-meta">
                {{ $activeFilters }} active {{ \Illuminate\Support\Str::plural('filter', $activeFilters) }}
            </div>
        </div>

        <div class="ar-filter-body">
            <form method="GET" action="{{ route('crm.reports.index') }}" id="reports-filter-form">
                <div class="ar-filter-grid">
                    <div class="ar-field">
                        <label>Date Range</label>
                        <input type="hidden" name="start_date" value="{{ request('start_date') }}"><input class="ar-date-range" type="text" value="{{ request('start_date') && request('end_date') ? request('start_date').' - '.request('end_date') : '' }}" placeholder="Date range">
                    </div>

                    <div class="ar-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Statuses</option>
                            @foreach(['pending' => 'Pending', 'delivered' => 'Delivered', 'rejected' => 'Rejected', 'paid' => 'Paid', 'unpaid' => 'Unpaid', 'ready_to_ship' => 'Ready To Ship', 'in_transit' => 'In Transit'] as $value => $label)
                                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ar-field">
                        <label>Agent</label>
                        <select name="agent">
                            <option value="">All Agents</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent }}" {{ request('agent') === $agent ? 'selected' : '' }}>
                                    {{ $agent }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ar-field">
                        <label>City</label>
                        <input type="text" name="city" value="{{ request('city') }}" placeholder="Billing or shipping city">
                    </div>

                    <div class="ar-field">
                        <label>Zip Code</label>
                        <input type="text" name="zip" value="{{ request('zip') }}" placeholder="ZIP / postal code">
                    </div>

                    <div class="ar-field ar-wide">
                        <label>Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Client, email, product, phone">
                    </div>

                    <div class="ar-actions">
                        <button class="ar-btn ar-btn-primary" type="submit">
                            <i class="fas fa-filter"></i>
                            Apply
                        </button>

                        <div class="ar-export-menu"><button class="ar-btn ar-btn-primary" type="button" onclick="document.getElementById('reportExportOptions').classList.toggle('show')"><i class="fas fa-download"></i> Export <i class="fas fa-chevron-down"></i></button><div class="ar-export-options" id="reportExportOptions"><a id="reports-export-link" href="{{ route('crm.reports.export', request()->query()) }}" data-no-ajax-nav><i class="fas fa-file-excel"></i> Excel</a><a id="reports-pdf-link" href="{{ route('crm.reports.export_pdf', request()->query()) }}" data-no-ajax-nav><i class="fas fa-file-pdf"></i> PDF</a></div></div>

                        <a class="ar-btn ar-btn-light" href="{{ route('crm.reports.index') }}">
                            <i class="fas fa-times"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>



    <div class="ar-panel">
        <div class="ar-panel-head">
            <h3 class="ar-panel-title">
                <i class="fas fa-table"></i>
                Master Report
            </h3>

            <div class="ar-panel-meta">{{ number_format($records->total()) }} records</div>
        </div>

        <div class="ar-table-wrap">
            <table class="ar-table ar-table-master">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Product</th>
                        <th>Status</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Total</th>
                        <th>Agent</th>
                        <th>City / Zip Source</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($records as $record)
                        @php
                            $salesOrder = $record->salesOrder;

                            $statusLabel = $record->status === 'Rejected'
                                ? 'Rejected'
                                : ($salesOrder->shipping_stage ?? $salesOrder->status ?? $record->status);

                            $statusClass = $record->status === 'Rejected'
                                ? 'bad'
                                : (in_array($statusLabel, ['delivered', 'payment_posted', 'order_completed']) ? 'ok' : 'warn');

                            $lineTotal = (float)($record->order_price ?? 0) * (float)($record->order_quantity ?? 0);
                        @endphp

                        <tr>
                            <td data-label="#">
                                <span class="ar-order-id">#{{ str_pad($record->id, 4, '0', STR_PAD_LEFT) }}</span>
                            </td>

                            <td data-label="Client">
                                <div class="ar-client-name">{{ $record->client_name ?: 'Unknown' }}</div>
                                <div class="ar-muted">{{ $record->client_email }}</div>
                            </td>

                            <td data-label="Product">{{ $record->product_name ?: 'General' }}</td>

                            <td data-label="Status">
                                <span class="ar-badge {{ $statusClass }}">
                                    {{ ucwords(str_replace('_', ' ', $statusLabel ?: 'Pending')) }}
                                </span>
                            </td>

                            <td data-label="Qty">{{ number_format($record->order_quantity ?? 0) }}</td>

                            <td data-label="Unit">${{ number_format($record->order_price ?? 0, 2) }}</td>

                            <td data-label="Total" class="ar-amount">${{ number_format($lineTotal, 2) }}</td>

                            <td data-label="Agent">{{ $record->order_marked_by ?: '-' }}</td>

                            <td data-label="City / Zip Source">
                                <span class="ar-muted">
                                    {{ \Illuminate\Support\Str::limit($record->shipping_address ?: $record->billing_address ?: 'No address', 60) }}
                                </span>
                            </td>

                            <td data-label="Date">
                                {{ $record->order_marked_at ? \Carbon\Carbon::parse($record->order_marked_at)->format('M d, Y') : $record->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="ar-empty-row">No records found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ar-pagination">
            {{ $records->appends(request()->all())->links() }}
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    (function() {
        if (window.__crmReportsInitBound) {
            return;
        }
        window.__crmReportsInitBound = true;

        document.addEventListener('DOMContentLoaded', initReportsPage);
        document.addEventListener('crm:page-loaded', initReportsPage);
    })();

    function initReportsPage() {
        let typingTimer;
        const doneTypingInterval = 500;
        
        function fetchReports() {
            const form = document.getElementById('reports-filter-form');
            if(!form) return;
            const url = form.action + '?' + new URLSearchParams(new FormData(form)).toString();
            
            const container = document.getElementById('admin-report-page');
            if(container) {
                container.style.opacity = '0.5';
                // Remove pointerEvents = 'none' so that if user clicks away, it works
            }

            // Save currently focused element and cursor position
            const activeElement = document.activeElement;
            const focusedName = activeElement ? activeElement.name : null;
            let cursorStart = null;
            let cursorEnd = null;
            
            if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
                try {
                    cursorStart = activeElement.selectionStart;
                    cursorEnd = activeElement.selectionEnd;
                } catch(e) {} // Some input types don't support selection properties
            }

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('admin-report-page');
                if(newContent && container) {
                    container.innerHTML = newContent.innerHTML;
                    container.style.opacity = '1';
                    container.style.pointerEvents = 'auto';
                    bindListeners();

                    // Restore focus and cursor position
                    if (focusedName) {
                        const newInput = document.querySelector(`[name="${focusedName}"]`);
                        if (newInput) {
                            newInput.focus();
                            if (cursorStart !== null && cursorEnd !== null) {
                                try {
                                    newInput.setSelectionRange(cursorStart, cursorEnd);
                                } catch(e) {}
                            }
                        }
                    }
                }
            })
            .catch(() => {
                if(container) {
                    container.style.opacity = '1';
                    container.style.pointerEvents = 'auto';
                }
            });
        }

        function bindListeners() {
            const form = document.getElementById('reports-filter-form');
            if(!form) return;
            updateExportLink();
            if (form.dataset.reportsBound === '1') return;
            form.dataset.reportsBound = '1';

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                fetchReports();
            });

            form.querySelectorAll('input, select').forEach(input => {
                // Remove pointer-events none from container on keyup/change to avoid blocking clicks
                if(input.name === 'search') {
                    input.addEventListener('keyup', (e) => {
                        // Ignore non-character keys (shift, ctrl, arrow keys) to avoid unnecessary requests
                        if(e.key && (e.key.includes('Arrow') || e.key === 'Shift' || e.key === 'Control' || e.key === 'Alt')) return;
                        clearTimeout(typingTimer);
                        updateExportLink();
                        typingTimer = setTimeout(fetchReports, doneTypingInterval);
                    });
                } else {
                    input.addEventListener('change', function() {
                        updateExportLink();
                        fetchReports();
                    });
                }
            });
            
            const container = document.getElementById('admin-report-page');
            if(container) {
                container.querySelectorAll('.ar-pagination a').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = this.href;
                        container.style.opacity = '0.5';
                        container.style.pointerEvents = 'none';
                        
                        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newContent = doc.getElementById('admin-report-page');
                            if(newContent) {
                                container.innerHTML = newContent.innerHTML;
                                container.style.opacity = '1';
                                container.style.pointerEvents = 'auto';
                                bindListeners();
                            }
                        });
                    });
                });
            }
        }

        function updateExportLink() {
            const form = document.getElementById('reports-filter-form');
            const exportLink = document.getElementById('reports-export-link');
            const pdfLink = document.getElementById('reports-pdf-link');
            if (!form) return;

            const params = new URLSearchParams(new FormData(form));
            const query = params.toString() ? '?' + params.toString() : '';
            if (exportLink) {
                exportLink.href = "{{ route('crm.reports.export') }}" + query;
            }
            if (pdfLink) {
                pdfLink.href = "{{ route('crm.reports.export_pdf') }}" + query;
            }
        }

        if (window.jQuery && jQuery.fn.daterangepicker) { jQuery('.ar-date-range').daterangepicker({autoUpdateInput:false,locale:{format:'DD/MM/YYYY',cancelLabel:'Clear'}}).on('apply.daterangepicker',function(e,p){this.value=p.startDate.format('DD/MM/YYYY')+' - '+p.endDate.format('DD/MM/YYYY');document.querySelector('[name="start_date"]').value=p.startDate.format('YYYY-MM-DD');document.querySelector('[name="end_date"]').value=p.endDate.format('YYYY-MM-DD');updateExportLink()}).on('cancel.daterangepicker',function(){this.value='';document.querySelector('[name="start_date"]').value='';document.querySelector('[name="end_date"]').value='';updateExportLink()}); }
        bindListeners();
    }
</script>
@endsection
