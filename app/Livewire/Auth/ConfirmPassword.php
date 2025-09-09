<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ConfirmPassword extends Component
{
    public string $password = '';

    protected function rules()
    {
        return [
            'password' => ['required', 'string'],
        ];
    }

    public function confirmPassword()
    {
        $this->validate();

        if (!Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.confirm-password');
    }
}
