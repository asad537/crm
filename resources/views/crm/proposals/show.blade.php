@extends('crm.layout')
@section('title', 'Proposal')

@section('content')
<style>
.pr-wrap{max-width:960px;margin:0 auto}
.pr-top{display:flex;align-items:center;gap:.8rem;margin-bottom:1.1rem}
.pr-back{display:inline-flex;align-items:center;gap:.4rem;color:#64748b;text-decoration:none;font-weight:700;font-size:.85rem}
.pr-card{background:#fff;border:1px solid #e4eaf1;border-radius:16px;box-shadow:0 8px 26px rgba(15,23,42,.05);padding:1.4rem 1.5rem;margin-bottom:1.25rem}
.pr-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem}
.pr-title{margin:0;font-size:1.2rem;font-weight:850;color:#0f172a}
.pr-eyebrow{font-size:.63rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--primary-purple)}
.pr-status{display:inline-flex;padding:.34rem .7rem;border-radius:999px;font-size:.66rem;font-weight:850;text-transform:uppercase;letter-spacing:.03em}
.pr-status.requested{background:#eef2ff;color:#4338ca}
.pr-status.in_progress{background:#eaf2ff;color:#285fbd}
.pr-status.change_requested{background:#fff3e8;color:#c2410c}
.pr-status.completed{background:#eafbf2;color:#08784c}
.pr-grid{display:grid;grid-template-columns:1fr 1fr;gap:.9rem 1.2rem}
.pr-field{display:flex;flex-direction:column;gap:.3rem}
.pr-field.full{grid-column:1 / -1}
.pr-label{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#718096}
.pr-input,.pr-textarea,.pr-select{width:100%;padding:.6rem .7rem;border:1.5px solid #dbe3ec;border-radius:9px;background:#fff;outline:0;box-sizing:border-box;font:inherit;font-size:.85rem}
.pr-input:focus,.pr-textarea:focus,.pr-select:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.pr-textarea{min-height:90px;resize:vertical}
.pr-ro{padding:.6rem .7rem;border:1.5px solid #eef2f7;border-radius:9px;background:#f8fafc;color:#1f2b3d;font-size:.85rem;font-weight:600;min-height:40px;display:flex;align-items:center}
.pr-actions{display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1.1rem}
.pr-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.62rem 1rem;border-radius:10px;border:1px solid #dbe3ec;background:#fff;color:#475569;font-weight:800;font-size:.85rem;cursor:pointer;text-decoration:none}
.pr-btn.primary{background:var(--primary-purple);color:#fff;border-color:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}
.pr-btn.warn{background:#fff7ed;color:#c2410c;border-color:#fed7aa}
.pr-file a{color:var(--primary-purple);font-weight:700;text-decoration:none}
.pr-note{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:.9rem 1rem;color:#7c2d12;font-size:.85rem}
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
            <div>
                <div class="pr-eyebrow">Proposal #{{ $proposal->id }}</div>
                <h1 class="pr-title">{{ $proposal->subject }}</h1>
            </div>
            <span class="pr-status {{ $proposal->status }}">{{ ucwords(str_replace('_',' ',$proposal->status)) }}</span>
        </div>

        <div class="pr-grid">
            <div class="pr-field full">
                <span class="pr-label">Subject</span>
                @if($isAdmin)<input class="pr-input" name="subject" value="{{ old('subject',$proposal->subject) }}" required>@else<div class="pr-ro">{{ $proposal->subject }}</div>@endif
            </div>
            <div class="pr-field">
                <span class="pr-label">Client Name</span>
                @if($isAdmin)<input class="pr-input" name="client_name" value="{{ old('client_name',$proposal->client_name) }}">@else<div class="pr-ro">{{ $proposal->client_name ?: '—' }}</div>@endif
            </div>
            <div class="pr-field">
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

            <div class="pr-field full">
                <span class="pr-label">Server Path</span>
                <input class="pr-input" name="server_path" value="{{ old('server_path',$proposal->server_path) }}" placeholder="\\server\\design\\client\\file.ai">
            </div>
            <div class="pr-field full">
                <span class="pr-label">Comment</span>
                <textarea class="pr-textarea" name="comment" placeholder="Notes for the designer / proposal details...">{{ old('comment',$proposal->comment) }}</textarea>
            </div>
            <div class="pr-field full">
                <span class="pr-label">Attachment (design file)</span>
                @if($proposal->attachment_path)
                    <div class="pr-file" style="margin-bottom:.4rem"><i class="fas fa-paperclip"></i> <a href="{{ asset($proposal->attachment_path) }}" target="_blank" rel="noopener">{{ $proposal->attachment_name ?: 'Current file' }}</a></div>
                @endif
                <input class="pr-input" type="file" name="attachment">
            </div>
        </div>

        <div class="pr-actions">
            @if($isAdmin)
                <button class="pr-btn primary" type="submit"><i class="fas fa-save"></i> Save Proposal</button>
            @else
                <button class="pr-btn primary" type="submit" onclick="return confirm('Submit this proposal as completed? It will move to History.')"><i class="fas fa-check-circle"></i> Submit &amp; Complete</button>
            @endif
        </div>
    </form>

    @if($proposal->change_request_note)
        <div class="pr-card">
            <div class="pr-eyebrow" style="margin-bottom:.5rem">Latest Change Request</div>
            <div class="pr-note">{{ $proposal->change_request_note }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('crm.proposals.change_request', $proposal->id) }}" class="pr-card">
        {{ csrf_field() }}
        <div class="pr-eyebrow" style="margin-bottom:.5rem">Request a Change</div>
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
