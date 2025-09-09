<?php

namespace App\Http\Requests;

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
class UpdateQuoteRequest extends BaseQuoteRequest
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
        
        return array_merge($this->getCommonRules(), [
            // Update-specific fields
            'change_reason' => 'nullable|string|max:500',
            
            // Enhanced relationship validation for updates
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
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge($this->getCommonAttributes(), [
            'change_reason' => 'reason for change',
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge($this->getCommonMessages(), [
            'change_reason.max' => 'The reason for change cannot exceed 500 characters.',
        ]);
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

            // Use base class validations
            $this->validateDiscountPercentage($validator);
            $this->validateVoipConfiguration($validator);
            $this->validatePricingModel($validator);

            // Validate expiration dates are not in the past for sent quotes
            if ($quote && $quote->isSent()) {
                if ($this->expire_date && now()->gt($this->expire_date)) {
                    $validator->errors()->add('expire_date', 'Expiration date cannot be in the past for sent quotes.');
                }
                
                if ($this->valid_until && now()->gt($this->valid_until)) {
                    $validator->errors()->add('valid_until', 'Valid until date cannot be in the past for sent quotes.');
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

        // Prepare VoIP configuration using base class method
        $this->prepareVoipConfiguration();
    }
}