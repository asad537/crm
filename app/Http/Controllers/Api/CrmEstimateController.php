<?php

namespace App\Http\Controllers\Api;

use App\CrmEmail;
use App\EstimateTicket;
use App\Http\Controllers\Controller;
use App\Services\CostingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CrmEstimateController extends Controller
{
    // POST /api/crm/estimate  (Bearer workspace key, same as /api/crm/leads)
    // Shared CostingEngine production cost + a flat COMPANY PROFIT margin (ALEX_ESTIMATE_MARGIN, default 50%),
    // optional insert surcharge. Returns an approximate quote and records a completed itemised EstimateTicket.
    public function store(Request $request)
    {
        $workspace = $request->attributes->get('crm_workspace');

        $data = $request->validate([
            'length'          => 'required|numeric|min:0.1',
            'width'           => 'required|numeric|min:0.1',
            'height'          => 'required|numeric|min:0.1',
            'quantity'        => 'required|integer|min:1',
            'material'        => 'nullable|string|max:100',
            'colors'          => 'nullable|string|max:100',
            'lamination'      => 'nullable|string|max:100',
            'gluing'          => 'nullable|string|max:100',
            'inserts'         => 'nullable|string|max:100',
            'shipping_region' => 'nullable|string|max:100',
            'product_style'   => 'nullable|string|max:255',
            'external_lead_id'=> 'nullable|string|max:191',
            'client_name'     => 'nullable|string|max:255',
            'client_email'    => 'nullable|email|max:255',
        ]);

        $breakdown = (new CostingEngine())->calculate($data);
        $qty  = (int) $data['quantity'];
        $cb   = $breakdown['cost_breakdown'] ?? [];
        $area = (float) ($breakdown['surface_area_sqin'] ?? 0);
        $prod = round((float) ($breakdown['total_production_cost'] ?? 0), 2);

        // Optional insert surcharge (per box).
        $insert = strtolower(trim((string) ($data['inserts'] ?? '')));
        $insertRate = 0.0;
        if (str_contains($insert, 'foam')) $insertRate = 0.35;
        elseif ($insert !== '' && $insert !== 'none' && $insert !== 'no' && $insert !== 'nil') $insertRate = 0.20;
        $insertCost = round($insertRate * $qty, 2);

        // Flat company-profit margin (owner wants 50%).
        $marginPct = (float) (env('ALEX_ESTIMATE_MARGIN', 50));
        if ($marginPct <= 0) $marginPct = 50;
        $base      = round($prod + $insertCost, 2);
        $marginAmt = round($base * $marginPct / 100, 2);
        $total     = round($base + $marginAmt, 2);
        $unit      = round($qty > 0 ? $total / $qty : 0, 2);

        // Bargain floor: the lowest price Alex may settle at (default 40% profit) - NEVER revealed as a margin to the client.
        $floorPct  = (float) (env('ALEX_ESTIMATE_FLOOR_MARGIN', 40));
        if ($floorPct <= 0 || $floorPct > $marginPct) $floorPct = min(40.0, $marginPct);
        $bestTotal = round($base * (1 + $floorPct / 100), 2);
        $bestUnit  = round($qty > 0 ? $bestTotal / $qty : 0, 2);

        $L = (float) $data['length'];
        $W = (float) $data['width'];
        $H = (float) $data['height'];
        $fmt = function ($n) { return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.'); };
        $openL = $L + 2 * $H;
        $openW = 2 * ($W + $H) + 0.5;
        $finishSize = $fmt($L) . 'x' . $fmt($W) . 'x' . $fmt($H);
        $flatSize   = $fmt($openL) . ' x ' . $fmt($openW);

        $items = [];
        $add = function ($name, $price, $detail, $section = null, $perUnit = false, $extra = []) use (&$items, $qty) {
            $price = round((float) $price, 2);
            $it = array_merge(['name' => $name, 'price' => $price, 'detail' => $detail], $extra);
            if ($perUnit) { $it['quantity'] = $qty; $it['unit_price'] = round($qty > 0 ? $price / $qty : 0, 4); }
            if ($section) $it['section'] = $section;
            $items[] = $it;
        };
        $add('Paper Dimensions', $cb['paper'] ?? 0, $fmt($area) . ' sq in open area/box', null, true);
        $add('CTP Plate', $cb['plates'] ?? 0, 'Prepress CMYK / spot plates');
        $add('Printing / Color', $cb['ink'] ?? 0, trim((string) ($data['colors'] ?? '4/0 cmyk')), null, true);
        if ((float) ($cb['coating_lamination'] ?? 0) > 0) {
            $add('Lamination / Coating', $cb['coating_lamination'], (string) ($data['lamination'] ?? 'lamination'), null, true);
        }
        $add('Die Cost', $cb['die_cutting'] ?? 0, 'Die making + cutting', 'fixed');
        $add('Pasting (Single Pasting)', $cb['gluing_assembly'] ?? 0, trim((string) ($data['gluing'] ?? 'gluing')) . ' assembly', null, true);
        if ($insertCost > 0) {
            $add('Insert', $insertCost, ucfirst($insert) . ' insert', null, true);
        }
        $add('Labor & Setup', $cb['labor'] ?? 0, 'Prepress + press run labor');
        $add('Shipping', $cb['shipping_freight'] ?? 0, 'Freight to ' . (string) ($data['shipping_region'] ?? 'usa'), 'fixed');
        $add('Production Cost', $marginAmt, $fmt($marginPct) . '% company profit', null, false, ['percentage' => $marginPct]);

        $ticketNumber = null;
        try {
            $lead = null;
            if (!empty($data['external_lead_id']) && Schema::hasColumn('crm_emails', 'external_lead_id')) {
                $lead = CrmEmail::withoutGlobalScopes()
                    ->where('external_lead_id', $data['external_lead_id'])
                    ->when($workspace, fn($q) => $q->where('workspace_id', $workspace->id))
                    ->first();
            }

            $ticketNumber = 'EST-ALEX-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $ticket = EstimateTicket::create([
                'workspace_id'    => $workspace->id ?? ($lead->workspace_id ?? null),
                'ticket_number'   => $ticketNumber,
                'crm_email_id'    => $lead->id ?? null,
                'client_name'     => $data['client_name'] ?? ($lead->client_name ?? 'Website Visitor'),
                'client_email'    => $data['client_email'] ?? ($lead->client_email ?? null),
                'product_style'   => $data['product_style'] ?? 'Custom Box',
                'length'          => $L,
                'width'           => $W,
                'height'          => $H,
                'unit'            => 'in',
                'stock'           => $data['material'] ?? null,
                'colors'          => $data['colors'] ?? null,
                'printing'        => $data['colors'] ?? null,
                'lamination'      => $data['lamination'] ?? null,
                'gluing'          => $data['gluing'] ?? null,
                'shipping_region' => $data['shipping_region'] ?? null,
                'finish_size'     => $finishSize,
                'flat_size'       => $flatSize,
                'requirements'    => ($insert !== '' && $insertRate > 0) ? ('Insert: ' . $insert) : null,
                'cost_breakdown'  => $items,
                'currency'        => 'USD',
                'estimator_notes' => 'Auto-estimate by Alex (approximate). List price ' . $fmt($marginPct) . '% profit = $' . $total . '; bargain floor ' . $fmt($floorPct) . '% profit = $' . $bestTotal . '. From the shared CostingEngine; adjust with real rates if needed.',
                'status'          => 'completed',
                'completed_at'    => now(),
            ]);
            $ticket->options()->create([
                'quantity'    => $qty,
                'total_price' => $total,
                'unit_price'  => $unit,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Alex estimate ticket create failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'        => true,
            'currency'       => 'USD',
            'quantity'       => $qty,
            'unit_price'      => $unit,
            'total_price'     => $total,
            'best_unit_price' => $bestUnit,
            'best_total_price'=> $bestTotal,
            'margin_percent'  => $marginPct,
            'open_size_in'   => $flatSize . ' in',
            'open_size_sqin' => $area,
            'ticket_number'  => $ticketNumber,
        ]);
    }
}
