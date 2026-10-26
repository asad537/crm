@php $showType = $meta['tab'] === 'all'; $cols = $showType ? 8 : 7; @endphp
<table border="1">
    <tr><td colspan="{{ $cols }}" style="font-weight:bold;font-size:14px">{{ $meta['workspaceName'] }} — General Ledger ({{ $meta['tabLabel'] }}) — {{ $meta['partyLabel'] }} Summary</td></tr>
    <tr><td colspan="{{ $cols }}">{{ $meta['rangeLabel'] }} · Generated {{ $meta['generatedAt'] }} · {{ $rows->count() }} {{ strtolower($meta['partyLabel']) }}(s)</td></tr>
    <tr style="font-weight:bold;background:#eef2f7">
        <td>{{ $meta['partyLabel'] }}</td>@if($showType)<td>Type</td>@endif<td>Entries</td><td>Last Entry</td>
        <td>Total</td><td>{{ $meta['paidLabel'] }}</td><td>Balance</td><td>Currency</td>
    </tr>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row->party }}</td>
            @if($showType)<td>{{ ucfirst($row->type) }}</td>@endif
            <td>{{ $row->count }}</td>
            <td>{{ $row->last_date->format('d/m/Y') }}</td>
            <td>{{ number_format($row->total, 2, '.', '') }}</td>
            <td>{{ number_format($row->paid, 2, '.', '') }}</td>
            <td>{{ number_format($row->balance, 2, '.', '') }}</td>
            <td>{{ $row->currency }}</td>
        </tr>
    @endforeach
    <tr style="font-weight:bold;background:#eef2f7">
        <td>Grand Total</td>@if($showType)<td></td>@endif<td>{{ $rows->sum('count') }}</td><td></td>
        <td>{{ number_format($meta['totals']['amount'], 2, '.', '') }}</td>
        <td>{{ number_format($meta['totals']['paid'], 2, '.', '') }}</td>
        <td>{{ number_format($meta['totals']['balance'], 2, '.', '') }}</td>
        <td></td>
    </tr>
</table>
