<?php

namespace App\Domains\Security\Models;

use App\Models\Company;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IpLookupLog extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'ip_address',
        'country',
        'country_code',
        'region',
        'region_code',
        'city',
        'zip',
        'latitude',
        'longitude',
        'timezone',
        'isp',
        'is_valid',
        'is_vpn',
        'is_proxy',
        'is_tor',
        'threat_level',
        'lookup_source',
        'api_response',
        'cached_until',
        'lookup_count',
        'last_lookup_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'is_valid' => 'boolean',
        'is_vpn' => 'boolean',
        'is_proxy' => 'boolean',
        'is_tor' => 'boolean',
        'api_response' => 'array',
        'cached_until' => 'datetime',
        'last_lookup_at' => 'datetime',
        'lookup_count' => 'integer',
    ];

    const THREAT_LEVEL_LOW = 'low';

    const THREAT_LEVEL_MEDIUM = 'medium';

    const THREAT_LEVEL_HIGH = 'high';

    const THREAT_LEVEL_CRITICAL = 'critical';

    const LOOKUP_SOURCE_API_NINJAS = 'api_ninjas';

    const LOOKUP_SOURCE_IPAPI = 'ipapi';

    const LOOKUP_SOURCE_MAXMIND = 'maxmind';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isExpired(): bool
    {
        return $this->cached_until && $this->cached_until->isPast();
    }

    public function isSuspicious(): bool
    {
        return $this->is_vpn || $this->is_proxy || $this->is_tor ||
               in_array($this->threat_level, [self::THREAT_LEVEL_HIGH, self::THREAT_LEVEL_CRITICAL]);
    }

    public function getThreatScore(): int
    {
        $score = 0;

        if ($this->is_vpn) {
            $score += 30;
        }
        if ($this->is_proxy) {
            $score += 25;
        }
        if ($this->is_tor) {
            $score += 50;
        }

        switch ($this->threat_level) {
            case self::THREAT_LEVEL_CRITICAL:
                $score += 100;
                break;
            case self::THREAT_LEVEL_HIGH:
                $score += 75;
                break;
            case self::THREAT_LEVEL_MEDIUM:
                $score += 40;
                break;
            case self::THREAT_LEVEL_LOW:
                $score += 10;
                break;
        }

        return min($score, 100);
    }

    public function getLocationString(): string
    {
        $parts = array_filter([$this->city, $this->region, $this->country]);

        return implode(', ', $parts);
    }

    public function scopeBySuspicious($query, bool $suspicious = true)
    {
        if ($suspicious) {
            return $query->where(function ($q) {
                $q->where('is_vpn', true)
                    ->orWhere('is_proxy', true)
                    ->orWhere('is_tor', true)
                    ->orWhereIn('threat_level', [self::THREAT_LEVEL_HIGH, self::THREAT_LEVEL_CRITICAL]);
            });
        }

        return $query->where('is_vpn', false)
            ->where('is_proxy', false)
            ->where('is_tor', false)
            ->whereNotIn('threat_level', [self::THREAT_LEVEL_HIGH, self::THREAT_LEVEL_CRITICAL]);
    }

    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    public function scopeByThreatLevel($query, string $level)
    {
        return $query->where('threat_level', $level);
    }

    public function scopeExpired($query)
    {
        return $query->where('cached_until', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('cached_until', '>', now());
    }
}
