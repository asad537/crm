<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th style="width: 60px;">#</th>
                <th>Client</th>
                @if(!Auth::guard('crm')->user()->isEstimator())
                <th>Contact</th>
                @endif
                <th>Source / Product</th>
                <th>Qty</th>
                <th>Estimate</th>
                <th>Status</th>
                <th>Date</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leads as $index => $lead)
            <tr onclick="window.location='{{ route('crm.emails.show', $lead->id) }}?context=quotes'" style="cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                <td style="color:#6b7280; font-weight:500;">
                    {{ ($leads->currentPage() - 1) * $leads->perPage() + $loop->iteration }}
                </td>
                <td>
                    <div style="font-weight: 600; color: #111827; display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                        {{ $lead->client_name ?: 'Unknown' }}
                        @if($lead->customer_type == 'RC')
                            <span title="Returning Customer" style="background:#eff6ff; color:#3b82f6; font-size: 0.7rem; padding: 1px 6px; border-radius: 4px; border: 1px solid #dbeafe;">R</span>
                        @else
                            <span title="New Customer" style="background:#f0fdf4; color:#16a34a; font-size: 0.7rem; padding: 1px 6px; border-radius: 4px; border: 1px solid #dcfce7;">N</span>
                        @endif
                    </div>
                </td>
                @if(!Auth::guard('crm')->user()->isEstimator())
                <td>
                    <div style="display: flex; flex-direction: column; gap: 0.15rem;">
                        <a href="mailto:{{ $lead->client_email }}" onclick="event.stopPropagation()" style="color: var(--primary-purple); text-decoration: none; font-size: 0.8rem;">
                            {{ Str::limit($lead->client_email, 25) }}
                        </a>
                        @if($lead->client_phone)
                        <a href="tel:{{ $lead->client_phone }}" onclick="event.stopPropagation()" style="color: #6b7280; text-decoration: none; font-size: 0.75rem;">
                            {{ $lead->client_phone }}
                        </a>
                        @endif
                    </div>
                </td>
                @endif
                <td>
                    @php
                        $sourceIcon = 'fa-box';
                        if (stripos($lead->product_name, 'Quote') !== false) $sourceIcon = 'fa-file-invoice';
                        elseif (stripos($lead->product_name, 'Contact') !== false) $sourceIcon = 'fa-paper-plane';
                    @endphp
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: #4b5563; font-size: 0.8rem;">
                        <span style="width: 20px; height: 20px; background: #f3f4f6; border-radius: 3px; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                            <i class="fas {{ $sourceIcon }}" style="font-size: 0.65rem;"></i>
                        </span>
                        {{ Str::limit($lead->product_name, 25) }}
                    </div>
                </td>
                <td style="font-size: 0.9rem; color: #1e293b; font-weight: 600;">{{ $lead->quantity ?? '-' }}</td>
                <td>
                    @php
                        $hasChangeRequest = (bool) $lead->rejectionLog;
                        $estimateStatus = strtolower((string) ($lead->estimate_status ?? 'pending'));
                        $estimateStyles = [
                            'change_request' => ['bg' => '#ffedd5', 'fg' => '#c2410c', 'label' => 'Change Request'],
                            'change_requested' => ['bg' => '#fee2e2', 'fg' => '#991b1b', 'label' => 'Change Requested'],
                            'pending' => ['bg' => '#fef3c7', 'fg' => '#92400e', 'label' => 'Pending'],
                            'estimated' => ['bg' => '#dcfce7', 'fg' => '#166534', 'label' => 'Estimated'],
                            'approved' => ['bg' => '#dbeafe', 'fg' => '#1d4ed8', 'label' => 'Approved'],
                        ];
                        $estimateKey = ($hasChangeRequest || $estimateStatus === 'change_requested')
                            ? 'change_requested'
                            : (array_key_exists($estimateStatus, $estimateStyles) ? $estimateStatus : 'pending');
                        $estimateStyle = $estimateStyles[$estimateKey] ?? $estimateStyles['pending'];
                    @endphp
                    <div style="display:flex; flex-direction:column; gap:0.35rem;">
                        <span style="display:inline-flex; align-items:center; width:max-content; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700; background: {{ $estimateStyle['bg'] }}; color: {{ $estimateStyle['fg'] }}; text-transform: uppercase; letter-spacing: 0.02em;">
                            {{ $estimateStyle['label'] }}
                        </span>
                        <div style="font-size:0.72rem; color:#6b7280; line-height:1.3;">
                            <div><strong style="color:#374151;">Estimator:</strong> {{ $lead->estimator->name ?? 'Not assigned' }}</div>
                            @if($lead->rejectionLog)
                                <div style="margin-top:0.25rem; display:inline-flex; align-items:center; gap:0.35rem; padding:0.2rem 0.45rem; border-radius:999px; background:#fff7ed; color:#c2410c; font-weight:600;">
                                    <i class="fas fa-rotate-left" style="font-size:0.65rem;"></i>
                                    {{ $lead->rejectionLog->status ? ucfirst(str_replace('_', ' ', $lead->rejectionLog->status)) : 'Change requested' }}
                                </div>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    @php
                        $badgeClass = 'status-new';
                        if($lead->status == 'Viewed') $badgeClass = 'status-viewed';
                        elseif($lead->status == 'Client Replied') $badgeClass = 'status-responded';
                        elseif($lead->status == 'Order Done') $badgeClass = 'status-order';
                        elseif($lead->status == 'Closed') $badgeClass = 'status-closed';
                    @endphp
                    @php
                        $labels = ['New'=>'Unread','Viewed'=>'Read','Client Replied'=>'Client Replied','Order Done'=>'Completed','Closed'=>'Closed'];
                    @endphp
                    <span class="status-badge {{ $badgeClass }}">{{ $labels[$lead->status] ?? $lead->status }}</span>
                </td>
                <td style="color: #6b7280; font-size: 0.8rem;">
                    {{ $lead->created_at->format('M d') }}<br>
                    <span style="font-size: 0.7rem;">{{ $lead->created_at->format('H:i') }}</span>
                </td>
                <td style="text-align: right;">
                    <a href="{{ route('crm.emails.show', $lead->id) }}?context=quotes" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                        View
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ Auth::guard('crm')->user()->isEstimator() ? 8 : 9 }}" style="text-align: center; padding: 4rem; color: #9ca3af;">
                    <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></i>
                    <p>No leads found matching your criteria.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div style="padding: 1rem; border-top: 1px solid #e5e7eb;">
    {{ $leads->links() }}
</div>
