<?php

namespace App\Domains\Security\Services;

use Silber\Bouncer\BouncerFacade as Bouncer;
use Illuminate\Support\Collection;
use App\Models\User;

/**
 * Enhanced Permission Service for Advanced Permission Management
 * 
 * Provides wildcard matching, permission inheritance, and granular access control
 */
class PermissionService
{
    /**
     * Check if user has permission (with wildcard support)
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        // Direct permission check
        if ($user->can($permission)) {
            return true;
        }
        
        // Wildcard permission check
        return $this->hasWildcardPermission($user, $permission);
    }
    
    /**
     * Check wildcard permissions
     * Example: 'assets.*' matches 'assets.view', 'assets.edit', etc.
     */
    private function hasWildcardPermission(User $user, string $permission): bool
    {
        $parts = explode('.', $permission);
        $wildcardChecks = [];
        
        // Build wildcard patterns to check
        // For 'assets.equipment.view', check:
        // - '*' (full wildcard)
        // - 'assets.*' 
        // - 'assets.equipment.*'
        for ($i = 0; $i < count($parts); $i++) {
            $wildcard = implode('.', array_slice($parts, 0, $i)) . ($i > 0 ? '.' : '') . '*';
            $wildcardChecks[] = $wildcard;
        }
        
        // Check each wildcard pattern
        foreach ($wildcardChecks as $pattern) {
            if ($user->can($pattern)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Grant permissions with wildcard expansion
     */
    public function grantPermission($role, string $permission): void
    {
        if (str_contains($permission, '*')) {
            // Store the wildcard permission
            Bouncer::allow($role)->to($permission);
            
            // Also expand common sub-permissions for better UI display
            $this->expandWildcardPermissions($role, $permission);
        } else {
            Bouncer::allow($role)->to($permission);
        }
    }
    
    /**
     * Expand wildcard permissions for UI display
     */
    private function expandWildcardPermissions($role, string $wildcardPermission): void
    {
        $basePermission = str_replace('.*', '', $wildcardPermission);
        
        // Define common sub-permissions for each module
        $expansions = [
            'assets' => ['view', 'create', 'edit', 'delete', 'export', 'import'],
            'tickets' => ['view', 'create', 'edit', 'delete', 'assign', 'close'],
            'clients' => ['view', 'create', 'edit', 'delete', 'credentials', 'contracts'],
            'financial' => ['view', 'create', 'edit', 'delete', 'approve', 'reports'],
            'projects' => ['view', 'create', 'edit', 'delete', 'tasks', 'timeline'],
            'reports' => ['view', 'create', 'export', 'schedule'],
        ];
        
        if (isset($expansions[$basePermission])) {
            foreach ($expansions[$basePermission] as $action) {
                Bouncer::allow($role)->to("{$basePermission}.{$action}");
            }
        }
    }
    
    /**
     * Get hierarchical permission structure for UI
     */
    public function getPermissionHierarchy(): array
    {
        return [
            'assets' => [
                'title' => 'Asset Management',
                'permissions' => [
                    'assets.*' => 'Full asset management access',
                    'assets.view' => 'View assets',
                    'assets.create' => 'Create assets',
                    'assets.edit' => 'Edit assets',
                    'assets.delete' => 'Delete assets',
                    'assets.export' => 'Export asset data',
                    'assets.import' => 'Import asset data',
                    'assets.equipment.*' => 'Manage equipment',
                    'assets.software.*' => 'Manage software licenses',
                    'assets.warranties.*' => 'Manage warranties',
                ],
            ],
            'tickets' => [
                'title' => 'Ticket Management',
                'permissions' => [
                    'tickets.*' => 'Full ticket management access',
                    'tickets.view' => 'View tickets',
                    'tickets.create' => 'Create tickets',
                    'tickets.edit' => 'Edit tickets',
                    'tickets.delete' => 'Delete tickets',
                    'tickets.assign' => 'Assign tickets to technicians',
                    'tickets.close' => 'Close tickets',
                    'tickets.priority.*' => 'Manage all priority tickets',
                    'tickets.sla.*' => 'Manage SLA compliance',
                ],
            ],
            'clients' => [
                'title' => 'Client Management',
                'permissions' => [
                    'clients.*' => 'Full client management access',
                    'clients.view' => 'View clients',
                    'clients.create' => 'Create clients',
                    'clients.edit' => 'Edit clients',
                    'clients.delete' => 'Delete clients',
                    'clients.credentials.*' => 'Manage client credentials',
                    'clients.contracts.*' => 'Manage client contracts',
                    'clients.contacts.*' => 'Manage client contacts',
                    'clients.locations.*' => 'Manage client locations',
                    'clients.networks.*' => 'Manage client networks',
                ],
            ],
            'financial' => [
                'title' => 'Financial Management',
                'permissions' => [
                    'financial.*' => 'Full financial access',
                    'financial.invoices.*' => 'Manage invoices',
                    'financial.payments.*' => 'Manage payments',
                    'financial.quotes.*' => 'Manage quotes',
                    'financial.expenses.*' => 'Manage expenses',
                    'financial.reports.*' => 'View financial reports',
                    'financial.approve' => 'Approve financial transactions',
                ],
            ],
            'users' => [
                'title' => 'User Management',
                'permissions' => [
                    'users.*' => 'Full user management access',
                    'users.view' => 'View users',
                    'users.create' => 'Create users',
                    'users.edit' => 'Edit users',
                    'users.delete' => 'Delete users',
                    'users.roles.*' => 'Manage user roles',
                    'users.permissions.*' => 'Manage user permissions',
                ],
            ],
            'settings' => [
                'title' => 'System Settings',
                'permissions' => [
                    'settings.*' => 'Full settings access',
                    'settings.company.*' => 'Manage company settings',
                    'settings.integrations.*' => 'Manage integrations',
                    'settings.security.*' => 'Manage security settings',
                    'settings.billing.*' => 'Manage billing settings',
                ],
            ],
        ];
    }
    
    /**
     * Check resource-level permissions
     * Example: Can user edit this specific asset?
     */
    public function canAccessResource(User $user, string $permission, $resource): bool
    {
        // Check general permission first
        if (!$this->userHasPermission($user, $permission)) {
            return false;
        }
        
        // Check resource-specific constraints
        // Example: User can only edit assets for their assigned clients
        if (str_starts_with($permission, 'assets.') && $resource) {
            return $this->checkAssetAccess($user, $resource);
        }
        
        if (str_starts_with($permission, 'tickets.') && $resource) {
            return $this->checkTicketAccess($user, $resource);
        }
        
        return true;
    }
    
    /**
     * Check asset-specific access
     */
    private function checkAssetAccess(User $user, $asset): bool
    {
        // Super admins and admins have full access
        if ($user->isA('super-admin') || $user->isA('admin')) {
            return true;
        }
        
        // Check if user is assigned to the client that owns this asset
        if ($asset->client_id) {
            return $user->isAssignedToClient($asset->client_id);
        }
        
        // Check if asset belongs to user's company
        return $asset->company_id === $user->company_id;
    }
    
    /**
     * Check ticket-specific access
     */
    private function checkTicketAccess(User $user, $ticket): bool
    {
        // Super admins and admins have full access
        if ($user->isA('super-admin') || $user->isA('admin')) {
            return true;
        }
        
        // User created the ticket
        if ($ticket->created_by === $user->id) {
            return true;
        }
        
        // User is assigned to the ticket
        if ($ticket->assigned_to === $user->id) {
            return true;
        }
        
        // User is assigned to the client with appropriate access level
        if ($ticket->client_id) {
            $accessLevel = $user->getClientAccessLevel($ticket->client_id);
            return $accessLevel !== null; // Any level of access allows viewing
        }
        
        return false;
    }
    
    /**
     * Get effective permissions for a user (including wildcards)
     */
    public function getEffectivePermissions(User $user): Collection
    {
        $permissions = collect();
        
        // Get all direct permissions
        foreach ($user->getAbilities() as $ability) {
            $permissions->push($ability->name);
            
            // If it's a wildcard, expand it
            if (str_contains($ability->name, '*')) {
                $expanded = $this->expandWildcard($ability->name);
                $permissions = $permissions->merge($expanded);
            }
        }
        
        // Get role-based permissions
        foreach ($user->roles as $role) {
            foreach ($role->getAbilities() as $ability) {
                $permissions->push($ability->name);
                
                // If it's a wildcard, expand it
                if (str_contains($ability->name, '*')) {
                    $expanded = $this->expandWildcard($ability->name);
                    $permissions = $permissions->merge($expanded);
                }
            }
        }
        
        return $permissions->unique()->sort();
    }
    
    /**
     * Expand a wildcard permission to its components
     */
    private function expandWildcard(string $wildcard): array
    {
        $base = str_replace('.*', '', $wildcard);
        $hierarchy = $this->getPermissionHierarchy();
        
        if ($wildcard === '*') {
            // Full system access
            $expanded = [];
            foreach ($hierarchy as $module => $config) {
                foreach ($config['permissions'] as $perm => $desc) {
                    if (!str_contains($perm, '*')) {
                        $expanded[] = $perm;
                    }
                }
            }
            return $expanded;
        }
        
        if (isset($hierarchy[$base])) {
            $expanded = [];
            foreach ($hierarchy[$base]['permissions'] as $perm => $desc) {
                if (!str_contains($perm, '*')) {
                    $expanded[] = $perm;
                }
            }
            return $expanded;
        }
        
        return [];
    }
}