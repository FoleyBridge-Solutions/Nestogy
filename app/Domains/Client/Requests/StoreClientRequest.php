<?php

namespace App\Domains\Client\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;

class StoreClientRequest extends BaseFormRequest
{
    protected function initializeRequest(): void
    {
        $this->modelClass = Client::class;
        $this->requiresCompanyValidation = true;
    }

    protected function getSpecificRules(): array
    {
        return [
            // Client specific information
            'company_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:100',
            'referral' => 'nullable|string|max:255',
            'rate' => 'nullable|numeric|min:0|max:999999.99',
            'currency_code' => $this->getCurrencyValidationRule(),
            'net_terms' => 'nullable|integer|min:0|max:365',
            'tax_id_number' => 'nullable|string|max:50',
            'rmm_id' => 'nullable|integer',
            'lead' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0|max:9999.99',

            // Primary location information
            'location_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'location_phone' => 'nullable|string|max:20',
            'location_address' => 'nullable|string|max:255',
            'location_city' => 'nullable|string|max:100',
            'location_state' => 'nullable|string|max:50',
            'location_zip' => 'nullable|string|max:20',
            'location_country' => 'nullable|string|max:100',

            // Primary contact information
            'contact_name' => 'nullable|string|max:255',
            'contact_title' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'contact_extension' => 'nullable|string|max:10',
            'contact_mobile' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'contact_technical' => 'boolean',
            'contact_billing' => 'boolean',

            // Tags
            'tags' => 'nullable|json',
        ];
    }

    protected function getExpectedFields(): array
    {
        return [
            'name', 'email', 'phone', 'website', 'notes', 'status'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Client name is required.',
            'name.max' => 'Client name cannot exceed 255 characters.',
            'website.url' => 'Website must be a valid URL.',
            'rate.numeric' => 'Rate must be a valid number.',
            'rate.min' => 'Rate cannot be negative.',
            'currency_code.size' => 'Currency code must be exactly 3 characters.',
            'net_terms.integer' => 'Net terms must be a whole number.',
            'net_terms.min' => 'Net terms cannot be negative.',
            'net_terms.max' => 'Net terms cannot exceed 365 days.',
            'contact_email.email' => 'Contact email must be a valid email address.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'client name',
            'company_name' => 'company name',
            'type' => 'client type',
            'email' => 'client email',
            'phone' => 'client phone',
            'website' => 'website',
            'referral' => 'referral source',
            'rate' => 'hourly rate',
            'currency_code' => 'currency code',
            'net_terms' => 'net terms',
            'tax_id_number' => 'tax ID number',
            'rmm_id' => 'RMM ID',
            'lead' => 'lead status',
            'notes' => 'notes',
            'status' => 'status',
            'hourly_rate' => 'hourly rate',
            'location_name' => 'location name',
            'address' => 'address',
            'city' => 'city',
            'state' => 'state',
            'zip_code' => 'ZIP code',
            'country' => 'country',
            'location_phone' => 'location phone',
            'location_address' => 'location address',
            'location_city' => 'location city',
            'location_state' => 'location state',
            'location_zip' => 'location ZIP',
            'location_country' => 'location country',
            'contact_name' => 'contact name',
            'contact_title' => 'contact title',
            'contact_phone' => 'contact phone',
            'contact_extension' => 'contact extension',
            'contact_mobile' => 'contact mobile',
            'contact_email' => 'contact email',
            'contact_technical' => 'technical contact',
            'contact_billing' => 'billing contact',
            'tags' => 'tags',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean phone numbers
        if ($this->has('location_phone')) {
            $this->merge([
                'location_phone' => preg_replace('/[^0-9]/', '', $this->location_phone)
            ]);
        }

        if ($this->has('contact_phone')) {
            $this->merge([
                'contact_phone' => preg_replace('/[^0-9]/', '', $this->contact_phone)
            ]);
        }

        if ($this->has('contact_mobile')) {
            $this->merge([
                'contact_mobile' => preg_replace('/[^0-9]/', '', $this->contact_mobile)
            ]);
        }

        if ($this->has('contact_extension')) {
            $this->merge([
                'contact_extension' => preg_replace('/[^0-9]/', '', $this->contact_extension)
            ]);
        }

        // Set boolean defaults
        $this->merge([
            'lead' => $this->boolean('lead'),
            'contact_technical' => $this->boolean('contact_technical'),
            'contact_billing' => $this->boolean('contact_billing'),
        ]);

        // Set default values
        if (!$this->has('currency_code') || empty($this->currency_code)) {
            $this->merge(['currency_code' => 'USD']);
        }

        if (!$this->has('net_terms') || empty($this->net_terms)) {
            $this->merge(['net_terms' => 30]);
        }

        if (!$this->has('status') || empty($this->status)) {
            $this->merge(['status' => 'active']);
        }

        if (!$this->has('country') || empty($this->country)) {
            $this->merge(['country' => 'US']);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure at least client name is provided
            if (empty($this->name)) {
                $validator->errors()->add('name', 'The name field is required.');
            }

            // Ensure email is provided
            if (empty($this->email)) {
                $validator->errors()->add('email', 'The email field is required.');
            }

            // If contact email is provided, validate it's unique for this company
            if ($this->filled('contact_email')) {
                $user = Auth::user();
                $existingContact = \App\Models\Contact::where('company_id', $user->company_id)
                    ->where('email', $this->contact_email)
                    ->first();
                
                if ($existingContact) {
                    $validator->errors()->add('contact_email', 'A contact with this email already exists.');
                }
            }

            // Validate currency code if provided
            if ($this->filled('currency_code')) {
                $validCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'CNY', 'INR'];
                if (!in_array(strtoupper($this->currency_code), $validCurrencies)) {
                    $validator->errors()->add('currency_code', 'Invalid currency code.');
                }
            }
        });
    }
}