<?php

namespace App\Domains\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBundleRequest extends FormRequest
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
            'type' => 'required|in:fixed,flexible',
            'currency_code' => 'required|string|size:3',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'min_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'bundle_options' => 'nullable|json',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'products.*.is_optional' => 'boolean',
            'products.*.display_order' => 'integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Bundle name is required.',
            'products.required' => 'At least one product must be selected.',
            'products.min' => 'Bundle must contain at least one product.',
            'products.*.product_id.required' => 'Product selection is required.',
            'products.*.product_id.exists' => 'Selected product does not exist.',
            'products.*.quantity.required' => 'Product quantity is required.',
            'products.*.quantity.min' => 'Product quantity must be at least 1.',
            'currency_code.required' => 'Currency code is required.',
            'currency_code.size' => 'Currency code must be exactly 3 characters.',
            'bundle_options.json' => 'Bundle options must be valid JSON.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->has('is_active'),
            'currency_code' => $this->input('currency_code', 'USD'),
        ]);

        // Ensure products array has proper boolean values
        if ($this->has('products')) {
            $products = $this->input('products');
            foreach ($products as $key => $product) {
                $products[$key]['is_optional'] = isset($product['is_optional']) && $product['is_optional'];
                $products[$key]['display_order'] = $product['display_order'] ?? 0;
                $products[$key]['discount_percentage'] = $product['discount_percentage'] ?? 0;
            }
            $this->merge(['products' => $products]);
        }
    }
}
