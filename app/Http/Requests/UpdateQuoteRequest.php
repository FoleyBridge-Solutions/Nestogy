<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Quote;
use App\Models\Client;
use App\Models\Category;

/**
 * UpdateQuoteRequest
 * 
 * Validation rules for updating existing quotes with enterprise features
 * and VoIP-specific configurations.
 */
class UpdateQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->hasPermission('financial.quotes.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = Auth::user();
        $quote = $this->route('quote');
        
        return [
            // Basic quote information
            'prefix' => 'nullable|string|max:10',
            'scope' => 'nullable|string|max:255',
            'date' => 'required|date',
            'expire_date' => 'nullable|date|after:date',
            'valid_until' => 'nullable|date|after:date',
            
            // Status and approval
            'status' => 'required|in:' . implode(',', Quote::getAvailableStatuses()),
            'approval_status' => 'nullable|in:' . implode(',', Quote::getAvailableApprovalStatuses()),
            
            // Financial information
            'discount_amount' => 'nullable|numeric|min:0|max:999999.99',
            'discount_type' => 'required|in:percentage,fixed',
            'currency_code' => 'required|string|size:3',
            
            // Content
            'note' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'change_reason' => 'nullable|string|max:500',
            
            // Auto-renewal settings
            'auto_renew' => 'nullable|boolean',
            'auto_renew_days' => 'nullable|integer|min:1|max:365|required_if:auto_renew,true',
            
            // Template and VoIP configuration
            'template_name' => 'nullable|string|max:100',
            'voip_config' => 'nullable|array',
            'voip_config.extensions' => 'nullable|integer|min:1|max:1000',
            'voip_config.concurrent_calls' => 'nullable|integer|min:1|max:500',
            'voip_config.features' => 'nullable|array',
            'voip_config.features.voicemail' => 'nullable|boolean',
            'voip_config.features.call_forwarding' => 'nullable|boolean',
            'voip_config.features.conference_calling' => 'nullable|boolean',
            'voip_config.features.auto_attendant' => 'nullable|boolean',
            'voip_config.equipment' => 'nullable|array',
            'voip_config.equipment.desk_phones' => 'nullable|integer|min:0|max:1000',
            'voip_config.equipment.wireless_phones' => 'nullable|integer|min:0|max:1000',
            'voip_config.equipment.conference_phone' => 'nullable|integer|min:0|max:100',
            'voip_config.monthly_allowances' => 'nullable|array',
            
            // Pricing model
            'pricing_model' => 'nullable|array',
            'pricing_model.type' => 'nullable|in:flat_rate,tiered,usage_based,hybrid',
            'pricing_model.setup_fee' => 'nullable|numeric|min:0|max:999999.99',
            'pricing_model.monthly_recurring' => 'nullable|numeric|min:0|max:999999.99',
            'pricing_model.per_extension' => 'nullable|numeric|min:0|max:9999.99',
            'pricing_model.per_minute_overage' => 'nullable|numeric|min:0|max:99.99',
            'pricing_model.equipment_lease' => 'nullable|numeric|min:0|max:999999.99',
            
            // Relationships
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($user) {
                    $category = Category::find($value);
                    if ($category && $category->company_id !== $user->company_id) {
                        $fail('The selected category is invalid.');
                    }
                },
            ],
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',
                function ($attribute, $value, $fail) use ($user) {
                    $client = Client::find($value);
                    if ($client && $client->company_id !== $user->company_id) {
                        $fail('The selected client is invalid.');
                    }
                },
            ],
            
            // Quote items
            'items' => 'nullable|array',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01|max:999999',
            'items.*.price' => 'required_with:items|numeric|min:0|max:999999.99',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_id' => 'nullable|integer|exists:taxes,id',
            'items.*.category_id' => 'nullable|integer|exists:categories,id',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
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
            'expire_date' => 'expiration date',
            'valid_until' => 'valid until date',
            'discount_amount' => 'discount amount',
            'discount_type' => 'discount type',
            'currency_code' => 'currency',
            'auto_renew_days' => 'auto-renewal days',
            'change_reason' => 'reason for change',
            'voip_config.extensions' => 'number of extensions',
            'voip_config.concurrent_calls' => 'concurrent calls',
            'voip_config.equipment.desk_phones' => 'desk phones',
            'voip_config.equipment.wireless_phones' => 'wireless phones',
            'voip_config.equipment.conference_phone' => 'conference phones',
            'pricing_model.setup_fee' => 'setup fee',
            'pricing_model.monthly_recurring' => 'monthly recurring fee',
            'pricing_model.per_extension' => 'per extension cost',
            'pricing_model.per_minute_overage' => 'per minute overage rate',
            'pricing_model.equipment_lease' => 'equipment lease cost',
            'items.*.name' => 'item name',
            'items.*.quantity' => 'item quantity',
            'items.*.price' => 'item price',
            'items.*.discount' => 'item discount',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'expire_date.after' => 'The expiration date must be after the quote date.',
            'valid_until.after' => 'The valid until date must be after the quote date.',
            'discount_amount.max' => 'The discount amount cannot exceed $999,999.99.',
            'currency_code.size' => 'The currency code must be exactly 3 characters (e.g., USD).',
            'auto_renew_days.required_if' => 'Auto-renewal days are required when auto-renewal is enabled.',
            'auto_renew_days.max' => 'Auto-renewal days cannot exceed 365 days.',
            'change_reason.max' => 'The reason for change cannot exceed 500 characters.',
            'voip_config.extensions.min' => 'At least 1 extension is required.',
            'voip_config.extensions.max' => 'Cannot exceed 1,000 extensions.',
            'voip_config.concurrent_calls.min' => 'At least 1 concurrent call is required.',
            'voip_config.concurrent_calls.max' => 'Cannot exceed 500 concurrent calls.',
            'pricing_model.setup_fee.max' => 'Setup fee cannot exceed $999,999.99.',
            'pricing_model.monthly_recurring.max' => 'Monthly recurring fee cannot exceed $999,999.99.',
            'pricing_model.per_extension.max' => 'Per extension cost cannot exceed $9,999.99.',
            'pricing_model.per_minute_overage.max' => 'Per minute overage rate cannot exceed $99.99.',
            'pricing_model.equipment_lease.max' => 'Equipment lease cost cannot exceed $999,999.99.',
            'items.*.name.required_with' => 'Item name is required.',
            'items.*.quantity.required_with' => 'Item quantity is required.',
            'items.*.quantity.min' => 'Item quantity must be at least 0.01.',
            'items.*.quantity.max' => 'Item quantity cannot exceed 999,999.',
            'items.*.price.required_with' => 'Item price is required.',
            'items.*.price.max' => 'Item price cannot exceed $999,999.99.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $quote = $this->route('quote');

            // Validate quote can be edited
            if ($quote && !$quote->isDraft() && $quote->approval_status !== Quote::APPROVAL_REJECTED) {
                $validator->errors()->add('quote', 'Only draft or rejected quotes can be edited.');
                return;
            }

            // Validate discount percentage doesn't exceed 100%
            if ($this->discount_type === 'percentage' && $this->discount_amount > 100) {
                $validator->errors()->add('discount_amount', 'Discount percentage cannot exceed 100%.');
            }

            // Validate expiration dates are not in the past for sent quotes
            if ($quote && $quote->isSent()) {
                if ($this->expire_date && now()->gt($this->expire_date)) {
                    $validator->errors()->add('expire_date', 'Expiration date cannot be in the past for sent quotes.');
                }
                
                if ($this->valid_until && now()->gt($this->valid_until)) {
                    $validator->errors()->add('valid_until', 'Valid until date cannot be in the past for sent quotes.');
                }
            }

            // Validate VoIP configuration consistency
            if ($this->voip_config) {
                $extensions = $this->input('voip_config.extensions', 0);
                $concurrentCalls = $this->input('voip_config.concurrent_calls', 0);
                
                if ($concurrentCalls > $extensions) {
                    $validator->errors()->add('voip_config.concurrent_calls', 
                        'Concurrent calls cannot exceed the number of extensions.');
                }
                
                // Validate equipment doesn't exceed extensions
                $deskPhones = $this->input('voip_config.equipment.desk_phones', 0);
                $wirelessPhones = $this->input('voip_config.equipment.wireless_phones', 0);
                $totalPhones = $deskPhones + $wirelessPhones;
                
                if ($totalPhones > $extensions) {
                    $validator->errors()->add('voip_config.equipment', 
                        'Total phones cannot exceed the number of extensions.');
                }
            }

            // Validate pricing model consistency
            if ($this->pricing_model && $this->input('pricing_model.type') === 'usage_based') {
                if (!$this->input('pricing_model.per_minute_overage')) {
                    $validator->errors()->add('pricing_model.per_minute_overage', 
                        'Per minute overage rate is required for usage-based pricing.');
                }
            }

            // Validate status transitions
            if ($quote && $this->status !== $quote->status) {
                $this->validateStatusTransition($validator, $quote->status, $this->status);
            }
        });
    }

    /**
     * Validate status transitions are allowed.
     */
    private function validateStatusTransition($validator, string $currentStatus, string $newStatus): void
    {
        $allowedTransitions = [
            Quote::STATUS_DRAFT => [Quote::STATUS_SENT, Quote::STATUS_CANCELLED],
            Quote::STATUS_SENT => [Quote::STATUS_VIEWED, Quote::STATUS_ACCEPTED, Quote::STATUS_DECLINED, Quote::STATUS_EXPIRED, Quote::STATUS_CANCELLED],
            Quote::STATUS_VIEWED => [Quote::STATUS_ACCEPTED, Quote::STATUS_DECLINED, Quote::STATUS_EXPIRED, Quote::STATUS_CANCELLED],
            Quote::STATUS_ACCEPTED => [Quote::STATUS_CONVERTED],
            Quote::STATUS_DECLINED => [Quote::STATUS_DRAFT], // Allow revision after decline
            Quote::STATUS_EXPIRED => [Quote::STATUS_DRAFT], // Allow renewal after expiry
            Quote::STATUS_CONVERTED => [], // No transitions allowed from converted
            Quote::STATUS_CANCELLED => [], // No transitions allowed from cancelled
        ];

        if (!isset($allowedTransitions[$currentStatus]) || 
            !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            $validator->errors()->add('status', 
                "Cannot change status from {$currentStatus} to {$newStatus}.");
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set default values
        $this->merge([
            'discount_type' => $this->discount_type ?? Quote::DISCOUNT_FIXED,
            'auto_renew' => $this->boolean('auto_renew'),
        ]);

        // Clean up VoIP configuration
        if ($this->voip_config && is_array($this->voip_config)) {
            $voipConfig = $this->voip_config;
            
            // Convert string booleans to actual booleans for features
            if (isset($voipConfig['features']) && is_array($voipConfig['features'])) {
                foreach ($voipConfig['features'] as $key => $value) {
                    $voipConfig['features'][$key] = $this->boolean("voip_config.features.{$key}");
                }
            }
            
            $this->merge(['voip_config' => $voipConfig]);
        }
    }
}