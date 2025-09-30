<?php

namespace App\Livewire\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Livewire\Component;

class TwoFactorChallenge extends Component
{
    public string $code = '';

    public string $recovery_code = '';

    public bool $showingRecoveryForm = false;

    public function challenge()
    {
        $user = session('login.user');

        if (! $user) {
            return redirect()->route('login');
        }

        $data = $this->validate([
            'code' => ['required_without:recovery_code', 'nullable', 'string'],
            'recovery_code' => ['required_without:code', 'nullable', 'string'],
        ]);

        $valid = $this->showingRecoveryForm
            ? $this->resolveRecoveryCode($user, $data['recovery_code'])
            : $this->resolveCode($user, $data['code']);

        if (! $valid) {
            $this->addError($this->showingRecoveryForm ? 'recovery_code' : 'code', __('The provided two factor authentication code was invalid.'));

            return;
        }

        Auth::login($user, session()->pull('login.remember'));

        session()->forget('login.user');

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    protected function resolveCode($user, $code)
    {
        return app(RedirectIfTwoFactorAuthenticatable::class)->checkCode($user, $code);
    }

    protected function resolveRecoveryCode($user, $recoveryCode)
    {
        foreach (json_decode(decrypt($user->two_factor_recovery_codes), true) as $code) {
            if (hash_equals($code['code'], $recoveryCode)) {
                $user->replaceRecoveryCode($recoveryCode);

                return true;
            }
        }

        return false;
    }

    public function toggleRecoveryForm()
    {
        $this->showingRecoveryForm = ! $this->showingRecoveryForm;
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge');
    }
}
