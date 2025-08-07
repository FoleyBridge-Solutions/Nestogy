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
        'role',
        'remember_me_token',
        'force_mfa',
        'records_per_page',
        'dashboard_financial_enable',
        'dashboard_technical_enable',
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
        'role' => 'integer',
        'force_mfa' => 'boolean',
        'records_per_page' => 'integer',
        'dashboard_financial_enable' => 'boolean',
        'dashboard_technical_enable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * User roles enumeration
     */
    const ROLE_ACCOUNTANT = 1;
    const ROLE_TECH = 2;
    const ROLE_ADMIN = 3;

    /**
     * Role labels mapping
     */
    const ROLE_LABELS = [
        self::ROLE_ACCOUNTANT => 'Accountant',
        self::ROLE_TECH => 'Technician',
        self::ROLE_ADMIN => 'Administrator',
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
     * Check if user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
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
     * Scope to get admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
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
     * Get validation rules for user settings.
     */
    public static function getValidationRules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|integer|in:1,2,3',
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
    public static function createDefaultForUser(int $userId, int $role = self::ROLE_ACCOUNTANT): self
    {
        return self::create([
            'user_id' => $userId,
            'role' => $role,
            'force_mfa' => false,
            'records_per_page' => 10,
            'dashboard_financial_enable' => $role === self::ROLE_ACCOUNTANT || $role === self::ROLE_ADMIN,
            'dashboard_technical_enable' => $role === self::ROLE_TECH || $role === self::ROLE_ADMIN,
        ]);
    }
}