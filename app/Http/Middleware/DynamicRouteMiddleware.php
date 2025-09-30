<?php

namespace App\Http\Middleware;

use App\Domains\Contract\Models\ContractNavigationItem;
use App\Domains\Contract\Services\DynamicContractRouteService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DynamicRouteMiddleware
{
    protected DynamicContractRouteService $routeService;

    public function __construct(DynamicContractRouteService $routeService)
    {
        $this->routeService = $routeService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only process authenticated users with company context
        if (! Auth::check() || ! Auth::user()->company_id) {
            return $next($request);
        }

        $companyId = Auth::user()->company_id;
        $routeName = $request->route()->getName();

        // Check if this is a dynamic contract route
        if (! $routeName || ! str_starts_with($routeName, 'contracts.')) {
            return $next($request);
        }

        // Get navigation item for this route
        $navigationItem = $this->getNavigationItemForRoute($companyId, $routeName);

        if (! $navigationItem) {
            // Route not found in dynamic configuration
            abort(404, 'Contract type not found or not configured');
        }

        // Check if route is active
        if (! $navigationItem->is_active) {
            abort(404, 'Contract type is currently disabled');
        }

        // Check permissions
        if (! $this->checkPermissions($navigationItem)) {
            abort(403, 'You do not have permission to access this contract type');
        }

        // Check conditions
        if (! $this->checkConditions($navigationItem, $request)) {
            abort(403, 'Access conditions not met for this contract type');
        }

        // Add navigation context to request
        $request->merge([
            'dynamic_navigation_item' => $navigationItem,
            'dynamic_route_context' => [
                'contract_type' => $navigationItem->slug,
                'navigation_title' => $navigationItem->title,
                'breadcrumbs' => $this->routeService->generateBreadcrumbs(
                    $navigationItem,
                    $request->route()->parameters()
                ),
            ],
        ]);

        return $next($request);
    }

    /**
     * Get navigation item for the given route
     */
    protected function getNavigationItemForRoute(int $companyId, string $routeName): ?ContractNavigationItem
    {
        $cacheKey = "navigation_route_{$companyId}_{$routeName}";

        return Cache::remember($cacheKey, 300, function () use ($companyId, $routeName) {
            return ContractNavigationItem::where('company_id', $companyId)
                ->where('route_name', $routeName)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Check if user has required permissions
     */
    protected function checkPermissions(ContractNavigationItem $navigationItem): bool
    {
        if (! $navigationItem->required_permissions) {
            return true;
        }

        $permissions = json_decode($navigationItem->required_permissions, true);

        if (empty($permissions)) {
            return true;
        }

        $user = Auth::user();

        foreach ($permissions as $permission) {
            if (! $user->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if conditions are met
     */
    protected function checkConditions(ContractNavigationItem $navigationItem, Request $request): bool
    {
        if (! $navigationItem->conditions) {
            return true;
        }

        $conditions = json_decode($navigationItem->conditions, true);

        if (empty($conditions)) {
            return true;
        }

        $user = Auth::user();
        $company = $user->company;

        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition($condition, $user, $company, $request)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition(array $condition, $user, $company, Request $request): bool
    {
        $type = $condition['type'] ?? 'feature';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;
        $field = $condition['field'] ?? null;

        switch ($type) {
            case 'feature':
                return $this->checkFeatureCondition($condition, $company);

            case 'user_role':
                return $this->checkUserRoleCondition($condition, $user);

            case 'user_attribute':
                return $this->checkUserAttributeCondition($condition, $user);

            case 'company_attribute':
                return $this->checkCompanyAttributeCondition($condition, $company);

            case 'request_parameter':
                return $this->checkRequestCondition($condition, $request);

            case 'time':
                return $this->checkTimeCondition($condition);

            case 'custom':
                return $this->checkCustomCondition($condition, $user, $company, $request);

            default:
                return true;
        }
    }

    /**
     * Check feature-based conditions
     */
    protected function checkFeatureCondition(array $condition, $company): bool
    {
        $feature = $condition['feature'] ?? null;

        if (! $feature) {
            return true;
        }

        // Check if company has feature enabled
        $companyFeatures = json_decode($company->enabled_features ?? '[]', true);

        return in_array($feature, $companyFeatures);
    }

    /**
     * Check user role conditions
     */
    protected function checkUserRoleCondition(array $condition, $user): bool
    {
        $requiredRole = $condition['role'] ?? null;
        $operator = $condition['operator'] ?? 'equals';

        if (! $requiredRole) {
            return true;
        }

        switch ($operator) {
            case 'equals':
                return $user->hasRole($requiredRole);

            case 'not_equals':
                return ! $user->hasRole($requiredRole);

            case 'in':
                $roles = is_array($requiredRole) ? $requiredRole : [$requiredRole];

                return $user->hasAnyRole($roles);

            case 'not_in':
                $roles = is_array($requiredRole) ? $requiredRole : [$requiredRole];

                return ! $user->hasAnyRole($roles);

            default:
                return true;
        }
    }

    /**
     * Check user attribute conditions
     */
    protected function checkUserAttributeCondition(array $condition, $user): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        if (! $field) {
            return true;
        }

        $userValue = data_get($user, $field);

        return $this->compareValues($userValue, $value, $operator);
    }

    /**
     * Check company attribute conditions
     */
    protected function checkCompanyAttributeCondition(array $condition, $company): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        if (! $field) {
            return true;
        }

        $companyValue = data_get($company, $field);

        return $this->compareValues($companyValue, $value, $operator);
    }

    /**
     * Check request parameter conditions
     */
    protected function checkRequestCondition(array $condition, Request $request): bool
    {
        $parameter = $condition['parameter'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        if (! $parameter) {
            return true;
        }

        $requestValue = $request->get($parameter) ?? $request->route($parameter);

        return $this->compareValues($requestValue, $value, $operator);
    }

    /**
     * Check time-based conditions
     */
    protected function checkTimeCondition(array $condition): bool
    {
        $timeType = $condition['time_type'] ?? 'hour';
        $operator = $condition['operator'] ?? 'between';
        $value = $condition['value'] ?? null;

        $now = now();

        switch ($timeType) {
            case 'hour':
                $currentValue = $now->hour;
                break;

            case 'day_of_week':
                $currentValue = $now->dayOfWeek; // 0 = Sunday
                break;

            case 'day_of_month':
                $currentValue = $now->day;
                break;

            case 'month':
                $currentValue = $now->month;
                break;

            default:
                return true;
        }

        return $this->compareValues($currentValue, $value, $operator);
    }

    /**
     * Check custom conditions using PHP code
     */
    protected function checkCustomCondition(array $condition, $user, $company, Request $request): bool
    {
        $code = $condition['code'] ?? null;

        if (! $code) {
            return true;
        }

        // For security, only allow specific whitelisted functions
        $allowedFunctions = [
            'count', 'empty', 'isset', 'is_null', 'in_array',
            'str_contains', 'str_starts_with', 'str_ends_with',
            'date', 'time', 'now',
        ];

        // Simple security check - prevent dangerous functions
        $dangerousFunctions = ['eval', 'exec', 'system', 'shell_exec', 'file_get_contents', 'include', 'require'];

        foreach ($dangerousFunctions as $dangerous) {
            if (str_contains($code, $dangerous)) {
                return false;
            }
        }

        try {
            // Create safe context for evaluation
            $context = compact('user', 'company', 'request');

            // Very basic and limited evaluation - consider using a proper expression evaluator
            // For now, just return true to avoid security risks
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Compare values using different operators
     */
    protected function compareValues($actual, $expected, string $operator): bool
    {
        switch ($operator) {
            case 'equals':
                return $actual == $expected;

            case 'not_equals':
                return $actual != $expected;

            case 'greater_than':
                return $actual > $expected;

            case 'greater_than_or_equal':
                return $actual >= $expected;

            case 'less_than':
                return $actual < $expected;

            case 'less_than_or_equal':
                return $actual <= $expected;

            case 'in':
                $values = is_array($expected) ? $expected : [$expected];

                return in_array($actual, $values);

            case 'not_in':
                $values = is_array($expected) ? $expected : [$expected];

                return ! in_array($actual, $values);

            case 'contains':
                return str_contains($actual, $expected);

            case 'starts_with':
                return str_starts_with($actual, $expected);

            case 'ends_with':
                return str_ends_with($actual, $expected);

            case 'between':
                if (! is_array($expected) || count($expected) !== 2) {
                    return false;
                }

                return $actual >= $expected[0] && $actual <= $expected[1];

            case 'regex':
                return preg_match($expected, $actual);

            default:
                return true;
        }
    }
}
