<?php

namespace App\Http\Middleware;

use App\Services\NavigationService;
use Closure;
use Illuminate\Http\Request;

class RequireSelectedClient
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if a client is selected in the session
        $selectedClient = NavigationService::getSelectedClient();

        if (!$selectedClient) {
            // Store the intended URL to return to after client selection
            session(['client_selection_return_url' => $request->fullUrl()]);

            // Add a flash message to inform the user
            session()->flash('info', 'Please select a client to continue.');

            // Redirect to the client selection page
            return redirect()->route('clients.index');
        }

        // Client is selected, proceed with the request
        return $next($request);
    }
}