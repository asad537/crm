@php
    $__invoiceWorkspace = $order->workspace ?: ($activeCrmWorkspace ?? null);
    // This template renders the TheCustomBoxes (TCB) branded "Sale Invoice".
    $__invoicePrimary = '#376094';
    $__invoicePrimarySoft = '#e6ecf5';
    $__invoiceCurrency = $order->invoice_currency ?: 'USD';
    $__currencySymbol = $__invoiceCurrency === 'USD' ? '$' : ($__invoiceCurrency === 'GBP' ? '£' : ($__invoiceCurrency === 'EUR' ? '€' : ''));
    $__orderItems = method_exists($order, 'orderItems') ? $order->orderItems : collect();
    $__hasItems = $__orderItems && $__orderItems->count() > 0;
    $__subtotal = $__hasItems
        ? (float) $__orderItems->sum('line_total')
        : ($order->order_price ?? 0) * ($order->order_quantity ?? 0);
    $__invoiceNo = $order->order_invoice_number ?: ('#' . str_pad($order->id, 5, '0', STR_PAD_LEFT));
    $__vatPercentage = (float) ($order->vat_percentage ?? 0);
    $__vatAmount = $__subtotal * $__vatPercentage / 100;
    $__discount = (float) ($order->discount ?? 0);
    $__grandTotal = max(0, $__subtotal - $__discount + $__vatAmount);
    $__paid = strtolower($order->payment_status ?: 'Unpaid') === 'paid';
    $__invoiceDate = $order->order_marked_at ? \Carbon\Carbon::parse($order->order_marked_at) : $order->created_at;
    $__money = function ($amount) use ($__invoiceCurrency) {
        return $__invoiceCurrency . ' ' . number_format((float) $amount, 2);
    };
    $__unitMoney = function ($amount) {
        $s = number_format((float) $amount, 4, '.', ',');
        if (strpos($s, '.') !== false) {
            $s = rtrim($s, '0');
            $decimals = strlen(substr($s, strrpos($s, '.') + 1));
            if ($decimals < 2) { $s = number_format((float) $amount, 2, '.', ','); }
        }
        return $s;
    };
    $__sizeStr = trim(collect([$order->length, $order->width, $order->height])->filter(fn($v) => $v !== null && $v !== '')->implode(' x '));
    if ($__sizeStr !== '' && $order->unit) { $__sizeStr .= ' ' . $order->unit; }
    $__specs = collect([$order->color, $order->coating, $order->lamination, $order->printing, $order->finish_size])->filter()->implode(', ');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Invoice {{ $__invoiceNo }} — {{ $order->client_name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', Arial, sans-serif; background: #eef1f6; color: #1f2733; padding: 2rem 1rem; display: flex; flex-direction: column; align-items: center; }

        /* Toolbar */
        .toolbar { width: 100%; max-width: 800px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
        .btn-back { display: inline-flex; align-items: center; gap: 6px; padding: .55rem 1.1rem; background: #fff; color: #64748b; border: 1px solid #e2e8f0; border-radius: 9px; font-weight: 600; font-size: .82rem; text-decoration: none; cursor: pointer; }
        .btn-back:hover { background: #f8fafc; }
        .btn-print { display: inline-flex; align-items: center; gap: 6px; padding: .55rem 1.25rem; background: {{ $__invoicePrimary }}; color: #fff; border: none; border-radius: 9px; font-weight: 700; font-size: .82rem; cursor: pointer; }

        /* Invoice sheet */
        .inv-card { background: #fff; width: 100%; max-width: 800px; box-shadow: 0 2px 20px rgba(0,0,0,.08); border: 1px solid #e6e9ef; }
        .inv-pad { padding: 20px 26px; }

        /* Header */
        .tcb-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .tcb-brand { background: {{ $__invoicePrimary }}; border-radius: 8px; padding: 11px 18px; display: flex; align-items: center; gap: 11px; }
        .tcb-brand img { height: 40px; width: auto; }
        .tcb-brand .b-text { line-height: 1; }
        .tcb-brand .b-name { font-size: 1.3rem; font-weight: 800; color: #fff; letter-spacing: .2px; white-space: nowrap; }
        .tcb-brand .b-name .light { color: #c7d2e6; font-weight: 700; }
        .tcb-brand .b-tag { margin-top: 5px; font-size: .58rem; letter-spacing: .16em; text-transform: uppercase; color: #aebbd4; }
        .tcb-sale { display: flex; align-items: center; }
        .tcb-sale h1 { color: {{ $__invoicePrimary }}; font-size: 1.7rem; font-weight: 800; white-space: nowrap; }

        /* Info under header */
        .meta-top { display: flex; justify-content: space-between; margin-top: 14px; }
        .addr-label { font-weight: 800; color: #1f2733; font-size: .8rem; }
        .addr-lines { margin-top: 4px; color: #475569; font-size: .78rem; line-height: 1.6; padding-left: 12px; }
        .inv-meta-box { min-width: 260px; }
        .paid-stamp { text-align: right; font-weight: 800; font-size: .95rem; margin-bottom: 5px; color: {{ $__invoicePrimary }}; }
        .paid-stamp .flag { color: {{ '#0a7d33' }}; }
        .paid-stamp .flag.unpaid { color: #d9342b; }
        table.kv { border-collapse: collapse; width: 100%; }
        table.kv td { border: 1px solid #b9c3d4; padding: 5px 9px; font-size: .78rem; }
        table.kv td.k { font-weight: 700; color: #1f2733; width: 46%; }
        table.kv td.v { color: #334155; }

        /* Section header bars */
        .bar { background: {{ $__invoicePrimary }}; color: #fff; font-weight: 700; font-size: .78rem; padding: 6px 10px; }
        .two-col { display: flex; gap: 14px; margin-top: 16px; }
        .two-col > div { flex: 1; }
        .party-body { border: 1px solid #d5dbe6; border-top: none; padding: 9px 10px; font-size: .78rem; color: #334155; line-height: 1.55; min-height: 92px; white-space: pre-line; }
        .party-body .pname { font-weight: 700; color: #0f172a; margin-bottom: 2px; }

        /* Meta strip (sales person etc) */
        table.strip { border-collapse: collapse; width: 100%; margin-top: 16px; table-layout: fixed; }
        table.strip th { background: {{ $__invoicePrimary }}; color: #fff; font-size: .68rem; font-weight: 700; padding: 6px 8px; text-align: center; border: 1px solid {{ $__invoicePrimary }}; }
        table.strip td { border: 1px solid #d5dbe6; padding: 7px 8px; font-size: .76rem; text-align: center; color: #334155; }

        /* Items */
        table.items { border-collapse: collapse; width: 100%; margin-top: 16px; }
        table.items th { background: {{ $__invoicePrimary }}; color: #fff; font-size: .68rem; font-weight: 700; padding: 8px 8px; text-align: center; border: 1px solid {{ $__invoicePrimary }}; text-transform: uppercase; letter-spacing: .03em; }
        table.items td { border: 1px solid #d5dbe6; padding: 9px 8px; font-size: .78rem; color: #334155; vertical-align: top; }
        table.items td.desc .pn { font-weight: 700; color: #0f172a; }
        table.items td.desc .sp { color: #64748b; font-size: .72rem; margin-top: 2px; }
        .c { text-align: center; } .r { text-align: right; }

        /* Bottom: terms + totals */
        .bottom { display: flex; gap: 16px; margin-top: 4px; align-items: flex-start; }
        .terms { flex: 1.35; }
        .terms .bar { margin-top: 0; }
        .terms-body { border: 1px solid #d5dbe6; border-top: none; padding: 10px 12px; font-size: .72rem; color: #334155; line-height: 1.55; }
        .terms-body ol { margin: 0; padding-left: 18px; }
        .terms-body li { margin-bottom: 6px; }
        .terms-body .sub { color: #475569; }
        .totals { flex: 1; }
        table.tot { border-collapse: collapse; width: 100%; }
        table.tot td { border: 1px solid #b9c3d4; padding: 7px 10px; font-size: .82rem; }
        table.tot td.k { font-weight: 700; color: #1f2733; width: 55%; }
        table.tot td.v { text-align: right; color: #334155; }
        table.tot tr.grand td { background: {{ $__invoicePrimary }}; color: #fff; font-weight: 800; font-size: 1rem; border-color: {{ $__invoicePrimary }}; }

        /* Footer */
        .foot { text-align: center; margin-top: 22px; }
        .foot .ty { color: {{ $__invoicePrimary }}; font-weight: 800; font-size: 1.05rem; }
        .foot .cs { color: #475569; font-size: .74rem; margin-top: 6px; line-height: 1.5; }
        .foot .contact { display: flex; flex-wrap: wrap; justify-content: center; gap: 4px 18px; margin-top: 10px; color: #22406a; font-size: .74rem; font-weight: 600; }
        .foot .pay { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 14px; }
        .foot .pay span { font-size: .72rem; color: #64748b; font-weight: 700; }
        .foot .pay img { height: 22px; width: auto; }

        @media print {
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            html, body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
            .toolbar { display: none !important; }
            .inv-card { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; }
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>
<body>

    {{-- Toolbar --}}
    <div class="toolbar">
        <a href="{{ route('crm.orders.index') }}" class="btn-back">&#8592; Back to Orders</a>
        <div style="display:flex; gap:.5rem;">
            @if(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || Auth::guard('crm')->user()->isAccounts() || (Auth::guard('crm')->user()->isSales() && ($order->assigned_to == Auth::guard('crm')->user()->id || $order->order_marked_by === Auth::guard('crm')->user()->name)))
            <a href="{{ route('crm.orders.invoice.edit', $order->id) }}" class="btn-back" style="color:{{ $__invoicePrimary }}; border-color:{{ $__invoicePrimarySoft }};">Edit Invoice</a>
            <form action="{{ route('crm.orders.invoice.send', $order->id) }}" method="POST" style="margin:0;" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Sending...';">
                {{ csrf_field() }}
                <button type="submit" class="btn-print">Send Invoice</button>
            </form>
            @endif
            <button onclick="window.print()" class="btn-back">Print</button>
            <button type="button" id="dlPdfBtn" onclick="downloadInvoicePdf(this)" class="btn-print">Download PDF</button>
        </div>
    </div>

    {{-- Invoice sheet --}}
    <div class="inv-card">
        <div class="inv-pad">

            {{-- Header --}}
            <div class="tcb-head">
                <div class="tcb-brand">
                    <img src="{{ asset('tcb-icon.png') }}" alt="TheCustomBoxes">
                    <div class="b-text">
                        <div class="b-name">TheCustom<span class="light">Boxes</span></div>
                        <div class="b-tag">Smart Packaging Solutions</div>
                    </div>
                </div>
                <div class="tcb-sale"><h1>Sale Invoice</h1></div>
            </div>

            {{-- Address + invoice meta --}}
            <div class="meta-top">
                <div>
                    <div class="addr-label">Address:</div>
                    <div class="addr-lines">9933 Franklin Ave,<br>Franklin Park, IL 60131<br>1800-396-1840</div>
                </div>
                <div class="inv-meta-box">
                    <div class="paid-stamp"># <span class="flag {{ $__paid ? '' : 'unpaid' }}">{{ $__paid ? 'PAID' : strtoupper($order->payment_status ?: 'UNPAID') }}</span></div>
                    <table class="kv">
                        <tr><td class="k">Invoice no :</td><td class="v">{{ $__invoiceNo }}</td></tr>
                        <tr><td class="k">Date:</td><td class="v">{{ $__invoiceDate->format('m/d/Y') }}</td></tr>
                        <tr><td class="k">Purchase Order # :</td><td class="v">{{ $order->external_lead_id ?: '—' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Bill To / Ship To --}}
            <div class="two-col">
                <div>
                    <div class="bar">Bill To :</div>
                    <div class="party-body"><span class="pname">{{ $order->client_name }}</span>{{ $order->billing_address ? "\n".$order->billing_address : '' }}{{ $order->client_phone ? "\n".$order->client_phone : '' }}{{ $order->client_email ? "\n".$order->client_email : '' }}</div>
                </div>
                <div>
                    <div class="bar">Ship To :</div>
                    <div class="party-body"><span class="pname">{{ $order->client_name }}</span>{{ ($order->shipping_address ?: $order->billing_address) ? "\n".($order->shipping_address ?: $order->billing_address) : '' }}{{ $order->client_phone ? "\n".$order->client_phone : '' }}</div>
                </div>
            </div>

            {{-- Meta strip --}}
            <table class="strip">
                <tr>
                    <th>Sales person</th><th>Shipping Method</th><th>Shipping Terms</th><th>Payment Terms</th><th>Due Date</th>
                </tr>
                <tr>
                    <td>{{ $order->order_marked_by ?: '—' }}</td>
                    <td>{{ $order->shipping_region ?: 'Standard' }}</td>
                    <td>Standard</td>
                    <td>{{ $order->payment_status ?: '—' }}</td>
                    <td>—</td>
                </tr>
            </table>

            {{-- Items --}}
            <table class="items">
                <thead>
                    <tr>
                        <th style="width:36px">Sr.<br>No</th>
                        <th style="width:90px">Size</th>
                        <th style="width:110px">Stock</th>
                        <th>Description</th>
                        <th style="width:56px">Qty</th>
                        <th style="width:78px">Unit Price</th>
                        <th style="width:92px">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if($__hasItems)
                        @foreach($__orderItems as $__i => $__item)
                        <tr>
                            <td class="c">{{ $__i + 1 }}</td>
                            <td class="c">{{ $__sizeStr ?: '—' }}</td>
                            <td class="c">{{ $order->stock ?: '—' }}</td>
                            <td class="desc"><div class="pn">{{ $__item->product_name ?: ($order->product_name ?: 'Custom Packaging') }}</div>@if($__specs)<div class="sp">{{ $__specs }}</div>@endif</td>
                            <td class="c">{{ number_format($__item->quantity) }}</td>
                            <td class="r">{{ $__unitMoney($__item->unit_price) }}</td>
                            <td class="r">{{ $__money($__item->line_total) }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="c">1</td>
                            <td class="c">{{ $__sizeStr ?: '—' }}</td>
                            <td class="c">{{ $order->stock ?: '—' }}</td>
                            <td class="desc"><div class="pn">{{ $order->product_name ?: 'Custom Packaging Order' }}</div>@if($__specs)<div class="sp">{{ $__specs }}</div>@endif</td>
                            <td class="c">{{ number_format($order->order_quantity ?? 0) }}</td>
                            <td class="r">{{ $__unitMoney($order->order_price ?? 0) }}</td>
                            <td class="r">{{ $__money($__subtotal) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- Terms + totals --}}
            <div class="bottom">
                <div class="terms">
                    <div class="bar">Terms &amp; Conditions :</div>
                    <div class="terms-body">
                        <ol>
                            <li>As a part of order placement we require the users and customers of our website to approve proof (whether an electronic file or hard copy) of the printing products or other services they order.</li>
                            <li>Following the approval of proof by the customers the printing jobs are to press or via our website, no changes are allowed to the artwork files, job specifications, or printing turnaround time.</li>
                            <li>Lead Time once a print job has been approved by customer and sent to press.<br><span class="sub">Standard (10 to 12 Business Days and 2 to 3 days for ground shipping.)</span><br><span class="sub">Rush (6 to 8 Business Days and 2 to 3 days for ground shipping.)</span></li>
                            <li>No hidden or setup charges.</li>
                        </ol>
                    </div>
                </div>
                <div class="totals">
                    <table class="tot">
                        <tr><td class="k">Sub Total</td><td class="v">{{ $__money($__subtotal) }}</td></tr>
                        <tr><td class="k">Discount</td><td class="v">{{ $__money($__discount) }}</td></tr>
                        @if($__vatAmount > 0.009)
                        <tr><td class="k">VAT {{ number_format($__vatPercentage, 2) }}%</td><td class="v">{{ $__money($__vatAmount) }}</td></tr>
                        @endif
                        <tr><td class="k">Rush Charges</td><td class="v">{{ $__money(0) }}</td></tr>
                        <tr class="grand"><td>Total</td><td class="v" style="text-align:right;">{{ $__money($__grandTotal) }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Footer --}}
            <div class="foot">
                <div class="ty">Thank you for your business</div>
                <div class="cs">If You Have Any Questions Or Require Further Assistance, Please Contact Our Customer Service Team<br>Between 8.00am And 7.00pm CST, Monday-Friday</div>
                <div class="contact">
                    <span>&#9742; 1800-396-1840, 630-364-3944</span>
                    <span>&#9742; 800-604-1874</span>
                    <span>&#9993; support@thecustomboxes.com</span>
                    <span>&#127760; www.thecustomboxes.com</span>
                </div>
                <div class="pay">
                    <span>Payment Options :</span>
                    <img src="{{ asset('box_assets/img/paypal.png') }}" alt="PayPal">
                    <img src="{{ asset('box_assets/img/master-card.png') }}" alt="MasterCard">
                    <img src="{{ asset('box_assets/img/visa.png') }}" alt="Visa">
                    <img src="{{ asset('box_assets/img/american-express.png') }}" alt="Amex">
                    <img src="{{ asset('box_assets/img/discover.png') }}" alt="Discover">
                    <img src="{{ asset('box_assets/img/ebank-transfer.png') }}" alt="Bank Transfer">
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.2/dist/html2pdf.bundle.min.js"></script>
    <script>
        function downloadInvoicePdf(btn) {
            var el = document.querySelector('.inv-card');
            if (!el) { window.print(); return; }
            var name = @json($__invoiceNo);
            name = String(name).replace(/[^A-Za-z0-9_-]+/g, '');
            if (typeof html2pdf === 'undefined') { window.print(); return; }
            var original = btn.innerHTML;
            btn.innerHTML = 'Generating…';
            btn.disabled = true;
            var opt = {
                margin: 6,
                filename: 'Sale-Invoice-' + name + '.pdf',
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
