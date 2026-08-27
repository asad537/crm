<?php
namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Gzip-compresses text responses (HTML/JSON/CSS/JS/XML/SVG) when the client
 * advertises gzip support. Cuts payload size ~60-75%, which is the single biggest
 * win for real users on the internet. Runs after MinifyHtml so it compresses the
 * already-minified body.
 *
 * Deliberately skipped for:
 *  - Streamed / binary / download responses (PDFs, Excel, file downloads)
 *  - Responses already carrying a Content-Encoding
 *  - Tiny bodies (< 1 KB) where gzip overhead isn't worth it
 *  - Requests whose Accept-Encoding does not include gzip
 */
class CompressResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Client must accept gzip.
        $accept = strtolower((string) $request->headers->get('Accept-Encoding', ''));
        if (strpos($accept, 'gzip') === false || !function_exists('gzencode')) {
            return $response;
        }

        // Never touch streamed/binary/download responses.
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return $response;
        }
        if (!method_exists($response, 'getContent') || !method_exists($response, 'setContent')) {
            return $response;
        }

        // Skip if something already encoded the body.
        if ($response->headers->get('Content-Encoding')) {
            return $response;
        }
        // Skip attachments (downloads) — they are served as-is.
        $disposition = strtolower((string) $response->headers->get('Content-Disposition', ''));
        if (strpos($disposition, 'attachment') !== false) {
            return $response;
        }

        // Only compress text-based content types.
        $type = strtolower((string) $response->headers->get('Content-Type', 'text/html'));
        $compressible = ['text/html', 'text/plain', 'text/css', 'text/xml',
            'application/json', 'application/javascript', 'text/javascript',
            'application/xml', 'image/svg+xml'];
        $isCompressible = false;
        foreach ($compressible as $ct) {
            if (strpos($type, $ct) !== false) { $isCompressible = true; break; }
        }
        if (!$isCompressible) return $response;

        $content = $response->getContent();
        if (!is_string($content) || strlen($content) < 1024) {
            return $response; // too small to bother
        }

        $encoded = gzencode($content, 6); // level 6 = good ratio/CPU balance
        if ($encoded === false) return $response;

        $response->setContent($encoded);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding', false);
        $response->headers->set('Content-Length', (string) strlen($encoded));

        return $response;
    }
}
