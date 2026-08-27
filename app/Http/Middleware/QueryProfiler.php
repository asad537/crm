<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Query profiler middleware.
 * Enable per-request by adding ?_profile=1 to any URL (only when APP_DEBUG=true).
 * Appends a summary HTML block at the end of the response with total queries,
 * time and top slow queries — perfect for N+1 hunting.
 */
class QueryProfiler
{
    public function handle($request, Closure $next)
    {
        if (!$request->has('_profile') || !config('app.debug')) {
            return $next($request);
        }
        DB::enableQueryLog();
        $start = microtime(true);
        $response = $next($request);
        $ms = round((microtime(true) - $start) * 1000, 1);
        $log = DB::getQueryLog();
        $totalTime = array_sum(array_column($log, 'time'));
        $slow = collect($log)->sortByDesc('time')->take(10);
        $slowHtml = '';
        foreach ($slow as $q) {
            $slowHtml .= '<li><b>'.round($q['time'], 2).'ms</b> — <code style="font-size:.75em">'.htmlspecialchars($q['query']).'</code></li>';
        }
        $bar = '<div style="position:fixed;bottom:0;left:0;right:0;background:#111;color:#fff;padding:10px 16px;font:12px monospace;z-index:99999;max-height:40vh;overflow:auto;box-shadow:0 -4px 16px rgba(0,0,0,.4)">'
            .'<b>🔍 Query Profiler</b> — total: '.count($log).' queries · '.round($totalTime, 1).'ms SQL · '.$ms.'ms request'
            .'<ol style="margin:8px 0 0;padding-left:20px">'.$slowHtml.'</ol></div>';
        $content = $response->getContent();
        if (stripos($content, '</body>') !== false) {
            $response->setContent(str_ireplace('</body>', $bar.'</body>', $content));
        }
        return $response;
    }
}
