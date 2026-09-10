@extends('crm.layout')
@section('title', 'Inventory')
@section('content')
<style>
.inv-page{color:#233047}
.inv-hero{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.15rem 1.35rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px}
.inv-hero h2{margin:0;font-size:1.3rem}.inv-hero p{margin:.2rem 0 0;color:#8290a3;font-size:.78rem}
.inv-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:.8rem;margin-bottom:1rem}
.inv-card{background:#fff;border:1px solid #e5ebf2;border-radius:14px;padding:1rem 1.1rem;box-shadow:0 8px 24px rgba(15,23,42,.05)}
.inv-card .k{color:#8a99ae;font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em}
.inv-card .v{font-size:1.4rem;font-weight:850;margin-top:.3rem;color:#1e293b}
.inv-card.low .v{color:#d97706}
.inv-toolbar{display:flex;gap:.6rem;align-items:center;margin-bottom:.85rem;flex-wrap:wrap}
.inv-search{flex:1;min-width:220px;display:flex;align-items:center;gap:.5rem;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:.55rem .8rem}
.inv-search input{border:0;outline:0;width:100%;font-size:.85rem}
.inv-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1rem;border:0;border-radius:10px;font-weight:800;font-size:.8rem;cursor:pointer;text-decoration:none}
.inv-btn-main{background:var(--primary-purple);color:#fff;box-shadow:0 6px 16px var(--primary-shadow)}
.inv-wrap{overflow:auto;background:#fff;border:1px solid #e4eaf1;border-radius:15px;box-shadow:0 8px 26px rgba(15,23,42,.055)}
.inv-table{width:100%;min-width:860px;border-collapse:collapse}
.inv-table th{padding:.8rem 1rem;background:#f7f9fc;border-bottom:2px solid var(--primary-soft);text-align:left;color:#718096;font-size:.65rem;text-transform:uppercase;letter-spacing:.04em}
.inv-table td{padding:.75rem 1rem;border-bottom:1px solid #edf1f5;font-size:.82rem;vertical-align:middle}
.inv-table tbody tr:hover{background:var(--primary-soft)}
.inv-qty{font-weight:850;font-size:.95rem}
.inv-qty.low{color:#d97706}
.inv-badge{display:inline-block;padding:.2rem .5rem;border-radius:999px;background:#eef2f7;color:#526176;font-size:.66rem;font-weight:800}
.inv-badge.lowb{background:#fff3d6;color:#b45309}
.inv-act{display:inline-flex;gap:.3rem}
.inv-mini{padding:.4rem .6rem;border:0;border-radius:8px;font-weight:800;font-size:.68rem;cursor:pointer;text-decoration:none}
.inv-mini.in{background:#e1f8ef;color:#047857}.inv-mini.out{background:#eef2ff;color:var(--primary-purple)}.inv-mini.hist{background:#f1f5f9;color:#475569}.inv-mini.del{background:#fff1f2;color:#be123c}
.inv-modal-bg{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.55)}
.inv-modal{width:100%;max-width:520px;background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(15,23,42,.25)}
.inv-modal-head{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.15rem;border-bottom:1px solid #edf1f5}
.inv-modal-head h3{margin:0;font-size:1.05rem}
.inv-modal-body{padding:1.1rem;display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
.inv-modal-body .full{grid-column:1/-1}
.inv-modal label{display:block;margin-bottom:.3rem;color:#536277;font-size:.7rem;font-weight:800}
.inv-modal .inv-control{width:100%;padding:.62rem .7rem;border:1.5px solid #dae3ed;border-radius:9px;box-sizing:border-box;outline:0;font-size:.85rem}
.inv-modal .inv-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.inv-modal-actions{display:flex;justify-content:flex-end;gap:.55rem;padding:.9rem 1.15rem;border-top:1px solid #edf1f5}
.inv-close{width:32px;height:32px;border:0;border-radius:8px;background:#eef2f7;cursor:pointer}
@media(max-width:800px){.inv-cards{grid-template-columns:1fr 1fr}}
</style>
<div class="inv-page">
    <div class="inv-hero">
        <div><h2>Inventory</h2><p>Stock items, purchases in, and job-wise consumption</p></div>
        <button class="inv-btn inv-btn-main" onclick="invOpen('invAddModal')"><i class="fas fa-plus"></i> Add Item</button>
    </div>

    @if(session('success'))<div style="background:#e1f8ef;border:1px solid #a7f3d0;color:#065f46;padding:.7rem 1rem;border-radius:10px;margin-bottom:.8rem">{{ session('success') }}</div>@endif
    @if(session('error'))<div style="background:#fff1f2;border:1px solid #fecaca;color:#9f1239;padding:.7rem 1rem;border-radius:10px;margin-bottom:.8rem">{{ session('error') }}</div>@endif

    <div class="inv-cards">
        <div class="inv-card"><div class="k">Items</div><div class="v">{{ number_format($summary['items']) }}</div></div>
        <div class="inv-card"><div class="k">Total In Stock</div><div class="v">{{ rtrim(rtrim(number_format($summary['in_stock'],3,'.',''),'0'),'.') }}</div></div>
        <div class="inv-card low"><div class="k">Low Stock</div><div class="v">{{ number_format($summary['low']) }}</div></div>
        <div class="inv-card"><div class="k">Stock Value</div><div class="v">{{ number_format($summary['value'],2) }}</div></div>
    </div>

    <form class="inv-toolbar" method="GET">
        <div class="inv-search"><i class="fas fa-search" style="color:#94a3b8"></i><input name="search" value="{{ request('search') }}" placeholder="Search item, size, GSM, stock..."></div>
        <select class="inv-control" name="category" style="max-width:180px;padding:.55rem .7rem;border:1px solid #e2e8f0;border-radius:10px" onchange="this.form.submit()">
            <option value="">All categories</option>
            @foreach(['Paper','Board','Ink','Finishing Material','Other'] as $c)<option value="{{ $c }}" {{ request('category')===$c?'selected':'' }}>{{ $c }}</option>@endforeach
        </select>
    </form>

    <div class="inv-wrap">
        <table class="inv-table">
            <thead><tr>
                <th>Item</th><th>Size</th><th>GSM</th><th>Stock Type</th><th>In Stock</th><th>Reorder</th><th>Unit Cost</th><th>Actions</th>
            </tr></thead>
            <tbody>
            @forelse($items as $it)
                <tr>
                    <td><strong>{{ $it->name }}</strong>@if($it->category)<div style="color:#94a3b8;font-size:.68rem">{{ $it->category }}</div>@endif</td>
                    <td>{{ $it->paper_size ?: '—' }}</td>
                    <td>{{ $it->gsm ?: '—' }}</td>
                    <td>{{ $it->stock_type ?: '—' }}</td>
                    <td><span class="inv-qty {{ $it->is_low ? 'low' : '' }}">{{ rtrim(rtrim(number_format($it->quantity,3,'.',''),'0'),'.') }}</span> <span style="color:#94a3b8;font-size:.7rem">{{ $it->unit }}</span> @if($it->is_low)<span class="inv-badge lowb">Low</span>@endif</td>
                    <td>{{ $it->reorder_level > 0 ? rtrim(rtrim(number_format($it->reorder_level,3,'.',''),'0'),'.') : '—' }}</td>
                    <td>{{ $it->unit_cost !== null ? number_format($it->unit_cost,2) : '—' }}</td>
                    <td><div class="inv-act">
                        <button class="inv-mini in" onclick='invStockIn(@json($it->id), @json($it->name), @json($it->unit))'><i class="fas fa-arrow-down"></i> In</button>
                        <button class="inv-mini out" onclick='invConsume(@json($it->id), @json($it->name), @json($it->unit), @json((float)$it->quantity))'><i class="fas fa-arrow-up"></i> Use</button>
                        <a class="inv-mini hist" href="{{ route('crm.inventory.movements',$it->id) }}"><i class="fas fa-clock-rotate-left"></i></a>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;padding:2.4rem;color:#94a3b8"><i class="fas fa-boxes-stacked" style="font-size:1.6rem;display:block;margin-bottom:.5rem"></i>No inventory items yet. Click “Add Item” to record your first stock.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($items->hasPages())<div style="margin-top:1rem">{{ $items->links() }}</div>@endif
</div>

{{-- Add Item modal --}}
<div class="inv-modal-bg" id="invAddModal" onclick="if(event.target===this)invClose('invAddModal')">
    <div class="inv-modal">
        <div class="inv-modal-head"><h3><i class="fas fa-boxes-stacked"></i> Add Inventory Item</h3><button class="inv-close" onclick="invClose('invAddModal')"><i class="fas fa-times"></i></button></div>
        <form method="POST" action="{{ route('crm.inventory.store') }}">{{ csrf_field() }}
            <div class="inv-modal-body">
                <div class="full"><label>Item Name *</label><input class="inv-control" name="name" placeholder="e.g. Art Card 300gsm 20x30" required></div>
                <div><label>Category</label><select class="inv-control" name="category">@foreach(['Paper','Board','Ink','Finishing Material','Other'] as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
                <div><label>Unit *</label><select class="inv-control" name="unit" required>@foreach(['sheets','kg','rolls','pieces','litres'] as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach</select></div>
                <div><label>Paper Size</label><input class="inv-control" name="paper_size" placeholder="20x30"></div>
                <div><label>GSM</label><input class="inv-control" name="gsm" placeholder="300"></div>
                <div class="full"><label>Stock Type</label><input class="inv-control" name="stock_type" placeholder="Art Card / Kraft / SBS..."></div>
                <div><label>Opening Quantity *</label><input class="inv-control" name="quantity" type="number" step="0.001" min="0" value="0" required></div>
                <div><label>Reorder Level</label><input class="inv-control" name="reorder_level" type="number" step="0.001" min="0" placeholder="Low-stock alert"></div>
                <div><label>Unit Cost</label><input class="inv-control" name="unit_cost" type="number" step="0.0001" min="0"></div>
                <div><label>Currency</label><input class="inv-control" name="currency" placeholder="PKR / USD / AED"></div>
                <div class="full"><label>Notes</label><textarea class="inv-control" name="notes" rows="2"></textarea></div>
            </div>
            <div class="inv-modal-actions"><button class="inv-btn" type="button" onclick="invClose('invAddModal')" style="background:#eef2f7;color:#475569">Cancel</button><button class="inv-btn inv-btn-main"><i class="fas fa-check"></i> Save Item</button></div>
        </form>
    </div>
</div>

{{-- Stock In modal --}}
<div class="inv-modal-bg" id="invInModal" onclick="if(event.target===this)invClose('invInModal')">
    <div class="inv-modal">
        <div class="inv-modal-head"><h3><i class="fas fa-arrow-down" style="color:#047857"></i> Stock In — <span id="invInName"></span></h3><button class="inv-close" onclick="invClose('invInModal')"><i class="fas fa-times"></i></button></div>
        <form method="POST" id="invInForm">{{ csrf_field() }}
            <div class="inv-modal-body">
                <div><label>Quantity In (<span class="invInUnit"></span>) *</label><input class="inv-control" name="quantity" type="number" step="0.001" min="0.001" placeholder="e.g. 1000" required></div>
                <div><label>Unit Cost</label><input class="inv-control" name="unit_cost" type="number" step="0.0001" min="0"></div>
                @if($vendors->count())
                <div class="full"><label>Vendor (optional)</label><select class="inv-control" name="vendor_purchase_id"><option value="">— none —</option>@foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>
                @endif
                <div class="full"><label>Reference</label><input class="inv-control" name="reference" placeholder="Invoice # / PO #"></div>
                <div class="full"><label>Note</label><textarea class="inv-control" name="note" rows="2"></textarea></div>
            </div>
            <div class="inv-modal-actions"><button class="inv-btn" type="button" onclick="invClose('invInModal')" style="background:#eef2f7;color:#475569">Cancel</button><button class="inv-btn inv-btn-main"><i class="fas fa-arrow-down"></i> Add Stock</button></div>
        </form>
    </div>
</div>

{{-- Consume modal --}}
<div class="inv-modal-bg" id="invOutModal" onclick="if(event.target===this)invClose('invOutModal')">
    <div class="inv-modal">
        <div class="inv-modal-head"><h3><i class="fas fa-arrow-up" style="color:var(--primary-purple)"></i> Use Stock — <span id="invOutName"></span></h3><button class="inv-close" onclick="invClose('invOutModal')"><i class="fas fa-times"></i></button></div>
        <form method="POST" id="invOutForm">{{ csrf_field() }}
            <div class="inv-modal-body">
                <div class="full" style="background:var(--primary-soft);border-radius:9px;padding:.5rem .7rem;color:var(--primary-purple);font-weight:700;font-size:.78rem">Available: <span id="invOutAvail"></span> <span class="invOutUnit"></span></div>
                <div><label>Quantity Used (<span class="invOutUnit"></span>) *</label><input class="inv-control" name="quantity" type="number" step="0.001" min="0.001" placeholder="e.g. 300" required></div>
                <div><label>Against Job</label>
                    <select class="inv-control" name="job_id">
                        <option value="">— select job —</option>
                        @foreach($jobs as $j)<option value="{{ $j->id }}">{{ $j->job_number }}</option>@endforeach
                    </select>
                </div>
                <div class="full"><label>Or Job Number (manual)</label><input class="inv-control" name="job_number" placeholder="JOB-XXXX (if not in list)"></div>
                <div class="full"><label>Note</label><textarea class="inv-control" name="note" rows="2"></textarea></div>
            </div>
            <div class="inv-modal-actions"><button class="inv-btn" type="button" onclick="invClose('invOutModal')" style="background:#eef2f7;color:#475569">Cancel</button><button class="inv-btn inv-btn-main"><i class="fas fa-arrow-up"></i> Consume</button></div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function invOpen(id){document.getElementById(id).style.display='flex';document.body.style.overflow='hidden'}
function invClose(id){document.getElementById(id).style.display='none';document.body.style.overflow=''}
function invStockIn(id,name,unit){
    var f=document.getElementById('invInForm');
    f.action='{{ url('crm/inventory') }}/'+id+'/stock-in';
    document.getElementById('invInName').textContent=name;
    document.querySelectorAll('.invInUnit').forEach(function(e){e.textContent=unit});
    f.reset(); invOpen('invInModal');
}
function invConsume(id,name,unit,avail){
    var f=document.getElementById('invOutForm');
    f.action='{{ url('crm/inventory') }}/'+id+'/consume';
    document.getElementById('invOutName').textContent=name;
    document.getElementById('invOutAvail').textContent=(''+avail).replace(/\.?0+$/,'');
    document.querySelectorAll('.invOutUnit').forEach(function(e){e.textContent=unit});
    f.reset(); invOpen('invOutModal');
}
</script>
@endsection
