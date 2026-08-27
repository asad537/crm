@extends('crm.layout')
@section('title', 'Edit Estimate Request')
@section('content')
<style>.ef-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.5rem;margin-bottom:1rem;box-shadow:0 5px 16px rgba(15,23,42,.05)}.ef-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}.ef-field label{display:block;font-size:.78rem;font-weight:800;color:#475569;margin-bottom:6px}.ef-field input,.ef-field select,.ef-field textarea{width:100%;box-sizing:border-box;padding:.72rem;border:1px solid #cbd5e1;border-radius:9px;font:inherit}.ef-btn{padding:.7rem 1rem;border:none;border-radius:9px;font-weight:800;cursor:pointer}.ef-primary{background:var(--primary-purple);color:#fff}@media(max-width:900px){.ef-grid{grid-template-columns:1fr}}</style>
<form action="{{ route('crm.estimate_tickets.update', $ticket->id) }}" method="POST">{{ csrf_field() }}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem"><p style="color:#64748b;margin:0">Edit and resubmit the returned estimate ticket to the estimator.</p><a href="{{ route('crm.estimate_tickets.show', $ticket->id) }}" style="color:#64748b;text-decoration:none"><i class="fas fa-arrow-left"></i> Back to Ticket</a></div>
@if($errors->any() || session('error'))<div style="padding:1rem;background:#fef2f2;color:#b91c1c;border-radius:10px;margin-bottom:1rem">{{ $errors->first() ?: session('error') }}</div>@endif
<div class="ef-card"><h3 style="margin-top:0">Client & Product</h3>
<div class="ef-grid"><div class="ef-field"><label>Client Name *</label><input id="clientName" name="client_name" required value="{{ old('client_name', $ticket->client_name) }}"></div><div class="ef-field"><label>Client Email</label><input id="clientEmail" type="email" name="client_email" value="{{ old('client_email', $ticket->client_email) }}"></div><div class="ef-field"><label>Product *</label><input id="productStyle" name="product_style" required value="{{ old('product_style', $ticket->product_style) }}"></div></div></div>
<div class="ef-card"><h3 style="margin-top:0">Estimator Specifications</h3><div class="ef-grid"><div class="ef-field"><label>Printing</label><input id="specPrinting" name="printing" placeholder="e.g. 4/0 CMYK, PMS" value="{{ old('printing', $ticket->printing) }}"></div><div class="ef-field"><label>Paper Dimensions</label><input name="finish_size" placeholder="e.g. 1000 × 700 mm" value="{{ old('finish_size', $ticket->finish_size) }}"></div><div class="ef-field"><label>Flat Size</label><input name="flat_size" placeholder="e.g. 22 × 18 inches" value="{{ old('flat_size', $ticket->flat_size) }}"></div><div class="ef-field"><label>Stock</label><input id="specStock" name="stock" placeholder="Material and thickness" value="{{ old('stock', $ticket->stock) }}"></div><div class="ef-field"><label>Shipping</label><input name="shipping" placeholder="Location / shipping method" value="{{ old('shipping', $ticket->shipping) }}"></div><div class="ef-field"><label>Weight</label><input name="weight" placeholder="e.g. 12 kg / 26 lb" value="{{ old('weight', $ticket->weight) }}"></div><div class="ef-field"><label>Assign Estimator *</label><select name="estimator_id" required><option value="">Choose estimator</option>@foreach($estimators as $e)<option value="{{ $e->id }}" {{ (int)old('estimator_id', $ticket->estimator_id) === (int)$e->id ? 'selected' : '' }}>{{ $e->name }}</option>@endforeach</select></div></div></div>
<div class="ef-card"><div style="display:flex;justify-content:space-between;align-items:center"><h3 style="margin:0">Quantity Options</h3><button type="button" class="ef-btn" id="addQuantityOption"><i class="fas fa-plus"></i> Add Quantity</button></div><div id="optionRows" style="margin-top:1rem">
@php $oldQty = old('quantities', $ticket->options->pluck('quantity')->all() ?: []); @endphp
@foreach($oldQty as $qty)
<div style="display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:end;padding:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:.75rem">
<div class="ef-field"><label>Quantity *</label><input type="number" min="1" name="quantities[]" required value="{{ $qty }}"></div><button type="button" data-remove-option class="ef-btn" style="background:#fef2f2;color:#dc2626"><i class="fas fa-trash"></i></button>
</div>
@endforeach
</div></div>
<div style="display:flex;justify-content:flex-end"><button class="ef-btn ef-primary" style="padding:.9rem 1.5rem"><i class="fas fa-paper-plane"></i> Resubmit Ticket to Estimator</button></div></form>
<script>
(function () {
    const optionRows = document.getElementById('optionRows');
    const addQuantity = document.getElementById('addQuantityOption');

    function addOption() {
        const row = document.createElement('div');
        row.style.cssText = 'display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:end;padding:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:.75rem';
        row.innerHTML = '<div class="ef-field"><label>Quantity *</label><input type="number" min="1" name="quantities[]" required></div><button type="button" data-remove-option class="ef-btn" style="background:#fef2f2;color:#dc2626"><i class="fas fa-trash"></i></button>';
        optionRows.appendChild(row);
    }

    addQuantity.addEventListener('click', addOption);
    optionRows.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-option]');
        if (remove) remove.closest('div[style*="display:grid"]').remove();
    });

    if (!optionRows.children.length) {
        addOption(); addOption(); addOption();
    }
})();
</script>
@endsection
