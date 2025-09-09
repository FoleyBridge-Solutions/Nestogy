<?php

namespace App\Domains\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'sku' => 'nullable|string|max:255|unique:products,sku,NULL,id,company_id,' . auth()->user()->company_id,
            'type' => 'required|in:product,service',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'tax_inclusive' => 'boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'unit_type' => 'required|in:hours,units,days,weeks,months,years,fixed,subscription',
            'billing_model' => 'required|in:one_time,subscription,usage_based,hybrid',
            'billing_cycle' => 'required|in:one_time,hourly,daily,weekly,monthly,quarterly,semi_annually,annually',
            'billing_interval' => 'integer|min:1',
            'track_inventory' => 'boolean',
            'current_stock' => 'integer|min:0',
            'reserved_stock' => 'integer|min:0',
            'min_stock_level' => 'integer|min:0',
            'max_quantity_per_order' => 'nullable|integer|min:1',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_taxable' => 'boolean',
            'allow_discounts' => 'boolean',
            'requires_approval' => 'boolean',
            'pricing_model' => 'required|in:fixed,tiered,volume,usage,value,custom',
            'pricing_tiers' => 'nullable|json',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'usage_rate' => 'nullable|numeric|min:0',
            'usage_included' => 'nullable|integer|min:0',
            'features' => 'nullable|json',
            'tags' => 'nullable|json',
            'metadata' => 'nullable|json',
            'custom_fields' => 'nullable|json',
            'image_url' => 'nullable|url',
            'gallery_urls' => 'nullable|json',
            'sort_order' => 'integer|min:0',
            'short_description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'base_price.required' => 'Base price is required.',
            'base_price.min' => 'Base price must be at least 0.',
            'currency_code.required' => 'Currency code is required.',
            'currency_code.size' => 'Currency code must be exactly 3 characters.',
            'sku.unique' => 'This SKU is already in use.',
            'category_id.exists' => 'Selected category does not exist.',
            'pricing_tiers.json' => 'Pricing tiers must be valid JSON.',
            'features.json' => 'Features must be valid JSON.',
            'tags.json' => 'Tags must be valid JSON.',
            'metadata.json' => 'Metadata must be valid JSON.',
            'custom_fields.json' => 'Custom fields must be valid JSON.',
            'gallery_urls.json' => 'Gallery URLs must be valid JSON.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_active' => $this->has('is_active'),
            'is_featured' => $this->has('is_featured'),
            'is_taxable' => $this->has('is_taxable') ? true : false,
            'allow_discounts' => $this->has('allow_discounts') ? true : false,
            'requires_approval' => $this->has('requires_approval'),
            'track_inventory' => $this->has('track_inventory'),
            'tax_inclusive' => $this->has('tax_inclusive'),
            'currency_code' => $this->input('currency_code', 'USD'),
            'billing_interval' => $this->input('billing_interval', 1),
            'current_stock' => $this->input('current_stock', 0),
            'reserved_stock' => $this->input('reserved_stock', 0),
            'min_stock_level' => $this->input('min_stock_level', 0),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}