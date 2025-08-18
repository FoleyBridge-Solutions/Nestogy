<?php

namespace App\Domains\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $serviceId = $this->route('service')->id;

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($serviceId)
            ],
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'tax_id' => 'nullable|exists:taxes,id',
            'tax_inclusive' => 'boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            
            // Service-specific fields
            'unit_type' => 'required|in:hours,units,days,weeks,months,years,fixed,subscription',
            'billing_model' => 'required|in:one_time,subscription,usage_based,hybrid',
            'billing_cycle' => 'required|in:one_time,hourly,daily,weekly,monthly,quarterly,semi_annually,annually',
            'billing_interval' => 'required|integer|min:1|max:12',
            
            // Pricing fields from unified form
            'pricing_model' => 'required|in:fixed,tiered,volume,usage,value,custom',
            
            // Tax fields from unified form  
            'is_taxable' => 'boolean',
            'allow_discounts' => 'boolean',
            'requires_approval' => 'boolean',
            
            // Optional service fields
            'track_inventory' => 'boolean',
            'current_stock' => 'nullable|integer|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
            'max_quantity_per_order' => 'nullable|integer|min:1',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'short_description' => 'nullable|string|max:500',
            
            // Legacy/compatibility fields
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'billing_cycle.required_if' => 'Billing cycle is required for subscription services.',
            'billing_interval.required_if' => 'Billing interval is required for subscription services.',
            'unit_type.in' => 'Please select a valid unit type.',
            'billing_model.in' => 'Please select a valid billing model.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'tax_id' => 'tax rate',
            'category_id' => 'category',
            'unit_type' => 'unit type',
            'billing_model' => 'billing model',
            'billing_cycle' => 'billing cycle',
            'billing_interval' => 'billing interval',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values similar to StoreServiceRequest
        $this->merge([
            'type' => 'service',
            'is_active' => $this->has('is_active'),
            'is_featured' => $this->has('is_featured'),
            'is_taxable' => $this->has('is_taxable') ? true : false,
            'tax_inclusive' => $this->has('tax_inclusive'),
            'allow_discounts' => $this->has('allow_discounts') ? true : false,
            'requires_approval' => $this->has('requires_approval'),
            'track_inventory' => $this->has('track_inventory'),
            'currency_code' => $this->input('currency_code', 'USD'),
            'billing_interval' => $this->input('billing_interval', 1),
            'current_stock' => $this->input('current_stock', 0),
            'min_stock_level' => $this->input('min_stock_level', 0),
            'sort_order' => $this->input('sort_order', 0),
        ]);

        // Set defaults for billing fields based on model
        if ($this->input('billing_model') === 'subscription') {
            // For subscriptions, use submitted values or defaults
            $this->merge([
                'billing_cycle' => $this->input('billing_cycle', 'month'),
                'billing_interval' => $this->input('billing_interval', 1),
            ]);
        } else {
            // For non-subscription models, use one_time as default
            $this->merge([
                'billing_cycle' => 'one_time',
                'billing_interval' => 1,
            ]);
        }
    }
}