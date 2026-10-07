@extends('crm.layout')
@section('title', 'Order Job Briefs')

@section('content')
<style>
.pi-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.1rem 1.3rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,#fff7ed);border:1px solid #e4eaf1;border-radius:17px}
.pi-hero h2{margin:0;font-size:1.25rem;color:#0f172a}
.pi-muted{color:#8796aa;font-size:.75rem}
.pi-filters{display:flex;gap:.6rem;flex-wrap:wrap;padding:.8rem 1rem;margin-bottom:1rem;background:#fff;border:1px solid #e4eaf1;border-radius:15px}
.pi-filters input,.pi-filters select{height:38px;padding:0 .8rem;border:1px solid #e4eaf1;border-radius:10px;background:#f8fafc;font:inherit;font-size:.8rem}
.pi-filters input{flex:1 1 260px}
.pi-btn{display:inline-flex;align-items:center;gap:.4rem;height:38px;padding:0 1rem;border-radius:10px;border:none;background:var(--primary-purple);color:#fff;font-weight:800;font-size:.75rem;cursor:pointer;text-decoration:none}
.pi-btn.clear{background:#eef2f7;color:#475569}
.pi-wrap{background:#fff;border:1px solid #e4eaf1;border-radius:15px;overflow-x:auto}
.pi-table{width:100%;border-collapse:collapse;min-width:900px}
.pi-table th{padding:.8rem .85rem;background:#f7f9fc;border-bottom:2px solid #fde68a;text-align:left;color:#718096;font-size:.62rem;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
.pi-table td{padding:.8rem .85rem;border-bottom:1px solid #edf1f5;font-size:.78rem;color:#334155;vertical-align:middle}
.pi-table tbody tr:hover{background:#fffbeb}
.pi-chip{display:inline-flex;padding:.25rem .55rem;border-radius:6px;font-size:.64rem;font-weight:800;background:#fef3c7;color:#92400e;text-transform:uppercase}
.pi-chip.rush{background:#fee2e2;color:#b91c1c}
.pi-a{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .65rem;border-radius:8px;border:1px solid #e4eaf1;background:#fff;color:#334155;text-decoration:none;font-weight:700;font-size:.7rem;margin-right:.3rem}
.pi-empty{text-align:center;padding:2.5rem;color:#94a3b8}
</style>
<div class="pi-hero">
    <div><h2><i class="fas fa-industry" style="color:#b45309"></i> Order Job Briefs</h2><div class="pi-muted">Paid orders sent to production from the Orders tab.</div></div>
</div>
<form class="pi-filters" method="GET" action="{{ route('crm.production_briefs.index') }}">
    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search job number, client or product...">
    <select name="type" onchange="this.form.submit()">
        <option value="">All production types</option>
        @foreach(\App\CrmManualOrderProductionBrief::PRODUCTION_TYPES as $k => $lbl)<option value="{{ $k }}" {{ $filters['type'] === $k ? 'selected' : '' }}>{{ $lbl }}</option>@endforeach
    </select>
    <button class="pi-btn" type="submit"><i class="fas fa-filter"></i> Apply</button>
    @if(array_filter($filters))<a class="pi-btn clear" href="{{ route('crm.production_briefs.index') }}">Clear</a>@endif
</form>
<div class="pi-wrap">
    <table class="pi-table">
        <thead><tr><th>Job #</th><th>Client</th><th>Product(s)</th><th>Qty</th><th>Type</th><th>Job Type</th><th>Printer's Deadline</th><th>Client's Deadline</th><th>Sent</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($briefs as $b)
            @php $prods = collect($b->products ?: []); @endphp
            <tr>
                <td><strong>{{ $b->job_number }}</strong><div class="pi-muted">{{ optional($b->order)->enquiry_number }}</div></td>
                <td>{{ $b->client_name }}</td>
                <td>{{ $prods->pluck('product')->filter()->implode(', ') ?: '—' }}</td>
                <td>{{ $prods->pluck('quantity')->filter()->implode(' / ') ?: '—' }}</td>
                <td>{{ $b->productionTypeLabel() ?: '—' }}</td>
                <td><span class="pi-chip {{ strtolower((string) $b->job_type) === 'rush' ? 'rush' : '' }}">{{ $b->job_type ?: '—' }}</span></td>
                <td>{{ optional($b->printers_deadline)->format('d M Y') ?: '—' }}</td>
                <td>{{ optional($b->clients_deadline)->format('d M Y') ?: '—' }}</td>
                <td>{{ optional($b->sent_at)->format('d M Y') }}<div class="pi-muted">{{ optional($b->sender)->name }}</div></td>
                <td style="white-space:nowrap">
                    <a class="pi-a" href="{{ route('crm.orders.manual.production.show', $b->manual_order_id) }}"><i class="fas fa-eye"></i> View</a>
                    <a class="pi-a" href="{{ route('crm.orders.manual.production.pdf', $b->manual_order_id) }}" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="10" class="pi-empty">No orders have been sent to production yet. Paid orders get a "Send to Production" action in the Orders tab.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:.8rem">{{ $briefs->links() }}</div>
@endsection
