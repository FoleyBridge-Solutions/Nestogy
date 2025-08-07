<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Verified;

/**
 * VerificationController
 * 
 * Handles email verification for new user accounts.
 * Includes resending verification emails and logging verification attempts.
 */
class VerificationController extends Controller
{

    /**
     * Where to redirect users after verification.
     */
    protected string $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Show the email verification notice.
     */
    public function show(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
                        ? redirect($this->redirectPath())
                        : view('auth.verify');
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(Request $request): RedirectResponse
    {
        if (! hash_equals((string) $request->route('id'), (string) $request->user()->getKey())) {
            throw new \Illuminate\Auth\Access\AuthorizationException;
        }

        if (! hash_equals((string) $request->route('hash'), sha1($request->user()->getEmailForVerification()))) {
            throw new \Illuminate\Auth\Access\AuthorizationException;
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath())->with('verified', true);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // Log email verification
        $this->logEmailVerification($request->user(), $request);

        return redirect($this->redirectPath())->with('verified', true);
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }

        $request->user()->sendEmailVerificationNotification();

        // Log verification email resent
        $this->logVerificationEmailResent($request->user(), $request);

        return back()->with('resent', true);
    }

    /**
     * Get the post-verification redirect path.
     */
    public function redirectPath(): string
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/dashboard';
    }

    /**
     * Log successful email verification.
     */
    protected function logEmailVerification(User $user, Request $request): void
    {
        \Log::info('Email verified successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Log verification email resent.
     */
    protected function logVerificationEmailResent(User $user, Request $request): void
    {
        \Log::info('Verification email resent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}