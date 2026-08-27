<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Internal Estimate {{ $ticket->ticket_number }}</title>
    <style>
        @page { margin: 28px 34px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1d2939; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.4; }
        .letterhead { width: 100%; border-collapse: collapse; }
        .letterhead td { padding: 0; vertical-align: top; }
        .logo-cell { width: 112px; }
        .logo { width: 92px; max-height: 92px; object-fit: contain; }
        .company-cell { padding-top: 4px !important; }
        .company-name { color: #06265e; font-size: 14px; font-weight: bold; letter-spacing: .2px; }
        .company-line { margin-top: 7px; color: #27364b; font-size: 8px; }
        .document-cell { width: 122px; padding-top: 43px !important; color: #e9aa10; font-size: 23px; text-align: right; }
        .letter-rule { height: 1px; margin: 10px 0 17px; background: #9aa0a6; border-left: 48px solid #06265e; }
        .header { padding: 12px 14px; border-radius: 7px; background: #f7f9fc; border: 1px solid #e1e7ef; }
        .brand { color: #06265e; font-size: 8px; font-weight: bold; letter-spacing: .5px; }
        .title { margin: 4px 0 2px; color: #142033; font-size: 19px; }
        .meta { width: 100%; margin-top: 9px; border-collapse: collapse; }
        .meta td { width: 25%; padding: 3px 8px 3px 0; vertical-align: top; }
        .label { display: block; margin-bottom: 2px; color: #748399; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .value { color: #1d2939; font-size: 9px; font-weight: bold; }
        .section { margin-top: 14px; page-break-inside: avoid; }
        h2 { margin: 0 0 6px; padding-bottom: 5px; color: #172033; border-bottom: 2px solid #f4511e; font-size: 11px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { padding: 6px 7px; background: #f1f4f8; color: #617087; font-size: 7px; text-align: left; text-transform: uppercase; }
        table.data td { padding: 6px 7px; border-bottom: 1px solid #e5e9ef; vertical-align: top; }
        .right { text-align: right !important; }
        .fixed { color: #8a4b18; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-left: -6px; }
        .summary td { padding: 8px; border: 1px solid #e1e7ef; border-radius: 6px; background: #f8fafc; }
        .summary td.total { background: #fff2ec; border-color: #ffc6b2; }
        .summary strong { display: block; margin-top: 3px; color: #172033; font-size: 11px; }
        .summary .total strong { color: #e84817; }
        .notes { padding: 9px 11px; border: 1px solid #e1e7ef; border-radius: 6px; background: #fafbfc; white-space: pre-line; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    @php
        $currency = $ticket->currency ?: 'USD';
        $workspaceName = optional($ticket->workspace)->name ?: 'CRM Estimate';
        $isAlMassa = optional($ticket->workspace)->slug === 'mybox-packaging-app';
        $companyName = $isAlMassa
            ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC'
            : 'MY BOX PRINTING';
        $logoPath = public_path($isAlMassa ? 'al-massa-packaging-logo-pdf.jpg' : 'my-box-printing-logo-pdf.jpg');
        $logoData = is_file($logoPath)
            ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath))
            : null;
        $finishing = is_array(optional($ticket->lead)->custom_specs)
            ? (array) (optional($ticket->lead)->custom_specs['Finishing Options'] ?? [])
            : [];
        $displayBreakdown = $breakdown->reject(function ($item) {
            return in_array(($item['name'] ?? ''), ['Production Cost', 'Machine Cost', 'Rent', 'Power'], true);
        });
    @endphp

    <table class="letterhead">
        <tr>
            <td class="logo-cell">@if($logoData)<img class="logo" src="{{ $logoData }}">@endif</td>
            <td class="company-cell">
                <div class="company-name">{{ $companyName }}</div>
                @if($isAlMassa)
                    <div class="company-line">All Cosmetics &amp; Perfumes Hard, Soft Boxes and Paper Bags</div>
                    <div class="company-line">Al Diyar Building 33, 4th Industrial Street, Industrial Area 12,<br>Sharjah, United Arab Emirates</div>
                    <div class="company-line">+971 56 682 0097 &nbsp;|&nbsp; +971 56 837 0097</div>
                    <div class="company-line">info@almassapackaging.com</div>
                @else
                    <div class="company-line">Premium Custom Packaging Solutions</div>
                    <div class="company-line">www.myboxprinting.com &nbsp;|&nbsp; support@myboxprinting.com</div>
                @endif
            </td>
            <td class="document-cell">ESTIMATE</td>
        </tr>
    </table>
    <div class="letter-rule"></div>

    <div class="header">
        <div class="brand">INTERNAL COSTING WORKSHEET</div>
        <div class="title">{{ $ticket->ticket_number }}</div>
        <div style="color:#6f7d90">Complete estimator worksheet · Generated {{ $generatedAt->format('d M Y, h:i A') }}</div>
        <table class="meta">
            <tr>
                <td><span class="label">Client</span><span class="value">{{ $ticket->client_name ?: '—' }}</span></td>
                <td><span class="label">Product</span><span class="value">{{ $ticket->product_style ?: '—' }}</span></td>
                <td><span class="label">Status</span><span class="value">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span></td>
                <td><span class="label">Currency</span><span class="value">{{ $currency }}</span></td>
            </tr>
            <tr>
                <td><span class="label">Estimator</span><span class="value">{{ optional($ticket->estimator)->name ?: 'Unassigned' }}</span></td>
                <td><span class="label">Requested By</span><span class="value">{{ optional($ticket->requester)->name ?: '—' }}</span></td>
                <td><span class="label">Box Dimensions</span><span class="value">{{ trim(($ticket->finish_size ?: '—').' '.(optional($ticket->lead)->unit ?: $ticket->unit)) }}</span></td>
                <td><span class="label">Open / Flat Size</span><span class="value">{{ trim(($ticket->flat_size ?: '—').' '.(optional($ticket->lead)->unit ?: $ticket->unit)) }}</span></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Estimate Request</h2>
        <table class="data">
            <tr><th>Stock</th><th>Printing</th><th>Shipping Address</th></tr>
            <tr>
                <td>{{ $ticket->stock ?: '—' }}</td>
                <td>{{ $ticket->printing ?: '—' }}</td>
                <td>{{ optional($ticket->lead)->shipping_address ?: 'Not provided' }}</td>
            </tr>
            <tr><th colspan="3">Finishing Options</th></tr>
            <tr><td colspan="3">{{ $finishing ? implode(', ', $finishing) : 'No finishing options selected' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Professional and Fixed Costs</h2>
        <table class="data">
            <thead><tr><th>Cost Item</th><th>Specification / Detail</th><th class="right">Cost</th></tr></thead>
            <tbody>
            @forelse($displayBreakdown as $item)
                <tr>
                    <td><strong>{{ ($item['name'] ?? '') === 'Paper Dimensions' ? 'Paper Cost' : ($item['name'] ?? 'Cost Item') }}</strong></td>
                    <td>{{ $item['detail'] ?? '—' }}</td>
                    <td class="right"><strong>{{ $currency }} {{ number_format((float)($item['price'] ?? 0), 2) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="3">No costing data saved yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Cost Summary</h2>
        <table class="summary">
            <tr>
                <td><span class="label">Professional Cost</span><strong>{{ $currency }} {{ number_format($variableCost,2) }}</strong></td>
                <td><span class="label">Fixed Cost</span><strong>{{ $currency }} {{ number_format($fixedCost,2) }}</strong></td>
                <td><span class="label">Base Cost</span><strong>{{ $currency }} {{ number_format($baseCost,2) }}</strong></td>
                <td><span class="label">Waste ({{ number_format((float)$ticket->waste_material_percentage,2) }}%)</span><strong>{{ $currency }} {{ number_format($wasteAmount,2) }}</strong></td>
                <td><span class="label">Production: Machine, Rent &amp; Power ({{ number_format($productionPercentage,2) }}%)</span><strong>{{ $currency }} {{ number_format($productionAmount,2) }}</strong></td>
                <td><span class="label">Before VAT</span><strong>{{ $currency }} {{ number_format($productionCost,2) }}</strong></td>
                <td><span class="label">VAT ({{ number_format($vatPercentage,2) }}%)</span><strong>{{ $currency }} {{ number_format($vatAmount,2) }}</strong></td>
                <td class="total"><span class="label">Final Price</span><strong>{{ $currency }} {{ number_format($finalCost,2) }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Quantity Price Options</h2>
        <table class="data">
            <thead><tr><th>Quantity</th><th class="right">Selling Price</th><th class="right">Price / Unit</th><th class="right">Discount</th><th class="right">After Discount</th><th class="right">Final Offer</th></tr></thead>
            <tbody>
            @foreach($ticket->options as $option)
                @php
                    $selling = (float) $option->total_price;
                    $discounted = $option->discounted_price !== null ? (float)$option->discounted_price : $selling;
                @endphp
                <tr>
                    <td><strong>{{ number_format($option->quantity) }}</strong></td>
                    <td class="right">{{ $currency }} {{ number_format($selling,2) }}</td>
                    <td class="right">{{ $currency }} {{ number_format($option->quantity > 0 ? $selling/$option->quantity : 0,4) }}</td>
                    <td class="right">{{ number_format((float)$option->discount_percentage,2) }}%</td>
                    <td class="right">{{ $currency }} {{ number_format($discounted,2) }}</td>
                    <td class="right">{{ $option->offer_price !== null ? $currency.' '.number_format($option->offer_price,2) : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if(trim((string)$ticket->estimator_notes) !== '' || trim((string)$ticket->team_lead_notes) !== '')
    <div class="section">
        <h2>Internal Notes</h2>
        @if(trim((string)$ticket->estimator_notes) !== '')
            <div class="notes"><strong>Estimator Notes</strong><br>{{ $ticket->estimator_notes }}</div>
        @endif
        @if($ticket->team_lead_notes)
            <div class="notes" style="{{ trim((string)$ticket->estimator_notes) !== '' ? 'margin-top:7px' : '' }}"><strong>Team Lead Notes</strong><br>{{ $ticket->team_lead_notes }}</div>
        @endif
    </div>
    @endif

</body>
</html>
