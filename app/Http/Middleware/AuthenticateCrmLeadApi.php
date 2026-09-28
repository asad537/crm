<?php

namespace App\Http\Middleware;

use App\CrmWorkspace;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthenticateCrmLeadApi
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        $workspace = $token ? CrmWorkspace::where('api_key_hash', hash('sha256', $token))
            ->where('is_active', true)->first() : null;

        if (!$workspace && $token && Schema::hasTable('crm_workspace_api_keys')) {
            $workspaceId = DB::table('crm_workspace_api_keys')
                ->where('key_hash', hash('sha256', $token))
                ->where('is_active', true)
                ->value('workspace_id');
            $workspace = $workspaceId ? CrmWorkspace::whereKey($workspaceId)
                ->where('is_active', true)->first() : null;
        }

        if (!$workspace) {
            return response()->json(['success' => false, 'message' => 'Invalid CRM API key.'], 401);
        }

        $request->attributes->set('crm_workspace', $workspace);
        return $next($request);
    }
}
