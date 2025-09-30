<?php

namespace App\Traits;

use Illuminate\Support\Facades\Gate;

/**
 * HasAuthorization Trait
 *
 * Provides consistent authorization patterns for domain controllers.
 * Handles permission checks, policy authorization, and export controls.
 */
trait HasAuthorization
{
    /**
     * Apply standard authorization middleware to controller.
     *
     * @param  string  $domain  The domain name (e.g., 'clients', 'assets')
     * @param  array  $customPermissions  Optional custom permission mappings
     */
    protected function applyAuthorizationMiddleware(string $domain, array $customPermissions = []): void
    {
        $this->middleware('auth');
        $this->middleware('company');

        $permissions = array_merge([
            'view' => ['index', 'show'],
            'create' => ['create', 'store'],
            'edit' => ['edit', 'update'],
            'delete' => ['destroy'],
            'manage' => ['archive', 'restore', 'bulk'],
            'export' => ['export', 'exportCsv', 'exportPdf'],
        ], $customPermissions);

        foreach ($permissions as $action => $methods) {
            $permission = "{$domain}.{$action}";
            $this->middleware("permission:{$permission}")->only($methods);
        }
    }

    /**
     * Check if user has permission for domain action.
     */
    protected function checkPermission(string $domain, string $action): bool
    {
        $permission = "{$domain}.{$action}";

        return auth()->user()->hasPermission($permission);
    }

    /**
     * Authorize domain action or fail with 403.
     */
    protected function authorizeAction(string $domain, string $action, ?string $message = null): void
    {
        if (! $this->checkPermission($domain, $action)) {
            $message = $message ?: "Insufficient permissions for {$domain}.{$action}";
            abort(403, $message);
        }
    }

    /**
     * Authorize export operation with additional security checks.
     */
    protected function authorizeExport(string $domain, string $exportType = 'basic'): void
    {
        // Check basic export permission
        $this->authorizeAction($domain, 'export', "Insufficient permissions to export {$domain} data");

        // Additional checks for sensitive exports
        if (in_array($exportType, ['sensitive', 'financial', 'personal'])) {
            if (! Gate::allows('export-sensitive-data')) {
                abort(403, 'Insufficient permissions to export sensitive data');
            }
        }

        // Check bulk export permissions for large operations
        if ($exportType === 'bulk') {
            if (! Gate::allows('bulk-export')) {
                abort(403, 'Insufficient permissions for bulk export operations');
            }
        }
    }

    /**
     * Authorize approval workflow action.
     */
    protected function authorizeApproval(string $workflowType, string $action = 'approve'): void
    {
        $permission = $workflowType.'.'.$action;

        if (! auth()->user()->hasPermission($permission)) {
            abort(403, "Insufficient permissions for {$workflowType} {$action}");
        }

        // Additional gate checks for approval workflows
        $gate = 'approve-'.str_replace('.', '-', $workflowType);
        if (Gate::has($gate) && ! Gate::allows($gate)) {
            abort(403, "Approval workflow permissions denied for {$workflowType}");
        }
    }

    /**
     * Check team membership for project-based authorization.
     */
    protected function checkTeamMembership($project, $userId = null): bool
    {
        $userId = $userId ?: auth()->id();

        // Project manager check
        if ($project->manager_id === $userId) {
            return true;
        }

        // Team member check
        if (method_exists($project, 'members')) {
            return $project->members()->where('user_id', $userId)->exists();
        }

        return false;
    }

    /**
     * Authorize project-based action with team checks.
     */
    protected function authorizeProjectAction($project, string $action, string $permission): void
    {
        if (! $this->checkPermission('projects', $action)) {
            abort(403, "Insufficient permissions for projects.{$action}");
        }

        // Check if user is project manager or team member
        if (! auth()->user()->hasPermission('projects.manage') && ! $this->checkTeamMembership($project)) {
            abort(403, 'You must be a project manager or team member to perform this action');
        }
    }

    /**
     * Check company scope for multi-tenant authorization.
     */
    protected function checkCompanyScope($model): bool
    {
        if (! method_exists($model, 'getAttribute') || ! $model->getAttribute('company_id')) {
            return false;
        }

        return $model->company_id === auth()->user()->company_id;
    }

    /**
     * Authorize company-scoped action.
     */
    protected function authorizeCompanyScope($model, string $action): void
    {
        if (! $this->checkCompanyScope($model)) {
            abort(403, 'Access denied: Resource not within your company scope');
        }
    }

    /**
     * Apply standard CRUD authorization pattern.
     */
    protected function authorizeCrud(string $domain, string $action, $model = null): void
    {
        $this->authorizeAction($domain, $action);

        if ($model && ! $this->checkCompanyScope($model)) {
            abort(403, 'Access denied: Resource not within your company scope');
        }
    }

    /**
     * Get permission-filtered query for index actions.
     */
    protected function getAuthorizedQuery($baseQuery, string $domain)
    {
        // Apply company scoping
        if (method_exists($baseQuery->getModel(), 'getAttribute')) {
            $baseQuery = $baseQuery->where('company_id', auth()->user()->company_id);
        }

        // Apply role-based filtering if needed
        if (! auth()->user()->hasPermission("{$domain}.manage")) {
            // Additional filtering logic based on user role
            // This can be customized per domain
        }

        return $baseQuery;
    }

    /**
     * Log authorization events for audit trail.
     */
    protected function logAuthorizationEvent(string $action, $model = null, array $context = []): void
    {
        if (config('app.log_authorization', false)) {
            $data = [
                'user_id' => auth()->id(),
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->id,
                'context' => $context,
                'timestamp' => now(),
                'ip_address' => request()->ip(),
            ];

            logger('Authorization Event', $data);
        }
    }

    /**
     * Handle authorization failure with custom response.
     */
    protected function handleAuthorizationFailure(string $message, int $code = 403, array $context = []): void
    {
        $this->logAuthorizationEvent('authorization_failure', null, [
            'message' => $message,
            'code' => $code,
            'context' => $context,
        ]);

        if (request()->expectsJson()) {
            response()->json([
                'message' => $message,
                'error' => 'Authorization failed',
                'code' => $code,
            ], $code)->throwResponse();
        }

        abort($code, $message);
    }

    /**
     * Check rate limiting for sensitive operations.
     */
    protected function checkRateLimit(string $operation, int $maxAttempts = 10, int $decayMinutes = 60): void
    {
        $key = "rate_limit:{$operation}:".auth()->id();

        if (cache()->has($key)) {
            $attempts = cache()->get($key, 0);

            if ($attempts >= $maxAttempts) {
                abort(429, "Rate limit exceeded for {$operation}. Please try again later.");
            }

            cache()->put($key, $attempts + 1, now()->addMinutes($decayMinutes));
        } else {
            cache()->put($key, 1, now()->addMinutes($decayMinutes));
        }
    }
}
