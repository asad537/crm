@php
    $customer = $sale->customer;
    $brand = 'Al Massa Al Malakiya';
    $company = 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC';
    $address = 'Al Diyar Building 33, 4th Industrial Street, Industrial Area 12, Sharjah, United Arab Emirates';
    $money = fn ($value) => number_format((float) $value, 2, '.', ',');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }} Invoice {{ $sale->order_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: #172033; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { width: 210mm; max-width: calc(100% - 28px); margin: 18px auto 12px; display: flex; justify-content: space-between; }
        .toolbar a, .toolbar button { display: inline-block; padding: 10px 15px; border: 1px solid #d7deea; border-radius: 8px; background: white; color: #344256; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar button { background: #f4511e; border-color: #f4511e; color: white; }
        .invoice { width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 18mm 17mm; background: white; box-shadow: 0 6px 28px rgba(15,23,42,.12); }
        .top { display: flex; justify-content: space-between; gap: 28px; align-items: flex-start; }
        .company { max-width: 125mm; display: flex; align-items: center; gap: 14px; }
        .company img { width: 27mm; height: 27mm; object-fit: contain; }
        .company-copy { min-width: 0; }
        .company h1 { margin: 0 0 8px; color: #102b61; font-size: 20px; }
        .company p { margin: 3px 0; color: #596579; font-size: 11px; line-height: 1.5; }
        .invoice-title { color: #d78a00; font-size: 30px; font-weight: 900; letter-spacing: 2px; }
        .rule { height: 3px; margin: 18px 0 24px; background: #102b61; }
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }
        .label { margin-bottom: 8px; color: #78859a; font-size: 10px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
        .customer-name { margin: 0 0 7px; font-size: 17px; font-weight: 800; text-transform: uppercase; }
        .customer p { margin: 3px 0; color: #566276; font-size: 12px; line-height: 1.45; }
        .meta { display: grid; grid-template-columns: auto 1fr; gap: 8px 12px; align-content: start; font-size: 12px; }
        .meta strong { color: #344256; }
        table { width: 100%; margin-top: 28px; border-collapse: collapse; }
        th { padding: 11px 10px; background: #102b61; color: white; font-size: 11px; text-align: left; }
        td { padding: 12px 10px; border-bottom: 1px solid #e3e8f0; font-size: 12px; }
        th:last-child, td:last-child { text-align: right; }
        .totals { width: 82mm; margin: 22px 0 0 auto; }
        .total-line { display: flex; justify-content: space-between; gap: 16px; padding: 6px 0; color: #49566b; font-size: 12px; }
        .grand { margin-top: 7px; padding: 12px; background: #102b61; color: white; font-size: 15px; font-weight: 800; }
        .grand, .grand span { display: flex; justify-content: space-between; }
        .payment { margin-top: 20px; padding: 13px 15px; border: 1px solid #dce3ec; border-radius: 8px; display: flex; justify-content: space-between; gap: 15px; font-size: 12px; }
        .payment strong { color: #102b61; }
        .footnote { margin-top: 42px; padding-top: 12px; border-top: 1px solid #d8dee8; color: #7b8798; font-size: 10px; text-align: center; }
        @media (max-width: 720px) { .invoice { width: 100%; min-height: auto; padding: 24px; } .top { flex-direction: column; } .details { grid-template-columns: 1fr; } .totals { width: 100%; } }
        @media print { @page { size: A4 portrait; margin: 0; } body { background: white; } .toolbar { display: none; } .invoice { width: 210mm; min-height: 297mm; margin: 0; padding: 18mm 17mm; box-shadow: none; } }
    </style>
</head>
<body>
    <nav class="toolbar">
        <a href="{{ route('crm.orders.index') }}">&#8592; Back to Orders</a>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </nav>
    <main class="invoice">
        <header class="top">
            <div class="company">
                <img src="{{ asset('al-massa-invoice-email-logo.png') }}" alt="{{ $brand }}">
                <div class="company-copy">
                    <h1>{{ $company }}</h1>
                    <p>All Cosmetics &amp; Perfumes Hard, Soft Boxes and Paper Bags</p>
                    <p>{{ $address }}</p>
                    <p>+971 6 579 6994 &nbsp; | &nbsp; info@almassapackaging.com</p>
                </div>
            </div>
            <div class="invoice-title">INVOICE</div>
        </header>
        <div class="rule"></div>
        <section class="details">
            <div class="customer">
                <div class="label">Bill to</div>
                <h2 class="customer-name">{{ optional($customer)->company_name ?: optional($customer)->name ?: 'Customer' }}</h2>
                @if(optional($customer)->contact_person)<p>{{ $customer->contact_person }}</p>@endif
                @if(optional($customer)->email)<p>{{ $customer->email }}</p>@endif
                @if(optional($customer)->phone)<p>{{ $customer->phone }}</p>@endif
                @if(optional($customer)->billing_address)<p>{!! nl2br(e($customer->billing_address)) !!}</p>@endif
                @if(optional($customer)->tax_number)<p>TRN: {{ $customer->tax_number }}</p>@endif
            </div>
            <div class="meta">
                <strong>Invoice / Order #</strong><span>{{ $sale->order_number }}</span>
                <strong>Invoice date</strong><span>{{ optional($sale->order_date)->format('d M Y') ?: '—' }}</span>
                <strong>Due date</strong><span>{{ optional($sale->due_date)->format('d M Y') ?: '—' }}</span>
                <strong>Currency</strong><span>{{ $sale->currency ?: 'AED' }}</span>
                <strong>Status</strong><span>{{ $sale->order_status }} / {{ $sale->payment_status }}</span>
            </div>
        </section>
        <table>
            <thead><tr><th>Description</th><th>Qty</th><th>Unit price</th><th>Amount</th></tr></thead>
            <tbody>
                <tr>
                    <td><strong>{{ $sale->item_name ?: 'Sales item' }}</strong>@if($sale->description)<br><span style="color:#64748b">{{ $sale->description }}</span>@endif</td>
                    <td>{{ $sale->quantity === null ? '—' : $money($sale->quantity).' '.($sale->unit ?: 'Pieces') }}</td>
                    <td>{{ $sale->currency ?: 'AED' }} {{ $money($sale->unit_price) }}</td>
                    <td>{{ $sale->currency ?: 'AED' }} {{ $money($sale->subtotal) }}</td>
                </tr>
            </tbody>
        </table>
        <section class="totals">
            <div class="total-line"><span>Subtotal</span><strong>{{ $sale->currency ?: 'AED' }} {{ $money($sale->subtotal) }}</strong></div>
            @if((float) $sale->discount_amount > 0)<div class="total-line"><span>Discount</span><strong>- {{ $sale->currency ?: 'AED' }} {{ $money($sale->discount_amount) }}</strong></div>@endif
            @if((float) $sale->tax_amount > 0)<div class="total-line"><span>Tax</span><strong>{{ $sale->currency ?: 'AED' }} {{ $money($sale->tax_amount) }}</strong></div>@endif
            @if((float) $sale->shipping_cost > 0)<div class="total-line"><span>Shipping</span><strong>{{ $sale->currency ?: 'AED' }} {{ $money($sale->shipping_cost) }}</strong></div>@endif
            <div class="grand"><span>Total</span><span>{{ $sale->currency ?: 'AED' }} {{ $money($sale->total_amount) }}</span></div>
        </section>
        <section class="payment">
            <span>Received: <strong>{{ $sale->currency ?: 'AED' }} {{ $money($sale->paid_amount) }}</strong></span>
            <span>Balance due: <strong>{{ $sale->currency ?: 'AED' }} {{ $money($sale->balance_amount) }}</strong></span>
            <span>{{ $sale->payment_method ?: $sale->payment_status }}</span>
        </section>
        <footer class="footnote">Thank you for your business.</footer>
    </main>
</body>
</html>
