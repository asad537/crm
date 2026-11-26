@extends('crm.layout')
@section('title', 'Voice Calls')
@section('content')
<style>
.vc-page{color:#233047;max-width:1050px}
.vc-hero{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.15rem 1.35rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px;flex-wrap:wrap}
.vc-hero h2{margin:0;font-size:1.3rem}
.vc-hero p{margin:.2rem 0 0;color:#8290a3;font-size:.78rem}
.vc-credits{font-size:.8rem;background:#fff;border:1px solid #e4eaf1;border-radius:12px;padding:.55rem .9rem;color:#233047}
.vc-credits b{font-size:.95rem}
.vc-card{background:#fff;border:1px solid #e4eaf1;border-radius:15px;padding:1.1rem 1.25rem;margin-bottom:1rem}
.vc-form{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center}
.vc-form input[type=text]{flex:1;min-width:230px;padding:.6rem .8rem;border:1px solid #d7dfe9;border-radius:10px;font-size:.95rem}
.vc-btn{background:#4f46e5;color:#fff;border:none;border-radius:10px;padding:.62rem 1.3rem;font-size:.9rem;cursor:pointer}
.vc-btn:disabled{opacity:.6;cursor:wait}
.vc-note{color:#8290a3;font-size:.74rem;margin-top:.45rem}
.vc-flash{border-radius:11px;padding:.7rem 1rem;font-size:.85rem;margin-bottom:1rem}
.vc-flash.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.vc-flash.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
table.vc-table{width:100%;border-collapse:collapse;font-size:.83rem}
.vc-table th{text-align:left;color:#8290a3;font-weight:600;padding:.45rem .6rem;border-bottom:1px solid #edf1f6;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em}
.vc-table td{padding:.55rem .6rem;border-bottom:1px solid #f3f6fa;vertical-align:top}
.vc-pill{display:inline-block;padding:.14rem .55rem;border-radius:999px;font-size:.7rem;font-weight:600}
.vc-pill.done{background:#ecfdf5;color:#047857}
.vc-pill.failed{background:#fef2f2;color:#b91c1c}
.vc-pill.other{background:#eef2ff;color:#4338ca}
.vc-lead a{color:#4f46e5;text-decoration:none;font-weight:600}
.vc-tr-link{color:#4f46e5;text-decoration:none;font-weight:600;white-space:nowrap}
</style>

<div class="vc-page">
    <div class="vc-hero">
        <div>
            <h2><i class="fas fa-phone-alt" style="color:var(--primary-purple)"></i> Voice Calls — Sara (AI)</h2>
            <p>Number likhein, Sara khud call kar ke requirement le gi; email milne par lead apne aap ban jati hai.</p>
        </div>
        @if($credits && $credits['limit'])
            @php $left = max(0, $credits['limit'] - (int) $credits['used']); @endphp
            <div class="vc-credits">ElevenLabs credits: <b>{{ number_format($left) }}</b> / {{ number_format($credits['limit']) }} baqi
                ≈ {{ floor($left / 700) }} min calls</div>
        @endif
    </div>

    @if(session('vc_success'))<div class="vc-flash ok"><i class="fas fa-check-circle"></i> {{ session('vc_success') }}</div>@endif
    @if(session('vc_error'))<div class="vc-flash err"><i class="fas fa-exclamation-triangle"></i> {{ session('vc_error') }}</div>@endif
    @if($errors->any())<div class="vc-flash err">{{ $errors->first() }}</div>@endif
    @if($error)<div class="vc-flash err">{{ $error }}</div>@endif

    @if(!$configured)
        <div class="vc-flash err">ElevenLabs keys CRM .env mein set nahi hain (ELEVENLABS_API_KEY / _AGENT_ID / _PHONE_ID).</div>
    @endif

    <div class="vc-card">
        <form class="vc-form" method="POST" action="{{ route('crm.voice_calls.call') }}"
              onsubmit="var b=this.querySelector('button');b.disabled=true;b.innerHTML='<i class=\'fas fa-circle-notch fa-spin\'></i> Dialing… (1 min tak lag sakta hai)';">
            {{ csrf_field() }}
            <input type="text" name="phone" placeholder="+923001234567 ya 03001234567" value="{{ old('phone') }}" required {{ $configured ? '' : 'disabled' }}>
            <button class="vc-btn" type="submit" {{ $configured ? '' : 'disabled' }}><i class="fas fa-phone"></i> Call karo</button>
        </form>
        <div class="vc-note">Pakistani numbers 0 se shuru (03…) khud +92 ban jate hain; baqi mulkon ke liye +country-code likhein. Har call free credits se katti hai.</div>
    </div>

    <div class="vc-card">
        <h3 style="margin:0 0 .7rem;font-size:1rem">Recent calls</h3>
        <table class="vc-table">
            <thead><tr><th>Waqt</th><th>Status</th><th>Duration</th><th>Lead</th><th></th></tr></thead>
            <tbody>
            @forelse($calls as $c)
                @php
                    $lead = $leads['elvoice-' . $c['conversation_id']] ?? null;
                    $st = $c['status'] ?? '?';
                    $cls = $st === 'done' ? 'done' : ($st === 'failed' ? 'failed' : 'other');
                @endphp
                <tr>
                    <td>{{ isset($c['start_time_unix_secs']) ? date('d M, h:i A', $c['start_time_unix_secs']) : '—' }}</td>
                    <td><span class="vc-pill {{ $cls }}">{{ $st }}</span></td>
                    <td>{{ isset($c['call_duration_secs']) ? gmdate('i:s', (int) $c['call_duration_secs']) : '—' }}</td>
                    <td class="vc-lead">
                        @if($lead)
                            <a href="{{ route('crm.emails.show', $lead->id) }}">{{ $lead->client_name }}</a>
                            <div style="color:#8290a3;font-size:.72rem">{{ $lead->client_email }}</div>
                        @else
                            <span style="color:#aab4c3">—</span>
                        @endif
                    </td>
                    <td><a class="vc-tr-link" href="{{ route('crm.voice_calls.transcript', $c['conversation_id']) }}">Transcript <i class="fas fa-chevron-right" style="font-size:.6rem"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#8290a3;padding:1rem .6rem">Abhi koi call nahi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
