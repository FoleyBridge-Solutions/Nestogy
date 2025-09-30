<?php

namespace App\Domains\Client\Controllers\Portal;

use App\Domains\Client\Services\PortalInvitationService;
use App\Domains\Security\Services\PortalAuthService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Portal Invitation Controller
 *
 * Handles the public-facing invitation acceptance flow
 * for client portal access.
 */
class PortalInvitationController extends Controller
{
    protected PortalInvitationService $invitationService;

    protected PortalAuthService $authService;

    public function __construct(
        PortalInvitationService $invitationService,
        PortalAuthService $authService
    ) {
        $this->invitationService = $invitationService;
        $this->authService = $authService;
    }

    /**
     * Show the invitation acceptance form
     */
    public function show($token)
    {
        // Validate token exists and is not expired
        $contact = $this->invitationService->validateToken($token);

        if (! $contact) {
            return view('portal.invitation.expired');
        }

        return view('portal.invitation.accept', [
            'token' => $token,
            'contact' => $contact,
            'expiresAt' => $contact->invitation_expires_at,
        ]);
    }

    /**
     * Accept the invitation and set password
     */
    public function accept(Request $request, $token)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ]);

        // Process invitation acceptance
        $result = $this->invitationService->acceptInvitation($token, $request->password);

        if (! $result['success']) {
            throw ValidationException::withMessages([
                'password' => [$result['message']],
            ]);
        }

        $contact = $result['data']['contact'];

        // Log activity
        activity()
            ->performedOn($contact)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Portal invitation accepted and password set');

        // Auto-login if configured
        if ($result['data']['auto_login']) {
            // Use the existing portal auth service for login
            Auth::guard('portal')->login($contact);
            $request->session()->regenerate();

            return redirect()->route('client.dashboard')
                ->with('success', 'Welcome! Your account has been successfully set up.');
        }

        return redirect()->route('client.login')
            ->with('success', 'Your password has been set successfully. Please log in to continue.');
    }

    /**
     * Show expired invitation page
     */
    public function expired()
    {
        return view('portal.invitation.expired');
    }
}
