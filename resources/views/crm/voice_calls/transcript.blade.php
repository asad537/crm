@extends('crm.layout')
@section('title', 'Call Transcript')
@section('content')
<style>
.vt-page{color:#233047;max-width:860px}
.vt-head{background:#fff;border:1px solid #e4eaf1;border-radius:15px;padding:1rem 1.25rem;margin-bottom:1rem;font-size:.85rem;display:flex;gap:1.4rem;flex-wrap:wrap}
.vt-head b{display:block;font-size:.72rem;color:#8290a3;text-transform:uppercase;letter-spacing:.04em;font-weight:600}
.vt-chat{background:#fff;border:1px solid #e4eaf1;border-radius:15px;padding:1.2rem 1.4rem}
.vt-msg{margin-bottom:.9rem;max-width:78%}
.vt-msg .who{font-size:.68rem;font-weight:700;color:#8290a3;margin-bottom:.15rem;text-transform:uppercase;letter-spacing:.05em}
.vt-msg .bubble{padding:.55rem .85rem;border-radius:13px;font-size:.88rem;line-height:1.45}
.vt-msg.agent .bubble{background:#eef2ff;color:#312e81;border-bottom-left-radius:4px}
.vt-msg.user{margin-left:auto;text-align:right}
.vt-msg.user .bubble{background:#f0fdf4;color:#14532d;border-bottom-right-radius:4px;text-align:left}
.vt-back{display:inline-block;margin-bottom:.9rem;color:#4f46e5;text-decoration:none;font-size:.85rem;font-weight:600}
</style>
@php
    $meta = $conv['metadata'] ?? [];
    $pc = $meta['phone_call'] ?? [];
    $turns = collect($conv['transcript'] ?? [])->filter(fn ($t) => trim($t['message'] ?? '') !== '');
@endphp
<div class="vt-page">
    <a class="vt-back" href="{{ route('crm.voice_calls.index') }}"><i class="fas fa-arrow-left"></i> Voice Calls</a>

    <div class="vt-head">
        <div><b>Status</b>{{ $conv['status'] ?? '—' }}</div>
        <div><b>Duration</b>{{ isset($meta['call_duration_secs']) ? gmdate('i:s', (int) $meta['call_duration_secs']) : '—' }}</div>
        <div><b>To</b>{{ $pc['external_number'] ?? '—' }}</div>
        <div><b>From</b>{{ $pc['agent_number'] ?? '—' }}</div>
        <div><b>Ended</b>{{ $meta['termination_reason'] ?? '—' }}</div>
        @if($lead)
            <div><b>Lead</b><a href="{{ route('crm.emails.show', $lead->id) }}" style="color:#4f46e5">{{ $lead->client_name }} ({{ $lead->client_email }})</a></div>
        @endif
    </div>

    <div class="vt-chat">
        @if(isset($conv['_error']))
            <div style="color:#991b1b">ElevenLabs se transcript nahi mila: {{ $conv['_error'] }}</div>
        @elseif($turns->isEmpty())
            <div style="color:#8290a3">Is call mein koi baat record nahi hui (shayad uthayi nahi gayi).</div>
        @else
            @foreach($turns as $t)
                @php $isUser = ($t['role'] ?? '') === 'user'; @endphp
                <div class="vt-msg {{ $isUser ? 'user' : 'agent' }}">
                    <div class="who">{{ $isUser ? 'Customer' : 'Sara' }}</div>
                    <div class="bubble">{{ trim($t['message']) }}</div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
