<?php

namespace App\Http\Middleware;

use App\CrmWorkspace;
use Closure;

class AuthenticateCrmLeadApi
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();
        $workspace = $token ? CrmWorkspace::where('api_key_hash', hash('sha256', $token))
            ->where('is_active', true)->first() : null;

        if (!$workspace) {
            return response()->json(['success' => false, 'message' => 'Invalid CRM API key.'], 401);
        }

        $request->attributes->set('crm_workspace', $workspace);
        return $next($request);
    }
}
