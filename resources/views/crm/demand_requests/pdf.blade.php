<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $stColors = [
        'Draft' => ['#64748b','#eef2f7'], 'Submitted' => ['#b45309','#fff7ed'], 'Approved' => ['#0e7490','#e0f7fb'],
        'Rejected' => ['#b91c1c','#fee2e2'], 'Partially Paid' => ['#b45309','#fef3c7'], 'Completed' => ['#15803d','#dcfce7'],
    ];
    $sc = $stColors[$dr->status] ?? ['#334155','#eef2f7'];
@endphp
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    @page { margin: 0; }
    body { margin: 0; color: #2b3648; font-size: 10.5px; }
    .doc { padding: 26px 30px; }

    /* Header */
    .hd { width: 100%; border-collapse: collapse; }
    .hd td { vertical-align: middle; }
    .hd .logo { width: 82px; }
    .hd .logo img { width: 74px; height: auto; }
    .brandbar { background: #16233e; color: #fff; padding: 11px 14px; }
    .brandbar .ttl { font-size: 17px; font-weight: bold; letter-spacing: 1px; }
    .brandbar .co { font-size: 10px; color: #c7d0e0; margin-top: 2px; }
    .rno { width: 92px; text-align: center; }
    .rno .n { font-size: 15px; font-weight: bold; color: #16233e; }
    .rno .l { font-size: 7.5px; color: #7a8699; text-transform: uppercase; letter-spacing: .5px; }
    .addr { background: #c99a3d; color: #fff; font-size: 8.5px; text-align: center; padding: 5px 10px; }
    .stpill { display: inline-block; padding: 3px 12px; border-radius: 10px; font-size: 9px; font-weight: bold; color: {{ $sc[0] }}; background: {{ $sc[1] }}; }

    /* Meta */
    .meta { width: 100%; border-collapse: collapse; margin-top: 14px; }
    .meta td { border: 1px solid #dde2ea; padding: 7px 10px; font-size: 10px; }
    .meta .k { background: #f5f6f8; font-weight: bold; color: #4a5568; width: 15%; }

    /* Section heading */
    .sec { margin: 16px 0 7px; font-size: 9.5px; font-weight: bold; color: #16233e; text-transform: uppercase; letter-spacing: .6px; border-bottom: 2px solid #16233e; padding-bottom: 4px; }

    /* Items */
    table.items { width: 100%; border-collapse: collapse; }
    table.items th { background: #16233e; color: #fff; padding: 7px 6px; font-size: 8.5px; text-align: left; }
    table.items td { border-bottom: 1px solid #e6e9ef; padding: 6px; font-size: 9.5px; }
    table.items tr:nth-child(even) td { background: #f8f9fb; }
    .r { text-align: right; } .c { text-align: center; }
    .grand td { background: #16233e !important; color: #fff; font-weight: bold; font-size: 11px; padding: 8px 6px; }

    /* Money summary boxes */
    .money { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-top: 4px; }
    .money td { width: 33%; padding: 10px; text-align: center; border-radius: 8px; }
    .m1 { background: #eef1fb; } .m2 { background: #dcfce7; } .m3 { background: #fee2e2; }
    .money .lbl { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }
    .money .val { font-size: 16px; font-weight: bold; padding-top: 3px; }
    .m1 .lbl, .m1 .val { color: #3f3fa3; } .m2 .lbl, .m2 .val { color: #15803d; } .m3 .lbl, .m3 .val { color: #b91c1c; }

    /* Payments */
    table.pay { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.pay th { background: #f0f2f6; color: #4a5568; padding: 6px; font-size: 8px; text-align: left; border-bottom: 1px solid #dde2ea; }
    table.pay td { border-bottom: 1px solid #eef0f4; padding: 5px 6px; font-size: 9px; }
    .tag { background: #eef2ff; color: #4338ca; padding: 1px 6px; border-radius: 6px; font-size: 8px; }
    .tag-gen { background: #eef2f7; color: #64748b; }

    /* Signatures */
    .sign { width: 100%; border-collapse: collapse; margin-top: 46px; }
    .sign td { width: 33%; padding: 0 16px; text-align: center; font-size: 9px; color: #4a5568; }
    .sign .line { border-top: 1px solid #333; padding-top: 5px; }
    .sign .who { color: #7a8699; font-size: 8px; }
    .foot { margin-top: 22px; text-align: center; color: #9aa4b2; font-size: 7.5px; border-top: 1px solid #e6e9ef; padding-top: 6px; }
</style>
</head>
<body>
<div class="doc">
    {{-- Header --}}
    <table class="hd">
        <tr>
            <td class="logo">@if($company['logo_path'])<img src="{{ $company['logo_path'] }}">@endif</td>
            <td class="brandbar">
                <div class="ttl">DEMAND REQUEST</div>
                <div class="co">{{ $company['name'] }}</div>
            </td>
            <td class="rno">
                <div class="l">Request No.</div>
                <div class="n">#{{ str_pad($dr->request_no,3,'0',STR_PAD_LEFT) }}</div>
                <div style="margin-top:4px"><span class="stpill">{{ $dr->status }}</span></div>
            </td>
        </tr>
    </table>
    @if($company['address'])<div class="addr">{{ $company['address'] }}</div>@endif

    {{-- Meta --}}
    <table class="meta">
        <tr>
            <td class="k">Request Date</td><td>{{ optional($dr->request_date)->format('d M Y') }}</td>
            <td class="k">Requested By</td><td>{{ $dr->requested_by ?: ($dr->creator->name ?? '—') }}</td>
        </tr>
        <tr>
            <td class="k">Priority</td><td>{{ $dr->priority }}</td>
            <td class="k">Approved By</td><td>{{ $dr->approver->name ?? '—' }}{{ $dr->approved_at ? ' · '.optional($dr->approved_at)->format('d M Y') : '' }}</td>
        </tr>
    </table>

    {{-- Items --}}
    <div class="sec">Items / Materials</div>
    <table class="items">
        <thead><tr>
            <th class="c" style="width:24px">Sr</th>
            <th style="width:14%">Category</th>
            <th style="width:10%">Job No#</th>
            <th>Item / Material Description</th>
            <th style="width:16%">Specification</th>
            <th class="c" style="width:8%">Qty</th>
            <th class="r" style="width:11%">Est. Price</th>
            <th class="r" style="width:12%">Total</th>
        </tr></thead>
        <tbody>
        @foreach($dr->items as $it)
            <tr>
                <td class="c">{{ $loop->iteration }}</td>
                <td>{{ $it->category ?: '—' }}</td>
                <td>{{ $it->job_no ?: 'N/A' }}</td>
                <td>{{ $it->description ?: '—' }}</td>
                <td>{{ $it->specification ?: '—' }}</td>
                <td class="c">{{ $it->qty ?: 'N/A' }}</td>
                <td class="r">{{ $it->estimated_price !== null ? number_format($it->estimated_price,2) : 'N/A' }}</td>
                <td class="r">{{ number_format($it->estimated_total,2) }}</td>
            </tr>
        @endforeach
            <tr class="grand">
                <td colspan="7" class="r">ESTIMATED TOTAL</td>
                <td class="r">{{ number_format($dr->estimated_total,2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Money summary --}}
    <div class="sec">Payment Summary</div>
    <table class="money">
        <tr>
            <td class="m1"><div class="lbl">Requested</div><div class="val">{{ number_format($dr->estimated_total,2) }}</div></td>
            <td class="m2"><div class="lbl">Paid</div><div class="val">{{ number_format($paid,2) }}</div></td>
            <td class="m3"><div class="lbl">Outstanding</div><div class="val">{{ number_format($outstanding,2) }}</div></td>
        </tr>
    </table>

    {{-- Payment history --}}
    @if($dr->payments->count())
    <table class="pay">
        <thead><tr><th>Date</th><th class="r">Amount</th><th>Source</th><th>Against</th><th>Paid To</th><th>Note</th></tr></thead>
        <tbody>
        @foreach($dr->payments as $pmt)
            <tr>
                <td>{{ optional($pmt->paid_at)->format('d M Y') }}</td>
                <td class="r" style="color:#15803d;font-weight:bold">{{ number_format($pmt->amount,2) }}</td>
                <td>{{ $pmt->method ?: '—' }}</td>
                <td>@if($pmt->item_id)<span class="tag">{{ \Illuminate\Support\Str::limit(optional($dr->items->firstWhere('id',$pmt->item_id))->description ?: 'Item', 22) }}</span>@else<span class="tag tag-gen">General</span>@endif</td>
                <td>{{ $pmt->paid_to ?: '—' }}</td>
                <td>{{ $pmt->note ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif

    {{-- Signatures --}}
    <table class="sign">
        <tr>
            <td><div class="line">Requested By</div><div class="who">{{ $dr->requested_by ?: ($dr->creator->name ?? '') }}</div></td>
            <td><div class="line">Approved By</div><div class="who">{{ $dr->approver->name ?? '' }}</div></td>
            <td><div class="line">Received By</div><div class="who">&nbsp;</div></td>
        </tr>
    </table>

    <div class="foot">Generated on {{ now()->format('d M Y, H:i') }} · {{ $company['name'] }} · Demand Request #{{ str_pad($dr->request_no,3,'0',STR_PAD_LEFT) }}</div>
</div>
</body>
</html>
