<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Domains\Financial\Services\VoIPTaxService;
use App\Models\VoIPTaxRate;
use App\Models\TaxJurisdiction;
use App\Models\TaxCategory;
use App\Models\TaxExemption;
use App\Models\InvoiceItem;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * VoIP Tax Service Test Suite
 * 
 * Comprehensive tests for VoIP tax calculation functionality.
 */
class VoIPTaxServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VoIPTaxService $taxService;
    protected int $companyId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxService = new VoIPTaxService($this->companyId);
    }

    /** @test */
    public function it_can_calculate_federal_excise_tax()
    {
        // Federal excise tax is 3% on amounts over $0.20
        $testCases = [
            ['amount' => 0.10, 'expected' => 0.00], // Below threshold
            ['amount' => 0.20, 'expected' => 0.00], // At threshold
            ['amount' => 1.00, 'expected' => 0.03], // Above threshold
            ['amount' => 10.00, 'expected' => 0.30], // Larger amount
            ['amount' => 100.00, 'expected' => 3.00], // Much larger amount
        ];

        foreach ($testCases as $case) {
            $tax = $this->taxService->calculateFederalExciseTax($case['amount']);
            $this->assertEquals($case['expected'], $tax, "Failed for amount {$case['amount']}");
        }
    }

    /** @test */
    public function it_can_calculate_usf_contribution()
    {
        // USF contribution is typically 33.4% of gross receipts
        $grossReceipts = 100.00;
        $expectedUsf = 33.40; // 100.00 * 0.334

        $usf = $this->taxService->calculateUSFContribution($grossReceipts);
        
        $this->assertEquals($expectedUsf, $usf);
    }

    /** @test */
    public function it_can_calculate_state_tax_with_percentage_rate()
    {
        // Create test jurisdiction and tax rate
        $jurisdiction = TaxJurisdiction::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'California',
            'jurisdiction_type' => 'state',
        ]);

        $category = TaxCategory::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Telecommunications',
        ]);

        $taxRate = VoIPTaxRate::factory()->create([
            'company_id' => $this->companyId,
            'tax_jurisdiction_id' => $jurisdiction->id,
            'tax_category_id' => $category->id,
            'tax_name' => 'CA State Tax',
            'rate_type' => 'percentage',
            'percentage_rate' => 5.25,
        ]);

        $baseAmount = 100.00;
        $expectedTax = 5.25; // 100.00 * 0.0525

        $tax = $this->taxService->calculateStateTax($baseAmount, 'CA', '90210', 'local');

        $this->assertEquals($expectedTax, $tax);
    }

    /** @test */
    public function it_can_calculate_local_tax_with_fixed_amount()
    {
        // Create test local jurisdiction with fixed amount tax
        $jurisdiction = TaxJurisdiction::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Los Angeles County',
            'jurisdiction_type' => 'county',
        ]);

        $category = TaxCategory::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'VoIP Services',
        ]);

        $taxRate = VoIPTaxRate::factory()->create([
            'company_id' => $this->companyId,
            'tax_jurisdiction_id' => $jurisdiction->id,
            'tax_category_id' => $category->id,
            'tax_name' => 'E911 Fee',
            'rate_type' => 'fixed_amount',
            'fixed_amount' => 1.50,
        ]);

        $baseAmount = 50.00; // Amount shouldn't matter for fixed rate
        $expectedTax = 1.50;

        $tax = $this->taxService->calculateLocalTax($baseAmount, '90210', 'voip_fixed');

        $this->assertEquals($expectedTax, $tax);
    }

    /** @test */
    public function it_can_calculate_comprehensive_tax_for_invoice_item()
    {
        // Set up test data
        $this->createTestTaxRates();

        $client = Client::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $invoiceItem = InvoiceItem::factory()->create([
            'company_id' => $this->companyId,
            'subtotal' => 100.00,
            'discount' => 10.00,
            'service_type' => 'local',
            'service_address' => [
                'street' => '123 Main St',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'zip' => '90210',
            ],
        ]);

        $result = $this->taxService->calculateTaxForInvoiceItem($invoiceItem, $client->id);

        // Verify result structure
        $this->assertArrayHasKey('total_tax_amount', $result);
        $this->assertArrayHasKey('tax_breakdown', $result);
        $this->assertArrayHasKey('service_type', $result);
        $this->assertEquals('local', $result['service_type']);

        // Verify tax breakdown has expected components
        $breakdown = $result['tax_breakdown'];
        $this->assertGreaterThan(0, count($breakdown));

        // Verify total tax is sum of all components
        $calculatedTotal = array_sum(array_column($breakdown, 'tax_amount'));
        $this->assertEquals($calculatedTotal, $result['total_tax_amount']);
    }

    /** @test */
    public function it_applies_tax_exemptions_correctly()
    {
        $this->createTestTaxRates();

        $client = Client::factory()->create([
            'company_id' => $this->companyId,
        ]);

        // Create a tax exemption for the client
        $exemption = TaxExemption::factory()->create([
            'company_id' => $this->companyId,
            'client_id' => $client->id,
            'exemption_type' => 'nonprofit',
            'exemption_percentage' => 100.00, // Full exemption
            'status' => TaxExemption::STATUS_ACTIVE,
        ]);

        $invoiceItem = InvoiceItem::factory()->create([
            'company_id' => $this->companyId,
            'subtotal' => 100.00,
            'discount' => 0.00,
            'service_type' => 'local',
            'service_address' => [
                'street' => '123 Main St',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'zip' => '90210',
            ],
        ]);

        $result = $this->taxService->calculateTaxForInvoiceItem($invoiceItem, $client->id);

        // With full exemption, total tax should be 0
        $this->assertEquals(0.00, $result['total_tax_amount']);
        $this->assertArrayHasKey('exemptions_applied', $result);
        $this->assertGreaterThan(0, count($result['exemptions_applied']));
    }

    /** @test */
    public function it_handles_different_service_types()
    {
        $this->createTestTaxRates();

        $client = Client::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $serviceTypes = ['local', 'long_distance', 'international', 'voip_fixed', 'voip_nomadic', 'data', 'equipment'];

        foreach ($serviceTypes as $serviceType) {
            $invoiceItem = InvoiceItem::factory()->create([
                'company_id' => $this->companyId,
                'subtotal' => 50.00,
                'discount' => 0.00,
                'service_type' => $serviceType,
                'service_address' => [
                    'street' => '123 Main St',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'zip' => '90210',
                ],
            ]);

            $result = $this->taxService->calculateTaxForInvoiceItem($invoiceItem, $client->id);

            $this->assertArrayHasKey('total_tax_amount', $result);
            $this->assertEquals($serviceType, $result['service_type']);
            
            // Tax amount should be reasonable (between 0 and base amount)
            $this->assertGreaterThanOrEqual(0, $result['total_tax_amount']);
            $this->assertLessThanOrEqual(50.00, $result['total_tax_amount']);
        }
    }

    /** @test */
    public function it_caches_tax_calculations()
    {
        Cache::flush();

        $this->createTestTaxRates();

        $client = Client::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $invoiceItem = InvoiceItem::factory()->create([
            'company_id' => $this->companyId,
            'subtotal' => 100.00,
            'discount' => 0.00,
            'service_type' => 'local',
            'service_address' => [
                'street' => '123 Main St',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'zip' => '90210',
            ],
        ]);

        // First calculation should miss cache
        $startTime = microtime(true);
        $result1 = $this->taxService->calculateTaxForInvoiceItem($invoiceItem, $client->id);
        $time1 = microtime(true) - $startTime;

        // Second calculation should hit cache and be faster
        $startTime = microtime(true);
        $result2 = $this->taxService->calculateTaxForInvoiceItem($invoiceItem, $client->id);
        $time2 = microtime(true) - $startTime;

        // Results should be identical
        $this->assertEquals($result1['total_tax_amount'], $result2['total_tax_amount']);
        
        // Second calculation should be faster (cached)
        $this->assertLessThan($time1, $time2);
    }

    /** @test */
    public function it_validates_service_addresses()
    {
        $client = Client::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $invoiceItem = InvoiceItem::factory()->create([
            'company_id' => $this->companyId,
            'subtotal' => 100.00,
            'service_type' => 'local',
            'service_address' => null, // Missing address
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service address is required for tax calculation');

        $this->taxService->calculateTaxForInvoiceItem($invoiceItem, $client->id);
    }

    /** @test */
    public function it_handles_tiered_tax_rates()
    {
        // Create tiered tax rate (rate increases with amount)
        $jurisdiction = TaxJurisdiction::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Test Jurisdiction',
            'jurisdiction_type' => 'state',
        ]);

        $category = TaxCategory::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Tiered Service',
        ]);

        $taxRate = VoIPTaxRate::factory()->create([
            'company_id' => $this->companyId,
            'tax_jurisdiction_id' => $jurisdiction->id,
            'tax_category_id' => $category->id,
            'tax_name' => 'Tiered Tax',
            'rate_type' => 'tiered',
            'tier_structure' => [
                ['min_amount' => 0, 'max_amount' => 50, 'rate' => 2.0],
                ['min_amount' => 50.01, 'max_amount' => 100, 'rate' => 4.0],
                ['min_amount' => 100.01, 'max_amount' => null, 'rate' => 6.0],
            ],
        ]);

        // Test different amounts in different tiers
        $this->assertEquals(1.00, $this->taxService->calculateTieredTax(50.00, $taxRate->tier_structure)); // 50 * 2%
        $this->assertEquals(3.00, $this->taxService->calculateTieredTax(75.00, $taxRate->tier_structure)); // 75 * 4%
        $this->assertEquals(7.50, $this->taxService->calculateTieredTax(125.00, $taxRate->tier_structure)); // 125 * 6%
    }

    /**
     * Create test tax rates for comprehensive testing.
     */
    protected function createTestTaxRates(): void
    {
        // Federal rates
        $federalJurisdiction = TaxJurisdiction::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Federal',
            'jurisdiction_type' => 'federal',
        ]);

        $telecomCategory = TaxCategory::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Telecommunications',
        ]);

        // Federal excise tax
        VoIPTaxRate::factory()->create([
            'company_id' => $this->companyId,
            'tax_jurisdiction_id' => $federalJurisdiction->id,
            'tax_category_id' => $telecomCategory->id,
            'tax_name' => 'Federal Excise Tax',
            'rate_type' => 'percentage',
            'percentage_rate' => 3.00,
            'service_types' => ['local', 'long_distance'],
            'conditions' => ['min_amount' => 0.20],
        ]);

        // USF contribution
        VoIPTaxRate::factory()->create([
            'company_id' => $this->companyId,
            'tax_jurisdiction_id' => $federalJurisdiction->id,
            'tax_category_id' => $telecomCategory->id,
            'tax_name' => 'USF Contribution',
            'rate_type' => 'percentage',
            'percentage_rate' => 33.4,
            'service_types' => ['local', 'long_distance', 'international'],
        ]);

        // State rates
        $stateJurisdiction = TaxJurisdiction::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'California',
            'jurisdiction_type' => 'state',
        ]);

        VoIPTaxRate::factory()->create([
            'company_id' => $this->companyId,
            'tax_jurisdiction_id' => $stateJurisdiction->id,
            'tax_category_id' => $telecomCategory->id,
            'tax_name' => 'CA State Tax',
            'rate_type' => 'percentage',
            'percentage_rate' => 5.25,
            'service_types' => ['local', 'long_distance', 'voip_fixed', 'voip_nomadic'],
        ]);

        // Local rates
        $localJurisdiction = TaxJurisdiction::factory()->create([
            'company_id' => $this->companyId,
            'name' => 'Los Angeles County',
            'jurisdiction_type' => 'county',
        ]);

        VoIPTaxRate::factory()->create([
            'company_id' => $this->companyId,
            'tax_jurisdiction_id' => $localJurisdiction->id,
            'tax_category_id' => $telecomCategory->id,
            'tax_name' => 'E911 Fee',
            'rate_type' => 'fixed_amount',
            'fixed_amount' => 1.50,
            'service_types' => ['local', 'voip_fixed', 'voip_nomadic'],
        ]);
    }
}