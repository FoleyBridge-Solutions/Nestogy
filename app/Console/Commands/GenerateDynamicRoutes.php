<?php

namespace App\Console\Commands;

use App\Domains\Contract\Models\ContractNavigationItem;
use App\Domains\Contract\Services\DynamicContractRouteService;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateDynamicRoutes extends Command
{
    protected $signature = 'routes:generate-dynamic 
                            {--company= : Generate routes for specific company ID}
                            {--export= : Export routes to file}
                            {--validate : Validate route configurations}
                            {--clear : Clear existing dynamic routes}';

    protected $description = 'Generate dynamic contract routes based on navigation configuration';

    protected DynamicContractRouteService $routeService;

    public function __construct(DynamicContractRouteService $routeService)
    {
        parent::__construct();
        $this->routeService = $routeService;
    }

    public function handle(): int
    {
        if ($this->option('clear')) {
            return $this->clearRoutes();
        }

        if ($this->option('validate')) {
            return $this->validateRoutes();
        }

        if ($this->option('export')) {
            return $this->exportRoutes();
        }

        return $this->generateRoutes();
    }

    protected function generateRoutes(): int
    {
        $this->info('Generating dynamic contract routes...');

        $companyId = $this->option('company');

        if ($companyId) {
            return $this->generateCompanyRoutes($companyId);
        }

        return $this->generateAllRoutes();
    }

    protected function generateCompanyRoutes(int $companyId): int
    {
        $company = Company::find($companyId);

        if (! $company) {
            $this->error("Company with ID {$companyId} not found.");

            return 1;
        }

        $this->info("Generating routes for company: {$company->name}");

        try {
            $this->routeService->registerCompanyRoutes($companyId);

            $registeredRoutes = $this->routeService->getRegisteredRoutes();
            $routeCount = count($registeredRoutes);

            $this->info("Successfully generated {$routeCount} routes for {$company->name}");

            if ($this->option('verbose')) {
                $this->table(['Route Name'], array_map(fn ($route) => [$route], $registeredRoutes));
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate routes: {$e->getMessage()}");

            return 1;
        }
    }

    protected function generateAllRoutes(): int
    {
        $this->info('Generating routes for all companies...');

        try {
            $companies = Company::whereHas('contractNavigationItems', function ($query) {
                $query->whereNotNull('route_name')->where('is_active', true);
            })->get();

            $totalRoutes = 0;

            foreach ($companies as $company) {
                $this->line("Processing company: {$company->name}");

                $routesBefore = count($this->routeService->getRegisteredRoutes());
                $this->routeService->registerCompanyRoutes($company->id);
                $routesAfter = count($this->routeService->getRegisteredRoutes());

                $companyRoutes = $routesAfter - $routesBefore;
                $totalRoutes += $companyRoutes;

                $this->info("  Generated {$companyRoutes} routes");
            }

            $this->info("Successfully generated {$totalRoutes} total routes for ".$companies->count().' companies');

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate routes: {$e->getMessage()}");

            return 1;
        }
    }

    protected function validateRoutes(): int
    {
        $this->info('Validating route configurations...');

        $companies = Company::whereHas('contractNavigationItems')->get();
        $totalErrors = 0;

        foreach ($companies as $company) {
            $this->line("Validating routes for company: {$company->name}");

            $navigationItems = ContractNavigationItem::where('company_id', $company->id)
                ->where('is_active', true)
                ->get();

            $config = $navigationItems->map(function ($item) {
                return [
                    'title' => $item->title,
                    'route_name' => $item->route_name,
                    'route_path' => $item->route_path,
                    'controller_action' => $item->controller_action,
                    'required_permissions' => $item->required_permissions,
                    'conditions' => $item->conditions,
                ];
            })->toArray();

            $errors = $this->routeService->validateNavigationConfig($config);

            if (! empty($errors)) {
                $this->warn('  Found '.count($errors).' errors:');
                foreach ($errors as $error) {
                    $this->error("    - {$error}");
                }
                $totalErrors += count($errors);
            } else {
                $this->info('  No errors found');
            }
        }

        if ($totalErrors > 0) {
            $this->error("Total validation errors: {$totalErrors}");

            return 1;
        }

        $this->info('All route configurations are valid!');

        return 0;
    }

    protected function exportRoutes(): int
    {
        $exportFile = $this->option('export');

        if (! $exportFile) {
            $this->error('Export file path is required when using --export option');

            return 1;
        }

        $this->info("Exporting routes to: {$exportFile}");

        try {
            $companies = Company::with(['contractNavigationItems' => function ($query) {
                $query->whereNotNull('route_name')->where('is_active', true)->orderBy('sort_order');
            }])->get();

            $exportData = [
                'generated_at' => now()->toISOString(),
                'total_companies' => $companies->count(),
                'companies' => [],
            ];

            foreach ($companies as $company) {
                $companyData = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'routes' => [],
                ];

                foreach ($company->contractNavigationItems as $item) {
                    $companyData['routes'][] = [
                        'name' => $item->route_name,
                        'path' => $item->route_path ?: 'generated',
                        'method' => $item->route_method ?: 'GET',
                        'controller' => $item->controller_action ?: 'DynamicContractController@index',
                        'title' => $item->title,
                        'type' => $item->type,
                        'permissions' => $item->required_permissions,
                        'conditions' => $item->conditions,
                    ];
                }

                $companyData['route_count'] = count($companyData['routes']);
                $exportData['companies'][] = $companyData;
            }

            $exportData['total_routes'] = collect($exportData['companies'])->sum('route_count');

            File::put($exportFile, json_encode($exportData, JSON_PRETTY_PRINT));

            $this->info("Successfully exported {$exportData['total_routes']} routes from {$exportData['total_companies']} companies");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to export routes: {$e->getMessage()}");

            return 1;
        }
    }

    protected function clearRoutes(): int
    {
        $this->info('Clearing dynamic routes...');

        try {
            $this->routeService->clearRoutes();
            $this->info('Dynamic routes cleared successfully');

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to clear routes: {$e->getMessage()}");

            return 1;
        }
    }
}
