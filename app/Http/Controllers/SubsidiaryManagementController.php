<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\CompanyHierarchy;
use App\Models\SubsidiaryPermission;
use App\Models\CrossCompanyUser;
use App\Models\User;
use App\Services\SubsidiaryCreationService;
use App\Services\HierarchyPermissionService;
use App\Http\Requests\StoreSubsidiaryRequest;
use App\Http\Requests\UpdateSubsidiaryRequest;

/**
 * SubsidiaryManagementController
 * 
 * Handles all subsidiary management operations including creation,
 * hierarchy visualization, permission management, and user assignments.
 */
class SubsidiaryManagementController extends Controller
{
    protected SubsidiaryCreationService $subsidiaryService;
    protected HierarchyPermissionService $permissionService;

    public function __construct(
        SubsidiaryCreationService $subsidiaryService,
        HierarchyPermissionService $permissionService
    ) {
        $this->middleware('auth');
        $this->middleware('subsidiary.access:manage_subsidiaries')->only([
            'create', 'store', 'edit', 'update', 'destroy'
        ]);
        
        $this->subsidiaryService = $subsidiaryService;
        $this->permissionService = $permissionService;
    }

    /**
     * Display subsidiary management dashboard.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $company = $user->company;

        // Check if user can manage subsidiaries
        if (!$user->settings?->canManageSubsidiaries() && !$company->canCreateSubsidiaries()) {
            abort(403, 'You do not have permission to manage subsidiaries.');
        }

        // Get hierarchy tree
        $hierarchyTree = $company->getHierarchyTree();
        
        // Get statistics
        $stats = [
            'total_subsidiaries' => CompanyHierarchy::getDescendants($company->id)->count(),
            'direct_subsidiaries' => $company->childCompanies()->count(),
            'max_depth' => $company->max_subsidiary_depth,
            'current_depth' => $company->organizational_level,
        ];

        // Get recent activity
        $recentActivity = $this->getRecentSubsidiaryActivity($company->id);

        if ($request->expectsJson()) {
            return response()->json([
                'company' => $company,
                'hierarchy_tree' => $hierarchyTree,
                'stats' => $stats,
                'recent_activity' => $recentActivity,
            ]);
        }

        return view('subsidiaries.index', compact(
            'company',
            'hierarchyTree',
            'stats',
            'recentActivity'
        ));
    }

    /**
     * Show form for creating a new subsidiary.
     */
    public function create(): View
    {
        $user = Auth::user();
        $company = $user->company;

        if (!$company->canCreateSubsidiaries()) {
            abort(403, 'This company cannot create subsidiaries.');
        }

        if ($company->hasReachedMaxSubsidiaryDepth()) {
            abort(403, 'Maximum subsidiary depth reached.');
        }

        return view('subsidiaries.create', compact('company'));
    }

    /**
     * Store a newly created subsidiary.
     */
    public function store(StoreSubsidiaryRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $subsidiary = $this->subsidiaryService->createSubsidiary(
                $request->validated(),
                Auth::user()->company_id
            );

            Log::info('Subsidiary created successfully', [
                'parent_company_id' => Auth::user()->company_id,
                'subsidiary_id' => $subsidiary->id,
                'subsidiary_name' => $subsidiary->name,
                'created_by' => Auth::id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Subsidiary created successfully.',
                    'subsidiary' => $subsidiary,
                ], 201);
            }

            return redirect()
                ->route('subsidiaries.show', $subsidiary)
                ->with('success', 'Subsidiary "' . $subsidiary->name . '" created successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to create subsidiary', [
                'parent_company_id' => Auth::user()->company_id,
                'error' => $e->getMessage(),
                'request_data' => $request->validated(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create subsidiary: ' . $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create subsidiary: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified subsidiary.
     */
    public function show(Company $subsidiary): View|JsonResponse
    {
        $user = Auth::user();
        
        // Verify access to this subsidiary
        if (!$user->company->canAccessCompany($subsidiary->id)) {
            abort(403, 'You do not have access to this subsidiary.');
        }

        // Load relationships
        $subsidiary->load([
            'parentCompany',
            'childCompanies',
            'users',
            'crossCompanyUsers.user',
            'grantedPermissions.granteeCompany',
            'receivedPermissions.granterCompany'
        ]);

        // Get subsidiary statistics
        $stats = [
            'users_count' => $subsidiary->users()->count(),
            'clients_count' => $subsidiary->clients()->count(),
            'subsidiaries_count' => $subsidiary->childCompanies()->count(),
            'active_permissions' => $subsidiary->grantedPermissions()->active()->count(),
        ];

        // Get hierarchy path
        $hierarchyPath = CompanyHierarchy::where('descendant_id', $subsidiary->id)
            ->where('depth', '>', 0)
            ->orderBy('depth', 'desc')
            ->with('ancestor')
            ->get();

        if (request()->expectsJson()) {
            return response()->json([
                'subsidiary' => $subsidiary,
                'stats' => $stats,
                'hierarchy_path' => $hierarchyPath,
            ]);
        }

        return view('subsidiaries.show', compact(
            'subsidiary',
            'stats',
            'hierarchyPath'
        ));
    }

    /**
     * Show form for editing the specified subsidiary.
     */
    public function edit(Company $subsidiary): View
    {
        $user = Auth::user();
        
        // Verify management access
        if (!$this->canManageSubsidiary($user, $subsidiary)) {
            abort(403, 'You do not have permission to edit this subsidiary.');
        }

        return view('subsidiaries.edit', compact('subsidiary'));
    }

    /**
     * Update the specified subsidiary.
     */
    public function update(UpdateSubsidiaryRequest $request, Company $subsidiary): RedirectResponse|JsonResponse
    {
        if (!$this->canManageSubsidiary(Auth::user(), $subsidiary)) {
            abort(403, 'You do not have permission to update this subsidiary.');
        }

        try {
            $subsidiary = $this->subsidiaryService->updateSubsidiary(
                $subsidiary,
                $request->validated()
            );

            Log::info('Subsidiary updated successfully', [
                'subsidiary_id' => $subsidiary->id,
                'subsidiary_name' => $subsidiary->name,
                'updated_by' => Auth::id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Subsidiary updated successfully.',
                    'subsidiary' => $subsidiary,
                ]);
            }

            return redirect()
                ->route('subsidiaries.show', $subsidiary)
                ->with('success', 'Subsidiary updated successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to update subsidiary', [
                'subsidiary_id' => $subsidiary->id,
                'error' => $e->getMessage(),
                'request_data' => $request->validated(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update subsidiary: ' . $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update subsidiary: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified subsidiary from hierarchy.
     */
    public function destroy(Company $subsidiary): RedirectResponse|JsonResponse
    {
        if (!$this->canManageSubsidiary(Auth::user(), $subsidiary)) {
            abort(403, 'You do not have permission to delete this subsidiary.');
        }

        try {
            $this->subsidiaryService->removeSubsidiary($subsidiary);

            Log::info('Subsidiary removed from hierarchy', [
                'subsidiary_id' => $subsidiary->id,
                'subsidiary_name' => $subsidiary->name,
                'removed_by' => Auth::id(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Subsidiary removed from hierarchy successfully.',
                ]);
            }

            return redirect()
                ->route('subsidiaries.index')
                ->with('success', 'Subsidiary removed from hierarchy successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to remove subsidiary', [
                'subsidiary_id' => $subsidiary->id,
                'error' => $e->getMessage(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove subsidiary: ' . $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to remove subsidiary: ' . $e->getMessage()]);
        }
    }

    /**
     * Get hierarchy tree data for visualization.
     */
    public function hierarchyTree(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $request->get('company_id', $user->company_id);
        
        $company = Company::findOrFail($companyId);
        
        if (!$user->company->canAccessCompany($companyId)) {
            abort(403, 'Access denied to company hierarchy.');
        }

        $tree = $company->getHierarchyTree();

        return response()->json([
            'tree' => $tree,
            'company' => $company,
        ]);
    }

    /**
     * Manage permissions for subsidiaries.
     */
    public function permissions(Request $request, Company $subsidiary): View|JsonResponse
    {
        if (!$this->canManageSubsidiary(Auth::user(), $subsidiary)) {
            abort(403, 'You do not have permission to manage permissions for this subsidiary.');
        }

        $grantedPermissions = $subsidiary->grantedPermissions()
            ->with(['granteeCompany', 'user'])
            ->active()
            ->paginate(20);

        $receivedPermissions = $subsidiary->receivedPermissions()
            ->with(['granterCompany', 'user'])
            ->active()
            ->paginate(20);

        if ($request->expectsJson()) {
            return response()->json([
                'subsidiary' => $subsidiary,
                'granted_permissions' => $grantedPermissions,
                'received_permissions' => $receivedPermissions,
            ]);
        }

        return view('subsidiaries.permissions', compact(
            'subsidiary',
            'grantedPermissions',
            'receivedPermissions'
        ));
    }

    /**
     * Grant permission to a subsidiary.
     */
    public function grantPermission(Request $request): JsonResponse
    {
        $request->validate([
            'grantee_company_id' => 'required|exists:companies,id',
            'resource_type' => 'required|string',
            'permission_type' => 'required|in:view,create,edit,delete,manage',
            'scope' => 'required|in:all,specific,filtered',
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $permission = $this->permissionService->grantPermission([
                'granter_company_id' => Auth::user()->company_id,
                'grantee_company_id' => $request->grantee_company_id,
                'resource_type' => $request->resource_type,
                'permission_type' => $request->permission_type,
                'scope' => $request->scope,
                'expires_at' => $request->expires_at,
                'notes' => $request->notes,
                'is_active' => true,
                'can_delegate' => $request->boolean('can_delegate', false),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission granted successfully.',
                'permission' => $permission,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to grant permission: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Revoke a permission.
     */
    public function revokePermission(SubsidiaryPermission $permission): JsonResponse
    {
        if ($permission->granter_company_id !== Auth::user()->company_id) {
            abort(403, 'You can only revoke permissions granted by your company.');
        }

        try {
            $permission->revoke();

            return response()->json([
                'success' => true,
                'message' => 'Permission revoked successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke permission: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Manage cross-company users.
     */
    public function users(Request $request, Company $subsidiary): View|JsonResponse
    {
        if (!$this->canManageSubsidiary(Auth::user(), $subsidiary)) {
            abort(403, 'You do not have permission to manage users for this subsidiary.');
        }

        $crossCompanyUsers = $subsidiary->crossCompanyUsers()
            ->with(['user', 'primaryCompany', 'authorizedByUser'])
            ->active()
            ->paginate(20);

        $availableUsers = User::where('company_id', Auth::user()->company_id)
            ->whereNotIn('id', $crossCompanyUsers->pluck('user_id'))
            ->get(['id', 'name', 'email']);

        if ($request->expectsJson()) {
            return response()->json([
                'subsidiary' => $subsidiary,
                'cross_company_users' => $crossCompanyUsers,
                'available_users' => $availableUsers,
            ]);
        }

        return view('subsidiaries.users', compact(
            'subsidiary',
            'crossCompanyUsers',
            'availableUsers'
        ));
    }

    /**
     * Grant cross-company access to a user.
     */
    public function grantUserAccess(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'company_id' => 'required|exists:companies,id',
            'role_in_company' => 'required|integer|in:1,2,3,6,7',
            'access_type' => 'required|in:full,limited,view_only',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            $crossCompanyUser = CrossCompanyUser::grantAccess([
                'user_id' => $request->user_id,
                'company_id' => $request->company_id,
                'primary_company_id' => Auth::user()->company_id,
                'role_in_company' => $request->role_in_company,
                'access_type' => $request->access_type,
                'authorized_by' => Auth::id(),
                'access_expires_at' => $request->expires_at,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User access granted successfully.',
                'cross_company_user' => $crossCompanyUser,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to grant user access: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Revoke cross-company user access.
     */
    public function revokeUserAccess(CrossCompanyUser $crossCompanyUser): JsonResponse
    {
        if ($crossCompanyUser->authorized_by !== Auth::id() && 
            !Auth::user()->settings?->isSuperAdmin()) {
            abort(403, 'You can only revoke access you authorized.');
        }

        try {
            $crossCompanyUser->revokeAccess();

            return response()->json([
                'success' => true,
                'message' => 'User access revoked successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke user access: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get recent subsidiary activity.
     */
    protected function getRecentSubsidiaryActivity(int $companyId): array
    {
        // This would typically pull from an activity log
        // For now, return basic information
        $recentSubsidiaries = CompanyHierarchy::where('ancestor_id', $companyId)
            ->with('descendant')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $recentSubsidiaries->map(function ($hierarchy) {
            return [
                'type' => 'subsidiary_created',
                'company' => $hierarchy->descendant,
                'created_at' => $hierarchy->created_at,
            ];
        })->toArray();
    }

    /**
     * Check if user can manage a specific subsidiary.
     */
    protected function canManageSubsidiary(User $user, Company $subsidiary): bool
    {
        // User must have subsidiary management permissions
        if (!$user->settings?->canManageSubsidiaries()) {
            return false;
        }

        // Subsidiary must be a descendant of user's company
        return CompanyHierarchy::isDescendant($subsidiary->id, $user->company_id);
    }
}