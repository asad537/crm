<table border="1">
    <tr><td colspan="12" style="font-weight:bold;font-size:14px">{{ $meta['workspaceName'] }} — General Ledger ({{ $meta['tabLabel'] }})</td></tr>
    <tr><td colspan="12">{{ $meta['rangeLabel'] }} · Generated {{ $meta['generatedAt'] }} · {{ $entries->count() }} entries</td></tr>
    <tr>
        <td colspan="4" style="font-weight:bold">Total: {{ number_format($meta['totals']['amount'], 2) }}</td>
        <td colspan="4" style="font-weight:bold;color:#15803d">Paid / Received: {{ number_format($meta['totals']['paid'], 2) }}</td>
        <td colspan="4" style="font-weight:bold;color:#b91c1c">Balance: {{ number_format($meta['totals']['balance'], 2) }}</td>
    </tr>
    <tr style="font-weight:bold;background:#eef2f7">
        <td>Date</td><td>Type</td><td>Party</td><td>Ref #</td><td>Detail</td><td>Term</td>
        <td>Total</td><td>Paid</td><td>Balance</td><td>Currency</td><td>Status</td><td>Settled On</td>
    </tr>
    @foreach($entries as $entry)
        <tr>
            <td>{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</td>
            <td>{{ ucfirst($entry->type) }}</td>
            <td>{{ $entry->party }}</td>
            <td>{{ $entry->ref }}</td>
            <td>{{ $entry->detail }}</td>
            <td>{{ $entry->term ?: '-' }}</td>
            <td>{{ number_format($entry->total, 2, '.', '') }}</td>
            <td>{{ number_format($entry->paid, 2, '.', '') }}</td>
            <td>{{ number_format($entry->balance, 2, '.', '') }}</td>
            <td>{{ $entry->currency }}</td>
            <td>{{ $entry->status_label }}</td>
            <td>{{ $entry->settled_at ? \Carbon\Carbon::parse($entry->settled_at)->format('d/m/Y') : '-' }}</td>
        </tr>
    @endforeach
</table>
