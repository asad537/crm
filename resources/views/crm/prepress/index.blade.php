@extends('crm.layout')

@section('title', 'Prepress Tickets')

@section('content')
<style>
    .prepress-shell { max-width: 1500px; margin: 0 auto; }
    .prepress-hero { display:flex; align-items:center; justify-content:space-between; gap:24px; margin-bottom:22px; }
    .prepress-eyebrow { color:var(--primary-purple); font-size:.75rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; margin-bottom:6px; }
    .prepress-heading { margin:0; color:#0f172a; font-size:2rem; font-weight:850; letter-spacing:-.035em; }
    .prepress-subtitle { margin:7px 0 0; color:#64748b; font-size:.96rem; }
    .prepress-hero-icon { width:58px; height:58px; border-radius:18px; display:grid; place-items:center; color:var(--primary-purple); background:linear-gradient(145deg,#eef2ff,#e0e7ff); font-size:1.35rem; box-shadow:0 12px 28px -18px rgba(var(--primary-rgb), .75); }

    .prepress-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:26px; }
    .prepress-stat { display:flex; align-items:center; gap:14px; min-height:82px; padding:15px 17px; background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 10px 28px -26px rgba(15,23,42,.75); }
    .stat-icon { width:42px; height:42px; flex:0 0 42px; border-radius:12px; display:grid; place-items:center; background:#eef2ff; color:var(--primary-purple); }
    .stat-value { display:block; color:#0f172a; font-size:1.35rem; line-height:1; font-weight:850; }
    .stat-label { display:block; margin-top:5px; color:#64748b; font-size:.72rem; font-weight:800; letter-spacing:.055em; text-transform:uppercase; }

    .queue-heading { display:flex; align-items:end; justify-content:space-between; gap:16px; margin:0 0 13px; }
    .queue-heading h2 { margin:0; color:#0f172a; font-size:1.12rem; font-weight:800; }
    .queue-heading span { color:#64748b; font-size:.82rem; font-weight:700; }
    .prepress-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(420px,1fr)); gap:18px; align-items:start; }
    .prepress-card { position:relative; overflow:hidden; background:#fff; border:1px solid #dfe6f0; border-radius:16px; box-shadow:0 18px 42px -34px rgba(15,23,42,.65); transition:transform .2s ease,box-shadow .2s ease; }
    .prepress-card:before { content:""; position:absolute; inset:0 0 auto; height:4px; background:linear-gradient(90deg,var(--primary-purple),var(--primary-purple)); }
    .prepress-card:hover { transform:translateY(-2px); box-shadow:0 24px 48px -34px rgba(15,23,42,.65); }
    .prepress-card-header { padding:22px 22px 17px; display:flex; justify-content:space-between; gap:18px; align-items:flex-start; border-bottom:1px solid #edf1f6; }
    .ticket-number { color:var(--primary-purple); font-size:.7rem; font-weight:850; letter-spacing:.09em; text-transform:uppercase; margin-bottom:5px; }
    .prepress-title { margin:0; color:#0f172a; font-size:1.16rem; font-weight:850; line-height:1.25; }
    .prepress-meta { display:flex; align-items:center; gap:7px; margin-top:8px; color:#64748b; font-size:.88rem; font-weight:650; }
    .prepress-meta i { color:#94a3b8; }
    .prepress-badge { background:#eef2ff; color:var(--primary-purple); border:1px solid #dfe3ff; border-radius:999px; font-size:.67rem; font-weight:850; letter-spacing:.055em; padding:7px 11px; text-transform:uppercase; white-space:nowrap; }
    .prepress-summary { padding:18px 22px 21px; }
    .summary-row { display:grid; grid-template-columns:1fr 1fr; gap:11px; margin-bottom:15px; }
    .summary-box { padding:12px 13px; background:#f8fafc; border:1px solid #e5eaf1; border-radius:11px; }
    .summary-label { display:block; color:#94a3b8; font-size:.66rem; font-weight:850; letter-spacing:.065em; text-transform:uppercase; }
    .summary-value { display:block; margin-top:4px; color:#1e293b; font-size:.88rem; font-weight:750; }
    .open-ticket-btn { width:100%; min-height:44px; display:flex; justify-content:center; align-items:center; gap:9px; border:1px solid #d9ddff; border-radius:11px; background:#f7f7ff; color:var(--primary-purple); font-weight:800; cursor:pointer; transition:.18s ease; }
    .open-ticket-btn:hover { background:var(--primary-purple); border-color:var(--primary-purple); color:#fff; }
    .prepress-workspace { display:none; padding:0 22px 22px; }
    .prepress-workspace.is-open { display:block; }
    .workspace-divider { height:1px; background:#edf1f6; margin-bottom:19px; }

    .proof-link { display:flex; align-items:center; justify-content:space-between; gap:12px; min-height:52px; padding:0 15px; border-radius:11px; border:1px solid #dce4ef; background:#f8fafc; color:#1e293b; font-weight:750; text-decoration:none; margin-bottom:18px; }
    .proof-link-main { display:flex; align-items:center; gap:11px; }
    .proof-link-main i { width:34px; height:34px; display:grid; place-items:center; color:var(--primary-purple); background:#e8eaff; border-radius:9px; }
    .proof-link small { display:block; margin-top:2px; color:#94a3b8; font-size:.68rem; font-weight:650; }
    .proof-link:hover { border-color:#bfc8f8; background:#f4f5ff; color:#312e81; }
    .ticket-section { margin-bottom:18px; }
    .ticket-section-title { display:flex; align-items:center; gap:8px; margin:0 0 10px; color:#1e293b; font-size:.9rem; font-weight:850; }
    .ticket-section-title i { color:var(--primary-purple); }
    .detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px; }
    .detail-item { padding:11px 12px; background:#f8fafc; border:1px solid #e5eaf1; border-radius:10px; min-width:0; }
    .detail-label { display:block; color:#94a3b8; font-size:.63rem; font-weight:850; letter-spacing:.06em; text-transform:uppercase; }
    .detail-value { display:block; margin-top:4px; color:#1e293b; font-size:.82rem; font-weight:750; word-break:break-word; }
    .detail-item.wide { grid-column:1/-1; }
    .notes-panel { padding:12px 13px; border:1px solid #e5eaf1; border-radius:10px; background:#fff; color:#334155; font-size:.82rem; line-height:1.55; white-space:pre-line; }
    .file-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:18px; }
    .file-actions .proof-link { margin-bottom:0; min-height:58px; }
    .check-section-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:11px; }
    .check-section-head h4 { margin:0; color:#1e293b; font-size:.91rem; font-weight:800; }
    .check-progress { color:#64748b; font-size:.72rem; font-weight:750; }
    .check-list { display:grid; grid-template-columns:1fr 1fr; gap:9px; margin-bottom:16px; }
    .check-item { display:flex; align-items:center; gap:10px; min-height:44px; padding:8px 11px; background:#f8fafc; border:1px solid #e3e9f1; border-radius:10px; color:#334155; font-size:.79rem; font-weight:700; cursor:pointer; transition:.16s ease; }
    .check-item:hover { border-color:#c7d2fe; background:#f7f7ff; }
    .check-item:has(input:checked) { border-color:#a7f3d0; background:#ecfdf5; color:#166534; }
    .check-item input { width:17px; height:17px; accent-color:#16a34a; flex:0 0 auto; }
    .select-all { border:0; padding:0; background:transparent; color:var(--primary-purple); font-size:.73rem; font-weight:800; cursor:pointer; }
    .field-label { display:block; margin:0 0 7px; color:#475569; font-size:.75rem; font-weight:800; }
    .prepress-textarea { width:100%; min-height:88px; padding:12px 13px; border:1px solid #d8e0eb; border-radius:10px; resize:vertical; outline:none; box-sizing:border-box; margin-bottom:13px; color:#1e293b; background:#fff; font:inherit; font-size:.83rem; transition:.18s ease; }
    .prepress-textarea:focus { border-color:var(--primary-purple); box-shadow:0 0 0 3px rgba(var(--primary-rgb), .11); }
    .prepress-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .prepress-btn { min-height:44px; display:flex; align-items:center; justify-content:center; gap:8px; border:0; border-radius:10px; cursor:pointer; font-weight:800; padding:0 14px; transition:.18s ease; }
    .prepress-btn-primary { background:#16a34a; color:white; box-shadow:0 10px 20px -14px rgba(22,163,74,.75); }
    .prepress-btn-primary:hover { background:#15803d; }
    .prepress-btn-danger { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
    .prepress-btn-danger:hover { background:#ffe4e6; }
    .revision-form { display:none; margin-top:13px; padding:14px; border:1px solid #fecdd3; border-radius:11px; background:#fff7f8; }
    .prepress-empty { grid-column:1/-1; padding:52px 20px; text-align:center; background:transparent; border:none; color:#64748b; }
    .prepress-empty i { font-size:2.1rem; color:#16a34a; margin-bottom:12px; display:block; }
    .prepress-empty h3 { margin:0 0 7px; color:#0f172a; }
    .prepress-empty p { margin:0; }

    @media(max-width:760px) {
        .prepress-grid,.check-list,.prepress-actions,.detail-grid,.file-actions { grid-template-columns:1fr; }
        .prepress-stats { grid-template-columns:1fr; }
        .prepress-hero { align-items:flex-start; }
        .prepress-hero-icon { display:none; }
        .prepress-heading { font-size:1.65rem; }
    }
</style>

<div class="prepress-shell">
    <div class="prepress-hero">
        <div>
            <div class="prepress-eyebrow">Quality Gate</div>
            <h1 class="prepress-heading">Prepress Tickets</h1>
            <p class="prepress-subtitle">Review artwork, complete technical checks and release approved jobs to production.</p>
        </div>
        <div class="prepress-hero-icon"><i class="fas fa-clipboard-check"></i></div>
    </div>

    <div class="prepress-stats">
        <div class="prepress-stat"><span class="stat-icon"><i class="fas fa-layer-group"></i></span><div><span class="stat-value">{{ $tickets->count() }}</span><span class="stat-label">Open Tickets</span></div></div>
        <div class="prepress-stat"><span class="stat-icon"><i class="fas fa-tasks"></i></span><div><span class="stat-value">{{ count($checks) }}</span><span class="stat-label">Required Checks</span></div></div>
        <div class="prepress-stat"><span class="stat-icon"><i class="fas fa-arrow-right"></i></span><div><span class="stat-value">Production</span><span class="stat-label">Next Workflow Stage</span></div></div>
    </div>

    <div class="queue-heading">
        <h2>Review Queue</h2>
        <span>{{ $tickets->count() }} {{ $tickets->count() === 1 ? 'ticket' : 'tickets' }}</span>
    </div>

    <div class="prepress-grid">
        @forelse($tickets as $ticket)
            @php
                $lead = $ticket->lead ?: new \App\CrmEmail;
                $latestProof = \App\ProofRevision::where('crm_email_id', $ticket->crm_email_id)->orderBy('version_number', 'desc')->first();
                $quantity = $lead->order_quantity ?: $lead->quantity;
                $sizeParts = array_filter([$lead->length, $lead->width, $lead->height], function ($value) { return $value !== null && $value !== ''; });
                $sizeText = count($sizeParts) ? implode(' x ', $sizeParts) . ($lead->unit ? ' ' . $lead->unit : '') : 'N/A';
                $customSpecs = is_array($lead->custom_specs) ? $lead->custom_specs : [];
                $artworkPath = $ticket->artwork_file_path ?: $lead->file_url;
                $artworkUrl = $artworkPath && preg_match('/^https?:\/\//', $artworkPath) ? $artworkPath : ($artworkPath ? asset($artworkPath) : null);
            @endphp

            <article class="prepress-card">
                <div class="prepress-card-header">
                    <div>
                        <div class="ticket-number">Prepress Ticket #{{ $ticket->id }}</div>
                        <h3 class="prepress-title">{{ $lead->client_name ?? 'Client' }}</h3>
                        <div class="prepress-meta"><i class="fas fa-box-open"></i>{{ $lead->product_name ?? 'N/A' }}</div>
                    </div>
                    <span class="prepress-badge">Awaiting Review</span>
                </div>

                <div class="prepress-summary">
                    <div class="summary-row">
                        <div class="summary-box"><span class="summary-label">Proof</span><span class="summary-value">{{ $latestProof ? 'Available' : 'Not attached' }}</span></div>
                        <div class="summary-box"><span class="summary-label">Quantity</span><span class="summary-value">{{ $quantity ? number_format($quantity) : 'N/A' }}</span></div>
                    </div>
                    <button type="button" class="open-ticket-btn" onclick="togglePrepressTicket({{ $ticket->id }}, this)">
                        <i class="fas fa-folder-open"></i><span>Open Prepress Ticket</span>
                    </button>
                </div>

                <div class="prepress-workspace" id="prepressTicket{{ $ticket->id }}">
                    <div class="workspace-divider"></div>
                    <div class="file-actions">
                        @if($artworkUrl)
                            <a href="{{ $artworkUrl }}" target="_blank" class="proof-link">
                                <span class="proof-link-main"><i class="fas fa-paperclip"></i><span>Original Artwork<small>Customer / sales uploaded file</small></span></span>
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        @endif
                        @if($latestProof)
                            <a href="{{ asset($latestProof->file_path) }}" target="_blank" class="proof-link">
                                <span class="proof-link-main"><i class="fas fa-file-download"></i><span>Approved Proof<small>Latest client-approved proof</small></span></span>
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        @endif
                    </div>

                    <div class="ticket-section">
                        <h4 class="ticket-section-title"><i class="fas fa-clipboard-list"></i> Job Packet Details</h4>
                        <div class="detail-grid">
                            <div class="detail-item"><span class="detail-label">Order ID</span><span class="detail-value">#{{ $ticket->id }}</span></div>
                            @if(!Auth::guard('crm')->user()->isPrepress())
                            <div class="detail-item"><span class="detail-label">Customer Email</span><span class="detail-value">{{ $lead->client_email ?: 'N/A' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Phone</span><span class="detail-value">{{ $lead->client_phone ?: 'N/A' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Shipping Region</span><span class="detail-value">{{ $lead->shipping_region ?: ($lead->country ?: 'N/A') }}</span></div>
                            @endif
                            <div class="detail-item"><span class="detail-label">Product</span><span class="detail-value">{{ $lead->product_name ?: 'N/A' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Size</span><span class="detail-value">{{ $sizeText }}</span></div>
                            <div class="detail-item"><span class="detail-label">Quantity</span><span class="detail-value">{{ $quantity ? number_format($quantity) : 'N/A' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Stock</span><span class="detail-value">{{ $lead->stock ?: 'N/A' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Color</span><span class="detail-value">{{ $lead->color ?: 'N/A' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Coating</span><span class="detail-value">{{ $lead->coating ?: 'N/A' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Lamination</span><span class="detail-value">{{ $lead->lamination ?: 'N/A' }}</span></div>
                            <div class="detail-item"><span class="detail-label">Die / Glue</span><span class="detail-value">{{ trim(($lead->die ?: 'N/A') . ' / ' . ($lead->glue ?: 'N/A')) }}</span></div>
                            @if(!Auth::guard('crm')->user()->isPrepress())
                            <div class="detail-item"><span class="detail-label">Payment Term</span><span class="detail-value">{{ ucfirst(str_replace('_',' ', $ticket->payment_term)) }}</span></div>
                            <div class="detail-item"><span class="detail-label">Payment Status</span><span class="detail-value">{{ ucfirst(str_replace('_',' ', $ticket->payment_status)) }}</span></div>
                            @endif
                        </div>
                    </div>

                    @if(count($customSpecs))
                    <div class="ticket-section">
                        <h4 class="ticket-section-title"><i class="fas fa-sliders-h"></i> Custom Specs</h4>
                        <div class="detail-grid">
                            @foreach($customSpecs as $specKey => $specValue)
                                <div class="detail-item"><span class="detail-label">{{ ucwords(str_replace('_',' ', $specKey)) }}</span><span class="detail-value">{{ is_array($specValue) ? implode(', ', $specValue) : $specValue }}</span></div>
                            @endforeach
                        </div>
                    </div>
                    @endif



                    @if(!$ticket->is_plate_created)
                        <form action="{{ route('crm.prepress_tickets.create_plate', $ticket->id) }}" method="POST" class="prepress-check-form" style="margin-bottom: 20px; padding: 15px; background: #f8fafc; border: 1px solid #e5eaf1; border-radius: 10px;">
                            {{ csrf_field() }}
                            <h4 style="margin-top: 0; margin-bottom: 15px; color: #1e293b; font-size: 1rem;"><i class="fas fa-industry"></i> Production Routing & Plate Creation</h4>
                            
                            <label class="field-label">Select Production Facility</label>
                            <select name="production_facility_id" required class="prepress-textarea" style="min-height: 44px; margin-bottom: 15px;">
                                <option value="">-- Select Facility --</option>
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}" {{ $ticket->production_facility_id == $facility->id ? 'selected' : '' }}>{{ $facility->name }} ({{ $facility->city }})</option>
                                @endforeach
                            </select>

                            <div style="margin-bottom: 15px;">
                                <label class="check-item" style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; background: white; margin-bottom: 0; display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="color_match_check" required style="margin-right: 10px;">
                                    <span style="font-weight: 600; color: #334155;"><i class="fas fa-palette" style="color: var(--primary-purple); margin-right: 5px;"></i> I confirm the Design and Color Match with the physical proof/requirements.</span>
                                </label>
                            </div>

                            <button type="submit" class="prepress-btn prepress-btn-primary" style="width: 100%;"><i class="fas fa-layer-group"></i> Create Plate</button>
                        </form>
                    @else
                        <form action="{{ route('crm.prepress_tickets.complete', $ticket->id) }}" method="POST" enctype="multipart/form-data" class="prepress-check-form">
                            {{ csrf_field() }}
                            <div class="check-section-head">
                                <h4>
                                    Technical Checklist
                                    @php
                                        $selectedFacility = $facilities->firstWhere('id', $ticket->production_facility_id);
                                    @endphp
                                    @if($selectedFacility)
                                        <span style="font-size: 0.85rem; color: #64748b; font-weight: normal; margin-left: 10px; background: #f1f5f9; padding: 4px 8px; border-radius: 4px;"><i class="fas fa-industry"></i> {{ $selectedFacility->name }}</span>
                                    @endif
                                </h4>
                                <div><span class="check-progress">0 / {{ count($checks) }} complete</span> &nbsp; <button type="button" class="select-all" onclick="toggleAllChecks(this)">Select all</button></div>
                            </div>
                            <div class="check-list">
                                @foreach($checks as $key => $label)
                                    <label class="check-item"><input type="checkbox" name="checks[]" value="{{ $key }}" onchange="updateCheckProgress(this)"><span>{{ $label }}</span></label>
                                @endforeach
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr; gap: 15px; margin-bottom: 15px;">
                                <div>
                                    <label class="field-label">Production Handoff Notes</label>
                                    <textarea name="prepress_notes" class="prepress-textarea" placeholder="Add plate details, technical notes, or production handoff instructions..." style="margin-bottom: 0;"></textarea>
                                </div>
                                
                                <div>
                                    <label class="field-label">Attach First Sheet / QC Sheet (Optional)</label>
                                    <div style="position: relative;">
                                        <input type="file" name="qc_sheet" class="prepress-textarea" style="padding-left: 40px; min-height: 48px; background: #f8fafc; padding-top: 13px; margin-bottom: 0;">
                                        <i class="fas fa-file-pdf" style="position: absolute; left: 14px; top: 16px; color: #94a3b8; font-size: 1.1rem;"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="prepress-actions">
                                <button type="submit" class="prepress-btn prepress-btn-primary"><i class="fas fa-check-circle"></i> Send to Production Manager</button>
                                <button type="button" class="prepress-btn prepress-btn-danger" onclick="toggleRevisionForm({{ $ticket->id }})"><i class="fas fa-undo"></i> Return to Design</button>
                            </div>
                        </form>
                    @endif

                    <form id="revisionForm{{ $ticket->id }}" class="revision-form" action="{{ route('crm.prepress_tickets.send_back', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <label class="field-label">Revision Required</label>
                        <textarea name="revision_notes" class="prepress-textarea" required placeholder="Clearly explain what Design needs to correct..."></textarea>

                        <label class="field-label" style="margin-top:4px;">Attach Reference File <span style="color:#94a3b8; font-weight:500;">(Optional — image, PDF, etc.)</span></label>
                        <div style="position:relative; margin-bottom:13px;">
                            <input type="file" name="revision_attachment"
                                style="width:100%; padding:11px 11px 11px 42px; border:1px dashed #d8e0eb; border-radius:10px; background:#f8fafc; color:#334155; font-size:.82rem; box-sizing:border-box; cursor:pointer; outline:none;"
                                accept=".jpg,.jpeg,.png,.gif,.pdf,.ai,.eps,.psd,.svg,.zip,.doc,.docx">
                            <i class="fas fa-paperclip" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:1rem; pointer-events:none;"></i>
                        </div>

                        @if($ticket->prepress_revision_attachment)
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:13px; padding:9px 12px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:9px;">
                            <i class="fas fa-paperclip" style="color:#475569; font-size:0.85rem;"></i>
                            <span style="font-size:0.78rem; color:#475569; font-weight:700;">Previous attachment:</span>
                            <a href="{{ asset($ticket->prepress_revision_attachment) }}" target="_blank"
                               style="font-size:0.78rem; color:var(--primary-purple); font-weight:700; text-decoration:none; word-break:break-all;">
                                {{ basename($ticket->prepress_revision_attachment) }}
                            </a>
                        </div>
                        @endif

                        <button type="submit" class="prepress-btn prepress-btn-danger"><i class="fas fa-paper-plane"></i> Confirm Revision Request</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="prepress-empty"><i class="fas fa-check-double"></i><h3>Prepress queue is clear</h3><p>Approved designs will appear here when they are ready for technical review.</p></div>
        @endforelse
    </div>
</div>

<script>
    function togglePrepressTicket(ticketId, button) {
        var panel = document.getElementById('prepressTicket' + ticketId);
        var isOpen = panel.classList.toggle('is-open');
        button.querySelector('span').textContent = isOpen ? 'Close Prepress Ticket' : 'Open Prepress Ticket';
        button.querySelector('i').className = isOpen ? 'fas fa-folder-minus' : 'fas fa-folder-open';
    }

    function updateCheckProgress(input) {
        var form = input.closest('.prepress-check-form');
        var checks = form.querySelectorAll('input[name="checks[]"]');
        var checked = form.querySelectorAll('input[name="checks[]"]:checked').length;
        form.querySelector('.check-progress').textContent = checked + ' / ' + checks.length + ' complete';
        form.querySelector('.select-all').textContent = checked === checks.length ? 'Clear all' : 'Select all';
    }

    function toggleAllChecks(button) {
        var form = button.closest('.prepress-check-form');
        var checks = form.querySelectorAll('input[name="checks[]"]');
        var shouldCheck = form.querySelectorAll('input[name="checks[]"]:checked').length !== checks.length;
        checks.forEach(function (check) { check.checked = shouldCheck; });
        if (checks.length) updateCheckProgress(checks[0]);
    }

    function toggleRevisionForm(ticketId) {
        var form = document.getElementById('revisionForm' + ticketId);
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
    }

    @if(session('open_ticket'))
    document.addEventListener('DOMContentLoaded', function() {
        var ticketId = {{ session('open_ticket') }};
        var panel = document.getElementById('prepressTicket' + ticketId);
        if(panel) {
            var btn = panel.previousElementSibling.querySelector('.open-ticket-btn');
            if(btn) btn.click();
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
    @endif
</script>
@endsection
