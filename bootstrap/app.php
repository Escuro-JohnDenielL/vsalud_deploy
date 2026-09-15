<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckPagePermission;
use App\Http\Middleware\CheckAdminRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        // Use the application's CSRF middleware (Laravel 11/12 puts
        // ValidateCsrfToken in the "web" group by default) so that a logout
        // submitted with a stale/expired token is handled gracefully instead of
        // showing a "419 Page Expired" page. See App\Http\Middleware\VerifyCsrfToken.
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $middleware->alias([
            'role.admin'      => CheckAdminRole::class,
            'page.permission' => CheckPagePermission::class,
            'mfa'             => \App\Http\Middleware\CheckMfa::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
