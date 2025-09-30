<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogSignupRequests
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('signup*')) {
            Log::info('SIGNUP REQUEST START', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'time' => now()->toIso8601String(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload_size' => strlen($request->getContent()),
                'memory_usage' => memory_get_usage(true),
                'session_id' => session()->getId(),
            ]);
        }

        $startTime = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $startTime;

        if ($request->is('signup*')) {
            Log::info('SIGNUP REQUEST END', [
                'url' => $request->fullUrl(),
                'status' => $response->getStatusCode(),
                'duration_seconds' => $duration,
                'time' => now()->toIso8601String(),
                'memory_usage' => memory_get_usage(true),
            ]);
        }

        return $response;
    }
}
