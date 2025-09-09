<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Company;
use App\Models\CrossCompanyUser;
use App\Models\CompanyHierarchy;

/**
 * SubsidiaryAccessMiddleware
 * 
 * Handles cross-company access validation and company switching
 * for users with multi-company permissions in organizational hierarchies.
 */
class SubsidiaryAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $requestedCompanyId = $this->getRequestedCompanyId($request);

        // If no specific company is requested, use current context
        if (!$requestedCompanyId) {
            $requestedCompanyId = Session::get('current_company_id', $user->company_id);
        }

        // Validate access to the requested company
        if (!$this->validateCompanyAccess($user, $requestedCompanyId)) {
            Log::warning('SubsidiaryAccessMiddleware: Access denied', [
                'user_id' => $user->id,
                'user_company_id' => $user->company_id,
                'requested_company_id' => $requestedCompanyId,
                'permissions' => $permissions
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied to requested company.'], 403);
            }

            return redirect()->back()->withErrors([
                'company_access' => 'You do not have permission to access this company.'
            ]);
        }

        // Set the current company context
        $this->setCompanyContext($request, $requestedCompanyId);

        // Check specific permissions if provided
        if (!empty($permissions)) {
            if (!$this->hasRequiredPermissions($user, $requestedCompanyId, $permissions)) {
                Log::warning('SubsidiaryAccessMiddleware: Permission denied', [
                    'user_id' => $user->id,
                    'company_id' => $requestedCompanyId,
                    'required_permissions' => $permissions
                ]);

                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Insufficient permissions.'], 403);
                }

                return redirect()->back()->withErrors([
                    'permissions' => 'You do not have the required permissions for this action.'
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Extract requested company ID from the request.
     */
    protected function getRequestedCompanyId(Request $request): ?int
    {
        // Check route parameters
        if ($request->route('company')) {
            return (int) $request->route('company');
        }

        // Check query parameters
        if ($request->has('company_id')) {
            return (int) $request->get('company_id');
        }

        // Check form data
        if ($request->has('company_id')) {
            return (int) $request->input('company_id');
        }

        // Check session for company switching
        if ($request->has('switch_company')) {
            return (int) $request->get('switch_company');
        }

        return null;
    }

    /**
     * Validate if user can access the requested company.
     */
    protected function validateCompanyAccess($user, int $companyId): bool
    {
        // User's own company - always allowed
        if ($user->company_id === $companyId) {
            return true;
        }

        // Check if company exists
        $company = Company::find($companyId);
        if (!$company) {
            return false;
        }

        // Check cross-company access
        if (CrossCompanyUser::canUserAccessCompany($user->id, $companyId)) {
            return true;
        }

        // Check if user has subsidiary management rights
        $userSettings = $user->settings;
        if ($userSettings && $userSettings->canManageSubsidiaries()) {
            // Check if requested company is a subsidiary of user's company
            if (CompanyHierarchy::isDescendant($companyId, $user->company_id)) {
                return true;
            }
        }

        // Check if user is from a subsidiary and trying to access parent
        if ($userSettings && $userSettings->isSubsidiaryAdmin()) {
            // Limited parent access for subsidiary admins
            if (CompanyHierarchy::isAncestor($companyId, $user->company_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set company context in the request and session.
     */
    protected function setCompanyContext(Request $request, int $companyId): void
    {
        // Store in session for consistent access
        Session::put('current_company_id', $companyId);

        // Set in request attributes for easy access
        $company = Company::find($companyId);
        if ($company) {
            $request->attributes->set('current_company', $company);
            $request->attributes->set('current_company_id', $companyId);

            // Share with views
            view()->share('currentCompany', $company);
            view()->share('currentCompanyId', $companyId);

            // Update config for queries
            config(['app.current_company_id' => $companyId]);
        }

        // Log company access for audit
        $this->logCompanyAccess($companyId);
    }

    /**
     * Check if user has the required permissions.
     */
    protected function hasRequiredPermissions($user, int $companyId, array $permissions): bool
    {
        // If it's their own company, delegate to Laravel policies
        if ($user->company_id === $companyId) {
            return true;
        }

        // Get cross-company access record
        $crossCompanyAccess = CrossCompanyUser::where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->active()
            ->first();

        if ($crossCompanyAccess) {
            // Check each required permission
            foreach ($permissions as $permission) {
                if (!$crossCompanyAccess->hasPermission($permission)) {
                    return false;
                }
            }
            return true;
        }

        // Check subsidiary permissions
        $userCompanyId = $user->company_id;
        foreach ($permissions as $permission) {
            $hasPermission = \App\Models\SubsidiaryPermission::hasPermission(
                $userCompanyId,
                '*', // Wildcard for general permissions
                $permission,
                $user->id
            );

            if (!$hasPermission) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log company access for audit purposes.
     */
    protected function logCompanyAccess(int $companyId): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Only log if accessing a different company
        if ($user->company_id !== $companyId) {
            // Update last accessed timestamp for cross-company access
            CrossCompanyUser::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->active()
                ->update(['last_accessed_at' => now()]);

            Log::info('SubsidiaryAccessMiddleware: Cross-company access', [
                'user_id' => $user->id,
                'user_company_id' => $user->company_id,
                'accessed_company_id' => $companyId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * Handle company switching requests.
     */
    public function handleCompanySwitch(Request $request): Response
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $targetCompanyId = (int) $request->input('company_id');
        $user = Auth::user();

        if (!$this->validateCompanyAccess($user, $targetCompanyId)) {
            return response()->json([
                'error' => 'Access denied to requested company',
                'company_id' => $targetCompanyId
            ], 403);
        }

        // Set new company context
        Session::put('current_company_id', $targetCompanyId);
        
        $company = Company::find($targetCompanyId);
        
        Log::info('Company switched', [
            'user_id' => $user->id,
            'from_company_id' => $user->company_id,
            'to_company_id' => $targetCompanyId,
        ]);

        return response()->json([
            'success' => true,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'type' => $company->company_type,
            ],
            'message' => 'Company context switched successfully'
        ]);
    }

    /**
     * Get available companies for the current user.
     */
    public function getAvailableCompanies(): array
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();
        $companies = collect();

        // Add user's own company
        $companies->push($user->company);

        // Add cross-company access
        $crossCompanyAccess = CrossCompanyUser::getAccessibleCompanies($user->id);
        $companies = $companies->merge($crossCompanyAccess);

        // Add subsidiaries if user can manage them
        $userSettings = $user->settings;
        if ($userSettings && $userSettings->canManageSubsidiaries()) {
            $subsidiaries = CompanyHierarchy::getDescendants($user->company_id)
                ->pluck('descendant_id')
                ->map(fn($id) => Company::find($id))
                ->filter();
            
            $companies = $companies->merge($subsidiaries);
        }

        // Add parent companies for subsidiary admins (limited access)
        if ($userSettings && $userSettings->isSubsidiaryAdmin()) {
            $parents = CompanyHierarchy::getAncestors($user->company_id)
                ->pluck('ancestor_id')
                ->map(fn($id) => Company::find($id))
                ->filter();
            
            $companies = $companies->merge($parents);
        }

        return $companies->unique('id')->values()->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'type' => $company->company_type,
                'is_primary' => $company->id === Auth::user()->company_id,
            ];
        })->toArray();
    }
}