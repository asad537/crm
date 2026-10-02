@php
    $date = function ($value) {
        if (!$value) return '';
        return $value instanceof \DateTimeInterface
            ? $value->format('d M Y')
            : (string) $value;
    };

    $flags = function (array $choices) use ($card) {
        $selected = [];
        foreach ($choices as $field => $label) {
            if ($card->{$field}) {
                $selected[] = $label;
            }
        }
        return implode(', ', $selected);
    };

    $choice = function ($value, array $choices) {
        return $choices[$value] ?? '';
    };

    $enabled = function ($section) use ($card) {
        return ($card->section_choices[$section] ?? 'yes') !== 'no';
    };

    // Only manually-filled data is shown, so the on-paper time/duration
    // placeholders are dropped.
    $timeRow = [];
    $durationRow = [];

    $sections = [
        'briefing' => [
            'Job Briefing',
            [
                [
                    [
                        'Box Size (L × W × H)',
                        implode(' × ', array_filter(
                            [$card->box_l, $card->box_w, $card->box_h],
                            fn($v) => $v !== null && $v !== ''
                        ))
                    ],
                    ['Unit', $card->box_unit]
                ],
                [
                    [
                        'Open Size (L × W)',
                        implode(' × ', array_filter(
                            [$card->open_l, $card->open_w],
                            fn($v) => $v !== null && $v !== ''
                        ))
                    ],
                    [
                        'Box Type',
                        $choice($card->box_type, [
                            'hard' => 'Hard Box',
                            'soft' => 'Soft Box',
                            'other' => 'Other',
                        ]) .
                        (
                            $card->box_type === 'other' && $card->box_type_other
                                ? ': ' . $card->box_type_other
                                : ''
                        )
                    ]
                ],
            ]
        ],

        'foam' => [
            'Foam',
            [
                [
                    [
                        'Foam Type',
                        $choice($card->foam_type, [
                            'eva' => 'EVA Foam',
                            'soft' => 'Soft Foam',
                            'other' => 'Other',
                        ]) . ($card->foam_type === 'other' && $card->foam_type_other
                            ? ': ' . $card->foam_type_other : '')
                    ],
                    [
                        'Color',
                        $choice($card->foam_color, [
                            'black' => 'Black',
                            'white' => 'White',
                            'other' => 'Other',
                        ]) . ($card->foam_color === 'other' && $card->foam_color_other
                            ? ': ' . $card->foam_color_other : '')
                    ],
                ],
                [
                    ['Thickness (mm)', $card->foam_thickness],
                    ['Quantity', $card->foam_qty],
                ],
            ],
        ],

        'printing' => [
            'Printing',
            [
                [
                    [
                        'Method',
                        $choice($card->printing_method, [
                            'offset' => 'Offset',
                            'digital' => 'Digital',
                            'other' => 'Other',
                        ]) .
                        (
                            $card->printing_method === 'other' &&
                            $card->printing_method_other
                                ? ': ' . $card->printing_method_other
                                : ''
                        )
                    ],
                    [
                        'Coating',
                        $flags([
                            'coating_uv' => 'UV',
                            'coating_coating' => 'Coating',
                            'coating_varnish' => 'Varnish',
                            'coating_other' => 'Other',
                        ]) .
                        (
                            $card->coating_other &&
                            $card->coating_other_text
                                ? ': ' . $card->coating_other_text
                                : ''
                        )
                    ]
                ],
                [
                    ['CTP Plates', $card->ctp_plates],
                    ['PMS', $card->pms]
                ],
                [
                    ['Total Plates', $card->total_plates],
                    ['', '']
                ],
            ]
        ],

        'lamination' => [
            'Lamination',
            [
                [
                    [
                        'Type',
                        $flags([
                            'lam_gloss' => 'Gloss',
                            'lam_matte' => 'Matte',
                            'lam_soft_touch' => 'Soft Touch',
                            'lam_other' => 'Other',
                        ]) .
                        (
                            $card->lam_other &&
                            $card->lam_other_text
                                ? ': ' . $card->lam_other_text
                                : ''
                        )
                    ],
                    ['Other Qty', $card->lam_other_qty]
                ],
            ]
        ],

        'screen' => [
            'Screen Printing / Spot UV',
            [
                [
                    ['Colors', $card->screen_colors],
                    ['Spot UV', $card->screen_uv ? 'Yes' : '']
                ],
            ]
        ],

        'foiling' => [
            'Foiling',
            [
                [
                    [
                        'Gold',
                        $card->foil_gold ? ($card->foil_gold_shade ?: 'Yes') : ''
                    ],
                    [
                        'Silver',
                        $card->foil_silver ? ($card->foil_silver_shade ?: 'Yes') : ''
                    ]
                ],
                [
                    [
                        'Other',
                        $card->foil_other ? ($card->foil_other_shade ?: 'Yes') : ''
                    ],
                    ['', '']
                ],
            ]
        ],

        'corrugation' => [
            'Corrugation',
            [
                [
                    [
                        'Color',
                        $choice($card->corr_color, [
                            'brown' => 'Brown',
                            'white' => 'White',
                            'other' => 'Other',
                        ]) .
                        (
                            $card->corr_color === 'other' &&
                            $card->corr_color_other
                                ? ': ' . $card->corr_color_other
                                : ''
                        )
                    ],
                    ['Ply', $card->corr_ply]
                ],
            ]
        ],

        'diecutting' => [
            'Die Cutting',
            [
                [
                    [
                        'Type',
                        $flags([
                            'die_full' => 'Full',
                            'die_half' => 'Half',
                            'die_embossing' => 'Embossing',
                            'die_debossing' => 'Debossing',
                        ])
                    ],
                    ['', '']
                ],
            ]
        ],

        'pasting' => [
            'Pasting',
            [
                [
                    [
                        'Type',
                        $flags([
                            'paste_tape' => 'Tape',
                            'paste_glue' => 'Glue',
                            'paste_double' => 'Double Pasting',
                            'paste_pvc_window' => 'PVC Window',
                            'paste_other' => 'Other',
                        ]) .
                        (
                            $card->paste_other &&
                            $card->paste_other_text
                                ? ': ' . $card->paste_other_text
                                : ''
                        )
                    ],
                    ['Other Qty', $card->paste_other_qty]
                ],
            ]
        ],

        'dummy' => [
            'Dummy / Sample Approval',
            [
                [
                    ['Dummy Sent On', $date($card->dummy_sent_on)],
                    ['Dummy Approved On', $date($card->dummy_approved_on)]
                ],
                [
                    ['Approved By / Signature', $card->dummy_approved_by],
                    ['', '']
                ],
            ]
        ],

        'quality' => [
            'Quality Check',
            [
                [
                    [
                        'Result',
                        $choice($card->qc_result, [
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                    ],
                    ['Approved By / Signature', $card->qc_approved_by]
                ],
                [
                    ['Comments', $card->qc_comments],
                    ['Rejection Comments', $card->qc_rejection_comments]
                ],
            ]
        ],

        'timeline' => [
            'Job Timeline',
            [
                [
                    [
                        'Status',
                        $choice($card->timeline_status, [
                            'on_time' => 'On Time',
                            'delayed' => 'Delayed',
                        ])
                    ],
                    ['Delay Days / Reason', $card->delay_reason]
                ],
            ]
        ],
    ];

    // Flatten each section to only the pairs that actually have a value.
    // Process sections always keep the four manual time-tracking blanks.
    $BLANK = '__BLANK__';
    $timeSections = ['printing', 'lamination', 'screen', 'foiling', 'corrugation', 'diecutting'];
    $timeFields = ['Start Time', 'End Time', 'Total Time (hr)', 'Total Time (min)'];
    // These always appear (even empty, as fillable blanks) and are placed last.
    $mandatorySections = ['quality', 'timeline'];
    $hasValue = fn($v) => $v !== null && $v !== '';
    $filledSections = [];
    foreach ($sections as $key => [$title, $rows]) {
        $isMandatory = in_array($key, $mandatorySections, true);
        $pairs = [];
        foreach ($rows as $row) {
            foreach ($row as [$label, $value]) {
                if ($label === '') {
                    continue;
                }
                if ($hasValue($value)) {
                    $pairs[] = [$label, $value];
                } elseif ($isMandatory) {
                    $pairs[] = [$label, $BLANK];
                }
            }
        }
        // Time-tracking blanks only appear when the section is actually used.
        if ($pairs && in_array($key, $timeSections, true)) {
            foreach ($timeFields as $tf) {
                $pairs[] = [$tf, $BLANK];
            }
        }
        if ($pairs) {
            $filledSections[$key] = [$title, array_chunk($pairs, 2)];
        }
    }

    // Render order: sections in order with the stock table right after Job
    // Briefing, then Quality Check and Job Timeline at the very end.
    $renderOrder = [];
    foreach ($filledSections as $key => $v) {
        if (in_array($key, $mandatorySections, true)) {
            continue;
        }
        $renderOrder[] = $key;
        if ($key === 'briefing') {
            $renderOrder[] = '__stock__';
        }
    }
    if (!in_array('__stock__', $renderOrder, true)) {
        $renderOrder[] = '__stock__';
    }
    foreach ($mandatorySections as $mk) {
        if (isset($filledSections[$mk])) {
            $renderOrder[] = $mk;
        }
    }

    // Job information — keep only filled entries (priority always shown).
    $priority = $card->priority_critical
        ? 'CRITICAL'
        : ($card->priority_urgent ? 'URGENT' : 'REGULAR');
    $priorityClass = $card->priority_critical
        ? 'badge-danger'
        : ($card->priority_urgent ? 'badge-warning' : 'badge-success');

    $jobInfo = array_values(array_filter([
        ['Job Assigned Date', $date($card->job_date ?: $job->created_at)],
        ['Job No.', $card->job_no ?: $job->job_number],
        ['Product', $card->product ?: $job->title],
        ['Order Qty', $card->order_qty],
        ['Job Start On', $date($card->job_start_on)],
        ['Deadline', $date($job->due_date)],
        ['Designer', $job->designer->name ?? ''],
    ], fn($p) => $hasValue($p[1])));
    $jobInfoChunks = array_chunk($jobInfo, 2);

    $print = $print ?? false;
    $logoSrc = $print
        ? asset('al-massa-packaging-logo-pdf.jpg')
        : public_path('al-massa-packaging-logo-pdf.jpg');
    $orderNo = $card->job_no ?: $job->job_number;
    $orderDate = $date($card->job_date ?: $job->created_at);

    // Deterministic faux-barcode bars from the job number.
    $seed = crc32((string) ($orderNo ?: 'job'));
    $bars = [];
    for ($i = 0; $i < 46; $i++) {
        $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
        $bars[] = 1 + ($seed % 3);
    }

    $hasStocks = $stocks->isNotEmpty();
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Job Card - {{ $orderNo }}</title>
    <style>
        @page { margin: 6mm 7mm 6mm 7mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 0;
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt; line-height: 1.32; color: #182436; background: #fff;
        }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }

        /* ===== HEADER ===== */
        .hdr td { vertical-align: middle; }
        .logo { height: 15mm; width: auto; }
        .co-name { font-size: 11pt; font-weight: 700; color: #1f2a4d; margin: 0; }
        .co-sub { font-size: 6.3pt; color: #444; margin: 1px 0 0; }
        .jc-title { font-size: 16pt; font-weight: 800; letter-spacing: 3px; color: #1f2a4d; margin: 0 0 3px; text-align: right; }
        .barcode { text-align: right; white-space: nowrap; line-height: 0; }
        .barcode span { display: inline-block; height: 24px; background: #111; vertical-align: bottom; }
        .barcode span.g { background: transparent; }
        .no-date { border: 1px solid #1f2a4d; padding: 4px 7px; margin-top: 4px; font-size: 9pt; text-align: right; }
        .no-date .lbl { font-weight: 700; }
        .ln { display: inline-block; min-width: 55px; border-bottom: 1px solid #777; padding: 0 3px; }

        /* ===== SUMMARY CARDS ===== */
        .summary { border-collapse: separate; border-spacing: 4px 0; margin: 6px -4px 6px -4px; }
        .summary td { width: 25%; padding: 6px 9px; border: 1px solid #c9d2e3; background: #f4f6fb; }
        .summary-label { display: block; font-size: 7pt; text-transform: uppercase; color: #6b7792; font-weight: 700; letter-spacing: .4px; margin-bottom: 2px; }
        .summary-value { font-size: 10.5pt; color: #1f2a4d; font-weight: 700; }
        .badge { display: inline-block; padding: 2px 9px; border-radius: 10px; font-size: 8.5pt; font-weight: 700; }
        .badge-danger { color: #991b1b; background: #fee2e2; }
        .badge-warning { color: #92400e; background: #fef3c7; }
        .badge-success { color: #166534; background: #dcfce7; }

        /* ===== SECTIONS ===== */
        .section { margin-bottom: 5px; border: 1px solid #9aa3b8; page-break-inside: avoid; }
        .bar { background: #2b3a67; color: #fff; font-weight: 700; font-size: 8.7pt; padding: 4px 9px; text-transform: uppercase; letter-spacing: .4px; }

        /* ===== DETAILS TABLE ===== */
        .details { table-layout: fixed; }
        .details th, .details td { padding: 6px 9px; border-bottom: 1px solid #e3e7ee; text-align: left; vertical-align: middle; overflow-wrap: break-word; }
        .details tr:last-child th, .details tr:last-child td { border-bottom: 0; }
        .details th { width: 20%; color: #51607a; font-size: 7.6pt; text-transform: uppercase; font-weight: 700; background: #eef1f7; border-right: 1px solid #e3e7ee; }
        .details td { width: 30%; color: #1f2a4d; font-size: 9.6pt; font-weight: 600; white-space: pre-line; border-right: 1px solid #e3e7ee; }
        .details td:last-child { border-right: 0; }

        /* ===== STOCK TABLE ===== */
        .stock-wrap { padding: 6px; }
        .stocks { table-layout: fixed; }
        .stocks th { padding: 6px 6px; background: #2b3a67; color: #fff; font-size: 7.5pt; text-transform: uppercase; font-weight: 700; line-height: 1.3; text-align: left; border: 1px solid #2b3a67; }
        .stocks td { padding: 7px 6px; border: 1px solid #c9d2e3; color: #27364a; font-size: 9pt; vertical-align: middle; overflow-wrap: break-word; }
        .stocks tbody tr:nth-child(even) { background: #f4f6fb; }

        /* ===== PROCUREMENT LIST ===== */
        .material-section { page-break-inside: auto; }
        .material-wrap { padding: 6px; }
        .materials { table-layout: fixed; }
        .materials thead { display: table-header-group; }
        .materials tr { page-break-inside: avoid; }
        .materials th { padding: 6px; background: #2b3a67; color: #fff; font-size: 7.5pt; text-align: left; border: 1px solid #2b3a67; }
        .materials td { padding: 7px 6px; border: 1px solid #c9d2e3; color: #27364a; font-size: 8.5pt; overflow-wrap: break-word; }
        .materials tbody tr:nth-child(even) { background: #f4f6fb; }

        /* ===== SIGNATURES / FOOTER ===== */
        .sign { margin-top: 10px; }
        .sign td { width: 33.33%; padding: 0 10px; text-align: center; vertical-align: bottom; }
        .sign .sl { border-top: 1px solid #1f2a4d; padding-top: 4px; margin-top: 18px; color: #51607a; font-size: 8pt; text-transform: uppercase; font-weight: 700; }
        .blank { display: inline-block; min-width: 90px; min-height: 10px; border-bottom: 1px solid #9ba7b6; }
        .text-navy { color: #2b3a67; }
        .deadline { color: #c62828; font-weight: 700; }

        /* Browsers normally omit background colors in the print preview. */
        @media print {
            html, body, body * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

{{-- HEADER --}}
<table class="hdr">
    <tr>
        <td style="width: 52%;">
            <table><tr>
                <td style="width: 22mm;"><img class="logo" src="{{ $logoSrc }}" alt="AL MASSA"></td>
                <td>
                    <p class="co-name">AL MASSA AL MALAKIYA</p>
                    <p class="co-sub">BOXES AND PACKING IND. LLC</p>
                    <p class="co-sub">All Cosmetics &amp; Perfumes Hard, Soft Boxes and Paper Bags</p>
                </td>
            </tr></table>
        </td>
        <td style="width: 48%; text-align: right;">
            <div class="jc-title">JOB CARD</div>
            <div class="barcode">
                @foreach($bars as $i => $w)
                    <span class="{{ $i % 2 ? 'g' : '' }}" style="width: {{ $w }}px;"></span>
                @endforeach
            </div>
            <div class="no-date">
                <span class="lbl">Job No:</span> <span class="ln" style="min-width:75px">{{ $orderNo }}</span>
                &nbsp;&nbsp;<span class="lbl">Date:</span> <span class="ln" style="min-width:60px">{{ $orderDate }}</span>
            </div>
        </td>
    </tr>
</table>

{{-- SUMMARY (only filled) --}}
<table class="summary">
    <tr>
        @if($hasValue($card->product ?: $job->title))
            <td><span class="summary-label">Product</span><span class="summary-value">{{ $card->product ?: $job->title }}</span></td>
        @endif
        @if($hasValue($card->order_qty))
            <td><span class="summary-label">Order Quantity</span><span class="summary-value">{{ $card->order_qty }}</span></td>
        @endif
        <td><span class="summary-label">Priority</span><span class="badge {{ $priorityClass }}">{{ $priority }}</span></td>
        @if($hasValue($date($job->due_date)))
            <td><span class="summary-label">Deadline</span><span class="summary-value deadline">{{ $date($job->due_date) }}</span></td>
        @endif
    </tr>
</table>

{{-- JOB INFORMATION (only filled) --}}
@if(count($jobInfo))
<div class="section">
    <div class="bar">Job Information</div>
    <table class="details">
        @foreach($jobInfoChunks as $chunk)
            <tr>
                @foreach($chunk as [$label, $value])
                    <th>{{ $label }}</th>
                    <td>
                        @if($label === 'Priority')
                            <span class="badge {{ $priorityClass }}">{{ $priority }}</span>
                        @elseif($label === 'Deadline')
                            <span class="deadline">{{ $value }}</span>
                        @else
                            {{ $value }}
                        @endif
                    </td>
                @endforeach
                @if(count($chunk) === 1)<th></th><td></td>@endif
            </tr>
        @endforeach
    </table>
</div>
@endif

{{-- PRODUCTION SECTIONS — stock in the middle, Quality + Timeline at the end --}}
@foreach($renderOrder as $key)

    @if($key === '__stock__')
        @if($enabled('stock') && $hasStocks)
            <div class="section">
                <div class="bar">Paper / Board / Stock</div>
                <div class="stock-wrap">
                    <table class="stocks">
                        <thead>
                        <tr>
                            <th>Material</th>
                            <th>GSM</th>
                            <th>Sheet Size<br>L × W</th>
                            <th>Sheet Qty</th>
                            <th>Wastage</th>
                            <th>Cutting Size<br>L × W</th>
                            <th>Total Sheets</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($stocks as $stock)
                            <tr>
                                <td>{{ $stock->material }}</td>
                                <td>{{ $stock->gsm }}</td>
                                <td>{{ $stock->sheet_l }} × {{ $stock->sheet_w }}</td>
                                <td>{{ $stock->sheet_qty }}</td>
                                <td>{{ $stock->wastage }}</td>
                                <td>{{ $stock->cutting_l }} × {{ $stock->cutting_w }}</td>
                                <td>{{ $stock->total_sheets }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @elseif($enabled($key))
        @php
            [$title, $chunks] = $filledSections[$key];
        @endphp
        <div class="section">
            <div class="bar">{{ $title }}</div>
            <table class="details">
                @foreach($chunks as $chunk)
                    <tr>
                        @foreach($chunk as [$label, $value])
                            <th>{{ $label }}</th>
                            <td>@if($value === '__BLANK__')&nbsp;@else{{ $value }}@endif</td>
                        @endforeach
                        @if(count($chunk) === 1)<th></th><td></td>@endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

@endforeach

{{-- PROCUREMENT LIST — final job-card section; extra rows may continue onto another page. --}}
<div class="section material-section">
    <div class="bar">Procurement List</div>
    <div class="material-wrap">
        <table class="materials">
            <thead><tr>
                <th style="width:5%">#</th>
                <th style="width:24%">Items</th>
                <th style="width:22%">Specs</th>
                <th style="width:10%">Qty</th>
                <th style="width:15%">Needed By</th>
                <th style="width:24%">Remarks</th>
            </tr></thead>
            <tbody>
                @foreach($materials as $material)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $material->item }}</td>
                        <td>{{ $material->specs }}</td>
                        <td>{{ $material->qty }}</td>
                        <td>{{ $date($material->needed_by) }}</td>
                        <td>{{ $material->remarks }}</td>
                    </tr>
                @endforeach
                @for($i = $materials->count(); $i < 1; $i++)
                    <tr><td>{{ $i + 1 }}</td><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>

{{-- SIGNATURES --}}
<table class="sign">
    <tr>
        <td><div class="sl">Production Supervisor</div></td>
        <td><div class="sl">Quality Control</div></td>
        <td><div class="sl">Final Approval</div></td>
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
