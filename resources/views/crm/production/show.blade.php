@extends('crm.layout')

@section('title', 'Production Job #' . $job->id)

@section('content')
@php
    $currentUser = Auth::guard('crm')->user();
    $isManager = $currentUser->isAdmin() || $currentUser->isProductionManager();
    $canFirstSheetQc = $currentUser->isAdmin() || $currentUser->isQC();
    $canSupervisorApprove = $currentUser->isAdmin() || $currentUser->isProductionManager();
    $operatorCanAct = $currentUser->isAdmin() || ($currentUser->isPressOperator() && (int)$job->press_operator_id === (int)$currentUser->id);
    $supervisorCanAct = $currentUser->isAdmin() || $currentUser->isProductionManager();
    $finalQcCanAct = $currentUser->isAdmin() || ($currentUser->isQC() && (!$hasQcAssignment || (int)$job->qc_inspector_id === (int)$currentUser->id));
    $lead = $job->salesOrder ? $job->salesOrder->lead : null;
    $salesOrder = $job->salesOrder;
    $latestCheck = $job->firstSheetChecks->first();
    $firstSheetStatus = $latestCheck ? $latestCheck->status : null;
    $quantity = $lead ? ($lead->order_quantity ?: $lead->quantity) : $job->planned_quantity;
    $sizeParts = $lead ? array_filter([$lead->length, $lead->width, $lead->height], function ($value) { return $value !== null && $value !== ''; }) : [];
    $sizeText = count($sizeParts) ? implode(' x ', $sizeParts) . ($lead && $lead->unit ? ' ' . $lead->unit : '') : 'N/A';
    $latestProof = $lead ? $lead->proofRevisions()->first() : null;
    $stageLabels = [
        'pending_planning' => 'Pending Production Planning',
        'scheduled' => 'Scheduled / Assigned',
        'press_setup' => 'Press Setup / Autoplate',
        'first_sheet_review' => $firstSheetStatus === 'qc_passed' ? 'Waiting Supervisor Approval' : 'First Sheet QC Review',
        'adjustments_required' => 'Adjustments Required',
        'production_ready' => 'Production Ready',
        'full_production' => 'Full Production Run',
        'in_process_checks' => 'In-Process Color Checks',
        'coating_options' => 'Coating Options',
        'lamination_options' => 'Lamination Options',
        'die_cutting' => 'Die Cutting / Stripping',
        'stripping' => 'Stripping',
        'blank_separation' => 'Blank Separation',
        'gluing' => 'Gluing Stage',
        'final_quality_control' => 'Final Quality Control',
        'packing' => 'Packing / Palletization',
        'palletization' => 'Palletization',
        'warehouse_ready' => 'Warehouse / Ready to Ship',
        'production_completed' => 'Production Completed',
    ];
    $currentStage = $stageLabels[$job->status] ?? ucfirst(str_replace('_', ' ', $job->status));
    $gluingLabels = [
        'straight_line_glue' => 'Straight Line Glue',
        'auto_lock_bottom' => 'Auto Lock Bottom',
        '4_corner_glue' => '4 Corner Glue',
        '5_corner_glue' => '5 Corner Glue',
        '6_corner_glue' => '6 Corner Glue',
        'burger_box_glue' => 'Burger Box Glue',
        'gift_box_assembly' => 'Gift Box Assembly',
    ];
    $backRoute = $currentUser->isPressOperator() ? route('crm.press_tickets.index') : ($currentUser->isQC() ? route('crm.qc_tickets.index') : route('crm.production_jobs.index'));
@endphp

<style>
    .job-detail-shell { max-width:1380px; margin:0 auto; }
    .topbar { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; margin-bottom:18px; }
    .back-link { display:inline-flex; align-items:center; gap:8px; color:var(--primary-purple); font-weight:800; text-decoration:none; margin-bottom:10px; }
    .page-title { margin:0; color:#0f172a; font-size:2rem; font-weight:850; letter-spacing:-.035em; }
    .page-subtitle { margin:8px 0 0; color:#64748b; font-size:.95rem; }
    .stage-banner { background:#fff; border:1px solid #dbe4ff; border-radius:16px; padding:18px; margin-bottom:18px; box-shadow:0 14px 32px -28px rgba(var(--primary-rgb), .65); }
    .stage-banner-top { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:14px; }
    .stage-pill { display:inline-flex; align-items:center; gap:8px; border-radius:999px; padding:8px 13px; background:#eef2ff; color:var(--primary-purple); font-size:.76rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
    .stage-title { margin:0; color:#0f172a; font-size:1.15rem; font-weight:850; }
    .stage-steps { display:grid; grid-template-columns:repeat(auto-fit,minmax(78px,1fr)); gap:8px; }
    .stage-step { min-height:52px; border:1px solid #e5e7eb; border-radius:11px; padding:9px; background:#f8fafc; color:#64748b; font-size:.68rem; font-weight:800; text-align:center; display:flex; align-items:center; justify-content:center; }
    .stage-step.active { background:var(--primary-purple); color:#fff; border-color:var(--primary-purple); }
    .detail-grid { display:grid; grid-template-columns:1.05fr .95fr; gap:18px; align-items:start; }
    .panel { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:18px; box-shadow:0 12px 32px -28px rgba(15,23,42,.6); }
    .panel + .panel { margin-top:14px; }
    .panel-title { display:flex; align-items:center; gap:9px; margin:0 0 14px; color:#1e293b; font-size:1rem; font-weight:850; }
    .panel-title i { color:var(--primary-purple); }
    .packet-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .packet-item { padding:11px 12px; border:1px solid #edf1f5; border-radius:10px; background:#f8fafc; min-width:0; }
    .packet-item.wide { grid-column:1/-1; }
    .packet-label { display:block; color:#94a3b8; font-size:.62rem; font-weight:850; letter-spacing:.055em; text-transform:uppercase; }
    .packet-value { display:block; margin-top:5px; color:#334155; font-size:.82rem; font-weight:750; line-height:1.5; word-break:break-word; white-space:pre-line; }
    .field-grid,.check-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .field { margin-bottom:10px; }
    .field label { display:block; margin-bottom:5px; color:#475569; font-size:.75rem; font-weight:800; }
    .field input,.field select,.field textarea { width:100%; box-sizing:border-box; border:1px solid #dbe2ea; border-radius:9px; padding:10px 11px; background:#fff; color:#1e293b; outline:none; }
    .field textarea { min-height:76px; resize:vertical; }
    .check-item { display:flex; align-items:center; gap:8px; border:1px solid #e2e8f0; border-radius:9px; padding:9px; color:#334155; font-size:.8rem; font-weight:750; background:#fff; }
    .action-btn { border:0; border-radius:10px; padding:11px 15px; cursor:pointer; font-weight:850; background:var(--primary-purple); color:#fff; }
    .action-btn.green { background:#16a34a; }
    .action-btn.orange { background:#ea580c; }
    .alert-box { border-radius:10px; padding:11px 12px; margin:0 0 12px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:.84rem; }
    .notice-box { border-radius:10px; padding:11px 12px; margin:0 0 12px; background:#eef2ff; border:1px solid #c7d2fe; color:#3730a3; font-size:.84rem; line-height:1.5; }
    .timeline-item { margin-top:10px; padding-left:12px; border-left:2px solid #c7d2fe; color:#64748b; font-size:.8rem; line-height:1.45; }
    @media(max-width:900px) { .detail-grid,.packet-grid,.field-grid,.check-grid,.stage-steps{grid-template-columns:1fr}.packet-item.wide{grid-column:auto}.topbar{display:block} }
</style>

<div class="job-detail-shell">
    <div class="topbar">
        <div>
            <a href="{{ $backRoute }}" class="back-link"><i class="fas fa-arrow-left"></i> Back to tickets</a>
            <h1 class="page-title">Production Job #{{ $job->id }} - {{ $lead->client_name ?? 'Client' }}</h1>
            <p class="page-subtitle">Order #{{ $job->sales_order_id }} · {{ $lead->product_name ?? 'Product not set' }}</p>
        </div>
        <span class="stage-pill"><i class="fas fa-map-signs"></i>{{ $currentStage }}</span>
    </div>

    <div class="stage-banner">
        <div class="stage-banner-top">
            <h2 class="stage-title">Current Stage: {{ $currentStage }}</h2>
            <span class="stage-pill">{{ str_replace('_', ' ', $job->status) }}</span>
        </div>
        <div class="stage-steps">
            <div class="stage-step {{ in_array($job->status, ['in_process_checks','coating_options','lamination_options','die_cutting','stripping','blank_separation','gluing','packing','palletization','warehouse_ready','production_completed']) ? 'active' : '' }}">Production Manager Check</div>
            <div class="stage-step {{ in_array($job->status, ['first_sheet_review','adjustments_required','final_quality_control']) ? 'active' : '' }}">QC Check</div>
        </div>
    </div>

    <div class="detail-grid">
        <div>
            <div class="panel">
                <h3 class="panel-title"><i class="fas fa-clipboard-list"></i> Full Ticket Details</h3>
                <div class="packet-grid">
                    @if(!$currentUser->isProductionManager() && !$currentUser->isPressOperator() && !$currentUser->isQC())
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
                    <div class="packet-item"><span class="packet-label">Gluing Type</span><span class="packet-value">{{ $gluingLabels[$job->gluing_type] ?? 'Not selected' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Manager</span><span class="packet-value">{{ $job->manager->name ?? 'Not assigned' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Production Supervisor</span><span class="packet-value">{{ $job->supervisor->name ?? 'Not assigned' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Press Operator</span><span class="packet-value">{{ $job->operator->name ?? 'Not assigned' }}</span></div>
                    <div class="packet-item"><span class="packet-label">QC Inspector</span><span class="packet-value">{{ $job->qcInspector->name ?? 'Not assigned' }}</span></div>
                    <div class="packet-item"><span class="packet-label">Approved Proof</span><span class="packet-value">@if($latestProof)<a href="{{ asset($latestProof->file_path) }}" target="_blank">View approved proof</a>@else N/A @endif</span></div>
                    @if(!$currentUser->isPressOperator())
                    <div class="packet-item wide"><span class="packet-label">Sales / Order Notes</span><span class="packet-value">{{ $lead ? ($lead->order_notes ?: $lead->sales_agent_notes ?: 'No sales notes.') : 'N/A' }}</span></div>
                    <div class="packet-item wide"><span class="packet-label">Design / Prepress Notes</span><span class="packet-value">{{ $salesOrder ? (($salesOrder->design_notes ?: 'No design notes.') . "\n" . ($salesOrder->prepress_notes ? 'Prepress: ' . $salesOrder->prepress_notes : '')) : 'N/A' }}</span></div>
                    @endif
                    <div class="packet-item wide"><span class="packet-label">Planning / Press Setup Notes</span><span class="packet-value">{{ trim(($job->planning_notes ?: 'No planning notes.') . "\n" . ($job->press_setup_notes ? 'Setup: ' . $job->press_setup_notes : '')) }}</span></div>
                </div>
            </div>
        </div>

        <div>
            <div class="panel">
                <h3 class="panel-title"><i class="fas fa-tasks"></i> Stage Actions</h3>

                @if($isManager && in_array($job->status, ['pending_planning','scheduled']))
                <form action="{{ route('crm.production_jobs.plan',$job->id) }}" method="POST">
                    {{ csrf_field() }}
                    <div class="field-grid">
                        <div class="field" style="display: none;"><label>Facility</label><select name="production_facility_id" class="job-facility-select" required><option value="">Select</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" {{ $job->production_facility_id==$facility->id?'selected':'' }}>{{ $facility->city }}, {{ $facility->country }}</option>@endforeach</select></div>
                        <div class="field"><label>Printing Method</label><select name="printing_method" required><option value="">Select</option>@foreach($printingMethods as $key=>$label)<option value="{{ $key }}" {{ $job->printing_method===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
                        <div class="field"><label>Gluing Type</label><select name="gluing_type"><option value="">Select (Optional)</option>@foreach($gluingLabels as $key=>$label)<option value="{{ $key }}" {{ $job->gluing_type===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
                        <div class="field"><label>Machine</label><select name="production_machine_id" class="facility-dependent" required><option value="">Select</option>@foreach($machines as $machine)<option value="{{ $machine->id }}" data-facility="{{ $machine->production_facility_id }}" {{ $job->production_machine_id==$machine->id?'selected':'' }}>{{ $machine->facility->city }} - {{ $machine->name }}</option>@endforeach</select></div>

                        <div class="field"><label>Press Operator</label><select name="press_operator_id" class="facility-dependent" required><option value="">Select</option>@foreach($operators as $operator)<option value="{{ $operator->id }}" data-facility="{{ $operator->production_facility_id }}" {{ $job->press_operator_id==$operator->id?'selected':'' }}>{{ $operator->name }}</option>@endforeach</select></div>
                        @if($hasQcAssignment)<div class="field"><label>QC Inspector</label><select name="qc_inspector_id" required><option value="">Select</option>@foreach($qcs as $qc)<option value="{{ $qc->id }}" {{ $job->qc_inspector_id==$qc->id?'selected':'' }}>{{ $qc->name }}</option>@endforeach</select></div>@endif
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
                <form action="{{ route('crm.production_jobs.start_setup',$job->id) }}" method="POST">{{ csrf_field() }}<div class="field"><label>Setup Notes</label><textarea name="press_setup_notes" required placeholder="Plate loading, stock, ink and machine setup details"></textarea></div><button class="action-btn orange" type="submit">Start Press Setup</button></form>
                @endif

                @if($operatorCanAct && in_array($job->status, ['press_setup','adjustments_required']))
                <form action="{{ route('crm.production_jobs.first_sheet',$job->id) }}" method="POST" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    @if($job->adjustment_notes)<div class="alert-box">QC Notes: {{ $job->adjustment_notes }}</div>@endif
                    <div class="field">
                        <label>Operator Notes</label>
                        <textarea name="notes" required placeholder="Ink, density, registration and sheet details"></textarea>
                    </div>
                    <div class="field">
                        <label>Attach QC Sheet (Optional)</label>
                        <input type="file" name="first_sheet_file" class="input-field" style="padding: 10px; background: #fff; border: 1px solid #dcdfe6; border-radius: 8px; width: 100%; box-sizing: border-box; cursor: pointer;">
                    </div>
                    <button class="action-btn" type="submit">Send First Sheet to QC</button>
                </form>
                @endif

                @if($job->status === 'first_sheet_review' && $latestCheck && $latestCheck->notes)
                <div class="notice-box">
                    <strong>Press Operator Notes:</strong><br>{{ $latestCheck->notes }}
                    @if($job->first_sheet_file_path)
                        <div style="margin-top: 10px;">
                            <a href="{{ asset($job->first_sheet_file_path) }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #eef2ff; color: var(--primary-purple); border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.85rem; border: 1px solid #c7d2fe;">
                                <i class="fas fa-file-pdf"></i> View First Sheet / QC File
                            </a>
                        </div>
                    @endif
                </div>
                @endif

                @if($canFirstSheetQc && $job->status === 'first_sheet_review' && in_array($firstSheetStatus, ['pending','pending_qc', null], true))
                <form action="{{ route('crm.production_jobs.first_sheet_review',$job->id) }}" method="POST">{{ csrf_field() }}<input type="hidden" name="review_stage" value="qc"><div class="check-grid"><label class="check-item"><input type="checkbox" name="proof_match_passed" value="1"> Match Approved Proof</label><label class="check-item"><input type="checkbox" name="cmyk_density_passed" value="1"> CMYK Density</label><label class="check-item"><input type="checkbox" name="spot_color_passed" value="1"> Pantone / Spot Color</label><label class="check-item"><input type="checkbox" name="registration_passed" value="1"> Registration</label><label class="check-item"><input type="checkbox" name="print_defect_passed" value="1"> Print Defects</label></div><div class="field"><label>QC Notes / Rejection Reason</label><textarea name="notes" placeholder="Required when any check fails"></textarea></div><div style="display:flex; gap:10px;"><button class="action-btn green" type="submit" name="qc_action" value="approve">Approve First Sheet</button><button class="action-btn" style="background:#ef4444;" type="submit" name="qc_action" value="reject">Reject First Sheet</button></div></form>
                @endif

                @if($job->status === 'sales_agent_review' && ($currentUser->isAdmin() || ($job->salesOrder && (int)$job->salesOrder->sales_agent_id === (int)$currentUser->id)))
                <form action="{{ route('crm.production_jobs.sales_agent_review',$job->id) }}" method="POST">
                    {{ csrf_field() }}
                    <div class="notice-box">QC checks passed. Sales Agent must approve color and details before full production.</div>
                    <div class="field">
                        <label>Action</label>
                        <select name="action" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #e2e8f0; background: #fff;">
                            <option value="approve">Approve First Sheet (Ready for Production)</option>
                            <option value="request_change">Request Changes / Reject</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Notes / Rejection Reason</label>
                        <textarea name="notes" placeholder="Required if requesting changes"></textarea>
                    </div>
                    <button class="action-btn green" type="submit">Submit Decision</button>
                </form>
                @endif

                @if($operatorCanAct && $job->status === 'production_ready')
                <form action="{{ route('crm.production_jobs.start_run',$job->id) }}" method="POST">{{ csrf_field() }}<button class="action-btn green" type="submit">Start Full Production Run</button></form>
                @endif

                @if($operatorCanAct && $job->status === 'full_production')
                <form action="{{ route('crm.production_jobs.complete_run',$job->id) }}" method="POST">{{ csrf_field() }}
                    <div class="notice-box">Diagram flow: press operator records in-process readings, then production manager reviews and moves finishing stages.</div>
                    <div class="field-grid">
                        <div class="field"><label>Good Quantity</label><input type="number" name="good_quantity" min="0" required></div>
                        <div class="field"><label>Waste Quantity</label><input type="number" name="waste_quantity" min="0" required></div>
                        <div class="field"><label>Every X Sheets Check</label><input type="text" name="every_x_sheets_check" placeholder="e.g. every 100 sheets checked"></div>
                        <div class="field"><label>Density Reading</label><input type="text" name="density_reading" placeholder="e.g. C/M/Y/K readings"></div>
                        <div class="field"><label>Color Variation Check</label><input type="text" name="color_variation_check" placeholder="Variation notes"></div>
                        <div class="field"><label>Registration Check</label><input type="text" name="registration_check" placeholder="Registration notes"></div>
                    </div>
                    <div class="field"><label>Run Notes</label><textarea name="notes"></textarea></div>
                    <button class="action-btn green" type="submit">Submit Readings to Production Manager</button>
                </form>
                @endif

                @if($supervisorCanAct && in_array($job->status, ['in_process_checks','coating_options','lamination_options','die_cutting','stripping','blank_separation','gluing']))
                <form action="{{ route('crm.production_jobs.supervisor_stage',$job->id) }}" method="POST">{{ csrf_field() }}
                    <div class="notice-box">Production Manager flow: review in-process checks, choose coating/lamination path, move die cutting, stripping, blank separation and gluing. After gluing, send to QC.</div>
                    <div class="field"><label>Completed Stages (Check all that apply)</label>
                        <div class="check-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                            @php
                                $finishingStages = [
                                    'in_process_checks'=>'Review In-Process Color Checks',
                                    'coating_options'=>'Coating Options',
                                    'lamination_options'=>'Lamination Options',
                                    'die_cutting'=>'Die Cutting',
                                    'stripping'=>'Stripping',
                                    'blank_separation'=>'Blank Separation',
                                    'gluing'=>'Gluing Stage',
                                    'final_quality_control'=>'Send to Final QC'
                                ];
                                $completedStages = $job->completed_finishing_stages ?: [$job->status];
                            @endphp
                            @foreach($finishingStages as $statusKey => $label)
                                <label class="check-item" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; padding: 15px; border: 1px solid #e1e5eb; border-radius: 8px; cursor: pointer; gap: 10px;">
                                    <input type="checkbox" name="stages[]" value="{{ $statusKey }}" {{ in_array($statusKey, $completedStages) ? 'checked' : '' }} style="margin: 0; width: 18px; height: 18px; cursor: pointer;">
                                    <span style="font-weight: 500;">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="field"><label>Production Manager Notes</label><textarea name="notes" placeholder="Coating, lamination, die cutting, stripping, blank separation, gluing, packing or warehouse notes"></textarea></div>
                    <button class="action-btn green" type="submit">Update Production Manager Stage</button>
                </form>
                @endif

                @if($finalQcCanAct && $job->status === 'final_quality_control')
                <form action="{{ route('crm.production_jobs.final_qc',$job->id) }}" method="POST">{{ csrf_field() }}
                    <div class="notice-box">Final QC runs after gluing, before packing.</div>
                <div class="check-grid">
                    <label class="check-item"><input type="checkbox" name="dimension_check_passed" value="1"> Dimension Check</label>
                    <label class="check-item"><input type="checkbox" name="fold_color_check_passed" value="1"> Fold Color Check</label>
                    <label class="check-item"><input type="checkbox" name="quantity_check_passed" value="1"> Quantity Check</label>
                    <label class="check-item"><input type="checkbox" name="glue_strength_check_passed" value="1"> Glue Strength Check</label>
                    <label class="check-item"><input type="checkbox" name="barcode_scan_passed" value="1"> Barcode Scan</label>
                    <label class="check-item"><input type="checkbox" name="packaging_inspection_passed" value="1"> Packaging Inspection</label>
                </div>
                <div class="field"><label>Final QC Notes / Rejection Reason</label><textarea name="notes" placeholder="Required when rejecting or if any check fails"></textarea></div>
                <div style="display:flex; gap:10px;">
                    <button class="action-btn green" type="submit" name="action" value="approve">Approve Final QC</button>
                    <button class="action-btn" style="background:#ef4444;" type="submit" name="action" value="reject">Reject / Cancel Order</button>
                </div>
            </form>
                @endif

                @if(!in_array($job->status, ['pending_planning','scheduled','press_setup','adjustments_required','first_sheet_review','production_ready','full_production','in_process_checks','coating_options','lamination_options','die_cutting','stripping','blank_separation','gluing','final_quality_control','packing','palletization','warehouse_ready']))
                    <div class="notice-box">No action required at this stage.</div>
                @endif
            </div>

            <div class="panel">
                <h3 class="panel-title"><i class="fas fa-history"></i> Workflow History</h3>
                @forelse($job->logs as $log)
                    <div class="timeline-item"><strong>{{ str_replace('_',' ',$log->to_status) }}</strong> by {{ $log->user->name ?? 'System' }}<br>{{ $log->notes }}<br>{{ $log->created_at->format('M d, Y h:i A') }}</div>
                @empty
                    <div class="notice-box">No workflow history yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.job-facility-select').forEach(function (facilitySelect) {
        var form = facilitySelect.closest('form');
        var dependentSelects = form.querySelectorAll('.facility-dependent');
        function filterFacilityOptions() {
            var facilityId = facilitySelect.value;
            dependentSelects.forEach(function (select) {
                Array.prototype.forEach.call(select.options, function (option) {
                    if (!option.value) { option.hidden = false; return; }
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
