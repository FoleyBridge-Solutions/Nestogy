<?php

namespace App\Domains\Core\Controllers\Traits;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasClientRelation
{
    protected function applyClientFilter(Builder $query, Request $request): Builder
    {
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        return $query;
    }

    protected function validateClientAccess(Client $client): void
    {
        if ($client->company_id !== auth()->user()->company_id) {
            abort(403, 'Access denied: Client does not belong to your company.');
        }
    }

    protected function getClientFilterOptions(): array
    {
        return Client::where('company_id', auth()->user()->company_id)
            ->orderBy('company_name')
            ->pluck('company_name', 'id')
            ->toArray();
    }

    protected function getClientFilters(): array
    {
        return array_merge(parent::getAllowedFilters(), ['client_id']);
    }

    protected function getAllowedFilters(): array
    {
        return $this->getClientFilters();
    }
}
