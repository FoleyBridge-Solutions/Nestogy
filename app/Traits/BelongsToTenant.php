<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait.
     */
    protected static function bootBelongsToTenant()
    {
        // Automatically set tenant_id when creating
        static::creating(function ($model) {
            if (empty($model->tenant_id) && auth()->check()) {
                // For now, map company_id to tenant_id until full tenancy is implemented
                $model->tenant_id = auth()->user()->company_id;
            }
        });

        // Add global scope to filter by tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                // For now, use company_id as tenant_id until full tenancy is implemented
                $builder->where($builder->getModel()->getTable() . '.tenant_id', auth()->user()->company_id);
            }
        });
    }

    /**
     * Scope a query to only include records for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId = null)
    {
        if ($tenantId === null) {
            // If no tenant ID provided, use the current user's company as tenant
            $tenantId = auth()->check() ? auth()->user()->company_id : null;
        }
        
        if ($tenantId) {
            return $query->withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
        }
        
        return $query;
    }

    /**
     * Get the tenant relationship.
     * For now, this maps to the Company model until full tenancy is implemented.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Company::class, 'tenant_id');
    }
}