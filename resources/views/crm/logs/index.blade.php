@extends('crm.layout')

@section('title', 'Status Change Logs')

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

    .btn-primary { 
        background: var(--primary-purple); 
        color: white; 
        border-color: var(--primary-purple); 
        box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3);
    }
    .btn-primary:hover { background: var(--primary-hover); box-shadow: 0 6px 15px var(--primary-shadow); }

    input, select {
        font-size: 0.9rem !important; 
        padding: 0.6rem !important; 
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        outline: none;
        background: white;
    }
    input:focus, select:focus { border-color: var(--primary-purple) !important; ring: 2px solid var(--primary-purple); }
    
    .badge-log {
        display: inline-flex;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
    }
    .badge-log.new { background: #eff6ff; color: #1d4ed8; }
</style>

<div class="filter-card">
    <form action="{{ route('crm.logs.index') }}" method="GET" style="display: flex; gap: 1.5rem; align-items: end; flex-wrap: wrap;">
        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <label style="font-size: 0.85rem; font-weight: 600; color: #475569;">Date Range</label>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('crm.logs.index', array_merge(request()->all(), ['date_filter' => 'today'])) }}" class="btn {{ request('date_filter') == 'today' ? 'btn-active' : 'btn-light' }}">Today</a>
                <a href="{{ route('crm.logs.index', array_merge(request()->all(), ['date_filter' => 'yesterday'])) }}" class="btn {{ request('date_filter') == 'yesterday' ? 'btn-active' : 'btn-light' }}">Yesterday</a>
                <a href="{{ route('crm.logs.index', array_merge(request()->all(), ['date_filter' => 'this_week'])) }}" class="btn {{ request('date_filter') == 'this_week' ? 'btn-active' : 'btn-light' }}">Week</a>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <label style="font-size: 0.85rem; font-weight: 600; color: #475569;">Filter by User</label>
            <select name="user" onchange="this.form.submit()" style="width: 200px;">
                <option value="">All Users</option>
                @foreach($users as $user)
                    <option value="{{ $user->name }}" {{ request('user') == $user->name ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

         <div style="margin-bottom: 2px;">
             <a href="{{ route('crm.logs.index') }}" class="btn btn-light" style="height: 40px;">Clear</a>
        </div>
    </form>
</div>

<div class="content-card" style="padding: 0;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>Email Subject</th>
                    <th>Old Status</th>
                    <th></th>
                    <th>New Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $log)
                @php
                    $labels = ['New'=>'Unread','Viewed'=>'Read','Responded'=>'Replied','Order Done'=>'Completed','Closed'=>'Closed'];
                @endphp
                <tr>
                    <td style="color:#94a3b8; font-weight:500;">
                        {{ $logs->firstItem() + $index }}
                    </td>
                    <td style="font-size: 0.85rem; color: #64748b;">
                        {{ $log->created_at->format('M d, Y') }} <br>
                        <span style="font-size: 0.75rem; opacity: 0.8;">{{ $log->created_at->format('H:i') }}</span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;">{{ $log->user_name }}</div>
                    </td>
                    <td>
                        @if($log->email)
                            <a href="{{ route('crm.emails.show', $log->email->id) }}" style="text-decoration: none; color: var(--primary-purple); font-weight: 500;">
                                {{ Str::limit($log->email->subject, 35) }}
                            </a>
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Deleted Email</span>
                        @endif
                    </td>
                    <td><span class="badge-log">{{ $labels[$log->old_status] ?? $log->old_status }}</span></td>
                    <td style="text-align: center; color: #cbd5e1;"><i class="fas fa-arrow-right"></i></td>
                    <td><span class="badge-log new">{{ $labels[$log->new_status] ?? $log->new_status }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 4rem; color: #94a3b8;">
                         <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></i>
                        <p>No activity logs found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding: 1.5rem; border-top: 1px solid #f1f5f9;">
        {{ $logs->links() }}
    </div>
</div>
@endsection
