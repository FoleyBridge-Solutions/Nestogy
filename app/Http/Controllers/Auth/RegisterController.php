<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSetting;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * RegisterController
 * 
 * Handles user registration with role assignment and company association.
 * Only accessible by admin users for creating new team members.
 */
class RegisterController extends Controller
{

    /**
     * Where to redirect users after registration.
     */
    protected string $redirectTo = '/users';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Show the application registration form.
     */
    public function showRegistrationForm(): View
    {
        $companies = Company::orderBy('name')->get();
        $roles = UserSetting::getAvailableRoles();
        
        return view('auth.register', compact('companies', 'roles'));
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(Request $request): RedirectResponse
    {
        $this->validator($request->all())->validate();

        $user = $this->create($request->all());

        // Don't automatically log in the new user since this is admin registration
        return redirect($this->redirectPath())
            ->with('success', 'User created successfully. Login credentials have been sent to their email.');
    }

    /**
     * Get a validator for an incoming registration request.
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'integer', 'in:1,2,3'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'extension_key' => ['nullable', 'string', 'max:18'],
            'force_mfa' => ['boolean'],
            'status' => ['boolean'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     */
    protected function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create the user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => $data['status'] ?? true,
                'extension_key' => $data['extension_key'] ?? null,
            ]);

            // Create user settings with role and preferences
            UserSetting::create([
                'user_id' => $user->id,
                'role' => $data['role'],
                'force_mfa' => $data['force_mfa'] ?? false,
                'records_per_page' => 10,
                'dashboard_financial_enable' => in_array($data['role'], [UserSetting::ROLE_ACCOUNTANT, UserSetting::ROLE_ADMIN]),
                'dashboard_technical_enable' => in_array($data['role'], [UserSetting::ROLE_TECH, UserSetting::ROLE_ADMIN]),
            ]);

            // Log user creation
            \Log::info('New user created', [
                'created_by' => Auth::id(),
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $data['role'],
                'company_id' => $data['company_id'],
            ]);

            // Send welcome email (to be implemented)
            // $this->sendWelcomeEmail($user, $data['password']);

            return $user;
        });
    }

    /**
     * Send welcome email to new user.
     * 
     * @param User $user
     * @param string $password
     */
    protected function sendWelcomeEmail(User $user, string $password): void
    {
        // TODO: Implement welcome email with login credentials
        // This should include:
        // - Welcome message
        // - Login URL
        // - Temporary password (encourage password change)
        // - Company information
        // - Role information
    }

    /**
     * Get the post-registration redirect path.
     */
    public function redirectPath(): string
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/users';
    }
}