<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

abstract class BaseFormRequest extends FormRequest
{
    protected string $modelClass;

    protected bool $requiresCompanyValidation = true;

    abstract protected function initializeRequest(): void;

    public function __construct()
    {
        parent::__construct();
        $this->initializeRequest();
    }

    public function authorize(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        $action = $this->getRouteAction();

        if ($this->route() && $this->route()->parameter($this->getModelParameter())) {
            $model = $this->route()->parameter($this->getModelParameter());

            return Auth::user()->can($action, $model);
        }

        return Auth::user()->can($action, $this->modelClass);
    }

    protected function getRouteAction(): string
    {
        $method = $this->getMethod();
        $routeName = $this->route() ? $this->route()->getName() : '';

        if ($method === 'POST') {
            return 'create';
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            return 'update';
        } elseif ($method === 'DELETE') {
            return 'delete';
        }

        return 'view';
    }

    protected function getModelParameter(): string
    {
        $modelName = class_basename($this->modelClass);

        return strtolower($modelName);
    }

    public function rules(): array
    {
        return array_merge(
            $this->getCommonRules(),
            $this->getSpecificRules()
        );
    }

    protected function getCommonRules(): array
    {
        $rules = [];

        // Common field validations
        if ($this->hasField('name')) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        if ($this->hasField('description')) {
            $rules['description'] = ['nullable', 'string', 'max:2000'];
        }

        if ($this->hasField('notes')) {
            $rules['notes'] = ['nullable', 'string', 'max:65535'];
        }

        if ($this->hasField('status')) {
            $rules['status'] = ['nullable', 'string', 'max:50'];
        }

        if ($this->hasField('type')) {
            $rules['type'] = ['nullable', 'string', 'max:100'];
        }

        if ($this->hasField('email')) {
            $rules['email'] = ['nullable', 'email', 'max:255'];
        }

        if ($this->hasField('phone')) {
            $rules['phone'] = ['nullable', 'string', 'max:20'];
        }

        if ($this->hasField('website')) {
            $rules['website'] = ['nullable', 'url', 'max:255'];
        }

        // Company-scoped relationship validations
        if ($this->requiresCompanyValidation) {
            $rules = array_merge($rules, $this->getCompanyScopedRules());
        }

        return $rules;
    }

    protected function getCompanyScopedRules(): array
    {
        $rules = [];
        $companyId = Auth::user()->company_id;

        if ($this->hasField('client_id')) {
            $rules['client_id'] = [
                'nullable',
                Rule::exists('clients', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)->whereNull('archived_at');
                }),
            ];
        }

        if ($this->hasField('contact_id')) {
            $rules['contact_id'] = [
                'nullable',
                Rule::exists('contacts', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)->whereNull('archived_at');
                }),
            ];
        }

        if ($this->hasField('location_id')) {
            $rules['location_id'] = [
                'nullable',
                Rule::exists('locations', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)->whereNull('archived_at');
                }),
            ];
        }

        if ($this->hasField('vendor_id')) {
            $rules['vendor_id'] = [
                'nullable',
                Rule::exists('vendors', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId)->whereNull('archived_at');
                }),
            ];
        }

        if ($this->hasField('user_id')) {
            $rules['user_id'] = [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                }),
            ];
        }

        return $rules;
    }

    abstract protected function getSpecificRules(): array;

    protected function hasField(string $field): bool
    {
        return $this->has($field) || in_array($field, $this->getExpectedFields());
    }

    protected function getExpectedFields(): array
    {
        return [];
    }

    public function messages(): array
    {
        return array_merge(
            $this->getCommonMessages(),
            $this->getSpecificMessages()
        );
    }

    protected function getCommonMessages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 2000 characters.',
            'notes.max' => 'Notes cannot exceed 65535 characters.',
            'email.email' => 'Email must be a valid email address.',
            'email.max' => 'Email cannot exceed 255 characters.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'website.url' => 'Website must be a valid URL.',
            'website.max' => 'Website cannot exceed 255 characters.',
            'client_id.exists' => 'The selected client does not exist or does not belong to your company.',
            'contact_id.exists' => 'The selected contact does not exist or does not belong to your company.',
            'location_id.exists' => 'The selected location does not exist or does not belong to your company.',
            'vendor_id.exists' => 'The selected vendor does not exist or does not belong to your company.',
            'user_id.exists' => 'The selected user does not exist or does not belong to your company.',
        ];
    }

    protected function getSpecificMessages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return array_merge(
            $this->getCommonAttributes(),
            $this->getSpecificAttributes()
        );
    }

    protected function getCommonAttributes(): array
    {
        return [
            'client_id' => 'client',
            'contact_id' => 'contact',
            'location_id' => 'location',
            'vendor_id' => 'vendor',
            'user_id' => 'user',
        ];
    }

    protected function getSpecificAttributes(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCommonFields();
        $this->prepareSpecificFields();
    }

    protected function prepareCommonFields(): void
    {
        // Clean and format phone numbers
        $phoneFields = ['phone', 'contact_phone', 'location_phone', 'mobile'];
        foreach ($phoneFields as $field) {
            if ($this->has($field) && $this->get($field)) {
                $this->merge([
                    $field => preg_replace('/[^0-9+]/', '', $this->get($field)),
                ]);
            }
        }

        // Normalize email addresses
        $emailFields = ['email', 'contact_email'];
        foreach ($emailFields as $field) {
            if ($this->has($field) && $this->get($field)) {
                $this->merge([
                    $field => strtolower(trim($this->get($field))),
                ]);
            }
        }

        // Handle boolean fields
        $booleanFields = $this->getBooleanFields();
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => $this->boolean($field),
                ]);
            }
        }

        // Set default values
        $this->setDefaultValues();
    }

    protected function prepareSpecificFields(): void
    {
        // Override in child classes for specific field preparation
    }

    protected function getBooleanFields(): array
    {
        return ['active', 'primary', 'technical', 'billing', 'lead'];
    }

    protected function setDefaultValues(): void
    {
        // Set common default values
        if ($this->hasField('status') && ! $this->filled('status')) {
            $this->merge(['status' => 'active']);
        }

        if ($this->hasField('currency_code') && ! $this->filled('currency_code')) {
            $this->merge(['currency_code' => 'USD']);
        }

        if ($this->hasField('country') && ! $this->filled('country')) {
            $this->merge(['country' => 'US']);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->performCommonValidation($validator);
            $this->performSpecificValidation($validator);
        });
    }

    protected function performCommonValidation($validator): void
    {
        // Validate currency codes
        if ($this->filled('currency_code')) {
            $validCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'CNY', 'INR'];
            if (! in_array(strtoupper($this->currency_code), $validCurrencies)) {
                $validator->errors()->add('currency_code', 'Invalid currency code.');
            }
        }

        // Validate unique fields within company scope if creating new record
        if ($this->isMethod('POST')) {
            $this->validateUniqueFields($validator);
        }
    }

    protected function performSpecificValidation($validator): void
    {
        // Override in child classes for specific validation logic
    }

    protected function validateUniqueFields($validator): void
    {
        $uniqueFields = $this->getUniqueFields();
        $companyId = Auth::user()->company_id;

        foreach ($uniqueFields as $field) {
            if ($this->filled($field)) {
                $exists = $this->modelClass::where('company_id', $companyId)
                    ->where($field, $this->get($field))
                    ->exists();

                if ($exists) {
                    $fieldName = $this->attributes()[$field] ?? $field;
                    $validator->errors()->add($field, "A record with this {$fieldName} already exists in your company.");
                }
            }
        }
    }

    protected function getUniqueFields(): array
    {
        return [];
    }

    protected function getCurrencyValidationRule(): array
    {
        return [
            'nullable',
            'string',
            'size:3',
            Rule::in(['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'CNY', 'INR']),
        ];
    }

    protected function getNumericAmountRule(): array
    {
        return [
            'nullable',
            'numeric',
            'min:0',
            'max:999999999.99',
        ];
    }

    protected function getDateRule(): array
    {
        return [
            'nullable',
            'date',
        ];
    }

    protected function getFutureDateRule(): array
    {
        return [
            'nullable',
            'date',
            'after:today',
        ];
    }

    protected function getColorRule(): array
    {
        return [
            'nullable',
            'string',
            'regex:/^#[a-fA-F0-9]{6}$/',
        ];
    }
}
