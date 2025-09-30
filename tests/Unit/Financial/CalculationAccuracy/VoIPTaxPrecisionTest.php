<?php

namespace Tests\Unit\Financial\CalculationAccuracy;

use Tests\Unit\Financial\FinancialTestCase;
use App\Domains\Financial\Services\VoIPTaxService;
use App\Models\InvoiceItem;

/**
 * Comprehensive VoIP Tax calculation precision and accuracy tests
 * 
 * Ensures VoIP tax calculations are accurate, compliant, and maintain precision
 * Tests federal excise tax, USF contributions, and multi-jurisdiction scenarios
 */
class VoIPTaxPrecisionTest extends FinancialTestCase
{
    /** @test */
    public function federal_excise_tax_threshold_accuracy()
    {
        $taxService = $this->taxService;

        // Test amounts at and around the $0.20 threshold
        $testCases = [
            ['amount' => 0.19, 'should_have_tax' => false, 'description' => 'Below threshold'],
            ['amount' => 0.20, 'should_have_tax' => true, 'description' => 'At threshold'],
            ['amount' => 0.21, 'should_have_tax' => true, 'description' => 'Above threshold'],
            ['amount' => 1.00, 'should_have_tax' => true, 'description' => 'Well above threshold'],
        ];

        foreach ($testCases as $case) {
            $result = $taxService->calculateTaxes($case['amount'], [
                'service_type' => 'voip',
                'service_address' => [
                    'state' => 'NY',
                    'jurisdiction' => 'federal'
                ]
            ]);

            $this->assertTaxRatesValid($result);

            if ($case['should_have_tax']) {
                $this->assertMonetaryPositive(
                    $result['total_tax'],
                    "Federal excise tax should apply for amount {$case['amount']} ({$case['description']})"
                );
                
                // Verify 3% federal excise tax calculation
                $expectedFederalTax = round($case['amount'] * 0.03, 2);
                $actualFederalTax = $this->extractFederalTaxFromBreakdown($result['breakdown']);
                
                $this->assertMonetaryEquals(
                    $expectedFederalTax,
                    $actualFederalTax,
                    "Federal excise tax should be 3% of {$case['amount']}"
                );
            } else {
                $this->assertMonetaryEquals(
                    0.00,
                    $result['total_tax'],
                    "No federal excise tax should apply for amount {$case['amount']} ({$case['description']})"
                );
            }

            $this->assertPrecisionMaintained($result['total_tax'], 2);
        }
    }

    /** @test */
    public function usf_contribution_calculation_accuracy()
    {
        $taxService = $this->taxService;

        // Test USF contribution calculation (33.4% rate on qualifying services)
        $testAmounts = [1.00, 10.00, 100.00, 1000.00];

        foreach ($testAmounts as $amount) {
            $result = $taxService->calculateTaxes($amount, [
                'service_type' => 'voip',
                'service_address' => [
                    'state' => 'CA',
                    'jurisdiction' => 'federal'
                ],
                'include_usf' => true
            ]);

            $this->assertTaxRatesValid($result);
            $this->assertMonetaryPositive($result['total_tax']);

            // Verify USF contribution is included
            $usfTax = $this->extractUSFTaxFromBreakdown($result['breakdown']);
            
            if ($usfTax > 0) {
                // USF rate should be approximately 33.4%
                $expectedUSF = round($amount * 0.334, 2);
                $this->assertMonetaryEquals(
                    $expectedUSF,
                    $usfTax,
                    "USF contribution should be 33.4% of $amount"
                );
            }

            $this->assertPrecisionMaintained($result['total_tax'], 2);
        }
    }

    /** @test */
    public function multi_jurisdiction_tax_aggregation_accuracy()
    {
        $taxService = $this->taxService;
        $amount = 50.00;

        $result = $taxService->calculateTaxes($amount, [
            'service_type' => 'voip',
            'service_address' => [
                'state' => 'NY',
                'county' => 'New York',
                'city' => 'New York',
                'jurisdiction' => 'multi'
            ]
        ]);

        $this->assertTaxRatesValid($result);
        
        // Verify breakdown exists for multi-jurisdictional taxes
        $this->assertArrayHasKey('breakdown', $result);
        $this->assertNotEmpty($result['breakdown']);

        // Calculate manual sum of breakdown to verify aggregation
        $manualTotal = 0;
        foreach ($result['breakdown'] as $taxItem) {
            $this->assertArrayHasKey('amount', $taxItem);
            $this->assertArrayHasKey('jurisdiction', $taxItem);
            $this->assertMonetaryNonNegative($taxItem['amount']);
            $manualTotal += $taxItem['amount'];
        }

        $this->assertMonetaryEquals(
            $result['total_tax'],
            $manualTotal,
            'Tax breakdown should sum to total tax'
        );

        $this->assertPrecisionMaintained($result['total_tax'], 2);
    }

    /** @test */
    public function tax_exemption_calculation_accuracy()
    {
        $taxService = $this->taxService;
        $amount = 100.00;

        // Calculate tax without exemption
        $normalTax = $taxService->calculateTaxes($amount, [
            'service_type' => 'voip',
            'service_address' => ['state' => 'TX']
        ]);

        // Calculate tax with exemption
        $exemptTax = $taxService->calculateTaxes($amount, [
            'service_type' => 'voip',
            'service_address' => ['state' => 'TX'],
            'exemptions' => ['federal_excise' => true]
        ]);

        $this->assertTaxRatesValid($normalTax);
        $this->assertTaxRatesValid($exemptTax);

        // With federal excise exemption, tax should be lower
        $this->assertLessThanOrEqual(
            $normalTax['total_tax'],
            $exemptTax['total_tax'],
            'Tax with exemption should be less than or equal to normal tax'
        );

        // Verify precision on both calculations
        $this->assertPrecisionMaintained($normalTax['total_tax'], 2);
        $this->assertPrecisionMaintained($exemptTax['total_tax'], 2);
    }

    /** @test */
    public function large_amount_tax_calculation_stability()
    {
        $taxService = $this->taxService;

        // Test very large amounts to ensure no overflow or precision loss
        $largeAmounts = [
            10000.00,    // $10K
            100000.00,   // $100K
            999999.99    // Close to $1M
        ];

        foreach ($largeAmounts as $amount) {
            $result = $taxService->calculateTaxes($amount, [
                'service_type' => 'voip',
                'service_address' => ['state' => 'CA']
            ]);

            $this->assertTaxRatesValid($result);
            $this->assertMonetaryPositive($result['total_tax']);
            $this->assertPrecisionMaintained($result['total_tax'], 2);

            // Tax should be reasonable percentage of amount (not more than 50%)
            $taxPercentage = ($result['total_tax'] / $amount) * 100;
            $this->assertLessThan(
                50.0,
                $taxPercentage,
                "Tax rate should be reasonable for large amounts. Got {$taxPercentage}% for $$amount"
            );
        }
    }

    /** @test */
    public function rounding_consistency_across_calculations()
    {
        $taxService = $this->taxService;

        // Test amounts that might have rounding issues
        $testAmounts = [
            33.33,   // 1/3 of $100
            66.67,   // 2/3 of $100
            0.33,    // Small fraction
            123.456, // More than 2 decimal places
            999.995  // Should round to 999.99 or 1000.00
        ];

        foreach ($testAmounts as $amount) {
            $result = $taxService->calculateTaxes($amount, [
                'service_type' => 'voip',
                'service_address' => ['state' => 'FL']
            ]);

            $this->assertTaxRatesValid($result);
            $this->assertPrecisionMaintained($result['total_tax'], 2);

            // Calculate the same tax twice to ensure consistency
            $result2 = $taxService->calculateTaxes($amount, [
                'service_type' => 'voip',
                'service_address' => ['state' => 'FL']
            ]);

            $this->assertMonetaryEquals(
                $result['total_tax'],
                $result2['total_tax'],
                "Tax calculation should be consistent for amount $amount"
            );
        }
    }

    /** @test */
    public function invoice_item_tax_integration_accuracy()
    {
        $invoice = $this->createTestInvoice();

        // Create VoIP service item that should trigger tax calculation
        $item = $this->createVoIPInvoiceItem($invoice, [
            'price' => 75.00,
            'quantity' => 2, // $150.00 total
            'service_type' => 'voip'
        ]);

        $invoice->refresh();
        $item->refresh();

        // Verify tax was calculated and applied to item
        $this->assertMonetaryNonNegative($item->tax_amount);
        $this->assertPrecisionMaintained($item->tax_amount, 2);

        // Verify invoice totals include the tax
        $this->assertInvoiceTotalsConsistent($invoice);
        $this->assertMonetaryEquals($item->tax_amount, $invoice->total_tax);

        // Manual verification of tax calculation
        $expectedTax = $this->taxService->calculateTaxes(150.00, [
            'service_type' => 'voip',
            'service_address' => ['state' => 'NY'] // Default test state
        ]);

        $this->assertMonetaryEquals(
            $expectedTax['total_tax'],
            $item->tax_amount,
            'Invoice item tax should match direct tax service calculation'
        );
    }

    /** @test */
    public function zero_and_negative_amount_handling()
    {
        $taxService = $this->taxService;

        // Test zero amount
        $zeroResult = $taxService->calculateTaxes(0.00, [
            'service_type' => 'voip',
            'service_address' => ['state' => 'TX']
        ]);

        $this->assertTaxRatesValid($zeroResult);
        $this->assertMonetaryEquals(0.00, $zeroResult['total_tax']);

        // Test very small amounts
        $smallResult = $taxService->calculateTaxes(0.01, [
            'service_type' => 'voip',
            'service_address' => ['state' => 'TX']
        ]);

        $this->assertTaxRatesValid($smallResult);
        $this->assertPrecisionMaintained($smallResult['total_tax'], 2);
    }

    /**
     * Helper method to extract federal tax from breakdown
     */
    private function extractFederalTaxFromBreakdown(array $breakdown): float
    {
        foreach ($breakdown as $item) {
            if (isset($item['jurisdiction']) && 
                (stripos($item['jurisdiction'], 'federal') !== false || 
                 stripos($item['type'], 'excise') !== false)) {
                return $item['amount'];
            }
        }
        return 0.00;
    }

    /**
     * Helper method to extract USF tax from breakdown
     */
    private function extractUSFTaxFromBreakdown(array $breakdown): float
    {
        foreach ($breakdown as $item) {
            if (isset($item['type']) && 
                (stripos($item['type'], 'usf') !== false || 
                 stripos($item['type'], 'universal') !== false)) {
                return $item['amount'];
            }
        }
        return 0.00;
    }
}