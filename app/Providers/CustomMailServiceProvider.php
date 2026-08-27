<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\TransportManager;

class CustomMailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // (L10) SwiftMailer was removed in favour of Symfony Mailer, which manages
        // its own transport timeout/stream options. The old 'swift.transport'
        // resolving hook no longer applies, so this boot method is intentionally empty.
    }
}
