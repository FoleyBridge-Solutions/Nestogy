<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * CrossCompanyUser Model
 *
 * Manages users who have access to multiple companies within an organizational hierarchy.
 * Handles access control, session management, and audit requirements for cross-company operations.
 *
 * @property int $id
 * @property int $user_id
 * @property int $company_id
 * @property int $primary_company_id
 * @property int $role_in_company
 * @property string $access_type
 * @property array|null $access_permissions
 * @property array|null $access_restrictions
 * @property int|null $authorized_by
 * @property int|null $delegated_from
 * @property string|null $authorization_reason
 * @property bool $is_active
 * @property \Carbon\Carbon|null $access_granted_at
 * @property \Carbon\Carbon|null $access_expires_at
 * @property \Carbon\Carbon|null $last_accessed_at
 */
class CrossCompanyUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'primary_company_id',
        'role_in_company',
        'access_type',
        'access_permissions',
        'access_restrictions',
        'authorized_by',
        'delegated_from',
        'authorization_reason',
        'is_active',
        'access_granted_at',
        'access_expires_at',
        'last_accessed_at',
        'require_re_auth',
        'max_concurrent_sessions',
        'allowed_features',
        'audit_actions',
        'compliance_settings',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'access_permissions' => 'array',
        'access_restrictions' => 'array',
        'allowed_features' => 'array',
        'compliance_settings' => 'array',
        'is_active' => 'boolean',
        'require_re_auth' => 'boolean',
        'audit_actions' => 'boolean',
        'access_granted_at' => 'datetime',
        'access_expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Access types enumeration
     */
    const ACCESS_FULL = 'full';

    const ACCESS_LIMITED = 'limited';

    const ACCESS_VIEW_ONLY = 'view_only';

    const ACCESS_TYPES = [
        self::ACCESS_FULL => 'Full Access',
        self::ACCESS_LIMITED => 'Limited Access',
        self::ACCESS_VIEW_ONLY => 'View Only',
    ];

    /**
     * Get the user who has cross-company access.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that the user can access.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user's primary company.
     */
    public function primaryCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'primary_company_id');
    }

    /**
     * Get the user who authorized this access.
     */
    public function authorizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * Get the user who delegated this access.
     */
    public function delegatedFromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_from');
    }

    /**
     * Get the user who created this record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Grant cross-company access to a user.
     */
    public static function grantAccess(array $data): static
    {
        // Validate that the companies are related
        if (! CompanyHierarchy::areRelated($data['company_id'], $data['primary_company_id'])) {
            throw new \InvalidArgumentException('Companies must be related in the organizational hierarchy.');
        }

        // Set audit fields
        $data['access_granted_at'] = now();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return static::create($data);
    }

    /**
     * Revoke cross-company access.
     */
    public function revokeAccess(): bool
    {
        $this->is_active = false;
        $this->updated_by = Auth::id();

        return $this->save();
    }

    /**
     * Update last accessed timestamp.
     */
    public function recordAccess(): bool
    {
        $this->last_accessed_at = now();

        return $this->save();
    }

    /**
     * Check if access has expired.
     */
    public function hasExpired(): bool
    {
        return $this->access_expires_at && $this->access_expires_at->isPast();
    }

    /**
     * Check if access is currently valid.
     */
    public function isValid(): bool
    {
        return $this->is_active && ! $this->hasExpired();
    }

    /**
     * Get companies a user can access.
     */
    public static function getAccessibleCompanies(int $userId): Collection
    {
        return static::with(['company'])
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->get()
            ->pluck('company');
    }

    /**
     * Check if user can access a specific company.
     */
    public static function canUserAccessCompany(int $userId, int $companyId): bool
    {
        // Check if it's their primary company
        $user = User::find($userId);
        if ($user && $user->company_id === $companyId) {
            return true;
        }

        // Check cross-company access
        return static::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get user's role in a specific company.
     */
    public static function getUserRoleInCompany(int $userId, int $companyId): ?int
    {
        // Check if it's their primary company
        $user = User::with('settings')->find($userId);
        if ($user && $user->company_id === $companyId) {
            return $user->settings->role ?? null;
        }

        // Check cross-company access
        $crossCompanyAccess = static::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->first();

        return $crossCompanyAccess?->role_in_company;
    }

    /**
     * Get users with access to a specific company.
     */
    public static function getUsersWithCompanyAccess(int $companyId): Collection
    {
        return static::with(['user', 'primaryCompany', 'authorizedByUser'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Check if user has specific permission in company.
     */
    public function hasPermission(string $permission): bool
    {
        // Full access users have all permissions
        if ($this->access_type === self::ACCESS_FULL) {
            return true;
        }

        // View-only users only have view permissions
        if ($this->access_type === self::ACCESS_VIEW_ONLY) {
            return in_array($permission, ['view', 'read', 'list']);
        }

        // Limited access - check specific permissions
        if ($this->access_type === self::ACCESS_LIMITED) {
            $permissions = $this->access_permissions ?? [];

            return in_array($permission, $permissions);
        }

        return false;
    }

    /**
     * Check if user is restricted from specific action.
     */
    public function isRestricted(string $action): bool
    {
        $restrictions = $this->access_restrictions ?? [];

        return in_array($action, $restrictions);
    }

    /**
     * Check if user can access specific feature.
     */
    public function canAccessFeature(string $feature): bool
    {
        $allowedFeatures = $this->allowed_features ?? [];

        // If no restrictions are set, allow all features
        if (empty($allowedFeatures)) {
            return true;
        }

        return in_array($feature, $allowedFeatures);
    }

    /**
     * Extend access expiration.
     */
    public function extendAccess(\Carbon\Carbon $newExpiration): bool
    {
        $this->access_expires_at = $newExpiration;
        $this->updated_by = Auth::id();

        return $this->save();
    }

    /**
     * Delegate access to another user.
     */
    public function delegateAccessTo(int $targetUserId, array $options = []): static
    {
        $delegatedAccess = static::create([
            'user_id' => $targetUserId,
            'company_id' => $this->company_id,
            'primary_company_id' => $this->primary_company_id,
            'role_in_company' => $options['role_in_company'] ?? $this->role_in_company,
            'access_type' => $options['access_type'] ?? self::ACCESS_LIMITED,
            'access_permissions' => $options['access_permissions'] ?? [],
            'access_restrictions' => $options['access_restrictions'] ?? [],
            'authorized_by' => $this->user_id,
            'delegated_from' => $this->user_id,
            'authorization_reason' => $options['reason'] ?? 'Delegated access',
            'is_active' => true,
            'access_granted_at' => now(),
            'access_expires_at' => $options['expires_at'] ?? $this->access_expires_at,
            'require_re_auth' => $options['require_re_auth'] ?? true,
            'max_concurrent_sessions' => $options['max_concurrent_sessions'] ?? 1,
            'allowed_features' => $options['allowed_features'] ?? $this->allowed_features,
            'audit_actions' => true,
            'compliance_settings' => $this->compliance_settings,
            'notes' => 'Delegated from user ID: '.$this->user_id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return $delegatedAccess;
    }

    /**
     * Scope to get active access records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            });
    }

    /**
     * Scope to get access records for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get access records for a specific company.
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to get expired access records.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('access_expires_at', '<', now());
    }

    /**
     * Scope to get delegated access records.
     */
    public function scopeDelegated(Builder $query): Builder
    {
        return $query->whereNotNull('delegated_from');
    }

    /**
     * Scope to get access records by type.
     */
    public function scopeByAccessType(Builder $query, string $accessType): Builder
    {
        return $query->where('access_type', $accessType);
    }

    /**
     * Get access type label.
     */
    public function getAccessTypeLabel(): string
    {
        return static::ACCESS_TYPES[$this->access_type] ?? $this->access_type;
    }

    /**
     * Get role label for this company.
     */
    public function getRoleLabel(): string
    {
        return UserSetting::ROLE_LABELS[$this->role_in_company] ?? 'Unknown';
    }

    /**
     * Check if access requires re-authentication.
     */
    public function requiresReAuth(): bool
    {
        return $this->require_re_auth === true;
    }

    /**
     * Check if actions should be audited.
     */
    public function shouldAuditActions(): bool
    {
        return $this->audit_actions === true;
    }
}
