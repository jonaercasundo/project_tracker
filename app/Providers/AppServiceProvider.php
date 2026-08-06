<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment(['production', 'local'])) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('http');
        }
    }
}