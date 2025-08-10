<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Domains\Client\Services\ClientPortalService;
use App\Services\PortalAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Exception;

/**
 * Base Portal API Controller
 * 
 * Provides common functionality for all portal API endpoints including:
 * - Client authentication and authorization
 * - Rate limiting and security checks
 * - Standardized response formatting
 * - Request validation helpers
 * - Error handling and logging
 * - Session management integration
 */
abstract class PortalApiController extends Controller
{
    protected ClientPortalService $portalService;
    protected PortalAuthService $authService;
    
    public function __construct(ClientPortalService $portalService, PortalAuthService $authService)
    {
        $this->portalService = $portalService;
        $this->authService = $authService;
        
        // Apply portal-specific middleware
        $this->middleware('portal.auth')->except(['login', 'register', 'forgotPassword', 'resetPassword']);
        $this->middleware('portal.session')->except(['login', 'register']);
        $this->middleware('throttle:portal-api')->except(['dashboard', 'profile']);
    }

    /**
     * Get authenticated client from portal session
     */
    protected function getAuthenticatedClient(): ?Client
    {
        $portalSession = request()->get('portal_session');
        return $portalSession?->client;
    }

    /**
     * Ensure client is authenticated
     */
    protected function requireAuthentication(): Client
    {
        $client = $this->getAuthenticatedClient();
        
        if (!$client) {
            abort(401, 'Portal authentication required');
        }

        return $client;
    }

    /**
     * Check if client has specific portal permission
     */
    protected function checkPermission(string $permission): bool
    {
        $client = $this->getAuthenticatedClient();
        
        if (!$client || !$client->portalAccess) {
            return false;
        }

        return $client->portalAccess->hasPermission($permission);
    }

    /**
     * Require specific portal permission
     */
    protected function requirePermission(string $permission): void
    {
        if (!$this->checkPermission($permission)) {
            abort(403, "Permission '{$permission}' required");
        }
    }

    /**
     * Apply rate limiting to endpoint
     */
    protected function applyRateLimit(string $key, int $maxAttempts = 60, int $decaySeconds = 60): void
    {
        $client = $this->getAuthenticatedClient();
        $rateLimitKey = "portal_api:{$key}:" . ($client?->id ?? request()->ip());
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);
            abort(429, "Too many requests. Retry after {$retryAfter} seconds.");
        }

        RateLimiter::hit($rateLimitKey, $decaySeconds);
    }

    /**
     * Log portal API activity
     */
    protected function logActivity(string $action, array $data = []): void
    {
        $client = $this->getAuthenticatedClient();
        $portalSession = request()->get('portal_session');

        Log::info('Portal API activity', [
            'action' => $action,
            'client_id' => $client?->id,
            'session_id' => $portalSession?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
        ]);

        // Update portal session activity
        if ($portalSession) {
            $portalSession->updateActivity($action, $data);
        }
    }

    /**
     * Return standardized success response
     */
    protected function successResponse(string $message = 'Success', array $data = [], int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return standardized error response
     */
    protected function errorResponse(string $message = 'An error occurred', int $statusCode = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return validation error response
     */
    protected function validationErrorResponse(array $errors): JsonResponse
    {
        return $this->errorResponse('Validation failed', 422, $errors);
    }

    /**
     * Handle service response and return appropriate JSON response
     */
    protected function handleServiceResponse(array $serviceResponse, string $defaultSuccessMessage = 'Operation completed'): JsonResponse
    {
        if ($serviceResponse['success']) {
            unset($serviceResponse['success']);
            $message = $serviceResponse['message'] ?? $defaultSuccessMessage;
            unset($serviceResponse['message']);
            
            return $this->successResponse($message, $serviceResponse);
        } else {
            $message = $serviceResponse['message'] ?? 'Operation failed';
            $statusCode = isset($serviceResponse['error_code']) ? $this->getStatusCodeFromErrorCode($serviceResponse['error_code']) : 400;
            
            return $this->errorResponse($message, $statusCode);
        }
    }

    /**
     * Get HTTP status code from service error code
     */
    private function getStatusCodeFromErrorCode(string $errorCode): int
    {
        return match($errorCode) {
            'VALIDATION_FAILED' => 422,
            'PERMISSION_DENIED' => 403,
            'NOT_FOUND' => 404,
            'PAYMENT_FAILED' => 402,
            'RATE_LIMITED' => 429,
            'SERVER_ERROR' => 500,
            default => 400,
        };
    }

    /**
     * Validate required fields in request
     */
    protected function validateRequired(Request $request, array $fields): array
    {
        $errors = [];
        
        foreach ($fields as $field => $name) {
            if (is_numeric($field)) {
                $field = $name;
                $name = ucfirst(str_replace('_', ' ', $field));
            }
            
            if (!$request->has($field) || empty($request->get($field))) {
                $errors[$field] = ["{$name} is required"];
            }
        }

        if (!empty($errors)) {
            throw new \Illuminate\Validation\ValidationException(
                validator($request->all(), []),
                response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors,
                ], 422)
            );
        }

        return $request->only(array_keys($fields));
    }

    /**
     * Handle exceptions and return appropriate error response
     */
    protected function handleException(Exception $e, string $context = 'API operation'): JsonResponse
    {
        Log::error("Portal API error in {$context}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'client_id' => $this->getAuthenticatedClient()?->id,
            'request_path' => request()->path(),
        ]);

        // Return user-friendly error messages for common exceptions
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->validationErrorResponse($e->errors());
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->errorResponse('Authentication required', 401);
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->errorResponse('Access denied', 403);
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->errorResponse('Resource not found', 404);
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
            return $this->errorResponse('Too many requests', 429);
        }

        // Generic error for production
        if (app()->environment('production')) {
            return $this->errorResponse('Service temporarily unavailable', 503);
        }

        return $this->errorResponse($e->getMessage(), 500);
    }

    /**
     * Get pagination parameters from request
     */
    protected function getPaginationParams(Request $request): array
    {
        return [
            'page' => max(1, (int) $request->get('page', 1)),
            'per_page' => min(100, max(10, (int) $request->get('per_page', 20))),
        ];
    }

    /**
     * Get filter parameters from request
     */
    protected function getFilterParams(Request $request, array $allowedFilters = []): array
    {
        $filters = [];
        
        foreach ($allowedFilters as $filter) {
            if ($request->has($filter)) {
                $filters[$filter] = $request->get($filter);
            }
        }

        return array_merge($filters, $this->getPaginationParams($request));
    }

    /**
     * Validate date range parameters
     */
    protected function validateDateRange(Request $request): array
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($startDate) {
            try {
                $startDate = \Carbon\Carbon::parse($startDate);
            } catch (Exception $e) {
                throw new \InvalidArgumentException('Invalid start_date format');
            }
        }

        if ($endDate) {
            try {
                $endDate = \Carbon\Carbon::parse($endDate);
            } catch (Exception $e) {
                throw new \InvalidArgumentException('Invalid end_date format');
            }
        }

        if ($startDate && $endDate && $startDate->gt($endDate)) {
            throw new \InvalidArgumentException('start_date must be before end_date');
        }

        return compact('start_date', 'end_date');
    }
}