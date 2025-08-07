<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

/**
 * ForgotPasswordController
 * 
 * Handles sending password reset emails to users.
 * Includes rate limiting and security logging.
 */
class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Display the form to request a password reset link.
     */
    public function showLinkRequestForm(): View
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $this->validateEmail($request);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(
            $this->credentials($request)
        );

        // Log password reset request
        $this->logPasswordResetRequest($request, $response);

        return $response == Password::RESET_LINK_SENT
                    ? $this->sendResetLinkResponse($request, $response)
                    : $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Validate the email for the given request.
     */
    protected function validateEmail(Request $request): void
    {
        $request->validate([
            'email' => 'required|email',
        ]);
    }

    /**
     * Get the needed authentication credentials from the request.
     */
    protected function credentials(Request $request): array
    {
        return $request->only('email');
    }

    /**
     * Get the response for a successful password reset link.
     */
    protected function sendResetLinkResponse(Request $request, string $response): RedirectResponse
    {
        return back()->with('status', trans($response));
    }

    /**
     * Get the response for a failed password reset link.
     */
    protected function sendResetLinkFailedResponse(Request $request, string $response): RedirectResponse
    {
        return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => trans($response)]);
    }

    /**
     * Get the broker to be used during password reset.
     */
    public function broker(): \Illuminate\Contracts\Auth\PasswordBroker
    {
        return Password::broker();
    }

    /**
     * Log password reset request.
     */
    protected function logPasswordResetRequest(Request $request, string $response): void
    {
        $status = $response == Password::RESET_LINK_SENT ? 'sent' : 'failed';
        
        \Log::info('Password reset link requested', [
            'email' => $request->input('email'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
            'response' => $response,
        ]);
    }
}