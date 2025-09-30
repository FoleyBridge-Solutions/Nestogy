<?php

namespace App\Domains\Client\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClientPortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

/**
 * Portal Authentication Controller
 * 
 * Handles authentication, session management, and password reset
 * for client portal users with company-based access control.
 */
class PortalAuthController extends Controller
{
    /**
     * Show the portal login form
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('portal.auth.login');
    }

    /**
     * Handle portal login request
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'company_code' => 'nullable|string',
            'remember' => 'boolean'
        ]);

        // Rate limiting
        $this->ensureIsNotRateLimited($request);

        try {
            // Find the user
            $user = ClientPortalUser::where('email', $request->email)
                ->when($request->company_code, function ($query) use ($request) {
                    $query->whereHas('company', function ($q) use ($request) {
                        $q->where('code', $request->company_code);
                    });
                })
                ->first();

            if (!$user) {
                RateLimiter::hit($this->throttleKey($request));
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Check if account is locked
            if ($user->isLocked()) {
                throw ValidationException::withMessages([
                    'email' => ['Your account has been locked. Please try again later or contact support.'],
                ]);
            }

            // Check if account is active
            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['Your account has been deactivated. Please contact your administrator.'],
                ]);
            }

            // Check IP restrictions
            if (!$user->isIpAllowed($request->ip())) {
                Log::warning('Portal login attempt from unauthorized IP', [
                    'user_id' => $user->id,
                    'ip' => $request->ip()
                ]);
                throw ValidationException::withMessages([
                    'email' => ['Access denied from this location.'],
                ]);
            }

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                $user->recordFailedLogin();
                RateLimiter::hit($this->throttleKey($request));
                
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Check if 2FA is enabled
            if ($user->two_factor_secret) {
                // Store user ID in session for 2FA verification
                session(['portal_2fa_user' => $user->id]);
                return redirect()->route('portal.two-factor.challenge');
            }

            // Perform login
            $this->performLogin($user, $request->remember);
            
            return $this->redirectAfterLogin($user);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Portal login error', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);
            
            throw ValidationException::withMessages([
                'email' => ['An error occurred during login. Please try again.'],
            ]);
        }
    }

    /**
     * Show two-factor challenge form
     *
     * @return \Illuminate\View\View
     */
    public function showTwoFactorChallenge()
    {
        if (!session('portal_2fa_user')) {
            return redirect()->route('portal.login');
        }

        return view('portal.auth.two-factor-challenge');
    }

    /**
     * Verify two-factor authentication code
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $userId = session('portal_2fa_user');
        if (!$userId) {
            return redirect()->route('portal.login');
        }

        $user = ClientPortalUser::find($userId);
        if (!$user) {
            session()->forget('portal_2fa_user');
            return redirect()->route('portal.login');
        }

        // Verify 2FA code
        $valid = app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)
            ->verify($user->two_factor_secret, $request->code);

        if (!$valid) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code was invalid.'],
            ]);
        }

        // Clear 2FA session
        session()->forget('portal_2fa_user');

        // Perform login
        $this->performLogin($user, $request->boolean('remember'));

        return $this->redirectAfterLogin($user);
    }

    /**
     * Perform the actual login
     *
     * @param ClientPortalUser $user
     * @param bool $remember
     * @return void
     */
    protected function performLogin(ClientPortalUser $user, bool $remember = false)
    {
        // Record successful login
        $user->recordSuccessfulLogin();

        // Clear rate limiter
        RateLimiter::clear($this->throttleKey(request()));

        // Login the user
        Auth::guard('portal')->login($user, $remember);

        // Regenerate session
        request()->session()->regenerate();

        // Set session timeout
        if ($user->session_timeout_minutes) {
            config(['session.lifetime' => $user->session_timeout_minutes]);
        }

        // Log activity
        activity()
            ->causedBy($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ])
            ->log('Portal user logged in');
    }

    /**
     * Redirect after successful login
     *
     * @param ClientPortalUser $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectAfterLogin(ClientPortalUser $user)
    {
        // Check if password change is required
        if ($user->needsPasswordChange()) {
            return redirect()->route('portal.password.change')
                ->with('warning', 'You must change your password before continuing.');
        }

        // Redirect to intended URL or dashboard
        return redirect()->intended(route('portal.dashboard'));
    }

    /**
     * Handle portal logout
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::guard('portal')->user();

        if ($user) {
            // Log activity
            activity()
                ->causedBy($user)
                ->log('Portal user logged out');
        }

        Auth::guard('portal')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')
            ->with('success', 'You have been successfully logged out.');
    }

    /**
     * Show password reset request form
     *
     * @return \Illuminate\View\View
     */
    public function showPasswordResetForm()
    {
        return view('portal.auth.forgot-password');
    }

    /**
     * Handle password reset request
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendPasswordResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'company_code' => 'nullable|string'
        ]);

        // Find user
        $user = ClientPortalUser::where('email', $request->email)
            ->when($request->company_code, function ($query) use ($request) {
                $query->whereHas('company', function ($q) use ($request) {
                    $q->where('code', $request->company_code);
                });
            })
            ->first();

        if (!$user || !$user->is_active) {
            // Don't reveal if user exists
            return back()->with('status', 'If an account exists with this email, you will receive a password reset link.');
        }

        // Send password reset link
        $status = Password::broker('portal_users')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            // Log activity
            activity()
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('Password reset requested');

            return back()->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show password reset form
     *
     * @param string $token
     * @return \Illuminate\View\View
     */
    public function showResetPasswordForm($token)
    {
        return view('portal.auth.reset-password', ['token' => $token]);
    }

    /**
     * Handle password reset
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('portal_users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'password_changed_at' => now(),
                    'must_change_password' => false
                ])->save();

                // Log activity
                activity()
                    ->causedBy($user)
                    ->log('Password reset completed');
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('portal.login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    /**
     * Show password change form
     *
     * @return \Illuminate\View\View
     */
    public function showPasswordChangeForm()
    {
        return view('portal.auth.change-password');
    }

    /**
     * Handle password change
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed|different:current_password',
        ]);

        $user = Auth::guard('portal')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'must_change_password' => false
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->log('Password changed');

        return redirect()->route('portal.dashboard')
            ->with('success', 'Your password has been successfully changed.');
    }

    /**
     * Enable two-factor authentication
     *
     * @param Request $request
     * @param EnableTwoFactorAuthentication $enable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enableTwoFactor(Request $request, EnableTwoFactorAuthentication $enable)
    {
        $user = Auth::guard('portal')->user();
        
        $enable($user);

        // Log activity
        activity()
            ->causedBy($user)
            ->log('Two-factor authentication enabled');

        return redirect()->route('portal.account.security')
            ->with('success', 'Two-factor authentication has been enabled.');
    }

    /**
     * Disable two-factor authentication
     *
     * @param Request $request
     * @param DisableTwoFactorAuthentication $disable
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disableTwoFactor(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $request->validate([
            'password' => 'required'
        ]);

        $user = Auth::guard('portal')->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect.'],
            ]);
        }

        $disable($user);

        // Log activity
        activity()
            ->causedBy($user)
            ->log('Two-factor authentication disabled');

        return redirect()->route('portal.account.security')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    /**
     * Ensure request is not rate limited
     *
     * @param Request $request
     * @return void
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(Request $request)
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => [
                'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
            ],
        ]);
    }

    /**
     * Get the throttle key for rate limiting
     *
     * @param Request $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        return 'portal_login_' . $request->ip();
    }
}