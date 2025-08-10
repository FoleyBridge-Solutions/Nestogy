<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Client Portal Access Model
 * 
 * Manages access control and permissions for client portal users.
 * Controls feature access, security settings, and compliance requirements.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property bool $portal_enabled
 * @property string $access_level
 * @property array|null $permissions
 * @property array|null $allowed_features
 * @property array|null $restricted_features
 * @property bool $invoice_access
 * @property bool $payment_access
 * @property bool $document_access
 * @property bool $support_access
 * @property bool $contract_access
 * @property bool $billing_history_access
 * @property bool $service_history_access
 * @property bool $usage_analytics_access
 * @property bool $auto_pay_management
 * @property bool $payment_method_management
 * @property bool $profile_management
 * @property bool $notification_management
 * @property bool $can_download_invoices
 * @property bool $can_download_documents
 * @property bool $can_submit_tickets
 * @property bool $can_view_ticket_history
 * @property bool $can_schedule_payments
 * @property bool $can_setup_payment_plans
 * @property bool $can_dispute_charges
 * @property bool $can_request_service_changes
 * @property array|null $ip_whitelist
 * @property array|null $ip_blacklist
 * @property array|null $allowed_countries
 * @property array|null $blocked_countries
 * @property array|null $time_restrictions
 * @property bool $require_two_factor
 * @property bool $require_device_verification
 * @property int $max_concurrent_sessions
 * @property int $session_timeout_minutes
 * @property bool $auto_logout_on_inactivity
 * @property int|null $password_expiry_days
 * @property bool $require_password_change
 * @property array|null $security_settings
 * @property string|null $custom_domain
 * @property array|null $branding_settings
 * @property array|null $notification_preferences
 * @property string $preferred_language
 * @property string $timezone
 * @property string|null $currency_preference
 * @property array|null $dashboard_layout
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $access_granted_at
 * @property \Illuminate\Support\Carbon|null $access_revoked_at
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $last_password_change_at
 * @property int $failed_login_attempts
 * @property \Illuminate\Support\Carbon|null $account_locked_until
 * @property string|null $access_notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ClientPortalAccess extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'client_portal_access';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'portal_enabled',
        'access_level',
        'permissions',
        'allowed_features',
        'restricted_features',
        'invoice_access',
        'payment_access',
        'document_access',
        'support_access',
        'contract_access',
        'billing_history_access',
        'service_history_access',
        'usage_analytics_access',
        'auto_pay_management',
        'payment_method_management',
        'profile_management',
        'notification_management',
        'can_download_invoices',
        'can_download_documents',
        'can_submit_tickets',
        'can_view_ticket_history',
        'can_schedule_payments',
        'can_setup_payment_plans',
        'can_dispute_charges',
        'can_request_service_changes',
        'ip_whitelist',
        'ip_blacklist',
        'allowed_countries',
        'blocked_countries',
        'time_restrictions',
        'require_two_factor',
        'require_device_verification',
        'max_concurrent_sessions',
        'session_timeout_minutes',
        'auto_logout_on_inactivity',
        'password_expiry_days',
        'require_password_change',
        'security_settings',
        'custom_domain',
        'branding_settings',
        'notification_preferences',
        'preferred_language',
        'timezone',
        'currency_preference',
        'dashboard_layout',
        'metadata',
        'access_granted_at',
        'access_revoked_at',
        'last_login_at',
        'last_password_change_at',
        'failed_login_attempts',
        'account_locked_until',
        'access_notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'portal_enabled' => 'boolean',
        'permissions' => 'array',
        'allowed_features' => 'array',
        'restricted_features' => 'array',
        'invoice_access' => 'boolean',
        'payment_access' => 'boolean',
        'document_access' => 'boolean',
        'support_access' => 'boolean',
        'contract_access' => 'boolean',
        'billing_history_access' => 'boolean',
        'service_history_access' => 'boolean',
        'usage_analytics_access' => 'boolean',
        'auto_pay_management' => 'boolean',
        'payment_method_management' => 'boolean',
        'profile_management' => 'boolean',
        'notification_management' => 'boolean',
        'can_download_invoices' => 'boolean',
        'can_download_documents' => 'boolean',
        'can_submit_tickets' => 'boolean',
        'can_view_ticket_history' => 'boolean',
        'can_schedule_payments' => 'boolean',
        'can_setup_payment_plans' => 'boolean',
        'can_dispute_charges' => 'boolean',
        'can_request_service_changes' => 'boolean',
        'ip_whitelist' => 'array',
        'ip_blacklist' => 'array',
        'allowed_countries' => 'array',
        'blocked_countries' => 'array',
        'time_restrictions' => 'array',
        'require_two_factor' => 'boolean',
        'require_device_verification' => 'boolean',
        'max_concurrent_sessions' => 'integer',
        'session_timeout_minutes' => 'integer',
        'auto_logout_on_inactivity' => 'boolean',
        'password_expiry_days' => 'integer',
        'require_password_change' => 'boolean',
        'security_settings' => 'array',
        'branding_settings' => 'array',
        'notification_preferences' => 'array',
        'dashboard_layout' => 'array',
        'metadata' => 'array',
        'access_granted_at' => 'datetime',
        'access_revoked_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_password_change_at' => 'datetime',
        'failed_login_attempts' => 'integer',
        'account_locked_until' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Access level constants
     */
    const ACCESS_FULL = 'full';
    const ACCESS_LIMITED = 'limited';
    const ACCESS_BILLING_ONLY = 'billing_only';
    const ACCESS_VIEW_ONLY = 'view_only';

    /**
     * Available access levels
     */
    const ACCESS_LEVELS = [
        self::ACCESS_FULL => 'Full Access',
        self::ACCESS_LIMITED => 'Limited Access',
        self::ACCESS_BILLING_ONLY => 'Billing Only',
        self::ACCESS_VIEW_ONLY => 'View Only',
    ];

    /**
     * Get the client that owns this access record.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the company that owns this access record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this access record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this access record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the portal sessions for this client.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClientPortalSession::class, 'client_id', 'client_id');
    }

    /**
     * Check if portal access is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->portal_enabled === true;
    }

    /**
     * Check if access is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->access_revoked_at !== null;
    }

    /**
     * Check if account is locked.
     */
    public function isLocked(): bool
    {
        return $this->account_locked_until && Carbon::now()->lt($this->account_locked_until);
    }

    /**
     * Check if password has expired.
     */
    public function isPasswordExpired(): bool
    {
        if (!$this->password_expiry_days || !$this->last_password_change_at) {
            return false;
        }

        $expiryDate = $this->last_password_change_at->addDays($this->password_expiry_days);
        return Carbon::now()->gt($expiryDate);
    }

    /**
     * Check if client has specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Check if permission is explicitly granted
        if (is_array($this->permissions) && in_array($permission, $this->permissions)) {
            return true;
        }

        // Check access level permissions
        return $this->hasAccessLevelPermission($permission);
    }

    /**
     * Check if feature is allowed.
     */
    public function canAccessFeature(string $feature): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // Check if feature is explicitly restricted
        if (is_array($this->restricted_features) && in_array($feature, $this->restricted_features)) {
            return false;
        }

        // Check if feature is explicitly allowed
        if (is_array($this->allowed_features) && in_array($feature, $this->allowed_features)) {
            return true;
        }

        // Check access level features
        return $this->hasAccessLevelFeature($feature);
    }

    /**
     * Check if IP address is allowed.
     */
    public function isIpAllowed(string $ipAddress): bool
    {
        // Check blacklist first
        if (is_array($this->ip_blacklist) && in_array($ipAddress, $this->ip_blacklist)) {
            return false;
        }

        // If whitelist is set, IP must be in it
        if (is_array($this->ip_whitelist) && !empty($this->ip_whitelist)) {
            return in_array($ipAddress, $this->ip_whitelist);
        }

        return true;
    }

    /**
     * Check if country is allowed.
     */
    public function isCountryAllowed(string $countryCode): bool
    {
        // Check blocked countries first
        if (is_array($this->blocked_countries) && in_array($countryCode, $this->blocked_countries)) {
            return false;
        }

        // If allowed countries is set, country must be in it
        if (is_array($this->allowed_countries) && !empty($this->allowed_countries)) {
            return in_array($countryCode, $this->allowed_countries);
        }

        return true;
    }

    /**
     * Check if current time is within allowed access hours.
     */
    public function isTimeAllowed(Carbon $time = null): bool
    {
        $time = $time ?? Carbon::now($this->timezone);
        
        if (!is_array($this->time_restrictions) || empty($this->time_restrictions)) {
            return true;
        }

        $dayOfWeek = strtolower($time->englishDayOfWeek);
        $currentTime = $time->format('H:i');

        if (!isset($this->time_restrictions[$dayOfWeek])) {
            return true;
        }

        $restrictions = $this->time_restrictions[$dayOfWeek];
        
        if (!isset($restrictions['start']) || !isset($restrictions['end'])) {
            return true;
        }

        return $currentTime >= $restrictions['start'] && $currentTime <= $restrictions['end'];
    }

    /**
     * Increment failed login attempts.
     */
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        $maxAttempts = config('portal.max_login_attempts', 5);
        $lockDuration = config('portal.login_lock_duration', 30); // minutes
        
        if ($this->failed_login_attempts >= $maxAttempts) {
            $this->update([
                'account_locked_until' => Carbon::now()->addMinutes($lockDuration)
            ]);
        }
    }

    /**
     * Reset failed login attempts.
     */
    public function resetFailedAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'account_locked_until' => null,
            'last_login_at' => Carbon::now(),
        ]);
    }

    /**
     * Grant portal access.
     */
    public function grantAccess(): bool
    {
        return $this->update([
            'portal_enabled' => true,
            'access_granted_at' => Carbon::now(),
            'access_revoked_at' => null,
        ]);
    }

    /**
     * Revoke portal access.
     */
    public function revokeAccess(): bool
    {
        return $this->update([
            'portal_enabled' => false,
            'access_revoked_at' => Carbon::now(),
        ]);
    }

    /**
     * Update password change timestamp.
     */
    public function markPasswordChanged(): bool
    {
        return $this->update([
            'last_password_change_at' => Carbon::now(),
            'require_password_change' => false,
        ]);
    }

    /**
     * Get default permissions for access level.
     */
    public function getDefaultPermissions(): array
    {
        switch ($this->access_level) {
            case self::ACCESS_FULL:
                return [
                    'view_invoices', 'pay_invoices', 'view_payments', 'manage_payment_methods',
                    'view_documents', 'download_documents', 'submit_tickets', 'view_tickets',
                    'view_contracts', 'manage_profile', 'setup_autopay', 'schedule_payments',
                    'setup_payment_plans', 'dispute_charges', 'request_service_changes'
                ];
                
            case self::ACCESS_LIMITED:
                return [
                    'view_invoices', 'pay_invoices', 'view_payments', 'manage_payment_methods',
                    'view_documents', 'submit_tickets', 'view_tickets', 'manage_profile'
                ];
                
            case self::ACCESS_BILLING_ONLY:
                return [
                    'view_invoices', 'pay_invoices', 'view_payments', 'manage_payment_methods'
                ];
                
            case self::ACCESS_VIEW_ONLY:
                return [
                    'view_invoices', 'view_payments', 'view_documents', 'view_tickets'
                ];
                
            default:
                return [];
        }
    }

    /**
     * Get security configuration.
     */
    public function getSecurityConfig(): array
    {
        return array_merge([
            'require_two_factor' => false,
            'require_device_verification' => false,
            'max_concurrent_sessions' => 3,
            'session_timeout_minutes' => 120,
            'auto_logout_on_inactivity' => true,
            'password_expiry_days' => null,
        ], $this->security_settings ?? []);
    }

    /**
     * Private helper methods
     */
    private function hasAccessLevelPermission(string $permission): bool
    {
        $permissions = $this->getDefaultPermissions();
        return in_array($permission, $permissions);
    }

    private function hasAccessLevelFeature(string $feature): bool
    {
        // Map features to access levels
        $featureMap = [
            'invoices' => ['full', 'limited', 'billing_only', 'view_only'],
            'payments' => ['full', 'limited', 'billing_only', 'view_only'],
            'documents' => ['full', 'limited', 'view_only'],
            'support' => ['full', 'limited', 'view_only'],
            'contracts' => ['full'],
            'analytics' => ['full'],
            'service_changes' => ['full'],
        ];

        if (!isset($featureMap[$feature])) {
            return false;
        }

        return in_array($this->access_level, $featureMap[$feature]);
    }

    /**
     * Scope to get enabled access records.
     */
    public function scopeEnabled($query)
    {
        return $query->where('portal_enabled', true)
                    ->whereNull('access_revoked_at');
    }

    /**
     * Scope to get access records by level.
     */
    public function scopeByAccessLevel($query, string $level)
    {
        return $query->where('access_level', $level);
    }

    /**
     * Scope to get locked accounts.
     */
    public function scopeLocked($query)
    {
        return $query->where('account_locked_until', '>', Carbon::now());
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($access) {
            if (!$access->access_granted_at && $access->portal_enabled) {
                $access->access_granted_at = Carbon::now();
            }

            // Set default permissions based on access level
            if (!$access->permissions) {
                $access->permissions = $access->getDefaultPermissions();
            }
        });

        static::updating(function ($access) {
            if ($access->isDirty('portal_enabled')) {
                if ($access->portal_enabled) {
                    $access->access_granted_at = Carbon::now();
                    $access->access_revoked_at = null;
                } else {
                    $access->access_revoked_at = Carbon::now();
                }
            }
        });
    }
}