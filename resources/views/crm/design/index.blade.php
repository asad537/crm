@extends('crm.layout')

@section('title', 'Design Tickets')

@section('content')
@php
    $designerAttachmentUrl = function ($path) {
        // Docroot IS the public/ folder, so serve straight from the web root.
        // Strip any stored 'public/' prefix and let asset() prepend APP_URL.
        $relativePath = ltrim(preg_replace('#^public/#', '', (string) $path), '/');

        return asset($relativePath);
    };
@endphp
<style>
.dt-page{color:#233047}.dt-hero{display:flex;justify-content:space-between;align-items:center;padding:1.2rem 1.35rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e4eaf1;border-radius:17px}.dt-hero h2{margin:0}.dt-muted{color:#8796aa;font-size:.76rem}.dt-count{padding:.55rem .75rem;border-radius:10px;background:var(--primary-soft);color:var(--primary-purple);font-weight:850}.dt-tabs{display:flex;gap:.55rem;margin-bottom:.85rem}.dt-tab{padding:.58rem .82rem;border-radius:9px;text-decoration:none;background:#eef2f7;color:#526176;font-weight:800;font-size:.76rem}.dt-tab.active{background:var(--primary-purple);color:#fff}.dt-wrap{overflow:auto;background:#fff;border:1px solid #e4eaf1;border-radius:15px;box-shadow:0 8px 26px rgba(15,23,42,.055)}.dt-table{width:100%;min-width:760px;border-collapse:collapse}.dt-table th{padding:.85rem 1rem;background:#f7f9fc;border-bottom:2px solid var(--primary-soft);text-align:left;color:#718096;font-size:.66rem;text-transform:uppercase;letter-spacing:.04em}.dt-table td{padding:.82rem 1rem;border-bottom:1px solid #edf1f5;vertical-align:middle;font-size:.78rem}.dt-table tbody tr:hover{background:var(--primary-soft)}.dt-client strong{display:block;color:#1f2b3d}.dt-chip{display:inline-flex;padding:.3rem .55rem;border-radius:999px;background:#eef2f7;color:#526176;font-size:.68rem;font-weight:800}.dt-chip.new{background:#fff3e8;color:#d65d14}.dt-chip.open{background:#eaf2ff;color:#285fbd}.dt-chip.forwarded{background:#eafbf2;color:#08784c}.dt-btn{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;min-height:34px;padding:.42rem .65rem;border:0;border-radius:8px;text-decoration:none;cursor:pointer;font-weight:800;font-size:.7rem}.dt-btn-main{background:var(--primary-purple);color:#fff}.dt-btn-soft{background:var(--primary-soft);color:var(--primary-purple)}.dt-btn-danger{background:#fff1f2;color:#be123c}.dt-files{display:flex;flex-wrap:wrap;gap:.3rem}.dt-file{padding:.28rem .45rem;border-radius:7px;background:#f1f5f9;color:#475569;text-decoration:none;font-size:.65rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.dt-empty{text-align:center;padding:2.2rem!important;color:#94a3b8}.dt-modal-bg{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.58)}.dt-modal{width:100%;max-width:920px;max-height:92vh;overflow:auto;background:#fff;border-radius:17px;box-shadow:0 24px 60px rgba(15,23,42,.25)}.dt-modal-head{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.15rem;border-bottom:1px solid #edf1f5}.dt-modal-head h3{margin:0}.dt-close{width:34px;height:34px;border:0;border-radius:9px;background:#eef2f7;cursor:pointer}.dt-form{padding:1.1rem}.dt-summary{display:grid;grid-template-columns:repeat(2,1fr);gap:.65rem;padding:.8rem;margin-bottom:.9rem;border-radius:11px;background:var(--primary-soft)}.dt-summary span{display:block;color:#7a899e;font-size:.65rem;text-transform:uppercase}.dt-summary strong{display:block;margin-top:.2rem}.dt-field{margin-bottom:.8rem}.dt-field label{display:block;margin-bottom:.35rem;color:#536277;font-size:.7rem;font-weight:800}.dt-control{width:100%;padding:.7rem .75rem;border:1.5px solid #dae3ed;border-radius:10px;box-sizing:border-box;outline:0}.dt-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}.dt-actions{display:flex;justify-content:flex-end;gap:.55rem;padding-top:.8rem;border-top:1px solid #edf1f5}.dt-detail-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.7rem}.dt-detail{padding:.72rem;border:1px solid #e7ecf2;border-radius:10px;background:#fbfcfe}.dt-detail span{display:block;margin-bottom:.25rem;color:#8695a9;font-size:.63rem;font-weight:800;text-transform:uppercase}.dt-detail strong{color:#273449}.dt-detail-wide{grid-column:1/-1}.dt-finish-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.4rem}.dt-finish-group{overflow:hidden;border:1px solid #e5eaf1;border-radius:9px;background:#fff}.dt-finish-parent{display:block!important;margin:0!important;padding:.38rem .45rem;background:var(--primary-soft);color:var(--primary-purple)!important;font-size:.61rem!important;font-weight:900!important}.dt-finish-children{display:flex;flex-wrap:wrap;gap:.22rem;padding:.4rem .45rem}.dt-finish-child{display:inline-flex!important;margin:0!important;padding:.23rem .38rem;border-radius:99px;background:#f1f5f9;color:#536277!important;font-size:.6rem!important;text-transform:none!important}@media(max-width:760px){.dt-finish-grid{grid-template-columns:1fr 1fr}}@media(max-width:480px){.dt-finish-grid{grid-template-columns:1fr}}
</style>
<style>.dt-finish-child{padding:0!important;border-radius:0;background:transparent}.dt-chip.returned_to_sales{background:var(--primary-soft);color:var(--primary-purple)}.dt-chip.returned_from_estimator{background:#fff1f2;color:#be123c}.dt-return-box{position:relative;overflow:hidden;margin:.9rem 1.1rem 1.1rem;padding:1rem;border:1px solid var(--primary-shadow);border-radius:13px;background:linear-gradient(135deg,#fff,var(--primary-soft));box-shadow:0 10px 25px rgba(15,23,42,.06)}.dt-return-box:before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:var(--primary-purple)}.dt-return-box label{display:block;margin-bottom:.5rem;color:var(--primary-purple);font-size:.72rem;font-weight:900}.dt-return-row{display:grid;grid-template-columns:1fr auto;align-items:stretch;gap:.6rem}.dt-return-row input{background:rgba(255,255,255,.92)}.dt-return-row .dt-btn-danger{min-width:145px;background:var(--primary-purple);color:#fff;box-shadow:0 7px 18px var(--primary-shadow)}@media(max-width:650px){.dt-return-row{grid-template-columns:1fr}.dt-return-box{margin:.75rem}}</style>
<style>
.dt-open-size{display:grid;grid-template-columns:1fr 1fr minmax(120px,.65fr);gap:.55rem}
.dt-open-input{position:relative}
.dt-open-input span{position:absolute;top:50%;right:.75rem;transform:translateY(-50%);color:#94a3b8;font-size:.68rem;font-weight:850;pointer-events:none}
.dt-open-input .dt-control{padding-right:2rem}
.dt-search-card{padding:.8rem;margin-bottom:.85rem;border:1px solid #e4eaf1;border-radius:13px;background:#fff;box-shadow:0 6px 20px rgba(15,23,42,.04)}
.dt-search-form{display:flex;gap:.55rem}.dt-search-field{position:relative;flex:1}.dt-search-field i{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:#94a3b8}.dt-search-field .dt-control{padding-left:2.25rem}
</style>
<div id="designTicketResults">
<div class="dt-page">
    <div class="dt-hero"><div><h2>Design Requirement Queue</h2><div class="dt-muted">Complete missing open sizes and forward customer files to Estimation.</div></div><div class="dt-count"><i class="fas fa-check-circle"></i> {{ $completedThisMonth }} completed this month</div></div>
    <div class="dt-tabs">
        <a class="dt-tab {{ $tab==='active'?'active':'' }}" href="{{ route('crm.design_tickets.index',['tab'=>'active']) }}">Active Tickets</a>
        @if(Auth::guard('crm')->user()->isDesigner())
            <a class="dt-tab {{ $tab==='mine'?'active':'' }}" href="{{ route('crm.design_tickets.index',['tab'=>'mine']) }}">My Open Tickets</a>
        @endif
        <a class="dt-tab {{ $tab==='history'?'active':'' }}" href="{{ route('crm.design_tickets.index',['tab'=>'history']) }}">History</a>
    </div>
    <div class="dt-search-card">
        <form id="designTicketSearchForm" class="dt-search-form" method="GET">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="dt-search-field"><i class="fas fa-search"></i><input class="dt-control" id="designTicketSearch" name="search" value="{{ request('search') }}" autocomplete="off" placeholder="Search inquiry number, client or product"></div>
            <button class="dt-btn dt-btn-main" type="submit"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
    <div class="dt-wrap"><table class="dt-table"><thead><tr><th>Ticket</th><th>Product</th><th>Quantities</th><th>Designer</th><th>Status</th><th>Action</th></tr></thead><tbody>
    @forelse($tickets as $ticket)
        @php
            $inquiry = $ticket->inquiry;
        @endphp
        <tr>
            <td><strong>{{ $ticket->ticket_number }}</strong><div class="dt-muted">{{ $ticket->created_at->format('d M Y, h:i A') }}</div></td>
            <td><div class="dt-client"><strong>{{ optional($inquiry)->product_name ?: 'Inquiry unavailable' }}</strong><div class="dt-muted">{{ optional($inquiry)->stock ?: 'No stock detail' }}</div></div></td>
            <td>@foreach(($ticket->quantities ?: []) as $qty)<span class="dt-chip">{{ number_format($qty) }}</span> @endforeach</td>
            <td>{{ $ticket->designer ? $ticket->designer->name : 'Unassigned' }}</td>
            <td>
                @if($ticket->return_note && in_array($ticket->status, ['open', 'new']))
                    <span class="dt-chip returned_from_estimator">Returned By Estimator</span>
                @else
                    <span class="dt-chip {{ $ticket->status }}">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span>
                @endif
            </td>
            <td>
                @if($ticket->status==='new' && Auth::guard('crm')->user()->isDesigner())
                    <form method="POST" action="{{ route('crm.design_requirements.claim',$ticket->id) }}">{{ csrf_field() }}<button class="dt-btn dt-btn-main"><i class="fas fa-eye"></i> View</button></form>
                @else
                    <button class="dt-btn dt-btn-main" type="button" onclick="openTicketDetail({{ $ticket->id }})"><i class="fas fa-eye"></i> View</button>
                @endif
            </td>
        </tr>
    @empty<tr><td colspan="6" class="dt-empty"><i class="fas fa-check-double"></i><br>No tickets in this section.</td></tr>@endforelse
    </tbody></table></div>
    {{ $tickets->links() }}
</div>
@foreach($tickets as $ticket)
@php
    $detailInquiry = $ticket->inquiry;
@endphp
@php
    $designFinishingDisplay = [];
    foreach ((array)(optional($detailInquiry)->custom_specs['Finishing Options'] ?? []) as $finishOption) {
        $parts = explode(' — ', $finishOption, 2);
        $designFinishingDisplay[$parts[0]][] = $parts[1] ?? $parts[0];
    }
@endphp
<div class="dt-modal-bg" id="ticketDetail{{ $ticket->id }}" onclick="if(event.target===this)closeTicketDetail({{ $ticket->id }})"><div class="dt-modal"><div class="dt-modal-head"><div><h3>{{ $ticket->ticket_number }} · Ticket Details</h3></div><button class="dt-close" onclick="closeTicketDetail({{ $ticket->id }})"><i class="fas fa-times"></i></button></div>
<div class="dt-form">
    <div class="dt-detail-grid">
        <div class="dt-detail"><span>Product</span><strong>{{ optional($detailInquiry)->product_name ?: 'Inquiry unavailable' }}</strong></div>
        <div class="dt-detail"><span>Created</span><strong>{{ $ticket->created_at->format('d M Y, h:i A') }}</strong></div>
        <div class="dt-detail"><span>Dimensions</span><strong>{{ optional($detailInquiry)->finish_size ?: '-' }} {{ optional($detailInquiry)->unit }}</strong></div>
        <div class="dt-detail"><span>Open Size</span><strong>{{ $ticket->open_size ?: 'Pending' }} {{ $ticket->unit }}</strong></div>
        <div class="dt-detail"><span>Stock / Material</span><strong>{{ optional($detailInquiry)->stock ?: '-' }}</strong></div>
        <div class="dt-detail"><span>Printing</span><strong>{{ optional($detailInquiry)->printing ?: '-' }}</strong></div>
        <div class="dt-detail"><span>Quantities</span><strong>{{ implode(', ', $ticket->quantities ?: []) }}</strong></div>
        <div class="dt-detail dt-detail-wide"><span>Finishing Options</span>@if(count($designFinishingDisplay))<div class="dt-finish-grid">@foreach($designFinishingDisplay as $finishGroup => $finishItems)<div class="dt-finish-group"><span class="dt-finish-parent">{{ $finishGroup }}</span><div class="dt-finish-children">@foreach($finishItems as $finishItem)<span class="dt-finish-child">{{ $finishItem }}</span>@endforeach</div></div>@endforeach</div>@else<strong>None selected</strong>@endif</div>
        <div class="dt-detail"><span>Designer / Status</span><strong>{{ $ticket->designer ? $ticket->designer->name : 'Unassigned' }} · @if($ticket->return_note && in_array($ticket->status, ['open', 'new'])) Returned By Estimator @else {{ ucwords(str_replace('_',' ',$ticket->status)) }} @endif</strong></div>
        <div class="dt-detail dt-detail-wide"><span>Sales Requirements</span><strong>{{ optional($detailInquiry)->csr_comment ?: '' }} {{ optional($detailInquiry)->message ?: 'No notes provided.' }}</strong></div>
        @if($ticket->return_note)<div class="dt-detail dt-detail-wide" style="border-color:#fecdd3;background:#fff7f7"><span>Return Note</span><strong>{{ $ticket->return_note }}</strong></div>@endif
        <div class="dt-detail dt-detail-wide"><span>Sales Files</span><div class="dt-files">@forelse($detailInquiry ? $detailInquiry->inquiryAttachments->where('stage','sales') : collect() as $file)<a class="dt-file" href="{{ $designerAttachmentUrl($file->file_path) }}" target="_blank" rel="noopener"><i class="fas fa-paperclip"></i> {{ $file->original_name }}</a>@empty<span class="dt-muted">No files attached</span>@endforelse</div></div>
    </div>
    <div class="dt-actions" style="margin-top:.9rem">
        <button class="dt-btn dt-btn-soft" type="button" onclick="closeTicketDetail({{ $ticket->id }})">Close</button>
        @if($ticket->status==='new' && Auth::guard('crm')->user()->isDesigner())
            <form method="POST" action="{{ route('crm.design_requirements.claim',$ticket->id) }}">{{ csrf_field() }}<button class="dt-btn dt-btn-main"><i class="fas fa-folder-open"></i> Open Ticket</button></form>
        @elseif(in_array($ticket->status,['open','on_hold']) && (Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || (int)$ticket->claimed_by===(int)Auth::guard('crm')->id()))
            <button class="dt-btn dt-btn-main" type="button" onclick="closeTicketDetail({{ $ticket->id }});openDesignModal({{ $ticket->id }})"><i class="fas fa-ruler-combined"></i> Continue Work</button>
            <form method="POST" action="{{ route('crm.design_requirements.release',$ticket->id) }}">{{ csrf_field() }}<button class="dt-btn dt-btn-danger"><i class="fas fa-undo"></i> Release</button></form>
        @elseif($ticket->status==='forwarded')
            <span class="dt-muted">Forwarded to {{ optional($ticket->estimateTicket)->ticket_number }}</span>
        @endif
    </div>
</div></div></div>
@endforeach
@foreach($tickets as $ticket)
@if(in_array($ticket->status,['open','on_hold']))
<div class="dt-modal-bg" id="designModal{{ $ticket->id }}" onclick="if(event.target===this)closeDesignModal({{ $ticket->id }})"><div class="dt-modal"><div class="dt-modal-head"><div><h3>{{ $ticket->ticket_number }} · Design Requirements</h3></div><button class="dt-close" onclick="closeDesignModal({{ $ticket->id }})"><i class="fas fa-times"></i></button></div>
@php $isOrderArtworkTicket = strpos($ticket->ticket_number, 'ART-') === 0; @endphp
<form class="dt-form" method="POST" enctype="multipart/form-data" action="{{ route('crm.design_requirements.complete',$ticket->id) }}">{{ csrf_field() }}
    <div class="dt-summary"><div><span>Product Name</span><strong>{{ optional($ticket->inquiry)->product_name ?: 'Inquiry unavailable' }}</strong></div><div><span>Dimensions</span><strong>{{ optional($ticket->inquiry)->finish_size ?: '-' }} {{ optional($ticket->inquiry)->unit }}</strong></div><div><span>Quantities</span><strong>{{ implode(', ', $ticket->quantities ?: []) }}</strong></div><div><span>Stock</span><strong>{{ optional($ticket->inquiry)->stock ?: '-' }}</strong></div><div><span>Printing</span><strong>{{ optional($ticket->inquiry)->printing ?: '-' }}</strong></div></div>
    @if($ticket->return_note)<div class="dt-return-box"><label>Estimator Return Note</label><div>{{ $ticket->return_note }}</div></div>@endif
    @if($isOrderArtworkTicket)
    <div class="dt-field"><label>Customer Artwork</label><div class="dt-files">@forelse($ticket->attachments->where('stage','order_artwork') as $file)<a class="dt-file" href="{{ $designerAttachmentUrl($file->file_path) }}" target="_blank" rel="noopener"><i class="fas fa-paperclip"></i> {{ $file->original_name }}</a>@empty<span class="dt-muted">No artwork attached</span>@endforelse</div></div>
    <div class="dt-field"><label>Designer Notes</label><textarea class="dt-control" name="designer_notes" rows="3">{{ $ticket->designer_notes }}</textarea></div>
    <div class="dt-field"><label>Final Artwork / Proof * (multiple allowed)</label><input class="dt-control" type="file" name="designer_files[]" multiple required></div>
    <div class="dt-actions"><button class="dt-btn dt-btn-soft" type="button" onclick="closeDesignModal({{ $ticket->id }})">Cancel</button><button class="dt-btn dt-btn-main"><i class="fas fa-paper-plane"></i> Send Final Artwork to Sales</button></div>
    @else
    @php
        $existingOpenSize = $ticket->open_size ?: optional($ticket->inquiry)->open_size;
        $openSizeParts = preg_split('/\s*(?:x|\*|×)\s*/i', (string) $existingOpenSize);
        $openLength = preg_replace('/[^0-9.]/', '', $openSizeParts[0] ?? '');
        $openWidth = preg_replace('/[^0-9.]/', '', $openSizeParts[1] ?? '');
    @endphp
    @php $ticketUnit = $ticket->unit ?: optional($ticket->inquiry)->unit; @endphp
    <div class="dt-field"><label>Open Size *</label><div class="dt-open-size"><div class="dt-open-input"><input class="dt-control" name="open_length" type="number" step="0.01" min="0.01" inputmode="decimal" placeholder="Length" value="{{ old('open_length',$openLength) }}" required><span>L</span></div><div class="dt-open-input"><input class="dt-control" name="open_width" type="number" step="0.01" min="0.01" inputmode="decimal" placeholder="Width" value="{{ old('open_width',$openWidth) }}" required><span>W</span></div><select class="dt-control" name="unit" aria-label="Open size unit" required><option value="mm" {{ $ticketUnit === 'mm' ? 'selected' : '' }}>mm</option><option value="cm" {{ $ticketUnit === 'cm' ? 'selected' : '' }}>cm</option><option value="inches" {{ $ticketUnit === 'inches' ? 'selected' : '' }}>inches</option></select></div></div>
    @if(optional($ticket->inquiry)->message || optional($ticket->inquiry)->csr_comment)<div class="dt-field"><label>Sales Requirements</label><div class="dt-control" style="background:#f8fafc;height:auto">{{ optional($ticket->inquiry)->csr_comment }} @if(optional($ticket->inquiry)->csr_comment && optional($ticket->inquiry)->message)<br>@endif {{ optional($ticket->inquiry)->message }}</div></div>@endif
    <div class="dt-field"><label>Designer Notes</label><textarea class="dt-control" name="designer_notes" rows="3"></textarea></div>
    <div class="dt-field"><label>Design Picture / Files * (multiple allowed)</label><input class="dt-control" type="file" name="designer_files[]" multiple required></div>
    <div class="dt-actions"><button class="dt-btn dt-btn-soft" type="button" onclick="closeDesignModal({{ $ticket->id }})">Cancel</button><button class="dt-btn dt-btn-main"><i class="fas fa-paper-plane"></i> Save & Send to Estimator</button></div>
    @endif
</form>
<form class="dt-return-box" method="POST" action="{{ route('crm.design_requirements.return_to_sales',$ticket->id) }}">{{ csrf_field() }}
    <label>Missing information? Return to Sales Agent with a note</label>
    <div class="dt-return-row"><input type="text" class="dt-control" name="return_note" placeholder="Explain what information or file is missing..." required><button class="dt-btn dt-btn-danger" type="submit"><i class="fas fa-undo"></i> Return to Sales</button></div>
</form>
</div></div>
@endif
@endforeach
</div>
@endsection
@section('scripts')
<script>
function openTicketDetail(id){document.getElementById('ticketDetail'+id).style.display='flex';document.body.style.overflow='hidden'}
function closeTicketDetail(id){document.getElementById('ticketDetail'+id).style.display='none';document.body.style.overflow=''}
function openDesignModal(id){document.getElementById('designModal'+id).style.display='flex';document.body.style.overflow='hidden'}
function closeDesignModal(id){document.getElementById('designModal'+id).style.display='none';document.body.style.overflow=''}
let designSearchTimer=null,designSearchController=null;
function loadDesignTickets(url=null){
    const form=document.getElementById('designTicketSearchForm');
    if(!form)return;
    const params=new URLSearchParams(new FormData(form));
    const targetUrl=url||(`${window.location.pathname}?${params.toString()}`);
    if(designSearchController)designSearchController.abort();
    designSearchController=new AbortController();
    fetch(targetUrl,{headers:{'X-Requested-With':'XMLHttpRequest'},signal:designSearchController.signal})
        .then(response=>response.text())
        .then(html=>{
            const doc=new DOMParser().parseFromString(html,'text/html');
            const fresh=doc.getElementById('designTicketResults');
            const current=document.getElementById('designTicketResults');
            if(fresh&&current){
                current.replaceWith(fresh);
                window.history.replaceState({},'',targetUrl);
                const input=document.getElementById('designTicketSearch');
                if(input){input.focus();input.setSelectionRange(input.value.length,input.value.length)}
            }
        })
        .catch(error=>{if(error.name!=='AbortError')console.error(error)});
}
document.addEventListener('input',event=>{
    if(event.target.id!=='designTicketSearch')return;
    clearTimeout(designSearchTimer);
    designSearchTimer=setTimeout(()=>loadDesignTickets(),300);
});
document.addEventListener('submit',event=>{
    if(event.target.id!=='designTicketSearchForm')return;
    event.preventDefault();
    loadDesignTickets();
});
document.addEventListener('click',event=>{
    const link=event.target.closest('#designTicketResults .pagination a');
    if(link){event.preventDefault();loadDesignTickets(link.href)}
});
@if(request()->filled('open_ticket'))
(function(){var id={{ (int) request('open_ticket') }};if(document.getElementById('ticketDetail'+id))openTicketDetail(id)})();
@endif
</script>
@endsection
