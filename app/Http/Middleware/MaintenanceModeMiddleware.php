<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

/**
 * MaintenanceModeMiddleware
 * 
 * Custom maintenance mode middleware that allows admin access
 * and provides better control over maintenance mode behavior.
 */
class MaintenanceModeMiddleware
{
    /**
     * URIs that should be accessible during maintenance mode
     */
    protected array $except = [
        'login',
        'logout',
        'api/health',
        'api/status',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled
        if (!$this->isMaintenanceMode()) {
            return $next($request);
        }

        // Check if request should be allowed
        if ($this->shouldAllowRequest($request)) {
            return $next($request);
        }

        // Return maintenance response
        return $this->maintenanceResponse($request);
    }

    /**
     * Check if maintenance mode is enabled.
     */
    protected function isMaintenanceMode(): bool
    {
        // Check cache first for performance
        $cacheKey = 'maintenance_mode_status';
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        // Check file-based maintenance mode
        $maintenanceFile = storage_path('framework/down');
        if (file_exists($maintenanceFile)) {
            $data = json_decode(file_get_contents($maintenanceFile), true);
            Cache::put($cacheKey, true, now()->addMinutes(1));
            
            // Store maintenance data in cache
            if ($data) {
                Cache::put('maintenance_mode_data', $data, now()->addMinutes(1));
            }
            
            return true;
        }

        // Check database/config based maintenance mode
        $configMaintenance = config('app.maintenance_mode', false);
        Cache::put($cacheKey, $configMaintenance, now()->addMinutes(1));
        
        return $configMaintenance;
    }

    /**
     * Check if request should be allowed during maintenance.
     */
    protected function shouldAllowRequest(Request $request): bool
    {
        // Allow if user is authenticated admin
        if ($this->isAdminUser()) {
            return true;
        }

        // Allow if IP is whitelisted
        if ($this->isIpWhitelisted($request)) {
            return true;
        }

        // Allow if secret token is provided
        if ($this->hasValidMaintenanceToken($request)) {
            return true;
        }

        // Allow if URI is in exception list
        if ($this->isUriExcepted($request)) {
            return true;
        }

        return false;
    }

    /**
     * Check if current user is admin.
     */
    protected function isAdminUser(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        
        // Check if user has admin role (3)
        if (method_exists($user, 'getRole')) {
            return $user->getRole() >= 3;
        }

        // Fallback to checking admin flag
        return $user->is_admin ?? false;
    }

    /**
     * Check if request IP is whitelisted.
     */
    protected function isIpWhitelisted(Request $request): bool
    {
        $maintenanceData = Cache::get('maintenance_mode_data', []);
        $allowedIps = $maintenanceData['allowed_ips'] ?? config('app.maintenance_allowed_ips', []);
        
        if (empty($allowedIps)) {
            return false;
        }

        $clientIp = $request->ip();
        
        foreach ($allowedIps as $ip) {
            if ($this->ipMatches($clientIp, $ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches pattern.
     */
    protected function ipMatches(string $clientIp, string $pattern): bool
    {
        // Exact match
        if ($clientIp === $pattern) {
            return true;
        }

        // CIDR notation
        if (strpos($pattern, '/') !== false) {
            list($subnet, $bits) = explode('/', $pattern);
            $ip = ip2long($clientIp);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet &= $mask;
            return ($ip & $mask) == $subnet;
        }

        // Wildcard
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('.', '\.', $pattern);
            $pattern = str_replace('*', '.*', $pattern);
            return preg_match('/^' . $pattern . '$/', $clientIp) === 1;
        }

        return false;
    }

    /**
     * Check if request has valid maintenance token.
     */
    protected function hasValidMaintenanceToken(Request $request): bool
    {
        $token = $request->input('maintenance_token') ?? $request->header('X-Maintenance-Token');
        
        if (!$token) {
            return false;
        }

        $maintenanceData = Cache::get('maintenance_mode_data', []);
        $validToken = $maintenanceData['secret'] ?? config('app.maintenance_secret');
        
        if (!$validToken) {
            return false;
        }

        return hash_equals($validToken, $token);
    }

    /**
     * Check if URI is excepted from maintenance.
     */
    protected function isUriExcepted(Request $request): bool
    {
        $path = $request->path();
        
        // Get custom exceptions from maintenance data
        $maintenanceData = Cache::get('maintenance_mode_data', []);
        $customExceptions = $maintenanceData['except'] ?? [];
        
        $exceptions = array_merge($this->except, $customExceptions);
        
        foreach ($exceptions as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate maintenance response.
     */
    protected function maintenanceResponse(Request $request): Response
    {
        $maintenanceData = Cache::get('maintenance_mode_data', []);
        
        // For API requests, return JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->apiMaintenanceResponse($maintenanceData);
        }

        // For web requests, return maintenance view
        return $this->webMaintenanceResponse($maintenanceData);
    }

    /**
     * Generate API maintenance response.
     */
    protected function apiMaintenanceResponse(array $data): Response
    {
        $response = [
            'error' => 'Service Unavailable',
            'message' => $data['message'] ?? 'The application is currently undergoing maintenance.',
        ];

        // Add retry time if available
        if (isset($data['retry']) && $data['retry'] > time()) {
            $response['retry_after'] = $data['retry'] - time();
            $response['retry_at'] = date('c', $data['retry']);
        }

        // Add maintenance window if available
        if (isset($data['ends_at'])) {
            $response['maintenance_ends_at'] = $data['ends_at'];
        }

        return response()->json($response, 503)
            ->header('Retry-After', $data['retry'] ?? 3600);
    }

    /**
     * Generate web maintenance response.
     */
    protected function webMaintenanceResponse(array $data): Response
    {
        // Check if custom maintenance view exists
        if (View::exists('maintenance')) {
            $content = view('maintenance', [
                'message' => $data['message'] ?? null,
                'retry' => $data['retry'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'contact' => $data['contact'] ?? config('app.maintenance_contact'),
            ])->render();
        } else {
            // Use default maintenance page
            $content = $this->getDefaultMaintenancePage($data);
        }

        return response($content, 503)
            ->header('Retry-After', $data['retry'] ?? 3600)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Get default maintenance page HTML.
     */
    protected function getDefaultMaintenancePage(array $data): string
    {
        $message = $data['message'] ?? 'We are currently performing scheduled maintenance.';
        $appName = config('app.name', 'Application');
        
        $retryInfo = '';
        if (isset($data['retry']) && $data['retry'] > time()) {
            $retryTime = date('g:i A', $data['retry']);
            $retryInfo = "<p>Please check back after {$retryTime}.</p>";
        } elseif (isset($data['ends_at'])) {
            $retryInfo = "<p>Maintenance is expected to end at {$data['ends_at']}.</p>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - {$appName}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .maintenance-container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
        }
        .maintenance-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #343a40;
        }
        p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin: 2rem 0;
        }
        .progress-bar-fill {
            height: 100%;
            background-color: #007bff;
            width: 30%;
            animation: progress 2s ease-in-out infinite;
        }
        @keyframes progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
        .contact-info {
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .contact-info a {
            color: #007bff;
            text-decoration: none;
        }
        .contact-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <h1>Maintenance Mode</h1>
        <p>{$message}</p>
        {$retryInfo}
        <div class="progress-bar">
            <div class="progress-bar-fill"></div>
        </div>
        <p>We apologize for any inconvenience this may cause.</p>
        <div class="contact-info">
            <p>If you need immediate assistance, please contact support.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}