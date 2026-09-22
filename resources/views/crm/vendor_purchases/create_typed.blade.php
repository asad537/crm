@extends('crm.layout')
@section('title', 'Add Vendor Purchase')
@section('content')
<style>
.vt-wrap{max-width:1150px;margin:0 auto}
.vt-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;padding:1.15rem 1.3rem;border:1px solid #e5ebf2;border-radius:16px;background:linear-gradient(135deg,var(--primary-soft),#fff 72%)}
.vt-hero h1{margin:0;color:#172033;font-size:1.25rem}.vt-hero p{margin:.25rem 0 0;color:#8290a3;font-size:.78rem}
.vt-card{padding:1.2rem 1.3rem;background:#fff;border:1px solid #e5ebf2;border-radius:16px;box-shadow:0 8px 26px rgba(15,23,42,.05)}
.vt-sec{display:flex;align-items:center;gap:.5rem;margin:.2rem 0 .85rem;color:#8a99ae;font-size:.7rem;font-weight:850;letter-spacing:.06em;text-transform:uppercase}.vt-sec:after{content:'';flex:1;height:1px;background:#e8edf3}
.vt-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:.85rem;margin-bottom:1rem}
.vt-f{grid-column:span 3;min-width:0}.vt-f.s4{grid-column:span 4}.vt-f.s6{grid-column:span 6}.vt-f.s12{grid-column:1/-1}
.vt-f label{display:block;margin-bottom:.35rem;color:#425168;font-size:.74rem;font-weight:750}.vt-req{color:#ef4444}.vt-opt{color:#9aa7b8;font-weight:600;font-size:.68rem}
.vt-control{width:100%;min-height:42px;padding:.6rem .75rem;border:1px solid #d8e1eb;border-radius:10px;background:#fff;color:#263449;font-size:.83rem;outline:0}
.vt-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.vt-badge{display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .7rem;border-radius:999px;font-size:.72rem;font-weight:850;background:var(--primary-soft);color:var(--primary-purple)}
.vt-sum{display:grid;grid-template-columns:repeat(3,1fr);gap:.7rem;padding:.9rem;border:1px solid var(--primary-shadow);border-radius:12px;background:var(--primary-soft);margin-top:.3rem}
.vt-sum span{display:block;color:#718096;font-size:.66rem;font-weight:800;text-transform:uppercase}.vt-sum strong{display:block;margin-top:.2rem;color:var(--primary-purple);font-size:1.05rem}
.vt-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.1rem}
.vt-btn{display:inline-flex;align-items:center;gap:.45rem;min-height:42px;padding:.6rem 1.1rem;border:0;border-radius:10px;font-weight:800;cursor:pointer;text-decoration:none}
.vt-btn-light{background:#eef2f7;color:#475569}.vt-btn-primary{background:var(--primary-purple);color:#fff;box-shadow:0 8px 18px var(--primary-shadow)}
.vt-btn-outline{color:var(--primary-purple);border:1px solid var(--primary-shadow);background:var(--primary-soft)}
.vt-hide{display:none!important}
.vt-item{position:relative;padding:1rem;border:1px solid #e2e8f0;border-radius:14px;background:#fbfcfe;margin-bottom:.9rem}
.vt-item-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:.7rem}
.vt-item-no{display:inline-flex;align-items:center;gap:.5rem;color:#27364b;font-weight:850;font-size:.85rem}
.vt-item-no span{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:8px;background:var(--primary-soft);color:var(--primary-purple);font-size:.8rem}
.vt-rm{width:32px;height:32px;border:0;border-radius:9px;background:#fff1f2;color:#e11d48;cursor:pointer}
.vt-errors{margin-bottom:1rem;padding:.8rem 1rem;border:1px solid #fecaca;border-radius:10px;background:#fff5f5;color:#b91c1c;font-size:.76rem}
@media(max-width:820px){.vt-f,.vt-f.s4{grid-column:span 6}.vt-sum{grid-template-columns:1fr}}
</style>

<div class="vt-wrap">
    <div class="vt-hero">
        <div><h1>Add Vendor Purchase</h1><p>Type-based entry — fields adapt to the vendor's type. Add multiple items.</p></div>
        <a class="vt-btn vt-btn-light" href="{{ route('crm.vendor_purchases.index', $selectedVendorId ? ['vendor_id'=>$selectedVendorId] : []) }}"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    @if($errors->any())
        <div class="vt-errors"><strong>Please check the form:</strong><ul style="margin:.4rem 0 0 1rem">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form class="vt-card" method="POST" action="{{ route('crm.vendor_purchases.store_typed') }}" id="vtForm" enctype="multipart/form-data">
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
            <div class="vt-f"><label>Expense Type <span class="vt-req">*</span></label>
                <select class="vt-control" name="expense_type" required>
                    @foreach(['Production Expense'=>'Production','Consumable Expense'=>'Consumable','Admin/General Expense'=>'Admin/General'] as $etv=>$etl)
                        <option value="{{ $etv }}" {{ old('expense_type','Production Expense')===$etv?'selected':'' }}>{{ $etl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="vt-f"><label>GP Status <span class="vt-opt">(opt)</span></label><input class="vt-control" name="gp_status" value="{{ old('gp_status') }}" placeholder="Received / Pending"></div>
        </div>

        <div class="vt-sec"><i class="fas fa-list-ul"></i> Item Details</div>
        <div id="vtItems"></div>
        <button type="button" class="vt-btn vt-btn-outline" onclick="vtAddItem()" style="margin-bottom:1rem"><i class="fas fa-plus"></i> Add Another Item</button>

        <div class="vt-sec"><i class="fas fa-paperclip"></i> Attachment</div>
        <div class="vt-grid">
            <div class="vt-f s6"><label>Attach file <span class="vt-opt">(invoice / proof, optional)</span></label><input class="vt-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv" style="padding:.45rem"></div>
        </div>

        <div class="vt-sec"><i class="fas fa-money-bill-wave"></i> Payment</div>
        <div class="vt-grid">
            <div class="vt-f"><label>Deduction <span class="vt-opt">(opt)</span></label><input class="vt-control" type="number" step="0.01" min="0" name="deduction" value="{{ old('deduction', 0) }}"></div>
            <div class="vt-f"><label>Paid Amount <span class="vt-opt">(opt)</span></label><input class="vt-control" type="number" step="0.01" min="0" name="paid_amount" value="{{ old('paid_amount', 0) }}"></div>
            <div class="vt-f"><label>Method</label><select class="vt-control" name="payment_method"><option value="">—</option>@foreach(['Cash','Bank Transfer','Card','Cheque','Credit'] as $m)<option value="{{ $m }}" {{ old('payment_method')===$m?'selected':'' }}>{{ $m }}</option>@endforeach</select></div>
            <div class="vt-f"><label>Currency</label><select class="vt-control" name="currency">@foreach(['AED','USD','GBP','EUR','PKR'] as $c)<option value="{{ $c }}" {{ old('currency','AED')===$c?'selected':'' }}>{{ $c }}</option>@endforeach</select></div>
            <div class="vt-f s12">
                <div class="vt-sum">
                    <div><span>Amount</span><strong id="vtAmount">0.00</strong></div>
                    <div><span>GST</span><strong id="vtGstAmt">0.00</strong></div>
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
var VT_IDX = 0;
function vtCurrentType(){ var s=document.getElementById('vtVendor'); var o=s.options[s.selectedIndex]; return o?o.getAttribute('data-vt'):''; }
function vtItemTemplate(i){
    return ''
    + '<div class="vt-item" data-i="'+i+'">'
    +   '<div class="vt-item-head"><span class="vt-item-no"><span>'+(i+1)+'</span> Item</span>'
    +     '<button type="button" class="vt-rm" title="Remove" onclick="vtRemoveItem(this)"><i class="fas fa-trash"></i></button></div>'
    +   '<div class="vt-grid" style="margin-bottom:0">'
    +     '<div class="vt-f s6" data-vt="paper ctp_plate die_making general"><label>Description</label><input class="vt-control" name="items['+i+'][description]" placeholder="Item / material description"></div>'
    +     '<div class="vt-f" data-vt="paper"><label>Paper Type</label><input class="vt-control" name="items['+i+'][paper_type]"></div>'
    +     '<div class="vt-f" data-vt="paper"><label>GSM</label><input class="vt-control" name="items['+i+'][gsm]"></div>'
    +     '<div class="vt-f" data-vt="paper die_making"><label>Size</label><input class="vt-control" name="items['+i+'][size]" placeholder="e.g. 787x1000"></div>'
    +     '<div class="vt-f" data-vt="paper"><label>Unit</label><input class="vt-control" name="items['+i+'][unit]" placeholder="Sheet / Kg / Ream"></div>'
    +     '<div class="vt-f" data-vt="ctp_plate"><label>Up / Imposition</label><input class="vt-control" name="items['+i+'][up_imposition]" placeholder="e.g. 4-up"></div>'
    +     '<div class="vt-f" data-vt="die_making"><label>Colours</label><input class="vt-control" name="items['+i+'][colours]"></div>'
    +     '<div class="vt-f" data-vt="paper ctp_plate die_making"><label class="vt-qtylabel">Qty</label><input class="vt-control vt-qty" type="number" step="0.01" min="0" name="items['+i+'][quantity]" value="1" oninput="vtCalc()"></div>'
    +     '<div class="vt-f"><label class="vt-ratelabel">Rate</label><input class="vt-control vt-rate" type="number" step="0.0001" min="0" name="items['+i+'][rate]" oninput="vtCalc()"></div>'
    +     '<div class="vt-f" data-vt="paper"><label>GST %</label><input class="vt-control vt-gst" type="number" step="0.01" min="0" max="100" name="items['+i+'][gst_percentage]" value="0" oninput="vtCalc()"></div>'
    +   '</div>'
    + '</div>';
}
function vtApplyTypeToItem(itemEl, vt){
    itemEl.querySelectorAll('[data-vt]').forEach(function(el){
        var types=(el.getAttribute('data-vt')||'').split(/\s+/);
        el.classList.toggle('vt-hide', !(vt && types.indexOf(vt)!==-1));
    });
    var qL=itemEl.querySelector('.vt-qtylabel'), rL=itemEl.querySelector('.vt-ratelabel'), q=itemEl.querySelector('.vt-qty');
    if(vt==='paper'){ if(qL)qL.textContent='Sheet Qty'; if(rL)rL.textContent='Rate'; }
    else if(vt==='general'){ if(rL)rL.textContent='Amount'; if(q)q.value=1; }
    else { if(qL)qL.textContent='Qty'; if(rL)rL.textContent='Rate'; }
}
function vtAddItem(){
    var wrap=document.getElementById('vtItems');
    var div=document.createElement('div'); div.innerHTML=vtItemTemplate(VT_IDX); var el=div.firstChild;
    wrap.appendChild(el);
    vtApplyTypeToItem(el, vtCurrentType());
    VT_IDX++;
    vtRenumber(); vtCalc();
}
function vtRemoveItem(btn){
    var items=document.querySelectorAll('#vtItems .vt-item');
    if(items.length<=1) return;
    btn.closest('.vt-item').remove();
    vtRenumber(); vtCalc();
}
function vtRenumber(){
    document.querySelectorAll('#vtItems .vt-item').forEach(function(it,idx){
        var n=it.querySelector('.vt-item-no span'); if(n)n.textContent=idx+1;
        it.querySelector('.vt-rm').style.visibility = document.querySelectorAll('#vtItems .vt-item').length>1 ? 'visible':'hidden';
    });
}
function vtVendorChanged(){
    var s=document.getElementById('vtVendor'); var o=s.options[s.selectedIndex];
    var vt=o?o.getAttribute('data-vt'):''; var lbl=o?o.getAttribute('data-vtlabel'):'';
    document.getElementById('vtTypeBadge').innerHTML='<i class="fas fa-tag"></i> '+(lbl||'—');
    document.querySelectorAll('#vtItems .vt-item').forEach(function(it){ vtApplyTypeToItem(it, vt); });
    vtCalc();
}
function vtCalc(){
    var vt=vtCurrentType();
    var amount=0, gstAmt=0;
    document.querySelectorAll('#vtItems .vt-item').forEach(function(it){
        var qty=parseFloat((it.querySelector('.vt-qty')||{}).value)||1;
        if(vt==='general') qty=1;
        var rate=parseFloat((it.querySelector('.vt-rate')||{}).value)||0;
        var gst=parseFloat((it.querySelector('.vt-gst')||{}).value)||0;
        var a=qty*rate; amount+=a; gstAmt+=a*gst/100;
    });
    var total=amount+gstAmt;
    var f=function(n){return n.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});};
    document.getElementById('vtAmount').textContent=f(amount);
    document.getElementById('vtGstAmt').textContent=f(gstAmt);
    document.getElementById('vtTotal').textContent=f(total);
}
document.addEventListener('DOMContentLoaded',function(){ vtAddItem(); vtVendorChanged(); });
</script>
@endsection
