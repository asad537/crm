<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use DB;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Pagination\Paginator::useBootstrap();
        if(config('app.env') === 'production' || strpos(config('app.url'), 'https://') !== false) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        // Global view data — shared once per request (was View::composer('*') which ran per view).
        // Wrapped in try/catch so app can boot before migrations run (fresh DBs, CI).
        $whatsappNumber = null;
        try {
            // Cached as a string ('' = no number): a null result is never stored by Cache::remember, which
            // made this run a schema check + a query on every single request.
            $whatsappNumber = Cache::remember('public_site_settings:whatsapp:v2', 600, function () {
                try {
                    $row = DB::table('site_settings')->first();
                    return (string) ($row->whatsapp_number ?? '');
                } catch (\Throwable $e) {
                    return '';
                }
            }) ?: null;
        } catch (\Throwable $e) {
            // silently ignore — DB not ready yet
        }
        View::share('cartCount', 0);
        View::share('whatsappNumber', $whatsappNumber);
    }
}
