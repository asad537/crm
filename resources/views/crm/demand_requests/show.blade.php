@extends('crm.layout')
@section('title', 'Demand #'.str_pad($dr->request_no,3,'0',STR_PAD_LEFT))
@section('header_actions')
<a class="dr-btn dr-btn-light" href="{{ route('crm.demand_requests.index') }}"><i class="fas fa-arrow-left"></i> Back</a>
@endsection
@section('content')
@php
    $estimated = (float) $dr->estimated_total;
    $paid = $dr->paidTotal();
    $writeOff = $dr->writeOffTotal();
    $outstanding = $dr->outstandingTotal();
    $covered = min($estimated, $paid + $writeOff);
    $pct = $estimated > 0 ? min(100, round($covered / $estimated * 100)) : ($paid > 0 ? 100 : 0);
    $stSlug = str_replace([' ','/'],['-','-'],$dr->status);
@endphp
<style>
.dr-wrap{max-width:100%;margin:0}
.dr-btn{display:inline-flex;align-items:center;gap:.45rem;min-height:40px;padding:.55rem 1rem;border:0;border-radius:10px;font-weight:800;text-decoration:none;cursor:pointer;font-size:.82rem}
.dr-btn-primary{color:#fff;background:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}.dr-btn-light{color:#475569;background:#eef2f7}
.dr-btn-green{color:#fff;background:#159447}.dr-btn-red{color:#fff;background:#e11d48}.dr-btn-outline{color:var(--primary-purple);border:1px solid var(--primary-shadow);background:var(--primary-soft)}
.dr-btn-sm{min-height:34px;padding:.4rem .7rem;font-size:.76rem}
.dr-card{padding:1.15rem 1.3rem;background:#fff;border:1px solid #e5ebf2;border-radius:16px;box-shadow:0 8px 28px rgba(15,23,42,.05);margin-bottom:1rem}
.dr-top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.dr-co h2{margin:0;color:#172033;font-size:1.1rem}.dr-co small{color:#8290a3;font-size:.72rem}
.dr-meta{display:flex;gap:1.6rem;flex-wrap:wrap;margin-top:.9rem}
.dr-meta div span{display:block;color:#8a99ae;font-size:.63rem;font-weight:800;text-transform:uppercase}
.dr-meta div strong{display:block;margin-top:.15rem;color:#27364b;font-size:.86rem}
.dr-badge{display:inline-flex;padding:.28rem .65rem;border-radius:999px;font-size:.66rem;font-weight:850}
.dr-pri-Urgent{background:#fff1f2;color:#e11d48}.dr-pri-Normal{background:#eef2f7;color:#64748b}
.dr-st-Draft{background:#eef2f7;color:#64748b}.dr-st-Submitted{background:#fff7ed;color:#c2620c}.dr-st-Approved{background:#e0f7fb;color:#0891b2}.dr-st-Rejected{background:#fff1f2;color:#e11d48}.dr-st-Partially-Paid{background:#fef3c7;color:#b45309}.dr-st-Completed{background:#e6f7e9;color:#159447}
.dr-money{display:grid;grid-template-columns:repeat(5,1fr);gap:.9rem;margin-top:.2rem}
@media(max-width:1100px){.dr-money{grid-template-columns:repeat(3,1fr)}}
.dr-m{padding:1rem;border-radius:14px;text-align:center}
.dr-m span{display:block;font-size:.66rem;font-weight:800;text-transform:uppercase;opacity:.85}
.dr-m strong{display:block;margin-top:.25rem;font-size:1.55rem;font-weight:850}
.dr-m1{background:var(--primary-soft);color:var(--primary-purple)}.dr-m2{background:#e6f7e9;color:#159447}.dr-m3{background:#fff1f2;color:#e11d48}
.dr-prog{height:9px;border-radius:99px;background:#eef2f7;margin-top:.9rem;overflow:hidden}
.dr-prog > i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,var(--primary-purple),#22c55e)}
.dr-prog-txt{margin-top:.35rem;font-size:.7rem;color:#8290a3;font-weight:700}
.dr-secttl{display:flex;align-items:center;gap:.5rem;margin:.1rem 0 .85rem;color:#8a99ae;font-size:.7rem;font-weight:850;text-transform:uppercase}.dr-secttl:after{content:'';flex:1;height:1px;background:#e8edf3}
.dr-table{width:100%;border-collapse:collapse;font-size:.8rem}
.dr-table th{padding:.5rem .6rem;text-align:left;color:#8a99ae;font-size:.62rem;font-weight:850;text-transform:uppercase;border-bottom:1px solid #eef2f7;white-space:nowrap}
.dr-table td{padding:.55rem .6rem;border-bottom:1px solid #f2f5f9;color:#334155;vertical-align:middle}
.dr-num{text-align:right;font-variant-numeric:tabular-nums}
.dr-pay-grid{display:flex;flex-wrap:wrap;gap:.6rem;align-items:end}.dr-pay-grid>.dr-field{flex:1 1 140px;min-width:0}.dr-pay-grid>.dr-field.dr-f-btn{flex:0 0 auto}
.dr-field label{display:block;margin-bottom:.28rem;color:#425168;font-size:.68rem;font-weight:780}
.dr-control{width:100%;min-height:40px;padding:.5rem .6rem;border:1px solid #d8e1eb;border-radius:9px;font-size:.8rem;outline:0;background:#fff}
.dr-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
.dr-flash{margin-bottom:1rem;padding:.7rem 1rem;border:1px solid #bbf7d0;border-radius:10px;background:#f0fdf4;color:#15803d;font-size:.8rem;font-weight:700}
.dr-actbar{display:flex;flex-wrap:wrap;gap:.6rem;align-items:center}
.dr-reject{display:none;margin-top:.8rem;padding:.8rem;border:1px solid #fecaca;border-radius:12px;background:#fff5f5}
.dr-reason{margin-top:.7rem;padding:.7rem .9rem;border-radius:10px;background:#fff1f2;color:#b91c1c;font-size:.78rem}
.dr-empty{padding:1.2rem;text-align:center;color:#94a3b8;font-size:.8rem}
.dr-tag{display:inline-block;padding:.15rem .5rem;border-radius:8px;background:#eef2ff;color:#4f46e5;font-size:.66rem;font-weight:800}
.dr-tag-gen{background:#eef2f7;color:#64748b}
.dr-del{width:30px;height:30px;border:0;border-radius:8px;background:#fff1f2;color:#e11d48;cursor:pointer}
.dr-table-wrap{overflow-x:auto}
.dr-pay-table td{padding:.3rem .35rem}.dr-pay-table .dr-control{min-height:36px;padding:.4rem .5rem;font-size:.78rem}
@media(max-width:900px){.dr-money{grid-template-columns:1fr}.dr-pay-grid{grid-template-columns:1fr 1fr}}
.dr-att{display:inline-flex;align-items:center;gap:.45rem;padding:.4rem .7rem;border:1px solid #e2e8f0;border-radius:9px;background:#f8fafc;font-size:.78rem}
.dr-att a{color:#334155;text-decoration:none;font-weight:700}.dr-att a:hover{color:var(--primary-purple)}
.dr-att i{color:var(--primary-purple)}
.dr-att-x{border:0;background:#fff1f2;color:#e11d48;width:22px;height:22px;border-radius:6px;cursor:pointer;font-size:.7rem}
</style>
<div class="dr-wrap">
    @if(session('status'))<div class="dr-flash"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>@endif

    {{-- Header + money summary --}}
    <div class="dr-card">
        <div class="dr-top">
            <div class="dr-co">
                <h2>{{ $company['name'] }}</h2>
                @if($company['address'])<small>{{ $company['address'] }}</small>@endif
            </div>
            <div style="text-align:right">
                <div style="font-weight:850;color:#172033">Demand #{{ str_pad($dr->request_no,3,'0',STR_PAD_LEFT) }}</div>
                <span class="dr-badge dr-st-{{ $stSlug }}">{{ $dr->status }}</span>
                @php($__pay = $dr->paymentStatus())
                @if($__pay)
                <span style="display:inline-block;margin-left:.35rem;padding:.28rem .6rem;border-radius:999px;font-size:.66rem;font-weight:850;text-transform:uppercase;{{ $__pay === 'Paid' ? 'background:#dcfce7;color:#166534' : ($__pay === 'Partial' ? 'background:#fef3c7;color:#b45309' : 'background:#fee2e2;color:#b91c1c') }}">{{ $__pay }}</span>
                @endif
            </div>
        </div>
        <div class="dr-meta">
            <div><span>Date</span><strong>{{ optional($dr->request_date)->format('d M Y') }}</strong></div>
            <div><span>Requested By</span><strong>{{ $dr->requested_by ?: ($dr->creator->name ?? '—') }}</strong></div>
            <div><span>Priority</span><strong><span class="dr-badge dr-pri-{{ $dr->priority }}">{{ $dr->priority }}</span></strong></div>
            @if($dr->approved_at)<div><span>{{ $dr->status==='Rejected'?'Rejected By':'Approved By' }}</span><strong>{{ $dr->approver->name ?? '—' }}</strong></div>@endif
        </div>
        @if($dr->status==='Rejected' && $dr->rejection_reason)<div class="dr-reason"><i class="fas fa-times-circle"></i> {{ $dr->rejection_reason }}</div>@endif

        <div class="dr-money">
            <div class="dr-m dr-m1"><span>Requested</span><strong>{{ number_format($estimated,2) }}</strong></div>
            <div class="dr-m dr-m2"><span>Paid</span><strong>{{ number_format($paid,2) }}</strong></div>
            @php($__net = $dr->netBalance())
            @php($__ao = $dr->accountOutstanding())
            @php($__co = $dr->companyOutstanding())
            <div class="dr-m {{ $__ao < -0.009 ? 'dr-m3' : 'dr-m2' }}"><span>Account Outstanding</span><strong>{{ $__ao < -0.009 ? '− '.number_format(abs($__ao),2) : ($__ao > 0.009 ? '+ '.number_format($__ao,2) : '✔ 0.00') }}</strong></div>
            <div class="dr-m {{ $__co < -0.009 ? 'dr-m3' : 'dr-m2' }}"><span>Company Outstanding</span><strong>{{ $__co < -0.009 ? '− '.number_format(abs($__co),2) : ($__co > 0.009 ? '+ '.number_format($__co,2) : '✔ 0.00') }}</strong></div>
            <div class="dr-m {{ $__net < -0.009 ? 'dr-m3' : 'dr-m2' }}"><span>Total Outstanding</span><strong>{{ $__net < -0.009 ? '− '.number_format(abs($__net),2) : ($__net > 0.009 ? '+ '.number_format($__net,2) : '✔ 0.00') }}</strong></div>
        </div>
        <div class="dr-prog"><i style="width:{{ $pct }}%"></i></div>
        <div class="dr-prog-txt">{{ $pct }}% covered @if($outstanding>0)· {{ number_format($outstanding,2) }} remaining @else· fully settled ✔@endif @if($writeOff>0)· <span style="color:#15803d">{{ number_format($writeOff,2) }} settled directly by company</span>@endif</div>
        @if($dr->vatAmount() > 0.009)
        <div style="margin-top:.6rem;font-size:.78rem;color:#475569">Subtotal <strong>{{ number_format($dr->subtotalExVat(),2) }}</strong> &nbsp;·&nbsp; VAT <strong>{{ number_format($dr->vatAmount(),2) }}</strong> &nbsp;·&nbsp; Grand Total <strong style="color:var(--primary-purple)">{{ number_format($dr->grandTotal(),2) }}</strong></div>
        @endif
        @if((float) $dr->cash_in_hand_used > 0.009)
        <div style="margin-top:.6rem;display:inline-flex;align-items:center;gap:.5rem;padding:.4rem .8rem;border-radius:9px;background:#ecfdf3;border:1px solid #bbf7d0;font-size:.78rem;color:#166534">
            <i class="fas fa-wallet"></i> Adjusted from Cash in Hand: <strong>{{ number_format((float) $dr->cash_in_hand_used, 2) }}</strong>{{ $dr->cash_in_hand_note ? ' — '.$dr->cash_in_hand_note : '' }}
        </div>
        @endif
    </div>

    {{-- Items with per-item paid / remaining --}}
    <div class="dr-card">
        <div class="dr-secttl"><i class="fas fa-list-ul"></i> Items / Materials</div>
        <div class="dr-table-wrap">
        <table class="dr-table">
            <thead><tr><th>#</th><th>Category</th><th>Job#</th><th>Vendor</th><th>Vendor Inv#</th><th>Description</th><th>Specification</th><th>Qty</th><th class="dr-num">Unit Price</th><th class="dr-num">VAT %</th><th class="dr-num">Requested</th><th class="dr-num">Paid</th><th class="dr-num">Remaining</th><th>Proforma Invoice</th></tr></thead>
            <tbody>
            @foreach($dr->items as $it)
                @php($ip = $dr->paidForItem($it->id))
                @php($inet = $dr->itemNet($it->id))
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $it->category ?: '—' }}</td>
                    <td>{{ $it->job_no ?: '—' }}</td>
                    <td>{{ $it->vendor_name ?: '—' }}</td>
                    <td>{{ $it->vendor_invoice_no ?: '—' }}</td>
                    <td>{{ $it->description ?: '—' }}</td>
                    <td>{{ $it->specification ?: '—' }}</td>
                    <td>{{ $it->qty ?: '—' }}</td>
                    <td class="dr-num">{{ $it->estimated_price !== null ? number_format($it->estimated_price,2) : '—' }}</td>
                    <td class="dr-num">{{ (float)$it->vat_percentage > 0 ? rtrim(rtrim(number_format($it->vat_percentage,2,'.',''),'0'),'.').'%' : '—' }}</td>
                    <td class="dr-num">{{ number_format($it->estimated_total,2) }}</td>
                    <td class="dr-num" style="color:#159447;font-weight:700">{{ $ip ? number_format($ip,2) : '—' }}</td>
                    <td class="dr-num" style="font-weight:800;color:{{ $inet < -0.009 ? '#e11d48' : '#159447' }}">{{ $inet < -0.009 ? '− '.number_format(abs($inet),2) : ($inet > 0.009 ? '+ '.number_format($inet,2) : '✔') }}</td>
                    <td style="vertical-align:middle;white-space:nowrap">@if($it->files->count())@foreach($it->files as $__k => $f)<a href="{{ $f->url }}" target="_blank" title="{{ $f->name }}" style="color:var(--primary-purple);text-decoration:none;margin-right:.4rem"><i class="fas fa-paperclip"></i>{{ $it->files->count()>1 ? ($__k+1) : '' }}</a>@endforeach@else<span style="color:#cbd5e1">—</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>

    {{-- Record payments: one row per item, submit all at once --}}
    @if(in_array($dr->status,['Approved','Partially Paid']))
    <div class="dr-card">
        <div class="dr-secttl"><i class="fas fa-plus-circle"></i> Record Payments
            @php($__pay = $dr->paymentStatus())
            @if($__pay)<span style="text-transform:none;margin-left:.4rem;padding:.2rem .55rem;border-radius:999px;font-size:.66rem;font-weight:850;{{ $__pay === 'Paid' ? 'background:#dcfce7;color:#166534' : ($__pay === 'Partial' ? 'background:#fef3c7;color:#b45309' : 'background:#fee2e2;color:#b91c1c') }}">{{ $__pay }}</span>@endif
            @if($outstanding>0)<span style="text-transform:none;color:#e11d48;font-weight:800">({{ number_format($outstanding,2) }} remaining)</span>@endif</div>
        @if((float) $dr->cash_in_hand_used > 0.009)
        <div style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;padding:.6rem .9rem;margin-bottom:.85rem;border-radius:11px;border:1px solid #bbf7d0;background:#ecfdf3;font-size:.78rem">
            <i class="fas fa-wallet" style="color:#159447"></i>
            <span style="font-weight:800;color:#475569">Adjusted from Cash in Hand:</span>
            <strong style="color:#159447;font-size:.95rem">{{ number_format((float) $dr->cash_in_hand_used, 2) }}</strong>
            @if($dr->cash_in_hand_note)<span style="color:#8290a3">— {{ $dr->cash_in_hand_note }}</span>@endif
            <span style="color:#8290a3">· record the remaining amount below.</span>
        </div>
        @endif
        @if($errors->has('proof'))<div style="margin-bottom:.8rem;padding:.7rem 1rem;border:1px solid #fecaca;border-radius:10px;background:#fff5f5;color:#b91c1c;font-size:.78rem;font-weight:700"><i class="fas fa-exclamation-triangle"></i> {{ $errors->first('proof') }}</div>@endif
        <form method="POST" action="{{ route('crm.demand_requests.add_payments',$dr->id) }}" enctype="multipart/form-data" onsubmit="return drCheckProof(this)">
            {{ csrf_field() }}
            <datalist id="drPayers">@foreach($payers as $p)<option value="{{ $p }}">@endforeach<option value="Direct Payment"></datalist>
            <datalist id="drVendors">@foreach($vendors as $v)<option value="{{ $v }}">@endforeach</datalist>
            <datalist id="drCatsPay">@foreach(($categories ?? []) as $c)<option value="{{ $c }}">@endforeach</datalist>
            <div class="dr-table-wrap">
            <table class="dr-table dr-pay-table">
                <thead><tr><th style="min-width:160px">Item</th><th style="min-width:100px">Amount</th><th style="min-width:130px">Paid By</th><th style="min-width:120px">Source</th><th style="min-width:120px">Paid To</th><th style="min-width:120px">Vendor Inv#</th><th style="min-width:110px">Note</th><th style="min-width:120px">Proof <span style="color:#e11d48">*</span></th></tr></thead>
                <tbody>
                @php($__anyOpen = false)
                @foreach($dr->items as $i => $it)
                    @php($__rem = $dr->itemRemaining($it->id))
                    @if($__rem > 0.009)
                    @php($__anyOpen = true)
                    <tr>
                        <td><div style="font-weight:700;color:#27364b">{{ $loop->iteration }}. {{ \Illuminate\Support\Str::limit($it->description ?: $it->category, 24) }}</div><div style="font-size:.67rem;color:#e11d48">{{ number_format($__rem,2) }} left</div><input type="hidden" name="rows[{{ $i }}][item_id]" value="{{ $it->id }}"></td>
                        <td><input class="dr-control" type="number" step="0.01" min="0" name="rows[{{ $i }}][amount]" placeholder="0.00"></td>
                        <td><select class="dr-control" name="rows[{{ $i }}][pay_type]"><option value="Account">By Accountant</option><option value="Direct">Direct Company</option></select></td>
                        <td><input class="dr-control" list="drPayers" name="rows[{{ $i }}][method]" placeholder="Cash / Bank"></td>
                        <td><input class="dr-control" list="drVendors" name="rows[{{ $i }}][paid_to]" placeholder="Vendor / person"></td>
                        <td><input class="dr-control" name="rows[{{ $i }}][vendor_invoice_no]" placeholder="Invoice #"></td>
                        <td><input class="dr-control" name="rows[{{ $i }}][note]" placeholder="Optional"></td>
                        <td><input class="dr-control" type="file" name="rows[{{ $i }}][proofs][]" multiple data-max="5" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv" style="padding:.28rem;font-size:.68rem"></td>
                    </tr>
                    @endif
                @endforeach
                @if(!$__anyOpen)<tr id="drNoOpen"><td colspan="8"><div class="dr-empty" style="padding:1rem;color:#159447;font-weight:700">All items settled ✔ — use “Add breakdown row” below to log any extra expense.</div></td></tr>@endif
                </tbody>
            </table>
            </div>
            <div style="margin-top:.7rem;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
                <button type="button" class="dr-btn dr-btn-light" onclick="drAddPayRow()" style="border:1px dashed #c7b8f5;color:var(--primary-purple);background:var(--primary-soft)"><i class="fas fa-plus"></i> Add breakdown row</button>
            </div>
            <div style="margin-top:.9rem"><button class="dr-btn dr-btn-primary" type="submit"><i class="fas fa-check"></i> Save Payments</button></div>
        </form>
        <script>
        var drItems = [@foreach($dr->items as $it){id:{{ $it->id }},label:"{{ addslashes($loop->iteration.'. '.\Illuminate\Support\Str::limit($it->description ?: $it->category, 24)) }}"},@endforeach];
        var drNewIdx = 500000;
        function drAddPayRow(){
            var no = document.getElementById('drNoOpen'); if (no) no.remove();
            var tb = document.querySelector('table.dr-pay-table tbody');
            var i = drNewIdx++;
            var opts = '<option value="">— Against (item) —</option>';
            for (var k=0;k<drItems.length;k++){ opts += '<option value="'+drItems[k].id+'">'+drItems[k].label+'</option>'; }
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input class="dr-control" list="drCatsPay" name="rows['+i+'][category]" placeholder="Category (type or pick)" style="margin-bottom:.25rem">'+
                '<select class="dr-control" name="rows['+i+'][item_id]">'+opts+'</select>'+
                '<button type="button" class="dr-btn dr-btn-light" onclick="this.closest(\'tr\').remove()" style="margin-top:.25rem;padding:.2rem .5rem;min-height:0;font-size:.66rem;color:#e11d48;background:#fff1f2"><i class="fas fa-trash"></i> Remove</button></td>'+
                '<td><input class="dr-control" type="number" step="0.01" min="0" name="rows['+i+'][amount]" placeholder="0.00"></td>'+
                '<td><select class="dr-control" name="rows['+i+'][pay_type]"><option value="Account">By Accountant</option><option value="Direct">Direct Company</option></select></td>'+
                '<td><input class="dr-control" list="drPayers" name="rows['+i+'][method]" placeholder="Cash / Bank"></td>'+
                '<td><input class="dr-control" list="drVendors" name="rows['+i+'][paid_to]" placeholder="Vendor / person"></td>'+
                '<td><input class="dr-control" name="rows['+i+'][vendor_invoice_no]" placeholder="Invoice #"></td>'+
                '<td><input class="dr-control" name="rows['+i+'][note]" placeholder="Optional"></td>'+
                '<td><input class="dr-control" type="file" name="rows['+i+'][proofs][]" multiple data-max="5" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv" style="padding:.28rem;font-size:.68rem"></td>';
            tb.appendChild(tr);
        }
        function drCheckProof(form){
            var rows = form.querySelectorAll('table.dr-pay-table tbody tr');
            for (var r=0; r<rows.length; r++){
                var amt = rows[r].querySelector('input[type=number]');
                var file = rows[r].querySelector('input[type=file]');
                if (amt && file && parseFloat(amt.value||0) > 0 && file.files.length === 0){
                    alert('Proof attachment is required for every payment you enter. Please attach a file for the amounts you added.');
                    file.focus();
                    return false;
                }
            }
            return true;
        }
        </script>
    </div>
    @endif

    {{-- Payment history --}}
    <div class="dr-card">
        <div class="dr-secttl"><i class="fas fa-receipt"></i> Payment History &amp; Breakdown</div>

        <div class="dr-table-wrap">
        <table class="dr-table">
            <thead><tr><th>Date</th><th class="dr-num">Amount</th><th>Type / Source</th><th>Against</th><th>Paid To</th><th>Vendor Inv#</th><th>Note</th><th>Proof</th><th></th></tr></thead>
            <tbody>
            @forelse($dr->payments as $pmt)
                <tr>
                    <td>{{ optional($pmt->paid_at)->format('d M Y') }}</td>
                    <td class="dr-num"><strong style="color:#159447">{{ number_format($pmt->amount,2) }}</strong></td>
                    <td>@if(($pmt->pay_type ?? 'Account')==='Direct')<span style="background:#dcfce7;color:#15803d;padding:.1rem .45rem;border-radius:7px;font-size:.64rem;font-weight:800">DIRECT</span>@else<span style="background:#e0f2fe;color:#0369a1;padding:.1rem .45rem;border-radius:7px;font-size:.64rem;font-weight:800">ACCOUNT</span>@endif{{ $pmt->method ? ' · '.$pmt->method : '' }}</td>
                    <td>
                        @if($pmt->category)<span class="dr-tag" style="background:#eef2ff;color:#4338ca">{{ $pmt->category }}</span> @endif
                        @if($pmt->item_id)<span class="dr-tag">{{ \Illuminate\Support\Str::limit(optional($dr->items->firstWhere('id',$pmt->item_id))->description ?: 'Item', 24) }}</span>@elseif(!$pmt->category)<span class="dr-tag dr-tag-gen">—</span>@endif
                    </td>
                    <td>{{ $pmt->paid_to ?: '—' }}</td>
                    <td>{{ $pmt->vendor_invoice_no ?: '—' }}</td>
                    <td>{{ $pmt->note ?: '—' }}</td>
                    <td>@php($__proofs = $pmt->allProofs())@if(count($__proofs))<span style="display:inline-flex;gap:.35rem;flex-wrap:wrap">@foreach($__proofs as $__k => $__pf)<a href="{{ $__pf['url'] }}" target="_blank" title="{{ $__pf['name'] }}" style="color:var(--primary-purple);text-decoration:none"><i class="fas fa-paperclip"></i>{{ count($__proofs)>1 ? ($__k+1) : '' }}</a>@endforeach</span>@else<span style="color:#cbd5e1">—</span>@endif</td>
                    <td>
                        <form method="POST" action="{{ route('crm.demand_requests.delete_payment',[$dr->id,$pmt->id]) }}" onsubmit="return confirm('Remove this payment?');">
                            {{ csrf_field() }} {{ method_field('DELETE') }}
                            <button class="dr-del" type="submit" title="Remove"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="dr-empty">No payments recorded yet.</div></td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        @if(count($payerSummary))
        <div style="margin-top:.9rem;display:flex;flex-wrap:wrap;gap:.5rem">
            @foreach($payerSummary as $src => $amt)
                <span class="dr-tag" style="padding:.4rem .7rem;font-size:.74rem"><i class="fas fa-wallet"></i> {{ $src }}: <strong>{{ number_format($amt,2) }}</strong></span>
            @endforeach
        </div>
        @endif
        <div style="display:flex;flex-wrap:wrap;gap:.7rem;margin-top:1rem;padding-top:1rem;border-top:1px solid #eef2f7">
            <div style="flex:1;min-width:180px;padding:.7rem .95rem;border-radius:12px;background:#e0f2fe"><div style="font-size:.62rem;font-weight:850;text-transform:uppercase;letter-spacing:.03em;color:#0369a1"><i class="fas fa-university"></i> Account — to reconcile</div><div style="font-size:1.25rem;font-weight:850;color:#0369a1;margin-top:.15rem">{{ number_format($dr->accountTotal(),2) }}</div></div>
            <div style="flex:1;min-width:180px;padding:.7rem .95rem;border-radius:12px;background:#dcfce7"><div style="font-size:.62rem;font-weight:850;text-transform:uppercase;letter-spacing:.03em;color:#15803d"><i class="fas fa-building"></i> Company — direct paid</div><div style="font-size:1.25rem;font-weight:850;color:#15803d;margin-top:.15rem">{{ number_format($dr->directTotal(),2) }}</div></div>
        </div>
    </div>

    {{-- Attachments --}}
    @php($__fileUser = Auth::guard('crm')->user())
    @php($__canManageFiles = $__fileUser && ($__fileUser->isAdmin() || $__fileUser->isSuperAdmin()))
    <div class="dr-card">
        <div class="dr-secttl"><i class="fas fa-paperclip"></i> Attachments</div>
        @if($__canManageFiles)
        <form method="POST" action="{{ route('crm.demand_requests.add_attachment',$dr->id) }}" enctype="multipart/form-data" style="margin-bottom:.8rem">
            {{ csrf_field() }}
            <div id="drAttRows"></div>
            <div style="display:flex;gap:.6rem;margin-top:.5rem">
                <button type="button" class="dr-btn dr-btn-light dr-btn-sm" onclick="drAddAttRow()" style="border:1px dashed #c7b8f5;color:var(--primary-purple);background:var(--primary-soft)"><i class="fas fa-plus"></i> Add row</button>
                <button class="dr-btn dr-btn-outline dr-btn-sm" type="submit"><i class="fas fa-upload"></i> Upload</button>
            </div>
        </form>
        <script>
            var drAttI = 0;
            function drAttRowHtml(i){
                var lab='font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase';
                return '<div class="dr-att-row" style="display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:.5rem">'
                    +'<div style="display:flex;flex-direction:column;gap:.25rem;flex:1;min-width:180px"><label style="'+lab+'">Note</label><input class="dr-control" name="entries['+i+'][note]" maxlength="255" placeholder="e.g. cash paid to vendor"></div>'
                    +'<div style="display:flex;flex-direction:column;gap:.25rem"><label style="'+lab+'">Amount</label><input class="dr-control" type="number" step="0.01" min="0" name="entries['+i+'][amount]" placeholder="0.00" style="width:130px"></div>'
                    +'<div style="display:flex;flex-direction:column;gap:.25rem"><label style="'+lab+'">File</label><input class="dr-control" type="file" name="entries['+i+'][file]" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv" style="max-width:300px"></div>'
                    +'<button type="button" class="dr-att-x" title="Remove row" onclick="drRemoveAttRow(this)"><i class="fas fa-times"></i></button>'
                    +'</div>';
            }
            function drAddAttRow(){ var w=document.getElementById('drAttRows'); var d=document.createElement('div'); d.innerHTML=drAttRowHtml(drAttI); w.appendChild(d.firstChild); drAttI++; drAttRowVis(); }
            function drRemoveAttRow(btn){ btn.closest('.dr-att-row').remove(); if(!document.querySelectorAll('#drAttRows .dr-att-row').length) drAddAttRow(); drAttRowVis(); }
            function drAttRowVis(){ var rows=document.querySelectorAll('#drAttRows .dr-att-row'); rows.forEach(function(r){ r.querySelector('.dr-att-x').style.visibility = rows.length>1?'visible':'hidden'; }); }
            drAddAttRow(); // show one row on load
        </script>
        @endif
        @if($dr->attachments->count())
            <div style="overflow-x:auto;border:1px solid #e5ebf2;border-radius:12px">
            <table style="width:100%;border-collapse:collapse;min-width:560px;font-size:.83rem">
                <thead><tr style="background:#f7f9fc;color:#94a3b8;font-size:.62rem;font-weight:850;text-transform:uppercase;letter-spacing:.04em;text-align:left">
                    <th style="padding:.55rem .8rem">File</th>
                    <th style="padding:.55rem .8rem">Note</th>
                    <th style="padding:.55rem .8rem;text-align:right">Amount</th>
                    <th style="padding:.55rem .8rem;text-align:right;width:60px">Action</th>
                </tr></thead>
                <tbody>
                @foreach($dr->attachments as $att)
                    <tr style="border-top:1px solid #eef1f6">
                        <td style="padding:.6rem .8rem"><a href="{{ $att->url }}" target="_blank" style="color:var(--primary-purple);font-weight:700"><i class="fas {{ $att->is_image ? 'fa-image' : 'fa-file-alt' }}"></i> {{ \Illuminate\Support\Str::limit($att->name ?: 'file', 34) }}</a></td>
                        <td style="padding:.6rem .8rem;color:#334155">{{ $att->note ?: '—' }}</td>
                        <td style="padding:.6rem .8rem;text-align:right;font-weight:800;color:{{ $att->amount !== null ? '#159447' : '#cbd5e1' }}">{{ $att->amount !== null ? number_format((float)$att->amount,2) : '—' }}</td>
                        <td style="padding:.6rem .8rem;text-align:right">
                            @if($__canManageFiles)
                            <form method="POST" action="{{ route('crm.demand_requests.delete_attachment',[$dr->id,$att->id]) }}" onsubmit="return confirm('Remove attachment?');" style="display:inline;margin:0">{{ csrf_field() }}{{ method_field('DELETE') }}<button class="dr-att-x" type="submit" title="Remove"><i class="fas fa-times"></i></button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        @else
            <div class="dr-empty">No attachments yet.</div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="dr-card">
        <div class="dr-actbar">
            <a class="dr-btn dr-btn-outline" href="{{ route('crm.demand_requests.edit',$dr->id) }}"><i class="fas fa-pen"></i> Edit</a>
            <form method="POST" action="{{ route('crm.demand_requests.destroy',$dr->id) }}" style="display:inline" onsubmit="return confirm('Delete this demand request permanently?');">{{ csrf_field() }}{{ method_field('DELETE') }}<button class="dr-btn dr-btn-red" type="submit"><i class="fas fa-trash"></i> Delete</button></form>
            @if($canApprove && $dr->status==='Submitted')
                <a class="dr-btn dr-btn-green" href="{{ route('crm.demand_requests.edit',$dr->id) }}"><i class="fas fa-check"></i> Review &amp; Approve</a>
                <button class="dr-btn dr-btn-red" type="button" onclick="document.getElementById('drReject').style.display='block'"><i class="fas fa-times"></i> Reject</button>
            @endif
            <a class="dr-btn dr-btn-light" href="{{ route('crm.demand_requests.pdf',$dr->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> Print PDF</a>
            @if(in_array($dr->status,['Approved','Partially Paid']) && !$dr->force_completed)
                <form method="POST" action="{{ route('crm.demand_requests.complete',$dr->id) }}" style="display:inline" onsubmit="return confirm('Mark this demand complete (close it)?');">{{ csrf_field() }}<button class="dr-btn dr-btn-green" type="submit"><i class="fas fa-flag-checkered"></i> Mark Complete</button></form>
            @endif
            @if($dr->status==='Completed' || $dr->force_completed)
                <form method="POST" action="{{ route('crm.demand_requests.reopen',$dr->id) }}" style="display:inline" onsubmit="return confirm('Reopen this demand so payments can resume?');">{{ csrf_field() }}<button class="dr-btn dr-btn-outline" type="submit"><i class="fas fa-undo"></i> Reopen</button></form>
            @endif
        </div>
        @if($canApprove && $dr->status==='Submitted')
        <div class="dr-reject" id="drReject">
            <form method="POST" action="{{ route('crm.demand_requests.reject',$dr->id) }}">{{ csrf_field() }}
                <label style="font-size:.74rem;font-weight:780;color:#b91c1c;display:block;margin-bottom:.35rem">Rejection reason (optional)</label>
                <textarea class="dr-control" name="rejection_reason" rows="2" placeholder="Why is this rejected?"></textarea>
                <div style="margin-top:.6rem"><button class="dr-btn dr-btn-red" type="submit"><i class="fas fa-times"></i> Confirm Reject</button></div>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
