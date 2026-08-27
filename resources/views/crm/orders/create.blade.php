@extends('crm.layout')

@section('title', 'Create Order')

@section('header_actions')
    <a href="{{ route('crm.orders.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:.55rem 1.1rem;background:#fff;color:#475569;border:1px solid #e2e8f0;border-radius:9px;font-weight:700;font-size:.82rem;text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Invoices</a>
@endsection

@section('content')
@php
    $__isAlMassaOrder = isset($activeCrmWorkspace) && $activeCrmWorkspace && $activeCrmWorkspace->slug === 'mybox-packaging-app';
@endphp
<style>
    .manual-order-wrap { max-width: 900px; margin: 1.5rem auto; }
    .manual-order-card { background: white; border: 1px solid #f1f5f9; border-radius: 16px; box-shadow: 0 4px 25px rgba(0,0,0,.05); overflow: hidden; }
    .manual-order-bar { height: 3px; background: linear-gradient(to right, var(--primary-purple), var(--primary-hover), var(--success-green)); }
    .manual-order-header { padding: 1.5rem 2rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 1rem; }
    .manual-order-icon { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: var(--primary-soft); color: var(--primary-purple); border-radius: 12px; }
    .manual-order-title { font-size: 1.1rem; font-weight: 800; color: #0f172a; }
    .manual-order-sub { margin-top: 2px; font-size: .75rem; color: #94a3b8; }
    .manual-order-body { padding: 2rem; }
    .section-title { margin: 0 0 1.15rem; padding-bottom: .5rem; border-bottom: 1px dashed #e2e8f0; color: var(--primary-purple); font-size: .72rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .form-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.15rem; margin-bottom: 1.75rem; }
    .form-group.full { grid-column: 1 / -1; }
    .form-group.span-2 { grid-column: span 2; }
    .form-label { display: block; margin-bottom: .45rem; color: #475569; font-size: .78rem; font-weight: 700; }
    .form-input,.form-select,.form-textarea { width: 100%; padding: .72rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #fdfdff; color: #0f172a; font-size: .85rem; outline: none; }
    .form-input:focus,.form-select:focus,.form-textarea:focus { border-color: var(--primary-purple); box-shadow: 0 0 0 3px var(--primary-shadow); background: white; }
    .form-textarea { resize: vertical; font-family: inherit; }
    .error-box { margin-bottom: 1.5rem; padding: 1rem 1.2rem; border: 1px solid #fecaca; border-radius: 10px; background: #fef2f2; color: #b91c1c; font-size: .82rem; }
    .form-actions { display: flex; gap: 1rem; }
    .btn-save,.btn-cancel { padding: .82rem 1.5rem; border-radius: 10px; font-size: .875rem; font-weight: 700; text-decoration: none; text-align: center; }
    .btn-save { flex: 1; border: 0; background: var(--primary-purple); color: white; cursor: pointer; box-shadow: 0 4px 12px var(--primary-shadow); transition: background 0.2s; }
    .btn-save:hover { background: var(--primary-hover); }
    .btn-cancel { border: 1.5px solid #e2e8f0; background: #f1f5f9; color: #475569; transition: background 0.2s; }
    .btn-cancel:hover { background: #e2e8f0; }
    @media(max-width:700px) { .form-grid { grid-template-columns: 1fr; } .form-group.full,.form-group.span-2 { grid-column: auto; } .manual-order-body { padding: 1.25rem; } }
</style>

<div class="manual-order-wrap">
    <div class="manual-order-card">
        <div class="manual-order-bar"></div>
        <div class="manual-order-header">
            <div class="manual-order-icon"><i class="fas fa-cart-plus"></i></div>
            <div>
                <div class="manual-order-title">Create Order</div>
                <div class="manual-order-sub">Record an order completed outside the CRM.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('crm.orders.store') }}" class="manual-order-body">
            @csrf

            @if($errors->any())
            <div class="error-box">
                <strong>Please correct the following:</strong>
                <ul style="margin:.5rem 0 0 1.2rem;">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <div class="section-title"><i class="fas fa-user"></i> Customer Information</div>
            <div class="form-grid">
                <div class="form-group"><label class="form-label">Client Name *</label><input class="form-input" name="client_name" value="{{ old('client_name') }}" required></div>
                <div class="form-group"><label class="form-label">Client Email *</label><input type="email" class="form-input" name="client_email" value="{{ old('client_email') }}" required></div>
                <div class="form-group"><label class="form-label">Client Phone</label><input class="form-input" name="client_phone" value="{{ old('client_phone') }}"></div>
                <div class="form-group"><label class="form-label">Customer TR No</label><input class="form-input" name="customer_trn" value="{{ old('customer_trn') }}" placeholder="Tax registration number"></div>
            </div>

            <div class="section-title"><i class="fas fa-box-open"></i> Order Details</div>
            <div class="form-grid">
                <div class="form-group"><label class="form-label">Invoice Number</label><input class="form-input" name="order_invoice_number" value="{{ old('order_invoice_number') }}" placeholder="e.g. INV-1024 (optional)"></div>
                <div class="form-group"><label class="form-label">Completed Date *</label><input type="date" class="form-input" name="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required></div>
                <div class="form-group"><label class="form-label">Length</label><input class="form-input" name="length" value="{{ old('length') }}"></div>
                <div class="form-group"><label class="form-label">Width</label><input class="form-input" name="width" value="{{ old('width') }}"></div>
                <div class="form-group"><label class="form-label">Height</label><input class="form-input" name="height" value="{{ old('height') }}"></div>
                <div class="form-group"><label class="form-label">Unit</label><select class="form-select" name="unit"><option value="inch">Inches</option><option value="mm" {{ old('unit') === 'mm' ? 'selected' : '' }}>Millimeters</option><option value="cm" {{ old('unit') === 'cm' ? 'selected' : '' }}>Centimeters</option></select></div>
                <div class="form-group"><label class="form-label">Material Stock</label><input class="form-input" name="stock" value="{{ old('stock') }}" placeholder="e.g. 14pt Cardstock"></div>
                <div class="form-group"><label class="form-label">Colors</label><input class="form-input" name="color" value="{{ old('color') }}" placeholder="e.g. 4/0 CMYK"></div>
                <div class="form-group"><label class="form-label">Coating / Lamination</label><input class="form-input" name="coating" value="{{ old('coating') }}"></div>
            </div>

            <div class="section-title"><i class="fas fa-boxes"></i> Products</div>
            <div id="orderItems" style="display:flex;flex-direction:column;gap:.5rem;margin-bottom:.5rem"></div>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
                <button type="button" class="oi-add" onclick="oiAddRow()" style="border:1px dashed #94a3b8;background:#fff;color:#475569;padding:.55rem 1rem;border-radius:9px;font-weight:700;cursor:pointer;font-size:.85rem">+ Add Product</button>
                <div style="font-size:.9rem;color:#475569;font-weight:600">Subtotal: <strong id="oiSubtotal" style="color:#0f172a;font-size:1.05rem">0.00</strong> <span id="oiCurr">AED</span></div>
            </div>

            <div class="section-title"><i class="fas fa-dollar-sign"></i> Pricing & Payment</div>
            <div class="form-grid">
                <div class="form-group"><label class="form-label">Payment Status *</label><select class="form-select" name="payment_status"><option value="Paid" {{ old('payment_status', 'Paid') === 'Paid' ? 'selected' : '' }}>Paid</option><option value="Unpaid" {{ old('payment_status') === 'Unpaid' ? 'selected' : '' }}>Unpaid</option></select></div>
                <div class="form-group"><label class="form-label">Currency *</label><select class="form-select" name="invoice_currency"><option value="AED" {{ old('invoice_currency', $__isAlMassaOrder ? 'AED' : 'USD') === 'AED' ? 'selected' : '' }}>AED</option><option value="USD" {{ old('invoice_currency', $__isAlMassaOrder ? 'AED' : 'USD') === 'USD' ? 'selected' : '' }}>USD</option><option value="GBP" {{ old('invoice_currency') === 'GBP' ? 'selected' : '' }}>GBP</option><option value="EUR" {{ old('invoice_currency') === 'EUR' ? 'selected' : '' }}>EUR</option></select></div>
                <div class="form-group"><label class="form-label">VAT Percentage *</label><input type="number" min="0" max="100" step="0.01" class="form-input" name="vat_percentage" value="{{ old('vat_percentage', $__isAlMassaOrder ? 5 : 0) }}" required></div>
                <div class="form-group"><label class="form-label">Company TR No</label><input class="form-input" name="company_trn" value="{{ old('company_trn') }}"></div>
                <div class="form-group"><label class="form-label">Trade License (TL) No</label><input class="form-input" name="trade_license_number" value="{{ old('trade_license_number', $__isAlMassaOrder ? '801625' : '') }}"></div>
            </div>

            <div class="section-title"><i class="fas fa-map-marker-alt"></i> Addresses & Notes</div>
            <div class="form-grid">
                <div class="form-group"><label class="form-label">Billing Address</label><textarea class="form-textarea" rows="4" name="billing_address">{{ old('billing_address') }}</textarea></div>
                <div class="form-group"><label class="form-label">Shipping Address</label><textarea class="form-textarea" rows="4" name="shipping_address">{{ old('shipping_address') }}</textarea></div>
                <div class="form-group"><label class="form-label">Order Notes</label><textarea class="form-textarea" rows="4" name="order_notes">{{ old('order_notes') }}</textarea></div>
            </div>

            <div class="form-actions">
                <a href="{{ route('crm.orders.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save"><i class="fas fa-check"></i> Create Completed Order</button>
            </div>
        </form>
    </div>
</div>

<script>
    var oiIndex = 0;
    var oiOld = @json(old('products', []));

    function oiRowHtml(i, name, qty, price) {
        return '<div class="oi-row" style="display:grid;grid-template-columns:1fr 90px 120px 120px 34px;gap:.5rem;align-items:center">'
            + '<input class="form-input" name="products[' + i + '][name]" value="' + (name || '') + '" placeholder="Product name / description" required>'
            + '<input class="form-input oi-qty" type="number" min="1" step="1" name="products[' + i + '][quantity]" value="' + (qty || '') + '" placeholder="Qty" required oninput="oiRecalc()">'
            + '<input class="form-input oi-price" type="number" min="0" step="0.001" inputmode="decimal" name="products[' + i + '][unit_price]" value="' + (price || '') + '" placeholder="Unit price" required oninput="oiRecalc()">'
            + '<input class="form-input oi-line" value="0.00" readonly tabindex="-1" style="background:#f1f5f9;text-align:right">'
            + '<button type="button" onclick="oiRemove(this)" title="Remove" style="border:none;background:#fef2f2;color:#dc2626;border-radius:8px;height:38px;cursor:pointer">&times;</button>'
            + '</div>';
    }

    function oiAddRow(name, qty, price) {
        var wrap = document.getElementById('orderItems');
        wrap.insertAdjacentHTML('beforeend', oiRowHtml(oiIndex++, name, qty, price));
        oiRecalc();
    }

    function oiRemove(btn) {
        var rows = document.querySelectorAll('#orderItems .oi-row');
        if (rows.length <= 1) return;
        btn.closest('.oi-row').remove();
        oiRecalc();
    }

    function oiRecalc() {
        var subtotal = 0;
        document.querySelectorAll('#orderItems .oi-row').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.oi-qty').value) || 0;
            var price = parseFloat(row.querySelector('.oi-price').value) || 0;
            var line = qty * price;
            row.querySelector('.oi-line').value = line.toFixed(2);
            subtotal += line;
        });
        document.getElementById('oiSubtotal').textContent = subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    (function () {
        var currSel = document.querySelector('[name="invoice_currency"]');
        if (currSel) {
            var syncCurr = function () { document.getElementById('oiCurr').textContent = currSel.value; };
            currSel.addEventListener('change', syncCurr); syncCurr();
        }
        if (oiOld && oiOld.length) {
            oiOld.forEach(function (p) { oiAddRow(p.name, p.quantity, p.unit_price); });
        } else {
            oiAddRow();
        }
    })();

</script>
@include('crm.partials.unsaved_guard', ['formSelector' => 'form.manual-order-body'])
@endsection
