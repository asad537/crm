@extends('crm.layout')
@section('title', 'Websites / Projects')
@section('content')
<style>
.ws-page{color:#233047;max-width:1100px}
.ws-hero{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.15rem 1.35rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px}
.ws-hero h2{margin:0;font-size:1.3rem}.ws-hero p{margin:.2rem 0 0;color:#8290a3;font-size:.78rem}
.ws-grid{display:grid;grid-template-columns:340px 1fr;gap:1rem}
.ws-card{background:#fff;border:1px solid #e5ebf2;border-radius:15px;padding:1.15rem;box-shadow:0 8px 24px rgba(15,23,42,.05)}
.ws-card h3{margin:0 0 .9rem;font-size:.95rem;color:#1e293b}
.ws-label{display:block;margin-bottom:.32rem;color:#536277;font-size:.7rem;font-weight:800}
.ws-control{width:100%;padding:.62rem .72rem;border:1.5px solid #dae3ed;border-radius:9px;box-sizing:border-box;outline:0;font-size:.85rem;margin-bottom:.7rem}
.ws-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.ws-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1rem;border:0;border-radius:10px;font-weight:800;font-size:.8rem;cursor:pointer;text-decoration:none;background:var(--primary-purple);color:#fff;box-shadow:0 6px 16px var(--primary-shadow)}
.ws-list{display:flex;flex-direction:column;gap:.5rem}
.ws-item{display:flex;align-items:center;gap:.7rem;padding:.7rem .9rem;border:1px solid #e8edf3;border-radius:11px;background:#fff}
.ws-item.off{opacity:.55;background:#f8fafc}
.ws-dot{width:12px;height:12px;border-radius:50%;flex:0 0 12px}
.ws-name{font-weight:750;color:#27364b;flex:1}
.ws-mini{padding:.4rem .6rem;border:0;border-radius:8px;font-weight:800;font-size:.68rem;cursor:pointer;text-decoration:none}
.ws-mini.tog{background:#eef2ff;color:var(--primary-purple)}.ws-mini.del{background:#fff1f2;color:#be123c}
.ws-badge{padding:.18rem .5rem;border-radius:999px;font-size:.62rem;font-weight:800}
.ws-badge.on{background:#e1f8ef;color:#047857}.ws-badge.offb{background:#f1f5f9;color:#64748b}
@media(max-width:820px){.ws-grid{grid-template-columns:1fr}}
</style>
<div class="ws-page">
    <div class="ws-hero"><div><h2>Websites / Projects</h2><p>Brands that appear in the inquiry “Website / Project” dropdown</p></div></div>
    @if($errors->any())<div style="background:#fff1f2;border:1px solid #fecaca;color:#9f1239;padding:.7rem 1rem;border-radius:10px;margin-bottom:.8rem">{{ $errors->first() }}</div>@endif

    <div class="ws-grid">
        <div class="ws-card" style="align-self:start">
            <h3><i class="fas fa-plus"></i> Add Website / Project</h3>
            <form method="POST" action="{{ route('crm.websites.store') }}">{{ csrf_field() }}
                <label class="ws-label">Name *</label>
                <input class="ws-control" name="name" placeholder="e.g. The Custom Boxes" required>
                <label class="ws-label">Color</label>
                <input class="ws-control" name="color" type="color" value="#6c5ce7" style="height:44px;padding:.25rem">
                <button class="ws-btn" type="submit"><i class="fas fa-check"></i> Add Website</button>
            </form>
        </div>
        <div class="ws-card">
            <h3>All Websites ({{ $websites->count() }})</h3>
            <div class="ws-list">
                @forelse($websites as $w)
                    <div class="ws-item {{ $w->is_active ? '' : 'off' }}">
                        <span class="ws-dot" style="background:{{ $w->color ?: '#6c5ce7' }}"></span>
                        <span class="ws-name">{{ $w->name }}</span>
                        <span class="ws-badge {{ $w->is_active ? 'on' : 'offb' }}">{{ $w->is_active ? 'Active' : 'Off' }}</span>
                        <form method="POST" action="{{ route('crm.websites.toggle', $w->id) }}" style="margin:0">{{ csrf_field() }}<button class="ws-mini tog" title="Toggle active"><i class="fas fa-power-off"></i></button></form>
                        <form method="POST" action="{{ route('crm.websites.destroy', $w->id) }}" style="margin:0" onsubmit="return confirm('Delete {{ $w->name }}?')">{{ csrf_field() }}{{ method_field('DELETE') }}<button class="ws-mini del" title="Delete"><i class="fas fa-trash"></i></button></form>
                    </div>
                @empty
                    <div style="text-align:center;padding:2rem;color:#94a3b8">No websites yet. Add one on the left.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
