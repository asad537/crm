@extends('crm.layout')
@section('title', 'New Estimate Request')
@section('content')
<style>.ef-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.5rem;margin-bottom:1rem;box-shadow:0 5px 16px rgba(15,23,42,.05)}.ef-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}.ef-field label{display:block;font-size:.78rem;font-weight:800;color:#475569;margin-bottom:6px}.ef-field input,.ef-field select,.ef-field textarea{width:100%;box-sizing:border-box;padding:.72rem;border:1px solid #cbd5e1;border-radius:9px;font:inherit}.ef-btn{padding:.7rem 1rem;border:none;border-radius:9px;font-weight:800;cursor:pointer}.ef-primary{background:var(--primary-purple);color:#fff}@media(max-width:900px){.ef-grid{grid-template-columns:1fr}}</style>
<form action="{{ route('crm.estimate_tickets.store') }}" method="POST" enctype="multipart/form-data">{{ csrf_field() }}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem"><p style="color:#64748b;margin:0">Send multiple quantity options to an estimator in one ticket.</p><a href="{{ route('crm.estimate_tickets.index') }}" style="color:#64748b;text-decoration:none"><i class="fas fa-arrow-left"></i> Back</a></div>
@if($errors->any() || session('error'))<div style="padding:1rem;background:#fef2f2;color:#b91c1c;border-radius:10px;margin-bottom:1rem">{{ $errors->first() ?: session('error') }}</div>@endif
<div class="ef-card"><h3 style="margin-top:0">Client & Product</h3><div class="ef-field" style="margin-bottom:1rem"><label>Load from existing lead (optional)</label><input type="hidden" id="selectedLeadId" name="crm_email_id"><div style="position:relative"><i class="fas fa-search" style="position:absolute;left:13px;top:21px;transform:translateY(-50%);color:#94a3b8;z-index:2"></i><input id="leadSearch" type="search" autocomplete="off" placeholder="Search by lead ID, client, email or product..." style="padding-left:2.4rem"><div id="leadResults" style="display:none;position:absolute;left:0;right:0;top:calc(100% + 6px);z-index:50;background:#fff;border:1px solid #dbe3ef;border-radius:10px;box-shadow:0 14px 30px rgba(15,23,42,.14);max-height:280px;overflow-y:auto"></div></div></div><div class="ef-grid"><div class="ef-field"><label>Client Name *</label><input id="clientName" name="client_name" required value="{{ old('client_name') }}"></div><div class="ef-field"><label>Client Email</label><input id="clientEmail" type="email" name="client_email" value="{{ old('client_email') }}"></div><div class="ef-field"><label>Product *</label><input id="productStyle" name="product_style" required value="{{ old('product_style') }}"></div></div></div>
<div class="ef-card"><h3 style="margin-top:0">Estimator Specifications</h3><div class="ef-grid"><div class="ef-field"><label>Printing</label><input id="specPrinting" name="printing" placeholder="e.g. 4/0 CMYK, PMS"></div><div class="ef-field"><label>Paper Dimensions</label><input name="finish_size" placeholder="e.g. 1000 × 700 mm"></div><div class="ef-field"><label>Flat Size</label><input name="flat_size" placeholder="e.g. 22 × 18 inches"></div><div class="ef-field"><label>Stock</label><input id="specStock" name="stock" placeholder="Material and thickness"></div><div class="ef-field"><label>Shipping</label><input name="shipping" placeholder="Location / shipping method"></div><div class="ef-field"><label>Weight</label><input name="weight" placeholder="e.g. 12 kg / 26 lb"></div><div class="ef-field"><label>Assign Estimator *</label><select name="estimator_id" required><option value="">Choose estimator</option>@foreach($estimators as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div></div></div>
<div class="ef-card"><div style="display:flex;justify-content:space-between;align-items:center"><h3 style="margin:0">Quantity Options</h3><button type="button" class="ef-btn" id="addQuantityOption"><i class="fas fa-plus"></i> Add Quantity</button></div><div id="optionRows" style="margin-top:1rem"></div></div>
<div style="display:flex;justify-content:flex-end"><button class="ef-btn ef-primary" style="padding:.9rem 1.5rem"><i class="fas fa-paper-plane"></i> Send Ticket to Estimator</button></div></form>
<script>
(function () {
    window.estimateLeadData = @json($leads->keyBy('id'));

    function escapeLeadText(value) {
        const node = document.createElement('div');
        node.textContent = value || '';
        return node.innerHTML;
    }

    function initEstimateRequestPage() {
        const search = document.getElementById('leadSearch');
        const results = document.getElementById('leadResults');
        const selectedId = document.getElementById('selectedLeadId');
        const optionRows = document.getElementById('optionRows');
        const addQuantity = document.getElementById('addQuantityOption');
        if (!search || !results || !selectedId || !optionRows || search.dataset.estimateReady === '1') return;

        search.dataset.estimateReady = '1';
        const leadData = window.estimateLeadData || {};

        function fillLead(id) {
            const lead = leadData[id];
            if (!lead) return;
            document.getElementById('clientName').value = lead.client_name || '';
            document.getElementById('clientEmail').value = lead.client_email || '';
            document.getElementById('productStyle').value = lead.product_name || '';
            document.getElementById('specStock').value = lead.stock || '';
            document.getElementById('specPrinting').value = lead.color || '';
        }

        function selectLead(id) {
            const lead = leadData[id];
            if (!lead) return;
            selectedId.value = id;
            search.value = `#${lead.id} — ${lead.client_name || lead.client_email || 'Unknown'} — ${lead.product_name || ''}`;
            fillLead(id);
            results.style.display = 'none';
        }

        function searchLeads(term, preserveSelection) {
            const query = (term || '').trim().toLowerCase();
            if (!preserveSelection) selectedId.value = '';
            const all = Object.values(leadData);
            const matches = (query ? all.filter(lead => [
                `#${lead.id}`, lead.client_name, lead.client_email, lead.product_name
            ].join(' ').toLowerCase().includes(query)) : all).slice(0, 12);

            results.innerHTML = matches.length ? matches.map(lead =>
                `<button type="button" data-lead-id="${lead.id}" style="width:100%;border:0;border-bottom:1px solid #f1f5f9;background:#fff;padding:.8rem 1rem;text-align:left;cursor:pointer"><strong style="color:#1e293b">#${lead.id} — ${escapeLeadText(lead.client_name || lead.client_email || 'Unknown')}</strong><div style="font-size:.78rem;color:#64748b;margin-top:3px">${escapeLeadText(lead.client_email || '')}${lead.product_name ? ' • ' + escapeLeadText(lead.product_name) : ''}</div></button>`
            ).join('') : '<div style="padding:1rem;color:#94a3b8;text-align:center">No matching lead found</div>';
            results.style.display = 'block';
        }

        function addOption() {
            const row = document.createElement('div');
            row.style.cssText = 'display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:end;padding:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:.75rem';
            row.innerHTML = '<div class="ef-field"><label>Quantity *</label><input type="number" min="1" name="quantities[]" required></div><button type="button" data-remove-option class="ef-btn" style="background:#fef2f2;color:#dc2626"><i class="fas fa-trash"></i></button>';
            optionRows.appendChild(row);
        }

        search.addEventListener('input', () => searchLeads(search.value, false));
        search.addEventListener('focus', () => searchLeads(search.value, true));
        results.addEventListener('click', event => {
            const choice = event.target.closest('[data-lead-id]');
            if (choice) selectLead(choice.dataset.leadId);
        });
        addQuantity.addEventListener('click', addOption);
        optionRows.addEventListener('click', event => {
            const remove = event.target.closest('[data-remove-option]');
            if (remove) remove.closest('div[style*="display:grid"]').remove();
        });

        if (window.estimateRequestOutsideClick) {
            document.removeEventListener('click', window.estimateRequestOutsideClick);
        }
        window.estimateRequestOutsideClick = event => {
            if (!event.target.closest('#leadSearch') && !event.target.closest('#leadResults')) {
                results.style.display = 'none';
            }
        };
        document.addEventListener('click', window.estimateRequestOutsideClick);

        if (!optionRows.children.length) {
            addOption(); addOption(); addOption();
        }
    }

    initEstimateRequestPage();
})();
</script>
@endsection
