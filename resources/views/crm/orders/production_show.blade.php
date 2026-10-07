@extends('crm.layout')
@section('title', 'Production Job ' . $brief->job_number)

@section('content')
@php
    $fields = \App\CrmManualOrderProductionBrief::PRODUCT_FIELDS;
    $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
    $d = fn ($x) => $x ? $x->format('d M Y') : '—';
@endphp
<style>
.ps{font-size:.8rem;color:#0f172a}
.ps-top{display:flex;align-items:center;justify-content:space-between;gap:.8rem;margin-bottom:.8rem;flex-wrap:wrap}
.ps-back{display:inline-flex;align-items:center;gap:.4rem;color:#64748b;text-decoration:none;font-weight:600;font-size:.8rem}
.ps-btn{display:inline-flex;align-items:center;gap:.4rem;height:34px;padding:0 .9rem;border-radius:7px;border:1px solid #d6dde6;background:#fff;color:#334155;font-weight:600;font-size:.78rem;text-decoration:none}
.ps-btn.primary{background:var(--primary-purple);color:#fff;border-color:var(--primary-purple)}
.ps-hero{background:linear-gradient(135deg,var(--primary-purple),#8b7cf6);color:#fff;border-radius:12px;padding:1rem 1.2rem;margin-bottom:.8rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.ps-hero h2{margin:0;font-size:1.15rem}
.ps-hero .sub{font-size:.75rem;color:rgba(255,255,255,.8);margin-top:.2rem}
.ps-hero .chip{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .65rem;border-radius:999px;background:rgba(255,255,255,.14);font-size:.68rem;font-weight:800}
.ps-card{background:#fff;border:1px solid #e3e8ef;border-radius:10px;margin-bottom:.7rem;overflow:hidden}
.ps-head{padding:.55rem .95rem;background:var(--primary-purple);color:#fff;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em}
.ps-body{padding:.85rem .95rem}
.ps-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.6rem .9rem}
.ps-grid.two{grid-template-columns:repeat(2,minmax(0,1fr))}
.ps-k{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#7c8aa0}
.ps-v{font-size:.82rem;color:#0f172a;margin-top:.1rem;white-space:pre-wrap;word-break:break-word}
.ps-v:empty::before{content:'—';color:#cbd5e1}
.ps-prod{border:1px solid #e3e8ef;border-radius:9px;padding:.75rem .85rem;margin-bottom:.6rem;background:#fcfcfd}
.ps-prod h4{margin:0 0 .5rem;font-size:.84rem;color:var(--primary-purple)}
.ps-full{grid-column:1/-1}
@media(max-width:900px){.ps-grid{grid-template-columns:1fr 1fr}}
</style>
<div class="ps">
    <div class="ps-top">
        <a href="{{ route('crm.orders.manual.index') }}" class="ps-back"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        <div style="display:flex;gap:.45rem;flex-wrap:wrap">
            <a class="ps-btn" href="{{ route('crm.orders.manual.production.pdf', $order->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
            <a class="ps-btn" href="{{ route('crm.orders.manual.production.pdf', $order->id) }}?print=1" target="_blank" onclick="var w=window.open(this.href,'_blank');return false;"><i class="fas fa-print"></i> Print</a>
            @if($canEdit)<a class="ps-btn primary" href="{{ route('crm.orders.manual.production.edit', $order->id) }}"><i class="fas fa-pen"></i> Edit (admin)</a>@endif
        </div>
    </div>
    <div class="ps-hero">
        <div><h2><i class="fas fa-industry"></i> Production Job {{ $brief->job_number }}</h2><div class="sub">Sent {{ optional($brief->sent_at)->format('d M Y H:i') }} by {{ optional($brief->sender)->name ?: '—' }} · Order {{ $label }} · {{ strtoupper($order->currency ?: 'USD') }} {{ number_format((float) $order->total, 2) }} · {{ strtoupper($order->invoice_status) }}</div></div>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap"><span class="chip"><i class="fas fa-print"></i> {{ $brief->productionTypeLabel() ?: '—' }}</span><span class="chip"><i class="fas fa-bolt"></i> {{ $brief->job_type ?: '—' }}</span><span class="chip"><i class="fas fa-lock"></i> Locked</span></div>
    </div>

    <div class="ps-card"><div class="ps-head">Job Briefing</div><div class="ps-body"><div class="ps-grid">
        <div><div class="ps-k">Date</div><div class="ps-v">{{ $d($brief->brief_date) }}</div></div>
        <div><div class="ps-k">Job Number</div><div class="ps-v">{{ $brief->job_number }}</div></div>
        <div><div class="ps-k">Production Type</div><div class="ps-v">{{ $brief->productionTypeLabel() }}</div></div>
        <div><div class="ps-k">Job Type</div><div class="ps-v">{{ $brief->job_type }}</div></div>
        <div><div class="ps-k">Client Name</div><div class="ps-v">{{ $brief->client_name }}</div></div>
        <div><div class="ps-k">Enquiry #</div><div class="ps-v">{{ $order->enquiry_number }}</div></div>
        <div><div class="ps-k">Sales Person</div><div class="ps-v">{{ $order->sales_person ?: $order->user_name }}</div></div>
        <div><div class="ps-k">Invoice</div><div class="ps-v">{{ $label }}</div></div>
    </div></div></div>

    <div class="ps-card"><div class="ps-head">Product Description</div><div class="ps-body">
        @foreach(($brief->products ?: []) as $i => $pr)
            <div class="ps-prod">
                <h4>Product {{ $i + 1 }}: {{ $pr['product'] ?? '' }}</h4>
                <div class="ps-grid">
                    @foreach($fields as $k => $lbl)
                        @if($k === 'product') @continue @endif
                        <div class="{{ $k === 'additional_requirements' ? 'ps-full' : '' }}"><div class="ps-k">{{ $lbl }}</div><div class="ps-v">{{ $pr[$k] ?? '' }}</div></div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div></div>

    <div class="ps-card"><div class="ps-head">Files &amp; Deadlines</div><div class="ps-body"><div class="ps-grid">
        <div class="ps-full"><div class="ps-k">Folder Path</div><div class="ps-v">{{ $brief->folder_path }}</div></div>
        <div><div class="ps-k">Job Forwarding Date</div><div class="ps-v">{{ $d($brief->job_forwarding_date) }}</div></div>
        <div><div class="ps-k">Printer's Deadline</div><div class="ps-v">{{ $d($brief->printers_deadline) }}</div></div>
        <div><div class="ps-k">Client's Deadline</div><div class="ps-v">{{ $d($brief->clients_deadline) }}</div></div>
        <div class="ps-full"><div class="ps-k">Notes for production</div><div class="ps-v">{{ $brief->additional_requirements }}</div></div>
    </div></div></div>
</div>
@endsection
