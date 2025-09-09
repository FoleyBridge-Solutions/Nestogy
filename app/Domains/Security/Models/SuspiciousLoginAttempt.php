<?php

namespace App\Domains\Security\Models;

use App\Models\Company;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SuspiciousLoginAttempt extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'ip_address',
        'verification_token',
        'status',
        'location_data',
        'device_fingerprint',
        'user_agent',
        'trusted_location_requested',
        'risk_score',
        'detection_reasons',
        'approved_at',
        'denied_at',
        'expires_at',
        'notification_sent_at',
        'approval_ip',
        'approval_user_agent',
    ];

    protected $casts = [
        'location_data' => 'array',
        'device_fingerprint' => 'array',
        'detection_reasons' => 'array',
        'trusted_location_requested' => 'boolean',
        'risk_score' => 'integer',
        'approved_at' => 'datetime',
        'denied_at' => 'datetime',
        'expires_at' => 'datetime',
        'notification_sent_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_DENIED = 'denied';
    const STATUS_EXPIRED = 'expired';

    const REASON_NEW_COUNTRY = 'new_country';
    const REASON_NEW_REGION = 'new_region';
    const REASON_VPN_DETECTED = 'vpn_detected';
    const REASON_PROXY_DETECTED = 'proxy_detected';
    const REASON_TOR_DETECTED = 'tor_detected';
    const REASON_SUSPICIOUS_ISP = 'suspicious_isp';
    const REASON_HIGH_RISK_COUNTRY = 'high_risk_country';
    const REASON_IMPOSSIBLE_TRAVEL = 'impossible_travel';
    const REASON_NEW_DEVICE = 'new_device';

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->verification_token)) {
                $model->verification_token = Str::random(64);
            }
            
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addMinutes(config('security.suspicious_login.token_expiry', 60));
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ipLookup(): BelongsTo
    {
        return $this->belongsTo(IpLookupLog::class, 'ip_address', 'ip_address');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->isExpired();
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    public function approve(string $approvalIp = null, string $approvalUserAgent = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_at = now();
        $this->approval_ip = $approvalIp ?: request()->ip();
        $this->approval_user_agent = $approvalUserAgent ?: request()->userAgent();
        
        return $this->save();
    }

    public function deny(string $approvalIp = null, string $approvalUserAgent = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = self::STATUS_DENIED;
        $this->denied_at = now();
        $this->approval_ip = $approvalIp ?: request()->ip();
        $this->approval_user_agent = $approvalUserAgent ?: request()->userAgent();
        
        return $this->save();
    }

    public function getLocationString(): string
    {
        if (!$this->location_data) {
            return 'Unknown Location';
        }

        $parts = array_filter([
            $this->location_data['city'] ?? null,
            $this->location_data['region'] ?? null,
            $this->location_data['country'] ?? null,
        ]);

        return implode(', ', $parts) ?: 'Unknown Location';
    }

    public function getDeviceString(): string
    {
        if (!$this->device_fingerprint) {
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

    public function getRiskLevelString(): string
    {
        if ($this->risk_score >= 80) {
            return 'Critical';
        } elseif ($this->risk_score >= 60) {
            return 'High';
        } elseif ($this->risk_score >= 40) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    public function getRiskLevelColor(): string
    {
        return match ($this->getRiskLevelString()) {
            'Critical' => 'red',
            'High' => 'orange',
            'Medium' => 'yellow',
            'Low' => 'green',
            default => 'gray',
        };
    }

    public function getDetectionReasonsString(): string
    {
        if (!$this->detection_reasons) {
            return 'Unknown reasons';
        }

        $reasons = collect($this->detection_reasons)->map(function ($reason) {
            return match ($reason) {
                self::REASON_NEW_COUNTRY => 'Login from new country',
                self::REASON_NEW_REGION => 'Login from new region',
                self::REASON_VPN_DETECTED => 'VPN connection detected',
                self::REASON_PROXY_DETECTED => 'Proxy connection detected',
                self::REASON_TOR_DETECTED => 'Tor connection detected',
                self::REASON_SUSPICIOUS_ISP => 'Suspicious internet provider',
                self::REASON_HIGH_RISK_COUNTRY => 'High-risk country',
                self::REASON_IMPOSSIBLE_TRAVEL => 'Impossible travel time',
                self::REASON_NEW_DEVICE => 'New device/browser',
                default => ucwords(str_replace('_', ' ', $reason)),
            };
        });

        return $reasons->implode(', ');
    }

    public function getApprovalUrl(): string
    {
        return route('security.suspicious-login.approve', $this->verification_token);
    }

    public function getDenialUrl(): string
    {
        return route('security.suspicious-login.deny', $this->verification_token);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('expires_at', '>', now());
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeDenied($query)
    {
        return $query->where('status', self::STATUS_DENIED);
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_PENDING)
              ->where('expires_at', '<=', now());
        });
    }

    public function scopeHighRisk($query, int $threshold = 60)
    {
        return $query->where('risk_score', '>=', $threshold);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}