<?php

namespace App\Services;

class CostingEngine
{
    /**
     * Calculate detailed cost breakdown and recommended selling price based on packaging specs.
     *
     * @param array $specs
     * @return array
     */
    public function calculate(array $specs)
    {
        // Extract inputs with sensible defaults
        $width = isset($specs['width']) ? floatval($specs['width']) : 6.0;
        $height = isset($specs['height']) ? floatval($specs['height']) : 6.0;
        $length = isset($specs['length']) ? floatval($specs['length']) : 2.0;
        $quantity = isset($specs['quantity']) ? intval($specs['quantity']) : 1000;
        $material = isset($specs['material']) ? strtolower($specs['material']) : 'cardstock';
        $colors = isset($specs['colors']) ? strtolower($specs['colors']) : '4/0 cmyk';
        $lamination = isset($specs['lamination']) ? strtolower($specs['lamination']) : 'none';
        $gluing = isset($specs['gluing']) ? strtolower($specs['gluing']) : 'straight line glue';
        $shippingRegion = isset($specs['shipping_region']) ? strtolower($specs['shipping_region']) : 'usa';

        if ($quantity <= 0) {
            $quantity = 1;
        }

        // 1. Calculate Surface Area (flat sheet layout size estimate)
        // Approximate box layout flat area: 2 * (L*W + W*H + L*H)
        $surfaceArea = 2 * (($length * $width) + ($width * $height) + ($length * $height));
        if ($surfaceArea <= 0) {
            $surfaceArea = 100; // default sq inches
        }

        // 2. Paper Cost (varies by material and size)
        $materialRates = [
            'cardstock' => 0.003,      // per sq inch
            'kraft' => 0.0025,
            'corrugated' => 0.004,
            'rigid' => 0.012,
            'sticker' => 0.0015,
            'linen' => 0.005,
        ];
        $paperRate = isset($materialRates[$material]) ? $materialRates[$material] : 0.003;
        $paperCost = $surfaceArea * $paperRate * $quantity;

        // 3. Ink & Color Printing Cost
        // Plate making fee + Ink run cost
        $plateCost = 0.0;
        $inkCost = 0.0;

        // Determine number of printing plates needed
        $numColors = 1;
        if (strpos($colors, '4/') !== false) {
            $numColors = 4;
        } elseif (strpos($colors, '2') !== false) {
            $numColors = 2;
        } elseif (strpos($colors, 'pantone') !== false || strpos($colors, 'spot') !== false) {
            $numColors = 5;
        }

        // Flat plate fee per color (e.g. $15/plate)
        $plateCost = $numColors * 15.00;

        // Ink run rate per sq inch per sheet
        $inkRate = 0.0002 * $numColors;
        $inkCost = $surfaceArea * $inkRate * $quantity;

        // 4. Coating / Lamination Cost (per sq inch)
        $coatingRates = [
            'gloss lamination' => 0.0008,
            'matte lamination' => 0.0010,
            'soft touch lamination' => 0.0018,
            'uv coating' => 0.0005,
            'aqueous coating' => 0.0003,
            'gloss coating' => 0.0004,
            'matte coating' => 0.0005,
        ];
        $coatingRate = isset($coatingRates[$lamination]) ? $coatingRates[$lamination] : 0.0;
        $coatingCost = $surfaceArea * $coatingRate * $quantity;

        // 5. Die & Cutting Tool Cost
        // Physical die template (flat fee) + running machine time
        $dieSetupFee = 45.00; // standard custom die mold
        $cuttingRunRate = 0.015; // per unit cutting cost
        $dieCuttingCost = $dieSetupFee + ($cuttingRunRate * $quantity);

        // 6. Gluing & Assembly Stage
        $gluingRates = [
            'straight line glue' => 0.012,
            'auto lock bottom' => 0.025,
            '4 corner glue' => 0.035,
            '5 corner glue' => 0.040,
            '6 corner glue' => 0.045,
            'burger box glue' => 0.015,
            'gift box assembly' => 0.150, // hand labor
        ];
        $gluingRate = isset($gluingRates[$gluing]) ? $gluingRates[$gluing] : 0.012;
        $gluingCost = $gluingRate * $quantity;

        // 7. Setup & Labor Costs
        // Run setup, prepress setup, plate mounting
        $prepressSetupLabor = 30.00;
        $pressRunLabor = ($quantity / 1000) * 15.00; // $15 per 1000 sheets
        $laborCost = $prepressSetupLabor + $pressRunLabor;

        // 8. Shipping / Freight Estimation
        $shippingRates = [
            'usa' => ['base' => 80.00, 'per_unit' => 0.05],
            'uae' => ['base' => 40.00, 'per_unit' => 0.025],
            'pk' => ['base' => 25.00, 'per_unit' => 0.01],
            'europe' => ['base' => 90.00, 'per_unit' => 0.06],
            'uk' => ['base' => 85.00, 'per_unit' => 0.05],
            'canada' => ['base' => 85.00, 'per_unit' => 0.055],
        ];
        $shipRate = isset($shippingRates[$shippingRegion]) ? $shippingRates[$shippingRegion] : $shippingRates['usa'];
        $shippingCost = $shipRate['base'] + ($shipRate['per_unit'] * $quantity);

        // 9. Total Production Cost
        $totalProductionCost = $paperCost + $plateCost + $inkCost + $coatingCost + $dieCuttingCost + $gluingCost + $laborCost + $shippingCost;

        // 10. Margin / Markup Calculation
        // Markup decreases slightly for high volume orders to be competitive
        $markupPercent = 0.40; // 40% standard margin
        if ($quantity >= 5000) {
            $markupPercent = 0.30;
        } elseif ($quantity >= 10000) {
            $markupPercent = 0.25;
        } elseif ($quantity >= 50000) {
            $markupPercent = 0.20;
        }

        $marginProfit = $totalProductionCost * $markupPercent;
        $recommendedPrice = $totalProductionCost + $marginProfit;
        $unitPrice = $recommendedPrice / $quantity;

        return [
            'specs' => [
                'width' => $width,
                'height' => $height,
                'length' => $length,
                'quantity' => $quantity,
                'material' => $material,
                'colors' => $colors,
                'lamination' => $lamination,
                'gluing' => $gluing,
                'shipping_region' => $shippingRegion,
            ],
            'surface_area_sqin' => round($surfaceArea, 2),
            'cost_breakdown' => [
                'paper' => round($paperCost, 2),
                'plates' => round($plateCost, 2),
                'ink' => round($inkCost, 2),
                'coating_lamination' => round($coatingCost, 2),
                'die_cutting' => round($dieCuttingCost, 2),
                'gluing_assembly' => round($gluingCost, 2),
                'labor' => round($laborCost, 2),
                'shipping_freight' => round($shippingCost, 2),
            ],
            'total_production_cost' => round($totalProductionCost, 2),
            'markup_percent' => $markupPercent * 100,
            'margin_profit' => round($marginProfit, 2),
            'recommended_price' => round($recommendedPrice, 2),
            'unit_price' => round($unitPrice, 4),
        ];
    }
}
