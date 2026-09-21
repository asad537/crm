@extends('crm.layout')
@section('title', 'Add Vendor Purchase')
@section('content')
<style>
.vt-wrap{max-width:1100px;margin:0 auto}
.vt-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;padding:1.15rem 1.3rem;border:1px solid #e5ebf2;border-radius:16px;background:linear-gradient(135deg,var(--primary-soft),#fff 72%)}
.vt-hero h1{margin:0;color:#172033;font-size:1.25rem}.vt-hero p{margin:.25rem 0 0;color:#8290a3;font-size:.78rem}
.vt-card{padding:1.2rem 1.3rem;background:#fff;border:1px solid #e5ebf2;border-radius:16px;box-shadow:0 8px 26px rgba(15,23,42,.05)}
.vt-sec{display:flex;align-items:center;gap:.5rem;margin:.2rem 0 .85rem;color:#8a99ae;font-size:.7rem;font-weight:850;letter-spacing:.06em;text-transform:uppercase}.vt-sec:after{content:'';flex:1;height:1px;background:#e8edf3}
.vt-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:.85rem;margin-bottom:1rem}
.vt-f{grid-column:span 3;min-width:0}.vt-f.s4{grid-column:span 4}.vt-f.s6{grid-column:span 6}.vt-f.s12{grid-column:1/-1}
.vt-f label{display:block;margin-bottom:.35rem;color:#425168;font-size:.74rem;font-weight:750}.vt-req{color:#ef4444}.vt-opt{color:#9aa7b8;font-weight:600;font-size:.68rem}
.vt-control{width:100%;min-height:42px;padding:.6rem .75rem;border:1px solid #d8e1eb;border-radius:10px;background:#fff;color:#263449;font-size:.83rem;outline:0}
.vt-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}.vt-control[readonly]{background:#f5f7fa;color:#475569;font-weight:750}
.vt-badge{display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .7rem;border-radius:999px;font-size:.72rem;font-weight:850;background:var(--primary-soft);color:var(--primary-purple)}
.vt-sum{display:grid;grid-template-columns:repeat(3,1fr);gap:.7rem;padding:.9rem;border:1px solid var(--primary-shadow);border-radius:12px;background:var(--primary-soft);margin-top:.3rem}
.vt-sum span{display:block;color:#718096;font-size:.66rem;font-weight:800;text-transform:uppercase}.vt-sum strong{display:block;margin-top:.2rem;color:var(--primary-purple);font-size:1.05rem}
.vt-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.1rem}
.vt-btn{display:inline-flex;align-items:center;gap:.45rem;min-height:42px;padding:.6rem 1.1rem;border:0;border-radius:10px;font-weight:800;cursor:pointer;text-decoration:none}
.vt-btn-light{background:#eef2f7;color:#475569}.vt-btn-primary{background:var(--primary-purple);color:#fff;box-shadow:0 8px 18px var(--primary-shadow)}
.vt-hide{display:none!important}
.vt-errors{margin-bottom:1rem;padding:.8rem 1rem;border:1px solid #fecaca;border-radius:10px;background:#fff5f5;color:#b91c1c;font-size:.76rem}
@media(max-width:820px){.vt-f,.vt-f.s4{grid-column:span 6}.vt-sum{grid-template-columns:1fr}}
</style>

<div class="vt-wrap">
    <div class="vt-hero">
        <div><h1>Add Vendor Purchase</h1><p>Type-based entry — fields adapt to the vendor's type.</p></div>
        <a class="vt-btn vt-btn-light" href="{{ route('crm.vendor_purchases.index', $selectedVendorId ? ['vendor_id'=>$selectedVendorId] : []) }}"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    @if($errors->any())
        <div class="vt-errors"><strong>Please check the form:</strong><ul style="margin:.4rem 0 0 1rem">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form class="vt-card" method="POST" action="{{ route('crm.vendor_purchases.store_typed') }}" id="vtForm">
        {{ csrf_field() }}

        <div class="vt-sec"><i class="fas fa-truck"></i> Vendor & Invoice</div>
        <div class="vt-grid">
            <div class="vt-f s4"><label>Vendor <span class="vt-req">*</span></label>
                <select class="vt-control" name="vendor_id" id="vtVendor" onchange="vtVendorChanged()" required>
                    <option value="">Choose vendor</option>
                    @foreach($vendors as $v)<option value="{{ $v->id }}" data-vt="{{ $v->vendor_type }}" data-vtlabel="{{ $v->typeLabel() }}" {{ (int)old('vendor_id',$selectedVendorId)===$v->id?'selected':'' }}>{{ $v->name }} ({{ $v->typeLabel() }})</option>@endforeach
                </select>
            </div>
            <div class="vt-f"><label>Type</label><div style="padding-top:.35rem"><span class="vt-badge" id="vtTypeBadge"><i class="fas fa-tag"></i> —</span></div></div>
            <div class="vt-f"><label>Date <span class="vt-req">*</span></label><input class="vt-control" type="date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required></div>
            <div class="vt-f"><label>Vendor Invoice <span class="vt-opt">(opt)</span></label><input class="vt-control" name="invoice_number" value="{{ old('invoice_number') }}"></div>
            <div class="vt-f"><label>Job # <span class="vt-opt">(opt)</span></label><input class="vt-control" name="job_id" value="{{ old('job_id') }}"></div>
            <div class="vt-f s6"><label>Demand Request <span class="vt-opt">(opt)</span></label>
                <select class="vt-control" name="demand_id"><option value="">— none —</option>@foreach(($demandOptions ?? []) as $opt)<option value="{{ $opt['id'] }}" {{ (int)old('demand_id')===(int)$opt['id']?'selected':'' }}>{{ $opt['label'] }}</option>@endforeach</select>
            </div>
            <div class="vt-f" data-vt="paper ctp_plate die_making general"><label>GP Status <span class="vt-opt">(opt)</span></label><input class="vt-control" name="gp_status" value="{{ old('gp_status') }}" placeholder="Received / Pending"></div>
        </div>

        <div class="vt-sec"><i class="fas fa-list-ul"></i> Item Details</div>
        <div class="vt-grid">
            {{-- Description: common (main field for General) --}}
            <div class="vt-f s6" data-vt="paper ctp_plate die_making general"><label>Description</label><input class="vt-control" name="description" value="{{ old('description') }}" placeholder="Item / material description"></div>

            {{-- Paper --}}
            <div class="vt-f" data-vt="paper"><label>Paper Type</label><input class="vt-control" name="paper_type" value="{{ old('paper_type') }}"></div>
            <div class="vt-f" data-vt="paper"><label>GSM</label><input class="vt-control" name="gsm" value="{{ old('gsm') }}"></div>
            <div class="vt-f" data-vt="paper die_making"><label>Size</label><input class="vt-control" name="size" value="{{ old('size') }}" placeholder="e.g. 787x1000"></div>
            <div class="vt-f" data-vt="paper"><label>Unit</label><input class="vt-control" name="unit" value="{{ old('unit') }}" placeholder="Sheet / Kg / Ream"></div>

            {{-- CTP --}}
            <div class="vt-f" data-vt="ctp_plate"><label>Up / Imposition</label><input class="vt-control" name="up_imposition" value="{{ old('up_imposition') }}" placeholder="e.g. 4-up"></div>

            {{-- Die --}}
            <div class="vt-f" data-vt="die_making"><label>Colours</label><input class="vt-control" name="colours" value="{{ old('colours') }}"></div>

            {{-- Quantity: paper=Sheet Qty, ctp/die=Qty (hidden for general -> 1) --}}
            <div class="vt-f" data-vt="paper ctp_plate die_making"><label id="vtQtyLabel">Qty</label><input class="vt-control" type="number" step="0.01" min="0" name="quantity" id="vtQty" value="{{ old('quantity', 1) }}" oninput="vtCalc()"></div>

            {{-- Rate: paper/ctp/die = Rate; general = Amount --}}
            <div class="vt-f"><label id="vtRateLabel">Rate</label><input class="vt-control" type="number" step="0.0001" min="0" name="rate" id="vtRate" value="{{ old('rate') }}" oninput="vtCalc()"></div>

            {{-- GST: paper only --}}
            <div class="vt-f" data-vt="paper"><label>GST %</label><input class="vt-control" type="number" step="0.01" min="0" max="100" name="gst_percentage" id="vtGst" value="{{ old('gst_percentage', 0) }}" oninput="vtCalc()"></div>

            <div class="vt-f" data-vt="paper ctp_plate die_making general"><label>Deduction <span class="vt-opt">(opt)</span></label><input class="vt-control" type="number" step="0.01" min="0" name="deduction" value="{{ old('deduction', 0) }}"></div>
        </div>

        <div class="vt-sec"><i class="fas fa-money-bill-wave"></i> Payment</div>
        <div class="vt-grid">
            <div class="vt-f"><label>Paid Amount <span class="vt-opt">(opt)</span></label><input class="vt-control" type="number" step="0.01" min="0" name="paid_amount" value="{{ old('paid_amount', 0) }}"></div>
            <div class="vt-f"><label>Method</label><select class="vt-control" name="payment_method"><option value="">—</option>@foreach(['Cash','Bank Transfer','Card','Cheque','Credit'] as $m)<option value="{{ $m }}" {{ old('payment_method')===$m?'selected':'' }}>{{ $m }}</option>@endforeach</select></div>
            <div class="vt-f"><label>Currency</label><select class="vt-control" name="currency">@foreach(['AED','USD','GBP','EUR','PKR'] as $c)<option value="{{ $c }}" {{ old('currency','AED')===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select></div>
            <div class="vt-f s6">
                <div class="vt-sum">
                    <div><span>Amount</span><strong id="vtAmount">0.00</strong></div>
                    <div><span id="vtGstLabel">GST</span><strong id="vtGstAmt">0.00</strong></div>
                    <div><span>Total</span><strong id="vtTotal">0.00</strong></div>
                </div>
            </div>
        </div>

        <div class="vt-actions">
            <a class="vt-btn vt-btn-light" href="{{ route('crm.vendor_purchases.index', $selectedVendorId ? ['vendor_id'=>$selectedVendorId] : []) }}">Cancel</a>
            <button class="vt-btn vt-btn-primary" type="submit"><i class="fas fa-check"></i> Save Purchase</button>
        </div>
    </form>
</div>

<script>
function vtVendorChanged(){
    var sel=document.getElementById('vtVendor');
    var opt=sel.options[sel.selectedIndex];
    var vt=opt?opt.getAttribute('data-vt'):'';
    var lbl=opt?opt.getAttribute('data-vtlabel'):'';
    document.getElementById('vtTypeBadge').innerHTML='<i class="fas fa-tag"></i> '+(lbl||'—');
    // Show/hide fields by their data-vt list.
    document.querySelectorAll('#vtForm [data-vt]').forEach(function(el){
        if(el.tagName==='OPTION') return;
        var types=(el.getAttribute('data-vt')||'').split(/\s+/);
        el.classList.toggle('vt-hide', !(vt && types.indexOf(vt)!==-1));
    });
    // Labels + qty behaviour per type.
    var qtyLabel=document.getElementById('vtQtyLabel');
    var rateLabel=document.getElementById('vtRateLabel');
    var qty=document.getElementById('vtQty');
    if(vt==='paper'){ qtyLabel.textContent='Sheet Qty'; rateLabel.textContent='Rate'; }
    else if(vt==='general'){ rateLabel.textContent='Amount'; if(qty) qty.value=1; }
    else { qtyLabel.textContent='Qty'; rateLabel.textContent='Rate'; }
    vtCalc();
}
function vtCalc(){
    var qty=parseFloat((document.getElementById('vtQty')||{}).value)||1;
    var sel=document.getElementById('vtVendor');var opt=sel.options[sel.selectedIndex];var vt=opt?opt.getAttribute('data-vt'):'';
    if(vt==='general') qty=1;
    var rate=parseFloat((document.getElementById('vtRate')||{}).value)||0;
    var gst=parseFloat((document.getElementById('vtGst')||{}).value)||0;
    var amount=qty*rate;
    var gstAmt=amount*gst/100;
    var total=amount+gstAmt;
    var f=function(n){return n.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});};
    document.getElementById('vtAmount').textContent=f(amount);
    document.getElementById('vtGstAmt').textContent=f(gstAmt);
    document.getElementById('vtTotal').textContent=f(total);
}
document.addEventListener('DOMContentLoaded',function(){ vtVendorChanged(); });
</script>
@endsection
