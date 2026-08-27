@extends('crm.layout')
@section('title', $ticket->ticket_number)
@section('content')
@php
    $u = Auth::guard('crm')->user();
    $canEstimate = $u->isAdmin() || ($u->isEstimator() && (int)$ticket->estimator_id === (int)$u->id);
    $canEditEstimate = $canEstimate && in_array($ticket->status, ['open', 'revision_requested']);
    $canTeamLeadReview = ($u->isAdmin() || ($u->isTeamLead() && (int)$ticket->team_lead_id === (int)$u->id))
        && $ticket->status === 'team_lead_open';
    $breakdown = is_array($ticket->cost_breakdown) ? $ticket->cost_breakdown : [];
    $savedCalculatorCost = collect($breakdown)->first(function ($item) {
        return ($item['name'] ?? '') === 'Paper Dimensions';
    });
    $calculatorState = old(
        'calculator_state',
        is_array($savedCalculatorCost['calculator_state'] ?? null)
            ? $savedCalculatorCost['calculator_state']
            : []
    );
    $leadCustomSpecs = optional($ticket->lead)->custom_specs;
    $selectedFinishingOptions = is_array($leadCustomSpecs)
        ? (array) ($leadCustomSpecs['Finishing Options'] ?? [])
        : [];
    $displayUnit = optional($ticket->lead)->unit ?: ($ticket->unit ?: 'mm');
    $standardCosts = [
        ['name' => 'Paper Dimensions', 'label' => 'Paper Cost', 'detail' => 'Enter sheet size and GSM in the Paper Cost Calculator'],
        [
            'name' => 'Grey Board',
            'label' => 'Grey Board',
            'detail' => '',
            'suggestions' => [
                '1 mm Grey Board',
                '1.5 mm Grey Board',
                '2 mm Grey Board',
                '2.5 mm Grey Board',
                '3 mm Grey Board',
                '1000 GSM Grey Board',
                '1200 GSM Grey Board',
                '1500 GSM Grey Board',
                '1800 GSM Grey Board',
            ],
        ],
        [
            'name' => 'EVA Foam',
            'label' => 'EVA Foam',
            'detail' => '',
            'suggestions' => [
                '1 cm EVA Foam',
                '2 cm EVA Foam',
                '3 cm EVA Foam',
                '5 cm EVA Foam',
                '10 cm EVA Foam',
                'Black EVA Foam',
                'White EVA Foam',
                'Custom-cut EVA Foam Insert',
            ],
        ],
        [
            'name' => 'E Flute',
            'label' => 'E Flute',
            'detail' => '',
            'suggestions' => [
                'Brown E Flute',
                'Black E Flute',
                'White E Flute',
            ],
        ],
        [
            'name' => 'CTP Plate',
            'label' => 'CTP PLATE',
            'detail' => 'Prepress plate preparation',
        ],
        ['name' => 'Printing / Color', 'label' => 'Printing / Color', 'detail' => trim(($ticket->printing ?: 'Not specified').($ticket->colors ? ' · '.$ticket->colors : ''))],
        ['name' => 'Pasting (Single Pasting)', 'label' => 'Pasting', 'detail' => 'Single Side', 'pasting_group' => [
            'Soft Box Pasting' => ['Single Side', 'Crush Auto Bottom', 'Other'],
            'Double Box Pasting' => ['Corrugation', 'Sheet to Sheet', 'Hard Rigid Box Pasting'],
        ]],
    ];
    foreach ($selectedFinishingOptions as $finishingOption) {
        $finishingParts = explode(' — ', $finishingOption, 2);
        $standardCosts[] = [
            'name' => 'Finishing: '.$finishingOption,
            'label' => $finishingParts[0],
            'detail' => $finishingParts[1] ?? $finishingParts[0],
        ];
    }
    $fixedCosts = [
        ['name' => 'Die Cost', 'label' => 'Die Cost', 'detail' => 'Tooling & Die making'],
        ['name' => 'Block Cost', 'label' => 'Block Cost', 'detail' => 'Custom foil stamping / embossing block preparation'],
        ['name' => 'Shipping', 'label' => 'Shipping', 'detail' => 'Fixed delivery, handling & logistics charge'],
    ];

    $standardNames = array_merge(array_column($standardCosts, 'name'), array_column($fixedCosts, 'name'), ['Production Cost']);
    $obsoleteDefaultNames = [
        'Coating & Finish', 'Lamination', 'Die Cutting', 'Gluing', 'Shipping Region',
        'Machine Cost', 'Rent', 'Power', 'Labor', 'Pasting',
    ];
    $getCost = function ($name) use ($breakdown) {
        foreach ($breakdown as $item) if (($item['name'] ?? '') === $name) return $item['price'] ?? 0;
        if ($name === 'Pasting (Single Pasting)') {
            foreach (['Pasting', 'Labor'] as $legacyName) {
                foreach ($breakdown as $item) if (($item['name'] ?? '') === $legacyName) return $item['price'] ?? 0;
            }
        }
        return 0;
    };
    $getCostDetail = function ($name, $fallback) use ($breakdown) {
        foreach ($breakdown as $item) {
            if (($item['name'] ?? '') === $name && !empty($item['detail'])) return $item['detail'];
        }
        if ($name === 'Pasting (Single Pasting)') {
            foreach (['Pasting', 'Labor'] as $legacyName) {
                foreach ($breakdown as $item) {
                    if (($item['name'] ?? '') === $legacyName && !empty($item['detail'])) return 'Single-side box pasting and assembly';
                }
            }
        }
        return $fallback;
    };
    $getCostQuantity = function ($name) use ($breakdown) {
        foreach ($breakdown as $item) {
            if (($item['name'] ?? '') === $name) return (float) ($item['quantity'] ?? 1);
        }
        return 1;
    };
    // Raw quantity for the input field: the saved value, or '' when none exists (so it isn't pre-filled with 1).
    $getCostQuantityRaw = function ($name) use ($breakdown) {
        foreach ($breakdown as $item) {
            if (($item['name'] ?? '') === $name && array_key_exists('quantity', $item) && $item['quantity'] !== null && $item['quantity'] !== '') {
                return $item['quantity'];
            }
        }
        return '';
    };
    // Variable quantity for the input: blank when unset OR exactly 1 (1 is the implicit default, so it isn't shown).
    $getVariableQty = function ($name) use ($getCostQuantityRaw) {
        $q = $getCostQuantityRaw($name);
        return ($q === '' || (float) $q == 1.0) ? '' : $q;
    };
    $getCostUnitPrice = function ($name) use ($breakdown, $getCostQuantity) {
        foreach ($breakdown as $item) {
            if (($item['name'] ?? '') === $name) {
                if (array_key_exists('unit_price', $item)) return (float) $item['unit_price'];
                $quantity = max(1, $getCostQuantity($name));
                return (float) ($item['price'] ?? 0) / $quantity;
            }
        }
        return 0;
    };
    $productionCostItem = collect($breakdown)->first(function ($item) {
        return ($item['name'] ?? '') === 'Production Cost';
    });
    $savedProductionPercentage = (float) ($productionCostItem['percentage'] ?? 0);
    if (!$savedProductionPercentage && !empty($productionCostItem['detail'])
        && preg_match('/([0-9]+(?:\.[0-9]+)?)\s*%/', $productionCostItem['detail'], $productionMatch)) {
        $savedProductionPercentage = (float) $productionMatch[1];
    }
    $allExtraCosts = array_values(array_filter($breakdown, function ($item) use ($standardNames, $obsoleteDefaultNames) {
        $itemName = $item['name'] ?? '';
        return !in_array($itemName, $standardNames, true) && !in_array($itemName, $obsoleteDefaultNames, true);
    }));
    $fixedExtraCosts = array_values(array_filter($allExtraCosts, function ($item) {
        return ($item['section'] ?? '') === 'fixed';
    }));
    $extraCosts = array_values(array_filter($allExtraCosts, function ($item) {
        return ($item['section'] ?? '') !== 'fixed';
    }));
    $currency = $ticket->currency ?: 'USD';
    $baseCostAmount = collect($breakdown)->reject(function ($item) {
        return in_array(($item['name'] ?? ''), ['Production Cost', 'Machine Cost', 'Rent', 'Power'], true);
    })->sum('price');
    $productionPercentageAmount = ($baseCostAmount + (float) $ticket->waste_material_amount)
        * ($savedProductionPercentage / 100);
    $finalProductionCost = $baseCostAmount + (float) $ticket->waste_material_amount + $productionPercentageAmount;
    $leadVatPercentage = optional($ticket->lead)->vat_percentage;
    $savedVatPercentage = (float) old(
        'vat_percentage',
        !empty($breakdown) ? ($leadVatPercentage ?? 5) : ((float) $leadVatPercentage > 0 ? $leadVatPercentage : 5)
    );
    $vatAmount = $finalProductionCost * ($savedVatPercentage / 100);
    $finalCostWithVat = $finalProductionCost + $vatAmount;
    $formatCompactNumber = function ($value, $precision = 4) {
        return rtrim(rtrim(number_format((float) $value, $precision, '.', ','), '0'), '.');
    };
    // Money display: "—" when the value is zero/empty, otherwise "AED 1,234.5" (no trailing zeros).
    $moneyOrDash = function ($value, $precision = 2) use (&$currency, $formatCompactNumber) {
        $v = (float) $value;
        return $v == 0.0 ? '—' : $currency.' '.$formatCompactNumber($v, $precision);
    };
    $estimatorAttachmentUrl = function ($file) {
        // Docroot IS the public/ folder — strip any stored 'public/' prefix and
        // let asset() prepend APP_URL. Serves correctly from the web root.
        $relativePath = ltrim(preg_replace('#^public/#', '', (string) $file), '/');

        return asset($relativePath);
    };
@endphp
<style>
.es-page{--es-ink:#152033;--es-muted:#708096;--es-line:#e3e9f1;--es-surface:#f7f9fc;max-width:1540px;margin:0 auto;padding-bottom:2rem}
.es-head{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:flex-start;gap:1.5rem;margin-bottom:1rem;padding:1.45rem 1.55rem;border:1px solid color-mix(in srgb,var(--primary-purple) 18%,#fff);border-radius:20px;background:linear-gradient(125deg,#fffaf7 0%,var(--primary-soft) 55%,color-mix(in srgb,var(--primary-soft) 78%,#ffd8c4) 100%);box-shadow:0 14px 34px rgba(15,23,42,.07);color:var(--es-ink)}
.es-head:before{content:"";position:absolute;inset:0 auto 0 0;width:5px;background:linear-gradient(180deg,var(--primary-purple),color-mix(in srgb,var(--primary-purple) 55%,#ffb28c))}.es-head:after{content:"";position:absolute;right:-65px;top:-105px;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,var(--primary-shadow),transparent 68%);opacity:.32;pointer-events:none}.es-head-main{position:relative;z-index:1}.es-back{display:inline-flex;align-items:center;gap:.45rem;color:var(--primary-hover);text-decoration:none;font-size:.75rem;font-weight:750;transition:.2s}.es-back:hover{color:var(--primary-purple);transform:translateX(-2px)}.es-head h2{margin:.65rem 0 .3rem;color:var(--es-ink);font-size:1.65rem;line-height:1.1;letter-spacing:-.03em}.es-head .es-sub{color:#748398}.es-sub{color:#8190a4;font-size:.76rem}.es-status-wrap{position:relative;z-index:1;display:flex;align-items:center;gap:.5rem}.es-status{display:inline-flex;align-items:center;gap:.45rem;padding:.52rem .85rem;border:1px solid var(--primary-shadow);border-radius:999px;background:rgba(255,255,255,.72);color:var(--primary-hover);font-size:.72rem;font-weight:850;backdrop-filter:blur(10px)}.es-status:before{content:"";width:7px;height:7px;border-radius:50%;background:var(--primary-purple);box-shadow:0 0 0 4px var(--primary-shadow)}
.es-progress{display:grid;grid-template-columns:repeat(4,1fr);margin-bottom:1rem;padding:.85rem 1rem;border:1px solid var(--es-line);border-radius:16px;background:rgba(255,255,255,.82);box-shadow:0 8px 24px rgba(15,23,42,.05)}.es-progress-step{display:flex;min-width:0;align-items:center;gap:.55rem;color:#9aa7b8;font-size:.68rem;font-weight:800}.es-progress-step span{flex:0 0 auto;white-space:nowrap}.es-progress-step:not(:last-child):after{content:"";flex:1 1 auto;min-width:18px;height:1px;margin:0 .8rem;background:#dce3ec}.es-progress-step i{display:grid;flex:0 0 28px;place-items:center;width:28px;height:28px;border-radius:9px;background:#edf1f6;color:#8c9aae}.es-progress-step.is-active,.es-progress-step.is-done{color:var(--es-ink)}.es-progress-step.is-active i{background:var(--primary-purple);color:#fff;box-shadow:0 5px 12px var(--primary-shadow)}.es-progress-step.is-done i{background:#e7f8f0;color:#129b65}
.es-card{position:relative;margin-bottom:1rem;padding:1.35rem;background:rgba(255,255,255,.96);border:1px solid var(--es-line);border-radius:18px;box-shadow:0 10px 30px rgba(15,23,42,.055)}.es-card h3{display:flex;align-items:center;gap:.65rem;margin:0 0 1.1rem;color:var(--es-ink);font-size:1.02rem;letter-spacing:-.015em}.es-card h3>i{display:grid;width:34px;height:34px;place-items:center;margin:0!important;border-radius:10px;background:var(--primary-soft);color:var(--primary-purple)!important;font-size:.82rem}.es-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}.es-info{min-height:76px;padding:.82rem .9rem;border:1px solid var(--es-line);border-radius:12px;background:linear-gradient(145deg,#fbfcfe,#f6f8fb);transition:border-color .2s,transform .2s,box-shadow .2s}.es-info:hover{transform:translateY(-1px);border-color:color-mix(in srgb,var(--primary-purple) 24%,#e3e9f1);box-shadow:0 8px 18px rgba(15,23,42,.05)}.es-label{display:block;margin-bottom:.3rem;color:#8391a5;font-size:.61rem;font-weight:850;text-transform:uppercase;letter-spacing:.07em}.es-value{color:#263449;font-size:.82rem;font-weight:750;line-height:1.4;overflow-wrap:anywhere}
.es-table-wrap{overflow:auto;border:1px solid var(--es-line);border-radius:13px;background:#fff}.es-table{width:100%;border-collapse:collapse;min-width:720px}.es-table th{padding:.76rem .9rem;background:#f4f7fa;color:#68778c;text-align:left;font-size:.63rem;text-transform:uppercase;letter-spacing:.065em}.es-table td{padding:.72rem .9rem;border-top:1px solid #edf1f5;color:#334155;font-size:.76rem}.es-table tbody tr{transition:background .15s}.es-table tbody tr:hover{background:#fafbfe}.es-quantity-table{min-width:980px}.es-quantity-table .es-money{min-width:150px}.es-discount-input{max-width:95px;text-align:right}.es-discounted{color:var(--primary-hover);font-size:.84rem;font-weight:850;white-space:nowrap}.es-new-option{background:color-mix(in srgb,var(--primary-soft) 38%,#fff)}.es-quantity-tools{display:flex;align-items:center;justify-content:flex-start;gap:.75rem;margin-top:.8rem}.es-quantity-help{color:#8a98aa;font-size:.67rem}.es-money{display:flex;align-items:center;gap:.4rem;min-width:145px}.currency-code{color:#708096;font-size:.68rem;font-weight:850}.es-input{width:100%;box-sizing:border-box;padding:.66rem .72rem;border:1.5px solid #d7e0ea;border-radius:9px;background:#fff;color:#1e293b;font:inherit;outline:0;transition:border-color .18s,box-shadow .18s,background .18s}.es-input:hover{border-color:#b9c5d4}.es-input:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}.es-input:invalid:not(:placeholder-shown){border-color:#ef9a9a}.es-price{text-align:right;font-weight:800;font-variant-numeric:tabular-nums}.es-btn{display:inline-flex;min-height:39px;align-items:center;justify-content:center;gap:.45rem;padding:.64rem .9rem;border:1px solid #d5deea;border-radius:10px;background:#fff;color:#526176;text-decoration:none;font:inherit;font-size:.75rem;font-weight:820;cursor:pointer;transition:transform .18s,box-shadow .18s,border-color .18s,background .18s}.es-btn:hover{transform:translateY(-1px);border-color:#b8c4d3;box-shadow:0 7px 16px rgba(15,23,42,.07)}.es-primary{background:var(--primary-purple);border-color:var(--primary-purple);color:#fff;box-shadow:0 7px 16px var(--primary-shadow)}.es-primary:hover{border-color:var(--primary-hover);background:var(--primary-hover)}.es-danger{color:#dc2626;background:#fff1f2;border-color:#fecdd3}
.material-cost-detail{color:#526176;font-weight:650}
.es-summary.es-summary-four{grid-template-columns:repeat(4,1fr)}.es-summary.es-summary-five{grid-template-columns:repeat(5,1fr)}
.es-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-top:.9rem}.es-cost-summary-card .es-summary{margin-top:0}.es-summary-box{min-height:88px;padding:.9rem 1rem;border-radius:12px;background:#f7f9fc;border:1px solid var(--es-line)}.es-summary-box:last-child{background:linear-gradient(135deg,var(--primary-soft),#fff);border-color:var(--primary-shadow)}.es-summary-box strong{display:block;margin-top:.25rem;color:var(--es-ink);font-size:1.08rem;font-variant-numeric:tabular-nums}.es-summary-box:last-child strong{color:var(--primary-purple)}.es-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1rem;flex-wrap:wrap}.es-actions .es-primary{min-height:44px;padding-inline:1.15rem}.es-note{width:100%;min-height:88px;resize:vertical}.es-file{display:flex;align-items:center;gap:.4rem;max-width:100%;margin:.2rem 0;padding:.38rem .52rem;border-radius:8px;background:#eaf0f6;color:#526176;text-decoration:none;font-size:.66rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.es-sales-actions{display:grid;grid-template-columns:2fr 1fr;gap:1rem}.es-action-box{padding:1rem;border:1px solid var(--es-line);border-radius:12px;background:#f8fafc}
@media(max-width:1100px){.es-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:900px){.es-summary,.es-summary.es-summary-four,.es-summary.es-summary-five,.es-sales-actions{grid-template-columns:1fr}.es-progress-step span{display:none}.es-progress-step:not(:last-child):after{margin:0 .45rem}}@media(max-width:600px){.es-head{padding:1.2rem;flex-direction:column}.es-head h2{font-size:1.35rem}.es-grid{grid-template-columns:1fr}.es-progress{padding:.7rem}.es-card{padding:1rem;border-radius:15px}.es-status-wrap{align-self:flex-start}}
</style>
<style>
.es-return-card{position:relative;overflow:hidden;padding:1.4rem;border:1px solid var(--primary-shadow);background:linear-gradient(135deg,#fff 0%,var(--primary-soft) 100%);box-shadow:0 14px 34px rgba(15,23,42,.07)}
.es-return-card:before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:var(--primary-purple)}
.es-return-head{display:flex;align-items:center;gap:.75rem;margin-bottom:.45rem}.es-return-head h3{margin:0}.es-return-icon{width:40px;height:40px;display:flex;align-items:center;justify-content:center;flex:0 0 40px;border-radius:11px;background:var(--primary-purple);color:#fff;box-shadow:0 7px 18px var(--primary-shadow)}
.es-return-copy{margin:0 0 1rem 3.25rem;color:#718096;font-size:.78rem}
.es-return-grid{display:grid;grid-template-columns:190px minmax(260px,1fr) auto;gap:.7rem;align-items:start}
.es-return-grid .es-input{border-color:var(--primary-shadow);background:rgba(255,255,255,.92)}
.es-return-grid select{font-weight:750;color:#334155}
.es-return-grid textarea{min-height:43px;height:43px}
.es-return-submit{min-width:155px;border-color:var(--primary-purple);background:var(--primary-purple);color:#fff;box-shadow:0 8px 20px var(--primary-shadow)}
.es-return-submit:hover{filter:brightness(.96);transform:translateY(-1px)}
.es-return-note{padding:.9rem 1rem;border:1px solid var(--primary-shadow);border-radius:11px;background:rgba(255,255,255,.85);color:#334155;white-space:pre-wrap}
.es-unsaved-backdrop{position:fixed;inset:0;z-index:12000;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.46);backdrop-filter:blur(7px)}
.es-unsaved-backdrop.is-open{display:flex}.es-unsaved-dialog{width:min(500px,96vw);overflow:hidden;border:1px solid #e6ebf2;border-radius:18px;background:#fff;box-shadow:0 30px 85px rgba(15,23,42,.24)}
.es-unsaved-head{display:flex;gap:.85rem;align-items:flex-start;padding:1.4rem 1.45rem 1rem;background:#fff}.es-unsaved-icon{display:grid;flex:0 0 38px;width:38px;height:38px;place-items:center;border:1px solid var(--primary-shadow);border-radius:50%;background:var(--primary-soft);color:var(--primary-purple);font-size:.92rem}
.es-unsaved-head h3{margin:.05rem 0 .22rem;color:var(--es-ink);font-size:1.08rem;letter-spacing:-.015em}.es-unsaved-body{margin:0 1.45rem;padding:1rem 0 1.25rem;border-top:1px solid #edf1f5;color:#64748b;font-size:.78rem;line-height:1.6}.es-unsaved-actions{display:flex;justify-content:flex-end;gap:.55rem;padding:1rem 1.45rem 1.25rem;background:#fff}.es-unsaved-actions .es-btn{min-height:42px;padding-inline:1rem}.es-unsaved-discard{border-color:#e2e8f0;background:#fff;color:#64748b}.es-unsaved-discard:hover{border-color:#fecaca;background:#fff7f7;color:#b42318}
@media(max-width:800px){.es-return-copy{margin-left:0}.es-return-grid{grid-template-columns:1fr}.es-return-submit{min-height:46px}}
</style>

@php
    $estimateStage = in_array($ticket->status, ['estimated','completed']) ? 4
        : ($ticket->status === 'team_lead_open' ? 3
        : (in_array($ticket->status, ['open','revision_requested']) ? 2 : 1));
@endphp
<div class="es-page">
<div class="es-head">
    <div class="es-head-main"><a class="es-back" href="{{ route('crm.estimate_tickets.index', ['tab' => in_array($ticket->status,['estimated','completed']) ? 'history' : 'mine']) }}"><i class="fas fa-arrow-left"></i> Back to estimate tickets</a><h2>{{ $ticket->ticket_number }}</h2><div class="es-sub">Requested by {{ $ticket->requester->name ?? 'Unknown' }} · {{ $ticket->created_at->format('d M Y, h:i A') }}</div></div>
    <div class="es-status-wrap"><span class="es-status">{{ ucwords(str_replace('_',' ',$ticket->status)) }}</span></div>
</div>
<div class="es-progress" aria-label="Estimate progress">
    @foreach([
        ['Request received','fa-inbox'],
        ['Costing','fa-calculator'],
        ['Lead review','fa-user-check'],
        ['Ready for sales','fa-check']
    ] as $index => $stage)
        <div class="es-progress-step {{ $estimateStage > $index + 1 ? 'is-done' : ($estimateStage === $index + 1 ? 'is-active' : '') }}">
            <i class="fas {{ $estimateStage > $index + 1 ? 'fa-check' : $stage[1] }}"></i><span>{{ $stage[0] }}</span>
        </div>
    @endforeach
</div>

<div class="es-card">
    <h3><i class="fas fa-box-open" style="color:var(--primary-purple);margin-right:.4rem"></i> Estimate Request</h3>
    <div class="es-grid">
        <div class="es-info"><span class="es-label">Client</span><div class="es-value">{{ $ticket->client_name }}</div>@if(!$u->isEstimator())<div class="es-sub">{{ $ticket->client_email }}</div>@endif</div>
        <div class="es-info"><span class="es-label">Product</span><div class="es-value">{{ $ticket->product_style }}</div></div>
        <div class="es-info">
            <span class="es-label">Requested Quantity</span>
            <div class="es-value">
                @if($ticket->options && $ticket->options->count() > 0)
                    {{ $ticket->options->pluck('quantity')->map(function($q){ return number_format($q); })->implode(', ') }} Units
                @elseif(optional($ticket->lead)->quantity)
                    {{ number_format(optional($ticket->lead)->quantity) }} Units
                @else
                    —
                @endif
            </div>
        </div>
        <div class="es-info"><span class="es-label">Box Dimensions</span><div class="es-value">{{ $ticket->finish_size ?: '—' }} {{ $displayUnit }}</div></div>
        <div class="es-info"><span class="es-label">Open / Flat Size</span><div class="es-value">{{ $ticket->flat_size ?: '—' }} {{ $displayUnit }}</div></div>
        <div class="es-info"><span class="es-label">Stock</span><div class="es-value">{{ $ticket->stock ?: '—' }}</div></div>
        <div class="es-info"><span class="es-label">Printing</span><div class="es-value">{{ $ticket->printing ?: '—' }}</div></div>
        <div class="es-info"><span class="es-label">Estimator</span><div class="es-value">{{ $ticket->estimator->name ?? 'Unassigned' }}</div></div>
        <div class="es-info"><span class="es-label">Shipping Address</span><div class="es-value" style="white-space:pre-line">{{ optional($ticket->lead)->shipping_address ?: 'Not provided' }}</div></div>
        <div class="es-info"><span class="es-label">Designer Files</span>@forelse(($ticket->attachments ?: []) as $file)<a class="es-file" target="_blank" rel="noopener" href="{{ $estimatorAttachmentUrl($file) }}"><i class="fas fa-paperclip"></i>{{ basename($file) }}</a>@empty<div class="es-value">No files</div>@endforelse</div>
    </div>
</div>

@if($ticket->return_note && !$u->isTeamLead())
<div class="es-card es-return-card">
    <div class="es-return-head"><span class="es-return-icon"><i class="fas fa-reply"></i></span><h3>Returned to {{ ucfirst($ticket->returned_to ?: 'department') }}</h3></div>
    <div class="es-return-note">{{ $ticket->return_note }}</div>
    @if($ticket->status === 'returned_to_sales' && ($u->isAdmin() || $u->isSalesManager() || $u->isSales()))
        <div style="margin-top:1rem;text-align:right">
            <a href="{{ route('crm.estimate_tickets.edit', $ticket->id) }}" class="es-btn es-primary" style="text-decoration:none;"><i class="fas fa-edit"></i> Edit & Resubmit Ticket</a>
        </div>
    @endif
</div>
@endif

@unless($u->isTeamLead())
    @include('crm.estimate_tickets.partials.advanced_calculator')
@endunless

<form method="POST" action="{{ route('crm.estimate_tickets.submit',$ticket->id) }}" id="professionalEstimateForm">
{{ csrf_field() }}
<input type="hidden" name="redirect_to" id="estimateRedirectTo" value="">
@unless($u->isTeamLead())
<div class="es-card">
    <h3><i class="fas fa-calculator" style="color:var(--primary-purple);margin-right:.4rem"></i> Professional Cost Calculator</h3>
    <div style="display:flex;align-items:end;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap">
        <div style="min-width:190px"><span class="es-label">Estimate Currency</span>
            @if($canEditEstimate)
                <select class="es-input" name="currency" required>
                    @foreach(['USD'=>'USD — US Dollar','AED'=>'AED — UAE Dirham','GBP'=>'GBP — British Pound','EUR'=>'EUR — Euro','CAD'=>'CAD — Canadian Dollar','AUD'=>'AUD — Australian Dollar','PKR'=>'PKR — Pakistani Rupee','SAR'=>'SAR — Saudi Riyal','QAR'=>'QAR — Qatari Riyal'] as $code=>$label)
                        <option value="{{ $code }}" {{ old('currency',$ticket->currency ?: 'USD')===$code?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
            @else
                <div class="es-info"><strong>{{ $ticket->currency ?: 'USD' }}</strong></div>
            @endif
        </div>
        <div class="es-sub">All costing and quantity prices below use this currency.</div>
    </div>
    <div class="es-table-wrap"><table class="es-table">
        <thead><tr><th>Cost Item</th><th>Specification / Detail</th><th style="text-align:center">Quantity</th><th style="text-align:center">Price Per Unit</th><th style="text-align:center">Cost</th></tr></thead>
        <tbody id="costRows">
        @foreach($standardCosts as $cost)
            @php
                $costDetail = $getCostDetail($cost['name'], $cost['detail']);
                $savedCostValue = old('cost_prices.'.$loop->index, $getCost($cost['name']));
                $savedUnitPriceValue = old('cost_unit_prices.'.$loop->index, $getCostUnitPrice($cost['name']));
            @endphp
            <tr>
                <td><strong>{{ $cost['label'] }}</strong><input type="hidden" name="cost_names[]" value="{{ $cost['name'] }}"><input type="hidden" name="cost_sections[]" value="variable"></td>
                <td class="cost-detail-text">
                    @if(!empty($cost['pasting_group']) && $canEditEstimate)
                        @php $selDetail = old('cost_details.'.$loop->index, $costDetail); @endphp
                        <select class="es-input es-pasting-select" name="cost_details[]">
                            @foreach($cost['pasting_group'] as $groupLabel => $children)
                                <optgroup label="{{ $groupLabel }}">
                                    @foreach($children as $child)
                                        <option value="{{ $child }}" {{ $selDetail === $child ? 'selected' : '' }}>{{ $child }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    @elseif(!empty($cost['suggestions']))
                        @php $materialDetail = old('cost_details.'.$loop->index, $costDetail); @endphp
                        <input type="hidden" name="cost_details[]" value="{{ $materialDetail }}">
                        <span class="material-cost-detail" data-cost-name="{{ $cost['name'] }}">
                            {{ $materialDetail ?: 'Use the '.$cost['label'].' Cost Calculator above' }}
                        </span>
                    @else
                        <input type="hidden" name="cost_details[]" value="{{ $costDetail }}">
                        {{ $costDetail ?: '—' }}
                    @endif
                </td>
                <td style="text-align:center">@if($canEditEstimate)<input class="es-input cost-quantity" type="number" min="0" step=".01" name="cost_quantities[]" value="{{ old('cost_quantities.'.$loop->index, $getVariableQty($cost['name'])) }}" oninput="calculateRowCost(this)">@else{{ $getVariableQty($cost['name']) === '' ? '—' : $formatCompactNumber($getVariableQty($cost['name']),2) }}@endif</td>
                <td style="text-align:center">@if($canEditEstimate)<div class="es-money"><span class="currency-code">{{ $currency }}</span><input class="es-input es-price cost-unit-price" type="number" min="0" step=".0001" name="cost_unit_prices[]" value="{{ (float)$savedUnitPriceValue > 0 ? $savedUnitPriceValue : '' }}" oninput="calculateRowCost(this)"></div>@else<strong>{{ $moneyOrDash($getCostUnitPrice($cost['name']),4) }}</strong>@endif</td>
                <td style="text-align:center">@if($canEditEstimate)<div class="es-money"><span class="currency-code">{{ $currency }}</span><input class="es-input es-price cost-price" type="number" min="0" step=".01" name="cost_prices[]" value="{{ (float)$savedCostValue > 0 ? $savedCostValue : '' }}" readonly aria-readonly="true" tabindex="-1" style="background:#f4f7fa;cursor:not-allowed"></div>@else<strong>{{ $moneyOrDash($getCost($cost['name']),2) }}</strong>@endif</td>
            </tr>
        @endforeach
        @foreach($extraCosts as $extra)
            <tr class="extra-cost-row">
                <td>@if($canEditEstimate)<input class="es-input" name="extra_names[]" value="{{ $extra['name'] ?? '' }}" required>@else<strong>{{ $extra['name'] ?? '' }}</strong>@endif</td>
                <td>@if($canEditEstimate)<input class="es-input" name="extra_details[]" value="{{ $extra['detail'] ?? '' }}">@else{{ $extra['detail'] ?? '—' }}@endif</td>
                <td style="text-align:center">@if($canEditEstimate)<input class="es-input cost-quantity" type="number" min="0" step=".01" name="extra_quantities[]" value="{{ $extra['quantity'] ?? 1 }}" oninput="calculateRowCost(this)">@else{{ $formatCompactNumber((float)($extra['quantity'] ?? 1),2) }}@endif</td>
                <td style="text-align:center">@if($canEditEstimate)<div class="es-money"><span class="currency-code">{{ $currency }}</span><input class="es-input es-price cost-unit-price" type="number" min="0" step=".0001" name="extra_unit_prices[]" value="{{ (float)($extra['unit_price'] ?? $extra['price'] ?? 0) > 0 ? ($extra['unit_price'] ?? $extra['price']) : '' }}" oninput="calculateRowCost(this)"></div>@else<strong>{{ $currency }} {{ $formatCompactNumber((float)($extra['unit_price'] ?? $extra['price'] ?? 0),4) }}</strong>@endif</td>
                <td style="text-align:center">@if($canEditEstimate)<div class="es-money"><span class="currency-code">{{ $currency }}</span><input class="es-input es-price cost-price" type="number" min="0" step=".01" name="extra_prices[]" value="{{ (float)($extra['price'] ?? 0) > 0 ? $extra['price'] : '' }}" oninput="calculateUnitPriceFromCost(this)"><button class="es-btn es-danger" type="button" onclick="this.closest('tr').remove();calculateCostSummary()"><i class="fas fa-times"></i></button></div>@else<strong>{{ $currency }} {{ $formatCompactNumber((float)($extra['price'] ?? 0),2) }}</strong>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @if($canEditEstimate)<div style="margin-top:.75rem"><button class="es-btn" type="button" onclick="addExtraCost()"><i class="fas fa-plus"></i> Add Extra Fee</button></div>@endif
</div>

<style>
.fixed-cost-stack{display:flex;flex-direction:row;align-items:center;justify-content:flex-end;gap:.65rem;flex-wrap:nowrap}
.fixed-cost-stack .es-money .currency-code{min-width:30px}
.fixed-cost-stack input.fixed-qty,.fixed-cost-stack input.fixed-weight{width:80px!important}
</style>
<div class="es-card">
    <h3><i class="fas fa-tools" style="color:var(--primary-purple);margin-right:.4rem"></i> Fixed Cost Calculator</h3>
    <div class="es-table-wrap"><table class="es-table">
        <thead><tr><th>Cost Item</th><th>Specification / Detail</th><th style="text-align:center">Cost</th></tr></thead>
        <tbody id="fixedCostRows">
        @foreach($fixedCosts as $cost)
            @php
                $isQtyBased = in_array($cost['name'], ['Die Cost', 'Block Cost']);
                $isWeightBased = $cost['name'] === 'Shipping';
            @endphp
            <tr>
                <td><strong>{{ $cost['label'] }}</strong><input type="hidden" name="cost_names[]" value="{{ $cost['name'] }}"><input type="hidden" name="cost_details[]" value="{{ $cost['detail'] }}"><input type="hidden" name="cost_sections[]" value="fixed"></td>
                <td>{{ $cost['detail'] }}</td>
                <td style="text-align:center">@if($canEditEstimate)<div class="fixed-cost-stack">@if($isQtyBased)<div class="es-money"><span class="currency-code">QTY</span><input class="es-input fixed-qty" name="cost_quantities[]" value="{{ old('cost_quantities.'.($loop->index+count($standardCosts)), $getVariableQty($cost['name'])) }}" type="number" min="0" step="1"></div>@elseif($isWeightBased)<div class="es-money"><span class="currency-code">KG</span><input class="es-input fixed-weight" name="cost_quantities[]" value="{{ old('cost_quantities.'.($loop->index+count($standardCosts)), $getVariableQty($cost['name'])) }}" type="number" min="0" step=".01"></div>@else<input type="hidden" name="cost_quantities[]" value="">@endif<div class="es-money"><span class="currency-code">{{ $currency }}</span><input class="es-input es-price cost-price" type="number" min="0" step=".01" name="cost_prices[]" value="{{ old('cost_prices.'.($loop->index+count($standardCosts)), $getCost($cost['name'])) }}" oninput="calculateCostSummary()"></div></div>@else<strong>{{ $moneyOrDash($getCost($cost['name']),2) }}</strong>@endif</td>
            </tr>
        @endforeach
        @foreach($fixedExtraCosts as $extra)
            <tr class="extra-fixed-cost-row">
                <td>@if($canEditEstimate)<input class="es-input" name="fixed_extra_names[]" value="{{ $extra['name'] ?? '' }}" required>@else<strong>{{ $extra['name'] ?? '' }}</strong>@endif</td>
                <td>@if($canEditEstimate)<input class="es-input" name="fixed_extra_details[]" value="{{ $extra['detail'] ?? '' }}" placeholder="Optional detail">@else{{ $extra['detail'] ?? '—' }}@endif</td>
                <td style="text-align:center">@if($canEditEstimate)<div class="es-money"><span class="currency-code">{{ $currency }}</span><input class="es-input es-price cost-price" type="number" min="0" step=".01" name="fixed_extra_prices[]" value="{{ $extra['price'] ?? 0 }}" oninput="calculateCostSummary()"><button class="es-btn es-danger" type="button" onclick="this.closest('tr').remove();calculateCostSummary()" aria-label="Remove fixed cost"><i class="fas fa-times"></i></button></div>@else<strong>{{ $currency }} {{ $formatCompactNumber((float)($extra['price'] ?? 0),2) }}</strong>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @if($canEditEstimate)<div style="margin-top:.75rem"><button class="es-btn" type="button" onclick="addFixedExtraCost()"><i class="fas fa-plus"></i> Add Extra</button></div>@endif
</div>

<div class="es-card es-cost-summary-card">
    <h3><i class="fas fa-chart-pie"></i> Cost Summary</h3>
    <div class="es-summary es-summary-five">
        <div class="es-summary-box"><span class="es-label">Base Cost</span><strong><span class="currency-code">{{ $currency }}</span> <span id="baseCost">{{ $formatCompactNumber($baseCostAmount,2) }}</span></strong></div>
        <div class="es-summary-box"><span class="es-label">Waste Material</span>@if($canEditEstimate)<div style="display:flex;align-items:center;gap:.35rem"><input class="es-input" style="max-width:90px" type="number" min="0" max="100" step=".01" name="waste_material_percentage" value="{{ old('waste_material_percentage',$ticket->waste_material_percentage ?: 0) }}" oninput="calculateCostSummary()"><strong>%</strong></div>@else<strong>{{ $formatCompactNumber((float)$ticket->waste_material_percentage,2) }}%</strong>@endif<div class="es-sub">+<span class="currency-code">{{ $currency }}</span> <span id="wasteCost">{{ $formatCompactNumber((float)$ticket->waste_material_amount,2) }}</span></div></div>
        <div class="es-summary-box"><span class="es-label">Production Cost (Machine, Rent &amp; Power)</span>@if($canEditEstimate)<div style="display:flex;align-items:center;gap:.35rem"><input class="es-input" style="max-width:90px" type="number" min="0" max="1000" step=".01" name="production_cost_percentage" value="{{ old('production_cost_percentage',$savedProductionPercentage ?: '') }}" oninput="calculateCostSummary()"><strong>%</strong></div>@else<strong>{{ $formatCompactNumber($savedProductionPercentage,2) }}%</strong>@endif<div class="es-sub">+<span class="currency-code">{{ $currency }}</span> <span id="productionPercentageCost">{{ $formatCompactNumber($productionPercentageAmount,2) }}</span></div></div>
        <div class="es-summary-box"><span class="es-label">VAT</span>@if($canEditEstimate)<div style="display:flex;align-items:center;gap:.35rem"><input class="es-input" style="max-width:90px" type="number" min="0" max="100" step=".01" name="vat_percentage" id="estimateVatPercentage" value="{{ $savedVatPercentage }}" oninput="calculateCostSummary()"><strong>%</strong></div>@else<strong>{{ $formatCompactNumber($savedVatPercentage,2) }}%</strong>@endif<div class="es-sub">+<span class="currency-code">{{ $currency }}</span> <span id="vatCost">{{ $formatCompactNumber($vatAmount,2) }}</span></div></div>
        <div class="es-summary-box"><span class="es-label">Final Price (Incl. VAT)</span><strong><span class="currency-code">{{ $currency }}</span> <span id="productionCost">{{ $formatCompactNumber($finalCostWithVat,2) }}</span></strong></div>
    </div>
</div>
@endunless

@if($u->isTeamLead())
<div class="es-card">
    <h3><i class="fas fa-file-invoice-dollar"></i> Estimator Cost Breakdown</h3>
    <div class="es-sub" style="margin:-.7rem 0 1rem">Read-only costing submitted by the estimator for Team Lead review.</div>
    <div class="es-table-wrap">
        <table class="es-table">
            <thead>
                <tr>
                    <th>Cost Item</th>
                    <th>Specification / Detail</th>
                    <th style="text-align:center">Quantity</th>
                    <th style="text-align:center">Price Per Unit</th>
                    <th style="text-align:center">Total Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse(collect($breakdown)->reject(function ($item) {
                    return ($item['name'] ?? '') === 'Production Cost';
                }) as $item)
                    @php
                        $hasItemQty = array_key_exists('quantity', $item) && $item['quantity'] !== null && $item['quantity'] !== '' && (float) $item['quantity'] != 1.0;
                        $itemQuantity = $hasItemQty ? (float) $item['quantity'] : 1.0;
                        $itemPrice = (float) ($item['price'] ?? 0);
                        $itemUnitPrice = array_key_exists('unit_price', $item)
                            ? (float) $item['unit_price']
                            : ($itemQuantity > 0 ? $itemPrice / $itemQuantity : 0);
                        $isShippingRow = ($item['name'] ?? '') === 'Shipping';
                        $qtyDisplay = $hasItemQty
                            ? $formatCompactNumber($itemQuantity, 2).($isShippingRow ? ' kg' : '')
                            : '—';
                    @endphp
                    <tr>
                        <td><strong>{{ ($item['name'] ?? '') === 'Paper Dimensions' ? 'Paper Cost' : ($item['name'] ?? 'Cost Item') }}</strong></td>
                        <td>{{ $item['detail'] ?? '—' }}</td>
                        <td style="text-align:center">{{ $qtyDisplay }}</td>
                        <td style="text-align:center">{{ $moneyOrDash($itemUnitPrice, 4) }}</td>
                        <td style="text-align:center"><strong>{{ $moneyOrDash($itemPrice, 2) }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:1.4rem;text-align:center;color:#8190a4">No estimator costing has been saved yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="es-summary es-summary-five">
        <div class="es-summary-box"><span class="es-label">Base Cost</span><strong>{{ $currency }} {{ $formatCompactNumber($baseCostAmount,2) }}</strong></div>
        <div class="es-summary-box"><span class="es-label">Waste Material ({{ $formatCompactNumber((float)$ticket->waste_material_percentage,2) }}%)</span><strong>+{{ $currency }} {{ $formatCompactNumber((float)$ticket->waste_material_amount,2) }}</strong></div>
        <div class="es-summary-box"><span class="es-label">Production Cost ({{ $formatCompactNumber($savedProductionPercentage,2) }}%)</span><strong>+{{ $currency }} {{ $formatCompactNumber($productionPercentageAmount,2) }}</strong></div>
        <div class="es-summary-box"><span class="es-label">VAT ({{ $formatCompactNumber($savedVatPercentage,2) }}%)</span><strong>+{{ $currency }} {{ $formatCompactNumber($vatAmount,2) }}</strong></div>
        <div class="es-summary-box"><span class="es-label">Final Cost</span><strong>{{ $currency }} {{ $formatCompactNumber($finalCostWithVat,2) }}</strong></div>
    </div>
</div>
@endif

<div class="es-card">
    <h3><i class="fas fa-layer-group" style="color:var(--primary-purple);margin-right:.4rem"></i> Quantity Price Options</h3>
    <div class="es-table-wrap"><table class="es-table es-quantity-table"><thead><tr><th style="text-align:center">Quantity</th><th style="text-align:center">Total Selling Price</th><th style="text-align:center">Price Per Unit</th><th style="text-align:center">Discount %</th><th style="text-align:center">Discounted Price</th>@if($canEditEstimate)<th aria-label="Actions" style="text-align:center"></th>@endif</tr></thead><tbody id="quantityOptionRows">
    @foreach($ticket->options as $option)
        @php
            $optionPrice = old('prices.'.$option->id, $option->total_price);
            $optionDiscount = old('discounts.'.$option->id, $option->discount_percentage ?: 0);
            $optionDiscounted = $optionPrice !== null ? round((float)$optionPrice * (1 - ((float)$optionDiscount / 100)), 2) : null;
        @endphp
        <tr class="quantity-option-row" data-existing="1"><td style="text-align:center"><strong class="quantity-value" data-qty="{{ $option->quantity }}">{{ $formatCompactNumber($option->quantity) }}</strong></td>
            <td style="text-align:center">@if($canEditEstimate)<div class="es-money" style="justify-content:center"><span class="currency-code">{{ $currency }}</span><input class="es-input es-price option-price" data-qty="{{ $option->quantity }}" data-target="unit{{ $option->id }}" data-discount-target="discounted{{ $option->id }}" type="number" step=".01" min="0" name="prices[{{ $option->id }}]" value="{{ $optionPrice }}" required></div>@else<strong>{{ $option->total_price!==null?$currency.' '.$formatCompactNumber($option->total_price,2):'Pending' }}</strong>@endif</td>
            <td style="text-align:center"><strong id="unit{{ $option->id }}">{{ $optionPrice!==null?$currency.' '.$formatCompactNumber((float)$optionPrice/$option->quantity,4):'—' }}</strong></td>
            <td style="text-align:center">@if($canEditEstimate)<div class="es-money" style="justify-content:center"><input class="es-input es-discount-input option-discount" data-price-selector="prices[{{ $option->id }}]" data-target="discounted{{ $option->id }}" type="number" min="0" max="100" step=".01" name="discounts[{{ $option->id }}]" value="{{ $optionDiscount }}"><strong>%</strong></div>@else<strong>{{ $formatCompactNumber((float)$option->discount_percentage,2) }}%</strong>@endif</td>
            <td style="text-align:center"><strong class="es-discounted" id="discounted{{ $option->id }}">{{ $optionDiscounted!==null?$currency.' '.$formatCompactNumber($optionDiscounted,2):'—' }}</strong></td>
            @if($canEditEstimate)<td></td>@endif
        </tr>
    @endforeach
    @if($canEditEstimate)
        @foreach((array)old('new_quantities',[]) as $index => $oldQuantity)
            <tr class="quantity-option-row es-new-option" data-existing="0">
                <td style="text-align:center"><input class="es-input option-quantity" type="number" min="1" step="1" name="new_quantities[]" value="{{ $oldQuantity }}" placeholder="Quantity" required></td>
                <td style="text-align:center"><div class="es-money" style="justify-content:center"><span class="currency-code">{{ $currency }}</span><input class="es-input es-price option-price new-option-price" type="number" min="0" step=".01" name="new_prices[]" value="{{ old('new_prices.'.$index) }}" required></div></td>
                <td style="text-align:center"><strong class="option-unit-output">—</strong></td>
                <td style="text-align:center"><div class="es-money" style="justify-content:center"><input class="es-input es-discount-input option-discount" type="number" min="0" max="100" step=".01" name="new_discounts[]" value="{{ old('new_discounts.'.$index,0) }}"><strong>%</strong></div></td>
                <td style="text-align:center"><strong class="es-discounted option-discount-output">—</strong></td>
                <td style="text-align:center"><button class="es-btn es-danger remove-option-row" type="button" aria-label="Remove quantity"><i class="fas fa-times"></i></button></td>
            </tr>
        @endforeach
    @endif
    </tbody></table></div>
    @if($canEditEstimate)
        <div class="es-quantity-tools"><button class="es-btn" type="button" id="addQuantityOption"><i class="fas fa-plus"></i> Add Extra</button><span class="es-quantity-help"><i class="fas fa-magic"></i> New quantities use the first available per-unit rate automatically.</span></div>
    @endif
    <div style="margin-top:1rem"><span class="es-label">Estimator Internal Notes</span>@if($canEditEstimate)<textarea class="es-input es-note" name="estimator_notes" placeholder="Notes for the sales team...">{{ old('estimator_notes',$ticket->estimator_notes) }}</textarea>@else<div class="es-info">{{ $ticket->estimator_notes ?: 'No notes.' }}</div>@endif</div>
    @if($canEditEstimate)<div class="es-actions"><button class="es-btn" type="submit" formaction="{{ route('crm.estimate_tickets.export',$ticket->id) }}" formmethod="POST" formtarget="_blank"><i class="fas fa-file-export"></i> Export Estimate</button><button class="es-btn" type="submit" name="save_mode" value="draft" formnovalidate><i class="fas fa-save"></i> Save &amp; Exit</button><button class="es-btn es-primary" type="submit"><i class="fas fa-paper-plane"></i> Submit Costing to Team Lead</button></div>@endif
</div>
</form>

@if($canEditEstimate)
<div class="es-unsaved-backdrop" id="unsavedEstimateModal" role="dialog" aria-modal="true" aria-labelledby="unsavedEstimateTitle">
    <div class="es-unsaved-dialog">
        <div class="es-unsaved-head"><span class="es-unsaved-icon"><i class="fas fa-exclamation"></i></span><div><h3 id="unsavedEstimateTitle">Unsaved estimate changes</h3><div class="es-sub">Your costing changes have not been saved yet.</div></div></div>
        <div class="es-unsaved-body">Leaving this page now may lose the quantities, prices, discounts or notes you entered.</div>
        <div class="es-unsaved-actions">
            <button class="es-btn" type="button" id="stayOnEstimate">Stay Here</button>
            <button class="es-btn es-unsaved-discard" type="button" id="discardEstimateChanges">Discard</button>
            <button class="es-btn es-primary" type="button" id="saveEstimateAndExit"><i class="fas fa-save"></i> Save &amp; Exit</button>
        </div>
    </div>
</div>
<div class="es-card es-return-card">
    <div class="es-return-head"><span class="es-return-icon"><i class="fas fa-undo-alt"></i></span><h3>Return for Missing Information</h3></div>
    <p class="es-return-copy">Choose the relevant department and clearly explain what information, correction, or file is required.</p>
    <form method="POST" action="{{ route('crm.estimate_tickets.return',$ticket->id) }}" class="es-return-grid">
        {{ csrf_field() }}
        <select class="es-input" name="returned_to" required><option value="">Select department</option><option value="design">Return to Designer</option><option value="sales">Return to Sales Agent</option></select>
        <textarea class="es-input es-note" name="return_note" placeholder="Explain what information, correction, or file is required..." required></textarea>
        <button class="es-btn es-return-submit" type="submit"><i class="fas fa-reply"></i> Return Ticket</button>
    </form>
</div>
@endif

@if($ticket->status === 'team_lead_open' || $ticket->options->filter(function ($option) { return $option->offer_price !== null; })->count())
<div class="es-card">
    <h3><i class="fas fa-user-check" style="color:var(--primary-purple);margin-right:.4rem"></i> Team Lead Final Offer</h3>
    <div class="es-info" style="margin-bottom:1rem"><span class="es-label">Estimator Costing Currency</span><strong>{{ $ticket->currency ?: 'USD' }}</strong><div class="es-sub">Team Lead offer will use the same currency.</div></div>
    <form method="POST" action="{{ route('crm.estimate_tickets.team_lead_offer',$ticket->id) }}">{{ csrf_field() }}
        <div class="es-table-wrap"><table class="es-table es-quantity-table"><thead><tr><th style="text-align:center">Quantity</th><th style="text-align:center">Estimator Price After Discount</th><th style="text-align:center">Estimator Unit</th><th style="text-align:center">Profit Margin %</th><th style="text-align:center">Final Offer Price</th><th style="text-align:center">Offer Per Unit</th></tr></thead><tbody>
        @foreach($ticket->options as $option)
            @php
                $estimatorFinalPrice = $option->discounted_price !== null ? $option->discounted_price : $option->total_price;
                $estimatorFinalUnit = $option->quantity > 0 ? $estimatorFinalPrice / $option->quantity : 0;
                $savedMargin = $option->profit_margin_percentage;
                if ($savedMargin === null && $option->offer_price !== null && (float)$estimatorFinalPrice > 0) {
                    $savedMargin = (($option->offer_price / $estimatorFinalPrice) - 1) * 100;
                }
                $teamLeadMargin = old('profit_margins.'.$option->id, $savedMargin);
                $calculatedOffer = $teamLeadMargin !== null
                    ? round((float)$estimatorFinalPrice * (1 + ((float)$teamLeadMargin / 100)), 2)
                    : null;
            @endphp
            <tr><td><strong>{{ $formatCompactNumber($option->quantity) }}</strong></td><td><strong>{{ $ticket->currency }} {{ $formatCompactNumber((float)$estimatorFinalPrice,2) }}</strong>@if((float)$option->discount_percentage > 0)<div class="es-sub">{{ $formatCompactNumber((float)$option->discount_percentage,2) }}% discount</div>@endif</td><td>{{ $ticket->currency }} {{ $formatCompactNumber((float)$estimatorFinalUnit,4) }}</td>
                <td>@if($canTeamLeadReview)<div class="es-money"><input class="es-input es-discount-input profit-margin" data-base-price="{{ (float)$estimatorFinalPrice }}" data-qty="{{ $option->quantity }}" data-offer-target="offer{{ $option->id }}" data-unit-target="offerUnit{{ $option->id }}" name="profit_margins[{{ $option->id }}]" type="number" min="0" max="1000" step=".01" value="{{ $teamLeadMargin }}" placeholder="0" required><strong>%</strong></div>@else<strong>{{ $savedMargin!==null?$formatCompactNumber((float)$savedMargin,2).'%':'—' }}</strong>@endif</td>
                <td><strong class="es-discounted" id="offer{{ $option->id }}">{{ $calculatedOffer!==null?$ticket->currency.' '.$formatCompactNumber($calculatedOffer,2):($option->offer_price!==null?$ticket->currency.' '.$formatCompactNumber($option->offer_price,2):'—') }}</strong></td>
                <td><strong id="offerUnit{{ $option->id }}">{{ $calculatedOffer!==null&&$option->quantity>0?$ticket->currency.' '.$formatCompactNumber($calculatedOffer/$option->quantity,4):($option->offer_unit_price!==null?$ticket->currency.' '.$formatCompactNumber($option->offer_unit_price,4):'—') }}</strong></td></tr>
        @endforeach
        </tbody></table></div>
        <div style="margin-top:1rem"><span class="es-label">Team Lead Notes</span>@if($canTeamLeadReview)<textarea class="es-input es-note" name="team_lead_notes" placeholder="Final offer notes for sales...">{{ old('team_lead_notes',$ticket->team_lead_notes) }}</textarea>@else<div class="es-info">{{ $ticket->team_lead_notes ?: 'No notes.' }}</div>@endif</div>
        @if($canTeamLeadReview)<div class="es-actions"><button class="es-btn es-primary"><i class="fas fa-check-circle"></i> Approve Offer & Send to Sales Inquiry</button></div>@endif
    </form>
    @if($canTeamLeadReview)
        <div class="es-return-card" style="margin-top:1rem">
            <div class="es-return-head">
                <span class="es-return-icon"><i class="fas fa-undo-alt"></i></span>
                <h3>Return to Estimator</h3>
            </div>
            <p class="es-return-copy">Send the estimate back with a clear correction or pricing note. The estimator will receive it in their active work queue.</p>
            <form method="POST" action="{{ route('crm.estimate_tickets.return',$ticket->id) }}" class="es-return-grid" style="grid-template-columns:minmax(260px,1fr) auto">
                {{ csrf_field() }}
                <input type="hidden" name="returned_to" value="estimator">
                <textarea class="es-input es-note" name="return_note" placeholder="Explain what the estimator needs to revise..." required></textarea>
                <button class="es-btn es-return-submit" type="submit"><i class="fas fa-reply"></i> Return to Estimator</button>
            </form>
        </div>
    @endif
</div>
@endif

@if(($u->isSales() || $u->isSalesManager() || $u->isAdmin()) && $ticket->status==='estimated')
<div class="es-card"><h3>Sales Review</h3>
    @if($ticket->crm_email_id)<form method="POST" action="{{ route('crm.estimate_tickets.send_chat',$ticket->id) }}" style="margin-bottom:1rem">{{ csrf_field() }}<button class="es-btn es-primary" style="width:100%"><i class="fas fa-file-pdf"></i> Prepare PDF in Client Reply</button></form>@endif
    <div class="es-sales-actions">
        <div class="es-action-box"><strong>Request Revision</strong><div class="es-sub" style="margin:.3rem 0 .65rem">Tell the estimator what needs to change.</div><form method="POST" action="{{ route('crm.estimate_tickets.revision',$ticket->id) }}" style="display:flex;gap:.5rem">{{ csrf_field() }}<textarea class="es-input" name="revision_note" required></textarea><button class="es-btn">Send</button></form></div>
        <div class="es-action-box"><strong>Approve Prices</strong><div class="es-sub" style="margin:.3rem 0 .65rem">Complete this estimate ticket.</div><form method="POST" action="{{ route('crm.estimate_tickets.complete',$ticket->id) }}">{{ csrf_field() }}<button class="es-btn es-primary" style="width:100%"><i class="fas fa-check"></i> Complete Ticket</button></form></div>
    </div>
</div>
@endif
@if(($u->isSales() || $u->isSalesManager() || $u->isAdmin()) && $ticket->status==='completed' && $ticket->crm_email_id)<div class="es-card"><form method="POST" action="{{ route('crm.estimate_tickets.send_chat',$ticket->id) }}">{{ csrf_field() }}<button class="es-btn es-primary" style="width:100%"><i class="fas fa-file-pdf"></i> Prepare PDF in Client Reply</button></form></div>@endif

</div>
<script>
function addExtraCost(){
    var row=document.createElement('tr');row.className='extra-cost-row';
    var currency=document.querySelector('[name="currency"]') ? document.querySelector('[name="currency"]').value : {!! json_encode($currency) !!};
    row.innerHTML='<td><input class="es-input" name="extra_names[]" placeholder="Fee name" required></td><td><input class="es-input" name="extra_details[]" placeholder="Optional detail"></td><td><input class="es-input cost-quantity" type="number" min="0" step=".01" name="extra_quantities[]" value="1" oninput="calculateRowCost(this)"></td><td><div class="es-money"><span class="currency-code">'+currency+'</span><input class="es-input es-price cost-unit-price" type="number" min="0" step=".0001" name="extra_unit_prices[]" oninput="calculateRowCost(this)"></div></td><td><div class="es-money"><span class="currency-code">'+currency+'</span><input class="es-input es-price cost-price" type="number" min="0" step=".01" name="extra_prices[]" oninput="calculateUnitPriceFromCost(this)"><button class="es-btn es-danger" type="button" onclick="this.closest(&quot;tr&quot;).remove();calculateCostSummary()"><i class="fas fa-times"></i></button></div></td>';
    document.getElementById('costRows').appendChild(row);
}
function addFixedExtraCost(){
    var row=document.createElement('tr');row.className='extra-fixed-cost-row';
    var currency=document.querySelector('[name="currency"]') ? document.querySelector('[name="currency"]').value : {!! json_encode($currency) !!};
    row.innerHTML='<td><input class="es-input" name="fixed_extra_names[]" placeholder="Fixed cost name" required></td><td><input class="es-input" name="fixed_extra_details[]" placeholder="Optional detail"></td><td><div class="es-money"><span class="currency-code">'+currency+'</span><input class="es-input es-price cost-price" type="number" min="0" step=".01" name="fixed_extra_prices[]" value="0" oninput="calculateCostSummary()"><button class="es-btn es-danger" type="button" onclick="this.closest(&quot;tr&quot;).remove();calculateCostSummary()" aria-label="Remove fixed cost"><i class="fas fa-times"></i></button></div></td>';
    document.getElementById('fixedCostRows').appendChild(row);
}
function formatInputNumber(value,precision){
    var factor=Math.pow(10,precision||2);
    return String(Math.round((Number(value)||0)*factor)/factor);
}
// Display formatter: thousands separators, NO trailing zeros (2330.00 -> "2,330", 2330.50 -> "2,330.5").
function nfz(value,dec){
    return (Number(value)||0).toLocaleString('en-US',{minimumFractionDigits:0,maximumFractionDigits:dec===undefined?2:dec});
}
function calculateRowCost(input){
    var row=input.closest('tr'),quantity=row?row.querySelector('.cost-quantity'):null,unitPrice=row?row.querySelector('.cost-unit-price'):null,total=row?row.querySelector('.cost-price'):null;
    // Blank quantity behaves as 1, so entering only a unit price still yields a cost.
    var q=(quantity&&quantity.value!=='')?Number(quantity.value||0):1;
    var hasUnit=unitPrice&&unitPrice.value!=='';
    if(total)total.value=hasUnit?formatInputNumber(q*Number(unitPrice.value||0),2):'';
    calculateCostSummary();
}
function calculateUnitPriceFromCost(input){
    var row=input.closest('tr'),quantity=row?row.querySelector('.cost-quantity'):null,unitPrice=row?row.querySelector('.cost-unit-price'):null;
    var quantityValue=Number(quantity?quantity.value:0)||0,totalValue=Number(input.value||0)||0;
    if(unitPrice)unitPrice.value=formatInputNumber(quantityValue>0?totalValue/quantityValue:0,4);
    calculateCostSummary();
}
document.addEventListener('input',function(event){
    var input=event.target;
    if(!input.matches('#costRows .cost-price'))return;
    var row=input.closest('tr'),quantity=row.querySelector('.cost-quantity'),unitPrice=row.querySelector('.cost-unit-price');
    if(!quantity||!unitPrice)return;
    var quantityValue=Number(quantity.value||0),totalValue=Number(input.value||0);
    unitPrice.value=formatInputNumber(quantityValue>0?totalValue/quantityValue:0,4);
});
function calculateCostSummary(){
    if(!document.getElementById('baseCost'))return;
    var variableBase=0,fixedBase=0;
    document.querySelectorAll('#costRows .cost-price').forEach(function(input){variableBase+=Number(input.value||0)});
    document.querySelectorAll('#fixedCostRows .cost-price').forEach(function(input){fixedBase+=Number(input.value||0)});
    var base=variableBase+fixedBase;
    var wasteInput=document.querySelector('[name="waste_material_percentage"]'),waste=base*(Number(wasteInput?wasteInput.value:0)/100);
    var productionPercentageInput=document.querySelector('[name="production_cost_percentage"]'),productionPercentage=Number(productionPercentageInput?productionPercentageInput.value:0);
    var productionPercentageCost=(base+waste)*(productionPercentage/100),beforeVat=base+waste+productionPercentageCost;
    var vatInput=document.querySelector('[name="vat_percentage"]'),vatPercentage=Number(vatInput?vatInput.value:5),vat=beforeVat*(vatPercentage/100),production=beforeVat+vat;
    document.getElementById('baseCost').textContent=nfz(base,2);document.getElementById('wasteCost').textContent=nfz(waste,2);document.getElementById('productionPercentageCost').textContent=nfz(productionPercentageCost,2);document.getElementById('vatCost').textContent=nfz(vat,2);document.getElementById('productionCost').textContent=nfz(production,2);
    var firstPrice=document.querySelector('.option-price');
    if(firstPrice&&firstPrice.dataset.manual!=='1'){
        firstPrice.value=production>0?formatInputNumber(production,2):'';
        firstPrice.dataset.autoProduction='1';
        if(typeof refreshOptionRow==='function')refreshOptionRow(firstPrice.closest('.quantity-option-row'));
    }
    if(firstPrice){
        var firstRow=firstPrice.closest('.quantity-option-row'),firstQuantity=optionRowQuantity(firstRow);
        var hasReferencePrice=firstPrice.value!==''&&firstQuantity>0;
        var unitRate=hasReferencePrice?Math.max(0,Number(firstPrice.value)-fixedCostTotal())/firstQuantity:0;
        document.querySelectorAll('.quantity-option-row').forEach(function(row){
            if(row===firstRow)return;
            var priceInput=row.querySelector('.option-price'),quantity=optionRowQuantity(row);
            if(!priceInput||priceInput.dataset.manual==='1')return;
            priceInput.value=hasReferencePrice&&quantity>0?formatInputNumber(fixedCostTotal()+(quantity*unitRate),2):'';
            priceInput.dataset.autoQuantity='1';
            if(typeof refreshOptionRow==='function')refreshOptionRow(row);
        });
    }
}
function selectedCurrency(){var picker=document.querySelector('[name="currency"]');return picker?picker.value:{!! json_encode($currency) !!}}
var currencyPicker=document.querySelector('[name="currency"]');
if(currencyPicker){currencyPicker.addEventListener('change',function(){document.querySelectorAll('.currency-code').forEach(function(el){el.textContent=currencyPicker.value});document.querySelectorAll('.quantity-option-row').forEach(function(row){refreshOptionRow(row)})})}
var quantityRows=document.getElementById('quantityOptionRows');
function optionRowQuantity(row){
    var quantityInput=row.querySelector('.option-quantity'),priceInput=row.querySelector('.option-price');
    return Number(quantityInput?quantityInput.value:(priceInput?priceInput.dataset.qty:0))||0;
}
function fixedCostTotal(){
    var total=0;
    document.querySelectorAll('#fixedCostRows .cost-price').forEach(function(input){total+=Number(input.value||0)});
    var wasteInput=document.querySelector('[name="waste_material_percentage"]'),wastePercentage=Number(wasteInput?wasteInput.value:0);
    var productionPercentageInput=document.querySelector('[name="production_cost_percentage"]'),productionPercentage=Number(productionPercentageInput?productionPercentageInput.value:0);
    var vatInput=document.querySelector('[name="vat_percentage"]'),vatPercentage=Number(vatInput?vatInput.value:5);
    return total*(1+wastePercentage/100)*(1+productionPercentage/100)*(1+vatPercentage/100);
}
function referenceUnitRate(excludeRow){
    var rate=0;
    document.querySelectorAll('.quantity-option-row').forEach(function(row){
        if(rate||row===excludeRow)return;
        var priceInput=row.querySelector('.option-price'),quantity=optionRowQuantity(row),price=Number(priceInput&&priceInput.value);
        if(priceInput&&priceInput.value!==''&&quantity>0&&price>=0)rate=Math.max(0,price-fixedCostTotal())/quantity;
    });
    return rate;
}
function refreshOptionRow(row){
    var priceInput=row.querySelector('.option-price'),discountInput=row.querySelector('.option-discount');
    if(!priceInput)return;
    var quantity=optionRowQuantity(row),price=Number(priceInput.value||0),discount=Math.min(100,Math.max(0,Number(discountInput?discountInput.value:0)));
    var unitOutput=priceInput.dataset.target?document.getElementById(priceInput.dataset.target):row.querySelector('.option-unit-output');
    var discountOutput=priceInput.dataset.discountTarget?document.getElementById(priceInput.dataset.discountTarget):row.querySelector('.option-discount-output');
    if(unitOutput)unitOutput.textContent=priceInput.value!==''&&quantity?selectedCurrency()+' '+nfz(price/quantity,4):'—';
    if(discountOutput)discountOutput.textContent=priceInput.value!==''?selectedCurrency()+' '+nfz(price*(1-discount/100),2):'—';
}
function autoPriceNewRow(row){
    var quantity=optionRowQuantity(row),priceInput=row.querySelector('.new-option-price'),rate=referenceUnitRate(row);
    var hasReference=Array.from(document.querySelectorAll('.quantity-option-row')).some(function(referenceRow){
        var referencePrice=referenceRow.querySelector('.option-price');
        return referenceRow!==row&&referencePrice&&referencePrice.value!==''&&optionRowQuantity(referenceRow)>0;
    });
    if(priceInput&&priceInput.dataset.manual!=='1'){
        priceInput.value=quantity>0&&hasReference?formatInputNumber(fixedCostTotal()+(quantity*rate),2):'';
        priceInput.dataset.autoQuantity='1';
    }
    refreshOptionRow(row);
}
function refreshAutomaticRows(){
    document.querySelectorAll('.es-new-option').forEach(function(row){autoPriceNewRow(row)});
}
if(quantityRows){
    quantityRows.addEventListener('input',function(event){
        var row=event.target.closest('.quantity-option-row');if(!row)return;
        if(event.target.classList.contains('option-quantity'))autoPriceNewRow(row);
        if(event.target.classList.contains('option-price')&&event.isTrusted){event.target.dataset.manual='1';delete event.target.dataset.autoQuantity;delete event.target.dataset.autoProduction}
        refreshOptionRow(row);
        if(row.dataset.existing==='1'&&event.target.classList.contains('option-price'))refreshAutomaticRows();
    });
    quantityRows.addEventListener('click',function(event){
        var remove=event.target.closest('.remove-option-row');if(!remove)return;
        remove.closest('tr').remove();
    });
}
var addQuantityButton=document.getElementById('addQuantityOption');
if(addQuantityButton)addQuantityButton.addEventListener('click',function(){
    var row=document.createElement('tr');row.className='quantity-option-row es-new-option';row.dataset.existing='0';
    row.innerHTML='<td><input class="es-input option-quantity" type="number" min="1" step="1" name="new_quantities[]" placeholder="Quantity" required></td><td><div class="es-money"><span class="currency-code">'+selectedCurrency()+'</span><input class="es-input es-price option-price new-option-price" type="number" min="0" step=".01" name="new_prices[]" required></div></td><td><strong class="option-unit-output">—</strong></td><td><div class="es-money"><input class="es-input es-discount-input option-discount" type="number" min="0" max="100" step=".01" name="new_discounts[]"><strong>%</strong></div></td><td><strong class="es-discounted option-discount-output">—</strong></td><td><button class="es-btn es-danger remove-option-row" type="button" aria-label="Remove quantity"><i class="fas fa-times"></i></button></td>';
    quantityRows.appendChild(row);row.querySelector('.option-quantity').focus();
});
document.querySelectorAll('.quantity-option-row').forEach(function(row){refreshOptionRow(row)});
document.querySelectorAll('.profit-margin').forEach(function(input){input.addEventListener('input',function(){var base=Number(input.dataset.basePrice||0),qty=Number(input.dataset.qty||0),currency={!! json_encode($currency) !!};var margin=Number(input.value||0),offer=base*(1+margin/100);document.getElementById(input.dataset.offerTarget).textContent=input.value!==''?currency+' '+nfz(offer,2):'—';document.getElementById(input.dataset.unitTarget).textContent=input.value!==''&&qty?currency+' '+nfz(offer/qty,4):'—'})});
@if($canEditEstimate)
(function(){
    var root=document.querySelector('.es-page'),form=document.getElementById('professionalEstimateForm'),modal=document.getElementById('unsavedEstimateModal');
    var stayButton=document.getElementById('stayOnEstimate'),discardButton=document.getElementById('discardEstimateChanges'),saveButton=document.getElementById('saveEstimateAndExit');
    var isDirty=false,isLeaving=false,pendingUrl='';
    if(!root||!form||!modal)return;
    function markDirty(){if(!isLeaving)isDirty=true}
    function openModal(url){pendingUrl=url||'';modal.classList.add('is-open');document.body.style.overflow='hidden';stayButton.focus()}
    function closeModal(){modal.classList.remove('is-open');document.body.style.overflow='';pendingUrl=''}
    root.addEventListener('input',markDirty);
    root.addEventListener('change',markDirty);
    form.addEventListener('click',function(event){
        if(event.target.closest('#addQuantityOption,.remove-option-row,[onclick*="addExtraCost"],[onclick*="addFixedExtraCost"]'))markDirty();
    });
    document.addEventListener('click',function(event){
        if(event.target.closest('#pcUse,.material-use'))markDirty();
    });
    form.addEventListener('submit',function(event){
        var submitter=event.submitter;
        if(submitter&&submitter.getAttribute('formtarget')==='_blank')return;
        isLeaving=true;
        isDirty=false;
    });
    document.addEventListener('click',function(event){
        if(!isDirty||isLeaving||event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
        var link=event.target.closest('a[href]');
        if(!link||link.target==='_blank'||link.hasAttribute('download')||link.closest('#unsavedEstimateModal'))return;
        var href=link.getAttribute('href');
        if(!href||href.charAt(0)==='#'||href.indexOf('javascript:')===0)return;
        event.preventDefault();
        openModal(link.href);
    },true);
    stayButton.addEventListener('click',closeModal);
    discardButton.addEventListener('click',function(){
        var url=pendingUrl;
        isLeaving=true;
        isDirty=false;
        closeModal();
        if(url)window.location.href=url;
    });
    saveButton.addEventListener('click',function(){
        var draftButton=form.querySelector('button[name="save_mode"][value="draft"]');
        var redirectInput=document.getElementById('estimateRedirectTo');
        if(redirectInput&&pendingUrl){
            var destination=new URL(pendingUrl,window.location.href);
            redirectInput.value=destination.origin===window.location.origin
                ? destination.pathname+destination.search+destination.hash
                : '';
        }
        isLeaving=true;
        isDirty=false;
        closeModal();
        if(draftButton)form.requestSubmit(draftButton);
    });
    modal.addEventListener('click',function(event){if(event.target===modal)closeModal()});
    document.addEventListener('keydown',function(event){if(event.key==='Escape'&&modal.classList.contains('is-open'))closeModal()});
    window.addEventListener('beforeunload',function(event){
        if(!isDirty||isLeaving)return;
        event.preventDefault();
        event.returnValue='';
    });
})();
@endif
calculateCostSummary();
</script>
@include('crm.estimate_tickets.partials.rate_matrix_modal')
@endsection
