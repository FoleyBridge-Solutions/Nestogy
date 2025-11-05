<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Helpers\ConfigHelper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Configure Fortify to use existing views (no companies for security)
        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.passwords.email');
        });

        Fortify::resetPasswordView(function (Request $request) {
            return view('auth.passwords.reset', ['request' => $request]);
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verify');
        });

        // Configure rate limiting with database settings
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            // Try to get company ID from session or authenticated user
            $companyId = session('company_id') ?? (Auth::check() ? Auth::user()->company_id : null);
            
            // Get max login attempts from database settings (fallback to 5)
            $maxAttempts = ConfigHelper::securitySetting($companyId, 'authentication', 'max_login_attempts', 5);
            
            // Get lockout duration in minutes from database settings (fallback to 15)
            $lockoutMinutes = ConfigHelper::securitySetting($companyId, 'authentication', 'lockout_duration', 15);

            return Limit::perMinutes($lockoutMinutes, $maxAttempts)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            // Use same rate limiting as login
            $companyId = session('company_id') ?? (Auth::check() ? Auth::user()->company_id : null);
            $maxAttempts = ConfigHelper::securitySetting($companyId, 'authentication', 'max_login_attempts', 5);
            
            return Limit::perMinute($maxAttempts)->by($request->session()->get('login.id'));
        });
    }
}
