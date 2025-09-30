<?php

namespace App\Traits;

use App\Domains\Security\Services\PermissionService;

/**
 * Enhanced permission checking trait with wildcard support
 * 
 * Add this trait to your User model to enable wildcard permission checking
 */
trait HasEnhancedPermissions
{
    /**
     * Check if user has permission (with wildcard support)
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return app(PermissionService::class)->userHasPermission($this, $permission);
    }
    
    /**
     * Check if user can access a specific resource
     * 
     * @param string $permission
     * @param mixed $resource
     * @return bool
     */
    public function canAccessResource(string $permission, $resource): bool
    {
        return app(PermissionService::class)->canAccessResource($this, $permission, $resource);
    }
    
    /**
     * Check if user has any of the given permissions
     * 
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has all of the given permissions
     * 
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get all effective permissions including expanded wildcards
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getEffectivePermissions()
    {
        return app(PermissionService::class)->getEffectivePermissions($this);
    }
}