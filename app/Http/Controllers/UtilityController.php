<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class UtilityController extends Controller
{
    /** Root → CRM login. */
    public function home()
    {
        return redirect()->route('crm.login');
    }

    /** Named brand asset (SVG logo etc.) with strong caching. */
    public function brandAsset($filename)
    {
        $assets = [
            'multisite-crm-logo.svg' => ['image/svg+xml', 'multisite-crm-logo.svg'],
        ];
        abort_unless(isset($assets[$filename]), 404);
        $path = resource_path('branding/'.$assets[$filename][1]);
        abort_unless(is_file($path) && is_readable($path), 404);
        return response()->file($path, [
            'Content-Type'  => $assets[$filename][0],
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /** Cache maintenance endpoints. */
    public function cacheClear() { Artisan::call('cache:clear'); return 'Application cache has been cleared'; }
    public function routeCache() { Artisan::call('route:cache');  return 'Route cache built'; }
    public function configCache(){ Artisan::call('config:cache'); return 'Config cache built'; }
    public function viewClear()  { Artisan::call('view:clear');   return 'View cache has been cleared'; }
}
