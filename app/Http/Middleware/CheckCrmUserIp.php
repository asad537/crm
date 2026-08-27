<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckCrmUserIp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guard('crm')->check()) {
            $user = Auth::guard('crm')->user();

            // If user has an allowed_ip set, check against request IP
            if (!empty($user->allowed_ip) && $request->ip() !== $user->allowed_ip) {
                // Determine if we should log them out or just show 403
                // 403 is better for "access denied"
                Auth::guard('crm')->logout();
                return redirect()->route('crm.login')->with('error', 'Access denied due to invalid IP address ');
            }
        }

        return $next($request);
    }
}
