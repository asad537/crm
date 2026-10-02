@php
    $print = $print ?? false;
    $logoSrc = $print
        ? asset('al-massa-packaging-logo-pdf.jpg')
        : public_path('al-massa-packaging-logo-pdf.jpg');

    $fmt = function ($value) {
        if (!$value) return '';
        try { return \Carbon\Carbon::parse($value)->format('d/m/Y'); } catch (\Throwable $e) { return (string) $value; }
    };

    $items = is_array($challan->items) ? $challan->items : [];
    $totalCartons = 0;
    foreach ($items as $it) {
        $totalCartons += (float) preg_replace('/[^0-9.]/', '', (string) ($it['total_cartons'] ?? ''));
    }
    // Pad to a minimum number of rows so the sheet looks complete.
    $minRows = 8;
    $rows = $items;
    for ($i = count($rows); $i < $minRows; $i++) {
        $rows[] = ['description' => '', 'boxes_per_carton' => '', 'total_cartons' => '', 'carton_size' => '', 'actual_wt' => '', 'volumetric_wt' => ''];
    }
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Delivery Challan - {{ $challan->challan_no }}</title>
    <style>
        @page { margin: 8mm 8mm 9mm 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9pt; line-height: 1.35; color: #182436; background: #fff; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .hdr td { vertical-align: middle; }
        .logo { height: 17mm; width: auto; }
        .co-name { font-size: 11pt; font-weight: 700; color: #1f2a4d; margin: 0; }
        .co-sub { font-size: 6.6pt; color: #444; margin: 1px 0 0; }
        .dc-title { font-size: 17pt; font-weight: 800; letter-spacing: 2px; color: #1f2a4d; margin: 0 0 3px; text-align: right; }
        .dc-no { font-size: 8pt; text-align: right; }
        .dc-no .lbl { font-weight: 700; }
        .bar { background: #2b3a67; color: #fff; font-weight: 700; font-size: 8.5pt; padding: 5px 9px; text-transform: uppercase; letter-spacing: .4px; }
        .box { border: 1px solid #9aa3b8; margin-top: 7px; }
        .info { table-layout: fixed; }
        .info th, .info td { padding: 5px 9px; border-bottom: 1px solid #e3e7ee; border-right: 1px solid #e3e7ee; text-align: left; font-size: 8.6pt; }
        .info td:last-child { border-right: 0; }
        .info tr:last-child th, .info tr:last-child td { border-bottom: 0; }
        .info th { width: 15%; color: #51607a; font-size: 7.4pt; text-transform: uppercase; font-weight: 700; background: #eef1f7; }
        .info td { width: 35%; color: #1f2a4d; font-weight: 600; }
        .items { table-layout: fixed; margin-top: 7px; }
        .items th { background: #2b3a67; color: #fff; font-size: 7pt; text-transform: uppercase; font-weight: 700; padding: 6px 5px; border: 1px solid #2b3a67; text-align: left; line-height: 1.25; }
        .items td { border: 1px solid #c9d2e3; padding: 7px 5px; font-size: 8.4pt; color: #27364a; min-height: 18px; }
        .items tbody tr:nth-child(even) { background: #f4f6fb; }
        .items .sr { text-align: center; width: 5%; }
        .items .total-row td { font-weight: 800; background: #eef1f7; color: #1f2a4d; }
        .remarks { border: 1px solid #9aa3b8; margin-top: 7px; }
        .remarks .body { padding: 6px 9px; min-height: 14mm; white-space: pre-wrap; font-size: 8.6pt; }
        .sign { margin-top: 16px; }
        .sign td { width: 33.33%; padding: 0 10px; text-align: center; vertical-align: bottom; }
        .sign .sl { border-top: 1px solid #1f2a4d; padding-top: 4px; margin-top: 20px; color: #51607a; font-size: 7.6pt; text-transform: uppercase; font-weight: 700; }
        .sign .sv { font-size: 8.4pt; color: #1f2a4d; font-weight: 700; min-height: 12px; }
    </style>
</head>
<body>

<table class="hdr">
    <tr>
        <td style="width: 58%;">
            <table><tr>
                <td style="width: 20mm;"><img class="logo" src="{{ $logoSrc }}" alt="AL MASSA"></td>
                <td>
                    <p class="co-name">ALMASSA AL MALAKIYA BOXES &amp; PACKAGING IND LLC</p>
                    <p class="co-sub">Shed 4, Al Diyar Building 33, Fourth Industrial St, Industrial Area 12 Sharjah UAE</p>
                    <p class="co-sub">Contact: +971 56 682 0097</p>
                </td>
            </tr></table>
        </td>
        <td style="width: 42%; text-align: right;">
            <div class="dc-title">DELIVERY CHALLAN</div>
            <div class="dc-no"><span class="lbl">Challan No:</span> {{ $challan->challan_no }}</div>
            <div class="dc-no"><span class="lbl">Date:</span> {{ $fmt($challan->challan_date) }}</div>
        </td>
    </tr>
</table>

<div class="box">
    <div class="bar">Delivery Details</div>
    <table class="info">
        <tr>
            <th>Job No.</th><td>{{ $challan->job_no }}</td>
            <th>Delivery Date</th><td>{{ $fmt($challan->delivery_date) }}</td>
        </tr>
        <tr>
            <th>Customer Name</th><td>{{ $challan->customer_name }}</td>
            <th>Contact Person</th><td>{{ $challan->contact_person }}</td>
        </tr>
        <tr>
            <th>Vehicle No.</th><td>{{ $challan->vehicle_no }}</td>
            <th>P.O. / Reference</th><td>{{ $challan->po_reference }}</td>
        </tr>
        <tr>
            <th>Delivery Address</th><td colspan="3" style="width:85%">{{ $challan->delivery_address }}</td>
        </tr>
    </table>
</div>

<table class="items">
    <thead>
    <tr>
        <th class="sr">Sr.</th>
        <th style="width:34%">Job / Item Description</th>
        <th>Boxes / Carton</th>
        <th>Total Cartons</th>
        <th>Carton Size (LxWxH) CM</th>
        <th>Actual Wt. / Carton (kg)</th>
        <th>Volumetric Weight</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $i => $it)
        <tr>
            <td class="sr">{{ $i + 1 }}</td>
            <td>{{ $it['description'] ?? '' }}</td>
            <td>{{ $it['boxes_per_carton'] ?? '' }}</td>
            <td>{{ $it['total_cartons'] ?? '' }}</td>
            <td>{{ $it['carton_size'] ?? '' }}</td>
            <td>{{ $it['actual_wt'] ?? '' }}</td>
            <td>{{ $it['volumetric_wt'] ?? '' }}</td>
        </tr>
    @endforeach
    <tr class="total-row">
        <td class="sr"></td>
        <td style="text-align:right">TOTAL</td>
        <td></td>
        <td>{{ $totalCartons ? rtrim(rtrim(number_format($totalCartons, 2, '.', ''), '0'), '.') : '' }}</td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    </tbody>
</table>

<div class="remarks">
    <div class="bar">Remarks / Special Instructions</div>
    <div class="body">{{ $challan->remarks }}</div>
</div>

<table class="sign">
    <tr>
        <td><div class="sv">{{ $challan->prepared_by }}</div><div class="sl">Prepared By</div></td>
        <td><div class="sv">{{ $challan->driver_name }}{{ $challan->driver_contact ? ' · ' . $challan->driver_contact : '' }}</div><div class="sl">Delivered By / Driver</div></td>
        <td><div class="sv">{{ $challan->received_by }}</div><div class="sl">Received By (Customer)</div></td>
    </tr>
</table>

@if($print)
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
@endif

</body>
</html>
