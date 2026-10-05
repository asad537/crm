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
.or-menu{position:relative;display:inline-block}
.or-menu-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .8rem;border-radius:9px;border:none;cursor:pointer;background:#33415a;color:#fff;font-weight:800;font-size:.68rem;text-transform:uppercase;letter-spacing:.02em}
.or-menu-btn:hover{background:#26334a}
.or-menu-list{position:fixed;left:0;top:0;z-index:1000;min-width:185px;max-height:calc(100vh - 16px);overflow-y:auto;background:#fff;border:1px solid #e4eaf1;border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.14);padding:.3rem;display:flex;flex-direction:column;gap:.15rem}
.or-menu-list[hidden]{display:none}
.or-menu-list a,.or-menu-list button{display:flex;align-items:center;gap:.55rem;padding:.55rem .6rem;border-radius:7px;font-size:.76rem;font-weight:700;color:#334155;text-decoration:none;background:none;border:0;width:100%;text-align:left;cursor:pointer}
.or-menu-list a:hover,.or-menu-list button:not(.or-menu-delete):hover{background:var(--primary-soft);color:var(--primary-purple)}
.or-menu-list i{width:16px;text-align:center;color:#94a3b8}
.or-menu-delete{color:#b91c1c}.or-menu-delete:hover{background:#fee2e2}.or-menu-delete:hover i{color:#b91c1c}
.or-menu-list form{margin:0}
.or-empty{text-align:center;padding:2.5rem;color:#94a3b8}
.or-filters{display:flex;flex-wrap:wrap;align-items:center;gap:.7rem;padding:.85rem 1rem;margin-bottom:1rem;background:#fff;border:1px solid #e4eaf1;border-radius:15px;box-shadow:0 8px 26px rgba(15,23,42,.055)}
.or-search{position:relative;flex:1 1 320px;min-width:240px}
.or-search i{position:absolute;left:.95rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.82rem}
.or-search input{width:100%;padding:.7rem .9rem .7rem 2.4rem;border:1px solid #e4eaf1;border-radius:11px;background:#f8fafc;font-size:.8rem;color:#334155;outline:none}
.or-ctrl{display:flex;align-items:center;gap:.4rem;padding:.55rem .85rem;border:1px solid #e4eaf1;border-radius:11px;background:#f8fafc}
.or-ctrl i{color:#94a3b8;font-size:.8rem}
.or-ctrl select,.or-ctrl input{border:none;background:transparent;font-size:.8rem;color:#334155;outline:none;cursor:pointer}
.or-ctrl input[type=date]{cursor:text;min-width:120px}
.or-ctrl .dash{color:#cbd5e1}
.or-search input:focus,.or-ctrl:focus-within{border-color:var(--primary-purple);background:#fff}
.or-fbtn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1rem;border-radius:11px;border:none;cursor:pointer;font-weight:800;font-size:.75rem}
.or-fbtn.apply{background:var(--primary-purple);color:#fff}
.or-fbtn.clear{background:#eef2f7;color:#475569;text-decoration:none}
</style>

<div class="or-hero">
    <div><h2>Orders</h2><div class="or-muted">Manual orders &amp; invoices.</div></div>
    <a href="{{ route('crm.orders.manual.create') }}" class="or-add"><i class="fas fa-plus"></i> Create Order</a>
</div>

<form class="or-filters" method="GET" action="{{ route('crm.orders.manual.index') }}">
    <div class="or-search">
        <i class="fas fa-search"></i>
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by invoice #, customer, or order ID...">
    </div>

    <div class="or-ctrl">
        <select name="status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="paid" {{ ($filters['status'] ?? '')==='paid' ? 'selected' : '' }}>Paid</option>
            <option value="unpaid" {{ ($filters['status'] ?? '')==='unpaid' ? 'selected' : '' }}>Unpaid</option>
        </select>
    </div>

    <div class="or-ctrl">
        <select name="customer" onchange="this.form.submit()">
            <option value="">All Customers</option>
            @foreach($customers as $c)
                <option value="{{ $c }}" {{ ($filters['customer'] ?? '')===$c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
        </select>
    </div>

    <div class="or-ctrl">
        <i class="far fa-calendar"></i>
        <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" aria-label="From date">
        <span class="dash">–</span>
        <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" aria-label="To date">
    </div>

    <button type="submit" class="or-fbtn apply"><i class="fas fa-filter"></i> Apply</button>
    @if(array_filter($filters ?? []))
        <a href="{{ route('crm.orders.manual.index') }}" class="or-fbtn clear">Clear</a>
    @endif
</form>

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
                    <div class="or-menu">
                        <button type="button" class="or-menu-btn" aria-haspopup="true"><i class="fas fa-ellipsis-h"></i> Actions</button>
                        <div class="or-menu-list" hidden>
                            <a href="{{ route('crm.orders.manual.edit',$order->id) }}"><i class="fas fa-pen"></i> Edit</a>
                            <a href="{{ route('crm.orders.manual.pdf',$order->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> View PDF</a>
                            <button type="button" onclick="alert('Send Invoice — connect this to your mail flow.')"><i class="fas fa-paper-plane"></i> Send Invoice</button>
                            <button type="button" onclick="alert('PayPal request — connect PayPal to enable.')"><i class="fab fa-paypal"></i> Send PayPal Request</button>
                            <button type="button" onclick="alert('CCA — connect the gateway to enable.')"><i class="fas fa-credit-card"></i> Send CCA</button>
                            <button type="button" onclick="alert('Payment — connect the gateway to enable.')"><i class="fas fa-money-bill-wave"></i> Payment</button>
                            <form method="POST" action="{{ route('crm.orders.manual.destroy',$order->id) }}" onsubmit="return confirm('Delete this order?')">
                                {{ csrf_field() }}<input type="hidden" name="_method" value="DELETE">
                                <button class="or-menu-delete" type="submit"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="or-empty">No orders yet. Click “Create Order”, or use an inquiry’s “Create Order” action after a proposal is completed.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<script>
(function(){
    function closeMenus(){
        document.querySelectorAll('.or-menu-list').forEach(function(l){ l.setAttribute('hidden',''); });
    }
    // Fixed-position the menu so the table's horizontal scroll container never clips it.
    function openMenu(btn, list){
        var rect = btn.getBoundingClientRect();
        list.style.visibility = 'hidden';
        list.removeAttribute('hidden');
        var w = list.offsetWidth, h = list.offsetHeight;
        var left = Math.max(8, Math.min(rect.right - w, window.innerWidth - w - 8));
        var above = window.innerHeight - rect.bottom < h + 8 && rect.top > h + 8;
        var top = above ? rect.top - h - 4 : rect.bottom + 4;
        list.style.left = left + 'px';
        list.style.top = Math.max(8, Math.min(top, window.innerHeight - h - 8)) + 'px';
        list.style.visibility = '';
    }
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.or-menu-btn');
        if(btn){
            var list = btn.nextElementSibling;
            var isOpen = list && !list.hasAttribute('hidden');
            closeMenus();
            if(list && !isOpen) openMenu(btn, list);
            return;
        }
        if(!e.target.closest('.or-menu')) closeMenus();
    });
    document.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);
})();
</script>
@endsection
