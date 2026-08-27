@extends('crm.layout')

@section('title', 'Sample Request Details')

@section('header_actions')
<a href="{{ route('crm.samples.index') }}" class="btn btn-light">
    <i class="fas fa-arrow-left"></i> Back to List
</a>
@endsection

@section('content')
<style>
    .detail-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 1.5rem;
    }
    .info-card {
        background: white;
        border-radius: var(--border-radius-base);
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
    }
    .info-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .info-value {
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        margin-bottom: 1rem;
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .status-badge-lg {
        padding: 0.5rem 1rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
    }
</style>

<div class="detail-grid">
    <div class="left-col">
        <!-- Client & Order Info -->
        <div class="info-card">
            <div class="section-title"><i class="fas fa-info-circle"></i> Request Information</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <div class="info-label">Client Name</div>
                    <div class="info-value">{{ $sample->contact_name }}</div>
                </div>
                <div>
                    <div class="info-label">Sample Type</div>
                    <div class="info-value" style="text-transform: capitalize;">{{ $sample->sample_type }}</div>
                </div>
                <div>
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $sample->contact_email }}</div>
                </div>
                <div>
                    <div class="info-label">Phone</div>
                    <div class="info-value">{{ $sample->contact_phone }}</div>
                </div>
                <div>
                    <div class="info-label">Quantity</div>
                    <div class="info-value">{{ $sample->quantity }}</div>
                </div>
                <div>
                    <div class="info-label">Requested On</div>
                    <div class="info-value">{{ $sample->created_at->format('F d, Y H:i') }}</div>
                </div>
            </div>
            <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 1rem 0;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <div class="info-label">Shipping Address</div>
                    <div class="info-value" style="white-space: pre-line;">{{ $sample->shipping_address }}</div>
                </div>
                <div>
                    <div class="info-label">Billing Address</div>
                    <div class="info-value" style="white-space: pre-line;">{{ $sample->billing_address }}</div>
                </div>
            </div>
        </div>

        <!-- Timeline / Status Update -->
        <div class="info-card">
            <div class="section-title"><i class="fas fa-tasks"></i> Update Status</div>
            <form action="{{ route('crm.samples.update_status', $sample->id) }}" method="POST" style="display: flex; gap: 1rem; align-items: center;">
                @csrf
                @method('PATCH')
                <div style="flex: 1;">
                    <select name="status" class="form-control" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1px solid #cbd5e1; background: white;">
                        <option value="processed" {{ $sample->status == 'processed' ? 'selected' : '' }}>1. Processed (Order Confirmed)</option>
                        <option value="produced" {{ $sample->status == 'produced' ? 'selected' : '' }}>2. Produced (Manufacturing Done)</option>
                        <option value="shipping" {{ $sample->status == 'shipping' ? 'selected' : '' }}>3. Shipping (Tracking Assigned)</option>
                        <option value="out_for_delivery" {{ $sample->status == 'out_for_delivery' ? 'selected' : '' }}>4. Out for Delivery (Local Courier)</option>
                        <option value="delivered" {{ $sample->status == 'delivered' ? 'selected' : '' }}>5. Delivered (Arrival)</option>
                        <option value="cancelled" {{ $sample->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Pipeline</button>
            </form>
        </div>
    </div>

    <div class="right-col">
        <!-- Status Summary -->
        <div class="info-card" style="text-align: center;">
            <div class="info-label">Current Status</div>
            @php
                $statusColors = [
                    'processed' => ['bg' => '#eff6ff', 'text' => '#3b82f6'],
                    'produced' => ['bg' => '#f3e8ff', 'text' => '#9333ea'],
                    'shipping' => ['bg' => '#fff7ed', 'text' => '#ea580c'],
                    'out_for_delivery' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                    'delivered' => ['bg' => '#dcfce7', 'text' => '#16a34a'],
                    'cancelled' => ['bg' => '#fee2e2', 'text' => '#ef4444'],
                ];
                $color = $statusColors[$sample->status] ?? ['bg' => '#f1f5f9', 'text' => '#475569'];
            @endphp
            <div style="margin: 1rem 0;">
                <span class="status-badge-lg" style="background: {{ $color['bg'] }}; color: {{ $color['text'] }};">
                    {{ $sample->status }}
                </span>
            </div>
            <div style="font-size: 0.8rem; color: #64748b;">
                Last updated: {{ $sample->updated_at->diffForHumans() }}
            </div>
        </div>

        <!-- Pricing (The "Bill Summary Card" activator) -->
        <div class="info-card">
            <div class="section-title"><i class="fas fa-file-invoice-dollar"></i> Pricing & Billing</div>
            <form action="{{ route('crm.samples.update_pricing', $sample->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div style="margin-bottom: 1rem;">
                    <label class="info-label">Unit Price ($)</label>
                    <input type="number" step="0.01" name="unit_price" value="{{ $sample->unit_price }}" class="form-control" style="width: 100%;" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label class="info-label">Delivery Fee ($)</label>
                    <input type="number" step="0.01" name="delivery_fee" value="{{ $sample->delivery_fee }}" class="form-control" style="width: 100%;" required>
                </div>
                @if($sample->is_price_provided)
                <div style="margin-bottom: 1.5rem; background: #f0fdf4; padding: 10px; border-radius: 8px; border: 1px solid #dcfce7;">
                    <div style="font-size: 0.8rem; color: #166534; font-weight: 600;">Total Bill (Qty x Rate + Fee)</div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #16a34a;">
                        ${{ number_format($sample->unit_price * $sample->quantity + $sample->delivery_fee, 2) }}
                    </div>
                    <div style="font-size: 0.7rem; color: #15803d; margin-top: 5px;">
                        <i class="fas fa-check-circle"></i> Activated in User's App
                    </div>
                </div>
                @else
                <div style="margin-bottom: 1.5rem; background: #fffcf0; padding: 10px; border-radius: 8px; border: 1px solid #fffae5;">
                    <div style="font-size: 0.75rem; color: #854d0e;">The bill summary card is currently <b>inactive</b> in the user's app. Setting price will activate it.</div>
                </div>
                @endif
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    {{ $sample->is_price_provided ? 'Update Pricing' : 'Set Price & Activate' }}
                </button>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="info-card" style="border: 1px solid #fee2e2;">
            <div class="section-title" style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Danger Zone</div>
            <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 1rem;">Deleting this request is permanent.</p>
            <form action="{{ route('crm.samples.destroy', $sample->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this sample request?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn" style="background: #fee2e2; color: #ef4444; width: 100%; font-weight: 700;">Delete Request</button>
            </form>
        </div>
    </div>
</div>
@endsection
