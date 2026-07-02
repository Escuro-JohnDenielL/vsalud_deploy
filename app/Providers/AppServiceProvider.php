<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        // Use relative paths for Vite assets so they work on both
        // localhost (HTTP) and tunnel/Dev Tunnels (HTTPS) without
        // mixed content blocking.
        Vite::createAssetPathsUsing(function (string $path, ?bool $secure) {
            return '/' . ltrim($path, '/');
        });
    }
}
