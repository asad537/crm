@php
    $__invoiceWorkspace = $order->workspace ?: ($activeCrmWorkspace ?? null);
    $__invoiceIsAlMassa = $__invoiceWorkspace && $__invoiceWorkspace->slug === 'mybox-packaging-app';
    $__invoiceBrand = $__invoiceIsAlMassa ? 'Al Massa Packaging' : 'My Box Printing';
    $__invoiceLogo = $__invoiceIsAlMassa ? 'al-massa-invoice-email-logo.png' : 'my-box-printing-logo.svg';
    $__invoicePrimary = $__invoiceIsAlMassa ? '#0b2a62' : '#6c5ce7';
    $__invoicePrimarySoft = $__invoiceIsAlMassa ? '#e8f1f8' : '#ede9fe';
    $__invoiceCurrency = $order->invoice_currency ?: ($__invoiceIsAlMassa ? 'AED' : 'USD');
    $__currencySymbol = $__invoiceCurrency === 'USD' ? '$' : ($__invoiceCurrency === 'GBP' ? '£' : ($__invoiceCurrency === 'EUR' ? '€' : ''));
    $__orderItems = method_exists($order, 'orderItems') ? $order->orderItems : collect();
    $__hasItems = $__orderItems && $__orderItems->count() > 0;
    $__subtotal = $__hasItems
        ? (float) $__orderItems->sum('line_total')
        : ($order->order_price ?? 0) * ($order->order_quantity ?? 0);
    $__invoiceNo = $order->order_invoice_number ?: ('#' . str_pad($order->id, 5, '0', STR_PAD_LEFT));
    $__vatPercentage = (float) ($order->vat_percentage ?? 0);
    $__vatAmount = $__subtotal * $__vatPercentage / 100;
    $__grandTotal = $__subtotal + $__vatAmount;
    $__money = function ($amount) use ($__currencySymbol, $__invoiceCurrency) {
        return $__currencySymbol . number_format($amount, 2) . ($__currencySymbol ? '' : ' ' . $__invoiceCurrency);
    };
    // Unit price may carry fine precision (e.g. 0.019). Show up to 4 decimals, min 2, no extra trailing zeros.
    $__unitMoney = function ($amount) use ($__currencySymbol, $__invoiceCurrency) {
        $s = number_format((float) $amount, 4, '.', ',');
        if (strpos($s, '.') !== false) {
            $s = rtrim($s, '0');
            $decimals = strlen(substr($s, strrpos($s, '.') + 1));
            if ($decimals < 2) {
                $s = number_format((float) $amount, 2, '.', ',');
            }
        }
        return $__currencySymbol . $s . ($__currencySymbol ? '' : ' ' . $__invoiceCurrency);
    };
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $__invoiceNo }} — {{ $order->client_name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

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
            width: 100%;
            max-width: 780px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.55rem 1.1rem;
            background: white;
            color: #64748b;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            font-weight: 600;
            font-size: 0.82rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: #f8fafc;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.55rem 1.25rem;
            background: #1e293b;
            color: white;
            border: none;
            border-radius: 9px;
            font-weight: 700;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background: #334155;
        }

        /* ── Card ── */
        .inv-card {
            background: white;
            width: 100%;
            max-width: 780px;
            border-radius: 16px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .top-bar {
            height: 3px;
            background: linear-gradient(to right, {{ $__invoicePrimary }}, {{ $__invoiceIsAlMassa ? '#d89b00' : '#a78bfa' }}, {{ $__invoiceIsAlMassa ? '#0b2a62' : '#10b981' }});
        }

        /* ── Header ── */
        .inv-header {
            padding: 1.75rem 2.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .inv-logo img {
            height: 64px;
            width: auto;
            object-fit: contain;
        }

        .company-header {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .company-name {
            color: {{ $__invoicePrimary }};
            font-size: 0.92rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .company-contact {
            margin-top: 4px;
            color: #475569;
            font-size: 0.68rem;
            line-height: 1.55;
        }

        .inv-logo-sub {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 6px;
            font-weight: 500;
            margin-left: 63px;

        }

        .inv-title-block {
            text-align: right;
        }

        .inv-title-label {
            font-size: 0.62rem;
            font-weight: 800;
            color: {{ $__invoicePrimary }};
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 4px;
        }

        .inv-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .inv-number {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 5px;
        }

        .inv-date {
            font-size: 0.82rem;
            font-weight: 700;
            color: #475569;
            margin-top: 3px;
        }

        /* ── Status strip ── */
        .status-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 2.25rem;
            background: #f0fdf4;
            border-bottom: 1px solid #dcfce7;
            font-size: 0.8rem;
        }

        .status-left {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #166534;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-right {
            font-size: 0.75rem;
            color: #64748b;
        }

        .status-right strong {
            color: #166534;
        }

        /* ── Body ── */
        .inv-body {
            padding: 1.75rem 2.25rem;
        }

        /* ── Bill grid ── */
        .bill-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .bill-section-label {
            font-size: 0.6rem;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 0.6rem;
        }

        /* Bill To rows */
        .bill-row {
            display: flex;
            gap: 1rem;
            font-size: 0.8rem;
            margin-bottom: 6px;
            align-items: baseline;
        }

        .blbl {
            color: #334155;
            font-weight: 500;
            min-width: 60px;
            flex-shrink: 0;
        }

        .bval {
            color: #0f172a;
            font-weight: 600;
        }

        /* Right column — Invoice Details */
        .inv-details-col {
            text-align: right;
        }

        .inv-detail-row {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            font-size: 0.8rem;
            margin-bottom: 6px;
            align-items: baseline;
        }

        .inv-detail-row .lbl {
            color: #334155;
            font-weight: 500;
        }

        .inv-detail-row .val {
            color: #0f172a;
            font-weight: 700;
            min-width: 110px;
            text-align: right;
        }

        .val-confirmed {
            color: #0f172a !important;
            font-weight: 700 !important;
        }

        /* ── Table ── */
        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .inv-table thead tr {
            background: #fafbff;
        }

        .inv-table th {
            padding: 0.7rem 0.9rem;
            font-size: 0.62rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1.5px solid #f1f5f9;
            text-align: left;
        }

        .inv-table th:last-child {
            text-align: right;
        }

        .inv-table td {
            padding: 1rem 0.9rem;
            font-size: 0.85rem;
            color: #334155;
            border-bottom: 1px solid #f8f9ff;
            vertical-align: top;
        }

        .inv-table tr:last-child td {
            border-bottom: none;
        }

        .prod-name {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .prod-specs {
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.5;
        }

        .qty-val {
            font-weight: 700;
            color: {{ $__invoicePrimary }};
        }

        .price-val {
            font-weight: 600;
        }

        .total-val {
            font-weight: 800;
            color: #0f172a;
            text-align: right;
            font-size: 0.95rem;
        }

        /* ── Totals ── */
        .totals-block {
            margin-left: auto;
            width: 260px;
            border-top: 1.5px solid #f1f5f9;
            padding-top: 1rem;
        }

        .t-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #64748b;
            padding: 0.4rem 0;
        }

        .t-row.grand {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            border-top: 1.5px solid {{ $__invoiceIsAlMassa ? '#ffe0d1' : '#ede9fe' }};
            padding-top: 0.75rem;
            margin-top: 0.5rem;
        }

        .t-row.grand span:last-child {
            color: {{ $__invoicePrimary }};
        }

        /* ── Notes ── */
        .notes-block {
            margin-top: 1.5rem;
            padding: 1rem 1.1rem;
            background: #fafbff;
            border-radius: 10px;
            border: 1px solid #f0f0f8;
        }

        .notes-label {
            font-size: 0.6rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 5px;
        }

        .notes-text {
            font-size: 0.8rem;
            color: #475569;
            line-height: 1.6;
        }

        /* ── Footer ── */
        .inv-footer {
            border-top: 1px solid #f1f5f9;
            padding: 1rem 2.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.72rem;
            color: #94a3b8;
        }

        .inv-footer strong {
            color: #64748b;
        }

        .al-massa-signoff {
            padding: 2.5rem 2.25rem 1.25rem;
        }

        .signatory {
            text-align: right;
            color: #171717;
            margin-bottom: 2.25rem;
        }

        .signatory-name {
            font-size: 1.05rem;
            font-weight: 800;
        }

        .signatory-title {
            margin-top: 3px;
            font-size: 0.82rem;
        }

        .thank-you-line {
            text-align: center;
            color: #171717;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .footer-rule {
            position: relative;
            height: 4px;
            margin-top: 1rem;
            background: #a7a7a7;
            border-radius: 99px;
        }

        .footer-rule::before,
        .footer-rule::after {
            content: '';
            position: absolute;
            top: 0;
            width: 78px;
            height: 4px;
            border-radius: 99px;
            background: {{ $__invoicePrimary }};
        }

        .footer-rule::before { left: 0; }
        .footer-rule::after { right: 0; }

        @media print {
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            html,
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .toolbar {
                display: none !important;
            }

            .inv-card {
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .inv-header {
                padding: 1rem 1.5rem 0.9rem;
            }

            .inv-logo img {
                height: 52px;
            }

            .inv-body {
                padding: 1.25rem 1.5rem;
            }

            .inv-footer {
                padding: 0.75rem 1.5rem;
            }

            @page {
                size: A4 portrait;
                margin: 1cm 1.2cm;
            }
        }
    </style>
</head>

<body>

    {{-- Toolbar --}}
    <div class="toolbar">
        <a href="{{ route('crm.orders.index') }}" class="btn-back">&#8592; Back to Orders</a>
        <div style="display: flex; gap: 0.5rem;">
            @if(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || Auth::guard('crm')->user()->isAccounts() || (Auth::guard('crm')->user()->isSales() && ($order->assigned_to == Auth::guard('crm')->user()->id || $order->order_marked_by === Auth::guard('crm')->user()->name)))
            <a href="{{ route('crm.orders.invoice.edit', $order->id) }}" class="btn-back" style="color:{{ $__invoicePrimary }}; border-color:{{ $__invoicePrimarySoft }};">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0; vertical-align:middle; margin-right:4px;"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Invoice
            </a>
            <form action="{{ route('crm.orders.invoice.send', $order->id) }}" method="POST" style="margin:0;" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Sending...';">
                {{ csrf_field() }}
                <button type="submit" class="btn-print" style="background:linear-gradient(135deg,{{ $__invoicePrimary }},{{ $__invoiceIsAlMassa ? '#dc4313' : '#8b5cf6' }});box-shadow:0 2px 8px {{ $__invoiceIsAlMassa ? 'rgba(244,90,36,.3)' : 'rgba(108,92,231,.3)' }};">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0; vertical-align:middle; margin-right:4px;"><path d="M3 19v-8.93a2 2 0 01.89-1.664l8-5.333a2 2 0 012.22 0l8 5.333A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/></svg>
                    Send Invoice
                </button>
            </form>
            @endif
            <button onclick="window.print()" class="btn-back">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0; vertical-align:middle; margin-right:4px;"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
                Print
            </button>
            <button type="button" id="dlPdfBtn" onclick="downloadInvoicePdf(this)" class="btn-print">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0; vertical-align:middle; margin-right:4px;"><path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
                Download PDF
            </button>
        </div>
    </div>

    {{-- Invoice Card --}}
    <div class="inv-card">
        <div class="top-bar"></div>

        {{-- Header --}}
        <div class="inv-header">
            <div class="company-header">
                <div class="inv-logo">
                    <img src="{{ asset($__invoiceLogo) }}" alt="{{ $__invoiceBrand }}" style="{{ $__invoiceIsAlMassa ? 'height:78px' : '' }}">
                    @if(!$__invoiceIsAlMassa)<div class="inv-logo-sub">Custom Packaging</div>@endif
                </div>
                @if($__invoiceIsAlMassa)
                <div>
                    <div class="company-name">AL MASSA AL MALAKIYA BOXES AND<br>PACKING IND. LLC</div>
                    <div class="company-contact">
                        All Cosmetics &amp; Perfumes Hard, Soft Boxes and Paper Bags<br>
                        Al Diyar Building 33, Industrial Area 12, Sharjah, UAE<br>
                        +971 6 579 6994 &nbsp; | &nbsp; info@almassapackaging.com
                    </div>
                </div>
                @endif
            </div>
            <div class="inv-title-block">
                <div class="inv-title-label">ORDER</div>
                <div class="inv-title">Invoice</div>
                <div class="inv-number">{{ $__invoiceNo }}</div>
                <div class="inv-date">
                    {{ $order->order_marked_at
    ? \Carbon\Carbon::parse($order->order_marked_at)->format('F d, Y')
    : $order->created_at->format('F d, Y') }}
                </div>
            </div>
        </div>

        {{-- Body --}}
        <div class="inv-body">

            {{-- Bill / Invoice details --}}
            <div class="bill-grid">
                <div>
                    <div class="bill-section-label">Customer Info</div>
                    <div class="bill-row"><span class="blbl">Name</span><span
                            class="bval"><strong>{{ $order->client_name }}</strong></span></div>
                    <div class="bill-row"><span class="blbl">Email</span><span
                            class="bval">{{ $order->client_email }}</span></div>
                    @if($order->client_phone)
                        <div class="bill-row"><span class="blbl">Phone</span><span
                                class="bval">{{ $order->client_phone }}</span></div>
                    @endif
                    @if($order->customer_trn)
                        <div class="bill-row"><span class="blbl">TR No</span><span class="bval">{{ $order->customer_trn }}</span></div>
                    @endif
                </div>
                <div class="inv-details-col">
                    <div class="bill-section-label" style="text-align:right;">Invoice Details</div>
                    <div class="inv-detail-row">
                        <span class="lbl">Invoice No</span>
                        <span class="val">{{ $__invoiceNo }}</span>
                    </div>
                    <div class="inv-detail-row">
                        <span class="lbl">Order Date</span>
                        <span class="val">
                            {{ $order->order_marked_at
    ? \Carbon\Carbon::parse($order->order_marked_at)->format('M d, Y')
    : $order->created_at->format('M d, Y') }}
                        </span>
                    </div>
                    <div class="inv-detail-row">
                        <span class="lbl">TR No</span>
                        <span class="val">{{ $order->company_trn ?: '—' }}</span>
                    </div>
                    <div class="inv-detail-row">
                        <span class="lbl">TL No</span>
                        <span class="val">{{ $order->trade_license_number ?: '—' }}</span>
                    </div>
                    <div class="inv-detail-row">
                        <span class="lbl">Currency</span>
                        <span class="val">{{ $__invoiceCurrency }}</span>
                    </div>
                    <div class="inv-detail-row">
                        <span class="lbl">Payment Status</span>
                        <span class="val" style="font-weight: 800; color: {{ $order->payment_status === 'Paid' ? '#10b981' : '#f43f5e' }};">
                            {{ $order->payment_status ?: 'Unpaid' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Address Section --}}
            <div class="bill-grid" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <div class="bill-section-label">Billing Address</div>
                    <div style="font-size: 0.8rem; line-height: 1.5; color: #475569; white-space: pre-line;">{!! nl2br(e($order->billing_address ?: 'No billing address provided')) !!}</div>
                </div>
                <div>
                    <div class="bill-section-label" style="text-align: right;">Shipping Address</div>
                    <div style="font-size: 0.8rem; line-height: 1.5; color: #475569; white-space: pre-line; text-align: right;">{!! nl2br(e($order->shipping_address ?: 'No shipping address provided')) !!}</div>
                </div>
            </div>

            {{-- Product Table --}}
            <table class="inv-table">
                <thead>
                    <tr>
                        <th style="width:50%">Description</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if($__hasItems)
                        @foreach($__orderItems as $__i => $__item)
                            <tr>
                                <td>
                                    <div class="prod-name">{{ $__item->product_name ?: 'Product' }}</div>
                                    @if($__i === 0)
                                        <div class="prod-specs">
                                            @if($order->length || $order->width || $order->height)
                                                Dimensions:
                                                {{ $order->length }}&nbsp;&times;&nbsp;{{ $order->width }}&nbsp;&times;&nbsp;{{ $order->height }}
                                                {{ $order->unit }}
                                            @endif
                                            @if($order->stock) &nbsp;|&nbsp; Material: {{ $order->stock }} @endif
                                            @if($order->color) &nbsp;|&nbsp; Color: {{ $order->color }} @endif
                                            @if($order->coating) &nbsp;|&nbsp; Coating: {{ $order->coating }} @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="qty-val">{{ number_format($__item->quantity) }}</td>
                                <td class="price-val">{{ $__unitMoney($__item->unit_price) }}</td>
                                <td class="total-val">{{ $__money($__item->line_total) }}</td>
                            </tr>
                        @endforeach
                    @else
                    <tr>
                        <td>
                            <div class="prod-name">{{ $order->product_name ?: 'Custom Packaging Order' }}</div>
                            <div class="prod-specs">
                                @if($order->length || $order->width || $order->height)
                                    Dimensions:
                                    {{ $order->length }}&nbsp;&times;&nbsp;{{ $order->width }}&nbsp;&times;&nbsp;{{ $order->height }}
                                    {{ $order->unit }}
                                @endif
                                @if($order->stock) &nbsp;|&nbsp; Material: {{ $order->stock }} @endif
                                @if($order->color) &nbsp;|&nbsp; Color: {{ $order->color }} @endif
                                @if($order->coating) &nbsp;|&nbsp; Coating: {{ $order->coating }} @endif
                            </div>
                        </td>
                        <td class="qty-val">{{ number_format($order->order_quantity ?? 0) }}</td>
                        <td class="price-val">{{ $__unitMoney($order->order_price ?? 0) }}</td>
                        <td class="total-val">
                            {{ $__money($__subtotal) }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>

            {{-- Totals --}}
            <div class="totals-block">
                <div class="t-row">
                    <span>Subtotal</span>
                    <span>{{ $__money($__subtotal) }}</span>
                </div>
                <div class="t-row">
                    <span>VAT {{ number_format($__vatPercentage, 2) }}%</span>
                    <span>{{ $__money($__vatAmount) }}</span>
                </div>
                <div class="t-row grand">
                    <span>Grand Total</span>
                    <span>{{ $__money($__grandTotal) }}</span>
                </div>
            </div>

            {{-- Notes --}}
            @if($order->order_notes || $order->message)
                <div class="notes-block">
                    <div class="notes-label">Notes</div>
                    <div class="notes-text">
                        {{ $order->order_notes ?: Str::limit($order->message, 250) }}
                    </div>
                </div>
            @endif

        </div>

        <div class="al-massa-signoff">
            <div class="signatory">
                <div class="signatory-name">AMIR BASHIR</div>
                <div class="signatory-title">Administrator</div>
            </div>
            <div class="thank-you-line">Thank you for business with us!</div>
            <div class="footer-rule"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.2/dist/html2pdf.bundle.min.js"></script>
    <script>
        function downloadInvoicePdf(btn) {
            var el = document.querySelector('.inv-card');
            if (!el) { window.print(); return; }
            var name = @json($__invoiceNo);
            name = String(name).replace(/[^A-Za-z0-9_-]+/g, '');
            if (typeof html2pdf === 'undefined') { window.print(); return; } // library blocked -> fall back to print
            var original = btn.innerHTML;
            btn.innerHTML = 'Generating…';
            btn.disabled = true;
            var opt = {
                margin: 0,
                filename: 'Invoice-' + name + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', scrollY: 0 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
            html2pdf().set(opt).from(el).save()
                .then(function () { btn.innerHTML = original; btn.disabled = false; })
                .catch(function () { btn.innerHTML = original; btn.disabled = false; window.print(); });
        }
    </script>
</body>

</html>
