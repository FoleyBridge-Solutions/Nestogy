<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Role Model
 *
 * DEPRECATED: This model is maintained for backward compatibility only.
 * The system now uses Bouncer's Role model for roles.
 *
 * @deprecated Use Silber\Bouncer\Database\Role instead
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $title
 * @property int $scope
 */
class Role extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'bouncer_roles'; // Point to Bouncer roles table

    protected $fillable = [
        'company_id',
        'name',
        'title',
        'scope',
    ];

    protected $casts = [
        'scope' => 'integer',
    ];

    /**
     * System role constants - maintained for backward compatibility
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
     * Find role by name (Bouncer compatibility).
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::where('name', $slug)->first();
    }

    /**
     * Create a role using Bouncer (backward compatibility).
     */
    public static function createRole(
        string $name,
        ?string $description = null,
        int $level = 1,
        bool $isSystem = false
    ): self {
        return self::create([
            'name' => str()->slug($name),
            'title' => $name,
        ]);
    }

    /**
     * Get role display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->title ?: $this->name;
    }

    /**
     * Get slug attribute (backward compatibility).
     */
    public function getSlugAttribute(): string
    {
        return $this->name;
    }

    /**
     * Bouncer compatibility methods
     */

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Use Bouncer to check permission
        return \Silber\Bouncer\BouncerFacade::role($this->name)->can($permissionSlug);
    }

    /**
     * Check if this role has any of the given permissions.
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this role has all of the given permissions.
     */
    public function hasAllPermissions(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Give permissions to this role using Bouncer.
     */
    public function givePermissionTo(...$permissions): self
    {
        foreach (collect($permissions)->flatten() as $permission) {
            \Silber\Bouncer\BouncerFacade::allow($this->name)->to($permission);
        }

        return $this;
    }

    /**
     * Remove permissions from this role using Bouncer.
     */
    public function revokePermissionTo(...$permissions): self
    {
        foreach (collect($permissions)->flatten() as $permission) {
            \Silber\Bouncer\BouncerFacade::disallow($this->name)->to($permission);
        }

        return $this;
    }

    /**
     * Sync permissions for this role (not directly supported by Bouncer).
     */
    public function syncPermissions(...$permissions): self
    {
        // This is a complex operation in Bouncer, for now just add permissions
        return $this->givePermissionTo(...$permissions);
    }

    /**
     * Get level from role name (backward compatibility).
     */
    public function getLevelAttribute(): int
    {
        $levels = [
            'accountant' => self::LEVEL_ACCOUNTANT,
            'tech' => self::LEVEL_TECHNICIAN,
            'technician' => self::LEVEL_TECHNICIAN,
            'admin' => self::LEVEL_ADMIN,
        ];

        return $levels[$this->name] ?? 1;
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
     * Get role level label.
     */
    public function getLevelLabelAttribute(): string
    {
        $levels = self::getRoleLevels();

        return $levels[$this->level] ?? 'Custom Level '.$this->level;
    }
}
