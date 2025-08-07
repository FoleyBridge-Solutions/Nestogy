<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Company;

/**
 * CompanyMiddleware
 * 
 * Handles multi-tenant company scoping for authenticated users.
 * Ensures users can only access data within their selected company context.
 */
class CompanyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $companyId = Session::get('company_id');

        // If no company is set in session, use the user's assigned company
        if (!$companyId) {
            if ($user->company_id) {
                $companyId = $user->company_id;
                Session::put('company_id', $companyId);
                \Log::info('CompanyMiddleware: Auto-set company_id from user: ' . $companyId);
            } else {
                \Log::error('CompanyMiddleware: User has no company assigned');
                return redirect()->route('login')
                    ->with('error', 'Your account is not assigned to a company. Please contact your administrator.');
            }
        }

        // Verify company exists and user has access to it
        $company = Company::find($companyId);
        if (!$company) {
            \Log::warning('CompanyMiddleware: Company not found for ID: ' . $companyId);
            Session::forget('company_id');
            return redirect()->route('login')
                ->with('error', 'Your assigned company is no longer available. Please contact your administrator.');
        }

        // Verify user belongs to this company
        if ($user->company_id !== $company->id) {
            \Log::warning('CompanyMiddleware: User ' . $user->id . ' attempted to access company ' . $company->id . ' but belongs to company ' . $user->company_id);
            Session::forget('company_id');
            return redirect()->route('login')
                ->with('error', 'Access denied. You can only access your assigned company.');
        }

        // Store company information in request for easy access
        $request->attributes->set('company', $company);
        $request->attributes->set('company_id', $companyId);

        // Set company context in view composer or global variable
        view()->share('currentCompany', $company);
        
        // Store in config for database queries
        config(['app.current_company_id' => $companyId]);

        return $next($request);
    }
}