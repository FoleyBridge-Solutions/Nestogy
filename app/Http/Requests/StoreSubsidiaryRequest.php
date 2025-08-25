<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * StoreSubsidiaryRequest
 * 
 * Validates data for creating a new subsidiary company.
 */
class StoreSubsidiaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        
        // User must be authenticated and have subsidiary management permissions
        if (!$user || !$user->settings?->canManageSubsidiaries()) {
            return false;
        }

        // Company must allow subsidiary creation
        return $user->company->canCreateSubsidiaries() && 
               !$user->company->hasReachedMaxSubsidiaryDepth();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic company information
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            
            // Address information
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            
            // Subsidiary-specific settings
            'access_level' => 'required|in:full,limited,read_only',
            'billing_type' => 'required|in:independent,parent_billed,shared',
            'can_create_subsidiaries' => 'boolean',
            'max_subsidiary_depth' => 'integer|min:0|max:10',
            
            // Admin user information (optional - create admin for subsidiary)
            'create_admin' => 'boolean',
            'admin_name' => 'required_if:create_admin,true|string|max:255',
            'admin_email' => 'required_if:create_admin,true|email|max:255|unique:users,email',
            'admin_password' => 'required_if:create_admin,true|string|min:8|confirmed',
            
            // Initial permissions
            'inherit_permissions' => 'boolean',
            'initial_permissions' => 'array',
            'initial_permissions.*' => 'string',
            
            // Currency and localization
            'currency' => 'nullable|string|in:USD,EUR,GBP,CAD,AUD,JPY',
            'locale' => 'nullable|string|max:10',
            
            // Subsidiary settings
            'subsidiary_settings' => 'array',
            'subsidiary_settings.department' => 'nullable|string|max:100',
            'subsidiary_settings.cost_center' => 'nullable|string|max:100',
            'subsidiary_settings.budget_limit' => 'nullable|numeric|min:0',
            'subsidiary_settings.auto_approval_limit' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'company name',
            'admin_name' => 'administrator name',
            'admin_email' => 'administrator email',
            'admin_password' => 'administrator password',
            'max_subsidiary_depth' => 'maximum subsidiary depth',
            'subsidiary_settings.budget_limit' => 'budget limit',
            'subsidiary_settings.auto_approval_limit' => 'auto-approval limit',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The subsidiary name is required.',
            'access_level.required' => 'Please specify the access level for this subsidiary.',
            'billing_type.required' => 'Please specify how this subsidiary will be billed.',
            'admin_name.required_if' => 'Administrator name is required when creating an admin user.',
            'admin_email.required_if' => 'Administrator email is required when creating an admin user.',
            'admin_email.unique' => 'An administrator with this email address already exists.',
            'admin_password.required_if' => 'Administrator password is required when creating an admin user.',
            'admin_password.confirmed' => 'Administrator password confirmation does not match.',
            'max_subsidiary_depth.max' => 'Maximum subsidiary depth cannot exceed 10 levels.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults
        $this->merge([
            'currency' => $this->currency ?? Auth::user()->company->currency ?? 'USD',
            'country' => $this->country ?? Auth::user()->company->country ?? 'United States',
            'can_create_subsidiaries' => $this->boolean('can_create_subsidiaries', false),
            'inherit_permissions' => $this->boolean('inherit_permissions', true),
            'create_admin' => $this->boolean('create_admin', false),
            'max_subsidiary_depth' => $this->max_subsidiary_depth ?? 
                (Auth::user()->company->max_subsidiary_depth - 1),
        ]);

        // Merge subsidiary settings with defaults
        $subsidiarySettings = $this->subsidiary_settings ?? [];
        $this->merge([
            'subsidiary_settings' => array_merge([
                'department' => null,
                'cost_center' => null,
                'budget_limit' => null,
                'auto_approval_limit' => null,
            ], $subsidiarySettings)
        ]);
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
                    'Maximum subsidiary depth must be less than parent company depth (' . 
                    $parentCompany->max_subsidiary_depth . ').');
            }

            // Validate currency matches parent if billing is shared
            if ($this->billing_type === 'shared' && 
                $this->currency !== $parentCompany->currency) {
                $validator->errors()->add('currency',
                    'Currency must match parent company when using shared billing.');
            }
        });
    }
}