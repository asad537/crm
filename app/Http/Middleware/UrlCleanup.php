<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class UrlCleanup
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
        // 0. Skip API routes entirely
        if ($request->is('api/*')) {
            return $next($request);
        }

        // Skip CRM admin routes — they use their own query params and don't need URL cleanup
        if ($request->is('crm/*')) {
            return $next($request);
        }

        // Only handle GET requests
        if (!$request->isMethod('get')) {
            return $next($request);
        }

        $uri = $request->getRequestUri();
        $shouldRedirect = false;

        // 1. Remove index.php from URL
        if (Str::contains($uri, '/index.php/')) {
            $uri = str_replace('/index.php/', '/', $uri);
            $shouldRedirect = true;
        } elseif (Str::endsWith($request->url(), '/index.php')) {
            $uri = '/';
            $shouldRedirect = true;
        }

        // 2. Detect broken /?/ or ?/ URLs or trailing ?
        if (Str::contains($uri, '?/') || Str::endsWith($uri, '?')) {
            $uri = str_replace('?/', '', $uri);
            $uri = rtrim($uri, '?');
            $shouldRedirect = true;
        }

        // 3. Whitelist parameters
        $whitelist = ['page', 'search_product', 'term', 'q', 's', 'search', 'firebase_uid', 'status', 'product', 'start_date', 'end_date'];
        $currentParams = $request->query();
        
        foreach ($currentParams as $key => $value) {
            if (!in_array($key, $whitelist) || is_null($value) || $value === '') {
                $shouldRedirect = true;
                break;
            }
        }

        // 4. Check for missing trailing slash or double slashes (Path only)
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Collapse double slashes
        if ($path && Str::contains($path, '//')) {
            $path = preg_replace('#/+#', '/', $path);
            $shouldRedirect = true;
        }

        if ($path && !Str::endsWith($path, '/') && !pathinfo($path, PATHINFO_EXTENSION) && $path !== '/') {
            $path .= '/';
            $shouldRedirect = true;
        }

        if ($shouldRedirect) {
            $uri = $path . (parse_url($uri, PHP_URL_QUERY) ? '?' . parse_url($uri, PHP_URL_QUERY) : '');
            
            // Rebuild params
            $cleanParams = [];
            foreach ($currentParams as $key => $value) {
                if (in_array($key, $whitelist) && !is_null($value) && $value !== '') {
                    $cleanParams[$key] = $value;
                }
            }
            
            $cleanPath = parse_url($uri, PHP_URL_PATH);
            // Forcefully remove index.php from path if it somehow remained
            $cleanPath = str_replace('/index.php', '', $cleanPath);
            
            // Final safety: ensure slash
            if (!Str::endsWith($cleanPath, '/') && !pathinfo($cleanPath, PATHINFO_EXTENSION)) {
                $cleanPath .= '/';
            }

            // Construct URL without using Laravel's url() helper to avoid it adding index.php back
            $finalUrl = $request->getSchemeAndHttpHost() . $cleanPath;
            
            if (!empty($cleanParams)) {
                $finalUrl .= '?' . http_build_query($cleanParams);
            }

            // Avoid loop
            if ($finalUrl === $request->fullUrl()) {
                return $next($request);
            }

            return redirect($finalUrl, 301);
        }

        return $next($request);
    }
}
