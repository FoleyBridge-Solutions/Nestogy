<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Client;
use App\Traits\BelongsToCompany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Client Portal User Model
 * 
 * Separate authentication model for client portal access with role-based permissions
 * and two-factor authentication support.
 */
class ClientPortalUser extends Authenticatable
{
    use HasApiTokens,
        HasFactory,
        Notifiable,
        TwoFactorAuthenticatable,
        SoftDeletes,
        BelongsToCompany,
        LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'client_portal_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'title',
        'department',
        'is_active',
        'is_primary',
        'can_view_invoices',
        'can_view_tickets',
        'can_create_tickets',
        'can_view_assets',
        'can_view_projects',
        'can_view_reports',
        'can_approve_quotes',
        'notification_preferences',
        'last_login_at',
        'last_login_ip',
        'login_count',
        'failed_login_count',
        'locked_until',
        'email_verified_at',
        'password_changed_at',
        'must_change_password',
        'session_timeout_minutes',
        'allowed_ip_addresses',
        'timezone',
        'locale',
        'metadata'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'can_view_invoices' => 'boolean',
        'can_view_tickets' => 'boolean',
        'can_create_tickets' => 'boolean',
        'can_view_assets' => 'boolean',
        'can_view_projects' => 'boolean',
        'can_view_reports' => 'boolean',
        'can_approve_quotes' => 'boolean',
        'must_change_password' => 'boolean',
        'notification_preferences' => 'array',
        'allowed_ip_addresses' => 'array',
        'metadata' => 'array',
        'login_count' => 'integer',
        'failed_login_count' => 'integer',
        'session_timeout_minutes' => 'integer'
    ];

    /**
     * Portal user roles
     */
    const ROLE_PRIMARY = 'primary';      // Primary contact with full access
    const ROLE_ADMIN = 'admin';          // Administrative access
    const ROLE_FINANCE = 'finance';      // Financial information access
    const ROLE_TECHNICAL = 'technical';  // Technical support access
    const ROLE_VIEWER = 'viewer';        // Read-only access

    /**
     * Available roles for portal users
     */
    const ROLES = [
        self::ROLE_PRIMARY => 'Primary Contact',
        self::ROLE_ADMIN => 'Administrator',
        self::ROLE_FINANCE => 'Finance',
        self::ROLE_TECHNICAL => 'Technical',
        self::ROLE_VIEWER => 'Viewer'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values
        static::creating(function ($user) {
            if (!$user->role) {
                $user->role = self::ROLE_VIEWER;
            }
            if (!$user->session_timeout_minutes) {
                $user->session_timeout_minutes = config('nestogy.portal.session_timeout', 30);
            }
            if (!$user->notification_preferences) {
                $user->notification_preferences = [
                    'email' => true,
                    'ticket_updates' => true,
                    'invoice_reminders' => true,
                    'project_updates' => false,
                    'maintenance_notices' => true
                ];
            }
        });

        // Track login attempts
        static::updating(function ($user) {
            if ($user->isDirty('last_login_at')) {
                $user->login_count = ($user->login_count ?? 0) + 1;
                $user->failed_login_count = 0; // Reset on successful login
            }
        });
    }

    /**
     * Get the client this portal user belongs to
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get portal permissions for this user
     */
    public function portalPermissions(): BelongsToMany
    {
        return $this->belongsToMany(PortalPermission::class, 'client_portal_user_permissions')
            ->withTimestamps();
    }

    /**
     * Get activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'email', 'role', 'is_active', 'is_primary',
                'can_view_invoices', 'can_view_tickets', 'can_create_tickets',
                'can_view_assets', 'can_view_projects', 'can_view_reports',
                'can_approve_quotes', 'last_login_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Portal user {$eventName}");
    }

    /**
     * Check if user has a specific role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is primary contact
     *
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->is_primary === true;
    }

    /**
     * Check if user has admin or primary role
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_PRIMARY, self::ROLE_ADMIN]);
    }

    /**
     * Check if user can access financial information
     *
     * @return bool
     */
    public function canAccessFinancials(): bool
    {
        return $this->can_view_invoices || 
               in_array($this->role, [self::ROLE_PRIMARY, self::ROLE_ADMIN, self::ROLE_FINANCE]);
    }

    /**
     * Check if user can manage tickets
     *
     * @return bool
     */
    public function canManageTickets(): bool
    {
        return $this->can_create_tickets || 
               in_array($this->role, [self::ROLE_PRIMARY, self::ROLE_ADMIN, self::ROLE_TECHNICAL]);
    }

    /**
     * Check if user account is locked
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Lock user account for specified minutes
     *
     * @param int $minutes
     * @return void
     */
    public function lockAccount(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes)
        ]);
    }

    /**
     * Unlock user account
     *
     * @return void
     */
    public function unlockAccount(): void
    {
        $this->update([
            'locked_until' => null,
            'failed_login_count' => 0
        ]);
    }

    /**
     * Record failed login attempt
     *
     * @return void
     */
    public function recordFailedLogin(): void
    {
        $this->increment('failed_login_count');
        
        // Lock account after 5 failed attempts
        if ($this->failed_login_count >= 5) {
            $this->lockAccount(30);
        }
    }

    /**
     * Record successful login
     *
     * @param string|null $ipAddress
     * @return void
     */
    public function recordSuccessfulLogin(?string $ipAddress = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress ?? request()->ip(),
            'failed_login_count' => 0
        ]);
    }

    /**
     * Check if password needs to be changed
     *
     * @return bool
     */
    public function needsPasswordChange(): bool
    {
        if ($this->must_change_password) {
            return true;
        }

        // Check if password is older than configured days
        $maxPasswordAge = config('nestogy.portal.password_expiry_days', 90);
        if ($maxPasswordAge && $this->password_changed_at) {
            return $this->password_changed_at->addDays($maxPasswordAge)->isPast();
        }

        return false;
    }

    /**
     * Check if IP address is allowed
     *
     * @param string $ipAddress
     * @return bool
     */
    public function isIpAllowed(string $ipAddress): bool
    {
        // If no restrictions, allow all
        if (empty($this->allowed_ip_addresses)) {
            return true;
        }

        // Check if IP is in allowed list
        foreach ($this->allowed_ip_addresses as $allowedIp) {
            if ($this->ipMatches($ipAddress, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches pattern (supports wildcards and CIDR)
     *
     * @param string $ip
     * @param string $pattern
     * @return bool
     */
    protected function ipMatches(string $ip, string $pattern): bool
    {
        // Exact match
        if ($ip === $pattern) {
            return true;
        }

        // Wildcard match (e.g., 192.168.1.*)
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('*', '.*', $pattern);
            return preg_match('/^' . $pattern . '$/', $ip);
        }

        // CIDR match (e.g., 192.168.1.0/24)
        if (strpos($pattern, '/') !== false) {
            list($subnet, $bits) = explode('/', $pattern);
            $ip_binary = sprintf("%032b", ip2long($ip));
            $subnet_binary = sprintf("%032b", ip2long($subnet));
            return substr($ip_binary, 0, $bits) === substr($subnet_binary, 0, $bits);
        }

        return false;
    }

    /**
     * Get role display name
     *
     * @return string
     */
    public function getRoleDisplayName(): string
    {
        return self::ROLES[$this->role] ?? 'Unknown';
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('locked_until')
                           ->orWhere('locked_until', '<', now());
                     });
    }

    /**
     * Scope for users by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for primary contacts
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get notification preferences for a specific type
     *
     * @param string $type
     * @return bool
     */
    public function wantsNotification(string $type): bool
    {
        return $this->notification_preferences[$type] ?? false;
    }

    /**
     * Update notification preference
     *
     * @param string $type
     * @param bool $enabled
     * @return void
     */
    public function updateNotificationPreference(string $type, bool $enabled): void
    {
        $preferences = $this->notification_preferences ?? [];
        $preferences[$type] = $enabled;
        $this->update(['notification_preferences' => $preferences]);
    }
}