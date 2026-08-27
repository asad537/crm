@extends('crm.layout')

@section('title', 'Sales Team')

@section('content')
<style>
    .team-shell{max-width:1440px;margin:0 auto}.team-toolbar{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:22px}.team-intro{display:flex;align-items:center;gap:14px}.team-intro-icon{width:46px;height:46px;border-radius:14px;background:var(--primary-soft);color:var(--primary-purple);display:grid;place-items:center;font-size:1.05rem}.team-intro h2{margin:0;color:#172033;font-size:1.15rem;font-weight:800}.team-intro p{margin:4px 0 0;color:#8a97aa;font-size:.8rem}.add-member{display:inline-flex;align-items:center;gap:9px;padding:.72rem 1.05rem;border-radius:11px;background:var(--primary-purple);color:#fff;text-decoration:none;font-size:.8rem;font-weight:800;box-shadow:0 8px 18px var(--primary-shadow);transition:.2s}.add-member:hover{background:var(--primary-hover);transform:translateY(-1px)}
    .team-panel{background:#fff;border:1px solid #e7ebf1;border-radius:18px;box-shadow:0 10px 30px rgba(15,23,42,.05);overflow:hidden}.team-panel-head{display:flex;align-items:center;justify-content:space-between;padding:17px 21px;border-bottom:1px solid #edf0f4}.team-panel-title{font-size:.9rem;font-weight:800;color:#253046}.team-count{display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;background:var(--primary-soft);color:var(--primary-purple);font-size:.7rem;font-weight:800}
    .member-row{display:grid;grid-template-columns:minmax(230px,1.3fr) minmax(200px,1fr) minmax(155px,.65fr) 170px;align-items:center;gap:18px;padding:17px 21px;border-bottom:1px solid #f0f2f6;transition:background .18s}.member-row:last-child{border-bottom:0}.member-row:hover{background:#fbfcfe}.member-profile{display:flex;align-items:center;gap:13px;min-width:0}.member-avatar{width:44px;height:44px;flex:0 0 44px;border-radius:13px;background:linear-gradient(145deg,var(--primary-soft),#fff);border:1px solid color-mix(in srgb,var(--primary-purple) 17%,#fff);color:var(--primary-purple);display:grid;place-items:center;font-size:.95rem;font-weight:900;box-shadow:0 5px 12px rgba(15,23,42,.06)}.member-name{color:#172033;font-size:.88rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.member-state{display:flex;align-items:center;gap:6px;margin-top:4px;color:#94a3b8;font-size:.69rem}.state-dot{width:7px;height:7px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px #dcfce7}.member-email{display:flex;align-items:center;gap:8px;color:#66758c;font-size:.78rem;min-width:0}.member-email span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.member-email i{color:#a2aec0}
    .role-badge{display:inline-flex;width:max-content;align-items:center;gap:6px;padding:6px 10px;border-radius:8px;background:#f5f7fa;color:#526076;font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.045em}.role-badge.super_admin{background:#eef2ff;color:#3730a3}.role-badge.admin{background:#fff0e8;color:#d94714}.role-badge.sales_manager{background:#fdf2f8;color:#9d174d}.role-badge.sales{background:#eef2ff;color:var(--primary-purple)}.role-badge.team_lead{background:#ecfeff;color:#0e7490}.member-actions{display:flex;align-items:center;justify-content:flex-end;gap:7px}.action-link,.action-delete{height:35px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:0 11px;font-size:.72rem;font-weight:800;cursor:pointer;text-decoration:none;transition:.18s}.action-link{background:var(--primary-soft);border:1px solid color-mix(in srgb,var(--primary-purple) 18%,#fff);color:var(--primary-purple)}.action-link:hover{background:var(--primary-purple);color:#fff}.action-delete{width:35px;padding:0;border:1px solid #fecdd3;background:#fff5f6;color:#ef4444}.action-delete:hover{background:#ef4444;color:#fff}.current-pill{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border-radius:8px;background:#ecfdf5;color:#16845b;font-size:.69rem;font-weight:800}
    .team-empty{text-align:center;padding:60px 20px;color:#94a3b8}.team-empty i{font-size:2rem;margin-bottom:12px;color:var(--primary-purple);opacity:.45}.team-pagination{margin-top:18px}
    @media(max-width:1000px){.member-row{grid-template-columns:1.2fr 1fr 150px}.member-actions{grid-column:1/-1;justify-content:flex-start;padding-left:57px}.team-panel{overflow:visible}}@media(max-width:720px){.team-toolbar{align-items:flex-start;flex-direction:column}.add-member{width:100%;justify-content:center}.member-row{grid-template-columns:1fr;gap:10px}.member-actions{grid-column:auto;padding-left:57px;justify-content:flex-start}.member-email,.role-badge{margin-left:57px}}
</style>

@php
    $currentUser = Auth::guard('crm')->user();
@endphp

<div class="team-shell">
    <div class="team-toolbar">
        <div class="team-intro">
            <div class="team-intro-icon"><i class="fas fa-users"></i></div>
            <div>
                <h2>Team directory</h2>
                <p>Manage workspace access, roles and team accounts.</p>
            </div>
        </div>
        <a href="{{ route('crm.users.create') }}" class="add-member"><i class="fas fa-user-plus"></i> Add Team Member</a>
    </div>

    <div class="team-panel">
        <div class="team-panel-head">
            <span class="team-panel-title">Workspace members</span>
            <span class="team-count">{{ $users->total() }} {{ Str::plural('member', $users->total()) }}</span>
        </div>

        @forelse($users as $user)
            @php
                $memberWorkspaceRole = $user->roleForWorkspace(session('crm_workspace_id')) ?: $user->role;
                $memberRoleLabel = $memberWorkspaceRole === 'super_admin' ? 'Owner' : ucwords(str_replace('_', ' ', $memberWorkspaceRole));
                $canManage = $currentUser->isAdmin() || ($currentUser->isSalesManager() && $memberWorkspaceRole === 'sales');
                $isCurrent = $currentUser->id === $user->id;
                $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->map(function($part){ return strtoupper(substr($part,0,1)); })->take(2)->implode('');
            @endphp
            <div class="member-row">
                <div class="member-profile">
                    <div class="member-avatar">{{ $initials }}</div>
                    <div style="min-width:0">
                        <div class="member-name">{{ $user->name }}</div>
                        <div class="member-state"><span class="state-dot"></span> Active account</div>
                    </div>
                </div>
                <div class="member-email"><i class="far fa-envelope"></i><span>{{ $user->email }}</span></div>
                <span class="role-badge {{ $memberWorkspaceRole }}"><i class="fas fa-shield-alt"></i>{{ $memberRoleLabel }}</span>
                <div class="member-actions">
                    @if($isCurrent)
                        <span class="current-pill"><i class="fas fa-check-circle"></i> Current session</span>
                    @elseif($canManage)
                        <a href="{{ route('crm.users.edit', $user->id) }}" class="action-link"><i class="fas fa-pen"></i> Edit</a>
                        <form action="{{ route('crm.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Remove this team member?');" style="margin:0">
                            {{ csrf_field() }}<input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="action-delete" title="Remove member"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="team-empty"><i class="fas fa-user-friends"></i><div>No team members found.</div></div>
        @endforelse
    </div>

    <div class="team-pagination">{{ $users->links() }}</div>
</div>
@endsection
