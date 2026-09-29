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

    $timeRow = [
        ['Start Time', '________________'],
        ['End Time', '________________']
    ];

    $durationRow = [
        ['Total Time (hr)', '________'],
        ['Total Time (min)', '________']
    ];

    $sections = [
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
                $timeRow,
                $durationRow,
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
                $timeRow,
                $durationRow,
            ]
        ],

        'screen' => [
            'Screen Printing / Spot UV',
            [
                [
                    ['Colors', $card->screen_colors],
                    ['Spot UV', $card->screen_uv ? 'Yes' : 'No']
                ],
                $timeRow,
                $durationRow,
            ]
        ],

        'foiling' => [
            'Foiling',
            [
                [
                    [
                        'Gold',
                        $card->foil_gold
                            ? ($card->foil_gold_shade ?: 'Yes')
                            : 'No'
                    ],
                    [
                        'Silver',
                        $card->foil_silver
                            ? ($card->foil_silver_shade ?: 'Yes')
                            : 'No'
                    ]
                ],
                [
                    [
                        'Other',
                        $card->foil_other
                            ? ($card->foil_other_shade ?: 'Yes')
                            : 'No'
                    ],
                    ['', '']
                ],
                $timeRow,
                $durationRow,
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
                $timeRow,
                $durationRow,
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
                $timeRow,
                $durationRow,
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

    $priority = $card->priority_critical
        ? 'CRITICAL'
        : ($card->priority_urgent ? 'URGENT' : 'REGULAR');

    $priorityClass = $card->priority_critical
        ? 'badge-danger'
        : ($card->priority_urgent ? 'badge-warning' : 'badge-success');
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <title>
        Production Job Card - {{ $job->job_number }}
    </title>

    <style>
        @page {
            margin: 10mm 10mm 12mm 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            line-height: 1.45;
            color: #182436;
            background: #ffffff;
        }

        .page {
            width: 100%;
        }

        /* =========================
           HEADER
        ========================== */

        .top-header {
            width: 100%;
            margin-bottom: 11px;
        }

        .top-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-header-table td {
            vertical-align: middle;
        }

        .title-area {
            width: 66%;
        }

        .title-label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: #ef5b2a;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .title {
            margin: 0;
            color: #132033;
            font-size: 19pt;
            line-height: 1.05;
            font-weight: 700;
        }

        .subtitle {
            margin-top: 4px;
            color: #69778a;
            font-size: 7.5pt;
        }

        .job-number-box {
            width: 34%;
            text-align: right;
        }

        .job-number-card {
            display: inline-block;
            border: 1px solid #dde4ec;
            background: #f7f9fb;
            padding: 8px 11px;
            min-width: 155px;
            text-align: left;
            border-radius: 4px;
        }

        .job-number-label {
            font-size: 6.5pt;
            color: #8894a4;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: .7px;
        }

        .job-number {
            color: #152238;
            font-size: 11pt;
            font-weight: 700;
            margin-top: 1px;
        }

        .accent-line {
            height: 3px;
            background: #ef5b2a;
            margin-top: 7px;
        }

        /* =========================
           SUMMARY CARDS
        ========================== */

        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px 0;
            margin: 0 -4px 11px -4px;
        }

        .summary td {
            width: 25%;
            padding: 7px 8px;
            border: 1px solid #e0e6ed;
            background: #f8fafc;
            vertical-align: top;
        }

        .summary-label {
            display: block;
            font-size: 6.3pt;
            text-transform: uppercase;
            color: #8490a0;
            font-weight: 700;
            letter-spacing: .4px;
            margin-bottom: 2px;
        }

        .summary-value {
            font-size: 8.5pt;
            color: #172337;
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 10px;
            font-size: 6.5pt;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .badge-danger {
            color: #991b1b;
            background: #fee2e2;
        }

        .badge-warning {
            color: #92400e;
            background: #fef3c7;
        }

        .badge-success {
            color: #166534;
            background: #dcfce7;
        }

        /* =========================
           SECTIONS
        ========================== */

        .section {
            margin-bottom: 8px;
            border: 1px solid #dde4eb;
            border-radius: 3px;
            page-break-inside: avoid;
            overflow: hidden;
        }

        .section-title {
            width: 100%;
            border-collapse: collapse;
            background: #f4f6f8;
        }

        .section-title td {
            padding: 6px 8px;
            border-bottom: 1px solid #dde4eb;
        }

        .section-title-accent {
            width: 4px;
            background: #ef5b2a;
            padding: 0 !important;
        }

        .section-title-text {
            font-size: 8.7pt;
            color: #172337;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: .35px;
        }

        /* =========================
           DETAILS TABLE
        ========================== */

        .details {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .details tr:nth-child(even) {
            background: #fbfcfd;
        }

        .details th,
        .details td {
            padding: 6px 8px;
            border-bottom: 1px solid #edf0f3;
            vertical-align: top;
            text-align: left;
            overflow-wrap: break-word;
        }

        .details tr:last-child th,
        .details tr:last-child td {
            border-bottom: 0;
        }

        .details th {
            width: 18%;
            color: #667489;
            font-size: 6.8pt;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: .2px;
            background: #fafbfd;
        }

        .details td {
            width: 32%;
            color: #172337;
            font-size: 8pt;
            font-weight: 600;
            white-space: pre-line;
        }

        /* =========================
           STOCK TABLE
        ========================== */

        .stock-wrap {
            padding: 7px;
        }

        .stocks {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .stocks th {
            padding: 6px 5px;
            background: #25344a;
            color: #ffffff;
            font-size: 6.2pt;
            text-transform: uppercase;
            font-weight: 700;
            line-height: 1.35;
            text-align: left;
            border: 1px solid #25344a;
        }

        .stocks td {
            padding: 6px 5px;
            border: 1px solid #dfe5eb;
            color: #27364a;
            font-size: 7pt;
            vertical-align: middle;
            overflow-wrap: break-word;
        }

        .stocks tbody tr:nth-child(even) {
            background: #f7f9fb;
        }

        /* =========================
           BLANK FIELDS
        ========================== */

        .blank {
            display: inline-block;
            min-width: 85px;
            min-height: 10px;
            border-bottom: 1px solid #9ba7b6;
        }

        /* =========================
           FOOTER NOTE
        ========================== */

        .footer-note {
            margin-top: 11px;
            padding: 7px 9px;
            background: #fff7f3;
            border-left: 3px solid #ef5b2a;
            color: #69778a;
            font-size: 6.8pt;
        }

        .footer-table {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
        }

        .footer-table td {
            width: 33.33%;
            padding: 0 8px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #8995a5;
            padding-top: 4px;
            margin-top: 20px;
            color: #788495;
            font-size: 6.5pt;
            text-transform: uppercase;
        }

        .muted {
            color: #8290a2;
        }

        .text-orange {
            color: #ef5b2a;
        }
    </style>
</head>

<body>
<div class="page">

    {{-- HEADER --}}
    <div class="top-header">
        <table class="top-header-table">
            <tr>
                <td class="title-area">
                    <div class="title-label">
                        Production Department
                    </div>

                    <h1 class="title">
                        Production Job Card
                    </h1>

                    <div class="subtitle">
                        Complete production specification, process tracking
                        and quality control record
                    </div>
                </td>

                <td class="job-number-box">
                    <div class="job-number-card">
                        <div class="job-number-label">
                            Job Number
                        </div>

                        <div class="job-number">
                            {{ $card->job_no ?: $job->job_number }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="accent-line"></div>
    </div>


    {{-- SUMMARY --}}
    <table class="summary">
        <tr>
            <td>
                <span class="summary-label">
                    Product
                </span>

                <span class="summary-value">
                    {{ $card->product ?: $job->title ?: '—' }}
                </span>
            </td>

            <td>
                <span class="summary-label">
                    Order Quantity
                </span>

                <span class="summary-value">
                    {{ $card->order_qty ?: '—' }}
                </span>
            </td>

            <td>
                <span class="summary-label">
                    Priority
                </span>

                <span class="badge {{ $priorityClass }}">
                    {{ $priority }}
                </span>
            </td>

            <td>
                <span class="summary-label">
                    Deadline
                </span>

                <span class="summary-value">
                    {{ $date($job->due_date) ?: '—' }}
                </span>
            </td>
        </tr>
    </table>


    {{-- JOB HEADER --}}
    <div class="section">

        <table class="section-title">
            <tr>
                <td class="section-title-accent"></td>

                <td class="section-title-text">
                    Job Information
                </td>
            </tr>
        </table>

        <table class="details">
            <tr>
                <th>Job Assigned Date</th>
                <td>
                    {{ $date($card->job_date ?: $job->created_at) }}
                </td>

                <th>Job No.</th>
                <td>
                    {{ $card->job_no ?: $job->job_number }}
                </td>
            </tr>

            <tr>
                <th>Product</th>
                <td>
                    {{ $card->product ?: $job->title }}
                </td>

                <th>Order Qty</th>
                <td>
                    {{ $card->order_qty }}
                </td>
            </tr>

            <tr>
                <th>Priority</th>
                <td>
                    <span class="badge {{ $priorityClass }}">
                        {{ $priority }}
                    </span>
                </td>

                <th>Job Start On</th>
                <td>
                    {{ $date($card->job_start_on) ?: '—' }}
                </td>
            </tr>

            <tr>
                <th>Deadline</th>
                <td>
                    {{ $date($job->due_date) ?: '—' }}
                </td>

                <th>Designer</th>
                <td>
                    {{ $job->designer->name ?? '—' }}
                </td>
            </tr>
        </table>
    </div>


    {{-- PRODUCTION SECTIONS --}}
    @foreach($sections as $key => [$title, $rows])

        @if($enabled($key))
            <div class="section">

                <table class="section-title">
                    <tr>
                        <td class="section-title-accent"></td>

                        <td class="section-title-text">
                            {{ $title }}
                        </td>
                    </tr>
                </table>

                <table class="details">
                    @foreach($rows as $row)
                        <tr>
                            @foreach($row as [$label, $value])

                                <th>
                                    {{ $label }}
                                </th>

                                <td>
                                    @if($value !== null && $value !== '')
                                        {{ $value }}
                                    @else
                                        <span class="blank"></span>
                                    @endif
                                </td>

                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif


        {{-- STOCK SECTION --}}
        @if($key === 'briefing' && $enabled('stock'))

            <div class="section">

                <table class="section-title">
                    <tr>
                        <td class="section-title-accent"></td>

                        <td class="section-title-text">
                            Paper / Board / Stock
                        </td>
                    </tr>
                </table>

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

                        @forelse($stocks as $stock)

                            <tr>
                                <td>
                                    {{ $stock->material }}
                                </td>

                                <td>
                                    {{ $stock->gsm }}
                                </td>

                                <td>
                                    {{ $stock->sheet_l }}
                                    ×
                                    {{ $stock->sheet_w }}
                                </td>

                                <td>
                                    {{ $stock->sheet_qty }}
                                </td>

                                <td>
                                    {{ $stock->wastage }}
                                </td>

                                <td>
                                    {{ $stock->cutting_l }}
                                    ×
                                    {{ $stock->cutting_w }}
                                </td>

                                <td>
                                    {{ $stock->total_sheets }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td style="height: 28px;"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>

                        @endforelse

                        </tbody>
                    </table>
                </div>
            </div>

        @endif

    @endforeach


    {{-- MANUAL APPROVALS --}}
    <table class="footer-table">
        <tr>
            <td>
                <div class="signature-line">
                    Production Supervisor
                </div>
            </td>

            <td>
                <div class="signature-line">
                    Quality Control
                </div>
            </td>

            <td>
                <div class="signature-line">
                    Final Approval
                </div>
            </td>
        </tr>
    </table>


    <div class="footer-note">
        <strong class="text-orange">Production Copy:</strong>
        Complete blank time, duration and approval fields manually where
        required. This document serves as the official production workflow
        and quality-control record for this job.
    </div>

</div>
</body>
</html>
