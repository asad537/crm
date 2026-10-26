@extends('crm.layout')
@section('title', 'Inventory — '.$item->name)
@section('content')
@php
    $qty = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
    $cur = $item->currency ?: 'AED';
    $status = (float) $item->quantity <= 0 ? 'out' : ($item->is_low ? 'low' : 'in');
@endphp
<style>
.invm-hero{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.15rem 1.35rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px;flex-wrap:wrap}
.invm-hero h2{margin:0;font-size:1.25rem}.invm-hero p{margin:.2rem 0 0;color:#8290a3;font-size:.78rem}
.invm-back{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem .9rem;background:#eef2f7;color:#475569;border-radius:9px;text-decoration:none;font-weight:800;font-size:.78rem}
.invm-buy{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1rem;background:#0f766e;color:#fff;border-radius:10px;text-decoration:none;font-weight:800;font-size:.8rem}
.invm-buy:hover{background:#115e59;color:#fff}
.invm-status{display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .65rem;border-radius:999px;font-size:.7rem;font-weight:800;margin-left:.5rem;vertical-align:middle}
.invm-status.in{background:#dcfce7;color:#15803d}.invm-status.low{background:#fef3c7;color:#b45309}.invm-status.out{background:#fee2e2;color:#b91c1c}
.invm-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:.8rem;margin-bottom:1rem}
.invm-card{padding:.95rem 1.05rem;background:#fff;border:1px solid #e4eaf1;border-radius:15px}
.invm-card .k{font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#64748b}
.invm-card .v{font-size:1.35rem;font-weight:900;color:#1e293b;margin-top:.2rem}.invm-card .v small{font-size:.75rem;font-weight:800;color:#64748b}
.invm-card .s{font-size:.7rem;color:#8290a3;margin-top:.15rem}
.invm-card.teal .v{color:#0f766e}.invm-card.red .v{color:#be123c}.invm-card.blue .v{color:#2563eb}
.invm-box{background:#fff;border:1px solid #e4eaf1;border-radius:15px;margin-bottom:1rem;overflow:hidden}
.invm-box-head{display:flex;align-items:center;gap:.5rem;padding:.8rem 1.05rem;border-bottom:1px solid #edf1f5;font-weight:800;font-size:.85rem;color:#1e293b}
.invm-box-head span{margin-left:auto;font-weight:600;font-size:.72rem;color:#94a3b8}
.invm-details{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.8rem 1.2rem;padding:1rem 1.05rem}
.invm-details .k{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8}
.invm-details .v{font-size:.88rem;font-weight:700;color:#1e293b;margin-top:.15rem}
.invm-two{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.invm-mini{width:100%;border-collapse:collapse}
.invm-mini th{padding:.6rem 1.05rem;background:#f7f9fc;text-align:left;color:#718096;font-size:.62rem;text-transform:uppercase}
.invm-mini td{padding:.6rem 1.05rem;border-top:1px solid #edf1f5;font-size:.8rem}
.invm-mini .r{text-align:right}.invm-mini a{color:var(--primary-purple);font-weight:800;text-decoration:none}
.invm-empty{padding:1.2rem 1.05rem;color:#94a3b8;font-size:.78rem}
.invm-wrap{overflow:auto}
.invm-table{width:100%;min-width:760px;border-collapse:collapse}
.invm-table th{padding:.8rem 1rem;background:#f7f9fc;border-bottom:2px solid var(--primary-soft);text-align:left;color:#718096;font-size:.65rem;text-transform:uppercase}
.invm-table td{padding:.72rem 1rem;border-bottom:1px solid #edf1f5;font-size:.82rem}
.invm-tag{display:inline-block;padding:.22rem .55rem;border-radius:999px;font-size:.66rem;font-weight:800}
.invm-tag.in{background:#e1f8ef;color:#047857}.invm-tag.out{background:#eef2ff;color:var(--primary-purple)}.invm-tag.adjust{background:#fef3c7;color:#b45309}
.invm-books{font-size:.72rem;font-weight:700;color:#0f766e}.invm-books.off{color:#94a3b8;font-weight:600}
.invm-search{display:flex;gap:.5rem;align-items:center;padding:.75rem 1.05rem;border-bottom:1px solid #edf1f5;flex-wrap:wrap}
.invm-search .field{flex:1;min-width:220px;position:relative}
.invm-search .field i{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem}
.invm-search input{width:100%;padding:.6rem .8rem .6rem 2.1rem;border:1px solid #dfe5ec;border-radius:9px;font:inherit;font-size:.82rem;color:#1e293b;background:#fbfcfe}
.invm-search input:focus{outline:none;border-color:var(--primary-purple);background:#fff}
.invm-search button{padding:.6rem 1rem;border:0;border-radius:9px;background:var(--primary-purple);color:#fff;font:inherit;font-weight:800;font-size:.8rem;cursor:pointer}
.invm-search a{font-size:.78rem;font-weight:800;color:#64748b;text-decoration:none}
.invm-search .count{font-size:.74rem;color:#8290a3}
.invm-by{display:flex;align-items:center;gap:.5rem;justify-content:space-between}
.invm-revert{display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .55rem;border:1px solid #fecaca;border-radius:8px;background:#fff5f5;color:#be123c;font:inherit;font-size:.68rem;font-weight:800;cursor:pointer;white-space:nowrap}
.invm-revert:hover{background:#fee2e2}
.invm-revert.locked{border-color:#e4eaf1;background:#f7f9fc;color:#94a3b8;cursor:help}
.invm-flag{display:inline-block;padding:.18rem .5rem;border-radius:999px;font-size:.64rem;font-weight:800;white-space:nowrap}
.invm-flag.reverted{background:#f1f5f9;color:#64748b}.invm-flag.reversal{background:#fff1f2;color:#be123c}
tr.invm-undone td{color:#94a3b8}tr.invm-undone td:nth-child(3){text-decoration:line-through}
@media(max-width:1000px){.invm-cards{grid-template-columns:1fr 1fr}.invm-two{grid-template-columns:1fr}}
@media(max-width:560px){.invm-cards{grid-template-columns:1fr}}
</style>
<div class="inv-page">
    <div class="invm-hero">
        <div>
            <a class="invm-back" href="{{ route('crm.inventory.index') }}"><i class="fas fa-arrow-left"></i> Inventory</a>
            <h2 style="margin-top:.6rem">{{ $item->name }}<span class="invm-status {{ $status }}"><i class="fas fa-circle" style="font-size:.45rem"></i> {{ $status === 'in' ? 'In Stock' : ($status === 'low' ? 'Low Stock' : 'Out of Stock') }}</span></h2>
            <p>{{ collect([$item->category, $item->paper_size, $item->gsm ? (is_numeric($item->gsm) ? $item->gsm.'gsm' : $item->gsm) : null, $item->stock_type])->filter()->implode(' · ') ?: 'No details yet' }}</p>
        </div>
        <a class="invm-buy" href="{{ route('crm.purchase_invoices.create', ['stock' => $item->id]) }}"><i class="fas fa-cart-plus"></i> Buy more (Purchase Invoice)</a>
    </div>

    <div class="invm-cards">
        <div class="invm-card blue"><div class="k">In Stock</div><div class="v">{{ $qty($item->quantity) }} <small>{{ $item->unit }}</small></div><div class="s">{{ $item->reorder_level > 0 ? 'Reorder at ' . $qty($item->reorder_level) . ' ' . $item->unit : 'No reorder level set' }}</div></div>
        <div class="invm-card"><div class="k">Average Cost</div><div class="v"><small>{{ $cur }}</small> {{ $item->unit_cost !== null ? number_format((float) $item->unit_cost, 2) : '—' }}</div><div class="s">per {{ \Illuminate\Support\Str::singular($item->unit) }} · average of what was paid</div></div>
        <div class="invm-card teal"><div class="k">Value in the Books</div><div class="v"><small>{{ $cur }}</small> {{ number_format($value['value'] ?? 0, 2) }}</div><div class="s">Balance Sheet → Inventory (since {{ $stockFrom->format('d M Y') }})</div></div>
        <div class="invm-card red"><div class="k">Used on Jobs</div><div class="v">{{ $qty($totals['used']) }} <small>{{ $item->unit }}</small></div><div class="s">In {{ $qty($totals['in']) }} · wastage / adjusted {{ $qty($totals['loss']) }}</div></div>
    </div>

    <div class="invm-box">
        <div class="invm-box-head"><i class="fas fa-info-circle" style="color:var(--primary-purple)"></i> Item Details</div>
        <div class="invm-details">
            <div><div class="k">Item Name</div><div class="v">{{ $item->name }}</div></div>
            <div><div class="k">Category</div><div class="v">{{ $item->category ?: '—' }}</div></div>
            <div><div class="k">Size</div><div class="v">{{ $item->paper_size ?: '—' }}</div></div>
            <div><div class="k">GSM</div><div class="v">{{ $item->gsm ?: '—' }}</div></div>
            <div><div class="k">Stock Type</div><div class="v">{{ $item->stock_type ?: '—' }}</div></div>
            <div><div class="k">Unit</div><div class="v">{{ $item->unit }}</div></div>
            <div><div class="k">Reorder Level</div><div class="v">{{ $item->reorder_level > 0 ? $qty($item->reorder_level) . ' ' . $item->unit : '—' }}</div></div>
            <div><div class="k">Added On</div><div class="v">{{ optional($item->created_at)->format('d M Y') ?: '—' }}</div></div>
            @if($item->notes)<div style="grid-column:1/-1"><div class="k">Notes</div><div class="v" style="font-weight:500;white-space:pre-line">{{ $item->notes }}</div></div>@endif
        </div>
    </div>

    <div class="invm-two">
        <div class="invm-box">
            <div class="invm-box-head"><i class="fas fa-file-invoice" style="color:#0f766e"></i> Bought on Purchase Invoices<span>{{ $bills->count() }} bill(s)</span></div>
            @if($bills->isEmpty())
                <div class="invm-empty">No purchase invoice has this item yet. Use <b>Buy more</b> to add one.</div>
            @else
                <table class="invm-mini">
                    <thead><tr><th>Invoice</th><th>Date</th><th>Vendor</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Total</th></tr></thead>
                    <tbody>
                    @foreach($bills as $line)
                        <tr>
                            <td><a href="{{ route('crm.purchase_invoices.show', $line->purchase_invoice_id) }}">{{ $line->invoice->number }}</a></td>
                            <td style="white-space:nowrap">{{ $line->invoice->issue_date->format('d M Y') }}</td>
                            <td>{{ $line->invoice->vendor_name }}</td>
                            <td class="r">{{ $qty($line->quantity) }}</td>
                            <td class="r">{{ number_format((float) $line->unit_price, 2) }}</td>
                            <td class="r" style="font-weight:800">{{ number_format((float) $line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="invm-box">
            <div class="invm-box-head"><i class="fas fa-briefcase" style="color:var(--primary-purple)"></i> Used on Jobs<span>{{ $jobs->count() }} job(s)</span></div>
            @if($jobs->isEmpty())
                <div class="invm-empty">Not used on any job yet.</div>
            @else
                <table class="invm-mini">
                    <thead><tr><th>Job</th><th>Last Used</th><th class="r">Qty</th><th class="r">Cost</th></tr></thead>
                    <tbody>
                    @foreach($jobs as $job => $j)
                        <tr>
                            <td style="font-weight:800">{{ $job }}</td>
                            <td style="white-space:nowrap">{{ $j['date']->format('d M Y') }}</td>
                            <td class="r">{{ $qty($j['qty']) }} {{ $item->unit }}</td>
                            <td class="r" style="font-weight:800">{{ number_format($j['cost'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="invm-box">
        <div class="invm-box-head"><i class="fas fa-history" style="color:#475569"></i> Stock History<span>every stock in, use and adjustment</span></div>
        <form class="invm-search" method="GET" action="{{ route('crm.inventory.movements', $item->id) }}">
            <div class="field"><i class="fas fa-search"></i><input type="search" name="q" value="{{ $search }}" placeholder="Search reference, job, bill (PI-0015), type, user, note or date (23 Oct)…" aria-label="Search stock history"></div>
            <button type="submit">Search</button>
            @if($search !== '')
                <a href="{{ route('crm.inventory.movements', $item->id) }}"><i class="fas fa-times"></i> Clear</a>
                <span class="count">{{ $movements->total() }} {{ \Illuminate\Support\Str::plural('entry', $movements->total()) }} found</span>
            @endif
        </form>
        <div class="invm-wrap">
            <table class="invm-table">
                <thead><tr><th>Date</th><th>Type</th><th>Qty</th><th>Rate</th><th>Balance</th><th>Job</th><th>Reference</th><th>In the Books</th><th>By</th></tr></thead>
                <tbody>
                @forelse($movements as $m)
                    <tr class="{{ $reverted->has($m->id) ? 'invm-undone' : '' }}">
                        <td style="white-space:nowrap;color:#64748b">{{ $m->created_at->format('d M Y, h:i A') }}</td>
                        <td><span class="invm-tag {{ $m->type }}">{{ $m->type === 'in' ? 'Stock In' : ($m->type === 'out' ? 'Used' : 'Adjust') }}</span></td>
                        @php($__d = $deltas[$m->id] ?? ($m->type === 'out' ? -$m->quantity : $m->quantity))
                        <td style="font-weight:800;color:{{ $__d < 0 ? '#be123c' : '#047857' }}">{{ $__d < 0 ? '−' : '+' }}{{ $qty(abs($__d)) }}</td>
                        <td>{{ $m->unit_cost !== null ? number_format($m->unit_cost,2) : '—' }}</td>
                        <td>{{ $qty($m->balance_after) }}</td>
                        <td>{{ $m->job_number ?: '—' }}</td>
                        <td>{{ $m->reference ?: '—' }}@if($m->note)<div style="color:#94a3b8;font-size:.68rem">{{ $m->note }}</div>@endif</td>
                        <td><span class="invm-books {{ \App\Services\InventoryAccounting::isBooked($m) ? '' : 'off' }}">{{ $books[$m->id] ?? '' }}</span></td>
                        <td style="color:#64748b">
                            <div class="invm-by">
                                <span>{{ optional($m->creator)->name ?: '—' }}</span>
                                @if($m->reversal_of_id)
                                    <span class="invm-flag reversal" title="This entry undoes an earlier one">Reversal</span>
                                @elseif($reverted->has($m->id))
                                    <span class="invm-flag reverted" title="Undone by a reversal entry">Reverted</span>
                                @elseif($canRevert && !$revertProblems[$m->id])
                                    <form method="POST" action="{{ route('crm.inventory.revert', [$item->id, $m->id]) }}" onsubmit="return confirm('Revert this entry? A reversing entry puts the stock back ({{ ($deltas[$m->id] ?? 0) < 0 ? '+' : '−' }}{{ $qty(abs($deltas[$m->id] ?? 0)) }} {{ $item->unit }}) and undoes it in the books. Nothing is deleted.')">
                                        @csrf
                                        <button type="submit" class="invm-revert" title="Undo this entry"><i class="fas fa-undo"></i> Revert</button>
                                    </form>
                                @elseif($canRevert && $revertProblems[$m->id])
                                    <span class="invm-revert locked" title="{{ $revertProblems[$m->id] }}"><i class="fas fa-lock"></i></span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center;padding:2rem;color:#94a3b8">{{ $search !== '' ? 'No entries match "' . $search . '".' : 'No movements yet.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($movements->hasPages())<div style="margin-top:1rem">{{ $movements->links() }}</div>@endif
</div>
@endsection
