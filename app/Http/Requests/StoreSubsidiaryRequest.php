<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;

/**
 * StoreSubsidiaryRequest
 *
 * Validates data for creating a new subsidiary company.
 */
class StoreSubsidiaryRequest extends BaseSubsidiaryRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->canManageSubsidiaries()) {
            return false;
        }

        $user = Auth::user();

        // Company must allow subsidiary creation
        return $user->company->canCreateSubsidiaries() &&
               ! $user->company->hasReachedMaxSubsidiaryDepth();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge($this->getCommonRules(), [
            // Admin user information (optional - create admin for subsidiary)
            'create_admin' => 'boolean',
            'admin_name' => 'required_if:create_admin,true|string|max:255',
            'admin_email' => 'required_if:create_admin,true|email|max:255|unique:users,email',
            'admin_password' => 'required_if:create_admin,true|string|min:8|confirmed',

            // Initial permissions
            'inherit_permissions' => 'boolean',
            'initial_permissions' => 'array',
            'initial_permissions.*' => 'string',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge($this->getCommonAttributes(), [
            'admin_name' => 'administrator name',
            'admin_email' => 'administrator email',
            'admin_password' => 'administrator password',
        ]);
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return array_merge($this->getCommonMessages(), [
            'admin_name.required_if' => 'Administrator name is required when creating an admin user.',
            'admin_email.required_if' => 'Administrator email is required when creating an admin user.',
            'admin_email.unique' => 'An administrator with this email address already exists.',
            'admin_password.required_if' => 'Administrator password is required when creating an admin user.',
            'admin_password.confirmed' => 'Administrator password confirmation does not match.',
        ]);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = Auth::user();

        // Set defaults
        $this->merge([
            'currency' => $this->currency ?? $user->company->currency ?? 'USD',
            'country' => $this->country ?? $user->company->country ?? 'United States',
            'can_create_subsidiaries' => $this->boolean('can_create_subsidiaries', false),
            'inherit_permissions' => $this->boolean('inherit_permissions', true),
            'create_admin' => $this->boolean('create_admin', false),
            'max_subsidiary_depth' => $this->max_subsidiary_depth ??
                ($user->company->max_subsidiary_depth - 1),
        ]);

        // Prepare subsidiary settings with defaults
        $this->prepareSubsidiarySettings();
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $parentCompany = Auth::user()->company;

            // Check if parent company has reached its subsidiary limit
            if ($parentCompany->hasReachedMaxSubsidiaryDepth()) {
                $validator->errors()->add('general',
                    'Cannot create subsidiary: Maximum organizational depth reached.');
            }

            // Validate billing parent if billing_type is parent_billed
            if ($this->billing_type === 'parent_billed') {
                // Billing parent should be the current company by default
                $this->merge(['billing_parent_id' => $parentCompany->id]);
            }

            // Validate max subsidiary depth
            if ($this->max_subsidiary_depth >= $parentCompany->max_subsidiary_depth) {
                $validator->errors()->add('max_subsidiary_depth',
                    'Maximum subsidiary depth must be less than parent company depth ('.
                    $parentCompany->max_subsidiary_depth.').');
            }

            // Validate currency compatibility with billing type
            $this->validateCurrencyBilling($validator, $parentCompany->currency);
        });
    }
}
