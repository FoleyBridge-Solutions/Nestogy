<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Client Portal Session Model
 *
 * Manages secure client authentication sessions for the portal.
 * Supports multi-factor authentication, device tracking, and security monitoring.
 *
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property string $session_token
 * @property string $refresh_token
 * @property string|null $device_id
 * @property string|null $device_name
 * @property string|null $device_type
 * @property string|null $browser_name
 * @property string|null $browser_version
 * @property string|null $os_name
 * @property string|null $os_version
 * @property string $ip_address
 * @property string|null $user_agent
 * @property array|null $location_data
 * @property bool $is_mobile
 * @property bool $is_trusted_device
 * @property bool $two_factor_verified
 * @property string|null $two_factor_method
 * @property \Illuminate\Support\Carbon|null $two_factor_verified_at
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon $refresh_expires_at
 * @property array|null $session_data
 * @property array|null $security_flags
 * @property string $status
 * @property string|null $revocation_reason
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ClientPortalSession extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'client_portal_sessions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'session_token',
        'refresh_token',
        'device_id',
        'device_name',
        'device_type',
        'browser_name',
        'browser_version',
        'os_name',
        'os_version',
        'ip_address',
        'user_agent',
        'location_data',
        'is_mobile',
        'is_trusted_device',
        'two_factor_verified',
        'two_factor_method',
        'two_factor_verified_at',
        'last_activity_at',
        'expires_at',
        'refresh_expires_at',
        'session_data',
        'security_flags',
        'status',
        'revocation_reason',
        'revoked_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'location_data' => 'array',
        'is_mobile' => 'boolean',
        'is_trusted_device' => 'boolean',
        'two_factor_verified' => 'boolean',
        'two_factor_verified_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'refresh_expires_at' => 'datetime',
        'session_data' => 'array',
        'security_flags' => 'array',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'session_token',
        'refresh_token',
    ];

    /**
     * Session status constants
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_EXPIRED = 'expired';

    const STATUS_REVOKED = 'revoked';

    const STATUS_SUSPENDED = 'suspended';

    /**
     * Device type constants
     */
    const DEVICE_WEB = 'web';

    const DEVICE_MOBILE = 'mobile';

    const DEVICE_TABLET = 'tablet';

    /**
     * Two-factor methods
     */
    const TWO_FACTOR_SMS = 'sms';

    const TWO_FACTOR_EMAIL = 'email';

    const TWO_FACTOR_AUTHENTICATOR = 'authenticator';

    /**
     * Get the client that owns the session.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the company that owns the session.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the access logs for this session.
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(PortalAccessLog::class, 'session_id');
    }

    /**
     * Generate a new session token.
     */
    public static function generateSessionToken(): string
    {
        return Hash::make(Str::random(64).microtime(true));
    }

    /**
     * Generate a new refresh token.
     */
    public static function generateRefreshToken(): string
    {
        return Hash::make(Str::random(64).microtime(true));
    }

    /**
     * Check if session is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
               ! $this->isExpired() &&
               ! $this->isRevoked();
    }

    /**
     * Check if session is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && Carbon::now()->gt($this->expires_at);
    }

    /**
     * Check if session is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED ||
               $this->revoked_at !== null;
    }

    /**
     * Check if session is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if refresh token is expired.
     */
    public function isRefreshExpired(): bool
    {
        return $this->refresh_expires_at && Carbon::now()->gt($this->refresh_expires_at);
    }

    /**
     * Check if session requires two-factor authentication.
     */
    public function requiresTwoFactor(): bool
    {
        return ! $this->two_factor_verified &&
               $this->client->portalAccess?->require_two_factor === true;
    }

    /**
     * Mark session as two-factor verified.
     */
    public function markTwoFactorVerified(string $method): bool
    {
        return $this->update([
            'two_factor_verified' => true,
            'two_factor_method' => $method,
            'two_factor_verified_at' => Carbon::now(),
        ]);
    }

    /**
     * Update last activity timestamp.
     */
    public function updateActivity(): bool
    {
        return $this->update(['last_activity_at' => Carbon::now()]);
    }

    /**
     * Revoke the session.
     */
    public function revoke(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REVOKED,
            'revocation_reason' => $reason,
            'revoked_at' => Carbon::now(),
        ]);
    }

    /**
     * Suspend the session.
     */
    public function suspend(): bool
    {
        return $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    /**
     * Reactivate a suspended session.
     */
    public function reactivate(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Extend session expiration.
     */
    public function extendSession(?int $minutes = null): bool
    {
        $minutes = $minutes ?? config('portal.session_lifetime', 120);

        return $this->update([
            'expires_at' => Carbon::now()->addMinutes($minutes),
        ]);
    }

    /**
     * Refresh the session with new tokens.
     */
    public function refresh(): bool
    {
        if ($this->isRefreshExpired()) {
            return false;
        }

        $sessionLifetime = config('portal.session_lifetime', 120);
        $refreshLifetime = config('portal.refresh_lifetime', 10080); // 7 days

        return $this->update([
            'session_token' => self::generateSessionToken(),
            'expires_at' => Carbon::now()->addMinutes($sessionLifetime),
            'refresh_expires_at' => Carbon::now()->addMinutes($refreshLifetime),
            'last_activity_at' => Carbon::now(),
        ]);
    }

    /**
     * Get session duration in minutes.
     */
    public function getDurationMinutes(): int
    {
        if (! $this->last_activity_at) {
            return 0;
        }

        return $this->created_at->diffInMinutes($this->last_activity_at);
    }

    /**
     * Get time until expiration in minutes.
     */
    public function getTimeUntilExpiration(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        $minutes = Carbon::now()->diffInMinutes($this->expires_at, false);

        return $minutes > 0 ? $minutes : 0;
    }

    /**
     * Check if session is from a trusted device.
     */
    public function isTrustedDevice(): bool
    {
        return $this->is_trusted_device === true;
    }

    /**
     * Mark device as trusted.
     */
    public function trustDevice(): bool
    {
        return $this->update(['is_trusted_device' => true]);
    }

    /**
     * Get security risk score based on session attributes.
     */
    public function getSecurityRiskScore(): int
    {
        $score = 0;

        // Base score
        if (! $this->two_factor_verified) {
            $score += 30;
        }

        if (! $this->is_trusted_device) {
            $score += 20;
        }

        if ($this->is_mobile) {
            $score += 10;
        }

        // Location-based risk
        if (isset($this->location_data['country'])) {
            $riskCountries = config('portal.high_risk_countries', []);
            if (in_array($this->location_data['country'], $riskCountries)) {
                $score += 40;
            }
        }

        // Session age risk
        $ageHours = $this->created_at->diffInHours(Carbon::now());
        if ($ageHours > 24) {
            $score += 15;
        }

        return min($score, 100);
    }

    /**
     * Scope to get only active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to get expired sessions.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now())
            ->orWhere('status', self::STATUS_EXPIRED);
    }

    /**
     * Scope to get sessions for cleanup.
     */
    public function scopeForCleanup($query)
    {
        return $query->where(function ($q) {
            $q->where('expires_at', '<=', Carbon::now()->subDays(7))
                ->orWhere('status', self::STATUS_REVOKED);
        });
    }

    /**
     * Scope to get sessions by device type.
     */
    public function scopeByDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope to get mobile sessions.
     */
    public function scopeMobile($query)
    {
        return $query->where('is_mobile', true);
    }

    /**
     * Scope to get trusted device sessions.
     */
    public function scopeTrustedDevices($query)
    {
        return $query->where('is_trusted_device', true);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (! $session->session_token) {
                $session->session_token = self::generateSessionToken();
            }

            if (! $session->refresh_token) {
                $session->refresh_token = self::generateRefreshToken();
            }

            if (! $session->expires_at) {
                $minutes = config('portal.session_lifetime', 120);
                $session->expires_at = Carbon::now()->addMinutes($minutes);
            }

            if (! $session->refresh_expires_at) {
                $minutes = config('portal.refresh_lifetime', 10080); // 7 days
                $session->refresh_expires_at = Carbon::now()->addMinutes($minutes);
            }

            $session->last_activity_at = Carbon::now();
        });

        static::updating(function ($session) {
            if ($session->isDirty(['last_activity_at', 'expires_at']) === false) {
                $session->last_activity_at = Carbon::now();
            }
        });
    }
}
