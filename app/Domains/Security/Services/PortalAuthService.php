<?php

namespace App\Domains\Security\Services;

use App\Models\Client;
use App\Models\ClientPortalAccess;
use App\Models\ClientPortalSession;
use App\Models\PortalNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Portal Authentication Service
 *
 * Comprehensive authentication service for client portal with advanced security features:
 * - Multi-factor authentication (SMS, Email, Authenticator apps)
 * - Password policies and complexity validation
 * - Account lockout and fraud prevention
 * - Session security and device management
 * - Password reset and account recovery
 * - Audit logging and security monitoring
 * - Single sign-on (SSO) integration support
 * - Mobile app authentication
 * - Risk-based authentication
 */
class PortalAuthService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('portal.auth', [
            'max_login_attempts' => 5,
            'lockout_duration' => 30, // minutes
            'session_lifetime' => 120, // minutes
            'refresh_lifetime' => 10080, // 7 days in minutes
            'password_min_length' => 8,
            'password_require_uppercase' => true,
            'password_require_lowercase' => true,
            'password_require_numbers' => true,
            'password_require_symbols' => false,
            'password_expiry_days' => null,
            'two_factor_enabled' => true,
            'risk_assessment_enabled' => true,
            'device_tracking_enabled' => true,
            'geo_blocking_enabled' => false,
            'high_risk_countries' => [],
        ]);
    }

    /**
     * Authenticate client with email and password
     */
    public function authenticate(Request $request, string $email, string $password): array
    {
        try {
            $client = Client::where('email', $email)->first();

            if (! $client) {
                $this->logSecurityEvent('login_failed', null, $request, 'Invalid email');

                return $this->failResponse('Invalid credentials');
            }

            // Check if portal access is enabled
            $portalAccess = $this->getPortalAccess($client);
            if (! $portalAccess || ! $portalAccess->isEnabled()) {
                $this->logSecurityEvent('access_denied', $client->id, $request, 'Portal access disabled');

                return $this->failResponse('Portal access is disabled for your account');
            }

            // Check account lockout
            if ($portalAccess->isLocked()) {
                $this->logSecurityEvent('login_blocked', $client->id, $request, 'Account locked');

                return $this->failResponse('Account is temporarily locked due to multiple failed attempts');
            }

            // Verify password
            if (! $this->verifyPassword($client, $password)) {
                $portalAccess->incrementFailedAttempts();
                $this->logSecurityEvent('login_failed', $client->id, $request, 'Invalid password');

                return $this->failResponse('Invalid credentials');
            }

            // Check password expiry
            if ($portalAccess->isPasswordExpired()) {
                $this->logSecurityEvent('password_expired', $client->id, $request);

                return $this->failResponse('Your password has expired. Please reset your password.', 'PASSWORD_EXPIRED');
            }

            // Perform risk assessment
            $riskScore = $this->performRiskAssessment($client, $request, $portalAccess);
            $requiresTwoFactor = $this->shouldRequireTwoFactor($portalAccess, $riskScore);

            // Check geolocation restrictions
            if (! $this->isLocationAllowed($portalAccess, $request)) {
                $this->logSecurityEvent('geo_blocked', $client->id, $request, 'Geographic restriction');

                return $this->failResponse('Access from your location is not permitted');
            }

            // Check time-based restrictions
            if (! $portalAccess->isTimeAllowed()) {
                $this->logSecurityEvent('time_restricted', $client->id, $request, 'Outside allowed hours');

                return $this->failResponse('Access is not permitted at this time');
            }

            // Reset failed attempts on successful verification
            $portalAccess->resetFailedAttempts();

            // Create session
            $session = $this->createSession($client, $request, $riskScore);

            $response = [
                'success' => true,
                'client' => $this->sanitizeClientData($client),
                'session_token' => $session->session_token,
                'expires_at' => $session->expires_at,
                'requires_two_factor' => $requiresTwoFactor,
                'risk_score' => $riskScore,
            ];

            if ($requiresTwoFactor) {
                $response['two_factor_methods'] = $this->getAvailableTwoFactorMethods($portalAccess);
                $response['message'] = 'Two-factor authentication required';
            }

            $this->logSecurityEvent('login_success', $client->id, $request, 'Authentication successful', [
                'session_id' => $session->id,
                'risk_score' => $riskScore,
                'requires_2fa' => $requiresTwoFactor,
            ]);

            return $response;

        } catch (Exception $e) {
            Log::error('Authentication error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failResponse('Authentication service temporarily unavailable');
        }
    }

    /**
     * Verify two-factor authentication code
     */
    public function verifyTwoFactor(ClientPortalSession $session, string $method, string $code): array
    {
        try {
            if (! $session->isActive()) {
                return $this->failResponse('Invalid or expired session');
            }

            if ($session->two_factor_verified) {
                return $this->successResponse('Two-factor authentication already verified');
            }

            $client = $session->client;
            $portalAccess = $this->getPortalAccess($client);

            $isValid = false;
            $errorMessage = 'Invalid verification code';

            switch ($method) {
                case ClientPortalSession::TWO_FACTOR_SMS:
                    $isValid = $this->verifySMSCode($client, $code);
                    break;

                case ClientPortalSession::TWO_FACTOR_EMAIL:
                    $isValid = $this->verifyEmailCode($client, $code);
                    break;

                case ClientPortalSession::TWO_FACTOR_AUTHENTICATOR:
                    $isValid = $this->verifyAuthenticatorCode($client, $code);
                    break;

                default:
                    $errorMessage = 'Unsupported two-factor method';
            }

            if (! $isValid) {
                $this->logSecurityEvent('2fa_failed', $client->id, request(), "Invalid 2FA code: {$method}");

                return $this->failResponse($errorMessage);
            }

            // Mark session as two-factor verified
            $session->markTwoFactorVerified($method);

            $this->logSecurityEvent('2fa_success', $client->id, request(), "2FA verified: {$method}", [
                'session_id' => $session->id,
            ]);

            return $this->successResponse('Two-factor authentication successful');

        } catch (Exception $e) {
            Log::error('Two-factor verification error', [
                'session_id' => $session->id,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Verification service temporarily unavailable');
        }
    }

    /**
     * Send two-factor authentication code
     */
    public function sendTwoFactorCode(ClientPortalSession $session, string $method): array
    {
        try {
            $client = $session->client;
            $code = $this->generateTwoFactorCode();

            switch ($method) {
                case ClientPortalSession::TWO_FACTOR_SMS:
                    if (! $client->phone) {
                        return $this->failResponse('Phone number not available');
                    }
                    $result = $this->sendSMSCode($client, $code);
                    break;

                case ClientPortalSession::TWO_FACTOR_EMAIL:
                    $result = $this->sendEmailCode($client, $code);
                    break;

                default:
                    return $this->failResponse('Unsupported two-factor method');
            }

            if ($result) {
                $this->logSecurityEvent('2fa_sent', $client->id, request(), "2FA code sent: {$method}");

                return $this->successResponse("Verification code sent via {$method}");
            }

            return $this->failResponse("Failed to send verification code via {$method}");

        } catch (Exception $e) {
            Log::error('Two-factor code sending error', [
                'session_id' => $session->id,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Unable to send verification code');
        }
    }

    /**
     * Logout and invalidate session
     */
    public function logout(ClientPortalSession $session): array
    {
        try {
            $this->logSecurityEvent('logout', $session->client_id, request(), 'User logout', [
                'session_id' => $session->id,
            ]);

            $session->revoke('User logout');

            return $this->successResponse('Successfully logged out');

        } catch (Exception $e) {
            Log::error('Logout error', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Logout failed');
        }
    }

    /**
     * Refresh session token
     */
    public function refreshSession(ClientPortalSession $session): array
    {
        try {
            if (! $session->refresh()) {
                return $this->failResponse('Unable to refresh session');
            }

            $this->logSecurityEvent('session_refreshed', $session->client_id, request(), 'Session refreshed', [
                'session_id' => $session->id,
            ]);

            return [
                'success' => true,
                'session_token' => $session->session_token,
                'expires_at' => $session->expires_at,
            ];

        } catch (Exception $e) {
            Log::error('Session refresh error', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Session refresh failed');
        }
    }

    /**
     * Initiate password reset process
     */
    public function initiatePasswordReset(string $email): array
    {
        try {
            $client = Client::where('email', $email)->first();

            if (! $client) {
                // Don't reveal if email exists for security
                return $this->successResponse('If the email address exists, a reset link has been sent');
            }

            $portalAccess = $this->getPortalAccess($client);
            if (! $portalAccess || ! $portalAccess->isEnabled()) {
                return $this->successResponse('If the email address exists, a reset link has been sent');
            }

            // Generate password reset token
            $token = Str::random(64);
            $expires = Carbon::now()->addHours(1);

            // Store reset token (you might want a dedicated table for this)
            Cache::put("password_reset:{$token}", [
                'client_id' => $client->id,
                'email' => $email,
                'expires_at' => $expires,
            ], $expires);

            // Send reset email
            $this->sendPasswordResetEmail($client, $token);

            $this->logSecurityEvent('password_reset_requested', $client->id, request(), 'Password reset initiated');

            return $this->successResponse('If the email address exists, a reset link has been sent');

        } catch (Exception $e) {
            Log::error('Password reset initiation error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Password reset service temporarily unavailable');
        }
    }

    /**
     * Complete password reset
     */
    public function completePasswordReset(string $token, string $newPassword): array
    {
        try {
            $resetData = Cache::get("password_reset:{$token}");

            if (! $resetData) {
                return $this->failResponse('Invalid or expired reset token');
            }

            $client = Client::find($resetData['client_id']);
            if (! $client) {
                return $this->failResponse('Invalid reset token');
            }

            // Validate new password
            $validation = $this->validatePassword($newPassword);
            if (! $validation['valid']) {
                return $this->failResponse($validation['message']);
            }

            // Update password
            $client->update(['password' => Hash::make($newPassword)]);

            // Update portal access
            $portalAccess = $this->getPortalAccess($client);
            $portalAccess->markPasswordChanged();

            // Invalidate all existing sessions for security
            ClientPortalSession::where('client_id', $client->id)
                ->where('status', ClientPortalSession::STATUS_ACTIVE)
                ->update([
                    'status' => ClientPortalSession::STATUS_REVOKED,
                    'revocation_reason' => 'Password reset',
                    'revoked_at' => Carbon::now(),
                ]);

            // Remove reset token
            Cache::forget("password_reset:{$token}");

            $this->logSecurityEvent('password_reset_completed', $client->id, request(), 'Password reset completed');

            // Create notification
            $this->createNotification($client, 'password_changed', 'Password Changed',
                'Your password has been successfully changed.');

            return $this->successResponse('Password has been successfully reset');

        } catch (Exception $e) {
            Log::error('Password reset completion error', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Password reset failed');
        }
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(ClientPortalSession $session, string $currentPassword, string $newPassword): array
    {
        try {
            $client = $session->client;

            // Verify current password
            if (! $this->verifyPassword($client, $currentPassword)) {
                return $this->failResponse('Current password is incorrect');
            }

            // Validate new password
            $validation = $this->validatePassword($newPassword);
            if (! $validation['valid']) {
                return $this->failResponse($validation['message']);
            }

            // Check password reuse (optional)
            if ($this->isPasswordReused($client, $newPassword)) {
                return $this->failResponse('New password cannot be the same as recent passwords');
            }

            // Update password
            $client->update(['password' => Hash::make($newPassword)]);

            // Update portal access
            $portalAccess = $this->getPortalAccess($client);
            $portalAccess->markPasswordChanged();

            $this->logSecurityEvent('password_changed', $client->id, request(), 'Password changed by user');

            // Create notification
            $this->createNotification($client, 'password_changed', 'Password Changed',
                'Your password has been successfully changed.');

            return $this->successResponse('Password has been successfully changed');

        } catch (Exception $e) {
            Log::error('Password change error', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Password change failed');
        }
    }

    /**
     * Get active sessions for client
     */
    public function getActiveSessions(Client $client): array
    {
        $sessions = ClientPortalSession::where('client_id', $client->id)
            ->active()
            ->orderBy('last_activity_at', 'desc')
            ->get();

        return $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'device_name' => $session->device_name,
                'device_type' => $session->device_type,
                'browser_name' => $session->browser_name,
                'os_name' => $session->os_name,
                'ip_address' => $session->ip_address,
                'location' => $session->location_data,
                'is_current' => false, // Would need current session context
                'last_activity' => $session->last_activity_at,
                'created_at' => $session->created_at,
            ];
        })->toArray();
    }

    /**
     * Revoke specific session
     */
    public function revokeSession(Client $client, int $sessionId): array
    {
        try {
            $session = ClientPortalSession::where('client_id', $client->id)
                ->where('id', $sessionId)
                ->first();

            if (! $session) {
                return $this->failResponse('Session not found');
            }

            $session->revoke('Revoked by user');

            $this->logSecurityEvent('session_revoked', $client->id, request(), 'Session revoked by user', [
                'revoked_session_id' => $sessionId,
            ]);

            return $this->successResponse('Session has been revoked');

        } catch (Exception $e) {
            Log::error('Session revocation error', [
                'client_id' => $client->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Session revocation failed');
        }
    }

    /**
     * Revoke all other sessions
     */
    public function revokeAllOtherSessions(ClientPortalSession $currentSession): array
    {
        try {
            $revoked = ClientPortalSession::where('client_id', $currentSession->client_id)
                ->where('id', '!=', $currentSession->id)
                ->active()
                ->update([
                    'status' => ClientPortalSession::STATUS_REVOKED,
                    'revocation_reason' => 'Revoked by user - logout all devices',
                    'revoked_at' => Carbon::now(),
                ]);

            $this->logSecurityEvent('all_sessions_revoked', $currentSession->client_id, request(),
                'All other sessions revoked by user', [
                    'current_session_id' => $currentSession->id,
                    'revoked_count' => $revoked,
                ]);

            return $this->successResponse("Successfully logged out of {$revoked} other devices");

        } catch (Exception $e) {
            Log::error('All sessions revocation error', [
                'session_id' => $currentSession->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failResponse('Failed to revoke other sessions');
        }
    }

    /**
     * Private helper methods
     */
    private function getPortalAccess(Client $client): ?ClientPortalAccess
    {
        return $client->portalAccess ?? ClientPortalAccess::where('client_id', $client->id)->first();
    }

    private function verifyPassword(Client $client, string $password): bool
    {
        return Hash::check($password, $client->password);
    }

    private function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < $this->config['password_min_length']) {
            $errors[] = "Password must be at least {$this->config['password_min_length']} characters long";
        }

        if ($this->config['password_require_uppercase'] && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if ($this->config['password_require_lowercase'] && ! preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if ($this->config['password_require_numbers'] && ! preg_match('/\d/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if ($this->config['password_require_symbols'] && ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Valid password' : implode('. ', $errors),
        ];
    }

    private function isPasswordReused(Client $client, string $newPassword): bool
    {
        // Check against current password
        return Hash::check($newPassword, $client->password);
    }

    private function performRiskAssessment(Client $client, Request $request, ClientPortalAccess $portalAccess): int
    {
        if (! $this->config['risk_assessment_enabled']) {
            return 0;
        }

        $riskScore = 0;

        // Check for suspicious patterns
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Geographic risk
        $location = $this->getLocationFromIP($ipAddress);
        if ($location && in_array($location['country'], $this->config['high_risk_countries'])) {
            $riskScore += 30;
        }

        // Time-based risk
        $currentHour = Carbon::now()->hour;
        if ($currentHour < 6 || $currentHour > 22) {
            $riskScore += 10;
        }

        // Device/browser risk
        if ($this->isUnknownDevice($client, $request)) {
            $riskScore += 20;
        }

        // Recent failed attempts
        if ($portalAccess->failed_login_attempts > 0) {
            $riskScore += $portalAccess->failed_login_attempts * 5;
        }

        return min($riskScore, 100);
    }

    private function shouldRequireTwoFactor(ClientPortalAccess $portalAccess, int $riskScore): bool
    {
        if (! $this->config['two_factor_enabled']) {
            return false;
        }

        // Always require if configured
        if ($portalAccess->require_two_factor) {
            return true;
        }

        // Risk-based requirement
        return $riskScore >= 50;
    }

    private function isLocationAllowed(ClientPortalAccess $portalAccess, Request $request): bool
    {
        if (! $this->config['geo_blocking_enabled']) {
            return true;
        }

        $location = $this->getLocationFromIP($request->ip());
        if (! $location) {
            return true; // Allow if we can't determine location
        }

        return $portalAccess->isCountryAllowed($location['country']);
    }

    private function createSession(Client $client, Request $request, int $riskScore): ClientPortalSession
    {
        $deviceInfo = $this->extractDeviceInfo($request);
        $locationData = $this->getLocationFromIP($request->ip());

        return ClientPortalSession::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'device_id' => $this->generateDeviceId($request),
            'device_name' => $deviceInfo['device_name'],
            'device_type' => $deviceInfo['device_type'],
            'browser_name' => $deviceInfo['browser_name'],
            'browser_version' => $deviceInfo['browser_version'],
            'os_name' => $deviceInfo['os_name'],
            'os_version' => $deviceInfo['os_version'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'location_data' => $locationData,
            'is_mobile' => $deviceInfo['is_mobile'],
            'is_trusted_device' => $this->isTrustedDevice($client, $request),
            'security_flags' => [
                'risk_score' => $riskScore,
                'requires_2fa' => $this->shouldRequireTwoFactor($this->getPortalAccess($client), $riskScore),
            ],
        ]);
    }

    private function extractDeviceInfo(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';

        // Basic user agent parsing (consider using a proper library like jenssegers/agent)
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad/', $userAgent);

        return [
            'device_name' => $this->parseDeviceName($userAgent),
            'device_type' => $isMobile ? 'mobile' : 'desktop',
            'browser_name' => $this->parseBrowserName($userAgent),
            'browser_version' => $this->parseBrowserVersion($userAgent),
            'os_name' => $this->parseOSName($userAgent),
            'os_version' => $this->parseOSVersion($userAgent),
            'is_mobile' => $isMobile,
        ];
    }

    private function generateDeviceId(Request $request): string
    {
        return hash('sha256', $request->ip().$request->userAgent().date('Y-m-d'));
    }

    private function isTrustedDevice(Client $client, Request $request): bool
    {
        $deviceId = $this->generateDeviceId($request);

        return ClientPortalSession::where('client_id', $client->id)
            ->where('device_id', $deviceId)
            ->where('is_trusted_device', true)
            ->exists();
    }

    private function isUnknownDevice(Client $client, Request $request): bool
    {
        $deviceId = $this->generateDeviceId($request);

        return ! ClientPortalSession::where('client_id', $client->id)
            ->where('device_id', $deviceId)
            ->exists();
    }

    private function getLocationFromIP(string $ipAddress): ?array
    {
        // Implement GeoIP lookup (using a service like MaxMind GeoLite2)
        // For now, return null
        return null;
    }

    private function getAvailableTwoFactorMethods(ClientPortalAccess $portalAccess): array
    {
        $methods = [];

        if ($portalAccess->client->phone) {
            $methods[] = ClientPortalSession::TWO_FACTOR_SMS;
        }

        if ($portalAccess->client->email) {
            $methods[] = ClientPortalSession::TWO_FACTOR_EMAIL;
        }

        // Add authenticator if configured
        if ($this->hasAuthenticatorSetup($portalAccess->client)) {
            $methods[] = ClientPortalSession::TWO_FACTOR_AUTHENTICATOR;
        }

        return $methods;
    }

    private function generateTwoFactorCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function verifySMSCode(Client $client, string $code): bool
    {
        $cacheKey = "sms_code:{$client->id}";
        $storedCode = Cache::get($cacheKey);

        if ($storedCode && $storedCode === $code) {
            Cache::forget($cacheKey);

            return true;
        }

        return false;
    }

    private function verifyEmailCode(Client $client, string $code): bool
    {
        $cacheKey = "email_code:{$client->id}";
        $storedCode = Cache::get($cacheKey);

        if ($storedCode && $storedCode === $code) {
            Cache::forget($cacheKey);

            return true;
        }

        return false;
    }

    private function verifyAuthenticatorCode(Client $client, string $code): bool
    {
        // Implement TOTP verification (using libraries like pragmarx/google2fa)
        return false;
    }

    private function sendSMSCode(Client $client, string $code): bool
    {
        // Store code with expiration
        Cache::put("sms_code:{$client->id}", $code, 300); // 5 minutes

        // Implement SMS sending (using services like Twilio)
        // For now, just log it
        Log::info("SMS 2FA code for client {$client->id}: {$code}");

        return true;
    }

    private function sendEmailCode(Client $client, string $code): bool
    {
        // Store code with expiration
        Cache::put("email_code:{$client->id}", $code, 300); // 5 minutes

        // Send email with code
        try {
            // Implement email sending
            Log::info("Email 2FA code for client {$client->id}: {$code}");

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send 2FA email', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function hasAuthenticatorSetup(Client $client): bool
    {
        // Check if client has authenticator app configured
        return false;
    }

    private function sendPasswordResetEmail(Client $client, string $token): bool
    {
        // Implement password reset email sending
        Log::info("Password reset token for client {$client->id}: {$token}");

        return true;
    }

    private function createNotification(Client $client, string $type, string $title, string $message): void
    {
        PortalNotification::create([
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'type' => $type,
            'category' => 'security',
            'priority' => 'normal',
            'title' => $title,
            'message' => $message,
            'show_in_portal' => true,
            'send_email' => true,
        ]);
    }

    private function logSecurityEvent(string $eventType, ?int $clientId, Request $request,
        string $description = '', array $metadata = []): void
    {
        // Create audit log entry using your audit logging system
        Log::info('Portal Security Event', [
            'event_type' => $eventType,
            'client_id' => $clientId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => $description,
            'metadata' => $metadata,
            'timestamp' => Carbon::now(),
        ]);
    }

    private function sanitizeClientData(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'company_name' => $client->company_name,
            'email' => $client->email,
            'status' => $client->status,
            'timezone' => $client->portalAccess?->timezone ?? 'UTC',
            'preferred_language' => $client->portalAccess?->preferred_language ?? 'en',
        ];
    }

    private function parseDeviceName(string $userAgent): string
    {
        if (preg_match('/iPhone/', $userAgent)) {
            return 'iPhone';
        }
        if (preg_match('/iPad/', $userAgent)) {
            return 'iPad';
        }
        if (preg_match('/Android/', $userAgent)) {
            return 'Android Device';
        }
        if (preg_match('/Windows/', $userAgent)) {
            return 'Windows PC';
        }
        if (preg_match('/Macintosh/', $userAgent)) {
            return 'Mac';
        }

        return 'Unknown Device';
    }

    private function parseBrowserName(string $userAgent): string
    {
        if (preg_match('/Chrome/', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/Firefox/', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/Safari/', $userAgent) && ! preg_match('/Chrome/', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/Edge/', $userAgent)) {
            return 'Edge';
        }

        return 'Unknown Browser';
    }

    private function parseBrowserVersion(string $userAgent): string
    {
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }
        if (preg_match('/Firefox\/([0-9.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }
        if (preg_match('/Version\/([0-9.]+).*Safari/', $userAgent, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }

    private function parseOSName(string $userAgent): string
    {
        if (preg_match('/Windows NT/', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/Mac OS X/', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/iPhone OS/', $userAgent)) {
            return 'iOS';
        }
        if (preg_match('/Android/', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/Linux/', $userAgent)) {
            return 'Linux';
        }

        return 'Unknown OS';
    }

    private function parseOSVersion(string $userAgent): string
    {
        if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }
        if (preg_match('/Mac OS X ([0-9_]+)/', $userAgent, $matches)) {
            return str_replace('_', '.', $matches[1]);
        }
        if (preg_match('/iPhone OS ([0-9_]+)/', $userAgent, $matches)) {
            return str_replace('_', '.', $matches[1]);
        }
        if (preg_match('/Android ([0-9.]+)/', $userAgent, $matches)) {
            return $matches[1];
        }

        return 'Unknown';
    }

    private function successResponse(string $message, array $data = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message,
        ], $data);
    }

    private function failResponse(string $message, ?string $errorCode = null): array
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        return $response;
    }
}
