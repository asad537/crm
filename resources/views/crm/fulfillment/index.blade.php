@extends('crm.layout')

@section('title', $pageTitle)

@section('content')
@php
    $currentUser = Auth::guard('crm')->user();
    $carrierLabels = [
        'ltl_freight' => 'LTL Freight',
        'fedex' => 'FedEx',
        'dhl' => 'DHL',
        'usps' => 'USPS',
        'ups' => 'UPS',
    ];
@endphp

<style>
    .fulfillment-shell { max-width:1450px; margin:0 auto; }
    .fulfillment-head { display:flex; justify-content:space-between; align-items:center; gap:18px; margin-bottom:22px; }
    .fulfillment-eyebrow { color:var(--primary-purple); font-size:.72rem; font-weight:850; letter-spacing:.1em; text-transform:uppercase; }
    .fulfillment-title { margin:6px 0 0; color:#0f172a; font-size:2rem; font-weight:850; letter-spacing:-.035em; }
    .fulfillment-subtitle { margin:8px 0 0; color:#64748b; font-size:.95rem; }
    .ticket-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(430px,1fr)); gap:18px; align-items:start; }
    .ticket-card { background:#fff; border:1px solid #e5e7eb; border-radius:17px; overflow:hidden; box-shadow:0 16px 34px -28px rgba(15,23,42,.65); }
    .ticket-head { display:flex; justify-content:space-between; gap:14px; padding:20px; border-bottom:1px solid #eef2f7; }
    .ticket-kicker { color:#94a3b8; font-size:.68rem; font-weight:850; text-transform:uppercase; letter-spacing:.07em; margin-bottom:6px; }
    .ticket-title { margin:0; color:#0f172a; font-size:1.08rem; font-weight:850; }
    .ticket-product { margin-top:6px; color:#64748b; font-size:.82rem; }
    .stage-pill { height:max-content; background:#eef2ff; color:var(--primary-purple); border-radius:999px; padding:7px 11px; font-size:.62rem; font-weight:850; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
    .ticket-body { padding:18px 20px 20px; }
    .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:9px; margin-bottom:14px; }
    .detail-item { background:#f8fafc; border:1px solid #edf1f5; border-radius:11px; padding:10px 11px; min-width:0; }
    .detail-item.wide { grid-column:1/-1; }
    .detail-label { display:block; color:#94a3b8; font-size:.58rem; font-weight:850; text-transform:uppercase; letter-spacing:.055em; }
    .detail-value { display:block; margin-top:4px; color:#334155; font-size:.78rem; font-weight:750; line-height:1.45; word-break:break-word; white-space:pre-line; }
    .stage-form { background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; padding:14px; margin-top:12px; }
    .stage-form h4 { margin:0 0 12px; color:#1e293b; font-size:.9rem; font-weight:850; }
    .field-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .field { margin-bottom:10px; }
    .field label { display:block; color:#475569; font-size:.72rem; font-weight:800; margin-bottom:5px; }
    .field input,.field select,.field textarea { width:100%; box-sizing:border-box; border:1px solid #dbe2ea; border-radius:9px; padding:9px 10px; background:#fff; color:#1e293b; outline:none; }
    .field textarea { min-height:70px; resize:vertical; }
    .check-row { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px; }
    .check-row label { display:flex; align-items:center; gap:7px; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:8px 10px; color:#334155; font-size:.76rem; font-weight:750; }
    .action-btn { border:0; border-radius:9px; padding:10px 14px; cursor:pointer; font-weight:850; background:var(--primary-purple); color:#fff; }
    .action-btn.green { background:#16a34a; }
    .action-btn.orange { background:#ea580c; }
    
    /* Premium Empty State */
    .empty-state { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 24px; background: transparent; border-radius: 24px; border: 1px dashed #cbd5e1; text-align: center; margin-top: 20px; }
    .empty-icon-wrapper { width: 100px; height: 100px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 24px; position: relative; }
    .empty-icon-wrapper::after { content: ''; position: absolute; inset: -10px; border-radius: 50%; border: 2px solid #e2e8f0; border-top-color: var(--primary-purple); animation: spin 3s linear infinite; opacity: 0.3; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    .empty-icon { font-size: 3.5rem; background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .empty-state h3 { font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0 0 12px 0; letter-spacing: -0.02em; }
    .empty-state p { font-size: 1.05rem; color: #64748b; max-width: 450px; margin: 0; line-height: 1.6; }

    @media(max-width:760px){.ticket-grid,.detail-grid,.field-grid{grid-template-columns:1fr}.detail-item.wide{grid-column:auto}.fulfillment-head{display:block}}
</style>

<div class="fulfillment-shell">
    <div class="fulfillment-head">
        <div>
            <div class="fulfillment-eyebrow">{{ $pageEyebrow }}</div>
            <h1 class="fulfillment-title">{{ $pageTitle }}</h1>
            <p class="fulfillment-subtitle">{{ $pageSubtitle }}</p>
        </div>
        <span class="stage-pill">{{ $orders->count() }} {{ $orders->count() === 1 ? 'ticket' : 'tickets' }}</span>
    </div>

    <div class="ticket-grid">
    @forelse($orders as $order)
        @php
            $lead = $order->lead;
            $stage = $order->shipping_stage ?: $order->status;
            $stageLabel = $stageLabels[$stage] ?? ucfirst(str_replace('_',' ', $stage));
            $canWarehouse = $currentUser->isAdmin() || $currentUser->isProductionManager() || $currentUser->isWarehouse();
            $canAccounts = $currentUser->isAdmin() || $currentUser->isSalesManager() || $currentUser->isAccounts();
            $canShipping = $currentUser->isAdmin() || $currentUser->isSalesManager() || $currentUser->isShipping();
            $canRetention = $currentUser->isAdmin() || $currentUser->isSalesManager() || $currentUser->isRetention();
        @endphp
        <article class="ticket-card">
            <div class="ticket-head">
                <div>
                    <div class="ticket-kicker">Sales Order #{{ $order->id }}</div>
                    <h3 class="ticket-title">{{ $lead->client_name ?? 'Client' }}</h3>
                    <div class="ticket-product">{{ $lead->product_name ?? 'Product not set' }}</div>
                </div>
                <span class="stage-pill">{{ $stageLabel }}</span>
            </div>
            <div class="ticket-body">
                <div class="detail-grid">
                    <div class="detail-item"><span class="detail-label">Payment Term</span><span class="detail-value">{{ ucfirst(str_replace('_',' ', $order->payment_term)) }} {{ $order->payment_term === 'credit' && $order->credit_days ? '(Net-'.$order->credit_days.')' : '' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Payment Status</span><span class="detail-value">{{ ucfirst(str_replace('_',' ', $order->payment_status)) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Carrier</span><span class="detail-value">{{ $order->shipping_carrier ? ($carrierLabels[$order->shipping_carrier] ?? $order->shipping_carrier) : 'Not selected' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Tracking</span><span class="detail-value">{{ $order->tracking_number ?: 'Not generated' }}</span></div>
                    <div class="detail-item"><span class="detail-label">Shipping Region</span><span class="detail-value">{{ $lead ? ($lead->shipping_region ?: ($lead->country ?: 'N/A')) : 'N/A' }}</span></div>
                    <div class="detail-item wide"><span class="detail-label">Shipping Address</span><span class="detail-value">{{ $order->shipping_address ?: 'Not provided' }}</span></div>
                    <div class="detail-item wide"><span class="detail-label">Shipping Notes</span><span class="detail-value">{{ $order->shipping_notes ?: 'No shipping notes yet.' }}</span></div>
                    @if($canAccounts)
                    <div class="detail-item wide"><span class="detail-label">Accounts Notes</span><span class="detail-value">{{ $order->accounts_notes ?: 'No accounts notes yet.' }}</span></div>
                    @endif
                </div>

                @if($canWarehouse && in_array($stage, ['warehouse_ready', 'production_completed'], true))
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="action" value="send_to_payment_check">
                    <h4>Warehouse Handoff</h4>
                    <div class="field"><label>Warehouse Notes</label><textarea name="notes" placeholder="Pallet count, ready area, handoff notes"></textarea></div>
                    <button class="action-btn" type="submit">Send to Balance Payment Check</button>
                </form>
                @endif

                @if($canAccounts && $stage === 'pending_payment')
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="action" value="initial_payment_decision">
                    <h4>Initial Payment Approval</h4>
                    <div class="field" style="margin-bottom:12px;">
                        <label>Payment Term Selected</label>
                        <div style="font-size:0.85rem; font-weight:700; color:#334155; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:9px 12px;">
                            {{ $order->payment_term === 'credit' ? 'Credit Terms (Net-' . ($order->credit_days ?: 30) . ')' : ($order->payment_term === '100_deposit' ? '100% Deposit' : '50% Advance') }}
                        </div>
                    </div>
                    <button class="action-btn green" type="submit">Mark Paid/Approved</button>
                </form>
                @endif

                @if($canAccounts && in_array($stage, ['balance_payment_check', 'final_payment_pending'], true))
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">
                    {{ csrf_field() }}
                    <input type="hidden" name="action" value="payment_decision">
                    <h4>Balance Payment Check</h4>
                    <div class="field" style="margin-bottom:12px;">
                        <label>Payment Term</label>
                        <div style="font-size:0.85rem; font-weight:700; color:#334155; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:9px 12px;">
                            {{ $order->payment_term === 'credit' ? 'Credit Terms (Net-' . ($order->credit_days ?: 30) . ')' : ($order->payment_term === '100_deposit' ? '100% Deposit' : '50% Advance') }}
                        </div>
                    </div>
                    <div class="field"><label>Notes</label><input type="text" name="notes" placeholder="Payment reference / credit approval notes"></div>
                    <div class="check-row" style="margin-top:10px;">
                        @if($order->payment_term === 'credit')
                            <label><input type="checkbox" name="credit_approved" value="1"> Confirm Credit Approval</label>
                        @elseif($order->payment_term === '100_deposit')
                            <label><input type="checkbox" name="final_payment_received" value="1"> 100% Deposit Received & Verified</label>
                        @else
                            <label style="opacity: 0.6; cursor: not-allowed;"><input type="checkbox" checked disabled> 50% Advance Received</label>
                            <label><input type="checkbox" name="final_payment_received" value="1"> 50% Balance / Final Payment Received</label>
                        @endif
                    </div>
                    <button class="action-btn green" type="submit">Approve Ready to Ship</button>
                </form>
                @endif

                @if($canShipping && in_array($stage, ['ready_to_ship', 'shipping_department'], true))
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <input type="hidden" name="action" value="shipping_label">
                    <h4>Shipping Department</h4>
                    <div class="field-grid">
                        <div class="field"><label>Carrier</label><select name="shipping_carrier" required>
                            <option value="">Select</option>
                            @foreach($carrierLabels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select></div>
                        <div class="field"><label>Tracking Number</label><input type="text" name="tracking_number" required></div>
                    </div>
                    <div class="field"><label>Shipping Notes</label><textarea name="notes" placeholder="Label, pickup, cartons, freight notes"></textarea></div>
                    <div class="field"><label>Upload Receipt (optional)</label><input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" style="width:100%; padding:8px; border:1px dashed #cbd5e1; border-radius:8px; background:#f8fafc;"></div>
                    <button class="action-btn green" type="submit">Generate Shipping Label</button>
                </form>
                @endif

                @if($canShipping && $stage === 'shipping_label_generated')
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">{{ csrf_field() }}<input type="hidden" name="action" value="in_transit"><h4>Customer Notification</h4><div class="field"><label>Notes</label><textarea name="notes" placeholder="Notification sent / pickup notes"></textarea></div><button class="action-btn green" type="submit">Mark In Transit</button></form>
                @endif

                @if($canShipping && $stage === 'in_transit')
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">{{ csrf_field() }}<input type="hidden" name="action" value="delivered"><h4>Delivery Confirmation</h4><div class="field"><label>Delivery Notes</label><textarea name="notes"></textarea></div><button class="action-btn green" type="submit">Mark Delivered</button></form>
                @endif

                @if($canAccounts && $stage === 'delivered')
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">{{ csrf_field() }}<input type="hidden" name="action" value="final_invoice"><h4>Invoice / Final Invoice</h4><div class="field"><label>Invoice Notes</label><textarea name="notes"></textarea></div><button class="action-btn" type="submit">Record Final Invoice</button></form>
                @endif

                @if($canAccounts && $stage === 'final_invoice')
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">{{ csrf_field() }}<input type="hidden" name="action" value="payment_posted"><h4>Payment Posted</h4><div class="field"><label>Payment Notes</label><textarea name="notes"></textarea></div><button class="action-btn green" type="submit">Post Payment</button></form>
                @endif

                @if($canAccounts && $stage === 'payment_posted')
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">{{ csrf_field() }}<input type="hidden" name="action" value="order_completed"><h4>Order Completed</h4><div class="field"><label>Completion Notes</label><textarea name="notes"></textarea></div><button class="action-btn green" type="submit">Complete Order</button></form>
                @endif

                @if($canRetention && $stage === 'order_completed')
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">{{ csrf_field() }}<input type="hidden" name="action" value="retention_follow_up"><h4>Customer Success Follow-up</h4><div class="field"><label>Follow-up Notes</label><textarea name="notes"></textarea></div><button class="action-btn" type="submit">Record Follow-up</button></form>
                @endif

                @if($canRetention && $stage === 'retention_follow_up')
                <form class="stage-form" action="{{ route('crm.fulfillment.update', $order->id) }}" method="POST">{{ csrf_field() }}<input type="hidden" name="action" value="reorder_reminder"><h4>Reorder Reminder / Upsell</h4><div class="field"><label>Retention Notes</label><textarea name="notes"></textarea></div><button class="action-btn green" type="submit">Record Reorder Reminder</button></form>
                @endif

               

            </div>
        </article>

    @empty
        <div class="empty-state">
            <div class="empty-icon-wrapper">
                @if(isset($pageTitle) && strpos(strtolower($pageTitle), 'warehouse') !== false)
                    <i class="fas fa-warehouse empty-icon"></i>
                @elseif(isset($pageTitle) && strpos(strtolower($pageTitle), 'shipping') !== false)
                    <i class="fas fa-truck empty-icon"></i>
                @elseif(isset($pageTitle) && strpos(strtolower($pageTitle), 'account') !== false)
                    <i class="fas fa-file-invoice-dollar empty-icon"></i>
                @else
                    <i class="fas fa-box-open empty-icon"></i>
                @endif
            </div>
            <h3>No tickets here yet</h3>
            <p>Tickets will appear automatically when the order reaches this department.</p>
        </div>
    @endforelse
    </div>
</div>

@push('scripts')
<script>
function toggleChat(orderId) {
    const panel = document.getElementById('chatPanel_' + orderId);
    const chevron = document.getElementById('chevron_' + orderId);
    const open = panel.style.display !== 'none';
    panel.style.display = open ? 'none' : 'flex';
    panel.style.flexDirection = 'column';
    chevron.style.transform = open ? '' : 'rotate(180deg)';
    if (!open) {
        const box = document.getElementById('chatMsgs_' + orderId);
        box.scrollTop = box.scrollHeight;
    }
}

function agentSend(orderId) {
    const input = document.getElementById('agentMsg_' + orderId);
    const msg = input.value.trim();
    if (!msg) return;

    fetch('/crm/customer-chat/' + orderId + '/reply', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ message: msg })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const box = document.getElementById('chatMsgs_' + orderId);
            const noMsg = document.getElementById('noChatMsg_' + orderId);
            if (noMsg) noMsg.remove();

            const div = document.createElement('div');
            div.style.cssText = 'align-self:flex-end; max-width:80%;';
            div.innerHTML = `
                <div style="font-size:0.7rem; font-weight:700; color:#7b9f6a; margin-bottom:2px;">You (Agent)</div>
                <div style="background:#7ec832; color:#fff; padding:8px 12px; border-radius:10px; font-size:0.85rem; line-height:1.5; border-bottom-right-radius:3px;">${msg}</div>
                <div style="font-size:0.68rem; color:#9ab08c; margin-top:2px; text-align:right;">Just now</div>
            `;
            box.appendChild(div);
            box.scrollTop = box.scrollHeight;
            input.value = '';
            input.style.height = 'auto';
        }
    })
    .catch(() => alert('Failed to send message. Please try again.'));
}
</script>
@endpush

@endsection
