<?php

namespace App\Http\Middleware;

use Closure;

class CheckIndexPhp
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
        // For local development like artisan serve, we check the raw URI
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($uri, 'index.php') !== false) {
            // Check if it's not a direct request to the 404 page itself to avoid loops
            if (strpos($uri, '404.php') === false) {
                return redirect('/404.php/');
            }
        }

        return $next($request);
    }
}
