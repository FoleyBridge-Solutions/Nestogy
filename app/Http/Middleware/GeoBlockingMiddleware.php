<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * GeoBlockingMiddleware
 *
 * Blocks or allows access based on geographic location of the request IP.
 * Uses IP geolocation services to determine country/region.
 */
class GeoBlockingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $mode  'allow' or 'block' mode
     * @param  string|null  $countries  Comma-separated country codes
     */
    public function handle(Request $request, Closure $next, ?string $mode = null, ?string $countries = null): Response
    {
        // Check if geo-blocking is enabled
        if (! config('security.geo_blocking.enabled', false)) {
            return $next($request);
        }

        $clientIp = $this->getClientIp($request);

        // Allow localhost in development
        if (app()->environment('local') && $this->isLocalhost($clientIp)) {
            return $next($request);
        }

        // Get country for the IP
        $country = $this->getCountryForIp($clientIp);

        if (! $country) {
            // If we can't determine country, use default policy
            $defaultPolicy = config('security.geo_blocking.default_policy', 'allow');
            if ($defaultPolicy === 'block') {
                $this->logBlockedAttempt($request, $clientIp, 'unknown', 'Could not determine country');
                abort(403, 'Access denied. Could not verify your location.');
            }

            return $next($request);
        }

        // Determine mode and countries list
        $mode = $mode ?? config('security.geo_blocking.mode', 'block');
        $countriesList = $countries ? explode(',', $countries) : config('security.geo_blocking.countries', []);
        $countriesList = array_map('strtoupper', array_map('trim', $countriesList));

        // Check if access should be allowed
        $isInList = in_array(strtoupper($country), $countriesList);
        $shouldAllow = ($mode === 'allow' && $isInList) || ($mode === 'block' && ! $isInList);

        if (! $shouldAllow) {
            $this->logBlockedAttempt($request, $clientIp, $country, "Country $country is ".($mode === 'allow' ? 'not allowed' : 'blocked'));

            // Return appropriate response
            if (config('security.geo_blocking.stealth_mode', false)) {
                abort(404); // Pretend the resource doesn't exist
            } else {
                abort(403, 'Access denied. This service is not available in your region.');
            }
        }

        // Add geo info to request for logging
        $request->attributes->set('client_country', $country);
        $request->attributes->set('geo_blocking_mode', $mode);

        return $next($request);
    }

    /**
     * Get the client's real IP address.
     */
    protected function getClientIp(Request $request): string
    {
        // Check for trusted proxies and get real IP
        $trustedProxies = config('security.trusted_proxies', []);

        if (! empty($trustedProxies)) {
            $requestIp = $request->ip();
            if (in_array($requestIp, $trustedProxies)) {
                // Get IP from headers
                $forwardedFor = $request->header('X-Forwarded-For');
                if ($forwardedFor) {
                    $ips = array_map('trim', explode(',', $forwardedFor));

                    return $ips[0];
                }

                $realIp = $request->header('X-Real-IP');
                if ($realIp) {
                    return $realIp;
                }
            }
        }

        return $request->ip();
    }

    /**
     * Check if IP is localhost.
     */
    protected function isLocalhost(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']);
    }

    /**
     * Get country code for an IP address.
     */
    protected function getCountryForIp(string $ip): ?string
    {
        // Check cache first
        $cacheKey = 'geo_country_'.$ip;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached ?: null; // Return null if cached value is false
        }

        // Try multiple geolocation services
        $country = $this->tryGeoServices($ip);

        // Cache the result (including failures)
        Cache::put($cacheKey, $country ?: false, now()->addHours(24));

        return $country;
    }

    /**
     * Try multiple geolocation services.
     */
    protected function tryGeoServices(string $ip): ?string
    {
        $services = config('security.geo_blocking.services', [
            'ipapi' => true,
            'ipgeolocation' => false,
            'maxmind' => false,
        ]);

        foreach ($services as $service => $enabled) {
            if (! $enabled) {
                continue;
            }

            try {
                $country = match ($service) {
                    'ipapi' => $this->getCountryFromIpApi($ip),
                    'ipgeolocation' => $this->getCountryFromIpGeolocation($ip),
                    'maxmind' => $this->getCountryFromMaxMind($ip),
                    default => null,
                };

                if ($country) {
                    return $country;
                }
            } catch (\Exception $e) {
                \Log::warning("Geo service {$service} failed", [
                    'ip' => $ip,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Get country from ip-api.com (free service).
     */
    protected function getCountryFromIpApi(string $ip): ?string
    {
        $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}", [
            'fields' => 'status,countryCode',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['status'] === 'success' && isset($data['countryCode'])) {
                return $data['countryCode'];
            }
        }

        return null;
    }

    /**
     * Get country from ipgeolocation.io (requires API key).
     */
    protected function getCountryFromIpGeolocation(string $ip): ?string
    {
        $apiKey = config('security.geo_blocking.api_keys.ipgeolocation');
        if (! $apiKey) {
            return null;
        }

        $response = Http::timeout(5)->get('https://api.ipgeolocation.io/ipgeo', [
            'apiKey' => $apiKey,
            'ip' => $ip,
            'fields' => 'country_code2',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return $data['country_code2'] ?? null;
        }

        return null;
    }

    /**
     * Get country from MaxMind GeoLite2 database (local).
     */
    protected function getCountryFromMaxMind(string $ip): ?string
    {
        $databasePath = config('security.geo_blocking.maxmind_database_path');
        if (! $databasePath || ! file_exists($databasePath)) {
            return null;
        }

        try {
            // This would require the geoip2/geoip2 package
            // composer require geoip2/geoip2
            if (class_exists(\GeoIp2\Database\Reader::class)) {
                $reader = new \GeoIp2\Database\Reader($databasePath);
                $record = $reader->country($ip);

                return $record->country->isoCode;
            }
        } catch (\Exception $e) {
            \Log::warning('MaxMind lookup failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Log blocked access attempt.
     */
    protected function logBlockedAttempt(Request $request, string $ip, string $country, string $reason): void
    {
        AuditLog::logSecurity('Geo-blocking Block', [
            'blocked_ip' => $ip,
            'country' => $country,
            'reason' => $reason,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
        ], AuditLog::SEVERITY_WARNING);
    }
}
