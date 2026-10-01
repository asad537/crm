@extends('crm.layout')

@section('title', 'Dummy / Sample Approval — ' . $job->job_number)

@section('header_actions')
<a class="jo-btn jo-btn-light" href="{{ route('crm.design_jobs.index') }}"><i class="fas fa-arrow-left"></i> Design Jobs</a>
@endsection

@section('content')
@php
    $dv = function ($value) {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) ($value ?? '');
    };
@endphp
<style>
.dm{width:100%;margin:0;color:var(--text-dark)}
.dm-hero{display:flex;align-items:center;gap:1rem;margin-bottom:1rem;padding:1.1rem 1.3rem;border:1px solid var(--primary-shadow);border-radius:14px;background:linear-gradient(120deg,var(--primary-soft),#fff 60%)}
.dm-hero .ic{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;background:var(--primary-purple);color:#fff;font-size:1.1rem}
.dm-hero h1{margin:0;color:var(--primary-purple);font-size:1.3rem;letter-spacing:-.02em}
.dm-hero p{margin:.25rem 0 0;color:var(--text-gray);font-size:.82rem}
.dm-flash{margin-bottom:1rem;padding:.7rem 1rem;border:1px solid #bbf7d0;border-radius:10px;background:#f0fdf4;color:#166534;font-size:.82rem;font-weight:700}
.dm-errors{margin-bottom:1rem;padding:.8rem 1rem;border:1px solid #fecaca;border-radius:10px;background:#fff5f5;color:#b91c1c;font-size:.8rem}
.dm-card{border:1px solid var(--primary-shadow);border-radius:12px;background:#fff;overflow:hidden}
.dm-bar{background:var(--primary-purple);color:#fff;font-weight:800;font-size:.78rem;letter-spacing:.4px;text-transform:uppercase;padding:.65rem 1rem}
.dm-body{padding:1.2rem}
.dm-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}
.dm label.lb{display:block;margin-bottom:.35rem;color:var(--text-dark);font-size:.78rem;font-weight:750}
.dm-in{width:100%;min-height:44px;padding:.5rem .7rem;border:1px solid var(--primary-shadow);border-radius:9px;background:#fff;color:var(--text-dark);font-size:.9rem;outline:0}
.dm-in:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.dm-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.2rem}
.dm-btn{display:inline-flex;align-items:center;gap:.4rem;min-height:44px;padding:.6rem 1.3rem;border:0;border-radius:10px;text-decoration:none;font-weight:800;cursor:pointer}
.dm-btn-light{color:var(--text-dark);background:var(--primary-soft)}
.dm-btn-primary{color:#fff;background:var(--primary-purple)}
.dm-btn-primary:hover{background:var(--primary-hover)}
@media(max-width:620px){.dm-grid{grid-template-columns:1fr}}
</style>

<div class="dm">
    <div class="dm-hero">
        <span class="ic"><i class="fas fa-stamp"></i></span>
        <div>
            <h1>Dummy / Sample Approval</h1>
            <p>{{ $job->job_number }} · {{ $job->title }}</p>
        </div>
    </div>

    @if(session('success'))<div class="dm-flash"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="dm-errors"><strong>Please check the form:</strong><ul style="margin:.4rem 0 0;padding-left:1.1rem">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('crm.design_jobs.dummy.save', $job->id) }}">
        @csrf
        <div class="dm-card">
            <div class="dm-bar">Dummy / Sample Approval</div>
            <div class="dm-body">
                <div class="dm-grid">
                    <div><label class="lb">Dummy sent on</label><input class="dm-in" type="date" name="dummy_sent_on" value="{{ old('dummy_sent_on', $dv($card->dummy_sent_on)) }}"></div>
                    <div><label class="lb">Dummy approved on</label><input class="dm-in" type="date" name="dummy_approved_on" value="{{ old('dummy_approved_on', $dv($card->dummy_approved_on)) }}"></div>
                    <div><label class="lb">Approved by (signature)</label><input class="dm-in" name="dummy_approved_by" value="{{ old('dummy_approved_by', $card->dummy_approved_by) }}"></div>
                </div>
            </div>
        </div>
        <div class="dm-actions">
            <a class="dm-btn dm-btn-light" href="{{ route('crm.design_jobs.index') }}">Cancel</a>
            <button class="dm-btn dm-btn-primary" type="submit"><i class="fas fa-check-circle"></i> Save Dummy</button>
        </div>
    </form>
</div>
@endsection
