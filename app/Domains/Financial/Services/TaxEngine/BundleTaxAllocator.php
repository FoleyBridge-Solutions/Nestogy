<?php

namespace App\Domains\Financial\Services\TaxEngine;

use App\Models\TaxCalculation;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Bundle Tax Allocator
 *
 * Handles complex tax allocation for MSP service bundles where different
 * services in a bundle may have different tax treatments, rates, and jurisdictions.
 *
 * Common MSP Bundle Scenarios:
 * - Mixed taxable/non-taxable services
 * - Different tax rates by service type (VoIP vs Cloud vs Equipment)
 * - Multi-jurisdiction services
 * - Professional services + recurring services
 * - Equipment + installation + ongoing support
 */
class BundleTaxAllocator
{
    protected TaxEngineRouter $taxRouter;

    protected int $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->taxRouter = new TaxEngineRouter($companyId);
    }

    /**
     * Allocation method constants
     */
    const METHOD_PROPORTIONAL = 'proportional';

    const METHOD_PRIORITY_BASED = 'priority_based';

    const METHOD_SERVICE_TYPE = 'service_type';

    const METHOD_TAX_CLASS = 'tax_class';

    const METHOD_JURISDICTION = 'jurisdiction';

    /**
     * Service tax priority levels (higher = more taxable)
     */
    const TAX_PRIORITIES = [
        'equipment' => 10,           // Physical goods - highest tax priority
        'software' => 9,             // Software licenses
        'installation' => 8,         // Installation services
        'voip' => 7,                // VoIP services (telecom taxes)
        'internet' => 6,            // Internet services
        'cloud_services' => 5,      // Cloud/hosting services
        'monitoring' => 4,          // Monitoring services
        'managed_services' => 3,    // Managed IT services
        'support' => 2,             // Support services
        'consulting' => 1,          // Professional services - lowest tax priority
    ];

    /**
     * Allocate taxes across bundle items
     *
     * @param  array  $bundleItems  Array of bundle items with pricing and service details
     * @param  array  $customerInfo  Customer address, VAT number, etc.
     * @param  array  $options  Allocation options and preferences
     * @return array Detailed tax allocation results
     */
    public function allocateBundleTaxes(array $bundleItems, array $customerInfo = [], array $options = []): array
    {
        $startTime = microtime(true);

        try {
            // Validate and normalize bundle items
            $normalizedItems = $this->normalizeBundleItems($bundleItems);

            // Determine allocation method
            $allocationMethod = $options['method'] ?? $this->determineOptimalAllocationMethod($normalizedItems);

            // Calculate individual tax rates for each service type
            $serviceTaxRates = $this->calculateServiceTaxRates($normalizedItems, $customerInfo);

            // Perform tax allocation based on method
            $allocation = $this->performAllocation($normalizedItems, $serviceTaxRates, $allocationMethod, $options);

            // Calculate bundle totals
            $bundleTotals = $this->calculateBundleTotals($allocation);

            // Generate allocation report
            $report = $this->generateAllocationReport($allocation, $bundleTotals, $allocationMethod);

            // Create audit trail
            $this->createBundleAuditTrail($bundleItems, $allocation, $customerInfo, $options);

            return [
                'success' => true,
                'allocation_method' => $allocationMethod,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'bundle_totals' => $bundleTotals,
                'item_allocations' => $allocation,
                'tax_breakdown' => $report['tax_breakdown'],
                'jurisdiction_summary' => $report['jurisdiction_summary'],
                'service_type_summary' => $report['service_type_summary'],
                'allocation_details' => $report['allocation_details'],
            ];

        } catch (Exception $e) {
            Log::error('Bundle tax allocation failed', [
                'error' => $e->getMessage(),
                'company_id' => $this->companyId,
                'bundle_items' => $bundleItems,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_calculation' => $this->calculateFallbackTaxes($bundleItems, $customerInfo),
            ];
        }
    }

    /**
     * Normalize bundle items to standard format
     */
    protected function normalizeBundleItems(array $bundleItems): array
    {
        $normalized = [];

        foreach ($bundleItems as $index => $item) {
            $normalized[] = [
                'id' => $item['id'] ?? "bundle_item_{$index}",
                'name' => $item['name'] ?? "Bundle Item {$index}",
                'description' => $item['description'] ?? '',
                'service_type' => $this->normalizeServiceType($item['service_type'] ?? 'managed_services'),
                'category_type' => $item['category_type'] ?? null,
                'price' => (float) ($item['price'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'subtotal' => (float) ($item['subtotal'] ?? ($item['price'] ?? 0) * ($item['quantity'] ?? 1)),
                'tax_class' => $item['tax_class'] ?? $this->inferTaxClass($item['service_type'] ?? 'managed_services'),
                'taxable' => $item['taxable'] ?? true,
                'tax_exempt' => $item['tax_exempt'] ?? false,
                'exemption_certificate' => $item['exemption_certificate'] ?? null,
                'service_address' => $item['service_address'] ?? null,
                'tax_data' => $item['tax_data'] ?? [],
                'priority' => $item['priority'] ?? $this->getServiceTaxPriority($item['service_type'] ?? 'managed_services'),
                'allocation_weight' => $item['allocation_weight'] ?? null, // Custom weighting
            ];
        }

        return $normalized;
    }

    /**
     * Normalize service type to standard values
     */
    protected function normalizeServiceType(string $serviceType): string
    {
        $mappings = [
            'equipment' => 'equipment',
            'hardware' => 'equipment',
            'devices' => 'equipment',
            'server' => 'equipment',

            'software' => 'software',
            'license' => 'software',
            'application' => 'software',

            'voip' => 'voip',
            'phone' => 'voip',
            'telecommunications' => 'voip',
            'pbx' => 'voip',

            'cloud' => 'cloud_services',
            'hosting' => 'cloud_services',
            'saas' => 'cloud_services',
            'storage' => 'cloud_services',

            'monitoring' => 'monitoring',
            'surveillance' => 'monitoring',
            'alerting' => 'monitoring',

            'managed' => 'managed_services',
            'management' => 'managed_services',
            'maintenance' => 'managed_services',

            'support' => 'support',
            'help_desk' => 'support',
            'technical_support' => 'support',

            'consulting' => 'consulting',
            'professional' => 'consulting',
            'advisory' => 'consulting',

            'installation' => 'installation',
            'setup' => 'installation',
            'deployment' => 'installation',

            'internet' => 'internet',
            'broadband' => 'internet',
            'connectivity' => 'internet',
        ];

        $normalized = strtolower(str_replace([' ', '-'], '_', $serviceType));

        return $mappings[$normalized] ?? 'managed_services';
    }

    /**
     * Infer tax class from service type
     */
    protected function inferTaxClass(string $serviceType): string
    {
        $taxClasses = [
            'equipment' => 'tangible_goods',
            'software' => 'digital_goods',
            'voip' => 'telecommunications',
            'internet' => 'telecommunications',
            'cloud_services' => 'digital_services',
            'monitoring' => 'digital_services',
            'managed_services' => 'professional_services',
            'support' => 'professional_services',
            'consulting' => 'professional_services',
            'installation' => 'professional_services',
        ];

        return $taxClasses[$serviceType] ?? 'professional_services';
    }

    /**
     * Get tax priority for service type
     */
    protected function getServiceTaxPriority(string $serviceType): int
    {
        return self::TAX_PRIORITIES[$serviceType] ?? 1;
    }

    /**
     * Determine optimal allocation method for the bundle
     */
    protected function determineOptimalAllocationMethod(array $items): string
    {
        // Analyze bundle composition
        $serviceTypes = array_column($items, 'service_type');
        $uniqueTypes = array_unique($serviceTypes);
        $taxClasses = array_unique(array_column($items, 'tax_class'));

        // If all items have same tax treatment, use proportional
        if (count($uniqueTypes) === 1 && count($taxClasses) === 1) {
            return self::METHOD_PROPORTIONAL;
        }

        // If mix of equipment and services, use priority-based
        if (in_array('equipment', $serviceTypes) && count($uniqueTypes) > 1) {
            return self::METHOD_PRIORITY_BASED;
        }

        // If multiple service types with different tax treatments
        if (count($uniqueTypes) > 2) {
            return self::METHOD_SERVICE_TYPE;
        }

        // Default to proportional allocation
        return self::METHOD_PROPORTIONAL;
    }

    /**
     * Calculate tax rates for each service type
     */
    protected function calculateServiceTaxRates(array $items, array $customerInfo): array
    {
        $rates = [];
        $uniqueServiceTypes = array_unique(array_column($items, 'service_type'));

        foreach ($uniqueServiceTypes as $serviceType) {
            try {
                $sampleItem = array_filter($items, fn ($item) => $item['service_type'] === $serviceType)[0];

                $taxParams = [
                    'base_price' => 100, // Standardized amount for rate calculation
                    'quantity' => 1,
                    'category_type' => $serviceType,
                    'customer_address' => $customerInfo['address'] ?? [],
                    'vat_number' => $customerInfo['vat_number'] ?? null,
                    'customer_country' => $customerInfo['country'] ?? null,
                    'tax_data' => $sampleItem['tax_data'],
                ];

                $taxResult = $this->taxRouter->calculateTaxes($taxParams, null, TaxCalculation::TYPE_PREVIEW);

                $rates[$serviceType] = [
                    'effective_rate' => $taxResult['effective_tax_rate'] ?? 0,
                    'tax_breakdown' => $taxResult['tax_breakdown'] ?? [],
                    'jurisdictions' => $taxResult['jurisdictions'] ?? [],
                    'engine_used' => $taxResult['engine_used'] ?? 'general',
                ];

            } catch (Exception $e) {
                Log::warning("Failed to calculate tax rate for service type: {$serviceType}", [
                    'error' => $e->getMessage(),
                ]);

                $rates[$serviceType] = [
                    'effective_rate' => 0,
                    'tax_breakdown' => [],
                    'jurisdictions' => [],
                    'engine_used' => 'fallback',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $rates;
    }

    /**
     * Perform tax allocation based on selected method
     */
    protected function performAllocation(array $items, array $serviceTaxRates, string $method, array $options): array
    {
        switch ($method) {
            case self::METHOD_PROPORTIONAL:
                return $this->allocateProportional($items, $serviceTaxRates);

            case self::METHOD_PRIORITY_BASED:
                return $this->allocatePriorityBased($items, $serviceTaxRates);

            case self::METHOD_SERVICE_TYPE:
                return $this->allocateByServiceType($items, $serviceTaxRates);

            case self::METHOD_TAX_CLASS:
                return $this->allocateByTaxClass($items, $serviceTaxRates);

            case self::METHOD_JURISDICTION:
                return $this->allocateByJurisdiction($items, $serviceTaxRates, $options);

            default:
                return $this->allocateProportional($items, $serviceTaxRates);
        }
    }

    /**
     * Proportional allocation - taxes allocated based on item value percentage
     */
    protected function allocateProportional(array $items, array $serviceTaxRates): array
    {
        $bundleSubtotal = array_sum(array_column($items, 'subtotal'));
        $allocation = [];

        foreach ($items as $item) {
            $proportion = $bundleSubtotal > 0 ? $item['subtotal'] / $bundleSubtotal : 0;
            $serviceRate = $serviceTaxRates[$item['service_type']]['effective_rate'] ?? 0;

            $itemTaxAmount = $item['subtotal'] * ($serviceRate / 100);

            $allocation[] = [
                'item' => $item,
                'proportion' => $proportion,
                'tax_rate' => $serviceRate,
                'tax_amount' => round($itemTaxAmount, 2),
                'total_amount' => round($item['subtotal'] + $itemTaxAmount, 2),
                'allocation_method' => 'proportional',
                'tax_breakdown' => $serviceTaxRates[$item['service_type']]['tax_breakdown'] ?? [],
                'jurisdictions' => $serviceTaxRates[$item['service_type']]['jurisdictions'] ?? [],
            ];
        }

        return $allocation;
    }

    /**
     * Priority-based allocation - higher priority items get taxed first
     */
    protected function allocatePriorityBased(array $items, array $serviceTaxRates): array
    {
        // Sort items by tax priority (highest first)
        usort($items, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        $allocation = [];
        $bundleSubtotal = array_sum(array_column($items, 'subtotal'));

        foreach ($items as $item) {
            $serviceRate = $serviceTaxRates[$item['service_type']]['effective_rate'] ?? 0;

            // Higher priority items get full tax rate
            // Lower priority items get reduced rate based on bundle composition
            $priorityWeight = $this->calculatePriorityWeight($item, $items);
            $adjustedRate = $serviceRate * $priorityWeight;

            $itemTaxAmount = $item['subtotal'] * ($adjustedRate / 100);

            $allocation[] = [
                'item' => $item,
                'priority' => $item['priority'],
                'priority_weight' => $priorityWeight,
                'base_tax_rate' => $serviceRate,
                'adjusted_tax_rate' => $adjustedRate,
                'tax_amount' => round($itemTaxAmount, 2),
                'total_amount' => round($item['subtotal'] + $itemTaxAmount, 2),
                'allocation_method' => 'priority_based',
                'tax_breakdown' => $serviceTaxRates[$item['service_type']]['tax_breakdown'] ?? [],
                'jurisdictions' => $serviceTaxRates[$item['service_type']]['jurisdictions'] ?? [],
            ];
        }

        return $allocation;
    }

    /**
     * Service type allocation - each service type gets its own tax treatment
     */
    protected function allocateByServiceType(array $items, array $serviceTaxRates): array
    {
        $allocation = [];

        foreach ($items as $item) {
            $serviceRate = $serviceTaxRates[$item['service_type']]['effective_rate'] ?? 0;
            $itemTaxAmount = $item['subtotal'] * ($serviceRate / 100);

            $allocation[] = [
                'item' => $item,
                'service_type' => $item['service_type'],
                'tax_rate' => $serviceRate,
                'tax_amount' => round($itemTaxAmount, 2),
                'total_amount' => round($item['subtotal'] + $itemTaxAmount, 2),
                'allocation_method' => 'service_type',
                'tax_breakdown' => $serviceTaxRates[$item['service_type']]['tax_breakdown'] ?? [],
                'jurisdictions' => $serviceTaxRates[$item['service_type']]['jurisdictions'] ?? [],
            ];
        }

        return $allocation;
    }

    /**
     * Tax class allocation - groups by tax classification
     */
    protected function allocateByTaxClass(array $items, array $serviceTaxRates): array
    {
        $allocation = [];

        foreach ($items as $item) {
            $serviceRate = $serviceTaxRates[$item['service_type']]['effective_rate'] ?? 0;

            // Apply class-specific adjustments
            $classAdjustment = $this->getTaxClassAdjustment($item['tax_class']);
            $adjustedRate = $serviceRate * $classAdjustment;

            $itemTaxAmount = $item['subtotal'] * ($adjustedRate / 100);

            $allocation[] = [
                'item' => $item,
                'tax_class' => $item['tax_class'],
                'base_tax_rate' => $serviceRate,
                'class_adjustment' => $classAdjustment,
                'adjusted_tax_rate' => $adjustedRate,
                'tax_amount' => round($itemTaxAmount, 2),
                'total_amount' => round($item['subtotal'] + $itemTaxAmount, 2),
                'allocation_method' => 'tax_class',
                'tax_breakdown' => $serviceTaxRates[$item['service_type']]['tax_breakdown'] ?? [],
                'jurisdictions' => $serviceTaxRates[$item['service_type']]['jurisdictions'] ?? [],
            ];
        }

        return $allocation;
    }

    /**
     * Jurisdiction-based allocation - considers service delivery location
     */
    protected function allocateByJurisdiction(array $items, array $serviceTaxRates, array $options): array
    {
        $allocation = [];

        foreach ($items as $item) {
            $serviceAddress = $item['service_address'] ?? $options['default_service_address'] ?? [];

            // Calculate jurisdiction-specific rates if service address differs
            if (! empty($serviceAddress)) {
                $jurisdictionRate = $this->calculateJurisdictionRate($item, $serviceAddress);
            } else {
                $jurisdictionRate = $serviceTaxRates[$item['service_type']]['effective_rate'] ?? 0;
            }

            $itemTaxAmount = $item['subtotal'] * ($jurisdictionRate / 100);

            $allocation[] = [
                'item' => $item,
                'service_address' => $serviceAddress,
                'jurisdiction_rate' => $jurisdictionRate,
                'tax_amount' => round($itemTaxAmount, 2),
                'total_amount' => round($item['subtotal'] + $itemTaxAmount, 2),
                'allocation_method' => 'jurisdiction',
                'tax_breakdown' => $serviceTaxRates[$item['service_type']]['tax_breakdown'] ?? [],
                'jurisdictions' => $serviceTaxRates[$item['service_type']]['jurisdictions'] ?? [],
            ];
        }

        return $allocation;
    }

    /**
     * Calculate priority weight for priority-based allocation
     */
    protected function calculatePriorityWeight(array $item, array $allItems): float
    {
        $maxPriority = max(array_column($allItems, 'priority'));
        $minPriority = min(array_column($allItems, 'priority'));

        if ($maxPriority === $minPriority) {
            return 1.0;
        }

        // Higher priority items get weight closer to 1.0
        return 0.3 + (0.7 * (($item['priority'] - $minPriority) / ($maxPriority - $minPriority)));
    }

    /**
     * Get tax class adjustment factor
     */
    protected function getTaxClassAdjustment(string $taxClass): float
    {
        $adjustments = [
            'tangible_goods' => 1.0,        // Full tax rate
            'digital_goods' => 0.95,        // Slightly reduced
            'telecommunications' => 1.1,     // May include additional fees
            'digital_services' => 0.9,      // Often reduced rate
            'professional_services' => 0.8,  // Often lower or exempt
        ];

        return $adjustments[$taxClass] ?? 1.0;
    }

    /**
     * Calculate jurisdiction-specific rate
     */
    protected function calculateJurisdictionRate(array $item, array $serviceAddress): float
    {
        try {
            $taxParams = [
                'base_price' => 100,
                'quantity' => 1,
                'category_type' => $item['service_type'],
                'customer_address' => $serviceAddress,
                'tax_data' => $item['tax_data'],
            ];

            $taxResult = $this->taxRouter->calculateTaxes($taxParams, null, TaxCalculation::TYPE_PREVIEW);

            return $taxResult['effective_tax_rate'] ?? 0;

        } catch (Exception $e) {
            Log::warning('Failed to calculate jurisdiction rate', [
                'error' => $e->getMessage(),
                'item' => $item,
                'service_address' => $serviceAddress,
            ]);

            return 0;
        }
    }

    /**
     * Calculate bundle totals
     */
    protected function calculateBundleTotals(array $allocation): array
    {
        $subtotal = array_sum(array_column(array_column($allocation, 'item'), 'subtotal'));
        $totalTax = array_sum(array_column($allocation, 'tax_amount'));
        $grandTotal = $subtotal + $totalTax;

        return [
            'subtotal' => round($subtotal, 2),
            'total_tax' => round($totalTax, 2),
            'grand_total' => round($grandTotal, 2),
            'effective_tax_rate' => $subtotal > 0 ? round(($totalTax / $subtotal) * 100, 4) : 0,
            'item_count' => count($allocation),
        ];
    }

    /**
     * Generate comprehensive allocation report
     */
    protected function generateAllocationReport(array $allocation, array $bundleTotals, string $method): array
    {
        // Tax breakdown by type
        $taxBreakdown = [];
        foreach ($allocation as $item) {
            foreach ($item['tax_breakdown'] as $taxType => $taxData) {
                if (! isset($taxBreakdown[$taxType])) {
                    $taxBreakdown[$taxType] = [
                        'description' => $taxData['description'] ?? $taxType,
                        'total_amount' => 0,
                        'rate_range' => [],
                        'sources' => [],
                    ];
                }

                $taxBreakdown[$taxType]['total_amount'] += $taxData['amount'] ?? 0;
                $taxBreakdown[$taxType]['rate_range'][] = $taxData['rate'] ?? 0;
                $taxBreakdown[$taxType]['sources'][] = $taxData['source'] ?? 'unknown';
            }
        }

        // Service type summary
        $serviceTypeSummary = [];
        foreach ($allocation as $item) {
            $serviceType = $item['item']['service_type'];

            if (! isset($serviceTypeSummary[$serviceType])) {
                $serviceTypeSummary[$serviceType] = [
                    'item_count' => 0,
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                ];
            }

            $serviceTypeSummary[$serviceType]['item_count']++;
            $serviceTypeSummary[$serviceType]['subtotal'] += $item['item']['subtotal'];
            $serviceTypeSummary[$serviceType]['tax_amount'] += $item['tax_amount'];
            $serviceTypeSummary[$serviceType]['tax_rate'] = $item['tax_rate'] ?? 0;
        }

        // Jurisdiction summary
        $jurisdictionSummary = [];
        foreach ($allocation as $item) {
            foreach ($item['jurisdictions'] as $jurisdiction) {
                $name = $jurisdiction['name'] ?? 'Unknown';

                if (! isset($jurisdictionSummary[$name])) {
                    $jurisdictionSummary[$name] = [
                        'type' => $jurisdiction['type'] ?? 'unknown',
                        'code' => $jurisdiction['code'] ?? null,
                        'tax_amount' => 0,
                        'items_affected' => 0,
                    ];
                }

                $jurisdictionSummary[$name]['tax_amount'] += $jurisdiction['tax_amount'] ?? 0;
                $jurisdictionSummary[$name]['items_affected']++;
            }
        }

        return [
            'tax_breakdown' => $taxBreakdown,
            'service_type_summary' => $serviceTypeSummary,
            'jurisdiction_summary' => $jurisdictionSummary,
            'allocation_details' => [
                'method' => $method,
                'total_items' => count($allocation),
                'taxable_items' => count(array_filter($allocation, fn ($item) => $item['tax_amount'] > 0)),
                'tax_exempt_items' => count(array_filter($allocation, fn ($item) => $item['tax_amount'] == 0)),
            ],
        ];
    }

    /**
     * Calculate fallback taxes (simple proportional)
     */
    protected function calculateFallbackTaxes(array $bundleItems, array $customerInfo): array
    {
        $bundleSubtotal = array_sum(array_column($bundleItems, 'price'));
        $fallbackRate = 8.5; // Default rate

        $taxAmount = $bundleSubtotal * ($fallbackRate / 100);

        return [
            'subtotal' => round($bundleSubtotal, 2),
            'tax_rate' => $fallbackRate,
            'tax_amount' => round($taxAmount, 2),
            'total' => round($bundleSubtotal + $taxAmount, 2),
            'method' => 'fallback',
        ];
    }

    /**
     * Create audit trail for bundle tax calculation
     */
    protected function createBundleAuditTrail(array $bundleItems, array $allocation, array $customerInfo, array $options): void
    {
        try {
            $bundleTotals = $this->calculateBundleTotals($allocation);

            // Create a summary calculation record
            $summaryParams = [
                'base_price' => $bundleTotals['subtotal'],
                'quantity' => 1,
                'category_type' => 'bundle',
                'customer_address' => $customerInfo['address'] ?? [],
                'vat_number' => $customerInfo['vat_number'] ?? null,
                'customer_country' => $customerInfo['country'] ?? null,
                'tax_data' => [
                    'bundle_items' => $bundleItems,
                    'allocation_method' => $options['method'] ?? 'auto',
                    'item_count' => count($bundleItems),
                ],
            ];

            $summaryResult = [
                'base_amount' => $bundleTotals['subtotal'],
                'total_tax_amount' => $bundleTotals['total_tax'],
                'final_amount' => $bundleTotals['grand_total'],
                'effective_tax_rate' => $bundleTotals['effective_tax_rate'],
                'engine_used' => 'bundle_allocator',
                'tax_breakdown' => ['bundle_allocation' => ['amount' => $bundleTotals['total_tax']]],
                'bundle_allocation' => $allocation,
            ];

            TaxCalculation::createCalculation(
                $this->companyId,
                null, // No specific calculable for bundle preview
                $summaryResult,
                $summaryParams,
                TaxCalculation::TYPE_PREVIEW
            );

        } catch (Exception $e) {
            Log::warning('Failed to create bundle tax audit trail', [
                'error' => $e->getMessage(),
                'company_id' => $this->companyId,
            ]);
        }
    }

    /**
     * Get bundle allocation recommendations
     */
    public function getRecommendations(array $bundleItems, array $customerInfo = []): array
    {
        $normalizedItems = $this->normalizeBundleItems($bundleItems);

        $recommendations = [
            'optimal_method' => $this->determineOptimalAllocationMethod($normalizedItems),
            'complexity_score' => $this->calculateComplexityScore($normalizedItems),
            'risk_factors' => $this->identifyRiskFactors($normalizedItems, $customerInfo),
            'optimization_suggestions' => $this->getOptimizationSuggestions($normalizedItems),
        ];

        return $recommendations;
    }

    /**
     * Calculate bundle complexity score
     */
    protected function calculateComplexityScore(array $items): int
    {
        $score = 0;

        $uniqueServiceTypes = count(array_unique(array_column($items, 'service_type')));
        $uniqueTaxClasses = count(array_unique(array_column($items, 'tax_class')));
        $hasMultipleAddresses = count(array_filter($items, fn ($item) => ! empty($item['service_address']))) > 1;
        $hasExemptions = count(array_filter($items, fn ($item) => $item['tax_exempt'])) > 0;

        $score += $uniqueServiceTypes * 2;
        $score += $uniqueTaxClasses * 3;
        $score += $hasMultipleAddresses ? 5 : 0;
        $score += $hasExemptions ? 3 : 0;

        return min($score, 10); // Cap at 10
    }

    /**
     * Identify potential risk factors
     */
    protected function identifyRiskFactors(array $items, array $customerInfo): array
    {
        $risks = [];

        if (count(array_unique(array_column($items, 'service_type'))) > 3) {
            $risks[] = 'high_service_type_diversity';
        }

        if (in_array('equipment', array_column($items, 'service_type'))) {
            $risks[] = 'tangible_goods_included';
        }

        if (! empty($customerInfo['vat_number'])) {
            $risks[] = 'international_vat_applicable';
        }

        if (count(array_filter($items, fn ($item) => ! empty($item['service_address']))) > 0) {
            $risks[] = 'multiple_service_locations';
        }

        return $risks;
    }

    /**
     * Get optimization suggestions
     */
    protected function getOptimizationSuggestions(array $items): array
    {
        $suggestions = [];

        $serviceTypes = array_column($items, 'service_type');

        if (in_array('equipment', $serviceTypes) && in_array('consulting', $serviceTypes)) {
            $suggestions[] = 'consider_separating_tangible_and_professional_services';
        }

        if (count(array_unique($serviceTypes)) > 4) {
            $suggestions[] = 'consider_grouping_similar_services';
        }

        $hasCustomWeights = count(array_filter($items, fn ($item) => $item['allocation_weight'] !== null)) > 0;
        if (! $hasCustomWeights) {
            $suggestions[] = 'consider_custom_allocation_weights';
        }

        return $suggestions;
    }
}
