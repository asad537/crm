@extends('crm.layout')

@section('title', $job->exists ? 'Job Card — ' . $job->job_number : 'New Job Card')

@section('header_actions')
<a class="jc-btn jc-btn-light" href="{{ route('crm.design_jobs.index') }}"><i class="fas fa-arrow-left"></i> Design Jobs</a>
@endsection

@section('content')
@php
    $val = fn($f, $d = null) => old($f, $card->{$f} ?? $d);
    $dv = fn($f) => old($f, optional($card->{$f})->format('Y-m-d'));
    $jobDateDefault = optional($card->job_date)->format('Y-m-d') ?: optional($job->created_at)->format('Y-m-d') ?: now()->format('Y-m-d');
    $priorityLevel = old('priority_level', $card->priority_critical ? 'critical' : ($card->priority_urgent ? 'urgent' : 'regular'));
@endphp
<style>
.jc-page{max-width:1200px;margin:0 auto;color:#263449}
.jc-page:not(.jc-ready) #jcForm>.jc-card,.jc-page:not(.jc-ready) #jcForm>.jc-actions{display:none}
#jcForm{counter-reset:jc-section}
.jc-hero{display:flex;align-items:center;gap:1rem;margin-bottom:1rem;padding:1.35rem 1.5rem;border:1px solid #dce5ee;border-radius:16px;background:linear-gradient(120deg,var(--primary-soft),#fff 58%);box-shadow:0 6px 20px rgba(15,23,42,.04)}
.jc-icon{display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:14px;background:var(--primary-purple);color:#fff;font-size:1.1rem}
.jc-hero h1{margin:0;color:#172033;font-size:1.35rem;letter-spacing:-.025em}
.jc-hero p{margin:.3rem 0 0;color:#65758b;font-size:.82rem}
.jc-print-note{display:none}
.jc-flash{margin-bottom:1rem;padding:.75rem 1rem;border:1px solid #bbf7d0;border-radius:10px;background:#f0fdf4;color:#166534;font-size:.8rem;font-weight:700}
.jc-errors{margin-bottom:1rem;padding:.8rem 1rem;border:1px solid #fecaca;border-radius:10px;background:#fff5f5;color:#b91c1c;font-size:.75rem}
.jc-errors ul{margin:.4rem 0 0;padding-left:1.1rem}
.jc-card{counter-increment:jc-section;margin-bottom:.75rem;padding:1rem 1.15rem;background:#fff;border:1px solid #dce5ee;border-radius:13px;box-shadow:0 4px 16px rgba(15,23,42,.035)}
.jc-title{display:flex;align-items:center;gap:.6rem;margin:0 0 1rem;color:#1e293b;font-size:1rem;font-weight:850;letter-spacing:-.01em}
.jc-title::before{content:counter(jc-section, decimal-leading-zero);display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;border:1px solid var(--primary-shadow);border-radius:8px;background:var(--primary-soft);color:var(--primary-purple);font-size:.72rem;font-weight:900}
.jc-title i{display:none}
.jc-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:1rem .9rem}
.jc-field{grid-column:span 3;min-width:0}
.jc-2{grid-column:span 2}.jc-4{grid-column:span 4}.jc-6{grid-column:span 6}.jc-8{grid-column:span 8}.jc-12{grid-column:1/-1}
.jc-field label{display:block;margin-bottom:.4rem;color:#405069;font-size:.76rem;font-weight:760}
.jc-control{width:100%;min-height:42px;padding:.58rem .75rem;border:1px solid #cfd9e5;border-radius:8px;background:#fff;color:#263449;font-size:.84rem;outline:0}
.jc-print-value{display:none}
.jc-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.jc-control[readonly]{background:#f5f7fa;color:#475569;font-weight:750}
textarea.jc-control{min-height:74px;resize:vertical}
.jc-checks{display:flex;flex-wrap:wrap;gap:.5rem .8rem;align-items:center}
.jc-check{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .7rem;border:1px solid #d8e1eb;border-radius:8px;background:#fff;color:#334155;font-size:.78rem;font-weight:700;cursor:pointer;transition:border-color .15s,background .15s}
.jc-check:hover{border-color:var(--primary-purple)}
.jc-check:has(input:checked){border-color:var(--primary-purple);background:var(--primary-soft);color:var(--primary-purple)}
.jc-check input{width:16px;height:16px;accent-color:var(--primary-purple)}
.jc-briefing-layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(0,1fr);gap:.8rem;align-items:stretch}
.jc-briefing-group{min-width:0;padding:1rem;background:#fbfcfe;border:1px solid #e1e8f0;border-radius:11px}
.jc-briefing-panel-heading{display:flex;align-items:center;gap:.65rem;margin-bottom:1rem;padding-bottom:.7rem;border-bottom:1px solid #e6edf4}
.jc-briefing-panel-icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;flex:none;border-radius:8px;background:var(--primary-soft);color:var(--primary-purple);font-size:.8rem}
.jc-briefing-panel-heading strong{display:block;color:#25364b;font-size:.82rem;font-weight:850}
.jc-briefing-panel-heading small{display:block;margin-top:.1rem;color:#8492a6;font-size:.68rem}
.jc-briefing-dimensions{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:1rem}
.jc-briefing-measure+.jc-briefing-measure{border-left:1px solid #e1e8f0;padding-left:1rem}
.jc-briefing-heading{display:block;margin-bottom:.55rem;color:#405069;font-size:.76rem;font-weight:800}
.jc-briefing-specs{display:grid;gap:.85rem}
.jc-briefing-specs-row+.jc-briefing-specs-row{border-top:1px solid #e6edf4;padding-top:.8rem}
.jc-briefing-options{display:flex;flex-wrap:wrap;align-items:center;gap:.4rem}
.jc-briefing-options .jc-check{padding:.38rem .6rem;white-space:nowrap}
.jc-dimension-fields{display:grid;align-items:end;gap:.35rem}
.jc-dim-three{grid-template-columns:minmax(0,1fr) auto minmax(0,1fr) auto minmax(0,1fr)}
.jc-dim-two{grid-template-columns:minmax(0,1fr) auto minmax(0,1fr)}
.jc-dimension-fields .jc-field{grid-column:auto}
.jc-dimension-fields .jc-field label{margin-bottom:.25rem;text-align:center}
.jc-multiply{align-self:end;padding-bottom:.65rem;color:#77869b;font-size:1rem;font-weight:800}
.jc-briefing-other{margin-top:.5rem}
.jc-printing-layout{display:grid;grid-template-columns:1.05fr .95fr;gap:.75rem}
.jc-printing-panel{min-width:0;padding:1rem;border:1px solid #e1e8f0;border-radius:10px;background:#fbfcfe}
.jc-printing-panel-title{margin:0 0 .8rem;color:#27364b;font-size:.8rem;font-weight:850}
.jc-printing-plates{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.7rem}
.jc-printing-plates .jc-field{grid-column:auto}
.jc-printing-coating{grid-column:1/-1}
.jc-printing-other{margin-top:.75rem;max-width:320px}
.jc-foil-card,.jc-corr-card{padding-bottom:1rem}
.jc-foil-card .jc-step-head,.jc-corr-card .jc-step-head{padding-bottom:.65rem}
.jc-foil-card .jc-step-body,.jc-corr-card .jc-step-body{margin-top:.75rem}
.jc-foil-options{display:flex;flex-wrap:wrap;align-items:flex-end;gap:.6rem 1rem}
.jc-foil-choice{display:inline-flex;align-items:flex-end;gap:.55rem;min-width:0}
.jc-foil-choice>.jc-check{min-height:42px;margin:0}
.jc-foil-choice .jc-field{grid-column:auto;width:170px}
.jc-corr-layout{display:flex;flex-wrap:wrap;align-items:flex-end;gap:.6rem 1rem}
.jc-corr-colors{min-width:0}
.jc-corr-colors>.jc-briefing-heading{margin-bottom:.45rem}
.jc-corr-layout>.jc-field{grid-column:auto;width:170px}
.jc-foil-card .jc-timestrip,.jc-corr-card .jc-timestrip{margin-top:.65rem;padding:.6rem}
.jc-qc-card .jc-grid,.jc-timeline-card .jc-grid{gap:.7rem .9rem}
.jc-qc-card textarea.jc-control,.jc-timeline-card textarea.jc-control{min-height:64px}
.jc-timestrip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem;margin-top:.75rem;padding:.75rem;border:1px solid #dce5ee;border-radius:9px;background:#f8fafc}
.jc-timestrip label{color:#526174}
.jc-timestrip .jc-control{min-height:36px}
.jc-hidden{display:none!important}
.jc-items{display:grid;gap:.7rem;margin-bottom:.7rem}
.jc-item{padding:.9rem 1rem;border:1px solid #e2e8f0;border-radius:10px;background:#fbfcfe}
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
.jc-step-hidden,.jc-step-body[hidden]{display:none!important}
.jc-step-head{display:flex;align-items:center;justify-content:space-between;gap:.8rem;padding-bottom:.7rem;border-bottom:1px solid #e7edf3}
.jc-step-head .jc-title{margin:0}
.jc-step-choice{display:flex;gap:.45rem}
.jc-step-choice button{border:1px solid #d8e1eb;border-radius:9px;background:#fff;color:#526174;padding:.4rem .85rem;font-weight:800;cursor:pointer}
.jc-step-choice button.is-selected{border-color:var(--primary-purple);background:var(--primary-soft);color:var(--primary-purple)}
.jc-step-body{margin-top:.85rem}
.jc-step-next{margin-top:1rem}
.jc-handwrite[readonly]{background:#fff;color:#263449}
.jc-form-error{display:none;align-self:center;color:#b91c1c;font-size:.75rem;font-weight:800}
.jc-form-error:not(:empty){display:block}
@media print{
  @page{size:A4;margin:11mm}
  body{display:block!important;height:auto!important;overflow:visible!important;background:#fff!important;color:#111!important}
  .custom-sidebar,.sidebar-overlay,.top-bar,.jc-actions,.jc-step-choice,.jc-step-next,.jc-flash,.jc-errors,.jc-add,.jc-remove{display:none!important}
  .main-area{height:auto!important;overflow:visible!important;padding:0!important;width:100%!important}
  .jc-page{max-width:none!important}
  .jc-hero{box-shadow:none!important;background:#fff!important;border:0!important;border-bottom:2px solid #222!important;border-radius:0!important;padding:0 0 4mm!important;margin-bottom:4mm!important}
  .jc-hero .jc-icon{display:none!important}
  .jc-hero h1{font-size:15pt!important;text-transform:uppercase;letter-spacing:.04em}
  .jc-hero p{font-size:8pt!important;color:#444!important}
  .jc-print-note{display:block!important;margin-left:auto;text-align:right;font-size:7pt;line-height:1.35;color:#555}
  #jcForm{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:3mm!important;align-items:start}
  .jc-card{box-shadow:none!important;border:1px solid #8d99a6!important;border-radius:0!important;break-inside:avoid;page-break-inside:avoid;margin:0!important;padding:3mm!important;min-width:0}
  .jc-card:nth-of-type(-n+5){grid-column:1/-1}
  .jc-step-head{padding-bottom:1.5mm!important;margin-bottom:2mm!important;border-bottom:1px solid #aab4be!important}
  .jc-title{font-size:9pt!important;color:#111!important;line-height:1.2}
  .jc-title::before{min-width:20px!important;height:20px!important;border:1px solid #555!important;border-radius:0!important;background:#fff!important;color:#111!important;font-size:7pt!important}
  .jc-title i{display:none!important}
  .jc-step-body{margin:0!important}
  .jc-grid{gap:2.5mm 2mm!important}
  .jc-qc-card .jc-field.jc-6{grid-column:1/-1}
  .jc-field{break-inside:avoid}
  .jc-field label,.jc-timestrip label{font-size:7pt!important;margin-bottom:.8mm!important;color:#333!important}
  .jc-field > .jc-control{display:none!important}
  .jc-print-value{display:block!important;min-height:6mm!important;border-bottom:1px solid #777!important;padding:.7mm 0!important;color:#111!important;font-size:8pt!important;line-height:1.35;overflow-wrap:anywhere}
  .jc-print-value-multiline{min-height:16mm!important;border:1px solid #777!important;padding:1.5mm!important;white-space:pre-wrap}
  .jc-control{border:0!important;border-bottom:1px solid #777!important;border-radius:0!important;background:#fff!important;color:#111!important;box-shadow:none!important;min-height:6mm!important;padding:.5mm 0!important;font-size:8pt!important;appearance:none!important;-webkit-appearance:none!important}
  .jc-control::-webkit-calendar-picker-indicator{display:none!important}
  .jc-control[readonly]{background:#fff!important}
  .jc-checks{gap:1mm 2.5mm!important}
  .jc-check,.jc-check:has(input:checked){border:0!important;border-radius:0!important;background:#fff!important;padding:.5mm 0!important;font-size:8pt!important;color:#111!important}
  .jc-check input{width:12px!important;height:12px!important;accent-color:#111!important}
  .jc-briefing-layout{grid-template-columns:1.35fr 1fr!important;gap:2mm!important}
  .jc-briefing-group{padding:1.5mm!important;border:1px solid #b7bec7!important;border-radius:0!important;background:#fff!important}
  .jc-briefing-panel-heading{margin-bottom:1.5mm!important;padding-bottom:1mm!important;border-bottom:1px solid #bbb!important}
  .jc-briefing-panel-icon,.jc-briefing-panel-heading small{display:none!important}
  .jc-briefing-panel-heading strong{font-size:8pt!important;color:#111!important}
  .jc-briefing-dimensions{gap:2mm!important}
  .jc-briefing-measure+.jc-briefing-measure{padding-left:2mm!important}
  .jc-briefing-specs{gap:1mm!important}
  .jc-briefing-specs-row+.jc-briefing-specs-row{padding-top:1mm!important}
  .jc-briefing-heading{margin-bottom:1mm!important;font-size:8pt!important;color:#111!important}
  .jc-dimension-fields{gap:1mm!important}
  .jc-multiply{padding-bottom:1mm!important;font-size:8pt!important}
  .jc-briefing-options{gap:0!important}
  .jc-briefing-options .jc-check{padding:.3mm 0!important}
  .jc-printing-layout{grid-template-columns:1.05fr .95fr!important;gap:2mm!important}
  .jc-printing-panel{padding:2mm!important;border:1px solid #b7bec7!important;border-radius:0!important;background:#fff!important}
  .jc-printing-panel-title{margin-bottom:1.5mm!important;color:#111!important;font-size:8pt!important}
  .jc-printing-plates{gap:2mm!important}
  .jc-printing-other{margin-top:1mm!important}
  .jc-foil-options{gap:1mm 3mm!important}
  .jc-foil-choice{gap:1.5mm!important}
  .jc-foil-choice>.jc-check{min-height:0!important}
  .jc-foil-choice .jc-field{width:25mm!important}
  .jc-corr-layout{gap:1mm 3mm!important}
  .jc-corr-colors>.jc-briefing-heading{margin-bottom:1mm!important}
  .jc-corr-layout>.jc-field{width:25mm!important}
  .jc-foil-card .jc-timestrip,.jc-corr-card .jc-timestrip{margin-top:2mm!important;padding:2mm!important}
  .jc-item{border:1px solid #b7bec7!important;border-radius:0!important;background:#fff!important;padding:2mm!important}
  .jc-item-head{margin-bottom:1mm!important}
  .jc-item-number{min-width:18px!important;height:18px!important;border-radius:0!important;background:#fff!important;border:1px solid #888!important;color:#111!important}
  .jc-timestrip{gap:1.5mm!important;margin-top:2.5mm!important;padding:2mm!important;border:1px solid #aab4be!important;border-radius:0!important;background:#fff!important}
  .jc-timestrip .jc-control{border:1px solid #777!important;min-height:7mm!important;padding:1mm!important}
  textarea.jc-control{border:1px solid #777!important;min-height:16mm!important;padding:1.5mm!important;resize:none!important}
  .jc-step-hidden{display:none!important}
  .jc-step[data-choice="no"]{display:none!important}
  .jc-step[data-choice="yes"] .jc-step-body,.jc-header-step .jc-step-body{display:block!important}
}
@media screen and (max-width:900px){.jc-briefing-layout{grid-template-columns:1fr}}
@media screen and (max-width:700px){.jc-printing-layout{grid-template-columns:1fr}.jc-printing-plates{grid-template-columns:repeat(2,minmax(0,1fr))}.jc-printing-coating{grid-column:auto}}
@media screen and (max-width:600px){.jc-briefing-dimensions{grid-template-columns:1fr}.jc-briefing-measure+.jc-briefing-measure{border-left:0;border-top:1px solid #e1e8f0;padding:1rem 0 0}}
@media screen and (max-width:820px){.jc-field,.jc-2,.jc-4,.jc-6,.jc-8{grid-column:1/-1}.jc-dimension-fields .jc-field,.jc-printing-plates .jc-field{grid-column:auto}.jc-timestrip{grid-template-columns:repeat(2,1fr)}}
</style>
<div class="jc-page">
<div class="jc-hero"><span class="jc-icon"><i class="fas fa-clipboard-list"></i></span><div><h1>Production Job Card</h1><p>@if($job->exists){{ $job->job_number }} · {{ $job->title }}@else Fill the card and save to create the job and its number.@endif</p></div><div class="jc-print-note">PRODUCTION COPY<br>Complete time fields by hand</div></div>

@if(session('success'))<div class="jc-flash"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
@if($errors->any())<div class="jc-errors"><strong>Please check the form:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<form id="jcForm" method="POST" novalidate action="{{ $job->exists ? route('crm.design_jobs.job_card.update', $job->id) : route('crm.design_jobs.store') }}">{{ csrf_field() }}
<input type="hidden" name="wizard_completed_step" value="{{ old('wizard_completed_step', ($card->section_choices ?? [])['__completed_step'] ?? ($job->exists ? 12 : -1)) }}">
<input type="hidden" name="wizard_current_step" value="{{ old('wizard_current_step', ($card->section_choices ?? [])['__active_step'] ?? 0) }}">

{{-- Card 1 — Job header --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-file-signature"></i> Job Header</h4>
    <div class="jc-grid">
        <div class="jc-field"><label>Job Assigned Date</label><input class="jc-control" type="date" name="job_date" value="{{ old('job_date', $jobDateDefault) }}"></div>
        <div class="jc-field"><label>Job No #</label><input class="jc-control" name="job_no" value="{{ $val('job_no', $job->job_number) }}" placeholder="{{ $job->exists ? '' : 'Auto-generated when saved' }}"></div>
        <div class="jc-field"><label>Product @unless($job->exists)<span style="color:#e11d48">*</span>@endunless</label><input class="jc-control" name="product" value="{{ $val('product', $job->title) }}" {{ $job->exists ? '' : 'required' }}></div>
        <div class="jc-field"><label>Order Qty</label><input class="jc-control" type="number" min="0" name="order_qty" value="{{ $val('order_qty') }}"></div>
        <div class="jc-field jc-4"><label>Priority</label><select class="jc-control" name="priority_level">
            @foreach(['regular' => 'Regular', 'urgent' => 'Urgent', 'critical' => 'Critical'] as $key => $label)
                <option value="{{ $key }}" {{ $priorityLevel === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select></div>
        <div class="jc-field jc-4"><label>Job Start On</label><input class="jc-control" type="date" name="job_start_on" value="{{ $dv('job_start_on') }}"></div>
        <div class="jc-field jc-4"><label>Job Deadline</label><input class="jc-control" type="date" name="due_date" value="{{ old('due_date', optional($job->due_date)->format('Y-m-d')) }}"></div>
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
    <div class="jc-briefing-layout">
        <div class="jc-briefing-group">
            <div class="jc-briefing-panel-heading"><span class="jc-briefing-panel-icon"><i class="fas fa-ruler-combined"></i></span><div><strong>Dimensions</strong><small>Finished box and open size</small></div></div>
            <div class="jc-briefing-dimensions">
                <div class="jc-briefing-measure"><span class="jc-briefing-heading">Box Size</span>
                    <div class="jc-dimension-fields jc-dim-three">
                        <div class="jc-field"><label>L</label><input class="jc-control" type="number" step="0.01" name="box_l" value="{{ $val('box_l') }}"></div>
                        <span class="jc-multiply" aria-hidden="true">×</span>
                        <div class="jc-field"><label>W</label><input class="jc-control" type="number" step="0.01" name="box_w" value="{{ $val('box_w') }}"></div>
                        <span class="jc-multiply" aria-hidden="true">×</span>
                        <div class="jc-field"><label>H</label><input class="jc-control" type="number" step="0.01" name="box_h" value="{{ $val('box_h') }}"></div>
                    </div>
                </div>
                <div class="jc-briefing-measure"><span class="jc-briefing-heading">Open Size</span>
                    <div class="jc-dimension-fields jc-dim-two">
                        <div class="jc-field"><label>L</label><input class="jc-control" type="number" step="0.01" name="open_l" value="{{ $val('open_l') }}"></div>
                        <span class="jc-multiply" aria-hidden="true">×</span>
                        <div class="jc-field"><label>W</label><input class="jc-control" type="number" step="0.01" name="open_w" value="{{ $val('open_w') }}"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="jc-briefing-group">
            <div class="jc-briefing-panel-heading"><span class="jc-briefing-panel-icon"><i class="fas fa-box"></i></span><div><strong>Specifications</strong><small>Measurement unit and box construction</small></div></div>
            <div class="jc-briefing-specs">
                <div class="jc-briefing-specs-row"><span class="jc-briefing-heading">Unit</span><div class="jc-briefing-options">
                    @foreach(['cm' => 'cm', 'inches' => 'inches', 'mm' => 'mm'] as $key => $label)
                        <label class="jc-check"><input type="radio" name="box_unit" value="{{ $key }}" {{ (string) $val('box_unit') === (string) $key ? 'checked' : '' }}> {{ $label }}</label>
                    @endforeach
                </div></div>
                <div class="jc-briefing-specs-row"><span class="jc-briefing-heading">Box Type</span><div class="jc-briefing-options">
                    @foreach(['hard' => 'Hard Box', 'soft' => 'Soft Box', 'other' => 'Other'] as $key => $label)
                        <label class="jc-check"><input type="radio" name="box_type" value="{{ $key }}" data-other-target="jcBoxTypeOther" onchange="jcToggleRadioOther(this)" {{ (string) $val('box_type') === (string) $key ? 'checked' : '' }}> {{ $label }}</label>
                    @endforeach
                </div><div class="jc-field jc-briefing-other {{ $val('box_type') === 'other' ? '' : 'jc-hidden' }}" id="jcBoxTypeOther"><label>Other Box Type</label><input class="jc-control" name="box_type_other" value="{{ $val('box_type_other') }}"></div></div>
            </div>
        </div>
    </div>
</div>

{{-- Card 4 — Paper / Board / Stock (repeatable) --}}
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
        <div class="jc-field jc-2"><label>Sheet Size — L</label><input class="jc-control" type="number" step="0.01" name="stocks[{{ $i }}][sheet_l]" value="{{ $s->sheet_l ?? '' }}"></div>
        <div class="jc-field jc-2"><label>Sheet Size — W</label><input class="jc-control" type="number" step="0.01" name="stocks[{{ $i }}][sheet_w]" value="{{ $s->sheet_w ?? '' }}"></div>
                <div class="jc-field jc-2"><label>Sheet Qty</label><input class="jc-control" name="stocks[{{ $i }}][sheet_qty]" value="{{ $s->sheet_qty ?? '' }}"></div>
                <div class="jc-field jc-3"><label>Wastage</label><input class="jc-control" name="stocks[{{ $i }}][wastage]" value="{{ $s->wastage ?? '' }}"></div>
        <div class="jc-field jc-2"><label>Cutting Size — L</label><input class="jc-control" type="number" step="0.01" name="stocks[{{ $i }}][cutting_l]" value="{{ $s->cutting_l ?? '' }}"></div>
        <div class="jc-field jc-2"><label>Cutting Size — W</label><input class="jc-control" type="number" step="0.01" name="stocks[{{ $i }}][cutting_w]" value="{{ $s->cutting_w ?? '' }}"></div>
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
    <div class="jc-printing-layout">
        <div class="jc-printing-panel"><h5 class="jc-printing-panel-title">Method</h5>
            <div class="jc-checks">
                @foreach(['offset' => 'Offset', 'digital' => 'Digital', 'other' => 'Other'] as $k => $lbl)
                    <label class="jc-check"><input type="radio" name="printing_method" value="{{ $k }}" data-other-target="jcPrintOther" onchange="jcToggleRadioOther(this)" {{ (string) $val('printing_method') === (string) $k ? 'checked' : '' }}> {{ $lbl }}</label>
                @endforeach
            </div>
            <div class="jc-field jc-printing-other {{ $val('printing_method') === 'other' ? '' : 'jc-hidden' }}" id="jcPrintOther"><label>Other Method</label><input class="jc-control" name="printing_method_other" value="{{ $val('printing_method_other') }}"></div>
        </div>
        <div class="jc-printing-panel"><h5 class="jc-printing-panel-title">Plate Details</h5>
            <div class="jc-printing-plates">
                <div class="jc-field"><label>CTP Plates</label><input class="jc-control jc-plate" type="number" min="0" name="ctp_plates" value="{{ $val('ctp_plates') }}" oninput="jcSyncPlates()"></div>
                <div class="jc-field"><label>PMS</label><input class="jc-control" name="pms" value="{{ $val('pms') }}"></div>
                <div class="jc-field"><label>Total Plates <small style="color:#94a3b8">(auto)</small></label><input class="jc-control" id="jcTotalPlates" type="number" min="0" name="total_plates" value="{{ $val('total_plates') }}"></div>
            </div>
        </div>
        <div class="jc-printing-panel jc-printing-coating"><h5 class="jc-printing-panel-title">Coating</h5>
            <div class="jc-checks">
                <label class="jc-check"><input type="checkbox" name="coating_uv" value="1" {{ $val('coating_uv') ? 'checked' : '' }}> UV</label>
                <label class="jc-check"><input type="checkbox" name="coating_coating" value="1" {{ $val('coating_coating') ? 'checked' : '' }}> Coating</label>
                <label class="jc-check"><input type="checkbox" name="coating_varnish" value="1" {{ $val('coating_varnish') ? 'checked' : '' }}> Varnish</label>
                <label class="jc-check"><input type="checkbox" name="coating_other" value="1" data-other-target="jcCoatOther" onchange="jcToggleCheckOther(this)" {{ $val('coating_other') ? 'checked' : '' }}> Other</label>
            </div>
            <div class="jc-field jc-printing-other {{ $val('coating_other') ? '' : 'jc-hidden' }}" id="jcCoatOther"><label>Other Coating</label><input class="jc-control" name="coating_other_text" value="{{ $val('coating_other_text') }}"></div>
        </div>
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
        <div class="jc-field jc-3" style="display:flex;align-items:flex-end"><label class="jc-check"><input type="checkbox" name="screen_uv" value="1" {{ $val('screen_uv') ? 'checked' : '' }}> UV</label></div>
    </div>
    @include('crm.design_jobs.partials.timestrip', ['prefix' => 'screen'])
</div>

{{-- Card 9 — Foiling --}}
<div class="jc-card jc-foil-card">
    <h4 class="jc-title"><i class="fas fa-certificate"></i> Foiling</h4>
    <div class="jc-foil-options">
        <div class="jc-foil-choice"><label class="jc-check"><input type="checkbox" name="foil_gold" value="1" data-other-target="jcFoilGold" onchange="jcToggleCheckOther(this)" {{ $val('foil_gold') ? 'checked' : '' }}> Gold</label><div class="jc-field {{ $val('foil_gold') ? '' : 'jc-hidden' }}" id="jcFoilGold"><label>Gold Shade</label><input class="jc-control" name="foil_gold_shade" value="{{ $val('foil_gold_shade') }}"></div></div>
        <div class="jc-foil-choice"><label class="jc-check"><input type="checkbox" name="foil_silver" value="1" data-other-target="jcFoilSilver" onchange="jcToggleCheckOther(this)" {{ $val('foil_silver') ? 'checked' : '' }}> Silver</label><div class="jc-field {{ $val('foil_silver') ? '' : 'jc-hidden' }}" id="jcFoilSilver"><label>Silver Shade</label><input class="jc-control" name="foil_silver_shade" value="{{ $val('foil_silver_shade') }}"></div></div>
        <div class="jc-foil-choice"><label class="jc-check"><input type="checkbox" name="foil_other" value="1" data-other-target="jcFoilOther" onchange="jcToggleCheckOther(this)" {{ $val('foil_other') ? 'checked' : '' }}> Other</label><div class="jc-field {{ $val('foil_other') ? '' : 'jc-hidden' }}" id="jcFoilOther"><label>Other Shade</label><input class="jc-control" name="foil_other_shade" value="{{ $val('foil_other_shade') }}"></div></div>
    </div>
    @include('crm.design_jobs.partials.timestrip', ['prefix' => 'foil'])
</div>

{{-- Card 10 — Corrugation --}}
<div class="jc-card jc-corr-card">
    <h4 class="jc-title"><i class="fas fa-stream"></i> Corrugation</h4>
    <div class="jc-corr-layout">
        <div class="jc-corr-colors"><span class="jc-briefing-heading">Colors</span><div class="jc-checks">
            @foreach(['brown' => 'Brown', 'white' => 'White', 'other' => 'Other'] as $k => $lbl)
                <label class="jc-check"><input type="radio" name="corr_color" value="{{ $k }}" data-other-target="jcCorrOther" onchange="jcToggleRadioOther(this)" {{ (string) $val('corr_color') === (string) $k ? 'checked' : '' }}> {{ $lbl }}</label>
            @endforeach
        </div></div>
        <div class="jc-field {{ $val('corr_color') === 'other' ? '' : 'jc-hidden' }}" id="jcCorrOther"><label>Other Color</label><input class="jc-control" name="corr_color_other" value="{{ $val('corr_color_other') }}"></div>
        <div class="jc-field"><label>Ply</label><input class="jc-control" name="corr_ply" placeholder="e.g. 3-ply" value="{{ $val('corr_ply') }}"></div>
    </div>
    @include('crm.design_jobs.partials.timestrip', ['prefix' => 'corr'])
</div>

{{-- Card 11 — Diecutting --}}
<div class="jc-card">
    <h4 class="jc-title"><i class="fas fa-cut"></i> Diecutting</h4>
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
            <label class="jc-check"><input type="checkbox" name="paste_pvc_window" value="1" {{ $val('paste_pvc_window') ? 'checked' : '' }}> PVC Window</label>
            <label class="jc-check"><input type="checkbox" name="paste_other" value="1" data-other-target="jcPasteOther" onchange="jcToggleCheckOther(this)" {{ $val('paste_other') ? 'checked' : '' }}> Other</label>
            <div class="jc-checks {{ $val('paste_other') ? '' : 'jc-hidden' }}" id="jcPasteOther">
                <input class="jc-control" style="width:200px" name="paste_other_text" placeholder="Other type" value="{{ $val('paste_other_text') }}">
                <input class="jc-control" style="width:110px" type="number" min="0" name="paste_other_qty" placeholder="Qty" value="{{ $val('paste_other_qty') }}">
            </div>
        </div></div>
    </div>
</div>

{{-- Card 13 — Quality check --}}
<div class="jc-card jc-qc-card">
    <h4 class="jc-title"><i class="fas fa-clipboard-check"></i> Quality Check</h4>
    <div class="jc-grid">
        <div class="jc-field jc-4"><label>Result</label><div class="jc-checks">
            @foreach(['approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $lbl)
                <label class="jc-check"><input type="radio" name="qc_result" value="{{ $k }}" onchange="jcToggleQc()" {{ (string) $val('qc_result') === (string) $k ? 'checked' : '' }}> {{ $lbl }}</label>
            @endforeach
        </div></div>
        <div class="jc-field jc-8"><label>Approved by (signature)</label><input class="jc-control" name="qc_approved_by" value="{{ $val('qc_approved_by') }}"></div>
        <div class="jc-field jc-6"><label>Comments</label><textarea class="jc-control" name="qc_comments" rows="2">{{ $val('qc_comments') }}</textarea></div>
        <div class="jc-field jc-6"><label id="jcQcRejectionLabel">If Rejected, Mention Comments</label><textarea class="jc-control" name="qc_rejection_comments" rows="2">{{ $val('qc_rejection_comments') }}</textarea></div>
    </div>
</div>

{{-- Card 14 — Job timeline --}}
<div class="jc-card jc-timeline-card">
    <h4 class="jc-title"><i class="fas fa-clock"></i> Job Timeline</h4>
    <div class="jc-grid">
        <div class="jc-field jc-4"><label>Status</label><div class="jc-checks">
            @foreach(['on_time' => 'On time', 'delayed' => 'Delayed'] as $k => $lbl)
                <label class="jc-check"><input type="radio" name="timeline_status" value="{{ $k }}" {{ (string) $val('timeline_status') === (string) $k ? 'checked' : '' }}> {{ $lbl }}</label>
            @endforeach
        </div></div>
        <div class="jc-field jc-8"><label>If Delayed, Mention Days and Reason <small style="color:#94a3b8">(can be filled later)</small></label><textarea class="jc-control" name="delay_reason" rows="2">{{ $val('delay_reason') }}</textarea></div>
    </div>
</div>

<div class="jc-actions">
    <a class="jc-btn jc-btn-light" href="{{ route('crm.design_jobs.index') }}">Cancel</a>
    <span class="jc-form-error" id="jcFormError" role="alert"></span>
    @if($job->exists)<button class="jc-btn jc-btn-light jc-print" type="button" onclick="window.print()"><i class="fas fa-print"></i> Print Form</button>@endif
    <button class="jc-btn jc-btn-light jc-draft" type="submit" name="save_mode" value="draft"><i class="fas fa-save"></i> Save</button>
    <button class="jc-btn jc-btn-primary jc-complete" type="submit" name="save_mode" value="complete"><i class="fas fa-check-circle"></i> Complete Job Card</button>
</div>
</form>
</div>
@endsection

@section('scripts')<script>
// Keep the handwritten-form radio choices and their optional "Other" line in sync.
function jcToggleRadioOther(radio){
    var t=document.getElementById(radio.dataset.otherTarget);if(!t)return;
    var show=radio.value==='other';t.classList.toggle('jc-hidden',!show);
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
    var r=document.querySelector('[name="qc_result"]:checked'),c=document.querySelector('[name="qc_rejection_comments"]'),l=document.getElementById('jcQcRejectionLabel');
    var need=r&&r.value==='rejected';c.required=need;l.style.color=need?'#b91c1c':'';
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
function jcSyncPrintFields(){
    document.querySelectorAll('#jcForm .jc-field > .jc-control').forEach(function(field){
        var printValue=field.nextElementSibling;
        if(!printValue||!printValue.classList.contains('jc-print-value')){
            printValue=document.createElement('span');
            printValue.className='jc-print-value';
            field.insertAdjacentElement('afterend',printValue);
        }
        var value=field.tagName==='SELECT' ? (field.selectedOptions[0]?.textContent||'') : field.value;
        if(field.tagName==='SELECT'&&(!field.value||value==='Select'))value='';
        if(field.type==='date'&&value){var parts=value.split('-');value=parts[2]+'/'+parts[1]+'/'+parts[0];}
        printValue.textContent=value||'\u00a0';
        printValue.classList.toggle('jc-print-value-multiline',field.tagName==='TEXTAREA');
    });
}
window.addEventListener('beforeprint',jcSyncPrintFields);
function jcInitJobCard(){
    var form=document.getElementById('jcForm');
    if(!form||form.dataset.jcInitialized==='1')return;
    form.dataset.jcInitialized='1';
    jcToggleQc();
    var keys=['header','dummy','briefing','stock','printing','lamination','screen','foiling','corrugation','diecutting','pasting','quality','timeline'];
    var cards=Array.from(form.querySelectorAll(':scope > .jc-card'));
    var savedChoices=@json(old('sections', $card->section_choices ?? []));
    var printMode=new URLSearchParams(window.location.search).has('print');
    // A legacy completed job has no wizard metadata; leave it fully editable/printable.
    var legacy=@json($job->exists)&&savedChoices.__completed_step===undefined;
    if(legacy&&!Object.keys(savedChoices).length){keys.slice(1).forEach(function(key){savedChoices[key]='yes';});}
    var completedStep=parseInt(form.elements.wizard_completed_step.value,10);
    if(isNaN(completedStep))completedStep=legacy?12:-1;
    var activeStep=parseInt(form.elements.wizard_current_step.value,10);
    if(isNaN(activeStep))activeStep=0;
    activeStep=Math.max(0,Math.min(activeStep,keys.length-1));
    var done=keys.map(function(key,index){return index<=completedStep;});
    var saveButton=form.querySelector('.jc-complete');
    var printButton=form.querySelector('.jc-print');
    var formError=document.getElementById('jcFormError');
    var cacheKey='crm-job-card-'+@json($job->exists ? (string) $job->id : 'new');
    var cached=null;
    try{cached=JSON.parse(sessionStorage.getItem(cacheKey)||'null');}catch(error){}
    if(cached&&cached.fields){
        Object.keys(cached.rows||{}).forEach(function(id){
            var box=document.getElementById(id),config=cached.rows[id];
            if(!box||!config||!config.name)return;
            while(box.querySelectorAll('.jc-item').length<config.count)jcAddRow(id,config.name);
        });
        form.querySelectorAll('input,select,textarea').forEach(function(field){
            if(!field.name||field.name==='_token'||field.name==='save_mode')return;
            var entry=cached.fields[field.name];if(entry===undefined)return;
            if(field.type==='radio'||field.type==='checkbox')field.checked=Array.isArray(entry)&&entry.includes(field.value);
            else field.value=entry;
        });
        if(Array.isArray(cached.done)&&cached.done.length===keys.length)done=cached.done;
        if(typeof cached.activeStep==='number')activeStep=Math.max(0,Math.min(cached.activeStep,keys.length-1));
        jcToggleQc();
    }
    for(var sectionIndex=2;sectionIndex<done.length;sectionIndex++)done[sectionIndex]=!!done[1];
    function syncProgress(){
        completedStep=-1;
        for(var i=0;i<done.length&&done[i];i++)completedStep=i;
        form.elements.wizard_completed_step.value=completedStep;
        form.elements.wizard_current_step.value=activeStep;
    }
    function remember(){
        syncProgress();
        var fields={},rows={};
        form.querySelectorAll('input,select,textarea').forEach(function(field){
            if(!field.name||field.name==='_token'||field.name==='save_mode')return;
            if(field.type==='radio'||field.type==='checkbox'){
                if(!fields[field.name])fields[field.name]=[];
                if(field.checked)fields[field.name].push(field.value);
            }else fields[field.name]=field.value;
        });
        form.querySelectorAll('.jc-items[id]').forEach(function(box){
            var first=box.querySelector('.jc-item [name]');
            if(first)rows[box.id]={count:box.querySelectorAll('.jc-item').length,name:first.name.split('[')[0]};
        });
        try{sessionStorage.setItem(cacheKey,JSON.stringify({fields:fields,rows:rows,done:done,activeStep:activeStep}));}catch(error){}
    }

    function refreshSteps(){
        cards.forEach(function(card,index){
            card.classList.toggle('jc-step-hidden',index===1?!done[0]:index>1?!done[0]||!done[1]:false);
        });
        saveButton.disabled=!done[0]||!done[1];
        saveButton.style.opacity=saveButton.disabled?'0.5':'1';
        if(printButton){printButton.disabled=saveButton.disabled;printButton.style.opacity=saveButton.style.opacity;}
        syncProgress();
    }
    cards.forEach(function(card,index){
        var key=keys[index],title=card.querySelector('.jc-title');
        card.classList.add(index===0?'jc-header-step':'jc-step');
        card.dataset.section=key;
        var head=document.createElement('div');head.className='jc-step-head';
        card.insertBefore(head,title);head.appendChild(title);
        var body=document.createElement('div');body.className='jc-step-body';body.hidden=true;
        Array.from(card.childNodes).forEach(function(node){if(node!==head)body.appendChild(node);});
        card.appendChild(body);

        if(index===0){
            var choices=document.createElement('div');choices.className='jc-step-choice';head.appendChild(choices);
            var start=document.createElement('button');start.type='button';start.textContent='Open';
            start.addEventListener('click',function(){body.hidden=!body.hidden;start.textContent=body.hidden?'Open':'Close';activeStep=index;remember();});
            choices.appendChild(start);
        }else{
            var input=document.createElement('input');input.type='hidden';input.name='sections['+key+']';input.value='yes';head.appendChild(input);
            card.dataset.choice='yes';
        }
        if(index<2){
            var next=document.createElement('button');next.type='button';next.className='jc-btn jc-btn-primary jc-step-next';
            next.textContent=index===0?'Continue to Dummy':'Continue to Job Details';
            next.addEventListener('click',function(){
                if(index===0){
                    var product=form.querySelector('[name="product"]');
                    if(!product.value.trim()){product.setCustomValidity('Enter a product before continuing.');product.reportValidity();return;}
                    product.setCustomValidity('');
                }else{
                    var required=['dummy_sent_on','dummy_approved_on','dummy_approved_by'];
                    for(var i=0;i<required.length;i++){
                        var field=body.querySelector('[name="'+required[i]+'"]');
                        field.required=true;
                        if(!field.reportValidity())return;
                    }
                }
                done[index]=true;body.hidden=true;
                activeStep=index+1;
                if(index===0){
                    choices.querySelector('button').textContent='Open';
                    cards[1].querySelector('.jc-step-body').hidden=false;
                }else{
                    for(var i=2;i<cards.length;i++){
                        done[i]=true;
                        cards[i].querySelector('.jc-step-body').hidden=false;
                    }
                }
                refreshSteps();remember();
                if(cards[index+1])cards[index+1].scrollIntoView({behavior:'smooth',block:'center'});
            });
            body.appendChild(next);
        }
        card.addEventListener('focusin',function(){activeStep=index;remember();});
        card.addEventListener('click',function(event){
            if(event.target.closest('.jc-step-next'))return;
            activeStep=index;
            remember();
        });
    });
    var product=form.querySelector('[name="product"]');
    product.addEventListener('input',function(){product.setCustomValidity('');if(!product.value.trim()){done[0]=false;refreshSteps();}});
    ['dummy_sent_on','dummy_approved_on','dummy_approved_by'].forEach(function(name){
        var field=form.querySelector('[name="'+name+'"]');
        field.required=false;
        field.addEventListener('input',function(){
            if(!field.value.trim()){done[1]=false;refreshSteps();}
        });
    });
    // After Dummy, every production card stays visible; restore the active card position.
    if(done[1]){
        cards.slice(2).forEach(function(card){card.querySelector('.jc-step-body').hidden=false;});
        if(activeStep===0){cards[0].querySelector('.jc-step-body').hidden=false;cards[0].querySelector('.jc-step-choice button').textContent='Close';}
        if(activeStep===1)cards[1].querySelector('.jc-step-body').hidden=false;
    }else if(done[0]){
        cards[1].querySelector('.jc-step-body').hidden=false;
    }else{
        cards[0].querySelector('.jc-step-body').hidden=false;
        cards[0].querySelector('.jc-step-choice button').textContent='Close';
    }
    form.addEventListener('submit',function(event){
        formError.textContent='';
        syncProgress();
        if(event.submitter&&event.submitter.value==='draft'){
            // An unfinished card may have empty required fields; the server accepts drafts.
            form.querySelectorAll('[required]').forEach(function(field){field.required=false;});
            try{sessionStorage.removeItem(cacheKey);}catch(error){}
            return;
        }
        if(!done[0]||!done[1]){
            event.preventDefault();
            var pending=done[0]?1:0;
            formError.textContent='Complete Header and Dummy before finishing the job card.';
            cards[pending].scrollIntoView({behavior:'smooth',block:'center'});
            return;
        }
        var invalid=Array.from(form.elements).find(function(field){return field.willValidate&&!field.checkValidity();});
        if(invalid){
            event.preventDefault();
            var card=invalid.closest('.jc-card');
            if(card){card.querySelector('.jc-step-body').hidden=false;card.scrollIntoView({behavior:'smooth',block:'center'});}
            formError.textContent='Fill the required field in the highlighted card.';
            invalid.reportValidity();
            return;
        }
        try{sessionStorage.removeItem(cacheKey);}catch(error){}
    });
    form.addEventListener('input',function(){formError.textContent='';remember();});
    form.addEventListener('change',remember);
    form.addEventListener('click',function(event){if(event.target.closest('.jc-add,.jc-remove'))setTimeout(remember,0);});
    refreshSteps();
    document.querySelector('.jc-page').classList.add('jc-ready');
    if(!printMode&&activeStep>0&&((activeStep===1&&done[0])||(activeStep>1&&done[1]))){
        requestAnimationFrame(function(){cards[activeStep].scrollIntoView({block:'center'});});
    }
    var serverErrors=@json($errors->all());
    if(serverErrors.length){formError.textContent=serverErrors[0];document.querySelector('.jc-errors').scrollIntoView({block:'start'});}
    jcSyncPrintFields();
    if(printMode&&@json($job->exists)){window.requestAnimationFrame(function(){window.print();});}
}
// The CRM also replaces pages through AJAX; DOMContentLoaded does not fire there.
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',jcInitJobCard,{once:true});
else jcInitJobCard();
if(window.__jcPageLoadedHandler)document.removeEventListener('crm:page-loaded',window.__jcPageLoadedHandler);
window.__jcPageLoadedHandler=jcInitJobCard;
document.addEventListener('crm:page-loaded',jcInitJobCard);
</script>
@endsection
