@extends('crm.layout')
@section('title', $ticket->ticket_number)
@section('content')
@php
    $u = Auth::guard('crm')->user();
    $canEstimate = $u->isAdmin() || ($u->isEstimator() && (int)$ticket->estimator_id === (int)$u->id);
    $canClaim = ($u->isEstimator() || $u->isAdmin()) && $ticket->status === 'pending';
    $canEditEstimate = $canEstimate && in_array($ticket->status, ['open', 'revision_requested']);
    $isRequester = $u->isSales() || $u->isSalesManager() || $u->isAdmin();
    $currency = $ticket->currency ?: 'AED';

    $breakdown = is_array($ticket->cost_breakdown) ? $ticket->cost_breakdown : [];
    $savedLines = collect($breakdown)->filter(function ($item) {
        return ($item['section'] ?? 'variable') !== 'production';
    })->values();

    $defaultItems = [
        'Paper', 'Gray Board', 'E Flute', 'Food Board', 'Matte Card', 'Size Color',
        'Printing Cost', 'Lamination', 'Rubber', 'Sponge', 'Foam', 'Yarn Cotton',
        'Block', 'Film Position', 'Die', 'Die Cutting', 'Foiling', 'Embossing / De Embossing',
        'Bison Kit', 'Foil', 'Gray Board Gluing', 'Diary Head', 'Magnet', 'Ribbon',
        'Glue', 'Pasting', 'Making Cost', 'Wastage', 'Packing & Delivery',
    ];
    if ($savedLines->isNotEmpty()) {
        $costLines = $savedLines->map(function ($item) {
            return [
                'name' => $item['name'] ?? '',
                'detail' => $item['detail'] ?? '',
                'unit_price' => $item['unit_price'] ?? '',
                'quantity' => $item['quantity'] ?? '',
                'price' => $item['price'] ?? '',
            ];
        })->all();
    } else {
        $costLines = array_map(function ($name) {
            return ['name' => $name, 'detail' => '', 'unit_price' => '', 'quantity' => '', 'price' => ''];
        }, $defaultItems);
    }

    $firstOption = $ticket->options->first();
    $savedMargin = $firstOption && $firstOption->profit_margin_percentage !== null
        ? (float) $firstOption->profit_margin_percentage : '';
    $savedTotalCost = $savedLines->sum(function ($item) { return (float) ($item['price'] ?? 0); });
    $sizeText = $ticket->finish_size
        ?: (($ticket->length || $ticket->width || $ticket->height)
            ? rtrim(rtrim(number_format((float)$ticket->length, 2, '.', ''), '0'), '.').' × '.rtrim(rtrim(number_format((float)$ticket->width, 2, '.', ''), '0'), '.').' × '.rtrim(rtrim(number_format((float)$ticket->height, 2, '.', ''), '0'), '.').' '.$ticket->unit
            : '—');
    $printingText = trim(($ticket->printing ?: '').($ticket->colors ? ($ticket->printing ? ' + ' : '').$ticket->colors : '')) ?: '—';
    $attachmentUrl = function ($file) {
        // Docroot IS the public/ folder — strip any stored 'public/' prefix and
        // let asset() prepend APP_URL. Serves correctly from the web root.
        $relativePath = ltrim(preg_replace('#^public/#', '', (string) $file), '/');
        return asset($relativePath);
    };
    $attachmentFiles = collect($ticket->attachments ?: [])
        ->merge($ticket->lead ? $ticket->lead->inquiryAttachments()->pluck('file_path') : [])
        ->filter()->unique()->values();
    // Server-side initial figures so the read-only view shows real numbers (edit mode recalcs via JS on load).
    $savedVat = $ticket->lead && $ticket->lead->vat_percentage !== null
        ? (float) $ticket->lead->vat_percentage : 5;
    $initMarginAmount = $savedTotalCost * ((float)($savedMargin ?: 0)) / 100;
    $initFinal = $firstOption && $firstOption->offer_price !== null
        ? (float) $firstOption->offer_price
        : ($savedTotalCost + $initMarginAmount) * (1 + $savedVat / 100);
    $initVatAmount = $savedVat > 0 && $initFinal > 0
        ? $initFinal - ($initFinal / (1 + $savedVat / 100)) : 0;
    $initUnit = $firstOption && $firstOption->quantity > 0 && $initFinal > 0
        ? $initFinal / $firstOption->quantity : null;
    $statusMeta = [
        'pending' => ['New — Unclaimed', '#92400e', '#fef3c7', '#fcd34d'],
        'open' => ['Open', '#166534', '#dcfce7', '#86efac'],
        'revision_requested' => ['Revision Requested', '#9a3412', '#ffedd5', '#fdba74'],
        'team_lead_review' => ['In Review', '#1e40af', '#dbeafe', '#93c5fd'],
        'team_lead_open' => ['In Review', '#1e40af', '#dbeafe', '#93c5fd'],
        'owner_review' => ['Awaiting Owner Approval', '#a21caf', '#fdf4ff', '#f0abfc'],
        'estimated' => ['Estimated', '#166534', '#dcfce7', '#86efac'],
        'completed' => ['Completed', '#334155', '#f1f5f9', '#cbd5e1'],
        'returned_to_design' => ['Returned to Design', '#9a3412', '#ffedd5', '#fdba74'],
        'returned_to_sales' => ['Returned to Sales', '#9a3412', '#ffedd5', '#fdba74'],
    ];
    [$statusLabel, $statusColor, $statusBg, $statusBorder] = $statusMeta[$ticket->status] ?? [ucfirst($ticket->status), '#334155', '#f1f5f9', '#cbd5e1'];
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .ame-page { --ink: #0f172a; --body: #334155; --muted: #64748b; --faint: #94a3b8;
        --line: #e2e8f0; --line-soft: #f1f5f9; --surface: #ffffff; --well: #f8fafc;
        --red: var(--primary-purple, #b3282d); --red-deep: var(--primary-hover, #96191e);
        --red-soft: var(--primary-soft, #fdf2f2);
        --red-line: color-mix(in srgb, var(--primary-purple, #b3282d) 26%, white);
        --red-ring: color-mix(in srgb, var(--primary-purple, #b3282d) 10%, transparent);
        max-width: 1200px; margin: 0 auto; color: var(--body);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-feature-settings: 'cv11', 'tnum' 0; }
    .ame-page *, .ame-page *::before, .ame-page *::after { box-sizing: border-box; }

    /* ── Slim meta bar (layout top-bar already shows the ticket title) ── */
    .ame-head { display: flex; justify-content: space-between; align-items: center; gap: 1.25rem; flex-wrap: wrap;
        margin-bottom: 1.1rem; }
    .ame-head-left { display: flex; align-items: center; gap: .9rem; flex-wrap: wrap; }
    .ame-back { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 600;
        color: var(--muted); text-decoration: none; background: #fff; border: 1px solid var(--line);
        padding: .45rem .9rem; border-radius: 999px; transition: all .13s; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
    .ame-back:hover { color: var(--ink); border-color: var(--faint); }
    .ame-status-chip { font-size: .7rem; font-weight: 700; letter-spacing: .02em;
        padding: .32rem .75rem; border-radius: 999px; color: {{ $statusColor }}; background: {{ $statusBg }};
        border: 1px solid {{ $statusBorder }}; white-space: nowrap; }
    .ame-head-meta { font-size: .8rem; color: var(--muted); }
    .ame-head-meta strong { color: var(--body); font-weight: 600; }
    .ame-brandmark { text-align: right; }
    .ame-brandmark .ame-b1 { font-size: .92rem; font-weight: 800; letter-spacing: .01em; color: var(--ink); }
    .ame-brandmark .ame-b1 em { font-style: normal; color: var(--red); }
    .ame-brandmark .ame-b2 { font-size: .66rem; color: var(--faint); margin-top: .15rem; letter-spacing: .03em; }

    /* ── Banners ─────────────────────────────────────── */
    .ame-banner { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;
        font-size: .86rem; padding: .85rem 1.1rem; border-radius: 10px; margin-bottom: 1rem;
        background: #fffbeb; border: 1px solid #fde68a; color: #78350f; }
    .ame-banner.ame-ok { background: #f0fdf4; border-color: #bbf7d0; color: #14532d; }
    .ame-banner.ame-err { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }

    /* ── Cards ───────────────────────────────────────── */
    .ame-card { background: var(--surface); border: 1px solid var(--line); border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,.05), 0 8px 24px -16px rgba(15,23,42,.12);
        margin-bottom: 1.15rem; overflow: hidden; }
    .ame-card-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;
        padding: 1.05rem 1.4rem; border-bottom: 1px solid var(--line-soft); }
    .ame-card-title { font-size: .95rem; font-weight: 700; color: var(--ink); letter-spacing: -.01em; }
    .ame-card-sub { font-size: .74rem; color: var(--faint); }
    .ame-card-body { padding: 1.25rem 1.4rem 1.4rem; }

    /* ── Customer grid ───────────────────────────────── */
    .ame-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.05rem 1.5rem; }
    .ame-field label { display: block; font-size: .66rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .06em; color: var(--faint); margin-bottom: .3rem; }
    .ame-field .ame-val { font-size: .93rem; font-weight: 600; color: var(--ink); overflow-wrap: anywhere; }
    .ame-field .ame-val.ame-muted { color: var(--faint); font-weight: 400; }
    .ame-qty-note { font-size: .7rem; color: var(--muted); margin-top: .25rem; }

    /* ── Attachments from sales ──────────────────────── */
    .ame-files { margin-top: 1.15rem; border-top: 1px dashed var(--line); padding-top: .95rem; }
    .ame-files-label { font-size: .66rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em;
        color: var(--faint); margin-bottom: .55rem; }
    .ame-files-list { display: flex; flex-wrap: wrap; gap: .6rem; align-items: flex-start; }
    .ame-file-thumb { display: block; width: 96px; height: 96px; border: 1px solid var(--line); border-radius: 10px;
        overflow: hidden; background: var(--well); transition: all .14s; position: relative; }
    .ame-file-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ame-file-thumb:hover { border-color: var(--red); box-shadow: 0 4px 12px -4px rgba(15,23,42,.2); transform: translateY(-1px); }
    .ame-file-chip { display: inline-flex; align-items: center; gap: .45rem; max-width: 260px; overflow: hidden;
        text-overflow: ellipsis; white-space: nowrap; font-size: .78rem; font-weight: 600; color: var(--body);
        text-decoration: none; background: #fff; border: 1px solid var(--line); border-radius: 999px;
        padding: .5rem .9rem; transition: all .13s; }
    .ame-file-chip:hover { border-color: var(--red); color: var(--red); }
    .ame-file-chip i { color: var(--faint); }

    /* ── Cost table ──────────────────────────────────── */
    .ame-tablewrap { overflow-x: auto; }
    .ame-table { width: 100%; border-collapse: collapse; min-width: 760px; }
    .ame-table thead th { font-size: .66rem; text-transform: uppercase; letter-spacing: .07em; font-weight: 600;
        color: var(--muted); text-align: left; padding: .65rem 1.4rem; background: var(--well);
        border-bottom: 1px solid var(--line); }
    .ame-table thead th.ame-r, .ame-table td.ame-r { text-align: right; }
    .ame-table tbody td { padding: .4rem .8rem; border-bottom: 1px solid var(--line-soft); font-size: .89rem; vertical-align: middle; }
    .ame-table tbody td:first-child, .ame-table thead th:first-child { padding-left: 1.4rem; }
    .ame-table tbody td:last-child, .ame-table thead th:last-child { padding-right: 1.4rem; }
    .ame-table tbody tr:hover { background: #fcfcfd; }
    .ame-table tbody tr:focus-within { background: var(--red-soft); }
    .ame-in { width: 100%; border: 1px solid transparent; border-radius: 8px; background: transparent;
        padding: .55rem .6rem; font-size: .89rem; font-family: inherit; color: var(--ink); outline: none; transition: border-color .12s, background .12s, box-shadow .12s; }
    .ame-in:hover { background: var(--well); }
    .ame-in:focus { background: #fff; border-color: var(--red); box-shadow: 0 0 0 3px var(--red-ring); }
    .ame-in.ame-name { font-weight: 600; color: var(--ink); }
    .ame-in.ame-desc { color: var(--muted); }
    .ame-in.ame-num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 500; }
    .ame-in::placeholder { color: #cbd5e1; font-weight: 400; }
    .ame-total-cell { text-align: right; white-space: nowrap; }
    .ame-total-cell .ame-in { font-weight: 700; color: var(--ink); pointer-events: none; }
    .ame-x { border: none; background: none; color: #cbd5e1; cursor: pointer; font-size: 1.05rem; padding: .3rem .5rem;
        border-radius: 6px; transition: all .12s; line-height: 1; }
    .ame-x:hover { background: #fee2e2; color: var(--red); }
    .ame-c { text-align: center; }
    .ame-table-foot { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;
        padding: .85rem 1.4rem; border-top: 1px solid var(--line); background: var(--well); }
    .ame-add { border: 1px solid var(--line); background: #fff; color: var(--body); padding: .5rem 1rem;
        font-size: .8rem; font-weight: 600; font-family: inherit; border-radius: 8px; cursor: pointer; transition: all .13s;
        box-shadow: 0 1px 2px rgba(15,23,42,.05); }
    .ame-add:hover { border-color: var(--faint); color: var(--ink); }
    .ame-lines-total { font-size: .8rem; color: var(--muted); font-weight: 500; }
    .ame-lines-total strong { font-size: .98rem; color: var(--ink); font-weight: 700; font-variant-numeric: tabular-nums; margin-left: .4rem; }

    /* ── Pricing ─────────────────────────────────────── */
    .ame-pricing-grid { display: grid; grid-template-columns: 1.15fr .9fr; gap: 1.15rem; align-items: stretch; }
    @media (max-width: 920px) { .ame-pricing-grid { grid-template-columns: 1fr; } }
    .ame-notes-card { display: flex; flex-direction: column; }
    .ame-notes-card .ame-card-body { flex: 1; display: flex; }
    .ame-notes-card textarea { width: 100%; min-height: 180px; border: 1px solid var(--line); border-radius: 10px;
        background: #fff; padding: .85rem .95rem; font-size: .88rem; font-family: inherit; color: var(--ink);
        resize: vertical; outline: none; transition: all .12s; line-height: 1.6; }
    .ame-notes-card textarea:focus { border-color: var(--red); box-shadow: 0 0 0 3px var(--red-ring); }
    .ame-notes-card textarea::placeholder { color: #cbd5e1; }
    .ame-sumrow { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: .7rem 0;
        border-bottom: 1px solid var(--line-soft); }
    .ame-sumrow:first-child { padding-top: 0; }
    .ame-sumrow .ame-k { font-size: .8rem; color: var(--muted); font-weight: 500; }
    .ame-sumrow .ame-v { font-weight: 700; font-variant-numeric: tabular-nums; font-size: .95rem; color: var(--ink); }
    .ame-sumrow .ame-v small { font-size: .68rem; color: var(--faint); font-weight: 600; margin-left: .25rem; }
    .ame-margin-in { width: 110px; text-align: right; border: 1px solid var(--line); border-radius: 8px;
        background: #fff; color: var(--ink); padding: .5rem .65rem; font-size: .92rem; font-weight: 600; font-family: inherit;
        outline: none; font-variant-numeric: tabular-nums; transition: all .12s; }
    .ame-margin-in:focus { border-color: var(--red); box-shadow: 0 0 0 3px var(--red-ring); }
    .ame-margin-in::placeholder { color: #cbd5e1; font-weight: 400; }
    .ame-final { padding: .95rem 0 .4rem; border-bottom: none; }
    .ame-final .ame-k { font-size: .8rem; font-weight: 600; color: var(--body); }
    .ame-final .ame-v { font-size: 1.75rem; font-weight: 800; letter-spacing: -.02em; }
    .ame-unitline { display: flex; justify-content: space-between; align-items: center; margin-top: .75rem;
        background: var(--red-soft); border: 1px solid var(--red-line); border-left: 3px solid var(--red);
        border-radius: 10px; padding: .8rem 1rem; }
    .ame-unitline .ame-k { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--red); }
    .ame-unitline .ame-uv { font-size: 1.25rem; font-weight: 800; color: var(--red); font-variant-numeric: tabular-nums; line-height: 1; }
    .ame-unitline .ame-uv small { font-size: .66rem; font-weight: 600; }
    .ame-opts { margin-top: .75rem; border-top: 1px dashed var(--line); padding-top: .5rem; }
    .ame-opts .ame-sumrow { border-bottom: none; padding: .3rem 0; }

    /* ── Quantity options editor ─────────────────────── */
    .ame-qopts { margin-top: 1rem; border-top: 1px dashed var(--line); padding-top: .85rem; }
    .ame-qopts-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: .6rem; }
    .ame-qopts-title { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
    .ame-qopts-add { border: 1px solid var(--line); background: #fff; color: var(--body); padding: .35rem .75rem;
        font-size: .74rem; font-weight: 600; font-family: inherit; border-radius: 7px; cursor: pointer; transition: all .13s; }
    .ame-qopts-add:hover { border-color: var(--faint); color: var(--ink); }
    .ame-optrow { display: flex; align-items: center; gap: .6rem; padding: .4rem 0; flex-wrap: wrap; }
    .ame-optrow .ame-opt-qty { font-size: .82rem; font-weight: 700; color: var(--ink); min-width: 86px; }
    .ame-optrow .ame-opt-qty-in { width: 86px; text-align: right; border: 1px solid var(--line); border-radius: 8px;
        background: #fff; color: var(--ink); padding: .45rem .55rem; font-size: .85rem; font-weight: 600; font-family: inherit;
        outline: none; font-variant-numeric: tabular-nums; transition: all .12s; }
    .ame-optrow .ame-opt-qty-in:focus { border-color: var(--red); box-shadow: 0 0 0 3px var(--red-ring); }
    .ame-optrow .ame-opt-cost { width: 110px; text-align: right; border: 1px solid var(--line); border-radius: 8px;
        background: #fff; color: var(--ink); padding: .45rem .55rem; font-size: .85rem; font-weight: 600; font-family: inherit;
        outline: none; font-variant-numeric: tabular-nums; transition: all .12s; }
    .ame-optrow .ame-opt-cost:focus { border-color: var(--red); box-shadow: 0 0 0 3px var(--red-ring); }
    .ame-optrow .ame-opt-cost[data-auto="1"] { background: var(--well); }
    .ame-optrow .ame-opt-calc { flex: 1; text-align: right; font-size: .82rem; font-weight: 600; color: var(--ink);
        font-variant-numeric: tabular-nums; white-space: nowrap; }
    .ame-optrow .ame-opt-calc small { color: var(--muted); font-weight: 500; }
    .ame-optrow .ame-opt-label { font-size: .66rem; color: var(--faint); font-weight: 600; }
    .ame-qopts-hint { font-size: .68rem; color: var(--faint); margin-top: .35rem; }

    /* ── Actions ─────────────────────────────────────── */
    .ame-actions { display: flex; justify-content: flex-end; align-items: center; gap: .7rem; flex-wrap: wrap;
        margin-top: .35rem; padding: 1.1rem 0 .2rem; }
    .ame-btn { padding: .72rem 1.4rem; font-size: .86rem; font-weight: 600; font-family: inherit; cursor: pointer;
        border-radius: 9px; border: 1px solid transparent; transition: all .15s; letter-spacing: .01em; }
    .ame-btn-ghost { background: transparent; color: var(--muted); }
    .ame-btn-ghost:hover { color: var(--ink); background: var(--line-soft); }
    .ame-btn-outline { background: #fff; color: var(--ink); border-color: var(--line); box-shadow: 0 1px 2px rgba(15,23,42,.05); }
    .ame-btn-outline:hover { border-color: var(--faint); }
    .ame-btn-red { background: var(--red); color: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.1), 0 8px 18px -8px var(--primary-shadow, rgba(150,25,30,.5)); }
    .ame-btn-red:hover { background: var(--red-deep); }

    /* ── Sales action box ────────────────────────────── */
    .ame-action-box { background: var(--surface); border: 1px solid var(--line); border-radius: 14px;
        padding: 1.2rem 1.4rem; margin-top: .15rem; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
    .ame-action-box .ame-box-title { font-size: .8rem; font-weight: 700; color: var(--ink); text-transform: uppercase; letter-spacing: .05em; }
    .ame-action-box textarea { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: .75rem .85rem;
        font-family: inherit; font-size: .87rem; margin: .65rem 0; outline: none; transition: all .12s; }
    .ame-action-box textarea:focus { border-color: var(--red); box-shadow: 0 0 0 3px var(--red-ring); }
    @media (max-width: 680px) { .ame-card-body, .ame-card-head { padding-left: 1rem; padding-right: 1rem; } }
</style>

<div class="ame-page">
    <div class="ame-head">
        <div class="ame-head-left">
            <a class="ame-back" href="{{ route('crm.estimate_tickets.index', ['tab' => in_array($ticket->status, ['estimated', 'completed']) ? 'history' : 'mine']) }}">&larr; All Estimates</a>
            <span class="ame-status-chip">{{ $statusLabel }}</span>
            <span class="ame-head-meta">Requested by <strong>{{ $ticket->requester->name ?? 'Sales' }}</strong> · {{ $ticket->created_at->format('d M Y, h:i A') }}</span>
        </div>
        <div class="ame-brandmark">
            <div class="ame-b1">AL MASSA <em>AL MALAKIYA</em></div>
            <div class="ame-b2">Estimation System · Packaging &amp; Packing Mat. Ind. L.L.C</div>
        </div>
    </div>

    @if(session('success'))<div class="ame-banner ame-ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="ame-banner ame-err">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="ame-banner ame-err">{{ $errors->first() }}</div>@endif

    @if($canClaim)
        <div class="ame-banner">
            <span>This estimate ticket is unclaimed. Open it to start costing.</span>
            <form method="POST" action="{{ route('crm.estimate_tickets.claim', $ticket->id) }}">{{ csrf_field() }}
                <button class="ame-btn ame-btn-red" type="submit">Open &amp; Claim Ticket</button>
            </form>
        </div>
    @endif
    @if($ticket->status === 'revision_requested' && $canEstimate)
        <div class="ame-banner"><span><strong>Revision requested:</strong> {{ \Illuminate\Support\Str::afterLast($ticket->requirements, 'Revision: ') }}</span></div>
    @endif

    <form method="POST" action="{{ route('crm.estimate_tickets.submit', $ticket->id) }}" id="alMassaEstimateForm">
        {{ csrf_field() }}
        <input type="hidden" name="currency" value="AED">

        <div class="ame-card">
            <div class="ame-card-head">
                <div><div class="ame-card-title">Customer &amp; Product</div></div>
                <div class="ame-card-sub">From the sales inquiry</div>
            </div>
            <div class="ame-card-body">
                <div class="ame-grid">
                    <div class="ame-field"><label>Client Name</label><div class="ame-val">{{ $ticket->client_name }}</div></div>
                    <div class="ame-field"><label>Customer Email</label><div class="ame-val {{ $ticket->client_email ? '' : 'ame-muted' }}">{{ $ticket->client_email ?: '—' }}</div></div>
                    <div class="ame-field"><label>Date</label><div class="ame-val">{{ $ticket->created_at->format('d/m/Y') }}</div></div>
                    <div class="ame-field"><label>Product / Job Name</label><div class="ame-val">{{ $ticket->product_style }}</div></div>
                    <div class="ame-field"><label>Finish Size</label><div class="ame-val {{ $sizeText === '—' ? 'ame-muted' : '' }}">{{ $sizeText }}</div></div>
                    @php $openSizeText = trim(($ticket->flat_size ?: optional($ticket->lead)->open_size ?: '').' '.((($ticket->flat_size ?: optional($ticket->lead)->open_size)) ? (optional($ticket->lead)->unit ?: $ticket->unit) : '')) ?: '—'; @endphp
                    <div class="ame-field"><label>Open / Flat Size</label><div class="ame-val {{ $openSizeText === '—' ? 'ame-muted' : '' }}">{{ $openSizeText }}</div></div>
                    <div class="ame-field"><label>Printing Color</label><div class="ame-val {{ $printingText === '—' ? 'ame-muted' : '' }}">{{ $printingText }}</div></div>
                    <div class="ame-field"><label>Total Quantity (pcs)</label>
                        <div class="ame-val">{{ $ticket->options->pluck('quantity')->map(function ($q) { return number_format($q); })->implode(' / ') ?: '—' }}</div>
                        @if($ticket->options->count() > 1)<div class="ame-qty-note">{{ $ticket->options->count() }} quantity options — margin applies to each.</div>@endif
                    </div>
                </div>
                @if($attachmentFiles->isNotEmpty())
                    <div class="ame-files">
                        <div class="ame-files-label">Attachments from Sales ({{ $attachmentFiles->count() }})</div>
                        <div class="ame-files-list">
                            @foreach($attachmentFiles as $file)
                                @php $isImage = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif']); @endphp
                                @if($isImage)
                                    <a class="ame-file-thumb" href="{{ $attachmentUrl($file) }}" target="_blank" rel="noopener" title="{{ basename($file) }}">
                                        <img src="{{ $attachmentUrl($file) }}" alt="{{ basename($file) }}" loading="lazy">
                                    </a>
                                @else
                                    <a class="ame-file-chip" href="{{ $attachmentUrl($file) }}" target="_blank" rel="noopener"><i class="fas fa-paperclip"></i>{{ basename($file) }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="ame-card">
            <div class="ame-card-head">
                <div><div class="ame-card-title">Cost Lines</div></div>
                <div class="ame-card-sub">Internal costing · Total = unit price × qty</div>
            </div>
            <div class="ame-tablewrap">
            <table class="ame-table">
                <thead><tr>
                    <th style="width:19%">Cost Item</th>
                    <th style="width:29%">Description</th>
                    <th class="ame-r" style="width:15%">Per Unit Price</th>
                    <th class="ame-r" style="width:12%">Qty</th>
                    <th class="ame-r" style="width:17%">Total Amount</th>
                    @if($canEditEstimate)<th style="width:48px"></th>@endif
                </tr></thead>
                <tbody id="ameCostRows">
                @foreach($costLines as $line)
                    <tr>
                        <td>
                            @if($canEditEstimate)
                                <input class="ame-in ame-name" name="cost_names[]" value="{{ $line['name'] }}" placeholder="Item name">
                            @else<span style="font-weight:600;color:var(--ink);padding:.55rem .6rem;display:inline-block">{{ $line['name'] }}</span>@endif
                            <input type="hidden" name="cost_sections[]" value="variable">
                        </td>
                        <td>@if($canEditEstimate)<input class="ame-in ame-desc" name="cost_details[]" value="{{ $line['detail'] }}" placeholder="—">@else<span style="color:var(--muted)">{{ $line['detail'] ?: '—' }}</span>@endif</td>
                        <td class="ame-r">@if($canEditEstimate)<input class="ame-in ame-num ame-unit" type="number" min="0" step=".0001" name="cost_unit_prices[]" value="{{ $line['unit_price'] !== '' && (float)$line['unit_price'] > 0 ? $line['unit_price'] : '' }}" placeholder="0.00">@else{{ (float)$line['unit_price'] > 0 ? number_format((float)$line['unit_price'], 2) : '—' }}@endif</td>
                        <td class="ame-r">@if($canEditEstimate)<input class="ame-in ame-num ame-qty" type="number" min="0" step=".01" name="cost_quantities[]" value="{{ $line['quantity'] }}" placeholder="0">@else{{ $line['quantity'] !== '' && $line['quantity'] !== null ? number_format((float)$line['quantity'], 2) : '—' }}@endif</td>
                        <td class="ame-total-cell">
                            @if($canEditEstimate)<input class="ame-in ame-num ame-line-total" type="number" name="cost_prices[]" value="{{ $line['price'] !== '' && (float)$line['price'] > 0 ? $line['price'] : '' }}" readonly tabindex="-1" placeholder="0.00">
                            @else<span style="font-weight:700;color:var(--ink)">{{ number_format((float)$line['price'], 2) }}</span>@endif
                        </td>
                        @if($canEditEstimate)<td class="ame-c"><button type="button" class="ame-x" onclick="ameRemoveRow(this)" aria-label="Remove line">&times;</button></td>@endif
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <div class="ame-table-foot">
                @if($canEditEstimate)<button type="button" class="ame-add" onclick="ameAddRow()">+ Add cost line</button>@else<span></span>@endif
                <div class="ame-lines-total">Cost lines total<strong id="ameLinesTotal">{{ number_format($savedTotalCost, 2) }}</strong> <span style="font-size:.7rem;color:var(--faint);font-weight:600">AED</span></div>
            </div>
        </div>

        <div class="ame-pricing-grid">
            <div class="ame-card ame-notes-card" style="margin-bottom:0">
                <div class="ame-card-head">
                    <div><div class="ame-card-title">Notes for the Customer</div></div>
                    <div class="ame-card-sub">Optional</div>
                </div>
                <div class="ame-card-body">
                    @if($canEditEstimate)
                        <textarea name="estimator_notes" placeholder="Delivery time, validity, payment terms...">{{ old('estimator_notes', $ticket->estimator_notes) }}</textarea>
                    @else
                        <div style="font-size:.88rem;white-space:pre-line;background:var(--well);border:1px solid var(--line);border-radius:10px;padding:.9rem 1rem;flex:1">{{ $ticket->estimator_notes ?: 'No notes.' }}</div>
                    @endif
                </div>
            </div>
            <div class="ame-card" style="margin-bottom:0">
                <div class="ame-card-head">
                    <div><div class="ame-card-title">Pricing</div></div>
                </div>
                <div class="ame-card-body">
                    <div class="ame-sumrow"><span class="ame-k">Total Cost</span><span class="ame-v"><span id="ameTotalCost">{{ number_format($savedTotalCost, 2) }}</span><small>AED</small></span></div>
                    <div class="ame-sumrow">
                        <span class="ame-k">Profit Margin %</span>
                        @if($canEditEstimate)
                            <input class="ame-margin-in" type="number" min="0" max="1000" step=".01" name="profit_margin_percentage" id="ameMargin" value="{{ old('profit_margin_percentage', $savedMargin) }}" placeholder="e.g. 20">
                        @else<span class="ame-v">{{ $savedMargin !== '' ? number_format((float)$savedMargin, 2) : '0.00' }}%</span>@endif
                    </div>
                    <div class="ame-sumrow"><span class="ame-k">Margin Amount</span><span class="ame-v"><span id="ameMarginAmount">{{ number_format($initMarginAmount, 2) }}</span><small>AED</small></span></div>
                    <div class="ame-sumrow">
                        <span class="ame-k">VAT %</span>
                        @if($canEditEstimate)
                            <input class="ame-margin-in" type="number" min="0" max="100" step=".01" name="vat_percentage" id="ameVat" value="{{ old('vat_percentage', $savedVat) }}" placeholder="e.g. 5">
                        @else<span class="ame-v">{{ number_format($savedVat, 2) }}%</span>@endif
                    </div>
                    <div class="ame-sumrow"><span class="ame-k">VAT Amount</span><span class="ame-v"><span id="ameVatAmount">{{ number_format($initVatAmount, 2) }}</span><small>AED</small></span></div>
                    <div class="ame-sumrow ame-final"><span class="ame-k">Final Price (Incl. VAT)</span><span class="ame-v"><span id="ameFinalPrice">{{ number_format($initFinal, 2) }}</span><small>AED</small></span></div>
                    <div class="ame-unitline">
                        <span class="ame-k">Unit Price</span>
                        <span class="ame-uv"><span id="ameUnitPrice">{{ $initUnit !== null ? number_format($initUnit, 2) : '—' }}</span> <small>AED/pc <span id="ameUnitQty">@if($firstOption)· qty {{ number_format($firstOption->quantity) }}@endif</span></small></span>
                    </div>

                    @if($canEditEstimate)
                        <div class="ame-qopts">
                            <div class="ame-qopts-head">
                                <span class="ame-qopts-title">Quantity Options</span>
                                <button type="button" class="ame-qopts-add" onclick="ameAddOption()">+ Add quantity</button>
                            </div>
                            <div id="ameOptionList">
                                @foreach($ticket->options as $option)
                                    @php
                                        $savedPrice = $option->total_price;
                                        $isAuto = $savedPrice === null || abs((float)$savedPrice - $savedTotalCost) < 0.01;
                                    @endphp
                                    <div class="ame-optrow" data-qty="{{ (int) $option->quantity }}">
                                        <span class="ame-opt-qty">{{ number_format($option->quantity) }} pcs</span>
                                        <span class="ame-opt-label">COST</span>
                                        <input class="ame-opt-cost" type="number" min="0" step=".01" name="prices[{{ $option->id }}]"
                                            value="{{ $savedPrice !== null && !$isAuto ? $savedPrice : '' }}" placeholder="auto" data-auto="{{ $isAuto ? '1' : '0' }}">
                                        <span class="ame-opt-calc"><span class="ame-opt-final">—</span> AED &nbsp;<small>· <span class="ame-opt-unit">—</span>/pc</small></span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="ame-qopts-hint">Cost auto-follows the cost lines total — type a value to override for that quantity. Final = cost + margin + VAT.</div>
                        </div>
                    @elseif($ticket->options->count() > 1)
                        <div class="ame-opts" id="ameOptionRows">
                            @foreach($ticket->options as $option)
                                <div class="ame-sumrow">
                                    <span class="ame-k">Qty {{ number_format($option->quantity) }}</span>
                                    <span class="ame-v" style="font-weight:600;font-size:.85rem">{{ $option->total_price !== null ? number_format((float)$option->total_price, 2) : '—' }} AED cost @if($option->offer_price !== null) &nbsp;·&nbsp; offer {{ number_format((float)$option->offer_price, 2) }} AED @endif</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($canEditEstimate)
            <div class="ame-actions">
                <button type="button" class="ame-btn ame-btn-ghost" onclick="ameClearForm()">Clear form</button>
                <button type="submit" class="ame-btn ame-btn-outline" name="save_mode" value="draft" formnovalidate>Save estimate</button>
                <button type="submit" class="ame-btn ame-btn-red">Save &amp; send to customer &rarr;</button>
            </div>
        @endif
    </form>

    @if(!$canEditEstimate && in_array($ticket->status, ['owner_review', 'estimated']))
        @php $firstOffer = $ticket->options->firstWhere('offer_price', '!=', null); @endphp
        @if($firstOffer)
            <div class="ame-action-box" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                <span class="ame-box-title">{{ $ticket->status === 'owner_review' ? 'Quoted Offer (awaiting approval)' : 'Final Offer' }}</span>
                <strong style="color:var(--ink)">{{ $ticket->options->map(function ($o) { return number_format($o->quantity).' pcs → '.number_format((float)$o->offer_price, 2).' AED'; })->implode(' · ') }}</strong>
            </div>
        @endif
    @endif

    @if(($u->isAdmin() || $u->isSuperAdmin() || $u->isSalesManager()) && $ticket->status === 'owner_review')
        <div class="ame-action-box" style="margin-top:1.15rem">
            <span class="ame-box-title">Approval — Admin / Owner</span>
            <div style="font-size:.82rem;color:var(--muted);margin-top:.4rem">Review the quote above. Approving releases it to sales for the customer offer.</div>
            <div style="display:flex;gap:.85rem;flex-wrap:wrap;margin-top:.85rem">
                <form method="POST" action="{{ route('crm.estimate_tickets.owner_approve', $ticket->id) }}">{{ csrf_field() }}
                    <button class="ame-btn ame-btn-red" type="submit">Approve &amp; Release to Sales</button>
                </form>
            </div>
            <form method="POST" action="{{ route('crm.estimate_tickets.revision', $ticket->id) }}" style="margin-top:.9rem">{{ csrf_field() }}
                <textarea name="revision_note" placeholder="Send back to the estimator with a note..." required></textarea>
                <button class="ame-btn ame-btn-outline" type="submit">Return to Estimator</button>
            </form>
        </div>
    @endif

    @if($isRequester && !$canEstimate && $ticket->status === 'estimated')
        <div class="ame-action-box" style="margin-top:1.15rem">
            <span class="ame-box-title">Sales Actions</span>
            <div style="display:flex;gap:.85rem;flex-wrap:wrap;margin-top:.8rem">
                @if($ticket->crm_email_id)
                    <form method="POST" action="{{ route('crm.estimate_tickets.send_chat', $ticket->id) }}">{{ csrf_field() }}
                        <button class="ame-btn ame-btn-red" type="submit">Prepare PDF in Client Reply</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('crm.estimate_tickets.complete', $ticket->id) }}">{{ csrf_field() }}
                    <button class="ame-btn ame-btn-outline" type="submit">Approve &amp; Complete Ticket</button>
                </form>
            </div>
            <form method="POST" action="{{ route('crm.estimate_tickets.revision', $ticket->id) }}" style="margin-top:.9rem">{{ csrf_field() }}
                <textarea name="revision_note" placeholder="Tell the estimator what needs to change..." required></textarea>
                <button class="ame-btn ame-btn-ghost" type="submit">Request Revision</button>
            </form>
        </div>
    @endif
    @if($isRequester && $ticket->status === 'completed' && $ticket->crm_email_id)
        <div class="ame-action-box" style="margin-top:1.15rem">
            <form method="POST" action="{{ route('crm.estimate_tickets.send_chat', $ticket->id) }}">{{ csrf_field() }}
                <button class="ame-btn ame-btn-red" type="submit">Prepare PDF in Client Reply</button>
            </form>
        </div>
    @endif
</div>

@if($canEditEstimate)
<script>
    function ameRecalc() {
        var total = 0;
        document.querySelectorAll('#ameCostRows tr').forEach(function (row) {
            var unit = parseFloat(row.querySelector('.ame-unit').value) || 0;
            var qty = parseFloat(row.querySelector('.ame-qty').value) || 0;
            var line = unit * qty;
            row.querySelector('.ame-line-total').value = line > 0 ? line.toFixed(2) : '';
            total += line;
        });
        var margin = parseFloat(document.getElementById('ameMargin').value) || 0;
        var vat = parseFloat(document.getElementById('ameVat').value) || 0;
        document.getElementById('ameLinesTotal').textContent = ameFmt(total);
        document.getElementById('ameTotalCost').textContent = ameFmt(total);
        document.getElementById('ameMarginAmount').textContent = ameFmt(total * margin / 100);
        document.getElementById('ameVatAmount').textContent = ameFmt(total * (1 + margin / 100) * vat / 100);

        var firstFinal = 0, firstQty = 0;
        document.querySelectorAll('#ameOptionList .ame-optrow').forEach(function (row, index) {
            var qtyInput = row.querySelector('.ame-opt-qty-in');
            var qty = qtyInput ? (parseInt(qtyInput.value, 10) || 0) : (parseInt(row.dataset.qty, 10) || 0);
            var costInput = row.querySelector('.ame-opt-cost');
            if (costInput.dataset.auto === '1') costInput.value = total > 0 ? total.toFixed(2) : '';
            var cost = parseFloat(costInput.value) || 0;
            var finalPrice = cost * (1 + margin / 100) * (1 + vat / 100);
            row.querySelector('.ame-opt-final').textContent = ameFmt(finalPrice);
            row.querySelector('.ame-opt-unit').textContent = qty > 0 && finalPrice > 0 ? ameFmt(finalPrice / qty) : '—';
            if (index === 0) { firstFinal = finalPrice; firstQty = qty; }
        });
        document.getElementById('ameFinalPrice').textContent = ameFmt(firstFinal);
        document.getElementById('ameUnitPrice').textContent = firstQty > 0 && firstFinal > 0 ? ameFmt(firstFinal / firstQty) : '—';
        document.getElementById('ameUnitQty').textContent = firstQty > 0 ? '· qty ' + firstQty.toLocaleString('en-US') : '';
    }

    function ameAddOption() {
        var list = document.getElementById('ameOptionList');
        var row = document.createElement('div');
        row.className = 'ame-optrow';
        row.innerHTML = '<input class="ame-opt-qty-in" type="number" min="1" step="1" name="new_quantities[]" placeholder="Qty">'
            + '<span class="ame-opt-label">COST</span>'
            + '<input class="ame-opt-cost" type="number" min="0" step=".01" name="new_prices[]" placeholder="auto" data-auto="1">'
            + '<span class="ame-opt-calc"><span class="ame-opt-final">—</span> AED &nbsp;<small>· <span class="ame-opt-unit">—</span>/pc</small></span>'
            + '<button type="button" class="ame-x" style="color:#cbd5e1" onclick="this.closest(\'.ame-optrow\').remove();ameRecalc()" aria-label="Remove option">&times;</button>';
        list.appendChild(row);
        row.querySelector('.ame-opt-qty-in').focus();
        ameRecalc();
    }

    function ameFmt(value) {
        return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function ameAddRow() {
        var tbody = document.getElementById('ameCostRows');
        var row = document.createElement('tr');
        row.innerHTML = '<td><input class="ame-in ame-name" name="cost_names[]" value="" placeholder="Item name"><input type="hidden" name="cost_sections[]" value="variable"></td>'
            + '<td><input class="ame-in ame-desc" name="cost_details[]" value="" placeholder="—"></td>'
            + '<td class="ame-r"><input class="ame-in ame-num ame-unit" type="number" min="0" step=".0001" name="cost_unit_prices[]" placeholder="0.00"></td>'
            + '<td class="ame-r"><input class="ame-in ame-num ame-qty" type="number" min="0" step=".01" name="cost_quantities[]" placeholder="0"></td>'
            + '<td class="ame-total-cell"><input class="ame-in ame-num ame-line-total" type="number" name="cost_prices[]" readonly tabindex="-1" placeholder="0.00"></td>'
            + '<td class="ame-c"><button type="button" class="ame-x" onclick="ameRemoveRow(this)" aria-label="Remove line">&times;</button></td>';
        tbody.appendChild(row);
        row.querySelector('.ame-name').focus();
    }

    function ameRemoveRow(btn) {
        var tbody = document.getElementById('ameCostRows');
        if (tbody.querySelectorAll('tr').length <= 1) return;
        btn.closest('tr').remove();
        ameRecalc();
    }

    function ameClearForm() {
        if (!confirm('Clear all cost lines and pricing?')) return;
        document.querySelectorAll('#ameCostRows .ame-in:not(.ame-name), #ameMargin').forEach(function (input) { input.value = ''; });
        document.getElementById('ameVat').value = 5;
        document.querySelector('#alMassaEstimateForm textarea[name="estimator_notes"]').value = '';
        document.querySelectorAll('#ameOptionList .ame-optrow').forEach(function (row) {
            var costInput = row.querySelector('.ame-opt-cost');
            costInput.value = ''; costInput.dataset.auto = '1';
            if (row.querySelector('.ame-opt-qty-in')) row.remove();
        });
        ameRecalc();
    }

    document.getElementById('alMassaEstimateForm').addEventListener('input', function (event) {
        // Typing in a cost field pins it to a manual value; auto rows keep following the lines total.
        if (event.isTrusted && event.target.classList.contains('ame-opt-cost')) {
            event.target.dataset.auto = '0';
        }
        ameRecalc();
    });
    document.getElementById('alMassaEstimateForm').addEventListener('submit', function (event) {
        var isDraft = event.submitter && event.submitter.value === 'draft';
        // Drop added quantity rows left without a quantity so validation doesn't reject the submit.
        document.querySelectorAll('#ameOptionList .ame-optrow').forEach(function (row) {
            var qtyInput = row.querySelector('.ame-opt-qty-in');
            if (qtyInput && !(parseInt(qtyInput.value, 10) > 0)) row.remove();
        });
        var rows = Array.prototype.slice.call(document.querySelectorAll('#ameCostRows tr'));
        var emptyRows = rows.filter(function (row) {
            var name = row.querySelector('.ame-name').value.trim();
            var unit = parseFloat(row.querySelector('.ame-unit').value) || 0;
            var qty = parseFloat(row.querySelector('.ame-qty').value) || 0;
            return name === '' || (unit === 0 && qty === 0);
        });
        if (!isDraft && emptyRows.length === rows.length) {
            event.preventDefault();
            alert('Add at least one cost line with a unit price and quantity.');
            return;
        }
        // Drop untouched default rows so validation only sees real cost lines.
        emptyRows.forEach(function (row, index) {
            // On draft keep one named row so validation passes with an otherwise empty form.
            if (isDraft && emptyRows.length === rows.length && index === 0) {
                if (row.querySelector('.ame-name').value.trim() === '') row.querySelector('.ame-name').value = 'Paper';
                return;
            }
            row.remove();
        });
        ameRecalc();
    });
    ameRecalc();
</script>
@endif
@endsection
