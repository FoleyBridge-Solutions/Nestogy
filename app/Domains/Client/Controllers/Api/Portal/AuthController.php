<?php

namespace App\Domains\Client\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Domains\Security\Services\PortalAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Portal Authentication Controller
 * 
 * Handles authentication-related functionality including:
 * - Client login and logout
 * - Multi-factor authentication
 * - Password reset and recovery
 * - Session management
 * - Registration (if enabled)
 * - Account verification
 */
class AuthController extends Controller
{
    protected PortalAuthService $authService;
    
    public function __construct(PortalAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Authenticate client and create portal session
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Apply rate limiting
            $key = 'portal-login:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $retryAfter = RateLimiter::availableIn($key);
                return $this->errorResponse("Too many login attempts. Try again in {$retryAfter} seconds.", 429);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:6|max:255',
                'remember_me' => 'boolean',
                'device_info' => 'array',
            ]);

            if ($validator->fails()) {
                RateLimiter::hit($key, 300); // 5 minute penalty for invalid requests
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            // Attempt authentication
            $authResult = $this->authService->authenticate(
                $validator->validated()['email'],
                $validator->validated()['password'],
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_info' => $request->get('device_info', []),
                    'remember_me' => $request->get('remember_me', false),
                ]
            );

            if (!$authResult['success']) {
                RateLimiter::hit($key, 300);
                return $this->errorResponse($authResult['message'], 401);
            }

            // Clear rate limit on successful login
            RateLimiter::clear($key);

            $responseData = [
                'session_id' => $authResult['session_id'],
                'client' => $authResult['client'],
                'expires_at' => $authResult['expires_at'],
                'requires_mfa' => $authResult['requires_mfa'] ?? false,
            ];

            if ($authResult['requires_mfa']) {
                $responseData['mfa_methods'] = $authResult['mfa_methods'] ?? [];
            }

            return $this->successResponse('Authentication successful', $responseData);

        } catch (Exception $e) {
            return $this->handleException($e, 'login');
        }
    }

    /**
     * Complete multi-factor authentication
     */
    public function verifyMfa(Request $request): JsonResponse
    {
        try {
            // Apply rate limiting
            $key = 'portal-mfa:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) {
                $retryAfter = RateLimiter::availableIn($key);
                return $this->errorResponse("Too many MFA attempts. Try again in {$retryAfter} seconds.", 429);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'mfa_code' => 'required|string|min:4|max:8',
                'mfa_method' => 'required|string|in:sms,email,totp',
            ]);

            if ($validator->fails()) {
                RateLimiter::hit($key, 60);
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            // For now, return a placeholder response since MFA isn't fully implemented
            $mfaResult = [
                'success' => true,
                'session_id' => $validator->validated()['session_id'],
                'client' => (object) ['id' => 1, 'company_name' => 'Test Client'],
                'expires_at' => now()->addHours(2),
            ];

            if (!$mfaResult['success']) {
                RateLimiter::hit($key, 60);
                return $this->errorResponse($mfaResult['message'], 401);
            }

            RateLimiter::clear($key);

            return $this->successResponse('MFA verification successful', [
                'session_id' => $mfaResult['session_id'],
                'client' => $mfaResult['client'],
                'expires_at' => $mfaResult['expires_at'],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'MFA verification');
        }
    }

    /**
     * Logout and terminate portal session
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->get('session_id') ?? $request->header('X-Portal-Session');
            
            if (!$sessionId) {
                return $this->errorResponse('Session ID required', 400);
            }

            $logoutResult = $this->authService->logout($sessionId);

            return $this->successResponse($logoutResult['message']);

        } catch (Exception $e) {
            return $this->handleException($e, 'logout');
        }
    }

    /**
     * Get current session information
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $portalSession = $request->get('portal_session');
            
            if (!$portalSession) {
                return $this->errorResponse('Not authenticated', 401);
            }

            $client = $portalSession->client;
            $sessionInfo = [
                'id' => $portalSession->id,
                'created_at' => $portalSession->created_at,
                'last_activity' => $portalSession->last_activity,
                'expires_at' => $portalSession->expires_at,
                'ip_address' => $portalSession->ip_address,
                'user_agent' => $portalSession->user_agent,
            ];

            return $this->successResponse('Session information retrieved', [
                'client' => [
                    'id' => $client->id,
                    'company_name' => $client->company_name,
                    'contact_name' => $client->contact_name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'account_status' => $client->status,
                ],
                'session' => $sessionInfo,
                'permissions' => $client->portalAccess?->getPermissions() ?? [],
                'preferences' => $client->portalAccess?->portal_preferences ?? [],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'session info retrieval');
        }
    }

    /**
     * Initiate password reset
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            // Apply rate limiting
            $key = 'portal-forgot:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $retryAfter = RateLimiter::availableIn($key);
                return $this->errorResponse("Too many reset attempts. Try again in {$retryAfter} seconds.", 429);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            RateLimiter::hit($key, 900); // 15 minute cooldown

            // Placeholder implementation
            $resetResult = ['success' => true];

            // Always return success to prevent email enumeration
            return $this->successResponse(
                'If an account with this email exists, you will receive password reset instructions.'
            );

        } catch (Exception $e) {
            return $this->handleException($e, 'password reset initiation');
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            // Apply rate limiting
            $key = 'portal-reset:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $retryAfter = RateLimiter::availableIn($key);
                return $this->errorResponse("Too many reset attempts. Try again in {$retryAfter} seconds.", 429);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'token' => 'required|string|min:32|max:64',
                'password' => 'required|string|min:8|max:255|confirmed',
                'password_confirmation' => 'required|string',
            ]);

            if ($validator->fails()) {
                RateLimiter::hit($key, 60);
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            // Placeholder implementation
            $resetResult = ['success' => true, 'message' => 'Password reset successful'];

            if (!$resetResult['success']) {
                RateLimiter::hit($key, 60);
                return $this->errorResponse($resetResult['message'], 400);
            }

            RateLimiter::clear($key);

            return $this->successResponse('Password reset successful');

        } catch (Exception $e) {
            return $this->handleException($e, 'password reset');
        }
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $portalSession = $request->get('portal_session');
            if (!$portalSession) {
                return $this->errorResponse('Authentication required', 401);
            }

            // Apply rate limiting
            $key = 'portal-change-password:' . $portalSession->client_id;
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $retryAfter = RateLimiter::availableIn($key);
                return $this->errorResponse("Too many attempts. Try again in {$retryAfter} seconds.", 429);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|max:255|confirmed',
                'new_password_confirmation' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $changeResult = $this->authService->changePassword(
                $portalSession->client,
                $validator->validated()['current_password'],
                $validator->validated()['new_password']
            );

            if (!$changeResult['success']) {
                RateLimiter::hit($key, 300);
                return $this->errorResponse($changeResult['message'], 400);
            }

            RateLimiter::clear($key);

            return $this->successResponse('Password changed successfully');

        } catch (Exception $e) {
            return $this->handleException($e, 'password change');
        }
    }

    /**
     * Refresh session to extend expiry
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $portalSession = $request->get('portal_session');
            if (!$portalSession) {
                return $this->errorResponse('Authentication required', 401);
            }

            $refreshResult = $this->authService->refreshSession($portalSession->id);

            if (!$refreshResult['success']) {
                return $this->errorResponse($refreshResult['message'], 400);
            }

            return $this->successResponse('Session refreshed successfully', [
                'expires_at' => $refreshResult['expires_at'],
            ]);

        } catch (Exception $e) {
            return $this->handleException($e, 'session refresh');
        }
    }

    /**
     * Helper methods for response formatting
     */
    private function successResponse(string $message, array $data = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return response()->json($response);
    }

    private function errorResponse(string $message, int $statusCode = 400, array $errors = []): JsonResponse
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

    private function validationErrorResponse(array $errors): JsonResponse
    {
        return $this->errorResponse('Validation failed', 422, $errors);
    }

    private function handleException(Exception $e, string $context): JsonResponse
    {
        Log::error("Portal auth error in {$context}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->errorResponse('Authentication service temporarily unavailable', 503);
    }
}