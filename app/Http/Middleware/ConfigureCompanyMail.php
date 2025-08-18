<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\DynamicMailConfigService;

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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only configure mail for authenticated users
        if (auth()->check()) {
            $this->mailConfigService->configureMailForCompany();
        }

        return $next($request);
    }
}