@extends('crm.layout')
@section('title', 'Design Jobs')
@section('content')
@php
    $u = Auth::guard('crm')->user();
    $canCreate = $u->isDesigner() || $u->isAdmin();
    $statusColors = [
        'designing'   => ['#1e40af', '#eaf0ff'],
        'mockup'      => ['#6b21a8', '#f4ecfd'],
        'printing'    => ['#9a3412', '#fdeee2'],
        'lamination'  => ['#0e7490', '#e5f5f9'],
        'embossing'   => ['var(--primary-purple)', '#f1ecfe'],
        'debossing'   => ['#a21caf', '#fbedfb'],
        'foiling'     => ['#b45309', '#fdf3e0'],
        'die_cutting' => ['#be123c', '#fdecef'],
        'pasting'     => ['#4d7c0f', '#f2f8e5'],
        'packing'     => ['#0f766e', '#e6f6f3'],
        'shipped'     => ['#1d4ed8', '#e9effe'],
        'delivered'   => ['#0f6d38', '#e9f7ee'],
    ];
    $totalJobs = $statusCounts->sum();
    $activeJobs = $totalJobs - ($statusCounts['delivered'] ?? 0);
    $deliveredJobs = $statusCounts['delivered'] ?? 0;
    $initials = function ($name) {
        $name = trim((string) $name);
        if ($name === '') return '—';
        $parts = preg_split('/\s+/', $name);
        $a = mb_substr($parts[0], 0, 1);
        $b = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($a . $b);
    };
@endphp

<style>
    .dj { --ink:#1a1d24; --muted:#8a909c; --soft:#f4f5f7; --line:#ecedf1; --card:#fff;
        --accent: var(--primary-purple, #f45a24); --accent-soft: var(--primary-soft, #fff1ec);
        max-width: none; width: 100%; margin: 0; color: var(--ink);
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,Roboto,sans-serif; }
    .dj *, .dj *::before, .dj *::after { box-sizing:border-box; }

    /* Summary strip */
    .dj-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:.9rem; margin:.2rem 0 1.4rem; }
    @media (max-width:640px){ .dj-stats{ grid-template-columns:1fr; } }
    .dj-stat { position:relative; display:flex; align-items:center; gap:1rem; background:var(--card); border:1px solid var(--line); border-radius:16px; padding:1.1rem 1.25rem;
        box-shadow:0 1px 3px rgba(20,23,33,.04), 0 20px 40px -34px rgba(20,23,33,.35); transition:transform .15s, box-shadow .15s; overflow:hidden; }
    .dj-stat:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(20,23,33,.08); }
    .dj-stat-icon { flex:0 0 46px; width:46px; height:46px; border-radius:13px; display:grid; place-items:center; font-size:1.05rem;
        background: var(--accent-soft); color: var(--accent); }
    .dj-stat .body { display:flex; flex-direction:column; gap:.35rem; min-width:0; }
    .dj-stat .n { font-size:1.75rem; font-weight:850; letter-spacing:-.02em; line-height:1; color:var(--ink); font-variant-numeric:tabular-nums; }
    .dj-stat .l { font-size:.68rem; text-transform:uppercase; letter-spacing:.09em; color:var(--muted); font-weight:700; }
    .dj-stat.accent .n { color:var(--accent); }
    .dj-stat.accent .dj-stat-icon { background:var(--accent); color:#fff; box-shadow: 0 8px 18px var(--primary-shadow, rgba(244,90,36,.35)); }
    .dj-stat.success .dj-stat-icon { background:#dcfce7; color:#059669; }

    .dj-banner { font-size:.86rem; padding:.8rem 1.05rem; border-radius:11px; margin-bottom:1rem; }
    .dj-ok { background:#eef9f0; border:1px solid #cbe8d1; color:#1c5b32; }
    .dj-err { background:#fdf0f0; border:1px solid #f0cccc; color:#8f2626; }

    /* Toolbar */
    .dj-toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1.1rem;
        padding:.85rem 1rem; background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:0 1px 3px rgba(20,23,33,.03); }
    .dj-chips { display:flex; gap:.4rem; flex-wrap:wrap; }
    .dj-chip { display:inline-flex; align-items:center; gap:.4rem; font-size:.76rem; font-weight:600; text-decoration:none;
        color:#5b616e; background:#fbfcfe; border:1px solid var(--line); padding:.45rem .85rem; border-radius:999px; transition:all .14s; }
    .dj-chip:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-soft); }
    .dj-chip.active { background: var(--accent); border-color: var(--accent); color:#fff; box-shadow: 0 6px 14px var(--primary-shadow, rgba(244,90,36,.35)); }
    .dj-chip.active:hover { color:#fff; background: var(--primary-hover, var(--accent)); border-color: var(--primary-hover, var(--accent)); }
    .dj-chip .c { font-size:.68rem; font-weight:700; min-width:1.2rem; height:1.2rem; padding:0 .35rem; border-radius:999px;
        display:inline-grid; place-items:center; background:var(--soft); color:#6b7280; font-variant-numeric:tabular-nums; }
    .dj-chip.active .c { background:rgba(255,255,255,.24); color:#fff; }
    .dj-search { position:relative; flex:1 1 100%; width:100%; }
    .dj-filter-row { display:flex; align-items:center; gap:.65rem; width:100%; }
    .dj-filter { flex:0 0 220px; position:relative; }
    .dj-filter > i { position:absolute; z-index:1; left:.85rem; top:50%; transform:translateY(-50%); color:var(--accent); font-size:.8rem; pointer-events:none; }
    .dj-filter select { width:100%; height:44px; padding:.55rem 2rem .55rem 2.25rem; border:1px solid #e1e5eb; border-radius:11px; background:#fbfcfd; color:#374151; font-family:inherit; font-size:.82rem; font-weight:700; outline:none; cursor:pointer; transition:border-color .15s,box-shadow .15s,background .15s; }
    .dj-filter select:hover { border-color:#d1d7e0; background:#fff; }
    .dj-filter select:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
    .dj-date-filter { flex:0 0 190px; position:relative; }
    .dj-date-filter > i { position:absolute; z-index:1; left:.85rem; top:50%; transform:translateY(-50%); color:var(--accent); font-size:.82rem; pointer-events:none; }
    .dj-date-filter select { width:100%; height:44px; padding:.55rem 2rem .55rem 2.25rem; border:1px solid #e1e5eb; border-radius:11px; background:#fbfcfd; color:#374151; font-family:inherit; font-size:.8rem; font-weight:700; outline:none; cursor:pointer; transition:border-color .15s,box-shadow .15s,background .15s; }
    .dj-date-filter select:hover { border-color:#d1d7e0; background:#fff; }
    .dj-date-filter select:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
    .dj-custom-date { flex:0 0 175px; }
    .dj-custom-date[hidden] { display:none; }
    .dj-custom-date input { width:100%; height:44px; padding:.55rem .7rem; border:1px solid #e1e5eb; border-radius:11px; background:#fbfcfd; color:#374151; font-family:inherit; font-size:.8rem; font-weight:650; outline:none; cursor:pointer; transition:border-color .15s,box-shadow .15s,background .15s; }
    .dj-custom-date input:hover { border-color:#d1d7e0; background:#fff; }
    .dj-custom-date input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
    .dj-search i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#9aa3b2; font-size:.82rem; }
    .dj-search input { height:44px; border:1px solid #e1e5eb; border-radius:11px; padding:.6rem .8rem .6rem 2.35rem; font-size:.85rem;
        font-family:inherit; outline:none; width:100%; transition:all .13s; background:#fbfcfd; }
    .dj-search input:hover { border-color:#d1d7e0; background:#fff; }
    .dj-search input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
    @media(max-width:760px){.dj-filter-row{align-items:stretch;flex-direction:column}.dj-filter,.dj-date-filter,.dj-custom-date{flex-basis:auto;width:100%}}

    /* Table card */
    .dj-card { background:var(--card); border:1px solid var(--line); border-radius:16px; overflow:hidden;
        box-shadow:0 1px 3px rgba(20,23,33,.04), 0 18px 40px -30px rgba(20,23,33,.25); }
    .dj-wrap { overflow-x:auto; }
    .dj-table { width:100%; border-collapse:collapse; min-width:1180px; }
    .dj-table thead th { background:#fafafb; font-size:.66rem; text-transform:uppercase; letter-spacing:.09em;
        font-weight:700; color:#9096a1; text-align:left; padding:.85rem 1.15rem; border-bottom:1px solid var(--line); }
    .dj-table tbody td { padding:1rem 1.15rem; border-bottom:1px solid var(--line); font-size:.88rem; vertical-align:middle; }
    .dj-table tbody tr:last-child td { border-bottom:none; }
    .dj-table tbody tr { transition:background .12s; }
    .dj-table tbody tr:hover { background:#fcfcfd; }
    .dj-job a { color:var(--ink); text-decoration:none; font-weight:700; letter-spacing:-.01em; }
    .dj-job a:hover { color:var(--accent); }
    .dj-job .sub { font-size:.72rem; color:var(--muted); margin-top:.2rem; }
    .dj-est a { color:var(--accent); text-decoration:none; font-weight:600; }
    .dj-est a:hover { text-decoration:underline; }
    .dj-est .sub { font-size:.74rem; color:var(--muted); margin-top:.15rem; }
    .dj-est .manual { font-weight:600; color:#4b5563; }
    .dj-title { font-weight:600; color:#2b2f38; }
    .dj-title .sub { font-size:.74rem; color:var(--muted); margin-top:.2rem; font-weight:400; white-space:normal; max-width:230px; }
    .dj-stage { display:inline-flex;align-items:center;max-width:190px;padding:.36rem .6rem;border:1px solid var(--primary-shadow);border-radius:999px;background:var(--accent-soft);color:var(--accent);font-size:.72rem;font-weight:750;line-height:1.25; }
    .dj-stage-muted { border-color:var(--line);background:var(--soft);color:var(--muted); }
    .dj-stage-form { margin:0; }
    .dj-stage-select { max-width:200px;padding:.4rem .7rem;border:1px solid var(--primary-shadow);border-radius:999px;background:var(--accent-soft);color:var(--accent);font-size:.72rem;font-weight:750;line-height:1.25;cursor:pointer;outline:0; }
    .dj-stage-select:focus { box-shadow:0 0 0 3px rgba(43,58,103,.12); }
    .dj-designer { display:flex; align-items:center; gap:.55rem; }
    .dj-ava { width:30px; height:30px; border-radius:50%; display:grid; place-items:center; font-size:.7rem; font-weight:700;
        background:var(--accent-soft); color:var(--accent); flex:0 0 30px; }
    .dj-badge { display:inline-block; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
        padding:.34rem .68rem; border-radius:999px; white-space:nowrap; }
    .dj-prog { height:4px; border-radius:999px; background:var(--soft); margin-top:.5rem; overflow:hidden; width:120px; }
    .dj-prog span { display:block; height:100%; border-radius:999px; }
    .dj-status-select { border:1px solid var(--line); border-radius:9px; background:var(--card); font-family:inherit;
        font-size:.78rem; font-weight:600; padding:.42rem 1.7rem .42rem .6rem; cursor:pointer; outline:none; transition:all .13s;
        appearance:none; background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path d='M1 1l4 4 4-4' stroke='%239096a1' stroke-width='1.5' fill='none' stroke-linecap='round'/></svg>");
        background-repeat:no-repeat; background-position:right .6rem center; }
    .dj-status-select:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
    .dj-deliv { font-size:.82rem; color:#3b3f48; white-space:nowrap; }
    .dj-deliv .none { color:#c3c7cf; }
    .dj-files{display:grid;gap:.3rem;max-width:170px;font-size:.73rem}
    .dj-files a{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--accent);text-decoration:none;font-weight:650}
    .dj-files a:hover{text-decoration:underline}
    .dj-files details summary{color:#607086;cursor:pointer;font-weight:700}
    .dj-track { display:inline-flex; align-items:center; gap:.4rem; font-size:.78rem; font-weight:600; text-decoration:none;
        color:#5b616e; border:1px solid var(--line); border-radius:9px; padding:.42rem .75rem; transition:all .14s; white-space:nowrap; }
    .dj-track:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-soft); }
    .dj-delete { color:#dc2626; border-color:#fecaca; background:#fff5f5; font-family:inherit; cursor:pointer; }
    .dj-delete:hover { color:#b91c1c; border-color:#fca5a5; background:#fee2e2; }
    .dj-actions { display:flex; align-items:center; gap:.4rem; }
    .dj-actions form { display:inline-flex; margin:0; }
    .dj-empty { text-align:center; padding:3.5rem 1rem; color:var(--muted); font-size:.9rem; }
    .dj-pagination { padding:1rem; display:flex; justify-content:center; }
    /* NOTE: this button renders in the layout top bar, OUTSIDE .dj — must use theme vars, not .dj-scoped --accent */
    .dj-new { display:inline-flex; align-items:center; gap:.4rem; background:var(--primary-purple, #f45a24); color:#fff !important; border:none; text-decoration:none;
        padding:.6rem 1.15rem; font-size:.84rem; font-weight:600; border-radius:10px; transition:all .15s;
        box-shadow:0 8px 18px -8px var(--primary-shadow, rgba(244,90,36,.5)); }
    .dj-new:hover { background:var(--primary-hover, #e04a17); filter:brightness(1.03); color:#fff !important; transform:translateY(-1px); }
</style>

@if($canCreate)
    @section('header_actions')
        <a href="{{ route('crm.design_jobs.create') }}" class="dj-new"><i class="fas fa-plus"></i> New Job</a>
    @endsection
@endif

<div class="dj">
    @if(session('success'))<div class="dj-banner dj-ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="dj-banner dj-err">{{ session('error') }}</div>@endif

    <div class="dj-stats">
        <div class="dj-stat"><div class="dj-stat-icon"><i class="fas fa-briefcase"></i></div><div class="body"><div class="n">{{ $totalJobs }}</div><div class="l">Total Jobs</div></div></div>
        <div class="dj-stat accent"><div class="dj-stat-icon"><i class="fas fa-cogs"></i></div><div class="body"><div class="n">{{ $activeJobs }}</div><div class="l">In Production</div></div></div>
        <div class="dj-stat success"><div class="dj-stat-icon"><i class="fas fa-truck"></i></div><div class="body"><div class="n">{{ $deliveredJobs }}</div><div class="l">Delivered</div></div></div>
    </div>

    <div class="dj-toolbar">
        <form class="dj-filter-row" method="GET" action="{{ route('crm.design_jobs.index') }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="dj-filter">
                <i class="fas fa-filter" aria-hidden="true"></i>
                <select id="djStageFilter" name="stage" onchange="this.form.submit()">
                    <option value="all" {{ $stage === 'all' ? 'selected' : '' }}>All statuses</option>
                    <option value="unassigned" {{ $stage === 'unassigned' ? 'selected' : '' }}>Not set</option>
                    @foreach(\App\DesignJob::STAGES as $stageKey => $stageLabel)
                        <option value="{{ $stageKey }}" {{ $stage === $stageKey ? 'selected' : '' }}>{{ $stageLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="dj-date-filter">
                <i class="far fa-calendar-alt" aria-hidden="true"></i>
                <select id="djDueFilter" name="due" aria-label="Filter by due date">
                    <option value="all" {{ $dueFilter === 'all' ? 'selected' : '' }}>All</option>
                    <option value="custom" {{ $dueFilter === 'custom' ? 'selected' : '' }}>Custom Date</option>
                    <option value="due" {{ $dueFilter === 'due' ? 'selected' : '' }}>Due</option>
                    <option value="overdue" {{ $dueFilter === 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="dj-custom-date" id="djCustomDueWrap" {{ $dueFilter === 'custom' ? '' : 'hidden' }}>
                <input id="djCustomDueDate" type="date" name="custom_due_date" value="{{ $customDueDate }}" aria-label="Select custom due date">
            </div>
            <div class="dj-search">
                <i class="fas fa-search"></i>
                <input name="search" value="{{ request('search') }}" placeholder="Search job, ticket, client…" oninput="if(!this.value){this.form.submit()}">
            </div>
        </form>
    </div>

    <div class="dj-card">
        <div class="dj-wrap">
        <table class="dj-table">
            <thead><tr>
                <th>Job</th>
                {{-- <th>Estimate</th> --}}
                <th>Title</th>
                <th>Current Stage</th>
                <th>Designer</th>
                {{-- <th>Delivery</th> --}}
                <th>Due</th>
                <th>Attachments</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($jobs as $job)
                @php
                    [$sc, $sb] = $statusColors[$job->status] ?? ['#4b5563', '#eef0f2'];
                    $pct = $job->progressPercent();
                    $challan = $job->challan;
                @endphp
                <tr data-challan="{{ $challan ? json_encode($challan->only(['challan_date', 'delivery_date', 'vehicle_no', 'customer_name', 'contact_person', 'po_reference', 'delivery_address', 'remarks', 'prepared_by', 'driver_name', 'driver_contact', 'received_by', 'items'])) : '' }}"
                    data-challan-update-url="{{ $challan ? route('crm.challans.update', $challan->id) : '' }}">
                    <td class="dj-job">
                        <a href="{{ route('crm.design_jobs.job_card.edit', $job->id) }}">{{ $job->job_number }}</a>
                        <div class="sub">{{ $job->created_at->format('d M Y') }}</div>
                    </td>
                    {{-- <td class="dj-est">
                        @if($job->ticket)
                            <a href="{{ route('crm.estimate_tickets.show', $job->ticket->id) }}">{{ $job->ticket->ticket_number }}</a>
                            <div class="sub">{{ $job->ticket->client_name }}</div>
                        @elseif($job->estimate_number)
                            <span class="manual">{{ $job->estimate_number }}</span>
                        @else <span class="sub">—</span> @endif
                    </td> --}}
                    <td class="dj-title">{{ $job->title }}
                        @if($job->client_name)<div class="sub">Client: {{ $job->client_name }}</div>@endif
                        @if($job->details)<div class="sub">{{ \Illuminate\Support\Str::limit($job->details, 60) }}</div>@endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('crm.design_jobs.stage', $job->id) }}" class="dj-stage-form">
                            @csrf
                            <select name="production_stage" class="dj-stage-select {{ $job->production_stage ? '' : 'dj-stage-muted' }}"
                                    data-prev="{{ $job->production_stage }}"
                                    data-challan-url="{{ route('crm.design_jobs.challan.store', $job->id) }}"
                                    data-job-no="{{ $job->job_number }}"
                                    data-customer="{{ optional($job->ticket)->client_name }}"
                                    data-title="{{ $job->title }}"
                                    data-designer="{{ $job->designer->name ?? '' }}">
                                <option value="">— Set stage —</option>
                                @foreach(\App\DesignJob::STAGES as $stageKey => $stageLabel)
                                    <option value="{{ $stageKey }}" {{ $job->production_stage === $stageKey ? 'selected' : '' }}>{{ $stageLabel }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>
                        <div class="dj-designer">
                            <span class="dj-ava">{{ $initials($job->designer->name ?? '') }}</span>
                            <span>{{ $job->designer->name ?? '—' }}</span>
                        </div>
                    </td>
                    {{-- <td class="dj-deliv">
                        @if($job->estimated_delivery_date){{ $job->estimated_delivery_date->format('d M Y') }}@else<span class="none">—</span>@endif
                    </td> --}}
                    <td class="dj-deliv">
                        @php($__dm = $job->dueMeta())
                        @if($job->due_date)
                            <span style="display:inline-flex;flex-direction:column;gap:.1rem">
                                <strong style="color:{{ $__dm['color'] }}">{{ $job->due_date->format('d M Y') }}</strong>
                                <span style="font-size:.62rem;font-weight:800;padding:.1rem .4rem;border-radius:999px;color:{{ $__dm['color'] }};background:{{ $__dm['bg'] }};align-self:flex-start">{{ $__dm['label'] }}</span>
                            </span>
                        @else<span class="none">—</span>@endif
                    </td>
                    <td>
                        @php($files = $job->jobCard ? $job->jobCard->attachments : collect())
                        @if($files->isNotEmpty())
                            <div class="dj-files">
                                @foreach($files->take(2) as $file)
                                    <a href="{{ route('crm.design_jobs.attachments.download', [$job->id, $file->id]) }}" title="{{ $file->original_name }}" data-no-ajax-nav><i class="fas fa-paperclip"></i> {{ $file->original_name }}</a>
                                @endforeach
                                @if($files->count() > 2)
                                    <details><summary>+{{ $files->count() - 2 }} more</summary>
                                        @foreach($files->skip(2) as $file)
                                            <a href="{{ route('crm.design_jobs.attachments.download', [$job->id, $file->id]) }}" title="{{ $file->original_name }}" data-no-ajax-nav><i class="fas fa-paperclip"></i> {{ $file->original_name }}</a>
                                        @endforeach
                                    </details>
                                @endif
                            </div>
                        @else<span class="dj-deliv none">—</span>@endif
                    </td>
                    <td><div class="dj-actions">
                        <a class="dj-track" href="{{ route('crm.design_jobs.job_card.preview', $job->id) }}" aria-label="View job card {{ $job->job_number }}"><i class="fas fa-eye"></i> View</a>
                        <a class="dj-track" href="{{ route('crm.design_jobs.dummy', $job->id) }}"><i class="fas fa-stamp"></i> Dummy</a>
                        <div class="dj-menu">
                            <button type="button" class="dj-track dj-menu-btn" aria-haspopup="true"><i class="fas fa-ellipsis-h"></i> Actions</button>
                            <div class="dj-menu-list" hidden>
                                <a href="{{ route('crm.design_jobs.job_card.edit', $job->id) }}"><i class="fas fa-edit"></i> Edit</a>
                                <a href="{{ route('crm.design_jobs.job_card.print', $job->id) }}" target="_blank" rel="noopener"><i class="fas fa-print"></i> Print</a>
                                <a href="{{ route('crm.design_jobs.job_card.pdf', $job->id) }}" data-no-ajax-nav><i class="fas fa-file-pdf"></i> PDF</a>
                                @if($challan)
                                    <button type="button" class="dj-menu-edit-challan"><i class="fas fa-edit"></i> Edit Challan</button>
                                    <a href="{{ route('crm.challans.print', $challan->id) }}" target="_blank" rel="noopener" data-no-ajax-nav><i class="fas fa-print"></i> Print Challan</a>
                                @endif
                                @if($u->isAdmin() || ($u->isDesigner() && (int) $job->designer_id === (int) $u->id))
                                    <form method="POST" action="{{ route('crm.design_jobs.destroy', $job->id) }}" onsubmit="return confirm('Delete this job and its job card? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dj-menu-delete" type="submit" aria-label="Delete job {{ $job->job_number }}"><i class="fas fa-trash-alt"></i> Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="dj-empty">No design jobs yet.@if($canCreate) Click "New Job" to create one and fill its job card.@endif</div></td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        @if($jobs->hasPages())<div class="dj-pagination">{{ $jobs->links() }}</div>@endif
    </div>
</div>

{{-- ===== Delivery Challan modal (opens when a job stage is set to "Completed") ===== --}}
<div id="djChallanModal" class="djc-overlay" hidden>
    <div class="djc-modal">
        <div class="djc-head">
            <span><i class="fas fa-truck"></i> <span id="djcTitle">Delivery Challan</span></span>
            <button type="button" class="djc-x" data-djc-close aria-label="Close">&times;</button>
        </div>
        <form id="djChallanForm" method="POST" action="">
            @csrf
            <input type="hidden" name="_method" value="PUT" id="djcMethod" disabled>
            <div class="djc-body">
                <div class="djc-grid">
                    <div><label>Job No.</label><input name="_job_no_display" id="djcJobNo" readonly></div>
                    <div><label>Date</label><input type="date" name="challan_date" id="djcDate"></div>
                    <div><label>Delivery Date</label><input type="date" name="delivery_date"></div>
                    <div><label>Vehicle No.</label><input name="vehicle_no"></div>
                    <div><label>Customer Name</label><input name="customer_name" id="djcCustomer"></div>
                    <div><label>Contact Person</label><input name="contact_person"></div>
                    <div><label>P.O. / Reference</label><input name="po_reference"></div>
                    <div class="djc-span2"><label>Delivery Address</label><input name="delivery_address"></div>
                </div>

                <div class="djc-items-head">
                    <strong>Items</strong>
                    <button type="button" class="djc-addrow"><i class="fas fa-plus"></i> Add row</button>
                </div>
                <div class="djc-table-wrap">
                    <table class="djc-items">
                        <thead><tr>
                            <th style="width:30px">#</th>
                            <th>Job / Item Description</th>
                            <th style="width:90px">Boxes / Carton</th>
                            <th style="width:80px">Total Cartons</th>
                            <th style="width:120px">Carton Size (LxWxH)</th>
                            <th style="width:90px">Actual Wt. (kg)</th>
                            <th style="width:90px">Volumetric Wt.</th>
                            <th style="width:34px"></th>
                        </tr></thead>
                        <tbody id="djcItemsBody"></tbody>
                    </table>
                </div>

                <div class="djc-grid" style="margin-top:.8rem">
                    <div class="djc-span2"><label>Remarks / Special Instructions</label><input name="remarks"></div>
                    <div><label>Prepared By</label><input name="prepared_by" id="djcPrepared"></div>
                    <div><label>Driver Name</label><input name="driver_name"></div>
                    <div><label>Driver Contact</label><input name="driver_contact"></div>
                    <div><label>Received By (Customer)</label><input name="received_by"></div>
                </div>
            </div>
            <div class="djc-actions">
                <button type="button" class="djc-btn djc-btn-light" data-djc-close>Cancel</button>
                <button type="submit" class="djc-btn djc-btn-primary"><i class="fas fa-check-circle"></i> <span id="djcSubmitLabel">Save Challan</span></button>
            </div>
        </form>
    </div>
</div>

<style>
.dj-menu{position:relative;display:inline-block}
.dj-menu-list{position:fixed;left:0;top:0;z-index:1000;min-width:150px;max-height:calc(100vh - 16px);overflow-y:auto;background:#fff;border:1px solid var(--line,#e5e7eb);border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.14);padding:.3rem;display:flex;flex-direction:column;gap:.15rem}
.dj-menu-list[hidden]{display:none}
.dj-menu-list a,.dj-menu-list button{display:flex;align-items:center;gap:.5rem;padding:.5rem .6rem;border-radius:7px;font-size:.78rem;font-weight:700;color:#334155;text-decoration:none;background:none;border:0;width:100%;text-align:left;cursor:pointer}
.dj-menu-list a:hover{background:var(--primary-soft,#eef2fb);color:var(--primary-purple,#2b3a67)}
.dj-menu-list button:not(.dj-menu-delete):hover{background:var(--primary-soft,#eef2fb);color:var(--primary-purple,#2b3a67)}
.dj-menu-delete{color:#b91c1c}.dj-menu-delete:hover{background:#fee2e2}
.dj-menu-list form{margin:0}
.djc-overlay{position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.5);display:flex;align-items:flex-start;justify-content:center;padding:2.5rem 1rem;overflow:auto}
.djc-overlay[hidden]{display:none}
.djc-modal{width:100%;max-width:980px;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 60px rgba(15,23,42,.3)}
.djc-head{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.2rem;background:color-mix(in srgb, var(--primary-purple,#2b3a67) 78%, #fff);color:#fff;font-weight:800;font-size:1rem}
.djc-x{background:none;border:0;color:#fff;font-size:1.5rem;line-height:1;cursor:pointer}
.djc-body{padding:1.1rem 1.2rem;max-height:65vh;overflow:auto}
.djc-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.7rem}
.djc-grid .djc-span2{grid-column:span 2}
.djc-grid label{display:block;margin-bottom:.25rem;color:#42506a;font-size:.72rem;font-weight:750}
.djc-grid input{width:100%;min-height:38px;padding:.4rem .6rem;border:1px solid #cfd9e5;border-radius:8px;font-size:.82rem;outline:0}
.djc-grid input:focus{border-color:var(--primary-purple,#2b3a67);box-shadow:0 0 0 3px var(--primary-shadow,rgba(43,58,103,.15))}
.djc-items-head{display:flex;align-items:center;justify-content:space-between;margin:1rem 0 .4rem}
.djc-addrow{border:1px solid var(--primary-shadow,#c9d2e3);background:var(--primary-soft,#eef2fb);color:var(--primary-purple,#2b3a67);border-radius:8px;padding:.35rem .7rem;font-weight:800;font-size:.75rem;cursor:pointer}
.djc-table-wrap{overflow-x:auto;border:1px solid #e3e7ee;border-radius:8px}
.djc-items{width:100%;border-collapse:collapse;min-width:760px}
.djc-items th{background:color-mix(in srgb, var(--primary-purple,#2b3a67) 78%, #fff);color:#fff;font-size:.66rem;text-transform:uppercase;font-weight:700;padding:.5rem .4rem;text-align:left}
.djc-items td{border-bottom:1px solid #eef1f5;padding:.3rem .35rem}
.djc-items input{width:100%;min-height:32px;padding:.3rem .4rem;border:1px solid #dbe3ec;border-radius:6px;font-size:.78rem;outline:0}
.djc-items input:focus{border-color:var(--primary-purple,#2b3a67)}
.djc-delrow{border:0;background:#fee2e2;color:#b91c1c;border-radius:6px;min-height:30px;width:30px;cursor:pointer;font-size:.8rem}
.djc-actions{display:flex;justify-content:flex-end;gap:.6rem;padding:.9rem 1.2rem;border-top:1px solid #eef1f5;background:#fafbfd}
.djc-btn{display:inline-flex;align-items:center;gap:.4rem;min-height:42px;padding:.55rem 1.2rem;border:0;border-radius:9px;font-weight:800;cursor:pointer}
.djc-btn-light{background:#eef2f7;color:#475569}
.djc-btn-primary{background:color-mix(in srgb, var(--primary-purple,#2b3a67) 82%, #fff);color:#fff}
@media(max-width:720px){.djc-grid{grid-template-columns:1fr 1fr}.djc-grid .djc-span2{grid-column:span 2}}
</style>

<script>
// Bind once to document so it survives the CRM's AJAX page swaps.
(function(){
    if(window.__djcBound) return;
    window.__djcBound = true;
    var rowIndex = 0;
    var pendingSelect = null;

    function bindDueFilter(){
        var filter=document.getElementById('djDueFilter');
        var wrap=document.getElementById('djCustomDueWrap');
        var date=document.getElementById('djCustomDueDate');
        if(!filter||!wrap||!date||filter.dataset.bound==='1')return;
        filter.dataset.bound='1';
        filter.addEventListener('change',function(){
            if(filter.value==='custom'){
                wrap.removeAttribute('hidden');
                date.focus();
            }else{
                date.value='';
                filter.form.submit();
            }
        });
        date.addEventListener('change',function(){if(date.value)filter.form.submit();});
    }
    bindDueFilter();
    document.addEventListener('crm:page-loaded',bindDueFilter);

    function esc(s){ return String(s==null?'':s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
    function rowHtml(i, item){
        item = item || {};
        return '<tr>'+
            '<td style="text-align:center;color:#64748b">'+(i+1)+'</td>'+
            '<td><input name="items['+i+'][description]" value="'+esc(item.description)+'"></td>'+
            '<td><input name="items['+i+'][boxes_per_carton]" value="'+esc(item.boxes_per_carton)+'"></td>'+
            '<td><input name="items['+i+'][total_cartons]" value="'+esc(item.total_cartons)+'"></td>'+
            '<td><input name="items['+i+'][carton_size]" value="'+esc(item.carton_size)+'"></td>'+
            '<td><input name="items['+i+'][actual_wt]" value="'+esc(item.actual_wt)+'"></td>'+
            '<td><input name="items['+i+'][volumetric_wt]" value="'+esc(item.volumetric_wt)+'"></td>'+
            '<td><button type="button" class="djc-delrow">&times;</button></td>'+
            '</tr>';
    }
    function addRow(item){
        var body = document.getElementById('djcItemsBody');
        if(!body) return;
        body.insertAdjacentHTML('beforeend', rowHtml(rowIndex, item));
        rowIndex++;
    }
    function openChallan(sel){
        var form = document.getElementById('djChallanForm');
        if(!form || !sel) return;
        var row = sel.closest('tr');
        var challan = null;
        try { challan = JSON.parse(row.getAttribute('data-challan') || 'null'); } catch(e) {}
        form.reset();
        form.action = challan ? row.getAttribute('data-challan-update-url') : sel.getAttribute('data-challan-url');
        document.getElementById('djcMethod').disabled = !challan;
        document.getElementById('djcTitle').textContent = challan ? 'Edit Delivery Challan' : 'Delivery Challan';
        document.getElementById('djcSubmitLabel').textContent = challan ? 'Update Challan' : 'Save Challan';
        var set = function(id,v){ var el=document.getElementById(id); if(el) el.value=v; };
        set('djcJobNo', sel.getAttribute('data-job-no')||'');
        set('djcCustomer', sel.getAttribute('data-customer')||'');
        set('djcPrepared', sel.getAttribute('data-designer')||'');
        var d = new Date();
        set('djcDate', d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'));
        if(challan){
            ['challan_date','delivery_date','vehicle_no','customer_name','contact_person','po_reference',
             'delivery_address','remarks','prepared_by','driver_name','driver_contact','received_by'].forEach(function(name){
                var field = form.elements.namedItem(name);
                if(field) field.value = challan[name] == null ? '' : String(challan[name]).slice(0, name.endsWith('_date') ? 10 : undefined);
            });
        }
        rowIndex = 0;
        var body = document.getElementById('djcItemsBody');
        if(body) body.innerHTML = '';
        if(challan && Array.isArray(challan.items) && challan.items.length){
            challan.items.forEach(addRow);
        }else{
            addRow(challan ? {} : {description:sel.getAttribute('data-title')||''});
        }
        var modal = document.getElementById('djChallanModal');
        if(modal) modal.removeAttribute('hidden');
    }
    function closeChallan(){
        var modal = document.getElementById('djChallanModal');
        if(modal) modal.setAttribute('hidden','');
        if(pendingSelect){ pendingSelect.value = pendingSelect.getAttribute('data-prev')||''; pendingSelect = null; }
    }
    function closeActionMenus(){
        document.querySelectorAll('.dj-menu-list').forEach(function(list){ list.setAttribute('hidden',''); });
    }
    function openActionMenu(button, list){
        var rect = button.getBoundingClientRect();
        list.style.visibility = 'hidden';
        list.removeAttribute('hidden');
        var width = list.offsetWidth, height = list.offsetHeight;
        var left = Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8));
        var above = window.innerHeight - rect.bottom < height + 8 && rect.top > height + 8;
        var top = above ? rect.top - height - 4 : rect.bottom + 4;
        list.style.left = left + 'px';
        list.style.top = Math.max(8, Math.min(top, window.innerHeight - height - 8)) + 'px';
        list.style.visibility = '';
    }

    // Stage dropdown change: "Completed" opens the challan modal; anything else saves.
    document.addEventListener('change', function(e){
        var sel = e.target.closest ? e.target.closest('.dj-stage-select') : null;
        if(!sel) return;
        if(sel.value === 'close'){ pendingSelect = sel; openChallan(sel); }
        else if(sel.form){ sel.form.submit(); }
    });

    document.addEventListener('click', function(e){
        var t = e.target;
        // Actions menu toggle
        var mbtn = t.closest('.dj-menu-btn');
        if(mbtn){
            var list = mbtn.nextElementSibling;
            var isOpen = list && !list.hasAttribute('hidden');
            closeActionMenus();
            if(list && !isOpen) openActionMenu(mbtn, list);
            return;
        }
        if(!t.closest('.dj-menu')){
            closeActionMenus();
        }
        var editChallan = t.closest('.dj-menu-edit-challan');
        if(editChallan){
            pendingSelect = null;
            editChallan.closest('.dj-menu-list').setAttribute('hidden','');
            openChallan(editChallan.closest('tr').querySelector('.dj-stage-select'));
            return;
        }
        // Challan modal controls
        if(t.closest('[data-djc-close]')){ closeChallan(); return; }
        if(t.closest('.djc-addrow')){ addRow(); return; }
        var del = t.closest('.djc-delrow');
        if(del){
            var row = del.closest('tr'); var body = row.parentNode;
            row.remove();
            if(body && !body.children.length) addRow();
            return;
        }
    });
    document.addEventListener('scroll', closeActionMenus, true);
    window.addEventListener('resize', closeActionMenus);
})();
</script>
@endsection
