@extends('crm.layout')
@section('title', $brief ? 'Edit Production Job' : 'Send to Production')

@section('content')
@php
    $pf = $prefill ?? [];
    $v = function ($key, $default = '') use ($brief, $pf) {
        if (old($key) !== null) return old($key);
        if ($brief) { $val = $brief->{$key}; return $val instanceof \DateTimeInterface ? $val->format('Y-m-d') : ($val ?? $default); }
        return $pf[$key] ?? $default;
    };
    $products = old('products') ?: ($brief ? ($brief->products ?: []) : ($pf['products'] ?? []));
    if (!$products) $products = [array_fill_keys(array_keys(\App\CrmManualOrderProductionBrief::PRODUCT_FIELDS), '')];
    $fields = \App\CrmManualOrderProductionBrief::PRODUCT_FIELDS;
    $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
    $cur = strtoupper($order->currency ?: 'USD');
@endphp
<style>
.pb{--b:#e3e8ef;--ink:#0f172a;--mut:#64748b;--lbl:#7c8aa0;--acc:var(--primary-purple);font-size:.8rem;color:var(--ink)}
.pb-top{display:flex;align-items:center;justify-content:space-between;gap:.8rem;margin-bottom:.7rem;flex-wrap:wrap}
.pb-back{display:inline-flex;align-items:center;gap:.4rem;color:var(--mut);text-decoration:none;font-weight:600;font-size:.8rem}
.pb-back:hover{color:var(--primary-purple)}
.pb-chip{display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .65rem;border-radius:999px;font-size:.68rem;font-weight:800;background:#dcfce7;color:#166534}
.pb-chip.amt{background:var(--primary-soft);color:var(--primary-purple)}
.pb-warn{display:flex;gap:.6rem;align-items:flex-start;padding:.7rem .9rem;border-radius:10px;background:#fff7ed;border:1px solid #fdba74;color:#9a3412;font-size:.78rem;margin-bottom:.75rem;line-height:1.45}
.pb-warn i{margin-top:.15rem}
.pb-card{background:#fff;border:1px solid var(--b);border-radius:10px;box-shadow:0 1px 2px rgba(15,23,42,.04);margin-bottom:.7rem;overflow:hidden}
.pb-head{display:flex;align-items:center;justify-content:space-between;gap:.6rem;padding:.6rem .95rem;border-bottom:1px solid var(--b);background:var(--primary-purple);color:#fff}
.pb-head h3{margin:0;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;display:flex;align-items:center;gap:.45rem}
.pb-head h3 i{color:rgba(255,255,255,.85);font-size:.72rem}
.pb-body{padding:.85rem .95rem}
.pb-g2,.pb-g3,.pb-g4{display:grid;gap:.55rem .75rem}
.pb-g2{grid-template-columns:repeat(2,minmax(0,1fr))}.pb-g3{grid-template-columns:repeat(3,minmax(0,1fr))}.pb-g4{grid-template-columns:repeat(4,minmax(0,1fr))}
.pb-f{display:flex;flex-direction:column;gap:.2rem;min-width:0}
.pb-f.full{grid-column:1/-1}.pb-f.s2{grid-column:span 2}
.pb-l{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--lbl)}
.pb-l .req{color:#dc2626}
.pb-i,.pb-sel,.pb-ta{width:100%;height:34px;padding:0 .6rem;border:1px solid #d6dde6;border-radius:7px;background:#fff;font:inherit;font-size:.8rem;color:var(--ink);outline:0;box-sizing:border-box}
.pb-sel{appearance:none;-webkit-appearance:none;padding-right:1.7rem;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path d='M1 1l4 4 4-4' fill='none' stroke='%2364748b' stroke-width='1.5'/></svg>");background-repeat:no-repeat;background-position:right .6rem center;cursor:pointer}
.pb-ta{height:auto;min-height:64px;padding:.45rem .6rem;resize:vertical}
.pb-i:focus,.pb-sel:focus,.pb-ta:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.pb-i[readonly]{background:#f8fafc;color:#475569}
.pb-prod{border:1px solid var(--b);border-radius:9px;padding:.7rem .8rem .8rem;margin-bottom:.6rem;background:#fcfcfd;position:relative}
.pb-prod-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem}
.pb-prod-no{font-size:.64rem;font-weight:800;color:var(--acc);text-transform:uppercase;letter-spacing:.06em}
.pb-rm{width:24px;height:24px;border:none;border-radius:6px;background:#fef2f2;color:#dc2626;cursor:pointer;font-size:.7rem;display:inline-flex;align-items:center;justify-content:center}
.pb-btn{display:inline-flex;align-items:center;gap:.4rem;height:34px;padding:0 .9rem;border-radius:7px;border:1px solid #d6dde6;background:#fff;color:#334155;font-weight:600;font-size:.78rem;cursor:pointer;text-decoration:none;white-space:nowrap}
.pb-btn.primary{background:var(--acc);color:#fff;border-color:var(--acc)}
.pb-btn.primary:hover{filter:brightness(.95)}
.pb-btn.ghost{border-style:dashed;color:var(--mut)}
.pb-head .pb-btn.ghost{background:transparent;color:#fff;border-color:rgba(255,255,255,.5)}
.pb-actions{position:sticky;bottom:0;z-index:5;display:flex;align-items:center;gap:.55rem;padding:.65rem .95rem;background:rgba(255,255,255,.94);backdrop-filter:blur(6px);border:1px solid var(--b);border-radius:10px;box-shadow:0 -4px 18px rgba(15,23,42,.06)}
.pb-actions .spacer{flex:1}
.pb-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:8px;padding:.55rem .85rem;margin-bottom:.7rem;font-size:.8rem}
.pb-order{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.5rem .9rem;font-size:.76rem;color:#475569}
.pb-order b{display:block;color:var(--ink);font-size:.8rem}
@media(max-width:1000px){.pb-g4{grid-template-columns:repeat(2,minmax(0,1fr))}.pb-order{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.pb-g2,.pb-g3,.pb-g4,.pb-order{grid-template-columns:1fr}.pb-f.s2{grid-column:auto}}
</style>

<div class="pb">
    <div class="pb-top">
        <a href="{{ $brief ? route('crm.orders.manual.production.show', $order->id) : route('crm.orders.manual.index') }}" class="pb-back"><i class="fas fa-arrow-left"></i> {{ $brief ? 'Back to production job' : 'Back to Orders' }}</a>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
            <span class="pb-chip"><i class="fas fa-check-circle"></i> {{ strtoupper($order->invoice_status) }}</span>
            <span class="pb-chip amt"><i class="fas fa-file-invoice"></i> {{ $label }} · {{ $cur }} {{ number_format((float) $order->total, 2) }}</span>
        </div>
    </div>
    @if($errors->any())<div class="pb-err">{{ $errors->first() }}</div>@endif
    @if(!$brief)
        <div class="pb-warn"><i class="fas fa-exclamation-triangle"></i><div><strong>Please fill this form carefully.</strong> Once sent, the job briefing is locked and goes to the production team. Only an admin can change it afterwards.</div></div>
    @endif

    <form method="POST" action="{{ $brief ? route('crm.orders.manual.production.update', $order->id) : route('crm.orders.manual.production.store', $order->id) }}" id="pbForm">
        {{ csrf_field() }}

        <div class="pb-card">
            <div class="pb-head"><h3><i class="fas fa-clipboard-list"></i> Job Briefing</h3></div>
            <div class="pb-body">
                <div class="pb-g4">
                    <div class="pb-f"><span class="pb-l">Date <span class="req">*</span></span><input class="pb-i" type="date" name="brief_date" value="{{ $v('brief_date', now()->format('Y-m-d')) }}" required></div>
                    <div class="pb-f"><span class="pb-l">Job Number <span class="req">*</span></span><input class="pb-i" name="job_number" value="{{ $v('job_number') }}" required></div>
                    <div class="pb-f">
                        <span class="pb-l">Production Type <span class="req">*</span></span>
                        <select class="pb-sel" name="production_type" required>
                            <option value="">Select production type</option>
                            @foreach(\App\CrmManualOrderProductionBrief::PRODUCTION_TYPES as $k => $lbl)<option value="{{ $k }}" {{ $v('production_type') === $k ? 'selected' : '' }}>{{ $lbl }}</option>@endforeach
                        </select>
                    </div>
                    <div class="pb-f">
                        <span class="pb-l">Job Type <span class="req">*</span></span>
                        <select class="pb-sel" name="job_type" required>
                            @foreach(\App\CrmManualOrderProductionBrief::JOB_TYPES as $jt)<option value="{{ $jt }}" {{ ($v('job_type') ?: 'Standard') === $jt ? 'selected' : '' }}>{{ $jt }}</option>@endforeach
                        </select>
                    </div>
                    <div class="pb-f s2"><span class="pb-l">Client Name <span class="req">*</span></span><input class="pb-i" name="client_name" value="{{ $v('client_name') }}" required></div>
                    <div class="pb-f s2"><span class="pb-l">Order reference</span><input class="pb-i" value="{{ $label }} · Enquiry {{ $order->enquiry_number ?: '—' }} · Sales: {{ $order->sales_person ?: ($order->user_name ?: '—') }}" readonly></div>
                </div>
            </div>
        </div>

        <div class="pb-card">
            <div class="pb-head">
                <h3><i class="fas fa-box-open"></i> Product Description</h3>
                <button type="button" class="pb-btn ghost" onclick="pbAddProduct()"><i class="fas fa-plus"></i> Add product</button>
            </div>
            <div class="pb-body">
                <div id="pbProducts">
                    @foreach($products as $i => $pr)
                        <div class="pb-prod">
                            <div class="pb-prod-bar">
                                <span class="pb-prod-no">Product <span class="pb-idx">{{ $i + 1 }}</span>@if(!empty($pr['product'])) · <span style="color:#334155;text-transform:none;letter-spacing:0">{{ $pr['product'] }}</span>@endif</span>
                                <button type="button" class="pb-rm" title="Remove" onclick="pbRemove(this)"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="pb-g2">
                                <div class="pb-f"><span class="pb-l">Product <span class="req">*</span></span><input class="pb-i" name="products[{{ $i }}][product]" value="{{ $pr['product'] ?? '' }}" required></div>
                                <div class="pb-f"><span class="pb-l">Material</span><input class="pb-i" name="products[{{ $i }}][material]" value="{{ $pr['material'] ?? '' }}" placeholder="e.g. 18pt white card stock"></div>
                                <div class="pb-f"><span class="pb-l">Quantity</span><input class="pb-i" name="products[{{ $i }}][quantity]" value="{{ $pr['quantity'] ?? '' }}"></div>
                                <div class="pb-f"><span class="pb-l">Finish Size</span><input class="pb-i" name="products[{{ $i }}][finish_size]" value="{{ $pr['finish_size'] ?? '' }}" placeholder="L x W x H"></div>
                                <div class="pb-f"><span class="pb-l">Lamination</span><input class="pb-i" name="products[{{ $i }}][lamination]" value="{{ $pr['lamination'] ?? '' }}"></div>
                                <div class="pb-f"><span class="pb-l">Printing</span><input class="pb-i" name="products[{{ $i }}][printing]" value="{{ $pr['printing'] ?? '' }}" placeholder="e.g. CMYK 4/0"></div>
                                <div class="pb-f"><span class="pb-l">Diecut Window</span><input class="pb-i" name="products[{{ $i }}][diecut_window]" value="{{ $pr['diecut_window'] ?? '' }}"></div>
                                <div class="pb-f"><span class="pb-l">Plastic Film</span><input class="pb-i" name="products[{{ $i }}][plastic_film]" value="{{ $pr['plastic_film'] ?? '' }}"></div>
                                <div class="pb-f"><span class="pb-l">Pasting</span><input class="pb-i" name="products[{{ $i }}][pasting]" value="{{ $pr['pasting'] ?? '' }}"></div>
                                <div class="pb-f"><span class="pb-l">Spot UV</span><input class="pb-i" name="products[{{ $i }}][spot_uv]" value="{{ $pr['spot_uv'] ?? '' }}"></div>
                                <div class="pb-f"><span class="pb-l">Deboss / Emboss</span><input class="pb-i" name="products[{{ $i }}][deboss_emboss]" value="{{ $pr['deboss_emboss'] ?? '' }}"></div>
                                <div class="pb-f"><span class="pb-l">Raised Ink / Foiling</span><input class="pb-i" name="products[{{ $i }}][raised_ink_foiling]" value="{{ $pr['raised_ink_foiling'] ?? '' }}"></div>
                                <div class="pb-f full"><span class="pb-l">Additional Requirements</span><textarea class="pb-ta" name="products[{{ $i }}][additional_requirements]">{{ $pr['additional_requirements'] ?? '' }}</textarea></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="pb-card">
            <div class="pb-head"><h3><i class="fas fa-calendar-check"></i> Files &amp; Deadlines</h3></div>
            <div class="pb-body">
                <div class="pb-g4">
                    <div class="pb-f s2"><span class="pb-l">Folder Path</span><input class="pb-i" name="folder_path" value="{{ $v('folder_path') }}" placeholder="\\server\jobs\TCB-0001"></div>
                    <div class="pb-f"><span class="pb-l">Job Forwarding Date</span><input class="pb-i" type="date" name="job_forwarding_date" value="{{ $v('job_forwarding_date') }}"></div>
                    <div class="pb-f"><span class="pb-l">Printer's Deadline</span><input class="pb-i" type="date" name="printers_deadline" value="{{ $v('printers_deadline') }}"></div>
                    <div class="pb-f"><span class="pb-l">Client's Deadline</span><input class="pb-i" type="date" name="clients_deadline" value="{{ $v('clients_deadline') }}"></div>
                    <div class="pb-f full"><span class="pb-l">Notes for production</span><textarea class="pb-ta" name="additional_requirements">{{ $v('additional_requirements') }}</textarea></div>
                </div>
            </div>
        </div>

        <div class="pb-actions">
            <a href="{{ $brief ? route('crm.orders.manual.production.show', $order->id) : route('crm.orders.manual.index') }}" class="pb-btn">Cancel</a>
            <span class="spacer"></span>
            <button type="submit" class="pb-btn primary" id="pbSubmit"><i class="fas fa-industry"></i> {{ $brief ? 'Update Production Job' : 'Send to Production' }}</button>
        </div>
    </form>
</div>

<script>
    function pbRenumber(){ document.querySelectorAll('#pbProducts .pb-prod').forEach(function(p,i){ var n=p.querySelector('.pb-idx'); if(n) n.textContent=i+1; }); }
    function pbAddProduct(){
        var wrap = document.getElementById('pbProducts'); var first = wrap.querySelector('.pb-prod'); var clone = first.cloneNode(true);
        var idx = wrap.querySelectorAll('.pb-prod').length;
        clone.querySelectorAll('input,textarea').forEach(function(el){ el.name = el.name.replace(/products\[\d+\]/, 'products['+idx+']'); el.value=''; });
        var no = clone.querySelector('.pb-prod-no'); if (no) no.innerHTML = 'Product <span class="pb-idx"></span>';
        wrap.appendChild(clone); pbRenumber(); clone.querySelector('input').focus();
    }
    function pbRemove(btn){ var wrap = document.getElementById('pbProducts'); if (wrap.querySelectorAll('.pb-prod').length <= 1) return; btn.closest('.pb-prod').remove(); pbRenumber(); }
    document.getElementById('pbForm').addEventListener('submit', function(e){
        @if(!$brief)
        if (!confirm('Send this job to production? The briefing will be locked after sending.')) { e.preventDefault(); return; }
        @endif
        var b = document.getElementById('pbSubmit'); b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
    });
</script>
@endsection
