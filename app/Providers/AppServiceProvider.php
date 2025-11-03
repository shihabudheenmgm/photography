<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Inertia::setRootView('app');
        
        if (app()->environment('production')) {
            Inertia::version(function () {
                return md5_file(public_path('build/manifest.json'));
            });
        }
    }
}
