<?php

namespace App\Http\Requests;

use App\Domains\Company\Models\CompanyHierarchy;
use Illuminate\Support\Facades\Auth;

/**
 * UpdateSubsidiaryRequest
 *
 * Validates data for updating an existing subsidiary company.
 */
class UpdateSubsidiaryRequest extends BaseSubsidiaryRequest
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
        $subsidiary = $this->route('subsidiary');

        // Subsidiary must be a descendant of user's company
        return CompanyHierarchy::isDescendant($subsidiary->id, $user->company_id);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge($this->getCommonRules(), [
            // Status management
            'is_active' => 'boolean',
            'suspension_reason' => 'nullable|string|max:500',

            // Hierarchy management
            'move_to_parent' => 'nullable|exists:companies,id',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge($this->getCommonAttributes(), [
            'suspension_reason' => 'suspension reason',
            'move_to_parent' => 'new parent company',
        ]);
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return array_merge($this->getCommonMessages(), [
            'move_to_parent.exists' => 'The selected parent company does not exist.',
            'suspension_reason.required_if' => 'A suspension reason is required when deactivating a subsidiary.',
        ]);
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
        if ($this->boolean('is_active') === false && ! $this->suspension_reason) {
            $this->merge(['suspension_reason' => 'Administrative action']);
        }

        // Prepare subsidiary settings with current settings as base
        $this->prepareSubsidiarySettings($subsidiary->subsidiary_settings ?? []);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $subsidiary = $this->route('subsidiary');
            $userCompany = Auth::user()->company;

            $this->validateHierarchyMove($validator, $subsidiary, $userCompany);
            $this->validateMaxSubsidiaryDepth($validator, $subsidiary);
            $this->validateCurrencyBilling($validator, $userCompany->currency);
            $this->validateSuspension($validator, $subsidiary);
            $this->validateAccessLevelDowngrade($validator, $subsidiary);
        });
    }

    /**
     * Validate hierarchy move operation.
     */
    protected function validateHierarchyMove($validator, $subsidiary, $userCompany): void
    {
        if (! $this->move_to_parent) {
            return;
        }

        $newParentId = (int) $this->move_to_parent;

        if ($newParentId === $subsidiary->id) {
            $validator->errors()->add('move_to_parent',
                'Cannot move company to itself.');
            return;
        }

        if (CompanyHierarchy::isDescendant($newParentId, $subsidiary->id)) {
            $validator->errors()->add('move_to_parent',
                'Cannot move company to one of its descendants.');
            return;
        }

        if (! CompanyHierarchy::areRelated($userCompany->id, $newParentId) &&
            $newParentId !== $userCompany->id) {
            $validator->errors()->add('move_to_parent',
                'You do not have access to the selected parent company.');
        }
    }

    /**
     * Validate max subsidiary depth changes.
     */
    protected function validateMaxSubsidiaryDepth($validator, $subsidiary): void
    {
        if ($this->max_subsidiary_depth === null) {
            return;
        }

        $currentDepth = $subsidiary->organizational_level;
        $requestedDepth = (int) $this->max_subsidiary_depth;

        if ($requestedDepth < $currentDepth) {
            $validator->errors()->add('max_subsidiary_depth',
                'Cannot set maximum depth below current organizational level ('.
                $currentDepth.').');
            return;
        }

        $hasDeepChildren = CompanyHierarchy::getDescendants($subsidiary->id)
            ->where('depth', '>', ($requestedDepth - $currentDepth))
            ->exists();

        if ($hasDeepChildren) {
            $validator->errors()->add('max_subsidiary_depth',
                'Cannot reduce maximum depth: existing subsidiaries exceed the new limit.');
        }
    }

    /**
     * Validate suspension operation.
     */
    protected function validateSuspension($validator, $subsidiary): void
    {
        if ($this->boolean('is_active') !== false) {
            return;
        }

        $hasActiveChildren = $subsidiary->childCompanies()
            ->where('is_active', true)
            ->exists();

        if ($hasActiveChildren) {
            $validator->errors()->add('is_active',
                'Cannot deactivate company with active subsidiaries.');
        }

        if (empty($this->suspension_reason)) {
            $validator->errors()->add('suspension_reason',
                'A suspension reason is required when deactivating a subsidiary.');
        }
    }

    /**
     * Validate access level downgrade.
     */
    protected function validateAccessLevelDowngrade($validator, $subsidiary): void
    {
        $currentLevel = $subsidiary->access_level;
        $newLevel = $this->access_level;

        if (! $this->isAccessLevelDowngrade($currentLevel, $newLevel)) {
            return;
        }

        $hasHighLevelPermissions = $subsidiary->grantedPermissions()
            ->whereIn('permission_type', ['manage', 'delete'])
            ->active()
            ->exists();

        if ($hasHighLevelPermissions && $newLevel === 'read_only') {
            $validator->errors()->add('access_level',
                'Cannot downgrade to read-only: subsidiary has active management permissions.');
        }
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
