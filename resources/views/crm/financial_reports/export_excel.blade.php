@php
    $width = max(array_map(fn ($t) => count($t['columns']), $report['tables'] ?: [['columns' => [1, 2, 3]]]));
    $plain = function ($value, $type) {
        if ($value === null || $value === '') return '';
        if (is_array($value)) return $value['text'];
        if ($type === 'money') return number_format((float) $value, 2, '.', '');
        if ($type === 'percent' || $type === 'share') return number_format((float) $value, 1, '.', '') . '%';
        return $value;
    };
@endphp
<table border="1">
    <tr><td colspan="{{ $width }}" style="font-weight:bold;font-size:14px">{{ $report['title'] }}</td></tr>
    <tr><td colspan="{{ $width }}">{{ $meta['rangeLabel'] }} · Generated {{ $meta['generatedAt'] }}</td></tr>
    <tr><td colspan="{{ $width }}">{{ $report['subtitle'] }}</td></tr>
    <tr><td colspan="{{ $width }}"></td></tr>
    @foreach($report['cards'] as $card)
        <tr><td style="font-weight:bold">{{ $card['label'] }}</td><td>{{ ($card['format'] ?? 'money') === 'money' ? number_format($card['value'], 2, '.', '') : $card['value'] }}</td>@if(!empty($card['note']))<td>{{ $card['note'] }}</td>@endif</tr>
    @endforeach
    @foreach($report['tables'] as $table)
        <tr><td colspan="{{ $width }}"></td></tr>
        <tr><td colspan="{{ $width }}" style="font-weight:bold;font-size:12px;background:#06265e;color:#ffffff">{{ $table['title'] }}</td></tr>
        <tr style="font-weight:bold;background:#eef2f7">@foreach($table['columns'] as [$label, $type])<td>{{ $label }}</td>@endforeach</tr>
        @foreach($table['rows'] as $row)
            @php $style = $row['_style'] ?? null; $cells = $style ? $row['cells'] : $row; @endphp
            <tr style="{{ $style ? 'font-weight:bold;' . ($style === 'section' ? 'background:#f4f6fb' : ($style === 'grand' ? 'background:#dbe4f5' : '')) : '' }}">
                @foreach($table['columns'] as $i => [$label, $type])<td>{{ $style === 'section' && $i > 0 ? '' : $plain($cells[$i] ?? null, $type) }}</td>@endforeach
            </tr>
        @endforeach
        @if(!empty($table['footer']))
            <tr style="font-weight:bold;background:#eef2f7">@foreach($table['columns'] as $i => [$label, $type])<td>{{ $plain($table['footer'][$i] ?? null, $type) }}</td>@endforeach</tr>
        @endif
    @endforeach
    @if(!empty($report['notes']))
        <tr><td colspan="{{ $width }}"></td></tr>
        @foreach($report['notes'] as $note)<tr><td colspan="{{ $width }}">Note: {{ $note }}</td></tr>@endforeach
    @endif
</table>
