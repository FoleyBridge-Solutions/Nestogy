<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

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
     * Get the company relationship.
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}