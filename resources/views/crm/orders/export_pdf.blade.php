<!doctype html><html><head><meta charset="utf-8"><title>{{ $meta['workspaceName'] }} — Invoices</title><style>
@page { margin: 24px 20px; }
body { font-family: DejaVu Sans, Arial, sans-serif; color: #172033; font-size: 8px; }
header { border-bottom: 3px solid {{ $meta['isAlMassa'] ? '#f45a24' : '#6c5ce7' }}; padding-bottom: 10px; margin-bottom: 12px; }
h1 { font-size: 15px; margin: 0 0 4px; color: {{ $meta['isAlMassa'] ? '#f45a24' : '#6c5ce7' }}; }
.meta { color: #64748b; margin-bottom: 10px; }
.totals td { padding: 5px 9px; border: 1px solid #cbd5e1; font-weight: bold; }
table.rows { width: 100%; border-collapse: collapse; }
.rows th, .rows td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; }
.rows th { background: {{ $meta['isAlMassa'] ? '#f45a24' : '#6c5ce7' }}; color: #fff; font-size: 7px; text-transform: uppercase; }
.rows tr:nth-child(even) td { background: #f4f6fb; }
.num { text-align: right; }
</style></head><body>
<header>
    <h1>{{ $meta['workspaceName'] }} — Invoices</h1>
    <div>Invoice / Orders Export</div>
</header>
<div class="meta">Generated {{ $meta['generatedAt'] }} &nbsp;·&nbsp; {{ $meta['count'] }} invoice(s)</div>
<table class="totals"><tr><td>Total: {{ number_format($meta['total'], 2) }}</td></tr></table>
<br>
<table class="rows"><thead><tr>
    <th>Invoice #</th><th>Client</th><th>Email</th><th>Product</th>
    <th class="num">Unit Price</th><th class="num">Qty</th><th class="num">Total</th>
    <th>Agent</th><th>Date</th><th>Payment</th><th>Currency</th>
</tr></thead><tbody>
@foreach($orders as $o)
    @php $__total = (float)($o->order_price ?? 0) * (float)($o->order_quantity ?? 0); @endphp
    <tr>
        <td>{{ $o->order_invoice_number ?: ('#'.str_pad($o->id, 5, '0', STR_PAD_LEFT)) }}</td>
        <td>{{ $o->client_name }}</td>
        <td>{{ $o->client_email }}</td>
        <td>{{ $o->product_name }}</td>
        <td class="num">{{ number_format($o->order_price ?? 0, 2) }}</td>
        <td class="num">{{ number_format($o->order_quantity ?? 0) }}</td>
        <td class="num">{{ number_format($__total, 2) }}</td>
        <td>{{ $o->order_marked_by ?: '—' }}</td>
        <td>{{ optional($o->order_marked_at ?: $o->created_at)->format('d/m/Y') }}</td>
        <td>{{ $o->payment_status ?: '—' }}</td>
        <td>{{ $o->invoice_currency }}</td>
    </tr>
@endforeach
</tbody></table>
</body></html>
