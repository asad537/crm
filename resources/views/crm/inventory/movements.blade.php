@extends('crm.layout')
@section('title', 'Inventory — '.$item->name)
@section('content')
<style>
.invm-hero{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.15rem 1.35rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px}
.invm-hero h2{margin:0;font-size:1.25rem}.invm-hero p{margin:.2rem 0 0;color:#8290a3;font-size:.78rem}
.invm-back{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem .9rem;background:#eef2f7;color:#475569;border-radius:9px;text-decoration:none;font-weight:800;font-size:.78rem}
.invm-wrap{overflow:auto;background:#fff;border:1px solid #e4eaf1;border-radius:15px}
.invm-table{width:100%;min-width:760px;border-collapse:collapse}
.invm-table th{padding:.8rem 1rem;background:#f7f9fc;border-bottom:2px solid var(--primary-soft);text-align:left;color:#718096;font-size:.65rem;text-transform:uppercase}
.invm-table td{padding:.72rem 1rem;border-bottom:1px solid #edf1f5;font-size:.82rem}
.invm-tag{display:inline-block;padding:.22rem .55rem;border-radius:999px;font-size:.66rem;font-weight:800}
.invm-tag.in{background:#e1f8ef;color:#047857}.invm-tag.out{background:#eef2ff;color:var(--primary-purple)}.invm-tag.adjust{background:#fef3c7;color:#b45309}
</style>
<div class="inv-page">
    <div class="invm-hero">
        <div>
            <a class="invm-back" href="{{ route('crm.inventory.index') }}"><i class="fas fa-arrow-left"></i> Inventory</a>
            <h2 style="margin-top:.6rem">{{ $item->name }}</h2>
            <p>{{ $item->paper_size ? $item->paper_size.' · ' : '' }}{{ $item->gsm ? $item->gsm.'gsm · ' : '' }}{{ $item->stock_type }} — In stock: <strong>{{ rtrim(rtrim(number_format($item->quantity,3,'.',''),'0'),'.') }} {{ $item->unit }}</strong></p>
        </div>
    </div>
    <div class="invm-wrap">
        <table class="invm-table">
            <thead><tr><th>Date</th><th>Type</th><th>Qty</th><th>Balance</th><th>Job</th><th>Reference</th><th>By</th></tr></thead>
            <tbody>
            @forelse($movements as $m)
                <tr>
                    <td style="white-space:nowrap;color:#64748b">{{ $m->created_at->format('d M Y, h:i A') }}</td>
                    <td><span class="invm-tag {{ $m->type }}">{{ $m->type === 'in' ? 'Stock In' : ($m->type === 'out' ? 'Used' : 'Adjust') }}</span></td>
                    <td style="font-weight:800;color:{{ $m->type==='out' ? '#be123c' : '#047857' }}">{{ $m->type==='out' ? '−' : '+' }}{{ rtrim(rtrim(number_format($m->quantity,3,'.',''),'0'),'.') }}</td>
                    <td>{{ rtrim(rtrim(number_format($m->balance_after,3,'.',''),'0'),'.') }}</td>
                    <td>{{ $m->job_number ?: '—' }}</td>
                    <td>{{ $m->reference ?: '—' }}@if($m->note)<div style="color:#94a3b8;font-size:.68rem">{{ $m->note }}</div>@endif</td>
                    <td style="color:#64748b">{{ optional($m->creator)->name ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:#94a3b8">No movements yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())<div style="margin-top:1rem">{{ $movements->links() }}</div>@endif
</div>
@endsection
