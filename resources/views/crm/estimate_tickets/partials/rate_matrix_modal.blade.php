@php
    $u = Auth::guard('crm')->user();
    $isOwner = $u && ($u->isAdmin() || $u->isSuperAdmin() || $u->isEstimator() || $u->isSalesManager());
    $currency = $ticket->currency ?: 'USD';
    $rateMatrices = $rateMatrices ?? collect();
    $paperGsmRates = $rateMatrices->where('type', 'paper_gsm');
    $colorRates = $rateMatrices->where('type', 'printing_color');
    $ctpPlateRates = $rateMatrices->where('type', 'ctp_plate');
@endphp

@if($isOwner)
<div id="estimationRateMatrixModal" class="es-unsaved-backdrop" role="dialog" aria-modal="true" style="z-index:12500;">
    <div class="es-unsaved-dialog" style="width:min(820px,96vw);max-height:90vh;overflow-y:auto;border-radius:24px;padding:0;">
        <div class="es-unsaved-head" style="justify-content:space-between;align-items:center;padding:1.25rem 1.6rem;background:linear-gradient(135deg,#ffffff 0%,#fff7f3 100%);border-bottom:1px solid #e2e8f0;">
            <div style="display:flex;align-items:center;gap:.85rem;">
                <span class="es-unsaved-icon" style="border:none;background:linear-gradient(135deg,var(--primary-purple),var(--primary-hover));color:#fff;box-shadow:0 4px 14px var(--primary-shadow);"><i class="fas fa-sliders-h"></i></span>
                <div>
                    <h3 style="margin:0;font-size:1.18rem;font-weight:800;color:#0f172a;">Owner Rate Matrix Settings</h3>
                    <div class="es-sub" style="margin-top:.15rem;color:#64748b;">Configure paper rates, color printing rates & CTP plate rates per 1,000 impression slabs.</div>
                </div>
            </div>
            <button class="es-btn" type="button" onclick="closeRateMatrixModal()" style="min-height:36px;padding:0 .75rem;border-radius:10px;"><i class="fas fa-times"></i></button>
        </div>

        <div class="es-unsaved-body" style="padding:1.4rem 1.6rem;">
            <!-- Tabs -->
            <div style="display:flex;gap:.5rem;margin-bottom:1.4rem;padding:.3rem;border-radius:50px;background:#f1f5f9;width:fit-content;">
                <button type="button" class="rm-tab-btn active" onclick="switchRateTab('paper')" id="btnTabPaper"><i class="fas fa-file-alt"></i> Paper & GSM Rates ({{ $paperGsmRates->count() }})</button>
                <button type="button" class="rm-tab-btn" onclick="switchRateTab('color')" id="btnTabColor"><i class="fas fa-palette"></i> Printing Color Rates ({{ $colorRates->count() }})</button>
                <button type="button" class="rm-tab-btn" onclick="switchRateTab('plate')" id="btnTabPlate"><i class="fas fa-print"></i> CTP Plate Rates ({{ $ctpPlateRates->count() }})</button>
            </div>

            <!-- Tab 1: Paper & GSM Rates -->
            <div id="rateTabPaper" class="rate-tab-content">
                <div class="rm-form-box">
                    <form method="POST" action="{{ route('crm.estimation_rates.store') }}" class="rm-form-grid-3">
                        {{ csrf_field() }}
                        <input type="hidden" name="type" value="paper_gsm">
                        <div class="rm-field">
                            <label>Paper Size</label>
                            <input class="rm-input" name="paper_size" placeholder="e.g. 22*33 or 25*36" required>
                        </div>
                        <div class="rm-field">
                            <label>GSM</label>
                            <input class="rm-input" name="gsm" placeholder="e.g. 300" required>
                        </div>
                        <div class="rm-field">
                            <label>Sheet Rate ({{ $currency }})</label>
                            <input class="rm-input" type="number" step=".0001" min="0" name="rate" placeholder="0.45" required>
                        </div>
                        <button class="rm-submit-btn" type="submit"><i class="fas fa-plus"></i> Add Rate</button>
                    </form>
                </div>

                <div class="rm-table-wrap">
                    <table class="rm-table">
                        <thead>
                            <tr><th>Paper Size</th><th>GSM</th><th>Rate Per Sheet</th><th style="text-align:right">Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse($paperGsmRates as $rate)
                                <tr>
                                    <td><span class="rm-badge"><i class="fas fa-expand-alt"></i> {{ str_replace('*',' × ',$rate->paper_size) }} in</span></td>
                                    <td><strong>{{ $rate->gsm }} GSM</strong></td>
                                    <td><strong style="color:var(--primary-purple);font-size:.95rem;">{{ $currency }} {{ number_format($rate->rate, 4) }}</strong></td>
                                    <td style="text-align:right">
                                        <button class="rm-edit-btn" type="button" onclick="openRmEditModal({{ $rate->id }}, 'paper_gsm', '{{ addslashes($rate->paper_size ?? '') }}', '{{ addslashes($rate->gsm ?? '') }}', '', '', '{{ $rate->rate }}')"><i class="fas fa-pen"></i> Edit</button>
                                        <form method="POST" action="{{ route('crm.estimation_rates.destroy', $rate->id) }}" onsubmit="return confirm('Delete this rate?');" style="display:inline-block">
                                            {{ csrf_field() }}
                                            {{ method_field('DELETE') }}
                                            <button class="rm-delete-btn" type="submit"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:2rem;">No custom Paper & GSM rates configured yet. Default fallback values will be used.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Printing Color Rates -->
            <div id="rateTabColor" class="rate-tab-content" style="display:none;">
                <div class="rm-form-box">
                    <form method="POST" action="{{ route('crm.estimation_rates.store') }}" class="rm-form-grid-3">
                        {{ csrf_field() }}
                        <input type="hidden" name="type" value="printing_color">
                        <div class="rm-field">
                            <label>Color Name / Type</label>
                            <input class="rm-input" name="name" placeholder="e.g. CMYK Color or Spot Color" required>
                        </div>
                        <div class="rm-field">
                            <label>Linked CTP Plate</label>
                            <select class="rm-input" name="ctp_plate_name">
                                <option value="">-- Select Linked CTP Plate --</option>
                                @forelse($ctpPlateRates as $plate)
                                    <option value="{{ $plate->name }}">{{ $plate->name }} ({{ $currency }} {{ number_format($plate->rate, 2) }})</option>
                                @empty
                                    <option value="" disabled>No CTP plates added yet. Add plate sizes in CTP Plate Rates tab first.</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="rm-field">
                            <label>Rate Per Color ({{ $currency }})</label>
                            <input class="rm-input" type="number" step=".01" min="0" name="rate" placeholder="100.00" required>
                        </div>
                        <button class="rm-submit-btn" type="submit"><i class="fas fa-plus"></i> Add Color Rate</button>
                    </form>
                </div>

                <div class="rm-table-wrap">
                    <table class="rm-table">
                        <thead>
                            <tr><th>Color Type</th><th>Linked CTP Plate</th><th>Rate Per Color / 1000 Impressions</th><th style="text-align:right">Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse($colorRates as $rate)
                                <tr>
                                    <td><strong>{{ $rate->name }}</strong></td>
                                    <td>
                                        @if($rate->ctp_plate_name)
                                            <span class="rm-badge"><i class="fas fa-print"></i> {{ $rate->ctp_plate_name }}</span>
                                        @else
                                            <span style="color:#94a3b8;font-size:.8rem;">— Standard Plate</span>
                                        @endif
                                    </td>
                                    <td><strong style="color:var(--primary-purple);font-size:.95rem;">{{ $currency }} {{ number_format($rate->rate, 2) }}</strong></td>
                                    <td style="text-align:right">
                                        <button class="rm-edit-btn" type="button" onclick="openRmEditModal({{ $rate->id }}, 'printing_color', '', '', '{{ addslashes($rate->name ?? '') }}', '{{ addslashes($rate->ctp_plate_name ?? '') }}', '{{ $rate->rate }}')"><i class="fas fa-pen"></i> Edit</button>
                                        <form method="POST" action="{{ route('crm.estimation_rates.destroy', $rate->id) }}" onsubmit="return confirm('Delete this rate?');" style="display:inline-block">
                                            {{ csrf_field() }}
                                            {{ method_field('DELETE') }}
                                            <button class="rm-delete-btn" type="submit"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:2rem;">No custom printing color rates added yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: CTP Plate Rates -->
            <div id="rateTabPlate" class="rate-tab-content" style="display:none;">
                <div class="rm-form-box">
                    <form method="POST" action="{{ route('crm.estimation_rates.store') }}" style="display:flex;gap:.9rem;align-items:end;">
                        {{ csrf_field() }}
                        <input type="hidden" name="type" value="ctp_plate">
                        <input type="hidden" name="rate" value="0">
                        <div class="rm-field" style="flex:1;">
                            <label>Plate Size / Name</label>
                            <input class="rm-input" name="name" placeholder="e.g. 14X19, 20X29, 28X40" required>
                        </div>
                        <button class="rm-submit-btn" type="submit"><i class="fas fa-plus"></i> Add Plate Size</button>
                    </form>
                </div>

                <div class="rm-table-wrap">
                    <table class="rm-table">
                        <thead>
                            <tr><th>Plate Size / Name</th><th style="text-align:right">Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse($ctpPlateRates as $rate)
                                <tr>
                                    <td><span class="rm-badge"><i class="fas fa-print"></i> {{ $rate->name }}</span></td>
                                    <td style="text-align:right">
                                        <button class="rm-edit-btn" type="button" onclick="openRmEditModal({{ $rate->id }}, 'ctp_plate', '', '', '{{ addslashes($rate->name ?? '') }}', '', '0')"><i class="fas fa-pen"></i> Edit</button>
                                        <form method="POST" action="{{ route('crm.estimation_rates.destroy', $rate->id) }}" onsubmit="return confirm('Delete this plate size?');" style="display:inline-block">
                                            {{ csrf_field() }}
                                            {{ method_field('DELETE') }}
                                            <button class="rm-delete-btn" type="submit"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" style="text-align:center;color:#94a3b8;padding:2rem;">No CTP Plate sizes configured yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openRateMatrixModal() {
    document.getElementById('estimationRateMatrixModal').style.display = 'flex';
}
function closeRateMatrixModal() {
    document.getElementById('estimationRateMatrixModal').style.display = 'none';
}
function switchRateTab(tab) {
    document.querySelectorAll('.rate-tab-content').forEach(function(el) { el.style.display = 'none'; });
    document.querySelectorAll('#estimationRateMatrixModal .rm-tab-btn').forEach(function(el) { el.classList.remove('active'); });
    if(tab==='paper'){ document.getElementById('rateTabPaper').style.display='block'; document.getElementById('btnTabPaper').classList.add('active'); }
    if(tab==='color'){ document.getElementById('rateTabColor').style.display='block'; document.getElementById('btnTabColor').classList.add('active'); }
    if(tab==='plate'){ document.getElementById('rateTabPlate').style.display='block'; document.getElementById('btnTabPlate').classList.add('active'); }
}
</script>
@endif
