<?php

namespace App\Http\Middleware;

use App\Support\CrmWorkspaceContext;
use Closure;
use Illuminate\Support\Facades\Auth;

class RequireCrmWorkspace
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('crm')->user();
        $workspaceId = (int) $request->session()->get('crm_workspace_id');
        $workspace = $user->workspaces()->where('crm_workspaces.id', $workspaceId)
            ->where('crm_workspaces.is_active', true)->first();

        if (!$workspace) {
            $workspace = $user->workspaces()->where('crm_workspaces.is_active', true)
                ->orderBy('crm_workspaces.id')->first();
            abort_unless($workspace, 403, 'No active CRM workspace is assigned to this account.');
            $request->session()->put('crm_workspace_id', $workspace->id);
        }

        CrmWorkspaceContext::set($workspace->id);
        $request->attributes->set('crm_workspace', $workspace);
        view()->share('activeCrmWorkspace', $workspace);

        return $next($request);
    }
}
