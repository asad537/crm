<?php

namespace App\Http\Controllers\Crm;

use App\CrmWorkspace;
use App\Http\Controllers\Controller;
use App\Support\CrmWorkspaceContext;
use Illuminate\Support\Facades\Auth;

class WorkspaceController extends Controller
{
    public function index()
    {
        return redirect()->route('crm.dashboard');
    }

    public function select(CrmWorkspace $workspace)
    {
        $user = Auth::guard('crm')->user();
        abort_unless($user->isAdmin(), 403, 'Only an Owner or Administrator can switch CRM projects.');
        $allowed = $user->workspaces()->where('crm_workspaces.id', $workspace->id)
            ->where('crm_workspaces.is_active', true)->exists();
        abort_unless($allowed, 403, 'You do not have access to this CRM.');

        session(['crm_workspace_id' => $workspace->id]);
        CrmWorkspaceContext::set($workspace->id);

        return redirect()->route('crm.dashboard');
    }
}
