<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyHierarchy;

/**
 * UpdateSubsidiaryRequest
 * 
 * Validates data for updating an existing subsidiary company.
 */
class UpdateSubsidiaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        $subsidiary = $this->route('subsidiary');
        
        // User must be authenticated and have subsidiary management permissions
        if (!$user || !$user->settings?->canManageSubsidiaries()) {
            return false;
        }

        // Subsidiary must be a descendant of user's company
        return CompanyHierarchy::isDescendant($subsidiary->id, $user->company_id);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $subsidiary = $this->route('subsidiary');
        
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
            
            // Status management
            'is_active' => 'boolean',
            'suspension_reason' => 'nullable|string|max:500',
            
            // Currency and localization
            'currency' => 'nullable|string|in:USD,EUR,GBP,CAD,AUD,JPY',
            'locale' => 'nullable|string|max:10',
            
            // Subsidiary settings
            'subsidiary_settings' => 'array',
            'subsidiary_settings.department' => 'nullable|string|max:100',
            'subsidiary_settings.cost_center' => 'nullable|string|max:100',
            'subsidiary_settings.budget_limit' => 'nullable|numeric|min:0',
            'subsidiary_settings.auto_approval_limit' => 'nullable|numeric|min:0',
            
            // Hierarchy management
            'move_to_parent' => 'nullable|exists:companies,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'company name',
            'max_subsidiary_depth' => 'maximum subsidiary depth',
            'suspension_reason' => 'suspension reason',
            'subsidiary_settings.budget_limit' => 'budget limit',
            'subsidiary_settings.auto_approval_limit' => 'auto-approval limit',
            'move_to_parent' => 'new parent company',
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
            'max_subsidiary_depth.max' => 'Maximum subsidiary depth cannot exceed 10 levels.',
            'move_to_parent.exists' => 'The selected parent company does not exist.',
            'suspension_reason.required_if' => 'A suspension reason is required when deactivating a subsidiary.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $subsidiary = $this->route('subsidiary');
        
        // Merge current values as defaults
        $this->merge([
            'currency' => $this->currency ?? $subsidiary->currency,
            'country' => $this->country ?? $subsidiary->country,
            'can_create_subsidiaries' => $this->boolean('can_create_subsidiaries', 
                $subsidiary->can_create_subsidiaries),
            'is_active' => $this->boolean('is_active', $subsidiary->is_active),
        ]);

        // Handle suspension logic
        if ($this->boolean('is_active') === false && !$this->suspension_reason) {
            $this->merge(['suspension_reason' => 'Administrative action']);
        }

        // Merge subsidiary settings
        $currentSettings = $subsidiary->subsidiary_settings ?? [];
        $newSettings = $this->subsidiary_settings ?? [];
        $this->merge([
            'subsidiary_settings' => array_merge($currentSettings, $newSettings)
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $subsidiary = $this->route('subsidiary');
            $userCompany = Auth::user()->company;
            
            // Validate hierarchy move
            if ($this->move_to_parent) {
                $newParentId = (int) $this->move_to_parent;
                
                // Cannot move to itself
                if ($newParentId === $subsidiary->id) {
                    $validator->errors()->add('move_to_parent',
                        'Cannot move company to itself.');
                }
                
                // Cannot move to a descendant (would create circular reference)
                if (CompanyHierarchy::isDescendant($newParentId, $subsidiary->id)) {
                    $validator->errors()->add('move_to_parent',
                        'Cannot move company to one of its descendants.');
                }
                
                // New parent must be accessible by current user
                if (!CompanyHierarchy::areRelated($userCompany->id, $newParentId) &&
                    $newParentId !== $userCompany->id) {
                    $validator->errors()->add('move_to_parent',
                        'You do not have access to the selected parent company.');
                }
            }

            // Validate max subsidiary depth changes
            if ($this->max_subsidiary_depth !== null) {
                $currentDepth = $subsidiary->organizational_level;
                $requestedDepth = (int) $this->max_subsidiary_depth;
                
                // Cannot reduce depth below current level
                if ($requestedDepth < $currentDepth) {
                    $validator->errors()->add('max_subsidiary_depth',
                        'Cannot set maximum depth below current organizational level (' . 
                        $currentDepth . ').');
                }
                
                // Check if subsidiary has children that would exceed new depth
                $hasDeepChildren = CompanyHierarchy::getDescendants($subsidiary->id)
                    ->where('depth', '>', ($requestedDepth - $currentDepth))
                    ->exists();
                    
                if ($hasDeepChildren) {
                    $validator->errors()->add('max_subsidiary_depth',
                        'Cannot reduce maximum depth: existing subsidiaries exceed the new limit.');
                }
            }

            // Validate billing type changes
            if ($this->billing_type === 'shared' && 
                $this->currency !== $userCompany->currency) {
                $validator->errors()->add('currency',
                    'Currency must match parent company when using shared billing.');
            }

            // Validate suspension
            if ($this->boolean('is_active') === false) {
                // Check if subsidiary has active children
                $hasActiveChildren = $subsidiary->childCompanies()
                    ->where('is_active', true)
                    ->exists();
                    
                if ($hasActiveChildren) {
                    $validator->errors()->add('is_active',
                        'Cannot deactivate company with active subsidiaries.');
                }

                // Require suspension reason
                if (empty($this->suspension_reason)) {
                    $validator->errors()->add('suspension_reason',
                        'A suspension reason is required when deactivating a subsidiary.');
                }
            }

            // Validate access level downgrades
            $currentLevel = $subsidiary->access_level;
            $newLevel = $this->access_level;
            
            if ($this->isAccessLevelDowngrade($currentLevel, $newLevel)) {
                // Check if subsidiary has permissions that would be invalid
                $hasHighLevelPermissions = $subsidiary->grantedPermissions()
                    ->whereIn('permission_type', ['manage', 'delete'])
                    ->active()
                    ->exists();
                    
                if ($hasHighLevelPermissions && $newLevel === 'read_only') {
                    $validator->errors()->add('access_level',
                        'Cannot downgrade to read-only: subsidiary has active management permissions.');
                }
            }
        });
    }

    /**
     * Check if the access level change is a downgrade.
     */
    protected function isAccessLevelDowngrade(string $current, string $new): bool
    {
        $levels = ['read_only' => 1, 'limited' => 2, 'full' => 3];
        return $levels[$new] < $levels[$current];
    }
}