<?php

namespace App\Http\Requests;

abstract class BaseStoreRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', $this->getModelClass());
    }
    
    abstract protected function getModelClass(): string;
    
    public function rules(): array
    {
        return $this->getValidationRules();
    }
    
    abstract protected function getValidationRules(): array;
    
    protected function prepareForValidation(): void
    {
        // Convert boolean fields
        $booleanFields = $this->getBooleanFields();
        $data = [];
        
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->boolean($field);
            }
        }
        
        if (!empty($data)) {
            $this->merge($data);
        }
        
        $this->customPrepareForValidation();
    }
    
    protected function getBooleanFields(): array
    {
        return [];
    }
    
    protected function customPrepareForValidation(): void
    {
        // Override in child classes
    }
}