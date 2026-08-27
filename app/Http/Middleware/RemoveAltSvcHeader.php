<?php

namespace App\Http\Middleware;

use Closure;

class RemoveAltSvcHeader
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
        $response = $next($request);

        // Ensure Alt-Svc header is not sent to avoid HTTP/3 (QUIC) issues in local dev
        if (method_exists($response, 'header')) {
            $response->header('Alt-Svc', '');
        }

        return $response;
    }
}
