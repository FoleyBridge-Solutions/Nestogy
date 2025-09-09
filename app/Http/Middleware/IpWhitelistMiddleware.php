<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;

/**
 * IpWhitelistMiddleware
 * 
 * Restricts access based on IP whitelist configuration.
 * Supports individual IPs, IP ranges, and CIDR notation.
 */
class IpWhitelistMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $listName  Optional whitelist name for route-specific lists
     */
    public function handle(Request $request, Closure $next, ?string $listName = null): Response
    {
        $clientIp = $this->getClientIp($request);
        
        // Check if IP whitelisting is enabled
        if (!config('security.ip_whitelist.enabled', false)) {
            return $next($request);
        }

        // Allow localhost in development
        if (app()->environment('local') && $this->isLocalhost($clientIp)) {
            return $next($request);
        }

        // Get the appropriate whitelist
        $whitelist = $this->getWhitelist($listName);

        // Check if IP is whitelisted
        if (!$this->isIpAllowed($clientIp, $whitelist)) {
            $this->logBlockedAttempt($request, $clientIp, $listName);
            
            // Return appropriate response based on configuration
            if (config('security.ip_whitelist.stealth_mode', false)) {
                abort(404); // Pretend the resource doesn't exist
            } else {
                abort(403, 'Access denied. Your IP address is not authorized.');
            }
        }

        // Add IP info to request for logging
        $request->attributes->set('client_ip', $clientIp);
        $request->attributes->set('ip_whitelist', $listName ?? 'default');

        return $next($request);
    }

    /**
     * Get the client's real IP address.
     */
    protected function getClientIp(Request $request): string
    {
        // Check for trusted proxies and get real IP
        $trustedProxies = config('security.trusted_proxies', []);
        
        if (!empty($trustedProxies)) {
            // Check X-Forwarded-For header if from trusted proxy
            $requestIp = $request->ip();
            if (in_array($requestIp, $trustedProxies) || $this->isIpInRanges($requestIp, $trustedProxies)) {
                // Get IP from headers
                $forwardedFor = $request->header('X-Forwarded-For');
                if ($forwardedFor) {
                    // Get the first IP in the chain
                    $ips = array_map('trim', explode(',', $forwardedFor));
                    return $ips[0];
                }
                
                // Try X-Real-IP header
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
     * Get the whitelist configuration.
     */
    protected function getWhitelist(?string $listName): array
    {
        // Cache whitelist for performance
        $cacheKey = 'ip_whitelist_' . ($listName ?? 'default');
        
        return Cache::remember($cacheKey, 300, function () use ($listName) {
            if ($listName) {
                // Get named whitelist
                $namedLists = config('security.ip_whitelist.lists', []);
                if (isset($namedLists[$listName])) {
                    return $namedLists[$listName];
                }
            }

            // Get default whitelist
            return config('security.ip_whitelist.allowed_ips', []);
        });
    }

    /**
     * Check if IP is allowed.
     */
    protected function isIpAllowed(string $ip, array $whitelist): bool
    {
        // Empty whitelist means all IPs are allowed
        if (empty($whitelist)) {
            return true;
        }

        foreach ($whitelist as $allowed) {
            // Check exact match
            if ($ip === $allowed) {
                return true;
            }

            // Check CIDR notation (e.g., 192.168.1.0/24)
            if (strpos($allowed, '/') !== false) {
                if ($this->isIpInCidr($ip, $allowed)) {
                    return true;
                }
            }

            // Check IP range (e.g., 192.168.1.1-192.168.1.255)
            if (strpos($allowed, '-') !== false) {
                if ($this->isIpInRange($ip, $allowed)) {
                    return true;
                }
            }

            // Check wildcard (e.g., 192.168.1.*)
            if (strpos($allowed, '*') !== false) {
                if ($this->matchWildcard($ip, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range.
     */
    protected function isIpInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $bits) = explode('/', $cidr);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask;
            return ($ip & $mask) == $subnet;
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6
            $ip = inet_pton($ip);
            $subnet = inet_pton($subnet);
            $binMask = str_repeat('1', $bits) . str_repeat('0', 128 - $bits);
            $mask = pack('H*', base_convert($binMask, 2, 16));
            return ($ip & $mask) == ($subnet & $mask);
        }
        
        return false;
    }

    /**
     * Check if IP is in range.
     */
    protected function isIpInRange(string $ip, string $range): bool
    {
        list($start, $end) = explode('-', $range);
        $start = trim($start);
        $end = trim($end);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = ip2long($ip);
            $start = ip2long($start);
            $end = ip2long($end);
            return $ip >= $start && $ip <= $end;
        }

        // For IPv6, use string comparison
        return strcmp($ip, $start) >= 0 && strcmp($ip, $end) <= 0;
    }

    /**
     * Check if IP matches wildcard pattern.
     */
    protected function matchWildcard(string $ip, string $pattern): bool
    {
        $pattern = str_replace('.', '\.', $pattern);
        $pattern = str_replace('*', '.*', $pattern);
        return preg_match('/^' . $pattern . '$/', $ip) === 1;
    }

    /**
     * Check if IP is in any of the given ranges.
     */
    protected function isIpInRanges(string $ip, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if ($this->isIpAllowed($ip, [$range])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Log blocked access attempt.
     */
    protected function logBlockedAttempt(Request $request, string $ip, ?string $listName): void
    {
        AuditLog::logSecurity('IP Whitelist Block', [
            'blocked_ip' => $ip,
            'whitelist' => $listName ?? 'default',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
        ], AuditLog::SEVERITY_WARNING);
    }
}