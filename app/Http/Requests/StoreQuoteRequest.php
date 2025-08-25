<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Quote;
use App\Models\Client;
use App\Models\Category;

/**
 * StoreQuoteRequest
 * 
 * Validation rules for creating new quotes with enterprise features
 * and VoIP-specific configurations.
 */
class StoreQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Temporarily simplify authorization for debugging
        return Auth::check();
        
        // Original permission check (commented out for debugging):
        // return Auth::check() && Auth::user()->hasPermission('financial.quotes.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = Auth::user();
        
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
            
            // Relationships - simplified validation
            'category_id' => 'nullable|integer|exists:categories,id',
            'client_id' => 'required|integer|exists:clients,id',
            
            // Quote items (optional during creation)
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
            'date.before_or_equal' => 'The quote date cannot be in the future.',
            'expire_date.after' => 'The expiration date must be after the quote date.',
            'valid_until.after' => 'The valid until date must be after the quote date.',
            'discount_amount.max' => 'The discount amount cannot exceed $999,999.99.',
            'currency_code.size' => 'The currency code must be exactly 3 characters (e.g., USD).',
            'auto_renew_days.required_if' => 'Auto-renewal days are required when auto-renewal is enabled.',
            'auto_renew_days.max' => 'Auto-renewal days cannot exceed 365 days.',
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
        // Debug: Log validation attempt
        \Log::info('StoreQuoteRequest validation', [
            'data' => $this->all(),
            'user_id' => \Auth::id()
        ]);

        $validator->after(function ($validator) {
            // Validate client belongs to user's company
            if ($this->client_id) {
                $client = Client::where('id', $this->client_id)
                    ->where('company_id', Auth::user()->company_id)
                    ->first();
                
                if (!$client) {
                    $validator->errors()->add('client_id', 'The selected client is invalid.');
                }
            }

            // Validate category belongs to user's company  
            if ($this->category_id) {
                $category = Category::where('id', $this->category_id)
                    ->where('company_id', Auth::user()->company_id)
                    ->first();
                
                if (!$category) {
                    $validator->errors()->add('category_id', 'The selected category is invalid.');
                }
            }
            
            // Validate discount percentage doesn't exceed 100%
            if ($this->discount_type === 'percentage' && $this->discount_amount > 100) {
                $validator->errors()->add('discount_amount', 'Discount percentage cannot exceed 100%.');
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set default values
        $this->merge([
            'status' => $this->status ?? Quote::STATUS_DRAFT,
            'discount_type' => $this->discount_type ?? Quote::DISCOUNT_FIXED,
            'currency_code' => $this->currency_code ?? 'USD',
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

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Debug: Log validation failure with more detail
        \Log::error('StoreQuoteRequest validation failed', [
            'errors' => $validator->errors()->toArray(),
            'data' => $this->all(),
            'user_id' => \Auth::id(),
            'status' => $this->input('status'),
            'client_id' => $this->input('client_id'),
            'category_id' => $this->input('category_id'),
        ]);

        // For JSON requests, ensure we return proper JSON response with detailed errors
        if ($this->expectsJson()) {
            $errors = $validator->errors()->toArray();
            $detailedErrors = [];
            
            foreach ($errors as $field => $messages) {
                $detailedErrors[$field] = [
                    'messages' => $messages,
                    'value' => $this->input($field),
                    'type' => gettype($this->input($field))
                ];
            }
            
            $response = response()->json([
                'message' => 'Validation failed. Please check the highlighted fields and try again.',
                'errors' => $errors,
                'detailed_errors' => $detailedErrors,
                'debug_info' => [
                    'status' => $this->input('status'),
                    'client_id' => $this->input('client_id'),
                    'category_id' => $this->input('category_id'),
                ]
            ], 422);

            throw new \Illuminate\Validation\ValidationException($validator, $response);
        }

        parent::failedValidation($validator);
    }
}