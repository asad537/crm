@php
    $startVal = old($prefix . '_start', substr((string) ($card->{$prefix . '_start'} ?? ''), 0, 5));
    $endVal = old($prefix . '_end', substr((string) ($card->{$prefix . '_end'} ?? ''), 0, 5));
    $mins = $card->{$prefix . '_total_minutes'} ?? null;
    $hrVal = $mins !== null ? intdiv($mins, 60) : '';
    $minVal = $mins !== null ? $mins % 60 : '';
@endphp
<div class="jc-timestrip">
    <div><label>Start time</label><input class="jc-control" type="time" name="{{ $prefix }}_start" value="{{ $startVal }}"></div>
    <div><label>End time</label><input class="jc-control" type="time" name="{{ $prefix }}_end" value="{{ $endVal }}"></div>
    <div><label>Total (hr)</label><input class="jc-control" id="jc_{{ $prefix }}_hr" value="{{ $hrVal }}" readonly></div>
    <div><label>Total (min)</label><input class="jc-control" id="jc_{{ $prefix }}_min" value="{{ $minVal }}" readonly></div>
</div>
