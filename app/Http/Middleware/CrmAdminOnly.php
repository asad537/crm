<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;

class CrmAdminOnly
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('crm')->check() || !Auth::guard('crm')->user()->isAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }
        return $next($request);
    }
}
