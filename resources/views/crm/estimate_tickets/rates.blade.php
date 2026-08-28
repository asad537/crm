@extends('crm.layout')
@section('title', 'Estimation Rate Matrix')

@section('content')
@php
    $u = Auth::guard('crm')->user();
    $currency = session('crm_currency', 'USD');
    $rateMatrices = $rateMatrices ?? collect();
    $paperGsmRates = $rateMatrices->where('type', 'paper_gsm')->values();
    $colorRates = $rateMatrices->where('type', 'printing_color')->values();
    $ctpPlateRates = $rateMatrices->where('type', 'ctp_plate')->values();
    $laminationRates = $rateMatrices->where('type', 'lamination')->values();
    $paperTypeRates = $rateMatrices->where('type', 'paper_type_rate')->values();
    $finishingRates = $rateMatrices->whereIn('type', ['uv_rate', 'foiling_rate', 'die_cutting', 'cardboard_pasting', 'corrugated_pasting', 'box_pasting'])->values();
    $finishingTypeLabels = ['die_cutting' => 'Die Cutting', 'cardboard_pasting' => 'Cardboard Pasting', 'corrugated_pasting' => 'Corrugated Pasting', 'box_pasting' => 'Box Pasting', 'uv_rate' => 'UV', 'foiling_rate' => 'Foiling'];
@endphp

<style>
.rm-page{max-width:1150px;margin:0 auto;padding-bottom:3rem}
.rm-hero{display:flex;align-items:center;justify-content:space-between;gap:1.2rem;margin-bottom:1.5rem;padding:1.4rem 1.75rem;border:1px solid rgba(var(--primary-rgb),.18);border-radius:22px;background:linear-gradient(135deg,#ffffff 0%,var(--primary-soft) 50%,var(--primary-soft) 100%);box-shadow:0 12px 32px rgba(var(--primary-rgb),.06)}
.rm-hero-title{display:flex;align-items:center;gap:.85rem}
.rm-hero-icon{display:grid;width:44px;height:44px;place-items:center;border-radius:14px;background:linear-gradient(135deg,var(--primary-purple),var(--primary-hover));color:#fff;font-size:1.15rem;box-shadow:0 6px 18px rgba(var(--primary-rgb),.3)}
.rm-hero h2{margin:0;font-size:1.35rem;font-weight:800;color:#0f172a;letter-spacing:-.02em}
.rm-hero p{margin:.2rem 0 0;color:#64748b;font-size:.82rem}

.rm-tabs{display:flex;flex-wrap:wrap;align-items:center;gap:.45rem;margin-bottom:1.4rem;padding:.35rem .45rem;border-radius:50px;background:#f1f5f9;width:fit-content;max-width:100%;box-sizing:border-box}
.rm-tab-btn{padding:.55rem 1.05rem;border-radius:50px;border:none;background:transparent;color:#64748b;font-weight:700;font-size:.83rem;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:.45rem;white-space:nowrap;transition:all .2s ease}
.rm-tab-btn i{font-size:.88rem}
.rm-tab-btn.active{background:linear-gradient(135deg,var(--primary-purple),var(--primary-hover));color:#fff;box-shadow:0 5px 16px rgba(var(--primary-rgb),.28)}
.rm-tab-btn:hover:not(.active){background:rgba(226,232,240,.8);color:#1e293b}

.rm-card{padding:1.6rem;background:#ffffff;border:1px solid #e2e8f0;border-radius:22px;box-shadow:0 14px 36px rgba(15,23,42,.04)}
.rm-card-header{margin-bottom:1.25rem;display:flex;align-items:center;justify-content:space-between}
.rm-card-header h3{margin:0;font-size:1.05rem;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:.5rem}
.rm-card-header h3 i{color:var(--primary-purple)}

.rm-form-box{margin-bottom:1.5rem;padding:1.25rem 1.35rem;border:1px solid #e2e8f0;border-radius:18px;background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%)}
.rm-form-grid-3{display:grid;grid-template-columns:repeat(5,minmax(0,1fr)) auto;gap:.9rem;align-items:end}
.rm-form-grid-2{display:grid;grid-template-columns:1.5fr 1fr auto;gap:.9rem;align-items:end}

.rm-field{display:flex;flex-direction:column;gap:.4rem}
.rm-field label{color:#475569;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}
.rm-input{width:100%;height:44px;padding:0 1rem;border:1px solid #cbd5e1;border-radius:12px;background:#ffffff;color:#0f172a;font-size:.88rem;font-weight:600;outline:none;transition:all .2s ease;box-shadow:0 2px 4px rgba(0,0,0,.02)}
.rm-input:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px rgba(var(--primary-rgb),.18);background:#fff}
.rm-input::placeholder{color:#94a3b8;font-weight:500}

.rm-submit-btn{height:44px;padding:0 1.4rem;border:none;border-radius:12px;background:linear-gradient(135deg,var(--primary-purple),var(--primary-hover));color:#ffffff;font-size:.85rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;box-shadow:0 6px 18px rgba(var(--primary-rgb),.25);transition:all .2s ease;white-space:nowrap}
.rm-submit-btn:hover{transform:translateY(-1px);box-shadow:0 8px 22px rgba(var(--primary-rgb),.35)}

.rm-table-wrap{overflow:hidden;border:1px solid #e2e8f0;border-radius:16px;background:#fff}
.rm-table{width:100%;border-collapse:collapse;font-size:.85rem}
.rm-table th{padding:.95rem 1.15rem;background:#f8fafc;color:#64748b;text-align:left;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #e2e8f0}
.rm-table td{padding:1rem 1.15rem;border-bottom:1px solid #f1f5f9;color:#334155;vertical-align:middle}
.rm-table tbody tr:last-child td{border-bottom:none}
.rm-table tbody tr:hover{background:#fafafc}

.rm-badge{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .75rem;border-radius:50px;background:var(--primary-soft);color:var(--primary-purple);font-size:.78rem;font-weight:800}
.rm-badge-slate{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .75rem;border-radius:50px;background:#f1f5f9;color:#475569;font-size:.78rem;font-weight:700}
.rm-delete-btn{height:34px;padding:0 .85rem;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#ef4444;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;transition:all .2s ease}
.rm-delete-btn:hover{background:#fee2e2;border-color:#fca5a5;color:#dc2626}
.rm-edit-btn{height:34px;padding:0 .85rem;border:1px solid #bfdbfe;border-radius:10px;background:#eff6ff;color:#2563eb;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;transition:all .2s ease;margin-right:.35rem}
.rm-edit-btn:hover{background:#dbeafe;border-color:#93c5fd;color:#1d4ed8}
.rm-modal-backdrop{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);z-index:99999;display:none;align-items:center;justify-content:center;padding:1rem;}
.rm-modal-box{background:#fff;border:1px solid rgba(var(--primary-rgb),.15);border-radius:24px;width:100%;max-width:560px;box-shadow:0 30px 70px -24px rgba(15,23,42,.38);overflow:hidden;animation:rmModalFadeIn .25s ease;}
.rm-modal-header{padding:1.3rem 1.5rem;background:linear-gradient(135deg,var(--primary-soft) 0%,var(--primary-soft) 100%);border-bottom:1px solid rgba(var(--primary-rgb),.16);display:flex;align-items:center;justify-content:space-between;gap:1rem}
.rm-modal-heading{display:flex;align-items:center;gap:.85rem;min-width:0}
.rm-modal-heading-icon{width:44px;height:44px;flex:0 0 44px;border-radius:14px;background:var(--primary-purple);color:#fff;display:grid;place-items:center;box-shadow:0 8px 20px rgba(var(--primary-rgb),.24)}
.rm-modal-heading h3{margin:0;color:#0f172a;font-size:1.12rem;font-weight:850;letter-spacing:-.015em}
.rm-modal-heading p{margin:.2rem 0 0;color:#7c8ba1;font-size:.78rem;font-weight:600}
.rm-modal-close{width:38px;height:38px;display:grid;place-items:center;border:1px solid #dbe3ee;border-radius:12px;background:#fff;color:#64748b;cursor:pointer;transition:.2s ease}
.rm-modal-close:hover{border-color:rgba(var(--primary-rgb),.35);color:var(--primary-purple);background:var(--primary-soft)}
.rm-modal-body{padding:1.5rem}
.rm-modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.rm-modal-field{display:flex;flex-direction:column;gap:.42rem}
.rm-modal-field.full{grid-column:1/-1}
.rm-modal-field label{color:#475569;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.065em}
.rm-modal-actions{display:flex;align-items:center;justify-content:flex-end;gap:.75rem;margin-top:1.35rem;padding-top:1.15rem;border-top:1px solid #edf1f6}
.rm-btn-secondary{height:44px;padding:0 1.25rem;border:1px solid #d7e0eb;border-radius:12px;background:#f8fafc;color:#526176;font-weight:750;cursor:pointer}
.rm-btn-primary{height:44px;padding:0 1.35rem;border:0;border-radius:12px;background:var(--primary-purple);color:#fff;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;box-shadow:0 8px 20px rgba(var(--primary-rgb),.25);transition:.2s ease}
.rm-btn-primary:hover{transform:translateY(-1px);filter:brightness(.97);box-shadow:0 10px 24px rgba(var(--primary-rgb),.32)}
@keyframes rmModalFadeIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}

.rm-empty{text-align:center;padding:3.5rem 1.5rem;color:#94a3b8}
.rm-empty-icon{display:grid;width:56px;height:56px;place-items:center;margin:0 auto 1rem;border-radius:18px;background:#f1f5f9;color:#94a3b8;font-size:1.4rem}
.rm-header-search-box {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    background: #f8fafc;
    padding: 0.35rem 0.75rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    min-height: 38px;
    box-sizing: border-box;
    width: 220px;
    flex: 0 1 220px;
    min-width: 170px;
    max-width: 260px;
    overflow: hidden;
    transition: all 0.2s ease;
}
.rm-header-search-box:focus-within {
    border-color: var(--primary-purple, #ea580c) !important;
    box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15) !important;
    background: #ffffff !important;
    width: 240px;
    flex: 0 1 240px;
}
.rm-header-search-box input,
.rm-header-search-box input:focus,
.rm-header-search-box input:active {
    border: none !important;
    outline: none !important;
    background: transparent !important;
    box-shadow: none !important;
    flex: 1 1 auto;
    width: 100% !important;
    min-width: 0 !important;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.2;
    cursor: text;
    padding: 0 !important;
    margin: 0 !important;
    box-sizing: border-box !important;
}
.rm-header-search-box input::placeholder{color:#94a3b8;opacity:1}
/* Neutralize the global .al-massa-crm input:focus ring — the wrapper's :focus-within already provides the orange ring, so the raw input must stay borderless/shadowless to avoid a second box. */
.al-massa-crm .rm-header-search-box input:not([type="checkbox"]):not([type="radio"]):focus,
.al-massa-crm .rm-header-search-box input:not([type="checkbox"]):not([type="radio"]):focus-visible{
    border:none !important;
    box-shadow:none !important;
    background:transparent !important;
}
.rm-header-filter-box{display:flex;align-items:center;gap:.3rem;background:#f8fafc;padding:.3rem .55rem;border-radius:12px;border:1px solid #e2e8f0;min-height:38px;transition:.2s ease}
.rm-header-filter-box:focus-within{border-color:var(--primary-purple);box-shadow:0 0 0 3px rgba(var(--primary-rgb),.14);background:#fff}
.rm-header-filter-label{font-size:.75rem;font-weight:700;color:#64748b;white-space:nowrap}
.rm-header-filter-box select,.rm-header-filter-box select:focus,.rm-header-filter-box select:active{all:unset!important;font-size:.82rem;font-weight:800;color:#0f172a;cursor:pointer;line-height:1.2;min-width:64px}
.rm-header-filter-chevron{color:#94a3b8;font-size:.68rem;pointer-events:none}
@media(max-width:850px){
    .rm-form-grid-3,.rm-form-grid-2{grid-template-columns:1fr}
    .rm-submit-btn{width:100%;justify-content:center}
    .rm-header-search-box{width:100%;flex:1 1 100%;max-width:none}
}
@media(max-width:560px){.rm-modal-grid{grid-template-columns:1fr}.rm-modal-field.full{grid-column:auto}.rm-modal-actions{display:grid;grid-template-columns:1fr 1fr}.rm-modal-actions button{width:100%;justify-content:center}.rm-modal-heading p{display:none}}
</style>

<div class="rm-page">
    <div class="rm-hero">
        <div class="rm-hero-title">
            <div class="rm-hero-icon"><i class="fas fa-sliders-h"></i></div>
            <div>
                <h2>Owner Estimation Rate Matrix</h2>
                <p>Configure paper rates, printing color rates & CTP plate rates per 1,000 impression slabs.</p>
            </div>
        </div>
        <a class="rm-submit-btn" href="{{ route('crm.estimate_tickets.index') }}" style="text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Back to Estimate Tickets
        </a>
    </div>

    <div class="rm-tabs">
        <button type="button" class="rm-tab-btn active" onclick="switchPageTab('paper')" id="pageTabPaper">
            <i class="fas fa-file-alt"></i> Paper & GSM ({{ $paperGsmRates->count() }})
        </button>
        <button type="button" class="rm-tab-btn" onclick="switchPageTab('color')" id="pageTabColor">
            <i class="fas fa-palette"></i> Printing Color ({{ $colorRates->count() }})
        </button>
        <button type="button" class="rm-tab-btn" onclick="switchPageTab('plate')" id="pageTabPlate">
            <i class="fas fa-print"></i> CTP Plates ({{ $ctpPlateRates->count() }})
        </button>
        <button type="button" class="rm-tab-btn" onclick="switchPageTab('lamination')" id="pageTabLamination">
            <i class="fas fa-layer-group"></i> Lamination ({{ $laminationRates->count() }})
        </button>
        <button type="button" class="rm-tab-btn" onclick="switchPageTab('papertype')" id="pageTabPaperType">
            <i class="fas fa-scroll"></i> Paper Type ({{ $paperTypeRates->count() }})
        </button>
        <button type="button" class="rm-tab-btn" onclick="switchPageTab('finishing')" id="pageTabFinishing">
            <i class="fas fa-stamp"></i> Finishing ({{ $finishingRates->count() }})
        </button>
    </div>

    <!-- Tab 1: Paper & GSM Rates -->
    <div id="pageTabContentPaper" class="rm-tab-content">
        <div class="rm-card">
            <div class="rm-card-header" style="flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <h3><i class="fas fa-file-alt"></i> Paper Size & GSM Rates</h3>
                    <span class="rm-badge-slate"><i class="fas fa-layer-group"></i> {{ $paperGsmRates->count() }} Configured</span>
                </div>

                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;">
                    <!-- Inline Search Box -->
                    <div class="rm-header-search-box">
                        <i class="fas fa-search" style="color:#94a3b8;font-size:.78rem;"></i>
                        <input type="text" placeholder="Search rates..." oninput="filterTableRows(this.value, 'paper')">
                    </div>

                    <!-- Currency Filter -->
                    <div class="rm-header-filter-box">
                        <i class="fas fa-filter" style="color:var(--primary-purple);font-size:.8rem;"></i>
                        <span class="rm-header-filter-label">Show in:</span>
                        <select onchange="rmCurrencySelected(this, 'paper')">
                            <option value="ALL">Original Rates</option>
                            @foreach(['USD','AED','GBP','EUR','CAD','AUD','PKR','SAR','QAR'] as $code)
                                <option value="{{ $code }}">{{ $code }}</option>
                            @endforeach
                        </select>
                        <i class="fas fa-chevron-down rm-header-filter-chevron"></i>
                    </div>

                    <!-- Size Unit Filter -->
                    <div class="rm-header-filter-box">
                        <i class="fas fa-ruler-combined" style="color:var(--primary-purple);font-size:.8rem;"></i>
                        <span class="rm-header-filter-label">Unit:</span>
                        <select onchange="convertPaperSizeUnit(this)">
                            <option value="ORIG">Original</option>
                            <option value="in">Inches</option>
                            <option value="cm">cm</option>
                        </select>
                        <i class="fas fa-chevron-down rm-header-filter-chevron"></i>
                    </div>

                    <!-- Add Button -->
                    <button class="rm-submit-btn" type="button" onclick="openRmAddModal('paper_gsm')">
                        <i class="fas fa-plus"></i> Add Paper Rate
                    </button>
                </div>
            </div>

@php
if (!function_exists('rmCleanRate')) {
    function rmCleanRate($val, $precision = 4) {
        $num = (float)$val;
        $formatted = number_format($num, $precision, '.', ',');
        if (strpos($formatted, '.') !== false) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }
        return $formatted;
    }
}
@endphp

            <div class="rm-table-wrap">
                <table class="rm-table" id="paperRatesTable">
                    <thead>
                        <tr>
                            <th>Paper Size</th>
                            <th style="text-align:center">GSM</th>
                            <th style="text-align:center">Rate Per Sheet</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paperGsmRates as $rate)
                            <tr class="rate-row-paper" data-currency="{{ $rate->currency ?: $currency }}">
                                <td>
                                    <span class="rm-badge"><i class="fas fa-expand-alt"></i> <span class="rm-size-value" data-id="{{ $rate->id }}" data-base-size="{{ $rate->paper_size }}" data-base-unit="{{ $rate->thickness_unit ?: 'in' }}">{{ str_replace('*',' × ',$rate->paper_size) }} {{ $rate->thickness_unit ?: 'in' }}</span></span>
                                </td>
                                <td style="text-align:center"><strong>{{ $rate->gsm }} GSM</strong></td>
                                <td style="text-align:center"><strong class="rm-rate-value" data-id="{{ $rate->id }}" data-base-rate="{{ $rate->rate }}" data-base-currency="{{ $rate->currency ?: $currency }}" data-decimals="4" style="color:var(--primary-purple);font-size:.95rem;">{{ $rate->currency ?: $currency }} {{ rmCleanRate($rate->rate) }}</strong></td>
                                <td style="text-align:right">
                                    <button class="rm-edit-btn" type="button" onclick='openRmEditModal(@json($rate->id), "paper_gsm", @json($rate->paper_size ?? ""), @json($rate->gsm ?? ""), "", "", @json($rate->rate), @json($rate->currency ?: $currency), @json($rate->thickness_unit ?: "in"))'><i class="fas fa-pen"></i> Edit</button>
                                    <form method="POST" action="{{ route('crm.estimation_rates.destroy', $rate->id) }}" onsubmit="return confirm('Delete this paper rate?');" style="display:inline-block">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button class="rm-delete-btn" type="submit"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="rm-empty">
                                        <div class="rm-empty-icon"><i class="fas fa-file-invoice"></i></div>
                                        <p>No custom Paper & GSM rates configured yet.</p>
                                        <div style="font-size:.78rem;color:#94a3b8;margin-top:.2rem;">Default fallback paper rates (e.g. 22x33, 25x36) will be used in the calculator.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Printing Color Rates -->
    <div id="pageTabContentColor" class="rm-tab-content" style="display:none;">
        <div class="rm-card">
            <div class="rm-card-header" style="flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <h3><i class="fas fa-palette"></i> Printing Color Rates</h3>
                    <span class="rm-badge-slate"><i class="fas fa-paint-brush"></i> {{ $colorRates->count() }} Configured</span>
                </div>

                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;">
                    <!-- Inline Search Box -->
                    <div class="rm-header-search-box">
                        <i class="fas fa-search" style="color:#94a3b8;font-size:.78rem;"></i>
                        <input type="text" placeholder="Search rates..." oninput="filterTableRows(this.value, 'color')">
                    </div>

                    <!-- Currency Filter -->
                    <div class="rm-header-filter-box">
                        <i class="fas fa-filter" style="color:var(--primary-purple);font-size:.8rem;"></i>
                        <span class="rm-header-filter-label">Show in:</span>
                        <select onchange="rmCurrencySelected(this, 'color')">
                            <option value="ALL">Original Rates</option>
                            @foreach(['USD','AED','GBP','EUR','CAD','AUD','PKR','SAR','QAR'] as $code)
                                <option value="{{ $code }}">{{ $code }}</option>
                            @endforeach
                        </select>
                        <i class="fas fa-chevron-down rm-header-filter-chevron"></i>
                    </div>

                    <!-- Add Button -->
                    <button class="rm-submit-btn" type="button" onclick="openRmAddModal('printing_color')">
                        <i class="fas fa-plus"></i> Add Color Rate
                    </button>
                </div>
            </div>

            <div class="rm-table-wrap">
                <table class="rm-table" id="colorRatesTable">
                    <thead>
                        <tr>
                            <th>Color Type</th>
                            <th style="text-align:center">Linked CTP Plate</th>
                            <th style="text-align:center">Rate Per Color / 1000 Impressions</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($colorRates as $rate)
                            <tr class="rate-row-color" data-currency="{{ $rate->currency ?: $currency }}">
                                <td><strong>{{ $rate->name }}</strong></td>
                                <td style="text-align:center">
                                    @if($rate->ctp_plate_name)
                                        <span class="rm-badge"><i class="fas fa-print"></i> {{ $rate->ctp_plate_name }}</span>
                                    @else
                                        <span style="color:#94a3b8;font-size:.8rem;">— Standard Plate</span>
                                    @endif
                                </td>
                                <td style="text-align:center"><strong class="rm-rate-value" data-id="{{ $rate->id }}" data-base-rate="{{ $rate->rate }}" data-base-currency="{{ $rate->currency ?: $currency }}" data-decimals="2" style="color:var(--primary-purple);font-size:.95rem;">{{ $rate->currency ?: $currency }} {{ rmCleanRate($rate->rate, 2) }}</strong></td>
                                <td style="text-align:right">
                                    <button class="rm-edit-btn" type="button" onclick='openRmEditModal(@json($rate->id), "printing_color", "", "", @json($rate->name ?? ""), @json($rate->ctp_plate_name ?? ""), @json($rate->rate), @json($rate->currency ?: $currency))'><i class="fas fa-pen"></i> Edit</button>
                                    <form method="POST" action="{{ route('crm.estimation_rates.destroy', $rate->id) }}" onsubmit="return confirm('Delete this color rate?');" style="display:inline-block">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button class="rm-delete-btn" type="submit"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="rm-empty">
                                        <div class="rm-empty-icon"><i class="fas fa-palette"></i></div>
                                        <p>No custom printing color rates configured yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: CTP Plate Rates -->
    <div id="pageTabContentPlate" class="rm-tab-content" style="display:none;">
        <div class="rm-card">
            <div class="rm-card-header" style="flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <h3><i class="fas fa-print"></i> CTP Plate Sizes</h3>
                    <span class="rm-badge-slate"><i class="fas fa-box"></i> {{ $ctpPlateRates->count() }} Configured</span>
                </div>

                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;">
                    <!-- Inline Search Box -->
                    <div class="rm-header-search-box">
                        <i class="fas fa-search" style="color:#94a3b8;font-size:.78rem;"></i>
                        <input type="text" placeholder="Search plates..." oninput="filterTableRows(this.value, 'plate')">
                    </div>

                    <!-- Add Button -->
                    <button class="rm-submit-btn" type="button" onclick="openRmAddModal('ctp_plate')">
                        <i class="fas fa-plus"></i> Add Plate Size
                    </button>
                </div>
            </div>

            <div class="rm-table-wrap">
                <table class="rm-table" id="plateRatesTable">
                    <thead>
                        <tr>
                            <th>Plate Size / Name</th>
                            <th style="text-align:center">Plate Rate (one-time)</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ctpPlateRates as $rate)
                            <tr>
                                <td>
                                    <span class="rm-badge"><i class="fas fa-print"></i> {{ $rate->name }}</span>
                                </td>
                                <td style="text-align:center"><strong style="color:var(--primary-purple);font-size:.95rem;">{{ $rate->currency ?: $currency }} {{ rmCleanRate($rate->rate, 2) }}</strong></td>
                                <td style="text-align:right">
                                    <button class="rm-edit-btn" type="button" onclick='openRmEditModal(@json($rate->id), "ctp_plate", "", "", @json($rate->name ?? ""), "", @json($rate->rate), @json($rate->currency ?: $currency))'><i class="fas fa-pen"></i> Edit</button>
                                    <form method="POST" action="{{ route('crm.estimation_rates.destroy', $rate->id) }}" onsubmit="return confirm('Delete this plate size?');" style="display:inline-block">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button class="rm-delete-btn" type="submit"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">
                                    <div class="rm-empty">
                                        <div class="rm-empty-icon"><i class="fas fa-print"></i></div>
                                        <p>No CTP Plate sizes configured yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 4: Lamination Rates -->
    <div id="pageTabContentLamination" class="rm-tab-content" style="display:none;">
        <div class="rm-card">
            <div class="rm-card-header" style="flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <h3><i class="fas fa-layer-group"></i> Lamination Rates <span style="font-size:.75rem;font-weight:600;color:#94a3b8;">(per square foot)</span></h3>
                    <span class="rm-badge-slate"><i class="fas fa-ruler-combined"></i> {{ $laminationRates->count() }} Configured</span>
                </div>

                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;">
                    <div class="rm-header-search-box">
                        <i class="fas fa-search" style="color:#94a3b8;font-size:.78rem;"></i>
                        <input type="text" placeholder="Search lamination..." oninput="filterTableRows(this.value, 'lamination')">
                    </div>
                    <button class="rm-submit-btn" type="button" onclick="openRmAddModal('lamination')">
                        <i class="fas fa-plus"></i> Add Lamination Rate
                    </button>
                </div>
            </div>

            <div class="rm-table-wrap">
                <table class="rm-table" id="laminationRatesTable">
                    <thead>
                        <tr>
                            <th>Lamination Type</th>
                            <th style="text-align:center">Rate Per Sq In</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laminationRates as $rate)
                            <tr>
                                <td><span class="rm-badge"><i class="fas fa-layer-group"></i> {{ $rate->name }}</span></td>
                                <td style="text-align:center"><strong style="color:var(--primary-purple);font-size:.95rem;">{{ $rate->currency ?: $currency }} {{ rmCleanRate($rate->rate) }}</strong> <span style="color:#94a3b8;font-size:.78rem;">/ sq in</span></td>
                                <td style="text-align:right">
                                    <button class="rm-edit-btn" type="button" onclick='openRmEditModal(@json($rate->id), "lamination", "", "", @json($rate->name ?? ""), "", @json($rate->rate), @json($rate->currency ?: $currency))'><i class="fas fa-pen"></i> Edit</button>
                                    <form method="POST" action="{{ route('crm.estimation_rates.destroy', $rate->id) }}" onsubmit="return confirm('Delete this lamination rate?');" style="display:inline-block">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button class="rm-delete-btn" type="submit"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">
                                    <div class="rm-empty">
                                        <div class="rm-empty-icon"><i class="fas fa-layer-group"></i></div>
                                        <p>No lamination rates configured yet.</p>
                                        <div style="font-size:.78rem;color:#94a3b8;margin-top:.2rem;">Add a per-square-inch rate (e.g. Gloss Lamination = 0.50 / sq in) so the calculator can price lamination automatically.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 5: Paper Type Rates -->
    <div id="pageTabContentPaperType" class="rm-tab-content" style="display:none;">
        <div class="rm-card">
            <div class="rm-card-header" style="flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <h3><i class="fas fa-scroll"></i> Paper Type Rates</h3>
                    <span class="rm-badge-slate"><i class="fas fa-tag"></i> {{ $paperTypeRates->count() }} Configured</span>
                </div>
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;">
                    <div class="rm-header-search-box">
                        <i class="fas fa-search" style="color:#94a3b8;font-size:.78rem;"></i>
                        <input type="text" placeholder="Search paper types..." oninput="filterTableRows(this.value, 'papertype')">
                    </div>
                    <button class="rm-submit-btn" type="button" onclick="openRmAddModal('paper_type_rate')">
                        <i class="fas fa-plus"></i> Add Paper Type Rate
                    </button>
                </div>
            </div>

            <div class="rm-table-wrap">
                <table class="rm-table" id="paperTypeRatesTable">
                    <thead>
                        <tr>
                            <th>Paper Type</th>
                            <th style="text-align:center">Rate / KG</th>
                            <th style="text-align:center">Weight Formula</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paperTypeRates as $rate)
                            <tr>
                                <td><span class="rm-badge"><i class="fas fa-scroll"></i> {{ $rate->name }}</span></td>
                                <td style="text-align:center"><strong style="color:var(--primary-purple);font-size:.95rem;">{{ $rate->currency ?: $currency }} {{ rmCleanRate($rate->rate) }}</strong> <span style="color:#94a3b8;font-size:.78rem;">/ kg</span></td>
                                <td style="text-align:center">@if($rate->weight_divisor)<span style="color:#334155;font-size:.82rem;">L×W×GSM ÷ {{ (int)$rate->weight_divisor }} = {{ (int)($rate->weight_sheets ?: 1) }} sheets</span>@else<span style="color:#94a3b8;font-size:.8rem;">— not set</span>@endif</td>
                                <td style="text-align:right">
                                    <button class="rm-edit-btn" type="button" onclick='openRmEditModal(@json($rate->id), "paper_type_rate", "", "", @json($rate->name ?? ""), "", @json($rate->rate), @json($rate->currency ?: $currency), "", @json($rate->weight_divisor), @json($rate->weight_sheets))'><i class="fas fa-pen"></i> Edit</button>
                                    <form method="POST" action="{{ route('crm.estimation_rates.destroy', $rate->id) }}" onsubmit="return confirm('Delete this paper type rate?');" style="display:inline-block">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button class="rm-delete-btn" type="submit"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="rm-empty">
                                        <div class="rm-empty-icon"><i class="fas fa-scroll"></i></div>
                                        <p>No paper type rates configured yet.</p>
                                        <div style="font-size:.78rem;color:#94a3b8;margin-top:.2rem;">Add each paper type (Bleach Card, Art Card, Kraft Card, Art Paper) with its price.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 6: Finishing Rates -->
    <div id="pageTabContentFinishing" class="rm-tab-content" style="display:none;">
        <div class="rm-card">
            <div class="rm-card-header" style="flex-wrap:wrap;gap:1rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <h3><i class="fas fa-stamp"></i> Finishing Rates</h3>
                    <span class="rm-badge-slate"><i class="fas fa-tag"></i> {{ $finishingRates->count() }} Configured</span>
                </div>
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end;">
                    <div class="rm-header-search-box">
                        <i class="fas fa-search" style="color:#94a3b8;font-size:.78rem;"></i>
                        <input type="text" placeholder="Search finishing..." oninput="filterTableRows(this.value, 'finishing')">
                    </div>
                    <button class="rm-submit-btn" type="button" onclick="openRmAddModal('die_cutting')">
                        <i class="fas fa-plus"></i> Add Finishing Rate
                    </button>
                </div>
            </div>
            <div style="font-size:.78rem;color:#94a3b8;padding:0 0 .6rem;">Size-tiered rates (Die Cutting &amp; Pasting): <b>Small</b> = piece fits within the threshold (short × long); larger pieces use <b>Large</b>. Box Pasting is a single per-1000 rate.</div>

            <div class="rm-table-wrap">
                <table class="rm-table" id="finishingRatesTable">
                    <thead>
                        <tr>
                            <th>Operation</th>
                            <th style="text-align:center">Small Rate</th>
                            <th style="text-align:center">Large Rate</th>
                            <th style="text-align:center">Threshold (in)</th>
                            <th style="text-align:right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($finishingRates as $rate)
                            <tr>
                                <td><span class="rm-badge"><i class="fas fa-stamp"></i> {{ $finishingTypeLabels[$rate->type] ?? $rate->type }}{{ $rate->type === 'box_pasting' ? ' — '.$rate->name : '' }}</span></td>
                                <td style="text-align:center"><strong style="color:var(--primary-purple);font-size:.95rem;">{{ $rate->currency ?: $currency }} {{ rmCleanRate($rate->rate, 2) }}</strong></td>
                                <td style="text-align:center">@if($rate->rate_large !== null)<strong style="color:#334155;">{{ $rate->currency ?: $currency }} {{ rmCleanRate($rate->rate_large, 2) }}</strong>@else<span style="color:#94a3b8;">—</span>@endif</td>
                                <td style="text-align:center">@if($rate->threshold_short)<span style="color:#334155;font-size:.82rem;">{{ rmCleanRate($rate->threshold_short, 2) }} × {{ rmCleanRate($rate->threshold_long, 2) }}</span>@else<span style="color:#94a3b8;">—</span>@endif</td>
                                <td style="text-align:right">
                                    <button class="rm-edit-btn" type="button" onclick='openRmEditModal(@json($rate->id), @json($rate->type), "", "", @json($rate->name ?? ""), "", @json($rate->rate), @json($rate->currency ?: $currency), "", null, null, @json($rate->rate_large), @json($rate->threshold_short), @json($rate->threshold_long))'><i class="fas fa-pen"></i> Edit</button>
                                    <form method="POST" action="{{ route('crm.estimation_rates.destroy', $rate->id) }}" onsubmit="return confirm('Delete this finishing rate?');" style="display:inline-block">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button class="rm-delete-btn" type="submit"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="rm-empty">
                                        <div class="rm-empty-icon"><i class="fas fa-stamp"></i></div>
                                        <p>No finishing rates configured yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="rmAddModal" class="rm-modal-backdrop">
    <div class="rm-modal-box">
        <div class="rm-modal-header">
            <div class="rm-modal-heading">
                <div class="rm-modal-heading-icon"><i class="fas fa-plus"></i></div>
                <div>
                    <h3 id="rm_add_heading">Add New Rate</h3>
                    <p>Enter specifications, currency & rate to add to rate matrix.</p>
                </div>
            </div>
            <button type="button" class="rm-modal-close" onclick="closeRmAddModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="{{ route('crm.estimation_rates.store') }}" class="rm-modal-body">
            {{ csrf_field() }}
            <input type="hidden" id="rm_add_type" name="type" value="paper_gsm">
            
            <div class="rm-modal-grid">
                <!-- Paper Fields -->
                <div id="rm_add_paper_size_group" class="rm-modal-field">
                    <label>Paper Size</label>
                    <input type="text" name="paper_size" class="rm-input" placeholder="e.g. 20*30 or 25*36">
                </div>
                <div id="rm_add_gsm_group" class="rm-modal-field">
                    <label>GSM</label>
                    <input type="text" name="gsm" class="rm-input" placeholder="e.g. 300">
                </div>
                <div id="rm_add_size_unit_group" class="rm-modal-field">
                    <label>Size Unit</label>
                    <select name="thickness_unit" class="rm-input">
                        <option value="in">Inches</option>
                        <option value="cm">Centimetres (cm)</option>
                    </select>
                </div>

                <!-- Color & Plate Fields -->
                <div id="rm_add_name_group" class="rm-modal-field full" style="display:none">
                    <label id="rm_add_name_label">Name / Title</label>
                    <input type="text" name="name" class="rm-input" placeholder="e.g. CMYK Color">
                </div>
                <div id="rm_add_ctp_group" class="rm-modal-field full" style="display:none">
                    <label>Linked CTP Plate</label>
                    <select name="ctp_plate_name" class="rm-input">
                        <option value="">-- Select Linked CTP Plate --</option>
                        @foreach($ctpPlateRates as $plate)
                            <option value="{{ $plate->name }}">{{ $plate->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Currency Field -->
                <div id="rm_add_currency_group" class="rm-modal-field">
                    <label>Currency</label>
                    <select id="rm_add_currency" name="currency" class="rm-input" onchange="rmConvertRate(this, 'rm_add_rate')">
                        @foreach(['USD'=>'US Dollar','AED'=>'UAE Dirham','GBP'=>'British Pound','EUR'=>'Euro','CAD'=>'Canadian Dollar','AUD'=>'Australian Dollar','PKR'=>'Pakistani Rupee','SAR'=>'Saudi Riyal','QAR'=>'Qatari Riyal'] as $code=>$label)
                            <option value="{{ $code }}" {{ $currency === $code ? 'selected' : '' }}>{{ $code }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Rate Field -->
                <div id="rm_add_rate_group" class="rm-modal-field">
                    <label id="rm_add_rate_label">Sheet Rate</label>
                    <input type="number" step="0.0001" min="0" id="rm_add_rate" name="rate" class="rm-input" placeholder="0.45">
                </div>
                <div id="rm_add_weight_divisor_group" class="rm-modal-field" style="display:none">
                    <label>Weight Divisor</label>
                    <input type="number" step="1" min="0" id="rm_add_weight_divisor" name="weight_divisor" class="rm-input" placeholder="e.g. 15500">
                </div>
                <div id="rm_add_weight_sheets_group" class="rm-modal-field" style="display:none">
                    <label>Weight Sheets</label>
                    <input type="number" step="1" min="1" id="rm_add_weight_sheets" name="weight_sheets" class="rm-input" placeholder="e.g. 100">
                </div>
                <input type="hidden" id="rm_add_paper_type" name="paper_type" value="">
                <div id="rm_add_fin_type_group" class="rm-modal-field full" style="display:none">
                    <label>Finishing Operation</label>
                    <select id="rm_add_fin_type" class="rm-input" onchange="rmAddFinTypeChange()">
                        <option value="die_cutting|Die Cutting">Die Cutting</option>
                        <option value="cardboard_pasting|Cardboard Pasting">Cardboard Pasting</option>
                        <option value="corrugated_pasting|Corrugated Pasting">Corrugated Pasting</option>
                        <option value="uv_rate||solid">UV — Solid Finish (Matte / Gloss / …)</option>
                        <option value="uv_rate|Spot UV|spot">UV — Spot UV</option>
                        <option value="box_pasting|Single">Box Pasting — Single</option>
                        <option value="box_pasting|Auto Bottom">Box Pasting — Auto Bottom</option>
                    </select>
                </div>
                <div id="rm_add_rate_large_group" class="rm-modal-field" style="display:none">
                    <label>Large Rate <span style="color:#94a3b8;font-weight:600;">(&gt; threshold)</span></label>
                    <input type="number" step="0.01" min="0" id="rm_add_rate_large" name="rate_large" class="rm-input" placeholder="e.g. 1500">
                </div>
                <div id="rm_add_threshold_short_group" class="rm-modal-field" style="display:none">
                    <label>Threshold Short (in)</label>
                    <input type="number" step="0.01" min="0" id="rm_add_threshold_short" name="threshold_short" class="rm-input" placeholder="18">
                </div>
                <div id="rm_add_threshold_long_group" class="rm-modal-field" style="display:none">
                    <label>Threshold Long (in)</label>
                    <input type="number" step="0.01" min="0" id="rm_add_threshold_long" name="threshold_long" class="rm-input" placeholder="23">
                </div>
            </div>

            <div class="rm-modal-actions">
                <button type="button" onclick="closeRmAddModal()" class="rm-btn-secondary">Cancel</button>
                <button type="submit" class="rm-btn-primary"><i class="fas fa-plus"></i> Add to Rate Matrix</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="rmEditModal" class="rm-modal-backdrop">
    <div class="rm-modal-box">
        <div class="rm-modal-header">
            <div class="rm-modal-heading">
                <div class="rm-modal-heading-icon"><i class="fas fa-layer-group"></i></div>
                <div>
                    <h3 id="rm_edit_heading">Edit Estimation Rate</h3>
                    <p>Update specifications, currency and pricing.</p>
                </div>
            </div>
            <button type="button" class="rm-modal-close" onclick="closeRmEditModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="rmEditForm" method="POST" class="rm-modal-body">
            {{ csrf_field() }}
            {{ method_field('PUT') }}
            <input type="hidden" id="rm_edit_type" name="type" value="">
            <div class="rm-modal-grid">
                <div id="rm_edit_paper_size_group" class="rm-modal-field" style="display:none">
                    <label>Paper Size</label>
                    <input type="text" id="rm_edit_paper_size" name="paper_size" class="rm-input" placeholder="e.g. 20X30">
                </div>
                <div id="rm_edit_gsm_group" class="rm-modal-field" style="display:none">
                    <label>GSM</label>
                    <input type="text" id="rm_edit_gsm" name="gsm" class="rm-input" placeholder="e.g. 250">
                </div>
                <div id="rm_edit_size_unit_group" class="rm-modal-field" style="display:none">
                    <label>Size Unit</label>
                    <select id="rm_edit_size_unit" name="thickness_unit" class="rm-input">
                        <option value="in">Inches</option>
                        <option value="cm">Centimetres (cm)</option>
                    </select>
                </div>
                <div id="rm_edit_name_group" class="rm-modal-field full" style="display:none">
                    <label id="rm_edit_name_label">Name / Title</label>
                    <input type="text" id="rm_edit_name" name="name" class="rm-input" placeholder="e.g. CMYK (14X19)">
                </div>
                <div id="rm_edit_ctp_group" class="rm-modal-field full" style="display:none">
                    <label>Linked CTP Plate</label>
                    <select id="rm_edit_ctp_plate_name" name="ctp_plate_name" class="rm-input">
                        <option value="">No plate linked</option>
                        @foreach($ctpPlateRates as $plate)
                            <option value="{{ $plate->name }}">{{ $plate->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="rm_edit_currency_group" class="rm-modal-field" style="display:none">
                    <label>Currency</label>
                    <select id="rm_edit_currency" name="currency" class="rm-input" onchange="rmConvertRate(this, 'rm_edit_rate')">
                        @foreach(['USD'=>'US Dollar','AED'=>'UAE Dirham','GBP'=>'British Pound','EUR'=>'Euro','CAD'=>'Canadian Dollar','AUD'=>'Australian Dollar','PKR'=>'Pakistani Rupee','SAR'=>'Saudi Riyal','QAR'=>'Qatari Riyal'] as $code=>$label)
                            <option value="{{ $code }}">{{ $code }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="rm_edit_rate_group" class="rm-modal-field" style="display:none">
                    <label id="rm_edit_rate_label">Rate</label>
                    <input type="number" step="0.0001" min="0" id="rm_edit_rate" name="rate" class="rm-input" placeholder="0.0000">
                </div>
                <div id="rm_edit_weight_divisor_group" class="rm-modal-field" style="display:none">
                    <label>Weight Divisor</label>
                    <input type="number" step="1" min="0" id="rm_edit_weight_divisor" name="weight_divisor" class="rm-input" placeholder="e.g. 15500">
                </div>
                <div id="rm_edit_weight_sheets_group" class="rm-modal-field" style="display:none">
                    <label>Weight Sheets</label>
                    <input type="number" step="1" min="1" id="rm_edit_weight_sheets" name="weight_sheets" class="rm-input" placeholder="e.g. 100">
                </div>
                <div id="rm_edit_rate_large_group" class="rm-modal-field" style="display:none">
                    <label>Large Rate <span style="color:#94a3b8;font-weight:600;">(&gt; threshold)</span></label>
                    <input type="number" step="0.01" min="0" id="rm_edit_rate_large" name="rate_large" class="rm-input" placeholder="e.g. 1500">
                </div>
                <div id="rm_edit_threshold_short_group" class="rm-modal-field" style="display:none">
                    <label>Threshold Short (in)</label>
                    <input type="number" step="0.01" min="0" id="rm_edit_threshold_short" name="threshold_short" class="rm-input" placeholder="18">
                </div>
                <div id="rm_edit_threshold_long_group" class="rm-modal-field" style="display:none">
                    <label>Threshold Long (in)</label>
                    <input type="number" step="0.01" min="0" id="rm_edit_threshold_long" name="threshold_long" class="rm-input" placeholder="23">
                </div>
            </div>
            <div class="rm-modal-actions">
                <button type="button" onclick="closeRmEditModal()" class="rm-btn-secondary">Cancel</button>
                <button type="submit" class="rm-btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchPageTab(tab) {
    document.querySelectorAll('.rm-tab-content').forEach(function(el) { el.style.display = 'none'; });
    document.querySelectorAll('.rm-tab-btn').forEach(function(el) { el.classList.remove('active'); });
    if(tab==='paper'){ document.getElementById('pageTabContentPaper').style.display='block'; document.getElementById('pageTabPaper').classList.add('active'); }
    if(tab==='color'){ document.getElementById('pageTabContentColor').style.display='block'; document.getElementById('pageTabColor').classList.add('active'); }
    if(tab==='plate'){ document.getElementById('pageTabContentPlate').style.display='block'; document.getElementById('pageTabPlate').classList.add('active'); }
    if(tab==='lamination'){ document.getElementById('pageTabContentLamination').style.display='block'; document.getElementById('pageTabLamination').classList.add('active'); }
    if(tab==='papertype'){ document.getElementById('pageTabContentPaperType').style.display='block'; document.getElementById('pageTabPaperType').classList.add('active'); }
    if(tab==='finishing'){ document.getElementById('pageTabContentFinishing').style.display='block'; document.getElementById('pageTabFinishing').classList.add('active'); }
    try { sessionStorage.setItem('rm_active_tab', tab); } catch(e){}
}

// Reload while remembering which tab was active, so a save-triggered reload stays on the same tab.
function rmReloadKeepingTab(tab) {
    try { sessionStorage.setItem('rm_active_tab', tab); } catch(e){}
    window.location.reload();
}
document.addEventListener('DOMContentLoaded', function(){
    try {
        var t = sessionStorage.getItem('rm_active_tab');
        if (t && t !== 'paper') switchPageTab(t);
    } catch(e){}
});

function openRmAddModal(type) {
    document.getElementById('rm_add_type').value = type;

    document.getElementById('rm_add_paper_size_group').style.display = 'none';
    document.getElementById('rm_add_gsm_group').style.display = 'none';
    document.getElementById('rm_add_size_unit_group').style.display = 'none';
    document.getElementById('rm_add_name_group').style.display = 'none';
    document.getElementById('rm_add_ctp_group').style.display = 'none';
    document.getElementById('rm_add_currency_group').style.display = 'none';
    document.getElementById('rm_add_rate_group').style.display = 'none';
    document.getElementById('rm_add_weight_divisor_group').style.display = 'none';
    document.getElementById('rm_add_weight_sheets_group').style.display = 'none';
    document.getElementById('rm_add_fin_type_group').style.display = 'none';
    var rmAddCat = document.getElementById('rm_add_paper_type'); if (rmAddCat) rmAddCat.value = '';
    document.getElementById('rm_add_rate_large_group').style.display = 'none';
    document.getElementById('rm_add_threshold_short_group').style.display = 'none';
    document.getElementById('rm_add_threshold_long_group').style.display = 'none';

    if(type === 'paper_gsm') {
        document.getElementById('rm_add_heading').innerText = 'Add Paper Size & Rate';
        document.getElementById('rm_add_paper_size_group').style.display = 'flex';
        document.getElementById('rm_add_gsm_group').style.display = 'flex';
        document.getElementById('rm_add_size_unit_group').style.display = 'flex';
        document.getElementById('rm_add_currency_group').style.display = 'flex';
        document.getElementById('rm_add_rate_group').style.display = 'flex';
        document.getElementById('rm_add_rate_label').innerText = 'Rate Per Sheet';
    } else if(type === 'printing_color') {
        document.getElementById('rm_add_heading').innerText = 'Add Printing Color Rate';
        document.getElementById('rm_add_name_group').style.display = 'flex';
        document.getElementById('rm_add_ctp_group').style.display = 'flex';
        document.getElementById('rm_add_currency_group').style.display = 'flex';
        document.getElementById('rm_add_rate_group').style.display = 'flex';
        document.getElementById('rm_add_name_label').innerText = 'Color Name / Type';
        document.getElementById('rm_add_rate_label').innerText = 'Rate Per 1,000 Impressions';
    } else if(type === 'ctp_plate') {
        document.getElementById('rm_add_heading').innerText = 'Add CTP Plate Size';
        document.getElementById('rm_add_name_group').style.display = 'flex';
        document.getElementById('rm_add_name_label').innerText = 'Plate Size / Name';
        document.getElementById('rm_add_currency_group').style.display = 'flex';
        document.getElementById('rm_add_rate_group').style.display = 'flex';
        document.getElementById('rm_add_rate_label').innerText = 'Plate Rate (one-time)';
    } else if(type === 'lamination') {
        document.getElementById('rm_add_heading').innerText = 'Add Lamination Rate';
        document.getElementById('rm_add_name_group').style.display = 'flex';
        document.getElementById('rm_add_currency_group').style.display = 'flex';
        document.getElementById('rm_add_rate_group').style.display = 'flex';
        document.getElementById('rm_add_name_label').innerText = 'Lamination Type';
        document.getElementById('rm_add_rate_label').innerText = 'Rate Per Sq In';
    } else if(type === 'paper_type_rate') {
        document.getElementById('rm_add_heading').innerText = 'Add Paper Type Rate';
        document.getElementById('rm_add_name_group').style.display = 'flex';
        document.getElementById('rm_add_currency_group').style.display = 'flex';
        document.getElementById('rm_add_rate_group').style.display = 'flex';
        document.getElementById('rm_add_weight_divisor_group').style.display = 'flex';
        document.getElementById('rm_add_weight_sheets_group').style.display = 'flex';
        document.getElementById('rm_add_name_label').innerText = 'Paper Type';
        document.getElementById('rm_add_rate_label').innerText = 'Rate / KG';
    } else if(['uv_rate', 'foiling_rate', 'die_cutting', 'cardboard_pasting', 'corrugated_pasting', 'box_pasting'].indexOf(type) !== -1) {
        document.getElementById('rm_add_heading').innerText = 'Add Finishing Rate';
        document.getElementById('rm_add_fin_type_group').style.display = 'flex';
        document.getElementById('rm_add_currency_group').style.display = 'flex';
        document.getElementById('rm_add_rate_group').style.display = 'flex';
        rmAddFinTypeChange();
    }

    // Plate / printing / paper-type / lamination / finishing rates are stored PKR-native — default the modal to PKR.
    if (['ctp_plate', 'printing_color', 'paper_type_rate', 'lamination', 'uv_rate', 'foiling_rate', 'die_cutting', 'cardboard_pasting', 'corrugated_pasting', 'box_pasting'].indexOf(type) !== -1) {
        var pkrDefault = document.getElementById('rm_add_currency');
        if (pkrDefault) pkrDefault.value = 'PKR';
    }

    var addCur = document.getElementById('rm_add_currency');
    if (addCur) addCur.setAttribute('data-prev-currency', addCur.value);

    document.getElementById('rmAddModal').style.display = 'flex';
}

function closeRmAddModal() {
    document.getElementById('rmAddModal').style.display = 'none';
}

// Finishing "Add" — the operation selector sets the hidden type + name and toggles the tiered fields.
function rmAddFinTypeChange() {
    var sel = document.getElementById('rm_add_fin_type');
    var parts = (sel ? sel.value : 'die_cutting|Die Cutting').split('|');
    var ftype = parts[0], fname = parts[1] || '', fcat = parts[2] || '';
    document.getElementById('rm_add_type').value = ftype;
    var nameInput = document.querySelector('#rm_add_name_group input[name="name"]');
    if (nameInput) nameInput.value = fname;
    var catInput = document.getElementById('rm_add_paper_type');
    if (catInput) catInput.value = fcat; // UV category: 'solid' / 'spot'
    // Box pasting carries a single flat rate — no size tier / threshold fields. UV is size-tiered.
    var noTier = (ftype === 'box_pasting');
    document.getElementById('rm_add_rate_large_group').style.display = noTier ? 'none' : 'flex';
    document.getElementById('rm_add_threshold_short_group').style.display = noTier ? 'none' : 'flex';
    document.getElementById('rm_add_threshold_long_group').style.display = noTier ? 'none' : 'flex';
    document.getElementById('rm_add_rate_label').innerText = (ftype === 'uv_rate') ? 'Small Rate / sq in' : (noTier ? 'Rate / 1000 sheets' : 'Small Rate');
    if (!noTier) {
        var ts = document.getElementById('rm_add_threshold_short'); if (ts && !ts.value) ts.value = 18;
        var tl = document.getElementById('rm_add_threshold_long'); if (tl && !tl.value) tl.value = 23;
    }
}

// Exchange rates: how many units of each currency equal 1 USD.
// Used only to auto-suggest a converted price in the Add/Edit modal — the value stays editable.
// These are fallback values; rmLoadLiveRates() refreshes them from a live API on page load.
var RM_FX = { USD:1, AED:3.6725, GBP:0.79, EUR:0.92, CAD:1.37, AUD:1.52, PKR:278, SAR:3.75, QAR:3.64 };

// Fetch live USD-based rates once on load. Read-only GET; no user data is sent.
// On any failure the hardcoded RM_FX fallback above stays in effect.
function rmLoadLiveRates() {
    fetch('https://open.er-api.com/v6/latest/USD')
        .then(function(r){ return r.ok ? r.json() : null; })
        .then(function(data){
            if (!data || data.result !== 'success' || !data.rates) return;
            Object.keys(RM_FX).forEach(function(code){
                var live = data.rates[code];
                if (typeof live === 'number' && live > 0) RM_FX[code] = live;
            });
            // Re-apply any active display-currency conversion now that live rates are in.
            if (typeof rmDisplayCurrency !== 'undefined') {
                if (rmDisplayCurrency.paper !== 'ALL') convertRatesDisplay(rmDisplayCurrency.paper, 'paper');
                if (rmDisplayCurrency.color !== 'ALL') convertRatesDisplay(rmDisplayCurrency.color, 'color');
            }
        })
        .catch(function(){ /* keep fallback rates */ });
}
document.addEventListener('DOMContentLoaded', rmLoadLiveRates);

function rmConvertRate(selectEl, rateInputId) {
    var rateInput = document.getElementById(rateInputId);
    if (!rateInput) return;
    var newCur = selectEl.value;
    var oldCur = selectEl.getAttribute('data-prev-currency') || newCur;
    var val = parseFloat(rateInput.value);
    if (oldCur !== newCur && !isNaN(val) && val > 0 && RM_FX[oldCur] && RM_FX[newCur]) {
        var converted = (val / RM_FX[oldCur]) * RM_FX[newCur];
        rateInput.value = converted.toFixed(4);
    }
    selectEl.setAttribute('data-prev-currency', newCur);
}

// Combined filter state so the Search box and the Currency filter stack instead of overriding each other.
var rmFilterState = {
    paper: { q: '', cur: 'ALL' },
    color: { q: '', cur: 'ALL' },
    plate: { q: '', cur: 'ALL' },
    lamination: { q: '', cur: 'ALL' },
    papertype: { q: '', cur: 'ALL' },
    finishing: { q: '', cur: 'ALL' }
};

var RM_TABLE_IDS = { paper: 'paperRatesTable', color: 'colorRatesTable', plate: 'plateRatesTable', lamination: 'laminationRatesTable', papertype: 'paperTypeRatesTable', finishing: 'finishingRatesTable' };
function applyRateFilters(tabType) {
    var tableId = RM_TABLE_IDS[tabType] || 'paperRatesTable';
    var st = rmFilterState[tabType];
    var q = (st.q || '').toLowerCase().trim();
    var cur = st.cur || 'ALL';
    var rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(function(row) {
        if (row.cells.length <= 1) return; // skip empty-state row
        var rowCur = row.getAttribute('data-currency');
        var textMatch = !q || row.innerText.toLowerCase().indexOf(q) !== -1;
        var curMatch = (cur === 'ALL') || (rowCur === cur);
        row.style.display = (textMatch && curMatch) ? '' : 'none';
    });
}

// Currency dropdown no longer filters rows — it re-displays EVERY rate converted into the chosen currency.
// 'ALL' restores each row's original stored currency/rate.
var rmDisplayCurrency = { paper: 'ALL', color: 'ALL' };

function convertRatesDisplay(selectedCurrency, tabType) {
    rmDisplayCurrency[tabType] = selectedCurrency;
    var tableId = (tabType === 'paper') ? 'paperRatesTable' : 'colorRatesTable';
    var cells = document.querySelectorAll('#' + tableId + ' .rm-rate-value');
    cells.forEach(function(el) {
        var base = parseFloat(el.getAttribute('data-base-rate'));
        var baseCur = el.getAttribute('data-base-currency');
        var dec = parseInt(el.getAttribute('data-decimals') || '2', 10);
        if (isNaN(base)) return;
        var showCur, showVal;
        if (selectedCurrency === 'ALL' || !RM_FX[selectedCurrency] || !RM_FX[baseCur]) {
            showCur = baseCur;
            showVal = base;
        } else {
            showCur = selectedCurrency;
            showVal = (base / RM_FX[baseCur]) * RM_FX[selectedCurrency];
        }
        var numFmt = showVal % 1 === 0 ? showVal.toLocaleString('en-US') : showVal.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).replace(/0+$/, '').replace(/\.$/, '');
        el.textContent = showCur + ' ' + numFmt;
    });
}

// Paper size unit conversion (display only). 1 in = 25.4 mm = 2.54 cm.
var RM_UNIT_MM = { in: 25.4, mm: 1, cm: 10 };
function rmFmtSize(n) {
    var r = Math.round(n * 100) / 100;
    if (r % 1 === 0) return String(r);
    return String(r).replace(/0+$/, '').replace(/\.$/, '');
}
// Convert one "a x b" size string from baseUnit to targetUnit. Returns { display, stored } or null.
function rmConvertSize(baseSize, baseUnit, targetUnit) {
    if (!RM_UNIT_MM[targetUnit] || !RM_UNIT_MM[baseUnit]) return null;
    var parts = baseSize.split(/\s*[*xX×]\s*/);
    var nums = [];
    var ok = false;
    parts.forEach(function(p) {
        var num = parseFloat(p);
        if (isNaN(num)) { nums.push(p.trim()); return; }
        ok = true;
        nums.push(rmFmtSize((num * RM_UNIT_MM[baseUnit]) / RM_UNIT_MM[targetUnit]));
    });
    if (!ok) return null;
    return { display: nums.join(' × ') + ' ' + targetUnit, stored: nums.join('*') };
}

// Dropdown handler: preview the unit conversion, then save it straight to the DB.
// 'Original' just restores the display; the dropdown snaps back on cancel/failure.
function convertPaperSizeUnit(selectEl) {
    var targetUnit = selectEl.value;
    var cells = document.querySelectorAll('#paperRatesTable .rm-size-value');
    var updates = [];

    cells.forEach(function(el) {
        var baseSize = el.getAttribute('data-base-size') || '';
        var baseUnit = el.getAttribute('data-base-unit') || 'in';
        if (targetUnit === 'ORIG') {
            el.textContent = baseSize.replace(/\s*[*xX×]\s*/g, ' × ') + ' ' + baseUnit;
            return;
        }
        var conv = rmConvertSize(baseSize, baseUnit, targetUnit);
        if (!conv) { el.textContent = baseSize.replace(/\s*[*xX×]\s*/g, ' × ') + ' ' + baseUnit; return; }
        el.textContent = conv.display;
        var id = parseInt(el.getAttribute('data-id'), 10);
        if (id) updates.push({ id: id, paper_size: conv.stored });
    });

    if (targetUnit === 'ORIG') return;
    if (!updates.length) { selectEl.value = 'ORIG'; return; }

    fetch(@json(route('crm.estimation_rates.bulk_convert_unit')), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': RM_CSRF },
        body: JSON.stringify({ unit: targetUnit, updates: updates })
    }).then(function(r) {
        if (r.ok) { rmReloadKeepingTab('paper'); }
        else { alert('Save failed (status ' + r.status + ').'); selectEl.value = 'ORIG'; convertPaperSizeUnit(selectEl); }
    }).catch(function() { alert('Network error — nothing was saved.'); selectEl.value = 'ORIG'; convertPaperSizeUnit(selectEl); });
}

// Dropdown handler: preview the conversion, then save it straight to the DB (confirmed).
// If the user cancels or it fails, the dropdown snaps back to "Original Rates".
function rmCurrencySelected(selectEl, tabType) {
    var cur = selectEl.value;
    convertRatesDisplay(cur, tabType);
    if (cur === 'ALL') return; // just restore originals, nothing to save
    rmSaveConverted(tabType, selectEl);
}

function rmResetToOriginal(selectEl, tabType) {
    if (selectEl) selectEl.value = 'ALL';
    convertRatesDisplay('ALL', tabType);
}

// Persist the converted rates + currency to the database for every row in the tab.
// Irreversible bulk overwrite — always confirmed first.
var RM_CSRF = @json(csrf_token());
function rmSaveConverted(tabType, selectEl) {
    var cur = rmDisplayCurrency[tabType];
    if (!cur || cur === 'ALL') { rmResetToOriginal(selectEl, tabType); return; }
    if (!RM_FX[cur]) { alert('Exchange rate is not available yet. Please try again in a moment.'); rmResetToOriginal(selectEl, tabType); return; }

    var tableId = (tabType === 'paper') ? 'paperRatesTable' : 'colorRatesTable';
    var cells = document.querySelectorAll('#' + tableId + ' .rm-rate-value');
    var updates = [];
    cells.forEach(function(el) {
        var id = parseInt(el.getAttribute('data-id'), 10);
        var base = parseFloat(el.getAttribute('data-base-rate'));
        var baseCur = el.getAttribute('data-base-currency');
        if (!id || isNaN(base) || !RM_FX[baseCur]) return;
        var conv = (base / RM_FX[baseCur]) * RM_FX[cur];
        updates.push({ id: id, rate: parseFloat(conv.toFixed(4)) });
    });

    if (!updates.length) { alert('No rates found to convert.'); rmResetToOriginal(selectEl, tabType); return; }

    fetch(@json(route('crm.estimation_rates.bulk_convert')), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': RM_CSRF },
        body: JSON.stringify({ currency: cur, updates: updates })
    }).then(function(r) {
        if (r.ok) { rmReloadKeepingTab(tabType); }
        else { alert('Save failed (status ' + r.status + ').'); rmResetToOriginal(selectEl, tabType); }
    }).catch(function() { alert('Network error — nothing was saved.'); rmResetToOriginal(selectEl, tabType); });
}

function openRmEditModal(id, type, paper_size, gsm, name, ctp_plate_name, rate, currency, sizeUnit, weightDivisor, weightSheets, rateLarge, thresholdShort, thresholdLong) {
    document.getElementById('rmEditForm').action = @json(route('crm.estimation_rates.update', ['id' => '__RATE_ID__'])).replace('__RATE_ID__', id);
    document.getElementById('rm_edit_type').value = type;

    document.getElementById('rm_edit_paper_size_group').style.display = 'none';
    document.getElementById('rm_edit_gsm_group').style.display = 'none';
    document.getElementById('rm_edit_size_unit_group').style.display = 'none';
    document.getElementById('rm_edit_name_group').style.display = 'none';
    document.getElementById('rm_edit_ctp_group').style.display = 'none';
    document.getElementById('rm_edit_weight_divisor_group').style.display = 'none';
    document.getElementById('rm_edit_weight_sheets_group').style.display = 'none';
    document.getElementById('rm_edit_currency_group').style.display = 'none';
    document.getElementById('rm_edit_rate_group').style.display = 'none';
    document.getElementById('rm_edit_rate_large_group').style.display = 'none';
    document.getElementById('rm_edit_threshold_short_group').style.display = 'none';
    document.getElementById('rm_edit_threshold_long_group').style.display = 'none';

    if(type === 'paper_gsm') {
        document.getElementById('rm_edit_heading').innerText = 'Edit Paper Rate';
        document.getElementById('rm_edit_paper_size_group').style.display = 'flex';
        document.getElementById('rm_edit_gsm_group').style.display = 'flex';
        document.getElementById('rm_edit_size_unit_group').style.display = 'flex';
        document.getElementById('rm_edit_currency_group').style.display = 'flex';
        document.getElementById('rm_edit_rate_group').style.display = 'block';
        document.getElementById('rm_edit_rate_label').innerText = 'Rate Per Sheet';
        document.getElementById('rm_edit_paper_size').value = paper_size;
        document.getElementById('rm_edit_gsm').value = gsm;
        document.getElementById('rm_edit_size_unit').value = sizeUnit || 'in';
        document.getElementById('rm_edit_rate').value = rate;
    } else if(type === 'printing_color') {
        document.getElementById('rm_edit_heading').innerText = 'Edit Printing Rate';
        document.getElementById('rm_edit_name_group').style.display = 'flex';
        document.getElementById('rm_edit_ctp_group').style.display = 'flex';
        document.getElementById('rm_edit_currency_group').style.display = 'flex';
        document.getElementById('rm_edit_rate_group').style.display = 'flex';
        document.getElementById('rm_edit_name_label').innerText = 'Color Type & Machine';
        document.getElementById('rm_edit_rate_label').innerText = 'Rate Per 1,000 Impressions';
        document.getElementById('rm_edit_name').value = name;
        document.getElementById('rm_edit_ctp_plate_name').value = ctp_plate_name;
        document.getElementById('rm_edit_rate').value = rate;
    } else if(type === 'ctp_plate') {
        document.getElementById('rm_edit_heading').innerText = 'Edit CTP Plate';
        document.getElementById('rm_edit_name_group').style.display = 'flex';
        document.getElementById('rm_edit_name_label').innerText = 'Plate Size / Name';
        document.getElementById('rm_edit_name').value = name;
        document.getElementById('rm_edit_currency_group').style.display = 'flex';
        document.getElementById('rm_edit_rate_group').style.display = 'flex';
        document.getElementById('rm_edit_rate_label').innerText = 'Plate Rate (one-time)';
    } else if(type === 'lamination') {
        document.getElementById('rm_edit_heading').innerText = 'Edit Lamination Rate';
        document.getElementById('rm_edit_name_group').style.display = 'flex';
        document.getElementById('rm_edit_currency_group').style.display = 'flex';
        document.getElementById('rm_edit_rate_group').style.display = 'flex';
        document.getElementById('rm_edit_name_label').innerText = 'Lamination Type';
        document.getElementById('rm_edit_rate_label').innerText = 'Rate Per Sq In';
        document.getElementById('rm_edit_name').value = name;
        document.getElementById('rm_edit_rate').value = rate;
    } else if(type === 'paper_type_rate') {
        document.getElementById('rm_edit_heading').innerText = 'Edit Paper Type Rate';
        document.getElementById('rm_edit_name_group').style.display = 'flex';
        document.getElementById('rm_edit_currency_group').style.display = 'flex';
        document.getElementById('rm_edit_rate_group').style.display = 'flex';
        document.getElementById('rm_edit_weight_divisor_group').style.display = 'flex';
        document.getElementById('rm_edit_weight_sheets_group').style.display = 'flex';
        document.getElementById('rm_edit_name_label').innerText = 'Paper Type';
        document.getElementById('rm_edit_rate_label').innerText = 'Rate / KG';
        document.getElementById('rm_edit_name').value = name;
        document.getElementById('rm_edit_rate').value = rate;
        document.getElementById('rm_edit_weight_divisor').value = weightDivisor || '';
        document.getElementById('rm_edit_weight_sheets').value = weightSheets || '';
    } else if(['uv_rate', 'foiling_rate', 'die_cutting', 'cardboard_pasting', 'corrugated_pasting', 'box_pasting'].indexOf(type) !== -1) {
        var finLabels = {die_cutting: 'Die Cutting', cardboard_pasting: 'Cardboard Pasting', corrugated_pasting: 'Corrugated Pasting', box_pasting: 'Box Pasting', uv_rate: 'UV'};
        var isBox = (type === 'box_pasting');
        var noTier = isBox; // box pasting = single flat rate; UV & others are size-tiered
        document.getElementById('rm_edit_heading').innerText = 'Edit ' + (finLabels[type] || 'Finishing') + ' Rate';
        document.getElementById('rm_edit_name_group').style.display = 'flex';
        document.getElementById('rm_edit_name_label').innerText = isBox ? 'Box Pasting Type' : 'Operation';
        document.getElementById('rm_edit_name').value = name;
        document.getElementById('rm_edit_currency_group').style.display = 'flex';
        document.getElementById('rm_edit_rate_group').style.display = 'flex';
        document.getElementById('rm_edit_rate_label').innerText = (type === 'uv_rate') ? 'Small Rate / sq in' : (isBox ? 'Rate / 1000 sheets' : 'Small Rate');
        document.getElementById('rm_edit_rate').value = rate;
        document.getElementById('rm_edit_rate_large_group').style.display = noTier ? 'none' : 'flex';
        document.getElementById('rm_edit_threshold_short_group').style.display = noTier ? 'none' : 'flex';
        document.getElementById('rm_edit_threshold_long_group').style.display = noTier ? 'none' : 'flex';
        document.getElementById('rm_edit_rate_large').value = (rateLarge !== null && rateLarge !== undefined) ? rateLarge : '';
        document.getElementById('rm_edit_threshold_short').value = (thresholdShort !== null && thresholdShort !== undefined) ? thresholdShort : '';
        document.getElementById('rm_edit_threshold_long').value = (thresholdLong !== null && thresholdLong !== undefined) ? thresholdLong : '';
    }

    var editCur = document.getElementById('rm_edit_currency');
    editCur.value = currency || @json($currency);
    editCur.setAttribute('data-prev-currency', editCur.value);

    document.getElementById('rmEditModal').style.display = 'flex';
}

function closeRmEditModal() {
    document.getElementById('rmEditModal').style.display = 'none';
}

function filterTableRows(query, tabType) {
    if (rmFilterState[tabType]) rmFilterState[tabType].q = query;
    applyRateFilters(tabType);
}
</script>
@endsection
