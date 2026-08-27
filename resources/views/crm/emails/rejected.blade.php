@extends('crm.layout')

@section('title', 'Rejected Leads')

@section('content')
<div style="background:#fff; border-radius:14px; padding:1.5rem; box-shadow:0 4px 12px rgba(15,23,42,.06);">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1.25rem; flex-wrap:wrap;">
        <h2 style="margin:0; color:#1e293b;">Rejected Leads</h2>
        <form action="{{ route('crm.emails.rejected') }}" method="GET" style="display:flex; gap:.5rem;">
            <select name="date_filter" onchange="this.form.submit()" style="padding:.6rem; border:1px solid #cbd5e1; border-radius:8px;">
                <option value="">All Dates</option>
                <option value="today" {{ request('date_filter') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ request('date_filter') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ request('date_filter') === 'this_week' ? 'selected' : '' }}>This Week</option>
            </select>
        </form>
    </div>
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr style="background:#f8fafc; color:#64748b; text-align:left;"><th style="padding:1rem;">#</th><th style="padding:1rem;">Subject</th><th style="padding:1rem;">Client</th><th style="padding:1rem;">Rejection Note</th><th style="padding:1rem;">Date</th><th style="padding:1rem; text-align:right;">Actions</th></tr></thead>
            <tbody>
            @forelse($emails as $index => $email)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:1rem; color:#94a3b8;">{{ $emails->firstItem() + $index }}</td>
                    <td style="padding:1rem; font-weight:700;">{{ Str::limit($email->subject, 35) ?: 'No Subject' }}</td>
                    <td style="padding:1rem;"><div>{{ $email->client_name ?: 'Unknown' }}</div><small style="color:#64748b;">{{ $email->client_email }}</small></td>
                    <td style="padding:1rem; max-width:320px;"><div style="color:#9a3412; background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:.55rem .7rem; font-size:.84rem; line-height:1.4;">{{ $email->rejection_note ?: 'No note provided' }}</div></td>
                    <td style="padding:1rem; color:#64748b;">{{ ($email->rejected_at ?: $email->created_at)->format('M d, Y H:i') }}</td>
                    <td style="padding:1rem;"><div style="display:flex; justify-content:flex-end; gap:.5rem;"><a href="{{ route('crm.emails.show', $email->id) }}" style="padding:.45rem .8rem; color:#fff; background:var(--primary-purple); border-radius:7px; text-decoration:none;">View</a><form action="{{ route('crm.emails.restoreRejected', $email->id) }}" method="POST">{{ csrf_field() }}<button type="submit" style="padding:.45rem .8rem; color:#475569; background:#fff; border:1px solid #cbd5e1; border-radius:7px; cursor:pointer;">Restore</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:4rem; text-align:center; color:#94a3b8;"><i class="fas fa-check-circle" style="font-size:2.5rem; margin-bottom:1rem; display:block;"></i>No rejected leads.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding-top:1.25rem;">{{ $emails->appends(request()->query())->links() }}</div>
</div>
@endsection
