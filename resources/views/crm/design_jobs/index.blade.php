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
        max-width: 1180px; margin: 0 auto; color: var(--ink);
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
    .dj-search { position:relative; }
    .dj-search i { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:#b3b8c2; font-size:.82rem; }
    .dj-search input { border:1px solid var(--line); border-radius:10px; padding:.6rem .8rem .6rem 2.1rem; font-size:.85rem;
        font-family:inherit; outline:none; min-width:260px; transition:all .13s; background:var(--card); }
    .dj-search input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }

    /* Table card */
    .dj-card { background:var(--card); border:1px solid var(--line); border-radius:16px; overflow:hidden;
        box-shadow:0 1px 3px rgba(20,23,33,.04), 0 18px 40px -30px rgba(20,23,33,.25); }
    .dj-wrap { overflow-x:auto; }
    .dj-table { width:100%; border-collapse:collapse; min-width:860px; }
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
    .dj-track { display:inline-flex; align-items:center; gap:.4rem; font-size:.78rem; font-weight:600; text-decoration:none;
        color:#5b616e; border:1px solid var(--line); border-radius:9px; padding:.42rem .75rem; transition:all .14s; white-space:nowrap; }
    .dj-track:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-soft); }
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
        <a class="dj-new" href="{{ route('crm.design_jobs.create') }}"><i class="fas fa-plus"></i> New Job</a>
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
        <div class="dj-chips">
            <a class="dj-chip {{ $status === 'all' ? 'active' : '' }}" href="{{ route('crm.design_jobs.index', array_merge(request()->except('page'), ['status' => 'all'])) }}">All<span class="c">{{ $totalJobs }}</span></a>
            @foreach(\App\DesignJob::STATUSES as $key => $label)
                <a class="dj-chip {{ $status === $key ? 'active' : '' }}" href="{{ route('crm.design_jobs.index', array_merge(request()->except('page'), ['status' => $key])) }}">{{ $label }}<span class="c">{{ $statusCounts[$key] ?? 0 }}</span></a>
            @endforeach
        </div>
        <form class="dj-search" method="GET" action="{{ route('crm.design_jobs.index') }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <i class="fas fa-search"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Search job, ticket, client…" oninput="if(!this.value){this.form.submit()}">
        </form>
    </div>

    <div class="dj-card">
        <div class="dj-wrap">
        <table class="dj-table">
            <thead><tr>
                <th>Job</th>
                <th>Estimate</th>
                <th>Title</th>
                <th>Designer</th>
                <th>Status</th>
                <th>Delivery</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse($jobs as $job)
                @php [$sc, $sb] = $statusColors[$job->status] ?? ['#4b5563', '#eef0f2']; $pct = $job->progressPercent(); @endphp
                <tr>
                    <td class="dj-job">
                        <a href="{{ route('crm.design_jobs.show', $job->id) }}">{{ $job->job_number }}</a>
                        <div class="sub">{{ $job->created_at->format('d M Y') }}</div>
                    </td>
                    <td class="dj-est">
                        @if($job->ticket)
                            <a href="{{ route('crm.estimate_tickets.show', $job->ticket->id) }}">{{ $job->ticket->ticket_number }}</a>
                            <div class="sub">{{ $job->ticket->client_name }}</div>
                        @elseif($job->estimate_number)
                            <span class="manual">{{ $job->estimate_number }}</span>
                        @else <span class="sub">—</span> @endif
                    </td>
                    <td class="dj-title">{{ $job->title }}
                        @if($job->details)<div class="sub">{{ \Illuminate\Support\Str::limit($job->details, 60) }}</div>@endif
                    </td>
                    <td>
                        <div class="dj-designer">
                            <span class="dj-ava">{{ $initials($job->designer->name ?? '') }}</span>
                            <span>{{ $job->designer->name ?? '—' }}</span>
                        </div>
                    </td>
                    <td>
                        @if($u->isAdmin() || (int) $job->designer_id === (int) $u->id)
                            <form method="POST" action="{{ route('crm.design_jobs.status', $job->id) }}">{{ csrf_field() }}
                                <select class="dj-status-select" name="status" onchange="this.form.submit()" style="color:{{ $sc }}">
                                    @foreach(\App\DesignJob::STATUSES as $key => $label)
                                        <option value="{{ $key }}" {{ $job->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <span class="dj-badge" style="color:{{ $sc }};background:{{ $sb }}">{{ $job->statusLabel() }}</span>
                        @endif
                        <div class="dj-prog"><span style="width:{{ $pct }}%;background:{{ $sc }}"></span></div>
                    </td>
                    <td class="dj-deliv">
                        @if($job->estimated_delivery_date){{ $job->estimated_delivery_date->format('d M Y') }}@else<span class="none">—</span>@endif
                    </td>
                    <td><a class="dj-track" href="{{ route('crm.design_jobs.show', $job->id) }}"><i class="fas fa-stream"></i> Track</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="dj-empty">No design jobs yet.@if($canCreate) Create the first one against an estimate ticket.@endif</div></td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        @if($jobs->hasPages())<div class="dj-pagination">{{ $jobs->links() }}</div>@endif
    </div>
</div>
@endsection
