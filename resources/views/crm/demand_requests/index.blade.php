@extends('crm.layout')
@section('title', 'Demand Requests')
@section('header_actions')
<a class="dr-btn dr-btn-primary" href="{{ route('crm.demand_requests.create') }}"><i class="fas fa-plus"></i> New Demand Request</a>
@endsection
@section('content')
<style>
.dr-wrap{max-width:100%;margin:0}
.dr-btn{display:inline-flex;align-items:center;gap:.45rem;min-height:40px;padding:.55rem 1rem;border:0;border-radius:10px;font-weight:800;text-decoration:none;cursor:pointer;font-size:.82rem}
.dr-btn-primary{color:#fff;background:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}
.dr-btn-light{color:#475569;background:#eef2f7}
.dr-cards{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.8rem;margin-bottom:1rem}
.dr-card{padding:1rem 1.1rem;background:#fff;border:1px solid #e5ebf2;border-radius:14px;box-shadow:0 8px 24px rgba(15,23,42,.05)}
.dr-card span{display:block;color:#8a99ae;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
.dr-card strong{display:block;margin-top:.3rem;color:#172033;font-size:1.4rem}
.dr-panel{background:#fff;border:1px solid #e5ebf2;border-radius:16px;box-shadow:0 8px 28px rgba(15,23,42,.05);overflow:hidden}
.dr-toolbar{display:flex;flex-wrap:wrap;gap:.6rem;align-items:center;padding:.9rem 1rem;border-bottom:1px solid #eef2f7}
.dr-control{min-height:40px;padding:.5rem .75rem;border:1px solid #d8e1eb;border-radius:10px;background:#fff;color:#263449;font-size:.82rem;outline:0}
.dr-search{flex:1;min-width:200px}
.dr-table{width:100%;border-collapse:collapse;font-size:.82rem}
.dr-table th{padding:.7rem .9rem;text-align:left;color:#8a99ae;font-size:.66rem;font-weight:850;text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid #eef2f7}
.dr-table td{padding:.75rem .9rem;border-bottom:1px solid #f2f5f9;color:#334155}
.dr-table tr:hover td{background:#f8fafc}
.dr-no{font-weight:850;color:var(--primary-purple)}.dr-num2{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.dr-badge{display:inline-flex;padding:.28rem .6rem;border-radius:999px;font-size:.66rem;font-weight:850;white-space:nowrap}
.dr-pri-Urgent{background:#fff1f2;color:#e11d48}.dr-pri-Normal{background:#eef2f7;color:#64748b}
.dr-st-Draft{background:#eef2f7;color:#64748b}.dr-st-Submitted{background:#fff7ed;color:#c2620c}
.dr-st-Approved{background:#e6f7e9;color:#159447}.dr-st-Rejected{background:#fff1f2;color:#e11d48}
.dr-st-Approved-alt{}.dr-st-Partially-Paid{background:#fef3c7;color:#b45309}.dr-st-Completed{background:#e6f7e9;color:#159447}
.dr-paybadge{display:inline-block;margin-left:.35rem;padding:.18rem .5rem;border-radius:999px;font-size:.6rem;font-weight:850;text-transform:uppercase;letter-spacing:.03em}
.dr-paybadge.is-paid{background:#dcfce7;color:#166534}.dr-paybadge.is-unpaid{background:#fee2e2;color:#b91c1c}.dr-paybadge.is-partial{background:#fef3c7;color:#b45309}
.dr-actions{display:flex;gap:.4rem}
.dr-ico{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:9px;cursor:pointer;text-decoration:none}
.dr-ico-edit{background:var(--primary-soft);color:var(--primary-purple)}.dr-ico-del{background:#fff1f2;color:#e11d48}
.dr-empty{padding:2.5rem;text-align:center;color:#8a99ae}
.dr-flash{margin-bottom:1rem;padding:.7rem 1rem;border:1px solid #bbf7d0;border-radius:10px;background:#f0fdf4;color:#15803d;font-size:.8rem;font-weight:700}
@media(max-width:1200px){.dr-cards{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:800px){.dr-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
<div class="dr-wrap">
    @if(session('status'))<div class="dr-flash"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>@endif

    <div class="dr-cards">
        <div class="dr-card"><span>Total Requests</span><strong>{{ number_format($summary['total']) }}</strong></div>
        <div class="dr-card"><span>Estimated Value</span><strong>{{ number_format($summary['estimated'], 2) }}</strong></div>
        <div class="dr-card"><span>Total Paid</span><strong style="color:#159447">{{ number_format($summary['paid'], 2) }}</strong></div>
        @php($__ao = $summary['account_out'] ?? 0)
        <div class="dr-card"><span>Account Outstanding</span><strong style="color:{{ $__ao < -0.009 ? '#e11d48' : '#159447' }}">@if($__ao < -0.009)&minus; {{ number_format(abs($__ao),2) }}@elseif($__ao > 0.009)+ {{ number_format($__ao,2) }}@else{{ number_format(0,2) }}@endif</strong></div>
        @php($__co = $summary['company_out'] ?? 0)
        <div class="dr-card"><span>Company Outstanding</span><strong style="color:{{ $__co < -0.009 ? '#e11d48' : '#159447' }}">@if($__co < -0.009)&minus; {{ number_format(abs($__co),2) }}@elseif($__co > 0.009)+ {{ number_format($__co,2) }}@else{{ number_format(0,2) }}@endif</strong></div>
        @php($__cih = $summary['cash_in_hand'] ?? 0)
        <div class="dr-card"><span>Cash in Hand Accounts</span><strong style="color:{{ $__cih < -0.009 ? '#e11d48' : '#159447' }}" title="Account outstanding of completed demands">@if($__cih < -0.009)&minus; {{ number_format(abs($__cih),2) }}@elseif($__cih > 0.009)+ {{ number_format($__cih,2) }}@else{{ number_format(0,2) }}@endif</strong></div>
    </div>

    <div class="dr-panel">
        <form method="GET" class="dr-toolbar">
            <input class="dr-control dr-search" name="search" value="{{ request('search') }}" autocomplete="off" placeholder="Search request no, requested by, item, job…" oninput="drSearchDebounced(this)">
            <select class="dr-control" name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(['Draft','Submitted','Approved','Rejected','Partially Paid','Completed'] as $st)
                    <option value="{{ $st }}" {{ request('status')===$st?'selected':'' }}>{{ $st }}</option>
                @endforeach
            </select>
            <select class="dr-control" name="priority" onchange="this.form.submit()">
                <option value="">All priorities</option>
                @foreach($priorities as $pr)
                    <option value="{{ $pr }}" {{ request('priority')===$pr?'selected':'' }}>{{ $pr }}</option>
                @endforeach
            </select>
            <button class="dr-btn dr-btn-light" type="submit"><i class="fas fa-search"></i> Search</button>
            <a class="dr-btn dr-btn-light" href="{{ route('crm.demand_requests.export') }}" style="margin-left:auto"><i class="fas fa-file-csv"></i> Export CSV</a>
        </form>

        <div style="overflow-x:auto">
        <table class="dr-table">
            <thead><tr>
                <th>No.</th><th>Date</th><th>Requested By</th><th>Priority</th><th>Items</th><th class="dr-num2">Est. Total</th><th class="dr-num2">Paid</th><th class="dr-num2">Acc. Out.</th><th class="dr-num2">Co. Out.</th><th>Status</th><th>Actions</th>
            </tr></thead>
            <tbody>
            @forelse($requests as $dr)
                <tr>
                    <td><span class="dr-no">#{{ str_pad($dr->request_no, 3, '0', STR_PAD_LEFT) }}</span></td>
                    <td>{{ optional($dr->request_date)->format('d M Y') }}</td>
                    <td>{{ $dr->requested_by ?: ($dr->creator->name ?? '—') }}</td>
                    <td><span class="dr-badge dr-pri-{{ $dr->priority }}">{{ $dr->priority }}</span></td>
                    <td>{{ $dr->items->count() }}</td>
                    <td class="dr-num2"><strong>{{ number_format($dr->estimated_total, 2) }}</strong></td>
                    <td class="dr-num2" style="color:#159447;font-weight:750">{{ number_format($dr->paidTotal(), 2) }}</td>
                    <!-- @php($__bal = round($dr->paidTotal() + $dr->writeOffTotal() - (float)$dr->estimated_total, 2))
                    <td class="dr-num2">
                        @if($__bal < -0.009)<span style="color:#e11d48;font-weight:850" title="Short / still to pay">&minus; {{ number_format(abs($__bal),2) }}</span>
                        @elseif($__bal > 0.009)<span style="color:#159447;font-weight:850" title="Overpaid">+ {{ number_format($__bal,2) }}</span>
                        @else<span style="color:#159447;font-weight:850" title="Fully settled">&#10004;</span>@endif
                    </td> -->
                    @php($__ao = $dr->accountOutstanding())
                    @php($__co = $dr->companyOutstanding())
                    <td class="dr-num2" style="font-weight:800;color:{{ $__ao < -0.009 ? '#e11d48' : '#159447' }}" title="Account balance">@if($__ao < -0.009)&minus; {{ number_format(abs($__ao),2) }}@elseif($__ao > 0.009)+ {{ number_format($__ao,2) }}@else&#10004;@endif</td>
                    <td class="dr-num2" style="font-weight:800;color:{{ $__co < -0.009 ? '#e11d48' : '#159447' }}" title="Company balance">@if($__co < -0.009)&minus; {{ number_format(abs($__co),2) }}@elseif($__co > 0.009)+ {{ number_format($__co,2) }}@else&#10004;@endif</td>
                    <td>
                        <span class="dr-badge dr-st-{{ str_replace([' ','/'],['-','-'],$dr->status) }}">{{ $dr->status }}</span>
                        @php($__pay = $dr->paymentStatus())
                        @if($__pay)
                        <span class="dr-paybadge {{ $__pay === 'Paid' ? 'is-paid' : ($__pay === 'Partial' ? 'is-partial' : 'is-unpaid') }}">{{ $__pay }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="dr-actions">
                            <a class="dr-ico dr-ico-edit" href="{{ route('crm.demand_requests.show', $dr->id) }}" title="Open"><i class="fas fa-eye"></i></a>
                            <form method="POST" action="{{ route('crm.demand_requests.destroy', $dr->id) }}" onsubmit="return confirm('Delete this demand request?');">
                                {{ csrf_field() }} {{ method_field('DELETE') }}
                                <button class="dr-ico dr-ico-del" type="submit" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12"><div class="dr-empty"><i class="fas fa-clipboard-list" style="font-size:2rem;display:block;margin-bottom:.6rem"></i>No demand requests yet. Click “New Demand Request” to create one.</div></td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div style="padding:.8rem 1rem">{{ $requests->links() }}</div>
    </div>
</div>
<script>
    var drSearchTimer=null;
    function drSearchDebounced(input){clearTimeout(drSearchTimer);drSearchTimer=setTimeout(function(){var f=input.form;if(!f)return;var pg=f.querySelector('[name="page"]');if(pg)pg.remove();f.submit();},500);}
    // Keep the cursor in the search box after the page reloads with a value.
    document.addEventListener('DOMContentLoaded',function(){var i=document.querySelector('.dr-search');if(i&&i.value){i.focus();var v=i.value;i.value='';i.value=v;}});
</script>
@endsection
