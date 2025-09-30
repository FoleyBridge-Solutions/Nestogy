<?php

namespace App\Domains\Security\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Domains\Security\Services\RoleService;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Illuminate\Validation\Rule;

/**
 * RoleController
 * 
 * Manages roles and permissions for the application. Provides CRUD operations
 * for roles, permission assignment, and role templates for MSP-specific workflows.
 */
class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Display a listing of roles with their permissions
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Role::class);

        $user = Auth::user();
        
        // Get all Bouncer roles
        $allRoles = Bouncer::role()->with(['abilities'])->get();
        
        // Filter roles based on user's role hierarchy
        $roles = $this->filterRolesByHierarchy($allRoles, $user);
        
        // Get role statistics for current company
        $roleStats = $this->roleService->getRoleStats($user->company_id);
        
        // Get all available abilities grouped by category
        $abilitiesByCategory = $this->getAbilitiesByCategory();

        if ($request->wantsJson()) {
            return response()->json([
                'roles' => $roles,
                'statistics' => $roleStats,
                'abilities' => $abilitiesByCategory,
            ]);
        }

        return view('settings.roles.index', compact('roles', 'roleStats', 'abilitiesByCategory'));
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        $this->authorize('create', \App\Models\Role::class);
        
        $abilitiesByCategory = $this->getAbilitiesByCategory();
        $roleTemplates = $this->getMspRoleTemplates();

        return view('settings.roles.create', compact('abilitiesByCategory', 'roleTemplates'));
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $this->authorize('create', \App\Models\Role::class);

        $request->validate([
            'name' => 'required|string|max:255|unique:bouncer_roles,name',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'abilities' => 'array',
            'abilities.*' => 'string|exists:bouncer_abilities,name',
        ]);

        DB::beginTransaction();
        
        try {
            // Create the role
            $role = Bouncer::role()->create([
                'name' => $request->name,
                'title' => $request->title,
                'description' => $request->description,
            ]);

            // Assign abilities to the role
            if ($request->has('abilities')) {
                foreach ($request->abilities as $abilityName) {
                    Bouncer::allow($role->name)->to($abilityName);
                }
            }

            DB::commit();

            Log::info('Role created', [
                'role_name' => $role->name,
                'abilities_count' => count($request->abilities ?? []),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully',
                    'role' => $role->load('abilities'),
                ], 201);
            }

            return redirect()
                ->route('settings.roles.index')
                ->with('success', "Role <strong>{$role->title}</strong> created successfully");

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Role creation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create role',
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified role
     */
    public function show(string $roleName)
    {
        $this->authorize('view', \App\Models\Role::class);

        $role = Bouncer::role()->where('name', $roleName)->with('abilities')->firstOrFail();
        
        // Get users with this role in current company
        $user = Auth::user();
        $usersWithRole = collect();
        
        // This would need to be implemented based on your user-role relationship structure
        // For now, we'll get role statistics instead
        $roleStats = $this->roleService->getRoleStats($user->company_id);
        
        return view('settings.roles.show', compact('role', 'roleStats'));
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(string $roleName)
    {
        $this->authorize('update', \App\Models\Role::class);

        $role = Bouncer::role()->where('name', $roleName)->with('abilities')->firstOrFail();
        
        // Check role hierarchy - users can't edit roles above their level
        $user = Auth::user();
        if (!$this->canManageRole($user, $role)) {
            return back()->with('error', 'You do not have permission to edit this role');
        }
        
        // Prevent editing certain system-critical roles
        if (in_array($role->name, ['super-admin']) && !$user->isA('super-admin')) {
            return back()->with('error', 'Only Super Administrators can edit the Super Admin role');
        }

        $abilitiesByCategory = $this->getAbilitiesByCategory();
        $roleAbilities = $role->abilities->pluck('name')->toArray();

        return view('settings.roles.edit', compact('role', 'abilitiesByCategory', 'roleAbilities'));
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, string $roleName)
    {
        $this->authorize('update', \App\Models\Role::class);

        $role = Bouncer::role()->where('name', $roleName)->firstOrFail();

        // Check role hierarchy - users can't edit roles above their level
        $user = Auth::user();
        if (!$this->canManageRole($user, $role)) {
            return back()->with('error', 'You do not have permission to edit this role');
        }
        
        // Prevent editing certain system-critical roles
        if (in_array($role->name, ['super-admin']) && !$user->isA('super-admin')) {
            return back()->with('error', 'Only Super Administrators can edit the Super Admin role');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'abilities' => 'array',
            'abilities.*' => 'string|exists:bouncer_abilities,name',
        ]);

        DB::beginTransaction();
        
        try {
            // Update role details
            $role->update([
                'title' => $request->title,
                'description' => $request->description,
            ]);

            // Remove all current abilities
            $currentAbilities = $role->abilities->pluck('name')->toArray();
            foreach ($currentAbilities as $abilityName) {
                Bouncer::disallow($role->name)->to($abilityName);
            }

            // Assign new abilities
            if ($request->has('abilities')) {
                foreach ($request->abilities as $abilityName) {
                    Bouncer::allow($role->name)->to($abilityName);
                }
            }

            DB::commit();

            Log::info('Role updated', [
                'role_name' => $role->name,
                'old_abilities_count' => count($currentAbilities),
                'new_abilities_count' => count($request->abilities ?? []),
                'updated_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role updated successfully',
                    'role' => $role->fresh()->load('abilities'),
                ]);
            }

            return redirect()
                ->route('settings.roles.index')
                ->with('success', "Role <strong>{$role->title}</strong> updated successfully");

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Role update failed', [
                'role_name' => $role->name,
                'error' => $e->getMessage(),
                'updated_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update role',
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy(Request $request, string $roleName)
    {
        $this->authorize('delete', \App\Models\Role::class);

        $role = Bouncer::role()->where('name', $roleName)->firstOrFail();

        // Check role hierarchy - users can't delete roles above their level
        $user = Auth::user();
        if (!$this->canManageRole($user, $role)) {
            return back()->with('error', 'You do not have permission to delete this role');
        }

        // Prevent deleting system-critical roles
        if (in_array($role->name, ['super-admin', 'admin', 'technician', 'accountant'])) {
            return back()->with('error', 'System roles cannot be deleted');
        }

        DB::beginTransaction();
        
        try {
            $roleName = $role->name;
            $roleTitle = $role->title;

            // Remove role (this will also remove all ability assignments)
            $role->delete();

            DB::commit();

            Log::warning('Role deleted', [
                'role_name' => $roleName,
                'role_title' => $roleTitle,
                'deleted_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role deleted successfully',
                ]);
            }

            return redirect()
                ->route('settings.roles.index')
                ->with('success', "Role <strong>{$roleTitle}</strong> deleted successfully");

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Role deletion failed', [
                'role_name' => $role->name,
                'error' => $e->getMessage(),
                'deleted_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete role',
                ], 500);
            }

            return back()->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate an existing role
     */
    public function duplicate(Request $request, string $roleName)
    {
        $this->authorize('create', \App\Models\Role::class);

        $originalRole = Bouncer::role()->where('name', $roleName)->with('abilities')->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255|unique:bouncer_roles,name',
            'title' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        
        try {
            // Create new role
            $newRole = Bouncer::role()->create([
                'name' => $request->name,
                'title' => $request->title,
                'description' => "Copy of {$originalRole->title}",
            ]);

            // Copy all abilities from original role
            foreach ($originalRole->abilities as $ability) {
                Bouncer::allow($newRole->name)->to($ability->name);
            }

            DB::commit();

            Log::info('Role duplicated', [
                'original_role' => $originalRole->name,
                'new_role' => $newRole->name,
                'abilities_copied' => $originalRole->abilities->count(),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role duplicated successfully',
                    'role' => $newRole->load('abilities'),
                ]);
            }

            return redirect()
                ->route('settings.roles.edit', $newRole->name)
                ->with('success', "Role <strong>{$newRole->title}</strong> created as copy of {$originalRole->title}");

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Role duplication failed', [
                'original_role' => $originalRole->name,
                'error' => $e->getMessage(),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to duplicate role',
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to duplicate role: ' . $e->getMessage());
        }
    }

    /**
     * Apply a role template (preset roles for MSP workflows)
     */
    public function applyTemplate(Request $request)
    {
        $this->authorize('create', \App\Models\Role::class);

        $request->validate([
            'template' => 'required|string|in:help-desk,field-tech,network-admin,security-specialist,project-manager,client-manager,billing-admin',
            'name' => 'required|string|max:255|unique:bouncer_roles,name',
            'title' => 'required|string|max:255',
        ]);

        $templates = $this->getMspRoleTemplates();
        $template = $templates[$request->template];

        DB::beginTransaction();
        
        try {
            // Create role
            $role = Bouncer::role()->create([
                'name' => $request->name,
                'title' => $request->title,
                'description' => $template['description'],
            ]);

            // Assign template abilities
            foreach ($template['abilities'] as $abilityName) {
                if (Bouncer::ability()->where('name', $abilityName)->exists()) {
                    Bouncer::allow($role->name)->to($abilityName);
                }
            }

            DB::commit();

            Log::info('Role template applied', [
                'template' => $request->template,
                'role_name' => $role->name,
                'abilities_count' => count($template['abilities']),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role template applied successfully',
                    'role' => $role->load('abilities'),
                ]);
            }

            return redirect()
                ->route('settings.roles.edit', $role->name)
                ->with('success', "Role <strong>{$role->title}</strong> created from {$template['title']} template");

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Role template application failed', [
                'template' => $request->template,
                'error' => $e->getMessage(),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to apply role template',
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to apply role template: ' . $e->getMessage());
        }
    }

    /**
     * Check if a user can manage a specific role based on hierarchy
     */
    private function canManageRole($user, $role): bool
    {
        // Define role hierarchy levels
        $roleHierarchy = [
            'super-admin' => 4,
            'admin' => 3,
            'technician' => 2,
            'accountant' => 2,
            'sales-representative' => 2,
            'marketing-specialist' => 2,
            'user' => 1,
            'client-user' => 1,
        ];
        
        // Get user's role level
        $userRoleLevel = 1;
        if ($user->isA('super-admin')) {
            $userRoleLevel = 4;
        } elseif ($user->isA('admin')) {
            $userRoleLevel = 3;
        } elseif ($user->isA('technician') || $user->isA('accountant') || 
                  $user->isA('sales-representative') || $user->isA('marketing-specialist')) {
            $userRoleLevel = 2;
        }
        
        // Get target role level
        $targetRoleLevel = $roleHierarchy[$role->name] ?? 1;
        
        // Super Admins can manage all roles
        if ($userRoleLevel === 4) {
            return true;
        }
        
        // Users can only manage roles at or below their level, excluding super-admin
        return $role->name !== 'super-admin' && $targetRoleLevel <= $userRoleLevel;
    }

    /**
     * Filter roles based on user's role hierarchy
     * Super Admins see all roles
     * Admins see Admin role and below
     * Regular users only see User role
     */
    private function filterRolesByHierarchy($roles, $user)
    {
        // Define role hierarchy levels
        $roleHierarchy = [
            'super-admin' => 4,
            'admin' => 3,
            'technician' => 2,
            'accountant' => 2,
            'sales-representative' => 2,
            'marketing-specialist' => 2,
            'user' => 1,
            'client-user' => 1,
        ];
        
        // Determine the user's highest role level
        $userRoleLevel = 1; // Default to lowest level
        $userRoleName = null;
        
        // Check if user has super-admin role
        if ($user->isA('super-admin')) {
            $userRoleLevel = 4;
            $userRoleName = 'super-admin';
        } elseif ($user->isA('admin')) {
            $userRoleLevel = 3;
            $userRoleName = 'admin';
        } elseif ($user->isA('technician') || $user->isA('accountant') || 
                  $user->isA('sales-representative') || $user->isA('marketing-specialist')) {
            $userRoleLevel = 2;
            // Find which specific role they have
            foreach (['technician', 'accountant', 'sales-representative', 'marketing-specialist'] as $role) {
                if ($user->isA($role)) {
                    $userRoleName = $role;
                    break;
                }
            }
        } else {
            $userRoleLevel = 1;
            $userRoleName = 'user';
        }
        
        // Filter roles based on hierarchy
        return $roles->filter(function ($role) use ($roleHierarchy, $userRoleLevel) {
            // Get the level of the current role
            $roleLevel = $roleHierarchy[$role->name] ?? 1;
            
            // Only show roles at or below the user's level
            // Exception: Super Admins can see all roles
            if ($userRoleLevel === 4) {
                return true; // Super Admin sees everything
            }
            
            // For non-super admins, hide the super-admin role and only show roles at or below their level
            return $role->name !== 'super-admin' && $roleLevel <= $userRoleLevel;
        })->values(); // Reset array keys
    }

    /**
     * Get abilities grouped by category for UI display
     */
    private function getAbilitiesByCategory(): array
    {
        $abilities = Bouncer::ability()->orderBy('name')->get();
        $categorized = [];

        foreach ($abilities as $ability) {
            $parts = explode('.', $ability->name);
            $category = ucfirst($parts[0]);
            
            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }
            
            $categorized[$category][] = [
                'name' => $ability->name,
                'title' => $ability->title ?? $this->generateAbilityTitle($ability->name),
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
        
        // Convert snake_case to Title Case
        $formatted = array_map(function($part) {
            return ucwords(str_replace(['_', '-'], ' ', $part));
        }, $parts);

        return implode(' - ', $formatted);
    }

    /**
     * Get MSP-specific role templates
     */
    private function getMspRoleTemplates(): array
    {
        return [
            'help-desk' => [
                'title' => 'Help Desk Technician',
                'description' => 'Front-line support role for handling customer inquiries and basic technical issues',
                'abilities' => [
                    'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.manage',
                    'clients.view', 'clients.contacts.view', 'clients.locations.view',
                    'assets.view', 'reports.tickets',
                ],
            ],
            'field-tech' => [
                'title' => 'Field Technician',
                'description' => 'On-site technical support with asset and network management capabilities',
                'abilities' => [
                    'tickets.*', 'assets.*', 'clients.view', 'clients.edit',
                    'clients.contacts.*', 'clients.locations.*', 'clients.credentials.view',
                    'clients.networks.*', 'clients.services.*', 'reports.tickets', 'reports.assets',
                ],
            ],
            'network-admin' => [
                'title' => 'Network Administrator',
                'description' => 'Advanced technical role with network, security, and infrastructure management',
                'abilities' => [
                    'tickets.*', 'assets.*', 'clients.*', 'projects.view', 'projects.tasks.*',
                    'reports.tickets', 'reports.assets', 'reports.projects',
                ],
            ],
            'security-specialist' => [
                'title' => 'Security Specialist',
                'description' => 'Focused on security assessments, compliance, and risk management',
                'abilities' => [
                    'clients.view', 'clients.edit', 'clients.credentials.*', 'clients.certificates.*',
                    'assets.view', 'assets.edit', 'tickets.view', 'tickets.create', 'tickets.edit',
                    'reports.view', 'reports.assets', 'reports.clients',
                ],
            ],
            'project-manager' => [
                'title' => 'Project Manager',
                'description' => 'Manages client projects, timelines, and resource allocation',
                'abilities' => [
                    'projects.*', 'clients.view', 'clients.edit', 'clients.contacts.*',
                    'tickets.view', 'tickets.edit', 'assets.view',
                    'reports.projects', 'reports.clients', 'reports.users',
                ],
            ],
            'client-manager' => [
                'title' => 'Client Relationship Manager',
                'description' => 'Manages client relationships, contracts, and business development',
                'abilities' => [
                    'clients.*', 'contracts.*', 'financial.quotes.*',
                    'reports.clients', 'reports.financial', 'projects.view',
                ],
            ],
            'billing-admin' => [
                'title' => 'Billing Administrator',
                'description' => 'Manages invoicing, payments, and financial reporting',
                'abilities' => [
                    'financial.*', 'clients.view', 'clients.edit', 'clients.contacts.view',
                    'reports.financial', 'reports.clients',
                ],
            ],
        ];
    }
}