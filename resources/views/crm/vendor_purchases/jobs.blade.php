@extends('crm.layout')
@section('title', 'Job Expenses')
@section('content')
@php $fmt = function ($v) { return number_format((float) $v, 2); }; @endphp

<style>
    .jb-page { --ink:#0f172a; --body:#334155; --muted:#64748b; --faint:#94a3b8; --line:#e2e8f0; --line-soft:#f1f5f9; --well:#f8fafc;
        --brand:var(--primary-purple,var(--primary-purple)); --brand-deep:var(--primary-hover,#5848d8); --brand-soft:var(--primary-soft,var(--primary-soft));
        --green:#15803d; --green-soft:#ecfdf3; --red:#b45309; --red-soft:#fffbeb;
        max-width:1200px; margin:0 auto; color:var(--body); font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; }
    .jb-page *, .jb-page *::before, .jb-page *::after { box-sizing:border-box; }
    .jb-head { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1.1rem; }
    .jb-tabs { display:inline-flex; background:#fff; border:1px solid var(--line); border-radius:12px; padding:4px; gap:4px; box-shadow:0 1px 2px rgba(15,23,42,.05); }
    .jb-tab { display:inline-flex; align-items:center; gap:.5rem; padding:.58rem 1.1rem; border-radius:9px; color:var(--muted); text-decoration:none; font-weight:700; font-size:.84rem; transition:all .15s; }
    .jb-tab:hover { color:var(--ink); background:var(--well); }
    .jb-tab.active { background:var(--brand); color:#fff; }
    .jb-back { display:inline-flex; align-items:center; gap:.4rem; font-size:.8rem; font-weight:600; color:var(--muted); text-decoration:none; background:#fff; border:1px solid var(--line); padding:.45rem .9rem; border-radius:999px; }
    .jb-back:hover { color:var(--ink); border-color:var(--faint); }
    .jb-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:1rem; margin-bottom:1.1rem; }
    .jb-stat { background:#fff; border:1px solid var(--line); border-radius:16px; padding:1.1rem 1.25rem; box-shadow:0 1px 2px rgba(15,23,42,.04),0 10px 28px -22px rgba(15,23,42,.25); }
    .jb-stat .k { font-size:.64rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--faint); }
    .jb-stat .v { font-size:1.5rem; font-weight:800; color:var(--ink); margin-top:.45rem; font-variant-numeric:tabular-nums; }
    .jb-stat .v small { font-size:.62rem; color:var(--muted); font-weight:700; }
    .jb-stat.green .v { color:var(--green); } .jb-stat.red .v { color:var(--red); }
    .jb-toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1rem; }
    .jb-search { display:flex; gap:.5rem; }
    .jb-search input { border:1px solid var(--line); border-radius:10px; padding:.6rem .85rem; font-size:.84rem; font-family:inherit; outline:none; min-width:240px; background:#fff; }
    .jb-search input:focus { border-color:var(--brand); box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 10%,transparent); }
    .jb-search button { border:none; background:var(--brand); color:#fff; border-radius:10px; padding:.6rem 1.25rem; font-size:.82rem; font-weight:700; font-family:inherit; cursor:pointer; transition:background .15s; }
    .jb-search button:hover { background:var(--brand-deep); }
    .jb-card { background:#fff; border:1px solid var(--line); border-radius:16px; overflow:hidden; box-shadow:0 1px 2px rgba(15,23,42,.04),0 12px 32px -24px rgba(15,23,42,.3); }
    .jb-wrap { overflow-x:auto; }
    .jb-table { width:100%; border-collapse:collapse; min-width:760px; }
    .jb-table thead th { font-size:.63rem; text-transform:uppercase; letter-spacing:.09em; font-weight:800; color:var(--muted); text-align:left; padding:.8rem 1.15rem; background:var(--well); border-bottom:1px solid var(--line); }
    .jb-table th.r, .jb-table td.r { text-align:right; }
    .jb-table tbody td { padding:.85rem 1.15rem; border-bottom:1px solid var(--line-soft); font-size:.87rem; vertical-align:middle; }
    .jb-table tbody tr:last-child td { border-bottom:none; }
    .jb-table tbody tr.link { cursor:pointer; transition:background .12s; }
    .jb-table tbody tr.link:hover { background:color-mix(in srgb,var(--brand) 4%,white); }
    .jb-jobid { display:inline-flex; align-items:center; gap:.5rem; font-weight:800; color:var(--ink); }
    .jb-jobid i { color:var(--brand); }
    .jb-num { font-variant-numeric:tabular-nums; font-weight:700; color:var(--ink); white-space:nowrap; }
    .jb-num.green { color:var(--green); } .jb-num.red { color:var(--red); }
    .jb-badge { display:inline-flex; align-items:center; gap:.35rem; font-size:.66rem; font-weight:800; text-transform:uppercase; padding:.32rem .65rem; border-radius:999px; white-space:nowrap; }
    .jb-badge.ok { color:var(--green); background:var(--green-soft); } .jb-badge.due { color:var(--red); background:var(--red-soft); }
    .jb-muted { color:var(--muted); font-size:.76rem; }
    .jb-empty { text-align:center; padding:3.5rem 1rem; color:var(--muted); }
    .jb-empty i { font-size:1.9rem; color:var(--faint); display:block; margin-bottom:.7rem; }
    .jb-open { color:var(--brand); font-weight:800; font-size:.75rem; text-decoration:none; white-space:nowrap; }
    .jb-pagination { padding:1rem; display:flex; justify-content:center; border-top:1px solid var(--line-soft); }
    .jb-pagination ul,.jb-pagination .pagination { display:flex; flex-wrap:wrap; gap:.3rem; list-style:none; margin:0; padding:0; align-items:center; }
    .jb-pagination li>*,.jb-pagination a,.jb-pagination span { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 .5rem; border:1px solid var(--line); border-radius:8px; text-decoration:none; color:var(--body); font-size:.82rem; font-weight:600; background:#fff; }
    .jb-pagination a:hover { border-color:var(--brand); color:var(--brand); }
    .jb-pagination .active>*,.jb-pagination [aria-current]>* { background:var(--brand); border-color:var(--brand); color:#fff; }
    /* single-job banner */
    .jb-jobbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; padding:1.05rem 1.4rem; margin-bottom:1rem; background:linear-gradient(135deg,var(--brand-soft),#fff); border:1px solid var(--primary-shadow,#ddd); border-radius:16px; }
    .jb-jobbar .amt { display:flex; gap:1.8rem; flex-wrap:wrap; }
    .jb-jobbar .amt .k { font-size:.6rem; text-transform:uppercase; color:var(--faint); font-weight:850; }
    .jb-jobbar .amt .v { font-size:1.2rem; font-weight:850; color:var(--ink); font-variant-numeric:tabular-nums; }
</style>

<div class="jb-page">
    <div class="jb-head">
        <div class="jb-tabs">
            <a class="jb-tab" href="{{ route('crm.vendor_purchases.index') }}"><i class="fas fa-truck-loading"></i> Vendors</a>
            <a class="jb-tab active" href="{{ route('crm.vendor_purchases.jobs') }}"><i class="fas fa-briefcase"></i> Job Expenses</a>
        </div>
        @if($selectedJob !== '')
            <a class="jb-back" href="{{ route('crm.vendor_purchases.jobs') }}">&larr; All Jobs</a>
        @endif
    </div>

    {{-- ── Single job: its purchases only ── --}}
    @if($selectedJob !== '')
        <div class="jb-jobbar">
            <div style="display:flex;align-items:center;gap:.85rem;">
                <span style="width:46px;height:46px;display:grid;place-items:center;border-radius:13px;background:var(--brand);color:#fff;font-size:1.1rem;flex:none;"><i class="fas fa-briefcase"></i></span>
                <div>
                    <div style="font-size:.64rem;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);font-weight:850;">Job Expense</div>
                    <div style="font-size:1.15rem;font-weight:850;color:var(--ink);">{{ $jobSummary['job_id'] }}</div>
                    <div class="jb-muted">{{ $jobSummary['count'] }} purchase(s) &middot; {{ $jobSummary['vendors'] }} vendor(s)</div>
                </div>
            </div>
            <div class="amt">
                <div><div class="k">Total</div><div class="v">{{ $fmt($jobSummary['total']) }} <small style="font-size:.7rem;color:var(--faint)">{{ $jobSummary['currency'] }}</small></div></div>
                <div><div class="k">Paid</div><div class="v" style="color:var(--green)">{{ $fmt($jobSummary['paid']) }}</div></div>
                <div><div class="k">Balance</div><div class="v" style="color:var(--red)">{{ $fmt($jobSummary['balance']) }}</div></div>
            </div>
        </div>

        <div class="jb-card">
            <div class="jb-wrap">
            <table class="jb-table">
                <thead><tr><th>Date</th><th>Vendor</th><th>Invoice #</th><th>Item</th><th class="r">Qty</th><th class="r">Total</th><th class="r">Paid</th><th class="r">Balance</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($purchases as $p)
                    @php $paid = strtolower($p->payment_status) === 'paid' || (float)$p->balance_amount <= 0; @endphp
                    <tr>
                        <td class="jb-muted">{{ optional($p->purchase_date)->format('d M Y') }}</td>
                        <td><strong>{{ $p->vendor_name ?: (optional($p->vendor)->name ?: '—') }}</strong></td>
                        <td class="jb-muted">{{ $p->invoice_number ?: '—' }}</td>
                        <td>{{ $p->items->pluck('item_name')->filter()->implode(', ') ?: ($p->item_name ?: '—') }}</td>
                        <td class="r jb-num">{{ number_format($p->quantity) }}</td>
                        <td class="r jb-num">{{ $fmt($p->total_amount) }}</td>
                        <td class="r jb-num green">{{ $fmt($p->paid_amount) }}</td>
                        <td class="r jb-num {{ (float)$p->balance_amount > 0 ? 'red' : '' }}">{{ $fmt($p->balance_amount) }}</td>
                        <td>@if($paid)<span class="jb-badge ok">Paid</span>@else<span class="jb-badge due">{{ $p->payment_status ?: 'Unpaid' }}</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="jb-empty"><i class="fas fa-box-open"></i>No purchases recorded for this job.</div></td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
            @if($purchases->hasPages())<div class="jb-pagination">{{ $purchases->links() }}</div>@endif
        </div>

    {{-- ── All jobs: totals ── --}}
    @else
        <div class="jb-stats">
            <div class="jb-stat"><div class="k">Total Jobs</div><div class="v">{{ number_format($overall['jobs']) }}</div></div>
            <div class="jb-stat"><div class="k">Total Expense</div><div class="v">{{ $fmt($overall['total']) }} <small>AED</small></div></div>
            <div class="jb-stat green"><div class="k">Paid</div><div class="v">{{ $fmt($overall['paid']) }} <small>AED</small></div></div>
            <div class="jb-stat red"><div class="k">Balance</div><div class="v">{{ $fmt($overall['balance']) }} <small>AED</small></div></div>
        </div>

        <div class="jb-toolbar">
            <div class="jb-muted">Every job with its total vendor purchase expense. Click a job to see its purchases.</div>
            <form class="jb-search" method="GET" action="{{ route('crm.vendor_purchases.jobs') }}">
                <input name="search" value="{{ $search }}" placeholder="Search Job ID…" autocomplete="off" oninput="jbLiveSearch(this.value)">
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="jb-card">
            <div class="jb-wrap">
            <table class="jb-table">
                <thead><tr><th>Job ID</th><th class="r">Purchases</th><th class="r">Vendors</th><th class="r">Total Expense</th><th class="r">Paid</th><th class="r">Balance</th><th>Last Purchase</th><th></th></tr></thead>
                <tbody>
                @forelse($jobGroups as $j)
                    <tr class="link" data-jobsearch="{{ strtolower($j->job_id) }}" onclick="window.location='{{ route('crm.vendor_purchases.jobs', ['job_id' => $j->job_id]) }}'">
                        <td><span class="jb-jobid"><i class="fas fa-briefcase"></i> {{ $j->job_id }}</span></td>
                        <td class="r jb-num">{{ number_format($j->count) }}</td>
                        <td class="r jb-num">{{ number_format($j->vendors) }}</td>
                        <td class="r jb-num">{{ $fmt($j->total) }} <small style="color:var(--faint);font-size:.7rem">{{ $j->currency }}</small></td>
                        <td class="r jb-num green">{{ $fmt($j->paid) }}</td>
                        <td class="r jb-num {{ $j->balance > 0 ? 'red' : '' }}">{{ $fmt($j->balance) }}</td>
                        <td class="jb-muted">{{ optional($j->last_date)->format('d M Y') }}</td>
                        <td class="r"><a class="jb-open" href="{{ route('crm.vendor_purchases.jobs', ['job_id' => $j->job_id]) }}">View &rarr;</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="jb-empty"><i class="fas fa-briefcase"></i>No jobs yet. Add a Job ID when recording a vendor purchase.</div></td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    @endif
</div>
<script>
function jbLiveSearch(term){
    term=(term||'').trim().toLowerCase();
    document.querySelectorAll('.jb-table tbody tr[data-jobsearch]').forEach(function(row){
        var hay=row.getAttribute('data-jobsearch')||'';
        row.style.display=(term===''||hay.indexOf(term)!==-1)?'':'none';
    });
}
</script>
@endsection
