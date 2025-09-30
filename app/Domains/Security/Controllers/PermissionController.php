<?php

namespace App\Domains\Security\Controllers;

use App\Domains\Security\Services\RoleService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Database\Role;

class PermissionController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Display the permissions management dashboard
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Role::class);

        $user = Auth::user();

        // Get company users with their roles
        $users = User::where('company_id', $user->company_id)
            ->with(['roles', 'abilities'])
            ->orderBy('name')
            ->paginate(20);

        // Get all roles with their abilities
        $roles = Bouncer::role()->with(['abilities', 'users'])->get();

        // Get all abilities grouped by category
        $abilitiesByCategory = $this->getAbilitiesByCategory();

        // Get permission statistics
        $stats = [
            'total_users' => User::where('company_id', $user->company_id)->count(),
            'total_roles' => $roles->count(),
            'total_abilities' => Bouncer::ability()->count(),
            'users_without_roles' => User::where('company_id', $user->company_id)
                ->whereDoesntHave('roles')
                ->count(),
        ];

        // Get recent permission changes
        $recentChanges = $this->getRecentPermissionChanges($user->company_id);

        if ($request->wantsJson()) {
            return response()->json([
                'users' => $users,
                'roles' => $roles,
                'abilities' => $abilitiesByCategory,
                'stats' => $stats,
                'recentChanges' => $recentChanges,
            ]);
        }

        return view('settings.permissions.index', compact(
            'users',
            'roles',
            'abilitiesByCategory',
            'stats',
            'recentChanges'
        ));
    }

    /**
     * Show user permissions management
     */
    public function userPermissions(Request $request, User $user)
    {
        $this->authorize('update', $user);

        // Ensure user is from same company
        if ($user->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to user from different company');
        }

        // Set Bouncer scope to user's company
        Bouncer::scope()->to($user->company_id);

        // Get user's current roles and abilities
        $userRoles = $user->roles->pluck('name')->toArray();
        $userAbilities = $user->abilities->pluck('name')->toArray();

        // Get all available roles
        $availableRoles = Bouncer::role()->get();

        // Get all abilities grouped by category
        $abilitiesByCategory = $this->getAbilitiesByCategory();

        // Get effective permissions (including those from roles)
        $effectivePermissions = $this->getUserEffectivePermissions($user);

        if ($request->wantsJson()) {
            return response()->json([
                'user' => $user->load(['roles', 'abilities']),
                'userRoles' => $userRoles,
                'userAbilities' => $userAbilities,
                'availableRoles' => $availableRoles,
                'abilitiesByCategory' => $abilitiesByCategory,
                'effectivePermissions' => $effectivePermissions,
            ]);
        }

        return view('settings.permissions.user', compact(
            'user',
            'userRoles',
            'userAbilities',
            'availableRoles',
            'abilitiesByCategory',
            'effectivePermissions'
        ));
    }

    /**
     * Update user permissions
     */
    public function updateUserPermissions(Request $request, User $user)
    {
        $this->authorize('update', $user);

        // Ensure user is from same company
        if ($user->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to user from different company');
        }

        $request->validate([
            'roles' => 'array',
            'roles.*' => 'string|exists:bouncer_roles,name',
            'abilities' => 'array',
            'abilities.*' => 'string|exists:bouncer_abilities,name',
        ]);

        DB::beginTransaction();

        try {
            // Set Bouncer scope to user's company
            Bouncer::scope()->to($user->company_id);

            // Update roles
            $currentRoles = $user->roles->pluck('name')->toArray();
            $newRoles = $request->roles ?? [];

            // Remove roles that are no longer assigned
            foreach (array_diff($currentRoles, $newRoles) as $roleToRemove) {
                Bouncer::retract($roleToRemove)->from($user);
            }

            // Add new roles
            foreach (array_diff($newRoles, $currentRoles) as $roleToAdd) {
                Bouncer::assign($roleToAdd)->to($user);
            }

            // Update direct abilities (not from roles)
            $currentAbilities = $user->abilities->pluck('name')->toArray();
            $newAbilities = $request->abilities ?? [];

            // Remove abilities that are no longer assigned
            foreach (array_diff($currentAbilities, $newAbilities) as $abilityToRemove) {
                Bouncer::disallow($user)->to($abilityToRemove);
            }

            // Add new abilities
            foreach (array_diff($newAbilities, $currentAbilities) as $abilityToAdd) {
                Bouncer::allow($user)->to($abilityToAdd);
            }

            DB::commit();

            // Log the permission change
            Log::info('User permissions updated', [
                'user_id' => $user->id,
                'updated_by' => Auth::id(),
                'roles_added' => array_diff($newRoles, $currentRoles),
                'roles_removed' => array_diff($currentRoles, $newRoles),
                'abilities_added' => array_diff($newAbilities, $currentAbilities),
                'abilities_removed' => array_diff($currentAbilities, $newAbilities),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User permissions updated successfully',
                    'user' => $user->fresh()->load(['roles', 'abilities']),
                ]);
            }

            return redirect()
                ->route('settings.permissions.user', $user)
                ->with('success', "Permissions for <strong>{$user->name}</strong> updated successfully");

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to update user permissions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user permissions',
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to update permissions: '.$e->getMessage());
        }
    }

    /**
     * Bulk assign permissions to multiple users
     */
    public function bulkAssign(Request $request)
    {
        $this->authorize('create', \App\Models\Role::class);

        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'action' => 'required|in:add_role,remove_role,add_ability,remove_ability',
            'role' => 'required_if:action,add_role,remove_role|exists:bouncer_roles,name',
            'ability' => 'required_if:action,add_ability,remove_ability|exists:bouncer_abilities,name',
        ]);

        $companyId = Auth::user()->company_id;

        // Verify all users belong to the same company
        $users = User::whereIn('id', $request->user_ids)
            ->where('company_id', $companyId)
            ->get();

        if ($users->count() !== count($request->user_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some users do not belong to your company',
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Set Bouncer scope to company
            Bouncer::scope()->to($companyId);

            foreach ($users as $user) {
                switch ($request->action) {
                    case 'add_role':
                        Bouncer::assign($request->role)->to($user);
                        break;
                    case 'remove_role':
                        Bouncer::retract($request->role)->from($user);
                        break;
                    case 'add_ability':
                        Bouncer::allow($user)->to($request->ability);
                        break;
                    case 'remove_ability':
                        Bouncer::disallow($user)->to($request->ability);
                        break;
                }
            }

            DB::commit();

            Log::info('Bulk permission assignment completed', [
                'action' => $request->action,
                'user_count' => $users->count(),
                'role' => $request->role,
                'ability' => $request->ability,
                'updated_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Permissions updated for {$users->count()} users",
                ]);
            }

            return redirect()
                ->route('settings.permissions.index')
                ->with('success', "Permissions updated for <strong>{$users->count()}</strong> users");

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Bulk permission assignment failed', [
                'error' => $e->getMessage(),
                'updated_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update permissions',
                ], 500);
            }

            return back()->with('error', 'Failed to update permissions: '.$e->getMessage());
        }
    }

    /**
     * Show permission matrix view
     */
    public function matrix(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Role::class);

        $companyId = Auth::user()->company_id;

        // Get all roles with their abilities
        $roles = Bouncer::role()->with('abilities')->get();

        // Get all abilities grouped by category
        $abilitiesByCategory = $this->getAbilitiesByCategory();

        // Create permission matrix
        $matrix = [];
        foreach ($abilitiesByCategory as $category => $abilities) {
            $matrix[$category] = [];
            foreach ($abilities as $ability) {
                $matrix[$category][$ability['name']] = [
                    'title' => $ability['title'],
                    'roles' => [],
                ];
                foreach ($roles as $role) {
                    $hasAbility = $role->abilities->contains('name', $ability['name']);
                    $matrix[$category][$ability['name']]['roles'][$role->name] = $hasAbility;
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'matrix' => $matrix,
                'roles' => $roles,
            ]);
        }

        return view('settings.permissions.matrix', compact('matrix', 'roles'));
    }

    /**
     * Update permission matrix (toggle permission for role)
     */
    public function updateMatrix(Request $request)
    {
        $this->authorize('update', \App\Models\Role::class);

        $request->validate([
            'role' => 'required|exists:bouncer_roles,name',
            'ability' => 'required|exists:bouncer_abilities,name',
            'granted' => 'required|boolean',
        ]);

        // Prevent editing system-critical roles
        if (in_array($request->role, ['super-admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify super-admin permissions',
            ], 403);
        }

        DB::beginTransaction();

        try {
            if ($request->granted) {
                Bouncer::allow($request->role)->to($request->ability);
            } else {
                Bouncer::disallow($request->role)->to($request->ability);
            }

            DB::commit();

            Log::info('Permission matrix updated', [
                'role' => $request->role,
                'ability' => $request->ability,
                'granted' => $request->granted,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Permission matrix update failed', [
                'error' => $e->getMessage(),
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission',
            ], 500);
        }
    }

    /**
     * Export permissions configuration
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Role::class);

        $roles = Bouncer::role()->with('abilities')->get();
        $abilities = Bouncer::ability()->get();

        $export = [
            'roles' => $roles->map(function ($role) {
                return [
                    'name' => $role->name,
                    'title' => $role->title,
                    'description' => $role->description,
                    'abilities' => $role->abilities->pluck('name')->toArray(),
                ];
            }),
            'abilities' => $abilities->map(function ($ability) {
                return [
                    'name' => $ability->name,
                    'title' => $ability->title,
                ];
            }),
            'exported_at' => now()->toIso8601String(),
            'exported_by' => Auth::user()->email,
        ];

        return response()->json($export, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="permissions-export-'.now()->format('Y-m-d').'.json"',
        ]);
    }

    /**
     * Import permissions configuration
     */
    public function import(Request $request)
    {
        $this->authorize('create', \App\Models\Role::class);

        $request->validate([
            'file' => 'required|file|mimes:json|max:2048',
        ]);

        try {
            $content = json_decode($request->file('file')->get(), true);

            if (! isset($content['roles']) || ! isset($content['abilities'])) {
                throw new \Exception('Invalid import file format');
            }

            DB::beginTransaction();

            // Import abilities first
            foreach ($content['abilities'] as $abilityData) {
                Bouncer::ability()->firstOrCreate(
                    ['name' => $abilityData['name']],
                    ['title' => $abilityData['title'] ?? null]
                );
            }

            // Import roles and their abilities
            foreach ($content['roles'] as $roleData) {
                // Skip system roles
                if (in_array($roleData['name'], ['super-admin', 'admin'])) {
                    continue;
                }

                $role = Bouncer::role()->firstOrCreate(
                    ['name' => $roleData['name']],
                    [
                        'title' => $roleData['title'],
                        'description' => $roleData['description'] ?? null,
                    ]
                );

                // Sync abilities
                foreach ($roleData['abilities'] as $abilityName) {
                    if (Bouncer::ability()->where('name', $abilityName)->exists()) {
                        Bouncer::allow($role->name)->to($abilityName);
                    }
                }
            }

            DB::commit();

            Log::info('Permissions configuration imported', [
                'roles_count' => count($content['roles']),
                'abilities_count' => count($content['abilities']),
                'imported_by' => Auth::id(),
            ]);

            return redirect()
                ->route('settings.permissions.index')
                ->with('success', 'Permissions configuration imported successfully');

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Permission import failed', [
                'error' => $e->getMessage(),
                'imported_by' => Auth::id(),
            ]);

            return back()->with('error', 'Failed to import permissions: '.$e->getMessage());
        }
    }

    /**
     * Get abilities grouped by category
     */
    private function getAbilitiesByCategory(): array
    {
        $abilities = Bouncer::ability()->orderBy('name')->get();
        $categorized = [];

        foreach ($abilities as $ability) {
            $parts = explode('.', $ability->name);
            $category = ucfirst($parts[0]);

            if (! isset($categorized[$category])) {
                $categorized[$category] = [];
            }

            $categorized[$category][] = [
                'name' => $ability->name,
                'title' => $ability->title ?? $this->generateAbilityTitle($ability->name),
                'description' => $this->generateAbilityDescription($ability->name),
            ];
        }

        return $categorized;
    }

    /**
     * Generate human-readable title from ability name
     */
    private function generateAbilityTitle(string $abilityName): string
    {
        $parts = explode('.', $abilityName);

        $formatted = array_map(function ($part) {
            return ucwords(str_replace(['_', '-'], ' ', $part));
        }, $parts);

        return implode(' - ', $formatted);
    }

    /**
     * Generate description for ability
     */
    private function generateAbilityDescription(string $abilityName): string
    {
        $descriptions = [
            'view' => 'View and list',
            'create' => 'Create new',
            'edit' => 'Edit existing',
            'delete' => 'Delete',
            'manage' => 'Full management access',
            'export' => 'Export data',
            'import' => 'Import data',
        ];

        $parts = explode('.', $abilityName);
        $action = end($parts);
        $resource = ucfirst($parts[0]);

        return ($descriptions[$action] ?? ucfirst($action)).' '.$resource;
    }

    /**
     * Get user's effective permissions (including from roles)
     */
    private function getUserEffectivePermissions(User $user): array
    {
        $permissions = [];

        // Get permissions from roles
        foreach ($user->roles as $role) {
            foreach ($role->abilities as $ability) {
                $permissions[$ability->name] = [
                    'source' => 'role',
                    'role' => $role->name,
                ];
            }
        }

        // Get direct permissions (override role permissions)
        foreach ($user->abilities as $ability) {
            $permissions[$ability->name] = [
                'source' => 'direct',
                'role' => null,
            ];
        }

        return $permissions;
    }

    /**
     * Get recent permission changes for audit trail
     */
    private function getRecentPermissionChanges($companyId, $limit = 10): array
    {
        // This would typically query an audit log table
        // For now, returning empty array as placeholder
        return [];
    }
}
