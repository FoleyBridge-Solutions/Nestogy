<?php

namespace App\Domains\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|in:percentage_discount,fixed_discount,fixed_price,bulk_pricing,buy_x_get_y',
            'product_id' => 'nullable|exists:products,id',
            'client_id' => 'nullable|exists:clients,id',
            'priority' => 'integer|min:0|max:100',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'fixed_price' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:1',
            'min_price' => 'nullable|numeric|min:0',
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:1',
            'conditions' => 'nullable|json',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Rule name is required.',
            'rule_type.required' => 'Rule type is required.',
            'rule_type.in' => 'Invalid rule type selected.',
            'product_id.exists' => 'Selected product does not exist.',
            'client_id.exists' => 'Selected client does not exist.',
            'valid_until.after' => 'End date must be after start date.',
            'discount_percentage.max' => 'Discount percentage cannot exceed 100%.',
            'priority.max' => 'Priority cannot exceed 100.',
            'conditions.json' => 'Conditions must be valid JSON.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $ruleType = $this->input('rule_type');

            // Validate required fields based on rule type
            switch ($ruleType) {
                case 'percentage_discount':
                    if (! $this->filled('discount_percentage')) {
                        $validator->errors()->add('discount_percentage', 'Discount percentage is required for this rule type.');
                    }
                    break;

                case 'fixed_discount':
                    if (! $this->filled('discount_amount')) {
                        $validator->errors()->add('discount_amount', 'Discount amount is required for this rule type.');
                    }
                    break;

                case 'fixed_price':
                    if (! $this->filled('fixed_price')) {
                        $validator->errors()->add('fixed_price', 'Fixed price is required for this rule type.');
                    }
                    break;

                case 'bulk_pricing':
                    if (! $this->filled('min_quantity')) {
                        $validator->errors()->add('min_quantity', 'Minimum quantity is required for bulk pricing.');
                    }
                    if (! $this->filled('discount_percentage')) {
                        $validator->errors()->add('discount_percentage', 'Discount percentage is required for bulk pricing.');
                    }
                    break;

                case 'buy_x_get_y':
                    if (! $this->filled('buy_quantity')) {
                        $validator->errors()->add('buy_quantity', 'Buy quantity is required for this rule type.');
                    }
                    if (! $this->filled('get_quantity')) {
                        $validator->errors()->add('get_quantity', 'Get quantity is required for this rule type.');
                    }
                    break;
            }
        });
    }
}
