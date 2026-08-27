@extends('crm.layout')

@section('title', 'Inquiries')

@section('header_actions')
@if(!Auth::guard('crm')->user()->isTeamLead())
<a href="{{ route('crm.emails.create_form') }}" class="iq-add-btn"><i class="fas fa-plus"></i> Add Inquiry</a>
@endif
@endsection

@section('content')
@php
    $inquiryListUser = Auth::guard('crm')->user();
@endphp
<style>
.iq-page{color:#243147}.iq-add-btn{min-height:42px;padding:.63rem .95rem;border-radius:11px;display:inline-flex;align-items:center;gap:.45rem;background:var(--primary-purple);color:#fff;text-decoration:none;font-weight:850;box-shadow:0 8px 20px var(--primary-shadow)}.iq-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.3rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px}.iq-hero h2{margin:0;font-size:1.25rem}.iq-muted{color:#8796aa;font-size:.73rem}.iq-filter{display:flex;gap:.55rem}.iq-control{min-height:40px;padding:.56rem .68rem;border:1.5px solid #dbe3ec;border-radius:9px;background:#fff;outline:0;box-sizing:border-box}.iq-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}.iq-search{width:330px}.iq-wrap{overflow:visible;background:#fff;border:1px solid #e4eaf1;border-radius:15px;box-shadow:0 8px 26px rgba(15,23,42,.055)}#inquiryResults{transition:opacity .16s ease}#inquiryResults.loading{opacity:.48;pointer-events:none}.iq-table{width:100%;border-collapse:collapse}.iq-table th{padding:.85rem .9rem;background:#f7f9fc;border-bottom:2px solid var(--primary-soft);text-align:left;color:#718096;font-size:.65rem;text-transform:uppercase;letter-spacing:.045em}.iq-table td{padding:.85rem .9rem;border-bottom:1px solid #edf1f5;vertical-align:middle;font-size:.77rem}.iq-table tbody tr:hover{background:var(--primary-soft)}.iq-client{display:flex;align-items:center;gap:.65rem}.iq-avatar{width:36px;height:36px;flex:0 0 36px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:var(--primary-soft);color:var(--primary-purple);font-weight:900}.iq-client strong{display:block;color:#1f2b3d}.iq-qty{display:inline-flex;margin:.12rem;padding:.27rem .48rem;border-radius:999px;background:#eef2f7;color:#526176;font-size:.65rem;font-weight:800}.iq-offers{display:flex;flex-direction:column;align-items:flex-start;gap:.35rem}.iq-offer{display:grid;grid-template-columns:minmax(72px,auto) auto;align-items:center;column-gap:.7rem;min-width:205px;padding:.35rem .55rem;border-radius:8px;background:var(--primary-soft);color:var(--primary-purple);white-space:nowrap;font-size:.67rem;font-weight:850}.iq-offer em{color:#718096;font-style:normal;font-weight:750}.iq-offer small{display:block;margin-top:.08rem;color:#718096;font-size:.58rem}.iq-status{display:inline-flex;padding:.32rem .58rem;border-radius:999px;font-size:.66rem;font-weight:850}.iq-status.design{background:#fff3e8;color:#d65d14}.iq-status.estimate{background:#eaf2ff;color:#285fbd}.iq-status.done{background:#eafbf2;color:#08784c}.iq-actions{display:flex;align-items:center;gap:.4rem;flex-wrap:nowrap}.iq-action{display:inline-flex;min-height:34px;align-items:center;gap:.38rem;padding:.48rem .72rem;border:1px solid var(--primary-shadow);border-radius:9px;background:var(--primary-soft);color:var(--primary-purple);font:inherit;font-weight:850;cursor:pointer;white-space:nowrap}.iq-action-menu{position:relative}.iq-action-menu>summary{list-style:none}.iq-action-menu>summary::-webkit-details-marker{display:none}.iq-action-trigger{border-color:var(--primary-purple);background:var(--primary-purple);color:#fff;box-shadow:0 6px 14px var(--primary-shadow)}.iq-action-dropdown{position:absolute;z-index:40;top:calc(100% + 7px);right:0;min-width:190px;padding:.38rem;border:1px solid #e1e7ef;border-radius:11px;background:#fff;box-shadow:0 14px 34px rgba(15,23,42,.17)}.iq-dropdown-item{display:flex;width:100%;align-items:center;gap:.5rem;padding:.62rem .7rem;border:0;border-radius:8px;background:transparent;color:#334155;text-decoration:none;font:inherit;font-size:.72rem;font-weight:800;text-align:left;cursor:pointer;white-space:nowrap}.iq-dropdown-item:hover{background:var(--primary-soft);color:var(--primary-purple)}.iq-dropdown-item i{width:16px;color:var(--primary-purple);text-align:center}.iq-action-dropdown form{margin:0}.iq-empty{text-align:center!important;padding:2rem!important;color:#94a3b8}
.iq-modal{position:fixed;inset:0;z-index:10050;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.iq-modal.open{display:flex}.iq-dialog{width:min(1050px,96vw);max-height:90vh;overflow:auto;background:#fff;border-radius:20px;box-shadow:0 24px 70px rgba(15,23,42,.28)}.iq-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1.25rem 1.35rem;border-bottom:1px solid #e8edf3}.iq-modal-head h3{margin:0 0 .2rem;font-size:1.15rem}.iq-close{width:38px;height:38px;border:0;border-radius:10px;background:#eef2f7;color:#334155;font-size:1rem;cursor:pointer}.iq-modal-body{padding:1.25rem 1.35rem}.iq-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}.iq-detail{padding:.8rem .9rem;border:1px solid #e6ebf1;border-radius:12px;background:#fbfcfe}.iq-detail.wide{grid-column:1/-1}.iq-detail label{display:block;margin-bottom:.25rem;color:#8190a4;font-size:.62rem;font-weight:850;text-transform:uppercase;letter-spacing:.045em}.iq-detail strong,.iq-detail span{overflow-wrap:anywhere}.iq-finish-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.34rem;margin-top:.35rem}.iq-finish-group{overflow:hidden;min-height:62px;border:1px solid #e5eaf1;border-radius:8px;background:#fff}.iq-finish-group b{display:block;margin:0;padding:.3rem .42rem;background:var(--primary-soft);color:var(--primary-purple);font-size:.56rem;text-transform:uppercase;letter-spacing:.025em}.iq-finish-children{display:flex;flex-wrap:wrap;gap:.16rem;padding:.3rem .4rem}.iq-finish-chip{display:inline-flex!important;margin:0;padding:.18rem .32rem;border-radius:99px;background:#f1f5f9;color:#4b5b70;font-size:.56rem;font-weight:750}.iq-files{display:flex;gap:.35rem;flex-wrap:wrap}.iq-file{display:inline-flex;align-items:center;gap:.3rem;padding:.38rem .55rem;border-radius:8px;background:#f1f5f9;color:#475569;text-decoration:none;font-size:.68rem}.iq-modal-foot{display:flex;align-items:center;justify-content:flex-end;gap:.55rem;padding:1rem 1.35rem;border-top:1px solid #e8edf3}.iq-secondary{padding:.55rem .8rem;border:0;border-radius:9px;background:#eef2f7;color:#475569;font-weight:800;cursor:pointer}.iq-next{display:inline-flex;align-items:center;gap:.4rem;padding:.58rem .82rem;border-radius:9px;background:var(--primary-purple);color:#fff;text-decoration:none;font-weight:850}
@media(max-width:850px){.iq-hero{align-items:flex-start;flex-direction:column}.iq-filter{width:100%;flex-wrap:wrap}.iq-search{width:100%;flex:1}.iq-wrap{overflow:auto}.iq-table{min-width:760px}.iq-detail-grid{grid-template-columns:1fr}.iq-finish-grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.iq-finish-grid{grid-template-columns:1fr}}
.iq-finish-chip{padding:0;border-radius:0;background:transparent}
</style>
<div class="iq-page">
    <div class="iq-hero">
        <div><h2>Customer Inquiries</h2><div class="iq-muted">All manually added inquiries and their current Design/Estimate workflow.</div></div>
        <form class="iq-filter" id="inquiryLiveFilter" method="GET" onsubmit="return false">
            <input class="iq-control iq-search" id="inquiryLiveSearch" autocomplete="off" name="search" value="{{ request('search') }}" placeholder="Search inquiry number, client, email or product...">
            <select class="iq-control" id="inquiryWorkflowFilter" name="workflow"><option value="">All Workflow</option><option value="design" {{ request('workflow')==='design'?'selected':'' }}>With Designer</option><option value="estimate" {{ request('workflow')==='estimate'?'selected':'' }}>With Estimator</option><option value="returned" {{ request('workflow')==='returned'?'selected':'' }}>Returned to Sales</option><option value="sales" {{ request('workflow')==='sales'?'selected':'' }}>Sales Order Running</option><option value="completed" {{ request('workflow')==='completed'?'selected':'' }}>Order Completed</option></select>
        </form>
    </div>
    <div id="inquiryResults">
    <div class="iq-wrap"><table class="iq-table"><thead><tr><th>Date</th><th>Client</th><th>Product</th><th>Open Size</th><th>Quantity / Offer Price</th><th>Status</th><th>Action</th></tr></thead><tbody>
    @forelse($inquiries as $inquiry)
        @php
            $designTicket = $inquiry->designRequirementTicket;
            $estimateTicket = $inquiry->estimateTickets->first();
            $offerOptions = is_array($inquiry->estimate_quantity_options) ? array_values($inquiry->estimate_quantity_options) : [];
            $offerCurrency = $inquiry->invoice_currency ?: 'USD';
            $finishingDisplay = [];
            foreach ((array)($inquiry->custom_specs['Finishing Options'] ?? []) as $finishOption) {
                $parts = explode(' — ', $finishOption, 2);
                $finishingDisplay[$parts[0]][] = $parts[1] ?? $parts[0];
            }
            $order = $inquiry->salesOrder;
            $orderStage = $order ? ($order->shipping_stage ?: $order->status) : null;
            if ($order && in_array($orderStage, ['order_completed','completed','complete'])) {
                $workflowClass='done'; $workflowLabel='Order Completed'; $department='Completed';
                $nextUrl=route('crm.emails.show',$inquiry->id); $nextLabel='View Complete Order';
            } elseif ($order) {
                $workflowClass='estimate'; $workflowLabel=ucwords(str_replace('_',' ',$orderStage ?: 'Sales Order'));
                if (in_array($orderStage,['pending_payment','balance_payment_check','final_payment_pending','final_invoice','payment_posted'])) $department='Accounts';
                elseif (in_array($orderStage,['pending_artwork','in_design','design_approved'])) $department='Design';
                elseif ($orderStage==='prepress') $department='Prepress';
                elseif (in_array($orderStage,['ready_to_ship','shipping_department','shipping_label_generated','in_transit','delivered'])) $department='Shipping';
                elseif (strpos((string)$orderStage,'production')!==false) $department='Production';
                else $department='Order Processing';
                $nextUrl=route('crm.sales_orders.index'); $nextLabel='Track Sales Order';
            } elseif ($estimateTicket && $estimateTicket->status === 'returned_to_sales') {
                $workflowClass='design'; $workflowLabel='Returned to Sales'; $department='Sales';
                $nextUrl=route('crm.emails.edit_inquiry',$inquiry->id); $nextLabel='Edit & Resubmit Ticket';
            } elseif ($designTicket && $designTicket->status === 'returned_to_sales') {
                $workflowClass='design'; $workflowLabel='Returned to Sales'; $department='Sales';
                $nextUrl=route('crm.emails.edit_inquiry',$inquiry->id); $nextLabel='Edit & Resubmit Ticket';
            } elseif ($estimateTicket && $estimateTicket->status === 'returned_to_design') {
                $workflowClass='design'; $workflowLabel='Returned to Design'; $department='Design';
                $nextUrl=route('crm.design_tickets.index'); $nextLabel='View Design Ticket';
            } elseif ($estimateTicket && in_array($estimateTicket->status,['estimated','completed'])) {
                $workflowClass='done'; $workflowLabel='Estimate Ready'; $department='Sales';
                $nextUrl=route('crm.emails.show',$inquiry->id); $nextLabel='Create Sales Order';
            } elseif ($estimateTicket && in_array($estimateTicket->status,['team_lead_review','team_lead_open'])) {
                $workflowClass='estimate'; $workflowLabel='Team Lead Review'; $department='Team Lead';
                $nextUrl=route('crm.estimate_tickets.show',$estimateTicket->id); $nextLabel='View Team Lead Review';
            } elseif ($estimateTicket) {
                $workflowClass='estimate'; $workflowLabel='Estimation Pending'; $department='Estimator';
                $nextUrl=route('crm.estimate_tickets.show',$estimateTicket->id); $nextLabel='View Estimate';
            } else {
                $workflowClass='design'; $workflowLabel=$designTicket && $designTicket->status==='open'?'Design Open':'Waiting for Design'; $department='Design';
                $nextUrl=route('crm.design_tickets.index'); $nextLabel='View Design Ticket';
            }
            if ($inquiryListUser->isTeamLead() && $estimateTicket) {
                $nextUrl=route('crm.estimate_tickets.show',$estimateTicket->id);
                $nextLabel='View Estimate Review';
            }
        @endphp
        <tr>
            <td><strong>{{ $inquiry->workflow_number }}</strong><div class="iq-muted">{{ $inquiry->created_at->format('d M Y, h:i A') }}</div><div class="iq-muted"><i class="fas fa-bullhorn"></i> {{ ['website'=>'Website','call'=>'Call','walk_in'=>'Walk-in','social_media'=>'Social Media','whatsapp'=>'WhatsApp','live_chat'=>'Live Chat','email'=>'Email'][$inquiry->source] ?? ucwords(str_replace('_',' ',(string)$inquiry->source)) }}</div></td>
            <td><div class="iq-client"><span class="iq-avatar">{{ strtoupper(substr($inquiry->client_name,0,1)) }}</span><div><strong>{{ $inquiry->client_name }}</strong><div class="iq-muted">{{ $inquiry->client_email }}</div><div class="iq-muted">{{ $inquiry->client_phone ?: 'No phone' }}</div></div></div></td>
            <td><strong>{{ $inquiry->product_name }}</strong></td>
            <td><strong>{{ $inquiry->open_size ?: 'Pending' }}</strong><div class="iq-muted">{{ $inquiry->unit }}</div></td>
            <td>
                @if(count($offerOptions))
                    <div class="iq-offers">
                        @foreach($offerOptions as $offer)
                            <span class="iq-offer"><em>{{ number_format((float)($offer['quantity'] ?? 0)) }} pcs</em><span>{{ $offerCurrency }} {{ number_format((float)($offer['price'] ?? 0),2) }}</span></span>
                        @endforeach
                    </div>
                @elseif($inquiry->price_offered !== null)
                    <strong>{{ $offerCurrency }} {{ number_format($inquiry->price_offered,2) }}</strong>
                @else
                    <div class="iq-offers">@foreach(($inquiry->inquiry_quantities ?: [$inquiry->quantity]) as $qty)<span class="iq-offer"><em>{{ number_format($qty) }} pcs</em><span>Price pending</span></span>@endforeach</div>
                @endif
            </td>
            <td><span class="iq-status {{ $workflowClass }}">{{ $workflowLabel }}</span></td>
            <td><div class="iq-actions">
                <button class="iq-action" type="button" onclick="openInquiry({{ $inquiry->id }})"><i class="fas fa-eye"></i> View</button>
                @if($inquiryListUser->isSales() || $inquiryListUser->isSalesManager() || $inquiryListUser->isAdmin())
                    <details class="iq-action-menu">
                        <summary class="iq-action iq-action-trigger">Action <i class="fas fa-chevron-down"></i></summary>
                        <div class="iq-action-dropdown">
                            <a class="iq-dropdown-item" href="{{ route('crm.emails.edit_inquiry',$inquiry->id) }}"><i class="fas fa-pen"></i> Edit Inquiry</a>
                            @if($estimateTicket && in_array($estimateTicket->status,['estimated','completed']) && count($offerOptions))
                                <button class="iq-dropdown-item" type="button"
                                    data-offer-url="{{ route('crm.emails.update_offer_price',$inquiry->id) }}"
                                    data-offer-currency="{{ $offerCurrency }}"
                                    data-offer-options="{{ json_encode($offerOptions) }}"
                                    onclick="openOfferPriceModal(this)">
                                    <i class="fas fa-tags"></i> Add Offer Price
                                </button>
                            @endif
                            @if($estimateTicket
                                && in_array($estimateTicket->status,['estimated','completed'])
                                && ((int)$estimateTicket->requested_by === (int)$inquiryListUser->id
                                    || $inquiryListUser->isAdmin() || $inquiryListUser->isSalesManager()))
                                <form method="POST" action="{{ route('crm.estimate_tickets.send_chat',$estimateTicket->id) }}">
                                    {{ csrf_field() }}
                                    <button class="iq-dropdown-item" type="submit"><i class="fas fa-file-pdf"></i> Send Estimate</button>
                                </form>
                            @endif
                            {{-- Follow Up is temporarily hidden; its chat workflow remains intact. --}}
                        </div>
                    </details>
                @endif
            </div></td>
        </tr>
    @empty<tr><td colspan="7" class="iq-empty">No inquiries found. Click “Add Inquiry” to create the first inquiry.</td></tr>@endforelse
    </tbody></table></div>

    @foreach($inquiries as $inquiry)
        @php
            $designTicket = $inquiry->designRequirementTicket;
            $estimateTicket = $inquiry->estimateTickets->first();
            $offerOptions = is_array($inquiry->estimate_quantity_options) ? array_values($inquiry->estimate_quantity_options) : [];
            $offerCurrency = $inquiry->invoice_currency ?: 'USD';
            $finishingDisplay = [];
            foreach ((array)($inquiry->custom_specs['Finishing Options'] ?? []) as $finishOption) {
                $parts = explode(' — ', $finishOption, 2);
                $finishingDisplay[$parts[0]][] = $parts[1] ?? $parts[0];
            }
            $order = $inquiry->salesOrder;
            $orderStage = $order ? ($order->shipping_stage ?: $order->status) : null;
            if ($order && in_array($orderStage, ['order_completed','completed','complete'])) {
                $workflowClass='done'; $workflowLabel='Order Completed'; $department='Completed';
                $nextUrl=route('crm.emails.show',$inquiry->id); $nextLabel='View Complete Order';
            } elseif ($order) {
                $workflowClass='estimate'; $workflowLabel=ucwords(str_replace('_',' ',$orderStage ?: 'Sales Order')); $department='Order Processing';
                if (in_array($orderStage,['pending_payment','balance_payment_check','final_payment_pending','final_invoice','payment_posted'])) $department='Accounts';
                elseif (in_array($orderStage,['pending_artwork','in_design','design_approved'])) $department='Design';
                elseif ($orderStage==='prepress') $department='Prepress';
                elseif (in_array($orderStage,['ready_to_ship','shipping_department','shipping_label_generated','in_transit','delivered'])) $department='Shipping';
                elseif (strpos((string)$orderStage,'production')!==false) $department='Production';
                $nextUrl=route('crm.sales_orders.index'); $nextLabel='Track Sales Order';
            } elseif ($estimateTicket && $estimateTicket->status === 'returned_to_sales') {
                $workflowClass='design'; $workflowLabel='Returned to Sales'; $department='Sales';
                $nextUrl=route('crm.emails.edit_inquiry',$inquiry->id); $nextLabel='Edit & Resubmit Ticket';
            } elseif ($designTicket && $designTicket->status === 'returned_to_sales') {
                $workflowClass='design'; $workflowLabel='Returned to Sales'; $department='Sales';
                $nextUrl=route('crm.emails.edit_inquiry',$inquiry->id); $nextLabel='Edit & Resubmit Ticket';
            } elseif ($estimateTicket && $estimateTicket->status === 'returned_to_design') {
                $workflowClass='design'; $workflowLabel='Returned to Design'; $department='Design';
                $nextUrl=route('crm.design_tickets.index'); $nextLabel='View Design Ticket';
            } elseif ($estimateTicket && in_array($estimateTicket->status,['estimated','completed'])) {
                $workflowClass='done'; $workflowLabel='Estimate Ready'; $department='Sales';
                $nextUrl=route('crm.emails.show',$inquiry->id); $nextLabel='Create Sales Order';
            } elseif ($estimateTicket && in_array($estimateTicket->status,['team_lead_review','team_lead_open'])) {
                $workflowClass='estimate'; $workflowLabel='Team Lead Review'; $department='Team Lead';
                $nextUrl=route('crm.estimate_tickets.show',$estimateTicket->id); $nextLabel='View Team Lead Review';
            } elseif ($estimateTicket) {
                $workflowClass='estimate'; $workflowLabel='Estimation Pending'; $department='Estimator';
                $nextUrl=route('crm.estimate_tickets.show',$estimateTicket->id); $nextLabel='View Estimate';
            } else {
                $workflowClass='design'; $workflowLabel=$designTicket && $designTicket->status==='open'?'Design Open':'Waiting for Design'; $department='Design';
                $nextUrl=route('crm.design_tickets.index'); $nextLabel='View Design Ticket';
            }
            if ($inquiryListUser->isTeamLead() && $estimateTicket) {
                $nextUrl=route('crm.estimate_tickets.show',$estimateTicket->id);
                $nextLabel='View Estimate Review';
            }
        @endphp
        <div class="iq-modal" id="inquiryModal{{ $inquiry->id }}" onclick="if(event.target===this)closeInquiry({{ $inquiry->id }})">
            <div class="iq-dialog">
                <div class="iq-modal-head">
                    <div><h3>{{ $inquiry->workflow_number }} · {{ $inquiry->product_name }}</h3><div class="iq-muted">{{ $inquiry->created_at->format('d M Y, h:i A') }} · {{ $inquiry->website }}</div></div>
                    <button class="iq-close" type="button" onclick="closeInquiry({{ $inquiry->id }})"><i class="fas fa-times"></i></button>
                </div>
                <div class="iq-modal-body">
                    <div class="iq-detail-grid">
                        <div class="iq-detail"><label>Client</label><strong>{{ $inquiry->client_name }}</strong><div class="iq-muted">{{ $inquiry->client_email }} · {{ $inquiry->client_phone ?: 'No phone' }}</div></div>
                        <div class="iq-detail"><label>Inquiry Source</label><strong>{{ ['website'=>'Website','call'=>'Call','walk_in'=>'Walk-in','social_media'=>'Social Media','whatsapp'=>'WhatsApp','live_chat'=>'Live Chat','email'=>'Email'][$inquiry->source] ?? ucwords(str_replace('_',' ',(string)$inquiry->source)) }}</strong></div>
                        <div class="iq-detail"><label>Current Department</label><strong>{{ $department }}</strong><div class="iq-muted">{{ $department === 'Team Lead' && $estimateTicket && $estimateTicket->teamLead ? $estimateTicket->teamLead->name : ($designTicket && $designTicket->designer ? $designTicket->designer->name : ($estimateTicket && $estimateTicket->estimator ? $estimateTicket->estimator->name : 'Not assigned')) }}</div><div style="margin-top:.35rem"><span class="iq-status {{ $workflowClass }}">{{ $workflowLabel }}</span></div></div>
                        <div class="iq-detail"><label>Product</label><strong>{{ $inquiry->product_name }}</strong></div>
                        <div class="iq-detail"><label>Stock / Printing</label><strong>{{ $inquiry->stock ?: '-' }}</strong><div class="iq-muted">{{ $inquiry->printing ?: '-' }}</div></div>
                        <div class="iq-detail"><label>Paper Size</label><strong>{{ $inquiry->finish_size ?: '-' }} {{ $inquiry->unit }}</strong></div>
                        <div class="iq-detail"><label>Open Size</label><strong>{{ $inquiry->open_size ?: 'Pending from Designer' }} {{ $inquiry->unit }}</strong></div>
                        <div class="iq-detail wide"><label>Finishing Options</label>
                            @if(count($finishingDisplay))
                                <div class="iq-finish-grid">
                                    @foreach($finishingDisplay as $finishGroup => $finishItems)
                                        <div class="iq-finish-group"><b>{{ $finishGroup }}</b><div class="iq-finish-children">@foreach($finishItems as $finishItem)<span class="iq-finish-chip">{{ $finishItem }}</span>@endforeach</div></div>
                                    @endforeach
                                </div>
                            @else
                                <span class="iq-muted">None selected</span>
                            @endif
                        </div>
                        <div class="iq-detail"><label>Quantities</label>@foreach(($inquiry->inquiry_quantities ?: [$inquiry->quantity]) as $qty)<span class="iq-qty">{{ number_format($qty) }}</span>@endforeach</div>
                        <div class="iq-detail"><label>Quantity-wise Final Offers</label>
                            @if(count($offerOptions))
                                <div class="iq-offers">
                                    @foreach($offerOptions as $offer)
                                        <span class="iq-offer"><em>{{ number_format((float)($offer['quantity'] ?? 0)) }} pcs</em><span>{{ $offerCurrency }} {{ number_format((float)($offer['price'] ?? 0),2) }}<small>{{ $offerCurrency }} {{ number_format((float)($offer['unit_price'] ?? 0),4) }} per pc</small></span></span>
                                    @endforeach
                                </div>
                            @elseif($inquiry->price_offered !== null)
                                <strong>{{ $offerCurrency }} {{ number_format($inquiry->price_offered,2) }}</strong>
                            @else
                                <span class="iq-muted">Not provided</span>
                            @endif
                        </div>
                        <div class="iq-detail wide"><label>Sales Requirements / Notes</label><span>{{ $inquiry->message ?: $inquiry->csr_comment ?: 'No notes provided.' }}</span></div>
                        @php $workflowReturnNote = ($estimateTicket && $estimateTicket->return_note) ? $estimateTicket->return_note : optional($designTicket)->return_note; @endphp
                        @if($workflowReturnNote)<div class="iq-detail wide" style="border-color:#fecdd3;background:#fff7f7"><label>Department Return Note</label><strong style="color:#9f1239">{{ $workflowReturnNote }}</strong></div>@endif
                        <div class="iq-detail wide"><label>Designer Files</label><div class="iq-files">@forelse($inquiry->inquiryAttachments->where('stage', 'designer') as $file)<a class="iq-file" href="{{ asset($file->file_path) }}" target="_blank"><i class="fas fa-paperclip"></i>{{ $file->original_name }}</a>@empty<span class="iq-muted">Designer has not uploaded any files yet.</span>@endforelse</div></div>
                    </div>
                </div>
                <div class="iq-modal-foot"><button class="iq-secondary" type="button" onclick="closeInquiry({{ $inquiry->id }})">Close</button><a class="iq-next" href="{{ $nextUrl }}">{{ $nextLabel }} <i class="fas fa-arrow-right"></i></a></div>
            </div>
        </div>
    @endforeach
    {{ $inquiries->links() }}
    </div>
</div>
<div class="iq-modal" id="offerPriceModal" onclick="if(event.target===this)closeOfferPriceModal()">
    <div class="iq-dialog" style="width:min(620px,96vw)">
        <div class="iq-modal-head">
            <div><h3>Add Customer Offer Price</h3><div class="iq-muted">Team Lead approved prices are locked minimums. You may only keep or increase them.</div></div>
            <button class="iq-close" type="button" onclick="closeOfferPriceModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="offerPriceForm" method="POST">
            {{ csrf_field() }}
            <div class="iq-modal-body" id="offerPriceRows"></div>
            <div class="iq-modal-foot"><button class="iq-secondary" type="button" onclick="closeOfferPriceModal()">Cancel</button><button class="iq-next" type="submit" style="border:0;cursor:pointer"><i class="fas fa-save"></i> Save Offer Prices</button></div>
        </form>
    </div>
</div>
<script>
function openInquiry(id){var modal=document.getElementById('inquiryModal'+id);if(modal){modal.classList.add('open');document.body.style.overflow='hidden'}}
function closeInquiry(id){var modal=document.getElementById('inquiryModal'+id);if(modal){modal.classList.remove('open');document.body.style.overflow=''}}
function openOfferPriceModal(button){
    var modal=document.getElementById('offerPriceModal'),form=document.getElementById('offerPriceForm'),rows=document.getElementById('offerPriceRows');
    var options=[];try{options=JSON.parse(button.dataset.offerOptions||'[]')}catch(error){}
    var currency=button.dataset.offerCurrency||'USD';form.action=button.dataset.offerUrl;rows.innerHTML='';
    options.forEach(function(option,index){
        var floor=Number(option.team_lead_price!==undefined?option.team_lead_price:option.price||0),offer=Number(option.price||floor),quantity=Number(option.quantity||0);
        var row=document.createElement('div');row.style.cssText='display:grid;grid-template-columns:120px 1fr;gap:.8rem;align-items:center;margin-bottom:.75rem;padding:.85rem;border:1px solid #e5eaf1;border-radius:12px;background:#fbfcfe';
        row.innerHTML='<div><strong>'+quantity.toLocaleString()+' pcs</strong><div class="iq-muted">Minimum '+currency+' '+floor.toFixed(2)+'</div></div><div><div style="display:flex;align-items:center;gap:.5rem;padding:0 .7rem;border:1.5px solid #dbe3ec;border-radius:9px;background:#fff"><strong>'+currency+'</strong><input class="iq-control" style="border:0;box-shadow:none;padding-left:0" type="number" step="0.01" min="'+floor.toFixed(2)+'" name="offer_prices['+index+']" value="'+offer.toFixed(2)+'" required></div><div class="iq-muted" style="margin-top:.3rem">Offer per unit: '+currency+' <span class="offer-unit">'+(quantity?offer/quantity:0).toFixed(4)+'</span></div></div>';
        var input=row.querySelector('input'),unit=row.querySelector('.offer-unit');input.addEventListener('input',function(){unit.textContent=(quantity?Number(this.value||0)/quantity:0).toFixed(4)});rows.appendChild(row);
    });
    document.querySelectorAll('.iq-action-menu[open]').forEach(function(menu){menu.removeAttribute('open')});modal.classList.add('open');document.body.style.overflow='hidden';
}
function closeOfferPriceModal(){document.getElementById('offerPriceModal').classList.remove('open');document.body.style.overflow=''}
document.addEventListener('keydown',function(event){if(event.key==='Escape'){document.querySelectorAll('.iq-modal.open').forEach(function(modal){modal.classList.remove('open')});document.body.style.overflow=''}});
document.addEventListener('click',function(event){
    document.querySelectorAll('.iq-action-menu[open]').forEach(function(menu){
        if(!menu.contains(event.target))menu.removeAttribute('open');
    });
});
(function(){
    var form=document.getElementById('inquiryLiveFilter'), search=document.getElementById('inquiryLiveSearch'), workflow=document.getElementById('inquiryWorkflowFilter');
    if(!form||!search||!workflow)return;
    var timer=null, requestNumber=0;
    function updateResults(){
        var current=++requestNumber, params=new URLSearchParams();
        if(search.value.trim())params.set('search',search.value.trim());
        if(workflow.value)params.set('workflow',workflow.value);
        var url=window.location.pathname+(params.toString()?'?'+params.toString():'');
        var results=document.getElementById('inquiryResults');
        results.classList.add('loading');
        fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(response){return response.text()}).then(function(html){
            if(current!==requestNumber)return;
            var documentCopy=new DOMParser().parseFromString(html,'text/html'), fresh=documentCopy.getElementById('inquiryResults');
            if(fresh){results.innerHTML=fresh.innerHTML;window.history.replaceState({},'',url)}
        }).catch(function(){}).then(function(){if(current===requestNumber)results.classList.remove('loading')});
    }
    search.addEventListener('input',function(){clearTimeout(timer);timer=setTimeout(updateResults,300)});
    workflow.addEventListener('change',updateResults);
})();
</script>
@endsection
