<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in controller via policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = auth()->user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|integer|in:1,2,3,4',
            'status' => 'boolean',
            'send_welcome_email' => 'boolean',
        ];

        // If user can access cross-tenant (super admin), allow company_id
        if ($user->canAccessCrossTenant()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'User name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'User role must be selected.',
            'role.in' => 'Invalid user role selected.',
            'company_id.required' => 'Company must be selected.',
            'company_id.exists' => 'Selected company does not exist.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'user name',
            'email' => 'email address',
            'phone' => 'phone number',
            'role' => 'user role',
            'company_id' => 'company',
            'send_welcome_email' => 'welcome email option',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Default status to active if not provided
        if (! $this->has('status')) {
            $this->merge(['status' => 1]);
        }

        // Default send_welcome_email to false if not provided
        if (! $this->has('send_welcome_email')) {
            $this->merge(['send_welcome_email' => false]);
        }

        // If not super admin, set company_id to current user's company
        $user = auth()->user();
        if (! $user->canAccessCrossTenant()) {
            $this->merge(['company_id' => $user->company_id]);
        }
    }
}
