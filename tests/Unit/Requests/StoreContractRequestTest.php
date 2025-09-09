<?php

namespace Tests\Unit\Requests;

use App\Domains\Contract\Requests\StoreContractRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContractRequestTest extends TestCase
{
    /** @test */
    public function sla_terms_as_json_string_passes_validation_after_preprocessing()
    {
        $validJsonString = '{"uptime_percentage":99.9,"response_time_hours":2,"resolution_time_hours":24}';
        
        // Simulate the preprocessing that happens in prepareForValidation
        $processedData = $this->getValidContractData([
            'sla_terms' => json_decode($validJsonString, true) // After preprocessing converts to array
        ]);

        // Create a mock request with user context to avoid company_id issues
        $request = \Mockery::mock(StoreContractRequest::class)->makePartial();
        $mockUser = \Mockery::mock();
        $mockUser->company_id = 1;
        $request->shouldReceive('user')->andReturn($mockUser);
        
        $rules = $request->rules();
        
        // Validate the request - focus only on sla_terms array rule
        $validator = Validator::make(['sla_terms' => $processedData['sla_terms']], ['sla_terms' => $rules['sla_terms']]);
        
        $this->assertTrue($validator->passes(), 'JSON string sla_terms should pass validation after preprocessing');
    }

    /** @test */
    public function sla_terms_json_string_normalizes_to_array_in_validated_with_computed()
    {
        $jsonString = '{"uptime_percentage":99.9,"response_time_hours":2}';
        $expectedArray = ['uptime_percentage' => 99.9, 'response_time_hours' => 2];
        
        $data = $this->getValidContractData([
            'sla_terms' => $jsonString
        ]);

        $request = $this->createMockStoreContractRequest($data);
        
        $validated = $request->validatedWithComputed();
        
        $this->assertIsArray($validated['sla_terms'], 'sla_terms should be normalized to array');
        $this->assertEquals($expectedArray, $validated['sla_terms'], 'JSON string should be decoded to correct array');
    }

    /** @test */
    public function sla_terms_as_array_remains_array_in_validated_with_computed()
    {
        $arrayData = ['uptime_percentage' => 98.5, 'response_time_hours' => 4];
        
        $data = $this->getValidContractData([
            'sla_terms' => $arrayData
        ]);

        $request = $this->createMockStoreContractRequest($data);
        
        $validated = $request->validatedWithComputed();
        
        $this->assertIsArray($validated['sla_terms'], 'sla_terms should remain as array');
        $this->assertEquals($arrayData, $validated['sla_terms'], 'Array sla_terms should remain unchanged');
    }

    /** @test */
    public function invalid_json_string_for_sla_terms_fails_validation()
    {
        $invalidJsonString = '{"uptime_percentage":99.9,"response_time_hours":}'; // Invalid JSON
        
        // Create a mock request with user context
        $request = \Mockery::mock(StoreContractRequest::class)->makePartial();
        $mockUser = \Mockery::mock();
        $mockUser->company_id = 1;
        $request->shouldReceive('user')->andReturn($mockUser);
        
        $rules = $request->rules();
        
        // Validate the request - focus only on sla_terms validation
        $validator = Validator::make(['sla_terms' => $invalidJsonString], ['sla_terms' => $rules['sla_terms']]);
        
        $this->assertTrue($validator->fails(), 'Invalid JSON string should fail validation');
        $this->assertTrue($validator->errors()->has('sla_terms'), 'Should have sla_terms validation error');
        
        // Check that it's specifically an array rule error
        $slaErrors = $validator->errors()->get('sla_terms');
        $this->assertStringContainsString('array', implode(' ', $slaErrors), 'Should fail with array rule error');
    }

    /** @test */
    public function empty_json_object_string_for_sla_terms_normalizes_to_empty_array()
    {
        $emptyJsonString = '{}';
        
        $data = $this->getValidContractData([
            'sla_terms' => $emptyJsonString
        ]);

        $request = $this->createMockStoreContractRequest($data);
        
        $validated = $request->validatedWithComputed();
        
        $this->assertIsArray($validated['sla_terms'], 'Empty JSON object should normalize to array');
        $this->assertEmpty($validated['sla_terms'], 'Empty JSON object should normalize to empty array');
    }

    /** @test */
    public function null_sla_terms_does_not_get_normalized()
    {
        $data = $this->getValidContractData([
            'sla_terms' => null
        ]);

        $request = $this->createMockStoreContractRequest($data);
        
        $validated = $request->validatedWithComputed();
        
        // null values pass through unchanged (not processed by our normalization logic)
        $this->assertNull($validated['sla_terms'], 'null sla_terms should remain null and not be normalized');
    }

    /** @test */
    public function empty_string_sla_terms_does_not_get_normalized()
    {
        $data = $this->getValidContractData([
            'sla_terms' => ''
        ]);

        $request = $this->createMockStoreContractRequest($data);
        
        $validated = $request->validatedWithComputed();
        
        // Empty string values pass through unchanged (not processed by our normalization logic)
        $this->assertEquals('', $validated['sla_terms'], 'Empty string sla_terms should remain empty string and not be normalized');
    }

    /** @test */
    public function non_string_non_array_sla_terms_normalizes_to_empty_array()
    {
        $data = $this->getValidContractData([
            'sla_terms' => 123 // Invalid type
        ]);

        $request = $this->createMockStoreContractRequest($data);
        
        $validated = $request->validatedWithComputed();
        
        $this->assertIsArray($validated['sla_terms'], 'Invalid type should normalize to array');
        $this->assertEmpty($validated['sla_terms'], 'Invalid type should normalize to empty array');
    }

    /** @test */
    public function complex_nested_json_string_for_sla_terms_normalizes_correctly()
    {
        $complexJsonString = '{"uptime_percentage":99.9,"response_times":{"business_hours":2,"after_hours":4},"escalation_tiers":["level1","level2"]}';
        $expectedArray = [
            'uptime_percentage' => 99.9,
            'response_times' => [
                'business_hours' => 2,
                'after_hours' => 4
            ],
            'escalation_tiers' => ['level1', 'level2']
        ];
        
        $data = $this->getValidContractData([
            'sla_terms' => $complexJsonString
        ]);

        $request = $this->createMockStoreContractRequest($data);
        
        $validated = $request->validatedWithComputed();
        
        $this->assertIsArray($validated['sla_terms'], 'Complex JSON should normalize to array');
        $this->assertEquals($expectedArray, $validated['sla_terms'], 'Complex JSON should be decoded correctly');
    }

    /**
     * Create a mock StoreContractRequest instance that bypasses database dependencies
     */
    protected function createMockStoreContractRequest(array $data): StoreContractRequest
    {
        $request = \Mockery::mock(StoreContractRequest::class)->makePartial();
        
        // Mock the validated method to return the data without database validation
        $request->shouldReceive('validated')->andReturn($data);
        
        return $request;
    }

    /**
     * Get valid contract data for testing
     */
    protected function getValidContractData(array $overrides = []): array
    {
        return array_merge([
            'client_id' => 1, // Mock client ID
            'contract_type' => 'managed_services',
            'title' => 'Test Contract',
            'description' => 'Test contract description',
            'start_date' => now()->addDays(1)->format('Y-m-d'),
            'end_date' => now()->addMonths(12)->format('Y-m-d'),
            'currency_code' => 'USD',
            'payment_terms' => 'Net 30',
            'auto_renewal' => false,
        ], $overrides);
    }
}