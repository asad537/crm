@extends('crm.layout')
@section('title', 'Proposals')

@section('content')
<style>
.pl-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.3rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px}
.pl-hero h2{margin:0;font-size:1.25rem;color:#0f172a}
.pl-muted{color:#8796aa;font-size:.75rem}
.pl-add{display:inline-flex;align-items:center;gap:.45rem;min-height:42px;padding:.63rem .95rem;border-radius:11px;background:var(--primary-purple);color:#fff;text-decoration:none;font-weight:850;box-shadow:0 8px 20px var(--primary-shadow)}
.pl-wrap{background:#fff;border:1px solid #e4eaf1;border-radius:15px;box-shadow:0 8px 26px rgba(15,23,42,.055);overflow:hidden}
.pl-table{width:100%;border-collapse:collapse}
.pl-table th{padding:.85rem .9rem;background:#f7f9fc;border-bottom:2px solid var(--primary-soft);text-align:left;color:#718096;font-size:.65rem;text-transform:uppercase;letter-spacing:.045em}
.pl-table td{padding:1rem .9rem;border-bottom:1px solid #edf1f5;vertical-align:middle;font-size:.8rem;color:#334155}
.pl-table tbody tr{transition:background-color .15s,box-shadow .15s}
.pl-table tbody tr:hover{background:var(--primary-soft);box-shadow:inset 3px 0 0 var(--primary-purple)}
.pl-status{display:inline-flex;padding:.34rem .68rem;border-radius:999px;font-size:.64rem;font-weight:850;text-transform:uppercase;letter-spacing:.03em}
.pl-status.requested{background:#eef2ff;color:#4338ca}
.pl-status.in_progress{background:#eaf2ff;color:#285fbd}
.pl-status.change_requested{background:#fff3e8;color:#c2410c}
.pl-status.completed{background:#eafbf2;color:#08784c}
.pl-view{display:inline-flex;align-items:center;gap:.38rem;padding:.5rem .8rem;border:1px solid var(--primary-shadow);border-radius:9px;background:var(--primary-soft);color:var(--primary-purple);font-weight:850;text-decoration:none;font-size:.75rem}
.pl-empty{text-align:center;padding:2.5rem;color:#94a3b8}
.pl-tabs{display:flex;gap:.55rem;margin-bottom:1rem;flex-wrap:wrap}
.pl-tab{display:inline-flex;align-items:center;gap:.45rem;padding:.62rem .9rem;border-radius:10px;background:#edf2f7;color:#526176;text-decoration:none;font-weight:850;font-size:.82rem}
.pl-tab.active{background:var(--primary-purple);color:#fff;box-shadow:0 6px 14px var(--primary-shadow)}
.pl-tab-count{min-width:21px;height:21px;padding:0 6px;display:inline-flex;align-items:center;justify-content:center;border-radius:99px;background:rgba(15,23,42,.08);font-size:.68rem}
.pl-tab.active .pl-tab-count{background:rgba(255,255,255,.24)}
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
.pl-access-btn.off{background:#f8fafc;color:#64748b;border-color:#e2e8f0}
.pl-access-add{display:flex;gap:.6rem;align-items:center;padding:.5rem .3rem .9rem;flex-wrap:wrap}
.pl-access-pick{position:relative;flex:1 1 280px;min-width:220px}
.pl-access-pick i{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.82rem}
.pl-access-input{width:100%;padding:.62rem .7rem .62rem 2rem;border:1.5px solid #dbe3ec;border-radius:10px;background:#fff;outline:0;box-sizing:border-box;font:inherit;font-size:.85rem}
.pl-access-input:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.pl-access-grant{display:inline-flex;align-items:center;gap:.45rem;padding:.62rem 1rem;border-radius:10px;border:1px solid var(--primary-purple);background:var(--primary-purple);color:#fff;font-weight:850;font-size:.82rem;cursor:pointer;box-shadow:0 8px 18px var(--primary-shadow)}
.pl-access-grant:disabled{opacity:.45;cursor:not-allowed;box-shadow:none}
.pl-access-label{font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;padding:.2rem .3rem .35rem}
</style>

<div class="pl-hero">
    <div><h2>Proposals</h2><div class="pl-muted">Design proposals raised from inquiries or created directly.</div></div>
    <a href="{{ route('crm.proposals.create') }}" class="pl-add"><i class="fas fa-plus"></i> Create Proposal</a>
</div>

@if($isAdmin)
@php
    $__granted = $accessDesigners->filter(fn($d) => $d->proposal_access);
    $__ungranted = $accessDesigners->filter(fn($d) => !$d->proposal_access)->values();
@endphp
<details class="pl-access" {{ $__granted->count() ? '' : 'open' }}>
    <summary><i class="fas fa-user-shield"></i> Designer Access <span class="pl-access-hint">— choose which designers can see the Proposal module</span></summary>
    <div class="pl-access-body">
        <form method="POST" action="{{ route('crm.proposals.grant_access') }}" class="pl-access-add">
            {{ csrf_field() }}
            <div class="pl-access-pick">
                <i class="fas fa-search"></i>
                <input list="proposalDesignerList" name="__designer_label" class="pl-access-input" placeholder="Search a designer to give access…" autocomplete="off" oninput="plSyncDesigner(this)">
                <input type="hidden" name="designer_id" id="plDesignerId">
                <datalist id="proposalDesignerList">
                    @foreach($__ungranted as $d)
                        <option data-id="{{ $d->id }}" value="{{ $d->name }}"></option>
                    @endforeach
                </datalist>
            </div>
            <button type="submit" class="pl-access-grant" {{ $__ungranted->count() ? '' : 'disabled' }}><i class="fas fa-plus"></i> Grant Access</button>
        </form>

        <div class="pl-access-label">Designers with access</div>
        @forelse($__granted as $d)
            <div class="pl-access-row">
                <div class="pl-access-name"><span class="pl-access-avatar">{{ strtoupper(substr($d->name,0,1)) }}</span> {{ $d->name }}</div>
                <form method="POST" action="{{ route('crm.proposals.toggle_access',$d->id) }}">
                    {{ csrf_field() }}
                    <input type="hidden" name="grant" value="0">
                    <button type="submit" class="pl-access-btn on"><i class="fas fa-check-circle"></i> Access granted — Revoke</button>
                </form>
            </div>
        @empty
            <div class="pl-muted" style="padding:.5rem .2rem">No designers have access yet. Search above to grant one.</div>
        @endforelse
    </div>
</details>
<script>
    // Map the typed/selected designer name back to its id from the datalist.
    function plSyncDesigner(input){
        var list = document.getElementById('proposalDesignerList');
        var hidden = document.getElementById('plDesignerId');
        var match = Array.prototype.find.call(list.options, function(o){ return o.value === input.value; });
        hidden.value = match ? match.getAttribute('data-id') : '';
    }
</script>
@endif

<div class="pl-tabs">
    <a class="pl-tab {{ $tab==='active'?'active':'' }}" href="{{ route('crm.proposals.index',['tab'=>'active']) }}"><i class="fas fa-bolt"></i> Active <span class="pl-tab-count">{{ $counts['active'] ?? 0 }}</span></a>
    <a class="pl-tab {{ $tab==='open'?'active':'' }}" href="{{ route('crm.proposals.index',['tab'=>'open']) }}"><i class="fas fa-folder-open"></i> Open <span class="pl-tab-count">{{ $counts['open'] ?? 0 }}</span></a>
    <a class="pl-tab {{ $tab==='history'?'active':'' }}" href="{{ route('crm.proposals.index',['tab'=>'history']) }}"><i class="fas fa-clock-rotate-left"></i> History <span class="pl-tab-count">{{ $counts['history'] ?? 0 }}</span></a>
</div>

<div class="pl-wrap">
    <table class="pl-table">
        <thead><tr><th>#</th><th>Subject</th><th>Client</th><th>Product</th><th>Assigned Designer</th><th>Status</th><th>Created</th><th></th></tr></thead>
        <tbody>
        @forelse($proposals as $p)
            <tr>
                <td><strong>{{ $p->id }}</strong></td>
                <td><strong style="color:#1f2b3d">{{ $p->subject }}</strong></td>
                <td>{{ $p->client_name ?: '—' }}</td>
                <td>{{ $p->product_name ?: '—' }}</td>
                <td>{{ optional($p->designer)->name ?: 'Not assigned' }}</td>
                <td><span class="pl-status {{ $p->status }}">{{ ucwords(str_replace('_',' ',$p->status)) }}</span></td>
                <td class="pl-muted">{{ $p->created_at->format('d M Y') }}</td>
                <td style="text-align:right;white-space:nowrap">
                    <a href="{{ route('crm.proposals.show',$p->id) }}" class="pl-view"><i class="fas fa-eye"></i> View Proposal</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="pl-empty">No proposals yet. Create one, or use “Request Proposal” from an inquiry’s Action menu.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
