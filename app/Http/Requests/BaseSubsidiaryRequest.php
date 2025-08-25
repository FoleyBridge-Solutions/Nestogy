<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * BaseSubsidiaryRequest
 * 
 * Base class for subsidiary-related form requests with common validation rules.
 */
abstract class BaseSubsidiaryRequest extends FormRequest
{
    /**
     * Get common validation rules for subsidiary operations.
     */
    protected function getCommonRules(): array
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
     * Get common custom attributes for validator errors.
     */
    protected function getCommonAttributes(): array
    {
        return [
            'name' => 'company name',
            'max_subsidiary_depth' => 'maximum subsidiary depth',
            'subsidiary_settings.budget_limit' => 'budget limit',
            'subsidiary_settings.auto_approval_limit' => 'auto-approval limit',
        ];
    }

    /**
     * Get common error messages.
     */
    protected function getCommonMessages(): array
    {
        return [
            'name.required' => 'The subsidiary name is required.',
            'access_level.required' => 'Please specify the access level for this subsidiary.',
            'billing_type.required' => 'Please specify how this subsidiary will be billed.',
            'max_subsidiary_depth.max' => 'Maximum subsidiary depth cannot exceed 10 levels.',
        ];
    }

    /**
     * Prepare common subsidiary settings with defaults.
     */
    protected function prepareSubsidiarySettings(?array $existingSettings = null): void
    {
        $subsidiarySettings = $this->subsidiary_settings ?? [];
        $baseSettings = [
            'department' => null,
            'cost_center' => null,
            'budget_limit' => null,
            'auto_approval_limit' => null,
        ];

        if ($existingSettings) {
            $baseSettings = array_merge($baseSettings, $existingSettings);
        }

        $this->merge([
            'subsidiary_settings' => array_merge($baseSettings, $subsidiarySettings)
        ]);
    }

    /**
     * Validate currency compatibility with billing type.
     */
    protected function validateCurrencyBilling($validator, ?string $parentCurrency = null): void
    {
        $parentCurrency = $parentCurrency ?? Auth::user()->company->currency;
        
        if ($this->billing_type === 'shared' && 
            $this->currency !== $parentCurrency) {
            $validator->errors()->add('currency',
                'Currency must match parent company when using shared billing.');
        }
    }

    /**
     * Check if user can manage subsidiaries.
     */
    protected function canManageSubsidiaries(): bool
    {
        $user = Auth::user();
        return $user && $user->settings?->canManageSubsidiaries();
    }
}