@extends('crm.layout')

@section('title', 'Spam')

@section('content')
<style>
    .content-card {
        background: var(--card-bg);
        border-radius: var(--border-radius-base);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: none;
        overflow: hidden;
        margin-bottom: 2rem;
    }
    .filter-card {
        background: var(--card-bg);
        border-radius: var(--border-radius-base);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: none;
    }

    .table-responsive { overflow-x: auto; }
    .table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.9rem; }
    .table th {
        background: #f8fafc;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }
    .table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .table tr:hover td { background: #f8fafc; }
    .table tr:last-child td { border-bottom: none; }

    .btn {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    .btn-light { background: white; border: 1px solid #cbd5e1; color: #475569; }
    .btn-light:hover { background: #f1f5f9; }
    .btn-active { background: #e0e7ff; color: var(--primary-purple); border: 1px solid #c7d2fe; }
    .btn-primary { background: var(--primary-purple); color: white; border-color: var(--primary-purple); }
    .btn-primary:hover { background: var(--primary-hover); }

    input, select {
        font-size: 0.9rem !important; 
        padding: 0.6rem !important; 
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        outline: none;
        background: white;
    }
    
    .badge-spam {
        background: #fee2e2; color: #ef4444; 
        padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600;
    }
</style>

<div class="filter-card">
    <form action="{{ route('crm.emails.spam') }}" method="GET" style="display: flex; gap: 1.5rem; align-items: end; flex-wrap: wrap;">
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <label style="font-size: 0.85rem; font-weight: 600; color: #475569;">Date Range</label>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('crm.emails.spam', array_merge(request()->all(), ['date_filter' => 'today'])) }}" class="btn {{ request('date_filter') == 'today' ? 'btn-active' : 'btn-light' }}">Today</a>
                <a href="{{ route('crm.emails.spam', array_merge(request()->all(), ['date_filter' => 'yesterday'])) }}" class="btn {{ request('date_filter') == 'yesterday' ? 'btn-active' : 'btn-light' }}">Yesterday</a>
                <a href="{{ route('crm.emails.spam', array_merge(request()->all(), ['date_filter' => 'this_week'])) }}" class="btn {{ request('date_filter') == 'this_week' ? 'btn-active' : 'btn-light' }}">Week</a>
            </div>
        </div>

         <div style="margin-bottom: 2px;">
             <a href="{{ route('crm.emails.spam') }}" class="btn btn-light" style="height: 35px;">Clear Filters</a>
        </div>
    </form>
</div>

<div class="content-card" style="padding: 0;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Subject</th>
                    <th>Client</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($emails as $index => $email)
                <tr>
                    <td style="color:#94a3b8; font-weight:500;">
                        {{ $emails->firstItem() + $index }}
                    </td>
                    <td style="font-weight: 600; color: #1e293b;">
                        {{ Str::limit($email->subject, 35) ?: 'No Subject' }}
                    </td>
                    <td>
                        <div style="font-weight: 500;">{{ $email->client_name }}</div>
                        <div style="font-size: 0.8rem; color: #64748b;">{{ $email->client_email }}</div>
                    </td>
                    <td><span class="badge-spam">{{ Str::limit($email->spam_reason, 20) ?: 'Detected' }}</span></td>
                    <td style="font-size: 0.85rem; color: #64748b;">{{ $email->created_at->format('M d, H:i') }}</td>
                    <td>
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                            <a href="{{ route('crm.emails.show', $email->id) }}" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 4rem; color: #94a3b8;">
                         <i class="fas fa-shield-alt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></i>
                        <p>No spam detected.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding: 1.5rem; border-top: 1px solid #f1f5f9;">
        {{ $emails->links() }}
    </div>
</div>
@endsection
