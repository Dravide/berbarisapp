<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'role:Admin'])
                ->prefix('admin')
                ->group(base_path('routes/admin.php'));

            Route::middleware(['web', 'auth', 'role:Eventner'])
                ->prefix('eventner')
                ->group(base_path('routes/eventner.php'));

            Route::middleware(['web', 'subdomain'])
                ->domain('{subdomain}.' . parse_url(config('app.url'), PHP_URL_HOST))
                ->group(base_path('routes/subdomain.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhook/autogopay',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'subdomain' => \App\Http\Middleware\ResolveEventnerSubdomain::class,
        ]);
    })
    ->withExceptions()
    ->create();
