<table border="1">
    <tr><td colspan="11" style="font-weight:bold;font-size:14px">{{ $meta['workspaceName'] }} — Invoices</td></tr>
    <tr><td colspan="11">Generated {{ $meta['generatedAt'] }} · {{ $meta['count'] }} invoice(s)</td></tr>
    <tr><td colspan="6" style="font-weight:bold">Total</td><td style="font-weight:bold">{{ number_format($meta['total'], 2, '.', '') }}</td><td colspan="4"></td></tr>
    <tr style="font-weight:bold;background:#eef2f7">
        <td>Invoice #</td><td>Client</td><td>Email</td><td>Product</td>
        <td>Unit Price</td><td>Qty</td><td>Total</td><td>Agent</td><td>Date</td><td>Payment</td><td>Currency</td>
    </tr>
    @foreach($orders as $o)
        @php $__total = (float)($o->order_price ?? 0) * (float)($o->order_quantity ?? 0); @endphp
        <tr>
            <td>{{ $o->order_invoice_number ?: ('#'.str_pad($o->id, 5, '0', STR_PAD_LEFT)) }}</td>
            <td>{{ $o->client_name }}</td>
            <td>{{ $o->client_email }}</td>
            <td>{{ $o->product_name }}</td>
            <td>{{ number_format($o->order_price ?? 0, 2, '.', '') }}</td>
            <td>{{ number_format($o->order_quantity ?? 0) }}</td>
            <td>{{ number_format($__total, 2, '.', '') }}</td>
            <td>{{ $o->order_marked_by ?: '-' }}</td>
            <td>{{ optional($o->order_marked_at ?: $o->created_at)->format('d/m/Y') }}</td>
            <td>{{ $o->payment_status ?: '-' }}</td>
            <td>{{ $o->invoice_currency }}</td>
        </tr>
    @endforeach
</table>
