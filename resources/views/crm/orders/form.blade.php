@extends('crm.layout')
@section('title', $order ? 'Edit Order' : 'Create Order')

@section('content')
@php
    $o = $order;
    $pf = $prefill ?? [];
    $val = function($col, $pfKey = null) use ($o, $pf) {
        if ($o) return old(str_replace(['[',']'],['.',''],$col), data_get($o, $col));
        return old($col, $pf[$pfKey ?? $col] ?? '');
    };
    $b = $o->billing ?? [];
    $s = $o->shipping ?? [];
    $items = $o && is_array($o->line_items) && count($o->line_items) ? $o->line_items : [[]];
    $cur = $o->currency ?? ($pf['currency'] ?? 'USD');
@endphp
<style>
.od-wrap{max-width:1120px;margin:0 auto}
.od-top{display:flex;align-items:center;gap:.8rem;margin-bottom:1rem}
.od-back{display:inline-flex;align-items:center;gap:.4rem;color:#64748b;text-decoration:none;font-weight:700;font-size:.85rem}
.od-card{background:#fff;border:1px solid #e4eaf1;border-radius:16px;box-shadow:0 8px 26px rgba(15,23,42,.05);padding:1.3rem 1.4rem;margin-bottom:1.1rem}
.od-sec{font-size:.95rem;font-weight:850;color:#0f172a;margin:.2rem 0 .9rem;padding-bottom:.5rem;border-bottom:2px solid var(--primary-soft)}
.od-grid{display:grid;grid-template-columns:1fr 1fr;gap:.85rem 1.1rem}
.od-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.85rem 1.1rem}
.od-f{display:flex;flex-direction:column;gap:.28rem}
.od-f.full{grid-column:1/-1}
.od-l{font-size:.64rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#718096}
.od-l .req{color:#dc2626}
.od-i,.od-sel,.od-ta{width:100%;padding:.55rem .65rem;border:1.5px solid #dbe3ec;border-radius:8px;background:#fff;outline:0;box-sizing:border-box;font:inherit;font-size:.84rem}
.od-i:focus,.od-sel:focus,.od-ta:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.od-ta{min-height:64px;resize:vertical}
.od-radio{display:flex;gap:1.2rem;align-items:center;padding:.35rem 0}
.od-radio label{display:inline-flex;align-items:center;gap:.4rem;font-weight:700;font-size:.85rem;color:#334155;cursor:pointer}
.od-check{display:inline-flex;align-items:center;gap:.5rem;font-weight:800;font-size:.72rem;text-transform:uppercase;letter-spacing:.03em;color:#475569;margin-bottom:.7rem;cursor:pointer}
.od-item{border:1px solid #e6ecf3;border-radius:12px;padding:1rem;margin-bottom:.8rem;background:#fbfcfe;position:relative}
.od-item-rm{position:absolute;top:.6rem;right:.6rem;width:26px;height:26px;border:none;border-radius:7px;background:#fef2f2;color:#dc2626;cursor:pointer}
.od-dup{display:inline-flex;align-items:center;gap:.45rem;padding:.55rem .9rem;border-radius:9px;border:1px solid #16a34a;background:#22a34a;color:#fff;font-weight:800;font-size:.82rem;cursor:pointer}
.od-actions{display:flex;gap:.7rem;flex-wrap:wrap;margin-top:.4rem}
.od-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.68rem 1.2rem;border-radius:10px;border:1px solid #dbe3ec;background:#fff;color:#475569;font-weight:850;font-size:.86rem;cursor:pointer;text-decoration:none}
.od-btn.primary{background:var(--primary-purple);color:#fff;border-color:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}
.od-total-row{font-weight:850}
.od-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.82rem}
@media(max-width:760px){.od-grid,.od-grid-3{grid-template-columns:1fr}}
</style>

<div class="od-wrap">
    <div class="od-top"><a href="{{ route('crm.orders.index') }}" class="od-back"><i class="fas fa-arrow-left"></i> Back to Orders</a></div>
    @if($errors->any())<div class="od-err">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ $o ? route('crm.orders.update',$o->id) : route('crm.orders.store') }}" id="orderForm">
        {{ csrf_field() }}
        <input type="hidden" name="crm_email_id" value="{{ $val('crm_email_id') }}">
        <input type="hidden" name="_action" id="orderAction" value="save">

        <div class="od-card">
            <div class="od-grid">
                <div class="od-f"><span class="od-l">User <span class="req">*</span></span><input class="od-i" name="user_name" value="{{ $val('user_name') }}" required></div>
                <div class="od-f"><span class="od-l">Enter Enquiry #</span><input class="od-i" name="enquiry_number" value="{{ $val('enquiry_number') }}"></div>
                <div class="od-f">
                    <span class="od-l">Invoice Status</span>
                    <div class="od-radio">
                        <label><input type="radio" name="invoice_status" value="unpaid" {{ ($val('invoice_status') ?: 'unpaid')==='unpaid'?'checked':'' }}> Unpaid</label>
                        <label><input type="radio" name="invoice_status" value="paid" {{ $val('invoice_status')==='paid'?'checked':'' }}> Paid</label>
                    </div>
                </div>
                <div class="od-f"><span class="od-l">Invoice (number without TCB, e.g. 0003, 0004)</span><input class="od-i" name="invoice_number" value="{{ $val('invoice_number') }}"></div>
                <div class="od-f">
                    <span class="od-l">Website</span>
                    <select class="od-sel" name="website">
                        @foreach(['www.thecustomboxes.com','www.myboxprinting.com'] as $w)
                            <option value="{{ $w }}" {{ strtolower($val('website'))===$w?'selected':'' }}>{{ strtoupper($w) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="od-f">
                    <span class="od-l">Currency</span>
                    <select class="od-sel" name="currency">
                        @foreach(['USD','AED','GBP','EUR','CAD','AUD','PKR','SAR','QAR'] as $c)
                            <option value="{{ $c }}" {{ ($cur)===$c?'selected':'' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="od-card">
            <div class="od-sec">Invoice Header</div>
            <div class="od-grid-3">
                <div class="od-f"><span class="od-l">Date</span><input class="od-i" type="date" name="invoice_date" value="{{ $o ? optional($o->invoice_date)->format('Y-m-d') : old('invoice_date', date('Y-m-d')) }}"></div>
                <div class="od-f"><span class="od-l">Customer ID</span><input class="od-i" name="customer_id" value="{{ $val('customer_id') }}"></div>
                <div class="od-f"><span class="od-l">Payment Term</span><input class="od-i" name="payment_term" value="{{ $val('payment_term') ?: '100% UPFRONT' }}"></div>
            </div>
        </div>

        <div class="od-grid" style="align-items:start">
            <div class="od-card">
                <div class="od-sec">Billing Detail</div>
                @foreach(['name'=>'Billing Name','company'=>'Billing Company Name','street'=>'Billing Street Address','city'=>'Billing City','state'=>'Billing State','country'=>'Billing Country','zip'=>'Billing Zip','phone'=>'Billing Phone'] as $k=>$lbl)
                    <div class="od-f" style="margin-bottom:.6rem"><span class="od-l">{{ $lbl }}</span><input class="od-i bill-{{ $k }}" name="billing[{{ $k }}]" value="{{ $o ? old('billing.'.$k, $b[$k] ?? '') : old('billing.'.$k, ($k==='name'?($pf['billing_name']??''):($k==='phone'?($pf['billing_phone']??''):''))) }}"></div>
                @endforeach
            </div>
            <div class="od-card">
                <div class="od-sec">Shipping Detail</div>
                <label class="od-check"><input type="checkbox" id="copyBilling" onchange="odCopyBilling(this)"> Copy from billing address</label>
                @foreach(['name'=>'Shipping Name','company'=>'Shipping Company','street'=>'Shipping Street','city'=>'Shipping City','state'=>'Shipping State','country'=>'Shipping Country','zip'=>'Shipping Zip','phone'=>'Shipping Phone'] as $k=>$lbl)
                    <div class="od-f" style="margin-bottom:.6rem"><span class="od-l">{{ $lbl }}</span><input class="od-i ship-{{ $k }}" name="shipping[{{ $k }}]" value="{{ old('shipping.'.$k, $s[$k] ?? '') }}"></div>
                @endforeach
            </div>
        </div>

        <div class="od-card">
            <div class="od-sec">Other Detail Header</div>
            <div class="od-grid-3">
                <div class="od-f"><span class="od-l">Sales Person</span><input class="od-i" name="sales_person" value="{{ $val('sales_person','sales_person') }}"></div>
                <div class="od-f"><span class="od-l">Shipping Method</span>
                    <select class="od-sel" name="shipping_method">
                        <option value="">Select method</option>
                        @foreach(['Air','Sea','Land','Courier','Pickup'] as $m)<option value="{{ $m }}" {{ $val('shipping_method')===$m?'selected':'' }}>{{ $m }}</option>@endforeach
                    </select>
                </div>
                <div class="od-f"><span class="od-l">Shipping Term</span>
                    <select class="od-sel" name="shipping_term">
                        <option value="">Select shipping term</option>
                        @foreach(['FOB','CIF','EXW','DDP','DAP'] as $m)<option value="{{ $m }}" {{ $val('shipping_term')===$m?'selected':'' }}>{{ $m }}</option>@endforeach
                    </select>
                </div>
                <div class="od-f"><span class="od-l">Payment Term Via</span>
                    <select class="od-sel" name="payment_term_via">
                        <option value="">Select payment term via</option>
                        @foreach(['PayPal','Credit/Debit Card','Wire Transfer','CCA','Cash'] as $m)<option value="{{ $m }}" {{ $val('payment_term_via')===$m?'selected':'' }}>{{ $m }}</option>@endforeach
                    </select>
                </div>
                <div class="od-f full"><span class="od-l">Additional Info</span><textarea class="od-ta" name="additional_info">{{ $val('additional_info') }}</textarea></div>
            </div>
        </div>

        <div class="od-card">
            <div class="od-sec">Order Detail</div>
            <div id="orderItems">
                @foreach($items as $i => $it)
                    <div class="od-item">
                        <button type="button" class="od-item-rm" title="Remove" onclick="odRemoveItem(this)"><i class="fas fa-times"></i></button>
                        <div class="od-grid-3">
                            <div class="od-f"><span class="od-l">Box Style</span><input class="od-i" name="items[{{ $i }}][box_style]" value="{{ $it['box_style'] ?? ($i===0?($pf['box_style']??''):'') }}" placeholder="Select box style"></div>
                            <div class="od-f"><span class="od-l">Stock</span><input class="od-i" name="items[{{ $i }}][stock]" value="{{ $it['stock'] ?? '' }}"></div>
                            <div class="od-f"><span class="od-l">Color</span><input class="od-i" name="items[{{ $i }}][color]" value="{{ $it['color'] ?? '' }}" placeholder="Select color"></div>
                        </div>
                        <div class="od-grid-3" style="margin-top:.6rem;grid-template-columns:1fr 1fr 1fr 1fr">
                            <div class="od-f"><span class="od-l">Length</span><input class="od-i" type="number" step="any" name="items[{{ $i }}][length]" value="{{ $it['length'] ?? '' }}"></div>
                            <div class="od-f"><span class="od-l">Width</span><input class="od-i" type="number" step="any" name="items[{{ $i }}][width]" value="{{ $it['width'] ?? '' }}"></div>
                            <div class="od-f"><span class="od-l">Height</span><input class="od-i" type="number" step="any" name="items[{{ $i }}][height]" value="{{ $it['height'] ?? '' }}"></div>
                            <div class="od-f"><span class="od-l">Unit</span><input class="od-i" name="items[{{ $i }}][unit]" value="{{ $it['unit'] ?? '' }}" placeholder="Select unit"></div>
                        </div>
                        <div class="od-grid" style="margin-top:.6rem">
                            <div class="od-f"><span class="od-l">Finishing</span><input class="od-i" name="items[{{ $i }}][finishing]" value="{{ $it['finishing'] ?? ($i===0?($pf['finishing']??''):'') }}"></div>
                            <div class="od-f"><span class="od-l">Additional Info</span><input class="od-i" name="items[{{ $i }}][additional_info]" value="{{ $it['additional_info'] ?? '' }}"></div>
                        </div>
                        <div class="od-grid-3" style="margin-top:.6rem;grid-template-columns:1fr 1fr 1fr 1fr">
                            <div class="od-f"><span class="od-l">Qty</span><input class="od-i it-qty" type="number" step="any" name="items[{{ $i }}][qty]" value="{{ $it['qty'] ?? '' }}" oninput="odCalc()"></div>
                            <div class="od-f"><span class="od-l">Unit Price</span><input class="od-i it-price" type="number" step="any" name="items[{{ $i }}][unit_price]" value="{{ $it['unit_price'] ?? '' }}" oninput="odCalc()"></div>
                            <div class="od-f"><span class="od-l">Other Charges</span><input class="od-i it-other" type="number" step="any" name="items[{{ $i }}][other_charges]" value="{{ $it['other_charges'] ?? '' }}" oninput="odCalc()"></div>
                            <div class="od-f"><span class="od-l">Line Total ({{ $cur }})</span><input class="od-i it-total" type="text" readonly value="{{ isset($it['line_total']) ? number_format($it['line_total'],2) : '0.00' }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="od-dup" onclick="odDuplicate()"><i class="fas fa-plus"></i> Duplicate</button>
        </div>

        <div class="od-card">
            <div class="od-sec">Payment Detail</div>
            <div class="od-grid-3" style="grid-template-columns:1fr 1fr 1fr 1fr">
                <div class="od-f"><span class="od-l">Sub Total</span><input class="od-i" id="subTotal" name="_sub_total_display" type="text" readonly value="{{ $o ? number_format($o->sub_total,2) : '0.00' }}"></div>
                <div class="od-f"><span class="od-l">Package Price</span><input class="od-i" id="packagePrice" type="number" step="any" name="package_price" value="{{ $o ? $o->package_price : old('package_price',0) }}" oninput="odCalc()"></div>
                <div class="od-f"><span class="od-l">Rush Charges</span><input class="od-i" id="rushCharges" type="number" step="any" name="rush_charges" value="{{ $o ? $o->rush_charges : old('rush_charges',0) }}" oninput="odCalc()"></div>
                <div class="od-f"><span class="od-l">Discount</span><input class="od-i" id="discount" type="number" step="any" name="discount" value="{{ $o ? $o->discount : old('discount',0) }}" oninput="odCalc()"></div>
            </div>
            <div class="od-grid-3" style="margin-top:.7rem;grid-template-columns:1fr">
                <div class="od-f"><span class="od-l">Total</span><input class="od-i od-total-row" id="grandTotal" type="text" readonly value="{{ $o ? number_format($o->total,2) : '0.00' }}"></div>
            </div>
        </div>

        <div class="od-actions">
            <button type="submit" class="od-btn primary" onclick="document.getElementById('orderAction').value='save'"><i class="fas fa-save"></i> Save</button>
            <button type="submit" class="od-btn" onclick="document.getElementById('orderAction').value='save_send'"><i class="fas fa-paper-plane"></i> Save and Send Email</button>
        </div>
    </form>
</div>

<script>
    var odCur = @json($cur);
    function odMoney(n){ return (isFinite(n)?n:0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function odCalc(){
        var sub = 0;
        document.querySelectorAll('#orderItems .od-item').forEach(function(row){
            var q = parseFloat(row.querySelector('.it-qty').value)||0;
            var p = parseFloat(row.querySelector('.it-price').value)||0;
            var o = parseFloat(row.querySelector('.it-other').value)||0;
            var lt = q*p + o;
            row.querySelector('.it-total').value = odMoney(lt);
            sub += lt;
        });
        document.getElementById('subTotal').value = odMoney(sub);
        var pkg = parseFloat(document.getElementById('packagePrice').value)||0;
        var rush = parseFloat(document.getElementById('rushCharges').value)||0;
        var disc = parseFloat(document.getElementById('discount').value)||0;
        document.getElementById('grandTotal').value = odMoney(sub + pkg + rush - disc);
    }
    function odDuplicate(){
        var items = document.getElementById('orderItems');
        var first = items.querySelector('.od-item');
        var clone = first.cloneNode(true);
        var idx = items.querySelectorAll('.od-item').length;
        clone.querySelectorAll('input').forEach(function(inp){
            if (inp.name) inp.name = inp.name.replace(/items\[\d+\]/, 'items['+idx+']');
            if (!inp.readOnly) inp.value = '';
            else inp.value = '0.00';
        });
        items.appendChild(clone);
    }
    function odRemoveItem(btn){
        var items = document.getElementById('orderItems');
        if (items.querySelectorAll('.od-item').length <= 1) return;
        btn.closest('.od-item').remove();
        odCalc();
    }
    function odCopyBilling(cb){
        ['name','company','street','city','state','country','zip','phone'].forEach(function(k){
            var b = document.querySelector('.bill-'+k), s = document.querySelector('.ship-'+k);
            if (b && s) s.value = cb.checked ? b.value : s.value;
        });
    }
    odCalc();
</script>
@endsection
