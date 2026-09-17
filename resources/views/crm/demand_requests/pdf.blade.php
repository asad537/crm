<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $stColors = [
        'Draft' => ['#475569','#eef2f7'], 'Submitted' => ['#b45309','#fff7ed'], 'Approved' => ['#0e7490','#e0f7fb'],
        'Rejected' => ['#b91c1c','#fee2e2'], 'Partially Paid' => ['#b45309','#fef3c7'], 'Completed' => ['#15803d','#dcfce7'],
    ];
    $sc = $stColors[$dr->status] ?? ['#334155','#eef2f7'];

    $acc = $dr->accountOutstanding();
    $co  = $dr->companyOutstanding();
    $net = $dr->netBalance();

    $sign = function ($v) {
        if ($v < -0.009) return ['&minus; ' . number_format(abs($v), 2), '#b91c1c', '#fdecec'];
        if ($v > 0.009)  return ['+ ' . number_format($v, 2), '#15803d', '#e9f9ef'];
        return ['0.00', '#15803d', '#e9f9ef'];
    };
    $accF = $sign($acc); $coF = $sign($co); $netF = $sign($net);
@endphp
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    @page { margin: 0; }
    body { margin: 0; color: #24303f; font-size: 10.5px; background: #fff; }
    .doc { padding: 30px 34px 24px; }
    .r { text-align: right; } .c { text-align: center; }

    /* Header band */
    .head { width: 100%; border-collapse: collapse; border-radius: 10px; overflow: hidden; }
    .head td { vertical-align: middle; }
    .logo { background: #fff; width: 108px; padding: 10px 14px; text-align: center; border: 1px solid #e4e8ef; border-right: 0; }
    .logo img { width: 84px; height: auto; }
    .brand { background: #14213d; padding: 16px 20px; }
    .brand .t { color: #fff; font-size: 20px; font-weight: bold; letter-spacing: 1.5px; }
    .brand .c2 { color: #aeb9d0; font-size: 9.5px; margin-top: 3px; }
    .rno { background: #14213d; padding: 16px 18px; text-align: right; width: 150px; border-left: 1px solid #26375c; }
    .rno .l { color: #8494b5; font-size: 7.5px; text-transform: uppercase; letter-spacing: 1px; }
    .rno .n { color: #fff; font-size: 18px; font-weight: bold; margin-top: 2px; }
    .rno .pill { display: inline-block; margin-top: 7px; padding: 3px 11px; border-radius: 10px; font-size: 8.5px; font-weight: bold; color: {{ $sc[0] }}; background: {{ $sc[1] }}; }
    .addr { background: #c1912f; color: #fff; font-size: 8.7px; text-align: center; padding: 6px 12px; letter-spacing: .3px; }

    /* Meta */
    .meta { width: 100%; border-collapse: collapse; margin-top: 18px; border: 1px solid #e4e8ef; border-radius: 8px; overflow: hidden; }
    .meta td { border: 1px solid #e9edf3; padding: 9px 12px; font-size: 10px; }
    .meta .k { background: #f6f8fb; font-weight: bold; color: #55617a; width: 15%; text-transform: uppercase; font-size: 8.5px; letter-spacing: .4px; }

    /* Section heading */
    .sec { margin: 20px 0 8px; font-size: 10px; font-weight: bold; color: #14213d; text-transform: uppercase; letter-spacing: 1px; }
    .sec span { display: inline-block; border-bottom: 2.5px solid #c1912f; padding-bottom: 5px; }

    /* Items */
    table.items { width: 100%; border-collapse: collapse; border: 1px solid #e4e8ef; }
    table.items th { background: #14213d; color: #fff; padding: 9px 8px; font-size: 8.3px; text-align: left; text-transform: uppercase; letter-spacing: .4px; }
    table.items td { border-bottom: 1px solid #eef1f5; padding: 8px; font-size: 9.6px; }
    table.items tr:nth-child(even) td { background: #f9fafc; }
    .grand td { background: #eef1f6 !important; color: #14213d; font-weight: bold; font-size: 11px; padding: 10px 8px; border-top: 2px solid #14213d; }

    /* Stat cards */
    .stats { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin-top: 2px; }
    .stats td { padding: 12px 8px; text-align: center; border-radius: 9px; border: 1px solid #eceff4; }
    .stats .lbl { font-size: 7.6px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; color: #6b7688; }
    .stats .val { font-size: 15px; font-weight: bold; padding-top: 4px; }

    .recon { margin-top: 12px; width: 100%; border-collapse: separate; border-spacing: 7px 0; }
    .recon td { padding: 9px 11px; border-radius: 8px; font-size: 9.5px; }
    .recon .rl { font-size: 7.6px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; }

    /* Payments */
    table.pay { width: 100%; border-collapse: collapse; margin-top: 6px; border: 1px solid #e9edf3; }
    table.pay th { background: #f4f6f9; color: #55617a; padding: 8px; font-size: 8px; text-align: left; text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid #e4e8ef; }
    table.pay td { border-bottom: 1px solid #eef1f5; padding: 7px 8px; font-size: 9px; }
    .tag { padding: 2px 8px; border-radius: 8px; font-size: 8px; font-weight: bold; }
    .tag-d { background: #e9f9ef; color: #15803d; } .tag-a { background: #e6f2ff; color: #1d6fd6; } .tag-g { background: #eef1f6; color: #64748b; }

    /* Signatures */
    .sign { width: 100%; border-collapse: collapse; margin-top: 52px; }
    .sign td { width: 33%; padding: 0 18px; text-align: center; font-size: 9px; color: #55617a; }
    .sign .line { border-top: 1.5px solid #26375c; padding-top: 6px; font-weight: bold; color: #14213d; }
    .sign .who { color: #8b95a6; font-size: 8.5px; margin-top: 2px; }
    .foot { margin-top: 26px; text-align: center; color: #a3adbb; font-size: 7.8px; border-top: 1px solid #eef1f5; padding-top: 8px; }
</style>
</head>
<body>
<div class="doc">
    {{-- Header --}}
    <table class="head">
        <tr>
            @if($company['logo_path'])<td class="logo"><img src="{{ $company['logo_path'] }}"></td>@endif
            <td class="brand">
                <div class="t">DEMAND REQUEST</div>
                <div class="c2">{{ $company['name'] }}</div>
            </td>
            <td class="rno">
                <div class="l">Request No.</div>
                <div class="n">#{{ str_pad($dr->request_no,3,'0',STR_PAD_LEFT) }}</div>
                <div><span class="pill">{{ $dr->status }}</span></div>
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
    <div class="sec"><span>Items / Materials</span></div>
    <table class="items">
        <thead><tr>
            <th class="c" style="width:26px">Sr</th>
            <th style="width:13%">Category</th>
            <th style="width:9%">Job No#</th>
            <th>Description</th>
            <th style="width:14%">Specification</th>
            <th class="c" style="width:7%">Qty</th>
            <th class="r" style="width:11%">Requested</th>
            <th class="r" style="width:10%">Paid</th>
            <th class="r" style="width:11%">Balance</th>
        </tr></thead>
        <tbody>
        @foreach($dr->items as $it)
            @php $inet = $dr->itemNet($it->id); $ipaid = $dr->paidForItem($it->id); $ib = $sign($inet); @endphp
            <tr>
                <td class="c">{{ $loop->iteration }}</td>
                <td>{{ $it->category ?: '—' }}</td>
                <td>{{ $it->job_no ?: 'N/A' }}</td>
                <td>{{ $it->description ?: '—' }}</td>
                <td>{{ $it->specification ?: '—' }}</td>
                <td class="c">{{ $it->qty ?: 'N/A' }}</td>
                <td class="r">{{ number_format($it->estimated_total,2) }}</td>
                <td class="r" style="color:#15803d;font-weight:bold">{{ $ipaid ? number_format($ipaid,2) : '—' }}</td>
                <td class="r" style="color:{{ $ib[1] }};font-weight:bold">{!! $inet < -0.009 || $inet > 0.009 ? $ib[0] : '&#10004;' !!}</td>
            </tr>
        @endforeach
            <tr class="grand">
                <td colspan="6" class="r">ESTIMATED TOTAL</td>
                <td class="r">{{ number_format($dr->estimated_total,2) }}</td>
                <td class="r">{{ number_format($dr->paidTotal(),2) }}</td>
                <td class="r" style="color:{{ $netF[1] }}">{!! $netF[0] !!}</td>
            </tr>
        </tbody>
    </table>

    {{-- Payment summary --}}
    <div class="sec"><span>Payment Summary</span></div>
    <table class="stats">
        <tr>
            <td style="background:#eef1fb">
                <div class="lbl">Requested</div>
                <div class="val" style="color:#3730a3">{{ number_format($dr->estimated_total,2) }}</div>
            </td>
            <td style="background:#e9f9ef">
                <div class="lbl">Paid</div>
                <div class="val" style="color:#15803d">{{ number_format($dr->paidTotal(),2) }}</div>
            </td>
            <td style="background:{{ $accF[2] }}">
                <div class="lbl">Account Outstanding</div>
                <div class="val" style="color:{{ $accF[1] }}">{!! $accF[0] !!}</div>
            </td>
            <td style="background:{{ $coF[2] }}">
                <div class="lbl">Company Outstanding</div>
                <div class="val" style="color:{{ $coF[1] }}">{!! $coF[0] !!}</div>
            </td>
            <td style="background:{{ $netF[2] }}">
                <div class="lbl">Total Outstanding</div>
                <div class="val" style="color:{{ $netF[1] }}">{!! $netF[0] !!}</div>
            </td>
        </tr>
    </table>

    {{-- Reconcile --}}
    <table class="recon">
        <tr>
            <td style="background:#e6f2ff">
                <div class="rl" style="color:#1d6fd6">Account — to reconcile</div>
                <div style="font-size:13px;font-weight:bold;color:#1d6fd6;padding-top:2px">{{ number_format($dr->accountTotal(),2) }}</div>
            </td>
            <td style="background:#e9f9ef">
                <div class="rl" style="color:#15803d">Company — direct paid</div>
                <div style="font-size:13px;font-weight:bold;color:#15803d;padding-top:2px">{{ number_format($dr->directTotal(),2) }}</div>
            </td>
        </tr>
    </table>

    {{-- Payment history --}}
    @if($dr->payments->count())
    <div class="sec" style="margin-top:18px"><span>Payment History</span></div>
    <table class="pay">
        <thead><tr><th style="width:14%">Date</th><th class="r" style="width:12%">Amount</th><th style="width:16%">Type / Source</th><th>Against</th><th style="width:14%">Paid To</th><th style="width:16%">Note</th></tr></thead>
        <tbody>
        @foreach($dr->payments as $pmt)
            @php $isD = ($pmt->pay_type ?? 'Account') === 'Direct'; @endphp
            <tr>
                <td>{{ optional($pmt->paid_at)->format('d M Y') }}</td>
                <td class="r" style="color:#15803d;font-weight:bold">{{ number_format($pmt->amount,2) }}</td>
                <td><span class="tag {{ $isD ? 'tag-d' : 'tag-a' }}">{{ $isD ? 'DIRECT' : 'ACCOUNT' }}</span>{{ $pmt->method ? ' · '.$pmt->method : '' }}</td>
                <td>@if($pmt->item_id)<span class="tag tag-g">{{ \Illuminate\Support\Str::limit(optional($dr->items->firstWhere('id',$pmt->item_id))->description ?: 'Item', 22) }}</span>@else<span class="tag tag-g">General</span>@endif</td>
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

    <div class="foot">Generated on {{ now()->format('d M Y, H:i') }} &nbsp;·&nbsp; {{ $company['name'] }} &nbsp;·&nbsp; Demand Request #{{ str_pad($dr->request_no,3,'0',STR_PAD_LEFT) }}</div>
</div>
</body>
</html>
