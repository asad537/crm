<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            if ($request->is('crm/*') || $request->is('crm')) {
                return route('crm.login');
            }
            // If there are other login routes, add them here. 
            // For now, defaulting to crm.login or handling generic login if it existed.
            // Since 'login' route does not exist, we check if generic login is needed. 
            // If strictly CRM app, return crm.login.
            return route('crm.login');
        }
    }
}
