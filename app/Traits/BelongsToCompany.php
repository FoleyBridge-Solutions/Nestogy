<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Models\CompanyHierarchy;
use App\Models\CrossCompanyUser;
use App\Models\SubsidiaryPermission;

trait BelongsToCompany
{
    /**
     * Boot the BelongsToCompany trait.
     */
    protected static function bootBelongsToCompany()
    {
        // Automatically set company_id when creating
        static::creating(function ($model) {
            if (empty($model->company_id) && auth()->check()) {
                $model->company_id = auth()->user()->company_id;
            }
        });

        // Add global scope to filter by company
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where($builder->getModel()->getTable() . '.company_id', auth()->user()->company_id);
            }
        });
    }

    /**
     * Scope a query to only include records for a specific company.
     */
    public function scopeForCompany($query, $companyId = null)
    {
        if ($companyId === null) {
            // If no company ID provided, use the current user's company
            $companyId = auth()->check() ? auth()->user()->company_id : null;
        }
        
        if ($companyId) {
            return $query->withoutGlobalScope('company')->where('company_id', $companyId);
        }
        
        return $query;
    }

    /**
     * Scope to include records from companies in the hierarchy.
     * This allows parent companies to access subsidiary data.
     */
    public function scopeForCompanyHierarchy($query, $companyId = null, $includeDescendants = true, $includeAncestors = false)
    {
        if ($companyId === null) {
            $companyId = auth()->check() ? auth()->user()->company_id : null;
        }
        
        if (!$companyId) {
            return $query->withoutGlobalScope('company')->whereNull('company_id');
        }

        $companyIds = [$companyId];

        // Include descendant companies (subsidiaries)
        if ($includeDescendants) {
            $descendants = CompanyHierarchy::getDescendants($companyId);
            $descendantIds = $descendants->pluck('descendant_id')->toArray();
            $companyIds = array_merge($companyIds, $descendantIds);
        }

        // Include ancestor companies (parents)
        if ($includeAncestors) {
            $ancestors = CompanyHierarchy::getAncestors($companyId);
            $ancestorIds = $ancestors->pluck('ancestor_id')->toArray();
            $companyIds = array_merge($companyIds, $ancestorIds);
        }

        return $query->withoutGlobalScope('company')
                    ->whereIn('company_id', array_unique($companyIds));
    }

    /**
     * Scope to include only records the current user can access based on permissions.
     */
    public function scopeWithCrossCompanyAccess($query)
    {
        if (!auth()->check()) {
            return $query->withoutGlobalScope('company')->whereNull('company_id');
        }

        $user = auth()->user();
        $userCompanyId = $user->company_id;
        
        // Get companies the user can access
        $accessibleCompanies = CrossCompanyUser::getAccessibleCompanies($user->id);
        $accessibleCompanyIds = $accessibleCompanies->pluck('id')->toArray();
        $accessibleCompanyIds[] = $userCompanyId; // Always include their own company

        // Check for specific resource permissions
        $modelClass = get_class($query->getModel());
        $permissionCompanies = SubsidiaryPermission::where('grantee_company_id', $userCompanyId)
            ->where('resource_type', $modelClass)
            ->whereIn('permission_type', ['view', 'manage'])
            ->active()
            ->pluck('granter_company_id')
            ->toArray();

        $accessibleCompanyIds = array_merge($accessibleCompanyIds, $permissionCompanies);

        return $query->withoutGlobalScope('company')
                    ->whereIn('company_id', array_unique($accessibleCompanyIds));
    }

    /**
     * Scope to get records from subsidiary companies only.
     */
    public function scopeFromSubsidiaries($query, $parentCompanyId = null)
    {
        if ($parentCompanyId === null) {
            $parentCompanyId = auth()->check() ? auth()->user()->company_id : null;
        }
        
        if (!$parentCompanyId) {
            return $query->withoutGlobalScope('company')->whereNull('company_id');
        }

        $subsidiaryIds = CompanyHierarchy::getDescendants($parentCompanyId)
            ->pluck('descendant_id')
            ->toArray();

        return $query->withoutGlobalScope('company')
                    ->whereIn('company_id', $subsidiaryIds);
    }

    /**
     * Scope to get records from parent companies only.
     */
    public function scopeFromParents($query, $childCompanyId = null)
    {
        if ($childCompanyId === null) {
            $childCompanyId = auth()->check() ? auth()->user()->company_id : null;
        }
        
        if (!$childCompanyId) {
            return $query->withoutGlobalScope('company')->whereNull('company_id');
        }

        $parentIds = CompanyHierarchy::getAncestors($childCompanyId)
            ->pluck('ancestor_id')
            ->toArray();

        return $query->withoutGlobalScope('company')
                    ->whereIn('company_id', $parentIds);
    }

    /**
     * Scope to filter by specific permission level.
     */
    public function scopeWithPermission($query, $permissionType = 'view', $userCompanyId = null)
    {
        if ($userCompanyId === null) {
            $userCompanyId = auth()->check() ? auth()->user()->company_id : null;
        }
        
        if (!$userCompanyId) {
            return $query->withoutGlobalScope('company')->whereNull('company_id');
        }

        $modelClass = get_class($query->getModel());
        
        $permittedCompanies = SubsidiaryPermission::where('grantee_company_id', $userCompanyId)
            ->where('resource_type', $modelClass)
            ->where('permission_type', $permissionType)
            ->active()
            ->pluck('granter_company_id')
            ->toArray();

        // Always include their own company
        $permittedCompanies[] = $userCompanyId;

        return $query->withoutGlobalScope('company')
                    ->whereIn('company_id', array_unique($permittedCompanies));
    }

    /**
     * Check if current user can access this specific record.
     */
    public function userCanAccess($userId = null): bool
    {
        if ($userId === null) {
            $userId = auth()->id();
        }

        if (!$userId) {
            return false;
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return false;
        }

        // Same company - always allowed
        if ($this->company_id === $user->company_id) {
            return true;
        }

        // Check cross-company access
        if (CrossCompanyUser::canUserAccessCompany($userId, $this->company_id)) {
            return true;
        }

        // Check subsidiary permissions
        $modelClass = get_class($this);
        return SubsidiaryPermission::hasPermission(
            $user->company_id,
            $modelClass,
            'view',
            $userId
        );
    }

    /**
     * Check if current user can edit this specific record.
     */
    public function userCanEdit($userId = null): bool
    {
        if ($userId === null) {
            $userId = auth()->id();
        }

        if (!$userId) {
            return false;
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return false;
        }

        // Same company - check user permissions (handled elsewhere)
        if ($this->company_id === $user->company_id) {
            return true; // Delegate to Laravel policies
        }

        // Check cross-company access with edit permissions
        $crossCompanyAccess = CrossCompanyUser::where('user_id', $userId)
            ->where('company_id', $this->company_id)
            ->active()
            ->first();

        if ($crossCompanyAccess && $crossCompanyAccess->hasPermission('edit')) {
            return true;
        }

        // Check subsidiary permissions
        $modelClass = get_class($this);
        return SubsidiaryPermission::hasPermission(
            $user->company_id,
            $modelClass,
            'edit',
            $userId
        );
    }

    /**
     * Get the company relationship.
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}