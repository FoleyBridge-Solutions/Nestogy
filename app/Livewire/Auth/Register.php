<?php

namespace App\Livewire\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $extension_key = '';

    public ?int $company_id = null;

    public ?string $role = '';

    public bool $status = true;

    public bool $force_mfa = false;

    public string $password = '';

    public string $password_confirmation = '';

    public $companies;

    public $roles;

    public function mount()
    {
        $this->companies = Company::all();
        $this->roles = Role::pluck('name', 'id');
    }

    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'extension_key' => ['nullable', 'string', 'max:18'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'status' => ['boolean'],
            'force_mfa' => ['boolean'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }

    public function register()
    {
        $validatedData = $this->validate();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'extension_key' => $validatedData['extension_key'],
            'company_id' => $validatedData['company_id'],
            'status' => $validatedData['status'] ? 'active' : 'inactive',
            'force_mfa' => $validatedData['force_mfa'],
        ]);

        $user->assignRole($validatedData['role']);

        session()->flash('success', 'User created successfully.');

        return redirect()->route('users.index');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
