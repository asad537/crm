<!doctype html><html><head><meta charset="utf-8"><title>{{ $meta['workspaceName'] }} — General Ledger</title><style>
@page { margin: 24px 20px; }
body { font-family: DejaVu Sans, Arial, sans-serif; color: #172033; font-size: 8px; }
header { border-bottom: 3px solid {{ $meta['isAlMassa'] ? '#f45a24' : '#6c5ce7' }}; padding-bottom: 10px; margin-bottom: 12px; }
h1 { font-size: 15px; margin: 0 0 4px; color: {{ $meta['isAlMassa'] ? '#f45a24' : '#6c5ce7' }}; }
.meta { color: #64748b; margin-bottom: 10px; }
.totals { margin: 0 0 10px; }
.totals td { padding: 5px 9px; border: 1px solid #cbd5e1; font-weight: bold; }
table.rows { width: 100%; border-collapse: collapse; }
.rows th, .rows td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; }
.rows th { background: {{ $meta['isAlMassa'] ? '#f45a24' : '#6c5ce7' }}; color: #fff; font-size: 7px; text-transform: uppercase; }
.rows tr:nth-child(even) td { background: #f4f6fb; }
.num { text-align: right; }
.green { color: #15803d; } .red { color: #b91c1c; }
</style></head><body>
<header>
    <h1>{{ $meta['workspaceName'] }} — General Ledger</h1>
    <div>{{ $meta['tabLabel'] }}</div>
</header>
<div class="meta">{{ $meta['rangeLabel'] }} &nbsp;·&nbsp; Generated {{ $meta['generatedAt'] }} &nbsp;·&nbsp; {{ $entries->count() }} entr{{ $entries->count() === 1 ? 'y' : 'ies' }}</div>
<table class="totals"><tr>
    <td>Total: {{ number_format($meta['totals']['amount'], 2) }}</td>
    <td class="green">Paid / Received: {{ number_format($meta['totals']['paid'], 2) }}</td>
    <td class="red">Balance: {{ number_format($meta['totals']['balance'], 2) }}</td>
</tr></table>
<table class="rows"><thead><tr>
    <th>Date</th><th>Type</th><th>Party</th><th>Ref #</th><th>Detail</th><th>Term</th>
    <th class="num">Total</th><th class="num">Paid</th><th class="num">Balance</th><th>Currency</th><th>Status</th><th>Settled On</th>
</tr></thead><tbody>
@foreach($entries as $entry)
    <tr>
        <td>{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</td>
        <td>{{ ucfirst($entry->type) }}</td>
        <td>{{ $entry->party }}</td>
        <td>{{ $entry->ref }}</td>
        <td>{{ $entry->detail }}</td>
        <td>{{ $entry->term ?: '-' }}</td>
        <td class="num">{{ number_format($entry->total, 2) }}</td>
        <td class="num green">{{ number_format($entry->paid, 2) }}</td>
        <td class="num {{ $entry->balance > 0 ? 'red' : '' }}">{{ number_format($entry->balance, 2) }}</td>
        <td>{{ $entry->currency }}</td>
        <td>{{ $entry->status_label }}</td>
        <td>{{ $entry->settled_at ? \Carbon\Carbon::parse($entry->settled_at)->format('d/m/Y') : '-' }}</td>
    </tr>
@endforeach
</tbody></table>
</body></html>
