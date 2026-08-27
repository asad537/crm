@extends('crm.layout')

@section('title', 'Sample Requests')

@section('content')
<style>
    .samples-card {
        background: var(--card-bg);
        border-radius: var(--border-radius-base);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: none;
        overflow: hidden;
        margin-bottom: 2rem;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.85rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: capitalize;
    }
    .status-processed { background: #eff6ff; color: #3b82f6; }
    .status-produced { background: #f3e8ff; color: #9333ea; }
    .status-shipping { background: #fff7ed; color: #ea580c; }
    .status-delivered { background: #dcfce7; color: #16a34a; }
    .status-cancelled { background: #fee2e2; color: #ef4444; }

    .btn-view {
        background: var(--primary-purple);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.8rem;
    }
</style>

<div class="leads-filter">
    <form action="{{ route('crm.samples.index') }}" method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
        <div style="flex: 2;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email..." style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 10px;">
        </div>
        <div style="flex: 1;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Status</label>
            <select name="status" onchange="this.form.submit()" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 10px; background: white;">
                <option value="">All Statuses</option>
                <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Processed</option>
                <option value="produced" {{ request('status') == 'produced' ? 'selected' : '' }}>Produced</option>
                <option value="shipping" {{ request('status') == 'shipping' ? 'selected' : '' }}>Shipping</option>
                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height: 45px;">Filter</button>
        <a href="{{ route('crm.samples.index') }}" class="btn btn-light" style="height: 45px; display: flex; align-items: center;">Reset</a>
    </form>
</div>

<div class="samples-card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Price Status</th>
                    <th>Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($samples as $sample)
                <tr>
                    <td>#{{ $sample->id }}</td>
                    <td>
                        <div style="font-weight: 600;">{{ $sample->contact_name }}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">{{ $sample->contact_email }}</div>
                    </td>
                    <td>
                        <span style="text-transform: capitalize; font-weight: 500;">
                            {{ $sample->sample_type }}
                        </span>
                    </td>
                    <td>{{ $sample->quantity }}</td>
                    <td>
                        <span class="status-badge status-{{ $sample->status }}">
                            {{ $sample->status }}
                        </span>
                    </td>
                    <td>
                        @if($sample->is_price_provided)
                            <span style="color: #16a34a; font-weight: 600;">${{ number_format($sample->unit_price * $sample->quantity + $sample->delivery_fee, 2) }}</span>
                        @else
                            <span style="color: #94a3b8;">Pending Price</span>
                        @endif
                    </td>
                    <td>{{ $sample->created_at->format('M d, Y') }}</td>
                    <td style="text-align: right;">
                        <a href="{{ route('crm.samples.show', $sample->id) }}" class="btn-view">Manage</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem; color: #94a3b8;">No sample requests found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding: 1rem;">
        {{ $samples->links() }}
    </div>
</div>
@endsection
