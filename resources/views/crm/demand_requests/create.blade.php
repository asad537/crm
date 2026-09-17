@extends('crm.layout')
@php($isEdit = isset($demandRequest) && $demandRequest)
@section('title', $isEdit ? 'Edit Demand Request' : 'New Demand Request')
@section('header_actions')
<a class="dr-btn dr-btn-light" href="{{ route('crm.demand_requests.index') }}"><i class="fas fa-arrow-left"></i> Back</a>
@endsection
@section('content')
<style>
.dr-wrap{max-width:100%;margin:0}
.dr-btn{display:inline-flex;align-items:center;gap:.45rem;min-height:40px;padding:.55rem 1rem;border:0;border-radius:10px;font-weight:800;text-decoration:none;cursor:pointer;font-size:.82rem}
.dr-btn-primary{color:#fff;background:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}
.dr-btn-light{color:#475569;background:#eef2f7}
.dr-btn-outline{color:var(--primary-purple);border:1px solid var(--primary-shadow);background:var(--primary-soft)}
.dr-card{padding:1.25rem 1.35rem;background:#fff;border:1px solid #e5ebf2;border-radius:16px;box-shadow:0 8px 28px rgba(15,23,42,.05)}
.dr-head{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;margin-bottom:1.1rem}
.dr-field label{display:block;margin-bottom:.35rem;color:#425168;font-size:.74rem;font-weight:780}
.dr-req{color:#ef4444}
.dr-control{width:100%;min-height:42px;padding:.55rem .75rem;border:1px solid #d8e1eb;border-radius:10px;background:#fff;color:#263449;font-size:.82rem;outline:0}
.dr-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.dr-section{display:flex;align-items:center;gap:.5rem;margin:.4rem 0 .7rem;color:#8a99ae;font-size:.7rem;font-weight:850;letter-spacing:.06em;text-transform:uppercase}
.dr-section:after{content:'';flex:1;height:1px;background:#e8edf3}
.dr-items{width:100%;border-collapse:separate;border-spacing:0 .5rem}
.dr-items th{padding:.2rem .55rem .5rem;text-align:left;color:#8a99ae;font-size:.63rem;font-weight:850;text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid #eef2f7}
.dr-items tbody tr{background:#fbfcff;transition:.12s}
.dr-items tbody tr:hover{background:#f4f2ff}
.dr-items td{padding:.35rem .3rem;vertical-align:middle;border-top:1px solid #eef1f6;border-bottom:1px solid #eef1f6}
.dr-items td:first-child{border-left:1px solid #eef1f6;border-radius:11px 0 0 11px}
.dr-items td:nth-last-child(2){border-right:0}
.dr-items td:last-child,.dr-items th:last-child{position:sticky;right:0;background:#fff;z-index:2;box-shadow:-8px 0 10px -8px rgba(15,23,42,.12);text-align:center}
.dr-items td:last-child{border:0}
.dr-items .dr-control{min-height:40px;padding:.5rem .6rem;font-size:.8rem}
.dr-sr{width:40px;text-align:center;border-radius:11px 0 0 11px}
.dr-srno{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:var(--primary-soft);color:var(--primary-purple);font-weight:850;font-size:.74rem}
.dr-rm{width:36px;height:36px;border:0;border-radius:9px;background:#fff1f2;color:#e11d48;cursor:pointer;margin-top:.15rem}
.dr-total-input{background:#f5f3ff;color:var(--primary-purple);font-weight:800}
.dr-add{margin:.8rem 0 0}
.dr-foot{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-top:1.3rem;padding:1rem 1.1rem;border-radius:14px;background:linear-gradient(135deg,var(--primary-soft),#fff 75%);border:1px solid #ece9ff}
.dr-grand{font-size:.74rem;color:#8a8099;font-weight:800;text-transform:uppercase;letter-spacing:.04em;display:flex;align-items:baseline;gap:.6rem}
.dr-grand strong{font-size:1.5rem;color:var(--primary-purple);margin-left:.5rem}
/* Cash in Hand adjust card (approve only) */
.dr-cih{margin-top:.85rem;width:min(420px,100%);background:#fff;border:1px solid #d6f0dd;border-radius:14px;box-shadow:0 6px 20px rgba(21,148,71,.08);overflow:hidden;text-transform:none;letter-spacing:normal}
.dr-cih-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.7rem .9rem;background:linear-gradient(135deg,#ecfdf3,#f6fffa);border-bottom:1px solid #e3f5e9}
.dr-cih-badge{display:inline-flex;align-items:center;gap:.45rem;font-size:.72rem;font-weight:850;color:#159447;text-transform:uppercase;letter-spacing:.04em}
.dr-cih-avail{font-size:1.1rem;font-weight:850;color:#0f7a3d}
.dr-cih-avail small{font-size:.62rem;font-weight:750;color:#8aa598;text-transform:uppercase;letter-spacing:.05em;margin-left:.15rem}
.dr-cih-fields{display:flex;gap:.6rem;align-items:flex-end;padding:.8rem .9rem .55rem}
.dr-cih-field{display:flex;flex-direction:column;gap:.3rem;flex:0 0 130px}
.dr-cih-field.grow{flex:1 1 auto}
.dr-cih-field label{font-size:.66rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.03em}
.dr-cih-field label span{font-weight:600;color:#a9b4c2;text-transform:none;letter-spacing:normal}
.dr-cih-field .dr-control{min-height:42px}
.dr-cih-remaining{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.7rem .9rem;background:#fafbff;border-top:1px dashed #dbe7e0}
.dr-cih-remaining span{font-size:.74rem;font-weight:800;color:#475569}
.dr-cih-remaining span em{font-style:normal;font-weight:600;color:#94a3b8}
.dr-cih-remaining strong{font-size:1.2rem;font-weight:850;color:var(--primary-purple)}
.dr-actions{display:flex;gap:.6rem}
.dr-errors{margin-bottom:1rem;padding:.8rem 1rem;border:1px solid #fecaca;border-radius:10px;background:#fff5f5;color:#b91c1c;font-size:.78rem}
.dr-table-wrap{overflow-x:auto}
@media(max-width:900px){.dr-head{grid-template-columns:repeat(2,1fr)}.dr-items{min-width:1340px}}
</style>
<div class="dr-wrap">
    @if($errors->any())
        <div class="dr-errors"><strong>Please check the form:</strong><ul style="margin:.4rem 0 0 1rem">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form class="dr-card" autocomplete="off" method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('crm.demand_requests.update', $demandRequest->id) : route('crm.demand_requests.store') }}">
        {{ csrf_field() }}
        @if($isEdit) {{ method_field('PUT') }} @endif

        <div class="dr-section"><i class="fas fa-file-signature"></i> Request Details</div>
        <div class="dr-head">
            <div class="dr-field">
                <label>Demand Request No.</label>
                <input class="dr-control" value="{{ $isEdit ? '#'.str_pad($demandRequest->request_no,3,'0',STR_PAD_LEFT) : 'Auto (on save)' }}" readonly style="background:#f5f7fa;color:#475569;font-weight:750">
            </div>
            <div class="dr-field">
                <label>Request Date <span class="dr-req">*</span></label>
                <input class="dr-control" type="date" name="request_date" value="{{ old('request_date', $isEdit ? optional($demandRequest->request_date)->format('Y-m-d') : date('Y-m-d')) }}" required>
            </div>
            <div class="dr-field">
                <label>Requested By</label>
                <input class="dr-control" name="requested_by" value="{{ old('requested_by', $defaultRequestedBy) }}" placeholder="Name">
            </div>
            <div class="dr-field">
                <label>Priority</label>
                <select class="dr-control" name="priority">
                    @foreach($priorities as $pr)
                        <option value="{{ $pr }}" {{ old('priority', $isEdit ? $demandRequest->priority : 'Normal')===$pr?'selected':'' }}>{{ $pr }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="dr-section"><i class="fas fa-list-ul"></i> Items / Materials</div>
        <datalist id="drVendorNames">@foreach(($vendors ?? []) as $v)<option value="{{ $v }}">@endforeach</datalist>
        <div class="dr-table-wrap">
        <table class="dr-items" id="drItems">
            <thead><tr>
                <th class="dr-sr">Sr</th>
                <th style="min-width:150px">Category</th>
                <th style="min-width:110px">Job No#</th>
                <th style="min-width:200px">Item / Material Description</th>
                <th style="min-width:140px">Specification</th>
                <th style="min-width:130px">Vendor Name <span style="color:#94a3b8;font-weight:600">(opt)</span></th>
                <th style="min-width:130px">Vendor Invoice No <span style="color:#94a3b8;font-weight:600">(opt)</span></th>
                <th style="min-width:90px">Qty</th>
                <th style="min-width:110px">Per Unit Price</th>
                <th style="min-width:80px">VAT %</th>
                <th style="min-width:120px">Total</th>
                <th style="min-width:130px">Attachment</th>
                <th style="width:48px;text-align:center">Del</th>
            </tr></thead>
            <tbody>
            @foreach($items as $i => $it)
                <tr class="dr-row">
                    <td class="dr-sr"><span class="dr-srno">{{ $i + 1 }}</span></td>
                    <td>
                        <select class="dr-control dr-cat" name="items[{{ $i }}][category]">
                            <option value="">— Select —</option>
                            @foreach($categories as $c)<option value="{{ $c }}" {{ ($it['category'] ?? '')===$c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
                            @if(!empty($it['category']) && !in_array($it['category'], $categories))<option value="{{ $it['category'] }}" selected>{{ $it['category'] }}</option>@endif
                        </select>
                    </td>
                    <td><input class="dr-control" autocomplete="off" name="items[{{ $i }}][job_no]" value="{{ $it['job_no'] ?? '' }}" placeholder="If against job"></td>
                    <td><input class="dr-control" autocomplete="off" name="items[{{ $i }}][description]" value="{{ $it['description'] ?? '' }}"></td>
                    <td><input class="dr-control" autocomplete="off" name="items[{{ $i }}][specification]" value="{{ $it['specification'] ?? '' }}"></td>
                    <td><input class="dr-control" list="drVendorNames" autocomplete="off" name="items[{{ $i }}][vendor_name]" value="{{ $it['vendor_name'] ?? '' }}" placeholder="Vendor"></td>
                    <td><input class="dr-control" autocomplete="off" name="items[{{ $i }}][vendor_invoice_no]" value="{{ $it['vendor_invoice_no'] ?? '' }}" placeholder="Invoice #"></td>
                    <td><input class="dr-control dr-qty" autocomplete="off" name="items[{{ $i }}][qty]" value="{{ $it['qty'] ?? '' }}" oninput="drCalcRow(this)"></td>
                    <td><input class="dr-control dr-price" type="number" step="0.01" min="0" name="items[{{ $i }}][estimated_price]" value="{{ $it['estimated_price'] ?? '' }}" oninput="drCalcRow(this)"></td>
                    <td><input class="dr-control dr-vat" type="number" step="0.01" min="0" max="100" name="items[{{ $i }}][vat_percentage]" value="{{ $it['vat_percentage'] ?? '' }}" placeholder="0" oninput="drCalcRow(this)"></td>
                    <td><input class="dr-control dr-total dr-total-input" type="number" step="0.01" min="0" name="items[{{ $i }}][estimated_total]" value="{{ $it['estimated_total'] ?? '' }}" oninput="drCalcGrand()"></td>
                    <td>
                        <input class="dr-control dr-file" type="file" name="items[{{ $i }}][files][]" multiple data-max="5" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv" style="padding:.28rem;font-size:.7rem">
                        @if(!empty($it['files']) && count($it['files']))<div style="margin-top:.25rem;display:flex;flex-wrap:wrap;gap:.25rem">@foreach($it['files'] as $f)<a href="{{ $f->url }}" target="_blank" title="{{ $f->name }}" style="font-size:.62rem;color:var(--primary-purple)"><i class="fas fa-paperclip"></i></a>@endforeach</div>@endif
                    </td>
                    <td><button class="dr-rm" type="button" onclick="drRemoveRow(this)" title="Remove"><i class="fas fa-trash"></i></button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <button class="dr-btn dr-btn-outline dr-add" type="button" onclick="drAddRow()"><i class="fas fa-plus"></i> Add Row</button>

        <div class="dr-field" style="margin-top:1rem">
            <label>Notes</label>
            <textarea class="dr-control" name="notes" rows="2" style="min-height:60px">{{ old('notes', $isEdit ? $demandRequest->notes : '') }}</textarea>
        </div>

        <div class="dr-field" style="margin-top:1rem">
            <label><i class="fas fa-paperclip"></i> Attachments (optional)</label>
            <input class="dr-control" type="file" name="files[]" multiple data-max="10" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv" style="padding:.5rem">
            <div class="dr-hint" style="font-size:.68rem;color:#94a3b8;margin-top:.25rem">Max 10 files · 20MB each</div>
            @if($isEdit && $demandRequest->attachments->count())
                <div style="margin-top:.5rem;display:flex;flex-wrap:wrap;gap:.4rem">
                    @foreach($demandRequest->attachments as $att)
                        <a href="{{ $att->url }}" target="_blank" style="font-size:.72rem;color:var(--primary-purple);background:var(--primary-soft);padding:.25rem .6rem;border-radius:999px;text-decoration:none"><i class="fas fa-file"></i> {{ \Illuminate\Support\Str::limit($att->name ?: 'file', 24) }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="dr-foot">
            <div style="display:flex;flex-direction:column;gap:.7rem;align-items:flex-start">
                <div class="dr-grand" style="flex-wrap:wrap">
                    <span style="font-size:.72rem;color:#8a8099">Subtotal: <strong id="drSub" style="color:#475569;font-size:.95rem">0.00</strong></span>
                    <span style="font-size:.72rem;color:#8a8099">VAT: <strong id="drVatAmt" style="color:#475569;font-size:.95rem">0.00</strong></span>
                    <span>Grand Total: <strong id="drGrand">0.00</strong></span>
                </div>

                @if($isEdit && isset($cashInHand))
                @php($__cih = (float) $cashInHand)
                @php($__ownUsed = (float) $demandRequest->cash_in_hand_used)
                @php($__avail = round($__cih + $__ownUsed, 2))
                <div class="dr-cih">
                    <div class="dr-cih-head">
                        <span class="dr-cih-badge"><i class="fas fa-wallet"></i> Cash in Hand</span>
                        <span class="dr-cih-avail">{{ number_format($__avail, 2) }} <small>available</small></span>
                    </div>
                    <div class="dr-cih-fields">
                        <div class="dr-cih-field">
                            <label>Adjust from cash</label>
                            <input class="dr-control" type="number" step="0.01" min="0" max="{{ $__avail }}" name="cash_in_hand_used" id="drCih"
                                   value="{{ old('cash_in_hand_used', $__ownUsed > 0 ? number_format($__ownUsed,2,'.','') : '') }}"
                                   placeholder="0.00" title="Max {{ number_format($__avail,2) }}" oninput="drCalcRemaining()" autocomplete="off">
                        </div>
                        <div class="dr-cih-field grow">
                            <label>Note <span>(optional)</span></label>
                            <input class="dr-control" name="cash_in_hand_note" maxlength="500"
                                   value="{{ old('cash_in_hand_note', $demandRequest->cash_in_hand_note) }}"
                                   placeholder="e.g. 400 from cash, rest by bank" autocomplete="off">
                        </div>
                    </div>
                    <div class="dr-cih-remaining">
                        <span>Company pays <em>(remaining)</em></span>
                        <strong id="drRemaining">0.00</strong>
                    </div>
                </div>
                @endif
            </div>
            <div class="dr-actions">
                <button class="dr-btn dr-btn-light" type="submit" name="action" value="draft"><i class="fas fa-save"></i> Save as Draft</button>
                <button class="dr-btn dr-btn-primary" type="submit" name="action" value="submit"><i class="fas fa-paper-plane"></i> {{ $isEdit ? 'Save' : 'Submit for Approval' }}</button>
                @if($isEdit && !empty($canApprove) && in_array($demandRequest->status, ['Submitted','Draft']))
                    <button class="dr-btn" type="submit" name="action" value="approve" style="background:#159447;color:#fff;box-shadow:0 8px 18px rgba(21,148,71,.3)"><i class="fas fa-check"></i> Save &amp; Approve</button>
                @endif
            </div>
        </div>
    </form>

    @if($isEdit)
    <div class="dr-actions" style="margin-top:.8rem">
        <form method="POST" action="{{ route('crm.demand_requests.destroy', $demandRequest->id) }}" style="display:inline" onsubmit="return confirm('Delete this demand request permanently?');">
            {{ csrf_field() }}{{ method_field('DELETE') }}
            <button class="dr-btn dr-btn-red" type="submit"><i class="fas fa-trash"></i> Delete</button>
        </form>
    </div>
    @endif
</div>

<script>
var drIndex = {{ count($items) }};
function drCalcRow(el){
    var row = el.closest('.dr-row');
    var qty = (row.querySelector('.dr-qty').value || '').trim();
    var price = parseFloat(row.querySelector('.dr-price').value);
    var vat = parseFloat((row.querySelector('.dr-vat')||{}).value) || 0;
    var totalEl = row.querySelector('.dr-total');
    // auto-fill total (incl VAT) only when qty is a plain number and price is set
    if (qty !== '' && !isNaN(qty) && !isNaN(price)) {
        var base = parseFloat(qty) * price;
        totalEl.value = (base + base * vat / 100).toFixed(2);
    }
    drCalcGrand();
}
function drCalcGrand(){
    var subtotal = 0, grand = 0;
    document.querySelectorAll('#drItems .dr-row').forEach(function(row){
        var qty = parseFloat((row.querySelector('.dr-qty')||{}).value) || 0;
        var price = parseFloat((row.querySelector('.dr-price')||{}).value) || 0;
        var total = parseFloat((row.querySelector('.dr-total')||{}).value) || 0;
        subtotal += qty * price;
        grand += total;
    });
    var vatAmt = grand - subtotal;
    if (vatAmt < 0) vatAmt = 0;
    var fmt = function(n){ return n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}); };
    var s = document.getElementById('drSub'); if (s) s.textContent = fmt(subtotal);
    var va = document.getElementById('drVatAmt'); if (va) va.textContent = fmt(vatAmt);
    document.getElementById('drGrand').textContent = fmt(grand);
    drCalcRemaining();
}
// Company pays (remaining) = Grand Total − Cash in Hand adjusted.
function drCalcRemaining(){
    var remEl = document.getElementById('drRemaining');
    if (!remEl) return;
    var grand = parseFloat((document.getElementById('drGrand').textContent || '0').replace(/,/g,'')) || 0;
    var cih = parseFloat((document.getElementById('drCih')||{}).value) || 0;
    var rem = grand - cih;
    if (rem < 0) rem = 0;
    remEl.textContent = rem.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
}
function drRenumber(){
    document.querySelectorAll('#drItems .dr-row').forEach(function(row, i){
        row.querySelector('.dr-srno').textContent = i + 1;
        // Delete button is always available (even on a single row).
        row.querySelector('.dr-rm').style.visibility = 'visible';
    });
}
function drAddRow(){
    var body = document.querySelector('#drItems tbody');
    var first = body.querySelector('.dr-row');
    var row = first.cloneNode(true);
    row.querySelectorAll('input, textarea, select').forEach(function(inp){
        inp.value = '';
        if (inp.tagName === 'SELECT') { inp.selectedIndex = 0; }
        inp.name = inp.name.replace(/items\[\d+\]/, 'items[' + drIndex + ']');
    });
    body.appendChild(row);
    drIndex++;
    drRenumber();
}
function drRemoveRow(btn){
    btn.closest('.dr-row').remove();
    // A demand always needs at least one item row — re-add a blank one if the last was removed.
    if (document.querySelectorAll('#drItems .dr-row').length === 0) { drAddRow(); }
    drRenumber();
    drCalcGrand();
}
drRenumber();
drCalcGrand();
</script>
@endsection
