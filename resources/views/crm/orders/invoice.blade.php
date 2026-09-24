@php
    // TheCustomBoxes (TCB) branded "INVOICE" — matches the order-create PDF (crm/orders/pdf.blade.php).
    $__blue = '#2E75B6';
    $__invoicePrimary = $__blue;
    $__invoicePrimarySoft = '#e3eefa';
    $__cur = $order->invoice_currency ?: 'USD';
    $__orderItems = method_exists($order, 'orderItems') ? $order->orderItems : collect();
    $__hasItems = $__orderItems && $__orderItems->count() > 0;
    $__subtotal = $__hasItems
        ? (float) $__orderItems->sum('line_total')
        : ($order->order_price ?? 0) * ($order->order_quantity ?? 0);
    $__invoiceNo = $order->order_invoice_number ? 'TCB-' . $order->order_invoice_number : ('#' . str_pad($order->id, 5, '0', STR_PAD_LEFT));
    $__vatPercentage = (float) ($order->vat_percentage ?? 0);
    $__vatAmount = $__subtotal * $__vatPercentage / 100;
    $__discount = (float) ($order->discount ?? 0);
    $__grandTotal = max(0, $__subtotal - $__discount + $__vatAmount);
    $__invoiceDate = $order->order_marked_at ? \Carbon\Carbon::parse($order->order_marked_at) : $order->created_at;
    $__money = fn ($amount) => $__cur . ' ' . number_format((float) $amount, 2);
    $__unit = function ($amount) {
        $s = number_format((float) $amount, 3, '.', ',');
        return $s;
    };
    $__sizeStr = trim(collect([$order->length, $order->width, $order->height])->filter(fn($v) => $v !== null && $v !== '')->implode('x'));
    if ($__sizeStr !== '' && $order->unit) { $__sizeStr .= ' ' . $order->unit; }
    $__specStr = trim(($order->stock ? $order->stock . ' ' : '') . $__sizeStr);
    $__finish = collect([
        $order->lamination ? 'Lamination — ' . $order->lamination : null,
        $order->coating ? 'Coating — ' . $order->coating : null,
        $order->die ? 'Die Cutting — ' . $order->die : null,
        $order->glue ? 'Gluing — ' . $order->glue : null,
        $order->printing ? 'Printing — ' . $order->printing : null,
        $order->finish_size ? 'Finish Size — ' . $order->finish_size : null,
    ])->filter()->implode(', ');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $__invoiceNo }} — {{ $order->client_name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', Arial, sans-serif; background: #eef1f6; color: #1a1a1a; padding: 2rem 1rem; display: flex; flex-direction: column; align-items: center; }

        .toolbar { width: 100%; max-width: 820px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
        .btn-back { display: inline-flex; align-items: center; gap: 6px; padding: .55rem 1.1rem; background: #fff; color: #64748b; border: 1px solid #e2e8f0; border-radius: 9px; font-weight: 600; font-size: .82rem; text-decoration: none; cursor: pointer; }
        .btn-back:hover { background: #f8fafc; }
        .btn-print { display: inline-flex; align-items: center; gap: 6px; padding: .55rem 1.25rem; background: {{ $__blue }}; color: #fff; border: none; border-radius: 9px; font-weight: 700; font-size: .82rem; cursor: pointer; }

        .inv-card { background: #fff; width: 100%; max-width: 820px; box-shadow: 0 2px 20px rgba(0,0,0,.08); border: 1px solid #e6e9ef; padding: 34px 38px; }

        /* Header */
        table.hdr { width: 100%; border-collapse: collapse; }
        table.hdr > tbody > tr > td { vertical-align: top; }
        .hdr .logo img { height: 46px; width: auto; max-width: 330px; }
        .hdr .addr { font-size: .74rem; line-height: 1.7; color: #334155; margin-top: 12px; }
        .inv-word { font-size: 1.9rem; font-weight: 800; color: {{ $__blue }}; letter-spacing: .02em; margin-bottom: 8px; text-align: right; }
        table.meta { border-collapse: collapse; width: 100%; table-layout: fixed; }
        table.meta td { border: 1px solid #1a1a1a; padding: 5px 8px; font-size: .74rem; text-align: left; white-space: nowrap; }
        table.meta td.k { font-weight: 700; width: 46%; }

        /* Section bars */
        .cols { display: flex; gap: 16px; margin-top: 22px; }
        .cols > div { flex: 1; }
        .sec { background: {{ $__blue }}; color: #fff; font-weight: 700; font-size: .78rem; padding: 6px 11px; letter-spacing: .04em; }
        .party { font-size: .8rem; line-height: 1.6; color: #334155; padding: 10px 2px; white-space: pre-line; }
        .party strong { color: #0f172a; }

        /* Items */
        table.items { width: 100%; border-collapse: collapse; margin-top: 22px; }
        table.items th { background: {{ $__blue }}; color: #fff; font-size: .72rem; font-weight: 700; padding: 8px 8px; text-align: left; border: 1px solid {{ $__blue }}; }
        table.items th.num, table.items td.num { text-align: right; }
        table.items td { border: 1px solid #d5dbe2; padding: 9px 8px; font-size: .8rem; color: #334155; vertical-align: top; }
        table.items td strong { color: #0f172a; }

        /* Totals */
        .tot { width: 300px; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
        .tot td { padding: 7px 4px; font-size: .84rem; color: #475569; border-bottom: 1px solid #eef2f7; }
        .tot td.v { text-align: right; color: #1f2733; }
        .tot tr.g td { font-weight: 800; font-size: 1rem; color: #0f172a; border-top: 2px solid {{ $__blue }}; border-bottom: none; padding-top: 10px; }

        @media print {
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            html, body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
            .toolbar { display: none !important; }
            .inv-card { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; padding: 6mm !important; }
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
            <a href="{{ route('crm.orders.invoice.edit', $order->id) }}" class="btn-back" style="color:{{ $__blue }}; border-color:{{ $__invoicePrimarySoft }};">Edit Invoice</a>
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

        {{-- Header --}}
        <table class="hdr">
            <tr>
                <td class="hdr" style="width:58%">
                    <div class="logo"><img src="{{ asset('thecustomboxes-logo.png') }}" alt="TheCustomBoxes"></div>
                    <div class="addr">9933 Franklin Ave, Franklin Park, IL 60131<br>www.thecustomboxes.com</div>
                </td>
                <td class="hdr" style="width:42%">
                    <div class="inv-word">INVOICE</div>
                    <table class="meta">
                        <tr><td class="k">Invoice #</td><td>{{ $__invoiceNo }}</td></tr>
                        <tr><td class="k">Enquiry #</td><td>{{ $order->external_lead_id ?: '—' }}</td></tr>
                        <tr><td class="k">Date</td><td>{{ $__invoiceDate->format('m/d/Y') }}</td></tr>
                        <tr><td class="k">Status</td><td>{{ strtoupper($order->payment_status ?: 'UNPAID') }}</td></tr>
                        <tr><td class="k">Payment Term</td><td>{{ $order->payment_term ?? '—' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Bill / Ship --}}
        <div class="cols">
            <div>
                <div class="sec">BILL TO</div>
                <div class="party"><strong>{{ $order->client_name }}</strong>{{ $order->billing_address ? "\n".$order->billing_address : '' }}{{ $order->client_phone ? "\n".$order->client_phone : '' }}</div>
            </div>
            <div>
                <div class="sec">SHIP TO</div>
                <div class="party"><strong>{{ $order->client_name }}</strong>{{ ($order->shipping_address ?: $order->billing_address) ? "\n".($order->shipping_address ?: $order->billing_address) : '' }}{{ $order->client_phone ? "\n".$order->client_phone : '' }}</div>
            </div>
        </div>

        {{-- Items --}}
        <table class="items">
            <thead>
                <tr>
                    <th style="width:16%">Box Style</th>
                    <th style="width:16%">Specification</th>
                    <th>Finishing</th>
                    <th class="num" style="width:8%">Qty</th>
                    <th class="num" style="width:12%">Unit ({{ $__cur }})</th>
                    <th class="num" style="width:9%">Other</th>
                    <th class="num" style="width:13%">Total ({{ $__cur }})</th>
                </tr>
            </thead>
            <tbody>
                @if($__hasItems)
                    @foreach($__orderItems as $__item)
                    <tr>
                        <td><strong>{{ $__item->product_name ?: ($order->product_name ?: 'Custom Box') }}</strong>@if($order->color) · {{ $order->color }}@endif</td>
                        <td>{{ $__specStr ?: '—' }}</td>
                        <td>{{ $__finish ?: '—' }}</td>
                        <td class="num">{{ number_format($__item->quantity) }}</td>
                        <td class="num">{{ $__unit($__item->unit_price) }}</td>
                        <td class="num">0.00</td>
                        <td class="num">{{ number_format((float) $__item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td><strong>{{ $order->product_name ?: 'Custom Box' }}</strong>@if($order->color) · {{ $order->color }}@endif</td>
                        <td>{{ $__specStr ?: '—' }}</td>
                        <td>{{ $__finish ?: '—' }}</td>
                        <td class="num">{{ number_format($order->order_quantity ?? 0) }}</td>
                        <td class="num">{{ $__unit($order->order_price ?? 0) }}</td>
                        <td class="num">0.00</td>
                        <td class="num">{{ number_format((float) $__subtotal, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Totals --}}
        <table class="tot">
            <tr><td>Sub Total</td><td class="v">{{ $__money($__subtotal) }}</td></tr>
            @if($__vatAmount > 0.009)
            <tr><td>VAT {{ number_format($__vatPercentage, 2) }}%</td><td class="v">{{ $__money($__vatAmount) }}</td></tr>
            @endif
            @if($__discount > 0.009)
            <tr><td>Discount</td><td class="v">- {{ $__money($__discount) }}</td></tr>
            @endif
            <tr class="g"><td>Total</td><td class="v">{{ $__money($__grandTotal) }}</td></tr>
        </table>

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
                margin: 8,
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
