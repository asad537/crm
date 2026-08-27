@php
    $__reportWorkspace = $activeCrmWorkspace ?? null;
    $__reportIsAlMassa = $__reportWorkspace && $__reportWorkspace->slug === 'mybox-packaging-app';
    $__reportBrand = $__reportIsAlMassa ? 'Al Massa Packaging' : 'My Box Printing';
    $__reportLogo = $__reportIsAlMassa ? 'al-massa-invoice-email-logo.png' : 'my-box-printing-logo.svg';
    $__reportPrimary = $__reportIsAlMassa ? '#f45a24' : '#6c5ce7';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Report — {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('M d, Y').' – '.\Carbon\Carbon::parse($endDate)->format('M d, Y') : 'All Time' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f8;
            color: #1e293b;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ── Toolbar ── */
        .toolbar {
            width: 100%; max-width: 900px;
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.25rem;
        }
        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 0.55rem 1.1rem; background: white; color: #64748b;
            border: 1px solid #e2e8f0; border-radius: 9px;
            font-weight: 600; font-size: 0.82rem; text-decoration: none;
            transition: all 0.2s;
        }
        .btn-back:hover { background: #f8fafc; color: #334155; }
        .btn-print {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 0.55rem 1.25rem;
            background: #1e293b; color: white;
            border: none; border-radius: 9px;
            font-weight: 700; font-size: 0.82rem; cursor: pointer;
            transition: all 0.2s;
        }
        .btn-print:hover { background: #334155; }

        /* ── Document ── */
        .doc {
            background: white;
            width: 100%; max-width: 900px;
            border-radius: 16px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        /* ── Top accent ── */
        .top-bar {
            height: 3px;
            background: linear-gradient(to right, {{ $__reportPrimary }}, {{ $__reportIsAlMassa ? '#ff8a4c' : '#a78bfa' }}, {{ $__reportIsAlMassa ? '#171717' : '#10b981' }});
        }

        /* ── Header ── */
        .doc-header {
            padding: 1.75rem 2.5rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left {}
        .header-logo img {
            height: 60px; width: auto; object-fit: contain; display: block;
        }
        .header-tagline {
            font-size: 0.72rem; color: #94a3b8; margin-top: 6px; font-weight: 500;
            letter-spacing: 0.02em;
        }

        .header-right { text-align: right; }
        .report-label {
            font-size: 0.65rem; font-weight: 800; color: {{ $__reportPrimary }};
            text-transform: uppercase; letter-spacing: 0.12em;
            margin-bottom: 4px;
        }
        .report-title {
            font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1.2;
        }
        .report-period {
            font-size: 0.8rem; color: #475569; font-weight: 600; margin-top: 5px;
        }
        .report-generated {
            font-size: 0.72rem; color: #94a3b8; margin-top: 3px;
        }

        /* ── Summary band ── */
        .summary-band {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            background: #fafbff;
            border-bottom: 1px solid #f1f5f9;
        }
        .summary-cell {
            padding: 1.1rem 2rem;
            border-right: 1px solid #f1f5f9;
        }
        .summary-cell:last-child { border-right: none; }
        .sc-label {
            font-size: 0.62rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 5px;
        }
        .sc-value {
            font-size: 1.35rem; font-weight: 800; color: #0f172a; line-height: 1;
        }
        .sc-value.v-purple { color: {{ $__reportPrimary }}; }
        .sc-value.v-green  { color: #059669; }
        .sc-sub {
            font-size: 0.7rem; color: #94a3b8; margin-top: 3px;
        }

        /* ── Table ── */
        .table-wrap { padding: 0 2.5rem 1.5rem; }
        .section-label {
            font-size: 0.62rem; font-weight: 800; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.1em;
            padding: 1.25rem 0 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 0;
        }

        table.rt { width: 100%; border-collapse: collapse; }
        table.rt thead tr { background: transparent; }
        table.rt th {
            padding: 0.7rem 0.75rem;
            font-size: 0.62rem; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.08em;
            border-bottom: 1.5px solid #f1f5f9;
            text-align: left; white-space: nowrap;
        }
        table.rt th.right { text-align: right; }
        table.rt td {
            padding: 0.8rem 0.75rem;
            font-size: 0.82rem; color: #334155;
            border-bottom: 1px solid #f8f9ff;
            vertical-align: middle;
        }
        table.rt tr:last-child td { border-bottom: none; }
        table.rt tbody tr:hover td { background: #fafbff; }

        .sn { font-size: 0.72rem; color: #94a3b8; font-weight: 600; }
        .cn { font-weight: 700; color: #0f172a; font-size: 0.82rem; }
        .ce { font-size: 0.7rem; color: #94a3b8; }
        .ptag {
            display: inline-block;
            background: #f0f9ff; color: #0369a1;
            font-size: 0.7rem; font-weight: 600;
            padding: 2px 8px; border-radius: 5px;
            max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .qchip {
            display: inline-block;
            background: {{ $__reportIsAlMassa ? '#fff0e8' : '#ede9fe' }}; color: {{ $__reportPrimary }};
            font-size: 0.72rem; font-weight: 700;
            padding: 2px 8px; border-radius: 5px;
        }
        .price-col { font-weight: 600; color: #334155; }
        .total-col { font-weight: 800; color: #0f172a; text-align: right; }
        .agent-col {
            font-size: 0.75rem; color: #64748b;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .ag-dot {
            width: 6px; height: 6px;
            background: #10b981; border-radius: 50%; display: inline-block; flex-shrink: 0;
        }
        .date-col { font-size: 0.72rem; color: #94a3b8; white-space: nowrap; }

        /* Grand total */
        tr.grand td {
            padding: 0.9rem 0.75rem;
            background: #fafbff;
            border-top: 2px solid {{ $__reportIsAlMassa ? '#ffe0d1' : '#ede9fe' }};
            font-size: 0.82rem; font-weight: 700; color: #475569;
        }
        td.grand-val {
            font-size: 1rem; font-weight: 800; color: #0f172a; text-align: right;
        }

        /* ── Footer ── */
        .doc-footer {
            border-top: 1px solid #f1f5f9;
            padding: 1rem 2.5rem;
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.72rem; color: #94a3b8;
        }
        .doc-footer strong { color: #64748b; }

        /* ── Empty ── */
        .empty { padding: 3rem; text-align: center; color: #94a3b8; font-size: 0.9rem; }

        @media print {
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            html, body {
                background: white !important;
                padding: 0 !important; margin: 0 !important;
                width: 100% !important;
            }
            .toolbar { display: none !important; }
            .doc {
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .doc-header { padding: 1rem 1.5rem 0.9rem; }
            .header-logo img { height: 48px; }
            .summary-band { }
            .summary-cell { padding: 0.75rem 1.2rem; }
            .sc-value { font-size: 1.1rem; }
            .table-wrap { padding: 0 1.5rem 1rem; }
            table.rt th,
            table.rt td { padding: 0.55rem 0.5rem; font-size: 0.75rem; }
            .doc-footer { padding: 0.75rem 1.5rem; }
            @page {
                size: A4 landscape;
                margin: 1cm 1.2cm;
            }
        }
    </style>
</head>
<body>

{{-- Toolbar --}}
<div class="toolbar">
    <a href="{{ route('crm.orders.index') }}{{ $startDate ? '?start_date='.$startDate.'&end_date='.$endDate : '' }}" class="btn-back">
        &#8592; Back to Orders
    </a>
    <button onclick="window.print()" class="btn-print">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
        Print / Save PDF
    </button>
</div>

{{-- Document --}}
<div class="doc">
    <div class="top-bar"></div>

    {{-- Header --}}
    <div class="doc-header">
        <div class="header-left">
            <div class="header-logo">
                <img src="{{ asset($__reportLogo) }}" alt="{{ $__reportBrand }}" style="{{ $__reportIsAlMassa ? 'height:78px' : '' }}">
            </div>
            <div class="header-tagline">{{ $__reportIsAlMassa ? 'Custom Packaging Solutions' : 'Custom Packaging Solutions · myboxprinting.com' }}</div>
        </div>
        <div class="header-right">
            <div class="report-label">Sales Report</div>
            <div class="report-title">Orders Summary</div>
            <div class="report-period">
                @if($startDate && $endDate)
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} &mdash; {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                @else
                    All Time
                @endif
            </div>
            <div class="report-generated">Generated {{ now()->format('F d, Y \a\t h:i A') }}</div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="summary-band">
        <div class="summary-cell">
            <div class="sc-label">Total Orders</div>
            <div class="sc-value v-purple">{{ $orders->count() }}</div>
            <div class="sc-sub">Confirmed deals</div>
        </div>
        <div class="summary-cell">
            <div class="sc-label">Total Sale</div>
            <div class="sc-value v-green">${{ number_format($totalRevenue, 2) }}</div>
            <div class="sc-sub">Combined order value</div>
        </div>
        <div class="summary-cell">
            <div class="sc-label">Avg. Order Value</div>
            <div class="sc-value">${{ $orders->count() > 0 ? number_format($totalRevenue / $orders->count(), 2) : '0.00' }}</div>
            <div class="sc-sub">Per confirmed order</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <div class="section-label">Order Details</div>

        @if($orders->count() > 0)
        <table class="rt">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Agent</th>
                    <th>Date</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $i => $order)
                <tr>
                    <td><span class="sn">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span></td>
                    <td>
                        <div class="cn">{{ $order->client_name }}</div>
                        <div class="ce">{{ $order->client_email }}</div>
                    </td>
                    <td><span class="ptag">{{ $order->product_name ?: 'General' }}</span></td>
                    <td><span class="qchip">{{ number_format($order->order_quantity ?? 0) }}</span></td>
                    <td class="price-col">${{ number_format($order->order_price ?? 0, 2) }}</td>
                    <td>
                        <span class="agent-col">
                            <span class="ag-dot"></span>
                            {{ $order->order_marked_by ?? '—' }}
                        </span>
                    </td>
                    <td class="date-col">
                        {{ $order->order_marked_at
                            ? \Carbon\Carbon::parse($order->order_marked_at)->format('M d, Y')
                            : $order->created_at->format('M d, Y') }}
                    </td>
                    <td class="total-col">
                        ${{ number_format(($order->order_price ?? 0) * ($order->order_quantity ?? 0), 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="grand">
                    <td colspan="7">
                        Grand Total &mdash; {{ $orders->count() }} {{ Str::plural('order', $orders->count()) }}
                    </td>
                    <td class="grand-val">${{ number_format($totalRevenue, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="empty">No orders found for the selected period.</div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="doc-footer">
        <span>{{ $__reportBrand }} &mdash; Confidential Sales Report</span>
        <span>Prepared by <strong>CRM System</strong> &middot; {{ now()->format('Y') }}</span>
    </div>
</div>

</body>
</html>
