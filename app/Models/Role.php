<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Role Model
 * 
 * Represents user roles with associated permissions.
 * Supports hierarchical role levels for inheritance.
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $level
 * @property bool $is_system
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'level',
        'is_system',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_system' => 'boolean',
    ];

    /**
     * System role constants (matching existing UserSetting constants)
     */
    const LEVEL_ACCOUNTANT = 1;
    const LEVEL_TECHNICIAN = 2;
    const LEVEL_ADMIN = 3;

    /**
     * System role slugs
     */
    const SLUG_ACCOUNTANT = 'accountant';
    const SLUG_TECHNICIAN = 'technician';
    const SLUG_ADMIN = 'admin';

    /**
     * Get role levels mapping
     */
    public static function getRoleLevels(): array
    {
        return [
            self::LEVEL_ACCOUNTANT => 'Accountant',
            self::LEVEL_TECHNICIAN => 'Technician',
            self::LEVEL_ADMIN => 'Administrator',
        ];
    }

    /**
     * Get the permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /**
     * Get the users who have this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot(['company_id'])
            ->withTimestamps();
    }

    /**
     * Scope system roles.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope custom roles.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope roles by level or higher.
     */
    public function scopeByLevelOrHigher($query, int $level)
    {
        return $query->where('level', '>=', $level);
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Check if this role has any of the given permissions.
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        return $this->permissions()->whereIn('slug', $permissionSlugs)->exists();
    }

    /**
     * Check if this role has all of the given permissions.
     */
    public function hasAllPermissions(array $permissionSlugs): bool
    {
        $rolePermissions = $this->permissions()->whereIn('slug', $permissionSlugs)->count();
        return $rolePermissions === count($permissionSlugs);
    }

    /**
     * Give permissions to this role.
     */
    public function givePermissionTo(...$permissions): self
    {
        $permissionModels = collect($permissions)->flatten()
            ->map(function ($permission) {
                if ($permission instanceof Permission) {
                    return $permission;
                }
                return Permission::findBySlug($permission);
            })
            ->filter()
            ->pluck('id');

        $this->permissions()->syncWithoutDetaching($permissionModels);
        
        return $this;
    }

    /**
     * Remove permissions from this role.
     */
    public function revokePermissionTo(...$permissions): self
    {
        $permissionModels = collect($permissions)->flatten()
            ->map(function ($permission) {
                if ($permission instanceof Permission) {
                    return $permission;
                }
                return Permission::findBySlug($permission);
            })
            ->filter()
            ->pluck('id');

        $this->permissions()->detach($permissionModels);
        
        return $this;
    }

    /**
     * Sync permissions for this role.
     */
    public function syncPermissions(...$permissions): self
    {
        $permissionModels = collect($permissions)->flatten()
            ->map(function ($permission) {
                if ($permission instanceof Permission) {
                    return $permission;
                }
                return Permission::findBySlug($permission);
            })
            ->filter()
            ->pluck('id');

        $this->permissions()->sync($permissionModels);
        
        return $this;
    }

    /**
     * Create a role with automatic slug generation.
     */
    public static function createRole(
        string $name,
        ?string $description = null,
        int $level = 1,
        bool $isSystem = false
    ): self {
        $slug = str()->slug($name);
        
        return self::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'level' => $level,
            'is_system' => $isSystem,
        ]);
    }

    /**
     * Find role by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }

    /**
     * Get all permissions for roles up to this level (hierarchical inheritance).
     */
    public function getInheritedPermissions(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::whereHas('roles', function ($query) {
            $query->where('level', '<=', $this->level);
        })->get();
    }

    /**
     * Check if this role is higher level than another role.
     */
    public function isHigherThan(Role $otherRole): bool
    {
        return $this->level > $otherRole->level;
    }

    /**
     * Check if this role is at least the given level.
     */
    public function isAtLeastLevel(int $level): bool
    {
        return $this->level >= $level;
    }

    /**
     * Get role display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get role level label.
     */
    public function getLevelLabelAttribute(): string
    {
        $levels = self::getRoleLevels();
        return $levels[$this->level] ?? 'Custom Level ' . $this->level;
    }
}