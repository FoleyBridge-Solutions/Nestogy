<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * HasPermissions Trait
 * 
 * Provides permission functionality to User model.
 * Supports both role-based and direct permissions with company scoping.
 */
trait HasPermissions
{
    /**
     * Get the roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['company_id'])
            ->withTimestamps();
    }

    /**
     * Get the direct permissions assigned to this user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot(['company_id', 'granted'])
            ->withTimestamps();
    }

    /**
     * Get all permissions for the user (from roles and direct permissions).
     */
    public function getAllPermissions(?int $companyId = null): Collection
    {
        $companyId = $companyId ?? $this->company_id;

        // Get permissions from roles
        $rolePermissions = Permission::whereHas('roles.users', function ($query) use ($companyId) {
            $query->where('users.id', $this->id)
                  ->where('user_roles.company_id', $companyId);
        })->get();

        // Get direct permissions (only granted ones)
        $directPermissions = $this->permissions()
            ->wherePivot('company_id', $companyId)
            ->wherePivot('granted', true)
            ->get();

        // Get denied permissions to exclude them
        $deniedPermissions = $this->permissions()
            ->wherePivot('company_id', $companyId)
            ->wherePivot('granted', false)
            ->pluck('slug')
            ->toArray();

        // Combine and filter out denied permissions
        $allPermissions = $rolePermissions->merge($directPermissions)
            ->unique('id')
            ->reject(function ($permission) use ($deniedPermissions) {
                return in_array($permission->slug, $deniedPermissions);
            });

        return $allPermissions;
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permissionSlug, ?int $companyId = null): bool
    {
        $companyId = $companyId ?? $this->company_id;

        // Check for explicit denial first
        $isDenied = $this->permissions()
            ->where('slug', $permissionSlug)
            ->wherePivot('company_id', $companyId)
            ->wherePivot('granted', false)
            ->exists();

        if ($isDenied) {
            return false;
        }

        // Check direct permission grant
        $hasDirectPermission = $this->permissions()
            ->where('slug', $permissionSlug)
            ->wherePivot('company_id', $companyId)
            ->wherePivot('granted', true)
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        // Check permission through roles
        return Permission::where('slug', $permissionSlug)
            ->whereHas('roles.users', function ($query) use ($companyId) {
                $query->where('users.id', $this->id)
                      ->where('user_roles.company_id', $companyId);
            })->exists();
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissionSlugs, ?int $companyId = null): bool
    {
        foreach ($permissionSlugs as $permission) {
            if ($this->hasPermission($permission, $companyId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissionSlugs, ?int $companyId = null): bool
    {
        foreach ($permissionSlugs as $permission) {
            if (!$this->hasPermission($permission, $companyId)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string|array $roleSlug, ?int $companyId = null): bool
    {
        $companyId = $companyId ?? $this->company_id;
        $roleSlugs = is_array($roleSlug) ? $roleSlug : [$roleSlug];

        return $this->roles()
            ->whereIn('slug', $roleSlugs)
            ->wherePivot('company_id', $companyId)
            ->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roleSlugs, ?int $companyId = null): bool
    {
        return $this->hasRole($roleSlugs, $companyId);
    }

    /**
     * Assign role to user for a specific company.
     */
    public function assignRole(string|Role $role, ?int $companyId = null): self
    {
        $companyId = $companyId ?? $this->company_id;
        $roleModel = is_string($role) ? Role::findBySlug($role) : $role;

        if ($roleModel && !$this->hasRole($roleModel->slug, $companyId)) {
            $this->roles()->attach($roleModel->id, ['company_id' => $companyId]);
        }

        return $this;
    }

    /**
     * Remove role from user for a specific company.
     */
    public function removeRole(string|Role $role, ?int $companyId = null): self
    {
        $companyId = $companyId ?? $this->company_id;
        $roleModel = is_string($role) ? Role::findBySlug($role) : $role;

        if ($roleModel) {
            $this->roles()
                ->wherePivot('company_id', $companyId)
                ->detach($roleModel->id);
        }

        return $this;
    }

    /**
     * Sync roles for user in a specific company.
     */
    public function syncRoles(array $roleSlugs, ?int $companyId = null): self
    {
        $companyId = $companyId ?? $this->company_id;
        $roleIds = Role::whereIn('slug', $roleSlugs)->pluck('id')->toArray();
        
        // Remove all roles for this company first
        $this->roles()->wherePivot('company_id', $companyId)->detach();
        
        // Add new roles
        foreach ($roleIds as $roleId) {
            $this->roles()->attach($roleId, ['company_id' => $companyId]);
        }

        return $this;
    }

    /**
     * Give permission to user directly.
     */
    public function givePermissionTo(string|Permission $permission, ?int $companyId = null): self
    {
        $companyId = $companyId ?? $this->company_id;
        $permissionModel = is_string($permission) ? Permission::findBySlug($permission) : $permission;

        if ($permissionModel) {
            $this->permissions()->syncWithoutDetaching([
                $permissionModel->id => [
                    'company_id' => $companyId,
                    'granted' => true
                ]
            ]);
        }

        return $this;
    }

    /**
     * Revoke permission from user directly.
     */
    public function revokePermissionTo(string|Permission $permission, ?int $companyId = null): self
    {
        $companyId = $companyId ?? $this->company_id;
        $permissionModel = is_string($permission) ? Permission::findBySlug($permission) : $permission;

        if ($permissionModel) {
            $this->permissions()
                ->wherePivot('company_id', $companyId)
                ->detach($permissionModel->id);
        }

        return $this;
    }

    /**
     * Deny permission to user directly (explicit denial).
     */
    public function denyPermissionTo(string|Permission $permission, ?int $companyId = null): self
    {
        $companyId = $companyId ?? $this->company_id;
        $permissionModel = is_string($permission) ? Permission::findBySlug($permission) : $permission;

        if ($permissionModel) {
            $this->permissions()->syncWithoutDetaching([
                $permissionModel->id => [
                    'company_id' => $companyId,
                    'granted' => false
                ]
            ]);
        }

        return $this;
    }

    /**
     * Get user's role level (highest level from all roles).
     */
    public function getRoleLevel(?int $companyId = null): int
    {
        $companyId = $companyId ?? $this->company_id;
        
        $maxLevel = $this->roles()
            ->wherePivot('company_id', $companyId)
            ->max('level');

        return $maxLevel ?? 1; // Default to level 1 (Accountant)
    }

    /**
     * Check if user has permission to access domain.
     */
    public function canAccessDomain(string $domain, ?int $companyId = null): bool
    {
        return $this->hasPermission($domain . '.view', $companyId);
    }

    /**
     * Check if user can perform action on domain.
     */
    public function canPerformAction(string $domain, string $action, ?int $companyId = null): bool
    {
        $permissionSlug = $domain . '.' . $action;
        return $this->hasPermission($permissionSlug, $companyId);
    }

    /**
     * Get permissions grouped by domain.
     */
    public function getPermissionsByDomain(?int $companyId = null): Collection
    {
        return $this->getAllPermissions($companyId)->groupBy('domain');
    }

    /**
     * Check if user is admin (backward compatibility).
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->getRoleLevel() >= Role::LEVEL_ADMIN;
    }

    /**
     * Check if user is technician (backward compatibility).
     */
    public function isTech(): bool
    {
        return $this->hasRole('technician') || $this->getRoleLevel() >= Role::LEVEL_TECHNICIAN;
    }

    /**
     * Check if user is accountant (backward compatibility).
     */
    public function isAccountant(): bool
    {
        return $this->hasRole('accountant') || $this->getRoleLevel() >= Role::LEVEL_ACCOUNTANT;
    }
}