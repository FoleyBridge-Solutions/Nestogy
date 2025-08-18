<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Setting;
use App\Domains\Security\Services\SuspiciousLoginService;
use App\Domains\Security\Services\IpLookupService;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. Compatible with Laravel 11.
    |
    */

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    protected SuspiciousLoginService $suspiciousLoginService;
    protected IpLookupService $ipLookupService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        SuspiciousLoginService $suspiciousLoginService,
        IpLookupService $ipLookupService
    ) {
        $this->suspiciousLoginService = $suspiciousLoginService;
        $this->ipLookupService = $ipLookupService;
        
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function showLoginForm()
    {
        $companies = Company::all();
        
        // Get the first company's settings to check if remember me is enabled
        $settings = null;
        if ($companies->isNotEmpty()) {
            $settings = Setting::where('company_id', $companies->first()->id)->first();
        }
        
        return view('auth.login', compact('companies', 'settings'));
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);
        
        // Get company settings to check security policies
        $companyId = $request->input('company_id');
        $settings = null;
        
        if ($companyId) {
            $settings = Setting::where('company_id', $companyId)->first();
        }
        
        // Check if remember me is allowed
        $rememberMe = false;
        if ($settings && $settings->remember_me_enabled) {
            $rememberMe = $request->boolean('remember');
        }
        
        // Check max login attempts
        if ($settings && $settings->max_login_attempts > 0) {
            $this->checkLoginAttempts($request, $settings);
        }

        // Attempt to log the user in
        if (Auth::attempt($this->credentials($request), $rememberMe)) {
            $user = Auth::user();
            
            // Check for suspicious login patterns
            $suspiciousAttempt = $this->suspiciousLoginService->analyzeLoginAttempt($user, $request);
            
            if ($suspiciousAttempt) {
                // Log out the user temporarily
                Auth::logout();
                $request->session()->invalidate();
                
                // Return response indicating verification is required
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'Suspicious login detected. Please check your email for verification.',
                        'verification_required' => true,
                        'verification_token' => $suspiciousAttempt->verification_token,
                        'expires_at' => $suspiciousAttempt->expires_at->toISOString(),
                    ], 202);
                }
                
                return redirect()->route('login')->with([
                    'verification_required' => true,
                    'verification_token' => $suspiciousAttempt->verification_token,
                    'message' => 'We detected a login from an unusual location. Please check your email to verify this login attempt.',
                ]);
            }
            
            // Check if there's an approved suspicious login attempt
            $approvedToken = session('suspicious_login_approved');
            if ($approvedToken) {
                session()->forget('suspicious_login_approved');
            }
            
            $request->session()->regenerate();
            
            // Clear login attempts on successful login
            if ($settings && $settings->max_login_attempts > 0) {
                $this->clearLoginAttempts($request);
            }
            
            // Enhanced audit logging with IP enrichment
            if ($settings && $settings->audit_login_attempts) {
                $this->logEnhancedLoginAttempt($request, $user, true);
            }

            return $this->authenticated($request, $user)
                ?: redirect()->intended($this->redirectPath());
        }
        
        // Log failed login attempt if audit is enabled
        if ($settings && $settings->audit_login_attempts) {
            $this->logEnhancedLoginAttempt($request, null, false);
        }
        
        // Increment login attempts
        if ($settings && $settings->max_login_attempts > 0) {
            $this->incrementLoginAttempts($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form.
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? response()->json([], 204)
            : redirect('/');
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
            'company_id' => 'required|exists:companies,id',
        ]);
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        $credentials = $request->only($this->username(), 'password');
        $credentials['company_id'] = $request->input('company_id');
        return $credentials;
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'email';
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Set session lifetime based on settings
        $settings = Setting::where('company_id', $user->company_id)->first();
        
        if ($settings) {
            // Configure session based on settings
            if ($settings->session_lifetime) {
                config(['session.lifetime' => $settings->session_lifetime]);
            }
            
            // Store idle timeout in session for client-side handling
            if ($settings->idle_timeout) {
                session(['idle_timeout' => $settings->idle_timeout * 60 * 1000]); // Convert to milliseconds
            }
        }
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        //
    }

    /**
     * Get the post-login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }
    
    /**
     * Check if user has exceeded login attempts
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Setting  $settings
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function checkLoginAttempts(Request $request, Setting $settings)
    {
        $key = $this->throttleKey($request);
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= $settings->max_login_attempts) {
            $lockoutMinutes = $settings->login_lockout_duration ?? 15;
            $remainingSeconds = cache()->get($key . ':timer', 0) - time();
            
            if ($remainingSeconds > 0) {
                $remainingMinutes = ceil($remainingSeconds / 60);
                
                throw ValidationException::withMessages([
                    $this->username() => ["Too many login attempts. Please try again in {$remainingMinutes} minutes."],
                ]);
            } else {
                // Lockout expired, clear attempts
                $this->clearLoginAttempts($request);
            }
        }
    }
    
    /**
     * Increment login attempts
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function incrementLoginAttempts(Request $request)
    {
        $key = $this->throttleKey($request);
        $attempts = cache()->get($key, 0) + 1;
        
        cache()->put($key, $attempts, now()->addMinutes(60));
        
        // If max attempts reached, set lockout timer
        $settings = Setting::where('company_id', $request->input('company_id'))->first();
        if ($settings && $attempts >= $settings->max_login_attempts) {
            $lockoutMinutes = $settings->login_lockout_duration ?? 15;
            cache()->put($key . ':timer', time() + ($lockoutMinutes * 60), now()->addMinutes($lockoutMinutes));
        }
    }
    
    /**
     * Clear login attempts
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clearLoginAttempts(Request $request)
    {
        $key = $this->throttleKey($request);
        cache()->forget($key);
        cache()->forget($key . ':timer');
    }
    
    /**
     * Get the throttle key for the request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request)
    {
        return 'login_attempts:' . $request->input($this->username()) . ':' . $request->ip();
    }
    
    /**
     * Log login attempt
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $success
     * @return void
     */
    protected function logLoginAttempt(Request $request, bool $success)
    {
        // This would typically log to your audit log table
        // For now, we'll use Laravel's built-in logging
        \Log::info('Login attempt', [
            'email' => $request->input($this->username()),
            'company_id' => $request->input('company_id'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success' => $success,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Enhanced login attempt logging with IP enrichment
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User|null  $user
     * @param  bool  $success
     * @return void
     */
    protected function logEnhancedLoginAttempt(Request $request, $user, bool $success)
    {
        try {
            // Get IP enrichment data
            $ipEnrichment = $this->ipLookupService->enrichAuditLogWithIpData($request->ip());
            
            $logData = [
                'user_id' => $user?->id,
                'company_id' => $user?->company_id ?? $request->input('company_id'),
                'event_type' => AuditLog::EVENT_LOGIN,
                'action' => $success ? 'login_success' : 'login_failed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'response_status' => $success ? 200 : 401,
                'severity' => $success ? AuditLog::SEVERITY_INFO : AuditLog::SEVERITY_WARNING,
                'metadata' => array_merge([
                    'email' => $request->input($this->username()),
                    'remember_me' => $request->boolean('remember'),
                ], $ipEnrichment),
            ];

            AuditLog::create($logData);

        } catch (\Exception $e) {
            // Fallback to basic logging if enhanced logging fails
            \Log::error('Enhanced login logging failed', [
                'error' => $e->getMessage(),
                'request_data' => [
                    'email' => $request->input($this->username()),
                    'ip' => $request->ip(),
                    'success' => $success,
                ]
            ]);
            
            // Still call the basic logging
            $this->logLoginAttempt($request, $success);
        }
    }

    /**
     * Handle waiting for suspicious login approval
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSuspiciousLoginApproval(Request $request)
    {
        $token = $request->input('verification_token');
        
        if (!$token) {
            return response()->json(['error' => 'Token required'], 400);
        }

        $attempt = \App\Domains\Security\Models\SuspiciousLoginAttempt::where('verification_token', $token)->first();

        if (!$attempt) {
            return response()->json(['error' => 'Invalid token'], 404);
        }

        if ($attempt->isApproved()) {
            // Log the user in
            Auth::login($attempt->user, true);
            session(['suspicious_login_approved' => $token]);
            
            return response()->json([
                'approved' => true,
                'redirect_url' => route('dashboard'),
            ]);
        } elseif ($attempt->isDenied()) {
            return response()->json([
                'denied' => true,
                'message' => 'Login attempt was denied for security reasons.',
            ]);
        } elseif ($attempt->isExpired()) {
            return response()->json([
                'expired' => true,
                'message' => 'Verification token has expired. Please try logging in again.',
            ]);
        }

        return response()->json([
            'pending' => true,
            'expires_at' => $attempt->expires_at->toISOString(),
        ]);
    }
}