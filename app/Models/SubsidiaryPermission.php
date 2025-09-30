<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * SubsidiaryPermission Model
 *
 * Manages cross-company permissions within organizational hierarchies.
 * Allows parent companies to grant specific access to subsidiary companies
 * and vice versa with proper inheritance and delegation controls.
 *
 * @property int $id
 * @property int $granter_company_id
 * @property int $grantee_company_id
 * @property int|null $user_id
 * @property string $resource_type
 * @property string $permission_type
 * @property array|null $conditions
 * @property string $scope
 * @property array|null $scope_filters
 * @property string|null $resource_ids
 * @property bool $is_inherited
 * @property string|null $inherited_from
 * @property bool $can_delegate
 * @property int $priority
 * @property bool $is_active
 * @property \Carbon\Carbon|null $expires_at
 */
class SubsidiaryPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'granter_company_id',
        'grantee_company_id',
        'user_id',
        'resource_type',
        'permission_type',
        'conditions',
        'scope',
        'scope_filters',
        'resource_ids',
        'is_inherited',
        'inherited_from',
        'can_delegate',
        'priority',
        'is_active',
        'expires_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'scope_filters' => 'array',
        'is_inherited' => 'boolean',
        'can_delegate' => 'boolean',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Permission types enumeration
     */
    const PERMISSION_VIEW = 'view';

    const PERMISSION_CREATE = 'create';

    const PERMISSION_EDIT = 'edit';

    const PERMISSION_DELETE = 'delete';

    const PERMISSION_MANAGE = 'manage';

    const PERMISSION_TYPES = [
        self::PERMISSION_VIEW => 'View',
        self::PERMISSION_CREATE => 'Create',
        self::PERMISSION_EDIT => 'Edit',
        self::PERMISSION_DELETE => 'Delete',
        self::PERMISSION_MANAGE => 'Full Management',
    ];

    /**
     * Scope types enumeration
     */
    const SCOPE_ALL = 'all';

    const SCOPE_SPECIFIC = 'specific';

    const SCOPE_FILTERED = 'filtered';

    const SCOPE_TYPES = [
        self::SCOPE_ALL => 'All Records',
        self::SCOPE_SPECIFIC => 'Specific Records',
        self::SCOPE_FILTERED => 'Filtered Records',
    ];

    /**
     * Get the company that granted the permission.
     */
    public function granterCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'granter_company_id');
    }

    /**
     * Get the company that received the permission.
     */
    public function granteeCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'grantee_company_id');
    }

    /**
     * Get the specific user (if permission is user-specific).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this permission.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this permission.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if a company has permission for a resource.
     */
    public static function hasPermission(
        int $granteeCompanyId,
        string $resourceType,
        string $permissionType,
        ?int $userId = null,
        ?array $context = null
    ): bool {
        $query = static::where('grantee_company_id', $granteeCompanyId)
            ->where('resource_type', $resourceType)
            ->where('permission_type', $permissionType)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        // Check user-specific permissions
        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            });
        } else {
            $query->whereNull('user_id');
        }

        $permissions = $query->orderBy('priority', 'desc')->get();

        if ($permissions->isEmpty()) {
            return false;
        }

        // Check each permission's conditions
        foreach ($permissions as $permission) {
            if ($permission->matchesContext($context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all permissions for a company.
     */
    public static function getPermissionsForCompany(
        int $companyId,
        ?int $userId = null
    ): Collection {
        $query = static::with(['granterCompany', 'granteeCompany', 'user'])
            ->where('grantee_company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            });
        } else {
            $query->whereNull('user_id');
        }

        return $query->orderBy('priority', 'desc')->get();
    }

    /**
     * Grant permission from one company to another.
     */
    public static function grantPermission(array $data): static
    {
        // Validate that granter has authority
        if (! static::canGrantPermission($data['granter_company_id'], $data['grantee_company_id'])) {
            throw new \InvalidArgumentException('Granter company cannot grant permission to grantee company.');
        }

        // Set audit fields
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return static::create($data);
    }

    /**
     * Revoke a permission.
     */
    public function revoke(): bool
    {
        $this->is_active = false;
        $this->updated_by = Auth::id();

        return $this->save();
    }

    /**
     * Check if one company can grant permissions to another.
     */
    public static function canGrantPermission(int $granterCompanyId, int $granteeCompanyId): bool
    {
        // Same company
        if ($granterCompanyId === $granteeCompanyId) {
            return false;
        }

        // Check if granter is ancestor of grantee
        return CompanyHierarchy::isAncestor($granterCompanyId, $granteeCompanyId);
    }

    /**
     * Inherit permissions from parent companies.
     */
    public static function inheritPermissions(int $companyId): void
    {
        $ancestors = CompanyHierarchy::getAncestors($companyId);

        foreach ($ancestors as $hierarchy) {
            $parentPermissions = static::where('grantee_company_id', $hierarchy->ancestor_id)
                ->where('can_delegate', true)
                ->where('is_active', true)
                ->get();

            foreach ($parentPermissions as $permission) {
                // Check if permission already exists
                $exists = static::where('granter_company_id', $permission->granter_company_id)
                    ->where('grantee_company_id', $companyId)
                    ->where('resource_type', $permission->resource_type)
                    ->where('permission_type', $permission->permission_type)
                    ->where('user_id', $permission->user_id)
                    ->exists();

                if (! $exists) {
                    static::create([
                        'granter_company_id' => $permission->granter_company_id,
                        'grantee_company_id' => $companyId,
                        'user_id' => $permission->user_id,
                        'resource_type' => $permission->resource_type,
                        'permission_type' => $permission->permission_type,
                        'conditions' => $permission->conditions,
                        'scope' => $permission->scope,
                        'scope_filters' => $permission->scope_filters,
                        'resource_ids' => $permission->resource_ids,
                        'is_inherited' => true,
                        'inherited_from' => $hierarchy->ancestor_id,
                        'can_delegate' => false, // Inherited permissions cannot be re-delegated
                        'priority' => $permission->priority - 10, // Lower priority for inherited
                        'is_active' => true,
                        'expires_at' => $permission->expires_at,
                        'notes' => 'Inherited from company: '.$permission->granterCompany->name,
                        'created_by' => null,
                        'updated_by' => null,
                    ]);
                }
            }
        }
    }

    /**
     * Check if permission matches given context.
     */
    public function matchesContext(?array $context = null): bool
    {
        if (empty($this->conditions) && empty($context)) {
            return true;
        }

        if (empty($this->conditions)) {
            return true;
        }

        if (empty($context)) {
            return false;
        }

        // Simple condition matching - can be extended for complex rules
        foreach ($this->conditions as $key => $value) {
            if (! isset($context[$key]) || $context[$key] != $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if permission has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if permission is currently valid.
     */
    public function isValid(): bool
    {
        return $this->is_active && ! $this->hasExpired();
    }

    /**
     * Scope to get active permissions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get permissions for a specific resource type.
     */
    public function scopeForResource(Builder $query, string $resourceType): Builder
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope to get permissions of a specific type.
     */
    public function scopeOfType(Builder $query, string $permissionType): Builder
    {
        return $query->where('permission_type', $permissionType);
    }

    /**
     * Scope to get inherited permissions.
     */
    public function scopeInherited(Builder $query): Builder
    {
        return $query->where('is_inherited', true);
    }

    /**
     * Scope to get delegable permissions.
     */
    public function scopeDelegable(Builder $query): Builder
    {
        return $query->where('can_delegate', true);
    }

    /**
     * Scope to get permissions for a specific user.
     */
    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        if ($userId) {
            return $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            });
        }

        return $query->whereNull('user_id');
    }

    /**
     * Get permission type label.
     */
    public function getPermissionTypeLabel(): string
    {
        return static::PERMISSION_TYPES[$this->permission_type] ?? $this->permission_type;
    }

    /**
     * Get scope type label.
     */
    public function getScopeTypeLabel(): string
    {
        return static::SCOPE_TYPES[$this->scope] ?? $this->scope;
    }
}
