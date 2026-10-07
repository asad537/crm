@extends('invoice_portal.layout')
@section('title', 'My Invoices')
@section('styles')
.head{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem}
.head h1{margin:0;font-size:1.45rem;letter-spacing:-.01em}
.head .mut{color:var(--mut);font-size:.84rem}
.tbl{width:100%;border-collapse:collapse}
.tbl th{text-align:left;padding:.8rem 1rem;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#7c8aa0;border-bottom:1px solid var(--b);background:#fafbfd;white-space:nowrap}
.tbl td{padding:.9rem 1rem;border-bottom:1px solid #eef1f5;vertical-align:middle;font-size:.88rem}
.tbl tr:last-child td{border-bottom:none}
.tbl tr:hover td{background:#f8fafc}
.tbl .num{font-weight:700;color:var(--ink)}
.tbl .amt{font-weight:800;white-space:nowrap}
.tbl .acts{display:flex;gap:.45rem;justify-content:flex-end;flex-wrap:wrap}
.empty{padding:3rem;text-align:center;color:var(--mut)}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.8rem;margin-bottom:1rem}
.stat{padding:1rem 1.1rem;display:flex;align-items:center;gap:.9rem}
.stat .ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;background:#eef2f7;color:var(--p)}
.stat.paid .ic{background:#dcfce7;color:#15803d}.stat.due .ic{background:#fee2e2;color:#b91c1c}
.stat .k{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#7c8aa0}
.stat .v{font-size:1.2rem;font-weight:800;margin-top:.15rem}
.stat.due .v{color:#b91c1c}.stat.paid .v{color:#15803d}
@media(max-width:820px){.stats{grid-template-columns:1fr}.tbl thead{display:none}.tbl td{display:block;padding:.4rem 1rem}.tbl tr{display:block;padding:.6rem 0;border-bottom:1px solid var(--b)}.tbl .acts{justify-content:flex-start}}
@endsection
@section('content')
@php
    $cur = optional($orders->first())->currency ?: 'USD';
    $totalDue = $orders->sum(fn ($o) => $o->balanceDue());
    $totalPaid = $orders->sum(fn ($o) => $o->paidAmount());
@endphp
<div class="head">
    <div><h1>My Invoices</h1><div class="mut">Invoices for {{ $account->email }}</div></div>
</div>
<div class="stats">
    <div class="card stat"><div class="ic"><i class="fas fa-file-invoice"></i></div><div><div class="k">Invoices</div><div class="v">{{ $orders->count() }}</div></div></div>
    <div class="card stat paid"><div class="ic"><i class="fas fa-check"></i></div><div><div class="k">Paid</div><div class="v">{{ $cur }} {{ number_format($totalPaid, 2) }}</div></div></div>
    <div class="card stat due"><div class="ic"><i class="fas fa-hourglass-half"></i></div><div><div class="k">Balance due</div><div class="v">{{ $cur }} {{ number_format($totalDue, 2) }}</div></div></div>
</div>
<div class="card" style="overflow:auto">
    <table class="tbl">
        <thead><tr>
            <th>#</th><th>Invoice</th><th>Enquiry / PO</th><th>Billing Name</th><th>Date</th><th>Status</th><th>Sales Rep</th><th style="text-align:right">Amount</th><th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($orders as $i => $o)
            @php $due = $o->balanceDue(); $paid = $o->invoice_status === 'paid' || $due <= 0.009; $partial = !$paid && $o->paidAmount() > 0; @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td class="num"><a href="{{ route('invoice_portal.show', $o->id) }}" style="text-decoration:none;color:var(--ink)">{{ $o->invoice_number ? 'TCB-'.$o->invoice_number : '#'.$o->id }}</a></td>
                <td>{{ $o->enquiry_number ?: '—' }}</td>
                <td>{{ data_get($o->billing,'name') ?: '—' }}</td>
                <td>{{ optional($o->invoice_date)->format('d M Y') ?: $o->created_at->format('d M Y') }}</td>
                <td><span class="badge {{ $paid ? 'paid' : ($partial ? 'partial' : 'unpaid') }}">{{ $paid ? 'Paid' : ($partial ? 'Partially paid' : 'Unpaid') }}</span></td>
                <td>{{ $o->sales_person ?: ($o->user_name ?: '—') }}</td>
                <td class="amt" style="text-align:right">{{ $o->currency }} {{ number_format($o->total, 2) }}@if($partial)<div style="font-size:.72rem;color:#b91c1c;font-weight:600">Due {{ number_format($due, 2) }}</div>@endif</td>
                <td><div class="acts">
                    @if($paid)
                        <a class="btn g sm" href="{{ route('invoice_portal.show', $o->id) }}"><i class="fas fa-check"></i> Paid</a>
                    @else
                        <a class="btn p sm" href="{{ route('invoice_portal.show', $o->id) }}"><i class="fas fa-credit-card"></i> Pay Invoice</a>
                    @endif
                    <a class="btn sm" href="{{ route('invoice_portal.pdf', $o->id) }}"><i class="fas fa-download"></i> Download</a>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="9" class="empty">No invoices yet. When we issue an invoice to {{ $account->email }}, it will appear here.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
