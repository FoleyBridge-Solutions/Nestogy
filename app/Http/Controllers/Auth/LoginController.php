<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * LoginController
 * 
 * Handles user authentication with multi-tenant support and role-based access.
 * Supports company selection during login and remember me functionality.
 */
class LoginController extends Controller
{
    /**
     * Maximum number of login attempts allowed.
     */
    protected int $maxAttempts = 5;

    /**
     * Number of minutes to throttle for.
     */
    protected int $decayMinutes = 1;

    /**
     * Where to redirect users after login.
     */
    protected string $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Show the application's login form.
     */
    public function showLoginForm(): View
    {
        $companies = Company::orderBy('name')->get();
        
        return view('auth.login', compact('companies'));
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request): RedirectResponse
    {
        $this->validateLogin($request);

        // Check if the user is locked out
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        // Attempt to authenticate the user
        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            $this->clearLoginAttempts($request);

            return $this->sendLoginResponse($request);
        }

        // Increment login attempts
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Validate the user login request.
     */
    protected function validateLogin(Request $request): void
    {
        $request->validate([
            $this->username() => 'required|string|email',
            'password' => 'required|string',
            'company_id' => 'required|integer|exists:companies,id',
            'remember' => 'in:0,1',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     */
    protected function attemptLogin(Request $request): bool
    {
        $credentials = $this->credentials($request);
        
        // Find user by email first
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            return false;
        }

        // Check if user is active
        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.inactive')],
            ]);
        }

        // Verify password
        if (!Hash::check($credentials['password'], $user->password)) {
            return false;
        }

        // Set company context in session
        Session::put('company_id', $request->input('company_id'));
        
        // Handle remember me functionality
        $remember = $request->boolean('remember');
        
        if ($remember) {
            $this->handleRememberMe($user);
        }

        // Log the user in
        Auth::login($user, $remember);

        // Log successful login
        $this->logSuccessfulLogin($user, $request);

        return true;
    }

    /**
     * Get the needed authorization credentials from the request.
     */
    protected function credentials(Request $request): array
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * Get the login username to be used by the controller.
     */
    public function username(): string
    {
        return 'email';
    }

    /**
     * Handle remember me functionality.
     */
    protected function handleRememberMe(User $user): void
    {
        $token = \Str::random(60);
        
        // Store remember token in user settings
        if ($user->userSetting) {
            $user->userSetting->update(['remember_me_token' => hash('sha256', $token)]);
        }
        
        // Set remember token on user
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * Send the response after the user was authenticated.
     */
    protected function sendLoginResponse(Request $request): RedirectResponse
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return $request->wantsJson()
                    ? new JsonResponse([], 204)
                    : redirect()->intended($this->redirectPath());
    }

    /**
     * The user has been authenticated.
     */
    protected function authenticated(Request $request, User $user): ?RedirectResponse
    {
        // Check if user requires MFA
        if ($user->userSetting && $user->userSetting->requiresMfa()) {
            // Redirect to MFA verification (to be implemented)
            return redirect()->route('auth.mfa.verify');
        }

        // Set user preferences in session
        $this->setUserPreferences($user);

        return null;
    }

    /**
     * Set user preferences in session.
     */
    protected function setUserPreferences(User $user): void
    {
        if ($user->userSetting) {
            Session::put('user_role', $user->userSetting->role);
            Session::put('records_per_page', $user->userSetting->getRecordsPerPage());
            Session::put('dashboard_financial_enable', $user->userSetting->hasFinancialDashboard());
            Session::put('dashboard_technical_enable', $user->userSetting->hasTechnicalDashboard());
        }
    }

    /**
     * Log out the user from the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        // Clear remember token
        if ($user && $user->userSetting) {
            $user->userSetting->update(['remember_me_token' => null]);
        }

        // Log successful logout
        $this->logSuccessfulLogout($user, $request);

        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect('/');
    }

    /**
     * Get the post-logout redirect path.
     */
    public function redirectPath(): string
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/dashboard';
    }

    /**
     * Log successful login attempt.
     */
    protected function logSuccessfulLogin(User $user, Request $request): void
    {
        \Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'company_id' => Session::get('company_id'),
        ]);
    }

    /**
     * Log successful logout attempt.
     */
    protected function logSuccessfulLogout(?User $user, Request $request): void
    {
        if ($user) {
            \Log::info('User logged out successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    /**
     * Get the guard to be used during authentication.
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * Determine if the user has too many failed login attempts.
     */
    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($request), $this->maxAttempts
        );
    }

    /**
     * Increment the login attempts for the user.
     */
    protected function incrementLoginAttempts(Request $request): void
    {
        $this->limiter()->hit(
            $this->throttleKey($request), $this->decayMinutes * 60
        );
    }

    /**
     * Clear the login locks for the given user credentials.
     */
    protected function clearLoginAttempts(Request $request): void
    {
        $this->limiter()->clear($this->throttleKey($request));
    }

    /**
     * Fire an event when a lockout occurs.
     */
    protected function fireLockoutEvent(Request $request): void
    {
        event(new \Illuminate\Auth\Events\Lockout($request));
    }

    /**
     * Redirect the user after determining they are locked out.
     */
    protected function sendLockoutResponse(Request $request): RedirectResponse
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ])->status(429);
    }

    /**
     * Get the failed login response instance.
     */
    protected function sendFailedLoginResponse(Request $request): RedirectResponse
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Get the throttle key for the given request.
     */
    protected function throttleKey(Request $request): string
    {
        return \Str::transliterate(\Str::lower($request->input($this->username())).'|'.$request->ip());
    }

    /**
     * Get the rate limiter instance.
     */
    protected function limiter()
    {
        return app(\Illuminate\Cache\RateLimiter::class);
    }

    /**
     * The user has logged out of the application.
     */
    protected function loggedOut(Request $request): ?RedirectResponse
    {
        return null;
    }
}