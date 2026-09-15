@extends('crm.layout')
@section('title', 'Orders')

@section('content')
<style>
.or-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.3rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px}
.or-hero h2{margin:0;font-size:1.25rem;color:#0f172a}
.or-muted{color:#8796aa;font-size:.75rem}
.or-add{display:inline-flex;align-items:center;gap:.45rem;min-height:42px;padding:.63rem .95rem;border-radius:11px;background:var(--primary-purple);color:#fff;text-decoration:none;font-weight:850;box-shadow:0 8px 20px var(--primary-shadow)}
.or-wrap{background:#fff;border:1px solid #e4eaf1;border-radius:15px;box-shadow:0 8px 26px rgba(15,23,42,.055);overflow-x:auto}
.or-table{width:100%;border-collapse:collapse;min-width:900px}
.or-table th{padding:.8rem .85rem;background:#f7f9fc;border-bottom:2px solid var(--primary-soft);text-align:left;color:#718096;font-size:.62rem;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
.or-table td{padding:.85rem .85rem;border-bottom:1px solid #edf1f5;vertical-align:middle;font-size:.78rem;color:#334155;white-space:nowrap}
.or-table tbody tr:hover{background:var(--primary-soft)}
.or-paid{display:inline-flex;padding:.3rem .6rem;border-radius:6px;font-size:.66rem;font-weight:850;text-transform:uppercase}
.or-paid.unpaid{background:#ef4444;color:#fff}
.or-paid.paid{background:#dcfce7;color:#166534}
.or-acts{display:flex;flex-direction:column;gap:.3rem;min-width:150px}
.or-act{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.42rem .6rem;border-radius:7px;font-weight:800;font-size:.68rem;text-decoration:none;border:none;cursor:pointer;background:#33415a;color:#fff;text-transform:uppercase;letter-spacing:.02em}
.or-act:hover{background:#26334a}
.or-act.del{background:#c0392b}.or-act.del:hover{background:#a33224}
.or-act.edit{background:#2f5fbf}
.or-empty{text-align:center;padding:2.5rem;color:#94a3b8}
</style>

<div class="or-hero">
    <div><h2>Orders</h2><div class="or-muted">Manual orders &amp; invoices.</div></div>
    <a href="{{ route('crm.orders.create') }}" class="or-add"><i class="fas fa-plus"></i> Create Order</a>
</div>

<div class="or-wrap">
    <table class="or-table">
        <thead><tr>
            <th>Enquiry #</th><th>Customer ID</th><th>Invoice #</th><th>Billing Name</th>
            <th>Status</th><th>Date</th><th>User</th><th>Amount</th><th>Actions</th>
        </tr></thead>
        <tbody>
        @forelse($orders as $order)
            <tr>
                <td><strong>{{ $order->enquiry_number ?: '—' }}</strong></td>
                <td>{{ $order->customer_id ?: '—' }}</td>
                <td>{{ $order->invoice_number ? 'TCB-'.$order->invoice_number : '—' }}</td>
                <td>{{ data_get($order->billing,'name') ?: '—' }}</td>
                <td><span class="or-paid {{ $order->invoice_status }}">#{{ strtoupper($order->invoice_status) }}</span></td>
                <td>{{ optional($order->invoice_date)->format('d-F-Y') ?: $order->created_at->format('d-F-Y') }}</td>
                <td>{{ $order->user_name ?: optional($order->creator)->name }}</td>
                <td><strong>{{ $order->currency }} {{ number_format($order->total,2) }}</strong></td>
                <td>
                    <div class="or-acts">
                        <form method="POST" action="{{ route('crm.orders.destroy',$order->id) }}" onsubmit="return confirm('Delete this order?')">
                            {{ csrf_field() }}<input type="hidden" name="_method" value="DELETE">
                            <button class="or-act del" type="submit" style="width:100%"><i class="fas fa-trash-alt"></i> Delete</button>
                        </form>
                        <a class="or-act edit" href="{{ route('crm.orders.edit',$order->id) }}"><i class="fas fa-pen"></i> Edit</a>
                        <a class="or-act" href="{{ route('crm.orders.pdf',$order->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> View PDF</a>
                        <button class="or-act" type="button" onclick="alert('Send Invoice — connect this to your mail flow.')">Send Invoice</button>
                        <button class="or-act" type="button" onclick="alert('PayPal request — connect PayPal to enable.')">Send PayPal Request</button>
                        <button class="or-act" type="button" onclick="alert('CCA — connect the gateway to enable.')">Send CCA</button>
                        <button class="or-act" type="button" onclick="alert('Payment — connect the gateway to enable.')">Payment</button>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="or-empty">No orders yet. Click “Create Order”, or use an inquiry’s “Create Order” action after a proposal is completed.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
