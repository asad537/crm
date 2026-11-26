@extends('crm.layout')
@section('title', 'Voice Calls')
@section('content')
<style>
.vc-page{color:#233047;width:100%}
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
.vc-btn.sm{padding:.38rem .8rem;font-size:.76rem}
.vc-btn.ghost{background:#eef2ff;color:#4338ca}
.vc-btn.red{background:#fee2e2;color:#b91c1c}
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
.vc-pill.pending{background:#fef9c3;color:#854d0e}
.vc-pill.calling{background:#dbeafe;color:#1d4ed8}
.vc-pill.cancelled{background:#f1f5f9;color:#64748b}
.vc-lead a{color:#4f46e5;text-decoration:none;font-weight:600}
.vc-tr-link{color:#4f46e5;text-decoration:none;font-weight:600;white-space:nowrap}
.vc-lead-head{display:flex;justify-content:space-between;align-items:center;gap:.8rem;flex-wrap:wrap;margin-bottom:.7rem}
.vc-lead-head h3{margin:0;font-size:1rem}
.vc-search{padding:.45rem .7rem;border:1px solid #d7dfe9;border-radius:9px;font-size:.82rem;min-width:220px}
.vc-qbar{font-size:.78rem;color:#4338ca;background:#eef2ff;border:1px solid #e0e7ff;border-radius:10px;padding:.5rem .8rem;margin-bottom:.8rem;display:flex;gap:.8rem;align-items:center;flex-wrap:wrap}
</style>

<div class="vc-page">
    <div class="vc-hero">
        <div>
            <h2><i class="fas fa-phone-alt" style="color:var(--primary-purple)"></i> Voice Calls — Sara (AI)</h2>
            <p>Number likhein ya neeche leads chun kar Sara se follow-up calls karwayein; email milne par lead apne aap ban jati hai.</p>
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

    {{-- ===================== Leads follow-up ===================== --}}
    <div class="vc-card">
        <div class="vc-lead-head">
            <h3><i class="fas fa-user-friends" style="color:var(--primary-purple)"></i> Leads — follow-up calls</h3>
            <form method="GET" action="{{ route('crm.voice_calls.index') }}" style="display:flex;gap:.4rem">
                <input class="vc-search" type="text" name="q" value="{{ $q }}" placeholder="Naam / email / phone / product dhoondein…">
                <button class="vc-btn sm ghost" type="submit">Search</button>
            </form>
        </div>

        @if($queueStats['pending'] || $queueStats['calling'])
            <div class="vc-qbar">
                <span><i class="fas fa-list"></i> Queue: <b>{{ $queueStats['pending'] }}</b> pending, <b>{{ $queueStats['calling'] }}</b> calling — Sara aik aik kar ke call kar rahi hai (har minute agli).</span>
                <form method="POST" action="{{ route('crm.voice_calls.queue_cancel') }}" onsubmit="return confirm('Saare pending calls cancel?');" style="margin:0">
                    {{ csrf_field() }}<button class="vc-btn sm red" type="submit">Pending cancel karo</button>
                </form>
            </div>
        @endif

        <form id="vcBulk" method="POST" action="{{ route('crm.voice_calls.queue') }}" style="margin:0">
            {{ csrf_field() }}
            <div id="vcBulkIds"></div>
        </form>

        <div style="margin-bottom:.6rem;display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
            <label style="font-size:.78rem;color:#55617a;display:flex;align-items:center;gap:.35rem;cursor:pointer">
                <input type="checkbox" id="vcAll"> Sab select
            </label>
            <button class="vc-btn sm" type="button" onclick="vcQueueSelected()" {{ $configured ? '' : 'disabled' }}>
                <i class="fas fa-phone-volume"></i> Selected ko queue karo
            </button>
            <span class="vc-note" style="margin:0">Queue mein lagi calls aik aik kar ke jati hain — ikathi sab ko ring nahi hoti (trunk aik waqt mein aik call leta hai).</span>
        </div>

        <table class="vc-table">
            <thead><tr><th></th><th>Lead</th><th>Phone</th><th>Product</th><th>CRM Status</th><th>Last call</th><th></th></tr></thead>
            <tbody>
            @forelse($leads as $lead)
                @php $qr = $queueRows[$lead->id] ?? null; @endphp
                <tr>
                    <td><input type="checkbox" class="vc-ck" value="{{ $lead->id }}"></td>
                    <td class="vc-lead">
                        <a href="{{ route('crm.emails.show', $lead->id) }}">{{ $lead->client_name ?: 'Lead #'.$lead->id }}</a>
                        <div style="color:#8290a3;font-size:.72rem">{{ $lead->client_email }}</div>
                    </td>
                    <td style="white-space:nowrap">{{ $lead->client_phone }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($lead->product_name, 28) ?: '—' }}</td>
                    <td><span class="vc-pill other">{{ $lead->status ?: '—' }}</span></td>
                    <td>
                        @if($qr)
                            <span class="vc-pill {{ in_array($qr->status, ['done','failed','pending','calling','cancelled']) ? $qr->status : 'other' }}">{{ $qr->status }}</span>
                            @if($qr->conversation_id)
                                <a class="vc-tr-link" style="font-size:.72rem;display:block;margin-top:.15rem" href="{{ route('crm.voice_calls.transcript', $qr->conversation_id) }}">transcript</a>
                            @endif
                        @else
                            <span style="color:#aab4c3">—</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('crm.voice_calls.call_lead', $lead->id) }}" style="margin:0"
                              onsubmit="var b=this.querySelector('button');b.disabled=true;b.innerHTML='<i class=\'fas fa-circle-notch fa-spin\'></i>';">
                            {{ csrf_field() }}
                            <button class="vc-btn sm ghost" type="submit" {{ $configured ? '' : 'disabled' }} title="Abhi call karo"><i class="fas fa-phone"></i> Call</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="color:#8290a3;padding:1rem .6rem">Phone number wali koi lead nahi mili{{ $q ? ' is search par' : '' }}.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="vc-note">Sirf phone number wali newest 60 leads dikh rahi hain — purani ke liye search use karein. Follow-up call mein Sara ko lead ka naam/product pata hota hai aur wo quote ke baare mein baat karti hai.</div>
    </div>

    {{-- ===================== Recent calls ===================== --}}
    <div class="vc-card">
        <h3 style="margin:0 0 .7rem;font-size:1rem">Recent calls</h3>
        <table class="vc-table">
            <thead><tr><th>Waqt</th><th>Status</th><th>Duration</th><th>Lead</th><th></th></tr></thead>
            <tbody>
            @forelse($calls as $c)
                @php
                    $clead = $callLeads['elvoice-' . $c['conversation_id']] ?? null;
                    $st = $c['status'] ?? '?';
                    $cls = $st === 'done' ? 'done' : ($st === 'failed' ? 'failed' : 'other');
                @endphp
                <tr>
                    <td>{{ isset($c['start_time_unix_secs']) ? date('d M, h:i A', $c['start_time_unix_secs']) : '—' }}</td>
                    <td><span class="vc-pill {{ $cls }}">{{ $st }}</span></td>
                    <td>{{ isset($c['call_duration_secs']) ? gmdate('i:s', (int) $c['call_duration_secs']) : '—' }}</td>
                    <td class="vc-lead">
                        @if($clead)
                            <a href="{{ route('crm.emails.show', $clead->id) }}">{{ $clead->client_name }}</a>
                            <div style="color:#8290a3;font-size:.72rem">{{ $clead->client_email }}</div>
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

<script>
document.getElementById('vcAll').addEventListener('change', function () {
    document.querySelectorAll('.vc-ck').forEach(c => c.checked = this.checked);
});
function vcQueueSelected() {
    var ids = Array.from(document.querySelectorAll('.vc-ck:checked')).map(c => c.value);
    if (!ids.length) { alert('Pehle leads select karein.'); return; }
    if (!confirm(ids.length + ' leads ko follow-up call queue mein lagana hai? Sara aik aik kar ke call karegi aur har call credits se kategi.')) return;
    var holder = document.getElementById('vcBulkIds');
    holder.innerHTML = '';
    ids.forEach(function (id) {
        var i = document.createElement('input');
        i.type = 'hidden'; i.name = 'lead_ids[]'; i.value = id;
        holder.appendChild(i);
    });
    document.getElementById('vcBulk').submit();
}
</script>
@endsection
