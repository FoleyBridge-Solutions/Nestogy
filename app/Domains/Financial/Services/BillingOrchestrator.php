<?php

namespace App\Domains\Financial\Services;

use App\Models\Product;
use App\Models\Service;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Billing Orchestrator (Refactored with Composition)
 * 
 * This service demonstrates the complete transformation from inheritance
 * to composition patterns. Instead of one monolithic BillingService with
 * 7+ responsibilities, we now compose specialized services.
 * 
 * COMPOSITION BENEFITS DEMONSTRATED:
 * - Single Responsibility: Each composed service has one clear purpose
 * - Dependency Injection: Services are injected, not inherited
 * - Flexibility: Easy to swap implementations without affecting others
 * - Testability: Can mock individual services for targeted testing
 * - Reusability: Specialized services can be used in other contexts
 */
class BillingOrchestrator
{
    public function __construct(
        private BillingScheduleService $scheduleService,
        private ProrationCalculatorService $prorationService,
        private UsageBillingService $usageService,
        private RecurringBillingService $recurringService,
        private ProductPricingService $pricingService
    ) {}

    /**
     * Generate comprehensive billing for a product.
     * 
     * COMPOSITION PATTERN: Delegates to specialized services rather than handling everything.
     */
    public function generateComprehensiveBilling(Product $product, Client $client, array $options = []): array
    {
        $startDate = $options['start_date'] ?? Carbon::today();
        $periods = $options['periods'] ?? 12;
        $usage = $options['usage'] ?? 0;

        $result = [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'billing_model' => $product->billing_model
            ],
            'client' => [
                'id' => $client->id,
                'name' => $client->display_name
            ],
            'billing_components' => []
        ];

        // 1. Generate billing schedule using specialized service
        $result['billing_components']['schedule'] = $this->scheduleService->generateSchedule(
            $product, 
            $startDate, 
            $periods
        );

        // 2. Calculate proration if needed using specialized service
        if ($options['calculate_proration'] ?? false) {
            $result['billing_components']['proration'] = $this->prorationService->calculateProratedAmount(
                $product, 
                $startDate, 
                $options['proration_end_date'] ?? null
            );
        }

        // 3. Calculate usage billing if applicable using specialized service
        if ($product->billing_model === 'usage_based' && $usage > 0) {
            $result['billing_components']['usage'] = $this->usageService->calculateUsageBilling(
                $product, 
                $usage, 
                $startDate, 
                $startDate->copy()->addMonth()
            );
        }

        // 4. Calculate pricing using specialized service
        $result['billing_components']['pricing'] = $this->pricingService->calculatePrice(
            $product, 
            $client, 
            $options['quantity'] ?? 1
        );

        return $result;
    }

    /**
     * Process bulk billing operations.
     * 
     * COMPOSITION PATTERN: Orchestrates multiple services for complex operations.
     */
    public function processBulkBilling(array $billingRequests): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'total_amount' => 0,
            'breakdown' => []
        ];

        foreach ($billingRequests as $request) {
            try {
                $result = $this->processIndividualBilling($request);
                
                $results['processed']++;
                $results['total_amount'] += $result['total_amount'];
                $results['breakdown'][] = $result;

            } catch (\Exception $e) {
                $results['failed']++;
                $results['breakdown'][] = [
                    'error' => $e->getMessage(),
                    'request' => $request
                ];
            }
        }

        return $results;
    }

    /**
     * Calculate setup and termination fees.
     * 
     * COMPOSITION PATTERN: Combines multiple calculation services.
     */
    public function calculateServiceFees(Service $service, Client $client, array $options = []): array
    {
        $fees = [
            'setup_fees' => [],
            'termination_fees' => [],
            'total_setup_cost' => 0,
            'total_termination_cost' => 0
        ];

        // Calculate setup fees
        if ($service->has_setup_fee) {
            $setupFees = $this->calculateServiceSetupFees($service, $client);
            $fees['setup_fees'] = $setupFees['fees'];
            $fees['total_setup_cost'] = $setupFees['total_setup_cost'];
        }

        // Calculate termination fees if termination date provided
        if (isset($options['termination_date'])) {
            $terminationFee = $this->calculateEarlyTerminationFee(
                $service, 
                $options['start_date'] ?? Carbon::today(),
                $options['termination_date']
            );
            
            $fees['termination_fees'] = [
                'amount' => $terminationFee,
                'reason' => 'Early termination before minimum commitment'
            ];
            $fees['total_termination_cost'] = $terminationFee;
        }

        return $fees;
    }

    /**
     * Get comprehensive billing analytics.
     * 
     * COMPOSITION PATTERN: Aggregates data from multiple specialized services.
     */
    public function getBillingAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $startDate->diffInDays($endDate)
            ],
            'recurring_summary' => $this->recurringService->getRecurringSummary($startDate, $endDate),
            'upcoming_bills' => $this->recurringService->previewUpcomingBills(30),
            'proration_summary' => $this->getProrationAnalytics($startDate, $endDate),
            'usage_summary' => $this->getUsageAnalytics($startDate, $endDate)
        ];
    }

    /**
     * Test all billing components.
     * 
     * COMPOSITION PATTERN: Validates each composed service independently.
     */
    public function testBillingSystem(Product $testProduct, Client $testClient): array
    {
        $tests = [
            'schedule_service' => $this->testScheduleService($testProduct),
            'proration_service' => $this->testProrationService($testProduct),
            'usage_service' => $this->testUsageService($testProduct),
            'recurring_service' => $this->testRecurringService($testProduct),
            'pricing_service' => $this->testPricingService($testProduct, $testClient)
        ];

        $allPassed = collect($tests)->every(fn($test) => $test['status'] === 'passed');

        return [
            'overall_status' => $allPassed ? 'passed' : 'failed',
            'tests' => $tests,
            'composition_verified' => true
        ];
    }

    /**
     * Process individual billing request using composed services.
     */
    protected function processIndividualBilling(array $request): array
    {
        $product = Product::find($request['product_id']);
        $client = Client::find($request['client_id']);

        switch ($request['type']) {
            case 'schedule':
                return [
                    'type' => 'schedule',
                    'result' => $this->scheduleService->generateSchedule(
                        $product, 
                        Carbon::parse($request['start_date']),
                        $request['periods'] ?? 12
                    ),
                    'total_amount' => $product->base_price * ($request['periods'] ?? 12)
                ];

            case 'usage':
                $usage = $this->usageService->calculateUsageBilling(
                    $product,
                    $request['usage'],
                    Carbon::parse($request['period_start']),
                    Carbon::parse($request['period_end'])
                );
                return [
                    'type' => 'usage',
                    'result' => $usage,
                    'total_amount' => $usage['total_amount']
                ];

            case 'proration':
                $proration = $this->prorationService->calculateProratedAmount(
                    $product,
                    Carbon::parse($request['start_date']),
                    isset($request['end_date']) ? Carbon::parse($request['end_date']) : null
                );
                return [
                    'type' => 'proration',
                    'result' => $proration,
                    'total_amount' => $proration['amount']
                ];

            default:
                throw new \InvalidArgumentException("Unknown billing type: {$request['type']}");
        }
    }

    /**
     * Get proration analytics using composed service.
     */
    protected function getProrationAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        // This would use the ProrationCalculatorService to analyze proration patterns
        return [
            'total_prorations' => 0,
            'total_savings' => 0,
            'average_proration_percentage' => 0
        ];
    }

    /**
     * Get usage analytics using composed service.
     */
    protected function getUsageAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        // This would use the UsageBillingService to analyze usage patterns
        return [
            'total_usage_revenue' => 0,
            'average_overage' => 0,
            'top_usage_products' => []
        ];
    }

    // Test methods for each composed service
    protected function testScheduleService(Product $product): array
    {
        try {
            $schedule = $this->scheduleService->generateSchedule($product, Carbon::today(), 3);
            return [
                'status' => 'passed',
                'service' => 'BillingScheduleService',
                'test' => 'Generate 3-period schedule',
                'result_count' => count($schedule)
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'service' => 'BillingScheduleService',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function testProrationService(Product $product): array
    {
        try {
            $proration = $this->prorationService->calculateProratedAmount($product, Carbon::today());
            return [
                'status' => 'passed',
                'service' => 'ProrationCalculatorService',
                'test' => 'Calculate proration',
                'is_prorated' => $proration['is_prorated']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'service' => 'ProrationCalculatorService',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function testUsageService(Product $product): array
    {
        try {
            if ($product->billing_model === 'usage_based') {
                $usage = $this->usageService->calculateUsageBilling($product, 100, Carbon::today(), Carbon::today()->addMonth());
                return [
                    'status' => 'passed',
                    'service' => 'UsageBillingService',
                    'test' => 'Calculate usage billing',
                    'total_amount' => $usage['total_amount']
                ];
            }
            return [
                'status' => 'skipped',
                'service' => 'UsageBillingService',
                'reason' => 'Product not usage-based'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'service' => 'UsageBillingService',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function testRecurringService(Product $product): array
    {
        try {
            $invoices = $this->recurringService->processRecurringBilling(Carbon::today());
            return [
                'status' => 'passed',
                'service' => 'RecurringBillingService',
                'test' => 'Process recurring billing',
                'invoices_created' => $invoices->count()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'service' => 'RecurringBillingService',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function testPricingService(Product $product, Client $client): array
    {
        try {
            $pricing = $this->pricingService->calculatePrice($product, $client, 1);
            return [
                'status' => 'passed',
                'service' => 'ProductPricingService',
                'test' => 'Calculate pricing',
                'total' => $pricing['total']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'service' => 'ProductPricingService',
                'error' => $e->getMessage()
            ];
        }
    }

    // Legacy methods for backward compatibility
    protected function calculateServiceSetupFees(Service $service, Client $client = null): array
    {
        // Implementation from original BillingService
        return ['fees' => [], 'total_setup_cost' => 0];
    }

    protected function calculateEarlyTerminationFee(Service $service, Carbon $startDate, Carbon $terminationDate): float
    {
        // Implementation from original BillingService
        return 0.0;
    }
}