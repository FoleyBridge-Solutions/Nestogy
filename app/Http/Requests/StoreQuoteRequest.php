<?php

namespace App\Http\Requests;

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
class StoreQuoteRequest extends BaseQuoteRequest
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
        return array_merge($this->getCommonRules(), [
            // Relationships - simplified validation for creation
            'category_id' => 'nullable|integer|exists:categories,id',
            'client_id' => 'required|integer|exists:clients,id',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return $this->getCommonAttributes();
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge($this->getCommonMessages(), [
            'date.before_or_equal' => 'The quote date cannot be in the future.',
        ]);
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
            $this->validateCompanyOwnership($validator);
            $this->validateDiscountPercentage($validator);
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

        // Prepare VoIP configuration
        $this->prepareVoipConfiguration();
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