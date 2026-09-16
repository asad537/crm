@php
    $__drUser = \Auth::guard('crm')->user();
    $__drShow = $__drUser && ($__drUser->isAdmin() || $__drUser->isSalesManager() || $__drUser->isAccounts());
    $__drPending = 0;
    $__drOpen = 0;
    $__drOutstanding = 0;
    if ($__drShow) {
        try {
            $__drPending = \App\DemandRequest::where('status', 'Submitted')->count();
            $__drOpen = \App\DemandRequest::whereIn('status', ['Approved', 'Partially Paid'])->count();
            $__est = (float) \App\DemandRequest::sum('estimated_total');
            $__paid = (float) \App\DemandRequestPayment::whereHas('request')->sum('amount');
            $__drOutstanding = round($__est - $__paid, 2);
        } catch (\Throwable $e) {
            $__drShow = false;
        }
    }
@endphp
@if($__drShow)
<a href="{{ route('crm.demand_requests.index') }}" style="text-decoration:none;display:block;margin-bottom:18px">
  <div style="display:flex;flex-wrap:wrap;align-items:center;gap:14px;padding:16px 20px;border:1px solid #e5ebf2;border-radius:16px;background:linear-gradient(135deg,#f5f3ff,#fff 70%);box-shadow:0 8px 24px rgba(15,23,42,.05)">
    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:180px">
      <span style="width:42px;height:42px;border-radius:12px;background:var(--primary-purple);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem"><i class="fas fa-clipboard-list"></i></span>
      <div>
        <div style="font-weight:850;color:#172033;font-size:.95rem">Demand Requests</div>
        <div style="color:#8290a3;font-size:.72rem">Procurement requests &amp; payments</div>
      </div>
    </div>
    <div style="text-align:center;min-width:90px">
      <div style="font-size:1.4rem;font-weight:850;color:{{ $__drPending>0?'#c2620c':'#159447' }}">{{ $__drPending }}</div>
      <div style="font-size:.63rem;font-weight:800;text-transform:uppercase;color:#8a99ae">Pending Approval</div>
    </div>
    <div style="text-align:center;min-width:70px">
      <div style="font-size:1.4rem;font-weight:850;color:#0891b2">{{ $__drOpen }}</div>
      <div style="font-size:.63rem;font-weight:800;text-transform:uppercase;color:#8a99ae">Open</div>
    </div>
    <div style="text-align:center;min-width:110px">
      <div style="font-size:1.4rem;font-weight:850;color:{{ $__drOutstanding>0?'#e11d48':'#159447' }}">{{ number_format($__drOutstanding,2) }}</div>
      <div style="font-size:.63rem;font-weight:800;text-transform:uppercase;color:#8a99ae">Outstanding</div>
    </div>
    <span style="color:var(--primary-purple);font-weight:800;font-size:.8rem;white-space:nowrap">Open <i class="fas fa-arrow-right"></i></span>
  </div>
</a>
@endif
