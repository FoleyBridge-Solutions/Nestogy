<?php

namespace Tests\Unit\Financial\CalculationAccuracy;

use Tests\Unit\Financial\FinancialTestCase;
use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Services\ContractService;
use App\Domains\Financial\Services\RecurringBillingService;
use App\Models\RecurringInvoice;
use Carbon\Carbon;

/**
 * Contract billing calculation accuracy tests
 * 
 * Ensures contract-based billing calculations are accurate across all
 * billing models, escalations, and complex pricing scenarios
 */
class ContractBillingAccuracyTest extends FinancialTestCase
{
    private ContractService $contractService;
    private RecurringBillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contractService = app(ContractService::class);
        $this->billingService = app(RecurringBillingService::class);
    }

    /** @test */
    public function fixed_price_contract_billing_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'fixed_price',
            'monthly_amount' => 2500.00,
            'billing_frequency' => 'monthly',
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-12-31'),
            'status' => 'active'
        ]);

        $billingCalculation = $this->contractService->calculateBillingAmount($contract);

        $this->assertMonetaryEquals(2500.00, $billingCalculation['amount']);
        $this->assertEquals('monthly', $billingCalculation['frequency']);
        $this->assertPrecisionMaintained($billingCalculation['amount'], 2);
        
        // Verify annual calculation
        $annualValue = $this->contractService->calculateAnnualValue($contract);
        $this->assertMonetaryEquals(30000.00, $annualValue); // $2500 * 12
    }

    /** @test */
    public function asset_based_contract_billing_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'asset_based',
            'per_asset_rate' => 15.00,
            'billing_frequency' => 'monthly',
            'asset_count' => 150,
            'status' => 'active'
        ]);

        $billingCalculation = $this->contractService->calculateBillingAmount($contract);

        // 150 assets * $15/asset = $2250/month
        $expectedAmount = 150 * 15.00;
        $this->assertMonetaryEquals($expectedAmount, $billingCalculation['amount']);
        $this->assertPrecisionMaintained($billingCalculation['amount'], 2);
    }

    /** @test */
    public function user_based_contract_billing_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'user_based',
            'per_user_rate' => 45.00,
            'billing_frequency' => 'monthly',
            'user_count' => 75,
            'minimum_users' => 50,
            'status' => 'active'
        ]);

        $billingCalculation = $this->contractService->calculateBillingAmount($contract);

        // 75 users * $45/user = $3375/month
        $expectedAmount = 75 * 45.00;
        $this->assertMonetaryEquals($expectedAmount, $billingCalculation['amount']);
        
        // Test minimum user billing
        $contractWithLowUsers = $this->createTestContract([
            'billing_model' => 'user_based',
            'per_user_rate' => 45.00,
            'user_count' => 25, // Below minimum
            'minimum_users' => 50,
        ]);

        $minimumBilling = $this->contractService->calculateBillingAmount($contractWithLowUsers);
        
        // Should bill for minimum 50 users despite only having 25
        $expectedMinimum = 50 * 45.00;
        $this->assertMonetaryEquals($expectedMinimum, $minimumBilling['amount']);
    }

    /** @test */
    public function hybrid_contract_billing_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'hybrid',
            'base_amount' => 1000.00,
            'per_asset_rate' => 5.00,
            'asset_count' => 200,
            'per_user_rate' => 25.00,
            'user_count' => 50,
            'billing_frequency' => 'monthly',
            'status' => 'active'
        ]);

        $billingCalculation = $this->contractService->calculateBillingAmount($contract);

        // Base: $1000 + Assets: (200 * $5) + Users: (50 * $25) = $1000 + $1000 + $1250 = $3250
        $expectedAmount = 1000.00 + (200 * 5.00) + (50 * 25.00);
        $this->assertMonetaryEquals($expectedAmount, $billingCalculation['amount']);
        $this->assertPrecisionMaintained($billingCalculation['amount'], 2);
    }

    /** @test */
    public function tiered_pricing_contract_billing_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'tiered',
            'billing_frequency' => 'monthly',
            'asset_count' => 125,
            'pricing_tiers' => [
                ['min' => 1, 'max' => 50, 'rate' => 20.00],
                ['min' => 51, 'max' => 100, 'rate' => 15.00],
                ['min' => 101, 'max' => 999, 'rate' => 10.00]
            ],
            'status' => 'active'
        ]);

        $billingCalculation = $this->contractService->calculateTieredBillingAmount($contract);

        // Tier 1: 50 assets * $20 = $1000
        // Tier 2: 50 assets * $15 = $750  
        // Tier 3: 25 assets * $10 = $250
        // Total: $2000
        $expectedAmount = (50 * 20.00) + (50 * 15.00) + (25 * 10.00);
        
        $this->assertMonetaryEquals($expectedAmount, $billingCalculation['amount']);
        $this->assertPrecisionMaintained($billingCalculation['amount'], 2);
    }

    /** @test */
    public function contract_escalation_calculation_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'fixed_price',
            'monthly_amount' => 2000.00,
            'escalation_rate' => 3.5, // 3.5% annual
            'escalation_frequency' => 'annually',
            'start_date' => Carbon::parse('2024-01-01'),
            'status' => 'active'
        ]);

        // Test escalation after 1 year
        $escalatedAmount1 = $this->contractService->calculateEscalatedAmount(
            $contract, 
            Carbon::parse('2025-01-01')
        );

        $expected1Year = 2000.00 * 1.035; // 3.5% increase
        $this->assertMonetaryEquals($expected1Year, $escalatedAmount1);

        // Test escalation after 2 years (compound)
        $escalatedAmount2 = $this->contractService->calculateEscalatedAmount(
            $contract,
            Carbon::parse('2026-01-01')
        );

        $expected2Years = 2000.00 * pow(1.035, 2);
        $this->assertMonetaryEquals($expected2Years, $escalatedAmount2);
        $this->assertPrecisionMaintained($escalatedAmount2, 2);
    }

    /** @test */
    public function quarterly_escalation_calculation_accuracy()
    {
        $contract = $this->createTestContract([
            'monthly_amount' => 1500.00,
            'escalation_rate' => 1.0, // 1% per quarter
            'escalation_frequency' => 'quarterly',
            'start_date' => Carbon::parse('2024-01-01'),
            'status' => 'active'
        ]);

        // Test after 1 quarter
        $escalated1Q = $this->contractService->calculateEscalatedAmount(
            $contract,
            Carbon::parse('2024-04-01')
        );

        $expected1Q = 1500.00 * 1.01;
        $this->assertMonetaryEquals($expected1Q, $escalated1Q);

        // Test after 1 year (4 quarters of compound growth)
        $escalated4Q = $this->contractService->calculateEscalatedAmount(
            $contract,
            Carbon::parse('2025-01-01')
        );

        $expected4Q = 1500.00 * pow(1.01, 4);
        $this->assertMonetaryEquals($expected4Q, $escalated4Q);
        $this->assertPrecisionMaintained($escalated4Q, 2);
    }

    /** @test */
    public function contract_with_voip_services_tax_calculation()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'service_based',
            'monthly_amount' => 800.00,
            'service_type' => 'voip',
            'include_taxes' => true,
            'billing_frequency' => 'monthly',
            'status' => 'active'
        ]);

        $billingCalculation = $this->contractService->calculateBillingWithTax($contract);

        $this->assertMonetaryEquals(800.00, $billingCalculation['base_amount']);
        $this->assertMonetaryPositive($billingCalculation['tax_amount']);
        
        $expectedTotal = $billingCalculation['base_amount'] + $billingCalculation['tax_amount'];
        $this->assertMonetaryEquals($expectedTotal, $billingCalculation['total_amount']);
        $this->assertPrecisionMaintained($billingCalculation['total_amount'], 2);
    }

    /** @test */
    public function contract_revenue_recognition_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'fixed_price',
            'monthly_amount' => 1200.00,
            'billing_frequency' => 'monthly',
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2026-12-31'), // 3-year contract
            'status' => 'active'
        ]);

        $revenueCalculation = $this->contractService->calculateRevenueRecognition($contract);

        // Monthly recurring revenue
        $this->assertMonetaryEquals(1200.00, $revenueCalculation['monthly_recurring']);
        
        // Annual recurring revenue
        $this->assertMonetaryEquals(14400.00, $revenueCalculation['annual_recurring']);
        
        // Total contract value (3 years)
        $this->assertMonetaryEquals(43200.00, $revenueCalculation['total_contract_value']);
        
        $this->assertPrecisionMaintained($revenueCalculation['total_contract_value'], 2);
    }

    /** @test */
    public function contract_billing_with_discounts_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'fixed_price',
            'monthly_amount' => 2000.00,
            'discount_type' => 'percentage',
            'discount_value' => 15.0, // 15% discount
            'billing_frequency' => 'monthly',
            'status' => 'active'
        ]);

        $billingCalculation = $this->contractService->calculateBillingWithDiscount($contract);

        $baseAmount = 2000.00;
        $discountAmount = $baseAmount * 0.15; // $300
        $expectedNetAmount = $baseAmount - $discountAmount; // $1700

        $this->assertMonetaryEquals($baseAmount, $billingCalculation['base_amount']);
        $this->assertMonetaryEquals($discountAmount, $billingCalculation['discount_amount']);
        $this->assertMonetaryEquals($expectedNetAmount, $billingCalculation['net_amount']);
        $this->assertPrecisionMaintained($billingCalculation['net_amount'], 2);
    }

    /** @test */
    public function contract_billing_handles_mid_cycle_changes()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'asset_based',
            'per_asset_rate' => 12.00,
            'asset_count' => 100,
            'billing_frequency' => 'monthly',
            'start_date' => Carbon::parse('2024-01-01'),
            'status' => 'active'
        ]);

        // Simulate asset count change mid-month (Jan 15)
        $changeDate = Carbon::parse('2024-01-15');
        $newAssetCount = 120;

        $prorationCalculation = $this->contractService->calculateMidCycleBillingChange(
            $contract,
            $changeDate,
            ['asset_count' => $newAssetCount]
        );

        // Calculate expected proration
        // First 14 days: 100 assets * $12 = $1200/month * (14/31) = ~$541.94
        // Remaining 17 days: 120 assets * $12 = $1440/month * (17/31) = ~$789.68
        // Total should be approximately $1331.62

        $this->assertMonetaryPositive($prorationCalculation['total_amount']);
        $this->assertPrecisionMaintained($prorationCalculation['total_amount'], 2);
        $this->assertArrayHasKey('proration_details', $prorationCalculation);
    }

    /** @test */
    public function contract_billing_edge_case_precision()
    {
        // Test edge cases that might cause precision issues
        $testCases = [
            [
                'model' => 'asset_based',
                'rate' => 33.33,
                'count' => 3,
                'expected' => 99.99
            ],
            [
                'model' => 'user_based', 
                'rate' => 66.67,
                'count' => 1.5, // Fractional user (consultant)
                'expected' => 100.005 // Should round to 100.01
            ],
            [
                'model' => 'fixed_price',
                'amount' => 0.01,
                'expected' => 0.01
            ]
        ];

        foreach ($testCases as $case) {
            $contractData = ['billing_frequency' => 'monthly', 'status' => 'active'];
            
            if ($case['model'] === 'asset_based') {
                $contractData['billing_model'] = 'asset_based';
                $contractData['per_asset_rate'] = $case['rate'];
                $contractData['asset_count'] = $case['count'];
            } elseif ($case['model'] === 'user_based') {
                $contractData['billing_model'] = 'user_based';
                $contractData['per_user_rate'] = $case['rate'];
                $contractData['user_count'] = $case['count'];
            } elseif ($case['model'] === 'fixed_price') {
                $contractData['billing_model'] = 'fixed_price';
                $contractData['monthly_amount'] = $case['amount'];
            }

            $contract = $this->createTestContract($contractData);
            $billing = $this->contractService->calculateBillingAmount($contract);

            $this->assertPrecisionMaintained($billing['amount'], 2);
            
            if (isset($case['expected'])) {
                $this->assertMonetaryEquals(
                    round($case['expected'], 2), 
                    $billing['amount'],
                    "Contract billing for {$case['model']} should handle precision correctly"
                );
            }
        }
    }

    /** @test */
    public function contract_billing_handles_complex_schedules()
    {
        $contract = $this->createTestContract([
            'billing_model' => 'scheduled',
            'billing_frequency' => 'custom',
            'billing_schedule' => [
                ['date' => '2024-01-15', 'amount' => 5000.00], // Setup fee
                ['date' => '2024-02-01', 'amount' => 2000.00], // Monthly
                ['date' => '2024-03-01', 'amount' => 2000.00], // Monthly
                ['date' => '2024-04-01', 'amount' => 2500.00], // Increased rate
            ],
            'status' => 'active'
        ]);

        foreach ($contract->billing_schedule as $scheduledBilling) {
            $calculation = $this->contractService->calculateScheduledBilling(
                $contract,
                Carbon::parse($scheduledBilling['date'])
            );

            $this->assertMonetaryEquals(
                $scheduledBilling['amount'],
                $calculation['amount']
            );
            $this->assertPrecisionMaintained($calculation['amount'], 2);
        }
    }
}