@extends('crm.layout')
@section('title', 'Deletion Logs')
@section('content')
@php
    $tabs = [
        ''                => ['All',     $counts['total']],
        'invoice'         => ['Invoices', $counts['invoice']],
        'vendor_purchase' => ['Vendor Purchases', $counts['vendor_purchase']],
        'vendor'          => ['Vendors', $counts['vendor']],
    ];
    $active = request('entity_type', '');
    $typeStyles = [
        'invoice'         => ['var(--primary-purple)', 'var(--primary-soft)', 'fa-file-invoice'],
        'vendor_purchase' => ['#0e7490', '#dff4f8', 'fa-shopping-cart'],
        'vendor'          => ['#b45309', '#fdf0d5', 'fa-truck'],
    ];
@endphp
<style>
    .dl-page { max-width: 1180px; margin: 0 auto; }
    .dl-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:.9rem; margin: .2rem 0 1.3rem; }
    @media(max-width:640px){ .dl-stats{grid-template-columns:1fr 1fr} }
    .dl-stat { background:#fff; border:1px solid #ecedf1; border-radius:14px; padding:1rem 1.15rem; box-shadow:0 1px 2px rgba(20,23,33,.03); }
    .dl-stat .n { font-size:1.5rem; font-weight:800; letter-spacing:-.02em; line-height:1; color:#1a1d24; }
    .dl-stat .l { font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#8a909c; margin-top:.45rem; font-weight:600; }
    .dl-stat.accent .n { color: var(--primary-purple); }
    .dl-toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1.1rem; }
    .dl-chips { display:flex; gap:.4rem; flex-wrap:wrap; }
    .dl-chip { display:inline-flex; align-items:center; gap:.4rem; font-size:.76rem; font-weight:600; text-decoration:none;
        color:#5b616e; background:#fff; border:1px solid #ecedf1; padding:.42rem .8rem; border-radius:999px; transition:all .14s; }
    .dl-chip:hover { border-color: var(--primary-purple); color: var(--primary-purple); background: var(--primary-soft); }
    .dl-chip.active { background: var(--primary-purple); border-color: var(--primary-purple); color:#fff; box-shadow: 0 6px 14px var(--primary-shadow); }
    .dl-chip.active:hover { color:#fff; background: var(--primary-hover); border-color: var(--primary-hover); }
    .dl-chip .c { font-size:.68rem; font-weight:700; min-width:1.15rem; height:1.15rem; padding:0 .3rem; border-radius:999px;
        display:inline-grid; place-items:center; background:#f4f5f7; color:#6b7280; font-variant-numeric:tabular-nums; }
    .dl-chip.active .c { background:rgba(255,255,255,.22); color:#fff; }
    .dl-search { position:relative; }
    .dl-search i { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:#b3b8c2; font-size:.82rem; }
    .dl-search input { border:1px solid #ecedf1; border-radius:10px; padding:.6rem .8rem .6rem 2.1rem; font-size:.85rem;
        font-family:inherit; outline:none; min-width:260px; background:#fff; transition:all .13s; }
    .dl-search input:focus { border-color:var(--primary-purple); box-shadow:0 0 0 3px var(--primary-shadow); }
    .dl-card { background:#fff; border:1px solid #ecedf1; border-radius:16px; overflow:hidden;
        box-shadow:0 1px 3px rgba(20,23,33,.04), 0 18px 40px -30px rgba(20,23,33,.25); }
    .dl-wrap { overflow-x:auto; }
    .dl-table { width:100%; border-collapse:collapse; min-width:820px; }
    .dl-table thead th { background:#fafafb; font-size:.66rem; text-transform:uppercase; letter-spacing:.09em;
        font-weight:700; color:#9096a1; text-align:left; padding:.85rem 1.15rem; border-bottom:1px solid #ecedf1; }
    .dl-table tbody td { padding:1rem 1.15rem; border-bottom:1px solid #ecedf1; font-size:.88rem; vertical-align:middle; }
    .dl-table tbody tr:last-child td { border-bottom:none; }
    .dl-table tbody tr:hover { background:#fcfcfd; }
    .dl-type { display:inline-flex; align-items:center; gap:.4rem; padding:.28rem .58rem; border-radius:999px; font-size:.7rem; font-weight:700; letter-spacing:.02em; }
    .dl-label { font-weight:700; color:#1a1d24; }
    .dl-sub { font-size:.72rem; color:#8a909c; margin-top:.2rem; }
    .dl-user { display:flex; align-items:center; gap:.55rem; }
    .dl-ava { width:30px; height:30px; border-radius:50%; display:grid; place-items:center; font-size:.7rem; font-weight:700;
        background:var(--primary-soft); color:var(--primary-purple); flex:0 0 30px; }
    .dl-empty { text-align:center; padding:3.5rem 1rem; color:#8a909c; font-size:.9rem; }
    .dl-pagination { padding:1rem; display:flex; justify-content:center; }
    .dl-snap { max-width:340px; }
    .dl-snap details { cursor:pointer; }
    .dl-snap summary { font-size:.75rem; color:var(--primary-purple); font-weight:600; }
    .dl-snap pre { margin:.5rem 0 0; font-size:.72rem; background:#f8fafc; border:1px solid #ecedf1; border-radius:8px; padding:.6rem; white-space:pre-wrap; word-break:break-word; color:#334155; }
</style>

<div class="dl-page">
    <div class="dl-stats">
        <div class="dl-stat"><div class="n">{{ $counts['total'] }}</div><div class="l">Total Deletions</div></div>
        <div class="dl-stat accent"><div class="n">{{ $counts['invoice'] }}</div><div class="l">Invoices</div></div>
        <div class="dl-stat"><div class="n">{{ $counts['vendor_purchase'] }}</div><div class="l">Vendor Purchases</div></div>
        <div class="dl-stat"><div class="n">{{ $counts['vendor'] }}</div><div class="l">Vendors</div></div>
    </div>

    <div class="dl-toolbar">
        <div class="dl-chips">
            @foreach($tabs as $key => [$label, $count])
                <a class="dl-chip {{ $active === (string) $key ? 'active' : '' }}"
                    href="{{ route('crm.deletion_logs.index', array_merge(request()->except(['entity_type','page']), $key !== '' ? ['entity_type' => $key] : [])) }}">
                    {{ $label }}<span class="c">{{ $count }}</span>
                </a>
            @endforeach
        </div>
        <form class="dl-search" method="GET" action="{{ route('crm.deletion_logs.index') }}">
            @if($active !== '')<input type="hidden" name="entity_type" value="{{ $active }}">@endif
            <i class="fas fa-search"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Search label or user…" oninput="if(!this.value){this.form.submit()}">
        </form>
    </div>

    <div class="dl-card">
        <div class="dl-wrap">
        <table class="dl-table">
            <thead><tr>
                <th>Type</th>
                <th>Deleted Item</th>
                <th>Deleted By</th>
                <th>When</th>
                <th>Details</th>
            </tr></thead>
            <tbody>
            @php
                $initials = function ($name) {
                    $name = trim((string) $name);
                    if ($name === '') return '?';
                    $p = preg_split('/\s+/', $name);
                    return mb_strtoupper(mb_substr($p[0], 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : ''));
                };
            @endphp
            @forelse($logs as $log)
                @php [$fg, $bg, $icon] = $typeStyles[$log->entity_type] ?? ['#4b5563', '#eef0f2', 'fa-trash']; @endphp
                <tr>
                    <td><span class="dl-type" style="color:{{ $fg }};background:{{ $bg }}"><i class="fas {{ $icon }}"></i> {{ $log->entityLabelPretty() }}</span></td>
                    <td>
                        <div class="dl-label">{{ $log->entity_label ?: '#'.$log->entity_id }}</div>
                        <div class="dl-sub">ID: {{ $log->entity_id }}</div>
                    </td>
                    <td>
                        <div class="dl-user">
                            <span class="dl-ava">{{ $initials($log->user_name) }}</span>
                            <div>
                                <div style="font-weight:600">{{ $log->user_name ?: '—' }}</div>
                                @if($log->user_role)<div class="dl-sub" style="margin-top:.05rem">{{ $log->user_role }}</div>@endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>{{ $log->created_at->format('d M Y') }}</div>
                        <div class="dl-sub">{{ $log->created_at->format('h:i A') }} · {{ $log->created_at->diffForHumans() }}</div>
                    </td>
                    <td class="dl-snap">
                        @if($log->snapshot)
                            <details><summary>View snapshot</summary><pre>{{ json_encode($log->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>
                        @else — @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="dl-empty">No deletions recorded yet.</div></td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        @if($logs->hasPages())<div class="dl-pagination">{{ $logs->links() }}</div>@endif
    </div>
</div>
@endsection
