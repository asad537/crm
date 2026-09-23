@php
    $__brandIsAlMassa = isset($activeCrmWorkspace) && $activeCrmWorkspace && $activeCrmWorkspace->slug === 'mybox-packaging-app';
    $__brandName = $__brandIsAlMassa ? 'Al Massa Packaging' : 'My Box Printing';
    $__brandColor = $__brandIsAlMassa ? '#f45a24' : '#6c5ce7';
@endphp
<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>body{font-family:Arial}h1{color:{{ $__brandColor }};font-size:22px;text-align:center;margin:0}h2{text-align:center;color:#475569;font-size:12px;margin:4px 0}table{border-collapse:collapse;font-size:11px;margin:auto}th{background:{{ $__brandColor }};color:#fff;font-weight:bold;padding:9px;border:1px solid {{ $__brandColor }}}td{padding:7px;border:1px solid #cbd5e1}tr:nth-child(even) td{background:#f8f5ff}.money{mso-number-format:"#,##0.00";text-align:right}.credit{color:#059669}.tot td{font-weight:bold;background:#eef2ff}</style></head><body>
<h1>{{ $__brandName }}</h1>
<h2>VENDOR LEDGER · {{ optional($vendor)->name }} · {{ now()->format('d M Y, h:i A') }}</h2>
<table>
<tr><th>Date</th><th>Vendor</th><th>Invoice</th><th>Job No</th><th>Demand</th><th>Item</th><th>Qty</th><th>Debit</th><th>Credit</th><th>Balance</th></tr>
@foreach($ledger as $e)
@php($p = $e->purchase ?? null)
<tr>
    <td>{{ $e->date ? $e->date->format('d/m/Y') : '' }}</td>
    <td>{{ $p ? $p->vendor_name : '' }}</td>
    <td>{{ $p ? $p->invoice_number : '' }}</td>
    <td>{{ $p ? $p->job_id : '' }}</td>
    <td>{{ $p ? $p->demand_no : '' }}</td>
    <td>{{ $p ? ($p->items->pluck('item_name')->filter()->implode(', ') ?: $p->item_name) : $e->desc }}</td>
    <td>{{ $p ? rtrim(rtrim(number_format($p->quantity,2,'.',''),'0'),'.') : '' }}</td>
    <td class="money">{{ $e->debit > 0.009 ? number_format($e->debit,2) : '' }}</td>
    <td class="money credit">{{ $e->credit > 0.009 ? number_format($e->credit,2) : '' }}</td>
    <td class="money">{{ number_format($e->balance,2) }}</td>
</tr>
@endforeach
<tr class="tot"><td colspan="7">TOTAL</td><td class="money">{{ number_format($totalDebit,2) }}</td><td class="money">{{ number_format($totalCredit,2) }}</td><td class="money">{{ number_format($ledgerBalance,2) }}</td></tr>
</table>
</body></html>
