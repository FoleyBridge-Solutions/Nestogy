<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * ResetPasswordController
 * 
 * Handles password reset functionality with security logging
 * and automatic login after successful reset.
 */
class ResetPasswordController extends Controller
{

    /**
     * Where to redirect users after resetting their password.
     */
    protected string $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Display the password reset view for the given token.
     */
    public function showResetForm(Request $request, ?string $token = null): View
    {
        return view('auth.passwords.reset')->with([
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate($this->rules(), $this->validationErrorMessages());

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        // Log password reset attempt
        $this->logPasswordReset($request, $response);

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
                    ? $this->sendResetResponse($request, $response)
                    : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Get the password reset validation rules.
     */
    protected function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    }

    /**
     * Get the password reset validation error messages.
     */
    protected function validationErrorMessages(): array
    {
        return [];
    }

    /**
     * Get the password reset credentials from the request.
     */
    protected function credentials(Request $request): array
    {
        return $request->only(
            'email', 'password', 'password_confirmation', 'token'
        );
    }

    /**
     * Reset the given user's password.
     */
    protected function resetPassword(User $user, string $password): void
    {
        $this->setUserPassword($user, $password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        event(new \Illuminate\Auth\Events\PasswordReset($user));

        $this->guard()->login($user);
    }

    /**
     * Set the user's password.
     */
    protected function setUserPassword(User $user, string $password): void
    {
        $user->password = Hash::make($password);
    }

    /**
     * Get the response for a successful password reset.
     */
    protected function sendResetResponse(Request $request, string $response): RedirectResponse
    {
        return redirect($this->redirectPath())
                            ->with('status', trans($response));
    }

    /**
     * Get the response for a failed password reset.
     */
    protected function sendResetFailedResponse(Request $request, string $response): RedirectResponse
    {
        return redirect()->back()
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
     * Get the guard to be used during password reset.
     */
    protected function guard(): \Illuminate\Contracts\Auth\StatefulGuard
    {
        return Auth::guard();
    }

    /**
     * Log password reset attempt.
     */
    protected function logPasswordReset(Request $request, string $response): void
    {
        $status = $response == Password::PASSWORD_RESET ? 'success' : 'failed';
        
        \Log::info('Password reset attempted', [
            'email' => $request->input('email'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
            'response' => $response,
        ]);
    }
}