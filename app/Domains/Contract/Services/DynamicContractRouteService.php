<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\ContractNavigationItem;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class DynamicContractRouteService
{
    protected Router $router;
    protected array $registeredRoutes = [];

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register all dynamic contract routes for the given company
     */
    public function registerCompanyRoutes(int $companyId): void
    {
        $navigationItems = ContractNavigationItem::where('company_id', $companyId)
            ->whereNotNull('route')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($navigationItems as $item) {
            $this->registerRoute($item);
        }
    }

    /**
     * Register all dynamic routes for all active companies
     */
    public function registerAllRoutes(): void
    {
        $navigationItems = ContractNavigationItem::with('company')
            ->whereNotNull('route')
            ->where('is_active', true)
            ->orderBy('company_id')
            ->orderBy('sort_order')
            ->get();

        foreach ($navigationItems as $item) {
            $this->registerRoute($item);
        }
    }

    /**
     * Register a single route based on navigation item configuration
     */
    protected function registerRoute(ContractNavigationItem $item): void
    {
        $routeName = $item->route;
        $routePath = $this->generateRoutePath($item);
        $controller = $this->getControllerAction($item);
        $method = $item->config['method'] ?? 'GET';

        // Avoid duplicate route registration
        if (in_array($routeName, $this->registeredRoutes)) {
            return;
        }

        $route = Route::match([$method], $routePath, $controller)
            ->name($routeName)
            ->middleware(['web', 'auth', 'company']);

        // Add route parameters if specified
        if (!empty($item->config['parameters'])) {
            $parameters = $item->config['parameters'];
            foreach ($parameters as $key => $constraint) {
                $route->where($key, $constraint);
            }
        }

        // Add role/permission middleware if specified
        if (!empty($item->permissions)) {
            $route->middleware(['can:' . implode(',', $item->permissions)]);
        }

        $this->registeredRoutes[] = $routeName;
    }

    /**
     * Generate route path based on navigation item configuration
     */
    protected function generateRoutePath(ContractNavigationItem $item): string
    {
        if (!empty($item->config['path'])) {
            return $item->config['path'];
        }

        // Generate path from navigation structure
        $pathParts = [];
        
        // Build hierarchical path
        $current = $item;
        $hierarchy = [];
        
        while ($current) {
            array_unshift($hierarchy, $current);
            $current = $current->parent_slug ? 
                ContractNavigationItem::where('company_id', $item->company_id)
                    ->where('slug', $current->parent_slug)
                    ->first() : null;
        }

        foreach ($hierarchy as $navItem) {
            if ($navItem->slug) {
                $pathParts[] = $navItem->slug;
            }
        }

        $basePath = '/contracts/' . implode('/', $pathParts);

        // Add resource parameters based on navigation type
        $type = $item->config['type'] ?? 'page';
        switch ($type) {
            case 'list':
                return $basePath;
                
            case 'create':
                return $basePath . '/create';
                
            case 'show':
                return $basePath . '/{id}';
                
            case 'edit':
                return $basePath . '/{id}/edit';
                
            case 'custom':
                return $item->config['path'] ?? $basePath;
                
            default:
                return $basePath;
        }
    }

    /**
     * Get the controller action for the navigation item
     */
    protected function getControllerAction(ContractNavigationItem $item): string
    {
        if (!empty($item->config['controller'])) {
            return $item->config['controller'];
        }

        // Default to DynamicContractController
        $controller = 'App\Domains\Contract\Controllers\DynamicContractController';
        
        $type = $item->config['type'] ?? 'page';
        switch ($type) {
            case 'list':
                return $controller . '@index';
                
            case 'create':
                return $controller . '@create';
                
            case 'show':
                return $controller . '@show';
                
            case 'edit':
                return $controller . '@edit';
                
            case 'custom':
                return $item->config['controller'] ?? $controller . '@index';
                
            default:
                return $controller . '@index';
        }
    }

    /**
     * Generate RESTful routes for a contract type
     */
    public function generateResourceRoutes(ContractNavigationItem $parentItem): array
    {
        $baseName = $parentItem->slug ?: Str::kebab($parentItem->label);
        $routePrefix = "contracts.{$baseName}";
        $pathPrefix = "/contracts/{$baseName}";

        $routes = [
            [
                'name' => "{$routePrefix}.index",
                'path' => $pathPrefix,
                'method' => 'GET',
                'action' => 'index',
                'type' => 'list',
                'title' => $parentItem->label . ' List',
            ],
            [
                'name' => "{$routePrefix}.create",
                'path' => "{$pathPrefix}/create",
                'method' => 'GET',
                'action' => 'create',
                'type' => 'create',
                'title' => 'Create ' . $parentItem->label,
            ],
            [
                'name' => "{$routePrefix}.store",
                'path' => $pathPrefix,
                'method' => 'POST',
                'action' => 'store',
                'type' => 'store',
                'title' => 'Store ' . $parentItem->label,
            ],
            [
                'name' => "{$routePrefix}.show",
                'path' => "{$pathPrefix}/{id}",
                'method' => 'GET',
                'action' => 'show',
                'type' => 'show',
                'title' => 'View ' . $parentItem->label,
            ],
            [
                'name' => "{$routePrefix}.edit",
                'path' => "{$pathPrefix}/{id}/edit",
                'method' => 'GET',
                'action' => 'edit',
                'type' => 'edit',
                'title' => 'Edit ' . $parentItem->label,
            ],
            [
                'name' => "{$routePrefix}.update",
                'path' => "{$pathPrefix}/{id}",
                'method' => 'PUT',
                'action' => 'update',
                'type' => 'update',
                'title' => 'Update ' . $parentItem->label,
            ],
            [
                'name' => "{$routePrefix}.destroy",
                'path' => "{$pathPrefix}/{id}",
                'method' => 'DELETE',
                'action' => 'destroy',
                'type' => 'destroy',
                'title' => 'Delete ' . $parentItem->label,
            ],
        ];

        return $routes;
    }

    /**
     * Clear all registered dynamic routes
     */
    public function clearRoutes(): void
    {
        $this->registeredRoutes = [];
    }

    /**
     * Get all registered route names
     */
    public function getRegisteredRoutes(): array
    {
        return $this->registeredRoutes;
    }

    /**
     * Check if a route exists in the registered routes
     */
    public function routeExists(string $routeName): bool
    {
        return in_array($routeName, $this->registeredRoutes);
    }

    /**
     * Generate route URL for navigation item
     */
    public function generateUrl(ContractNavigationItem $item, array $parameters = []): string
    {
        if (!$item->route) {
            return '#';
        }

        try {
            return route($item->route, $parameters);
        } catch (\Exception $e) {
            // Fallback to manual URL generation
            $path = $this->generateRoutePath($item);
            
            foreach ($parameters as $key => $value) {
                $path = str_replace("{{$key}}", $value, $path);
            }
            
            return url($path);
        }
    }

    /**
     * Generate breadcrumbs for a navigation item
     */
    public function generateBreadcrumbs(ContractNavigationItem $item, array $parameters = []): array
    {
        $breadcrumbs = [];
        $current = $item;

        // Build hierarchy from current item to root
        $hierarchy = [];
        while ($current) {
            array_unshift($hierarchy, $current);
            $current = $current->parent_id ? ContractNavigationItem::find($current->parent_id) : null;
        }

        // Convert to breadcrumb format
        foreach ($hierarchy as $navItem) {
            $breadcrumbs[] = [
                'title' => $navItem->label,
                'url' => $navItem->route ? $this->generateUrl($navItem, $parameters) : null,
                'active' => $navItem->id === $item->id,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Validate navigation configuration
     */
    public function validateNavigationConfig(array $config): array
    {
        $errors = [];

        foreach ($config as $index => $item) {
            // Check required fields
            if (empty($item['label'])) {
                $errors[] = "Navigation item {$index}: Label is required";
            }

            if (!empty($item['route'])) {
                // Validate route name format
                if (!preg_match('/^[a-z0-9_.]+$/', $item['route'])) {
                    $errors[] = "Navigation item {$index}: Invalid route name format";
                }
            }

            if (!empty($item['route_path'])) {
                // Validate route path format
                if (!str_starts_with($item['route_path'], '/')) {
                    $errors[] = "Navigation item {$index}: Route path must start with '/'";
                }
            }

            // Validate controller action format
            if (!empty($item['controller_action'])) {
                if (!str_contains($item['controller_action'], '@')) {
                    $errors[] = "Navigation item {$index}: Controller action must be in format 'Controller@method'";
                }
            }

            // Validate permissions format
            if (!empty($item['required_permissions'])) {
                $permissions = json_decode($item['required_permissions'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = "Navigation item {$index}: Invalid permissions JSON format";
                }
            }

            // Validate conditions format
            if (!empty($item['conditions'])) {
                $conditions = json_decode($item['conditions'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = "Navigation item {$index}: Invalid conditions JSON format";
                }
            }
        }

        return $errors;
    }

    /**
     * Generate API routes for navigation items
     */
    public function generateApiRoutes(ContractNavigationItem $item): array
    {
        $baseName = $item->slug ?: Str::kebab($item->label);
        $routePrefix = "api.contracts.{$baseName}";
        $pathPrefix = "/api/contracts/{$baseName}";

        return [
            [
                'name' => "{$routePrefix}.index",
                'path' => $pathPrefix,
                'method' => 'GET',
                'action' => 'App\Domains\Contract\Controllers\Api\DynamicContractApiController@index',
            ],
            [
                'name' => "{$routePrefix}.store",
                'path' => $pathPrefix,
                'method' => 'POST',
                'action' => 'App\Domains\Contract\Controllers\Api\DynamicContractApiController@store',
            ],
            [
                'name' => "{$routePrefix}.show",
                'path' => "{$pathPrefix}/{id}",
                'method' => 'GET',
                'action' => 'App\Domains\Contract\Controllers\Api\DynamicContractApiController@show',
            ],
            [
                'name' => "{$routePrefix}.update",
                'path' => "{$pathPrefix}/{id}",
                'method' => 'PUT',
                'action' => 'App\Domains\Contract\Controllers\Api\DynamicContractApiController@update',
            ],
            [
                'name' => "{$routePrefix}.destroy",
                'path' => "{$pathPrefix}/{id}",
                'method' => 'DELETE',
                'action' => 'App\Domains\Contract\Controllers\Api\DynamicContractApiController@destroy',
            ],
        ];
    }
}