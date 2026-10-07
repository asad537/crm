@extends('crm.layout')
@section('title', 'Print Ready')

<style>
.pl-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.3rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px}
.pl-hero h2{margin:0;font-size:1.25rem;color:#0f172a}
.pl-muted{color:#8796aa;font-size:.75rem}
.pl-wrap{background:#fff;border:1px solid #e4eaf1;border-radius:15px;box-shadow:0 8px 26px rgba(15,23,42,.055);overflow-x:auto}
.pl-table{width:100%;border-collapse:collapse;min-width:960px}
.pl-table th{padding:.85rem .9rem;background:#f7f9fc;border-bottom:2px solid var(--primary-soft);text-align:left;color:#718096;font-size:.65rem;text-transform:uppercase;letter-spacing:.045em;white-space:nowrap}
.pl-table td{padding:.9rem .9rem;border-bottom:1px solid #edf1f5;vertical-align:middle;font-size:.8rem;color:#334155}
.pl-table tbody tr:hover{background:var(--primary-soft);box-shadow:inset 3px 0 0 var(--primary-purple)}
.pl-status{display:inline-flex;padding:.34rem .68rem;border-radius:999px;font-size:.64rem;font-weight:850;text-transform:uppercase;letter-spacing:.03em;white-space:nowrap}
.pl-status.requested{background:#eef2ff;color:#4338ca}.pl-status.in_progress{background:#eaf2ff;color:#285fbd}.pl-status.change_requested{background:#fff3e8;color:#c2410c}.pl-status.completed{background:#eafbf2;color:#08784c}
.pl-view{display:inline-flex;align-items:center;gap:.38rem;padding:.5rem .8rem;border:1px solid var(--primary-shadow);border-radius:9px;background:var(--primary-soft);color:var(--primary-purple);font-weight:850;text-decoration:none;font-size:.75rem;white-space:nowrap}
.pl-pick{display:inline-flex;align-items:center;gap:.38rem;padding:.5rem .8rem;border:none;border-radius:9px;background:var(--primary-purple);color:#fff;font-weight:850;font-size:.75rem;cursor:pointer;white-space:nowrap}
.pl-empty{text-align:center;padding:2.5rem;color:#94a3b8}
.pl-tabs{display:flex;gap:.55rem;margin-bottom:1rem;flex-wrap:wrap}
.pl-tab{display:inline-flex;align-items:center;gap:.45rem;padding:.62rem .9rem;border-radius:10px;background:#edf2f7;color:#526176;text-decoration:none;font-weight:850;font-size:.82rem}
.pl-tab.active{background:var(--primary-purple);color:#fff;box-shadow:0 6px 14px var(--primary-shadow)}
.pl-tab-count{min-width:21px;height:21px;padding:0 6px;display:inline-flex;align-items:center;justify-content:center;border-radius:99px;background:rgba(15,23,42,.08);font-size:.68rem}
.pl-tab.active .pl-tab-count{background:rgba(255,255,255,.24)}
.pl-due{white-space:nowrap}.pl-due.late{color:#b91c1c;font-weight:800}
.pl-access{background:#fff;border:1px solid #e4eaf1;border-radius:14px;box-shadow:0 6px 18px rgba(15,23,42,.05);margin-bottom:1rem;overflow:hidden}
.pl-access>summary{list-style:none;cursor:pointer;padding:.9rem 1.1rem;font-weight:850;color:#0f172a;display:flex;align-items:center;gap:.5rem}
.pl-access>summary::-webkit-details-marker{display:none}
.pl-access>summary i{color:var(--primary-purple)}
.pl-access-hint{font-weight:600;color:#8796aa;font-size:.78rem}
.pl-access-body{border-top:1px solid #eef2f7;padding:.4rem .6rem .7rem}
.pl-access-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.55rem .5rem;border-bottom:1px solid #f4f7fb}
.pl-access-row:last-child{border-bottom:0}
.pl-access-name{display:flex;align-items:center;gap:.6rem;font-weight:800;color:#1f2b3d;font-size:.85rem}
.pl-access-avatar{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:var(--primary-soft);color:var(--primary-purple);font-weight:900;font-size:.8rem}
.pl-access-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .8rem;border-radius:9px;font-weight:800;font-size:.76rem;cursor:pointer;border:1px solid transparent}
.pl-access-btn.on{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.pl-access-add{display:flex;gap:.6rem;align-items:center;padding:.5rem .3rem .9rem;flex-wrap:wrap}
.pl-access-pick{position:relative;flex:1 1 280px;min-width:220px}
.pl-access-pick i{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.82rem}
.pl-access-input{width:100%;padding:.62rem .7rem .62rem 2rem;border:1.5px solid #dbe3ec;border-radius:10px;background:#fff;outline:0;box-sizing:border-box;font:inherit;font-size:.85rem}
.pl-access-input:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.pl-access-grant{display:inline-flex;align-items:center;gap:.45rem;padding:.62rem 1rem;border-radius:10px;border:1px solid var(--primary-purple);background:var(--primary-purple);color:#fff;font-weight:850;font-size:.82rem;cursor:pointer;box-shadow:0 8px 18px var(--primary-shadow)}
.pl-access-grant:disabled{opacity:.45;cursor:not-allowed;box-shadow:none}
.pl-access-label{font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;padding:.2rem .3rem .35rem}
</style>

@section('content')
<div class="pl-hero">
    <div><h2><i class="fas fa-print" style="color:var(--primary-purple)"></i> Print Ready</h2><div class="pl-muted">Artwork tickets for paid orders sent to production. Pick a ticket, prepare print-ready files, complete it.</div></div>
</div>

@if($isAdmin)
@php $__granted = $accessDesigners->filter(fn($d) => $d->print_ready_access); $__ungranted = $accessDesigners->filter(fn($d) => !$d->print_ready_access)->values(); @endphp
<details class="pl-access" {{ $__granted->count() ? '' : 'open' }}>
    <summary><i class="fas fa-user-shield"></i> Designer Access <span class="pl-access-hint">— choose which designers can see the Print Ready tab</span></summary>
    <div class="pl-access-body">
        <form method="POST" action="{{ route('crm.print_ready.grant_access') }}" class="pl-access-add">
            {{ csrf_field() }}
            <div class="pl-access-pick">
                <i class="fas fa-search"></i>
                <input list="prDesignerList" name="__designer_label" class="pl-access-input" placeholder="Search a designer to give access…" autocomplete="off" oninput="prSyncDesigner(this)">
                <input type="hidden" name="designer_id" id="prDesignerId">
                <datalist id="prDesignerList">@foreach($__ungranted as $d)<option data-id="{{ $d->id }}" value="{{ $d->name }}"></option>@endforeach</datalist>
            </div>
            <button type="submit" class="pl-access-grant" {{ $__ungranted->count() ? '' : 'disabled' }}><i class="fas fa-plus"></i> Grant Access</button>
        </form>
        <div class="pl-access-label">Designers with access</div>
        @forelse($__granted as $d)
            <div class="pl-access-row">
                <div class="pl-access-name"><span class="pl-access-avatar">{{ strtoupper(substr($d->name,0,1)) }}</span> {{ $d->name }}</div>
                <form method="POST" action="{{ route('crm.print_ready.toggle_access',$d->id) }}">{{ csrf_field() }}<input type="hidden" name="grant" value="0"><button type="submit" class="pl-access-btn on"><i class="fas fa-check-circle"></i> Access granted — Revoke</button></form>
            </div>
        @empty
            <div class="pl-muted" style="padding:.5rem .2rem">No designers have access yet. Search above to grant one.</div>
        @endforelse
    </div>
</details>
<script>
    function prSyncDesigner(input){ var list=document.getElementById('prDesignerList'), hidden=document.getElementById('prDesignerId'); var m=Array.prototype.find.call(list.options,function(o){return o.value===input.value;}); hidden.value=m?m.getAttribute('data-id'):''; }
</script>
@endif

<div class="pl-tabs">
    <a class="pl-tab {{ $tab==='active'?'active':'' }}" href="{{ route('crm.print_ready.index',['tab'=>'active']) }}"><i class="fas fa-bolt"></i> Active Tickets <span class="pl-tab-count">{{ $counts['active'] ?? 0 }}</span></a>
    <a class="pl-tab {{ $tab==='mine'?'active':'' }}" href="{{ route('crm.print_ready.index',['tab'=>'mine']) }}"><i class="fas fa-user-check"></i> {{ $isAdmin ? 'In Progress' : 'My Tickets' }} <span class="pl-tab-count">{{ $counts['mine'] ?? 0 }}</span></a>
    <a class="pl-tab {{ $tab==='history'?'active':'' }}" href="{{ route('crm.print_ready.index',['tab'=>'history']) }}"><i class="fas fa-clock-rotate-left"></i> History <span class="pl-tab-count">{{ $counts['history'] ?? 0 }}</span></a>
</div>

<div class="pl-wrap">
    <table class="pl-table">
        <thead><tr><th>Ticket</th><th>Job #</th><th>Client</th><th>Product(s)</th><th>Qty</th><th>Printer's Deadline</th><th>Designer</th><th>Status</th><th>Created</th><th></th></tr></thead>
        <tbody>
        @forelse($tickets as $t)
            @php $prods = collect($t->products ?: []); $late = $t->printers_deadline && $t->status !== 'completed' && $t->printers_deadline->isPast(); @endphp
            <tr>
                <td><strong>{{ $t->ticket_number }}</strong></td>
                <td><strong style="color:#1f2b3d">{{ $t->job_number }}</strong></td>
                <td>{{ $t->client_name ?: '—' }}</td>
                <td>{{ $prods->pluck('product')->filter()->implode(', ') ?: '—' }}</td>
                <td>{{ $prods->pluck('quantity')->filter()->implode(' / ') ?: '—' }}</td>
                <td class="pl-due {{ $late ? 'late' : '' }}">{{ optional($t->printers_deadline)->format('d M Y') ?: '—' }}</td>
                <td>{{ optional($t->designer)->name ?: 'Not assigned' }}</td>
                <td><span class="pl-status {{ $t->status }}">{{ $t->statusLabel() }}</span></td>
                <td class="pl-muted">{{ $t->created_at->format('d M Y') }}</td>
                <td style="text-align:right;white-space:nowrap">
                    @if($t->status === 'requested' && !$t->assigned_designer_id && !$isAdmin)
                        <form method="POST" action="{{ route('crm.print_ready.claim',$t->id) }}" style="display:inline">{{ csrf_field() }}<button type="submit" class="pl-pick"><i class="fas fa-hand-paper"></i> Pick Ticket</button></form>
                    @endif
                    <a href="{{ route('crm.print_ready.show',$t->id) }}" class="pl-view"><i class="fas fa-eye"></i> View</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="10" class="pl-empty">{{ $tab === 'active' ? 'No open tickets. New tickets appear here when a paid order is sent to production.' : ($tab === 'mine' ? 'Nothing in progress. Pick a ticket from Active Tickets.' : 'No completed tickets yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
