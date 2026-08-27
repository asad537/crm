@php
    $materialCalculators = [
        [
            'key' => 'greyBoard',
            'cost_name' => 'Grey Board',
            'title' => 'Grey Board Cost Calculator',
            'icon' => 'fa-layer-group',
            'unit_label' => 'sheets',
            'specifications' => [
                '1 mm Grey Board', '1.5 mm Grey Board', '2 mm Grey Board',
                '2.5 mm Grey Board', '3 mm Grey Board', '1000 GSM Grey Board',
                '1200 GSM Grey Board', '1500 GSM Grey Board', '1800 GSM Grey Board',
            ],
        ],
        [
            'key' => 'evaFoam',
            'cost_name' => 'EVA Foam',
            'title' => 'EVA Foam Cost Calculator',
            'icon' => 'fa-th-large',
            'unit_label' => 'pieces',
            'specifications' => [
                '1 cm EVA Foam', '2 cm EVA Foam', '3 cm EVA Foam',
                '5 cm EVA Foam', '10 cm EVA Foam', 'Black EVA Foam',
                'White EVA Foam', 'Custom-cut EVA Foam Insert',
            ],
        ],
        [
            'key' => 'eFlute',
            'cost_name' => 'E Flute',
            'title' => 'E Flute Cost Calculator',
            'icon' => 'fa-box',
            'unit_label' => 'sheets',
            'specifications' => [
                'Brown E Flute', 'Black E Flute', 'White E Flute',
            ],
        ],
    ];
@endphp

<style>
.pc-card{overflow:hidden;border:1px solid #e6ebf5;border-radius:18px;box-shadow:0 1px 3px rgba(15,23,42,.04),0 18px 40px -30px rgba(15,23,42,.2)}.pc-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.15rem;padding-bottom:.85rem;border-bottom:1px solid #eef1f7}.pc-heading h3{margin:0 0 .3rem;display:flex;align-items:center;gap:.65rem;font-size:1rem;font-weight:800;color:#0f172a;letter-spacing:-.01em}.pc-heading h3 i{width:36px;height:36px;display:inline-grid;place-items:center;border-radius:11px;background:var(--primary-soft);color:var(--primary-purple);font-size:.95rem;box-shadow:0 4px 12px var(--primary-shadow)}.pc-kicker{color:#8a98aa;font-size:.75rem;line-height:1.45;font-weight:500}.pc-badge{display:inline-flex;align-items:center;gap:.4rem;padding:.42rem .75rem;border-radius:999px;background:linear-gradient(135deg,#e6f7ec,#f0fdf4);color:#0d7a4a;font-size:.66rem;font-weight:800;white-space:nowrap;border:1px solid #bbf7d0}.pc-badge i{color:#0d7a4a}.pc-message{display:none;align-items:center;gap:.45rem;margin-top:.75rem;padding:.62rem .75rem;border-radius:9px;font-size:.68rem;font-weight:750}.pc-message.show{display:flex}.pc-message.error{border:1px solid #fecaca;background:#fff1f2;color:#b91c1c}.pc-message.success{border:1px solid #bbf7d0;background:#f0fdf4;color:#15803d}
.pc-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;padding:1.15rem;border:1px solid #e6ebf5;border-radius:14px;background:linear-gradient(180deg,#fbfcfe 0%,#f7f9fc 100%)}.pc-grid.pc-grid--flute{grid-template-columns:repeat(3,minmax(0,1fr))}.pc-grid.pc-grid--flute-inline{grid-template-columns:1fr 1fr 1.2fr;align-items:end}.pc-inline-cost{display:flex;flex-direction:column;justify-content:flex-end}.pc-inline-cost-box{padding:.7rem .95rem;border:1.5px solid var(--primary-shadow);border-radius:10px;background:linear-gradient(135deg,var(--primary-soft),#fff);min-height:44px;display:flex;align-items:center}.pc-field{position:relative}.pc-field label,.pc-result span{display:block;margin-bottom:.35rem;color:#6b7a94;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.pc-field .es-input{width:100%;padding:.7rem .85rem;border:1.5px solid #dbe1ec;border-radius:10px;background:#fff;font-size:.95rem;font-weight:650;color:#0f172a;font-variant-numeric:tabular-nums;transition:all .13s;outline:none}.pc-field .es-input:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow);background:#fff}.pc-field .es-input.material-input.autofilled{background:linear-gradient(135deg,var(--primary-soft),#fff);border-color:var(--primary-shadow)}.pc-field small{display:block;margin-top:.35rem;color:#9aa6b6;font-size:.63rem;font-weight:500}.pc-results{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem;margin-top:1rem}.pc-result{min-height:82px;padding:.9rem .95rem;border:1px solid #e6ebf5;border-radius:12px;background:#fff;transition:all .15s}.pc-result:hover{border-color:#cfd7e6;box-shadow:0 3px 8px rgba(15,23,42,.04)}.pc-result strong{color:#0f172a;font-size:.95rem;font-weight:800;font-variant-numeric:tabular-nums}.pc-result.final{position:relative;overflow:hidden;background:linear-gradient(135deg,var(--primary-soft),#fff);border-color:var(--primary-shadow);grid-column:span 1}.pc-result.final:after{content:"";position:absolute;right:-22px;bottom:-27px;width:80px;height:80px;border-radius:50%;background:var(--primary-shadow);opacity:.4}.pc-result.final strong{position:relative;z-index:1;color:var(--primary-purple);font-size:1.15rem;font-weight:900}.pc-actions{display:flex;gap:.5rem;justify-content:flex-end;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid #edf1f5}.pc-actions .es-primary{min-width:145px}.pc-stale{display:none;margin-top:.75rem;padding:.6rem .75rem;border:1px solid var(--primary-shadow);border-radius:999px;background:var(--primary-soft);color:var(--primary-purple);font-size:.68rem;font-weight:750}.pc-card.stale .pc-results{opacity:.42;filter:grayscale(.3)}.pc-card.stale .pc-stale{display:block}
@media(max-width:1100px){.pc-grid{grid-template-columns:repeat(3,1fr)}.pc-results{grid-template-columns:repeat(3,1fr)}}@media(max-width:760px){.pc-grid{grid-template-columns:repeat(2,1fr)}.pc-heading{flex-direction:column}.pc-badge{align-self:flex-start}}@media(max-width:520px){.pc-grid,.pc-results{grid-template-columns:1fr}.pc-actions .es-btn{width:100%}}
</style>

@foreach($materialCalculators as $calculator)
@php
    $materialCalculatorState = is_array($calculatorState[$calculator['key']] ?? null)
        ? $calculatorState[$calculator['key']]
        : [];
@endphp
<div
    class="es-card pc-card material-calculator"
    id="{{ $calculator['key'] }}Calculator"
    data-key="{{ $calculator['key'] }}"
    data-cost-name="{{ $calculator['cost_name'] }}"
    data-unit-label="{{ $calculator['unit_label'] }}"
>
    <div class="pc-heading">
        <div>
            <h3><i class="fas {{ $calculator['icon'] }}"></i> {{ $calculator['title'] }}</h3>
            <div class="pc-kicker">{{ $calculator['key'] === 'eFlute' ? 'Enter sheet dimensions and cutting sheets to calculate the cost automatically.' : 'Select a common specification or enter your own, then calculate the landed material cost.' }}</div>
        </div>
        <span class="pc-badge"><i class="fas fa-shield-alt"></i> Live calculation</span>
    </div>

    <div class="pc-grid {{ $calculator['key'] === 'eFlute' ? 'pc-grid--flute-inline' : '' }}">
        @if($calculator['key'] === 'eFlute')
        {{-- Sheet L & W come from Paper Cost automatically. Hidden but still submitted so the price calc has them. --}}
        <input type="hidden" class="material-input material-length" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][length]" value="{{ $materialCalculatorState['length'] ?? '' }}">
        <input type="hidden" class="material-input material-width" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][width]" value="{{ $materialCalculatorState['width'] ?? '' }}">
        <div class="pc-field">
            <label>E Flute</label>
            <select class="es-input material-input material-flute-toggle" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][enabled]">
                <option value="no" {{ (($materialCalculatorState['enabled'] ?? '') === 'yes') ? '' : 'selected' }}>None</option>
                <option value="yes" {{ (($materialCalculatorState['enabled'] ?? '') === 'yes') ? 'selected' : '' }}>Yes</option>
            </select>
        </div>
        <div class="pc-field">
            <label>Sheets</label>
            <input class="es-input material-input material-quantity" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][quantity]" value="{{ $materialCalculatorState['quantity'] ?? '' }}" type="number" min="0" step="1" placeholder="0" readonly>
        </div>
        <div class="pc-field pc-inline-cost">
            <label>E Flute Cost</label>
            <div class="pc-inline-cost-box"><strong class="material-final" style="color:var(--primary-purple);font-size:1.05rem;font-weight:900;">—</strong></div>
        </div>
        @else
        <div class="pc-field">
            <label for="{{ $calculator['key'] }}Specification">Specification / Detail</label>
            <input
                class="es-input material-input material-specification"
                id="{{ $calculator['key'] }}Specification"
                type="text"
                list="{{ $calculator['key'] }}Specifications"
                autocomplete="off"
                form="professionalEstimateForm"
                name="calculator_state[{{ $calculator['key'] }}][specification]"
                value="{{ $materialCalculatorState['specification'] ?? '' }}"
            >
            <datalist id="{{ $calculator['key'] }}Specifications">
                @foreach($calculator['specifications'] as $specification)
                    <option value="{{ $specification }}"></option>
                @endforeach
            </datalist>
            <small>Select from the list or type manually</small>
        </div>
        @if($calculator['key'] === 'greyBoard')
        <div class="pc-field">
            <label for="{{ $calculator['key'] }}Gsm">GSM</label>
            <input
                class="es-input material-input material-gsm"
                id="{{ $calculator['key'] }}Gsm"
                type="text"
                list="{{ $calculator['key'] }}GsmOptions"
                autocomplete="off"
                form="professionalEstimateForm"
                name="calculator_state[{{ $calculator['key'] }}][gsm]"
                value="{{ $materialCalculatorState['gsm'] ?? '' }}"
                placeholder="e.g. 1200"
            >
            <datalist id="{{ $calculator['key'] }}GsmOptions">
                <option value="1000"></option>
                <option value="1200"></option>
                <option value="1500"></option>
                <option value="1800"></option>
                <option value="2000"></option>
            </datalist>
            <small>Board weight (g/m²)</small>
        </div>
        @endif
        <div class="pc-field">
            <label>Material Length</label>
            <input class="es-input material-input material-length" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][length]" value="{{ $materialCalculatorState['length'] ?? '' }}" type="number" min="0" step=".01">
            <small>Centimetres (cm)</small>
        </div>
        <div class="pc-field">
            <label>Material Width</label>
            <input class="es-input material-input material-width" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][width]" value="{{ $materialCalculatorState['width'] ?? '' }}" type="number" min="0" step=".01">
            <small>Centimetres (cm)</small>
        </div>
        <div class="pc-field">
            <label>Required Quantity</label>
            <input class="es-input material-input material-quantity" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][quantity]" value="{{ $materialCalculatorState['quantity'] ?? '' }}" type="number" min="1" step="1">
            <small>Number of {{ $calculator['unit_label'] }}</small>
        </div>
        <div class="pc-field">
            <label>Purchase Price</label>
            <input class="es-input material-input material-price" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][price]" value="{{ $materialCalculatorState['price'] ?? '' }}" type="number" min="0" step=".0001">
            <small>Supplier price before VAT — <span class="material-curr-hint">{{ $ticket->currency ?: 'USD' }}</span></small>
        </div>
        <div class="pc-field">
            <label>Priced By</label>
            <select class="es-input material-input material-priced-by" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][priced_by]">
                <option value="unit" {{ ($materialCalculatorState['priced_by'] ?? 'unit') === 'unit' ? 'selected' : '' }}>Per {{ $calculator['unit_label'] === 'pieces' ? 'Piece' : 'Sheet' }}</option>
                <option value="pack" {{ ($materialCalculatorState['priced_by'] ?? '') === 'pack' ? 'selected' : '' }}>Per Pack</option>
            </select>
        </div>
        <div class="pc-field material-pack-field" hidden>
            <label>Units Per Pack</label>
            <input class="es-input material-input material-pack-size" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][pack_size]" value="{{ $materialCalculatorState['pack_size'] ?? '' }}" type="number" min="1" step="1">
            <small>{{ ucfirst($calculator['unit_label']) }} in one pack</small>
        </div>
        <div class="pc-field">
            <label>Wastage %</label>
            <input class="es-input material-input material-waste" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][waste]" value="{{ $materialCalculatorState['waste'] ?? '' }}" type="number" min="0" max="100" step=".01">
        </div>
        <div class="pc-field">
            <label>VAT %</label>
            <input class="es-input material-input material-vat" form="professionalEstimateForm" name="calculator_state[{{ $calculator['key'] }}][vat]" value="{{ $materialCalculatorState['vat'] ?? '' }}" type="number" min="0" max="100" step=".01">
        </div>
        @endif
    </div>

    @if($calculator['key'] === 'eFlute')
        {{-- Cost is shown inline with the input row above; no separate result strip needed. --}}
    @else
    <div class="pc-results">
        <div class="pc-result"><span>Price / Unit</span><strong class="material-unit-price">—</strong></div>
        <div class="pc-result"><span>Subtotal</span><strong class="material-subtotal">—</strong></div>
        <div class="pc-result"><span>VAT</span><strong class="material-vat-amount">—</strong></div>
        <div class="pc-result final"><span>Final Price</span><strong class="material-final">—</strong></div>
    </div>
    @endif
</div>
@endforeach

<script>
(function(){
function numeric(card, selector){var field=card.querySelector(selector);return Number(field&&field.value||0)}
function money(value, currency){return currency+' '+value.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}
// Live auto-calculation: recompute the card on every input change — no Calculate button.
function recalcCard(card){
    // Currency always follows the Job Plan selector at the top — no per-card currency.
    var cs=document.getElementById('acCurrencySelect');
    var csOpt=cs?cs.options[cs.selectedIndex]:null;
    var currency=cs?cs.value:'USD';
    var sym=csOpt?(csOpt.dataset.symbol||currency):currency;
    var hint=card.querySelector('.material-curr-hint');
    if(hint)hint.textContent=currency;
    if(card.dataset.key==='eFlute'){
        var toggle=card.querySelector('.material-flute-toggle');
        var enabled=toggle?toggle.value==='yes':true;
        var fluteLength=numeric(card,'.material-length');
        var fluteWidth=numeric(card,'.material-width');
        var fluteSheets=numeric(card,'.material-quantity');
        var fluteReady=enabled&&fluteLength>0&&fluteWidth>0&&fluteSheets>0;
        // Rate 0.05 is PKR per cm² of sheet; convert to the estimate's selected currency.
        var pkrToCurr=(window.latestAdvancedEst&&window.latestAdvancedEst.pkrToCurr)?window.latestAdvancedEst.pkrToCurr:1;
        var unitPriceCurr=fluteLength*fluteWidth*0.05*pkrToCurr;
        var fluteTotal=fluteReady?(unitPriceCurr*fluteSheets):0;
        card.materialResult=fluteReady?{
            specification:'E Flute',gsm:'',length:fluteLength,width:fluteWidth,
            quantity:fluteSheets,unitLabel:'cutting sheets',currency:currency,
            unitPrice:unitPriceCurr,subtotal:fluteTotal,vat:0,total:fluteTotal
        }:null;
        card.querySelector('.material-final').textContent=fluteReady?money(fluteTotal,sym):'—';
        if(typeof calculateAdvancedEst==='function')calculateAdvancedEst();
        return;
    }
    var pricedBy=card.querySelector('.material-priced-by');
    var specification=card.querySelector('.material-specification').value.trim();
    var gsmField=card.querySelector('.material-gsm');
    var gsm=gsmField?gsmField.value.trim():'';
    var length=numeric(card,'.material-length'),width=numeric(card,'.material-width');
    var quantity=numeric(card,'.material-quantity'),priceField=card.querySelector('.material-price');
    var purchasePrice=numeric(card,'.material-price');
    var packSize=pricedBy.value==='pack'?numeric(card,'.material-pack-size'):1;
    var ready=specification&&length&&width&&quantity&&priceField.value!==''&&purchasePrice>=0&&!(pricedBy.value==='pack'&&!packSize);
    if(!ready){
        card.materialResult=null;
        card.querySelector('.material-unit-price').textContent='—';
        card.querySelector('.material-subtotal').textContent='—';
        card.querySelector('.material-vat-amount').textContent='—';
        card.querySelector('.material-final').textContent='—';
        if(typeof calculateAdvancedEst==='function')calculateAdvancedEst();
        return;
    }
    var unitPrice=purchasePrice/packSize;
    var subtotal=unitPrice*quantity*(1+numeric(card,'.material-waste')/100);
    var vat=subtotal*numeric(card,'.material-vat')/100;
    var total=subtotal+vat;
    card.materialResult={specification:specification,gsm:gsm,length:length,width:width,quantity:quantity,unitLabel:card.dataset.unitLabel,currency:currency,unitPrice:unitPrice,subtotal:subtotal,vat:vat,total:total};
    card.querySelector('.material-unit-price').textContent=money(unitPrice,sym);
    card.querySelector('.material-subtotal').textContent=money(subtotal,sym);
    card.querySelector('.material-vat-amount').textContent=money(vat,sym);
    card.querySelector('.material-final').textContent=money(total,sym);
    // Feed the material price straight into the Job Plan grand total.
    if(typeof calculateAdvancedEst==='function')calculateAdvancedEst();
}
document.querySelectorAll('.material-calculator').forEach(function(card){
    var pricedBy=card.querySelector('.material-priced-by');
    var packField=card.querySelector('.material-pack-field');
    function togglePack(){if(packField&&pricedBy)packField.hidden=pricedBy.value!=='pack'}
    if(pricedBy){
        togglePack();
        pricedBy.addEventListener('change',togglePack);
    }
    card.querySelectorAll('.material-input').forEach(function(input){
        input.addEventListener('input',function(){recalcCard(card)});
        input.addEventListener('change',function(){recalcCard(card)});
    });
    recalcCard(card); // saved calculator_state values price up immediately on page load
});
// Lets the Job Plan currency selector refresh every material card when it changes.
window.recalcMaterialCards=function(){document.querySelectorAll('.material-calculator').forEach(recalcCard)};

// ---- E-Flute: auto-fill from Paper Cost card (uses window.latestAdvancedEst) ----
//  - Cutting Sheets  ← Paper Cost's computed cutSheets (or parentSheets when 1/1)
//  - Sheet L / W     ← Cut Size L/W when the user entered them,
//                       else the selected Paper Size (parent) converted to cm.
// Any field manually edited stops auto-syncing (independent per field).
(function(){
    var flute = document.getElementById('eFluteCalculator');
    if (!flute) return;
    var lenInput = flute.querySelector('.material-length');
    var widInput = flute.querySelector('.material-width');
    var qtyInput = flute.querySelector('.material-quantity');
    if (!lenInput || !widInput || !qtyInput) return;

    function paperSizeToCm(sizeStr, unit) {
        if (!sizeStr) return null;
        var parts = String(sizeStr).split(/[*xX×]/);
        if (parts.length < 2) return null;
        var a = parseFloat(parts[0]); var b = parseFloat(parts[1]);
        if (!(a > 0) || !(b > 0)) return null;
        var u = (unit || 'in').toLowerCase();
        var toCm = u === 'in' ? 2.54 : (u === 'mm' ? 0.1 : 1);
        return {
            length: Math.round(Math.max(a, b) * toCm * 100) / 100,
            width:  Math.round(Math.min(a, b) * toCm * 100) / 100,
        };
    }

    function setIfDifferent(input, value){
        var s = String(value);
        if (input.dataset.userTouched === '1') return false;
        if (input.value === s) return false;
        input.value = s;
        input.classList.add('autofilled');
        return true;
    }

    function pullFromAdvancedEst(){
        // Only autofill when the user has enabled E-Flute for this estimate.
        var toggle = flute.querySelector('.material-flute-toggle');
        if (toggle && toggle.value !== 'yes') return;

        var data = window.latestAdvancedEst;
        var changed = false;

        // --- Sheet L / W ---
        var cutL = data && data.cutL > 0 ? data.cutL : 0;
        var cutW = data && data.cutW > 0 ? data.cutW : 0;
        var dims = null;
        if (cutL > 0 && cutW > 0) {
            dims = { length: Math.max(cutL, cutW), width: Math.min(cutL, cutW) };
        } else {
            // Fall back to selected Paper Size (parent) in cm.
            var sizeSel = document.getElementById('acPaperSize');
            if (sizeSel && sizeSel.value) {
                var opt = sizeSel.options[sizeSel.selectedIndex];
                var unit = opt ? (opt.getAttribute('data-unit') || 'in') : 'in';
                dims = paperSizeToCm(sizeSel.value, unit);
            }
        }
        if (dims) {
            if (setIfDifferent(lenInput, dims.length)) changed = true;
            if (setIfDifferent(widInput, dims.width))  changed = true;
        }

        // --- Cutting Sheets (prefer computed cutSheets; fall back to parentSheets) ---
        var count = 0;
        if (data && data.cutSheets > 0) count = Math.ceil(data.cutSheets);
        else if (data && data.parentSheets > 0) count = Math.ceil(data.parentSheets);
        else {
            var cutEl = document.getElementById('pcCutSheets');
            if (cutEl) count = parseInt((cutEl.textContent || '').replace(/[^0-9]/g, ''), 10) || 0;
        }
        if (count > 0 && setIfDifferent(qtyInput, count)) changed = true;

        if (changed) qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // Lock fields the user edits — remove the autofilled badge too.
    [lenInput, widInput, qtyInput].forEach(function(el){
        el.addEventListener('input', function(){ el.dataset.userTouched = '1'; el.classList.remove('autofilled'); });
    });

    // Toggle: switching to "Yes" fills right away; "None" clears values so cost drops to —.
    var fluteToggle = flute.querySelector('.material-flute-toggle');
    if (fluteToggle) {
        fluteToggle.addEventListener('change', function(){
            if (fluteToggle.value === 'yes') {
                // Reset "user touched" flags so autofill can populate afresh.
                [lenInput, widInput, qtyInput].forEach(function(el){ delete el.dataset.userTouched; });
                pullFromAdvancedEst();
            } else {
                // Clear values so the price falls back to 0.
                [lenInput, widInput, qtyInput].forEach(function(el){ el.value = ''; el.classList.remove('autofilled'); });
                qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    }

    // Refresh whenever anything in Paper Cost changes.
    ['acPaperType','acPaperSize','acGsm','acQuantity','acWasteSheets','acCuttingPlan','acCutL','acCutW'].forEach(function(id){
        var el = document.getElementById(id); if (!el) return;
        var handler = function(){ setTimeout(pullFromAdvancedEst, 0); };
        el.addEventListener('change', handler);
        if (el.tagName === 'INPUT') el.addEventListener('input', handler);
    });

    // Wrap calculateAdvancedEst so we always sync right after it runs.
    var origCalc = window.calculateAdvancedEst;
    if (typeof origCalc === 'function') {
        window.calculateAdvancedEst = function(){
            var r = origCalc.apply(this, arguments);
            setTimeout(pullFromAdvancedEst, 0);
            return r;
        };
    }

    // First sync on load — but nudge the advanced calculator first so latestAdvancedEst is fresh.
    setTimeout(function(){
        if (typeof window.calculateAdvancedEst === 'function') window.calculateAdvancedEst();
        pullFromAdvancedEst();
    }, 100);
})();
})();
</script>
