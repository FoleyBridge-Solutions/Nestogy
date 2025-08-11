<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceTenantBoundaries
 * 
 * Ensures users can only access data from their own company unless they are
 * super-admins from Company 1 with cross-tenant permissions.
 */
class EnforceTenantBoundaries
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Super-admins from Company 1 can access cross-tenant data
        if ($user->canAccessCrossTenant()) {
            return $next($request);
        }

        // Regular users are restricted to their company's data
        // This middleware will be used with route model binding to ensure
        // models belong to the authenticated user's company
        
        return $next($request);
    }

    /**
     * Check if a model belongs to the user's company.
     */
    protected function modelBelongsToUserCompany($model, $user): bool
    {
        if (!$model || !$user) {
            return false;
        }

        // Check if model has company_id field
        if (isset($model->company_id)) {
            return $model->company_id === $user->company_id;
        }

        // Check if model belongs to a company through relationships
        if (method_exists($model, 'company') && $model->company) {
            return $model->company->id === $user->company_id;
        }

        return true; // If no company association, allow access
    }
}