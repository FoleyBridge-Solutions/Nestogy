<?php

namespace App\Domains\Core\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasCompanyScoping
{
    protected function applyCompanyScope(Builder $query): Builder
    {
        return $query->where('company_id', auth()->user()->company_id);
    }

    protected function getCompanyFilters(Request $request): array
    {
        return array_merge(
            $request->only($this->getAllowedFilters()),
            ['company_id' => auth()->user()->company_id]
        );
    }

    protected function validateCompanyAccess($model): void
    {
        if ($model->company_id !== auth()->user()->company_id) {
            abort(403, 'Access denied: Resource does not belong to your company.');
        }
    }
}
