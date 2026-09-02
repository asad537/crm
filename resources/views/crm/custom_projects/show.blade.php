@extends('crm.layout')

@section('title', 'Project Details - ' . $project->project_name)

@section('styles')
    /* Hide generic CRM breadcrumb */
    .content-header { display: none !important; }

    :root {
        --tab-active-color: var(--primary-purple);
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        --primary-indigo: var(--primary-purple);
        --secondary-slate: #64748b;
        --text-main: #1e293b;
    }

    /* Compact Dashboard Header */
    .dashboard-header {
        background: white;
        background-image: linear-gradient(to right, #ffffff, #f8fafc);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-left-group {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .back-btn-modern {
        width: 36px;
        height: 36px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-indigo);
        text-decoration: none;
        transition: all 0.2s;
    }

    .back-btn-modern:hover {
        background: var(--primary-indigo);
        color: white;
        transform: translateX(-2px);
    }

    .title-set h1 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
    }

    .meta-stripe {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        margin-top: 3px;
    }

    .ref-badge {
        font-family: 'Monaco', monospace;
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--primary-indigo);
        background: #eef2ff;
        padding: 2px 8px;
        border-radius: 4px;
        border: 1px solid #e0e7ff;
    }

    .header-right-group {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .stat-pill {
        display: flex;
        flex-direction: column;
        gap: 1px;
    }

    .stat-pill .label {
        font-size: 0.6rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .stat-pill .value {
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
    }

    .phase-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Compact Card */
    .modern-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        border: 1px solid #f1f5f9;
        margin-bottom: 1.25rem;
        overflow: hidden;
    }

    .card-header {
        padding: 0.875rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fafafa;
    }

    .card-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Compact Tabs */
    .tabs-container {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.25rem;
        border-bottom: 2px solid #f1f5f9;
        padding: 0 0.25rem;
    }

    .tab-btn {
        padding: 0.75rem 0.375rem;
        font-weight: 600;
        font-size: 0.8rem;
        color: var(--secondary-slate);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
        background: none;
        border-left: none;
        border-right: none;
        border-top: none;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .tab-btn:hover {
        color: var(--tab-active-color);
    }

    .tab-btn.active {
        color: var(--tab-active-color);
        border-bottom-color: var(--tab-active-color);
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.2s ease;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    /* Compact Image Cards */
    .pic-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .pic-card {
        background: #f8fafc;
        border-radius: 8px;
        padding: 6px;
        border: 1px solid #e2e8f0;
        transition: transform 0.15s;
    }

    .pic-card:hover {
        transform: scale(1.02);
    }

    .pic-card img {
        width: 100%;
        height: 110px;
        object-fit: cover;
        border-radius: 6px;
        margin-bottom: 6px;
    }

    .pic-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #64748b;
        text-align: center;
        text-transform: uppercase;
    }

    /* Compact Specs */
    .spec-item {
        padding: 0.625rem 0;
        border-bottom: 1px dashed #f1f5f9;
        display: flex;
        justify-content: space-between;
    }

    .spec-label { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
    .spec-value { font-size: 0.75rem; color: #1e293b; font-weight: 600; }

    .status-pill {
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .action-btn {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
        transition: all 0.15s;
        cursor: pointer;
    }

    .action-btn:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .dieline-table {
        width: 100%;
        border-collapse: collapse;
    }

    .dieline-table th {
        text-align: left;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.7rem;
        text-transform: uppercase;
    }

    .dieline-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        font-size: 0.8rem;
    }

    .status-pending { background: #fef3c7; color: #92400e; }
    .status-approved { background: #dcfce7; color: #166534; }
    .status-change_requested { background: #fee2e2; color: #991b1b; }

@endsection

@section('content')
<!-- Premium Dashboard Header -->
<div class="dashboard-header">
    <div class="header-left-group">
        <a href="{{ route('crm.app_projects') }}" class="back-btn-modern" title="Return to List">
            <i class="fas fa-chevron-left"></i>
        </a>
        <div class="title-set">
            <h1>{{ $project->project_name }}</h1>
            <div class="meta-stripe">
                <span class="ref-badge">ID: MBP-{{ str_pad($project->id + 9000, 4, '0', STR_PAD_LEFT) }}</span>
                @if($project->sampleOrder)
                    <span class="phase-badge" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe;">
                        <i class="fas fa-vial"></i> Sampling Active
                    </span>
                @else
                    <span class="phase-badge" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">
                         <i class="fas fa-magic"></i> Design Phase
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="header-right-group">
        <div class="stat-pill">
            <span class="label">Customer</span>
            <span class="value" style="color: var(--primary-indigo);">{{ $project->user->name ?? 'Client' }}</span>
        </div>
        <div class="stat-pill">
            <span class="label">Product Category</span>
            <span class="value">{{ $project->category_name }}</span>
        </div>
        <div class="stat-pill" style="text-align: right;">
            <span class="label">Submitted On</span>
            <span class="value">{{ $project->created_at->format('M d, Y') }}</span>
        </div>
        <div style="margin-left: 1rem;">
            <form action="{{ route('crm.app_projects.destroy', $project->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this entire project? This will also remove all associated dielines, mockups, and orders.')">
                @csrf @method('DELETE')
                <button type="submit" class="action-btn" style="background: #fee2e2; color: #ef4444; border-color: #fecaca; width: 38px; height: 38px;" title="Delete Project">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="tabs-container">
    <button class="tab-btn {{ (!$project->sampleOrder && !$project->productionOrders->count()) ? 'active' : '' }}" onclick="openTab(event, 'overview-tab')">
        <i class="fas fa-th-large"></i> Project Overview
    </button>
    <button class="tab-btn" onclick="openTab(event, 'design-tab')">
        <i class="fas fa-layer-group"></i> Design Assets
    </button>
    @if($project->sampleOrder)
    <button class="tab-btn {{ ($project->sampleOrder && !$project->productionOrders->count()) ? 'active' : '' }}" onclick="openTab(event, 'sample-tab')">
        <i class="fas fa-shipping-fast"></i> Sample Tracking
    </button>
    @endif
    @if($project->productionOrders && $project->productionOrders->count() > 0)
    <button class="tab-btn active" onclick="openTab(event, 'production-tab')">
        <i class="fas fa-industry"></i> Production Orders
    </button>
    @endif
</div>

@if(session('error') || $errors->any())
    <div style="margin: 1rem 1.5rem; padding: .9rem 1.1rem; background:#fef2f2; border:1px solid #fca5a5; border-radius:10px; color:#991b1b; font-weight:600;">
        <i class="fas fa-exclamation-circle"></i>
        {{ session('error') ?: $errors->first() }}
    </div>
@endif

<!-- Tab 1: Overview -->
<div id="overview-tab" class="tab-content {{ (!$project->sampleOrder && !$project->productionOrders->count()) ? 'active' : '' }}">
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <!-- Specs Column -->
        <div class="modern-card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-ruler-combined"></i> Specifications</div>
            </div>
            <div style="padding: 1.5rem;">
                <div class="spec-item"><span class="spec-label">Category</span> <span class="spec-value">{{ $project->category_name }}</span></div>
                <div class="spec-item"><span class="spec-label">Sub Category</span> <span class="spec-value">{{ $project->subcategory_name }}</span></div>
                <div class="spec-item"><span class="spec-label">Product Name</span> <span class="spec-value">{{ $project->product_name }}</span></div>
                <div class="spec-item"><span class="spec-label">Material</span> <span class="spec-value">{{ $project->material_name }}</span></div>
                @php
                    $hasDims = $project->width || $project->height || $project->length;
                    $dimsText = $hasDims
                        ? trim(($project->length ?: '—').' × '.($project->width ?: '—').' × '.($project->height ?: '—').' '.$project->unit)
                        : 'Not specified';
                @endphp
                <div class="spec-item"><span class="spec-label">Dimensions (L × W × H)</span> <span class="spec-value" style="{{ $hasDims ? '' : 'color:#94a3b8;' }}">{{ $dimsText }}</span></div>

                @if(!empty($project->project_description))
                <div style="margin-top: 1.5rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; margin-bottom: 0.5rem;">Description</div>
                    <p style="font-size: 0.9rem; color: #475569; line-height: 1.6; background: #f8fafc; padding: 1rem; border-radius: 8px; white-space: pre-line;">{{ $project->project_description }}</p>
                </div>
                @endif

                <div style="margin-top: 1.5rem;">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; margin-bottom: 0.5rem;">Notes from Client</div>
                    <p style="font-size: 0.9rem; color: #475569; line-height: 1.6; background: #f8fafc; padding: 1rem; border-radius: 8px;">
                        {{ $project->message ?: 'No additional message.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Project Images ("See Pic") -->
        <div>
            <div class="modern-card">
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-images"></i> Project Imagery</div>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="pic-grid">
                        @if($project->subcategory_image)
                        <div class="pic-card">
                            @php $src = Str::startsWith($project->subcategory_image, 'http') ? $project->subcategory_image : url($project->subcategory_image); @endphp
                            <img src="{{ $src }}" alt="Category">
                            <div class="pic-label">Category</div>
                        </div>
                        @endif
                        @if($project->product_image)
                        <div class="pic-card">
                            @php $src = Str::startsWith($project->product_image, 'http') ? $project->product_image : url($project->product_image); @endphp
                            <img src="{{ $src }}" alt="Product">
                            <div class="pic-label">Product Style</div>
                        </div>
                        @endif
                        @if($project->material_image)
                        <div class="pic-card">
                            @php $src = Str::startsWith($project->material_image, 'http') ? $project->material_image : url($project->material_image); @endphp
                            <img src="{{ $src }}" alt="Material">
                            <div class="pic-label">Material Info</div>
                        </div>
                        @endif
                        @if($project->addon_image)
                        <div class="pic-card">
                            @php $src = Str::startsWith($project->addon_image, 'http') ? $project->addon_image : url($project->addon_image); @endphp
                            <img src="{{ $src }}" alt="Addon">
                            <div class="pic-label">Addons</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab 2: Design Assets -->
<div id="design-tab" class="tab-content">
    <div class="modern-card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-drafting-compass" style="color: #10b981;"></i> Manage Dielines</div>
        </div>
        
        @php 
            $dielineRequest = $project->dielines->firstWhere('status', 'pending_company_design'); 
            $mockupRequestCount = 0;
            foreach($project->dielines as $dl) {
                $mockupRequestCount += $dl->mockups->where('status', 'pending_company_design')->count();
            }
        @endphp

        @if($dielineRequest || $mockupRequestCount > 0)
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #f59e0b; border-radius: 10px; padding: 1rem 1.5rem; margin: 1.5rem; display: flex; align-items: center; gap: 1rem;">
            <i class="fas fa-paint-brush" style="font-size: 1.5rem; color: #d97706;"></i>
            <div style="flex: 1;">
                <div style="font-weight: 700; color: #92400e; font-size: 0.95rem;">⚠️ Attention: Design Fulfillment Needed</div>
                <div style="font-size: 0.85rem; color: #78350f;">
                    @if($dielineRequest) • <strong>Dieline Request</strong> pending. @endif
                    @if($mockupRequestCount > 0) • <strong>{{ $mockupRequestCount }} Mockup Request(s)</strong> pending. @endif
                </div>
            </div>
        </div>
        @endif

        <div style="padding: 0; overflow-x: auto;">
            <table class="dieline-table">
                <thead>
                    <tr>
                        <th style="padding-left: 1.5rem;">File Name</th>
                        <th>Size</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->dielines as $dieline)
                    <tr style="{{ $dieline->status == 'pending_company_design' ? 'background: #fffbeb;' : '' }}">
                        <td style="padding-left: 1.5rem;">
                            @if(!$dieline->file_path)
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-hourglass-half" style="color: #f59e0b;"></i>
                                    <span style="font-weight: 600; color: #92400e;">
                                        {{ $dieline->status == 'pending_company_design' ? 'Design Requested by Customer' : 'Dieline file not uploaded' }}
                                    </span>
                                </div>
                            @else
                                @php 
                                    $ext = strtolower(pathinfo($dieline->file_path ?? '', PATHINFO_EXTENSION)); 
                                    $isImg = in_array($ext, ['jpg','jpeg','png','webp','gif']);
                                @endphp
                                <a href="{{ url($dieline->file_path) }}" target="_blank" style="text-decoration: none; display: inline-block;">
                                    <div style="width: 140px; height: 100px; border-radius: 10px; overflow: hidden; background: #f8fafc; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.08);" onmouseover="this.style.borderColor='var(--primary-purple)'; this.style.transform='scale(1.03)'; this.style.boxShadow='0 4px 12px rgba(var(--primary-rgb), 0.2)';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='scale(1)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)';">
                                        @if($isImg && $dieline->file_path)
                                            <img src="{{ url($dieline->file_path) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <i class="fas fa-file-alt" style="color: #94a3b8; font-size: 2.5rem;"></i>
                                        @endif
                                    </div>
                                </a>
                            @endif
                        </td>
                        <td>{{ $dieline->file_size }}</td>
                        <td>
                            <form action="{{ route('crm.dielines.update_status', $dieline->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" 
                                        style="padding: 4px 8px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.8rem; font-weight: 700;"
                                        class="status-{{ $dieline->status }}">
                                    <option value="pending" {{ $dieline->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ $dieline->status == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="change_requested" {{ $dieline->status == 'change_requested' ? 'selected' : '' }}>Change Requested</option>
                                </select>
                            </form>
                            @if($dieline->change_request_comment)
                                <div style="margin-top: 8px; padding: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; border-left: 4px solid #64748b; border-radius: 8px; font-size: 0.85rem; color: #1e293b;">
                                    <div style="font-weight: 800; font-size: 0.7rem; text-transform: uppercase; color: #64748b; margin-bottom: 4px; display: flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-comment-dots"></i> Client Message
                                    </div>
                                    <div style="max-height: 85px; overflow-y: auto; line-height: 1.5; padding-right: 5px;">
                                        {{ $dieline->change_request_comment }}
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td><div style="font-size: 0.8rem; color: #64748b;">{{ $dieline->created_at->diffForHumans() }}</div></td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                @if($dieline->file_path)
                                <a href="{{ url($dieline->file_path) }}" target="_blank" class="action-btn" style="background: var(--primary-purple); color: white; border-color: var(--primary-purple);" title="View File"><i class="fas fa-eye"></i></a>
                                @else
                                <form action="{{ route('crm.dielines.fulfill', $dieline->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <label for="f-{{ $dieline->id }}" class="action-btn" style="background:#f59e0b; color:white; border:none; cursor: pointer;" title="Choose dieline file"><i class="fas fa-file-upload"></i></label>
                                    <input type="file" name="file" id="f-{{ $dieline->id }}" style="display:none">
                                    <button type="submit" class="action-btn" style="background:#16a34a; color:white; border:none; cursor:pointer;" title="Upload selected dieline"><i class="fas fa-check"></i></button>
                                </form>
                                @endif
                                <form action="{{ route('crm.dielines.destroy', $dieline->id) }}" method="POST" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn" style="color:#ef4444; border-color: #fecaca;" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @if($dieline->file_path || $dieline->mockups->count() > 0)
                    <tr>
                        <td colspan="5" style="background: #fdfcfe; padding: 0;">
                            <div style="padding: 1rem 1rem 1rem 3rem; border-left: 4px solid var(--primary-indigo);">
                                <div style="font-weight: 700; color: #64748b; font-size: 0.75rem; margin-bottom: 0.75rem;">ASSOCIATED MOCKUPS</div>
                                <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                                    @foreach($dieline->mockups as $mockup)
                                    <div style="background: white; border: 1px solid {{ $mockup->is_company ? '#e2e8f0' : '#fbbf24' }}; border-radius: 10px; padding: 0.5rem; width: 180px; position: relative; box-shadow: 0 1px 3px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;" 
                                         onclick="if(!event.target.closest('.no-click')){ window.open('{{ url($mockup->file_path ?? '#') }}', '_blank'); }">
                                        {{-- Mockup Preview --}}
                                        @php $ext = strtolower(pathinfo($mockup->file_path ?? '', PATHINFO_EXTENSION)); $isImg = in_array($ext, ['jpg','jpeg','png','webp']); @endphp
                                        @if($mockup->status == 'pending_company_design')
                                            <div style="height: 100px; background: #fff5eb; border: 2px dashed #f59e0b; border-radius: 6px; display: flex; align-items:center; justify-content:center; flex-direction:column; gap:4px;">
                                                <i class="fas fa-file-signature" style="color:#f59e0b;"></i>
                                                <span style="font-size:0.6rem; font-weight:700; color:#92400e;">REQUESTED</span>
                                            </div>
                                        @elseif($isImg && $mockup->file_path)
                                            <div style="position: relative; height: 100px;">
                                                <img src="{{ url($mockup->file_path) }}" style="width:100%; height:100%; object-fit:cover; border-radius:6px;">
                                            </div>
                                        @else
                                            <div style="height:100px; background:#f1f5f9; border-radius:6px; display:flex; align-items:center; justify-content:center; position: relative;">
                                                <i class="fas fa-file" style="font-size:2rem; color:#cbd5e1;"></i>
                                            </div>
                                        @endif
                                        <div style="font-size: 0.75rem; font-weight: 700; color: #1e293b; margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $mockup->file_name }}</div>
                                        
                                        @if($mockup->change_request_comment)
                                            <div style="margin-top: 10px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid var(--primary-purple); border-radius: 8px; font-size: 0.8rem; color: #1e293b;">
                                                <div style="font-weight: 800; font-size: 0.65rem; text-transform: uppercase; color: #64748b; margin-bottom: 3px; display: flex; align-items: center; gap: 4px;">
                                                    <i class="fas fa-comment"></i> Client Message
                                                </div>
                                                <div style="max-height: 75px; overflow-y: auto; line-height: 1.4; padding-right: 4px;">
                                                    {{ $mockup->change_request_comment }}
                                                </div>
                                            </div>
                                        @endif
                                        
                                        @if($mockup->status == 'pending_company_design')
                                            <form action="{{ route('crm.mockups.fulfill', $mockup->id) }}" method="POST" enctype="multipart/form-data" style="margin-top:5px;" class="no-click">
                                                @csrf
                                                <label for="fm-{{ $mockup->id }}" style="display:block; background:#f59e0b; color:white; text-align:center; padding:4px; border-radius:4px; font-size:0.65rem; font-weight:800; cursor:pointer;">UPLOAD MOCKUP</label>
                                                <input type="file" name="file" id="fm-{{ $mockup->id }}" style="display:none">
                                                <button type="submit" style="display:block; width:100%; background:#16a34a; color:white; text-align:center; padding:4px; border:none; border-radius:4px; font-size:0.65rem; font-weight:800; cursor:pointer; margin-top:4px;">UPLOAD MOCKUP</button>
                                            </form>
                                        @endif
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;" class="no-click">
                                            <form action="{{ route('crm.mockups.update_status', $mockup->id) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <select name="status" onchange="this.form.submit()" style="font-size: 0.65rem; border-radius: 4px; border: 1px solid #e2e8f0; font-weight:700; cursor: pointer;">
                                                    <option value="pending" {{ $mockup->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="approved" {{ $mockup->status == 'approved' ? 'selected' : '' }}>Approved</option>
                                                    <option value="change_requested" {{ $mockup->status == 'change_requested' ? 'selected' : '' }}>Change</option>
                                                </select>
                                            </form>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <a href="{{ url($mockup->file_path ?? '#') }}" target="_blank" style="color: var(--primary-purple); font-size: 0.85rem; text-decoration: none;" title="View Mockup">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="{{ route('crm.mockups.destroy', $mockup->id) }}" method="POST">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" style="border:none; background:none; color:#ef4444; cursor:pointer; font-size:0.85rem;" title="Delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    @if($dieline->file_path)
                                    <div style="width: 180px; height: 145px; border: 2px dashed #e2e8f0; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #fafafa;">
                                        <form action="{{ route('crm.dielines.upload_mockup', $dieline->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <label for="nm-{{ $dieline->id }}" style="cursor: pointer; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align: center; gap:6px;">
                                                <i class="fas fa-plus" style="color: #cbd5e1; font-size: 1.5rem;"></i>
                                                <div style="font-size: 0.65rem; color: #94a3b8; font-weight: 800;">ADD MOCKUP</div>
                                            </label>
                                            <input type="file" name="file" id="nm-{{ $dieline->id }}" style="display:none" onchange="this.form.submit()">
                                        </form>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr><td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8; font-weight: 600;">No assets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upload Box -->
    <div class="modern-card" style="border: 2px dashed #e2e8f0; background: #f8fafc; padding: 2rem; text-align: center;">
        <form action="{{ route('crm.app_projects.upload_dieline', $project->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <h4 style="margin: 0 0 1rem 0; font-weight: 800; color: #1e293b;">Deploy New Design Work</h4>
            <div style="max-width: 450px; margin: 0 auto; display: flex; flex-direction: column; gap: 0.75rem;">
                <input type="text" name="file_name" placeholder="File Label (Optional)" style="padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 0.5rem;">
                    <input type="file" name="file" required style="flex: 1; padding: 8px; background: white; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <button type="submit" style="background: var(--primary-indigo); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700;">Upload Dieline</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tab 3: Sample Tracking -->
@if($project->sampleOrder)
<div id="sample-tab" class="tab-content {{ ($project->sampleOrder && !$project->productionOrders->count()) ? 'active' : '' }}">
    <div class="modern-card" style="border-left: 5px solid var(--primary-purple);">
        <div class="card-header" style="background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <div class="card-title">
                <i class="fas fa-vial"></i> Sample Order #SO-{{ str_pad($project->sampleOrder->id + 3000, 4, '0', STR_PAD_LEFT) }}
                <span class="status-pill" style="margin-left: 10px; background: #e0e7ff; color: var(--primary-purple);">{{ strtoupper(str_replace('_', ' ', $project->sampleOrder->status)) }}</span>
            </div>
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 1rem;">
                <span>Submitted: {{ $project->sampleOrder->created_at->format('M d, Y h:i A') }}</span>
                <form action="{{ route('crm.samples.destroy', $project->sampleOrder->id) }}" method="POST" onsubmit="return confirm('Delete this sample request?')">
                    @csrf @method('DELETE')
                    <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font-size: 0.9rem;" title="Delete Sample Request">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <div style="padding: 1.5rem; display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div>
                        <div class="spec-label">Contact Person</div>
                        <div class="spec-value">{{ $project->sampleOrder->contact_name }}</div>
                        <div style="font-size: 0.8rem; color: #64748b;">{{ $project->sampleOrder->contact_phone }} | {{ $project->sampleOrder->contact_email }}</div>
                    </div>
                    <div>
                        <div class="spec-label">Sample Specs</div>
                        <div class="spec-value">{{ strtoupper($project->sampleOrder->sample_type) }}</div>
                        <div style="font-weight: 800; color: #1e293b;">{{ number_format($project->sampleOrder->quantity) }} Units</div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; padding-top: 1rem; border-top: 1px dashed #e2e8f0;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div style="width: 28px; height: 28px; background: #eff6ff; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-truck" style="color: #3b82f6; font-size: 0.75rem;"></i>
                            </div>
                            <span style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Shipping Address</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #334155; line-height: 1.6; background: #f8fafc; padding: 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-weight: 500;">
                            {{ $project->sampleOrder->shipping_address }}
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div style="width: 28px; height: 28px; background: #f0fdf4; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-file-invoice" style="color: #22c55e; font-size: 0.75rem;"></i>
                            </div>
                            <span style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Billing Address</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #334155; line-height: 1.6; background: #f8fafc; padding: 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-weight: 500;">
                            {{ $project->sampleOrder->billing_address }}
                        </div>
                    </div>
                </div>

                <div style="background: #fafafa; padding: 1rem; border-radius: 12px; border: 1px solid #f1f5f9;">
                    @if(!$project->sampleOrder->is_price_provided)
                        <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                            <i class="fas fa-lock" style="color:#ea580c;"></i>
                            <span style="font-size:0.8rem; color:#9a3412; font-weight:600;">Status is locked — <strong>Activate Bill</strong> first to move beyond Pending Review.</span>
                        </div>
                    @endif
                    <form action="{{ route('crm.samples.update_status', $project->sampleOrder->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <label style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Manage Sample Pipeline</label>
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                            <select name="status" class="form-control" style="flex: 1; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; padding: 0 10px;">
                                <option value="pending_review" {{ $project->sampleOrder->status == 'pending_review' ? 'selected' : '' }}>0. Pending Review (New)</option>
                                <option value="processed" {{ $project->sampleOrder->status == 'processed' ? 'selected' : '' }} {{ !$project->sampleOrder->is_price_provided ? 'disabled' : '' }}>1. Processed (Order Confirmed)</option>
                                <option value="produced"  {{ $project->sampleOrder->status == 'produced'  ? 'selected' : '' }} {{ !$project->sampleOrder->is_price_provided ? 'disabled' : '' }}>2. Produced (Manufacturing Done)</option>
                                <option value="shipping"  {{ $project->sampleOrder->status == 'shipping'  ? 'selected' : '' }} {{ !$project->sampleOrder->is_price_provided ? 'disabled' : '' }}>3. Shipping (Tracking Assigned)</option>
                                <option value="delivered"  {{ $project->sampleOrder->status == 'delivered'  ? 'selected' : '' }} {{ !$project->sampleOrder->is_price_provided ? 'disabled' : '' }}>4. Delivered (Arrival)</option>
                                <option value="cancelled"  {{ $project->sampleOrder->status == 'cancelled'  ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            <button type="submit" style="background: #1e293b; color: white; border: none; padding: 0 1.5rem; border-radius: 8px; font-weight: 700; cursor: pointer;">
                                UPDATE
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div style="border-left: 1px solid #f1f5f9; padding-left: 1.5rem;">
                <form action="{{ route('crm.samples.update_pricing', $project->sampleOrder->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <div style="margin-bottom: 1rem;">
                        <label style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Unit Price ($)</label>
                        <input type="number" step="0.01" name="unit_price" value="{{ $project->sampleOrder->unit_price }}" class="form-control" style="width: 100%; height: 38px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 700; padding: 0 10px;" required>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Shipping & Handling ($)</label>
                        <input type="number" step="0.01" name="delivery_fee" value="{{ $project->sampleOrder->delivery_fee }}" class="form-control" style="width: 100%; height: 38px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 700; padding: 0 10px;" required>
                    </div>

                    <div style="background: #1e293b; color: white; padding: 1.25rem; border-radius: 12px; text-align: center; margin-bottom: 1.25rem;">
                        <div style="font-size: 0.65rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">TOTAL AMOUNT</div>
                        <div style="font-size: 1.5rem; font-weight: 900;">${{ number_format($project->sampleOrder->unit_price * $project->sampleOrder->quantity + $project->sampleOrder->delivery_fee, 2) }}</div>
                    </div>

                    <button type="submit" style="width: 100%; background: {{ $project->sampleOrder->is_price_provided ? '#10b981' : 'var(--primary-purple)' }}; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer;">
                        {{ $project->sampleOrder->is_price_provided ? 'UPDATE BILL' : 'ACTIVATE BILL' }}
                    </button>
                </form>
                @if($project->sampleOrder->is_price_provided)
                    <div style="margin-top: 1rem; text-align: center; font-size: 0.75rem; color: #10b981; font-weight: 700;">
                        <i class="fas fa-check-circle"></i> Price sync with App active
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<!-- Production Orders Tab Content -->
<div id="production-tab" class="tab-content {{ ($project->productionOrders && $project->productionOrders->count() > 0) ? 'active' : '' }}">
    @if($project->productionOrders->count() > 0)
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            @foreach($project->productionOrders as $order)
                <div class="modern-card" style="border-left: 5px solid {{ $order->is_price_provided ? '#10b981' : '#f59e0b' }};">
                    <div class="card-header" style="background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                        <div class="card-title">
                            <i class="fas fa-barcode"></i> Order #PO-{{ str_pad($order->id + 5000, 4, '0', STR_PAD_LEFT) }}
                            <span class="status-pill" style="margin-left: 10px; background: #e2e8f0; color: #475569;">{{ strtoupper(str_replace('_', ' ', $order->status)) }}</span>
                        </div>
                        <div style="font-size: 0.8rem; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 1rem;">
                            <span>Submitted: {{ $order->created_at->format('M d, Y h:i A') }}</span>
                            <form action="{{ route('crm.production_orders.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Delete this production order?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font-size: 0.9rem;" title="Delete Production Order">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div style="padding: 1.5rem; display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                        <div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div>
                                    <div class="spec-label">Contact Person</div>
                                    <div class="spec-value">{{ $order->contact_name }}</div>
                                    <div style="font-size: 0.8rem; color: #64748b;">{{ $order->contact_phone }} | {{ $order->contact_email }}</div>
                                </div>
                                <div>
                                    <div class="spec-label">Production Specs</div>
                                    <div class="spec-value">{{ strtoupper($order->production_type) }}</div>
                                    <div style="font-weight: 800; color: #1e293b;">{{ number_format($order->quantity) }} Units</div>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; padding-top: 1rem; border-top: 1px dashed #e2e8f0;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <div style="width: 28px; height: 28px; background: #eff6ff; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-truck" style="color: #3b82f6; font-size: 0.75rem;"></i>
                                        </div>
                                        <span style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Shipping Address</span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #334155; line-height: 1.6; background: #f8fafc; padding: 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-weight: 500;">
                                        {{ $order->shipping_address }}
                                    </div>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                        <div style="width: 28px; height: 28px; background: #f0fdf4; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-file-invoice" style="color: #22c55e; font-size: 0.75rem;"></i>
                                        </div>
                                        <span style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Billing Address</span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: #334155; line-height: 1.6; background: #f8fafc; padding: 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-weight: 500;">
                                        {{ $order->billing_address }}
                                    </div>
                                </div>
                            </div>

                            <div style="background: #fafafa; padding: 1rem; border-radius: 12px; border: 1px solid #f1f5f9;">
                                @if(!$order->is_price_provided)
                                    <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                                        <i class="fas fa-lock" style="color:#ea580c;"></i>
                                        <span style="font-size:0.8rem; color:#9a3412; font-weight:600;">Status is locked — <strong>Activate Bill</strong> first to move beyond Pending Review.</span>
                                    </div>
                                @endif
                                <form action="{{ route('crm.production_orders.update_status', $order->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <label style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Manage Production Pipeline</label>
                                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                        <select name="status" class="form-control" style="flex: 1; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; padding: 0 10px;">
                                            <option value="pending_review" {{ $order->status == 'pending_review' ? 'selected' : '' }}>0. Pending Review (New)</option>
                                            <option value="processed" {{ $order->status == 'processed' ? 'selected' : '' }} {{ !$order->is_price_provided ? 'disabled' : '' }}>1. Processed (Order Confirmed)</option>
                                            <option value="produced" {{ $order->status == 'produced' || $order->status == 'payment_pending' || $order->status == 'in_production' ? 'selected' : '' }} {{ !$order->is_price_provided ? 'disabled' : '' }}>2. Produced (Manufacturing Done)</option>
                                            <option value="shipping" {{ $order->status == 'shipping' || $order->status == 'shipped' ? 'selected' : '' }} {{ !$order->is_price_provided ? 'disabled' : '' }}>3. Shipping (Tracking Assigned)</option>
                                            <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }} {{ !$order->is_price_provided ? 'disabled' : '' }}>4. Delivered (Arrival)</option>
                                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                        <button type="submit" style="background: #1e293b; color: white; border: none; padding: 0 1.5rem; border-radius: 8px; font-weight: 700; cursor: pointer;">
                                            UPDATE
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div style="border-left: 1px solid #f1f5f9; padding-left: 1.5rem;">
                            <form action="{{ route('crm.production_orders.update_pricing', $order->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <div style="margin-bottom: 1rem;">
                                    <label style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Unit Price ($)</label>
                                    <input type="number" step="0.001" name="unit_price" value="{{ $order->unit_price }}" class="form-control" style="width: 100%; height: 38px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 700; padding: 0 10px;" required>
                                </div>
                                <div style="margin-bottom: 1.5rem;">
                                    <label style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Shipping & Handling ($)</label>
                                    <input type="number" step="0.01" name="delivery_fee" value="{{ $order->delivery_fee }}" class="form-control" style="width: 100%; height: 38px; border-radius: 6px; border: 1px solid #e2e8f0; font-weight: 700; padding: 0 10px;" required>
                                </div>

                                <div style="background: #1e293b; color: white; padding: 1.25rem; border-radius: 12px; text-align: center; margin-bottom: 1.25rem;">
                                    <div style="font-size: 0.65rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px;">TOTAL CONTRACT VALUE</div>
                                    <div style="font-size: 1.5rem; font-weight: 900;">${{ number_format($order->unit_price * $order->quantity + $order->delivery_fee, 2) }}</div>
                                </div>

                                <button type="submit" style="width: 100%; background: {{ $order->is_price_provided ? '#10b981' : 'var(--primary-purple)' }}; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer;">
                                    {{ $order->is_price_provided ? 'UPDATE BILL' : 'ACTIVATE BILL' }}
                                </button>
                            </form>
                            @if($order->is_price_provided)
                                <div style="margin-top: 1rem; text-align: center; font-size: 0.75rem; color: #10b981; font-weight: 700;">
                                    <i class="fas fa-check-circle"></i> Price sync with App active
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="padding: 4rem 2rem; text-align: center; background: #f8fafc; border-radius: 20px; border: 2px dashed #e2e8f0;">
            <div style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"><i class="fas fa-industry"></i></div>
            <h3 style="color: #64748b; font-weight: 700;">No Production Orders</h3>
            <p style="color: #94a3b8; max-width: 400px; margin: 0 auto;">When the client places a full manufacturing order from the mobile app, it will appear here for your review and invoicing.</p>
        </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
    function openTab(evt, tabName) {
        var i, tabContent, tabBtns;
        tabContent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabContent.length; i++) {
            tabContent[i].classList.remove("active");
        }
        tabBtns = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tabBtns.length; i++) {
            tabBtns[i].classList.remove("active");
        }
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }
</script>
@endsection
