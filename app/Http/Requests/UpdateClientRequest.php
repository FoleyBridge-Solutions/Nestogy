<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('update', $this->route('client'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $clientId = $this->route('client')->id;
        
        return [
            // Client basic information
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:100',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('clients')->ignore($clientId)],
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'referral' => 'nullable|string|max:255',
            'rate' => 'nullable|numeric|min:0|max:999999.99',
            'currency_code' => 'nullable|string|size:3',
            'net_terms' => 'nullable|integer|min:0|max:365',
            'tax_id_number' => 'nullable|string|max:50',
            'rmm_id' => 'nullable|integer',
            'lead' => 'boolean',
            'notes' => 'nullable|string|max:65535',
            'status' => 'nullable|in:active,inactive,suspended',
            'hourly_rate' => 'nullable|numeric|min:0|max:9999.99',

            // Primary location information (for updating existing)
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

            // Primary contact information (for updating existing)
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

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Client name is required.',
            'name.max' => 'Client name cannot exceed 255 characters.',
            'email.email' => 'Client email must be a valid email address.',
            'email.unique' => 'This email is already in use by another client.',
            'website.url' => 'Website must be a valid URL.',
            'rate.numeric' => 'Rate must be a valid number.',
            'rate.min' => 'Rate cannot be negative.',
            'currency_code.size' => 'Currency code must be exactly 3 characters.',
            'net_terms.integer' => 'Net terms must be a whole number.',
            'net_terms.min' => 'Net terms cannot be negative.',
            'net_terms.max' => 'Net terms cannot exceed 365 days.',
            'contact_email.email' => 'Contact email must be a valid email address.',
            'tags.*.exists' => 'One or more selected tags are invalid.',
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
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/[^0-9]/', '', $this->phone)
            ]);
        }

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
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure at least client name is provided
            if (empty($this->name)) {
                $validator->errors()->add('name', 'Client name is required.');
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