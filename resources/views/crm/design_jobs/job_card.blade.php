@extends('crm.layout')

@section('title', 'Job Card — ' . ($job->job_number ?? ('Design Job #' . $job->id)))

@section('header_actions')
<a class="jc-btn jc-btn-light" href="{{ route('crm.design_jobs.index') }}"><i class="fas fa-arrow-left"></i> Design Jobs</a>
@endsection

@section('content')
@php
    $val = fn($f, $d = null) => old($f, $card->{$f} ?? $d);
    $dv = fn($f) => old($f, optional($card->{$f})->format('Y-m-d'));
@endphp
<style>
.jc-page{max-width:1200px;margin:0 auto}
.jc-hero{display:flex;align-items:center;gap:1rem;margin-bottom:1rem;padding:1.2rem 1.35rem;border:1px solid #e5ebf2;border-radius:17px;background:linear-gradient(135deg,var(--primary-soft),#fff 72%);box-shadow:0 8px 28px rgba(15,23,42,.05)}
.jc-icon{display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:14px;background:var(--primary-purple);color:#fff;font-size:1.1rem}
.jc-hero h1{margin:0;color:#172033;font-size:1.3rem}
.jc-hero p{margin:.25rem 0 0;color:#8290a3;font-size:.78rem}
.jc-flash{margin-bottom:1rem;padding:.75rem 1rem;border:1px solid #bbf7d0;border-radius:10px;background:#f0fdf4;color:#166534;font-size:.8rem;font-weight:700}
.jc-errors{margin-bottom:1rem;padding:.8rem 1rem;border:1px solid #fecaca;border-radius:10px;background:#fff5f5;color:#b91c1c;font-size:.75rem}
.jc-errors ul{margin:.4rem 0 0;padding-left:1.1rem}
.jc-card{margin-bottom:1rem;padding:1.15rem 1.3rem;background:#fff;border:1px solid #e5ebf2;border-radius:16px;box-shadow:0 8px 28px rgba(15,23,42,.05)}
.jc-title{display:flex;align-items:center;gap:.55rem;margin:0 0 1rem;color:#27364b;font-size:.95rem;font-weight:850}
.jc-title i{display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:9px;background:var(--primary-soft);color:var(--primary-purple);font-size:.85rem}
.jc-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:.85rem}
.jc-field{grid-column:span 3;min-width:0}
.jc-2{grid-column:span 2}.jc-4{grid-column:span 4}.jc-6{grid-column:span 6}.jc-12{grid-column:1/-1}
.jc-field label{display:block;margin-bottom:.35rem;color:#425168;font-size:.74rem;font-weight:760}
.jc-control{width:100%;min-height:42px;padding:.58rem .75rem;border:1px solid #d8e1eb;border-radius:10px;background:#fff;color:#263449;font-size:.82rem;outline:0}
.jc-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.jc-control[readonly]{background:#f5f7fa;color:#475569;font-weight:750}
textarea.jc-control{min-height:74px;resize:vertical}
.jc-checks{display:flex;flex-wrap:wrap;gap:.5rem .8rem;align-items:center}
.jc-check{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .75rem;border:1px solid #d8e1eb;border-radius:10px;background:#fbfcfe;color:#334155;font-size:.78rem;font-weight:700;cursor:pointer}
.jc-check input{width:16px;height:16px;accent-color:var(--primary-purple)}
.jc-timestrip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem;margin-top:.9rem;padding:.85rem;border:1px dashed var(--primary-shadow);border-radius:12px;background:var(--primary-soft)}
.jc-timestrip label{color:var(--primary-purple)}
.jc-hidden{display:none!important}
.jc-items{display:grid;gap:.7rem;margin-bottom:.7rem}
.jc-item{padding:.75rem .85rem;border:1px solid #e2e8f0;border-radius:12px;background:#fbfcfe}
.jc-item-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:.55rem}
.jc-item-number{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:8px;background:var(--primary-soft);color:var(--primary-purple);font-weight:800;font-size:.75rem}
.jc-remove{width:32px;height:32px;border:0;border-radius:9px;background:#fff1f2;color:#e11d48;cursor:pointer}
.jc-item .jc-control{min-height:36px;padding:.42rem .6rem;font-size:.8rem}
.jc-item .jc-field label{margin-bottom:.2rem;font-size:.7rem}
.jc-add{display:inline-flex;align-items:center;gap:.45rem;min-height:38px;padding:.5rem .9rem;border:1px solid var(--primary-shadow);border-radius:10px;background:var(--primary-soft);color:var(--primary-purple);font-weight:800;cursor:pointer}
.jc-actions{position:sticky;bottom:0;display:flex;justify-content:flex-end;gap:.65rem;margin-top:1rem;padding:.9rem 1rem;border:1px solid #e5ebf2;border-radius:14px;background:rgba(255,255,255,.96);backdrop-filter:blur(4px);box-shadow:0 -6px 20px rgba(15,23,42,.06)}
.jc-btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;min-height:42px;padding:.6rem 1.1rem;border:0;border-radius:10px;text-decoration:none;font-weight:800;cursor:pointer}
.jc-btn-light{color:#475569;background:#eef2f7}
.jc-btn-primary{color:#fff;background:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}
@media(max-width:820px){.jc-field,.jc-2,.jc-4,.jc-6{grid-column:1/-1}.jc-timestrip{grid-template-columns:repeat(2,1fr)}}
</style>
<div class="jc-page">
<div class="jc-hero"><span class="jc-icon"><i class="fas fa-clipboard-list"></i></span><div><h1>Production Job Card</h1><p>{{ $job->job_number ?? ('Design Job #' . $job->id) }}@if($job->title) · {{ $job->title }}@endif — fill each production stage with times; totals are calculated automatically.</p></div></div>

@if(session('success'))<div class="jc-flash"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if($errors->any())<div class="jc-errors"><strong>Please check the form:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@php
    $me = Auth::guard('crm')->user();
    $canStatus = $me && ($me->isAdmin() || (int) $job->designer_id === (int) $me->id);
    $pct = method_exists($job, 'progressPercent') ? $job->progressPercent() : 0;
@endphp
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-map-signs"></i> Job Status</h4>
    <div class="jc-grid" style="align-items:end">
        <div class="jc-field jc-4">
            <label>Current stage</label>
            @if($canStatus)
            <form method="POST" action="{{ route('crm.design_jobs.status', $job->id) }}">{{ csrf_field() }}
                <select class="jc-control" name="status" onchange="this.form.submit()">
                    @foreach(\App\DesignJob::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ $job->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            @else
                <input class="jc-control" value="{{ $job->statusLabel() }}" readonly>
            @endif
        </div>
        <div class="jc-field jc-4"><label>Designer</label><input class="jc-control" value="{{ $job->designer->name ?? '—' }}" readonly></div>
        <div class="jc-field jc-4"><label>Estimate</label><input class="jc-control" value="{{ optional($job->ticket)->ticket_number ?? ($job->estimate_number ?? '—') }}" readonly></div>
        <div class="jc-field jc-12"><div style="height:8px;border-radius:999px;background:#eef0f2;overflow:hidden"><span style="display:block;height:100%;width:{{ $pct }}%;background:var(--primary-purple)"></span></div></div>
    </div>
</div>

<form id="jcForm" method="POST" action="{{ route('crm.design_jobs.job_card.update', $job->id) }}">{{ csrf_field() }}

{{-- Card 1 — Job header --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-file-signature"></i> Job Header</h4>
    <div class="jc-grid">
        <div class="jc-field"><label>Date</label><input class="jc-control" type="date" name="job_date" value="{{ $dv('job_date') }}"></div>
        <div class="jc-field"><label>Job No <small style="color:#94a3b8">(job number)</small></label><input class="jc-control" name="job_no" value="{{ $val('job_no', $job->job_number) }}"></div>
        <div class="jc-field"><label>Product</label><input class="jc-control" name="product" value="{{ $val('product', $job->title) }}"></div>
        <div class="jc-field"><label>Order Qty</label><input class="jc-control" type="number" min="0" name="order_qty" value="{{ $val('order_qty') }}"></div>
        <div class="jc-field jc-6"><label>Priority</label>
            <div class="jc-checks">
                <label class="jc-check"><input type="checkbox" name="priority_urgent" value="1" {{ $val('priority_urgent') ? 'checked' : '' }}> Urgent</label>
                <label class="jc-check"><input type="checkbox" name="priority_critical" value="1" {{ $val('priority_critical') ? 'checked' : '' }}> Critical</label>
                <label class="jc-check"><input type="checkbox" name="priority_substandard" value="1" {{ $val('priority_substandard') ? 'checked' : '' }}> Sub-standard size</label>
            </div>
        </div>
        <div class="jc-field"><label>Priority Date</label><input class="jc-control" type="date" name="priority_date" value="{{ $dv('priority_date') }}"></div>
    </div>
</div>

{{-- Card 2 — Dummy / Sample approval --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-stamp"></i> Dummy / Sample Approval</h4>
    <div class="jc-grid">
        <div class="jc-field jc-4"><label>Dummy sent on</label><input class="jc-control" type="date" name="dummy_sent_on" value="{{ $dv('dummy_sent_on') }}"></div>
        <div class="jc-field jc-4"><label>Dummy approved on</label><input class="jc-control" type="date" name="dummy_approved_on" value="{{ $dv('dummy_approved_on') }}"></div>
        <div class="jc-field jc-4"><label>Approved by (signature)</label><input class="jc-control" name="dummy_approved_by" value="{{ $val('dummy_approved_by') }}"></div>
    </div>
</div>

{{-- Card 3 — Job briefing --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-ruler-combined"></i> Job Briefing</h4>
    <div class="jc-grid">
        <div class="jc-field jc-2"><label>Box L</label><input class="jc-control" type="number" step="0.01" name="box_l" value="{{ $val('box_l') }}"></div>
        <div class="jc-field jc-2"><label>Box W</label><input class="jc-control" type="number" step="0.01" name="box_w" value="{{ $val('box_w') }}"></div>
        <div class="jc-field jc-2"><label>Box H</label><input class="jc-control" type="number" step="0.01" name="box_h" value="{{ $val('box_h') }}"></div>
        <div class="jc-field jc-2"><label>Unit</label>
            <select class="jc-control" name="box_unit">
                @foreach(['' => 'Select', 'cm' => 'cm', 'inches' => 'inches', 'mm' => 'mm'] as $k => $lbl)
                    <option value="{{ $k }}" {{ (string) $val('box_unit') === (string) $k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="jc-field jc-2"><label>Open L</label><input class="jc-control" type="number" step="0.01" name="open_l" value="{{ $val('open_l') }}"></div>
        <div class="jc-field jc-2"><label>Open W</label><input class="jc-control" type="number" step="0.01" name="open_w" value="{{ $val('open_w') }}"></div>
        <div class="jc-field jc-4"><label>Box type</label>
            <select class="jc-control" name="box_type" data-other-target="jcBoxTypeOther" onchange="jcToggleSelectOther(this)">
                @foreach(['' => 'Select', 'hard' => 'Hard box', 'soft' => 'Soft box', 'other' => 'Other'] as $k => $lbl)
                    <option value="{{ $k }}" {{ (string) $val('box_type') === (string) $k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="jc-field jc-4 {{ $val('box_type') === 'other' ? '' : 'jc-hidden' }}" id="jcBoxTypeOther"><label>Other box type</label><input class="jc-control" name="box_type_other" value="{{ $val('box_type_other') }}"></div>
    </div>
</div>

{{-- Card 4 — Procurement list (repeatable) --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-list-check"></i> Procurement List</h4>
    <div class="jc-items" id="jcMaterials">
        @php($matRows = $materials->count() ? $materials : collect([null]))
        @foreach($matRows as $i => $m)
        <div class="jc-item" data-index="{{ $i }}">
            <div class="jc-item-head"><span class="jc-item-number">{{ $i + 1 }}</span><button class="jc-remove" type="button" onclick="jcRemoveRow(this,'jcMaterials','materials')"><i class="fas fa-trash"></i></button></div>
            <div class="jc-grid">
                <div class="jc-field jc-4"><label>Item</label><input class="jc-control" name="materials[{{ $i }}][item]" value="{{ $m->item ?? '' }}"></div>
                <div class="jc-field jc-4"><label>Specs</label><input class="jc-control" name="materials[{{ $i }}][specs]" value="{{ $m->specs ?? '' }}"></div>
                <div class="jc-field jc-2"><label>Qty</label><input class="jc-control" name="materials[{{ $i }}][qty]" value="{{ $m->qty ?? '' }}"></div>
                <div class="jc-field jc-2"><label>Needed by</label><input class="jc-control" type="date" name="materials[{{ $i }}][needed_by]" value="{{ optional($m->needed_by ?? null)->format('Y-m-d') }}"></div>
                <div class="jc-field jc-12"><label>Remarks</label><input class="jc-control" name="materials[{{ $i }}][remarks]" value="{{ $m->remarks ?? '' }}"></div>
            </div>
        </div>
        @endforeach
    </div>
    <button class="jc-add" type="button" onclick="jcAddRow('jcMaterials','materials')"><i class="fas fa-plus"></i> Add item</button>
</div>

{{-- Card 5 — Paper / Board / Stock (repeatable) --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-layer-group"></i> Paper / Board / Stock</h4>
    <div class="jc-items" id="jcStocks">
        @php($stockRows = $stocks->count() ? $stocks : collect([null]))
        @foreach($stockRows as $i => $s)
        <div class="jc-item" data-index="{{ $i }}">
            <div class="jc-item-head"><span class="jc-item-number">{{ $i + 1 }}</span><button class="jc-remove" type="button" onclick="jcRemoveRow(this,'jcStocks','stocks')"><i class="fas fa-trash"></i></button></div>
            <div class="jc-grid">
                <div class="jc-field jc-4"><label>Material</label><input class="jc-control" name="stocks[{{ $i }}][material]" value="{{ $s->material ?? '' }}"></div>
                <div class="jc-field jc-2"><label>GSM</label><input class="jc-control" name="stocks[{{ $i }}][gsm]" value="{{ $s->gsm ?? '' }}"></div>
                <div class="jc-field jc-2"><label>Sheet L</label><input class="jc-control" type="number" step="0.01" name="stocks[{{ $i }}][sheet_l]" value="{{ $s->sheet_l ?? '' }}"></div>
                <div class="jc-field jc-2"><label>Sheet W</label><input class="jc-control" type="number" step="0.01" name="stocks[{{ $i }}][sheet_w]" value="{{ $s->sheet_w ?? '' }}"></div>
                <div class="jc-field jc-2"><label>Sheet Qty</label><input class="jc-control" name="stocks[{{ $i }}][sheet_qty]" value="{{ $s->sheet_qty ?? '' }}"></div>
                <div class="jc-field jc-3"><label>Wastage</label><input class="jc-control" name="stocks[{{ $i }}][wastage]" value="{{ $s->wastage ?? '' }}"></div>
                <div class="jc-field jc-2"><label>Cutting L</label><input class="jc-control" type="number" step="0.01" name="stocks[{{ $i }}][cutting_l]" value="{{ $s->cutting_l ?? '' }}"></div>
                <div class="jc-field jc-2"><label>Cutting W</label><input class="jc-control" type="number" step="0.01" name="stocks[{{ $i }}][cutting_w]" value="{{ $s->cutting_w ?? '' }}"></div>
                <div class="jc-field jc-3"><label>Total Sheets</label><input class="jc-control" name="stocks[{{ $i }}][total_sheets]" value="{{ $s->total_sheets ?? '' }}"></div>
            </div>
        </div>
        @endforeach
    </div>
    <button class="jc-add" type="button" onclick="jcAddRow('jcStocks','stocks')"><i class="fas fa-plus"></i> Add stock line</button>
</div>

{{-- Card 6 — Printing --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-print"></i> Printing</h4>
    <div class="jc-grid">
        <div class="jc-field jc-4"><label>Method</label>
            <select class="jc-control" name="printing_method" data-other-target="jcPrintOther" onchange="jcToggleSelectOther(this)">
                @foreach(['' => 'Select', 'offset' => 'Offset', 'digital' => 'Digital', 'other' => 'Other'] as $k => $lbl)
                    <option value="{{ $k }}" {{ (string) $val('printing_method') === (string) $k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="jc-field jc-4 {{ $val('printing_method') === 'other' ? '' : 'jc-hidden' }}" id="jcPrintOther"><label>Other method</label><input class="jc-control" name="printing_method_other" value="{{ $val('printing_method_other') }}"></div>
        <div class="jc-field jc-2"><label>CTP Plates</label><input class="jc-control jc-plate" type="number" min="0" name="ctp_plates" value="{{ $val('ctp_plates') }}" oninput="jcSyncPlates()"></div>
        <div class="jc-field jc-2"><label>PMS</label><input class="jc-control" name="pms" value="{{ $val('pms') }}"></div>
        <div class="jc-field jc-12"><label>Coating</label>
            <div class="jc-checks">
                <label class="jc-check"><input type="checkbox" name="coating_uv" value="1" {{ $val('coating_uv') ? 'checked' : '' }}> UV</label>
                <label class="jc-check"><input type="checkbox" name="coating_coating" value="1" {{ $val('coating_coating') ? 'checked' : '' }}> Coating</label>
                <label class="jc-check"><input type="checkbox" name="coating_varnish" value="1" {{ $val('coating_varnish') ? 'checked' : '' }}> Varnish</label>
                <label class="jc-check"><input type="checkbox" name="coating_other" value="1" data-other-target="jcCoatOther" onchange="jcToggleCheckOther(this)" {{ $val('coating_other') ? 'checked' : '' }}> Other</label>
                <div class="{{ $val('coating_other') ? '' : 'jc-hidden' }}" id="jcCoatOther" style="min-width:220px"><input class="jc-control" name="coating_other_text" placeholder="Other coating" value="{{ $val('coating_other_text') }}"></div>
            </div>
        </div>
        <div class="jc-field jc-2"><label>Total Plates <small style="color:#94a3b8">(auto)</small></label><input class="jc-control" id="jcTotalPlates" type="number" min="0" name="total_plates" value="{{ $val('total_plates') }}"></div>
    </div>
    @include('crm.design_jobs.partials.timestrip', ['prefix' => 'printing'])
</div>

{{-- Card 7 — Lamination --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-scroll"></i> Lamination</h4>
    <div class="jc-grid">
        <div class="jc-field jc-12"><label>Type</label>
            <div class="jc-checks">
                <label class="jc-check"><input type="checkbox" name="lam_gloss" value="1" {{ $val('lam_gloss') ? 'checked' : '' }}> Gloss</label>
                <label class="jc-check"><input type="checkbox" name="lam_matte" value="1" {{ $val('lam_matte') ? 'checked' : '' }}> Matte</label>
                <label class="jc-check"><input type="checkbox" name="lam_soft_touch" value="1" {{ $val('lam_soft_touch') ? 'checked' : '' }}> Soft touch</label>
                <label class="jc-check"><input type="checkbox" name="lam_other" value="1" data-other-target="jcLamOther" onchange="jcToggleCheckOther(this)" {{ $val('lam_other') ? 'checked' : '' }}> Other</label>
                <div class="jc-checks {{ $val('lam_other') ? '' : 'jc-hidden' }}" id="jcLamOther">
                    <input class="jc-control" style="width:200px" name="lam_other_text" placeholder="Other type" value="{{ $val('lam_other_text') }}">
                    <input class="jc-control" style="width:110px" type="number" min="0" name="lam_other_qty" placeholder="Qty" value="{{ $val('lam_other_qty') }}">
                </div>
            </div>
        </div>
    </div>
    @include('crm.design_jobs.partials.timestrip', ['prefix' => 'lam'])
</div>

{{-- Card 8 — Screen printing / Spot UV --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-fill-drip"></i> Screen Printing / Spot UV</h4>
    <div class="jc-grid">
        <div class="jc-field jc-3"><label>Colors</label><input class="jc-control" type="number" min="0" name="screen_colors" value="{{ $val('screen_colors') }}"></div>
        <div class="jc-field jc-3" style="display:flex;align-items:flex-end"><label class="jc-check"><input type="checkbox" name="screen_uv" value="1" {{ $val('screen_uv') ? 'checked' : '' }}> Spot UV</label></div>
    </div>
    @include('crm.design_jobs.partials.timestrip', ['prefix' => 'screen'])
</div>

{{-- Card 9 — Foiling --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-certificate"></i> Foiling</h4>
    <div class="jc-grid">
        <div class="jc-field jc-2" style="display:flex;align-items:flex-end"><label class="jc-check"><input type="checkbox" name="foil_gold" value="1" data-other-target="jcFoilGold" onchange="jcToggleCheckOther(this)" {{ $val('foil_gold') ? 'checked' : '' }}> Gold</label></div>
        <div class="jc-field jc-2 {{ $val('foil_gold') ? '' : 'jc-hidden' }}" id="jcFoilGold"><label>Gold shade</label><input class="jc-control" name="foil_gold_shade" value="{{ $val('foil_gold_shade') }}"></div>
        <div class="jc-field jc-2" style="display:flex;align-items:flex-end"><label class="jc-check"><input type="checkbox" name="foil_silver" value="1" data-other-target="jcFoilSilver" onchange="jcToggleCheckOther(this)" {{ $val('foil_silver') ? 'checked' : '' }}> Silver</label></div>
        <div class="jc-field jc-2 {{ $val('foil_silver') ? '' : 'jc-hidden' }}" id="jcFoilSilver"><label>Silver shade</label><input class="jc-control" name="foil_silver_shade" value="{{ $val('foil_silver_shade') }}"></div>
        <div class="jc-field jc-2" style="display:flex;align-items:flex-end"><label class="jc-check"><input type="checkbox" name="foil_other" value="1" data-other-target="jcFoilOther" onchange="jcToggleCheckOther(this)" {{ $val('foil_other') ? 'checked' : '' }}> Other</label></div>
        <div class="jc-field jc-2 {{ $val('foil_other') ? '' : 'jc-hidden' }}" id="jcFoilOther"><label>Other shade</label><input class="jc-control" name="foil_other_shade" value="{{ $val('foil_other_shade') }}"></div>
    </div>
    @include('crm.design_jobs.partials.timestrip', ['prefix' => 'foil'])
</div>

{{-- Card 10 — Corrugation --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-stream"></i> Corrugation</h4>
    <div class="jc-grid">
        <div class="jc-field jc-4"><label>Color</label>
            <select class="jc-control" name="corr_color" data-other-target="jcCorrOther" onchange="jcToggleSelectOther(this)">
                @foreach(['' => 'Select', 'brown' => 'Brown', 'white' => 'White', 'other' => 'Other'] as $k => $lbl)
                    <option value="{{ $k }}" {{ (string) $val('corr_color') === (string) $k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="jc-field jc-4 {{ $val('corr_color') === 'other' ? '' : 'jc-hidden' }}" id="jcCorrOther"><label>Other color</label><input class="jc-control" name="corr_color_other" value="{{ $val('corr_color_other') }}"></div>
        <div class="jc-field jc-4"><label>Ply</label><input class="jc-control" name="corr_ply" placeholder="e.g. 3-ply" value="{{ $val('corr_ply') }}"></div>
    </div>
    @include('crm.design_jobs.partials.timestrip', ['prefix' => 'corr'])
</div>

{{-- Card 11 — Diecutting --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-scissors"></i> Diecutting</h4>
    <div class="jc-grid">
        <div class="jc-field jc-12"><div class="jc-checks">
            <label class="jc-check"><input type="checkbox" name="die_full" value="1" {{ $val('die_full') ? 'checked' : '' }}> Full</label>
            <label class="jc-check"><input type="checkbox" name="die_half" value="1" {{ $val('die_half') ? 'checked' : '' }}> Half</label>
            <label class="jc-check"><input type="checkbox" name="die_embossing" value="1" {{ $val('die_embossing') ? 'checked' : '' }}> Embossing</label>
            <label class="jc-check"><input type="checkbox" name="die_debossing" value="1" {{ $val('die_debossing') ? 'checked' : '' }}> Debossing</label>
        </div></div>
    </div>
    @include('crm.design_jobs.partials.timestrip', ['prefix' => 'die'])
</div>

{{-- Card 12 — Pasting --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-paste"></i> Pasting</h4>
    <div class="jc-grid">
        <div class="jc-field jc-12"><div class="jc-checks">
            <label class="jc-check"><input type="checkbox" name="paste_tape" value="1" {{ $val('paste_tape') ? 'checked' : '' }}> Tape</label>
            <label class="jc-check"><input type="checkbox" name="paste_glue" value="1" {{ $val('paste_glue') ? 'checked' : '' }}> Glue</label>
            <label class="jc-check"><input type="checkbox" name="paste_double" value="1" {{ $val('paste_double') ? 'checked' : '' }}> Double Pasting</label>
            <label class="jc-check"><input type="checkbox" name="paste_pvc_window" value="1" {{ $val('paste_pvc_window') ? 'checked' : '' }}> PVC window</label>
            <label class="jc-check"><input type="checkbox" name="paste_other" value="1" data-other-target="jcPasteOther" onchange="jcToggleCheckOther(this)" {{ $val('paste_other') ? 'checked' : '' }}> Other</label>
            <div class="jc-checks {{ $val('paste_other') ? '' : 'jc-hidden' }}" id="jcPasteOther">
                <input class="jc-control" style="width:200px" name="paste_other_text" placeholder="Other type" value="{{ $val('paste_other_text') }}">
                <input class="jc-control" style="width:110px" type="number" min="0" name="paste_other_qty" placeholder="Qty" value="{{ $val('paste_other_qty') }}">
            </div>
        </div></div>
    </div>
</div>

{{-- Card 13 — Quality check --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-clipboard-check"></i> Quality Check</h4>
    <div class="jc-grid">
        <div class="jc-field jc-4"><label>Result</label>
            <select class="jc-control" name="qc_result" onchange="jcToggleQc()" id="jcQcResult">
                @foreach(['' => 'Select', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $lbl)
                    <option value="{{ $k }}" {{ (string) $val('qc_result') === (string) $k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="jc-field jc-4"><label>Approved by (signature)</label><input class="jc-control" name="qc_approved_by" value="{{ $val('qc_approved_by') }}"></div>
        <div class="jc-field jc-12"><label id="jcQcCommentsLabel">Comments <small style="color:#94a3b8">(required if rejected)</small></label><textarea class="jc-control" name="qc_comments">{{ $val('qc_comments') }}</textarea></div>
    </div>
</div>

{{-- Card 14 — Job timeline --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-clock"></i> Job Timeline</h4>
    <div class="jc-grid">
        <div class="jc-field jc-4"><label>Status</label>
            <select class="jc-control" name="timeline_status" onchange="jcToggleTimeline()" id="jcTimelineStatus">
                @foreach(['' => 'Select', 'on_time' => 'On time', 'delayed' => 'Delayed'] as $k => $lbl)
                    <option value="{{ $k }}" {{ (string) $val('timeline_status') === (string) $k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="jc-field jc-2 {{ $val('timeline_status') === 'delayed' ? '' : 'jc-hidden' }}" id="jcDelayDays"><label>Delay days</label><input class="jc-control" type="number" min="0" name="delay_days" value="{{ $val('delay_days') }}"></div>
        <div class="jc-field jc-6 {{ $val('timeline_status') === 'delayed' ? '' : 'jc-hidden' }}" id="jcDelayReason"><label>Delay reason</label><input class="jc-control" name="delay_reason" value="{{ $val('delay_reason') }}"></div>
    </div>
</div>

<div class="jc-actions">
    <a class="jc-btn jc-btn-light" href="{{ route('crm.design_jobs.index') }}">Cancel</a>
    <button class="jc-btn jc-btn-primary" type="submit"><i class="fas fa-check-circle"></i> Save Job Card</button>
</div>
</form>
</div>
@endsection

@section('scripts')<script>
// Show/hide the "Other" text for a <select> whose value is 'other'.
function jcToggleSelectOther(sel){
    var t=document.getElementById(sel.dataset.otherTarget);if(!t)return;
    var show=sel.value==='other';t.classList.toggle('jc-hidden',!show);
    if(!show)t.querySelectorAll('input').forEach(function(i){i.value=''});
}
// Show/hide a target block tied to a checkbox (Other / Gold / Silver ...).
function jcToggleCheckOther(cb){
    var t=document.getElementById(cb.dataset.otherTarget);if(!t)return;
    t.classList.toggle('jc-hidden',!cb.checked);
    if(!cb.checked)t.querySelectorAll('input').forEach(function(i){i.value=''});
}
// Total plates auto-fills from CTP plates until the user overrides it manually.
function jcSyncPlates(){
    var ctp=document.querySelector('[name="ctp_plates"]'),total=document.getElementById('jcTotalPlates');
    if(!ctp||!total)return;if(total.dataset.touched)return;total.value=ctp.value;
}
document.getElementById('jcTotalPlates').addEventListener('input',function(){this.dataset.touched='1';});
// QC comments become required when result is Rejected.
function jcToggleQc(){
    var r=document.getElementById('jcQcResult'),c=document.querySelector('[name="qc_comments"]'),l=document.getElementById('jcQcCommentsLabel');
    var need=r.value==='rejected';c.required=need;l.style.color=need?'#b91c1c':'';
}
// Timeline delay fields appear only when Delayed.
function jcToggleTimeline(){
    var s=document.getElementById('jcTimelineStatus'),show=s.value==='delayed';
    ['jcDelayDays','jcDelayReason'].forEach(function(id){var el=document.getElementById(id);el.classList.toggle('jc-hidden',!show);var inp=el.querySelector('input');inp.required=show;if(!show)inp.value='';});
}
// Live total time (hr + min) for each timed stage.
function jcCalcTime(prefix){
    var s=document.querySelector('[name="'+prefix+'_start"]'),e=document.querySelector('[name="'+prefix+'_end"]');
    var hr=document.getElementById('jc_'+prefix+'_hr'),min=document.getElementById('jc_'+prefix+'_min');
    if(!s||!e||!hr||!min)return;
    if(!s.value||!e.value){hr.value='';min.value='';return;}
    var sp=s.value.split(':'),ep=e.value.split(':');
    var diff=(parseInt(ep[0],10)*60+parseInt(ep[1],10))-(parseInt(sp[0],10)*60+parseInt(sp[1],10));
    if(diff<0)diff+=24*60;
    hr.value=Math.floor(diff/60);min.value=diff%60;
}
// Repeatable rows: clone the first row, clear its inputs, reindex names.
function jcAddRow(containerId,name){
    var box=document.getElementById(containerId),first=box.querySelector('.jc-item'),clone=first.cloneNode(true);
    clone.querySelectorAll('input,select,textarea').forEach(function(f){if(f.type==='checkbox'||f.type==='radio')f.checked=false;else f.value='';});
    box.appendChild(clone);jcReindexRows(containerId,name);
}
function jcRemoveRow(btn,containerId,name){
    var box=document.getElementById(containerId);
    if(box.querySelectorAll('.jc-item').length===1){btn.closest('.jc-item').querySelectorAll('input,select,textarea').forEach(function(f){f.value='';});return;}
    btn.closest('.jc-item').remove();jcReindexRows(containerId,name);
}
function jcReindexRows(containerId,name){
    document.querySelectorAll('#'+containerId+' .jc-item').forEach(function(card,index){
        card.dataset.index=index;
        var num=card.querySelector('.jc-item-number');if(num)num.textContent=index+1;
        card.querySelectorAll('[name]').forEach(function(f){f.name=f.name.replace(new RegExp(name+'\\[\\d+\\]'),name+'['+index+']');});
    });
}
document.addEventListener('DOMContentLoaded',function(){
    jcToggleQc();jcToggleTimeline();
    ['printing','lam','screen','foil','corr','die'].forEach(function(p){
        var s=document.querySelector('[name="'+p+'_start"]'),e=document.querySelector('[name="'+p+'_end"]');
        if(s)s.addEventListener('input',function(){jcCalcTime(p);});
        if(e)e.addEventListener('input',function(){jcCalcTime(p);});
        jcCalcTime(p);
    });
});
</script>
@endsection
