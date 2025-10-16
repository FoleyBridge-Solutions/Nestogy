<?php

namespace App\Domains\Financial\Services\TaxEngine;

use App\Domains\Financial\Models\Category;
use App\Domains\Product\Models\Product;
use App\Domains\Tax\Models\ProductTaxData;
use App\Domains\Tax\Models\TaxProfile;
use Illuminate\Support\Collection;

/**
 * Tax Profile Service
 *
 * Manages tax profiles and their application to products/services.
 * Handles profile selection, field requirements, and validation.
 */
class TaxProfileService
{
    protected ?int $companyId = null;

    protected array $profileCache = [];

    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId;
    }

    /**
     * Set the company ID for tax profile operations
     */
    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    /**
     * Ensure company ID is set before operations
     */
    protected function ensureCompanyId(): void
    {
        if ($this->companyId === null) {
            throw new \InvalidArgumentException('Company ID must be set before using tax profile operations. Use setCompanyId() method.');
        }
    }

    /**
     * Get tax profile for a category or product
     */
    public function getProfile($categoryId = null, $productId = null, $categoryType = null): ?TaxProfile
    {
        $this->ensureCompanyId();
        // Try to get profile from product first
        if ($productId) {
            $product = Product::find($productId);
            if ($product && $product->tax_profile_id) {
                return $this->loadProfile($product->tax_profile_id);
            }

            // Use product's category if available
            if ($product && $product->category_id) {
                $categoryId = $product->category_id;
            }
        }

        // Try to find profile by category
        if ($categoryId) {
            $profile = $this->findProfileByCategory($categoryId);
            if ($profile) {
                return $profile;
            }
        }

        // Try to find profile by category type
        if ($categoryType) {
            $profile = $this->findProfileByType($categoryType);
            if ($profile) {
                return $profile;
            }
        }

        // Return default profile
        return $this->getDefaultProfile();
    }

    /**
     * Find profile by category ID
     */
    protected function findProfileByCategory(int $categoryId): ?TaxProfile
    {
        // Check if there's a specific profile for this category
        $profile = TaxProfile::where('company_id', $this->companyId)
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->ordered()
            ->first();

        if ($profile) {
            return $profile;
        }

        // Try to determine profile by category name
        $category = Category::find($categoryId);
        if ($category) {
            return $this->findProfileByType($this->determineCategoryType($category));
        }

        return null;
    }

    /**
     * Find profile by type
     */
    protected function findProfileByType(string $type): ?TaxProfile
    {
        return TaxProfile::where('company_id', $this->companyId)
            ->where('profile_type', $this->mapCategoryTypeToProfileType($type))
            ->where('is_active', true)
            ->ordered()
            ->first();
    }

    /**
     * Get default profile
     */
    protected function getDefaultProfile(): TaxProfile
    {
        $profile = TaxProfile::where('company_id', $this->companyId)
            ->where('profile_type', TaxProfile::TYPE_GENERAL)
            ->where('is_active', true)
            ->first();

        if (! $profile) {
            // Create default profile if it doesn't exist
            $profile = TaxProfile::create([
                'company_id' => $this->companyId,
                'profile_type' => TaxProfile::TYPE_GENERAL,
                'name' => 'General Tax Profile',
                'description' => 'Default tax profile for general products and services',
                'required_fields' => [],
                'tax_types' => ['sales_tax'],
                'calculation_engine' => TaxProfile::ENGINE_SERVICE_TAX,
                'is_active' => true,
                'priority' => 999,
            ]);
        }

        return $profile;
    }

    /**
     * Determine category type from category
     */
    protected function determineCategoryType(Category $category): string
    {
        $name = strtolower($category->name);

        // Check for VoIP/Telecom keywords
        $voipKeywords = ['voip', 'pbx', 'sip', 'telecom', 'phone', 'calling', 'toll'];
        foreach ($voipKeywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                return 'voip';
            }
        }

        // Check for digital service keywords
        $digitalKeywords = ['cloud', 'saas', 'software', 'hosting', 'digital', 'online'];
        foreach ($digitalKeywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                return 'digital_services';
            }
        }

        // Check for equipment keywords
        $equipmentKeywords = ['equipment', 'hardware', 'device', 'router', 'switch'];
        foreach ($equipmentKeywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                return 'equipment';
            }
        }

        // Check for professional service keywords
        $professionalKeywords = ['consult', 'professional', 'service', 'support', 'maintenance'];
        foreach ($professionalKeywords as $keyword) {
            if (strpos($name, $keyword) !== false) {
                return 'professional';
            }
        }

        return 'general';
    }

    /**
     * Map category type to profile type
     */
    protected function mapCategoryTypeToProfileType(string $categoryType): string
    {
        $mapping = [
            'voip' => TaxProfile::TYPE_VOIP,
            'hosted_pbx' => TaxProfile::TYPE_VOIP,
            'sip_trunking' => TaxProfile::TYPE_VOIP,
            'telecommunications' => TaxProfile::TYPE_VOIP,
            'cloud_services' => TaxProfile::TYPE_DIGITAL_SERVICES,
            'saas' => TaxProfile::TYPE_DIGITAL_SERVICES,
            'software' => TaxProfile::TYPE_DIGITAL_SERVICES,
            'digital_services' => TaxProfile::TYPE_DIGITAL_SERVICES,
            'equipment' => TaxProfile::TYPE_EQUIPMENT,
            'hardware' => TaxProfile::TYPE_EQUIPMENT,
            'professional' => TaxProfile::TYPE_PROFESSIONAL,
            'professional_services' => TaxProfile::TYPE_PROFESSIONAL,
            'consulting' => TaxProfile::TYPE_PROFESSIONAL,
        ];

        return $mapping[$categoryType] ?? TaxProfile::TYPE_GENERAL;
    }

    /**
     * Load profile by ID with caching
     */
    protected function loadProfile(int $profileId): ?TaxProfile
    {
        if (isset($this->profileCache[$profileId])) {
            return $this->profileCache[$profileId];
        }

        $profile = TaxProfile::find($profileId);

        if ($profile) {
            $this->profileCache[$profileId] = $profile;
        }

        return $profile;
    }

    /**
     * Get required fields for a product/category
     */
    public function getRequiredFields($categoryId = null, $productId = null, $categoryType = null): array
    {
        $this->ensureCompanyId();
        $profile = $this->getProfile($categoryId, $productId, $categoryType);

        if (! $profile) {
            return [];
        }

        $fields = [];

        foreach ($profile->required_fields as $fieldName) {
            $fieldDef = $profile->getFieldDefinition($fieldName);

            if ($fieldDef) {
                $fields[$fieldName] = $fieldDef;
            }
        }

        return $fields;
    }

    /**
     * Validate tax data against profile requirements
     */
    public function validateTaxData(array $taxData, TaxProfile $profile): array
    {
        $errors = [];

        $errors = array_merge($errors, $this->validateRequiredFields($taxData, $profile));
        $errors = array_merge($errors, $this->validateFieldTypes($taxData, $profile));

        return $errors;
    }

    /**
     * Validate required fields
     */
    protected function validateRequiredFields(array $taxData, TaxProfile $profile): array
    {
        $errors = [];

        foreach ($profile->required_fields as $field) {
            if (! isset($taxData[$field]) || empty($taxData[$field])) {
                $fieldDef = $profile->getFieldDefinition($field);
                $label = $fieldDef['label'] ?? $field;
                $errors[$field] = "{$label} is required for tax calculation";
            }
        }

        return $errors;
    }

    /**
     * Validate field types
     */
    protected function validateFieldTypes(array $taxData, TaxProfile $profile): array
    {
        $errors = [];

        foreach ($taxData as $field => $value) {
            $fieldDef = $profile->getFieldDefinition($field);

            if ($fieldDef) {
                $fieldError = $this->validateFieldByType($field, $value, $fieldDef);
                if ($fieldError) {
                    $errors[$field] = $fieldError;
                }
            }
        }

        return $errors;
    }

    /**
     * Validate field by type
     */
    protected function validateFieldByType(string $field, $value, array $fieldDef): ?string
    {
        switch ($fieldDef['type']) {
            case 'number':
                return $this->validateNumberField($value, $fieldDef);

            case 'address':
                return $this->validateAddressField($value, $fieldDef);
        }

        return null;
    }

    /**
     * Validate number field
     */
    protected function validateNumberField($value, array $fieldDef): ?string
    {
        if (! is_numeric($value)) {
            return "{$fieldDef['label']} must be a number";
        }

        if (isset($fieldDef['min']) && $value < $fieldDef['min']) {
            return "{$fieldDef['label']} must be at least {$fieldDef['min']}";
        }

        return null;
    }

    /**
     * Validate address field
     */
    protected function validateAddressField($value, array $fieldDef): ?string
    {
        if (! is_array($value) || ! isset($value['state'])) {
            return "{$fieldDef['label']} must include at least a state";
        }

        return null;
    }

    /**
     * Store tax data for a product
     */
    public function storeTaxData(int $productId, array $taxData, ?int $profileId = null): ProductTaxData
    {
        $product = Product::findOrFail($productId);

        // Get or create tax data record
        $productTaxData = ProductTaxData::firstOrNew([
            'product_id' => $productId,
            'company_id' => $product->company_id,
        ]);

        $productTaxData->tax_data = $taxData;
        $productTaxData->tax_profile_id = $profileId;
        $productTaxData->save();

        // Update product with tax profile reference
        if ($profileId && ! $product->tax_profile_id) {
            $product->tax_profile_id = $profileId;
            $product->save();
        }

        return $productTaxData;
    }

    /**
     * Get tax data for a product
     */
    public function getTaxData(int $productId): ?ProductTaxData
    {
        $this->ensureCompanyId();

        return ProductTaxData::where('product_id', $productId)
            ->where('company_id', $this->companyId)
            ->first();
    }

    /**
     * Get all available profiles for a company
     */
    public function getAvailableProfiles(): Collection
    {
        $this->ensureCompanyId();

        return TaxProfile::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->ordered()
            ->get();
    }

    /**
     * Create default profiles for a company if they don't exist
     */
    public function ensureDefaultProfiles(): void
    {
        $this->ensureCompanyId();
        $existingCount = TaxProfile::where('company_id', $this->companyId)->count();

        if ($existingCount === 0) {
            TaxProfile::createDefaultProfiles($this->companyId);
        }
    }

    /**
     * Get profile summary for display
     */
    public function getProfileSummary(TaxProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'type' => $profile->profile_type,
            'description' => $profile->description,
            'tax_types' => $profile->tax_types,
            'required_fields' => array_map(function ($field) use ($profile) {
                $def = $profile->getFieldDefinition($field);

                return [
                    'name' => $field,
                    'label' => $def['label'] ?? $field,
                    'type' => $def['type'] ?? 'text',
                    'help' => $def['help'] ?? null,
                ];
            }, $profile->required_fields),
            'is_voip' => $profile->isVoIPProfile(),
        ];
    }
}
