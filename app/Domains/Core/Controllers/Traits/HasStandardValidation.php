<?php

namespace App\Domains\Core\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait HasStandardValidation
{
    protected function getClientValidationRule(): array
    {
        return [
            'required',
            'integer',
            Rule::exists('clients', 'id')->where(function ($query) {
                return $query->where('company_id', auth()->user()->company_id);
            })
        ];
    }
    
    protected function getCompanyValidationRule(): array
    {
        return [
            'sometimes',
            'integer',
            Rule::in([auth()->user()->company_id])
        ];
    }
    
    protected function getAssetValidationRule(): array
    {
        return [
            'required',
            'integer',
            Rule::exists('assets', 'id')->where(function ($query) {
                return $query->where('company_id', auth()->user()->company_id);
            })
        ];
    }
    
    protected function getUserValidationRule(): array
    {
        return [
            'sometimes',
            'integer',
            Rule::exists('users', 'id')->where(function ($query) {
                return $query->where('company_id', auth()->user()->company_id);
            })
        ];
    }
    
    protected function getStandardTextValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'status' => 'required|string|in:active,inactive,pending,completed,cancelled',
            'priority' => 'sometimes|string|in:low,medium,high,critical'
        ];
    }
    
    protected function getStandardDateValidationRules(): array
    {
        return [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'due_date' => 'nullable|date',
            'scheduled_at' => 'nullable|date'
        ];
    }
    
    protected function getStandardFinancialValidationRules(): array
    {
        return [
            'amount' => 'required|numeric|min:0|max:999999.99',
            'cost' => 'nullable|numeric|min:0|max:999999.99',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'quantity' => 'sometimes|integer|min:1|max:999999',
            'tax_rate' => 'nullable|numeric|min:0|max:100'
        ];
    }
    
    protected function mergeValidationRules(array ...$ruleSets): array
    {
        return array_merge_recursive(...$ruleSets);
    }
}