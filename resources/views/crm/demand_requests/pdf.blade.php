@php
    $primary = $brand['primary'];
    $accent  = $brand['accent'];
    $rowCol  = $brand['row'];
    $money = fn ($a) => number_format((float) $a, 2, '.', ',');
    $sign = function ($v) use ($money) {
        if ($v < -0.009) return ['&minus; ' . $money(abs($v)), '#b91c1c'];
        if ($v > 0.009)  return ['+ ' . $money($v), '#15803d'];
        return ['0.00', '#15803d'];
    };
    $acc = $dr->accountOutstanding(); $co = $dr->companyOutstanding(); $net = $dr->netBalance();
    $accF = $sign($acc); $coF = $sign($co); $netF = $sign($net);
    $reqNo = str_pad($dr->request_no, 3, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demand Request #{{ $reqNo }} — {{ $company['name'] }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { background: #eef1f7; color: #202020; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { width: 210mm; max-width: calc(100% - 24px); margin: 18px auto 12px; display: flex; justify-content: space-between; gap: 10px; }
        .toolbar-group { display: flex; gap: 8px; }
        .toolbar a, .toolbar button { border: 1px solid #d8dee9; border-radius: 8px; background: #fff; color: #334155; padding: 9px 14px; text-decoration: none; font-size: 13px; font-weight: 700; cursor: pointer; }
        .toolbar .primary { background: {{ $primary }}; color: #fff; border-color: {{ $primary }}; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 16mm 15mm 14mm; background: #fff; box-shadow: 0 5px 24px rgba(15,23,42,.12); position: relative; }

        .header { display: grid; grid-template-columns: 45mm 1fr 45mm; align-items: start; column-gap: 6mm; }
        .logo { width: 40mm; height: 40mm; object-fit: contain; }
        .company-arabic { color: {{ $primary }}; font-size: 15px; font-weight: 800; margin: 2px 0 3px; direction: rtl; text-align: left; }
        .company-name { color: {{ $primary }}; font-size: 15.5px; line-height: 1.25; font-weight: 800; }
        .company-tagline { color: {{ $primary }}; font-size: 10.5px; margin-top: 5px; }
        .company-line { color: {{ $primary }}; font-size: 10px; line-height: 1.45; margin-top: 4px; }
        .doc-word { color: {{ $accent }}; font-size: 24px; font-weight: 900; letter-spacing: 1px; margin-top: 30mm; text-align: right; }
        .doc-sub { color: {{ $accent }}; font-size: 11px; font-weight: 700; text-align: right; }

        .header-rule { height: 2px; background: #aaa; margin: 8px 0 14px; position: relative; }
        .header-rule::before { content: ''; position: absolute; left: 0; top: 0; width: 16mm; height: 3px; border-radius: 3px; background: {{ $primary }}; }

        .details { display: grid; grid-template-columns: 1fr 66mm; column-gap: 14mm; }
        .req-to { font-size: 13px; margin-bottom: 8px; }
        .req-name { font-size: 17px; font-weight: 800; margin-bottom: 6px; text-transform: uppercase; }
        .req-line { color: #606060; font-size: 12px; line-height: 1.5; }
        .meta-row { display: grid; grid-template-columns: auto 1fr; gap: 6px; font-size: 12.5px; line-height: 1.55; }
        .meta-label { font-weight: 800; }
        .status-pill { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 11px; font-weight: 800; color: {{ $primary }}; background: {{ $rowCol }}; }

        .items { width: 100%; border-collapse: collapse; margin-top: 16px; table-layout: fixed; }
        .items th { background: {{ $primary }}; color: #fff; padding: 7px 6px; font-size: 11px; font-weight: 800; text-align: center; }
        .items td { background: {{ $rowCol }}; border-right: 2px solid #fff; padding: 6px 8px; font-size: 11.5px; vertical-align: top; }
        .items td:last-child { border-right: 0; }
        .items .center { text-align: center; } .items .right { text-align: right; }
        .items tr.total-bar td { background: {{ $primary }}; color: #fff; font-weight: 800; font-size: 12.5px; border-right-color: {{ $primary }}; }

        .sec { margin: 20px 0 8px; font-size: 12px; font-weight: 800; color: {{ $primary }}; text-transform: uppercase; letter-spacing: .5px; }

        .stats { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin-top: 2px; }
        .stats td { padding: 11px 6px; text-align: center; border-radius: 8px; border: 1px solid #e7ebf1; }
        .stats .lbl { font-size: 8.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .4px; color: #6b7688; }
        .stats .val { font-size: 15px; font-weight: 800; padding-top: 4px; }

        .recon { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin-top: 10px; }
        .recon td { padding: 9px 12px; border-radius: 8px; font-size: 12px; }

        .pay { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .pay th { background: #f1f4f9; color: #45506a; padding: 7px 8px; font-size: 10px; text-align: left; text-transform: uppercase; letter-spacing: .3px; border-bottom: 1px solid #e2e7ef; }
        .pay td { border-bottom: 1px solid #eef1f5; padding: 6px 8px; font-size: 11px; }
        .badge { padding: 2px 8px; border-radius: 8px; font-size: 9px; font-weight: 800; }
        .b-d { background: #e9f9ef; color: #15803d; } .b-a { background: #e6f2ff; color: #1d6fd6; } .b-g { background: #eef1f6; color: #64748b; }

        .signoff { margin-top: 24mm; }
        .signatory { text-align: right; margin-right: 3mm; }
        .signatory-name { font-size: 18px; font-weight: 800; color: #202020; }
        .signatory-title { font-size: 13px; margin-top: 4px; }
        .thanks { margin-top: 12mm; text-align: center; font-size: 13px; font-weight: 800; }
        .footer-rule { height: 2px; margin-top: 8px; background: #aaa; position: relative; }
        .footer-rule::before, .footer-rule::after { content: ''; position: absolute; top: 0; width: 16mm; height: 3px; border-radius: 3px; background: {{ $primary }}; }
        .footer-rule::before { left: 0; } .footer-rule::after { right: 0; }

        @media (max-width: 820px) {
            .page { width: 100%; min-height: auto; padding: 22px; }
            .header { grid-template-columns: 90px 1fr; }
            .doc-word, .doc-sub { grid-column: 1 / -1; margin-top: 10px; text-align: left; }
            .details { grid-template-columns: 1fr; row-gap: 16px; }
        }
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: #fff; }
            .toolbar { display: none; }
            .page { margin: 0; box-shadow: none; width: 210mm; min-height: 297mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('crm.demand_requests.show', $dr->id) }}">&#8592; Back</a>
        <div class="toolbar-group">
            <button type="button" onclick="window.print()">Print</button>
            <button type="button" class="primary" id="dlBtn" onclick="downloadPdf(this)">&#8681; Download PDF</button>
        </div>
    </div>

    <main class="page">
        <header class="header">
            <img class="logo" src="{{ $brand['logo'] }}" alt="{{ $company['name'] }}">
            <div>
                @if($brand['arabic'])<div class="company-arabic">{{ $brand['arabic'] }}</div>@endif
                <div class="company-name">{{ $company['name'] }}</div>
                <div class="company-tagline">{{ $brand['tagline'] }}</div>
                @if($company['address'])<div class="company-line">&#9679;&nbsp; {{ $company['address'] }}</div>@endif
                @if($brand['phone'])<div class="company-line">&#9742;&nbsp; {{ $brand['phone'] }}</div>@endif
                @if($brand['email'])<div class="company-line">&#9993;&nbsp; {{ $brand['email'] }}</div>@endif
            </div>
            <div>
                <div class="doc-word">DEMAND</div>
                <div class="doc-sub">REQUEST</div>
            </div>
        </header>

        <div class="header-rule"></div>

        <section class="details">
            <div>
                <div class="req-to">Requested by :</div>
                <div class="req-name">{{ $dr->requested_by ?: ($dr->creator->name ?? '—') }}</div>
                <div class="req-line">Priority: {{ $dr->priority }}</div>
                @if($dr->approved_at)<div class="req-line">Approved by: {{ $dr->approver->name ?? '—' }} · {{ optional($dr->approved_at)->format('d M Y') }}</div>@endif
                @if($dr->notes)<div class="req-line">{{ $dr->notes }}</div>@endif
            </div>
            <div>
                <div class="meta-row"><span class="meta-label">Request No:</span><span>#{{ $reqNo }}</span></div>
                <div class="meta-row"><span class="meta-label">Date:</span><span>{{ optional($dr->request_date)->format('d F Y') }}</span></div>
                <div class="meta-row"><span class="meta-label">Status:</span><span><span class="status-pill">{{ $dr->status }}</span></span></div>
            </div>
        </section>

        <table class="items">
            <colgroup>
                <col style="width:7%"><col style="width:14%"><col style="width:9%"><col style="width:24%">
                <col style="width:14%"><col style="width:7%"><col style="width:12%"><col style="width:13%">
            </colgroup>
            <thead><tr>
                <th>NO</th><th>CATEGORY</th><th>JOB</th><th>DESCRIPTION</th><th>SPECIFICATION</th><th>QTY</th><th>UNIT PRICE</th><th>TOTAL</th>
            </tr></thead>
            <tbody>
            @foreach($dr->items as $it)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $it->category ?: '—' }}</td>
                    <td class="center">{{ $it->job_no ?: '—' }}</td>
                    <td>{{ $it->description ?: '—' }}</td>
                    <td>{{ $it->specification ?: '—' }}</td>
                    <td class="center">{{ $it->qty ?: '—' }}</td>
                    <td class="right">{{ $it->estimated_price !== null ? $money($it->estimated_price) : '—' }}</td>
                    <td class="right">{{ $money($it->estimated_total) }}</td>
                </tr>
            @endforeach
                <tr class="total-bar">
                    <td colspan="7" class="right">ESTIMATED TOTAL</td>
                    <td class="right">{{ $money($dr->estimated_total) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="sec">Payment Summary</div>
        <table class="stats">
            <tr>
                <td style="background:#eef1fb"><div class="lbl">Requested</div><div class="val" style="color:#3730a3">{{ $money($dr->estimated_total) }}</div></td>
                <td style="background:#e9f9ef"><div class="lbl">Paid</div><div class="val" style="color:#15803d">{{ $money($dr->paidTotal()) }}</div></td>
                <td style="background:#f4f7fb"><div class="lbl">Account Outstanding</div><div class="val" style="color:{{ $accF[1] }}">{!! $accF[0] !!}</div></td>
                <td style="background:#f4f7fb"><div class="lbl">Company Outstanding</div><div class="val" style="color:{{ $coF[1] }}">{!! $coF[0] !!}</div></td>
                <td style="background:#f4f7fb"><div class="lbl">Total Outstanding</div><div class="val" style="color:{{ $netF[1] }}">{!! $netF[0] !!}</div></td>
            </tr>
        </table>
        <table class="recon">
            <tr>
                <td style="background:#e6f2ff"><div class="lbl" style="color:#1d6fd6;font-size:8.5px;font-weight:800;text-transform:uppercase">Account — to reconcile</div><div style="font-size:14px;font-weight:800;color:#1d6fd6;padding-top:2px">{{ $money($dr->accountTotal()) }}</div></td>
                <td style="background:#e9f9ef"><div class="lbl" style="color:#15803d;font-size:8.5px;font-weight:800;text-transform:uppercase">Company — direct paid</div><div style="font-size:14px;font-weight:800;color:#15803d;padding-top:2px">{{ $money($dr->directTotal()) }}</div></td>
            </tr>
        </table>

        @if($dr->payments->count())
        <div class="sec" style="margin-top:16px">Payment History</div>
        <table class="pay">
            <thead><tr><th style="width:13%">Date</th><th class="right" style="width:11%">Amount</th><th style="width:16%">Type / Source</th><th>Against</th><th style="width:14%">Paid To</th><th style="width:15%">Note</th></tr></thead>
            <tbody>
            @foreach($dr->payments as $pmt)
                @php $isD = ($pmt->pay_type ?? 'Account') === 'Direct'; @endphp
                <tr>
                    <td>{{ optional($pmt->paid_at)->format('d M Y') }}</td>
                    <td class="right" style="color:#15803d;font-weight:800">{{ $money($pmt->amount) }}</td>
                    <td><span class="badge {{ $isD ? 'b-d' : 'b-a' }}">{{ $isD ? 'DIRECT' : 'ACCOUNT' }}</span>{{ $pmt->method ? ' · '.$pmt->method : '' }}</td>
                    <td>@if($pmt->item_id)<span class="badge b-g">{{ \Illuminate\Support\Str::limit(optional($dr->items->firstWhere('id',$pmt->item_id))->description ?: 'Item', 22) }}</span>@else<span class="badge b-g">General</span>@endif</td>
                    <td>{{ $pmt->paid_to ?: '—' }}</td>
                    <td>{{ $pmt->note ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif

        <section class="signoff">
            <div class="signatory"><div class="signatory-name">{{ $brand['signatory'] }}</div><div class="signatory-title">Administrator</div></div>
            <div class="thanks">Thank you for business with us!</div>
            <div class="footer-rule"></div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.2/dist/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPdf(btn){
            var el = document.querySelector('.page');
            if (!el || typeof html2pdf === 'undefined') { window.print(); return; }
            var original = btn.innerHTML; btn.innerHTML = 'Generating…'; btn.disabled = true;
            var opt = { margin:0, filename:'Demand-Request-{{ $reqNo }}.pdf', image:{type:'jpeg',quality:0.98},
                html2canvas:{scale:2,useCORS:true,backgroundColor:'#ffffff',scrollY:0}, jsPDF:{unit:'mm',format:'a4',orientation:'portrait'},
                pagebreak:{mode:['avoid-all','css','legacy']} };
            html2pdf().set(opt).from(el).save().then(function(){ btn.innerHTML=original; btn.disabled=false; })
                .catch(function(){ btn.innerHTML=original; btn.disabled=false; window.print(); });
        }
    </script>
</body>
</html>
