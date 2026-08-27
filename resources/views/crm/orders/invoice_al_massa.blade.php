@php
    $isAlMassa = $order->workspace && $order->workspace->slug === 'mybox-packaging-app';
    $brandName = $isAlMassa ? 'Al Massa Al Malakiya' : 'My Box Printing';
    $logo = $isAlMassa ? 'al-massa-invoice-email-logo.png' : 'my-box-printing-logo.svg';
    $primaryColor = $isAlMassa ? '#0b2a62' : '#6c5ce7';
    $accentColor = $isAlMassa ? '#d69a00' : '#6c5ce7';
    $rowColor = $isAlMassa ? '#b8d9ec' : '#e4e0ff';
    $companyName = $isAlMassa ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING';
    $tagline = $isAlMassa ? 'All Cosmetics & Perfumes Hard, Soft Boxes and Paper Bags' : 'Custom Packaging Boxes with Logo';
    $address = $isAlMassa ? 'Al Diyar Building 33, 4th Industrial Street, Industrial Area 12, Sharjah, United Arab Emirates' : '9933 Franklin Ave Suite 112, Franklin Park, IL 60131, United States';
    $phone = $isAlMassa ? '+971 6 579 6994, +971 56 997 0652, +971 54 793 4286' : '+1 847-200-0974';
    $email = $isAlMassa ? 'info@almassapackaging.com' : 'quotes@myboxprinting.com';
    $signatoryName = $isAlMassa ? 'AMIR BASHIR' : 'MY BOX PRINTING';
    $currency = $order->invoice_currency ?: ($isAlMassa ? 'AED' : 'USD');
    $orderItems = method_exists($order, 'orderItems') ? $order->orderItems : collect();
    $hasItems = $orderItems && $orderItems->count() > 0;
    $subtotal = $hasItems
        ? (float) $orderItems->sum('line_total')
        : ($order->order_price ?? 0) * ($order->order_quantity ?? 0);
    $vatPercentage = (float) ($order->vat_percentage ?? 5);
    $vatAmount = $subtotal * $vatPercentage / 100;
    $grandTotal = $subtotal + $vatAmount;
    $invoiceDate = $order->order_marked_at ?: $order->created_at;
    $formatMoney = function ($amount) {
        return number_format($amount, 2, '.', ',');
    };
    // Unit price may carry fine precision (e.g. 0.019). Show up to 4 decimals, min 2, no trailing zeros beyond that.
    $formatUnit = function ($amount) {
        $s = number_format((float) $amount, 4, '.', ',');
        if (strpos($s, '.') !== false) {
            $s = rtrim($s, '0');
            $decimals = strlen(substr($s, strrpos($s, '.') + 1));
            if ($decimals < 2) {
                $s = number_format((float) $amount, 2, '.', ',');
            }
        }
        return $s;
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandName }} Invoice {{ $order->order_invoice_number ?: str_pad($order->id, 6, '0', STR_PAD_LEFT) }} - {{ $order->client_name }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { background: #eef1f7; color: #202020; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { width: 210mm; max-width: calc(100% - 24px); margin: 18px auto 12px; display: flex; justify-content: space-between; gap: 10px; }
        .toolbar-group { display: flex; gap: 8px; }
        .toolbar a, .toolbar button { border: 1px solid #d8dee9; border-radius: 8px; background: white; color: #334155; padding: 9px 14px; text-decoration: none; font-size: 13px; font-weight: 700; cursor: pointer; }
        .toolbar .primary { background: {{ $primaryColor }}; color: white; border-color: {{ $primaryColor }}; }
        .invoice-page { width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 17mm 16mm 15mm; background: white; box-shadow: 0 5px 24px rgba(15,23,42,.12); position: relative; display: flex; flex-direction: column; }
        .header { display: grid; grid-template-columns: 47mm 1fr 47mm; align-items: start; column-gap: 6mm; }
        .logo { width: 42mm; height: 42mm; object-fit: contain; }
        .company-arabic { color: {{ $primaryColor }}; font-size: 15px; font-weight: 800; margin: 2px 0 3px; direction: rtl; text-align: left; }
        .company-name { color: {{ $primaryColor }}; font-size: 16px; line-height: 1.25; font-weight: 800; }
        .company-tagline { color: {{ $primaryColor }}; font-size: 11px; margin-top: 5px; }
        .company-line { color: {{ $primaryColor }}; font-size: 10.5px; line-height: 1.45; margin-top: 4px; }
        .invoice-word { color: {{ $accentColor }}; font-size: 29px; font-weight: 900; letter-spacing: 1px; align-self: end; margin-top: 31mm; text-align: right; }
        .header-rule { height: 2px; background: #aaa; margin: 7px 0 14px; position: relative; }
        .header-rule::before { content: ''; position: absolute; left: 0; top: 0; width: 16mm; height: 3px; border-radius: 3px; background: {{ $primaryColor }}; }
        .customer-details { display: grid; grid-template-columns: 1fr 55mm; column-gap: 15mm; }
        .invoice-to { font-size: 13px; margin-bottom: 10px; }
        .customer-name { font-size: 18px; font-weight: 800; margin-bottom: 7px; text-transform: uppercase; }
        .customer-line { color: #606060; font-size: 12px; line-height: 1.45; }
        .meta-row { display: grid; grid-template-columns: auto 1fr; gap: 5px; font-size: 13px; line-height: 1.5; }
        .meta-label { font-weight: 800; }
        .items { width: 100%; border-collapse: collapse; margin-top: 17px; table-layout: fixed; }
        .items th { background: {{ $primaryColor }}; color: white; padding: 7px 6px; font-size: 12px; font-weight: 800; text-align: center; }
        .items th:nth-child(1) { width: 8%; }
        .items th:nth-child(2) { width: 42%; }
        .items th:nth-child(3) { width: 18%; }
        .items th:nth-child(4) { width: 20%; }
        .items th:nth-child(5) { width: 12%; }
        .items td { background: {{ $rowColor }}; border-right: 2px solid white; padding: 6px 8px; font-size: 12px; }
        .items td:last-child { border-right: 0; }
        .items .center { text-align: center; }
        .items .right { text-align: right; }
        .totals { width: 70mm; margin-left: auto; margin-top: 45mm; }
        .total-row { display: grid; grid-template-columns: 1fr 28mm; gap: 8px; padding: 5px 4px; font-size: 13px; }
        .total-row span:first-child { text-align: right; }
        .total-row span:last-child { text-align: right; }
        .grand-total { width: 100%; background: {{ $primaryColor }}; color: white; display: grid; grid-template-columns: 1fr auto; gap: 8px; margin-top: 8px; padding: 7px 10px; font-size: 14px; font-weight: 800; }
        .grand-total span:first-child { text-align: right; }
        .signoff { margin-top: 36mm; }
        .signatory { text-align: right; margin-right: 3mm; }
        .signatory-name { font-size: 19px; font-weight: 800; }
        .signatory-title { font-size: 14px; margin-top: 4px; }
        .thanks { margin-top: 15mm; text-align: center; font-size: 14px; font-weight: 800; }
        .footer-rule { height: 2px; margin-top: 8px; background: #aaa; position: relative; }
        .footer-rule::before, .footer-rule::after { content: ''; position: absolute; top: 0; width: 16mm; height: 3px; border-radius: 3px; background: {{ $primaryColor }}; }
        .footer-rule::before { left: 0; }
        .footer-rule::after { right: 0; }
        @media(max-width: 820px) {
            .invoice-page { width: 100%; min-height: auto; padding: 24px; }
            .header { grid-template-columns: 100px 1fr; }
            .invoice-word { grid-column: 1 / -1; margin-top: 12px; text-align: left; }
            .customer-details { grid-template-columns: 1fr; row-gap: 18px; }
            .items { min-width: 680px; }
            .invoice-page { overflow-x: auto; }
        }
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: white; }
            .toolbar { display: none; }
            .invoice-page { margin: 0; box-shadow: none; width: 210mm; min-height: 297mm; page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('crm.orders.index') }}">&#8592; Back to Orders</a>
        <div class="toolbar-group">
            @if(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || Auth::guard('crm')->user()->isAccounts() || (Auth::guard('crm')->user()->isSales() && ($order->assigned_to == Auth::guard('crm')->user()->id || $order->order_marked_by === Auth::guard('crm')->user()->name)))
                <a href="{{ route('crm.orders.invoice.edit', $order->id) }}">Edit Invoice</a>
                <form action="{{ route('crm.orders.invoice.send', $order->id) }}" method="POST" style="margin:0;">@csrf<button type="submit">Send Invoice</button></form>
            @endif
            <button type="button" onclick="window.print()">Print</button>
            <button type="button" class="primary" id="dlPdfBtn" onclick="downloadInvoicePdf(this)">&#8681; Download PDF</button>
        </div>
    </div>

    <main class="invoice-page">
        <header class="header">
            <img class="logo" src="{{ asset($logo) }}" alt="{{ $brandName }}">
            <div>
                @if($isAlMassa)<div class="company-arabic">الماسة الملكية لصناعة العلب و التغليف ذ.م.م</div>@endif
                <div class="company-name">{{ $companyName }}</div>
                <div class="company-tagline">{{ $tagline }}</div>
                <div class="company-line">&#9679;&nbsp; {{ $address }}</div>
                <div class="company-line">&#9742;&nbsp; {{ $phone }}</div>
                <div class="company-line">&#9993;&nbsp; {{ $email }}</div>
            </div>
            <div class="invoice-word">INVOICE</div>
        </header>

        <div class="header-rule"></div>

        <section class="customer-details">
            <div>
                <div class="invoice-to">Invoice to :</div>
                <div class="customer-name">{{ $order->client_name }}</div>
                @if($order->client_phone)<div class="customer-line">{{ $order->client_phone }}</div>@endif
                @if($order->customer_trn)<div class="customer-line">TR No: {{ $order->customer_trn }}</div>@endif
                <div class="customer-line">{!! nl2br(e($order->billing_address ?: $order->shipping_address ?: '')) !!}</div>
            </div>
            <div>
                <div class="meta-row"><span class="meta-label">Invoice No:</span><span>{{ $order->order_invoice_number ?: str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span></div>
                <div class="meta-row"><span class="meta-label">TR No:</span><span>{{ $order->company_trn }}</span></div>
                <div class="meta-row"><span class="meta-label">TL No:</span><span>{{ $order->trade_license_number ?: '801625' }}</span></div>
                <div class="meta-row"><span class="meta-label">Currency:</span><span>{{ $currency }}</span></div>
                <div class="meta-row"><span class="meta-label">Dated:</span><span>{{ \Carbon\Carbon::parse($invoiceDate)->format('d F Y') }}</span></div>
            </div>
        </section>

        <table class="items">
            <thead><tr><th>NO</th><th>DESCRIPTION</th><th>QTY</th><th>PRICE PER UNIT</th><th>TOTAL</th></tr></thead>
            <tbody>
            @if($hasItems)
                @foreach($orderItems as $__i => $__item)
                <tr>
                    <td class="center">{{ $__i + 1 }}</td>
                    <td>{{ $__item->product_name ?: 'Custom Packaging Order' }}</td>
                    <td class="center">{{ number_format($__item->quantity) }}</td>
                    <td class="center">{{ $formatUnit($__item->unit_price) }}</td>
                    <td class="right">{{ $formatMoney($__item->line_total) }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td class="center">1</td>
                    <td>{{ $order->product_name ?: 'Custom Packaging Order' }}</td>
                    <td class="center">{{ number_format($order->order_quantity ?? 0) }}</td>
                    <td class="center">{{ $formatUnit($order->order_price ?? 0) }}</td>
                    <td class="right">{{ $formatMoney($subtotal) }}</td>
                </tr>
            @endif
            </tbody>
        </table>

        <section class="totals">
            <div class="total-row"><span>Sub Total :</span><span>{{ $formatMoney($subtotal) }}</span></div>
            <div class="total-row"><span>VAT {{ number_format($vatPercentage, 2) }}% :</span><span>{{ $formatMoney($vatAmount) }}</span></div>
        </section>
        <div class="grand-total"><span>GRAND TOTAL :</span><span>{{ $formatMoney($grandTotal) }} {{ $currency }}</span></div>

        <section class="signoff">
            <div class="signatory"><div class="signatory-name">{{ $signatoryName }}</div><div class="signatory-title">Administrator</div></div>
            <div class="thanks">Thank you for business with us!</div>
            <div class="footer-rule"></div>
        </section>
    </main>

    @php $__pdfName = $order->order_invoice_number ?: str_pad($order->id, 6, '0', STR_PAD_LEFT); @endphp
    <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.2/dist/html2pdf.bundle.min.js"></script>
    <script>
        function downloadInvoicePdf(btn) {
            var el = document.querySelector('.invoice-page');
            if (!el) { window.print(); return; }
            var name = String(@json($__pdfName)).replace(/[^A-Za-z0-9_-]+/g, '');
            if (typeof html2pdf === 'undefined') { window.print(); return; }
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
