<?php

namespace Tests\Unit\Services;

use App\Services\ContractGenerationService;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Client;
use App\Models\User;
use App\Models\Company;
use App\Exceptions\ContractGenerationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ContractGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContractGenerationService $service;
    protected Company $company;
    protected User $user;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(ContractGenerationService::class);
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_generate_contract_from_template()
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Service Agreement Template',
            'content' => 'Contract for {{client_name}} with value of {{contract_value}}',
            'fields' => [
                'client_name' => ['type' => 'text', 'required' => true],
                'contract_value' => ['type' => 'currency', 'required' => true],
            ],
        ]);

        $data = [
            'client_id' => $this->client->id,
            'template_id' => $template->id,
            'title' => 'Test Service Agreement',
            'contract_type' => 'service_agreement',
            'contract_value' => 10000.00,
            'start_date' => now(),
            'end_date' => now()->addMonths(12),
            'field_values' => [
                'client_name' => $this->client->name,
                'contract_value' => '$10,000.00',
            ],
        ];

        $contract = $this->service->generateFromTemplate($template, $data);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals($this->company->id, $contract->company_id);
        $this->assertEquals($this->client->id, $contract->client_id);
        $this->assertEquals('Test Service Agreement', $contract->title);
        $this->assertEquals(10000.00, $contract->contract_value);
        $this->assertStringContains($this->client->name, $contract->content);
        $this->assertStringContains('$10,000.00', $contract->content);
    }

    /** @test */
    public function it_validates_required_template_fields()
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'content' => 'Contract for {{client_name}}',
            'fields' => [
                'client_name' => ['type' => 'text', 'required' => true],
                'project_description' => ['type' => 'textarea', 'required' => true],
            ],
        ]);

        $data = [
            'client_id' => $this->client->id,
            'template_id' => $template->id,
            'title' => 'Test Contract',
            'field_values' => [
                'client_name' => $this->client->name,
                // Missing required project_description
            ],
        ];

        $this->expectException(ContractGenerationException::class);
        
        $this->service->generateFromTemplate($template, $data);
    }

    /** @test */
    public function it_can_substitute_template_variables()
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'content' => 'This agreement is between {{client_name}} and {{company_name}} for services valued at {{contract_value}} starting on {{start_date}}.',
        ]);

        $variables = [
            'client_name' => 'Acme Corporation',
            'company_name' => 'Our Company',
            'contract_value' => '$50,000',
            'start_date' => '2024-01-01',
        ];

        $result = $this->service->substituteTemplateVariables($template->content, $variables);

        $expected = 'This agreement is between Acme Corporation and Our Company for services valued at $50,000 starting on 2024-01-01.';
        
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function it_handles_missing_template_variables()
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'content' => 'Contract for {{client_name}} with {{missing_variable}}.',
        ]);

        $variables = [
            'client_name' => 'Test Client',
        ];

        $result = $this->service->substituteTemplateVariables($template->content, $variables);

        $this->assertStringContains('Test Client', $result);
        $this->assertStringContains('{{missing_variable}}', $result); // Should remain unsubstituted
    }

    /** @test */
    public function it_can_validate_contract_data()
    {
        $validData = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'service_agreement',
            'contract_value' => 10000.00,
            'start_date' => now(),
            'end_date' => now()->addMonths(12),
        ];

        $result = $this->service->validateContractData($validData);
        $this->assertTrue($result);

        // Test invalid data
        $invalidData = [
            'client_id' => 999, // Non-existent client
            'title' => '', // Empty title
            'contract_value' => -100, // Negative value
        ];

        $this->expectException(ContractGenerationException::class);
        $this->service->validateContractData($invalidData);
    }

    /** @test */
    public function it_can_generate_unique_contract_number()
    {
        $number1 = $this->service->generateContractNumber($this->company->id);
        $number2 = $this->service->generateContractNumber($this->company->id);

        $this->assertNotEquals($number1, $number2);
        $this->assertStringStartsWith('CNT-', $number1);
        $this->assertStringStartsWith('CNT-', $number2);
    }

    /** @test */
    public function it_can_prepare_contract_metadata()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $metadata = $this->service->prepareContractMetadata($contract);

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('contract_number', $metadata);
        $this->assertArrayHasKey('client_name', $metadata);
        $this->assertArrayHasKey('company_name', $metadata);
        $this->assertArrayHasKey('current_date', $metadata);
        $this->assertEquals($contract->contract_number, $metadata['contract_number']);
        $this->assertEquals($this->client->name, $metadata['client_name']);
    }

    /** @test */
    public function it_applies_business_rules_during_generation()
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Test contract with very high value (should trigger business rule)
        $data = [
            'client_id' => $this->client->id,
            'template_id' => $template->id,
            'title' => 'High Value Contract',
            'contract_value' => 1000000.00, // Very high value
            'start_date' => now(),
            'end_date' => now()->addMonths(12),
        ];

        $contract = $this->service->generateFromTemplate($template, $data);

        // Should still create the contract but may set special flags
        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals(1000000.00, $contract->contract_value);
    }

    /** @test */
    public function it_handles_template_processing_errors()
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'content' => null, // Invalid content
        ]);

        $data = [
            'client_id' => $this->client->id,
            'template_id' => $template->id,
            'title' => 'Test Contract',
        ];

        $this->expectException(ContractGenerationException::class);
        
        $this->service->generateFromTemplate($template, $data);
    }

    /** @test */
    public function it_can_generate_contract_without_template()
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Custom Contract',
            'contract_type' => 'service_agreement',
            'contract_value' => 5000.00,
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'terms' => 'Custom terms and conditions.',
        ];

        $contract = $this->service->generateContract($data);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals($this->company->id, $contract->company_id);
        $this->assertEquals($this->client->id, $contract->client_id);
        $this->assertEquals('Custom Contract', $contract->title);
        $this->assertEquals(5000.00, $contract->contract_value);
        $this->assertStringStartsWith('CNT-', $contract->contract_number);
    }

    /** @test */
    public function it_logs_contract_generation_activity()
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $data = [
            'client_id' => $this->client->id,
            'template_id' => $template->id,
            'title' => 'Test Contract',
            'contract_type' => 'service_agreement',
            'contract_value' => 10000.00,
            'start_date' => now(),
            'end_date' => now()->addMonths(12),
        ];

        $contract = $this->service->generateFromTemplate($template, $data);

        // Check that audit log was created
        $this->assertDatabaseHas('contract_audit_logs', [
            'contract_id' => $contract->id,
            'action' => 'contract_generated',
            'user_id' => $this->user->id,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}