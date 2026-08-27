@extends('crm.layout')

@section('title', 'App Projects')

@section('styles')
        /* ─── RESET & BASE ─────────────────────────────── */
        .ap-wrap {
            padding: 0;
        }

        /* ─── SUMMARY BAR ─────────────────────────────── */
        .ap-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .ap-stat {
            background: #fff;
            border-radius: 14px;
            padding: 1.1rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
        }

        .ap-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .ap-stat-info {
            flex: 1;
        }

        .ap-stat-num {
            font-size: 1.6rem;
            font-weight: 800;
            color: #111827;
            line-height: 1;
        }

        .ap-stat-lbl {
            font-size: 0.72rem;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-top: 3px;
        }

        /* ─── MAIN CARD ────────────────────────────────── */
        .ap-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            overflow: hidden;
        }

        .ap-card-header {
            padding: 1.2rem 1.8rem;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
        }

        .ap-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        /* ─── TABLE ────────────────────────────────────── */
        .ap-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ap-table thead th {
            background: #f8fafc;
            color: #94a3b8;
            font-size: 0.62rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: .6rem .8rem;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .ap-table tbody tr {
            cursor: pointer;
            transition: background .12s;
            border-bottom: 1px solid #f1f5f9;
        }

        /* .ap-table tbody tr:last-child { border-bottom: none; }
            .ap-table tbody tr:hover { background: #f8fafc; }
            .ap-table tbody tr.is-unread { background: #fffbeb; }
            .ap-table tbody tr.is-unread:hover { background: #fef9e0; } */
        .ap-table td {
            padding: .65rem .8rem;
            vertical-align: middle;
            font-size: .8rem;
            color: #374151;
        }

        /* ─── REF ID ───────────────────────────────────── */
        .ap-ref {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: var(--primary-purple);
            font-size: .85rem;
        }

        .ap-unread-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #396dbbff;
            margin-left: 6px;
            vertical-align: middle;
            box-shadow: 0 0 0 2px rgba(44, 126, 185, 0.25);
            animation: blink 1.4s infinite;
        }

        /* ─── CUSTOMER ─────────────────────────────────── */
        .ap-cust-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-purple), #a855f7);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .72rem;
            flex-shrink: 0;
        }

        .ap-cust-name {
            font-weight: 500;
            color: #111827;
            font-size: .78rem;
        }

        .ap-cust-email {
            font-size: .65rem;
            color: #9ca3af;
        }

        /* ─── PROJECT INFO ─────────────────────────────── */
        .ap-proj-name {
            font-weight: 500;
            color: #1e293b;
            font-size: .78rem;
        }

        .ap-proj-prod {
            font-size: .65rem;
            color: #9ca3af;
            margin-top: 2px;
            display: flex;
            align-items: flex-start;
            gap: 5px;
        }

        .ap-proj-prod i {
            margin-top: 1px;
            flex-shrink: 0;
        }

        /* ─── PILLS ────────────────────────────────────── */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 7px;
            border-radius: 99px;
            font-size: .62rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .pill-green {
            background: #dcfce7;
            color: #166534;
        }

        .pill-red {
            background: #fee2e2;
            color: #991b1b;
            animation: blink 1.4s infinite;
        }

        .pill-yellow {
            background: #fef3c7;
            color: #92400e;
        }

        .pill-gray {
            background: #f1f5f9;
            color: #64748b;
        }

        .pill-purple {
            background: var(--primary-soft);
            color: var(--primary-purple);
        }

        .pill-blue {
            background: #dbeafe;
            color: #1e40af;
        }

        /* ─── PHASE BADGE ──────────────────────────────── */
        .phase-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: .75rem;
            font-weight: 700;
        }

        .phase-design {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .phase-sampling {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .phase-production {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* ─── PROGRESS STEPS ───────────────────────────── */
        .steps {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .step {
            width: 22px;
            height: 6px;
            border-radius: 3px;
            background: #e5e7eb;
        }

        .step.done {
            background: #10b981;
        }

        .step.active {
            background: #f59e0b;
        }

        /* ─── BTN ──────────────────────────────────────── */
        .ap-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            font-size: .85rem;
            text-decoration: none;
            transition: all .15s;
            border: none;
        }

        .ap-btn-view {
            background: var(--primary-purple);
            color: #fff;
        }

        .ap-btn-view:hover {
            background: var(--primary-purple);
            color: #fff;
        }

        /* ─── EMPTY ────────────────────────────────────── */
        .ap-empty {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
        }

        .ap-empty i {
            font-size: 3rem;
            opacity: .25;
            margin-bottom: .75rem;
            display: block;
        }

        .ap-empty p {
            margin: 0;
            font-size: .95rem;
            font-weight: 500;
        }

        /* ─── ANIMATIONS ───────────────────────────────── */
        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .45;
            }
        }

        /* ─── DATE FILTER BAR ─────────────────────────── */
        .ap-filter-bar {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .ap-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 14px;
            border-radius: 99px;
            font-size: .75rem;
            font-weight: 600;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            white-space: nowrap;
        }

        .ap-filter-btn:hover {
            border-color: var(--primary-purple);
            color: var(--primary-purple);
            background: var(--primary-soft);
        }

        .ap-filter-btn.is-active {
            background: var(--primary-purple);
            color: #fff;
            border-color: var(--primary-purple);
        }

        /* ─── RESPONSIVE ───────────────────────────────── */
        @media(max-width:900px) {
            .ap-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width:600px) {
            .ap-summary {
                grid-template-columns: 1fr 1fr;
            }
        }
@endsection

@section('content')
    @php
        $filter = $filter ?? 'all';
        $baseUrl = route('crm.app_projects');
        $filters = [
            'all' => ['label' => 'All', 'icon' => 'fas fa-list'],
            'today' => ['label' => 'Today', 'icon' => 'fas fa-sun'],
            'yesterday' => ['label' => 'Yesterday', 'icon' => 'fas fa-history'],
            'this_week' => ['label' => 'This Week', 'icon' => 'fas fa-calendar-week'],
            'this_month' => ['label' => 'This Month', 'icon' => 'fas fa-calendar-alt'],
        ];
    @endphp

    <div class="ap-wrap">

        {{-- ── SUMMARY STATS ─────────────────────────── --}}
        <div class="ap-summary">
            <div class="ap-stat">
                <div class="ap-stat-icon" style="background:var(--primary-soft);color:var(--primary-purple);"><i class="fas fa-folder-open"></i></div>
                <div class="ap-stat-info">
                    <div class="ap-stat-num">{{ $totalCount }}</div>
                    <div class="ap-stat-lbl">Total Projects</div>
                </div>
            </div>
            <div class="ap-stat">
                <div class="ap-stat-icon" style="background:#fee2e2;color:#ef4444;"><i class="fas fa-bell"></i></div>
                <div class="ap-stat-info">
                    <div class="ap-stat-num">{{ $newCount }}</div>
                    <div class="ap-stat-lbl">Unread / New</div>
                </div>
            </div>
            <div class="ap-stat">
                <div class="ap-stat-icon" style="background:#f3f4f6;color:#374151;"><i class="fas fa-magic"></i></div>
                <div class="ap-stat-info">
                    <div class="ap-stat-num">{{ $designCount }}</div>
                    <div class="ap-stat-lbl">In Design</div>
                </div>
            </div>
            <div class="ap-stat">
                <div class="ap-stat-icon" style="background:#dcfce7;color:#166534;"><i class="fas fa-industry"></i></div>
                <div class="ap-stat-info">
                    <div class="ap-stat-num">{{ $productionCount }}</div>
                    <div class="ap-stat-lbl">In Order</div>
                </div>
            </div>
        </div>

        {{-- ── MAIN TABLE CARD ────────────────────────── --}}
        <div class="ap-card">
            <div class="ap-card-header">
                <h2 class="ap-card-title">
                    <i class="fas fa-layer-group" style="color:var(--primary-purple);"></i>
                    App Projects
                    @if($filter !== 'all')
                        <span style="font-size:.75rem;font-weight:500;color:#9ca3af;">&mdash;
                            {{ $filters[$filter]['label'] ?? '' }}</span>
                    @endif
                </h2>
                <span style="font-size:.8rem;color:#9ca3af;font-weight:600;">{{ $projects->total() }} total</span>
            </div>

            {{-- ── DATE FILTER BAR ─────────────────────── --}}
            <div style="padding:.85rem 1.5rem;border-bottom:1px solid #f1f5f9;background:#fafbfc;">
                <div class="ap-filter-bar" style="flex-wrap:wrap; gap:0.5rem;">
                    @foreach($filters as $key => $meta)
                        <a href="{{ $baseUrl . '?date_filter=' . $key }}"
                            class="ap-filter-btn {{ $filter === $key && !request('date_from') ? 'is-active' : '' }}">
                            <i class="{{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                        </a>
                    @endforeach

                    {{-- ── CUSTOM DATE RANGE ── --}}
                    <div style="position:relative; margin-left:auto;">
                        <button id="datePickerToggle"
                            style="display:flex;align-items:center;gap:6px;padding:6px 14px;border:1.5px solid {{ request('date_from') ? 'var(--primary-purple)' : '#e5e7eb' }};border-radius:8px;background:{{ request('date_from') ? 'var(--primary-soft)' : '#fff' }};color:{{ request('date_from') ? 'var(--primary-purple)' : '#64748b' }};font-size:0.8rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .2s;">
                            <i class="fas fa-filter"></i>
                            @if(request('date_from') && request('date_to'))
                                {{ \Carbon\Carbon::parse(request('date_from'))->format('d M') }} –
                                {{ \Carbon\Carbon::parse(request('date_to'))->format('d M Y') }}
                            @else
                                Filter
                            @endif
                        </button>

                        {{-- Calendar Dropdown --}}
                        <div id="datePickerDropdown"
                            style="display:none;position:absolute;right:0;top:calc(100% + 8px);z-index:999;background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,0.12);padding:1.25rem;min-width:280px;">
                            <div
                                style="font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:.05em;">
                                <i class="fas fa-calendar" style="color:var(--primary-purple);"></i> Select Date Range
                            </div>
                            <form method="GET" action="{{ $baseUrl }}">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem;">
                                    <div>
                                        <label
                                            style="font-size:0.72rem;font-weight:600;color:#9ca3af;display:block;margin-bottom:4px;">FROM</label>
                                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                                            style="width:100%;padding:7px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:0.82rem;color:#1e293b;outline:none;transition:border .2s;"
                                            onfocus="this.style.borderColor='var(--primary-purple)'"
                                            onblur="this.style.borderColor='#e5e7eb'">
                                    </div>
                                    <div>
                                        <label
                                            style="font-size:0.72rem;font-weight:600;color:#9ca3af;display:block;margin-bottom:4px;">TO</label>
                                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                                            style="width:100%;padding:7px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:0.82rem;color:#1e293b;outline:none;transition:border .2s;"
                                            onfocus="this.style.borderColor='var(--primary-purple)'"
                                            onblur="this.style.borderColor='#e5e7eb'">
                                    </div>
                                </div>
                                <div style="display:flex;gap:0.5rem;">
                                    <button type="submit"
                                        style="flex:1;padding:8px;background:linear-gradient(135deg,var(--primary-purple),var(--primary-purple));color:#fff;border:none;border-radius:8px;font-size:0.82rem;font-weight:700;cursor:pointer;">
                                        <i class="fas fa-search"></i> Apply
                                    </button>
                                    @if(request('date_from'))
                                        <a href="{{ $baseUrl }}"
                                            style="padding:8px 12px;background:#f1f5f9;color:#64748b;border-radius:8px;font-size:0.82rem;font-weight:600;text-decoration:none;display:flex;align-items:center;">
                                            Clear
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    const btn = document.getElementById('datePickerToggle');
                    const drop = document.getElementById('datePickerDropdown');
                    if (!btn || !drop) return;
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        drop.style.display = drop.style.display === 'none' ? 'block' : 'none';
                    });
                    document.addEventListener('click', function () { drop.style.display = 'none'; });
                    drop.addEventListener('click', function (e) { e.stopPropagation(); });
                })();
            </script>

            <div style="overflow-x:auto;">
                <table class="ap-table">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Customer</th>
                            <th>Project</th>
                            <th>Dieline</th>
                            <th>Mockup</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="text-align:center;">Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                            @php
                                /* Dieline logic */
                                $dielineCount = $project->dielines->count();
                                $clientPending = $project->dielines->where('is_company_upload', false)->where('status', 'pending')->count();
                                $pendingCount = $project->dielines->where('status', 'pending')->count();
                                $approvedCount = $project->dielines->where('status', 'approved')->count();

                                /* Mockup logic */
                                $hasMockup = false;
                                $hasRevision = false;
                                foreach ($project->dielines as $dl) {
                                    if ($dl->mockups->count() > 0)
                                        $hasMockup = true;
                                    if ($dl->mockups->where('status', 'revision')->count() > 0 || $dl->change_request_comment)
                                        $hasRevision = true;
                                }

                                /* Unread logic */
                                $isUnread = ($project->status == 'new' || $clientPending > 0);

                                /* Phase */
                                $isProduction = $project->productionOrders->count() > 0;
                                $isSampling = !$isProduction && $project->sampleOrder;

                                /* 4-Step progress: Dieline → Mockup → Sample → Order */
                                $stepDieline = $approvedCount > 0 ? 'done' : ($pendingCount > 0 ? 'active' : 'empty');
                                $stepMockup = !$hasMockup ? 'empty' : ($hasRevision ? 'active' : 'done');
                                $stepSample = $isSampling ? 'active' : ($isProduction ? 'done' : 'empty');
                                $stepOrder = $isProduction ? ($project->productionOrders->first()->status == 'delivered' ? 'done' : 'active') : 'empty';

                                /* Customer initial & avatar color */
                                $custName = $project->user->name ?? 'Guest';
                                $initial = strtoupper(substr($custName, 0, 1));
                                $avatarColors = [
                                    'var(--primary-purple)',
                                    'var(--primary-purple)',
                                    '#ec4899',
                                    '#f43f5e',
                                    '#f97316',
                                    '#eab308',
                                    '#10b981',
                                    '#14b8a6',
                                    '#0ea5e9',
                                    '#3b82f6',
                                    '#a855f7',
                                    '#ef4444',
                                    '#22c55e',
                                    '#06b6d4',
                                    '#f59e0b',
                                ];
                                $avatarBg = $avatarColors[abs(crc32($custName)) % count($avatarColors)];
                            @endphp

                            <tr class="{{ $isUnread ? 'is-unread' : '' }}"
                                onclick="window.location='{{ route('crm.app_projects.show', $project->id) }}'">

                                {{-- REF ID --}}
                                <td>
                                    <div style="display:flex;align-items:center;gap:5px;white-space:nowrap;">
                                        <span
                                            class="ap-ref">{{ 'MBP-' . str_pad($project->id + 9000, 4, '0', STR_PAD_LEFT) }}</span>
                                        @if($isUnread) <span class="ap-unread-dot" title="Unread"></span> @endif
                                    </div>
                                </td>

                                {{-- CUSTOMER --}}
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="ap-cust-avatar" style="background:{{ $avatarBg }};">{{ $initial }}</div>
                                        <div>
                                            <div class="ap-cust-name">{{ $custName }}</div>
                                            <div class="ap-cust-email">{{ $project->user->email ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- PROJECT --}}
                                <td>
                                    <div class="ap-proj-name">{{ Str::limit($project->project_name, 22) }}</div>
                                    <div class="ap-proj-prod"><i class="fas fa-box"
                                            style="opacity:.4;"></i><span>{{ $project->product_name ?? '—' }}</span></div>
                                    @if(!empty($project->project_description))
                                        <div class="ap-proj-prod" style="color:#94a3b8;margin-top:.15rem;" title="{{ $project->project_description }}"><i class="fas fa-align-left" style="opacity:.4;"></i><span>{{ Str::limit($project->project_description, 30) }}</span></div>
                                    @endif
                                </td>

                                {{-- DIELINE STATUS (exact status from show page) --}}
                                <td>
                                    @php
                                        $latestDieline = $project->dielines->sortByDesc('created_at')->first();
                                    @endphp
                                    @if(!$latestDieline)
                                        <span class="pill pill-gray"><i class="fas fa-minus-circle"></i> No Dieline</span>
                                    @else
                                        @php
                                            $ds = $latestDieline->status;
                                            $dTotal = $project->dielines->count();
                                        @endphp
                                        @if($ds == 'approved')
                                            <span class="pill pill-green"><i class="fas fa-check-circle"></i> Approved</span>
                                        @elseif($ds == 'change_requested')
                                            <span class="pill pill-red"><i class="fas fa-edit"></i> Change Req.</span>
                                        @elseif($ds == 'unapproved')
                                            <span class="pill pill-red"><i class="fas fa-times-circle"></i> Unapproved</span>
                                        @elseif($ds == 'pending_company_design')
                                            <span class="pill pill-purple"><i class="fas fa-drafting-compass"></i> Design Req.</span>
                                        @elseif($ds == 'viewed')
                                            <span class="pill pill-blue"><i class="fas fa-eye"></i> Viewed</span>
                                        @elseif($clientPending > 0)
                                            <span class="pill pill-red" style="animation:blink 1.2s infinite;"><i
                                                    class="fas fa-exclamation-circle"></i> Client Upload!</span>
                                        @else
                                            <span class="pill pill-yellow"><i class="fas fa-clock"></i> Pending</span>
                                        @endif
                                        @if($dTotal > 1)
                                            <span style="font-size:.65rem;color:#9ca3af;margin-left:4px;">({{ $dTotal }})</span>
                                        @endif
                                    @endif
                                </td>

                                {{-- MOCKUP STATUS (exact status from show page) --}}
                                <td>
                                    @php
                                        $latestMockup = null;
                                        foreach ($project->dielines as $dl) {
                                            $m = $dl->mockups->sortByDesc('created_at')->first();
                                            if ($m && (!$latestMockup || $m->created_at > $latestMockup->created_at)) {
                                                $latestMockup = $m;
                                            }
                                        }
                                    @endphp
                                    @if(!$latestMockup)
                                        <span class="pill pill-gray"><i class="fas fa-image"></i> No Mockup</span>
                                    @else
                                        @php $ms = $latestMockup->status; @endphp
                                        @if($ms == 'approved')
                                            <span class="pill pill-green"><i class="fas fa-check-circle"></i> Approved</span>
                                        @elseif($ms == 'change_requested')
                                            <span class="pill pill-red"><i class="fas fa-redo"></i> Change Req.</span>
                                        @elseif($ms == 'unapproved')
                                            <span class="pill pill-red"><i class="fas fa-times-circle"></i> Unapproved</span>
                                        @elseif($ms == 'pending_company_design')
                                            <span class="pill pill-purple"><i class="fas fa-paint-brush"></i> Design Req.</span>
                                        @elseif($ms == 'revision')
                                            <span class="pill pill-yellow"><i class="fas fa-sync-alt"></i> Revision</span>
                                        @elseif($ms == 'viewed')
                                            <span class="pill pill-blue"><i class="fas fa-eye"></i> Viewed</span>
                                        @else
                                            <span class="pill pill-yellow"><i class="fas fa-clock"></i> Pending</span>
                                        @endif
                                    @endif
                                </td>



                                {{-- PHASE + 4-STEP PROGRESS --}}
                                <td>
                                    <div style="display:flex;flex-direction:column;gap:7px;">
                                        {{-- Phase Badge --}}
                                        @if($isProduction)
                                            <span class="phase-badge phase-production"><i class="fas fa-industry"></i>Order</span>
                                        @elseif($isSampling)
                                            <span class="phase-badge phase-sampling"><i class="fas fa-vial"></i> Sample</span>
                                        @else
                                            <span class="phase-badge phase-design"><i class="fas fa-magic"></i> Design</span>
                                        @endif

                                        {{-- 4 Steps with labels --}}
                                        <div style="display:flex;align-items:center;gap:5px;">
                                            <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                                                <div class="step {{ $stepDieline }}" title="Dieline" style="width:26px;"></div>
                                                <span style="font-size:.55rem;color:#9ca3af;letter-spacing:.02em;">DLINE</span>
                                            </div>
                                            <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                                                <div class="step {{ $stepMockup }}" title="Mockup" style="width:26px;"></div>
                                                <span style="font-size:.55rem;color:#9ca3af;letter-spacing:.02em;">MOCK</span>
                                            </div>
                                            <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                                                <div class="step {{ $stepSample }}" title="Sample" style="width:26px;"></div>
                                                <span style="font-size:.55rem;color:#9ca3af;letter-spacing:.02em;">SMPL</span>
                                            </div>
                                            <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                                                <div class="step {{ $stepOrder }}" title="Order" style="width:26px;"></div>
                                                <span style="font-size:.55rem;color:#9ca3af;letter-spacing:.02em;">ORDR</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- DATE --}}
                                <td style="white-space:nowrap;">
                                    <span
                                        style="font-size:.82rem;color:#374151;font-weight:600;">{{ $project->created_at->format('M j, Y') }}</span>
                                    <span
                                        style="font-size:.75rem;color:#9ca3af;margin-left:4px;">{{ $project->created_at->format('h:i A') }}</span>
                                </td>

                                {{-- ACTION --}}
                                <td onclick="event.stopPropagation();" style="text-align:center;">
                                    <a href="{{ route('crm.app_projects.show', $project->id) }}" class="ap-btn ap-btn-view"
                                        title="Open Project">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="ap-empty">
                                        <i class="fas fa-folder-open"></i>
                                        <p>No App Projects Found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if($projects->hasPages())
                <div style="padding:1.2rem 1.8rem;border-top:1px solid #f1f5f9;">
                    {{ $projects->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
@endsection
