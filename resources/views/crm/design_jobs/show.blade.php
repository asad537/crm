@extends('crm.layout')
@section('title', 'Design Job ' . $job->job_number)
@section('content')
@php
    $statuses = \App\DesignJob::STATUSES;
    $currentIdx = $job->statusIndex();
    $progress = $job->progressPercent();
    $estRef = $job->estimateRef();
@endphp
<style>
    .djs-page { --ink:#191c22; --muted:#7a7f8a; --hair:#e7e2d4; --paper:#fbfaf6; --beige:#f4efe3;
        --red:#b3282d; --red-dark:#8f181d; --green:#0f6d38;
        max-width: 900px; margin:0 auto; color:var(--ink);
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,sans-serif; }
    .djs-page *, .djs-page *::before, .djs-page *::after { box-sizing:border-box; }
    .djs-mono { font-family:'SF Mono','JetBrains Mono','Courier New',ui-monospace,monospace; letter-spacing:.09em; }
    .djs-mast { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap;
        padding:.35rem .2rem 1.15rem; border-bottom:3px solid var(--ink); margin-bottom:1.3rem; }
    .djs-mast h1 { margin:0; font-size:1.25rem; font-weight:800; letter-spacing:.05em; }
    .djs-mast .djs-sub { font-size:.69rem; color:var(--muted); margin-top:.4rem; text-transform:uppercase; }
    .djs-back { font-size:.72rem; text-transform:uppercase; text-decoration:none; color:var(--ink);
        border:1px solid #cfcaba; background:#fff; padding:.5rem .85rem; border-radius:999px; transition:all .15s; }
    .djs-back:hover { border-color:var(--ink); }
    .djs-banner { font-size:.86rem; padding:.85rem 1.1rem; border-radius:10px; margin-bottom:1rem;
        background:#eef9ef; border:1px solid #c4e3c8; }
    .djs-grid { display:grid; grid-template-columns: 1.15fr .85fr; gap:1.2rem; align-items:start; }
    @media (max-width: 760px) { .djs-grid { grid-template-columns:1fr; } }
    .djs-card { background:#fff; border:1px solid var(--hair); border-radius:14px; padding:1.5rem 1.6rem;
        box-shadow:0 1px 2px rgba(25,28,34,.04), 0 12px 32px -18px rgba(25,28,34,.18); }
    .djs-card h2 { margin:0 0 1.1rem; font-size:.7rem; text-transform:uppercase; letter-spacing:.12em; color:#55503f; font-weight:800; }
    /* Meta */
    .djs-meta { display:flex; flex-direction:column; gap:.9rem; }
    .djs-meta .row { display:flex; justify-content:space-between; gap:1rem; font-size:.85rem; align-items:baseline; }
    .djs-meta .row .k { color:var(--muted); font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; }
    .djs-meta .row .v { font-weight:600; text-align:right; }
    .djs-meta .row .v a { color:var(--red); text-decoration:none; }
    .djs-progress { margin:.2rem 0 1.3rem; }
    .djs-progress .bar { height:8px; border-radius:999px; background:#eee7d8; overflow:hidden; }
    .djs-progress .bar span { display:block; height:100%; background:linear-gradient(90deg,var(--red),#e0654a); border-radius:999px; }
    .djs-progress .lbl { display:flex; justify-content:space-between; font-size:.68rem; color:var(--muted); margin-top:.4rem; text-transform:uppercase; letter-spacing:.05em; }
    /* Stepper */
    .djs-steps { list-style:none; margin:0; padding:0; position:relative; }
    .djs-steps li { position:relative; padding:0 0 .1rem 2.4rem; }
    .djs-steps li .line { position:absolute; left:13px; top:1.35rem; bottom:-.35rem; width:2px; background:#e2ddcd; }
    .djs-steps li:last-child .line { display:none; }
    .djs-step-btn { display:flex; align-items:center; gap:.6rem; width:100%; text-align:left; background:transparent;
        border:none; padding:.5rem 0; font:inherit; cursor:default; color:var(--ink); }
    .djs-steps.editable .djs-step-btn { cursor:pointer; }
    .djs-dot { position:absolute; left:0; top:.5rem; width:28px; height:28px; border-radius:50%;
        display:grid; place-items:center; font-size:.72rem; font-weight:700; border:2px solid #d6d2c4; background:#fff; color:var(--muted); z-index:1; }
    .djs-step.done .djs-dot { background:var(--green); border-color:var(--green); color:#fff; }
    .djs-step.done .line { background:var(--green); }
    .djs-step.current .djs-dot { background:var(--red); border-color:var(--red); color:#fff; box-shadow:0 0 0 4px rgba(179,40,45,.16); }
    .djs-step-name { font-size:.9rem; font-weight:600; }
    .djs-step.done .djs-step-name { color:#3f4a43; }
    .djs-step.current .djs-step-name { color:var(--red); font-weight:800; }
    .djs-step.upcoming .djs-step-name { color:#9a958a; font-weight:500; }
    .djs-step-tag { margin-left:auto; font-size:.62rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); }
    .djs-editnote { font-size:.72rem; color:var(--muted); margin-top:.9rem; }
    .djs-deliv { margin-top:1.2rem; border-top:1px solid var(--hair); padding-top:1rem; }
    .djs-deliv label { display:block; font-size:.64rem; text-transform:uppercase; color:var(--muted); margin-bottom:.4rem; letter-spacing:.06em; }
    .djs-deliv-row { display:flex; gap:.5rem; }
    .djs-deliv input { flex:1; border:1px solid #d6d2c4; border-radius:9px; background:var(--paper); padding:.55rem .7rem; font:inherit; font-size:.85rem; outline:none; }
    .djs-deliv input:focus { border-color:var(--red); box-shadow:0 0 0 3px rgba(179,40,45,.1); background:#fff; }
    .djs-deliv button { border:1px solid var(--ink); background:#fff; border-radius:9px; padding:.55rem .9rem; font:inherit; font-size:.8rem; font-weight:600; cursor:pointer; }
    .djs-deliv button:hover { background:var(--ink); color:#fff; }
    .djs-details { margin-top:1rem; font-size:.85rem; color:#4a4f59; line-height:1.6; white-space:pre-wrap; }
</style>

@section('header_actions')
    <a class="djs-back djs-mono" href="{{ route('crm.design_jobs.index') }}">&larr; Design Jobs</a>
@endsection

<div class="djs-page">
    <div class="djs-sub djs-mono" style="margin:.2rem 0 1.1rem;">{{ $job->job_number }} &nbsp;·&nbsp; {{ $job->title }}</div>

    @if(session('success'))<div class="djs-banner">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="djs-banner" style="background:#fdf0f0;border-color:#eac5c5">{{ session('error') }}</div>@endif

    <div class="djs-grid">
        {{-- Tracker --}}
        <div class="djs-card">
            <h2>Status Tracker</h2>
            <div class="djs-progress">
                <div class="bar"><span style="width: {{ $progress }}%"></span></div>
                <div class="lbl"><span>{{ $job->statusLabel() }}</span><span>{{ $progress }}%</span></div>
            </div>
            <ul class="djs-steps {{ $canUpdate ? 'editable' : '' }}">
                @foreach($statuses as $key => $label)
                    @php
                        $i = $loop->index;
                        $cls = $i < $currentIdx ? 'done' : ($i === $currentIdx ? 'current' : 'upcoming');
                    @endphp
                    <li class="djs-step {{ $cls }}">
                        <span class="line"></span>
                        <span class="djs-dot">
                            @if($i < $currentIdx)<i class="fas fa-check"></i>@else{{ $i + 1 }}@endif
                        </span>
                        @if($canUpdate)
                            <form method="POST" action="{{ route('crm.design_jobs.status', $job->id) }}" style="margin:0">
                                {{ csrf_field() }}
                                <input type="hidden" name="status" value="{{ $key }}">
                                <button type="submit" class="djs-step-btn">
                                    <span class="djs-step-name">{{ $label }}</span>
                                    @if($i === $currentIdx)<span class="djs-step-tag" style="color:var(--red)">Current</span>
                                    @elseif($i < $currentIdx)<span class="djs-step-tag" style="color:var(--green)">Done</span>@endif
                                </button>
                            </form>
                        @else
                            <div class="djs-step-btn">
                                <span class="djs-step-name">{{ $label }}</span>
                                @if($i === $currentIdx)<span class="djs-step-tag" style="color:var(--red)">Current</span>
                                @elseif($i < $currentIdx)<span class="djs-step-tag" style="color:var(--green)">Done</span>@endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if($canUpdate)<div class="djs-editnote">Click any stage to move the job there.</div>@endif
        </div>

        {{-- Meta --}}
        <div class="djs-card">
            <h2>Job Details</h2>
            <div class="djs-meta">
                <div class="row"><span class="k">Estimate</span><span class="v">
                    @if($job->ticket)
                        <a href="{{ route('crm.estimate_tickets.show', $job->ticket->id) }}">{{ $job->ticket->ticket_number }}</a>
                    @elseif($estRef)
                        {{ $estRef }}
                    @else — @endif
                </span></div>
                @if($job->ticket)
                    <div class="row"><span class="k">Client</span><span class="v">{{ $job->ticket->client_name }}</span></div>
                @endif
                <div class="row"><span class="k">Designer</span><span class="v">{{ $job->designer->name ?? '—' }}</span></div>
                <div class="row"><span class="k">Status</span><span class="v" style="color:var(--red)">{{ $job->statusLabel() }}</span></div>
                <div class="row"><span class="k">Delivery Date</span><span class="v">{{ optional($job->estimated_delivery_date)->format('d M Y') ?: '—' }}</span></div>
                <div class="row"><span class="k">Created</span><span class="v">{{ $job->created_at->format('d M Y') }}</span></div>
                <div class="row"><span class="k">Last Update</span><span class="v">{{ optional($job->status_updated_at ?: $job->updated_at)->format('d M Y, h:i A') }}</span></div>
            </div>

            @if($canUpdate)
                <div class="djs-deliv">
                    <form method="POST" action="{{ route('crm.design_jobs.status', $job->id) }}">
                        {{ csrf_field() }}
                        <input type="hidden" name="status" value="{{ $job->status }}">
                        <label class="djs-mono">Update Delivery Date</label>
                        <div class="djs-deliv-row">
                            <input type="date" name="estimated_delivery_date" value="{{ optional($job->estimated_delivery_date)->format('Y-m-d') }}">
                            <button type="submit">Save</button>
                        </div>
                    </form>
                </div>
            @endif

            @if($job->details)
                <div class="djs-deliv" style="margin-top:1rem">
                    <label class="djs-mono">Details</label>
                    <div class="djs-details">{{ $job->details }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
