<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Register domain routes using the Domain Route Manager
            $routeManager = app(\App\Services\DomainRouteManager::class);
            $routeManager->registerDomainRoutes();
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust load balancer proxies for cluster deployment
        $middleware->trustProxies(
            at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '127.0.0.1'],
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // Register custom authentication middleware
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'company' => \App\Http\Middleware\CompanyMiddleware::class,
            'remember' => \App\Http\Middleware\RememberTokenMiddleware::class,
            'tenant' => \App\Http\Middleware\EnforceTenantBoundaries::class,
            'super-admin' => \App\Http\Middleware\RequireSuperAdmin::class,
            'bouncer-scope' => \App\Http\Middleware\SetBouncerScope::class,
            'configure-mail' => \App\Http\Middleware\ConfigureCompanyMail::class,
            'subsidiary.access' => \App\Http\Middleware\SubsidiaryAccessMiddleware::class,
            'setup-wizard' => \App\Http\Middleware\SetupWizardMiddleware::class,
            'platform-company' => \App\Http\Middleware\PlatformCompanyMiddleware::class,
            'auto-verify-email' => \App\Http\Middleware\AutoVerifyEmailWithoutSMTP::class,
        ]);

        // Add middleware to web group
        $middleware->web(append: [
            \App\Http\Middleware\RememberTokenMiddleware::class,
            \App\Http\Middleware\SetBouncerScope::class, // Ensure Bouncer scope is set
            \App\Http\Middleware\ConfigureCompanyMail::class, // Configure mail for company
            \App\Http\Middleware\AutoVerifyEmailWithoutSMTP::class, // Auto-verify emails when SMTP not configured
            \App\Http\Middleware\SetupWizardMiddleware::class, // Check if setup is needed last
        ]);

        // Force HTTPS in production environment
        if (($_ENV['APP_ENV'] ?? '') === 'production') {
            $middleware->web(prepend: [
                \App\Http\Middleware\ForceHttpsMiddleware::class.':all',
            ]);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
