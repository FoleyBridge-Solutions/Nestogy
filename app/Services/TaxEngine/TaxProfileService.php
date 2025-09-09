<?php

namespace App\Services\TaxEngine;

use App\Models\Category;
use App\Models\Product;
use App\Models\TaxProfile;
use App\Models\ProductTaxData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        
        if (!$profile) {
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
        
        if (!$profile) {
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
        
        foreach ($profile->required_fields as $field) {
            if (!isset($taxData[$field]) || empty($taxData[$field])) {
                $fieldDef = $profile->getFieldDefinition($field);
                $label = $fieldDef['label'] ?? $field;
                $errors[$field] = "{$label} is required for tax calculation";
            }
        }
        
        // Additional validation based on field types
        foreach ($taxData as $field => $value) {
            $fieldDef = $profile->getFieldDefinition($field);
            
            if ($fieldDef) {
                switch ($fieldDef['type']) {
                    case 'number':
                        if (!is_numeric($value)) {
                            $errors[$field] = "{$fieldDef['label']} must be a number";
                        } elseif (isset($fieldDef['min']) && $value < $fieldDef['min']) {
                            $errors[$field] = "{$fieldDef['label']} must be at least {$fieldDef['min']}";
                        }
                        break;
                        
                    case 'address':
                        if (!is_array($value) || !isset($value['state'])) {
                            $errors[$field] = "{$fieldDef['label']} must include at least a state";
                        }
                        break;
                }
            }
        }
        
        return $errors;
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
        if ($profileId && !$product->tax_profile_id) {
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