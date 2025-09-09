<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BaseRequest extends FormRequest
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
    
    protected function getStandardTextRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ];
    }
    
    protected function getStatusValidationRule(array $allowedStatuses = ['active', 'inactive']): array
    {
        return [
            'required',
            'string',
            Rule::in($allowedStatuses)
        ];
    }
    
    protected function getPriorityValidationRule(): array
    {
        return [
            'sometimes',
            'string',
            Rule::in(['low', 'medium', 'high', 'critical'])
        ];
    }
    
    protected function getStandardDateRules(): array
    {
        return [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'due_date' => 'nullable|date',
            'scheduled_at' => 'nullable|date'
        ];
    }
    
    protected function getFinancialRules(): array
    {
        return [
            'amount' => 'required|numeric|min:0|max:999999.99',
            'cost' => 'nullable|numeric|min:0|max:999999.99',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'quantity' => 'sometimes|integer|min:1|max:999999',
            'tax_rate' => 'nullable|numeric|min:0|max:100'
        ];
    }
    
    protected function getFileUploadRules(int $maxSizeMB = 50): array
    {
        return [
            'file' => "required|file|max:" . ($maxSizeMB * 1024), // Convert MB to KB
        ];
    }
    
    protected function mergeRules(array ...$ruleSets): array
    {
        return array_merge_recursive(...$ruleSets);
    }
    
    protected function getStandardMessages(): array
    {
        return [
            'client_id.exists' => 'The selected client does not belong to your company.',
            'asset_id.exists' => 'The selected asset does not belong to your company.',
            'user_id.exists' => 'The selected user does not belong to your company.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'file.max' => 'The file may not be larger than :max KB.',
            'amount.max' => 'The amount may not be greater than $999,999.99.',
        ];
    }
    
    public function messages(): array
    {
        return array_merge($this->getStandardMessages(), $this->customMessages());
    }
    
    protected function customMessages(): array
    {
        return [];
    }
}