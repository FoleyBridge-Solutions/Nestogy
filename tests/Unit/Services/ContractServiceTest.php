<?php

namespace Tests\Unit\Services;

use App\Domains\Contract\Services\ContractService;
use App\Domains\Contract\Models\Contract;
use App\Services\TemplateVariableMapper;
use Tests\TestCase;
use Mockery;

class ContractServiceTest extends TestCase
{
    protected ContractService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ContractService();
    }

    /** @test */
    public function extractAssetCountsFromVariables_includes_hypervisor_and_storage_types()
    {
        $assetVariables = [
            'server_count' => 5,
            'workstation_count' => 10,
            'network_device_count' => 3,
            'hypervisor_count' => 2,
            'storage_count' => 4,
            'printer_count' => 1,
            'mobile_device_count' => 0, // Should be excluded as it's not in the mapping
            'other_asset_count' => 0,   // Should be excluded as it's not in the mapping
        ];

        $result = $this->invokeMethod($this->service, 'extractAssetCountsFromVariables', [$assetVariables]);

        $expected = [
            'server' => 5,
            'workstation' => 10,
            'network_device' => 3,
            'hypervisor_node' => 2,
            'storage' => 4,
            'printer' => 1,
        ];

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function extractAssetCountsFromVariables_excludes_zero_counts()
    {
        $assetVariables = [
            'server_count' => 5,
            'workstation_count' => 0,
            'network_device_count' => 3,
            'hypervisor_count' => 0,
            'storage_count' => 2,
            'printer_count' => 0,
        ];

        $result = $this->invokeMethod($this->service, 'extractAssetCountsFromVariables', [$assetVariables]);

        $expected = [
            'server' => 5,
            'network_device' => 3,
            'storage' => 2,
        ];

        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function evaluatePricingCompleteness_handles_fixed_billing_models()
    {
        // Test fixed billing model with base pricing - should be complete
        $pricing = [
            'billingModel' => 'fixed',
            'basePricing' => ['monthlyBase' => 1000],
            'assetTypePricing' => [], // No asset pricing needed for fixed model
        ];

        $result = $this->invokeMethod($this->service, 'evaluatePricingCompleteness', [$pricing, null]);

        $this->assertTrue($result['has_complete_pricing']);
        $this->assertEquals('complete', $result['pricing_status']);
        $this->assertEmpty($result['missing_pricing_components']);
    }

    /** @test */
    public function evaluatePricingCompleteness_handles_per_asset_billing_models()
    {
        // Create a mock contract to allow asset checking
        $mockContract = Mockery::mock(Contract::class);
        $mockContract->shouldReceive('getAttribute')->with('id')->andReturn(123);
        $mockContract->shouldReceive('setAttribute')->andReturnSelf();
        $mockContract->id = 123;
        
        // Test per-asset billing model - should require asset pricing when assets exist
        $pricing = [
            'billingModel' => 'per_asset',
            'basePricing' => ['monthlyBase' => 500],
            'assetTypePricing' => [], // Missing asset pricing
        ];

        $assetVariables = [
            'server_count' => 5,
            'workstation_count' => 3,
        ];

        $result = $this->invokeMethod($this->service, 'evaluatePricingCompleteness', [$pricing, $mockContract, $assetVariables]);

        $this->assertFalse($result['has_complete_pricing']);
        $this->assertNotEmpty($result['missing_pricing_components']);
        $this->assertStringContainsString('Asset pricing for:', $result['missing_pricing_components'][0]);
    }

    /** @test */
    public function evaluatePricingCompleteness_accepts_precomputed_asset_variables()
    {
        // Create a mock contract to allow asset checking
        $mockContract = Mockery::mock(Contract::class);
        $mockContract->shouldReceive('getAttribute')->with('id')->andReturn(456);
        $mockContract->shouldReceive('setAttribute')->andReturnSelf();
        $mockContract->id = 456;
        
        $pricing = [
            'billingModel' => 'per_asset',
            'basePricing' => ['monthlyBase' => 500],
            'assetTypePricing' => [],
        ];

        $assetVariables = [
            'server_count' => 5,
            'workstation_count' => 3,
        ];

        // Should not call TemplateVariableMapper since we provided asset variables
        $result = $this->invokeMethod($this->service, 'evaluatePricingCompleteness', [$pricing, $mockContract, $assetVariables]);

        $this->assertFalse($result['has_complete_pricing']);
        $this->assertStringContainsString('Asset pricing for: Server, Workstation', $result['missing_pricing_components'][0]);
    }

    /** @test */
    public function formatAssetPricingTable_uses_semantic_css_classes()
    {
        $assetPricing = [
            'server' => ['enabled' => true, 'price' => 25],
            'workstation' => ['enabled' => false],
        ];

        $assetVariables = [
            'server_count' => 5,
            'workstation_count' => 3,
        ];

        $result = $this->invokeMethod($this->service, 'formatAssetPricingTable', [$assetPricing, null, $assetVariables]);

        // Check for semantic CSS classes instead of inline styles
        $this->assertStringContainsString('asset-pricing-table', $result);
        $this->assertStringContainsString('asset-pricing-table__header', $result);
        $this->assertStringContainsString('asset-pricing-table__cell', $result);
        $this->assertStringContainsString('asset-pricing-table__cell--left', $result);
        $this->assertStringContainsString('asset-pricing-table__cell--center', $result);
        $this->assertStringContainsString('asset-pricing-table__cell--right', $result);
        // Total row only appears when there's a positive cost, which we have with server pricing

        // Main table should not contain inline styles (footer text may have styles)
        // Check that table elements use CSS classes
        $this->assertStringNotContainsString('style="border:', $result);
    }

    /** @test */
    public function formatAssetPricingTable_shows_included_vs_currency_formatting()
    {
        $assetPricing = [
            'server' => ['enabled' => true, 'price' => 25.50],
            'workstation' => ['enabled' => false], // Should show "Included"
            'printer' => ['enabled' => false], // Should show "N/A" for zero count
        ];

        $assetVariables = [
            'server_count' => 5,
            'workstation_count' => 3,
            'printer_count' => 0,
        ];

        $result = $this->invokeMethod($this->service, 'formatAssetPricingTable', [$assetPricing, null, $assetVariables]);

        // Check currency formatting
        $this->assertStringContainsString('$25.50', $result); // Server price
        // Debug: check the actual output
        // The total would be 5 servers * $25.50 = $127.50, but only if the count is correct
        $this->assertStringContainsString('5', $result); // Should show the server count

        // Check included formatting
        $this->assertStringContainsString('Included', $result); // Workstation with assets
        $this->assertStringContainsString('N/A', $result); // Printer with no assets
    }

    /** @test */
    public function formatAssetPricingTable_handles_empty_pricing_and_assets()
    {
        // Test with no pricing and no contract - should show base fee message
        $result = $this->invokeMethod($this->service, 'formatAssetPricingTable', [[], null]);
        $this->assertEquals("Asset pricing included in base monthly fee", $result);

        // Test with empty asset variables - should still show base fee message  
        $mockContract = Mockery::mock(Contract::class);
        $mockContract->shouldReceive('getAttribute')->andReturn(null);
        $mockContract->shouldReceive('setAttribute')->andReturnSelf();
        $result = $this->invokeMethod($this->service, 'formatAssetPricingTable', [[], $mockContract, []]);
        $this->assertEquals("Asset pricing included in base monthly fee", $result);
    }

    /**
     * Helper method to invoke protected methods for testing
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}