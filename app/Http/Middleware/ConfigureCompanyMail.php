<?php

namespace App\Http\Middleware;

use App\Domains\Email\Services\DynamicMailConfigService;
use Closure;
use Illuminate\Http\Request;

class ConfigureCompanyMail
{
    protected DynamicMailConfigService $mailConfigService;

    public function __construct(DynamicMailConfigService $mailConfigService)
    {
        $this->mailConfigService = $mailConfigService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Configure mail for authenticated users or use platform company fallback
        if (auth()->check()) {
            $this->mailConfigService->configureMailForCompany();
        } else {
            // For unauthenticated requests (like email verification), use platform company
            $this->mailConfigService->configureMailForPlatformCompany();
        }

        return $next($request);
    }
}
