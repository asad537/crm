@php
    $u = Auth::guard('crm')->user();
    $isOwner = $u && ($u->isAdmin() || $u->isSuperAdmin() || $u->isEstimator() || $u->isSalesManager());
    $currency = $ticket->currency ?: 'USD';
    $currencyRatesMap = [
        'USD' => 1.0,
        'PKR' => 278.50,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'AED' => 3.67,
        'SAR' => 3.75,
        'CAD' => 1.37,
        'AUD' => 1.52,
        'INR' => 83.50,
    ];
    $defaultExchangeRate = $currencyRatesMap[$currency] ?? 1.0;
    $rateMatrices = $rateMatrices ?? collect();
    $paperGsmRates = $rateMatrices->where('type', 'paper_gsm')->values();
    $colorRates = $rateMatrices->where('type', 'printing_color')->values();
    $ctpPlateRates = $rateMatrices->where('type', 'ctp_plate')->values();
    $laminationRates = $rateMatrices->where('type', 'lamination')->values();
    $paperTypeRates = $rateMatrices->where('type', 'paper_type_rate')->values();
    $finishingRates = $rateMatrices->whereIn('type', ['uv_rate', 'foiling_rate', 'die_cutting', 'cardboard_pasting', 'corrugated_pasting', 'box_pasting'])->values();
@endphp

<style>
.ac-card {
    position: relative;
    overflow: hidden;
    margin-bottom: 1.5rem;
    padding: 1.6rem;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
}
.ac-card:before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-purple), var(--primary-purple));
}
.ac-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.ac-card-title h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.65rem;
}
.ac-title-icon {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border-radius: 10px;
    font-size: 1rem;
    flex-shrink: 0;
}
.ac-title-icon.orange {
    background: var(--primary-soft);
    color: var(--primary-purple);
    border: 1px solid var(--primary-soft);
    box-shadow: 0 2px 8px var(--primary-shadow);
}
.ac-title-icon.blue {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #dbeafe;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
}
.ac-card-title p {
    margin: 0.25rem 0 0;
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 500;
}

/* Toolbar Controls */
.ac-toolbar {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
}
.ac-curr-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    font-size: 0.78rem;
    font-weight: 700;
    color: #334155;
}
.ac-curr-select-clean {
    border: none;
    background: transparent;
    font-size: 0.8rem;
    font-weight: 800;
    color: #0f172a;
    outline: none;
    cursor: pointer;
}
.ac-curr-rate-clean {
    width: 70px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.25rem 0.4rem;
    font-size: 0.78rem;
    font-weight: 800;
    color: #0f172a;
    background: #ffffff;
    text-align: center;
    outline: none;
}
.ac-curr-rate-clean:focus {
    border-color: var(--primary-purple);
}

.ac-btn-setting {
    height: 38px;
    padding: 0 0.9rem;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #ffffff;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
}
.ac-btn-setting:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #94a3b8;
}

/* Form Layout Grid */
.ac-form-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
}
/* Job-plan input row: fixed 4 columns so 8 fields land as a clean 4 + 4 */
.ac-form-grid-4 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}
@media (max-width: 960px) {
    .ac-form-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 560px) {
    .ac-form-grid-4 { grid-template-columns: 1fr; }
}

/* Parent cost cards — each cost category as a self-contained card (inputs + result cards) */
.ac-pcard {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #ffffff;
    margin-bottom: 1.25rem;
    overflow: hidden;
    box-shadow: 0 3px 14px rgba(15, 23, 42, 0.04);
}
.ac-pcard-head {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.95rem 1.15rem;
    font-size: 0.98rem;
    font-weight: 800;
    color: #0f172a;
    border-bottom: 1px solid #eef2f7;
}
.ac-pcard-body {
    padding: 1.15rem;
    background: #f8fafc;
}
.ac-pcard-results {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 0.75rem;
    padding: 1.1rem 1.15rem;
    border-top: 1px dashed #e2e8f0;
}
@media (max-width: 560px) {
    .ac-pcard-results { grid-template-columns: 1fr; }
}
.ac-rcard {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
    padding: 0.85rem 0.95rem;
    min-height: 74px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.2rem;
}
.ac-rcard.hero {
    background: linear-gradient(135deg, #ffffff 0%, var(--primary-soft) 100%);
    border: 1.5px solid var(--primary-shadow);
    box-shadow: 0 4px 14px var(--primary-shadow);
}
.ac-rcard-label { font-size: 0.65rem; font-weight: 850; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; }
.ac-rcard-val { font-size: 1.15rem; font-weight: 850; color: #0f172a; }
.ac-rcard.hero .ac-rcard-val { color: var(--primary-purple); }
.ac-rcard-sub { font-size: 0.73rem; color: #64748b; font-weight: 700; }
.ac-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.38rem;
}
.ac-field-group label {
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
}
.ac-select-field, .ac-input-field {
    height: 42px;
    padding: 0 0.85rem;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #ffffff;
    font-size: 0.85rem;
    font-weight: 700;
    color: #0f172a;
    outline: none;
    transition: all 0.2s ease;
    width: 100%;
}
.ac-select-field:focus, .ac-input-field:focus {
    border-color: var(--primary-purple);
    box-shadow: 0 0 0 3px var(--primary-shadow);
}
/* Auto-filled, non-editable field (rate comes from the rate matrix) */
.ac-input-locked {
    background: #f1f5f9 !important;
    color: #475569 !important;
    cursor: not-allowed !important;
    pointer-events: none;
    border-style: dashed;
}
.ac-input-locked:focus {
    border-color: #cbd5e1 !important;
    box-shadow: none !important;
}

.ac-section-divider {
    grid-column: 1 / -1;
    margin: 0.35rem 0 0.2rem;
    padding-top: 0.85rem;
    border-top: 1px dashed #e2e8f0;
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--primary-purple);
    letter-spacing: 0.04em;
    display: flex;
    align-items: center;
    gap: 0.45rem;
}

/* Executive Summary Box */
.ac-exec-box {
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 1.25rem;
}
.ac-exec-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}
/* Spec Cards Row */
.ac-spec-cards-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
    width: 100%;
}
@media (max-width: 1300px) {
    .ac-spec-cards-row {
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }
}
.ac-spec-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0.85rem;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    transition: all 0.2s ease;
    min-height: 72px;
    box-sizing: border-box;
}
.ac-spec-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}

.ac-spec-card.blue { border-left: 3.5px solid #2563eb; }
.ac-spec-card.orange { border-left: 3.5px solid var(--primary-purple); }
.ac-spec-card.purple { border-left: 3.5px solid var(--primary-purple); }
.ac-spec-card.green { border-left: 3.5px solid #0f766e; }

.ac-sc-icon {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border-radius: 10px;
    font-size: 0.95rem;
    flex-shrink: 0;
}
.ac-spec-card.blue .ac-sc-icon { background: #eff6ff; color: #2563eb; }
.ac-spec-card.orange .ac-sc-icon { background: var(--primary-soft); color: var(--primary-purple); }
.ac-spec-card.purple .ac-sc-icon { background: var(--primary-soft); color: var(--primary-purple); }
.ac-spec-card.green .ac-sc-icon { background: #f0fdfa; color: #0f766e; }

.ac-sc-content {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.ac-sc-label {
    font-size: 0.64rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
}
.ac-sc-value {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-wrap: wrap;
}
.ac-sc-value strong {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
.ac-sc-badge {
    padding: 0.15rem 0.45rem;
    border-radius: 50px;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.68rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    display: inline-block;
}

.ac-exec-fin-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
}
.ac-fin-item {
    padding: 1rem 1.15rem;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.ac-fin-item.featured {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, var(--primary-soft) 0%, #ffffff 100%);
    border: 1.5px solid var(--primary-hover);
    box-shadow: 0 4px 16px var(--primary-shadow);
}
.ac-fin-label {
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.ac-fin-val {
    font-size: 1.4rem;
    font-weight: 900;
    color: #0f172a;
}
.ac-fin-val.gt {
    color: var(--primary-purple);
    font-size: 1.6rem;
}
.ac-fin-sub {
    font-size: 0.72rem;
    font-weight: 600;
    color: #94a3b8;
    margin-top: 0.25rem;
}

.ac-btn-apply-hero {
    height: 40px;
    padding: 0 1.25rem;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary-hover), var(--primary-purple));
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 4px 14px var(--primary-shadow);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}
.ac-btn-apply-hero:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px var(--primary-shadow);
    background: linear-gradient(135deg, var(--primary-purple), var(--primary-hover));
}

@media(max-width: 1100px) {
    .ac-form-grid, .ac-exec-fin-grid {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}
@media(max-width: 750px) {
    .ac-form-grid, .ac-exec-fin-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media(max-width: 550px) {
    .ac-form-grid, .ac-exec-fin-grid {
        grid-template-columns: 1fr !important;
    }
    .ac-exec-head {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* Tooling Charges — clean 2-column rows: inputs left, that row's total right */
.ac-tooling-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
    gap: 0.9rem 1rem;
    align-items: center;
}
.ac-tooling-grid .ac-field-group { align-self: end; }
.ac-tooling-total { grid-column: 1 / -1; }
@media(max-width: 700px) {
    .ac-tooling-grid { grid-template-columns: 1fr; }
}
</style>

<div class="ac-card" id="advancedEstimatorCard">
    <!-- Header -->
    <div class="ac-card-header">
        <div class="ac-card-title">
            <h3><span class="ac-title-icon orange"><i class="fas fa-clipboard-list"></i></span> Job Plan Calculator</h3>
            <p>Calculate sheet cuts, paper wastage %, paper rates, CMYK & Spot Color CTP plates with 1,000-impression slab scaling.</p>
        </div>

        <div class="ac-toolbar">
            <button type="button" class="ac-btn-setting" onclick="openAcRatesModal()" style="border-color:var(--primary-soft);color:var(--primary-purple);background:var(--primary-soft);">
                <i class="fas fa-eye"></i> View Rates
            </button>
            <!-- Currency Widget Pill -->
            <div class="ac-curr-pill">
                <i class="fas fa-coins" style="color:var(--primary-purple);"></i>
                <span>Currency:</span>
                <select id="acCurrencySelect" class="ac-curr-select-clean" onchange="onCurrencyChange()">
                    <option value="USD" data-symbol="$" data-rate="1.0" {{ $currency == 'USD' ? 'selected' : '' }}>USD ($)</option>
                    <option value="PKR" data-symbol="Rs" data-rate="278.50" {{ $currency == 'PKR' ? 'selected' : '' }}>PKR (Rs)</option>
                    <option value="EUR" data-symbol="€" data-rate="0.92" {{ $currency == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                    <option value="GBP" data-symbol="£" data-rate="0.79" {{ $currency == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                    <option value="AED" data-symbol="AED" data-rate="3.67" {{ $currency == 'AED' ? 'selected' : '' }}>AED</option>
                    <option value="SAR" data-symbol="SAR" data-rate="3.75">SAR</option>
                    <option value="CAD" data-symbol="CA$" data-rate="1.37">CAD (CA$)</option>
                    <option value="AUD" data-symbol="AU$" data-rate="1.52">AUD (AU$)</option>
                    <option value="INR" data-symbol="₹" data-rate="83.50">INR (₹)</option>
                </select>
                <span style="color:#94a3b8;margin-left:0.2rem;">1 USD =</span>
                <input type="number" step="0.0001" id="acExchangeRate" value="{{ $defaultExchangeRate }}" class="ac-curr-rate-clean" oninput="calculateAdvancedEst()" title="Exchange rate against USD base">
            </div>
        </div>
    </div>

    <!-- Paper Cost Card -->
    <div class="ac-pcard">
        <div class="ac-pcard-head"><span class="ac-title-icon blue"><i class="fas fa-file-invoice-dollar"></i></span> Paper Cost</div>
        <div class="ac-pcard-body ac-form-grid ac-form-grid-4">
        <div class="ac-field-group">
            <label>Paper Type</label>
            <select class="ac-select-field" id="acPaperType" onchange="calculateAdvancedEst()">
                <option value="">Select Paper</option>
                @foreach($paperTypeRates as $pt)
                    <option value="{{ $pt->name }}" data-rate="{{ $pt->rate }}" data-divisor="{{ $pt->weight_divisor }}" data-sheets="{{ $pt->weight_sheets }}">{{ $pt->name }}</option>
                @endforeach
            </select>
        </div>

@php
if (!function_exists('acFormatDualPaperSize')) {
    function acFormatDualPaperSize($sizeStr, $unitStr) {
        $u = strtolower(trim($unitStr ?: 'in'));
        $parts = preg_split('/\s*[*xX×]\s*/', trim($sizeStr));
        if (count($parts) < 2) {
            return str_replace('*', ' × ', $sizeStr) . ' ' . $u;
        }
        $d1 = (float)$parts[0];
        $d2 = (float)$parts[1];
        if ($d1 <= 0 || $d2 <= 0) {
            return str_replace('*', ' × ', $sizeStr) . ' ' . $u;
        }

        $fmt = function($num, $dec = 2) {
            $formatted = number_format($num, $dec, '.', '');
            return strpos($formatted, '.') !== false ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
        };

        if ($u === 'cm') {
            $d1_in = $d1 / 2.54;
            $d2_in = $d2 / 2.54;
            return $fmt($d1) . ' × ' . $fmt($d2) . ' cm (' . $fmt($d1_in) . ' × ' . $fmt($d2_in) . ' in)';
        } elseif ($u === 'mm') {
            $d1_cm = $d1 / 10;
            $d2_cm = $d2 / 10;
            $d1_in = $d1 / 25.4;
            $d2_in = $d2 / 25.4;
            return $fmt($d1) . ' × ' . $fmt($d2) . ' mm (' . $fmt($d1_in) . ' × ' . $fmt($d2_in) . ' in / ' . $fmt($d1_cm) . ' × ' . $fmt($d2_cm) . ' cm)';
        } else {
            // Default: unit is inches ('in')
            $d1_cm = $d1 * 2.54;
            $d2_cm = $d2 * 2.54;
            return $fmt($d1) . ' × ' . $fmt($d2) . ' in (' . $fmt($d1_cm) . ' × ' . $fmt($d2_cm) . ' cm)';
        }
    }
}
@endphp

        <div class="ac-field-group">
            <label>Paper Size</label>
            <select class="ac-select-field" id="acPaperSize" onchange="onPaperSizeChange()">
                <option value="">Select Size</option>
                @if($paperGsmRates->count() > 0)
                    {{-- Show admin-configured paper sizes with auto-calculated dual unit display (in & cm). --}}
                    @foreach($paperGsmRates->unique('paper_size') as $matrix)
                        @if($matrix->paper_size)
                            <option value="{{ $matrix->paper_size }}" data-rate="{{ $matrix->rate }}" data-unit="{{ $matrix->thickness_unit ?: 'in' }}">{{ acFormatDualPaperSize($matrix->paper_size, $matrix->thickness_unit ?: 'in') }}</option>
                        @endif
                    @endforeach
                @else
                    {{-- Fallback built-in sizes with dual unit display (in & cm). --}}
                    <option value="20X30" data-rate="0.40" data-unit="in">20 × 30 in (50.8 × 76.2 cm)</option>
                    <option value="22X28" data-rate="0.42" data-unit="in">22 × 28 in (55.88 × 71.12 cm)</option>
                    <option value="23X36" data-rate="0.52" data-unit="in">23 × 36 in (58.42 × 91.44 cm)</option>
                    <option value="25X30" data-rate="0.48" data-unit="in">25 × 30 in (63.5 × 76.2 cm)</option>
                    <option value="25X36" data-rate="0.55" data-unit="in">25 × 36 in (63.5 × 91.44 cm)</option>
                    <option value="27X34" data-rate="0.60" data-unit="in">27 × 34 in (68.58 × 86.36 cm)</option>
                    <option value="28X40" data-rate="0.68" data-unit="in">28 × 40 in (71.12 × 101.6 cm)</option>
                    <option value="22*33" data-rate="0.45" data-unit="in">22 × 33 in (55.88 × 83.82 cm)</option>
                    <option value="31*43" data-rate="0.75" data-unit="in">31 × 43 in (78.74 × 109.22 cm)</option>
                @endif
            </select>
        </div>

        <div class="ac-field-group">
            <label>GSM</label>
            <select class="ac-select-field" id="acGsm" onchange="lookupPaperRate()">
                <option value="210">210 GSM</option>
                <option value="250">250 GSM</option>
                <option value="300" selected>300 GSM</option>
                <option value="350">350 GSM</option>
                <option value="400">400 GSM</option>
                @foreach($paperGsmRates->unique('gsm') as $matrix)
                    @if($matrix->gsm && !in_array($matrix->gsm, ['210','250','300','350','400']))
                        <option value="{{ $matrix->gsm }}">{{ $matrix->gsm }} GSM</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="ac-field-group">
            <label>Required Quantity</label>
            <input class="ac-input-field" type="number" id="acQuantity" min="1" value="100" oninput="calculateAdvancedEst()">
        </div>
          <div class="ac-field-group">
            <label>Wastage Sheets</label>
            <input class="ac-input-field" type="number" step="1" min="0" id="acWasteSheets" value="0" oninput="calculateAdvancedEst()" placeholder="0">
        </div>

        <div class="ac-field-group">
            <label>Cutting Plan</label>
            <select class="ac-select-field" id="acCuttingPlan" onchange="calculateAdvancedEst()">
                <option value="1" selected>1/1 (Full Sheet)</option>
                <option value="2">1/2 (Half Sheet — 2 Cut Sheets)</option>
                <!-- <option value="3">1/3 (1/3 Sheet — 3 Cut Sheets)</option> -->
                <option value="4">1/4 (Quarter Sheet — 4 Cut Sheets)</option>
                <option value="6">1/6 (6 Cut Sheets)</option>
                <option value="8">1/8 (8 Cut Sheets)</option>
                <option value="10">1/10 (10 Cut Sheets)</option>
            </select>
        </div>

        <div class="ac-field-group" id="acCutSizeGroup" style="display:none;">
            <label>Cut Size L W</label>
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <input class="ac-input-field" type="number" step="0.01" min="0" id="acCutL" oninput="calculateAdvancedEst()" placeholder="L" style="text-align:center;padding:0 0.4rem;">
                <input class="ac-input-field" type="number" step="0.01" min="0" id="acCutW" oninput="calculateAdvancedEst()" placeholder="W" style="text-align:center;padding:0 0.4rem;">
            </div>
        </div>
        </div>
        <div class="ac-pcard-results">
            <div class="ac-rcard">
                <span class="ac-rcard-label" id="pcCutSheetsLabel">Cut Sheets</span>
                <span class="ac-rcard-val" id="pcCutSheets">0</span>
                <span class="ac-rcard-sub" id="pcParentSheets">0 parent</span>
            </div>
            <div class="ac-rcard">
                <span class="ac-rcard-label">Total Paper Weight</span>
                <span class="ac-rcard-val" id="pcTotalWeight">0 kg</span>
                <span class="ac-rcard-sub" id="pcPerSheetWeight">0 g / sheet</span>
            </div>
            <div class="ac-rcard">
                <span class="ac-rcard-label">Price / Sheet</span>
                <span class="ac-rcard-val" id="pcPricePerSheet">—</span>
                <span class="ac-rcard-sub">per parent sheet</span>
            </div>
            <div class="ac-rcard hero">
                <span class="ac-rcard-label">Total Paper Cost</span>
                <span class="ac-rcard-val" id="pcTotalCost">—</span>
                <!-- <span class="ac-rcard-sub" id="pcTotalCostSub"></span> -->
            </div>
        </div>
    </div>

    {{-- Material cost calculators (Grey Board / EVA Foam / E Flute) sit right below Paper Cost --}}
    @include('crm.estimate_tickets.partials.material_calculators')

    <!-- CTP Plates Card -->
    <div class="ac-pcard">
        <div class="ac-pcard-head"><span class="ac-title-icon orange"><i class="fas fa-compact-disc"></i></span> CTP Plates</div>
        <div class="ac-pcard-body ac-form-grid" style="grid-template-columns: repeat(2, minmax(0,1fr));">
            <div class="ac-field-group">
                <label>CMYK Colors Count & Plate Select</label>
                <div style="display:flex;gap:.4rem;">
                    <input class="ac-input-field" style="width:75px;flex-shrink:0;" type="number" id="acCmykCount" min="0" max="8" value="4" oninput="calculateAdvancedEst()">
                    <select class="ac-select-field" id="acCmykPlate" onchange="calculateAdvancedEst()">
                        @forelse($ctpPlateRates as $matrix)
                            <option value="{{ $matrix->rate }}" data-rate="{{ $matrix->rate }}" data-name="{{ $matrix->name }}">{{ $matrix->name ?: 'Plate' }}</option>
                        @empty
                            <option value="200" data-rate="200" data-name="Standard CTP Plate">Standard CTP Plate</option>
                            <option value="150" data-rate="150" data-name="Small CTP Plate">Small CTP Plate</option>
                            <option value="300" data-rate="300" data-name="Large CTP Plate">Large CTP Plate</option>
                        @endforelse
                    </select>
                </div>
            </div>
            <div class="ac-field-group">
                <label>Spot Colors Count & Plate Select</label>
                <div style="display:flex;gap:.4rem;">
                    <input class="ac-input-field" style="width:75px;flex-shrink:0;" type="number" id="acSpotCount" min="0" max="8" value="0" oninput="calculateAdvancedEst()">
                    <select class="ac-select-field" id="acSpotPlate" onchange="calculateAdvancedEst()">
                        @forelse($ctpPlateRates as $matrix)
                            <option value="{{ $matrix->rate }}" data-rate="{{ $matrix->rate }}" data-name="{{ $matrix->name }}">{{ $matrix->name ?: 'Plate' }}</option>
                        @empty
                            <option value="200" data-rate="200" data-name="Standard CTP Plate">Standard CTP Plate</option>
                            <option value="150" data-rate="150" data-name="Small CTP Plate">Small CTP Plate</option>
                            <option value="300" data-rate="300" data-name="Large CTP Plate">Large CTP Plate</option>
                        @endforelse
                    </select>
                </div>
            </div>
        </div>
        <div class="ac-pcard-results">
            <div class="ac-rcard"><span class="ac-rcard-label">Total CTP Plates</span><span class="ac-rcard-val" id="resTotalPlates">4 Plates</span><span class="ac-rcard-sub" id="resPlateDetail">4 CMYK · 0 Spot</span></div>
            <div class="ac-rcard hero"><span class="ac-rcard-label">CTP Plate Cost</span><span class="ac-rcard-val"><span class="ac-curr-sym">$</span> <span id="resPlateCost">0.00</span></span><span class="ac-rcard-sub" id="resPlateCostSub">one-time</span></div>
        </div>
    </div>

    <!-- Printing Card — results only (paper rate stays hidden for the calc; VAT input lives on the Grand Total card) -->
    <div class="ac-pcard">
        <div class="ac-pcard-head"><span class="ac-title-icon orange"><i class="fas fa-palette"></i></span> Printing</div>
        <input type="hidden" id="acPaperRate" value="0.00">
        <span id="acPaperRateLabel" style="display:none;">Paper Rate Per Sheet (<span class="ac-curr-sym">$</span>)</span>
        <div class="ac-pcard-results" style="border-top:none;">
            <div class="ac-rcard"><span class="ac-rcard-label">Impression Slabs</span><span class="ac-rcard-val" id="resSlabs">1 Slab (1x)</span><span class="ac-rcard-sub">1,000-sheet rule</span></div>
            <div class="ac-rcard hero"><span class="ac-rcard-label">Printing Cost</span><span class="ac-rcard-val"><span class="ac-curr-sym">$</span> <span id="resCtpCost">0.00</span></span><span class="ac-rcard-sub" id="resCtpCostSub">—</span></div>
        </div>
    </div>

    <!-- Lamination Card -->
    <div class="ac-pcard">
        <div class="ac-pcard-head"><span class="ac-title-icon orange" style="background:var(--primary-soft);color:var(--primary-purple);border:1px solid var(--primary-soft);"><i class="fas fa-layer-group" style="font-size:1rem;color:var(--primary-purple);"></i></span> Lamination</div>
        <div class="ac-pcard-body ac-form-grid ac-form-grid-4">
            <div class="ac-field-group">
                <label>Lamination</label>
                <select class="ac-select-field" id="acLamination" onchange="calculateAdvancedEst()">
                    <option value="0" data-rate="0" data-name="None">None</option>
                    @foreach($laminationRates as $lam)
                        <option value="{{ $lam->rate }}" data-rate="{{ $lam->rate }}" data-name="{{ $lam->name }}">{{ $lam->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ac-rcard" style="min-height:74px;border:1.5px solid #e2e8f0;border-radius:16px;background:#ffffff;padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">SHEETS</span>
                <span class="ac-rcard-val" id="pcLamSheets" style="font-size:1.15rem;font-weight:850;color:#0f172a;">0</span>
                <span class="ac-rcard-sub" id="pcLamSheetsSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
            <div class="ac-rcard hero" style="grid-column: span 2;min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem 1.1rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">LAMINATION TOTAL COST</span>
                <span class="ac-rcard-val" style="font-size:1.2rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="resLamCost">0.00</span></span>
                <span class="ac-rcard-sub" id="resLamCostSub" style="font-size:.73rem;color:#64748b;font-weight:700;">None</span>
            </div>
        </div>
    </div>

    <!-- UV Coating Card (rates come from the admin Finishing Rates tab) -->
    <div class="ac-pcard">
        <div class="ac-pcard-head"><span class="ac-title-icon orange" style="background:var(--primary-soft);color:var(--primary-purple);border:1px solid var(--primary-soft);"><i class="fas fa-stamp" style="font-size:1rem;color:var(--primary-purple);"></i></span> UV Coating (Solid / Spot)</div>
        <div class="ac-pcard-body ac-form-grid ac-form-grid-4">
@php
    $uvRows = $finishingRates->where('type', 'uv_rate')->values();
    $solidUvOptions = $uvRows->filter(function ($r) {
        $cat = strtolower($r->paper_type ?: '');
        return $cat === 'solid' || ($cat === '' && stripos($r->name, 'spot') === false);
    })->values();
@endphp
            <div class="ac-field-group">
                <label>Solid UV</label>
                <select class="ac-select-field" id="acSolidUv" onchange="calculateAdvancedEst()">
                    <option value="0">None</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="ac-field-group">
                <label>Solid UV Finish</label>
                <select class="ac-select-field" id="acSolidUvFinish" onchange="calculateAdvancedEst()">
                    <option value="">Select Finish</option>
                    @foreach($solidUvOptions as $uv)
                        <option value="{{ $uv->name }}">{{ $uv->name }}</option>
                    @endforeach
                </select>
            </div>
             <div class="ac-rcard" style="min-height:74px;border:1.5px solid #e2e8f0;border-radius:16px;background:#ffffff;padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">SHEETS</span>
                <span class="ac-rcard-val" id="pcSolidUvSheets" style="font-size:1.15rem;font-weight:850;color:#0f172a;">0</span>
                <span class="ac-rcard-sub" id="pcSolidUvSheetsSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
              <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">SOLID UV COST</span>
                <span class="ac-rcard-val" style="font-size:1.15rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcSolidUvCost">0.00</span></span>
                <span class="ac-rcard-sub" id="pcSolidUvSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
            <div class="ac-field-group">
                <label>Spot UV</label>
                <select class="ac-select-field" id="acSpotUv" onchange="calculateAdvancedEst()">
                    <option value="0">None</option>
                    <option value="1">Yes</option>
                </select>
            </div>
           <div class="ac-rcard" style="min-height:74px;border:1.5px solid #e2e8f0;border-radius:16px;background:#ffffff;padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">SHEETS</span>
                <span class="ac-rcard-val" id="pcSpotUvSheets" style="font-size:1.15rem;font-weight:850;color:#0f172a;">0</span>
                <span class="ac-rcard-sub" id="pcSpotUvSheetsSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
            <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">SPOT UV COST</span>
                <span class="ac-rcard-val" style="font-size:1.15rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcSpotUvCost">0.00</span></span>
                <span class="ac-rcard-sub" id="pcSpotUvSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
        </div>
    </div>

    <!-- Die Cutting / Foiling Card -->
    <div class="ac-pcard">
        <div class="ac-pcard-head"><span class="ac-title-icon orange" style="background:var(--primary-soft);color:var(--primary-purple);border:1px solid var(--primary-soft);"><i class="fas fa-cut" style="font-size:1rem;color:var(--primary-purple);"></i></span> Die Cutting &amp; Foiling</div>
@php
    $foilingOptions = $finishingRates->where('type', 'foiling_rate')->values();
@endphp
        <div class="ac-pcard-body ac-form-grid ac-form-grid-4">
            <div class="ac-field-group">
                <label>Die Cutting</label>
                <select class="ac-select-field" id="acDieCut" onchange="calculateAdvancedEst()">
                    <option value="0">None</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="ac-rcard" style="min-height:74px;border:1.5px solid #e2e8f0;border-radius:16px;background:#ffffff;padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">SHEETS</span>
                <span class="ac-rcard-val" id="pcDieCutSheets" style="font-size:1.15rem;font-weight:850;color:#0f172a;">0</span>
                <span class="ac-rcard-sub" id="pcDieCutSheetsSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
            <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">DIE CUTTING COST</span>
                <span class="ac-rcard-val" style="font-size:1.15rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcDieCutCost">0.00</span></span>
                <span class="ac-rcard-sub" id="pcDieCutSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
            <div></div>
            {{-- Foil Block (one-time block making) — sits right above Foiling --}}
            <div class="ac-field-group">
                <label>Foil Block L W <span style="color:#94a3b8;font-weight:600;text-transform:none;">(inches)</span></label>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <input class="ac-input-field" type="number" step="0.01" min="0" id="acFoilBlockL" oninput="calculateAdvancedEst()" placeholder="L" style="text-align:center;padding:0 0.4rem;">
                    <span style="color:#94a3b8;font-weight:800;">×</span>
                    <input class="ac-input-field" type="number" step="0.01" min="0" id="acFoilBlockW" oninput="calculateAdvancedEst()" placeholder="W" style="text-align:center;padding:0 0.4rem;">
                </div>
            </div>
            <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">FOIL BLOCK TOTAL</span>
                <span class="ac-rcard-val" style="font-size:1.15rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcFoilBlockCost">0.00</span></span>
            </div>
            <div></div>
            <div></div>
            <div class="ac-field-group">
                <label>Foiling <span style="color:#94a3b8;font-weight:600;text-transform:none;">(select finish)</span></label>
                <select class="ac-select-field" id="acFoiling" onchange="calculateAdvancedEst()">
                    <option value="">None</option>
                    @foreach($foilingOptions as $foil)
                        <option value="{{ $foil->name }}">{{ $foil->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ac-field-group">
                <label>L * W (inches) </label>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <input class="ac-input-field" type="number" step="0.01" min="0" id="acFoilL" oninput="calculateAdvancedEst()" placeholder="L" style="text-align:center;padding:0 0.4rem;">
                    <input class="ac-input-field" type="number" step="0.01" min="0" id="acFoilW" oninput="calculateAdvancedEst()" placeholder="W" style="text-align:center;padding:0 0.4rem;">
                </div>
            </div>
            <div class="ac-rcard" style="min-height:74px;border:1.5px solid #e2e8f0;border-radius:16px;background:#ffffff;padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">SHEETS</span>
                <span class="ac-rcard-val" id="pcFoilingSheets" style="font-size:1.15rem;font-weight:850;color:#0f172a;">0</span>
                <span class="ac-rcard-sub" id="pcFoilingSheetsSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
            <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">FOILING COST</span>
                <span class="ac-rcard-val" style="font-size:1.15rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcFoilingCost">0.00</span></span>
                <span class="ac-rcard-sub" id="pcFoilingSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
        </div>
    </div>

    <!-- Pasting Card -->
    <div class="ac-pcard">
        <div class="ac-pcard-head"><span class="ac-title-icon orange" style="background:var(--primary-soft);color:var(--primary-purple);border:1px solid var(--primary-soft);"><i class="fas fa-layer-group" style="font-size:1rem;color:var(--primary-purple);"></i></span> Mounting</div>
        <div class="ac-pcard-body ac-form-grid ac-form-grid-4">
            <div class="ac-field-group">
                <label>Cardboard Pasting</label>
                <select class="ac-select-field" id="acCardboardPaste" onchange="calculateAdvancedEst()">
                    <option value="0">None</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="ac-rcard" style="min-height:74px;border:1.5px solid #e2e8f0;border-radius:16px;background:#ffffff;padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">SHEETS</span>
                <span class="ac-rcard-val" id="pcCardboardSheets" style="font-size:1.15rem;font-weight:850;color:#0f172a;">0</span>
                <span class="ac-rcard-sub" id="pcCardboardSheetsSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
            <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">CARDBOARD COST</span>
                <span class="ac-rcard-val" style="font-size:1.15rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcCardboardCost">0.00</span></span>
                <span class="ac-rcard-sub" id="pcCardboardSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
            <div></div>
            <div class="ac-field-group">
                <label>Corrugated Pasting</label>
                <select class="ac-select-field" id="acCorrugatedPaste" onchange="calculateAdvancedEst()">
                    <option value="0">None</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="ac-rcard" style="min-height:74px;border:1.5px solid #e2e8f0;border-radius:16px;background:#ffffff;padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">SHEETS</span>
                <span class="ac-rcard-val" id="pcCorrugatedSheets" style="font-size:1.15rem;font-weight:850;color:#0f172a;">0</span>
                <span class="ac-rcard-sub" id="pcCorrugatedSheetsSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
            <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">CORRUGATED COST</span>
                <span class="ac-rcard-val" style="font-size:1.15rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcCorrugatedCost">0.00</span></span>
                <span class="ac-rcard-sub" id="pcCorrugatedSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
        </div>
    </div>

    <!-- Box Pasting Card -->
    <div class="ac-pcard">
        <div class="ac-pcard-head"><span class="ac-title-icon orange"><i class="fas fa-box"></i></span> Box Pasting</div>
        <div class="ac-pcard-body ac-form-grid ac-form-grid-4">
            <div class="ac-field-group">
                <label>Box Pasting Type</label>
                <select class="ac-select-field" id="acBoxPaste" onchange="calculateAdvancedEst()">
                    <option value="">None</option>
                    <option value="single">Single Side</option>
                    <option value="auto">Auto Bottom / Lock</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="ac-field-group">
                <label>Quantity <span style="color:#94a3b8;font-weight:600;text-transform:none;">(boxes)</span></label>
                <input class="ac-input-field" type="number" step="1" min="0" id="acBoxPasteQty" value="" oninput="calculateAdvancedEst()" placeholder="e.g. 1000">
            </div>
            <div class="ac-field-group" id="acBoxPasteRateGroup" style="display:none;">
                <label>Box Rate <span style="color:#94a3b8;font-weight:600;text-transform:none;">/ box</span></label>
                <input class="ac-input-field" type="number" step="0.01" min="0" id="acBoxPasteRate" value="0" oninput="calculateAdvancedEst()" placeholder="0">
            </div>
            <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem 1.1rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">BOX PASTING COST</span>
                <span class="ac-rcard-val" style="font-size:1.2rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcBoxCost">0.00</span></span>
                <span class="ac-rcard-sub" id="pcBoxSub" style="font-size:.73rem;color:#64748b;font-weight:700;">—</span>
            </div>
        </div>
    </div>

    <!-- Tooling Charges Card -->
    <div class="ac-pcard">
        <div class="ac-pcard-head"><span class="ac-title-icon orange"><i class="fas fa-toolbox"></i></span> Tooling Charges <span style="font-weight:600;color:#94a3b8;font-size:.8rem;text-transform:none;">&nbsp;(one-time)</span></div>
        <div class="ac-pcard-body ac-tooling-grid">
            {{-- Row 1: Die Making — manual amount + its own total --}}
            <div class="ac-field-group"><label>Die Making</label><input class="ac-input-field" type="number" step="0.01" min="0" id="acDieMaking" value="0" oninput="calculateAdvancedEst()" placeholder="0"></div>
            <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">DIE MAKING TOTAL</span>
                <span class="ac-rcard-val" style="font-size:1.15rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcDieMakingCost">0.00</span></span>
            </div>
            {{-- Row 2: Positive --}}
            <div class="ac-field-group">
                <label>Positive L W <span style="color:#94a3b8;font-weight:600;text-transform:none;">(inches)</span></label>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <input class="ac-input-field" type="number" step="0.01" min="0" id="acPositiveL" oninput="calculateAdvancedEst()" placeholder="L" style="text-align:center;padding:0 0.4rem;">
                    <span style="color:#94a3b8;font-weight:800;">×</span>
                    <input class="ac-input-field" type="number" step="0.01" min="0" id="acPositiveW" oninput="calculateAdvancedEst()" placeholder="W" style="text-align:center;padding:0 0.4rem;">
                </div>
            </div>
            <div class="ac-rcard hero" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">POSITIVE TOTAL</span>
                <span class="ac-rcard-val" style="font-size:1.15rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcPositiveCost">0.00</span></span>
            </div>
            {{-- Combined tooling total --}}
            <div class="ac-rcard hero ac-tooling-total" style="min-height:74px;border:1.5px solid var(--primary-shadow);border-radius:16px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 100%);padding:.75rem .95rem;display:flex;flex-direction:column;justify-content:center;gap:.2rem;box-shadow:0 4px 14px var(--primary-shadow);">
                <span class="ac-rcard-label" style="font-size:.65rem;font-weight:850;letter-spacing:.05em;color:#475569;">TOOLING TOTAL COST</span>
                <span class="ac-rcard-val" style="font-size:1.35rem;font-weight:850;color:var(--primary-purple);"><span class="ac-curr-sym">$</span> <span id="pcToolingTotalCost">0.00</span></span>
                <span class="ac-rcard-sub" style="font-size:.73rem;color:#64748b;font-weight:700;">one-time charges</span>
            </div>
        </div>
    </div>

    <!-- Grand Total Card -->
    <div class="ac-pcard" id="acGrandTotalCard">
        <div class="ac-pcard-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;background:linear-gradient(135deg,var(--primary-soft),#ffffff);">
            <div>
                <div class="ac-rcard-label" style="color:var(--primary-purple);"><i class="fas fa-coins"></i> GRAND TOTAL [<span id="resGtCurrencyBadge">USD</span>]</div>
                <div style="font-size:1.9rem;font-weight:800;color:var(--primary-purple);margin:.15rem 0;"><span class="ac-curr-sym">$</span> <span id="resGrandTotal">0.00</span></div>
                <div class="ac-rcard-sub">Sub: <span class="ac-curr-sym">$</span> <span id="resSubtotal">0.00</span> + VAT: <span class="ac-curr-sym">$</span> <span id="resVatAmount">0.00</span> (<span id="resVatBadgePercent">5</span>%)</div>
                <div class="ac-rcard-sub" id="resMaterialsLine" style="display:none;">Incl. Materials (Grey Board / EVA / E Flute): <span class="ac-curr-sym">$</span> <span id="resMaterialsCost">0.00</span></div>
            </div>
            <div class="ac-field-group" style="min-width:120px;">
                <label>VAT (%)</label>
                <input class="ac-input-field" type="number" step="0.1" min="0" max="100" id="acVatPercent" value="{{ old('vat_percentage', (float)(optional($ticket->lead)->vat_percentage ?: 5)) }}" oninput="calculateAdvancedEst()">
            </div>
            @if($canEditEstimate)
            <button class="ac-btn-apply-hero" type="button" onclick="applyAdvancedEstToCosting()">
                <i class="fas fa-check-circle"></i> Apply to Costing Table
            </button>
            @endif
        </div>
    </div>
</div>

<!-- Rates Viewer Modal (read-only reference for the estimator) -->
<div id="acRatesModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);align-items:flex-start;justify-content:center;padding:3vh 1rem;">
    <div style="background:#fff;border-radius:18px;max-width:960px;width:100%;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(15,23,42,.25);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #eef2f7;">
            <h3 style="margin:0;font-size:1.05rem;font-weight:800;color:#0f172a;"><i class="fas fa-eye" style="color:var(--primary-purple);"></i> Rate Reference <span style="color:#94a3b8;font-weight:600;font-size:.8rem;">(<span id="rvCurrLabel">PKR</span> — read only)</span></h3>
            <button type="button" onclick="closeAcRatesModal()" style="border:none;background:#f1f5f9;color:#475569;width:34px;height:34px;border-radius:10px;cursor:pointer;font-size:1rem;"><i class="fas fa-times"></i></button>
        </div>
        <div style="overflow-y:auto;padding:1.1rem 1.25rem;display:flex;flex-direction:column;gap:1rem;">
            @php
                $rvTable = 'width:100%;border-collapse:collapse;font-size:.8rem;';
                $rvTh = 'text-align:left;padding:.45rem .6rem;background:#f8fafc;color:#64748b;font-size:.66rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #eef2f7;';
                $rvTd = 'padding:.45rem .6rem;border-bottom:1px solid #f1f5f9;color:#334155;font-weight:600;';
                $rvBox = 'border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;flex-shrink:0;background:#fff;';
                $rvHead = 'padding:.6rem .75rem;font-weight:800;font-size:.82rem;color:var(--primary-purple);background:var(--primary-soft);border-bottom:1px solid var(--primary-soft);';
                $rvNum = function ($v) { $s = number_format((float)$v, 2, '.', ','); return rtrim(rtrim($s, '0'), '.'); };
            @endphp

            <div style="{{ $rvBox }}">
                <div style="{{ $rvHead }}"><i class="fas fa-scroll"></i> Paper Type Rates (per kg)</div>
                <table style="{{ $rvTable }}">
                    <tr><th style="{{ $rvTh }}">Paper Type</th><th style="{{ $rvTh }}">Rate / kg</th></tr>
                    @foreach($paperTypeRates as $pt)
                        <tr><td style="{{ $rvTd }}">{{ $pt->name }}</td><td style="{{ $rvTd }}"><span class="rv-rate" data-pkr="{{ (float)$pt->rate }}">Rs {{ $rvNum($pt->rate) }}</span></td></tr>
                    @endforeach
                </table>
            </div>

            <div style="{{ $rvBox }}">
                <div style="{{ $rvHead }}"><i class="fas fa-compact-disc"></i> CTP Plate Rates (one-time)</div>
                <table style="{{ $rvTable }}">
                    <tr><th style="{{ $rvTh }}">Plate</th><th style="{{ $rvTh }}">Rate / plate</th></tr>
                    @foreach($ctpPlateRates as $pl)
                        <tr><td style="{{ $rvTd }}">{{ $pl->name }}</td><td style="{{ $rvTd }}"><span class="rv-rate" data-pkr="{{ (float)$pl->rate }}">Rs {{ $rvNum($pl->rate) }}</span></td></tr>
                    @endforeach
                </table>
            </div>

            <div style="{{ $rvBox }}">
                <div style="{{ $rvHead }}"><i class="fas fa-palette"></i> Printing Colors (per color / 1000)</div>
                <table style="{{ $rvTable }}">
                    <tr><th style="{{ $rvTh }}">Color</th><th style="{{ $rvTh }}">Plate</th><th style="{{ $rvTh }}">Rate</th></tr>
                    @foreach($colorRates as $cr)
                        <tr><td style="{{ $rvTd }}">{{ $cr->name }}</td><td style="{{ $rvTd }}">{{ $cr->ctp_plate_name ?: '—' }}</td><td style="{{ $rvTd }}"><span class="rv-rate" data-pkr="{{ (float)$cr->rate }}">Rs {{ $rvNum($cr->rate) }}</span></td></tr>
                    @endforeach
                </table>
            </div>

            <div style="{{ $rvBox }}">
                <div style="{{ $rvHead }}"><i class="fas fa-layer-group"></i> Lamination (per sq ft)</div>
                <table style="{{ $rvTable }}">
                    <tr><th style="{{ $rvTh }}">Type</th><th style="{{ $rvTh }}">Rate</th></tr>
                    @foreach($laminationRates as $lm)
                        <tr><td style="{{ $rvTd }}">{{ $lm->name }}</td><td style="{{ $rvTd }}"><span class="rv-rate" data-pkr="{{ (float)$lm->rate }}">Rs {{ $rvNum($lm->rate) }}</span></td></tr>
                    @endforeach
                </table>
            </div>

            <div style="{{ $rvBox }}">
                <div style="{{ $rvHead }}"><i class="fas fa-stamp"></i> UV &amp; Foiling (per sq ft, small / large)</div>
                <table style="{{ $rvTable }}">
                    <tr><th style="{{ $rvTh }}">Finish</th><th style="{{ $rvTh }}">Small</th><th style="{{ $rvTh }}">Large</th></tr>
                    @foreach($finishingRates->whereIn('type', ['uv_rate', 'foiling_rate']) as $uv)
                        <tr><td style="{{ $rvTd }}">{{ $uv->name }}</td><td style="{{ $rvTd }}"><span class="rv-rate" data-pkr="{{ (float)$uv->rate }}">Rs {{ $rvNum($uv->rate) }}</span></td><td style="{{ $rvTd }}">@if($uv->rate_large !== null)<span class="rv-rate" data-pkr="{{ (float)$uv->rate_large }}">Rs {{ $rvNum($uv->rate_large) }}</span>@else — @endif</td></tr>
                    @endforeach
                </table>
            </div>

            <div style="{{ $rvBox }}">
                <div style="{{ $rvHead }}"><i class="fas fa-cut"></i> Die Cutting, Pasting &amp; Box</div>
                <table style="{{ $rvTable }}">
                    <tr><th style="{{ $rvTh }}">Operation</th><th style="{{ $rvTh }}">Small</th><th style="{{ $rvTh }}">Large</th></tr>
                    @foreach($finishingRates->whereIn('type', ['die_cutting', 'cardboard_pasting', 'corrugated_pasting', 'box_pasting']) as $fr)
                        <tr><td style="{{ $rvTd }}">{{ $fr->name }}</td><td style="{{ $rvTd }}"><span class="rv-rate" data-pkr="{{ (float)$fr->rate }}">Rs {{ $rvNum($fr->rate) }}</span></td><td style="{{ $rvTd }}">@if($fr->rate_large !== null)<span class="rv-rate" data-pkr="{{ (float)$fr->rate_large }}">Rs {{ $rvNum($fr->rate_large) }}</span>@else — @endif</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function openAcRatesModal() {
    var m = document.getElementById('acRatesModal');
    if (!m) return;
    // Convert every PKR-stored rate into the estimate's selected currency.
    var cs = document.getElementById('acCurrencySelect');
    var opt = cs ? cs.options[cs.selectedIndex] : null;
    var sym = opt ? (opt.dataset.symbol || cs.value) : 'Rs';
    var pkrOpt = cs ? cs.querySelector('option[value="PKR"]') : null;
    var pkrPerUsd = pkrOpt ? (parseFloat(pkrOpt.dataset.rate) || 278.5) : 278.5;
    var exch = Math.max(0.0001, parseFloat((document.getElementById('acExchangeRate') || {}).value) || 1);
    var conv = exch / pkrPerUsd;
    var cells = m.querySelectorAll('.rv-rate');
    for (var i = 0; i < cells.length; i++) {
        var pkr = parseFloat(cells[i].dataset.pkr) || 0;
        var v = pkr * conv;
        var s = v.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
        cells[i].textContent = sym + ' ' + s;
    }
    var lbl = document.getElementById('rvCurrLabel');
    if (lbl) lbl.textContent = cs ? cs.value : 'PKR';
    m.style.display = 'flex';
}
function closeAcRatesModal() { var m = document.getElementById('acRatesModal'); if (m) m.style.display = 'none'; }
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAcRatesModal(); });
document.addEventListener('click', function (e) { var m = document.getElementById('acRatesModal'); if (m && e.target === m) closeAcRatesModal(); });

var acPaperRatesMap = @json($paperGsmRates);
var acColorRatesMap = @json($colorRates);

// Finishing rates come from the admin Rate Matrix (die_cutting, cardboard_pasting,
// corrugated_pasting, box_pasting). FIN holds fallback defaults if a row is missing.
var acFinishingRates = @json($finishingRates);
var FIN = {
    dieCutSmallPer1000: 600, dieCutLargePer1000: 1500,
    cardboardSmallPerSheet: 3, cardboardLargePerSheet: 8,
    corrugatedSmallPerSheet: 5, corrugatedLargePerSheet: 10,
    boxSinglePer1000: 500, boxAutoPer1000: 800,
    thresholdShortIn: 18, thresholdLongIn: 23
};
// Find an admin finishing-rate row by type (and optional name, e.g. Box Pasting Single/Auto).
function finRow(type, name) {
    if (!acFinishingRates || !acFinishingRates.length) return null;
    for (var i = 0; i < acFinishingRates.length; i++) {
        var r = acFinishingRates[i];
        if (r.type === type && (!name || (r.name || '').toLowerCase() === name.toLowerCase())) return r;
    }
    return null;
}
function finNum(v, fallback) { var n = parseFloat(v); return (isNaN(n) || n < 0) ? fallback : n; }

function onCurrencyChange() {
    var select = document.getElementById('acCurrencySelect');
    var selectedOpt = select.options[select.selectedIndex];
    var defaultRate = parseFloat(selectedOpt.dataset.rate) || 1.0;
    
    document.getElementById('acExchangeRate').value = defaultRate;
    lookupPaperRate();
    // Material cards follow the Job Plan currency — refresh their displayed prices too.
    if (typeof window.recalcMaterialCards === 'function') window.recalcMaterialCards();
}

// Format a number with up to `dec` decimals and NO trailing zeros (5.00 -> "5", 0.4500 -> "0.45"). No thousands separators (safe for number inputs).
function stripZeros(v, dec) {
    var s = (Number(v) || 0).toFixed(dec === undefined ? 2 : dec);
    return s.indexOf('.') >= 0 ? s.replace(/0+$/, '').replace(/\.$/, '') : s;
}
function lookupPaperRate() {
    var sizeSelect = document.getElementById('acPaperSize');
    var gsmSelect = document.getElementById('acGsm');
    var rateInput = document.getElementById('acPaperRate');
    
    var currSelect = document.getElementById('acCurrencySelect');
    var selectedCurrOpt = currSelect ? currSelect.options[currSelect.selectedIndex] : null;
    var exchangeRate = Math.max(0.0001, parseFloat(document.getElementById('acExchangeRate').value) || 1.0);

    var sizeVal = (sizeSelect.value || '').toUpperCase().replace('*','X');
    var gsmVal = (gsmSelect.value || '').toString();

    if (!sizeVal) {
        if (rateInput) rateInput.value = "0.00";
        calculateAdvancedEst();
        return;
    }

    var matchedRateUSD = null;
    if (acPaperRatesMap && acPaperRatesMap.length) {
        for (var i = 0; i < acPaperRatesMap.length; i++) {
            var item = acPaperRatesMap[i];
            var itemSize = (item.paper_size || '').toUpperCase().replace('*','X');
            var itemGsm = (item.gsm || '').toString();
            if (itemSize === sizeVal && itemGsm === gsmVal) {
                matchedRateUSD = parseFloat(item.rate) || 0;
                break;
            }
        }
    }

    if (matchedRateUSD !== null && matchedRateUSD !== undefined) {
        rateInput.value = stripZeros(matchedRateUSD * exchangeRate, 4);
    } else {
        var selectedOption = sizeSelect.options[sizeSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.rate) {
            var baseOptRate = parseFloat(selectedOption.dataset.rate) || 0;
            rateInput.value = stripZeros(baseOptRate * exchangeRate, 4);
        } else {
            if (rateInput) rateInput.value = "0.00";
        }
    }
    calculateAdvancedEst();
}

function onPaperSizeChange() {
    lookupPaperRate();
}

function updatePlateOptionsText(selectId, currSymbol, convFactor) {
    // Plate dropdowns show only the plate name — no price (rates still drive the calc via data-rate).
    var select = document.getElementById(selectId);
    if (!select) return;
    for (var i = 0; i < select.options.length; i++) {
        var opt = select.options[i];
        opt.text = opt.dataset.name || 'Plate';
    }
}

function calculateAdvancedEst() {
    var typeSelEl = document.getElementById('acPaperType');
    var typeVal = typeSelEl ? (typeSelEl.value || '').trim() : '';
    var hasPaperType = (typeVal !== '');

    var sizeSelEl = document.getElementById('acPaperSize');
    var sizeOpt = sizeSelEl ? sizeSelEl.options[sizeSelEl.selectedIndex] : null;
    var sizeVal = sizeSelEl ? (sizeSelEl.value || '').trim() : '';
    var hasPaperSize = (sizeVal !== '');

    var hasSpecs = (hasPaperType && hasPaperSize);

    var specMessage = '';
    if (!hasPaperType && !hasPaperSize) {
        specMessage = 'Select Paper Type & Size';
    } else if (!hasPaperType) {
        specMessage = 'Select Paper Type';
    } else {
        specMessage = 'Select Paper Size';
    }

    var qty = Math.max(1, parseFloat(document.getElementById('acQuantity').value) || 0);
    var cutRatio = Math.max(1, parseFloat(document.getElementById('acCuttingPlan').value) || 1);

    // Show Cut Size L/W input group when the paper is actually cut (cutRatio > 1).
    var cutSizeGroup = document.getElementById('acCutSizeGroup');
    var cutLInput = document.getElementById('acCutL');
    var cutWInput = document.getElementById('acCutW');
    if (cutSizeGroup) {
        var isCut = (cutRatio > 1);
        cutSizeGroup.style.display = isCut ? 'flex' : 'none';
        if (!isCut && cutLInput && cutWInput) {
            cutLInput.value = '';
            cutWInput.value = '';
        }
    }
    var paperRateCurr = hasSpecs ? Math.max(0, parseFloat(document.getElementById('acPaperRate').value) || 0) : 0;

    var cmykCount = hasSpecs ? Math.max(0, parseInt(document.getElementById('acCmykCount').value) || 0) : 0;
    var spotCount = hasSpecs ? Math.max(0, parseInt(document.getElementById('acSpotCount').value) || 0) : 0;

    var currSelect = document.getElementById('acCurrencySelect');
    var selectedCurrOpt = currSelect ? currSelect.options[currSelect.selectedIndex] : null;
    var currCode = currSelect ? currSelect.value : 'USD';
    var currSymbol = selectedCurrOpt ? selectedCurrOpt.dataset.symbol : '$';
    var exchangeRate = Math.max(0.0001, parseFloat(document.getElementById('acExchangeRate').value) || 1.0);

    // All CRM rates (paper type, plate, printing, lamination) are stored per-unit in PKR.
    // pkrToCurr converts a PKR-stored rate into the estimate's selected currency.
    var pkrOptEl = currSelect ? currSelect.querySelector('option[value="PKR"]') : null;
    var pkrPerUsd = pkrOptEl ? (parseFloat(pkrOptEl.dataset.rate) || 278.5) : 278.5;
    var pkrToCurr = exchangeRate / pkrPerUsd;

    // Update Dropdown Option Texts with converted prices dynamically
    updatePlateOptionsText('acCmykPlate', currSymbol, pkrToCurr);
    updatePlateOptionsText('acSpotPlate', currSymbol, pkrToCurr);

    var cmykPlateSelect = document.getElementById('acCmykPlate');
    var selectedCmykOpt = cmykPlateSelect ? cmykPlateSelect.options[cmykPlateSelect.selectedIndex] : null;
    var cmykPlateText = selectedCmykOpt ? (selectedCmykOpt.dataset.name || selectedCmykOpt.text) : '';
    var cmykPlateRateUSD = Math.max(0, parseFloat(selectedCmykOpt ? selectedCmykOpt.dataset.rate : 0) || 0);

    var spotPlateSelect = document.getElementById('acSpotPlate');
    var selectedSpotOpt = spotPlateSelect ? spotPlateSelect.options[spotPlateSelect.selectedIndex] : null;
    var spotPlateText = selectedSpotOpt ? (selectedSpotOpt.dataset.name || selectedSpotOpt.text) : '';
    var spotPlateRateUSD = Math.max(0, parseFloat(selectedSpotOpt ? selectedSpotOpt.dataset.rate : 0) || 0);

    // 1. Cut Sheets & Parent Sheets (wastage entered directly as a number of extra parent sheets)
    var wasteSheets = Math.max(0, parseInt(document.getElementById('acWasteSheets').value) || 0);
    var baseParentSheets = hasSpecs ? Math.ceil(qty) : 0;
    var parentSheets = hasSpecs ? (baseParentSheets + wasteSheets) : 0;
    var cutSheets = hasSpecs ? (parentSheets * cutRatio) : 0;

    // 2. Paper Cost — computed after paper weight is known
    var paperCostCurr = 0;

    // 3. Impression Slab Multiplier (Every 1,000 cut sheets = 1 slab)
    var slabs = hasSpecs ? Math.max(1, Math.ceil(cutSheets / 1000)) : 0;

    // 4. Lookup Printing Color Rates for CMYK & SPOT — per SINGLE color, per 1000 cut sheets (stored in PKR).
    var cmykRatePKR = 0;
    var spotRatePKR = 0;

    if (hasSpecs && acColorRatesMap && acColorRatesMap.length) {
        for (var i = 0; i < acColorRatesMap.length; i++) {
            var cr = acColorRatesMap[i];
            var nameUpper = (cr.name || '').toUpperCase();
            var plateUpper = (cr.ctp_plate_name || '').toUpperCase();

            if (!cmykRatePKR && nameUpper.indexOf('CMYK') !== -1) {
                if (!plateUpper || cmykPlateText.toUpperCase().indexOf(plateUpper) !== -1) {
                    cmykRatePKR = parseFloat(cr.rate) || 0;
                }
            }
            if (!spotRatePKR && nameUpper.indexOf('SPOT') !== -1) {
                if (!plateUpper || spotPlateText.toUpperCase().indexOf(plateUpper) !== -1) {
                    spotRatePKR = parseFloat(cr.rate) || 0;
                }
            }
        }
    }

    // Convert per-color printing rates (PKR) to the selected currency. Rate is already PER single color.
    var cmykPerColorRateCurr = cmykRatePKR * pkrToCurr;
    var spotPerColorRateCurr = spotRatePKR * pkrToCurr;
    var cmykTotalRateCurr = cmykCount * cmykPerColorRateCurr;
    var spotTotalRateCurr = spotCount * spotPerColorRateCurr;

    // 5a. Printing Cost — per-color printing rates × impression slabs (slab-based)
    var cmykPrintCostCurr = cmykTotalRateCurr * slabs;
    var spotPrintCostCurr = spotTotalRateCurr * slabs;
    var printingCostCurr = hasSpecs ? (cmykPrintCostCurr + spotPrintCostCurr) : 0;
    var totalPlates = hasSpecs ? (cmykCount + spotCount) : 0;

    // 5b. CTP Plate Cost — one-time: one plate per color × plate rate (PKR → selected currency; NOT × slabs)
    var cmykPlateRateCurr = cmykPlateRateUSD * pkrToCurr;
    var spotPlateRateCurr = spotPlateRateUSD * pkrToCurr;
    var plateCostCurr = hasSpecs ? ((cmykCount * cmykPlateRateCurr) + (spotCount * spotPlateRateCurr)) : 0;

    // 5c. Lamination Cost — rate per sq ft × total laminated area.
    var lamSelect = document.getElementById('acLamination');
    var lamOpt = lamSelect ? lamSelect.options[lamSelect.selectedIndex] : null;
    var lamName = lamOpt ? (lamOpt.dataset.name || 'None') : 'None';
    var lamRateCurr = Math.max(0, parseFloat(lamOpt ? lamOpt.dataset.rate : 0) || 0) * pkrToCurr; // rate/sq ft (PKR)

    var sheetUnit = (sizeOpt && sizeOpt.dataset.unit) ? sizeOpt.dataset.unit : 'in';
    var sizeParts = sizeVal.split(/\s*[*xX×]\s*/);
    // Convert dimensions to inches so ÷144 (sq inches per sq ft) stays correct for any admin unit (in/cm/mm).
    var toInch = (sheetUnit === 'cm') ? (1 / 2.54) : ((sheetUnit === 'mm') ? (1 / 25.4) : 1);
    var sheetL = (parseFloat(sizeParts[0]) || 0) * toInch;
    var sheetW = (parseFloat(sizeParts[1]) || 0) * toInch;
    var sheetSqFt = hasSpecs ? (sheetL * sheetW) / 144 : 0;

    // Optional cut size (same unit as the paper size). When given, lamination is priced on each
    // CUT sheet's area × cut-sheet count; otherwise on the full parent sheet × parent-sheet count.
    var cutL = Math.max(0, parseFloat((document.getElementById('acCutL') || {}).value) || 0) * toInch;
    var cutW = Math.max(0, parseFloat((document.getElementById('acCutW') || {}).value) || 0) * toInch;
    var hasCutSize = (cutL > 0 && cutW > 0 && cutRatio > 1); // full sheet (1/1) → no cut, use parent
    var lamSqFt = hasCutSize ? ((cutL * cutW) / 144) : sheetSqFt;
    var lamSheets = hasCutSize ? cutSheets : parentSheets;
    var laminationCostCurr = hasSpecs ? (lamSqFt * lamSheets * lamRateCurr) : 0;

    // 5c-2. Finishing & Post-Printing — priced on the cut piece when a cut size is set, else the parent sheet.
    var finPieceL = hasCutSize ? cutL : sheetL;   // inches
    var finPieceW = hasCutSize ? cutW : sheetW;
    var finPieceSqIn = finPieceL * finPieceW;
    var finPieceSqFt = finPieceSqIn / 144;
    var finPieceShort = Math.min(finPieceL, finPieceW);
    var finPieceLong = Math.max(finPieceL, finPieceW);
    var finSheets = hasCutSize ? cutSheets : parentSheets;
    function finOn(id){ var el = document.getElementById(id); return el && el.value === '1'; }
    function amt(id){ return Math.max(0, parseFloat((document.getElementById(id) || {}).value) || 0); }

    // Admin finishing-rate rows (fallback to FIN defaults). Threshold shared across tiered ops.
    var dieRow = finRow('die_cutting'), cardRow = finRow('cardboard_pasting'), corrRow = finRow('corrugated_pasting');
    var boxSingleRow = finRow('box_pasting', 'Single'), boxAutoRow = finRow('box_pasting', 'Auto Bottom');
    var thrShort = dieRow ? finNum(dieRow.threshold_short, FIN.thresholdShortIn) : FIN.thresholdShortIn;
    var thrLong  = dieRow ? finNum(dieRow.threshold_long,  FIN.thresholdLongIn)  : FIN.thresholdLongIn;
    // "small" = piece fits within the threshold (short side ≤ thrShort AND long side ≤ thrLong)
    var finIsSmall = (finPieceShort > 0 && finPieceShort <= thrShort && finPieceLong <= thrLong);

    var dieCutSmall = dieRow ? finNum(dieRow.rate, FIN.dieCutSmallPer1000) : FIN.dieCutSmallPer1000;
    var dieCutLarge = dieRow ? finNum(dieRow.rate_large, FIN.dieCutLargePer1000) : FIN.dieCutLargePer1000;
    var cardSmall = cardRow ? finNum(cardRow.rate, FIN.cardboardSmallPerSheet) : FIN.cardboardSmallPerSheet;
    var cardLarge = cardRow ? finNum(cardRow.rate_large, FIN.cardboardLargePerSheet) : FIN.cardboardLargePerSheet;
    var corrSmall = corrRow ? finNum(corrRow.rate, FIN.corrugatedSmallPerSheet) : FIN.corrugatedSmallPerSheet;
    var corrLarge = corrRow ? finNum(corrRow.rate_large, FIN.corrugatedLargePerSheet) : FIN.corrugatedLargePerSheet;
    var boxSingleRate = boxSingleRow ? finNum(boxSingleRow.rate, FIN.boxSinglePer1000) : FIN.boxSinglePer1000;
    var boxAutoRate   = boxAutoRow   ? finNum(boxAutoRow.rate,   FIN.boxAutoPer1000)   : FIN.boxAutoPer1000;

    // Solid / Spot UV — admin rate per sq ft (PKR, Finishing Rates tab): (L × W ÷ 144) × sheets × rate.
    // Solid UV rate comes from the selected FINISH option (DB row); Spot UV from the 'Spot UV' row.
    // Size-tiered like die cutting: small rate when the piece fits the row's threshold, else rate_large.
    var solidUvFinishSel = document.getElementById('acSolidUvFinish');
    var solidUvFinish = solidUvFinishSel ? solidUvFinishSel.value : '';
    var solidUvRow = solidUvFinish ? finRow('uv_rate', solidUvFinish) : finRow('uv_rate', 'Solid UV');
    var spotUvRow = finRow('uv_rate', 'Spot UV');
    function uvTierRate(row) {
        if (!row) return 0;
        var small = finNum(row.rate, 0);
        var large = finNum(row.rate_large, small); // no large rate set → use small for every size
        var ts = finNum(row.threshold_short, FIN.thresholdShortIn);
        var tl = finNum(row.threshold_long, FIN.thresholdLongIn);
        var isSmall = (finPieceShort > 0 && finPieceShort <= ts && finPieceLong <= tl);
        return isSmall ? small : large;
    }
    var solidUvRate = uvTierRate(solidUvRow) * pkrToCurr;
    var spotUvRate  = uvTierRate(spotUvRow)  * pkrToCurr;
    // Solid UV = area-based: (L × W ÷ 144) sq ft × sheets × rate.
    // Spot UV = per sheet: admin rate × sheets (no area math).
    // Both rates stay size-tiered: piece within threshold → small rate, bigger → rate_large.
    var solidUvCost = (hasSpecs && finOn('acSolidUv') && solidUvRate > 0) ? (finPieceSqFt * finSheets * solidUvRate) : 0;
    var spotUvCost  = (hasSpecs && finOn('acSpotUv')  && spotUvRate  > 0) ? (finSheets * spotUvRate)  : 0;
    // Die cutting — size-based rate per 1000 sheets (slab)
    var dieCutSlabs = Math.max(1, Math.ceil(finSheets / 1000));
    var dieCutRate = finIsSmall ? dieCutSmall : dieCutLarge;
    var dieCutCost = (hasSpecs && finOn('acDieCut')) ? (dieCutSlabs * dieCutRate * pkrToCurr) : 0;
    // Pasting — size-based per sheet
    var cardRate = finIsSmall ? cardSmall : cardLarge;
    var corrRate = finIsSmall ? corrSmall : corrLarge;
    var cardboardCost  = (hasSpecs && finOn('acCardboardPaste'))  ? (finSheets * cardRate * pkrToCurr) : 0;
    var corrugatedCost = (hasSpecs && finOn('acCorrugatedPaste')) ? (finSheets * corrRate * pkrToCurr) : 0;
    // Box pasting — per 1000 sheets (slab). Single/Auto = admin PKR rate; Other = manual rate (selected currency).
    var boxPasteSel = document.getElementById('acBoxPaste');
    var boxPasteType = boxPasteSel ? boxPasteSel.value : '';
    // The manual per-box rate only applies to "Other" — hide it for Single/Auto/None.
    var boxRateGroup = document.getElementById('acBoxPasteRateGroup');
    if (boxRateGroup) boxRateGroup.style.display = (boxPasteType === 'other') ? 'flex' : 'none';
    var boxPasteRate = Math.max(0, parseFloat((document.getElementById('acBoxPasteRate') || {}).value) || 0);
    // Box pasting quantity — REQUIRED manual box count; no quantity → no box pasting cost.
    var boxPasteQty = Math.max(0, parseFloat((document.getElementById('acBoxPasteQty') || {}).value) || 0);
    var boxUnits = boxPasteQty;
    // Single/Auto rates are per 1000-box SLAB (ceil): 1–1000 = 1 slab, 1001–2000 = 2 slabs, …
    // e.g. @800: qty 999 → 800, qty 1001 → 1600, qty 2001 → 2400.
    // "Other" rate is PER BOX: qty × rate (entered in the selected currency).
    var boxSlabCount = Math.max(1, Math.ceil(boxUnits / 1000));
    var boxPasteCost = 0;
    if (boxUnits > 0) {
        if (hasSpecs && boxPasteType === 'single')      boxPasteCost = boxSlabCount * boxSingleRate * pkrToCurr;
        else if (hasSpecs && boxPasteType === 'auto')   boxPasteCost = boxSlabCount * boxAutoRate * pkrToCurr;
        else if (hasSpecs && boxPasteType === 'other' && boxPasteRate > 0) boxPasteCost = boxUnits * boxPasteRate; // per box
    }

    // Foiling — manual foil size, fixed formula (NO backend rate): (L × W × 0.20 per sheet × sheets) + 2500 flat (PKR).
    var foilingSel = document.getElementById('acFoiling');
    var foilingFinish = (foilingSel && foilingSel.tagName === 'SELECT') ? foilingSel.value : '';
    var foilL = Math.max(0, parseFloat((document.getElementById('acFoilL') || {}).value) || 0);
    var foilW = Math.max(0, parseFloat((document.getElementById('acFoilW') || {}).value) || 0);
    var foilingCost = (hasSpecs && foilingFinish && foilL > 0 && foilW > 0)
        ? ((foilL * foilW * 0.20 * finSheets + 2500) * pkrToCurr)
        : 0;

    var finishingCostCurr = solidUvCost + spotUvCost + dieCutCost + cardboardCost + corrugatedCost + boxPasteCost + foilingCost;

    // Tooling charges (one-time): Die Making = manual amount; Positive = (L × W × 5) + 400 (PKR);
    // Foil Block = (L × W × 100) + 1000 (PKR). Fixed rates + flat charges — no backend rate.
    var dieMakingCost = amt('acDieMaking');
    var posL = amt('acPositiveL'), posW = amt('acPositiveW');
    var positiveCost = (posL > 0 && posW > 0) ? ((posL * posW * 5 + 400) * pkrToCurr) : 0;
    var fbL = amt('acFoilBlockL'), fbW = amt('acFoilBlockW');
    var foilBlockCost = (fbL > 0 && fbW > 0) ? ((fbL * fbW * 100 + 1000) * pkrToCurr) : 0;
    var toolingCostCurr = dieMakingCost + positiveCost + foilBlockCost;

    // Reset paper rate to 0 if specs are not selected
    if (!hasSpecs) {
        var rateInputEl = document.getElementById('acPaperRate');
        if (rateInputEl) rateInputEl.value = "0.00";
    }

    // 5d. Paper Weight — use the SELECTED paper type's own admin formula (divisor + sheets) when set;
    //     otherwise fall back to the standard L × W × GSM ÷ 15500 = weight of 100 sheets (kg). [dims in inches]
    var gsmVal = Math.max(0, parseFloat(document.getElementById('acGsm').value) || 0);
    var pwSel = document.getElementById('acPaperType');
    var pwOpt = pwSel ? pwSel.options[pwSel.selectedIndex] : null;
    var pwDivisor = Math.max(0, parseFloat(pwOpt ? pwOpt.dataset.divisor : 0) || 0);
    var pwSheets = Math.max(0, parseFloat(pwOpt ? pwOpt.dataset.sheets : 0) || 0);
    var perSheetWeightKg = 0;
    if (hasSpecs) {
        if (pwDivisor > 0 && pwSheets > 0) {
            perSheetWeightKg = ((sheetL * sheetW * gsmVal) / pwDivisor) / pwSheets; // selected paper type's admin formula
        } else {
            perSheetWeightKg = ((sheetL * sheetW * gsmVal) / 15500) / 100; // default: 100 sheets basis
        }
    }
    var perSheetWeightG = perSheetWeightKg * 1000;
    var totalPaperWeightKg = perSheetWeightKg * parentSheets; // total weight of the paper consumed

    // 5e. Paper Cost — weight-based when the selected paper type has a per-kg rate.
    //     Paper type rates are stored per kg in PKR; convert PKR → selected currency.
    var ptRatePkr = Math.max(0, parseFloat(pwOpt ? pwOpt.dataset.rate : 0) || 0); // per kg (PKR)
    var ptRateCurr = ptRatePkr * pkrToCurr;                                        // per kg (selected currency)
    var usingWeightPricing = (hasSpecs && ptRatePkr > 0);
    if (usingWeightPricing) {
        paperCostCurr = totalPaperWeightKg * ptRateCurr;                           // weight (kg) × rate/kg
        // Keep the variable in sync so Apply pastes the CURRENT per-sheet rate, not last render's field value.
        paperRateCurr = perSheetWeightKg * ptRateCurr;
        // Reflect the derived per-sheet paper cost in the locked "Paper Rate Per Sheet" field.
        var rateFld = document.getElementById('acPaperRate');
        if (rateFld) rateFld.value = stripZeros(paperRateCurr, 4);
    } else {
        paperCostCurr = hasSpecs ? (parentSheets * paperRateCurr) : 0;             // legacy per-sheet × sheets
    }

    // 5f. Material calculators (Grey Board / EVA Foam / E Flute) — each card's last CALCULATED
    //     final price joins the grand total; prices are entered directly in the selected estimate currency.
    var materialsCostCurr = 0;
    var materialOps = [];
    document.querySelectorAll('.material-calculator').forEach(function(card) {
        var res = card.materialResult;
        if (!res || !(res.total > 0)) return;
        var costCurr = res.total;
        materialsCostCurr += costCurr;
        // Spec text + quantity ride along so Apply can fill the costing row like the old "Use as Cost" button did.
        var matSpec = (res.specification || '') + (res.gsm ? ' · ' + res.gsm + ' GSM' : '')
            + ' · ' + res.length + ' × ' + res.width + ' cm · ' + res.quantity + ' ' + res.unitLabel;
        materialOps.push({name: card.dataset.costName, cost: costCurr, keys: [(card.dataset.costName || '').toLowerCase()], detail: matSpec, qty: res.quantity});
    });

    // 6. VAT Calculations
    var vatPercent = Math.max(0, parseFloat(document.getElementById('acVatPercent').value) || 0);
    var subtotalCostCurr = paperCostCurr + printingCostCurr + plateCostCurr + laminationCostCurr + finishingCostCurr + toolingCostCurr + materialsCostCurr;
    var vatAmountCurr = subtotalCostCurr * (vatPercent / 100);
    var grandTotalCostCurr = subtotalCostCurr + vatAmountCurr;

    // Update Currency Symbols across UI
    var symbolEls = document.querySelectorAll('.ac-curr-sym');
    symbolEls.forEach(function(el) { el.textContent = currSymbol; });

    var paperRateLabel = document.getElementById('acPaperRateLabel');
    if (paperRateLabel) {
        paperRateLabel.innerHTML = 'Paper Rate Per Sheet (<span class="ac-curr-sym">' + currSymbol + '</span>)';
    }

    // Render Formatted Converted Results
    var _rcs = document.getElementById('resCutSheets'); if (_rcs) _rcs.textContent = cutSheets.toLocaleString();

    var parentBadgeText = hasSpecs ? (parentSheets.toLocaleString() + ' Parent') : '0 Parent';
    if (hasSpecs && wasteSheets > 0) {
        parentBadgeText += ' (+' + wasteSheets.toLocaleString() + ' Waste)';
    }
    var _rps = document.getElementById('resParentSheets'); if (_rps) _rps.textContent = parentBadgeText;

    // Render inside Paper Card mini summary
    var pCut = document.getElementById('paperCardCutSheets');
    if (pCut) pCut.textContent = cutSheets.toLocaleString();

    var pPar = document.getElementById('paperCardParentSheets');
    if (pPar) pPar.textContent = parentBadgeText;

    var pCost = document.getElementById('paperCardTotalCost');
    if (pCost) pCost.innerHTML = '<span class="ac-curr-sym">' + currSymbol + '</span> ' + paperCostCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    var paperSubtext = '';
    if (!hasSpecs) {
        paperSubtext = specMessage;
    } else {
        paperSubtext = parentSheets.toLocaleString() + ' Parent Sheets';
        if (wasteSheets > 0) {
            paperSubtext += ' (incl. ' + wasteSheets.toLocaleString() + ' waste)';
        }
        // Actual per-sheet paper cost (weight-based when a paper type is set), NOT the stale locked-field value.
        var paperPerSheetCurr = parentSheets > 0 ? (paperCostCurr / parentSheets) : 0;
        paperSubtext += ' × ' + currSymbol + ' ' + stripZeros(paperPerSheetCurr, 4);
    }

    var pSub = document.getElementById('paperCardCostSub');
    if (pSub) pSub.textContent = paperSubtext;

    // Paper Cost card — result cards (new self-contained layout)
    var pcSet = function(id, val){ var e = document.getElementById(id); if (e) e.textContent = val; };
    var pcPerSheet = (hasSpecs && parentSheets > 0) ? (paperCostCurr / parentSheets) : 0;

    var pcLabelEl = document.getElementById('pcCutSheetsLabel');
    if (pcLabelEl) {
        pcLabelEl.textContent = (cutRatio > 1) ? 'TOTAL CUT SHEETS' : 'TOTAL PARENT SHEETS';
    }

    if (!hasSpecs) {
        pcSet('pcCutSheets', '0');
        pcSet('pcParentSheets', specMessage);
    } else if (cutRatio > 1) {
        pcSet('pcCutSheets', cutSheets.toLocaleString() + ' Cut');
        var subTxt = baseParentSheets.toLocaleString();
        if (wasteSheets > 0) {
            subTxt += ' + ' + wasteSheets.toLocaleString() + ' Waste = ' + parentSheets.toLocaleString() + ' Total Parent';
        } else {
            subTxt += ' Total Parent';
        }
        pcSet('pcParentSheets', subTxt);
    } else {
        pcSet('pcCutSheets', parentSheets.toLocaleString() + ' Parent');
        var subTxt = baseParentSheets.toLocaleString();
        if (wasteSheets > 0) {
            subTxt += ' + ' + wasteSheets.toLocaleString() + ' Waste = ' + parentSheets.toLocaleString() + ' Total Parent';
        } else {
            subTxt += ' Total Parent';
        }
        pcSet('pcParentSheets', subTxt);
    }

    pcSet('pcTotalWeight', (hasSpecs ? totalPaperWeightKg.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0') + ' kg');
    pcSet('pcPerSheetWeight', (hasSpecs ? perSheetWeightG.toLocaleString('en-US', {minimumFractionDigits: 1, maximumFractionDigits: 1}) : '0') + ' g / sheet');
    pcSet('pcPricePerSheet', hasSpecs ? (currSymbol + ' ' + stripZeros(pcPerSheet, 4)) : '—');
    pcSet('pcTotalCost', hasSpecs ? (currSymbol + ' ' + paperCostCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})) : '—');
    pcSet('pcTotalCostSub', '');

    document.getElementById('resSlabs').textContent = hasSpecs ? (slabs + ' Slab' + (slabs > 1 ? 's (' + slabs + 'x)' : ' (1x)')) : '0 Slabs';

    var _rpc = document.getElementById('resPaperCost'); if (_rpc) _rpc.textContent = paperCostCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    var _rpcs = document.getElementById('resPaperCostSub'); if (_rpcs) _rpcs.textContent = paperSubtext;

    document.getElementById('resTotalPlates').textContent = hasSpecs ? (totalPlates + ' Plate' + (totalPlates !== 1 ? 's' : '')) : '0 Plates';
    document.getElementById('resPlateDetail').textContent = hasSpecs ? ('CMYK: ' + cmykCount + ' · Spot: ' + spotCount) : '0 CMYK · 0 Spot';

    var pwEl = document.getElementById('resPaperWeight');
    if (pwEl) pwEl.textContent = totalPaperWeightKg.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg';
    var pwSheetEl = document.getElementById('resPerSheetWeight');
    if (pwSheetEl) pwSheetEl.textContent = perSheetWeightG.toLocaleString('en-US', {minimumFractionDigits: 1, maximumFractionDigits: 1}) + ' g / sheet';

    var totalColors = (hasSpecs ? cmykCount : 0) + (hasSpecs ? spotCount : 0);
    document.getElementById('resCtpCost').textContent = printingCostCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('resCtpCostSub').textContent = '';

    var plateCostEl = document.getElementById('resPlateCost');
    if (plateCostEl) plateCostEl.textContent = plateCostCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    var plateCostSubEl = document.getElementById('resPlateCostSub');
    if (plateCostSubEl) plateCostSubEl.textContent = '';

    var lamCostEl = document.getElementById('resLamCost');
    if (lamCostEl) lamCostEl.textContent = laminationCostCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    var lamCostSubEl = document.getElementById('resLamCostSub');
    if (lamCostSubEl) lamCostSubEl.textContent = '';

    // Finishing & Tooling — populate each section card's own result cards.
    // Formula sub-texts intentionally left blank (client asked for clean totals only).
    var setFin = function(id, cost){ var e = document.getElementById(id); if (e) e.textContent = cost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };
    var setFinSub = function(id, txt){ var e = document.getElementById(id); if (e) e.textContent = txt; };
    // Lamination card — sheets + total
    var pcLamSheetsEl = document.getElementById('pcLamSheets');
    if (pcLamSheetsEl) pcLamSheetsEl.textContent = (hasSpecs && lamRateCurr > 0) ? lamSheets.toLocaleString() : '0';
    setFinSub('pcLamSheetsSub', '');
    setFin('pcSolidUvCost', solidUvCost);      setFinSub('pcSolidUvSub', '');
    setFin('pcSpotUvCost', spotUvCost);        setFinSub('pcSpotUvSub', '');
    // SHEETS tiles across finishing cards
    var uvSheetsTxt = hasSpecs ? finSheets.toLocaleString() : '0';
    var e1 = document.getElementById('pcSolidUvSheets'); if (e1) e1.textContent = uvSheetsTxt;
    setFinSub('pcSolidUvSheetsSub', '');
    var e2 = document.getElementById('pcSpotUvSheets'); if (e2) e2.textContent = uvSheetsTxt;
    setFinSub('pcSpotUvSheetsSub', '');
    setFin('pcDieCutCost', dieCutCost);        setFinSub('pcDieCutSub', '');
    setFin('pcCardboardCost', cardboardCost);  setFinSub('pcCardboardSub', '');
    setFin('pcCorrugatedCost', corrugatedCost);setFinSub('pcCorrugatedSub', '');
    setFin('pcBoxCost', boxPasteCost);         setFinSub('pcBoxSub', '');
    var eCb = document.getElementById('pcCardboardSheets'); if (eCb) eCb.textContent = uvSheetsTxt;
    setFinSub('pcCardboardSheetsSub', '');
    var eCo = document.getElementById('pcCorrugatedSheets'); if (eCo) eCo.textContent = uvSheetsTxt;
    setFinSub('pcCorrugatedSheetsSub', '');
    setFin('pcFoilingCost', foilingCost);
    setFinSub('pcFoilingSub', '');
    var eDc = document.getElementById('pcDieCutSheets'); if (eDc) eDc.textContent = uvSheetsTxt;
    setFinSub('pcDieCutSheetsSub', '');
    var eFo = document.getElementById('pcFoilingSheets'); if (eFo) eFo.textContent = uvSheetsTxt;
    setFinSub('pcFoilingSheetsSub', '');
    setFin('pcDieMakingCost', dieMakingCost);
    setFin('pcPositiveCost', positiveCost);
    setFin('pcFoilBlockCost', foilBlockCost);
    // Tooling card now shows Die Making + Positive only; Foil Block's tile lives in the Foiling card
    // (its cost still counts in toolingCostCurr → grand total).
    setFin('pcToolingTotalCost', dieMakingCost + positiveCost);

    var matLineEl = document.getElementById('resMaterialsLine');
    var matCostEl = document.getElementById('resMaterialsCost');
    if (matCostEl) matCostEl.textContent = materialsCostCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (matLineEl) matLineEl.style.display = materialsCostCurr > 0 ? '' : 'none';

    document.getElementById('resSubtotal').textContent = subtotalCostCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('resVatAmount').textContent = vatAmountCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('resVatBadgePercent').textContent = vatPercent % 1 === 0 ? vatPercent.toFixed(0) : vatPercent.toFixed(1);

    document.getElementById('resGrandTotal').textContent = grandTotalCostCurr.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('resGtCurrencyBadge').textContent = currCode;

    // Raw cut-size values (in the calculator's own unit) for downstream cards like E-Flute.
    var _rawCutL = parseFloat((document.getElementById('acCutL') || {}).value) || 0;
    var _rawCutW = parseFloat((document.getElementById('acCutW') || {}).value) || 0;
    window.latestAdvancedEst = {
        paperCost: paperCostCurr,
        parentSheets: parentSheets,
        cutSheets: cutSheets,
        hasCutSize: hasCutSize,
        cutL: _rawCutL,
        cutW: _rawCutW,
        pkrToCurr: pkrToCurr, // multiply any PKR-stored rate by this to convert into the current currency
        paperRate: paperRateCurr,
        paperType: document.getElementById('acPaperType') ? document.getElementById('acPaperType').value : '',
        paperSize: document.getElementById('acPaperSize').value,
        paperUnit: sheetUnit,
        gsm: document.getElementById('acGsm').value,
        cmykCount: cmykCount,
        spotCount: spotCount,
        totalPlates: totalPlates,
        slabs: slabs,
        printingCost: printingCostCurr,
        plateCost: plateCostCurr,
        laminationCost: laminationCostCurr,
        laminationName: lamName,
        perSheetWeightKg: perSheetWeightKg,
        totalPaperWeightKg: totalPaperWeightKg,
        materialsCost: materialsCostCurr,
        subtotalCost: subtotalCostCurr,
        vatPercent: vatPercent,
        vatAmount: vatAmountCurr,
        grandTotalCost: grandTotalCostCurr,
        currencyCode: currCode,
        currencySymbol: currSymbol,
        // Finishing / post-printing / tooling. `keys` match an EXISTING costing row (by cost name);
        // if none matches, a new fixed cost row is created with `name`.
        finishing: [
            {name: 'Lamination' + (lamName && lamName !== 'None' ? ' · ' + lamName : ''), cost: laminationCostCurr, keys: ['lamination']},
            {name: 'Solid UV' + (solidUvFinish ? ' · ' + solidUvFinish : ''), cost: solidUvCost, keys: ['solid uv', 'coating']},
            {name: 'Spot UV', cost: spotUvCost, keys: ['spot uv', 'coating']},
            {name: 'Die Cutting', cost: dieCutCost, keys: ['die cutting']},
            {name: 'Cardboard Pasting', cost: cardboardCost, keys: ['cardboard', 'gluing', 'pasting']},
            {name: 'Corrugated Pasting', cost: corrugatedCost, keys: ['corrugated', 'gluing', 'pasting']},
            {name: 'Box Pasting' + (boxPasteType ? ' (' + boxPasteType + ')' : ''), cost: boxPasteCost, keys: ['box pasting', 'gluing', 'pasting']},
            {name: 'Die Making', cost: dieMakingCost, keys: ['die cost', 'die making']},
            {name: 'Positive', cost: positiveCost, keys: ['positive']},
            {name: 'Foil Block', cost: foilBlockCost, keys: ['block cost', 'foil block']},
            {name: 'Foiling', cost: foilingCost, keys: ['foiling']}
        ].concat(materialOps)
    };
}

function applyAdvancedEstToCosting() {
    calculateAdvancedEst();
    var data = window.latestAdvancedEst;
    if (!data) return;

    // --- Required-field validation: block Apply until they're filled ---
    var vErrors = [];
    var vMark = function (el, bad) { if (el) el.style.borderColor = bad ? '#dc2626' : ''; };
    // Cutting plan selected → Cut Size L & W are compulsory.
    var vCutRatio = parseFloat((document.getElementById('acCuttingPlan') || {}).value) || 1;
    var vCutLEl = document.getElementById('acCutL'), vCutWEl = document.getElementById('acCutW');
    var vCutL = parseFloat(vCutLEl ? vCutLEl.value : 0) || 0;
    var vCutW = parseFloat(vCutWEl ? vCutWEl.value : 0) || 0;
    var cutMissing = (vCutRatio > 1 && (vCutL <= 0 || vCutW <= 0));
    vMark(vCutLEl, cutMissing && vCutL <= 0);
    vMark(vCutWEl, cutMissing && vCutW <= 0);
    if (cutMissing) vErrors.push('• Cut Size L & W (cutting plan selected)');
    // Box pasting selected → Quantity is compulsory.
    var vBoxType = (document.getElementById('acBoxPaste') || {}).value || '';
    var vBoxQtyEl = document.getElementById('acBoxPasteQty');
    var vBoxQty = parseFloat(vBoxQtyEl ? vBoxQtyEl.value : 0) || 0;
    var boxMissing = (vBoxType !== '' && vBoxQty <= 0);
    vMark(vBoxQtyEl, boxMissing);
    if (boxMissing) vErrors.push('• Box Pasting Quantity (box pasting selected)');
    // Foiling selected → Foil Size L & W are compulsory.
    var vFoilFinish = (document.getElementById('acFoiling') || {}).value || '';
    var vFoilLEl = document.getElementById('acFoilL'), vFoilWEl = document.getElementById('acFoilW');
    var vFoilL = parseFloat(vFoilLEl ? vFoilLEl.value : 0) || 0;
    var vFoilW = parseFloat(vFoilWEl ? vFoilWEl.value : 0) || 0;
    var foilMissing = (vFoilFinish !== '' && (vFoilL <= 0 || vFoilW <= 0));
    vMark(vFoilLEl, foilMissing && vFoilL <= 0);
    vMark(vFoilWEl, foilMissing && vFoilW <= 0);
    if (foilMissing) vErrors.push('• Foil Size L & W (foiling selected)');
    if (vErrors.length) {
        alert('Please fill the required fields before applying:\n' + vErrors.join('\n'));
        return;
    }

    // Update Paper Cost Row
    var paperNameRow = Array.prototype.slice.call(document.querySelectorAll('input[name="cost_names[]"]')).filter(function(i){return i.value==='Paper Dimensions'})[0];
    if (paperNameRow) {
        var row = paperNameRow.closest('tr');
        var priceInput = row.querySelector('.cost-price');
        var qtyInput = row.querySelector('.cost-quantity');
        var unitPriceInput = row.querySelector('.cost-unit-price');
        var detailInput = row.querySelector('input[name="cost_details[]"]');
        var detailText = row.querySelector('.cost-detail-text');

        if (qtyInput) qtyInput.value = data.parentSheets;
        if (unitPriceInput) unitPriceInput.value = stripZeros(data.paperRate, 4);
        if (priceInput) priceInput.value = stripZeros(data.paperCost, 2);

        var specText = (data.paperType ? data.paperType + ' · ' : '') + (data.paperSize ? data.paperSize.replace('*',' × ') + ' ' + (data.paperUnit || 'in') + ' · ' : '') + data.gsm + ' GSM (' + data.currencyCode + ')';
        if (detailInput) detailInput.value = specText;
        if (detailText) detailText.textContent = specText;

        if (priceInput) priceInput.dispatchEvent(new Event('input'));
    }

    // Update CTP Plate Row
    var ctpNameRow = Array.prototype.slice.call(document.querySelectorAll('input[name="cost_names[]"]')).filter(function(i){return i.value==='CTP Plate'})[0];
    if (ctpNameRow) {
        var row = ctpNameRow.closest('tr');
        var priceInput = row.querySelector('.cost-price');
        var qtyInput = row.querySelector('.cost-quantity');
        var unitPriceInput = row.querySelector('.cost-unit-price');
        var detailInput = row.querySelector('input[name="cost_details[]"]');

        if (qtyInput) qtyInput.value = data.totalPlates;
        if (unitPriceInput) unitPriceInput.value = data.totalPlates > 0 ? stripZeros(data.plateCost / data.totalPlates, 4) : 0;
        if (priceInput) priceInput.value = stripZeros(data.plateCost, 2);

        var detailText = data.totalPlates + ' Plates (' + data.cmykCount + ' CMYK + ' + data.spotCount + ' Spot) × rate, one-time [' + data.currencyCode + ']';
        if (detailInput) detailInput.value = detailText;

        if (priceInput) priceInput.dispatchEvent(new Event('input'));
    }

    // Update Printing / Color Row (slab-based printing cost, separate from plates)
    var printNameRow = Array.prototype.slice.call(document.querySelectorAll('input[name="cost_names[]"]')).filter(function(i){return i.value==='Printing / Color'})[0];
    if (printNameRow) {
        var row = printNameRow.closest('tr');
        var priceInput = row.querySelector('.cost-price');
        var qtyInput = row.querySelector('.cost-quantity');
        var unitPriceInput = row.querySelector('.cost-unit-price');
        var detailInput = row.querySelector('input[name="cost_details[]"]');
        var totalColors = data.cmykCount + data.spotCount;

        if (qtyInput) qtyInput.value = data.slabs;
        if (unitPriceInput) unitPriceInput.value = data.slabs > 0 ? stripZeros(data.printingCost / data.slabs, 4) : 0;
        if (priceInput) priceInput.value = stripZeros(data.printingCost, 2);

        var pDetail = totalColors + ' Color(s) (' + data.cmykCount + ' CMYK + ' + data.spotCount + ' Spot) × ' + data.slabs + ' Slab(s) [' + data.currencyCode + ']';
        if (detailInput) detailInput.value = pDetail;

        if (priceInput) priceInput.dispatchEvent(new Event('input'));
    }

    // Finishing / post-printing / tooling — paste INTO an existing matching costing row; create one only if none exists.
    var matchedCount = 0, createdCount = 0;
    if (data.finishing) {
        var nameInputs = document.querySelectorAll('input[name="cost_names[]"]');
        var findRow = function(keys) {
            for (var i = 0; i < nameInputs.length; i++) {
                var v = (nameInputs[i].value || '').toLowerCase();
                for (var k = 0; k < keys.length; k++) if (v.indexOf(keys[k]) !== -1) return nameInputs[i].closest('tr');
            }
            return null;
        };
        var rowTotals = [], toCreate = [];
        data.finishing.forEach(function(op) {
            if (!(op.cost > 0)) return;
            var row = findRow(op.keys || []);
            if (row) {
                var hit = null;
                for (var r = 0; r < rowTotals.length; r++) if (rowTotals[r].row === row) hit = rowTotals[r];
                if (hit) hit.total += op.cost; else rowTotals.push({row: row, total: op.cost, detail: op.detail, qty: op.qty});
            } else {
                toCreate.push(op);
            }
        });
        rowTotals.forEach(function(rt) {
            var qtyInput = rt.row.querySelector('.cost-quantity');
            var unitInput = rt.row.querySelector('.cost-unit-price');
            var priceInput = rt.row.querySelector('.cost-price');
            if (unitInput && qtyInput) { // standard row: cost = qty × unit
                var rtQty = rt.qty > 0 ? rt.qty : 1;
                qtyInput.value = rtQty;
                unitInput.value = stripZeros(rt.total / rtQty, 4);
                unitInput.dispatchEvent(new Event('input'));
            } else if (priceInput) { // fixed row: cost entered directly
                priceInput.value = rt.total.toFixed(2);
                priceInput.dispatchEvent(new Event('input'));
            }
            if (rt.detail) { // material rows: carry the spec text into the costing row
                var rtDetailInput = rt.row.querySelector('input[name="cost_details[]"]');
                var rtDetailSpan = rt.row.querySelector('.material-cost-detail');
                if (rtDetailInput) rtDetailInput.value = rt.detail;
                if (rtDetailSpan) rtDetailSpan.textContent = rt.detail;
            }
            matchedCount++;
        });
        // Anything with no existing row → add as a fixed cost row (de-duplicated on re-apply).
        var fixedTbody = document.getElementById('fixedCostRows');
        if (fixedTbody) {
            var prevRows = fixedTbody.querySelectorAll('.es-auto-finishing');
            for (var pr = 0; pr < prevRows.length; pr++) prevRows[pr].parentNode.removeChild(prevRows[pr]);
            var curr = document.querySelector('[name="currency"]') ? document.querySelector('[name="currency"]').value : data.currencyCode;
            toCreate.forEach(function(op) {
                var tr = document.createElement('tr');
                tr.className = 'extra-fixed-cost-row es-auto-finishing';
                tr.innerHTML = '<td><input class="es-input" name="fixed_extra_names[]" value="' + op.name + '" required></td>'
                    + '<td><input class="es-input" name="fixed_extra_details[]" value="From Job Plan Calculator [' + data.currencyCode + ']"></td>'
                    + '<td><div class="es-money"><span class="currency-code">' + curr + '</span><input class="es-input es-price cost-price" type="number" min="0" step=".01" name="fixed_extra_prices[]" value="' + op.cost.toFixed(2) + '" oninput="calculateCostSummary()"><button class="es-btn es-danger" type="button" onclick="this.closest(&quot;tr&quot;).remove();calculateCostSummary()" aria-label="Remove"><i class="fas fa-times"></i></button></div></td>';
                fixedTbody.appendChild(tr);
                createdCount++;
            });
        }
    }

    if (typeof calculateCostSummary === 'function') calculateCostSummary();
    alert('Applied to Costing Table:\nPaper ' + data.currencySymbol + ' ' + stripZeros(data.paperCost, 2) + '   ·   Printing ' + data.currencySymbol + ' ' + stripZeros(data.printingCost, 2) + '   ·   CTP Plate ' + data.currencySymbol + ' ' + stripZeros(data.plateCost, 2) + '\nFinishing/Tooling: ' + matchedCount + ' pasted into existing rows, ' + createdCount + ' new row(s) added.');
}

document.addEventListener('DOMContentLoaded', function() {
    onCurrencyChange();
    calculateAdvancedEst();
});
</script>
