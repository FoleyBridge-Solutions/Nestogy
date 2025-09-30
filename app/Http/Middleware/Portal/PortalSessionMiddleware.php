<?php

namespace App\Http\Middleware\Portal;

use App\Models\ClientPortalSession;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Portal Session Management Middleware
 *
 * Handles portal session lifecycle including:
 * - Session timeout management
 * - Session extension and refresh
 * - Concurrent session limits
 * - Session cleanup
 * - Activity tracking
 */
class PortalSessionMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        $session = $request->get('portal_session');

        if (! $session) {
            return $next($request);
        }

        // Check session timeout
        if ($this->isSessionTimedOut($session)) {
            $this->invalidateSession($session, 'timeout');

            return $this->sessionTimeoutResponse();
        }

        // Check concurrent session limits
        if (! $this->checkConcurrentSessionLimits($session)) {
            $this->invalidateSession($session, 'concurrent_limit_exceeded');

            return $this->concurrentLimitResponse();
        }

        // Extend session if needed
        $this->extendSessionIfNeeded($session, $request);

        // Clean up old sessions periodically
        $this->periodicSessionCleanup($session->client_id);

        $response = $next($request);

        // Update session metrics
        $this->updateSessionMetrics($session, $request, $response);

        return $response;
    }

    /**
     * Check if session is timed out
     */
    private function isSessionTimedOut(ClientPortalSession $session): bool
    {
        $portalAccess = $session->client->portalAccess;
        $timeoutMinutes = $portalAccess->session_timeout ?? config('portal.session.timeout', 120);

        if ($session->last_activity) {
            $timeoutAt = $session->last_activity->addMinutes($timeoutMinutes);

            return Carbon::now()->gt($timeoutAt);
        }

        return false;
    }

    /**
     * Check concurrent session limits
     */
    private function checkConcurrentSessionLimits(ClientPortalSession $session): bool
    {
        $portalAccess = $session->client->portalAccess;
        $maxConcurrentSessions = $portalAccess->max_concurrent_sessions ?? config('portal.session.max_concurrent', 3);

        if ($maxConcurrentSessions <= 0) {
            return true; // No limit
        }

        $activeSessions = ClientPortalSession::where('client_id', $session->client_id)
            ->where('is_active', true)
            ->where('id', '!=', $session->id)
            ->count();

        return $activeSessions < $maxConcurrentSessions;
    }

    /**
     * Extend session if needed
     */
    private function extendSessionIfNeeded(ClientPortalSession $session, Request $request): void
    {
        $now = Carbon::now();
        $lastActivity = $session->last_activity;

        // Extend session if more than 15 minutes since last extension
        if (! $lastActivity || $lastActivity->diffInMinutes($now) >= 15) {
            $portalAccess = $session->client->portalAccess;
            $timeoutMinutes = $portalAccess->session_timeout ?? config('portal.session.timeout', 120);

            $session->update([
                'expires_at' => $now->addMinutes($timeoutMinutes),
                'last_activity' => $now,
            ]);

            Log::debug('Portal session extended', [
                'session_id' => $session->id,
                'client_id' => $session->client_id,
                'new_expires_at' => $session->expires_at,
            ]);
        }
    }

    /**
     * Periodic cleanup of old sessions
     */
    private function periodicSessionCleanup(int $clientId): void
    {
        // Run cleanup randomly (1% chance per request)
        if (random_int(1, 100) !== 1) {
            return;
        }

        try {
            $cutoff = Carbon::now()->subDays(7); // Clean up sessions older than 7 days

            $cleanedCount = ClientPortalSession::where('client_id', $clientId)
                ->where(function ($query) use ($cutoff) {
                    $query->where('is_active', false)
                        ->orWhere('expires_at', '<', $cutoff)
                        ->orWhere('created_at', '<', $cutoff);
                })
                ->delete();

            if ($cleanedCount > 0) {
                Log::info('Portal session cleanup completed', [
                    'client_id' => $clientId,
                    'cleaned_sessions' => $cleanedCount,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Portal session cleanup failed', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update session metrics
     */
    private function updateSessionMetrics(ClientPortalSession $session, Request $request, $response): void
    {
        try {
            $statusCode = $response instanceof JsonResponse ? $response->getStatusCode() : 200;

            // Update request count and response times
            $session->increment('request_count');

            // Track different types of requests
            $path = $request->path();
            $metrics = $session->metrics ?? [];

            if (str_starts_with($path, 'api/portal/dashboard')) {
                $metrics['dashboard_views'] = ($metrics['dashboard_views'] ?? 0) + 1;
            } elseif (str_starts_with($path, 'api/portal/invoices')) {
                $metrics['invoice_views'] = ($metrics['invoice_views'] ?? 0) + 1;
            } elseif (str_starts_with($path, 'api/portal/payments')) {
                $metrics['payment_actions'] = ($metrics['payment_actions'] ?? 0) + 1;
            }

            // Track error rates
            if ($statusCode >= 400) {
                $metrics['error_count'] = ($metrics['error_count'] ?? 0) + 1;
            }

            $session->update([
                'metrics' => $metrics,
                'last_status_code' => $statusCode,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update session metrics', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate session
     */
    private function invalidateSession(ClientPortalSession $session, string $reason): void
    {
        try {
            $session->update([
                'is_active' => false,
                'ended_at' => Carbon::now(),
                'end_reason' => $reason,
            ]);

            Log::info('Portal session invalidated by middleware', [
                'session_id' => $session->id,
                'client_id' => $session->client_id,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to invalidate session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return session timeout response
     */
    private function sessionTimeoutResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Your session has expired. Please log in again.',
            'error_code' => 'SESSION_TIMEOUT',
            'timestamp' => Carbon::now()->toISOString(),
        ], 401);
    }

    /**
     * Return concurrent limit response
     */
    private function concurrentLimitResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Maximum concurrent sessions exceeded. Please close other sessions and try again.',
            'error_code' => 'CONCURRENT_LIMIT_EXCEEDED',
            'timestamp' => Carbon::now()->toISOString(),
        ], 403);
    }
}
