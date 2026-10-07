@extends('crm.layout')
@section('title', 'Orders')

@section('content')
<style>
.or-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.3rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px}
.or-hero h2{margin:0;font-size:1.25rem;color:#0f172a}
.or-muted{color:#8796aa;font-size:.75rem}
.or-add{display:inline-flex;align-items:center;gap:.45rem;min-height:42px;padding:.63rem .95rem;border-radius:11px;background:var(--primary-purple);color:#fff;text-decoration:none;font-weight:850;box-shadow:0 8px 20px var(--primary-shadow)}
.or-wrap{background:#fff;border:1px solid #e4eaf1;border-radius:15px;box-shadow:0 8px 26px rgba(15,23,42,.055);overflow-x:auto}
.or-table{width:100%;border-collapse:collapse;min-width:900px}
.or-table th{padding:.8rem .85rem;background:#f7f9fc;border-bottom:2px solid var(--primary-soft);text-align:left;color:#718096;font-size:.62rem;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
.or-table td{padding:.85rem .85rem;border-bottom:1px solid #edf1f5;vertical-align:middle;font-size:.78rem;color:#334155;white-space:nowrap}
.or-table tbody tr:hover{background:var(--primary-soft)}
.or-paid{display:inline-flex;padding:.3rem .6rem;border-radius:6px;font-size:.66rem;font-weight:850;text-transform:uppercase}
.or-paid.unpaid{background:#ef4444;color:#fff}
.or-paid.paid{background:#dcfce7;color:#166534}
.or-menu{position:relative;display:inline-block}
.or-menu-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .8rem;border-radius:9px;border:none;cursor:pointer;background:#33415a;color:#fff;font-weight:800;font-size:.68rem;text-transform:uppercase;letter-spacing:.02em}
.or-menu-btn:hover{background:#26334a}
.or-menu-list{position:fixed;left:0;top:0;z-index:1000;min-width:185px;max-height:calc(100vh - 16px);overflow-y:auto;background:#fff;border:1px solid #e4eaf1;border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.14);padding:.3rem;display:flex;flex-direction:column;gap:.15rem}
.or-menu-list[hidden]{display:none}
.or-menu-list a,.or-menu-list button{display:flex;align-items:center;gap:.55rem;padding:.55rem .6rem;border-radius:7px;font-size:.76rem;font-weight:700;color:#334155;text-decoration:none;background:none;border:0;width:100%;text-align:left;cursor:pointer}
.or-menu-list a:hover,.or-menu-list button:not(.or-menu-delete):hover{background:var(--primary-soft);color:var(--primary-purple)}
.or-menu-list i{width:16px;text-align:center;color:#94a3b8}
.or-menu-delete{color:#b91c1c}.or-menu-delete:hover{background:#fee2e2}.or-menu-delete:hover i{color:#b91c1c}
.or-menu-list form{margin:0}
.or-pp{margin-top:.3rem;font-size:.62rem;font-weight:800;color:#1d4ed8;white-space:nowrap}
.or-pp i{margin-right:.2rem}
.or-pp.cca{color:#0f766e}
.or-pp.ok{color:#15803d}
.or-pp.inv{color:#6d28d9}
.or-paysum{margin-top:.25rem;font-size:.62rem;font-weight:700;color:#64748b;white-space:nowrap}
.pm-overlay{position:fixed;inset:0;z-index:1100;background:rgba(15,23,42,.45);display:none;align-items:center;justify-content:center;padding:1rem}
.pm-overlay.show{display:flex}
.pm-modal{width:100%;max-width:560px;background:#fff;border-radius:14px;box-shadow:0 24px 60px rgba(15,23,42,.25);overflow:hidden;max-height:calc(100vh - 2rem);display:flex;flex-direction:column}
.pm-head{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.1rem;border-bottom:1px solid #e4eaf1;background:#fafbfd}
.pm-head h3{margin:0;font-size:.95rem;color:#0f172a}
.pm-head .sub{font-size:.72rem;color:#64748b;margin-top:.15rem}
.pm-close{border:none;background:#eef2f7;color:#475569;width:30px;height:30px;border-radius:8px;cursor:pointer}
.pm-body{padding:1rem 1.1rem;overflow-y:auto}
.pm-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;margin-bottom:.9rem}
.pm-stat{border:1px solid #e4eaf1;border-radius:10px;padding:.6rem .75rem;background:#f8fafc}
.pm-stat .k{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#8795a7}
.pm-stat .v{font-size:.95rem;font-weight:800;color:#0f172a;margin-top:.15rem}
.pm-stat.due .v{color:#b91c1c}.pm-stat.paid .v{color:#15803d}
.pm-sec{font-size:.64rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#475569;margin:.2rem 0 .5rem}
.pm-list{border:1px solid #e4eaf1;border-radius:10px;overflow:hidden;margin-bottom:.9rem}
.pm-row{display:flex;align-items:center;gap:.6rem;padding:.5rem .75rem;border-bottom:1px solid #eef1f5;font-size:.76rem;color:#334155}
.pm-row:last-child{border-bottom:none}
.pm-row .d{color:#64748b;min-width:76px}.pm-row .a{font-weight:800;color:#0f172a;min-width:90px}.pm-row .m{flex:1;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pm-row form{margin:0}.pm-row .del{border:none;background:none;color:#b91c1c;cursor:pointer;font-size:.72rem;padding:.2rem .3rem;border-radius:5px}.pm-row .del:hover{background:#fee2e2}
.pm-empty{padding:.7rem .75rem;font-size:.76rem;color:#94a3b8}
.pm-grid{display:grid;grid-template-columns:1fr 1fr;gap:.55rem .7rem}
.pm-f{display:flex;flex-direction:column;gap:.2rem}.pm-f.full{grid-column:1/-1}
.pm-f label{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#8795a7}
.pm-f input,.pm-f select{height:34px;padding:0 .6rem;border:1px solid #d6dde6;border-radius:7px;font:inherit;font-size:.8rem;color:#0f172a;outline:0}
.pm-f input:focus,.pm-f select:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.pm-foot{display:flex;justify-content:flex-end;gap:.5rem;margin-top:.9rem}
.pm-btn{display:inline-flex;align-items:center;gap:.4rem;height:36px;padding:0 1rem;border-radius:8px;border:1px solid #d6dde6;background:#fff;color:#334155;font-weight:700;font-size:.78rem;cursor:pointer}
.pm-btn.primary{background:var(--primary-purple);color:#fff;border-color:var(--primary-purple)}
.pm-tabs{display:flex;gap:.4rem;margin:.2rem 0 .8rem;border-bottom:1px solid #e4eaf1}
.pm-tab{border:none;background:none;padding:.5rem .7rem;font-size:.74rem;font-weight:800;color:#64748b;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;display:inline-flex;align-items:center;gap:.4rem}
.pm-tab.active{color:var(--primary-purple);border-bottom-color:var(--primary-purple)}
.pm-bill{font-size:.72rem;color:#475569;background:#f8fafc;border:1px solid #e4eaf1;border-radius:8px;padding:.5rem .7rem;margin-bottom:.7rem;line-height:1.4}
.pm-bill i{color:#94a3b8;margin-right:.25rem}
.pm-secure{margin-top:.6rem;font-size:.66rem;color:#94a3b8;text-align:right}
.pm-secure i{color:#16a34a;margin-right:.2rem}
.pm-fullpaid{padding:.7rem .85rem;border-radius:10px;background:#dcfce7;color:#166534;font-size:.78rem;font-weight:700}
.or-empty{text-align:center;padding:2.5rem;color:#94a3b8}
.or-filters{display:flex;flex-wrap:wrap;align-items:center;gap:.7rem;padding:.85rem 1rem;margin-bottom:1rem;background:#fff;border:1px solid #e4eaf1;border-radius:15px;box-shadow:0 8px 26px rgba(15,23,42,.055)}
.or-search{position:relative;flex:1 1 320px;min-width:240px}
.or-search i{position:absolute;left:.95rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.82rem}
.or-search input{width:100%;padding:.7rem .9rem .7rem 2.4rem;border:1px solid #e4eaf1;border-radius:11px;background:#f8fafc;font-size:.8rem;color:#334155;outline:none}
.or-ctrl{display:flex;align-items:center;gap:.4rem;padding:.55rem .85rem;border:1px solid #e4eaf1;border-radius:11px;background:#f8fafc}
.or-ctrl i{color:#94a3b8;font-size:.8rem}
.or-ctrl select,.or-ctrl input{border:none;background:transparent;font-size:.8rem;color:#334155;outline:none;cursor:pointer}
.or-ctrl input[type=date]{cursor:text;min-width:120px}
.or-ctrl .dash{color:#cbd5e1}
.or-search input:focus,.or-ctrl:focus-within{border-color:var(--primary-purple);background:#fff}
.or-fbtn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1rem;border-radius:11px;border:none;cursor:pointer;font-weight:800;font-size:.75rem}
.or-fbtn.apply{background:var(--primary-purple);color:#fff}
.or-fbtn.clear{background:#eef2f7;color:#475569;text-decoration:none}
</style>

<div class="or-hero">
    <div><h2>Orders</h2><div class="or-muted">Manual orders &amp; invoices.</div></div>
    <a href="{{ route('crm.orders.manual.create') }}" class="or-add"><i class="fas fa-plus"></i> Create Order</a>
</div>

<form class="or-filters" method="GET" action="{{ route('crm.orders.manual.index') }}">
    <div class="or-search">
        <i class="fas fa-search"></i>
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by invoice #, customer, or order ID...">
    </div>

    <div class="or-ctrl">
        <select name="status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="paid" {{ ($filters['status'] ?? '')==='paid' ? 'selected' : '' }}>Paid</option>
            <option value="unpaid" {{ ($filters['status'] ?? '')==='unpaid' ? 'selected' : '' }}>Unpaid</option>
        </select>
    </div>

    <div class="or-ctrl">
        <select name="customer" onchange="this.form.submit()">
            <option value="">All Customers</option>
            @foreach($customers as $c)
                <option value="{{ $c }}" {{ ($filters['customer'] ?? '')===$c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
        </select>
    </div>

    <div class="or-ctrl">
        <i class="far fa-calendar"></i>
        <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" aria-label="From date">
        <span class="dash">–</span>
        <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" aria-label="To date">
    </div>

    <button type="submit" class="or-fbtn apply"><i class="fas fa-filter"></i> Apply</button>
    @if(array_filter($filters ?? []))
        <a href="{{ route('crm.orders.manual.index') }}" class="or-fbtn clear">Clear</a>
    @endif
</form>

<div class="or-wrap">
    <table class="or-table">
        <thead><tr>
            <th>Enquiry #</th><th>Customer ID</th><th>Invoice #</th><th>Customer Name</th>
            <th>Status</th><th>Date</th><th>User</th><th>Amount</th><th>Actions</th>
        </tr></thead>
        <tbody>
        @forelse($orders as $order)
            <tr>
                <td><strong>{{ $order->enquiry_number ?: '—' }}</strong></td>
                <td>{{ $order->customer_id ?: '—' }}</td>
                <td>{{ $order->invoice_number ? 'TCB-'.$order->invoice_number : '—' }}</td>
                <td>{{ optional($order->customer)->name ?: (data_get($order->billing,'name') ?: '—') }}</td>
                <td>
                    <span class="or-paid {{ $order->invoice_status }}">#{{ strtoupper($order->invoice_status) }}</span>
                    @if($order->paypal_sent_at)
                        @php $ppSt = strtoupper($order->paypal_invoice_status ?: 'SENT'); $ppPaid = in_array($ppSt, ['PAID','MARKED_AS_PAID']); @endphp
                        <div class="or-pp {{ $ppPaid ? 'ok' : '' }}" title="PayPal request sent to {{ $order->paypal_sent_to }} on {{ $order->paypal_sent_at->format('d-M-Y H:i') }} · PayPal status: {{ $ppSt }}"><i class="fab fa-paypal"></i> {{ $ppPaid ? 'Paid' : ucfirst(strtolower(str_replace('_',' ',$ppSt))) }} {{ $order->paypal_sent_at->format('d-M') }}</div>
                    @endif
                    @if($order->invoice_sent_at)
                        <div class="or-pp inv" title="Invoice sent to {{ $order->invoice_sent_to }} on {{ $order->invoice_sent_at->format('d-M-Y H:i') }}"><i class="fas fa-paper-plane"></i> Inv {{ $order->invoice_sent_at->format('d-M') }}</div>
                    @endif
                    @if($order->cca_sent_at)
                        <div class="or-pp cca" title="CCA form sent to {{ $order->cca_sent_to }} on {{ $order->cca_sent_at->format('d-M-Y H:i') }}"><i class="fas fa-credit-card"></i> CCA {{ $order->cca_sent_at->format('d-M') }}</div>
                    @endif
                </td>
                <td>{{ optional($order->invoice_date)->format('d-F-Y') ?: $order->created_at->format('d-F-Y') }}</td>
                <td>{{ $order->user_name ?: optional($order->creator)->name }}</td>
                <td>
                    <strong>{{ $order->currency }} {{ number_format($order->total,2) }}</strong>
                    @if($order->payments->count())
                        <div class="or-paysum">Paid {{ number_format($order->paidAmount(),2) }} · Due {{ number_format($order->balanceDue(),2) }}</div>
                    @endif
                </td>
                <td>
                    <div class="or-menu">
                        <button type="button" class="or-menu-btn" aria-haspopup="true"><i class="fas fa-ellipsis-h"></i> Actions</button>
                        <div class="or-menu-list" hidden>
                            @php $canManagePaid = Auth::guard('crm')->user()->isAdmin(); $lockedPaid = $order->invoice_status === 'paid' && !$canManagePaid; @endphp
                            @if(!$lockedPaid)
                            <a href="{{ route('crm.orders.manual.edit',$order->id) }}"><i class="fas fa-pen"></i> Edit</a>
                            @endif
                            <a href="{{ route('crm.orders.manual.pdf',$order->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> View PDF</a>
                            @php $ppEmail = $order->customerEmail(); @endphp
                            <form method="POST" action="{{ route('crm.orders.manual.send_invoice',$order->id) }}" class="or-direct-form" data-email="{{ $ppEmail }}" data-what="invoice">
                                {{ csrf_field() }}
                                <input type="hidden" name="email" value="">
                                <button type="submit"><i class="fas fa-paper-plane"></i> {{ $order->invoice_sent_at ? 'Resend Invoice' : 'Send Invoice' }}</button>
                            </form>
                            @php $ppEmail = $order->customerEmail(); $ppAmount = strtoupper($order->currency ?: 'USD').' '.number_format((float)$order->total, 2); $isPaidOrder = $order->invoice_status === 'paid'; @endphp
                            @if(!$isPaidOrder)
                            <form method="POST" action="{{ route('crm.orders.manual.paypal_request',$order->id) }}" class="or-paypal-form"
                                  data-email="{{ $ppEmail }}" data-amount="{{ $ppAmount }}" data-resend="{{ $order->paypal_sent_at ? 1 : 0 }}">
                                {{ csrf_field() }}
                                <input type="hidden" name="email" value="">
                                <button type="submit"><i class="fab fa-paypal"></i> {{ $order->paypal_sent_at ? 'Resend PayPal Request' : 'Send PayPal Request' }}</button>
                            </form>
                            @if($order->paypal_invoice_id)
                                <form method="POST" action="{{ route('crm.orders.manual.paypal_sync',$order->id) }}">
                                    {{ csrf_field() }}
                                    <button type="submit"><i class="fas fa-sync-alt"></i> Sync PayPal Status</button>
                                </form>
                            @endif
                            @if($order->paypal_invoice_url)
                                <a href="{{ $order->paypal_invoice_url }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Open PayPal Invoice</a>
                            @endif
                            @endif
                            @if(!$isPaidOrder)
                            <form method="POST" action="{{ route('crm.orders.manual.send_cca',$order->id) }}" class="or-direct-form" data-email="{{ $ppEmail }}" data-what="CCA form">
                                {{ csrf_field() }}
                                <input type="hidden" name="email" value="">
                                <button type="submit"><i class="fas fa-credit-card"></i> {{ $order->cca_sent_at ? 'Resend CCA' : 'Send CCA' }}</button>
                            </form>
                            <a href="{{ route('crm.orders.manual.cca',$order->id) }}" target="_blank"><i class="fas fa-file-signature"></i> View CCA Form</a>
                            @endif
                            <form method="POST" action="{{ route('crm.orders.manual.send_portal_login',$order->id) }}" class="or-direct-form" data-email="{{ $ppEmail }}" data-what="portal login details">
                                {{ csrf_field() }}
                                <input type="hidden" name="email" value="">
                                <button type="submit"><i class="fas fa-key"></i> Send Login Details</button>
                            </form>
                            @php
                                $pmPaid = $order->paidAmount(); $pmDue = $order->balanceDue();
                                $pmJson = [
                                    'id' => $order->id,
                                    'label' => $order->invoice_number ? 'TCB-'.$order->invoice_number : '#'.$order->id,
                                    'customer' => optional($order->customer)->name ?: (data_get($order->billing,'name') ?: ''),
                                    'currency' => strtoupper($order->currency ?: 'USD'),
                                    'total' => (float) $order->total, 'paid' => $pmPaid, 'due' => $pmDue,
                                    'status' => $order->invoice_status,
                                    'action' => route('crm.orders.manual.payments.store', $order->id),
                                    'charge' => route('crm.orders.manual.charge_card', $order->id),
                                    'billing' => trim(implode(', ', array_filter([data_get($order->billing,'name'), data_get($order->billing,'street'), data_get($order->billing,'city'), data_get($order->billing,'state'), data_get($order->billing,'zip'), data_get($order->billing,'country')]))),
                                    'payments' => $order->payments->map(function ($p) use ($order) {
                                        return [
                                            'id' => $p->id, 'date' => $p->paid_at->format('d M Y'), 'amount' => (float) $p->amount,
                                            'method' => $p->method, 'reference' => $p->reference, 'note' => $p->note,
                                            'delete' => route('crm.orders.manual.payments.destroy', [$order->id, $p->id]),
                                        ];
                                    })->values(),
                                ];
                            @endphp
                            <button type="button" class="or-pay-btn" data-pay='@json($pmJson)'><i class="fas {{ $isPaidOrder ? 'fa-receipt' : 'fa-money-bill-wave' }}"></i> {{ $isPaidOrder ? 'Payment History' : 'Payment' }}</button>
                            @if(!$lockedPaid)
                            <form method="POST" action="{{ route('crm.orders.manual.destroy',$order->id) }}" onsubmit="return confirm('Delete this order?')">
                                {{ csrf_field() }}<input type="hidden" name="_method" value="DELETE">
                                <button class="or-menu-delete" type="submit"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="or-empty">No orders yet. Click “Create Order”, or use an inquiry’s “Create Order” action after a proposal is completed.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="pm-overlay" id="pmOverlay">
    <div class="pm-modal" role="dialog" aria-modal="true">
        <div class="pm-head">
            <div><h3 id="pmTitle">Payment</h3><div class="sub" id="pmSub"></div></div>
            <button type="button" class="pm-close" id="pmClose" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="pm-body">
            <div class="pm-stats">
                <div class="pm-stat"><div class="k">Total</div><div class="v" id="pmTotal"></div></div>
                <div class="pm-stat paid"><div class="k">Paid</div><div class="v" id="pmPaid"></div></div>
                <div class="pm-stat due"><div class="k">Balance Due</div><div class="v" id="pmDue"></div></div>
            </div>
            <div class="pm-sec">Payment history</div>
            <div class="pm-list" id="pmList"></div>
            <div id="pmFormWrap">
                <div class="pm-tabs">
                    <button type="button" class="pm-tab active" data-tab="card"><i class="fas fa-credit-card"></i> Charge Card (Vault)</button>
                    <button type="button" class="pm-tab" data-tab="manual"><i class="fas fa-pen"></i> Record Manual Payment</button>
                </div>
                <form method="POST" id="pmCardForm" class="pm-pane" data-pane="card" autocomplete="on">
                    {{ csrf_field() }}
                    <div class="pm-bill"><i class="fas fa-map-marker-alt"></i> Billing &amp; shipping are taken from the order: <span id="pmBilling"></span></div>
                    <div class="pm-grid">
                        <div class="pm-f full"><label>Cardholder Name</label><input name="card_name" id="pmCardName" maxlength="120" autocomplete="cc-name" placeholder="Name on card"></div>
                        <div class="pm-f full"><label>Card Number</label><input name="card_number" id="pmCardNumber" inputmode="numeric" autocomplete="cc-number" maxlength="23" placeholder="1234 5678 9012 3456" required></div>
                        <div class="pm-f"><label>Expiry (MM/YY)</label><input name="card_exp" id="pmCardExp" inputmode="numeric" autocomplete="cc-exp" maxlength="5" placeholder="MM/YY" required></div>
                        <div class="pm-f"><label>CVV</label><input name="card_cvv" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="123" required></div>
                        <div class="pm-f full"><label>Amount to charge <span class="pmCurLbl"></span></label><input type="number" name="amount" id="pmChargeAmount" step="0.01" min="0.01" required></div>
                    </div>
                    <div class="pm-foot">
                        <button type="button" class="pm-btn" id="pmChargeFull">Charge full balance</button>
                        <button type="submit" class="pm-btn primary" id="pmChargeBtn"><i class="fas fa-lock"></i> Charge card</button>
                    </div>
                    <div class="pm-secure"><i class="fas fa-shield-alt"></i> Card details go directly to the payment gateway and are never stored in the CRM.</div>
                </form>
                <form method="POST" id="pmForm" class="pm-pane" data-pane="manual" hidden>
                    {{ csrf_field() }}
                    <div class="pm-grid">
                        <div class="pm-f"><label>Amount <span class="pmCurLbl"></span></label><input type="number" name="amount" id="pmAmount" step="0.01" min="0.01" required></div>
                        <div class="pm-f"><label>Date</label><input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required></div>
                        <div class="pm-f"><label>Method</label>
                            <select name="method">
                                <option value="">Select</option>
                                @foreach(['PayPal','Credit/Debit Card','Wire Transfer','CCA','Zelle','Cash','Other'] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                            </select>
                        </div>
                        <div class="pm-f"><label>Reference / Txn ID</label><input name="reference" maxlength="255" placeholder="Optional"></div>
                        <div class="pm-f full"><label>Note</label><input name="note" maxlength="2000" placeholder="Optional"></div>
                    </div>
                    <div class="pm-foot">
                        <button type="button" class="pm-btn" id="pmFull">Pay full balance</button>
                        <button type="submit" class="pm-btn primary"><i class="fas fa-check"></i> Record payment</button>
                    </div>
                </form>
            </div>
            <div class="pm-fullpaid" id="pmFullPaid" hidden><i class="fas fa-check-circle"></i> This order is fully paid.</div>
        </div>
    </div>
</div>

<script>
(function(){
    // ---- Payment modal
    var canDeletePayments = @json(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || (method_exists(Auth::guard('crm')->user(),'isAccounts') && Auth::guard('crm')->user()->isAccounts()));
    var csrf = @json(csrf_token());
    var overlay = document.getElementById('pmOverlay');
    function money(n){ return (isFinite(n)?n:0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
    function openPayment(d){
        document.getElementById('pmTitle').textContent = 'Payment — ' + d.label;
        document.getElementById('pmSub').textContent = (d.customer ? d.customer + ' · ' : '') + d.currency + ' ' + money(d.total);
        document.getElementById('pmTotal').textContent = d.currency + ' ' + money(d.total);
        document.getElementById('pmPaid').textContent = d.currency + ' ' + money(d.paid);
        document.getElementById('pmDue').textContent = d.currency + ' ' + money(d.due);
        document.querySelectorAll('.pmCurLbl').forEach(function(el){ el.textContent = '(' + d.currency + ')'; });
        document.getElementById('pmBilling').textContent = d.billing || 'no billing address on the order';
        var cform = document.getElementById('pmCardForm');
        cform.action = d.charge; cform.reset();
        document.getElementById('pmCardName').value = d.customer || '';
        var camt = document.getElementById('pmChargeAmount');
        camt.value = d.due > 0 ? d.due.toFixed(2) : ''; camt.max = d.due > 0 ? d.due.toFixed(2) : '';
        document.getElementById('pmChargeFull').onclick = function(){ camt.value = d.due.toFixed(2); };
        var cbtn = document.getElementById('pmChargeBtn'); cbtn.disabled = false; cbtn.innerHTML = '<i class="fas fa-lock"></i> Charge card';
        switchTab('card');
        var list = document.getElementById('pmList');
        if (!d.payments.length) { list.innerHTML = '<div class="pm-empty">No payments recorded yet.</div>'; }
        else {
            list.innerHTML = d.payments.map(function(p){
                var meta = [p.method, p.reference, p.note].filter(Boolean).join(' · ');
                var del = canDeletePayments ? '<form method="POST" action="'+esc(p.delete)+'" onsubmit="return confirm(\'Remove this payment?\')"><input type="hidden" name="_token" value="'+esc(csrf)+'"><input type="hidden" name="_method" value="DELETE"><button type="submit" class="del" title="Remove"><i class="fas fa-trash-alt"></i></button></form>' : '';
                return '<div class="pm-row"><span class="d">'+esc(p.date)+'</span><span class="a">'+esc(d.currency)+' '+money(p.amount)+'</span><span class="m" title="'+esc(meta)+'">'+esc(meta || '—')+'</span>'+del+'</div>';
            }).join('');
        }
        var form = document.getElementById('pmForm');
        form.action = d.action;
        var amt = document.getElementById('pmAmount');
        amt.value = ''; amt.max = d.due > 0 ? d.due.toFixed(2) : '';
        document.getElementById('pmFull').onclick = function(){ amt.value = d.due.toFixed(2); amt.focus(); };
        var full = d.due <= 0.009 && d.total > 0;
        document.getElementById('pmFormWrap').hidden = full;
        document.getElementById('pmFullPaid').hidden = !full;
        overlay.classList.add('show');
        if (!full) setTimeout(function(){ amt.focus(); }, 50);
    }
    function closePayment(){ overlay.classList.remove('show'); document.getElementById('pmCardForm').reset(); }
    function switchTab(name){
        document.querySelectorAll('.pm-tab').forEach(function(t){ t.classList.toggle('active', t.getAttribute('data-tab') === name); });
        document.querySelectorAll('.pm-pane').forEach(function(p){ p.hidden = p.getAttribute('data-pane') !== name; });
    }
    document.querySelectorAll('.pm-tab').forEach(function(t){ t.addEventListener('click', function(){ switchTab(t.getAttribute('data-tab')); }); });
    // Card input formatting
    document.getElementById('pmCardNumber').addEventListener('input', function(){
        var v = this.value.replace(/\D/g,'').slice(0,19); this.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
    });
    document.getElementById('pmCardExp').addEventListener('input', function(){
        var v = this.value.replace(/\D/g,'').slice(0,4); this.value = v.length > 2 ? v.slice(0,2) + '/' + v.slice(2) : v;
    });
    document.getElementById('pmCardForm').addEventListener('submit', function(){
        var b = document.getElementById('pmChargeBtn'); b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Charging…';
    });
    document.getElementById('pmClose').addEventListener('click', closePayment);
    overlay.addEventListener('click', function(e){ if (e.target === overlay) closePayment(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closePayment(); });
    document.querySelectorAll('.or-pay-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            closeMenus();
            try { openPayment(JSON.parse(btn.getAttribute('data-pay'))); } catch (err) { alert('Could not open payment dialog.'); }
        });
    });

    function closeMenus(){
        document.querySelectorAll('.or-menu-list').forEach(function(l){ l.setAttribute('hidden',''); });
    }
    // Fixed-position the menu so the table's horizontal scroll container never clips it.
    function openMenu(btn, list){
        var rect = btn.getBoundingClientRect();
        list.style.visibility = 'hidden';
        list.removeAttribute('hidden');
        var w = list.offsetWidth, h = list.offsetHeight;
        var left = Math.max(8, Math.min(rect.right - w, window.innerWidth - w - 8));
        var above = window.innerHeight - rect.bottom < h + 8 && rect.top > h + 8;
        var top = above ? rect.top - h - 4 : rect.bottom + 4;
        list.style.left = left + 'px';
        list.style.top = Math.max(8, Math.min(top, window.innerHeight - h - 8)) + 'px';
        list.style.visibility = '';
    }
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.or-menu-btn');
        if(btn){
            var list = btn.nextElementSibling;
            var isOpen = list && !list.hasAttribute('hidden');
            closeMenus();
            if(list && !isOpen) openMenu(btn, list);
            return;
        }
        if(!e.target.closest('.or-menu')) closeMenus();
    });
    // PayPal / CCA: sends immediately. Only asks for an email when the order has none at all.
    document.querySelectorAll('.or-paypal-form, .or-direct-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            var email = (form.getAttribute('data-email') || '').trim();
            var what = form.getAttribute('data-what') || 'PayPal request';
            if(!email){
                var entered = window.prompt('This order has no customer email. Enter the email to send the ' + what + ' to:', '');
                if(entered === null){ e.preventDefault(); return; }
                email = entered.trim();
                if(!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)){ alert('Please enter a valid email address.'); e.preventDefault(); return; }
            }
            form.querySelector('input[name=email]').value = email;
            var btn = form.querySelector('button[type=submit]');
            if(btn){ btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…'; }
        });
    });
    document.addEventListener('scroll', closeMenus, true);
    window.addEventListener('resize', closeMenus);
})();
</script>
@endsection
