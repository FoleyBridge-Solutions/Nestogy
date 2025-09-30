<?php

namespace App\Domains\Security\Services;

use App\Domains\Core\Services\BaseService;
use App\Domains\Security\Models\SuspiciousLoginAttempt;
use App\Domains\Security\Models\TrustedDevice;
use App\Domains\Security\Models\IpLookupLog;
use App\Domains\Security\Services\IpLookupService;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SuspiciousLoginService extends BaseService
{
    protected IpLookupService $ipLookupService;

    public function __construct(IpLookupService $ipLookupService)
    {
        $this->ipLookupService = $ipLookupService;
        parent::__construct();
    }

    protected function initializeService(): void
    {
        $this->modelClass = SuspiciousLoginAttempt::class;
        $this->defaultEagerLoad = ['user', 'company'];
        $this->searchableFields = ['ip_address'];
    }

    public function analyzeLoginAttempt(User $user, Request $request): ?SuspiciousLoginAttempt
    {
        if (!config('security.suspicious_login.enabled', true)) {
            return null;
        }

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $deviceFingerprint = $this->generateDeviceFingerprint($request);

        $riskScore = 0;
        $detectionReasons = [];

        $ipLookup = $this->ipLookupService->lookupIp($ipAddress);
        
        if ($ipLookup) {
            $locationAnalysis = $this->analyzeLocation($user, $ipLookup);
            $riskScore += $locationAnalysis['risk_score'];
            $detectionReasons = array_merge($detectionReasons, $locationAnalysis['reasons']);
        }

        $deviceAnalysis = $this->analyzeDevice($user, $deviceFingerprint, $ipAddress);
        $riskScore += $deviceAnalysis['risk_score'];
        $detectionReasons = array_merge($detectionReasons, $deviceAnalysis['reasons']);

        $behaviorAnalysis = $this->analyzeBehavior($user, $ipAddress);
        $riskScore += $behaviorAnalysis['risk_score'];
        $detectionReasons = array_merge($detectionReasons, $behaviorAnalysis['reasons']);

        $riskThreshold = config('security.suspicious_login.risk_threshold', 60);
        
        if ($riskScore >= $riskThreshold) {
            return $this->createSuspiciousLoginAttempt($user, $request, $ipLookup, $deviceFingerprint, $riskScore, $detectionReasons);
        }

        $this->updateTrustedDevice($user, $deviceFingerprint, $ipAddress, $ipLookup);

        return null;
    }

    protected function analyzeLocation(User $user, IpLookupLog $ipLookup): array
    {
        $riskScore = 0;
        $reasons = [];

        if ($ipLookup->isSuspicious()) {
            if ($ipLookup->is_vpn) {
                $riskScore += 30;
                $reasons[] = SuspiciousLoginAttempt::REASON_VPN_DETECTED;
            }
            
            if ($ipLookup->is_proxy) {
                $riskScore += 25;
                $reasons[] = SuspiciousLoginAttempt::REASON_PROXY_DETECTED;
            }
            
            if ($ipLookup->is_tor) {
                $riskScore += 50;
                $reasons[] = SuspiciousLoginAttempt::REASON_TOR_DETECTED;
            }
        }

        $userLoginHistory = $this->getUserLoginHistory($user);
        
        if (!$this->hasUserLoggedInFromCountry($userLoginHistory, $ipLookup->country_code)) {
            $riskScore += 40;
            $reasons[] = SuspiciousLoginAttempt::REASON_NEW_COUNTRY;
        } elseif (!$this->hasUserLoggedInFromRegion($userLoginHistory, $ipLookup->country_code, $ipLookup->region)) {
            $riskScore += 20;
            $reasons[] = SuspiciousLoginAttempt::REASON_NEW_REGION;
        }

        $lastLoginLocation = $this->getLastLoginLocation($user);
        if ($lastLoginLocation && $ipLookup->latitude && $ipLookup->longitude) {
            $distance = $this->calculateDistance(
                $lastLoginLocation['latitude'],
                $lastLoginLocation['longitude'],
                $ipLookup->latitude,
                $ipLookup->longitude
            );

            $lastLoginTime = $this->getLastLoginTime($user);
            if ($lastLoginTime) {
                $timeDiffHours = now()->diffInHours($lastLoginTime);
                $maxPossibleSpeed = 900;
                
                if ($timeDiffHours > 0 && ($distance / $timeDiffHours) > $maxPossibleSpeed) {
                    $riskScore += 35;
                    $reasons[] = SuspiciousLoginAttempt::REASON_IMPOSSIBLE_TRAVEL;
                }
            }
        }

        $highRiskCountries = config('security.geo_blocking.high_risk_countries', []);
        if (in_array($ipLookup->country_code, $highRiskCountries)) {
            $riskScore += 25;
            $reasons[] = SuspiciousLoginAttempt::REASON_HIGH_RISK_COUNTRY;
        }

        return ['risk_score' => $riskScore, 'reasons' => $reasons];
    }

    protected function analyzeDevice(User $user, array $deviceFingerprint, string $ipAddress): array
    {
        $riskScore = 0;
        $reasons = [];

        $trustedDevices = TrustedDevice::byUser($user->id)
            ->active()
            ->trusted()
            ->get();

        $deviceMatches = false;
        foreach ($trustedDevices as $trustedDevice) {
            if ($trustedDevice->matchesFingerprint($deviceFingerprint)) {
                $deviceMatches = true;
                break;
            }
        }

        if (!$deviceMatches) {
            $riskScore += 30;
            $reasons[] = SuspiciousLoginAttempt::REASON_NEW_DEVICE;
        }

        return ['risk_score' => $riskScore, 'reasons' => $reasons];
    }

    protected function analyzeBehavior(User $user, string $ipAddress): array
    {
        $riskScore = 0;
        $reasons = [];

        $recentFailedAttempts = AuditLog::where('user_id', $user->id)
            ->where('event_type', AuditLog::EVENT_LOGIN)
            ->where('response_status', '>=', 400)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($recentFailedAttempts >= 3) {
            $riskScore += 15;
        }

        $concurrentSessions = AuditLog::where('user_id', $user->id)
            ->where('event_type', AuditLog::EVENT_LOGIN)
            ->where('response_status', '<', 400)
            ->where('created_at', '>=', now()->subHours(1))
            ->distinct('ip_address')
            ->count();

        if ($concurrentSessions > 2) {
            $riskScore += 20;
        }

        return ['risk_score' => $riskScore, 'reasons' => $reasons];
    }

    protected function createSuspiciousLoginAttempt(
        User $user, 
        Request $request, 
        ?IpLookupLog $ipLookup, 
        array $deviceFingerprint, 
        int $riskScore, 
        array $reasons
    ): SuspiciousLoginAttempt {
        $locationData = null;
        if ($ipLookup) {
            $locationData = [
                'country' => $ipLookup->country,
                'country_code' => $ipLookup->country_code,
                'region' => $ipLookup->region,
                'city' => $ipLookup->city,
                'latitude' => $ipLookup->latitude,
                'longitude' => $ipLookup->longitude,
                'isp' => $ipLookup->isp,
                'timezone' => $ipLookup->timezone,
            ];
        }

        $attempt = SuspiciousLoginAttempt::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'location_data' => $locationData,
            'device_fingerprint' => $deviceFingerprint,
            'user_agent' => $request->userAgent(),
            'risk_score' => min($riskScore, 100),
            'detection_reasons' => $reasons,
            'expires_at' => now()->addMinutes(config('security.suspicious_login.token_expiry', 60)),
        ]);

        $this->sendSuspiciousLoginNotification($attempt);

        AuditLog::logSecurity('Suspicious Login Detected', [
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'risk_score' => $riskScore,
            'reasons' => $reasons,
            'location' => $ipLookup?->getLocationString(),
            'verification_token' => $attempt->verification_token,
        ], AuditLog::SEVERITY_WARNING);

        return $attempt;
    }

    protected function sendSuspiciousLoginNotification(SuspiciousLoginAttempt $attempt): void
    {
        if (!config('security.suspicious_login.email_enabled', true)) {
            return;
        }

        try {
            Mail::to($attempt->user->email)->send(
                new \App\Mail\SuspiciousLoginAttemptMail($attempt)
            );

            $attempt->update(['notification_sent_at' => now()]);

            Log::info('Suspicious login notification sent', [
                'user_id' => $attempt->user_id,
                'ip_address' => $attempt->ip_address,
                'token' => $attempt->verification_token,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send suspicious login notification', [
                'user_id' => $attempt->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function approveLoginAttempt(string $token, Request $request): bool
    {
        $attempt = SuspiciousLoginAttempt::where('verification_token', $token)
            ->pending()
            ->first();

        if (!$attempt) {
            return false;
        }

        $success = $attempt->approve($request->ip(), $request->userAgent());

        if ($success) {
            if ($attempt->trusted_location_requested) {
                $this->createTrustedDevice($attempt);
            }

            AuditLog::logSecurity('Suspicious Login Approved', [
                'user_id' => $attempt->user_id,
                'original_ip' => $attempt->ip_address,
                'approval_ip' => $request->ip(),
                'token' => $token,
            ], AuditLog::SEVERITY_INFO);
        }

        return $success;
    }

    public function denyLoginAttempt(string $token, Request $request): bool
    {
        $attempt = SuspiciousLoginAttempt::where('verification_token', $token)
            ->pending()
            ->first();

        if (!$attempt) {
            return false;
        }

        $success = $attempt->deny($request->ip(), $request->userAgent());

        if ($success) {
            AuditLog::logSecurity('Suspicious Login Denied', [
                'user_id' => $attempt->user_id,
                'original_ip' => $attempt->ip_address,
                'denial_ip' => $request->ip(),
                'token' => $token,
            ], AuditLog::SEVERITY_WARNING);

            $this->blockSuspiciousIp($attempt->ip_address);
        }

        return $success;
    }

    protected function createTrustedDevice(SuspiciousLoginAttempt $attempt): TrustedDevice
    {
        return TrustedDevice::create([
            'company_id' => $attempt->company_id,
            'user_id' => $attempt->user_id,
            'device_fingerprint' => $attempt->device_fingerprint,
            'ip_address' => $attempt->ip_address,
            'location_data' => $attempt->location_data,
            'user_agent' => $attempt->user_agent,
            'trust_level' => TrustedDevice::TRUST_LEVEL_MEDIUM,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30),
            'verification_method' => TrustedDevice::VERIFICATION_SUSPICIOUS_LOGIN,
            'created_from_suspicious_login' => true,
        ]);
    }

    protected function updateTrustedDevice(User $user, array $deviceFingerprint, string $ipAddress, ?IpLookupLog $ipLookup): void
    {
        $trustedDevice = TrustedDevice::byUser($user->id)
            ->active()
            ->whereJsonContains('device_fingerprint', $deviceFingerprint)
            ->first();

        if ($trustedDevice) {
            $trustedDevice->updateLastUsed();
            $trustedDevice->extendExpiry();
        }
    }

    protected function blockSuspiciousIp(string $ipAddress): void
    {
        // This could integrate with firewall or security services
        Log::warning('IP address should be blocked', ['ip' => $ipAddress]);
        
        // Update the threat level in IP lookup log
        $ipLookup = IpLookupLog::where('ip_address', $ipAddress)->first();
        if ($ipLookup) {
            $ipLookup->update(['threat_level' => IpLookupLog::THREAT_LEVEL_CRITICAL]);
        }
    }

    protected function generateDeviceFingerprint(Request $request): array
    {
        $userAgent = $request->userAgent();
        
        $fingerprint = [
            'user_agent_hash' => md5($userAgent),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
        ];

        if ($userAgent) {
            $parsed = $this->parseUserAgent($userAgent);
            $fingerprint = array_merge($fingerprint, $parsed);
        }

        return $fingerprint;
    }

    protected function parseUserAgent(string $userAgent): array
    {
        $result = [
            'browser' => 'Unknown',
            'browser_version' => null,
            'os' => 'Unknown',
            'os_version' => null,
            'device_type' => 'desktop',
        ];

        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Chrome';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Firefox';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Safari';
            $result['browser_version'] = $matches[1];
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent, $matches)) {
            $result['browser'] = 'Edge';
            $result['browser_version'] = $matches[1];
        }

        if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
            $result['os'] = 'Windows';
            $result['os_version'] = $matches[1];
        } elseif (preg_match('/Mac OS X ([0-9._]+)/', $userAgent, $matches)) {
            $result['os'] = 'macOS';
            $result['os_version'] = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Linux/', $userAgent)) {
            $result['os'] = 'Linux';
        } elseif (preg_match('/iPhone OS ([0-9._]+)/', $userAgent, $matches)) {
            $result['os'] = 'iOS';
            $result['os_version'] = str_replace('_', '.', $matches[1]);
            $result['device_type'] = 'mobile';
        } elseif (preg_match('/Android ([0-9.]+)/', $userAgent, $matches)) {
            $result['os'] = 'Android';
            $result['os_version'] = $matches[1];
            $result['device_type'] = 'mobile';
        }

        if (preg_match('/Mobile/', $userAgent) || preg_match('/iPhone|iPad|Android/', $userAgent)) {
            $result['device_type'] = 'mobile';
        } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
            $result['device_type'] = 'tablet';
        }

        return $result;
    }

    protected function getUserLoginHistory(User $user): \Illuminate\Support\Collection
    {
        return AuditLog::where('user_id', $user->id)
            ->where('event_type', AuditLog::EVENT_LOGIN)
            ->where('response_status', '<', 400)
            ->where('created_at', '>=', now()->subDays(90))
            ->get();
    }

    protected function hasUserLoggedInFromCountry(\Illuminate\Support\Collection $loginHistory, ?string $countryCode): bool
    {
        if (!$countryCode) {
            return true;
        }

        return $loginHistory->contains(function ($log) use ($countryCode) {
            $metadata = $log->metadata ?? [];
            return isset($metadata['ip_country_code']) && $metadata['ip_country_code'] === $countryCode;
        });
    }

    protected function hasUserLoggedInFromRegion(\Illuminate\Support\Collection $loginHistory, ?string $countryCode, ?string $region): bool
    {
        if (!$countryCode || !$region) {
            return true;
        }

        return $loginHistory->contains(function ($log) use ($countryCode, $region) {
            $metadata = $log->metadata ?? [];
            return isset($metadata['ip_country_code'], $metadata['ip_region']) 
                && $metadata['ip_country_code'] === $countryCode 
                && $metadata['ip_region'] === $region;
        });
    }

    protected function getLastLoginLocation(User $user): ?array
    {
        $lastLogin = AuditLog::where('user_id', $user->id)
            ->where('event_type', AuditLog::EVENT_LOGIN)
            ->where('response_status', '<', 400)
            ->whereJsonLength('metadata', '>', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastLogin || !$lastLogin->metadata) {
            return null;
        }

        $metadata = $lastLogin->metadata;
        if (isset($metadata['ip_coordinates'])) {
            [$lat, $lon] = explode(',', $metadata['ip_coordinates']);
            return ['latitude' => (float)$lat, 'longitude' => (float)$lon];
        }

        return null;
    }

    protected function getLastLoginTime(User $user): ?\Carbon\Carbon
    {
        $lastLogin = AuditLog::where('user_id', $user->id)
            ->where('event_type', AuditLog::EVENT_LOGIN)
            ->where('response_status', '<', 400)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastLogin?->created_at;
    }

    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
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

    public function getPendingAttempts(): \Illuminate\Database\Eloquent\Collection
    {
        return SuspiciousLoginAttempt::where('company_id', auth()->user()->company_id)
            ->pending()
            ->orderBy('risk_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRecentAttempts(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return SuspiciousLoginAttempt::where('company_id', auth()->user()->company_id)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function cleanupExpiredAttempts(): int
    {
        return SuspiciousLoginAttempt::where('status', SuspiciousLoginAttempt::STATUS_PENDING)
            ->where('expires_at', '<=', now())
            ->update(['status' => SuspiciousLoginAttempt::STATUS_EXPIRED]);
    }
}