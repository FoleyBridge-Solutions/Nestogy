<?php

namespace App\Providers;

use App\Domains\Core\Services\ConfigurationValidationService;
use App\Domains\Core\Services\NavigationService;
use App\Helpers\ConfigHelper;
use App\Http\ViewComposers\ClientViewComposer;
use App\Http\ViewComposers\NavigationComposer;
use App\Domains\Company\Models\Company;
use App\Observers\CompanyObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Silber\Bouncer\BouncerFacade as Bouncer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the configuration validation service
        $this->app->singleton(ConfigurationValidationService::class, function ($app) {
            return new ConfigurationValidationService;
        });

        // Register the navigation service
        $this->app->singleton(NavigationService::class, function ($app) {
            return new NavigationService;
        });

        // Override PWA service with fixed version (removes script tag for sw.js)
        $this->app->singleton(\EragLaravelPwa\Services\PWAService::class, function ($app) {
            return new \App\Services\PWAService;
        });

        // Register Tax Profile Service with company context
        $this->app->bind(\App\Services\TaxEngine\TaxProfileService::class, function ($app) {
            $service = new \App\Services\TaxEngine\TaxProfileService;
            if (Auth::check() && Auth::user()->company_id) {
                $service->setCompanyId(Auth::user()->company_id);
            }

            return $service;
        });

        // Register Tax Engine Router with company context
        $this->app->bind(\App\Services\TaxEngine\TaxEngineRouter::class, function ($app) {
            $service = new \App\Services\TaxEngine\TaxEngineRouter;
            if (Auth::check() && Auth::user()->company_id) {
                $service->setCompanyId(Auth::user()->company_id);
            }

            return $service;
        });

        // Contract Configuration Registry with company context
        $this->app->bind('contract.config.registry', function () {
            $companyId = Auth::check() && Auth::user()->company_id 
                ? Auth::user()->company_id 
                : 1;
            return new \App\Domains\Contract\Services\ContractConfigurationRegistry($companyId);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Company::observe(CompanyObserver::class);
        \App\Domains\HR\Models\EmployeeTimeEntry::observe(\App\Observers\EmployeeTimeEntryObserver::class);

        // Force HTTPS in production, when behind SSL proxy, or when FORCE_HTTPS is set
        if ($this->app->environment('production') 
            || request()->server('HTTPS') === 'on' 
            || request()->server('HTTP_X_FORWARDED_PROTO') === 'https'
            || env('FORCE_HTTPS', false)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Run configuration validation in non-production environments
        if ($this->app->environment(['local', 'development', 'staging'])) {
            $this->validateConfiguration();
        }

        // Set default timezone from configuration
        if ($timezone = config('nestogy.company.default_timezone')) {
            date_default_timezone_set($timezone);
        }

        // Configure upload directories
        $this->configureUploadDirectories();

        // Configure password validation rules from database
        $this->configurePasswordValidation();

        // Register view composers
        $this->registerViewComposers();

        // Register event listeners
        $this->registerEventListeners();

        // Configure Bouncer
        $this->configureBouncer();

        // Configure parallel testing
        $this->configureParallelTesting();

        // Register Blaze directive for Livewire Flux compatibility
        $this->registerBlazeDirective();
    }

    /**
     * Configure password validation rules based on security settings
     * Uses config-based defaults to avoid database queries on every boot
     */
    protected function configurePasswordValidation(): void
    {
        Password::defaults(function () {
            // Use simple config-based defaults, not database
            // Database-driven password rules can be applied at validation time, not globally
            $minLength = config('security.password.min_length', 12);
            $requireUppercase = config('security.password.require_uppercase', true);
            $requireLowercase = config('security.password.require_lowercase', true);
            $requireNumbers = config('security.password.require_numbers', true);
            $requireSymbols = config('security.password.require_symbols', false);

            $rule = Password::min($minLength);

            if ($requireUppercase || $requireLowercase) {
                if ($requireUppercase && $requireLowercase) {
                    $rule->mixedCase();
                } elseif ($requireUppercase) {
                    $rule->letters()->rules(['regex:/[A-Z]/']);
                } elseif ($requireLowercase) {
                    $rule->letters()->rules(['regex:/[a-z]/']);
                }
            } else {
                $rule->letters();
            }

            if ($requireNumbers) {
                $rule->numbers();
            }

            if ($requireSymbols) {
                $rule->symbols();
            }

            return $rule;
        });
    }

    /**
     * Configure parallel testing
     */
    protected function configureParallelTesting(): void
    {
        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            $host = config('database.connections.pgsql.host');
            $port = config('database.connections.pgsql.port');
            $username = config('database.connections.pgsql.username');
            $password = config('database.connections.pgsql.password');
            
            $dsn = "pgsql:host={$host};port={$port};dbname=postgres";
            
            try {
                $pdo = new \PDO($dsn, $username, $password);
                $pdo->exec("DROP DATABASE IF EXISTS {$database} WITH (FORCE)");
                $pdo->exec("CREATE DATABASE {$database}");
            } catch (\PDOException $e) {
                // Silently fail - database might already exist
            }
            
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--quiet' => true,
            ]);
        });

        // ParallelTesting::setUpTestCase is causing timeouts when tests run in isolation
        // The registry clearing is handled in individual tests that need it
    }

    /**
     * Validate application configuration
     */
    protected function validateConfiguration(): void
    {
        try {
            $validator = $this->app->make(ConfigurationValidationService::class);
            $isValid = $validator->validate();

            if (! $isValid) {
                $errors = $validator->getErrors();
                Log::error('Configuration validation failed during boot', ['errors' => $errors]);

                // In console, show errors
                if ($this->app->runningInConsole()) {
                    foreach ($errors as $error) {
                        $this->app->make('log')->error("Config Error: {$error}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to run configuration validation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Register view composers
     */
    protected function registerViewComposers(): void
    {
        // Inject navigation data into the main app layout
        View::composer('layouts.app', NavigationComposer::class);

        // Inject selected client data into client views
        View::composer('clients.*', ClientViewComposer::class);
    }

    /**
     * Configure upload directories
     */
    protected function configureUploadDirectories(): void
    {
        $uploadPaths = config('uploads.paths', []);

        foreach ($uploadPaths as $key => $path) {
            $fullPath = storage_path('app/'.$path);

            if (! file_exists($fullPath)) {
                try {
                    mkdir($fullPath, 0755, true);
                } catch (\Exception $e) {
                    Log::warning("Failed to create upload directory: {$fullPath}", ['error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        // Asset support evaluation when assets are created
        Event::listen(
            \App\Events\AssetCreated::class,
            \App\Listeners\EvaluateAssetSupportStatus::class
        );

        // Re-evaluate assets when contract schedules are activated
        Event::listen(
            \App\Events\ContractScheduleActivated::class,
            \App\Listeners\ReevaluateAssetsOnScheduleChange::class
        );

        // Service Management Event Listeners
        
        // Service Activation - Create recurring billing
        Event::listen(
            \App\Domains\Client\Events\ServiceActivated::class,
            \App\Domains\Client\Listeners\CreateRecurringBillingForService::class
        );

        // Service Activation - Send notification
        Event::listen(
            \App\Domains\Client\Events\ServiceActivated::class,
            \App\Domains\Client\Listeners\NotifyServiceActivated::class
        );

        // Service Suspension - Suspend billing
        Event::listen(
            \App\Domains\Client\Events\ServiceSuspended::class,
            \App\Domains\Client\Listeners\SuspendRecurringBilling::class
        );

        // Service Suspension - Send notification
        Event::listen(
            \App\Domains\Client\Events\ServiceSuspended::class,
            \App\Domains\Client\Listeners\NotifyServiceSuspended::class
        );

        // Service Resume - Resume billing
        Event::listen(
            \App\Domains\Client\Events\ServiceResumed::class,
            \App\Domains\Client\Listeners\ResumeRecurringBilling::class
        );

        // Service Cancellation - Cancel billing (handled by ServiceBillingService)
        // Event dispatched but billing cancellation happens in the service directly

        // Service Renewal Due - Send reminder notification
        Event::listen(
            \App\Domains\Client\Events\ServiceDueForRenewal::class,
            \App\Domains\Client\Listeners\NotifyServiceRenewalDue::class
        );

        // SLA Breach - Create alert/ticket
        Event::listen(
            \App\Domains\Client\Events\ServiceSLABreached::class,
            \App\Domains\Client\Listeners\AlertOnSLABreach::class
        );

        // SLA Breach - Recalculate health score
        Event::listen(
            \App\Domains\Client\Events\ServiceSLABreached::class,
            \App\Domains\Client\Listeners\RecalculateServiceHealth::class
        );

        // Health Degraded - Recalculate (additional monitoring)
        Event::listen(
            \App\Domains\Client\Events\ServiceHealthDegraded::class,
            \App\Domains\Client\Listeners\RecalculateServiceHealth::class
        );
    }

    /**
     * Configure Bouncer for multi-tenancy
     */
    protected function configureBouncer(): void
    {
        // Set custom table names
        \Bouncer::tables([
            'abilities' => 'bouncer_abilities',
            'assigned_roles' => 'bouncer_assigned_roles',
            'permissions' => 'bouncer_permissions',
            'roles' => 'bouncer_roles',
        ]);

        // Note: Bouncer scope is set per-request via SetBouncerScope middleware

        // Register custom Blade directives for enhanced permissions
        $this->registerPermissionDirectives();
    }

    /**
     * Register custom Blade directives for permission checking
     */
    protected function registerPermissionDirectives(): void
    {
        // Check single permission with wildcard support
        Blade::if('permission', function ($permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });

        // Check any of multiple permissions
        Blade::if('anyPermission', function (...$permissions) {
            return auth()->check() && auth()->user()->hasAnyPermission($permissions);
        });

        // Check all permissions
        Blade::if('allPermissions', function (...$permissions) {
            return auth()->check() && auth()->user()->hasAllPermissions($permissions);
        });

        // Check resource-level permission
        Blade::if('canAccess', function ($permission, $resource) {
            return auth()->check() && auth()->user()->canAccessResource($permission, $resource);
        });
    }

    /**
     * Register the Blaze directive for Livewire Flux compatibility
     * The @blaze directive is used by Flux UI components to mark sections
     * for optimization by the Blaze compiler in Livewire 4
     */
    protected function registerBlazeDirective(): void
    {
        // The @blaze directive compiles to empty string - it's just a marker
        // for the Blaze compiler to optimize the following content
        Blade::directive('blaze', fn () => '');
    }
}
