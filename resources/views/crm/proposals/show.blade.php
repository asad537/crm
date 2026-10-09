@extends('crm.layout')
@section('title', 'Proposal')

@section('content')
<style>
.pr-wrap{width:100%;max-width:none;margin:0}
.pr-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem}
.pr-back{display:inline-flex;align-items:center;gap:.5rem;padding:.55rem .8rem;border:1px solid #e2e8f0;border-radius:10px;background:#fff;color:#52627a;text-decoration:none;font-weight:800;font-size:.78rem;transition:.18s ease}
.pr-back:hover{color:var(--primary-purple);border-color:#cfc7ff;transform:translateX(-2px)}
.pr-card{background:#fff;border:1px solid #e1e8f0;border-radius:18px;box-shadow:0 10px 30px rgba(15,23,42,.055);margin-bottom:1rem;overflow:hidden}
.pr-form-body{padding:1.15rem 1.35rem 1.3rem}
.pr-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:1.15rem 1.35rem;background:linear-gradient(105deg,#fbfaff 0%,#fff 48%,#f8faff 100%);border-bottom:1px solid #e8edf4}
.pr-heading{display:flex;align-items:center;gap:.85rem;min-width:0}
.pr-icon{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--primary-purple),#8372ff);color:#fff;display:grid;place-items:center;box-shadow:0 7px 16px var(--primary-shadow);flex:0 0 auto}
.pr-title{margin:.12rem 0 0;font-size:1.15rem;line-height:1.25;font-weight:850;color:#0f172a}
.pr-eyebrow{font-size:.62rem;font-weight:850;letter-spacing:.09em;text-transform:uppercase;color:var(--primary-purple)}
.pr-status{display:inline-flex;align-items:center;gap:.35rem;padding:.42rem .72rem;border-radius:999px;font-size:.65rem;font-weight:850;text-transform:uppercase;letter-spacing:.035em;white-space:nowrap}
.pr-status:before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}
.pr-status.requested{background:#eef2ff;color:#4338ca}
.pr-status.in_progress{background:#eaf2ff;color:#285fbd}
.pr-status.change_requested{background:#fff3e8;color:#c2410c}
.pr-status.completed{background:#eafbf2;color:#08784c}
.pr-section-title{display:flex;align-items:center;gap:.55rem;margin:0 0 .85rem;color:#334155;font-size:.76rem;font-weight:850;text-transform:uppercase;letter-spacing:.055em}
.pr-section-title:after{content:'';height:1px;background:#e7edf4;flex:1}
.pr-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem 1rem}
.pr-field{display:flex;flex-direction:column;gap:.3rem}
.pr-field.span-2{grid-column:span 2}
.pr-field.span-3{grid-column:span 3}
.pr-field.full{grid-column:1 / -1}
.pr-label{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#718096}
.pr-input,.pr-textarea,.pr-select{width:100%;padding:.66rem .75rem;border:1.5px solid #dbe3ec;border-radius:10px;background:#fff;outline:0;box-sizing:border-box;font:inherit;font-size:.83rem;transition:border-color .18s,box-shadow .18s}
.pr-input:focus,.pr-textarea:focus,.pr-select:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.pr-textarea{min-height:92px;resize:vertical}
.pr-ro{padding:.66rem .75rem;border:1.5px solid #edf1f6;border-radius:10px;background:#f8fafc;color:#1f2b3d;font-size:.83rem;font-weight:650;min-height:42px;display:flex;align-items:center}
.pr-upload{min-height:92px;padding:.85rem;border:1.5px dashed #cfd8e5;border-radius:12px;background:#f8fafc;display:flex;flex-direction:column;justify-content:center;gap:.55rem}
.pr-upload .pr-input{padding:.45rem;background:#fff}
.pr-file{display:flex;align-items:center;gap:.45rem;font-size:.78rem}
.pr-actions{display:flex;align-items:center;justify-content:flex-end;gap:.6rem;flex-wrap:wrap;margin-top:1.1rem;padding-top:1rem;border-top:1px solid #e8edf4}
.pr-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.62rem 1rem;border-radius:10px;border:1px solid #dbe3ec;background:#fff;color:#475569;font-weight:800;font-size:.85rem;cursor:pointer;text-decoration:none}
.pr-btn.primary{background:var(--primary-purple);color:#fff;border-color:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}
.pr-btn.warn{background:#fff7ed;color:#c2410c;border-color:#fed7aa}
.pr-file a{color:var(--primary-purple);font-weight:700;text-decoration:none}
.pr-note{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:.9rem 1rem;color:#7c2d12;font-size:.85rem}
.pr-change{padding:1.15rem 1.35rem}
.pr-change-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.85rem}
.pr-change-title{margin:0;color:#172033;font-size:.95rem;font-weight:850}
.pr-change-copy{margin:.2rem 0 0;color:#7a879b;font-size:.76rem}
@media(max-width:1100px){.pr-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.pr-field.span-3{grid-column:span 2}}
@media(max-width:640px){.pr-form-body,.pr-head,.pr-change{padding:1rem}.pr-grid{grid-template-columns:1fr}.pr-field.span-2,.pr-field.span-3,.pr-field.full{grid-column:1}.pr-title{font-size:1rem}.pr-actions{justify-content:stretch}.pr-btn{justify-content:center;flex:1}.pr-change-head{display:block}}
</style>

<div class="pr-wrap">
    <div class="pr-top">
        <a href="{{ route('crm.proposals.index') }}" class="pr-back"><i class="fas fa-arrow-left"></i> Back to Proposals</a>
    </div>

    @if(session('success'))
        <div class="pr-card" style="border-color:#a7f3d0;background:#ecfdf5;color:#065f46;padding:.85rem 1.1rem;font-weight:700">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('crm.proposals.update', $proposal->id) }}" enctype="multipart/form-data" class="pr-card">
        {{ csrf_field() }}
        <div class="pr-head">
            <div class="pr-heading">
                <div class="pr-icon"><i class="fas fa-file-signature"></i></div>
                <div>
                    <div class="pr-eyebrow">Proposal #{{ $proposal->id }}</div>
                    <h1 class="pr-title">{{ $proposal->subject }}</h1>
                </div>
            </div>
            <span class="pr-status {{ $proposal->status }}">{{ ucwords(str_replace('_',' ',$proposal->status)) }}</span>
        </div>

        <div class="pr-form-body">
        <div class="pr-section-title"><i class="fas fa-clipboard-list"></i> Proposal Details</div>
        <div class="pr-grid">
            <div class="pr-field full">
                <span class="pr-label">Subject</span>
                @if($isAdmin)<input class="pr-input" name="subject" value="{{ old('subject',$proposal->subject) }}" required>@else<div class="pr-ro">{{ $proposal->subject }}</div>@endif
            </div>
            <div class="pr-field span-2">
                <span class="pr-label">Client Name</span>
                @if($isAdmin)<input class="pr-input" name="client_name" value="{{ old('client_name',$proposal->client_name) }}">@else<div class="pr-ro">{{ $proposal->client_name ?: '—' }}</div>@endif
            </div>
            <div class="pr-field span-2">
                <span class="pr-label">Product</span>
                @if($isAdmin)<input class="pr-input" name="product_name" value="{{ old('product_name',$proposal->product_name) }}">@else<div class="pr-ro">{{ $proposal->product_name ?: '—' }}</div>@endif
            </div>
            <div class="pr-field">
                <span class="pr-label">Quantity</span>
                @if($isAdmin)<input class="pr-input" name="quantity" value="{{ old('quantity',$proposal->quantity) }}">@else<div class="pr-ro">{{ $proposal->quantity ?: '—' }}</div>@endif
            </div>
            <div class="pr-field">
                <span class="pr-label">Size</span>
                @if($isAdmin)<input class="pr-input" name="size" value="{{ old('size',$proposal->size) }}">@else<div class="pr-ro">{{ $proposal->size ?: '—' }}</div>@endif
            </div>

            <div class="pr-field">
                <span class="pr-label">Assigned Designer</span>
                @if($isAdmin)
                    <select class="pr-select" name="assigned_designer_id">
                        <option value="">— Not assigned —</option>
                        @foreach($designers as $d)
                            <option value="{{ $d->id }}" {{ (int)old('assigned_designer_id',$proposal->assigned_designer_id)===(int)$d->id?'selected':'' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                @else
                    <input class="pr-input" value="{{ optional($proposal->designer)->name ?: 'Not assigned' }}" disabled>
                @endif
            </div>
            <div class="pr-field">
                <span class="pr-label">Status</span>
                @if($isAdmin)
                    <select class="pr-select" name="status">
                        @foreach(['requested'=>'Requested','in_progress'=>'In Progress','change_requested'=>'Change Requested','completed'=>'Completed'] as $val=>$lbl)
                            <option value="{{ $val }}" {{ old('status',$proposal->status)===$val?'selected':'' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="pr-ro">{{ ucwords(str_replace('_',' ',$proposal->status)) }}</div>
                @endif
            </div>

            <div class="pr-field full" style="margin-top:.2rem">
                <span class="pr-label">Server Path</span>
                <input class="pr-input" name="server_path" value="{{ old('server_path',$proposal->server_path) }}" placeholder="\\server\\design\\client\\file.ai">
            </div>
            <div class="pr-field span-3">
                <span class="pr-label">Comment</span>
                <textarea class="pr-textarea" name="comment" placeholder="Notes for the designer / proposal details...">{{ old('comment',$proposal->comment) }}</textarea>
            </div>
            <div class="pr-field">
                <span class="pr-label">Attachment (design file)</span>
                <div class="pr-upload">
                @if($proposal->attachment_path)
                    <div class="pr-file"><i class="fas fa-paperclip"></i> <a href="{{ asset($proposal->attachment_path) }}" target="_blank" rel="noopener">{{ $proposal->attachment_name ?: 'Current file' }}</a></div>
                @endif
                <input class="pr-input" type="file" name="attachment">
                </div>
            </div>
        </div>

        <div class="pr-actions">
            @if($isAdmin)
                <button class="pr-btn primary" type="submit"><i class="fas fa-save"></i> Save Proposal</button>
            @else
                <button class="pr-btn primary" type="submit" onclick="return confirm('Submit this proposal as completed? It will move to History.')"><i class="fas fa-check-circle"></i> Submit &amp; Complete</button>
            @endif
        </div>
        </div>
    </form>

    @if($proposal->change_request_note)
        <div class="pr-card pr-change">
            <div class="pr-eyebrow" style="margin-bottom:.5rem">Latest Change Request</div>
            <div class="pr-note">{{ $proposal->change_request_note }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('crm.proposals.change_request', $proposal->id) }}" class="pr-card pr-change">
        {{ csrf_field() }}
        <div class="pr-change-head">
            <div>
                <div class="pr-eyebrow">Revision</div>
                <h2 class="pr-change-title">Request a Change</h2>
                <p class="pr-change-copy">Send clear revision notes back to the assigned designer.</p>
            </div>
        </div>
        <div class="pr-field full">
            <textarea class="pr-textarea" name="change_request_note" placeholder="Describe the change you need on this proposal..." required></textarea>
        </div>
        <div class="pr-actions">
            <button class="pr-btn warn" type="submit"><i class="fas fa-undo-alt"></i> Submit Change Request</button>
            @if($isAdmin)
                <button class="pr-btn" type="submit" form="prDeleteForm" onclick="return confirm('Delete this proposal?')" style="color:#dc2626;border-color:#fecaca"><i class="fas fa-trash-alt"></i> Delete</button>
            @endif
        </div>
    </form>

    @if($isAdmin)
        <form id="prDeleteForm" method="POST" action="{{ route('crm.proposals.destroy', $proposal->id) }}" style="display:none">
            {{ csrf_field() }}<input type="hidden" name="_method" value="DELETE">
        </form>
    @endif
</div>
@endsection
