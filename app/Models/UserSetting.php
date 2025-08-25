<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserSetting Model
 * 
 * Stores user-specific settings and preferences including role, dashboard settings,
 * and security preferences.
 * 
 * @property int $id
 * @property int $user_id
 * @property int $role
 * @property string|null $remember_me_token
 * @property bool $force_mfa
 * @property int $records_per_page
 * @property bool $dashboard_financial_enable
 * @property bool $dashboard_technical_enable
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'role',
        'remember_me_token',
        'force_mfa',
        'records_per_page',
        'dashboard_financial_enable',
        'dashboard_technical_enable',
        'theme',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'remember_me_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'user_id' => 'integer',
        'company_id' => 'integer',
        'role' => 'integer',
        'force_mfa' => 'boolean',
        'records_per_page' => 'integer',
        'dashboard_financial_enable' => 'boolean',
        'dashboard_technical_enable' => 'boolean',
        'theme' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * User roles enumeration
     */
    const ROLE_ACCOUNTANT = 1;
    const ROLE_TECH = 2;
    const ROLE_ADMIN = 3;           // Tenant administrator
    const ROLE_SUPER_ADMIN = 4;     // Platform operator (Company 1 only)
    const ROLE_PARENT_ADMIN = 5;    // Can manage subsidiaries
    const ROLE_SUBSIDIARY_ADMIN = 6; // Admin of subsidiary with limited parent access
    const ROLE_CROSS_COMPANY_USER = 7; // Access across company hierarchy

    /**
     * Role labels mapping
     */
    const ROLE_LABELS = [
        self::ROLE_ACCOUNTANT => 'Accountant',
        self::ROLE_TECH => 'Technician',
        self::ROLE_ADMIN => 'Administrator',
        self::ROLE_SUPER_ADMIN => 'Super Administrator',
        self::ROLE_PARENT_ADMIN => 'Parent Company Admin',
        self::ROLE_SUBSIDIARY_ADMIN => 'Subsidiary Admin',
        self::ROLE_CROSS_COMPANY_USER => 'Cross-Company User',
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role label.
     */
    public function getRoleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? 'Unknown';
    }

    /**
     * Check if user has admin role (tenant administrator).
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user has super admin role (platform operator).
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user has any admin role (tenant or super).
     */
    public function isAnyAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user has tech role.
     */
    public function isTech(): bool
    {
        return $this->role === self::ROLE_TECH;
    }

    /**
     * Check if user has accountant role.
     */
    public function isAccountant(): bool
    {
        return $this->role === self::ROLE_ACCOUNTANT;
    }

    /**
     * Check if user has parent admin role (can manage subsidiaries).
     */
    public function isParentAdmin(): bool
    {
        return $this->role === self::ROLE_PARENT_ADMIN;
    }

    /**
     * Check if user has subsidiary admin role.
     */
    public function isSubsidiaryAdmin(): bool
    {
        return $this->role === self::ROLE_SUBSIDIARY_ADMIN;
    }

    /**
     * Check if user is a cross-company user.
     */
    public function isCrossCompanyUser(): bool
    {
        return $this->role === self::ROLE_CROSS_COMPANY_USER;
    }

    /**
     * Check if user can manage subsidiaries.
     */
    public function canManageSubsidiaries(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_PARENT_ADMIN,
            self::ROLE_ADMIN, // Regular admins can also create subsidiaries if company allows
        ]);
    }

    /**
     * Check if user has any subsidiary-related role.
     */
    public function hasSubsidiaryRole(): bool
    {
        return in_array($this->role, [
            self::ROLE_PARENT_ADMIN,
            self::ROLE_SUBSIDIARY_ADMIN,
            self::ROLE_CROSS_COMPANY_USER,
        ]);
    }

    /**
     * Check if MFA is required for this user.
     */
    public function requiresMfa(): bool
    {
        return $this->force_mfa === true;
    }

    /**
     * Check if financial dashboard is enabled.
     */
    public function hasFinancialDashboard(): bool
    {
        return $this->dashboard_financial_enable === true;
    }

    /**
     * Check if technical dashboard is enabled.
     */
    public function hasTechnicalDashboard(): bool
    {
        return $this->dashboard_technical_enable === true;
    }

    /**
     * Get records per page with fallback.
     */
    public function getRecordsPerPage(): int
    {
        return $this->records_per_page > 0 ? $this->records_per_page : 10;
    }

    /**
     * Scope to get settings by role.
     */
    public function scopeByRole($query, int $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get admin users (tenant administrators).
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Scope to get super admin users (platform operators).
     */
    public function scopeSuperAdmins($query)
    {
        return $query->where('role', self::ROLE_SUPER_ADMIN);
    }

    /**
     * Scope to get any admin users (tenant or super).
     */
    public function scopeAnyAdmins($query)
    {
        return $query->whereIn('role', [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /**
     * Scope to get tech users.
     */
    public function scopeTechs($query)
    {
        return $query->where('role', self::ROLE_TECH);
    }

    /**
     * Scope to get accountant users.
     */
    public function scopeAccountants($query)
    {
        return $query->where('role', self::ROLE_ACCOUNTANT);
    }

    /**
     * Scope to get users with MFA enabled.
     */
    public function scopeWithMfa($query)
    {
        return $query->where('force_mfa', true);
    }

    /**
     * Scope to get parent admin users.
     */
    public function scopeParentAdmins($query)
    {
        return $query->where('role', self::ROLE_PARENT_ADMIN);
    }

    /**
     * Scope to get subsidiary admin users.
     */
    public function scopeSubsidiaryAdmins($query)
    {
        return $query->where('role', self::ROLE_SUBSIDIARY_ADMIN);
    }

    /**
     * Scope to get cross-company users.
     */
    public function scopeCrossCompanyUsers($query)
    {
        return $query->where('role', self::ROLE_CROSS_COMPANY_USER);
    }

    /**
     * Scope to get users who can manage subsidiaries.
     */
    public function scopeCanManageSubsidiaries($query)
    {
        return $query->whereIn('role', [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_PARENT_ADMIN,
            self::ROLE_ADMIN,
        ]);
    }

    /**
     * Get validation rules for user settings.
     */
    public static function getValidationRules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|integer|in:1,2,3,4,5,6,7',
            'force_mfa' => 'boolean',
            'records_per_page' => 'integer|min:5|max:100',
            'dashboard_financial_enable' => 'boolean',
            'dashboard_technical_enable' => 'boolean',
        ];
    }

    /**
     * Get available roles for selection.
     */
    public static function getAvailableRoles(): array
    {
        return self::ROLE_LABELS;
    }

    /**
     * Create default settings for a user.
     */
    public static function createDefaultForUser(int $userId, int $role = self::ROLE_ACCOUNTANT, ?int $companyId = null): self
    {
        // If no company ID provided, try to get it from the user
        if (!$companyId) {
            $user = User::find($userId);
            $companyId = $user ? $user->company_id : null;
        }
        
        return self::create([
            'user_id' => $userId,
            'company_id' => $companyId,
            'role' => $role,
            'force_mfa' => false,
            'records_per_page' => 10,
            'dashboard_financial_enable' => in_array($role, [self::ROLE_ACCOUNTANT, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]),
            'dashboard_technical_enable' => in_array($role, [self::ROLE_TECH, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]),
            'theme' => 'light',
        ]);
    }
}