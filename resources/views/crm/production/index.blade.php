@extends('crm.layout')

@section('title', 'Production Jobs')

@section('content')
@php
    $currentUser = Auth::guard('crm')->user();
    $isManager = $currentUser->isAdmin() || $currentUser->isProductionManager();
    $canFirstSheetQc = $currentUser->isAdmin() || $currentUser->isQC();
    $canSupervisorApprove = $currentUser->isAdmin() || $currentUser->isProductionManager();
    $pendingCount = $jobs->where('status', 'pending_planning')->count();
    $activeCount = $jobs->whereIn('status', ['scheduled', 'press_setup', 'first_sheet_review', 'adjustments_required', 'production_ready', 'full_production', 'in_process_checks', 'coating_options', 'lamination_options', 'die_cutting', 'stripping', 'blank_separation', 'gluing', 'final_quality_control'])->count();
    $completedCount = $jobs->whereIn('status', ['warehouse_ready', 'production_completed'])->count();
    $pageTitle = $pageTitle ?? 'Production Jobs';
    $pageEyebrow = $pageEyebrow ?? 'Operations Center';
    $pageSubtitle = $pageSubtitle ?? 'Plan, assign and monitor every active print job from one workspace.';
    $queueTitle = $queueTitle ?? 'Job Queue';
    $hasQcAssignment = $hasQcAssignment ?? false;
    $hasSupervisorAssignment = $hasSupervisorAssignment ?? false;
    $gluingTypes = $gluingTypes ?? [];
@endphp

<style>
    .production-shell { max-width:1500px; margin:0 auto; }
    .production-header { display:flex; justify-content:space-between; gap:24px; align-items:center; margin-bottom:24px; }
    .production-eyebrow { display:flex; align-items:center; gap:8px; margin-bottom:8px; color:var(--primary-purple); font-size:.72rem; font-weight:800; letter-spacing:.11em; text-transform:uppercase; }
    .production-eyebrow:before { content:""; width:22px; height:2px; border-radius:2px; background:var(--primary-purple); }
    .production-title { margin:0; color:#0f172a; font-size:2rem; line-height:1.15; font-weight:800; letter-spacing:-.035em; }
    .production-subtitle { margin:8px 0 0; color:#64748b; font-size:.95rem; }
    .header-icon { width:52px; height:52px; display:flex; align-items:center; justify-content:center; border-radius:15px; color:#fff; font-size:1.25rem; background:var(--primary-purple); box-shadow:0 12px 28px -12px var(--primary-shadow); }
    .stats-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:22px; }
    .stat-card { display:flex; align-items:center; gap:13px; min-height:78px; padding:14px 16px; box-sizing:border-box; background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 7px 22px -18px rgba(15,23,42,.45); }
    .stat-icon { width:42px; height:42px; flex:0 0 42px; display:flex; align-items:center; justify-content:center; border-radius:12px; background:var(--primary-soft); color:var(--primary-purple); }
    .stat-icon.amber { background:#fff7ed; color:#ea580c; }
    .stat-icon.green { background:#ecfdf5; color:#059669; }
    .stat-icon.slate { background:#f1f5f9; color:#475569; }
    .stat-number { display:block; color:#0f172a; font-size:1.28rem; font-weight:800; line-height:1; }
    .stat-label { display:block; margin-top:5px; color:#64748b; font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
    .machine-panel { margin:0 0 24px; background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 8px 22px -20px rgba(15,23,42,.55); }
    .machine-panel summary { display:flex; align-items:center; gap:12px; min-height:58px; padding:0 18px; cursor:pointer; list-style:none; color:#1e293b; font-size:.88rem; font-weight:800; }
    .machine-panel summary::-webkit-details-marker { display:none; }
    .machine-panel summary:after { content:"\f078"; margin-left:auto; color:#94a3b8; font-family:"Font Awesome 5 Free"; font-weight:900; transition:.2s; }
    .machine-panel[open] summary:after { transform:rotate(180deg); }
    .machine-panel-icon { width:34px; height:34px; display:flex; align-items:center; justify-content:center; border-radius:9px; background:#f1f5f9; color:#475569; }
    .machine-form { padding:18px; border-top:1px solid #f1f5f9; }
    .section-heading { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
    .section-heading h2 { margin:0; color:#0f172a; font-size:1rem; font-weight:800; }
    .section-heading span { color:#94a3b8; font-size:.78rem; }
    .production-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(390px,1fr)); gap:18px; align-items:start; }
    .job-card { position:relative; background:#fff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; box-shadow:0 12px 30px -24px rgba(15,23,42,.65); transition:transform .2s,box-shadow .2s,border-color .2s; }
    .job-card:hover { transform:translateY(-2px); border-color:var(--primary-purple); box-shadow:0 20px 38px -26px var(--primary-shadow); }
    .job-card:before { content:""; position:absolute; top:0; left:0; right:0; height:4px; background:var(--primary-purple); }
    .job-head { padding:21px 20px 17px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; gap:14px; align-items:flex-start; }
    .job-number { margin-bottom:6px; color:#94a3b8; font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .job-head h3 { margin:0 0 5px; color:#0f172a; font-size:1.05rem; font-weight:800; letter-spacing:-.015em; }
    .job-meta { display:flex; align-items:center; gap:7px; color:#64748b; font-size:.8rem; line-height:1.5; }
    .job-meta i { color:var(--primary-purple); }
    .status-badge { height:max-content; border-radius:999px; padding:7px 12px; background:var(--primary-soft); color:var(--primary-purple); border:1px solid var(--primary-shadow); font-size:.63rem; font-weight:800; letter-spacing:.045em; text-transform:uppercase; white-space:nowrap; }
    .job-body { padding:18px 20px 20px; }
    .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:9px; margin-bottom:16px; }
    .info-box { display:flex; align-items:center; gap:10px; min-height:58px; box-sizing:border-box; background:#f8fafc; border:1px solid #edf1f5; border-radius:11px; padding:10px 11px; }
    .info-icon { width:34px; height:34px; flex:0 0 34px; display:flex; align-items:center; justify-content:center; border-radius:9px; background:var(--primary-soft); color:var(--primary-purple); border:1px solid var(--primary-shadow); font-size:.78rem; }
    .info-label { display:block; color:#94a3b8; font-size:.6rem; font-weight:800; letter-spacing:.055em; text-transform:uppercase; margin-bottom:2px; }
    .info-value { display:block; max-width:145px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#334155; font-size:.8rem; font-weight:750; }
    .stage-form { background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-top:12px; }
    .stage-form h4 { margin:0 0 14px; color:#1e293b; font-size:.92rem; font-weight:800; }
    .field-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .field { margin-bottom:10px; }
    .field label { display:block; color:#475569; font-size:.75rem; font-weight:800; margin-bottom:5px; }
    .field input,.field select,.field textarea { width:100%; box-sizing:border-box; border:1px solid #dbe2ea; border-radius:9px; padding:10px 11px; background:#fff; color:#1e293b; outline:none; transition:border-color .2s,box-shadow .2s; }
    .field input:focus,.field select:focus,.field textarea:focus { border-color:var(--primary-purple); box-shadow:0 0 0 3px var(--primary-shadow); }
    .field textarea { min-height:72px; resize:vertical; }
    .action-btn { border:0; border-radius:9px; padding:10px 14px; cursor:pointer; font-weight:800; background:var(--primary-purple); color:#fff; transition:transform .18s,box-shadow .18s,background .18s; }
    .action-btn:hover { transform:translateY(-1px); box-shadow:0 9px 20px -12px var(--primary-shadow); background:var(--primary-hover); }
    .action-btn.green { background:#16a34a; }
    .action-btn.orange { background:#ea580c; }
    .open-job-btn { width:100%; min-height:46px; display:flex; align-items:center; justify-content:center; gap:9px; margin-top:4px; background:var(--primary-purple); color:#fff !important; border:1px solid var(--primary-purple); box-shadow:0 8px 18px var(--primary-shadow); }
    .open-job-btn i,.open-job-btn span { color:#fff !important; }
    .open-job-btn:hover { background:var(--primary-hover); border-color:var(--primary-hover); box-shadow:0 10px 22px var(--primary-shadow); }
    .job-workflow { display:none; margin-top:14px; padding-top:14px; border-top:1px solid #e2e8f0; }
    .job-workflow.is-open { display:block; }
    .check-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px; }
    .check-item { display:flex; gap:8px; align-items:center; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:8px; color:#334155; font-size:.78rem; font-weight:700; }
    .alert-box { border-radius:9px; padding:11px 12px; margin-top:12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:.84rem; }
    .notice-box { border-radius:9px; padding:11px 12px; margin-top:12px; background:#eef2ff; border:1px solid #c7d2fe; color:#3730a3; font-size:.84rem; line-height:1.5; }
    .operator-note { border-radius:9px; padding:11px 12px; margin-bottom:12px; background:#fff; border:1px dashed #cbd5e1; color:#475569; font-size:.82rem; line-height:1.5; }
    .job-packet { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; margin:0 0 12px; }
    .packet-title { display:flex; align-items:center; gap:8px; margin:0 0 12px; color:#1e293b; font-size:.9rem; font-weight:850; }
    .packet-title i { color:var(--primary-purple); }
    .packet-grid { display:grid; grid-template-columns:1fr 1fr; gap:9px; }
    .packet-item { min-width:0; padding:10px 11px; background:#f8fafc; border:1px solid #edf1f5; border-radius:9px; }
    .packet-item.wide { grid-column:1/-1; }
    .packet-label { display:block; color:#94a3b8; font-size:.58rem; font-weight:850; letter-spacing:.055em; text-transform:uppercase; }
    .packet-value { display:block; margin-top:4px; color:#334155; font-size:.78rem; font-weight:750; line-height:1.45; word-break:break-word; white-space:pre-line; }
    .timeline { margin-top:14px; border-top:1px dashed #cbd5e1; padding-top:12px; }
    .timeline summary { cursor:pointer; color:#475569; font-weight:800; font-size:.82rem; }
    .timeline-item { margin-top:9px; padding-left:10px; border-left:2px solid #c7d2fe; color:#64748b; font-size:.78rem; }
    .empty-state { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 24px; background: transparent; border-radius: 0; border: 0; box-shadow: none; text-align: center; margin-top: 20px; }
    .empty-icon-wrapper { width: 100px; height: 100px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 24px; position: relative; }
    .empty-icon-wrapper::after { content: ''; position: absolute; inset: -10px; border-radius: 50%; border: 2px solid #e2e8f0; border-top-color: var(--primary-purple); animation: spin 3s linear infinite; opacity: 0.3; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    .empty-icon { font-size: 3.5rem; background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .empty-state h3 { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 12px 0; letter-spacing: -0.02em; }
    .empty-state p { font-size: 1.05rem; color: #64748b; max-width: 450px; margin: 0; line-height: 1.6; }
    @media(max-width:900px) { .stats-grid{grid-template-columns:1fr 1fr} }
    @media(max-width:700px) { .production-grid{grid-template-columns:1fr}.field-grid,.check-grid,.packet-grid{grid-template-columns:1fr}.packet-item.wide{grid-column:auto}.production-header{align-items:flex-start}.header-icon{display:none}.stats-grid{grid-template-columns:1fr 1fr}.production-title{font-size:1.65rem} }
</style>

<div class="production-shell">
<div class="production-header">
    <div>
        <div class="production-eyebrow">{{ $pageEyebrow }}</div>
        <h1 class="production-title">{{ $pageTitle }}</h1>
        <p class="production-subtitle">{{ $pageSubtitle }}</p>
    </div>
    <div class="header-icon"><i class="fas fa-industry"></i></div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon slate"><i class="fas fa-layer-group"></i></div><div><span class="stat-number">{{ $jobs->count() }}</span><span class="stat-label">Total Jobs</span></div></div>
    <div class="stat-card"><div class="stat-icon amber"><i class="fas fa-clipboard-list"></i></div><div><span class="stat-number">{{ $pendingCount }}</span><span class="stat-label">Awaiting Plan</span></div></div>
    <div class="stat-card"><div class="stat-icon"><i class="fas fa-print"></i></div><div><span class="stat-number">{{ $activeCount }}</span><span class="stat-label">In Production</span></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check"></i></div><div><span class="stat-number">{{ $completedCount }}</span><span class="stat-label">Completed</span></div></div>
</div>

@if($isManager)
<details class="machine-panel">
    <summary><span class="machine-panel-icon"><i class="fas fa-cog"></i></span><span>Machine Management</span><small style="color:#94a3b8;font-weight:600;">Add a machine to a facility</small></summary>
    <form class="machine-form" action="{{ route('crm.production_machines.store') }}" method="POST">
        {{ csrf_field() }}
        <div class="field-grid">
            <div class="field"><label>Facility</label><select name="production_facility_id" required><option value="">Select facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}">{{ $facility->city }}, {{ $facility->country }}</option>@endforeach</select></div>
            <div class="field"><label>Printing Method</label><select name="printing_method" required><option value="">Select method</option>@foreach($printingMethods as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label>Machine Name</label><input type="text" name="name" required placeholder="Heidelberg XL 106"></div>
            <div class="field"><label>Machine Code</label><input type="text" name="code" required placeholder="LHR-OFF-01"></div>
        </div>
        <button class="action-btn" type="submit">Add Machine</button>
    </form>
</details>
@endif

<div class="section-heading"><h2>{{ $queueTitle }}</h2><span>{{ $jobs->count() }} {{ $jobs->count() === 1 ? 'ticket' : 'tickets' }}</span></div>

<div class="production-grid">
@forelse($jobs as $job)
    @php
        $lead = $job->salesOrder ? $job->salesOrder->lead : null;
        $latestCheck = $job->firstSheetChecks->first();
        $firstSheetStatus = $latestCheck ? $latestCheck->status : null;
        $operatorCanAct = $currentUser->isAdmin() || $currentUser->isProductionManager() || ($currentUser->isPressOperator() && (int)$job->press_operator_id === (int)$currentUser->id);
        $salesOrder = $job->salesOrder;
        $quantity = $lead ? ($lead->order_quantity ?: $lead->quantity) : $job->planned_quantity;
        $sizeParts = $lead ? array_filter([$lead->length, $lead->width, $lead->height], function ($value) { return $value !== null && $value !== ''; }) : [];
        $sizeText = count($sizeParts) ? implode(' x ', $sizeParts) . ($lead && $lead->unit ? ' ' . $lead->unit : '') : 'N/A';
        $latestProof = $lead ? $lead->proofRevisions()->first() : null;
    @endphp
    <article class="job-card">
        <div class="job-head">
            <div>
                <div class="job-number">Production Job #{{ $job->id }}</div>
                <h3>{{ $lead->client_name ?? 'Client' }}</h3>
                <div class="job-meta"><i class="fas fa-box"></i> Order #{{ $job->sales_order_id }} &middot; {{ $lead->product_name ?? 'Product not set' }}</div>
            </div>
            <div style="text-align:right;">
                <span class="status-badge">{{ str_replace('_',' ',$job->status) }}</span>
                <div style="margin-top:6px;color:#94a3b8;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;">Current Stage</div>
            </div>
        </div>
        <div class="job-body">
            <div class="info-grid">
                <div class="info-box"><span class="info-icon"><i class="fas fa-map-marker-alt"></i></span><span><span class="info-label">Facility</span><span class="info-value">{{ $job->facility ? $job->facility->city : 'Not planned' }}</span></span></div>
                <div class="info-box"><span class="info-icon"><i class="fas fa-print"></i></span><span><span class="info-label">Machine</span><span class="info-value">{{ $job->machine->name ?? 'Not assigned' }}</span></span></div>
                <div class="info-box"><span class="info-icon"><i class="fas fa-user-cog"></i></span><span><span class="info-label">Operator</span><span class="info-value">{{ $job->operator->name ?? 'Not assigned' }}</span></span></div>
                <div class="info-box"><span class="info-icon"><i class="fas fa-user-shield"></i></span><span><span class="info-label">Manager</span><span class="info-value">{{ $job->manager->name ?? 'Not assigned' }}</span></span></div>
            </div>

            <a href="{{ route('crm.production_jobs.show', $job->id) }}" class="action-btn open-job-btn" style="text-decoration:none;">
                <i class="fas fa-folder-open"></i>
                <span>Open Production Job</span>
            </a>

            <div class="job-workflow" id="productionJob{{ $job->id }}">
            <div class="job-packet">
                <h4 class="packet-title"><i class="fas fa-clipboard-list"></i> Ticket Details / Job Packet</h4>
                <div class="packet-grid">
                    @if(!$currentUser->isPressOperator() && !$currentUser->isQC())
                    <div class="packet-item"><span class="packet-label">Customer</span><span class="packet-value">{{ $lead->client_name ?? 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Email</span><span class="packet-value">{{ $lead->client_email ?? 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Phone</span><span class="packet-value">{{ $lead->client_phone ?? 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Shipping Region</span><span class="packet-value">{{ $lead ? ($lead->shipping_region ?: ($lead->country ?: 'N/A')) : 'N/A' }}</span></div>
                    @endif
                    <div class="packet-item"><span class="packet-label">Product</span><span class="packet-value">{{ $lead->product_name ?? 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Size</span><span class="packet-value">{{ $sizeText }}</span></div>
                    <div class="packet-item"><span class="packet-label">Quantity</span><span class="packet-value">{{ $quantity ? number_format($quantity) : 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Stock</span><span class="packet-value">{{ $lead->stock ?? 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Color</span><span class="packet-value">{{ $lead->color ?? 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Coating</span><span class="packet-value">{{ $lead->coating ?? 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Lamination</span><span class="packet-value">{{ $lead->lamination ?? 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Die / Glue</span><span class="packet-value">{{ $lead ? (($lead->die ?: 'N/A') . ' / ' . ($lead->glue ?: 'N/A')) : 'N/A' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Facility</span><span class="packet-value">{{ $job->facility ? $job->facility->city . ', ' . $job->facility->country : 'Not planned' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Machine</span><span class="packet-value">{{ $job->machine->name ?? 'Not assigned' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Manager</span><span class="packet-value">{{ $job->manager->name ?? 'Not assigned' }}</span></div>

                    <div class="packet-item"><span class="packet-label">Press Operator</span><span class="packet-value">{{ $job->operator->name ?? 'Not assigned' }}</span></div>
                    <div class="packet-item"><span class="packet-label">QC Inspector</span><span class="packet-value">{{ $job->qcInspector->name ?? 'Not assigned' }}</span></div>
                    @if(!$currentUser->isPressOperator() && !$currentUser->isQC())
                    <div class="packet-item"><span class="packet-label">Payment</span><span class="packet-value">{{ $salesOrder ? ucfirst(str_replace('_',' ', $salesOrder->payment_status)) : 'N/A' }}</span></div>
                    @endif
                    <div class="packet-item"><span class="packet-label">Approved Proof</span><span class="packet-value">@if($latestProof)<a href="{{ asset($latestProof->file_path) }}" target="_blank">View proof</a>@else N/A @endif</span></div>
                    <div class="packet-item wide"><span class="packet-label">Sales / Order Notes</span><span class="packet-value">{{ $lead ? ($lead->order_notes ?: $lead->sales_agent_notes ?: 'No sales notes.') : 'N/A' }}</span></div>
                    <div class="packet-item wide"><span class="packet-label">Design / Prepress Notes</span><span class="packet-value">{{ $salesOrder ? (($salesOrder->design_notes ?: 'No design notes.') . "\n" . ($salesOrder->prepress_notes ? 'Prepress: ' . $salesOrder->prepress_notes : '')) : 'N/A' }}</span></div>
                    <div class="packet-item wide"><span class="packet-label">Planning / Press Setup Notes</span><span class="packet-value">{{ trim(($job->planning_notes ?: 'No planning notes.') . "\n" . ($job->press_setup_notes ? 'Setup: ' . $job->press_setup_notes : '')) }}</span></div>
                </div>
            </div>

            @if($job->status === 'pending_planning' && !$machines->count())
                <div class="alert-box">Add at least one machine before planning this job.</div>
            @endif

            @if($isManager && in_array($job->status, ['pending_planning','scheduled']))
            <form class="stage-form" action="{{ route('crm.production_jobs.plan',$job->id) }}" method="POST">
                {{ csrf_field() }}
                <h4>Production Planning</h4>
                <div class="field-grid">
                    <div class="field"><label>Facility</label><select name="production_facility_id" class="job-facility-select" required><option value="">Select</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" {{ $job->production_facility_id==$facility->id?'selected':'' }}>{{ $facility->city }}, {{ $facility->country }}</option>@endforeach</select></div>
                    <div class="field"><label>Printing Method</label><select name="printing_method" required><option value="">Select</option>@foreach($printingMethods as $key=>$label)<option value="{{ $key }}" {{ $job->printing_method===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
                    <div class="field"><label>Gluing Type</label><select name="gluing_type" required><option value="">Select</option>@foreach($gluingTypes as $key=>$label)<option value="{{ $key }}" {{ $job->gluing_type===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
                    <div class="field"><label>Machine</label><select name="production_machine_id" class="facility-dependent" required><option value="">Select</option>@foreach($machines as $machine)<option value="{{ $machine->id }}" data-facility="{{ $machine->production_facility_id }}" {{ $job->production_machine_id==$machine->id?'selected':'' }}>{{ $machine->facility->city }} - {{ $machine->name }} ({{ $printingMethods[$machine->printing_method] ?? $machine->printing_method }})</option>@endforeach</select></div>
                    <div class="field"><label>Manager</label><select name="production_manager_id" class="facility-dependent" required><option value="">Select</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" data-facility="{{ $manager->production_facility_id }}" {{ $job->production_manager_id==$manager->id?'selected':'' }}>{{ $manager->name }}</option>@endforeach</select></div>
                    @if($hasSupervisorAssignment)
                    <div class="field"><label>Production Supervisor</label><select name="production_supervisor_id" class="facility-dependent" required><option value="">Select</option>@foreach($supervisors as $supervisor)<option value="{{ $supervisor->id }}" data-facility="{{ $supervisor->production_facility_id }}" {{ $job->production_supervisor_id==$supervisor->id?'selected':'' }}>{{ $supervisor->name }}</option>@endforeach</select></div>
                    @endif
                    <div class="field"><label>Press Operator</label><select name="press_operator_id" class="facility-dependent" required><option value="">Select</option>@foreach($operators as $operator)<option value="{{ $operator->id }}" data-facility="{{ $operator->production_facility_id }}" {{ $job->press_operator_id==$operator->id?'selected':'' }}>{{ $operator->name }}</option>@endforeach</select></div>
                    @if($hasQcAssignment)
                    <div class="field"><label>QC Inspector</label><select name="qc_inspector_id" required><option value="">Select</option>@foreach($qcs as $qc)<option value="{{ $qc->id }}" {{ $job->qc_inspector_id==$qc->id?'selected':'' }}>{{ $qc->name }}</option>@endforeach</select></div>
                    @endif
                    <div class="field"><label>Priority</label><select name="priority" required>@foreach(['low','normal','high','urgent'] as $priority)<option value="{{ $priority }}" {{ $job->priority===$priority?'selected':'' }}>{{ ucfirst($priority) }}</option>@endforeach</select></div>
                    <div class="field"><label>Planned Quantity</label><input type="number" name="planned_quantity" min="1" value="{{ $job->planned_quantity ?: ($lead->quantity ?? 1) }}" required></div>
                    <div class="field"><label>Start</label><input type="datetime-local" name="scheduled_start_at" value="{{ $job->scheduled_start_at ? $job->scheduled_start_at->format('Y-m-d\TH:i') : '' }}" required></div>
                    <div class="field"><label>Due</label><input type="datetime-local" name="scheduled_due_at" value="{{ $job->scheduled_due_at ? $job->scheduled_due_at->format('Y-m-d\TH:i') : '' }}" required></div>
                </div>
                <div class="field"><label>Planning Notes</label><textarea name="planning_notes">{{ $job->planning_notes }}</textarea></div>
                <button class="action-btn" type="submit">Save Production Plan</button>
            </form>
            @endif

            @if($operatorCanAct && $job->status === 'scheduled')
            <form class="stage-form" action="{{ route('crm.production_jobs.start_setup',$job->id) }}" method="POST">
                {{ csrf_field() }}
                <h4>Press Setup / Autoplate</h4>
                <div class="field"><label>Setup Notes</label><textarea name="press_setup_notes" required placeholder="Plate loading, stock, ink and machine setup details"></textarea></div>
                <button class="action-btn orange" type="submit">Start Press Setup</button>
            </form>
            @endif

            @if($operatorCanAct && in_array($job->status, ['press_setup','adjustments_required']))
            <form class="stage-form" action="{{ route('crm.production_jobs.first_sheet',$job->id) }}" method="POST">
                {{ csrf_field() }}
                <h4>{{ $job->status==='adjustments_required'?'Submit Corrected First Sheet':'First Sheet Pull' }}</h4>
                @if($job->adjustment_notes)<div class="alert-box">QC Notes: {{ $job->adjustment_notes }}</div>@endif
                <div class="field"><label>Operator Notes</label><textarea name="notes" required placeholder="Ink, density, registration and sheet details"></textarea></div>
                <button class="action-btn" type="submit">Send First Sheet to QC</button>
            </form>
            @endif

            @if($job->status === 'first_sheet_review' && $latestCheck && $latestCheck->notes)
                <div class="operator-note"><strong>Press Operator Notes:</strong><br>{{ $latestCheck->notes }}</div>
            @endif

            @if($canFirstSheetQc && $job->status === 'first_sheet_review' && in_array($firstSheetStatus, ['pending','pending_qc', null], true))
            <form class="stage-form" action="{{ route('crm.production_jobs.first_sheet_review',$job->id) }}" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="review_stage" value="qc">
                <h4>First-Sheet QC / Press Check - Attempt #{{ $latestCheck->attempt_number ?? 1 }}</h4>
                <div class="check-grid">
                    <label class="check-item"><input type="checkbox" name="proof_match_passed" value="1"> Match Approved Proof</label>
                    <label class="check-item"><input type="checkbox" name="cmyk_density_passed" value="1"> CMYK Density</label>
                    <label class="check-item"><input type="checkbox" name="spot_color_passed" value="1"> Pantone / Spot Color</label>
                    <label class="check-item"><input type="checkbox" name="registration_passed" value="1"> Registration</label>
                    <label class="check-item"><input type="checkbox" name="print_defect_passed" value="1"> Print Defects</label>
                </div>
                <div class="field"><label>QC Notes / Rejection Reason</label><textarea name="notes" placeholder="Required when any check fails"></textarea></div>
                <button class="action-btn green" type="submit">Submit QC Checks</button>
            </form>
            @endif

            @if($job->status === 'sales_agent_review' && ($currentUser->isAdmin() || ($job->salesOrder && (int)$job->salesOrder->sales_agent_id === (int)$currentUser->id)))
            <form class="stage-form" action="{{ route('crm.production_jobs.sales_agent_review',$job->id) }}" method="POST">
                {{ csrf_field() }}
                <h4>Sales Agent Review</h4>
                <div class="notice-box">QC checks passed. Sales Agent must approve color and details before full production.</div>
                <div class="field">
                    <label>Action</label>
                    <select name="action" required>
                        <option value="approve">Approve First Sheet (Ready for Production)</option>
                        <option value="request_change">Request Changes / Reject</option>
                    </select>
                </div>
                <div class="field"><label>Notes / Rejection Reason</label><textarea name="notes" placeholder="Required if requesting changes"></textarea></div>
                <button class="action-btn green" type="submit">Submit Decision</button>
            </form>
            @endif

            @if($job->status === 'first_sheet_review' && !$canFirstSheetQc && in_array($firstSheetStatus, ['pending','pending_qc', null], true))
                <div class="notice-box">First sheet is waiting for QC to complete proof match, density, spot color, registration and defect checks.</div>
            @endif

            @if($job->status === 'sales_agent_review' && !($currentUser->isAdmin() || ($job->salesOrder && (int)$job->salesOrder->sales_agent_id === (int)$currentUser->id)))
                <div class="notice-box">QC checks are passed. Waiting for Sales Agent approval.</div>
            @endif

            @if($operatorCanAct && $job->status === 'production_ready')
            <form class="stage-form" action="{{ route('crm.production_jobs.start_run',$job->id) }}" method="POST">
                {{ csrf_field() }}
                <h4>First Sheet Approved</h4>
                <button class="action-btn green" type="submit">Start Full Production Run</button>
            </form>
            @endif

            @if($operatorCanAct && $job->status === 'full_production')
            <form class="stage-form" action="{{ route('crm.production_jobs.complete_run',$job->id) }}" method="POST">
                {{ csrf_field() }}
                <h4>Submit Production Run Readings</h4>
                <div class="notice-box">This moves the job to production manager review for finishing stages.</div>
                <div class="field-grid">
                    <div class="field"><label>Good Quantity</label><input type="number" name="good_quantity" min="0" required></div>
                    <div class="field"><label>Waste Quantity</label><input type="number" name="waste_quantity" min="0" required></div>
                    <div class="field"><label>Every X Sheets Check</label><input type="text" name="every_x_sheets_check"></div>
                    <div class="field"><label>Density Reading</label><input type="text" name="density_reading"></div>
                    <div class="field"><label>Color Variation Check</label><input type="text" name="color_variation_check"></div>
                    <div class="field"><label>Registration Check</label><input type="text" name="registration_check"></div>
                </div>
                <div class="field"><label>Run Notes</label><textarea name="notes"></textarea></div>
                <button class="action-btn green" type="submit">Submit Readings to Production Manager</button>
            </form>
            @endif

            @if($job->status === 'production_completed')
                <div class="stage-form"><h4>Production Completed</h4><div class="info-grid"><div class="info-box"><span class="info-label">Good</span><span class="info-value">{{ number_format($job->good_quantity) }}</span></div><div class="info-box"><span class="info-label">Waste</span><span class="info-value">{{ number_format($job->waste_quantity) }}</span></div></div></div>
            @endif

            <details class="timeline">
                <summary>Workflow History ({{ $job->logs->count() }})</summary>
                @foreach($job->logs as $log)
                    <div class="timeline-item"><strong>{{ str_replace('_',' ',$log->to_status) }}</strong> by {{ $log->user->name ?? 'System' }}<br>{{ $log->notes }}<br>{{ $log->created_at->format('M d, Y h:i A') }}</div>
                @endforeach
            </details>
            </div>
        </div>
    </article>
@empty
    <div class="empty-state">
        <div class="empty-icon-wrapper">
            @if(isset($pageTitle) && $pageTitle === 'QC Tickets')
                <i class="fas fa-clipboard-check empty-icon"></i>
            @elseif(isset($pageTitle) && $pageTitle === 'Press Tickets')
                <i class="fas fa-print empty-icon"></i>
            @else
                <i class="fas fa-industry empty-icon"></i>
            @endif
        </div>
        <h3>No production jobs yet</h3>
        <p>Jobs appear here automatically after Prepress approval.</p>
    </div>
@endforelse
</div>
</div>

<script>
    function toggleProductionJob(jobId, button) {
        var panel = document.getElementById('productionJob' + jobId);
        var label = button.querySelector('span');
        var icon = button.querySelector('i');
        var isOpen = panel.classList.toggle('is-open');

        label.textContent = isOpen ? 'Close Production Job' : 'Open Production Job';
        icon.className = isOpen ? 'fas fa-folder-minus' : 'fas fa-folder-open';
    }

    document.querySelectorAll('.job-facility-select').forEach(function (facilitySelect) {
        var form = facilitySelect.closest('form');
        var dependentSelects = form.querySelectorAll('.facility-dependent');

        function filterFacilityOptions() {
            var facilityId = facilitySelect.value;
            dependentSelects.forEach(function (select) {
                Array.prototype.forEach.call(select.options, function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    var visible = !!facilityId && option.getAttribute('data-facility') === facilityId;
                    option.hidden = !visible;
                    if (!visible && option.selected) select.value = '';
                });
            });
        }

        facilitySelect.addEventListener('change', filterFacilityOptions);
        filterFacilityOptions();
    });
</script>
@endsection
