<?php

namespace App\Http\Middleware;

use Closure;

class RequireCrmWorkspaceSlug
{
    public function handle($request, Closure $next, $slug)
    {
        $workspace = $request->attributes->get('crm_workspace');

        if (!$workspace || $workspace->slug !== $slug) {
            return redirect()->route('crm.dashboard')
                ->with('error', 'This module is not available in the selected CRM workspace.');
        }

        return $next($request);
    }
}
