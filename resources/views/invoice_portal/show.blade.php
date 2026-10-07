@extends('invoice_portal.layout')
@php
    $cur = strtoupper($order->currency ?: 'USD');
    $money = fn ($v) => $cur . ' ' . number_format((float) $v, 2);
    $b = $order->billing ?? []; $s = $order->shipping ?? [];
    $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
    $due = $order->balanceDue(); $paidAmt = $order->paidAmount();
    $isPaid = $order->invoice_status === 'paid' || $due <= 0.009;
    $items = is_array($order->line_items) ? $order->line_items : [];
    $party = fn ($a) => array_values(array_filter([$a['name'] ?? null, $a['company'] ?? null, $a['street'] ?? null, trim(implode(', ', array_filter([$a['city'] ?? null, $a['state'] ?? null, $a['zip'] ?? null]))), $a['country'] ?? null, $a['phone'] ?? null]));
@endphp
@section('title', 'Invoice ' . $label)
@section('styles')
.back{display:inline-flex;align-items:center;gap:.4rem;color:var(--mut);text-decoration:none;font-weight:600;font-size:.84rem;margin-bottom:.9rem}
.grid{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:1.25rem;align-items:start}
.inv{padding:1.6rem 1.8rem}
.inv-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.inv-head img{height:44px}
.inv-head .status{font-size:1rem;font-weight:700}.status .paid{color:#15803d}.status .unpaid{color:#dc2626}
.inv-head .title{font-size:1.35rem;font-weight:800;color:var(--p)}
.row{display:flex;gap:1.5rem;margin-top:1.2rem;flex-wrap:wrap}
.row>div{flex:1 1 220px}
.addr{font-size:.82rem;color:#475569;line-height:1.6}.addr b{color:var(--ink)}
table.kv{border-collapse:collapse;width:100%;font-size:.82rem}
table.kv td{border:1px solid #cbd5e1;padding:.4rem .6rem}table.kv td.k{font-weight:700;background:#f8fafc;width:45%}
.bar{background:var(--p);color:#fff;font-weight:700;font-size:.78rem;padding:.4rem .7rem;border-radius:6px 6px 0 0;margin-top:1.2rem}
.party{border:1px solid #cbd5e1;border-top:none;padding:.7rem .8rem;font-size:.82rem;line-height:1.6;color:#334155;min-height:70px;border-radius:0 0 6px 6px}
table.items{width:100%;border-collapse:collapse;margin-top:1.2rem;font-size:.82rem}
table.items th{background:var(--p);color:#fff;text-align:left;padding:.5rem .6rem;font-size:.72rem;text-transform:uppercase}
table.items td{border:1px solid #cbd5e1;padding:.55rem .6rem;vertical-align:top}
.num{text-align:right;white-space:nowrap}.ctr{text-align:center}
.strip td{text-align:center;font-size:.8rem}
.tot{display:flex;justify-content:flex-end;margin-top:1rem}
table.totals{border-collapse:collapse;min-width:260px;font-size:.84rem}
table.totals td{border:1px solid #cbd5e1;padding:.45rem .7rem}table.totals td.k{font-weight:700;background:#f8fafc}
table.totals tr.g td{background:var(--p);color:#fff;font-weight:800;font-size:.95rem;border-color:var(--p)}
table.totals td.v{text-align:right;white-space:nowrap}
.thanks{text-align:center;margin-top:1.5rem;color:#475569;font-size:.8rem;line-height:1.6}.thanks b{display:block;font-size:1rem;color:var(--ink);margin-bottom:.3rem}
.pay{padding:1.4rem 1.5rem;position:sticky;top:1rem}
.pm-tabs{display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin:.2rem 0 1rem}
.pm-tab{display:flex;flex-direction:column;align-items:center;gap:.3rem;padding:.7rem .5rem;border:2px solid var(--b);border-radius:10px;background:#fff;cursor:pointer;font:inherit;font-weight:700;font-size:.8rem;color:var(--mut)}
.pm-tab i{font-size:1.25rem}
.pm-tab.active{border-color:var(--p);color:var(--p);background:#f3f7fc}
.pm-tab.pp.active{border-color:#003087;color:#003087;background:#f0f5ff}
.pm-pane[hidden]{display:none}
#paypalButtons{min-height:150px}
.pp-note{font-size:.78rem;color:var(--mut);text-align:center;margin-bottom:.8rem;line-height:1.5}
.pp-err{display:none;margin-top:.6rem;padding:.6rem .8rem;border-radius:8px;background:#fee2e2;color:#991b1b;font-size:.8rem;font-weight:600}
.pp-wait{display:none;text-align:center;color:var(--mut);font-size:.82rem;padding:.6rem 0}
.sandbox{display:inline-block;margin-left:.4rem;padding:.1rem .45rem;border-radius:999px;background:#fef3c7;color:#92400e;font-size:.62rem;font-weight:800;text-transform:uppercase}
.pay h2{margin:0 0 .2rem;font-size:1.05rem}
.pay .amt{font-size:1.6rem;font-weight:800;color:var(--p);margin:.3rem 0 1rem}
.pay .f{display:flex;flex-direction:column;gap:.25rem;margin-bottom:.75rem}
.pay label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#7c8aa0}
.pay input{height:42px;padding:0 .8rem;border:1px solid #d6dde6;border-radius:8px;font:inherit;font-size:.95rem;outline:0;width:100%}
.pay input:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(55,96,148,.15)}
.pay .two{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
.pay .btn.p{width:100%;justify-content:center;height:46px;font-size:.95rem;margin-top:.3rem}
.secure{margin-top:.8rem;font-size:.72rem;color:#94a3b8;text-align:center}.secure i{color:#16a34a}
.paidbox{text-align:center;padding:1rem 0}.paidbox i{font-size:2.6rem;color:#16a34a}.paidbox h3{margin:.5rem 0 .2rem}.paidbox p{margin:0;color:var(--mut);font-size:.84rem}
.hist{margin-top:1rem;border-top:1px dashed var(--b);padding-top:.8rem;font-size:.8rem;color:#475569}
.hist div{display:flex;justify-content:space-between;gap:.5rem;padding:.2rem 0}
.alt{margin-top:1rem;padding-top:.9rem;border-top:1px dashed var(--b);font-size:.8rem;color:var(--mut);text-align:center}
.cards{display:flex;gap:.4rem;justify-content:center;margin-bottom:.9rem}.cards img{height:22px;border:1px solid var(--b);border-radius:4px;padding:2px;background:#fff}
@media(max-width:960px){.grid{grid-template-columns:1fr}.pay{position:static}}
@endsection
@section('content')
<a class="back" href="{{ route('invoice_portal.invoices') }}"><i class="fas fa-arrow-left"></i> Back to my invoices</a>
<div class="grid">
    <div class="card inv">
        <div class="inv-head">
            <img src="{{ asset($brand['logo']) }}" alt="{{ $brand['name'] }}">
            <div class="status">Status: <span class="{{ $isPaid ? 'paid' : 'unpaid' }}">{{ $isPaid ? 'Paid' : 'Unpaid' }}</span></div>
            <div class="title">Sale Invoice</div>
        </div>
        <div class="row">
            <div class="addr"><b>Address:</b><br>9933 Franklin Ave, Franklin Park, IL 60131<br><b>Contact:</b> {{ $brand['phones'] }}</div>
            <div>
                <table class="kv">
                    <tr><td class="k">Invoice no</td><td>{{ $label }}</td></tr>
                    <tr><td class="k">Date</td><td>{{ optional($order->invoice_date)->format('d-M-Y') ?: $order->created_at->format('d-M-Y') }}</td></tr>
                    <tr><td class="k">Enquiry #</td><td>{{ $order->enquiry_number ?: '—' }}</td></tr>
                    @if($order->customer_id)<tr><td class="k">Customer ID</td><td>{{ $order->customer_id }}</td></tr>@endif
                </table>
            </div>
        </div>
        <div class="row">
            <div><div class="bar">Bill To</div><div class="party">{!! implode('<br>', array_map('e', $party($b))) ?: '—' !!}</div></div>
            <div><div class="bar">Ship To</div><div class="party">{!! implode('<br>', array_map('e', $party($s))) ?: '—' !!}</div></div>
        </div>
        <table class="items strip">
            <tr><th class="ctr">Sales Person</th><th class="ctr">Shipping Method</th><th class="ctr">Shipping Terms</th><th class="ctr">Payment Terms</th><th class="ctr">Payment Via</th></tr>
            <tr><td>{{ $order->sales_person ?: '—' }}</td><td>{{ $order->shipping_method ?: '—' }}</td><td>{{ $order->shipping_term ?: '—' }}</td><td>{{ $order->payment_term ?: '—' }}</td><td>{{ $order->payment_term_via ?: '—' }}</td></tr>
        </table>
        <table class="items">
            <tr><th>Sr.</th><th>Item</th><th>Size</th><th>Stock / Color / Finishing</th><th class="ctr">Qty</th><th class="num">Unit Price</th><th class="num">Line Total</th></tr>
            @forelse($items as $i => $it)
                <tr>
                    <td class="ctr">{{ $i + 1 }}</td>
                    <td><b>{{ $it['box_style'] ?? 'Custom Packaging' }}</b>@if(!empty($it['additional_info']))<div style="color:#64748b;font-size:.76rem">{{ $it['additional_info'] }}</div>@endif</td>
                    <td>{{ ($it['length'] ?? '') !== '' ? ($it['length'].' x '.($it['width'] ?? '').' x '.($it['height'] ?? '').' '.($it['unit'] ?? '')) : '—' }}</td>
                    <td>{{ implode(', ', array_filter([$it['stock'] ?? null, $it['color'] ?? null, $it['finishing'] ?? null])) ?: '—' }}</td>
                    <td class="ctr">{{ rtrim(rtrim(number_format((float)($it['qty'] ?? 0), 2, '.', ','), '0'), '.') }}</td>
                    <td class="num">{{ $money($it['unit_price'] ?? 0) }}</td>
                    <td class="num">{{ $money($it['line_total'] ?? 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;color:#94a3b8">See the PDF for item details.</td></tr>
            @endforelse
        </table>
        <div class="tot">
            <table class="totals">
                <tr><td class="k">Sub Total</td><td class="v">{{ $money($order->sub_total) }}</td></tr>
                @if((float)$order->package_price > 0)<tr><td class="k">Packaging</td><td class="v">{{ $money($order->package_price) }}</td></tr>@endif
                @if((float)$order->rush_charges > 0)<tr><td class="k">Rush Charges</td><td class="v">{{ $money($order->rush_charges) }}</td></tr>@endif
                @if((float)$order->discount > 0)<tr><td class="k">Discount</td><td class="v">- {{ $money($order->discount) }}</td></tr>@endif
                <tr class="g"><td>Total</td><td class="v">{{ $money($order->total) }}</td></tr>
                @if($paidAmt > 0)<tr><td class="k">Paid</td><td class="v" style="color:#15803d">{{ $money($paidAmt) }}</td></tr>
                <tr><td class="k">Balance Due</td><td class="v" style="color:#b91c1c;font-weight:800">{{ $money($due) }}</td></tr>@endif
            </table>
        </div>
        <div class="thanks"><b>Thank you for your business</b>If you have any questions or require further assistance, please contact our customer service team between 8.00am and 7.00pm CST, Monday to Friday.<br>&#9742; {{ $brand['phones'] }} &nbsp;&middot;&nbsp; &#9993; {{ $brand['email'] }} &nbsp;&middot;&nbsp; {{ $brand['site'] }}</div>
        <div style="text-align:center;margin-top:1rem"><a class="btn" href="{{ route('invoice_portal.pdf', $order->id) }}"><i class="fas fa-download"></i> Download PDF</a></div>
    </div>

    <div class="card pay">
        @if($isPaid)
            <div class="paidbox"><i class="fas fa-check-circle"></i><h3>Invoice paid</h3><p>Thank you! Invoice {{ $label }} is fully paid.</p></div>
        @else
            <h2>Pay Invoice {{ $label }}</h2>
            <div style="color:var(--mut);font-size:.82rem">Amount due</div>
            <div class="amt">{{ $money($due) }}</div>

            <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#7c8aa0;margin-bottom:.45rem">Payment method</div>
            <div class="pm-tabs">
                <button type="button" class="pm-tab {{ $cardReady ? 'active' : '' }}" data-pane="card" {{ $cardReady ? '' : 'disabled' }}><i class="far fa-credit-card"></i> Credit / Debit Card</button>
                <button type="button" class="pm-tab pp {{ !$cardReady && $paypalClientId ? 'active' : '' }}" data-pane="paypal" {{ $paypalClientId ? '' : 'disabled' }}><i class="fab fa-paypal"></i> PayPal</button>
            </div>

            <div class="pm-pane" data-pane="card" {{ $cardReady ? '' : 'hidden' }}>
                <div class="cards">
                    @foreach(['visa.png','master-card.png','american-express.png','discover.png'] as $ci)@if(is_file(public_path('box_assets/img/'.$ci)))<img src="{{ asset('box_assets/img/'.$ci) }}" alt="">@endif @endforeach
                </div>
                <form method="POST" action="{{ route('invoice_portal.pay', $order->id) }}" id="payForm" autocomplete="on">
                    {{ csrf_field() }}
                    <div class="f"><label>Name on card</label><input name="card_name" value="{{ old('card_name', $b['name'] ?? '') }}" autocomplete="cc-name" required></div>
                    <div class="f"><label>Card number</label><input name="card_number" id="cardNumber" inputmode="numeric" autocomplete="cc-number" placeholder="1234 5678 9012 3456" maxlength="23" required></div>
                    <div class="two">
                        <div class="f"><label>Expiry (MM/YY)</label><input name="card_exp" id="cardExp" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5" required></div>
                        <div class="f"><label>CVC</label><input name="card_cvv" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="4" required></div>
                    </div>
                    <button class="btn p" type="submit" id="payBtn"><i class="fas fa-lock"></i> Pay {{ $money($due) }}</button>
                </form>
                <div class="secure"><i class="fas fa-shield-alt"></i> Secure payment. Your card details are sent directly to our payment processor and are never stored.</div>
            </div>

            <div class="pm-pane" data-pane="paypal" {{ !$cardReady && $paypalClientId ? '' : 'hidden' }}>
                @if($paypalClientId)
                    <div class="pp-note">Pay <strong>{{ $money($due) }}</strong> with your PayPal balance, bank or card through PayPal.@if($paypalMode !== 'live')<span class="sandbox">Sandbox</span>@endif</div>
                    <div id="paypalButtons"></div>
                    <div class="pp-wait" id="ppWait"><i class="fas fa-spinner fa-spin"></i> Confirming your payment…</div>
                    <div class="pp-err" id="ppErr"></div>
                @else
                    <div class="pp-note">PayPal is not available for this invoice.</div>
                @endif
            </div>
            @if(!$cardReady && !$paypalClientId)
                <div class="pp-note">Online payment is not available right now. Please contact us at {{ $brand['email'] }}.</div>
            @endif
        @endif
        @if($order->payments->count())
            <div class="hist"><strong>Payment history</strong>
                @foreach($order->payments as $p)
                    <div><span>{{ $p->paid_at->format('d M Y') }}{{ $p->method ? ' · '.$p->method : '' }}{{ $p->card_last4 ? ' ****'.$p->card_last4 : '' }}</span><span style="font-weight:700">{{ $money($p->amount) }}</span></div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
@section('scripts')
@if(!$isPaid && $paypalClientId)
<script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ strtoupper($order->currency ?: 'USD') }}&intent=capture&disable-funding=paylater,venmo" data-namespace="paypalSdk"></script>
@endif
<script>
(function(){
    // Payment method tabs
    document.querySelectorAll('.pm-tab').forEach(function(t){
        t.addEventListener('click', function(){
            if (t.disabled) return;
            document.querySelectorAll('.pm-tab').forEach(function(x){ x.classList.toggle('active', x === t); });
            document.querySelectorAll('.pm-pane').forEach(function(p){ p.hidden = p.getAttribute('data-pane') !== t.getAttribute('data-pane'); });
        });
    });
    // PayPal buttons
    var host = document.getElementById('paypalButtons');
    if (host && window.paypalSdk) {
        var csrf = @json(csrf_token());
        var errBox = document.getElementById('ppErr'), wait = document.getElementById('ppWait');
        var showErr = function(m){ errBox.textContent = m; errBox.style.display = 'block'; wait.style.display = 'none'; };
        var post = function(url, body){ return fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body: JSON.stringify(body||{})}).then(function(r){ return r.json().then(function(j){ if(!r.ok) throw new Error(j.error || 'Request failed'); return j; }); }); };
        window.paypalSdk.Buttons({
            style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'pay', height: 45 },
            createOrder: function(){ errBox.style.display = 'none'; return post(@json(route('invoice_portal.paypal_create', $order->id))).then(function(j){ return j.id; }); },
            onApprove: function(data){
                wait.style.display = 'block';
                return post(@json(route('invoice_portal.paypal_capture', $order->id)), {orderID: data.orderID})
                    .then(function(j){ window.location.href = j.redirect; })
                    .catch(function(e){ showErr(e.message || 'Payment could not be completed.'); });
            },
            onError: function(e){ showErr('PayPal error: ' + (e && e.message ? e.message : 'please try again.')); },
            onCancel: function(){ wait.style.display = 'none'; }
        }).render('#paypalButtons');
    } else if (host) {
        host.innerHTML = '<div class="pp-note">PayPal could not be loaded. Please disable ad-blockers or try again.</div>';
    }

    var n = document.getElementById('cardNumber'), x = document.getElementById('cardExp'), f = document.getElementById('payForm');
    if (n) n.addEventListener('input', function(){ var v = this.value.replace(/\D/g,'').slice(0,19); this.value = v.replace(/(\d{4})(?=\d)/g,'$1 '); });
    if (x) x.addEventListener('input', function(){ var v = this.value.replace(/\D/g,'').slice(0,4); this.value = v.length > 2 ? v.slice(0,2) + '/' + v.slice(2) : v; });
    if (f) f.addEventListener('submit', function(){ var b = document.getElementById('payBtn'); b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…'; });
})();
</script>
@endsection
