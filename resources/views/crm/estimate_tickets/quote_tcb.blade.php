<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 24px 26px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1a1a1a; font-size: 10px; margin: 0; }
    .q-blue { color: #2E75B6; }
    .hdr { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .hdr td { vertical-align: top; }
    .brand-logo { height: 54px; width: auto; }
    .brand-addr { font-size: 9px; color: #333; line-height: 1.45; }
    .brand-addr a { color: #2E75B6; text-decoration: none; }
    .quote-word { font-size: 30px; font-weight: bold; color: #2E75B6; text-align: right; letter-spacing: 1px; }
    .meta { border-collapse: collapse; width: 260px; float: right; margin-top: 4px; }
    .meta td { border: 1px solid #1a1a1a; padding: 3px 6px; font-size: 9px; }
    .meta .k { font-weight: bold; background: #fff; white-space: nowrap; }
    .meta .v { text-align: right; }
    .prep { font-size: 9px; line-height: 1.6; }
    .prep b { display: inline-block; min-width: 78px; }
    .cust { border-collapse: collapse; margin-top: 8px; }
    .cust .sec { background: #2E75B6; color: #fff; font-weight: bold; padding: 3px 7px; font-size: 9px; }
    .cust .lbl { font-weight: bold; padding: 3px 7px 3px 0; font-size: 9px; white-space: nowrap; }
    .cust .val { border: 1px solid #1a1a1a; padding: 3px 8px; font-size: 9px; width: 240px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
    table.items th { background: #2E75B6; color: #fff; font-size: 9px; padding: 6px 7px; text-align: left; border: 1px solid #2E75B6; }
    table.items th.num { text-align: center; }
    table.items td { border: 1px solid #d5dbe2; padding: 5px 7px; font-size: 9px; height: 15px; }
    table.items td.desc { font-weight: bold; background: #f2f5f9; white-space: nowrap; }
    table.items td.spec { background: #f2f5f9; }
    table.items td.num { text-align: center; }
    .terms-h { background: #2E75B6; color: #fff; font-weight: bold; padding: 4px 7px; font-size: 9px; margin-top: 14px; border: 1px solid #2E75B6; }
    .terms { border: 1px solid #d5dbe2; border-top: none; padding: 7px 9px; font-size: 9px; line-height: 1.7; }
    .foot { border: 1px solid #d5dbe2; margin-top: 12px; text-align: center; padding: 10px; font-size: 9px; }
    .foot .csr { font-weight: bold; }
    .foot .ty { font-weight: bold; font-style: italic; margin-top: 2px; }
</style>
</head>
<body>

    <table class="hdr">
        <tr>
            <td style="width: 60%;">
                @if(!empty($logoData))
                    <img src="{{ $logoData }}" class="brand-logo" alt="{{ $brandName }}">
                @else
                    <div style="font-size:20px;font-weight:bold;" class="q-blue">{{ $brandName }}</div>
                @endif
                <div class="brand-addr">
                    {{ $addressLine }}<br>
                    <a href="{{ $website }}">{{ $website }}</a>
                </div>
                <div class="prep" style="margin-top:8px;">
                    <b>PREPARED BY:</b> {{ $preparedBy }}<br>
                    <b>CONTACT:</b> {{ $contactPhone }}
                </div>
            </td>
            <td style="width: 40%;">
                <div class="quote-word">QUOTE</div>
                <table class="meta">
                    <tr><td class="k">DATE</td><td class="v">{{ $date }}</td></tr>
                    <tr><td class="k">QUOTE #</td><td class="v">{{ $quoteNo }}</td></tr>
                    <tr><td class="k">INQUIRY ID</td><td class="v">{{ $inquiryId }}</td></tr>
                    <tr><td class="k">VALID UNTIL</td><td class="v">{{ $validUntil }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="cust">
        <tr><td class="sec" colspan="2">CUSTOMER:</td></tr>
        <tr><td class="lbl">NAME:</td><td class="val">{{ $customerName }}</td></tr>
        <tr><td class="lbl">EMAIL ADDRESS:</td><td class="val">{{ $customerEmail }}</td></tr>
        <tr><td class="lbl">PHONE:</td><td class="val">{{ $customerPhone }}</td></tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:16%;">DESCRIPTION</th>
                <th style="width:40%;">SPECIFICATIONS:</th>
                <th class="num" style="width:12%;">QUANTITY</th>
                <th class="num" style="width:16%;">PER UNIT ({{ $currency }})</th>
                <th class="num" style="width:16%;">TOTAL COST ({{ $currency }})</th>
            </tr>
        </thead>
        <tbody>
            @php $rowCount = max(count($specRows), count($priceRows)); @endphp
            @for($i = 0; $i < $rowCount; $i++)
                @php
                    $spec = $specRows[$i] ?? ['', ''];
                    $price = $priceRows[$i] ?? null;
                @endphp
                <tr>
                    <td class="desc">{{ $spec[0] }}</td>
                    <td class="spec">{{ $spec[1] }}</td>
                    <td class="num">{{ $price ? number_format($price['quantity']) : '' }}</td>
                    <td class="num">{{ $price ? $symbol.number_format($price['unit'], 3) : '' }}</td>
                    <td class="num">{{ $price ? $symbol.number_format($price['total'], 2) : '' }}</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="terms-h">TERMS AND CONDITIONS:</div>
    <div class="terms">
        1. No extra or hidden charges.<br>
        2. Payment shall be made through Paypal, Credit/Debit Card, or wire transfer upon confirmation of the order.<br>
        3. Please send acknowledgment of the price quote via email.
    </div>

    <div class="foot">
        If you have any questions about this price quote, please contact us anytime!<br>
        <span class="csr">{{ $csrEmail }}</span>
        <div class="ty">Thank You For Your Business!</div>
    </div>

</body>
</html>
