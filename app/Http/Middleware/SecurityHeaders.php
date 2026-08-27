<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    /**
     * Add a safe baseline of browser security headers to every HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (!method_exists($response, 'header')) {
            return $response;
        }

        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'SAMEORIGIN');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->header(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), usb=(), payment=(self)'
        );
        $response->header('X-Permitted-Cross-Domain-Policies', 'none');
        $response->header('X-Download-Options', 'noopen');

        // HSTS must only be emitted by the production site over HTTPS.
        if (app()->environment('production') && $request->isSecure()) {
            $response->header(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
