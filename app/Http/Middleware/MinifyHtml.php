<?php
namespace App\Http\Middleware;
use Closure;

/**
 * HTML minifier — trims whitespace, drops HTML comments (except IE conditionals)
 * from the final response body. Runs only when APP_DEBUG=false so dev output stays
 * readable. Skips JSON, streamed and non-2xx responses.
 */
class MinifyHtml
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if (config('app.debug')) return $response;
        if (!method_exists($response, 'getContent') || !method_exists($response, 'setContent')) return $response;

        $type = strtolower((string) $response->headers->get('content-type', ''));
        if ($type !== '' && strpos($type, 'text/html') === false) return $response;
        if ($response->getStatusCode() >= 300) return $response;

        $html = $response->getContent();
        if (!is_string($html) || $html === '') return $response;

        // Protect <pre>, <textarea>, <script>, <style> content from whitespace collapse.
        $placeholders = [];
        $html = preg_replace_callback('#<(pre|textarea|script|style)\b[^>]*>.*?</\1>#si', function ($m) use (&$placeholders) {
            $key = "\x1FPRESERVE".count($placeholders)."\x1F";
            $placeholders[$key] = $m[0];
            return $key;
        }, $html);

        // Strip HTML comments (keep IE conditional comments).
        $html = preg_replace('/<!--(?!\[if|<!)[\s\S]*?-->/', '', $html);
        // Collapse whitespace between tags.
        $html = preg_replace('/>\s+</', '><', $html);
        // Collapse runs of whitespace inside text nodes.
        $html = preg_replace('/[ \t\r\n]{2,}/', ' ', $html);

        // Restore preserved blocks.
        if ($placeholders) $html = strtr($html, $placeholders);

        $response->setContent($html);
        return $response;
    }
}
