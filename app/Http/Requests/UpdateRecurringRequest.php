<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecurringRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|integer|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:weekly,bi_weekly,monthly,quarterly,semi_annually,annually',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'next_billing_date' => 'required|date',
            'currency_code' => 'required|string|size:3',
            'status' => 'required|boolean',
            'auto_send' => 'required|boolean',
            'category_id' => 'nullable|integer|exists:categories,id',

            // VoIP-specific fields
            'billing_type' => 'required|in:flat,usage_based,tiered,hybrid,volume_discount',
            'pricing_model' => 'required|in:flat,usage_based,tiered,hybrid,volume_discount',
            'service_type' => 'required|string|max:100',
            'contract_escalation_enabled' => 'required|boolean',
            'proration_enabled' => 'required|boolean',
            'tax_calculation_enabled' => 'required|boolean',

            // Service tiers (array validation)
            'service_tiers' => 'nullable|array',
            'service_tiers.*.service_type' => 'required|string|max:100',
            'service_tiers.*.monthly_allowance' => 'required|numeric|min:0',
            'service_tiers.*.base_rate' => 'required|numeric|min:0',
            'service_tiers.*.overage_rate' => 'required|numeric|min:0',
            'service_tiers.*.tier_structure' => 'nullable|array',

            // Volume discounts
            'volume_discounts' => 'nullable|array',
            'volume_discounts.*.threshold' => 'required|numeric|min:0',
            'volume_discounts.*.discount_type' => 'required|in:percentage,fixed',
            'volume_discounts.*.discount_value' => 'required|numeric|min:0',

            // Contract escalations
            'contract_escalations' => 'nullable|array',
            'contract_escalations.*.effective_date' => 'required|date',
            'contract_escalations.*.percentage' => 'required|numeric|min:0|max:100',
            'contract_escalations.*.description' => 'nullable|string|max:255',

            // Service bundles
            'service_bundles' => 'nullable|array',
            'service_bundles.*.name' => 'required|string|max:255',
            'service_bundles.*.bundle_price' => 'required|numeric|min:0',
            'service_bundles.*.included_services' => 'required|array',

            // Additional metadata
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'The selected client is invalid.',
            'name.required' => 'Recurring billing name is required.',
            'name.max' => 'Recurring billing name cannot exceed 255 characters.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be greater than or equal to 0.',
            'billing_cycle.required' => 'Billing cycle is required.',
            'billing_cycle.in' => 'Invalid billing cycle. Must be weekly, bi_weekly, monthly, quarterly, semi_annually, or annually.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after' => 'End date must be after the start date.',
            'next_billing_date.required' => 'Next billing date is required.',
            'next_billing_date.date' => 'Next billing date must be a valid date.',
            'currency_code.required' => 'Currency is required.',
            'currency_code.size' => 'Currency code must be exactly 3 characters.',
            'status.required' => 'Status is required.',
            'status.boolean' => 'Status must be true or false.',
            'auto_send.required' => 'Auto send setting is required.',
            'auto_send.boolean' => 'Auto send must be true or false.',
            'category_id.exists' => 'The selected category is invalid.',

            // VoIP-specific messages
            'billing_type.required' => 'Billing type is required.',
            'billing_type.in' => 'Invalid billing type.',
            'pricing_model.required' => 'Pricing model is required.',
            'pricing_model.in' => 'Invalid pricing model.',
            'service_type.required' => 'Service type is required.',
            'service_type.max' => 'Service type cannot exceed 100 characters.',
            'contract_escalation_enabled.required' => 'Contract escalation setting is required.',
            'contract_escalation_enabled.boolean' => 'Contract escalation must be true or false.',
            'proration_enabled.required' => 'Proration setting is required.',
            'proration_enabled.boolean' => 'Proration must be true or false.',
            'tax_calculation_enabled.required' => 'Tax calculation setting is required.',
            'tax_calculation_enabled.boolean' => 'Tax calculation must be true or false.',

            // Service tier messages
            'service_tiers.array' => 'Service tiers must be an array.',
            'service_tiers.*.service_type.required' => 'Service type is required for each tier.',
            'service_tiers.*.monthly_allowance.required' => 'Monthly allowance is required for each tier.',
            'service_tiers.*.monthly_allowance.numeric' => 'Monthly allowance must be a number.',
            'service_tiers.*.base_rate.required' => 'Base rate is required for each tier.',
            'service_tiers.*.base_rate.numeric' => 'Base rate must be a number.',
            'service_tiers.*.overage_rate.required' => 'Overage rate is required for each tier.',
            'service_tiers.*.overage_rate.numeric' => 'Overage rate must be a number.',

            // Volume discount messages
            'volume_discounts.*.threshold.required' => 'Threshold is required for each volume discount.',
            'volume_discounts.*.threshold.numeric' => 'Threshold must be a number.',
            'volume_discounts.*.discount_type.required' => 'Discount type is required for each volume discount.',
            'volume_discounts.*.discount_type.in' => 'Discount type must be percentage or fixed.',
            'volume_discounts.*.discount_value.required' => 'Discount value is required for each volume discount.',
            'volume_discounts.*.discount_value.numeric' => 'Discount value must be a number.',

            // Contract escalation messages
            'contract_escalations.*.effective_date.required' => 'Effective date is required for each escalation.',
            'contract_escalations.*.effective_date.date' => 'Effective date must be a valid date.',
            'contract_escalations.*.percentage.required' => 'Percentage is required for each escalation.',
            'contract_escalations.*.percentage.numeric' => 'Percentage must be a number.',
            'contract_escalations.*.percentage.max' => 'Percentage cannot exceed 100.',

            // Service bundle messages
            'service_bundles.*.name.required' => 'Bundle name is required.',
            'service_bundles.*.bundle_price.required' => 'Bundle price is required.',
            'service_bundles.*.bundle_price.numeric' => 'Bundle price must be a number.',
            'service_bundles.*.included_services.required' => 'Included services are required for each bundle.',
            'service_bundles.*.included_services.array' => 'Included services must be an array.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'client',
            'category_id' => 'category',
            'currency_code' => 'currency',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'next_billing_date' => 'next billing date',
            'billing_cycle' => 'billing cycle',
            'billing_type' => 'billing type',
            'pricing_model' => 'pricing model',
            'service_type' => 'service type',
            'contract_escalation_enabled' => 'contract escalation',
            'proration_enabled' => 'proration',
            'tax_calculation_enabled' => 'tax calculation',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $this->merge([
            'status' => $this->boolean('status'),
            'auto_send' => $this->boolean('auto_send'),
            'contract_escalation_enabled' => $this->boolean('contract_escalation_enabled'),
            'proration_enabled' => $this->boolean('proration_enabled'),
            'tax_calculation_enabled' => $this->boolean('tax_calculation_enabled'),
        ]);

        // Parse JSON strings for complex fields if they come as strings
        if (is_string($this->service_tiers)) {
            $this->merge(['service_tiers' => json_decode($this->service_tiers, true)]);
        }
        if (is_string($this->volume_discounts)) {
            $this->merge(['volume_discounts' => json_decode($this->volume_discounts, true)]);
        }
        if (is_string($this->contract_escalations)) {
            $this->merge(['contract_escalations' => json_decode($this->contract_escalations, true)]);
        }
        if (is_string($this->service_bundles)) {
            $this->merge(['service_bundles' => json_decode($this->service_bundles, true)]);
        }
        if (is_string($this->metadata)) {
            $this->merge(['metadata' => json_decode($this->metadata, true)]);
        }
    }
}
