            <!-- Product Specs -->
            <!-- Product Specs -->
            <div class="crm-card">
                <div class="card-header-title" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fas fa-box-open"></i> Product Specs</span>
                    <button type="button" onclick="document.getElementById('specsDisplayArea').style.display='none'; document.getElementById('specsEditArea').style.display='block';" style="background:none; border:none; color:var(--primary-purple); cursor:pointer; font-size:0.8rem; font-weight:bold;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>

                @php
                    $changedSpecs = is_array($email->changed_specs) ? $email->changed_specs : [];
                    $editedBadge = '<span style="font-size: 0.65rem; background: #fef08a; color: #854d0e; padding: 3px 8px; border-radius: 99px; font-weight: 700; white-space: nowrap; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Edited</span>';
                    $formatSpecValue = function ($value) {
                        if (!is_array($value)) return (string) $value;
                        return implode(', ', array_map(function ($item) {
                            return is_array($item) ? json_encode($item) : (string) $item;
                        }, $value));
                    };
                    
                    $getPrice = function($name) use ($email) {
                        if (!is_array($email->estimate_breakdown)) return '';
                        foreach($email->estimate_breakdown as $item) {
                            if($item['name'] == $name) return $item['price'];
                        }
                        return '';
                    };

                    $calcSubtotal = 0;
                    if(is_array($email->estimate_breakdown)) {
                        foreach($email->estimate_breakdown as $item) {
                            $calcSubtotal += (float)$item['price'];
                        }
                    }
                    $calcQty = (float)($email->quantity ?: 1);
                    $calcGrossTotal = $calcSubtotal * $calcQty;
                    $calcWastePct = (float)($email->waste_material_percentage ?: 0);
                    $estimateOptions = is_array($email->estimate_quantity_options) ? array_values($email->estimate_quantity_options) : [];
                    $defaultOptionPrices = [
                        ['quantity' => (int)($email->quantity ?: 0), 'price' => (float)($email->estimated_price ?: 0)],
                        ['quantity' => '', 'price' => ''],
                        ['quantity' => '', 'price' => ''],
                    ];
                    for ($i = 0; $i < 3; $i++) {
                        if (isset($estimateOptions[$i])) {
                            $defaultOptionPrices[$i] = [
                                'quantity' => $estimateOptions[$i]['quantity'] ?? '',
                                'price' => $estimateOptions[$i]['price'] ?? '',
                            ];
                        }
                    }
                @endphp

                <div id="specsDisplayArea">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); overflow: hidden;">
                        
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; width: 45%;">Specification</th>
                                    <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; width: 40%;">Details</th>
                                    @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <th style="padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; width: 15%; text-align: right;">Cost</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Core Specs -->
                                <tr style="border-bottom: 1px solid #f1f5f9; background: #ffffff;">
                                    <td style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                            <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">Dimensions (L x W x H)</span>
                                            {!! (in_array('length', $changedSpecs) || in_array('width', $changedSpecs) || in_array('height', $changedSpecs)) ? $editedBadge : '' !!}
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #0f172a; vertical-align: top;">{{ $email->length ?: '-' }} x {{ $email->width ?: '-' }} x {{ $email->height ?: '-' }}</td>
                                    @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <td style="padding: 1.2rem 1.5rem; text-align: right; vertical-align: top;">
                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                            <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                            <input type="hidden" name="breakdown_names[]" value="Dimensions" form="estimateForm">
                                            <input type="number" step="0.01" name="breakdown_prices[]" form="estimateForm" value="{{ $getPrice('Dimensions') }}" placeholder="0.00" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; font-weight: 600; outline: none; text-align: right; background: transparent;">
                                        </div>
                                    </td>
                                    @endif
                                </tr>

                                <tr style="border-bottom: 1px solid #f1f5f9; background: #fafaf9;">
                                    <td style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                            <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">Stock Material</span>
                                            {!! in_array('stock', $changedSpecs) ? $editedBadge : '' !!}
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #0f172a; vertical-align: top;">{{ $email->stock ?: 'Standard' }}</td>
                                    @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <td style="padding: 1.2rem 1.5rem; text-align: right; vertical-align: top;">
                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                            <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                            <input type="hidden" name="breakdown_names[]" value="Stock Material" form="estimateForm">
                                            <input type="number" step="0.01" name="breakdown_prices[]" form="estimateForm" value="{{ $getPrice('Stock Material') }}" placeholder="0.00" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; font-weight: 600; outline: none; text-align: right; background: transparent;">
                                        </div>
                                    </td>
                                    @endif
                                </tr>

                                <tr style="border-bottom: 1px solid #f1f5f9; background: #ffffff;">
                                    <td style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                            <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">Color Profile</span>
                                            {!! in_array('color', $changedSpecs) ? $editedBadge : '' !!}
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #0f172a; vertical-align: top;">{{ $email->color ?: 'No Color Info' }}</td>
                                    @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <td style="padding: 1.2rem 1.5rem; text-align: right; vertical-align: top;">
                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                            <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                            <input type="hidden" name="breakdown_names[]" value="Color Profile" form="estimateForm">
                                            <input type="number" step="0.01" name="breakdown_prices[]" form="estimateForm" value="{{ $getPrice('Color Profile') }}" placeholder="0.00" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; font-weight: 600; outline: none; text-align: right; background: transparent;">
                                        </div>
                                    </td>
                                    @endif
                                </tr>

                                <tr style="border-bottom: 1px solid #f1f5f9; background: #fafaf9;">
                                    <td style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                            <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">Coating & Finish</span>
                                            {!! in_array('coating', $changedSpecs) ? $editedBadge : '' !!}
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #0f172a; vertical-align: top;">{{ $email->coating ?: 'No Coating Info' }}</td>
                                    @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <td style="padding: 1.2rem 1.5rem; text-align: right; vertical-align: top;">
                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                            <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                            <input type="hidden" name="breakdown_names[]" value="Coating & Finish" form="estimateForm">
                                            <input type="number" step="0.01" name="breakdown_prices[]" form="estimateForm" value="{{ $getPrice('Coating & Finish') }}" placeholder="0.00" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; font-weight: 600; outline: none; text-align: right; background: transparent;">
                                        </div>
                                    </td>
                                    @endif
                                </tr>

                                <tr style="border-bottom: 1px solid #f1f5f9; background: #ffffff;">
                                    <td style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                            <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">Lamination</span>
                                            {!! in_array('lamination', $changedSpecs) ? $editedBadge : '' !!}
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #0f172a; vertical-align: top;">{{ $email->lamination ?: '-' }}</td>
                                    @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <td style="padding: 1.2rem 1.5rem; text-align: right; vertical-align: top;">
                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                            <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                            <input type="hidden" name="breakdown_names[]" value="Lamination" form="estimateForm">
                                            <input type="number" step="0.01" name="breakdown_prices[]" form="estimateForm" value="{{ $getPrice('Lamination') }}" placeholder="0.00" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; font-weight: 600; outline: none; text-align: right; background: transparent;">
                                        </div>
                                    </td>
                                    @endif
                                </tr>

                                <tr style="border-bottom: 1px solid #f1f5f9; background: #fafaf9;">
                                    <td style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                            <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">Die Cutting</span>
                                            {!! in_array('die', $changedSpecs) ? $editedBadge : '' !!}
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #0f172a; vertical-align: top;">{{ $email->die ?: '-' }}</td>
                                    @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <td style="padding: 1.2rem 1.5rem; text-align: right; vertical-align: top;">
                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                            <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                            <input type="hidden" name="breakdown_names[]" value="Die Cutting" form="estimateForm">
                                            <input type="number" step="0.01" name="breakdown_prices[]" form="estimateForm" value="{{ $getPrice('Die Cutting') }}" placeholder="0.00" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; font-weight: 600; outline: none; text-align: right; background: transparent;">
                                        </div>
                                    </td>
                                    @endif
                                </tr>

                                <tr style="border-bottom: 1px solid #f1f5f9; background: #ffffff;">
                                    <td style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                            <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">Gluing</span>
                                            {!! in_array('glue', $changedSpecs) ? $editedBadge : '' !!}
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #0f172a; vertical-align: top;">{{ $email->glue ?: '-' }}</td>
                                    @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <td style="padding: 1.2rem 1.5rem; text-align: right; vertical-align: top;">
                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                            <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                            <input type="hidden" name="breakdown_names[]" value="Gluing" form="estimateForm">
                                            <input type="number" step="0.01" name="breakdown_prices[]" form="estimateForm" value="{{ $getPrice('Gluing') }}" placeholder="0.00" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; font-weight: 600; outline: none; text-align: right; background: transparent;">
                                        </div>
                                    </td>
                                    @endif
                                </tr>

                                <tr style="border-bottom: 2px solid #e2e8f0; background: #fafaf9;">
                                    <td style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                            <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">Shipping Region</span>
                                            {!! in_array('shipping_region', $changedSpecs) ? $editedBadge : '' !!}
                                        </div>
                                    </td>
                                    <td style="padding: 1.2rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #0f172a; vertical-align: top;">{{ $email->shipping_region ?: '-' }}</td>
                                    @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <td style="padding: 1.2rem 1.5rem; text-align: right; vertical-align: top;">
                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                            <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                            <input type="hidden" name="breakdown_names[]" value="Shipping Region" form="estimateForm">
                                            <input type="number" step="0.01" name="breakdown_prices[]" form="estimateForm" value="{{ $getPrice('Shipping Region') }}" placeholder="0.00" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; font-weight: 600; outline: none; text-align: right; background: transparent;">
                                        </div>
                                    </td>
                                    @endif
                                </tr>

                                @if(is_array($email->custom_specs) && count($email->custom_specs) > 0)
                                    <tr>
                                        <td colspan="{{ (Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved') ? '3' : '2' }}" style="padding: 1.5rem 1.5rem 0.5rem 1.5rem; border-top: 2px solid #e2e8f0; background: #ffffff;">
                                            <h4 style="margin: 0; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;"><i class="fas fa-star" style="margin-right: 6px;"></i> Custom Specifications</h4>
                                        </td>
                                    </tr>
                                    @foreach($email->custom_specs as $key => $value)
                                        @php
                                            $isFinishingOptions = strcasecmp(trim((string) $key), 'Finishing Options') === 0;
                                            $finishingGroups = [];

                                            if ($isFinishingOptions) {
                                                $finishingItems = is_array($value) ? $value : [$value];

                                                foreach ($finishingItems as $finishingItem) {
                                                    if (is_array($finishingItem)) {
                                                        $parent = trim((string) ($finishingItem['parent'] ?? $finishingItem['category'] ?? 'Other'));
                                                        $child = trim((string) ($finishingItem['child'] ?? $finishingItem['option'] ?? $finishingItem['value'] ?? ''));
                                                    } else {
                                                        $parts = preg_split('/\s+[—–-]\s+/u', trim((string) $finishingItem), 2);
                                                        $parent = trim((string) ($parts[0] ?? 'Other'));
                                                        $child = trim((string) ($parts[1] ?? ''));
                                                    }

                                                    if ($parent === '') {
                                                        $parent = 'Other';
                                                    }

                                                    if ($child !== '') {
                                                        $finishingGroups[$parent][] = $child;
                                                    }
                                                }
                                            }
                                        @endphp
                                        <tr style="border-bottom: 1px solid #f1f5f9; background: #ffffff;">
                                            @if($isFinishingOptions && count($finishingGroups) > 0)
                                                <td colspan="2" style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                                        <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">{{ strtoupper($key) }}</span>
                                                        {!! in_array('custom_specs', $changedSpecs) ? $editedBadge : '' !!}
                                                    </div>
                                                    <div style="width: 100%;">
                                                        @foreach($finishingGroups as $parent => $children)
                                                            <div style="display: table; width: 100%; table-layout: fixed; margin-bottom: 8px; font-size: 0.86rem; line-height: 1.45;">
                                                                <strong style="display: table-cell; width: 34%; padding-right: 12px; color: #475569; font-weight: 800; vertical-align: top; overflow-wrap: anywhere;">{{ $parent }}</strong>
                                                                <span style="display: table-cell; color: #0f172a; font-weight: 600; vertical-align: top; overflow-wrap: anywhere;">{{ implode(', ', array_unique($children)) }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            @else
                                                <td style="padding: 1.2rem 1.5rem; vertical-align: top;">
                                                    <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                                        <span style="font-size: 0.85rem; font-weight: 700; color: #475569; line-height: 1.4;">{{ strtoupper($key) }}</span>
                                                        {!! in_array('custom_specs', $changedSpecs) ? $editedBadge : '' !!}
                                                    </div>
                                                </td>
                                                <td style="padding: 1.2rem 1.5rem; font-size: 0.95rem; font-weight: 600; color: #0f172a; vertical-align: top;">{{ $formatSpecValue($value) ?: '-' }}</td>
                                            @endif
                                            @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                            <td style="padding: 1.2rem 1.5rem; text-align: right; vertical-align: top;">
                                                <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                                                    <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                                    <input type="hidden" name="breakdown_names[]" value="{{ $key }}" form="estimateForm">
                                                    <input type="number" step="0.01" name="breakdown_prices[]" form="estimateForm" value="{{ $getPrice($key) }}" placeholder="0.00" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; font-weight: 600; outline: none; text-align: right; background: transparent; color: #0f172a;">
                                                </div>
                                            </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endif

                                @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                                    <!-- Dynamic Additional Costs Rows -->
                                    <tbody id="additionalCostsBody">
                                        @if(is_array($email->estimate_breakdown) && count($email->estimate_breakdown) > 0)
                                            @php
                                                $standardNames = ['Dimensions', 'Stock Material', 'Color Profile', 'Coating & Finish', 'Lamination', 'Die Cutting', 'Gluing', 'Shipping Region'];
                                                if(is_array($email->custom_specs)) {
                                                    $standardNames = array_merge($standardNames, array_keys($email->custom_specs));
                                                }
                                            @endphp
                                            @foreach($email->estimate_breakdown as $item)
                                                @if(!in_array($item['name'], $standardNames))
                                                <tr style="border-bottom: 1px dashed #cbd5e1; background: #f8fafc;">
                                                    <td style="padding: 0.75rem 1.5rem;"><input type="text" name="extra_names[]" form="estimateForm" value="{{ $item['name'] }}" required style="width:100%; padding:0.4rem; border:1px solid #cbd5e1; border-radius:4px; font-size:0.8rem; background:white;"></td>
                                                    <td style="padding: 0.75rem 1.5rem;"><input type="text" name="extra_details[]" form="estimateForm" value="{{ $item['detail'] ?? '' }}" placeholder="Detail (Optional)" style="width:100%; padding:0.4rem; border:1px solid #cbd5e1; border-radius:4px; font-size:0.8rem; background:white;"></td>
                                                    <td style="padding: 0.75rem 1.5rem; text-align: right;">
                                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 8px;">
                                                            <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                                            <input type="number" step="0.01" name="extra_prices[]" form="estimateForm" value="{{ $item['price'] }}" oninput="calculateTotal()" required style="width: 70px; border: none; font-size: 0.9rem; outline: none; text-align: right; background: transparent;">
                                                            <button type="button" onclick="this.closest('tr').remove(); calculateTotal();" style="color:#ef4444; background:none; border:none; cursor:pointer; margin-left: 6px;"><i class="fas fa-times"></i></button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        @endif
                                    </tbody>
                                @endif
                            </tbody>
                            
                            @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                            <tfoot>
                                <!-- Add row button & Subtotals -->
                                <tr style="border-top: 2px solid #e2e8f0; background: #f8fafc;">
                                    <td rowspan="4" style="padding: 1rem 1.5rem; vertical-align: top;">
                                        <button type="button" onclick="addBreakdownRow()" style="padding: 0.4rem 0.8rem; background: white; color: var(--primary-color); border: 1px solid var(--primary-color); border-radius: 6px; font-size: 0.75rem; font-weight: bold; cursor: pointer;"><i class="fas fa-plus"></i> Add Extra Fee</button>
                                    </td>
                                    <td style="padding: 0.75rem 1.5rem; text-align: right; font-weight: 600; color: #475569; font-size: 0.85rem;">Price Per Unit (without quantity):</td>
                                    <td style="padding: 0.75rem 1.5rem; text-align: right; color: #64748b; font-size: 0.95rem; font-weight: 600;">$<span id="perUnitDisplay">0.00</span></td>
                                </tr>
                                <tr style="background: #f8fafc;">
                                    <td style="padding: 0.75rem 1.5rem; text-align: right; font-weight: 600; color: #475569; font-size: 0.85rem;">Quantity Multiplier:</td>
                                    <td style="padding: 0.75rem 1.5rem; text-align: right; color: #64748b; font-size: 0.95rem; font-weight: 600;">x <span id="quantityDisplay">{{ (float)($email->quantity ?: 1) }}</span></td>
                                </tr>
                                <tr style="background: #f8fafc;">
                                    <td style="padding: 0.75rem 1.5rem; text-align: right; font-weight: 600; color: #475569; font-size: 0.85rem;">Waste Material Percentage:</td>
                                    <td style="padding: 0.75rem 1.5rem; text-align: right;">
                                        <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 8px;">
                                            <input type="number" min="0" step="0.01" name="waste_material_percentage" form="estimateForm" value="{{ $calcWastePct }}" oninput="calculateTotal()" style="width: 70px; border: none; font-size: 0.9rem; outline: none; text-align: right; background: transparent; color: #0f766e; font-weight: bold; margin-right: 4px;">
                                            <span style="color: #94a3b8; font-size: 0.85rem;">%</span>
                                        </div>
                                        <div style="margin-top: 4px; color: #64748b; font-size: 0.75rem;">+$<span id="wasteAmountDisplay">{{ number_format((float)($email->waste_material_amount ?: 0), 2) }}</span></div>
                                    </td>
                                </tr>
                                <tr style="background: linear-gradient(180deg, #eef2ff 0%, #f8fafc 100%); border-top: 1px dashed #cbd5e1;">
                                    <td style="padding: 1.1rem 1.5rem; text-align: right; font-weight: 700; color: #334155; font-size: 0.9rem; letter-spacing: 0.01em;">Total Estimated Price:</td>
                                    <td style="padding: 1.1rem 1.5rem; text-align: right; color: var(--primary-color); font-size: 1.3rem; font-weight: 900;">
                                        <span style="display:inline-flex; align-items:center; gap:0.35rem; background:#ffffff; border:1px solid #c7d2fe; padding:0.45rem 0.85rem; border-radius:999px; box-shadow:0 6px 14px rgba(var(--primary-rgb), 0.08);">
                                            <span style="font-size:0.9rem; color:var(--primary-purple);">$</span><span id="totalDisplay">{{ number_format((float)$email->estimated_price, 2) }}</span>
                                        </span>
                                    </td>
                                </tr>

                                <tr style="background:#ffffff; border-top:1px solid #e2e8f0;">
                                    <td colspan="3" style="padding: 1.25rem 1.5rem;">
                                        <div style="font-size:0.82rem; font-weight:800; color:#334155; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.85rem;">
                                            <i class="fas fa-layer-group" style="margin-right:6px; color:var(--primary-purple);"></i> Quantity Price Options
                                        </div>
                                        <input type="hidden" name="tier_quantities[]" form="estimateForm" id="tierOption1Quantity" value="{{ (int)($email->quantity ?: 1) }}">
                                        <input type="hidden" name="tier_prices[]" form="estimateForm" id="tierOption1Price" value="{{ (float)($email->estimated_price ?: 0) }}">
                                        <div style="overflow-x:auto; border:1px solid #e5e7eb; border-radius:10px; background:#ffffff;">
                                            <table style="width:100%; border-collapse:collapse; min-width:620px;">
                                                <thead>
                                                    <tr style="background:#f9fafb; color:#374151; border-bottom:1px solid #e5e7eb;">
                                                        <th style="padding:0.75rem; text-align:left; font-size:0.82rem; font-weight:800;">QTY</th>
                                                        <th style="padding:0.75rem; text-align:left; font-size:0.82rem; font-weight:800;">Price Per Unit</th>
                                                        <th style="padding:0.75rem; text-align:left; font-size:0.82rem; font-weight:800;">Price</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr style="background:#ffffff; border-bottom:1px solid #e5e7eb;">
                                                        <td style="padding:0.75rem; font-weight:800; color:#334155;">{{ (int)($email->quantity ?: 1) }}</td>
                                                        <td style="padding:0.75rem; font-weight:900; color:#111827;">$<span id="tierOption1UnitDisplay">{{ (int)($email->quantity ?: 1) > 0 ? number_format((float)$email->estimated_price / (int)($email->quantity ?: 1), 2) : '0.00' }}</span></td>
                                                        <td style="padding:0.75rem; font-weight:900; color:#111827;">$<span id="tierOption1Display">{{ number_format((float)$email->estimated_price, 2) }}</span></td>
                                                    </tr>
                                                    @for($i = 1; $i < 3; $i++)
                                                        <tr style="background:{{ $i % 2 ? '#f9fafb' : '#ffffff' }}; border-bottom:1px solid #e5e7eb;">
                                                            <td style="padding:0.6rem;">
                                                                <input type="number" min="1" step="1" name="tier_quantities[]" form="estimateForm" value="{{ $defaultOptionPrices[$i]['quantity'] }}" required oninput="calculateTierPrice(this)" style="width:100%; padding:0.5rem; border:1px solid #d1d5db; border-radius:7px; font-weight:800; background:#ffffff;">
                                                            </td>
                                                            <td style="padding:0.6rem;">
                                                                <div style="display:flex; align-items:center; background:#ffffff; border:1px solid #d1d5db; border-radius:7px; padding:0 0.5rem;">
                                                                    <span style="color:#64748b; font-weight:800;">$</span>
                                                                    <input type="number" min="0" step="0.01" class="tier-unit-input" value="{{ (!empty($defaultOptionPrices[$i]['quantity']) && (float)$defaultOptionPrices[$i]['quantity'] > 0) ? number_format((float)$defaultOptionPrices[$i]['price'] / (float)$defaultOptionPrices[$i]['quantity'], 2, '.', '') : '0.00' }}" required oninput="calculateTierPrice(this)" style="width:100%; padding:0.5rem; border:none; outline:none; font-weight:900; text-align:right; background:transparent;">
                                                                </div>
                                                            </td>
                                                            <td style="padding:0.6rem; font-weight:900; color:#111827;">
                                                                $<span class="tier-total-price">{{ number_format((float)$defaultOptionPrices[$i]['price'], 2) }}</span>
                                                                <input type="hidden" name="tier_prices[]" form="estimateForm" value="{{ $defaultOptionPrices[$i]['price'] }}">
                                                            </td>
                                                        </tr>
                                                    @endfor
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr style="background: #ffffff; border-top: 1px solid #e2e8f0;">
                                    <td colspan="3" style="padding: 1.5rem;">
                                        @if($email->sales_agent_notes)
                                            <div style="margin-bottom: 1rem; padding: 14px 16px; background: linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%); border: 1px solid #bbf7d0; border-left: 5px solid #22c55e; border-radius: 12px; box-shadow: 0 6px 18px rgba(34, 197, 94, 0.08);">
                                                <strong style="display:block; color:#14532d; font-size:0.85rem; margin-bottom:0.35rem;">
                                                    <i class="fas fa-user-tag" style="margin-right:6px;"></i> Sales Agent Request Notes
                                                </strong>
                                                <div style="font-size:0.92rem; color:#064e3b; line-height:1.55;">{!! nl2br(e($email->sales_agent_notes)) !!}</div>
                                            </div>
                                        @endif
                                        <div style="margin-bottom: 1rem; padding: 14px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                                            <label style="display:block; font-size:0.85rem; font-weight:800; margin-bottom:0.5rem; color:#334155;">
                                                <i class="fas fa-comment-dots" style="margin-right: 6px; color:var(--primary-purple);"></i> Estimator Notes (Internal)
                                            </label>
                                            <textarea name="estimator_notes" form="estimateForm" rows="4" placeholder="Add internal notes for the sales team..." style="width:100%; padding: 0.9rem 1rem; border: 1px solid #cbd5e1; border-radius: 10px; font-family: inherit; font-size: 0.9rem; background:#ffffff; color:#0f172a; resize: vertical;">{{ $email->estimator_notes }}</textarea>
                                        </div>
                                        <button type="submit" form="estimateForm" style="width:100%; padding: 0.95rem 1rem; background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple) 100%); color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 800; cursor: pointer; box-shadow: 0 10px 20px rgba(var(--primary-rgb), 0.22); letter-spacing: 0.01em;"><i class="fas fa-paper-plane" style="margin-right: 10px;"></i> Submit Final Estimate to Sales</button>
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div> <!-- End of the white card container -->

                    <script>
                        function addBreakdownRow() {
                            const tbody = document.getElementById('additionalCostsBody');
                            const tr = document.createElement('tr');
                            tr.style.cssText = "border-bottom: 1px dashed #cbd5e1; background: #f8fafc;";
                            tr.innerHTML = `
                                <td style="padding: 0.75rem 1.5rem;"><input type="text" name="extra_names[]" form="estimateForm" placeholder="e.g. Rush Fee" required style="width:100%; padding:0.4rem; border:1px solid #cbd5e1; border-radius:4px; font-size:0.8rem; background:white;"></td>
                                <td style="padding: 0.75rem 1.5rem;"><input type="text" name="extra_details[]" form="estimateForm" placeholder="Detail (Optional)" style="width:100%; padding:0.4rem; border:1px solid #cbd5e1; border-radius:4px; font-size:0.8rem; background:white;"></td>
                                <td style="padding: 0.75rem 1.5rem; text-align: right;">
                                    <div style="display: inline-flex; align-items: center; background: white; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 8px;">
                                        <span style="color: #94a3b8; font-size: 0.85rem; margin-right: 4px;">$</span>
                                        <input type="number" step="0.01" name="extra_prices[]" form="estimateForm" placeholder="0.00" oninput="calculateTotal()" required style="width: 70px; border: none; font-size: 0.9rem; outline: none; text-align: right; background: transparent;">
                                        <button type="button" onclick="this.closest('tr').remove(); calculateTotal();" style="color:#ef4444; background:none; border:none; cursor:pointer; margin-left: 6px;"><i class="fas fa-times"></i></button>
                                    </div>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        }

                        function calculateTotal() {
                            const standardPrices = document.querySelectorAll('input[name="breakdown_prices[]"]');
                            const extraPrices = document.querySelectorAll('input[name="extra_prices[]"]');
                            let subtotal = 0;
                            standardPrices.forEach(p => { subtotal += parseFloat(p.value || 0); });
                            extraPrices.forEach(p => { subtotal += parseFloat(p.value || 0); });
                            
                            document.getElementById('perUnitDisplay').innerText = subtotal.toFixed(2);
                            
                            const quantity = parseFloat(document.getElementById('quantityDisplay').innerText) || 1;
                            const wasteInput = document.querySelector('input[name="waste_material_percentage"]');
                            const wastePct = Math.max(0, parseFloat(wasteInput ? wasteInput.value : 0) || 0);
                            const grossTotal = subtotal * quantity;
                            const wasteAmount = grossTotal * (wastePct / 100);

                            const wasteDisplay = document.getElementById('wasteAmountDisplay');
                            if (wasteDisplay) wasteDisplay.innerText = wasteAmount.toFixed(2);

                            let grandTotal = grossTotal + wasteAmount;
                            if (grandTotal < 0) grandTotal = 0;
                            document.getElementById('totalDisplay').innerText = grandTotal.toFixed(2);

                            const option1Price = document.getElementById('tierOption1Price');
                            const option1Display = document.getElementById('tierOption1Display');
                            const option1Quantity = parseFloat(document.getElementById('tierOption1Quantity')?.value || 0);
                            const option1UnitDisplay = document.getElementById('tierOption1UnitDisplay');
                            if (option1Price) option1Price.value = grandTotal.toFixed(2);
                            if (option1Display) option1Display.innerText = grandTotal.toFixed(2);
                            if (option1UnitDisplay) option1UnitDisplay.innerText = (option1Quantity > 0 ? grandTotal / option1Quantity : 0).toFixed(2);
                        }

                        function calculateTierPrice(input) {
                            const row = input.closest('tr');
                            if (!row) return;
                            const quantity = parseFloat(row.querySelector('input[name="tier_quantities[]"]')?.value || 0);
                            const unitPrice = parseFloat(row.querySelector('.tier-unit-input')?.value || 0);
                            const totalPrice = quantity * unitPrice;
                            const hiddenTotal = row.querySelector('input[name="tier_prices[]"]');
                            const totalDisplay = row.querySelector('.tier-total-price');
                            if (hiddenTotal) hiddenTotal.value = totalPrice.toFixed(2);
                            if (totalDisplay) totalDisplay.innerText = totalPrice.toFixed(2);
                        }
                        
                        document.addEventListener('DOMContentLoaded', calculateTotal);
                    </script>
                </div>

                <div id="specsEditArea" style="display: none;">
                    <form action="{{ route('crm.emails.update_product_specs', $email->id) }}" method="POST">
                        {{ csrf_field() }}
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div>
                                <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Quantity</label>
                                <input type="number" name="quantity" value="{{ $email->quantity }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Unit</label>
                                <input type="text" name="unit" value="{{ $email->unit }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                        </div>

                        <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Dimensions (L x W x H)</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input type="number" step="0.01" name="length" value="{{ $email->length }}" placeholder="L" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <input type="number" step="0.01" name="width" value="{{ $email->width }}" placeholder="W" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            <input type="number" step="0.01" name="height" value="{{ $email->height }}" placeholder="H" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>

                        <div style="margin-bottom: 0.5rem;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Stock Material</label>
                            <input type="text" name="stock" value="{{ $email->stock }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div>
                                <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Colors</label>
                                <input type="text" name="color" value="{{ $email->color }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Coating</label>
                                <input type="text" name="coating" value="{{ $email->coating }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div>
                                <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Lamination</label>
                                <input type="text" name="lamination" value="{{ $email->lamination }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Die</label>
                                <input type="text" name="die" value="{{ $email->die }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem;">
                            <div>
                                <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Glue</label>
                                <input type="text" name="glue" value="{{ $email->glue }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Shipping</label>
                                <input type="text" name="shipping_region" value="{{ $email->shipping_region }}" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                        </div>

                        <!-- Dynamic Custom Specs Section -->
                        <div id="customSpecsContainer" style="margin-bottom: 1rem; padding-top: 0.5rem; border-top: 1px dashed #cbd5e1;">
                            <label style="font-size: 0.75rem; font-weight: 600; color: #64748b;">Custom Specifications</label>
                            
                            <div id="customSpecsList">
                                @if(is_array($email->custom_specs) && count($email->custom_specs) > 0)
                                    @foreach($email->custom_specs as $key => $value)
                                        <div class="custom-spec-row" style="display: flex; gap: 0.5rem; margin-top: 0.5rem; align-items: center;">
                                            @if(is_array($value))
                                                <div style="width:45%;padding:.4rem;font-weight:700;color:#475569;">{{ $key }}</div>
                                                <div style="width:55%;padding:.4rem;color:#64748b;">{{ $formatSpecValue($value) }}</div>
                                            @else
                                                <input type="text" name="custom_spec_keys[]" value="{{ $key }}" placeholder="Heading (e.g. Foil)" style="width: 45%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                                                <input type="text" name="custom_spec_values[]" value="{{ $value }}" placeholder="Value (e.g. Gold)" style="width: 45%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                                                <button type="button" onclick="this.parentElement.remove()" style="width: 10%; background: #fee2e2; color: #ef4444; border: none; border-radius: 6px; padding: 0.4rem; cursor: pointer;"><i class="fas fa-trash"></i></button>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <button type="button" onclick="addCustomSpecRow()" style="margin-top: 0.5rem; width: 100%; padding: 0.4rem; background: #f8fafc; color: var(--primary-purple); border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 0.75rem; font-weight: 600; cursor: pointer;">
                                <i class="fas fa-plus"></i> Add Custom Field
                            </button>
                        </div>

                        <script>
                            function addCustomSpecRow() {
                                const container = document.getElementById('customSpecsList');
                                const row = document.createElement('div');
                                row.className = 'custom-spec-row';
                                row.style.cssText = 'display: flex; gap: 0.5rem; margin-top: 0.5rem; align-items: center;';
                                row.innerHTML = `
                                    <input type="text" name="custom_spec_keys[]" placeholder="Heading (e.g. Foil)" style="width: 45%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                                    <input type="text" name="custom_spec_values[]" placeholder="Value (e.g. Gold)" style="width: 45%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                                    <button type="button" onclick="this.parentElement.remove()" style="width: 10%; background: #fee2e2; color: #ef4444; border: none; border-radius: 6px; padding: 0.4rem; cursor: pointer;"><i class="fas fa-trash"></i></button>
                                `;
                                container.appendChild(row);
                            }
                        </script>

                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="button" onclick="document.getElementById('specsDisplayArea').style.display='block'; document.getElementById('specsEditArea').style.display='none';" style="padding: 0.5rem 1rem; background: white; border: 1px solid #cbd5e1; border-radius: 6px; color: #64748b; cursor: pointer;">Cancel</button>
                            <button type="submit" style="padding: 0.5rem 1rem; background: var(--primary-purple); border: none; border-radius: 6px; color: white; cursor: pointer; font-weight: 600;">Save Specs</button>
                        </div>
                    </form>
                </div>
            </div>
