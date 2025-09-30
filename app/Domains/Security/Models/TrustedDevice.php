<?php

namespace App\Domains\Security\Models;

use App\Models\Company;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrustedDevice extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'device_fingerprint',
        'device_name',
        'ip_address',
        'location_data',
        'user_agent',
        'trust_level',
        'last_used_at',
        'expires_at',
        'is_active',
        'verification_method',
        'created_from_suspicious_login',
    ];

    protected $casts = [
        'device_fingerprint' => 'array',
        'location_data' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'created_from_suspicious_login' => 'boolean',
        'trust_level' => 'integer',
    ];

    const TRUST_LEVEL_LOW = 25;

    const TRUST_LEVEL_MEDIUM = 50;

    const TRUST_LEVEL_HIGH = 75;

    const TRUST_LEVEL_FULL = 100;

    const VERIFICATION_EMAIL = 'email';

    const VERIFICATION_SMS = 'sms';

    const VERIFICATION_MANUAL = 'manual';

    const VERIFICATION_SUSPICIOUS_LOGIN = 'suspicious_login';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function isTrusted(): bool
    {
        return $this->isActive() && $this->trust_level >= self::TRUST_LEVEL_MEDIUM;
    }

    public function updateLastUsed(): bool
    {
        $this->last_used_at = now();

        return $this->save();
    }

    public function extendExpiry(int $days = 30): bool
    {
        $this->expires_at = now()->addDays($days);

        return $this->save();
    }

    public function revoke(): bool
    {
        $this->is_active = false;

        return $this->save();
    }

    public function getDeviceString(): string
    {
        if ($this->device_name) {
            return $this->device_name;
        }

        if (! $this->device_fingerprint) {
            return 'Unknown Device';
        }

        $device = $this->device_fingerprint;
        $parts = [];

        if (isset($device['browser'])) {
            $parts[] = $device['browser'];
        }

        if (isset($device['os'])) {
            $parts[] = $device['os'];
        }

        if (isset($device['device_type'])) {
            $parts[] = ucfirst($device['device_type']);
        }

        return implode(' on ', $parts) ?: 'Unknown Device';
    }

    public function getLocationString(): string
    {
        if (! $this->location_data) {
            return 'Unknown Location';
        }

        $parts = array_filter([
            $this->location_data['city'] ?? null,
            $this->location_data['region'] ?? null,
            $this->location_data['country'] ?? null,
        ]);

        return implode(', ', $parts) ?: 'Unknown Location';
    }

    public function getTrustLevelString(): string
    {
        return match (true) {
            $this->trust_level >= self::TRUST_LEVEL_FULL => 'Full Trust',
            $this->trust_level >= self::TRUST_LEVEL_HIGH => 'High Trust',
            $this->trust_level >= self::TRUST_LEVEL_MEDIUM => 'Medium Trust',
            $this->trust_level >= self::TRUST_LEVEL_LOW => 'Low Trust',
            default => 'No Trust',
        };
    }

    public function getTrustLevelColor(): string
    {
        return match (true) {
            $this->trust_level >= self::TRUST_LEVEL_FULL => 'green',
            $this->trust_level >= self::TRUST_LEVEL_HIGH => 'blue',
            $this->trust_level >= self::TRUST_LEVEL_MEDIUM => 'yellow',
            $this->trust_level >= self::TRUST_LEVEL_LOW => 'orange',
            default => 'red',
        };
    }

    public function getStatusString(): string
    {
        if (! $this->is_active) {
            return 'Revoked';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        return 'Active';
    }

    public function getStatusColor(): string
    {
        return match ($this->getStatusString()) {
            'Active' => 'green',
            'Expired' => 'yellow',
            'Revoked' => 'red',
            default => 'gray',
        };
    }

    public function matchesFingerprint(array $fingerprint): bool
    {
        if (! $this->device_fingerprint || ! $fingerprint) {
            return false;
        }

        $currentDevice = $this->device_fingerprint;
        $score = 0;
        $total = 0;

        $fields = ['browser', 'browser_version', 'os', 'os_version', 'screen_resolution', 'timezone'];

        foreach ($fields as $field) {
            if (isset($currentDevice[$field]) && isset($fingerprint[$field])) {
                $total++;
                if ($currentDevice[$field] === $fingerprint[$field]) {
                    $score++;
                }
            }
        }

        return $total > 0 && ($score / $total) >= 0.8;
    }

    public function matchesLocation(array $location, int $distanceThreshold = 100): bool
    {
        if (! $this->location_data || ! $location) {
            return false;
        }

        $currentLat = $this->location_data['latitude'] ?? null;
        $currentLon = $this->location_data['longitude'] ?? null;
        $newLat = $location['latitude'] ?? null;
        $newLon = $location['longitude'] ?? null;

        if (! $currentLat || ! $currentLon || ! $newLat || ! $newLon) {
            return ($this->location_data['country_code'] ?? '') === ($location['country_code'] ?? '');
        }

        $distance = $this->calculateDistance($currentLat, $currentLon, $newLat, $newLon);

        return $distance <= $distanceThreshold;
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeTrusted($query, int $minTrustLevel = self::TRUST_LEVEL_MEDIUM)
    {
        return $query->where('trust_level', '>=', $minTrustLevel);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDevice($query, array $fingerprint)
    {
        return $query->whereJsonContains('device_fingerprint', $fingerprint);
    }

    public function scopeRecentlyUsed($query, int $days = 30)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }
}
