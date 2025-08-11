<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom authentication middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'company' => \App\Http\Middleware\CompanyMiddleware::class,
            'remember' => \App\Http\Middleware\RememberTokenMiddleware::class,
            'tenant' => \App\Http\Middleware\EnforceTenantBoundaries::class,
            'super-admin' => \App\Http\Middleware\RequireSuperAdmin::class,
        ]);

        // Add remember token middleware to web group
        $middleware->web(append: [
            \App\Http\Middleware\RememberTokenMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
