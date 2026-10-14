@extends('crm.layout')
@section('title', 'Inventory')
@section('content')
<style>
.inv-page{color:#233047}
.inv-hero{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem}
.inv-hero-l{display:flex;align-items:center;gap:1rem}.inv-hero-ic{width:62px;height:62px;border-radius:18px;background:#e8efff;color:#3b63f6;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
.inv-hero h2{margin:0;font-size:1.75rem;font-weight:900;letter-spacing:-.02em}.inv-hero p{margin:.15rem 0 0;color:#8290a3;font-size:.86rem}
.inv-date{display:inline-flex;align-items:center;gap:.55rem;padding:.7rem 1rem;background:#fff;border:1px solid #e5ebf2;border-radius:14px;font-size:.78rem;color:#64748b;box-shadow:0 6px 18px rgba(15,23,42,.04)}.inv-date b{color:#ea580c;font-weight:900}
.inv-flash{padding:.7rem 1rem;border-radius:10px;margin-bottom:.8rem;font-size:.82rem;font-weight:700}.inv-flash.err{background:#fff1f2;border:1px solid #fecaca;color:#9f1239}.inv-flash.ok{background:#ecfdf5;border:1px solid #bbf7d0;color:#166534}
.inv-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:.9rem;margin-bottom:1rem}
.inv-card{display:flex;align-items:center;gap:.9rem;padding:1.1rem 1.15rem;border-radius:18px;border:1px solid #e9eef5;text-decoration:none;color:inherit;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.045);transition:transform .15s,box-shadow .15s}
.inv-card:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(15,23,42,.08)}
.inv-card .ic{width:62px;height:62px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.45rem;flex:0 0 auto}
.inv-card .t{flex:1;min-width:0}.inv-card .k{color:#64748b;font-size:.84rem;font-weight:700}.inv-card .v{font-size:1.75rem;font-weight:900;color:#1e293b;letter-spacing:-.02em;line-height:1.15;margin:.1rem 0}.inv-card .v small{font-size:.9rem;font-weight:800;color:#64748b}.inv-card .s{color:#8290a3;font-size:.74rem}
.inv-card .go{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;flex:0 0 auto}
.inv-card.c-blue{background:linear-gradient(135deg,#f5f8ff,#fff)}.inv-card.c-blue .ic{background:#dbe7ff;color:#3b63f6}.inv-card.c-blue .go{background:#e6edff;color:#3b63f6}
.inv-card.c-green{background:linear-gradient(135deg,#f3fcf7,#fff)}.inv-card.c-green .ic{background:#d7f5e4;color:#16a34a}.inv-card.c-green .go{background:#e3f7ec;color:#16a34a}
.inv-card.c-amber{background:linear-gradient(135deg,#fff8ee,#fff)}.inv-card.c-amber .ic{background:#ffe8c7;color:#f59e0b}.inv-card.c-amber .go{background:#ffefd6;color:#f59e0b}.inv-card.c-amber.alert .v{color:#d97706}
.inv-card.c-purple{background:linear-gradient(135deg,#f8f5ff,#fff)}.inv-card.c-purple .ic{background:#e9dfff;color:#7c3aed}.inv-card.c-purple .go{background:#eee6ff;color:#7c3aed}
.inv-toolbar{display:flex;gap:.6rem;align-items:center;margin-bottom:.9rem;padding:.7rem .8rem;background:#fff;border:1px solid #e5ebf2;border-radius:16px;box-shadow:0 8px 24px rgba(15,23,42,.045);flex-wrap:wrap}
.inv-search{flex:1;min-width:240px;display:flex;align-items:center;gap:.6rem;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:.7rem .95rem;color:#94a3b8}.inv-search input{border:0;outline:0;width:100%;font-size:.88rem;color:#1e293b}.inv-search:focus-within{border-color:var(--primary-purple)}
.inv-select{display:inline-flex;align-items:center;gap:.5rem;border:1.5px solid #e2e8f0;border-radius:12px;padding:0 .5rem 0 .85rem;background:#fff;color:#475569;font-weight:700;font-size:.84rem}.inv-select i{color:#64748b}.inv-select select{border:0;outline:0;background:transparent;padding:.72rem .3rem;font-weight:700;color:#334155;font-size:.84rem;cursor:pointer}.inv-select.sm select{padding:.45rem .2rem;font-size:.78rem}
.inv-clear{width:38px;height:40px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;background:#f1f5f9;color:#64748b;text-decoration:none}.inv-clear:hover{background:#fee2e2;color:#b91c1c}
.inv-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.72rem 1.15rem;border:0;border-radius:12px;font-weight:800;font-size:.86rem;cursor:pointer;text-decoration:none}
.inv-btn-main{background:#f4511e;color:#fff;box-shadow:0 8px 18px rgba(244,81,30,.3)}.inv-btn-main:hover{filter:brightness(1.05)}
.inv-wrap{background:#fff;border:1px solid #e4eaf1;border-radius:18px;box-shadow:0 8px 26px rgba(15,23,42,.055);overflow:hidden}
.inv-scroll{overflow:auto}
.inv-table{width:100%;min-width:980px;border-collapse:collapse}
.inv-table th{padding:.95rem 1rem;background:#f8fafc;border-bottom:1px solid #e9eef5;text-align:left;color:#64748b;font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;white-space:nowrap}
.inv-table td{padding:.85rem 1rem;border-bottom:1px solid #eef2f6;font-size:.86rem;vertical-align:middle;color:#334155}
.inv-table tbody tr:hover{background:#fafbff}.inv-table .chk{width:34px}.inv-table input[type=checkbox]{width:16px;height:16px;border-radius:5px;accent-color:var(--primary-purple)}
.inv-sort{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:.35rem}.inv-sort i{color:#cbd5e1;font-size:.7rem}.inv-sort.on i,.inv-sort:hover i{color:var(--primary-purple)}
.inv-item{display:flex;align-items:center;gap:.75rem}.inv-item-ic{width:44px;height:44px;border-radius:12px;background:#eef2f6;color:#64748b;display:flex;align-items:center;justify-content:center;font-size:1rem;flex:0 0 auto}.inv-item strong{font-size:.92rem;color:#1e293b}.inv-sub{color:#94a3b8;font-size:.72rem}
.inv-qty{font-weight:900;font-size:1.05rem;color:#1e293b}.inv-qty.low{color:#d97706}.inv-qty.out{color:#b91c1c}.inv-unit{color:#94a3b8;font-size:.72rem;margin-left:.2rem}
.inv-status{display:inline-flex;align-items:center;gap:.4rem;padding:.38rem .75rem;border-radius:999px;font-size:.74rem;font-weight:800;white-space:nowrap}.inv-status i{font-size:.45rem}
.inv-status.in{background:#dcfce7;color:#15803d}.inv-status.low{background:#fef3c7;color:#b45309}.inv-status.out{background:#fee2e2;color:#b91c1c}
.inv-act{display:inline-flex;gap:.4rem;align-items:center;white-space:nowrap}
.inv-mini{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:11px;font-size:.82rem;cursor:pointer;text-decoration:none;transition:transform .1s,filter .1s}.inv-mini:hover{transform:translateY(-1px);filter:brightness(.96)}
.inv-mini.edit{background:#e8efff;color:#3b63f6}.inv-mini.in{background:#e1f8ef;color:#16a34a}.inv-mini.out{background:#fff1e6;color:#ea580c}.inv-mini.hist{background:#f1f5f9;color:#475569}.inv-mini.del{background:#fee2e2;color:#dc2626}
.inv-foot{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.9rem 1rem;color:#64748b;font-size:.82rem;border-top:1px solid #eef2f6;flex-wrap:wrap}
.inv-pager{display:inline-flex;align-items:center;gap:.4rem}.inv-pager .pg{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #e2e8f0;border-radius:11px;color:#475569;text-decoration:none;font-weight:800;font-size:.82rem;background:#fff}.inv-pager .pg.on{background:#f4511e;color:#fff;border-color:#f4511e;box-shadow:0 6px 14px rgba(244,81,30,.3)}.inv-pager .pg.dis{opacity:.45;pointer-events:none}
.inv-modal-bg{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.55)}
.inv-modal{width:100%;max-width:520px;max-height:92vh;overflow:auto;background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(15,23,42,.25)}
.inv-modal-head{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.15rem;border-bottom:1px solid #edf1f5}
.inv-modal-head h3{margin:0;font-size:1.05rem}
.inv-modal-body{padding:1.1rem;display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
.inv-modal-body .full{grid-column:1/-1}
.inv-modal label{display:block;margin-bottom:.3rem;color:#536277;font-size:.7rem;font-weight:800}
.inv-modal .inv-control{width:100%;padding:.62rem .7rem;border:1.5px solid #dae3ed;border-radius:9px;box-sizing:border-box;outline:0;font-size:.85rem}
.inv-modal .inv-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.inv-modal-actions{display:flex;justify-content:flex-end;gap:.55rem;padding:.9rem 1.15rem;border-top:1px solid #edf1f5}
.inv-close{width:32px;height:32px;border:0;border-radius:8px;background:#eef2f7;cursor:pointer}
@media(max-width:1100px){.inv-cards{grid-template-columns:1fr 1fr}}@media(max-width:700px){.inv-cards{grid-template-columns:1fr}.inv-hero{flex-direction:column;align-items:flex-start}}
</style>
<div class="inv-page">
    @php($__cur = optional($items->first())->currency ?: 'AED')
    <div class="inv-hero">
        <div class="inv-hero-l"><span class="inv-hero-ic"><i class="fas fa-cube"></i></span><div><h2>Inventory</h2><p>Stock items, purchases in, and job-wise consumption</p></div></div>
        <div class="inv-hero-r"><span class="inv-date"><i class="far fa-calendar"></i> {{ now()->format('l, F j, Y') }} <b>{{ now()->format('h:i A') }}</b></span></div>
    </div>

    @if(session('error'))<div class="inv-flash err"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif
    @if(session('success'))<div class="inv-flash ok"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif

    <div class="inv-cards">
        <a class="inv-card c-blue" href="{{ route('crm.inventory.index') }}"><span class="ic"><i class="fas fa-cube"></i></span><div class="t"><div class="k">Total Items</div><div class="v">{{ number_format($summary['items']) }}</div><div class="s">Unique inventory items</div></div><span class="go"><i class="fas fa-chevron-right"></i></span></a>
        <a class="inv-card c-green" href="{{ route('crm.inventory.index', ['status' => 'in']) }}"><span class="ic"><i class="fas fa-boxes"></i></span><div class="t"><div class="k">Total In Stock</div><div class="v">{{ rtrim(rtrim(number_format($summary['in_stock'],3,'.',''),'0'),'.') }}</div><div class="s">Units available</div></div><span class="go"><i class="fas fa-chevron-right"></i></span></a>
        <a class="inv-card c-amber {{ $summary['low'] ? 'alert' : '' }}" href="{{ route('crm.inventory.index', ['status' => 'low']) }}"><span class="ic"><i class="fas fa-exclamation-triangle"></i></span><div class="t"><div class="k">Low Stock</div><div class="v">{{ number_format($summary['low']) }}</div><div class="s">Items below threshold</div></div><span class="go"><i class="fas fa-chevron-right"></i></span></a>
        <a class="inv-card c-purple" href="{{ route('crm.inventory.index', ['sort' => 'unit_cost', 'dir' => 'desc']) }}"><span class="ic"><i class="fas fa-money-bill-wave"></i></span><div class="t"><div class="k">Stock Value</div><div class="v"><small>{{ $__cur }}</small> {{ number_format($summary['value'],2) }}</div><div class="s">Total inventory value</div></div><span class="go"><i class="fas fa-chevron-right"></i></span></a>
    </div>

    <form class="inv-toolbar" method="GET" id="invFilterForm">
        <input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="dir" value="{{ $dir }}"><input type="hidden" name="per_page" value="{{ $perPage }}">
        <div class="inv-search"><i class="fas fa-search"></i><input name="search" value="{{ request('search') }}" placeholder="Search item, size, GSM, stock type..."></div>
        <label class="inv-select"><i class="fas fa-th-large"></i><select name="category" onchange="this.form.submit()"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c }}" {{ request('category')===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select></label>
        <label class="inv-select"><i class="fas fa-filter"></i><select name="status" onchange="this.form.submit()"><option value="">Filters</option><option value="in" {{ request('status')==='in'?'selected':'' }}>In stock</option><option value="low" {{ request('status')==='low'?'selected':'' }}>Low stock</option><option value="out" {{ request('status')==='out'?'selected':'' }}>Out of stock</option></select></label>
        @if(request()->hasAny(['search','category','status']))<a class="inv-clear" href="{{ route('crm.inventory.index') }}" title="Clear filters"><i class="fas fa-times"></i></a>@endif
        <button class="inv-btn inv-btn-main" type="button" onclick="invOpen('invAddModal')"><i class="fas fa-plus"></i> Add Item</button>
    </form>

    @php($__canEdit = auth('crm')->user()->isAdmin() || auth('crm')->user()->isSuperAdmin() || auth('crm')->user()->isAccounts())
    <div class="inv-wrap">
        <div class="inv-scroll">
        <table class="inv-table">
            <thead><tr>
                <th class="chk"><input type="checkbox" onclick="document.querySelectorAll('.inv-row-chk').forEach(function(c){c.checked=event.target.checked})"></th>
                @foreach([['name','Item'],['paper_size','Size'],['gsm','GSM'],['stock_type','Stock Type'],['quantity','In Stock'],['reorder_level','Reorder'],['unit_cost','Unit Cost']] as [$__c,$__l])
                    @php($__next = ($sort === $__c && $dir === 'asc') ? 'desc' : 'asc')
                    <th><a class="inv-sort {{ $sort === $__c ? 'on' : '' }}" href="{{ request()->fullUrlWithQuery(['sort' => $__c, 'dir' => $__next, 'page' => 1]) }}">{{ $__l }} <i class="fas {{ $sort === $__c ? ($dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }}"></i></a></th>
                @endforeach
                <th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
            @forelse($items as $it)
                @php($__st = (float)$it->quantity <= 0 ? 'out' : ($it->is_low ? 'low' : 'in'))
                <tr>
                    <td class="chk"><input type="checkbox" class="inv-row-chk" value="{{ $it->id }}"></td>
                    <td><div class="inv-item"><span class="inv-item-ic"><i class="fas fa-cube"></i></span><div><strong>{{ $it->name }}</strong>@if($it->category)<div class="inv-sub">{{ $it->category }}</div>@endif</div></div></td>
                    <td>{{ $it->paper_size ?: '—' }}</td>
                    <td>{{ $it->gsm ?: '—' }}</td>
                    <td>{{ $it->stock_type ?: '—' }}</td>
                    <td><span class="inv-qty {{ $__st }}">{{ rtrim(rtrim(number_format($it->quantity,3,'.',''),'0'),'.') }}</span><span class="inv-unit">{{ $it->unit }}</span></td>
                    <td>{{ $it->reorder_level > 0 ? rtrim(rtrim(number_format($it->reorder_level,3,'.',''),'0'),'.') : '—' }}</td>
                    <td>{{ $it->unit_cost !== null ? ($it->currency ?: $__cur).' '.number_format($it->unit_cost,2) : '—' }}</td>
                    <td><span class="inv-status {{ $__st }}"><i class="fas fa-circle"></i> {{ $__st === 'in' ? 'In Stock' : ($__st === 'low' ? 'Low Stock' : 'Out of Stock') }}</span></td>
                    <td><div class="inv-act">
                        @if($__canEdit)<button class="inv-mini edit" type="button" title="Edit item" data-item="{{ json_encode($it->only(['id','name','category','paper_size','gsm','stock_type','unit','reorder_level','unit_cost','currency','notes'])) }}" onclick="invEdit(JSON.parse(this.dataset.item))"><i class="fas fa-pen"></i></button>@endif
                        <button class="inv-mini in" type="button" title="Stock in" onclick='invStockIn(@json($it->id), @json($it->name), @json($it->unit))'><i class="fas fa-arrow-down"></i></button>
                        <button class="inv-mini out" type="button" title="Use stock" onclick='invConsume(@json($it->id), @json($it->name), @json($it->unit), @json((float)$it->quantity))'><i class="fas fa-arrow-up"></i></button>
                        <a class="inv-mini hist" href="{{ route('crm.inventory.movements',$it->id) }}" title="Stock history"><i class="fas fa-history"></i></a>
                        @if($__canEdit)<form method="POST" action="{{ route('crm.inventory.destroy',$it->id) }}" onsubmit="return confirm('Delete {{ addslashes($it->name) }} and its stock history?')" style="display:inline">{{ csrf_field() }}{{ method_field('DELETE') }}<button class="inv-mini del" title="Delete item"><i class="fas fa-trash"></i></button></form>@endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;padding:2.6rem;color:#94a3b8"><i class="fas fa-boxes" style="font-size:1.6rem;display:block;margin-bottom:.5rem"></i>No inventory items{{ request()->hasAny(['search','category','status']) ? ' match these filters' : ' yet. Click “Add Item” to record your first stock' }}.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div class="inv-foot">
            <span>Showing {{ $items->firstItem() ?: 0 }} to {{ $items->lastItem() ?: 0 }} of {{ $items->total() }} items</span>
            <div class="inv-pager">
                <a class="pg {{ $items->onFirstPage() ? 'dis' : '' }}" href="{{ $items->previousPageUrl() ?: '#' }}"><i class="fas fa-chevron-left"></i></a>
                @foreach($items->getUrlRange(max(1,$items->currentPage()-2), min($items->lastPage(),$items->currentPage()+2)) as $pg => $url)<a class="pg {{ $pg === $items->currentPage() ? 'on' : '' }}" href="{{ $url }}">{{ $pg }}</a>@endforeach
                <a class="pg {{ $items->hasMorePages() ? '' : 'dis' }}" href="{{ $items->nextPageUrl() ?: '#' }}"><i class="fas fa-chevron-right"></i></a>
                <label class="inv-select sm"><select onchange="location.href=this.value">@foreach([10,25,50,100] as $n)<option value="{{ request()->fullUrlWithQuery(['per_page' => $n, 'page' => 1]) }}" {{ $perPage===$n?'selected':'' }}>{{ $n }} / page</option>@endforeach</select></label>
            </div>
        </div>
    </div>
</div>

{{-- Edit Item modal --}}
<div class="inv-modal-bg" id="invEditModal" onclick="if(event.target===this)invClose('invEditModal')">
    <div class="inv-modal">
        <div class="inv-modal-head"><h3><i class="fas fa-pen" style="color:var(--primary-purple)"></i> Edit Item — <span id="invEditName"></span></h3><button class="inv-close" onclick="invClose('invEditModal')"><i class="fas fa-times"></i></button></div>
        <form method="POST" id="invEditForm">{{ csrf_field() }}{{ method_field('PUT') }}
            <div class="inv-modal-body">
                <div class="full"><label>Item Name *</label><input class="inv-control" name="name" required></div>
                <div><label>Category</label><input class="inv-control" name="category" list="invCats"><datalist id="invCats">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist></div>
                <div><label>Unit *</label><input class="inv-control" name="unit" list="invUnits" required><datalist id="invUnits">@foreach(['pcs','sheets','kg','rolls','pieces','litres','boxes'] as $u)<option value="{{ $u }}">@endforeach</datalist></div>
                <div><label>Paper Size</label><input class="inv-control" name="paper_size"></div>
                <div><label>GSM</label><input class="inv-control" name="gsm"></div>
                <div class="full"><label>Stock Type</label><input class="inv-control" name="stock_type"></div>
                <div><label>Reorder Level</label><input class="inv-control" name="reorder_level" type="number" step="0.001" min="0"></div>
                <div><label>Unit Cost</label><input class="inv-control" name="unit_cost" type="number" step="0.0001" min="0"></div>
                <div><label>Currency</label><input class="inv-control" name="currency"></div>
                <div class="full"><label>Notes</label><textarea class="inv-control" name="notes" rows="2"></textarea></div>
                <div class="full" style="font-size:.72rem;color:#64748b"><i class="fas fa-info-circle"></i> Quantity is not edited here — use <b>Stock in</b>, <b>Use stock</b> or the history page's adjustment so the ledger stays correct.</div>
            </div>
            <div class="inv-modal-actions"><button class="inv-btn" type="button" onclick="invClose('invEditModal')" style="background:#eef2f7;color:#475569">Cancel</button><button class="inv-btn inv-btn-main"><i class="fas fa-check"></i> Save Changes</button></div>
        </form>
    </div>
</div>

{{-- Add Item modal --}}
<div class="inv-modal-bg" id="invAddModal" onclick="if(event.target===this)invClose('invAddModal')">
    <div class="inv-modal">
        <div class="inv-modal-head"><h3><i class="fas fa-boxes"></i> Add Inventory Item</h3><button class="inv-close" onclick="invClose('invAddModal')"><i class="fas fa-times"></i></button></div>
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
                        @foreach($jobs as $j)<option value="{{ $j->id }}">{{ $j->label }}</option>@endforeach
                    </select>
                    @if($jobs->isEmpty())<div style="margin-top:.3rem;font-size:.68rem;color:#b45309;font-weight:700"><i class="fas fa-info-circle"></i> No jobs in this workspace yet (Jobs page is empty) — type the job number below.</div>@endif
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
function invEdit(d){var f=document.getElementById('invEditForm');f.action='{{ url('/crm/inventory') }}/'+d.id;document.getElementById('invEditName').textContent=d.name;['name','category','paper_size','gsm','stock_type','unit','reorder_level','unit_cost','currency','notes'].forEach(function(k){if(f.elements[k])f.elements[k].value=(d[k]===null||d[k]===undefined)?'':d[k];});invOpen('invEditModal');}
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
