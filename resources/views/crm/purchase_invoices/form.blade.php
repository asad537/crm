@extends('crm.layout')
@section('title', $invoice->exists ? 'Edit ' . $invoice->number : 'New Purchase Invoice')
@section('content')
@php
    $items = old('items', $invoice->exists
        ? $invoice->items->map(fn ($i) => ['fixed_asset_id' => $i->fixed_asset_id, 'inventory_item_id' => $i->inventory_item_id, 'name' => $i->name, 'category' => $i->category, 'quantity' => (float) $i->quantity, 'unit_price' => (float) $i->unit_price])->all()
        : [['name' => '', 'category' => '', 'quantity' => 1, 'unit_price' => '']]);
    $assetMap = collect($assetOptions)->keyBy('id');
    $stockMap = collect($stockOptions);
    $qtyText = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
    $value = fn ($field, $default = null) => old($field, $invoice->$field ?? $default);
    $dateValue = fn ($field) => old($field, $invoice->$field ? $invoice->$field->toDateString() : '');
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>{{ $invoice->exists ? 'Edit ' . $invoice->number : 'New Purchase Invoice' }}</strong> — supplier, dates, items and the invoice image.</div>
        <a class="pi-btn" href="{{ $invoice->exists ? route('crm.purchase_invoices.show', $invoice->id) : route('crm.purchase_invoices.index') }}"><i class="fas fa-arrow-left"></i>&nbsp; Back</a>
    </div>

    <form method="POST" action="{{ $invoice->exists ? route('crm.purchase_invoices.update', $invoice->id) : route('crm.purchase_invoices.store') }}" enctype="multipart/form-data" id="piForm">
        {{ csrf_field() }}
        @if($invoice->exists) {{ method_field('PUT') }} @endif

        @if($errors->any())
            <div class="ex-errors" style="margin-bottom:1rem"><strong>Please fix the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="pi-card">
            <div class="pi-card-head"><i class="fas fa-file-invoice" style="color:var(--brand)"></i> Invoice details</div>
            <div class="pi-card-body pi-grid">
                <div class="ex-f"><label>Supplier (Vendor) <span class="req">*</span></label>
                    <select name="vendor_id" required>
                        <option value="">Select vendor...</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ (string) old('vendor_id', $invoice->vendor_id) === (string) $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                    <div class="ex-current-proof">Vendor missing? Add it on the <a href="{{ route('crm.vendor_purchases.index') }}" target="_blank">Vendors</a> tab, then reload.</div>
                </div>
                <div class="ex-f"><label>Issue Date <span class="req">*</span></label><input type="date" name="issue_date" value="{{ $dateValue('issue_date') }}" required></div>
                <div class="ex-f"><label>Due Date</label><input type="date" name="due_date" value="{{ $dateValue('due_date') }}"></div>
                <div class="ex-f"><label>Reference <span class="hint">(supplier's invoice #)</span></label><input name="reference" value="{{ $value('reference') }}" maxlength="120"></div>
                <div class="ex-f"><label>Project <span class="hint">(optional)</span></label><input name="project" value="{{ $value('project') }}" maxlength="120" placeholder="e.g. job / site name"></div>
                <div class="ex-f"><label>Currency <span class="req">*</span></label>
                    <select name="currency" id="piCurrency" onchange="piRecalc()">@foreach(['AED', 'USD', 'GBP', 'EUR'] as $cur)<option {{ $value('currency', 'AED') === $cur ? 'selected' : '' }}>{{ $cur }}</option>@endforeach</select>
                </div>
                <div class="ex-f full"><label>Description</label><textarea name="description" rows="2" maxlength="2000" placeholder="What this invoice is for">{{ $value('description') }}</textarea></div>
            </div>
        </div>

        <div class="pi-card">
            <div class="pi-card-head"><i class="fas fa-list" style="color:var(--brand)"></i> Items
                <span class="gl-mutedtext" style="margin-left:auto;font-weight:600;font-size:.72rem">Category = what was bought. Each category shows as its own line in Profit &amp; Loss.</span>
            </div>
            <div class="pi-card-body">
                <datalist id="piCategoryList">@foreach($categoryOptions as $c)<option value="{{ $c }}">@endforeach</datalist>
                <div class="gl-tablewrap">
                    <table class="pi-items">
                        <thead><tr><th style="width:36px">#</th><th style="min-width:220px">Item Name</th><th style="width:200px;min-width:170px">Category</th><th style="width:120px" class="r">Quantity</th><th style="width:140px" class="r">Unit Price</th><th style="width:140px" class="r">Total</th><th style="width:44px"></th></tr></thead>
                        <tbody id="piRows">
                        @foreach($items as $i => $item)
                            @php
                                $rowAsset = !empty($item['fixed_asset_id']) ? $assetMap->get((int) $item['fixed_asset_id']) : null;
                                $rowStock = !$rowAsset && !empty($item['inventory_item_id']) ? $stockMap->get((int) $item['inventory_item_id']) : null;
                            @endphp
                            <tr @if($rowAsset) class="pi-asset-row" data-asset="{{ $rowAsset['id'] }}" @elseif($rowStock) class="pi-asset-row pi-stock-row" data-stock="{{ $rowStock['id'] }}" @endif>
                                <td class="pi-rownum">{{ $i + 1 }}</td>
                                @if($rowAsset)
                                    <td><input type="hidden" name="items[{{ $i }}][fixed_asset_id]" value="{{ $rowAsset['id'] }}">
                                        <div class="pi-asset-name"><span class="pi-asset-chip"><i class="fas fa-boxes"></i> {{ $rowAsset['number'] }}</span><input name="items[{{ $i }}][name]" value="{{ $rowAsset['name'] }}" readonly></div></td>
                                    <td><input name="items[{{ $i }}][category]" value="{{ \App\CrmPurchaseInvoiceItem::ASSET_CATEGORY }}" readonly title="Fixed assets go to the balance sheet, not Profit & Loss"></td>
                                @elseif($rowStock)
                                    <td><input type="hidden" name="items[{{ $i }}][inventory_item_id]" value="{{ $rowStock['id'] }}">
                                        <div class="pi-asset-name"><span class="pi-asset-chip pi-stock-chip"><i class="fas fa-cubes"></i> Stock</span><input name="items[{{ $i }}][name]" value="{{ $rowStock['name'] }}" readonly></div>
                                        <div class="pi-stock-note">Qty in {{ $rowStock['unit'] }} · on hand {{ $qtyText($rowStock['quantity']) }}</div></td>
                                    <td><input name="items[{{ $i }}][category]" value="{{ \App\CrmPurchaseInvoiceItem::STOCK_CATEGORY }}" readonly title="Stock goes to Inventory (balance sheet); it becomes Cost of Sales when used on a job"></td>
                                @else
                                    <td><input name="items[{{ $i }}][name]" value="{{ $item['name'] ?? '' }}" placeholder="Item / material name" required maxlength="255"></td>
                                    <td><input name="items[{{ $i }}][category]" value="{{ $item['category'] ?? '' }}" list="piCategoryList" placeholder="e.g. Raw Material" required maxlength="80"></td>
                                @endif
                                <td><input class="r" type="number" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? '' }}" step="any" min="0.001" required oninput="piRecalc()"></td>
                                <td><input class="r" type="number" name="items[{{ $i }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" step="0.01" min="0" required oninput="piRecalc()"></td>
                                <td class="r pi-line-total">0.00</td>
                                <td><button type="button" class="ex-icon danger" title="Remove item" onclick="piRemoveRow(this)"><i class="fas fa-times"></i></button></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pi-add-bar">
                    <button type="button" class="pi-add-row" onclick="piAddRow()"><i class="fas fa-plus"></i> Add Item</button>
                    @if(count($assetOptions))
                        <div class="pi-asset-pick"><i class="fas fa-boxes"></i>
                            <select id="piAssetPick" onchange="piAddAsset(this.value); this.value='';">
                                <option value="">Add Fixed Asset (from Fixed Assets tab)…</option>
                                @foreach($assetOptions as $a)
                                    <option value="{{ $a['id'] }}">{{ $a['number'] }} — {{ $a['name'] }} · {{ $a['rate'] }}%/yr{{ $a['unit_price'] !== null ? ' · ' . rtrim(rtrim(number_format($a['quantity'], 3, '.', ''), '0'), '.') . ' × ' . number_format($a['unit_price'], 2) . ' ' . $a['currency'] : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <span class="gl-mutedtext" style="font-size:.74rem">Buying a fixed asset? Add it on <a href="{{ route('crm.fixed_assets.index') }}" target="_blank">Fixed Assets</a> first (name + depreciation rate), then reload.</span>
                    @endif
                    @if($stockMap->isNotEmpty())
                        <div class="pi-asset-pick pi-stock-pick"><i class="fas fa-cubes"></i>
                            <select id="piStockPick" onchange="piAddStock(this.value); this.value='';">
                                <option value="">Add Stock Item (Inventory)…</option>
                                @foreach($stockMap as $s)
                                    <option value="{{ $s['id'] }}">{{ $s['name'] }} · {{ $qtyText($s['quantity']) }} {{ $s['unit'] }} on hand{{ $s['unit_cost'] !== null ? ' · avg ' . number_format($s['unit_cost'], 2) : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="pi-stock-help"><i class="fas fa-info-circle"></i>
                    <span><b>Stock items</b> (paper, board, ink…) go to <b>Inventory</b> on the Balance Sheet, not to expenses; stock goes up by the quantity on this bill.
                    When it is used on a job it becomes <b>Cost of Sales</b>. For bills dated {{ $stockFrom->format('d M Y') }} or later.
                    New material? Add it on <a href="{{ route('crm.inventory.index') }}" target="_blank">Inventory</a> first (opening qty 0), then reload.</span>
                </div>
                <div class="pi-total-row"><span>Invoice Total</span><span id="piGrandTotal">0.00</span></div>
            </div>
        </div>

        <div class="pi-card">
            <div class="pi-card-head"><i class="fas fa-image" style="color:var(--brand)"></i> Invoice image</div>
            <div class="pi-card-body">
                <div class="ex-f"><label>Upload invoice photo / PDF <span class="hint">(optional, max 10 MB)</span></label>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" style="max-width:420px">
                    @if($invoice->attachment_name)<div class="ex-current-proof">Current: <a href="{{ route('crm.purchase_invoices.attachment', $invoice->id) }}" target="_blank">{{ $invoice->attachment_name }}</a> (choose a file to replace it)</div>@endif
                </div>
            </div>
        </div>

        <div class="pi-actions">
            <a class="ex-cancel" style="text-decoration:none" href="{{ $invoice->exists ? route('crm.purchase_invoices.show', $invoice->id) : route('crm.purchase_invoices.index') }}">Cancel</a>
            <button type="submit" class="ex-add"><i class="fas fa-check"></i> {{ $invoice->exists ? 'Save Changes' : 'Create Invoice' }}</button>
        </div>
    </form>
</div>

<script>
    var piRowIndex = {{ count($items) }};

    function piMoney(n) { return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function piRecalc() {
        var total = 0;
        document.querySelectorAll('#piRows tr').forEach(function (row, i) {
            var qty = parseFloat(row.querySelector('input[name$="[quantity]"]').value) || 0;
            var price = parseFloat(row.querySelector('input[name$="[unit_price]"]').value) || 0;
            var line = Math.round(qty * price * 100) / 100;
            row.querySelector('.pi-line-total').textContent = piMoney(line);
            row.querySelector('.pi-rownum').textContent = i + 1;
            total += line;
        });
        document.getElementById('piGrandTotal').textContent = piMoney(total) + ' ' + document.getElementById('piCurrency').value;
    }

    function piAddRow() {
        var i = piRowIndex++;
        // New rows start with the previous row's category: invoices usually buy one kind of thing (asset / stock lines don't count).
        var plainRows = document.querySelectorAll('#piRows tr:not(.pi-asset-row)');
        var lastCategory = plainRows.length ? plainRows[plainRows.length - 1].querySelector('input[name$="[category]"]') : null;
        var tr = document.createElement('tr');
        tr.innerHTML = '<td class="pi-rownum"></td>'
            + '<td><input name="items[' + i + '][name]" placeholder="Item / material name" required maxlength="255"></td>'
            + '<td><input name="items[' + i + '][category]" list="piCategoryList" placeholder="e.g. Raw Material" required maxlength="80"></td>'
            + '<td><input class="r" type="number" name="items[' + i + '][quantity]" value="1" step="any" min="0.001" required oninput="piRecalc()"></td>'
            + '<td><input class="r" type="number" name="items[' + i + '][unit_price]" step="0.01" min="0" required oninput="piRecalc()"></td>'
            + '<td class="r pi-line-total">0.00</td>'
            + '<td><button type="button" class="ex-icon danger" title="Remove item" onclick="piRemoveRow(this)"><i class="fas fa-times"></i></button></td>';
        document.getElementById('piRows').appendChild(tr);
        if (lastCategory) tr.querySelector('input[name$="[category]"]').value = lastCategory.value;
        piRecalc();
        tr.querySelector('input').focus();
    }

    function piRemoveRow(btn) {
        var rows = document.querySelectorAll('#piRows tr');
        if (rows.length <= 1) { alert('An invoice needs at least one item.'); return; }
        btn.closest('tr').remove();
        piRecalc();
        piSyncAssetPicker();
    }

    // Fixed assets from the Fixed Assets tab. Their line is locked to the asset; quantity and price can be
    // corrected to match the bill (the asset takes them over when the invoice is saved).
    var piAssets = @json($assetMap);

    function piEsc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    function piAddAsset(id) {
        var a = piAssets[id];
        if (!a || document.querySelector('#piRows tr[data-asset="' + id + '"]')) return;
        // Drop untouched blank lines so an asset-only invoice needs no clean-up.
        document.querySelectorAll('#piRows tr:not(.pi-asset-row)').forEach(function (row) {
            var name = row.querySelector('input[name$="[name]"]').value.trim();
            var price = row.querySelector('input[name$="[unit_price]"]').value.trim();
            if (!name && !price && document.querySelectorAll('#piRows tr').length > 0) row.remove();
        });
        var i = piRowIndex++;
        var tr = document.createElement('tr');
        tr.className = 'pi-asset-row';
        tr.dataset.asset = a.id;
        tr.innerHTML = '<td class="pi-rownum"></td>'
            + '<td><input type="hidden" name="items[' + i + '][fixed_asset_id]" value="' + a.id + '">'
            + '<div class="pi-asset-name"><span class="pi-asset-chip"><i class="fas fa-boxes"></i> ' + piEsc(a.number) + '</span><input name="items[' + i + '][name]" value="' + piEsc(a.name) + '" readonly></div></td>'
            + '<td><input name="items[' + i + '][category]" value="{{ \App\CrmPurchaseInvoiceItem::ASSET_CATEGORY }}" readonly title="Fixed assets go to the balance sheet, not Profit & Loss"></td>'
            + '<td><input class="r" type="number" name="items[' + i + '][quantity]" value="' + (a.quantity || 1) + '" step="any" min="0.001" required oninput="piRecalc()"></td>'
            + '<td><input class="r" type="number" name="items[' + i + '][unit_price]" value="' + (a.unit_price == null ? '' : a.unit_price) + '" step="0.01" min="0.01" placeholder="Price" required oninput="piRecalc()"></td>'
            + '<td class="r pi-line-total">0.00</td>'
            + '<td><button type="button" class="ex-icon danger" title="Remove asset from this invoice" onclick="piRemoveRow(this)"><i class="fas fa-times"></i></button></td>';
        document.getElementById('piRows').appendChild(tr);
        piRecalc();
        piSyncAssetPicker();
    }

    // Stock items from the Inventory page: the line is locked to the item; quantity (in the item's unit) and price come from the bill.
    var piStock = @json($stockMap);

    function piAddStock(id) {
        var s = piStock[id];
        if (!s) return;
        var existing = document.querySelector('#piRows tr[data-stock="' + id + '"] input[name$="[quantity]"]');
        if (existing) { existing.focus(); return; }   // one line per stock item: change its quantity instead
        document.querySelectorAll('#piRows tr:not(.pi-asset-row)').forEach(function (row) {
            var name = row.querySelector('input[name$="[name]"]').value.trim();
            var price = row.querySelector('input[name$="[unit_price]"]').value.trim();
            if (!name && !price) row.remove();
        });
        var i = piRowIndex++;
        var tr = document.createElement('tr');
        tr.className = 'pi-asset-row pi-stock-row';
        tr.dataset.stock = s.id;
        var onHand = (Math.round(s.quantity * 1000) / 1000).toString();
        tr.innerHTML = '<td class="pi-rownum"></td>'
            + '<td><input type="hidden" name="items[' + i + '][inventory_item_id]" value="' + s.id + '">'
            + '<div class="pi-asset-name"><span class="pi-asset-chip pi-stock-chip"><i class="fas fa-cubes"></i> Stock</span><input name="items[' + i + '][name]" value="' + piEsc(s.name) + '" readonly></div>'
            + '<div class="pi-stock-note">Qty in ' + piEsc(s.unit) + ' · on hand ' + onHand + '</div></td>'
            + '<td><input name="items[' + i + '][category]" value="{{ \App\CrmPurchaseInvoiceItem::STOCK_CATEGORY }}" readonly title="Stock goes to Inventory (balance sheet); it becomes Cost of Sales when used on a job"></td>'
            + '<td><input class="r" type="number" name="items[' + i + '][quantity]" step="any" min="0.001" placeholder="' + piEsc(s.unit) + '" required oninput="piRecalc()"></td>'
            + '<td><input class="r" type="number" name="items[' + i + '][unit_price]" value="' + (s.unit_cost == null ? '' : s.unit_cost) + '" step="0.01" min="0" placeholder="Price / ' + piEsc(s.unit) + '" required oninput="piRecalc()"></td>'
            + '<td class="r pi-line-total">0.00</td>'
            + '<td><button type="button" class="ex-icon danger" title="Remove stock line" onclick="piRemoveRow(this)"><i class="fas fa-times"></i></button></td>';
        document.getElementById('piRows').appendChild(tr);
        piRecalc();
        tr.querySelector('input[name$="[quantity]"]').focus();
    }

    // Assets already on the invoice can't be picked twice.
    function piSyncAssetPicker() {
        var pick = document.getElementById('piAssetPick');
        if (!pick) return;
        Array.prototype.forEach.call(pick.options, function (o) {
            if (o.value) o.disabled = !!document.querySelector('#piRows tr[data-asset="' + o.value + '"]');
        });
    }

    // Opened from Fixed Assets ("Create purchase invoice"): add that asset and pick its supplier.
    (function () {
        var id = @json((int) request('asset'));
        if (!id || !piAssets[id] || document.querySelector('#piRows tr[data-asset="' + id + '"]')) return;
        piAddAsset(String(id));
        var price = document.querySelector('#piRows tr[data-asset="' + id + '"] input[name$="[unit_price]"]');
        if (price && !price.value) price.focus();
        var vendor = document.querySelector('select[name="vendor_id"]');
        var supplier = (piAssets[id].supplier || '').trim().toLowerCase();
        if (vendor && !vendor.value && supplier) {
            Array.prototype.forEach.call(vendor.options, function (o) { if (o.text.trim().toLowerCase() === supplier) vendor.value = o.value; });
        }
    })();

    // Opened from an inventory item ("Buy more"): add that stock line.
    (function () {
        var id = @json((int) request('stock'));
        if (id && piStock[id]) piAddStock(String(id));
    })();

    piRecalc();
    piSyncAssetPicker();
</script>
@endsection
