@extends('crm.layout')
@section('title', 'Create Proposal')

@section('content')
<style>
.pr-wrap{max-width:820px;margin:0 auto}
.pr-top{display:flex;align-items:center;gap:.8rem;margin-bottom:1.1rem}
.pr-back{display:inline-flex;align-items:center;gap:.4rem;color:#64748b;text-decoration:none;font-weight:700;font-size:.85rem}
.pr-card{background:#fff;border:1px solid #e4eaf1;border-radius:16px;box-shadow:0 8px 26px rgba(15,23,42,.05);padding:1.4rem 1.5rem}
.pr-title{margin:0 0 1rem;font-size:1.2rem;font-weight:850;color:#0f172a}
.pr-grid{display:grid;grid-template-columns:1fr 1fr;gap:.9rem 1.2rem}
.pr-field{display:flex;flex-direction:column;gap:.3rem}
.pr-field.full{grid-column:1 / -1}
.pr-label{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#718096}
.pr-input,.pr-textarea,.pr-select{width:100%;padding:.6rem .7rem;border:1.5px solid #dbe3ec;border-radius:9px;background:#fff;outline:0;box-sizing:border-box;font:inherit;font-size:.85rem}
.pr-input:focus,.pr-textarea:focus,.pr-select:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.pr-textarea{min-height:90px;resize:vertical}
.pr-actions{display:flex;gap:.6rem;margin-top:1.1rem}
.pr-btn{display:inline-flex;align-items:center;gap:.45rem;padding:.62rem 1rem;border-radius:10px;border:1px solid #dbe3ec;background:#fff;color:#475569;font-weight:800;font-size:.85rem;cursor:pointer;text-decoration:none}
.pr-btn.primary{background:var(--primary-purple);color:#fff;border-color:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}
.pr-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;border-radius:10px;padding:.7rem 1rem;margin-bottom:1rem;font-size:.82rem}
</style>

<div class="pr-wrap">
    <div class="pr-top"><a href="{{ route('crm.proposals.index') }}" class="pr-back"><i class="fas fa-arrow-left"></i> Back to Proposals</a></div>

    @if($errors->any())
        <div class="pr-err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('crm.proposals.store') }}" enctype="multipart/form-data" class="pr-card">
        {{ csrf_field() }}
        <h1 class="pr-title"><i class="fas fa-file-signature" style="color:var(--primary-purple);margin-right:.4rem"></i> Create Proposal</h1>
        <div class="pr-grid">
            <div class="pr-field full">
                <span class="pr-label">Subject *</span>
                <input class="pr-input" name="subject" value="{{ old('subject') }}" placeholder="e.g. Display Boxes — Proposal" required>
            </div>
            <div class="pr-field">
                <span class="pr-label">Client Name</span>
                <input class="pr-input" name="client_name" value="{{ old('client_name') }}">
            </div>
            <div class="pr-field">
                <span class="pr-label">Product</span>
                <input class="pr-input" name="product_name" value="{{ old('product_name') }}">
            </div>
            <div class="pr-field">
                <span class="pr-label">Quantity</span>
                <input class="pr-input" name="quantity" value="{{ old('quantity') }}">
            </div>
            <div class="pr-field">
                <span class="pr-label">Size</span>
                <input class="pr-input" name="size" value="{{ old('size') }}">
            </div>
            <div class="pr-field">
                <span class="pr-label">Assigned Designer</span>
                <select class="pr-select" name="assigned_designer_id">
                    <option value="">— Not assigned —</option>
                    @foreach($designers as $d)
                        <option value="{{ $d->id }}" {{ (int)old('assigned_designer_id')===(int)$d->id?'selected':'' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pr-field">
                <span class="pr-label">Server Path</span>
                <input class="pr-input" name="server_path" value="{{ old('server_path') }}">
            </div>
            <div class="pr-field full">
                <span class="pr-label">Comment</span>
                <textarea class="pr-textarea" name="comment" placeholder="Notes / details for the designer...">{{ old('comment') }}</textarea>
            </div>
            <div class="pr-field full">
                <span class="pr-label">Attachment (design file)</span>
                <input class="pr-input" type="file" name="attachment">
            </div>
        </div>
        <div class="pr-actions">
            <button class="pr-btn primary" type="submit"><i class="fas fa-paper-plane"></i> Create Proposal</button>
            <a href="{{ route('crm.proposals.index') }}" class="pr-btn">Cancel</a>
        </div>
    </form>
</div>
@endsection
