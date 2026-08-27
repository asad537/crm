<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Auth;

class CrmAdminOrManager
{
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager())) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }
        return $next($request);
    }
}
