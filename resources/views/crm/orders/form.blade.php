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
    $selCust = old('crm_customer_id', $o ? $o->crm_customer_id : ($pf['crm_customer_id'] ?? ''));
    $billFields = ['name'=>'Name','company'=>'Company','street'=>'Street Address','city'=>'City','state'=>'State','country'=>'Country','zip'=>'Zip','phone'=>'Phone','email'=>'Email (PayPal / invoice)'];
    $shipFields = ['name'=>'Name','company'=>'Company','street'=>'Street Address','city'=>'City','state'=>'State','country'=>'Country','zip'=>'Zip','phone'=>'Phone'];
    $billPrefill = function($k) use ($pf) {
        return $k==='name' ? ($pf['billing_name'] ?? '') : ($k==='phone' ? ($pf['billing_phone'] ?? '') : ($k==='email' ? ($pf['billing_email'] ?? '') : ''));
    };
@endphp
<style>
.od{--od-b:#e3e8ef;--od-txt:#0f172a;--od-mut:#64748b;--od-lbl:#7c8aa0;font-size:.8rem;color:var(--od-txt)}
.od-top{display:flex;align-items:center;justify-content:space-between;gap:.8rem;margin-bottom:.7rem}
.od-back{display:inline-flex;align-items:center;gap:.4rem;color:var(--od-mut);text-decoration:none;font-weight:600;font-size:.8rem}
.od-back:hover{color:var(--primary-purple)}
.od-badge{display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .6rem;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:.68rem;font-weight:700;letter-spacing:.02em}
.od-card{background:#fff;border:1px solid var(--od-b);border-radius:10px;box-shadow:0 1px 2px rgba(15,23,42,.04);margin-bottom:.7rem;overflow:hidden}
.od-head{display:flex;align-items:center;justify-content:space-between;gap:.6rem;padding:.6rem .95rem;border-bottom:1px solid var(--od-b);background:#fafbfd}
.od-head h3{margin:0;font-size:.7rem;font-weight:800;color:#334155;text-transform:uppercase;letter-spacing:.06em;display:flex;align-items:center;gap:.45rem}
.od-head h3 i{color:var(--primary-purple);font-size:.72rem}
.od-body{padding:.8rem .95rem}
.od-g2,.od-g3,.od-g4,.od-g6{display:grid;gap:.55rem .7rem}
.od-g2{grid-template-columns:repeat(2,minmax(0,1fr))}
.od-g3{grid-template-columns:repeat(3,minmax(0,1fr))}
.od-g4{grid-template-columns:repeat(4,minmax(0,1fr))}
.od-g6{grid-template-columns:repeat(6,minmax(0,1fr))}
.od-f{display:flex;flex-direction:column;gap:.2rem;min-width:0}
.od-f.full{grid-column:1/-1}.od-f.s2{grid-column:span 2}.od-f.s3{grid-column:span 3}
.od-l{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--od-lbl);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.od-l .req{color:#dc2626}
.od-i,.od-sel,.od-ta{width:100%;height:34px;padding:0 .6rem;border:1px solid #d6dde6;border-radius:7px;background:#fff;outline:0;box-sizing:border-box;font:inherit;font-size:.8rem;color:var(--od-txt);transition:border-color .12s,box-shadow .12s}
.od-sel{appearance:none;-webkit-appearance:none;padding-right:1.7rem;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path d='M1 1l4 4 4-4' fill='none' stroke='%2364748b' stroke-width='1.5'/></svg>");background-repeat:no-repeat;background-position:right .6rem center;cursor:pointer}
.od-ta{height:auto;min-height:60px;padding:.45rem .6rem;resize:vertical}
.od-i:focus,.od-sel:focus,.od-ta:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.od-i[readonly]{background:#f8fafc;color:#475569}
.od-i::placeholder{color:#b3bcc9}
.od-seg{display:inline-flex;height:34px;border:1px solid #d6dde6;border-radius:7px;overflow:hidden;background:#fff}
.od-seg label{display:flex;align-items:center;justify-content:center;flex:1;padding:0 .9rem;font-size:.76rem;font-weight:600;color:var(--od-mut);cursor:pointer;user-select:none}
.od-seg label+label{border-left:1px solid #d6dde6}
.od-seg input{display:none}
.od-seg input:checked+span{color:#fff}
.od-seg label:has(input[value=unpaid]:checked){background:#ef4444;color:#fff}
.od-seg label:has(input[value=paid]:checked){background:#16a34a;color:#fff}
.od-div{height:1px;background:var(--od-b);margin:.75rem 0}
.od-cust{display:flex;align-items:flex-end;gap:.6rem;margin-bottom:.8rem}
.od-cust .od-f{flex:1}
.od-btn{display:inline-flex;align-items:center;gap:.4rem;height:34px;padding:0 .9rem;border-radius:7px;border:1px solid #d6dde6;background:#fff;color:#334155;font-weight:600;font-size:.78rem;cursor:pointer;text-decoration:none;white-space:nowrap}
.od-btn:hover{border-color:#aeb9c7;color:var(--od-txt)}
.od-btn.primary{background:var(--primary-purple);color:#fff;border-color:var(--primary-purple)}
.od-btn.primary:hover{filter:brightness(.94)}
.od-btn.ghost{border-style:dashed;color:var(--od-mut)}
.od-split{display:grid;grid-template-columns:1fr 1fr;gap:.9rem}
.od-sub{display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem}
.od-sub h4{margin:0;font-size:.64rem;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:.06em}
.od-check{display:inline-flex;align-items:center;gap:.4rem;font-weight:600;font-size:.66rem;text-transform:uppercase;letter-spacing:.03em;color:var(--od-mut);cursor:pointer}
.od-check input{margin:0}
.od-item{border:1px solid var(--od-b);border-radius:9px;padding:.7rem .8rem .75rem;margin-bottom:.6rem;background:#fcfcfd;position:relative}
.od-item+.od-item{margin-top:.6rem}
.od-item-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem}
.od-item-no{font-size:.64rem;font-weight:800;color:var(--primary-purple);text-transform:uppercase;letter-spacing:.06em}
.od-item-rm{width:24px;height:24px;border:none;border-radius:6px;background:#fef2f2;color:#dc2626;cursor:pointer;font-size:.7rem;display:inline-flex;align-items:center;justify-content:center}
.od-item-rm:hover{background:#fee2e2}
.od-item .od-g6+.od-g6{margin-top:.55rem}
.od-pay{display:grid;grid-template-columns:1fr 240px;gap:.9rem;align-items:stretch}
.od-total{display:flex;flex-direction:column;justify-content:center;gap:.25rem;padding:.8rem 1rem;border-radius:9px;background:linear-gradient(135deg,var(--primary-purple),#7c6cf1);color:#fff}
.od-total .od-l{color:rgba(255,255,255,.75)}
.od-total-val{font-size:1.35rem;font-weight:800;letter-spacing:-.01em;line-height:1.1}
.od-total-cur{font-size:.7rem;font-weight:700;opacity:.8}
.od-actions{position:sticky;bottom:0;z-index:5;display:flex;align-items:center;justify-content:flex-end;gap:.55rem;padding:.65rem .95rem;margin:0 -.1rem;background:rgba(255,255,255,.92);backdrop-filter:blur(6px);border:1px solid var(--od-b);border-radius:10px;box-shadow:0 -4px 18px rgba(15,23,42,.06)}
.od-actions .spacer{flex:1}
.od-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:8px;padding:.55rem .85rem;margin-bottom:.7rem;font-size:.8rem}
@media(max-width:1100px){.od-g6{grid-template-columns:repeat(3,minmax(0,1fr))}.od-g4{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:820px){.od-split,.od-pay{grid-template-columns:1fr}.od-g2,.od-g3,.od-g4,.od-g6{grid-template-columns:1fr}.od-f.s2,.od-f.s3{grid-column:auto}.od-cust{flex-wrap:wrap}}
</style>

<div class="od">
    <div class="od-top">
        <a href="{{ route('crm.orders.manual.index') }}" class="od-back"><i class="fas fa-arrow-left"></i> Back to Orders</a>
        @if($o)
            <span class="od-badge"><i class="fas fa-file-invoice"></i> {{ $o->invoice_number ? 'TCB-'.$o->invoice_number : 'Order #'.$o->id }}</span>
        @endif
    </div>
    @if($errors->any())<div class="od-err">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ $o ? route('crm.orders.manual.update',$o->id) : route('crm.orders.manual.store') }}" id="orderForm">
        {{ csrf_field() }}
        <input type="hidden" name="crm_email_id" value="{{ $val('crm_email_id') }}">
        <input type="hidden" name="_action" id="orderAction" value="save">

        {{-- Order details --}}
        <div class="od-card">
            <div class="od-head"><h3><i class="fas fa-file-alt"></i> Order Details</h3></div>
            <div class="od-body">
                <div class="od-g4">
                    <div class="od-f"><span class="od-l">User <span class="req">*</span></span><input class="od-i" name="user_name" value="{{ $val('user_name') }}" required></div>
                    <div class="od-f"><span class="od-l">Enquiry #</span><input class="od-i" name="enquiry_number" value="{{ $val('enquiry_number') }}" placeholder="INQ-0001"></div>
                    <div class="od-f"><span class="od-l">Invoice # (without TCB)</span><input class="od-i" name="invoice_number" value="{{ $val('invoice_number') }}" placeholder="e.g. 0003"></div>
                    <div class="od-f"><span class="od-l">Invoice Date</span><input class="od-i" type="date" name="invoice_date" value="{{ $o ? optional($o->invoice_date)->format('Y-m-d') : old('invoice_date', date('Y-m-d')) }}"></div>

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
                    <div class="od-f"><span class="od-l">Customer ID</span><input class="od-i" name="customer_id" value="{{ $val('customer_id') }}"></div>
                    <div class="od-f"><span class="od-l">Payment Term</span><input class="od-i" name="payment_term" value="{{ $val('payment_term') ?: '100% UPFRONT' }}"></div>

                    <div class="od-f">
                        <span class="od-l">Invoice Status</span>
                        <div class="od-seg">
                            <label><input type="radio" name="invoice_status" value="unpaid" {{ ($val('invoice_status') ?: 'unpaid')==='unpaid'?'checked':'' }}><span>Unpaid</span></label>
                            <label><input type="radio" name="invoice_status" value="paid" {{ $val('invoice_status')==='paid'?'checked':'' }}><span>Paid</span></label>
                        </div>
                    </div>
                    <div class="od-f"><span class="od-l">Sales Person</span><input class="od-i" name="sales_person" value="{{ $val('sales_person','sales_person') }}"></div>
                    <div class="od-f"><span class="od-l">Shipping Method</span>
                        <select class="od-sel" name="shipping_method">
                            <option value="">Select method</option>
                            @foreach(['Air','Sea','Land','Courier','Pickup'] as $m)<option value="{{ $m }}" {{ $val('shipping_method')===$m?'selected':'' }}>{{ $m }}</option>@endforeach
                        </select>
                    </div>
                    <div class="od-f"><span class="od-l">Shipping Term</span>
                        <select class="od-sel" name="shipping_term">
                            <option value="">Select term</option>
                            @foreach(['FOB','CIF','EXW','DDP','DAP'] as $m)<option value="{{ $m }}" {{ $val('shipping_term')===$m?'selected':'' }}>{{ $m }}</option>@endforeach
                        </select>
                    </div>

                    <div class="od-f"><span class="od-l">Payment Via</span>
                        <select class="od-sel" name="payment_term_via">
                            <option value="">Select</option>
                            @foreach(['PayPal','Credit/Debit Card','Wire Transfer','CCA','Cash'] as $m)<option value="{{ $m }}" {{ $val('payment_term_via')===$m?'selected':'' }}>{{ $m }}</option>@endforeach
                        </select>
                    </div>
                    <div class="od-f s3"><span class="od-l">Additional Info</span><textarea class="od-ta" name="additional_info" style="min-height:34px;height:34px" rows="1" onfocus="this.style.height='70px'">{{ $val('additional_info') }}</textarea></div>
                </div>
            </div>
        </div>

        {{-- Customer: billing + shipping --}}
        <div class="od-card">
            <div class="od-head"><h3><i class="fas fa-user"></i> Customer</h3></div>
            <div class="od-body">
                <div class="od-cust">
                    <div class="od-f">
                        <span class="od-l">Select customer (Customers tab) — fills billing &amp; shipping below</span>
                        <select class="od-sel" name="crm_customer_id" id="crmCustomerSelect" onchange="odApplyCustomer(this)">
                            <option value="">Manual entry (not linked)</option>
                            @foreach(($customers ?? []) as $c)
                                <option value="{{ $c->id }}" {{ (string)$selCust === (string)$c->id ? 'selected' : '' }}
                                    data-name="{{ $c->name }}" data-company="{{ $c->company_name }}" data-phone="{{ $c->phone }}"
                                    data-email="{{ $c->email }}" data-country="{{ $c->country }}" data-currency="{{ $c->currency }}"
                                    data-billing="{{ $c->billing_address }}" data-shipping="{{ $c->shipping_address }}">
                                    {{ $c->name }}{{ $c->company_name ? ' · '.$c->company_name : '' }}{{ $c->email ? ' · '.$c->email : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <a href="{{ route('crm.customer_sales.index') }}" target="_blank" rel="noopener" class="od-btn"><i class="fas fa-user-plus"></i> New customer</a>
                </div>

                <div class="od-split">
                    <div>
                        <div class="od-sub"><h4>Billing</h4></div>
                        <div class="od-g2">
                            @foreach($billFields as $k=>$lbl)
                                <div class="od-f {{ $k==='street'?'full':'' }}">
                                    <span class="od-l">{{ $lbl }}</span>
                                    <input class="od-i bill-{{ $k }}" name="billing[{{ $k }}]" {{ $k==='email'?'type=email':'' }}
                                           value="{{ $o ? old('billing.'.$k, $b[$k] ?? '') : old('billing.'.$k, $billPrefill($k)) }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <div class="od-sub">
                            <h4>Shipping</h4>
                            <label class="od-check"><input type="checkbox" id="copyBilling" onchange="odCopyBilling(this)"> Same as billing</label>
                        </div>
                        <div class="od-g2">
                            @foreach($shipFields as $k=>$lbl)
                                <div class="od-f {{ $k==='street'?'full':'' }}">
                                    <span class="od-l">{{ $lbl }}</span>
                                    <input class="od-i ship-{{ $k }}" name="shipping[{{ $k }}]" value="{{ old('shipping.'.$k, $s[$k] ?? '') }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="od-card">
            <div class="od-head">
                <h3><i class="fas fa-box-open"></i> Order Items</h3>
                <button type="button" class="od-btn ghost" onclick="odDuplicate()"><i class="fas fa-plus"></i> Add item</button>
            </div>
            <div class="od-body">
                <div id="orderItems">
                    @foreach($items as $i => $it)
                        <div class="od-item">
                            <div class="od-item-bar">
                                <span class="od-item-no">Item <span class="od-item-idx">{{ $i+1 }}</span></span>
                                <button type="button" class="od-item-rm" title="Remove" onclick="odRemoveItem(this)"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="od-g6">
                                <div class="od-f s2"><span class="od-l">Box Style</span><input class="od-i" name="items[{{ $i }}][box_style]" value="{{ $it['box_style'] ?? ($i===0?($pf['box_style']??''):'') }}" placeholder="e.g. Display Boxes"></div>
                                <div class="od-f s2"><span class="od-l">Stock</span><input class="od-i" name="items[{{ $i }}][stock]" value="{{ $it['stock'] ?? '' }}"></div>
                                <div class="od-f s2"><span class="od-l">Color</span><input class="od-i" name="items[{{ $i }}][color]" value="{{ $it['color'] ?? '' }}"></div>
                            </div>
                            <div class="od-g6">
                                <div class="od-f"><span class="od-l">Length</span><input class="od-i" type="number" step="any" name="items[{{ $i }}][length]" value="{{ $it['length'] ?? '' }}"></div>
                                <div class="od-f"><span class="od-l">Width</span><input class="od-i" type="number" step="any" name="items[{{ $i }}][width]" value="{{ $it['width'] ?? '' }}"></div>
                                <div class="od-f"><span class="od-l">Height</span><input class="od-i" type="number" step="any" name="items[{{ $i }}][height]" value="{{ $it['height'] ?? '' }}"></div>
                                <div class="od-f"><span class="od-l">Unit</span><input class="od-i" name="items[{{ $i }}][unit]" value="{{ $it['unit'] ?? '' }}" placeholder="inch / mm"></div>
                                <div class="od-f s2"><span class="od-l">Finishing</span><input class="od-i" name="items[{{ $i }}][finishing]" value="{{ $it['finishing'] ?? ($i===0?($pf['finishing']??''):'') }}"></div>
                            </div>
                            <div class="od-g6">
                                <div class="od-f"><span class="od-l">Qty</span><input class="od-i it-qty" type="number" step="any" name="items[{{ $i }}][qty]" value="{{ $it['qty'] ?? '' }}" oninput="odCalc()"></div>
                                <div class="od-f"><span class="od-l">Unit Price</span><input class="od-i it-price" type="number" step="any" name="items[{{ $i }}][unit_price]" value="{{ $it['unit_price'] ?? '' }}" oninput="odCalc()"></div>
                                <div class="od-f"><span class="od-l">Other Charges</span><input class="od-i it-other" type="number" step="any" name="items[{{ $i }}][other_charges]" value="{{ $it['other_charges'] ?? '' }}" oninput="odCalc()"></div>
                                <div class="od-f"><span class="od-l">Line Total (<span class="od-cur">{{ $cur }}</span>)</span><input class="od-i it-total" type="text" readonly value="{{ isset($it['line_total']) ? number_format($it['line_total'],2) : '0.00' }}"></div>
                                <div class="od-f s2"><span class="od-l">Additional Info</span><input class="od-i" name="items[{{ $i }}][additional_info]" value="{{ $it['additional_info'] ?? '' }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Payment --}}
        <div class="od-card">
            <div class="od-head"><h3><i class="fas fa-calculator"></i> Payment</h3></div>
            <div class="od-body">
                <div class="od-pay">
                    <div class="od-g4">
                        <div class="od-f"><span class="od-l">Sub Total</span><input class="od-i" id="subTotal" name="_sub_total_display" type="text" readonly value="{{ $o ? number_format($o->sub_total,2) : '0.00' }}"></div>
                        <div class="od-f"><span class="od-l">Package Price</span><input class="od-i" id="packagePrice" type="number" step="any" name="package_price" value="{{ $o ? $o->package_price : old('package_price',0) }}" oninput="odCalc()"></div>
                        <div class="od-f"><span class="od-l">Rush Charges</span><input class="od-i" id="rushCharges" type="number" step="any" name="rush_charges" value="{{ $o ? $o->rush_charges : old('rush_charges',0) }}" oninput="odCalc()"></div>
                        <div class="od-f"><span class="od-l">Discount</span><input class="od-i" id="discount" type="number" step="any" name="discount" value="{{ $o ? $o->discount : old('discount',0) }}" oninput="odCalc()"></div>
                    </div>
                    <div class="od-total">
                        <span class="od-l">Grand Total</span>
                        <div class="od-total-val" id="grandTotal">{{ $o ? number_format($o->total,2) : '0.00' }}</div>
                        <div class="od-total-cur od-cur">{{ $cur }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="od-actions">
            <a href="{{ route('crm.orders.manual.index') }}" class="od-btn">Cancel</a>
            <span class="spacer"></span>
            <button type="submit" class="od-btn" onclick="document.getElementById('orderAction').value='save_send'"><i class="fas fa-paper-plane"></i> Save &amp; Send Email</button>
            <button type="submit" class="od-btn primary" onclick="document.getElementById('orderAction').value='save'"><i class="fas fa-save"></i> {{ $o ? 'Update Order' : 'Save Order' }}</button>
        </div>
    </form>
</div>

<script>
    function odMoney(n){ return (isFinite(n)?n:0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function odCalc(){
        var sub = 0;
        document.querySelectorAll('#orderItems .od-item').forEach(function(row, i){
            var q = parseFloat(row.querySelector('.it-qty').value)||0;
            var p = parseFloat(row.querySelector('.it-price').value)||0;
            var o = parseFloat(row.querySelector('.it-other').value)||0;
            var lt = q*p + o;
            row.querySelector('.it-total').value = odMoney(lt);
            var idx = row.querySelector('.od-item-idx'); if (idx) idx.textContent = i+1;
            sub += lt;
        });
        document.getElementById('subTotal').value = odMoney(sub);
        var pkg = parseFloat(document.getElementById('packagePrice').value)||0;
        var rush = parseFloat(document.getElementById('rushCharges').value)||0;
        var disc = parseFloat(document.getElementById('discount').value)||0;
        document.getElementById('grandTotal').textContent = odMoney(sub + pkg + rush - disc);
        var cur = document.querySelector('select[name=currency]');
        if (cur) document.querySelectorAll('.od-cur').forEach(function(el){ el.textContent = cur.value; });
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
        odCalc();
        clone.querySelector('input').focus();
    }
    function odRemoveItem(btn){
        var items = document.getElementById('orderItems');
        if (items.querySelectorAll('.od-item').length <= 1) return;
        btn.closest('.od-item').remove();
        odCalc();
    }
    function odApplyCustomer(sel){
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return;
        var d = opt.dataset;
        var set = function(cls, v){ var el = document.querySelector(cls); if (el && v !== undefined) el.value = v || ''; };
        set('.bill-name', d.name); set('.bill-company', d.company); set('.bill-phone', d.phone);
        set('.bill-email', d.email); set('.bill-country', d.country); set('.bill-street', d.billing);
        set('.ship-name', d.name); set('.ship-company', d.company); set('.ship-phone', d.phone);
        set('.ship-country', d.country); set('.ship-street', d.shipping || d.billing);
        var cur = document.querySelector('select[name=currency]');
        if (cur && d.currency) cur.value = d.currency;
        var cid = document.querySelector('input[name=customer_id]');
        if (cid && !cid.value) cid.value = opt.value;
        odCalc();
    }
    function odCopyBilling(cb){
        if (!cb.checked) return;
        ['name','company','street','city','state','country','zip','phone'].forEach(function(k){
            var b = document.querySelector('.bill-'+k), s = document.querySelector('.ship-'+k);
            if (b && s) s.value = b.value;
        });
    }
    document.querySelector('select[name=currency]').addEventListener('change', odCalc);
    odCalc();
</script>
@endsection
