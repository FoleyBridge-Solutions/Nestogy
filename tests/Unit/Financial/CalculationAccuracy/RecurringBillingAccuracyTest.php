<?php

namespace Tests\Unit\Financial\CalculationAccuracy;

use Tests\Unit\Financial\FinancialTestCase;
use App\Domains\Financial\Services\RecurringBillingService;
use App\Domains\Financial\Services\ProrationCalculatorService;
use App\Models\RecurringInvoice;
use App\Domains\Contract\Models\Contract;
use Carbon\Carbon;

/**
 * Comprehensive recurring billing calculation accuracy tests
 * 
 * Ensures recurring billing calculations are accurate, handle prorations correctly,
 * and maintain financial consistency across billing cycles
 */
class RecurringBillingAccuracyTest extends FinancialTestCase
{
    private RecurringBillingService $recurringService;
    private ProrationCalculatorService $prorationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recurringService = app(RecurringBillingService::class);
        $this->prorationService = app(ProrationCalculatorService::class);
    }

    /** @test */
    public function monthly_recurring_calculation_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_frequency' => 'monthly',
            'monthly_amount' => 500.00,
            'start_date' => Carbon::parse('2024-01-01'),
            'status' => 'active'
        ]);

        $recurringInvoice = RecurringInvoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'contract_id' => $contract->id,
            'amount' => 500.00,
            'frequency' => 'monthly',
            'next_billing_date' => Carbon::parse('2024-02-01'),
            'status' => 'active'
        ]);

        // Generate next billing amount
        $nextBilling = $this->recurringService->calculateNextBillingAmount($recurringInvoice);

        $this->assertMonetaryEquals(500.00, $nextBilling['amount']);
        $this->assertPrecisionMaintained($nextBilling['amount'], 2);
        $this->assertEquals('2024-03-01', $nextBilling['next_billing_date']->format('Y-m-d'));
    }

    /** @test */
    public function quarterly_recurring_calculation_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_frequency' => 'quarterly',
            'monthly_amount' => 1000.00, // $3000/quarter
            'start_date' => Carbon::parse('2024-01-01'),
            'status' => 'active'
        ]);

        $recurringInvoice = RecurringInvoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'contract_id' => $contract->id,
            'amount' => 3000.00, // 3 months * $1000
            'frequency' => 'quarterly',
            'next_billing_date' => Carbon::parse('2024-04-01'), // Next quarter
            'status' => 'active'
        ]);

        $nextBilling = $this->recurringService->calculateNextBillingAmount($recurringInvoice);

        $this->assertMonetaryEquals(3000.00, $nextBilling['amount']);
        $this->assertPrecisionMaintained($nextBilling['amount'], 2);
        $this->assertEquals('2024-07-01', $nextBilling['next_billing_date']->format('Y-m-d'));
    }

    /** @test */
    public function annual_recurring_calculation_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_frequency' => 'annually',
            'monthly_amount' => 800.00, // $9600/year
            'start_date' => Carbon::parse('2024-01-01'),
            'status' => 'active'
        ]);

        $recurringInvoice = RecurringInvoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'contract_id' => $contract->id,
            'amount' => 9600.00, // 12 months * $800
            'frequency' => 'annually',
            'next_billing_date' => Carbon::parse('2025-01-01'),
            'status' => 'active'
        ]);

        $nextBilling = $this->recurringService->calculateNextBillingAmount($recurringInvoice);

        $this->assertMonetaryEquals(9600.00, $nextBilling['amount']);
        $this->assertPrecisionMaintained($nextBilling['amount'], 2);
        $this->assertEquals('2026-01-01', $nextBilling['next_billing_date']->format('Y-m-d'));
    }

    /** @test */
    public function proration_calculation_accuracy_mid_month_start()
    {
        // Contract starts mid-month (Jan 15th)
        $startDate = Carbon::parse('2024-01-15');
        $endDate = Carbon::parse('2024-01-31');
        $monthlyAmount = 310.00; // $10/day in 31-day month

        $proratedAmount = $this->prorationService->calculateProration(
            $monthlyAmount,
            $startDate,
            $endDate,
            'monthly'
        );

        // From Jan 15 to Jan 31 = 17 days
        // $310/31 days * 17 days = $170.00
        $expectedAmount = round(($monthlyAmount / 31) * 17, 2);

        $this->assertMonetaryEquals($expectedAmount, $proratedAmount);
        $this->assertPrecisionMaintained($proratedAmount, 2);
    }

    /** @test */
    public function proration_calculation_accuracy_leap_year()
    {
        // Test proration in February during leap year
        $startDate = Carbon::parse('2024-02-01'); // 2024 is leap year
        $endDate = Carbon::parse('2024-02-15');
        $monthlyAmount = 290.00; // $10/day in 29-day month

        $proratedAmount = $this->prorationService->calculateProration(
            $monthlyAmount,
            $startDate,
            $endDate,
            'monthly'
        );

        // From Feb 1 to Feb 15 = 15 days
        // $290/29 days * 15 days = $150.00
        $expectedAmount = round(($monthlyAmount / 29) * 15, 2);

        $this->assertMonetaryEquals($expectedAmount, $proratedAmount);
        $this->assertPrecisionMaintained($proratedAmount, 2);
    }

    /** @test */
    public function proration_calculation_accuracy_cross_month()
    {
        // Test proration across month boundary
        $startDate = Carbon::parse('2024-01-25');
        $endDate = Carbon::parse('2024-02-10');
        $monthlyAmount = 600.00;

        $proratedAmount = $this->prorationService->calculateProration(
            $monthlyAmount,
            $startDate,
            $endDate,
            'monthly'
        );

        // Should calculate based on actual days in period
        $totalDays = $startDate->diffInDays($endDate) + 1; // 17 days
        $dailyRate = $monthlyAmount / 30; // Assuming 30-day billing cycle
        $expectedAmount = round($dailyRate * $totalDays, 2);

        $this->assertMonetaryEquals($expectedAmount, $proratedAmount);
        $this->assertMonetaryNonNegative($proratedAmount);
        $this->assertPrecisionMaintained($proratedAmount, 2);
    }

    /** @test */
    public function contract_escalation_calculation_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_frequency' => 'monthly',
            'monthly_amount' => 1000.00,
            'escalation_rate' => 5.0, // 5% annual escalation
            'escalation_frequency' => 'annually',
            'start_date' => Carbon::parse('2024-01-01'),
            'status' => 'active'
        ]);

        // Calculate escalated amount after 1 year
        $escalatedAmount = $this->recurringService->calculateEscalatedAmount(
            $contract,
            Carbon::parse('2025-01-01')
        );

        // $1000 + (5% of $1000) = $1050.00
        $expectedAmount = 1000.00 * 1.05;

        $this->assertMonetaryEquals($expectedAmount, $escalatedAmount);
        $this->assertPrecisionMaintained($escalatedAmount, 2);

        // Test 2-year escalation (compound)
        $twoYearEscalated = $this->recurringService->calculateEscalatedAmount(
            $contract,
            Carbon::parse('2026-01-01')
        );

        // $1000 * (1.05)^2 = $1102.50
        $expectedTwoYear = 1000.00 * pow(1.05, 2);

        $this->assertMonetaryEquals($expectedTwoYear, $twoYearEscalated);
        $this->assertPrecisionMaintained($twoYearEscalated, 2);
    }

    /** @test */
    public function usage_based_billing_calculation_accuracy()
    {
        $contract = $this->createTestContract([
            'billing_frequency' => 'monthly',
            'billing_model' => 'usage_based',
            'base_amount' => 200.00,
            'usage_rate' => 0.50, // $0.50 per unit
            'included_usage' => 100, // First 100 units included
            'status' => 'active'
        ]);

        // Test usage below included amount
        $lowUsageBill = $this->recurringService->calculateUsageBasedBilling(
            $contract,
            ['usage_units' => 75]
        );

        $this->assertMonetaryEquals(200.00, $lowUsageBill['total_amount']); // Just base amount
        $this->assertMonetaryEquals(0.00, $lowUsageBill['overage_charges']);

        // Test usage above included amount
        $highUsageBill = $this->recurringService->calculateUsageBasedBilling(
            $contract,
            ['usage_units' => 150]
        );

        // Base: $200 + Overage: (150-100) * $0.50 = $200 + $25 = $225
        $expectedTotal = 200.00 + (50 * 0.50);

        $this->assertMonetaryEquals($expectedTotal, $highUsageBill['total_amount']);
        $this->assertMonetaryEquals(25.00, $highUsageBill['overage_charges']);
        $this->assertPrecisionMaintained($highUsageBill['total_amount'], 2);
    }

    /** @test */
    public function recurring_billing_with_voip_tax_integration()
    {
        $contract = $this->createTestContract([
            'billing_frequency' => 'monthly',
            'monthly_amount' => 100.00,
            'service_type' => 'voip',
            'include_taxes' => true,
            'status' => 'active'
        ]);

        $recurringInvoice = RecurringInvoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'contract_id' => $contract->id,
            'amount' => 100.00,
            'frequency' => 'monthly',
            'include_taxes' => true,
            'status' => 'active'
        ]);

        $billingResult = $this->recurringService->generateInvoiceFromRecurring($recurringInvoice);

        $this->assertNotNull($billingResult['invoice']);
        $invoice = $billingResult['invoice'];

        // Verify tax was calculated and included
        $this->assertMonetaryPositive($invoice->total_tax);
        $this->assertInvoiceTotalsConsistent($invoice);
        
        // Total should be base amount plus tax
        $expectedTotal = 100.00 + $invoice->total_tax;
        $this->assertMonetaryEquals($expectedTotal, $invoice->amount);
        $this->assertPrecisionMaintained($invoice->amount, 2);
    }

    /** @test */
    public function recurring_billing_handles_failed_payment_scenarios()
    {
        $recurringInvoice = RecurringInvoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'amount' => 500.00,
            'frequency' => 'monthly',
            'failed_attempts' => 2,
            'status' => 'active'
        ]);

        // Test late fee calculation
        $lateFeeResult = $this->recurringService->calculateLateFees($recurringInvoice);

        $this->assertMonetaryNonNegative($lateFeeResult['late_fee']);
        $this->assertPrecisionMaintained($lateFeeResult['late_fee'], 2);

        // Test that total includes original amount plus late fees
        $totalWithFees = $lateFeeResult['original_amount'] + $lateFeeResult['late_fee'];
        $this->assertMonetaryEquals($totalWithFees, $lateFeeResult['total_amount']);
    }

    /** @test */
    public function recurring_billing_handles_partial_month_cancellation()
    {
        $contract = $this->createTestContract([
            'billing_frequency' => 'monthly',
            'monthly_amount' => 600.00,
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-01-15'), // Cancelled mid-month
            'status' => 'cancelled'
        ]);

        // Calculate final invoice for partial month
        $finalInvoice = $this->recurringService->calculateFinalInvoice($contract);

        // Should be prorated for 15 days out of 31
        $expectedAmount = round((600.00 / 31) * 15, 2);

        $this->assertMonetaryEquals($expectedAmount, $finalInvoice['amount']);
        $this->assertPrecisionMaintained($finalInvoice['amount'], 2);
    }

    /** @test */
    public function recurring_billing_precision_with_complex_pricing()
    {
        // Test complex pricing that might cause precision issues
        $contract = $this->createTestContract([
            'billing_frequency' => 'monthly',
            'monthly_amount' => 333.33, // 1/3 of $1000
            'escalation_rate' => 3.33, // Non-round percentage
            'status' => 'active'
        ]);

        $recurringInvoice = RecurringInvoice::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'contract_id' => $contract->id,
            'amount' => 333.33,
            'frequency' => 'monthly',
            'status' => 'active'
        ]);

        // Generate multiple billing cycles to test consistency
        for ($i = 0; $i < 12; $i++) {
            $billingResult = $this->recurringService->calculateNextBillingAmount($recurringInvoice);
            
            $this->assertPrecisionMaintained($billingResult['amount'], 2);
            $this->assertMonetaryPositive($billingResult['amount']);
            
            // Amount should be consistent with precision requirements
            $this->assertMonetaryEquals(333.33, $billingResult['amount']);
        }
    }
}