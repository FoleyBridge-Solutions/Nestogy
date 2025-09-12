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
        // No longer pass companies - security improvement
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     * Implements secure atomic authentication: password + 2FA validation together.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);
        
        // Implement rate limiting by IP to prevent enumeration attacks
        $this->checkRateLimit($request);
        
        // Get start time for consistent timing
        $startTime = microtime(true);
        
        try {
            // Step 1: Find user by email (don't reveal if user exists)
            $user = \App\Models\User::where('email', $request->email)
                ->whereNull('archived_at')
                ->first();
            
            // Step 2: Always check password hash (prevent timing attacks)
            $passwordValid = $user ? password_verify($request->password, $user->password) : false;
            
            if (!$passwordValid) {
                $this->ensureConsistentTiming($startTime);
                $this->incrementFailedAttempts($request);
                $this->failedLogin($request);
                return null; // This line won't be reached due to exception
            }
            
            // Step 3: Check 2FA if enabled (atomic with password check)
            if ($user->two_factor_secret) {
                $twoFactorCode = $request->input('code');
                if (!$twoFactorCode || !$this->verify2FA($user, $twoFactorCode)) {
                    $this->ensureConsistentTiming($startTime);
                    $this->incrementFailedAttempts($request);
                    $this->failedLogin($request);
                    return null; // This line won't be reached due to exception
                }
            }
            
            // Step 4: Get user's accessible companies
            $companies = $this->getUserCompanies($user);
            
            if ($companies->isEmpty()) {
                $this->ensureConsistentTiming($startTime);
                $this->incrementFailedAttempts($request);
                $this->failedLogin($request, 'Your account has been deactivated.');
                return null; // This line won't be reached due to exception
            }
            
            // Clear failed attempts on successful authentication
            $this->clearFailedAttempts($request);
            
            // Step 5: Handle company selection
            if ($companies->count() === 1) {
                // Single company - direct login
                return $this->completeLogin($request, $user, $companies->first());
            } else {
                // Multiple companies - show selection
                return $this->redirectToCompanySelection($user, $companies);
            }
            
        } catch (\Exception $e) {
            $this->ensureConsistentTiming($startTime);
            \Log::error('Login error: ' . $e->getMessage());
            $this->failedLogin($request);
            return null; // This line won't be reached due to exception
        }
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
            $this->username() => 'required|email',
            'password' => 'required|string|min:6',
            'code' => 'nullable|string|size:6', // Optional 2FA code
        ]);
    }

    /**
     * Show company selection form for multi-company users
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function showCompanySelection()
    {
        if (!session('pending_auth_user_id')) {
            return redirect()->route('login');
        }
        
        $userId = session('pending_auth_user_id');
        $user = \App\Models\User::find($userId);
        $companies = $this->getUserCompanies($user);
        
        return view('auth.select-company', compact('user', 'companies'));
    }
    
    /**
     * Handle company selection
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function selectCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer'
        ]);
        
        $userId = session('pending_auth_user_id');
        if (!$userId) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please login again.']);
        }
        
        $user = \App\Models\User::find($userId);
        $companies = $this->getUserCompanies($user);
        
        $selectedCompany = $companies->where('id', $request->company_id)->first();
        if (!$selectedCompany) {
            return redirect()->back()->withErrors(['company_id' => 'Invalid company selection.']);
        }
        
        // Clear the pending auth session
        session()->forget('pending_auth_user_id');
        
        return $this->completeLogin($request, $user, $selectedCompany);
    }

    /**
     * Get user's accessible companies
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    protected function getUserCompanies($user)
    {
        // Get user's primary company
        $companies = collect();
        
        if ($user->company_id) {
            $primaryCompany = Company::find($user->company_id);
            if ($primaryCompany) {
                $companies->push($primaryCompany);
            }
        }
        
        // Get additional cross-company access
        $crossCompanyAccess = \App\Models\CrossCompanyUser::where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now());
            })
            ->with('company')
            ->get();
        
        foreach ($crossCompanyAccess as $access) {
            if ($access->company && !$companies->contains('id', $access->company->id)) {
                $companies->push($access->company);
            }
        }
        
        return $companies;
    }
    
    /**
     * Verify 2FA code
     *
     * @param \App\Models\User $user
     * @param string $code
     * @return bool
     */
    protected function verify2FA($user, $code)
    {
        if (!$user->two_factor_secret) {
            return true; // 2FA not enabled
        }
        
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        return $google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $code,
            config('fortify.2fa.window', 4) // Allow 4 windows (2 minutes tolerance)
        );
    }
    
    /**
     * Complete the login process
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @param \App\Models\Company $company
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function completeLogin($request, $user, $company)
    {
        // Set company context for this session
        $user->company_id = $company->id;
        $user->save();
        
        // Login the user
        Auth::login($user, $request->boolean('remember'));
        
        // Regenerate session for security
        $request->session()->regenerate();
        
        // Set company in session
        session(['company_id' => $company->id]);
        
        // Log successful login
        $this->logSuccessfulLogin($request, $user, $company);
        
        // Apply company settings
        $this->applyCompanySettings($user, $company);
        
        return redirect()->intended($this->redirectPath());
    }
    
    /**
     * Redirect to company selection with user in session
     *
     * @param \App\Models\User $user
     * @param \Illuminate\Support\Collection $companies
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectToCompanySelection($user, $companies)
    {
        // Store user ID in session for company selection
        session(['pending_auth_user_id' => $user->id]);
        
        return redirect()->route('auth.company-select');
    }
    
    /**
     * Handle failed login with consistent response
     *
     * @param \Illuminate\Http\Request $request
     * @param string $message
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function failedLogin($request, $message = null)
    {
        throw ValidationException::withMessages([
            'email' => [$message ?? 'These credentials do not match our records.'],
        ]);
    }
    
    /**
     * Check rate limiting by IP
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function checkRateLimit($request)
    {
        $key = 'login_attempts:' . $request->ip();
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= 10) { // 10 attempts per hour per IP
            $remainingMinutes = cache()->get($key . ':timer', 0) - time();
            if ($remainingMinutes > 0) {
                throw ValidationException::withMessages([
                    'email' => ['Too many login attempts. Please try again later.'],
                ]);
            } else {
                cache()->forget($key);
                cache()->forget($key . ':timer');
            }
        }
    }
    
    /**
     * Ensure consistent response timing to prevent timing attacks
     *
     * @param float $startTime
     * @return void
     */
    protected function ensureConsistentTiming($startTime)
    {
        $elapsedTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $targetTime = 200; // Target 200ms response time
        
        if ($elapsedTime < $targetTime) {
            usleep(($targetTime - $elapsedTime) * 1000); // Convert back to microseconds
        }
    }
    
    /**
     * Increment failed login attempts
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function incrementFailedAttempts($request)
    {
        $key = 'login_attempts:' . $request->ip();
        $attempts = cache()->get($key, 0) + 1;
        
        cache()->put($key, $attempts, now()->addHour());
        
        if ($attempts >= 10) {
            cache()->put($key . ':timer', time() + 3600, now()->addHour()); // 1 hour lockout
        }
    }
    
    /**
     * Clear failed login attempts
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function clearFailedAttempts($request)
    {
        $key = 'login_attempts:' . $request->ip();
        cache()->forget($key);
        cache()->forget($key . ':timer');
    }
    
    /**
     * Log successful login
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @param \App\Models\Company $company
     * @return void
     */
    protected function logSuccessfulLogin($request, $user, $company)
    {
        try {
            AuditLog::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'event_type' => AuditLog::EVENT_LOGIN,
                'action' => 'login_success',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'severity' => AuditLog::SEVERITY_INFO,
                'metadata' => [
                    'email' => $user->email,
                    'company_name' => $company->name,
                    '2fa_used' => !empty($user->two_factor_secret),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log successful login: ' . $e->getMessage());
        }
    }
    
    /**
     * Apply company-specific settings
     *
     * @param \App\Models\User $user
     * @param \App\Models\Company $company
     * @return void
     */
    protected function applyCompanySettings($user, $company)
    {
        $settings = Setting::where('company_id', $company->id)->first();
        
        if ($settings) {
            // Configure session based on settings
            if ($settings->session_lifetime) {
                config(['session.lifetime' => $settings->session_lifetime]);
            }
            
            // Store idle timeout in session for client-side handling
            if ($settings->idle_timeout) {
                session(['idle_timeout' => $settings->idle_timeout * 60 * 1000]);
            }
        }
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
     * Get the post-login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/dashboard';
    }
}