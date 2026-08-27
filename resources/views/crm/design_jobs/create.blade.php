@extends('crm.layout')
@section('title', 'New Design Job')
@section('content')
<style>
    .djc-page { --ink: #191c22; --muted: #7a7f8a; --hair: #e7e2d4; --paper: #fbfaf6;
        --red: #b3282d; --red-dark: #8f181d;
        max-width: 720px; margin: 0 auto; color: var(--ink);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Inter, sans-serif; }
    .djc-page *, .djc-page *::before, .djc-page *::after { box-sizing: border-box; }
    .djc-mono { font-family: 'SF Mono', 'JetBrains Mono', 'Courier New', ui-monospace, monospace; letter-spacing: .09em; }
    .djc-mast { padding: .35rem .2rem 1.15rem; border-bottom: 3px solid var(--ink); margin-bottom: 1.3rem;
        display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap; }
    .djc-mast h1 { margin: 0; font-size: 1.3rem; font-weight: 800; letter-spacing: .05em; }
    .djc-back { font-size: .72rem; text-transform: uppercase; text-decoration: none; color: var(--ink);
        border: 1px solid #cfcaba; background: #fff; padding: .5rem .85rem; border-radius: 999px; transition: all .15s; }
    .djc-back:hover { border-color: var(--ink); }
    .djc-banner { font-size: .86rem; padding: .85rem 1.1rem; border-radius: 10px; margin-bottom: 1rem;
        background: #fdf0f0; border: 1px solid #eac5c5; }
    .djc-card { background: #fff; border: 1px solid var(--hair); border-radius: 14px; padding: 1.7rem 1.9rem 2rem;
        box-shadow: 0 1px 2px rgba(25,28,34,.04), 0 12px 32px -18px rgba(25,28,34,.18); }
    .djc-field { margin-bottom: 1.15rem; }
    .djc-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media (max-width: 560px) { .djc-row { grid-template-columns: 1fr; } }
    .djc-field label { display: block; font-size: .64rem; text-transform: uppercase; color: var(--muted); margin-bottom: .45rem; }
    .djc-field label .req { color: var(--red); }
    .djc-in, .djc-select, .djc-textarea { width: 100%; border: 1px solid #d6d2c4; border-radius: 10px; background: var(--paper);
        padding: .7rem .85rem; font-size: .89rem; font-family: inherit; outline: none; transition: all .13s; }
    .djc-in:focus, .djc-select:focus, .djc-textarea:focus { background: #fff; border-color: var(--red); box-shadow: 0 0 0 3px rgba(179,40,45,.1); }
    .djc-textarea { min-height: 110px; resize: vertical; }
    .djc-hint { font-size: .72rem; color: var(--muted); margin-top: .35rem; }
    .djc-actions { display: flex; justify-content: flex-end; gap: .7rem; border-top: 1px solid var(--hair);
        margin-top: 1.6rem; padding-top: 1.3rem; }
    .djc-btn { padding: .7rem 1.45rem; font-size: .84rem; font-weight: 600; font-family: inherit; cursor: pointer;
        border-radius: 9px; border: 1px solid transparent; transition: all .15s; text-decoration: none; }
    .djc-ghost { background: transparent; color: #6d7280; border-color: #d6d2c4; }
    .djc-ghost:hover { color: var(--ink); border-color: var(--ink); }
    .djc-red { background: var(--red); color: #fff; box-shadow: 0 8px 18px -8px rgba(179,40,45,.55); }
    .djc-red:hover { background: var(--red-dark); transform: translateY(-1px); }
</style>

@section('header_actions')
    <a class="djc-back djc-mono" href="{{ route('crm.design_jobs.index') }}">&larr; Design Jobs</a>
@endsection

<div class="djc-page">
    @if(session('error'))<div class="djc-banner">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="djc-banner">{{ $errors->first() }}</div>@endif

    <div class="djc-card">
        <form method="POST" action="{{ route('crm.design_jobs.store') }}" class="djc-form">
            {{ csrf_field() }}
            <div class="djc-row">
                <div class="djc-field">
                    <label class="djc-mono">Job Number</label>
                    <input class="djc-in" name="job_number" value="{{ old('job_number', $suggestedJobNumber ?? '') }}" maxlength="100" placeholder="e.g. JOB-1024">
                    <div class="djc-hint">Auto-suggested. Edit it or type your own.</div>
                </div>
                <div class="djc-field">
                    <label class="djc-mono">Estimate Number</label>
                    <input class="djc-in" name="estimate_number" id="djcEstNo" value="{{ old('estimate_number') }}" maxlength="100" placeholder="e.g. EST-2048 (if not selecting a ticket)">
                    <div class="djc-hint">Type manually, or pick a ticket below.</div>
                </div>
            </div>
            <div class="djc-field">
                <label class="djc-mono">Estimate Ticket</label>
                <select class="djc-select" name="estimate_ticket_id" id="djcTicket">
                    <option value="">Select an estimate ticket...</option>
                    @foreach($tickets as $ticket)
                        <option value="{{ $ticket->id }}" data-title="{{ $ticket->product_style }}"
                            {{ (string) old('estimate_ticket_id') === (string) $ticket->id ? 'selected' : '' }}>
                            {{ $ticket->ticket_number }} — {{ $ticket->client_name }} — {{ $ticket->product_style }}
                        </option>
                    @endforeach
                </select>
                <div class="djc-hint">Job is created against this estimation. Latest 150 tickets shown. Leave empty if using a manual estimate number.</div>
            </div>
            <div class="djc-field">
                <label class="djc-mono">Job Title <span class="req">*</span></label>
                <input class="djc-in" name="title" id="djcTitle" value="{{ old('title') }}" required maxlength="255" placeholder="e.g. Rigid box dieline + artwork">
            </div>
            <div class="djc-row">
                <div class="djc-field">
                    <label class="djc-mono">Status</label>
                    <select class="djc-select" name="status">
                        @foreach(\App\DesignJob::STATUSES as $key => $label)
                            <option value="{{ $key }}" {{ old('status', 'designing') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="djc-hint">Current production stage.</div>
                </div>
                <div class="djc-field">
                    <label class="djc-mono">Estimated Delivery Date</label>
                    <input class="djc-in" type="date" name="estimated_delivery_date" value="{{ old('estimated_delivery_date') }}">
                    <div class="djc-hint">Estimated date of delivery for this job.</div>
                </div>
            </div>
            <div class="djc-field">
                <label class="djc-mono">Details (optional)</label>
                <textarea class="djc-textarea" name="details" maxlength="3000" placeholder="Design brief, dieline notes, references...">{{ old('details') }}</textarea>
            </div>
            <div class="djc-actions">
                <a class="djc-btn djc-ghost" href="{{ route('crm.design_jobs.index') }}">Cancel</a>
                <button class="djc-btn djc-red" type="submit">Create Job &rarr;</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('djcTicket').addEventListener('change', function () {
        var title = document.getElementById('djcTitle');
        var selected = this.options[this.selectedIndex];
        if (!title.value && selected && selected.dataset.title) title.value = selected.dataset.title;
        // Picking a ticket supersedes a manual estimate number.
        var estNo = document.getElementById('djcEstNo');
        if (this.value && estNo) estNo.value = '';
    });
</script>
@endsection
