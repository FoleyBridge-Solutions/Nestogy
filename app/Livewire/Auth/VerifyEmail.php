<?php

namespace App\Livewire\Auth;

use Illuminate\Http\Request;
use Livewire\Component;

class VerifyEmail extends Component
{
    public ?string $status = null;

    public function sendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        $request->user()->sendEmailVerificationNotification();

        $this->status = 'A new verification link has been sent to the email address you provided during registration.';
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
