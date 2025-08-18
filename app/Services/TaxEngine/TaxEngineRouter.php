<?php

namespace App\Services\TaxEngine;

use App\Models\Category;
use App\Models\Product;
use App\Models\TaxCategory;
use App\Models\TaxProfile;
use App\Services\ServiceTaxCalculator;
use App\Services\VoIPTaxService;
use App\Services\TaxEngine\VatComplyApiClient;
use App\Services\TaxEngine\NominatimApiClient;
use App\Services\TaxEngine\CensusBureauApiClient;
use App\Services\TaxEngine\FccApiClient;
use App\Services\TaxEngine\TaxCloudApiClient;
use App\Models\TaxCalculation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Tax Engine Router
 * 
 * Routes tax calculations to appropriate engines based on product/service category
 * and aggregates results from multiple tax calculation services.
 */
class TaxEngineRouter
{
    protected ?int $companyId = null;
    protected array $engines = [];
    protected array $taxProfiles = [];
    protected array $apiClients = [];
    
    /**
     * Tax engine type constants
     */
    const ENGINE_VOIP = 'voip';
    const ENGINE_GENERAL = 'general';
    const ENGINE_DIGITAL = 'digital';
    const ENGINE_EQUIPMENT = 'equipment';
    
    /**
     * Category to engine mapping
     */
    const CATEGORY_ENGINE_MAP = [
        // Telecommunications categories
        'voip' => self::ENGINE_VOIP,
        'hosted_pbx' => self::ENGINE_VOIP,
        'sip_trunking' => self::ENGINE_VOIP,
        'did_numbers' => self::ENGINE_VOIP,
        'long_distance' => self::ENGINE_VOIP,
        'international' => self::ENGINE_VOIP,
        'local_calling' => self::ENGINE_VOIP,
        'toll_free' => self::ENGINE_VOIP,
        'unified_communications' => self::ENGINE_VOIP,
        'telecommunications' => self::ENGINE_VOIP,
        
        // Digital services
        'cloud_services' => self::ENGINE_DIGITAL,
        'saas' => self::ENGINE_DIGITAL,
        'software' => self::ENGINE_DIGITAL,
        'hosting' => self::ENGINE_DIGITAL,
        'data_services' => self::ENGINE_DIGITAL,
        'internet_access' => self::ENGINE_DIGITAL,
        
        // Equipment
        'equipment' => self::ENGINE_EQUIPMENT,
        'hardware' => self::ENGINE_EQUIPMENT,
        'devices' => self::ENGINE_EQUIPMENT,
        
        // General services
        'professional_services' => self::ENGINE_GENERAL,
        'installation' => self::ENGINE_GENERAL,
        'maintenance' => self::ENGINE_GENERAL,
        'consulting' => self::ENGINE_GENERAL,
        'support' => self::ENGINE_GENERAL,
    ];
    
    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId;
        if ($companyId !== null) {
            $this->initializeEngines();
        }
    }
    
    /**
     * Set the company ID for tax routing operations
     */
    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;
        $this->initializeEngines();
        return $this;
    }
    
    /**
     * Ensure company ID is set before operations
     */
    protected function ensureCompanyId(): void
    {
        if ($this->companyId === null) {
            throw new \InvalidArgumentException('Company ID must be set before using tax engine operations. Use setCompanyId() method.');
        }
    }
    
    /**
     * Initialize tax calculation engines and API clients
     */
    protected function initializeEngines(): void
    {
        // Initialize VoIP tax engine
        $voipEngine = new VoIPTaxService();
        $voipEngine->setCompanyId($this->companyId);
        $this->engines[self::ENGINE_VOIP] = $voipEngine;
        
        // Initialize general service tax calculator
        $this->engines[self::ENGINE_GENERAL] = new ServiceTaxCalculator($this->companyId);
        
        // Digital and equipment engines use the general calculator with specific configurations
        $this->engines[self::ENGINE_DIGITAL] = new ServiceTaxCalculator($this->companyId);
        $this->engines[self::ENGINE_EQUIPMENT] = new ServiceTaxCalculator($this->companyId);
        
        // Initialize API clients for enhanced tax calculations
        $this->initializeApiClients();
    }
    
    /**
     * Initialize API clients for tax calculations
     */
    protected function initializeApiClients(): void
    {
        try {
            // VAT compliance for international taxes
            $this->apiClients['vat_comply'] = new VatComplyApiClient($this->companyId);
            
            // Nominatim for geocoding and address resolution
            $this->apiClients['nominatim'] = new NominatimApiClient($this->companyId);
            
            // US Census Bureau for jurisdiction detection
            $this->apiClients['census'] = new CensusBureauApiClient($this->companyId);
            
            // FCC for telecommunications taxes
            $this->apiClients['fcc'] = new FccApiClient($this->companyId);
            
            // TaxCloud removed - using local data-driven tax system
            
            Log::info('Tax API clients initialized successfully', [
                'company_id' => $this->companyId,
                'clients' => array_keys($this->apiClients)
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to initialize tax API clients', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
            
            // Continue without API clients - fallback to basic engines
            $this->apiClients = [];
        }
    }
    
    /**
     * Calculate taxes for multiple items in bulk for better performance
     */
    public function calculateBulkTaxes(array $items, string $calculationType = TaxCalculation::TYPE_PREVIEW): array
    {
        $startTime = microtime(true);
        $this->ensureCompanyId();
        
        $results = [];
        $groupedItems = [];
        
        // Group items by engine type to batch similar calculations
        foreach ($items as $index => $params) {
            $categoryId = $params['category_id'] ?? null;
            $categoryType = $params['category_type'] ?? null;
            $engineType = $this->determineEngineType($categoryId, $categoryType);
            
            $groupedItems[$engineType][] = [
                'index' => $index,
                'params' => $params,
                'category_id' => $categoryId,
                'category_type' => $categoryType,
            ];
        }
        
        // Process each engine group
        foreach ($groupedItems as $engineType => $engineItems) {
            $engineResults = $this->calculateBulkForEngine($engineType, $engineItems, $calculationType);
            
            // Merge results back to original order
            foreach ($engineResults as $result) {
                $results[$result['original_index']] = $result;
            }
        }
        
        // Sort results by original index
        ksort($results);
        
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        return [
            'bulk_results' => array_values($results),
            'total_items' => count($items),
            'calculation_time_ms' => round($executionTime, 2),
            'items_per_second' => round(count($items) / ($executionTime / 1000), 2),
            'engine_breakdown' => array_map('count', $groupedItems),
        ];
    }
    
    /**
     * Calculate taxes for items using a specific engine
     */
    protected function calculateBulkForEngine(string $engineType, array $engineItems, string $calculationType): array
    {
        $results = [];
        
        try {
            $engine = $this->engines[$engineType] ?? $this->engines[self::ENGINE_GENERAL];
            
            if ($engineType === self::ENGINE_VOIP) {
                // VoIP engine processes items individually but we can optimize address geocoding
                $addressCache = [];
                
                foreach ($engineItems as $item) {
                    $params = $item['params'];
                    $customerAddress = $params['customer_address'] ?? [];
                    
                    // Cache address enhancement
                    $addressKey = md5(json_encode($customerAddress));
                    if (!isset($addressCache[$addressKey])) {
                        $addressCache[$addressKey] = $this->enhanceCustomerAddress($customerAddress);
                    }
                    $enhancedAddress = $addressCache[$addressKey];
                    
                    // Update params with enhanced address
                    $calculationParams = $this->prepareCalculationParams(
                        $engineType,
                        $params['base_price'] ?? 0,
                        $params['quantity'] ?? 1,
                        $params['tax_data'] ?? [],
                        $enhancedAddress,
                        $params['customer_id'] ?? null,
                        $this->getTaxProfile($item['category_id'], $item['category_type'])
                    );
                    
                    $result = $engine->calculateTaxes($calculationParams);
                    $result['original_index'] = $item['index'];
                    $result['engine_used'] = $engineType;
                    
                    $results[] = $result;
                }
            } else {
                // For ServiceTaxCalculator, we can process items in batches
                $allItems = collect($engineItems)->map(function ($item) {
                    $params = $item['params'];
                    return (object)[
                        'id' => 'bulk_' . $item['index'],
                        'name' => $params['name'] ?? 'Product/Service',
                        'price' => $params['base_price'] ?? 0,
                        'quantity' => $params['quantity'] ?? 1,
                        'subtotal' => ($params['base_price'] ?? 0) * ($params['quantity'] ?? 1),
                        'service_type' => $params['tax_data']['service_type'] ?? 'general',
                        'client_id' => $params['customer_id'] ?? null,
                        'original_index' => $item['index'],
                        'original_params' => $item['params'],
                    ];
                });
                
                $calculations = $engine->calculate(
                    $allItems,
                    $engineItems[0]['params']['tax_data']['service_type'] ?? 'general',
                    $engineItems[0]['params']['customer_address'] ?? []
                );
                
                foreach ($calculations as $calculation) {
                    $calculation['original_index'] = $allItems->firstWhere('id', $calculation['item_id'])->original_index;
                    $calculation['engine_used'] = $engineType;
                    $results[] = $calculation;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Bulk tax calculation error for engine: ' . $engineType, [
                'error' => $e->getMessage(),
                'items_count' => count($engineItems),
            ]);
            
            // Return zero tax results for failed calculations
            foreach ($engineItems as $item) {
                $results[] = [
                    'original_index' => $item['index'],
                    'base_amount' => $item['params']['base_price'] ?? 0,
                    'total_tax_amount' => 0,
                    'final_amount' => $item['params']['base_price'] ?? 0,
                    'tax_breakdown' => [],
                    'error' => 'Calculation failed: ' . $e->getMessage(),
                    'engine_used' => $engineType,
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Calculate comprehensive taxes for a product/service
     */
    public function calculateTaxes(array $params, $calculable = null, string $calculationType = TaxCalculation::TYPE_PREVIEW): array
    {
        $startTime = microtime(true);
        $this->ensureCompanyId();
        
        // Extract parameters
        $basePrice = $params['base_price'] ?? 0;
        $quantity = $params['quantity'] ?? 1;
        $categoryId = $params['category_id'] ?? null;
        $categoryType = $params['category_type'] ?? null;
        $taxData = $params['tax_data'] ?? [];
        $customerAddress = $params['customer_address'] ?? [];
        $customerId = $params['customer_id'] ?? null;
        $vatNumber = $params['vat_number'] ?? null;
        $customerCountry = $params['customer_country'] ?? null;
        
        // Enhance customer address with geocoding if needed
        $enhancedAddress = $this->enhanceCustomerAddress($customerAddress);
        
        // Determine which engine(s) to use
        $engineType = $this->determineEngineType($categoryId, $categoryType);
        
        // Get tax profile for this category
        $taxProfile = $this->getTaxProfile($categoryId, $categoryType);
        
        // Prepare calculation parameters
        $calculationParams = $this->prepareCalculationParams(
            $engineType,
            $basePrice,
            $quantity,
            $taxData,
            $enhancedAddress,
            $customerId,
            $taxProfile
        );
        
        // Perform base calculation using appropriate engine
        $result = $this->performCalculation($engineType, $calculationParams);
        
        // Enhance with API-based calculations
        $result = $this->enhanceWithApiCalculations($result, $calculationParams, $engineType, $vatNumber, $customerCountry);
        
        // Add metadata to result
        $result['engine_used'] = $engineType;
        $result['tax_profile'] = $taxProfile ? [
            'id' => $taxProfile['id'] ?? null,
            'name' => $taxProfile['name'] ?? null,
            'category_type' => $categoryType,
        ] : null;
        
        $result['api_enhancements'] = $this->getApiEnhancementStatus();
        
        // Calculate execution time
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $result['calculation_time_ms'] = round($executionTime, 2);
        
        // Create audit trail record
        $this->createAuditTrail($params, $result, $calculable, $calculationType, $executionTime);
        
        return $result;
    }
    
    /**
     * Determine which tax engine to use based on category
     */
    protected function determineEngineType(?int $categoryId, ?string $categoryType): string
    {
        // Check category type first
        if ($categoryType && isset(self::CATEGORY_ENGINE_MAP[$categoryType])) {
            return self::CATEGORY_ENGINE_MAP[$categoryType];
        }
        
        // If category ID provided, look up category details with caching
        if ($categoryId) {
            $cacheKey = "category_engine_type_company_{$this->companyId}_cat_{$categoryId}";
            $engineType = Cache::remember($cacheKey, 1800, function () use ($categoryId) {
                $category = Category::find($categoryId);
                if ($category) {
                    // Check category name for keywords
                    $categoryName = strtolower($category->name);
                    foreach (self::CATEGORY_ENGINE_MAP as $keyword => $engine) {
                        if (strpos($categoryName, $keyword) !== false) {
                            return $engine;
                        }
                    }
                }
                return self::ENGINE_GENERAL;
            });
            
            return $engineType;
        }
        
        // Default to general engine
        return self::ENGINE_GENERAL;
    }
    
    /**
     * Get tax profile for a category
     */
    protected function getTaxProfile(?int $categoryId, ?string $categoryType): ?array
    {
        // Check memory cache first
        $cacheKey = "tax_profile_{$categoryId}_{$categoryType}";
        if (isset($this->taxProfiles[$cacheKey])) {
            return $this->taxProfiles[$cacheKey];
        }
        
        // Check Laravel cache
        $laravelCacheKey = "tax_profile_company_{$this->companyId}_{$categoryId}_{$categoryType}";
        $profile = Cache::remember($laravelCacheKey, 3600, function () use ($categoryId, $categoryType) {
            // Try to load actual TaxProfile from database first
            if ($categoryId || $categoryType) {
                $taxProfile = $this->loadDatabaseProfile($categoryId, $categoryType);
                if ($taxProfile) {
                    return [
                        'id' => $taxProfile->id,
                        'name' => $taxProfile->name,
                        'category_type' => $categoryType,
                        'tax_types' => $taxProfile->tax_types,
                        'required_fields' => $taxProfile->required_fields,
                        'calculation_engine' => $this->mapProfileEngineToConstant($taxProfile->calculation_engine),
                        'field_definitions' => $taxProfile->field_definitions,
                        'priority' => $taxProfile->priority,
                        'is_database_profile' => true,
                    ];
                }
            }
            
            // Fallback to default profile
            return $this->getDefaultTaxProfile($categoryType);
        });
        
        // Cache in memory for this request
        $this->taxProfiles[$cacheKey] = $profile;
        
        return $profile;
    }
    
    /**
     * Load actual TaxProfile from database
     */
    protected function loadDatabaseProfile(?int $categoryId, ?string $categoryType): ?TaxProfile
    {
        $query = TaxProfile::where('company_id', $this->companyId)
            ->where('is_active', true);
        
        // Try exact category match first
        if ($categoryId) {
            $profile = $query->clone()->where('category_id', $categoryId)->ordered()->first();
            if ($profile) {
                return $profile;
            }
        }
        
        // Try profile type match
        if ($categoryType) {
            $profileType = $this->mapCategoryTypeToProfileType($categoryType);
            $profile = $query->clone()->where('profile_type', $profileType)->ordered()->first();
            if ($profile) {
                return $profile;
            }
        }
        
        return null;
    }
    
    /**
     * Map profile engine names to constants
     */
    protected function mapProfileEngineToConstant(string $engine): string
    {
        $mapping = [
            'VoIPTaxService' => self::ENGINE_VOIP,
            'ServiceTaxCalculator' => self::ENGINE_GENERAL,
        ];
        
        return $mapping[$engine] ?? self::ENGINE_GENERAL;
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
     * Get default tax profile based on category type
     */
    protected function getDefaultTaxProfile(?string $categoryType): array
    {
        $profiles = [
            'voip' => [
                'id' => 'voip_default',
                'name' => 'VoIP Services Tax Profile',
                'tax_types' => ['federal_excise', 'usf', 'e911', 'state_telecom', 'local_telecom'],
                'required_fields' => ['line_count', 'minutes', 'service_address'],
                'calculation_engine' => self::ENGINE_VOIP,
            ],
            'hosted_pbx' => [
                'id' => 'hosted_pbx_default',
                'name' => 'Hosted PBX Tax Profile',
                'tax_types' => ['federal_excise', 'usf', 'e911', 'state_telecom', 'local_telecom'],
                'required_fields' => ['line_count', 'extensions', 'service_address'],
                'calculation_engine' => self::ENGINE_VOIP,
            ],
            'cloud_services' => [
                'id' => 'cloud_default',
                'name' => 'Cloud Services Tax Profile',
                'tax_types' => ['sales_tax', 'digital_services_tax'],
                'required_fields' => ['data_usage', 'storage_amount'],
                'calculation_engine' => self::ENGINE_DIGITAL,
            ],
            'saas' => [
                'id' => 'saas_default',
                'name' => 'SaaS Tax Profile',
                'tax_types' => ['sales_tax', 'digital_services_tax', 'software_tax'],
                'required_fields' => ['user_count', 'features'],
                'calculation_engine' => self::ENGINE_DIGITAL,
            ],
            'equipment' => [
                'id' => 'equipment_default',
                'name' => 'Equipment Tax Profile',
                'tax_types' => ['sales_tax', 'use_tax', 'recycling_fee'],
                'required_fields' => ['weight', 'dimensions', 'manufacturer'],
                'calculation_engine' => self::ENGINE_EQUIPMENT,
            ],
            'professional_services' => [
                'id' => 'professional_default',
                'name' => 'Professional Services Tax Profile',
                'tax_types' => ['service_tax', 'professional_tax'],
                'required_fields' => ['hours', 'service_location'],
                'calculation_engine' => self::ENGINE_GENERAL,
            ],
        ];
        
        return $profiles[$categoryType] ?? [
            'id' => 'general_default',
            'name' => 'General Tax Profile',
            'tax_types' => ['sales_tax'],
            'required_fields' => [],
            'calculation_engine' => self::ENGINE_GENERAL,
        ];
    }
    
    /**
     * Prepare calculation parameters based on engine type
     */
    protected function prepareCalculationParams(
        string $engineType,
        float $basePrice,
        int $quantity,
        array $taxData,
        array $customerAddress,
        ?int $customerId,
        ?array $taxProfile
    ): array {
        $params = [
            'amount' => $basePrice * $quantity,
            'base_price' => $basePrice,
            'quantity' => $quantity,
            'service_address' => $customerAddress,
            'client_id' => $customerId,
            'calculation_date' => now(),
        ];
        
        // Add engine-specific parameters
        switch ($engineType) {
            case self::ENGINE_VOIP:
                $params['service_type'] = $taxData['service_type'] ?? 'voip_fixed';
                $params['line_count'] = $taxData['line_count'] ?? 1;
                $params['minutes'] = $taxData['minutes'] ?? 0;
                $params['extensions'] = $taxData['extensions'] ?? 0;
                break;
                
            case self::ENGINE_DIGITAL:
                $params['service_type'] = 'cloud';
                $params['data_usage'] = $taxData['data_usage'] ?? 0;
                $params['storage_amount'] = $taxData['storage_amount'] ?? 0;
                $params['user_count'] = $taxData['user_count'] ?? 1;
                break;
                
            case self::ENGINE_EQUIPMENT:
                $params['service_type'] = 'equipment';
                $params['weight'] = $taxData['weight'] ?? 0;
                $params['dimensions'] = $taxData['dimensions'] ?? [];
                $params['manufacturer'] = $taxData['manufacturer'] ?? '';
                break;
                
            case self::ENGINE_GENERAL:
            default:
                $params['service_type'] = 'general';
                $params['hours'] = $taxData['hours'] ?? 0;
                $params['service_location'] = $taxData['service_location'] ?? $customerAddress;
                break;
        }
        
        return $params;
    }
    
    /**
     * Perform tax calculation using the appropriate engine
     */
    protected function performCalculation(string $engineType, array $params): array
    {
        try {
            $engine = $this->engines[$engineType] ?? $this->engines[self::ENGINE_GENERAL];
            
            if ($engineType === self::ENGINE_VOIP) {
                // VoIP engine has different method signature
                return $engine->calculateTaxes($params);
            } else {
                // ServiceTaxCalculator expects items collection
                $items = collect([
                    (object)[
                        'id' => 'temp_' . uniqid(),
                        'name' => 'Product/Service',
                        'price' => $params['base_price'],
                        'quantity' => $params['quantity'],
                        'subtotal' => $params['amount'],
                        'service_type' => $params['service_type'] ?? 'general',
                        'client_id' => $params['client_id'] ?? null,
                    ]
                ]);
                
                $calculations = $engine->calculate(
                    $items,
                    $params['service_type'] ?? 'general',
                    $params['service_address'] ?? null
                );
                
                // Get summary
                $summary = $engine->getTaxSummary($calculations);
                
                // Format result to match VoIP engine output
                $calculation = $calculations[0] ?? [];
                
                return [
                    'base_amount' => $params['amount'],
                    'service_type' => $params['service_type'] ?? 'general',
                    'calculation_date' => now()->toISOString(),
                    'tax_breakdown' => $calculation['tax_breakdown'] ?? [],
                    'exemptions_applied' => $calculation['exemptions_applied'] ?? [],
                    'total_tax_amount' => $calculation['total_tax_amount'] ?? 0,
                    'final_amount' => $params['amount'] + ($calculation['total_tax_amount'] ?? 0),
                    'effective_tax_rate' => $summary['effective_tax_rate'] ?? 0,
                    'jurisdictions' => [],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Tax calculation error', [
                'engine' => $engineType,
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return zero tax on error
            return [
                'base_amount' => $params['amount'],
                'service_type' => $params['service_type'] ?? 'general',
                'calculation_date' => now()->toISOString(),
                'tax_breakdown' => [],
                'exemptions_applied' => [],
                'total_tax_amount' => 0,
                'final_amount' => $params['amount'],
                'effective_tax_rate' => 0,
                'jurisdictions' => [],
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get required fields for a category
     */
    public function getRequiredFields(string $categoryType): array
    {
        $this->ensureCompanyId();
        $profile = $this->getDefaultTaxProfile($categoryType);
        return $profile['required_fields'] ?? [];
    }
    
    /**
     * Get applicable tax types for a category
     */
    public function getApplicableTaxTypes(string $categoryType): array
    {
        $this->ensureCompanyId();
        $profile = $this->getDefaultTaxProfile($categoryType);
        return $profile['tax_types'] ?? ['sales_tax'];
    }
    
    /**
     * Enhance customer address with geocoding data
     */
    protected function enhanceCustomerAddress(array $customerAddress): array
    {
        if (empty($customerAddress) || !isset($this->apiClients['nominatim'])) {
            return $customerAddress;
        }
        
        try {
            // Build address string for geocoding
            $addressParts = array_filter([
                $customerAddress['address_line_1'] ?? $customerAddress['street'] ?? '',
                $customerAddress['city'] ?? '',
                $customerAddress['state'] ?? '',
                $customerAddress['postal_code'] ?? $customerAddress['zip'] ?? '',
                $customerAddress['country'] ?? 'US',
            ]);
            
            if (empty($addressParts)) {
                return $customerAddress;
            }
            
            $addressString = implode(', ', $addressParts);
            $geocoded = $this->apiClients['nominatim']->geocode($addressString);
            
            if ($geocoded['found'] && !empty($geocoded['results'])) {
                $result = $geocoded['results'][0];
                
                $customerAddress['geocoded'] = [
                    'latitude' => $result['latitude'],
                    'longitude' => $result['longitude'],
                    'formatted_address' => $result['display_name'],
                    'address_components' => $result['address'],
                    'administrative_levels' => $result['administrative_levels'],
                ];
                
                // Get tax jurisdictions if using Census Bureau API
                if (isset($this->apiClients['census'])) {
                    $jurisdictions = $this->apiClients['census']->getTaxJurisdictions(
                        $customerAddress['address_line_1'] ?? '',
                        $customerAddress['city'] ?? null,
                        $customerAddress['state'] ?? null,
                        $customerAddress['postal_code'] ?? null
                    );
                    
                    if ($jurisdictions['found']) {
                        $customerAddress['tax_jurisdictions'] = $jurisdictions['jurisdictions'];
                    }
                }
            }
            
        } catch (Exception $e) {
            Log::warning('Failed to enhance customer address with geocoding', [
                'error' => $e->getMessage(),
                'address' => $customerAddress
            ]);
        }
        
        return $customerAddress;
    }
    
    /**
     * Enhance tax calculations with API-based data
     */
    protected function enhanceWithApiCalculations(array $result, array $params, string $engineType, ?string $vatNumber = null, ?string $customerCountry = null): array
    {
        $enhancements = [];
        
        try {
            // VAT calculations for international transactions
            if ($vatNumber && isset($this->apiClients['vat_comply'])) {
                $enhancements['vat'] = $this->calculateVatEnhancements($vatNumber, $customerCountry, $params);
            }
            
            // Telecommunications-specific enhancements
            if ($engineType === self::ENGINE_VOIP && isset($this->apiClients['fcc'])) {
                $enhancements['telecom'] = $this->calculateTelecomEnhancements($params);
            }
            
            // Enhanced jurisdiction detection
            if (isset($params['service_address']['geocoded'])) {
                $enhancements['jurisdictions'] = $this->enhanceJurisdictionData($params['service_address']);
            }
            
            // US sales tax now handled by local data-driven system
            // No longer using external TaxCloud API
            
            // Merge enhancements into result
            if (!empty($enhancements)) {
                $result['api_enhancements'] = $enhancements;
                $result = $this->mergeApiEnhancements($result, $enhancements);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to enhance tax calculations with API data', [
                'error' => $e->getMessage(),
                'engine_type' => $engineType
            ]);
        }
        
        return $result;
    }
    
    /**
     * Calculate VAT-related enhancements
     */
    protected function calculateVatEnhancements(?string $vatNumber, ?string $customerCountry, array $params): array
    {
        $enhancements = [];
        
        try {
            $vatClient = $this->apiClients['vat_comply'];
            
            // Validate VAT number if provided
            if ($vatNumber) {
                $validation = $vatClient->validateVatNumber($vatNumber);
                $enhancements['vat_validation'] = $validation;
                
                if ($validation['valid']) {
                    $customerCountry = $validation['country_code'] ?? $customerCountry;
                }
            }
            
            // Get VAT rates for customer country
            if ($customerCountry) {
                $vatRates = $vatClient->getVatRates($customerCountry);
                $enhancements['vat_rates'] = $vatRates;
                
                // Determine VAT treatment
                $treatment = $vatClient->getVatTreatment(
                    'US', // Assuming supplier is in US
                    $customerCountry,
                    !empty($vatNumber), // Is business if VAT number provided
                    $vatNumber
                );
                $enhancements['vat_treatment'] = $treatment;
            }
            
        } catch (Exception $e) {
            Log::warning('Failed to calculate VAT enhancements', [
                'error' => $e->getMessage(),
                'vat_number' => $vatNumber,
                'country' => $customerCountry
            ]);
        }
        
        return $enhancements;
    }
    
    /**
     * Calculate telecommunications-specific enhancements
     */
    protected function calculateTelecomEnhancements(array $params): array
    {
        $enhancements = [];
        
        try {
            $fccClient = $this->apiClients['fcc'];
            $amount = $params['amount'] ?? 0;
            $stateCode = $params['service_address']['state'] ?? 'CA';
            $lineCount = $params['line_count'] ?? 1;
            
            // Get comprehensive telecom taxes
            $telecomTaxes = $fccClient->calculateTelecomTaxes(
                $amount * 0.7, // Assume 70% local
                $amount * 0.3, // Assume 30% long distance
                $stateCode,
                $lineCount
            );
            
            $enhancements['telecom_taxes'] = $telecomTaxes;
            
            // Get current USF rates
            $usfData = $fccClient->getUsfRate();
            $enhancements['usf_info'] = $usfData;
            
        } catch (Exception $e) {
            Log::warning('Failed to calculate telecom enhancements', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
        }
        
        return $enhancements;
    }
    
    /**
     * Enhance jurisdiction data with additional geographic information
     */
    protected function enhanceJurisdictionData(array $addressData): array
    {
        $enhancements = [];
        
        try {
            if (isset($addressData['geocoded']['latitude'], $addressData['geocoded']['longitude'])) {
                $lat = $addressData['geocoded']['latitude'];
                $lon = $addressData['geocoded']['longitude'];
                
                // Get FCC area information
                if (isset($this->apiClients['fcc'])) {
                    $areaInfo = $this->apiClients['fcc']->getAreaInfo($lat, $lon);
                    $enhancements['fcc_area'] = $areaInfo;
                }
                
                // Get Census geographic information
                if (isset($this->apiClients['census'])) {
                    $geoInfo = $this->apiClients['census']->getGeographicInfo($lat, $lon);
                    $enhancements['census_geography'] = $geoInfo;
                }
            }
            
        } catch (Exception $e) {
            Log::warning('Failed to enhance jurisdiction data', [
                'error' => $e->getMessage(),
                'address_data' => $addressData
            ]);
        }
        
        return $enhancements;
    }
    
    /**
     * Merge API enhancements into the main result
     */
    protected function mergeApiEnhancements(array $result, array $enhancements): array
    {
        // Add VAT information to tax breakdown if available
        if (isset($enhancements['vat']['vat_rates']['rates'])) {
            $vatRate = $enhancements['vat']['vat_rates']['rates']['standard'] ?? 0;
            if ($vatRate > 0) {
                $vatAmount = ($result['base_amount'] ?? 0) * ($vatRate / 100);
                
                $result['tax_breakdown']['vat'] = [
                    'rate' => $vatRate,
                    'amount' => round($vatAmount, 2),
                    'description' => 'Value Added Tax',
                    'source' => 'vatcomply_api',
                ];
                
                $result['total_tax_amount'] = ($result['total_tax_amount'] ?? 0) + $vatAmount;
                $result['final_amount'] = ($result['base_amount'] ?? 0) + $result['total_tax_amount'];
            }
        }
        
        // Add enhanced telecom taxes if available
        if (isset($enhancements['telecom']['tax_breakdown'])) {
            $telecomTaxes = $enhancements['telecom']['tax_breakdown'];
            
            foreach ($telecomTaxes as $taxType => $taxData) {
                $taxKey = 'api_' . $taxType;
                $result['tax_breakdown'][$taxKey] = [
                    'rate' => $taxData['rate'] ?? null,
                    'amount' => $taxData['tax_amount'] ?? $taxData['total_fee'] ?? $taxData['contribution_amount'] ?? 0,
                    'description' => $this->getTelecomTaxDescription($taxType),
                    'source' => 'fcc_api',
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Get description for telecom tax types
     */
    protected function getTelecomTaxDescription(string $taxType): string
    {
        $descriptions = [
            'federal_excise_tax' => 'Federal Excise Tax',
            'usf_contribution' => 'Universal Service Fund Contribution',
            'e911_fee' => 'Enhanced 911 Emergency Fee',
        ];
        
        return $descriptions[$taxType] ?? ucwords(str_replace('_', ' ', $taxType));
    }
    
    /**
     * Calculate US sales tax using local data-driven system
     * No longer uses TaxCloud - replaced with intelligent local calculation
     */
    protected function calculateUsSalesTax(array $params, string $engineType): array
    {
        // This method is deprecated - tax calculations now handled by
        // the data-driven LocalTaxRateService with automatic jurisdiction discovery
        return [
            'available' => false,
            'message' => 'Using local data-driven tax system',
            'source' => 'local_intelligent_system'
        ];
    }
    
    /**
     * Map engine type to service type for TaxCloud
     */
    protected function mapEngineTypeToServiceType(string $engineType): string
    {
        $mapping = [
            self::ENGINE_VOIP => 'voip',
            self::ENGINE_DIGITAL => 'cloud_services',
            self::ENGINE_EQUIPMENT => 'equipment',
            self::ENGINE_GENERAL => 'managed_services',
        ];
        
        return $mapping[$engineType] ?? 'managed_services';
    }
    
    /**
     * Get API enhancement status
     */
    protected function getApiEnhancementStatus(): array
    {
        return [
            'available_clients' => array_keys($this->apiClients),
            'vat_comply_enabled' => isset($this->apiClients['vat_comply']),
            'nominatim_enabled' => isset($this->apiClients['nominatim']),
            'census_enabled' => isset($this->apiClients['census']),
            'fcc_enabled' => isset($this->apiClients['fcc']),
            'taxcloud_enabled' => false, // Removed - using local data-driven system
        ];
    }
    
    /**
     * Get API client for external use
     */
    public function getApiClient(string $clientName): ?object
    {
        return $this->apiClients[$clientName] ?? null;
    }
    
    /**
     * Validate VAT number using API
     */
    public function validateVatNumber(string $vatNumber): array
    {
        $this->ensureCompanyId();
        
        if (!isset($this->apiClients['vat_comply'])) {
            return [
                'valid' => false,
                'error' => 'VAT validation API not available',
                'source' => 'router_fallback',
            ];
        }
        
        try {
            return $this->apiClients['vat_comply']->validateVatNumber($vatNumber);
        } catch (Exception $e) {
            Log::error('VAT number validation failed', [
                'vat_number' => $vatNumber,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'source' => 'api_error',
            ];
        }
    }
    
    /**
     * Get USF rates and telecom tax information
     */
    public function getTelecomTaxInfo(string $stateCode, int $lineCount = 1): array
    {
        $this->ensureCompanyId();
        
        if (!isset($this->apiClients['fcc'])) {
            return [
                'available' => false,
                'error' => 'FCC API not available',
            ];
        }
        
        try {
            return [
                'available' => true,
                'usf_rates' => $this->apiClients['fcc']->getAllUsfRates(),
                'e911_info' => $this->apiClients['fcc']->calculateE911Fee($stateCode, $lineCount),
                'federal_excise' => $this->apiClients['fcc']->calculateFederalExciseTax(100), // Sample calculation
            ];
        } catch (Exception $e) {
            Log::error('Telecom tax info retrieval failed', [
                'state_code' => $stateCode,
                'error' => $e->getMessage()
            ]);
            
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Create audit trail record for tax calculation
     */
    protected function createAuditTrail(
        array $inputParams, 
        array $result, 
        $calculable = null, 
        string $calculationType = TaxCalculation::TYPE_PREVIEW,
        float $executionTimeMs = 0
    ): ?TaxCalculation {
        try {
            // Count API calls made
            $apiCallsCount = 0;
            $apiCost = 0;
            
            if (isset($result['api_enhancements'])) {
                foreach ($result['api_enhancements'] as $enhancement) {
                    if (is_array($enhancement)) {
                        $apiCallsCount++;
                        // Add cost calculation logic here if needed
                    }
                }
            }
            
            // Prepare customer data
            $customerData = [];
            if (isset($inputParams['vat_number'])) {
                $customerData['vat_number'] = $inputParams['vat_number'];
            }
            if (isset($inputParams['customer_country'])) {
                $customerData['country'] = $inputParams['customer_country'];
            }
            if (isset($inputParams['customer_id'])) {
                $customerData['customer_id'] = $inputParams['customer_id'];
            }
            
            $taxCalculation = TaxCalculation::createCalculation(
                $this->companyId,
                $calculable,
                $result,
                $inputParams,
                $calculationType
            );
            
            // Update performance metrics
            $taxCalculation->update([
                'calculation_time_ms' => round($executionTimeMs, 2),
                'api_calls_count' => $apiCallsCount,
                'api_cost' => $apiCost,
                'customer_data' => !empty($customerData) ? $customerData : null,
            ]);
            
            return $taxCalculation;
            
        } catch (Exception $e) {
            Log::error('Failed to create tax calculation audit trail', [
                'error' => $e->getMessage(),
                'company_id' => $this->companyId,
                'input_params' => $inputParams,
            ]);
            
            return null;
        }
    }
    
    /**
     * Get calculation history for a calculable entity
     */
    public function getCalculationHistory($calculable, int $limit = 10): Collection
    {
        if (!$calculable || !$calculable->id) {
            return collect();
        }
        
        return TaxCalculation::where('company_id', $this->companyId)
            ->where('calculable_type', get_class($calculable))
            ->where('calculable_id', $calculable->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Compare current calculation with previous ones
     */
    public function compareWithPrevious(array $currentParams, $calculable): array
    {
        if (!$calculable) {
            return [
                'has_previous' => false,
                'comparison' => null,
            ];
        }
        
        $previousCalculations = $this->getCalculationHistory($calculable, 5);
        
        if ($previousCalculations->isEmpty()) {
            return [
                'has_previous' => false,
                'comparison' => null,
            ];
        }
        
        $latest = $previousCalculations->first();
        $currentResult = $this->calculateTaxes($currentParams, null, TaxCalculation::TYPE_PREVIEW);
        
        return [
            'has_previous' => true,
            'comparison' => [
                'base_amount_diff' => $currentResult['base_amount'] - $latest->base_amount,
                'tax_amount_diff' => $currentResult['total_tax_amount'] - $latest->total_tax_amount,
                'final_amount_diff' => $currentResult['final_amount'] - $latest->final_amount,
                'rate_diff' => $currentResult['effective_tax_rate'] - $latest->effective_tax_rate,
                'engine_changed' => $currentResult['engine_used'] !== $latest->engine_type,
                'previous_calculation_id' => $latest->calculation_id,
                'previous_created_at' => $latest->created_at,
            ],
            'recent_calculations' => $previousCalculations->map->getSummary(),
        ];
    }
    
    /**
     * Get tax calculation statistics for the company
     */
    public function getCalculationStatistics(Carbon $from = null, Carbon $to = null): array
    {
        $this->ensureCompanyId();
        
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();
        
        return TaxCalculation::getCompanyStatistics($this->companyId, $from, $to);
    }
    
    /**
     * Validate a tax calculation
     */
    public function validateCalculation(string $calculationId, string $notes = ''): bool
    {
        $this->ensureCompanyId();
        
        $calculation = TaxCalculation::where('company_id', $this->companyId)
            ->where('calculation_id', $calculationId)
            ->first();
            
        if (!$calculation) {
            return false;
        }
        
        $calculation->validateCalculation($notes);
        return true;
    }
    
    /**
     * Clear tax calculation caches for a company
     */
    public function clearTaxCaches(?int $specificCategoryId = null): void
    {
        $this->ensureCompanyId();
        
        if ($specificCategoryId) {
            // Clear cache for specific category
            Cache::forget("category_engine_type_company_{$this->companyId}_cat_{$specificCategoryId}");
            Cache::forget("tax_profile_company_{$this->companyId}_{$specificCategoryId}_*");
        } else {
            // Clear all tax caches for the company
            $cacheKeys = [
                "category_engine_type_company_{$this->companyId}_*",
                "tax_profile_company_{$this->companyId}_*",
                "tax_rates_company_{$this->companyId}_*",
                "jurisdiction_data_company_{$this->companyId}_*",
            ];
            
            foreach ($cacheKeys as $pattern) {
                // For production, you might want to use Redis SCAN or a more sophisticated cache tagging system
                Cache::flush(); // Simplified for now - in production, implement selective cache clearing
            }
        }
        
        // Clear memory caches
        $this->taxProfiles = [];
        $this->calculationCache = [];
        
        Log::info('Tax caches cleared', [
            'company_id' => $this->companyId,
            'specific_category' => $specificCategoryId,
        ]);
    }
    
    /**
     * Warm up tax calculation caches for commonly used categories
     */
    public function warmUpCaches(array $categoryIds = []): void
    {
        $this->ensureCompanyId();
        
        if (empty($categoryIds)) {
            // Get popular categories for this company
            $categoryIds = Category::where('company_id', $this->companyId)
                ->withCount('products')
                ->orderBy('products_count', 'desc')
                ->limit(20)
                ->pluck('id')
                ->toArray();
        }
        
        foreach ($categoryIds as $categoryId) {
            // Warm up engine type cache
            $this->determineEngineType($categoryId, null);
            
            // Warm up tax profile cache
            $this->getTaxProfile($categoryId, null);
        }
        
        Log::info('Tax caches warmed up', [
            'company_id' => $this->companyId,
            'categories_cached' => count($categoryIds),
        ]);
    }
    
    /**
     * Get cache statistics for monitoring
     */
    public function getCacheStatistics(): array
    {
        $this->ensureCompanyId();
        
        $stats = [
            'memory_cache_size' => [
                'tax_profiles' => count($this->taxProfiles),
                'calculation_cache' => count($this->calculationCache),
            ],
            'company_id' => $this->companyId,
            'cache_enabled' => Cache::getStore() instanceof \Illuminate\Cache\Repository,
        ];
        
        return $stats;
    }
    
    /**
     * Recalculate taxes using stored parameters
     */
    public function recalculateFromAudit(string $calculationId): array
    {
        $this->ensureCompanyId();
        
        $originalCalculation = TaxCalculation::where('company_id', $this->companyId)
            ->where('calculation_id', $calculationId)
            ->first();
            
        if (!$originalCalculation) {
            throw new Exception("Calculation {$calculationId} not found");
        }
        
        // Use stored input parameters for recalculation
        $result = $this->calculateTaxes(
            $originalCalculation->input_parameters,
            $originalCalculation->calculable,
            TaxCalculation::TYPE_ADJUSTMENT
        );
        
        // Add comparison with original
        $result['recalculation'] = [
            'original_calculation_id' => $originalCalculation->calculation_id,
            'original_total_tax' => $originalCalculation->total_tax_amount,
            'original_final_amount' => $originalCalculation->final_amount,
            'tax_difference' => $result['total_tax_amount'] - $originalCalculation->total_tax_amount,
            'amount_difference' => $result['final_amount'] - $originalCalculation->final_amount,
        ];
        
        return $result;
    }
}