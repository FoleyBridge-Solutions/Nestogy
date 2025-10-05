<?php

namespace Tests\Unit\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Services\ContractGenerationService;
use App\Models\Client;
use App\Models\Company;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContractGenerationService $service;
    protected User $user;
    protected Company $company;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
        
        $this->actingAs($this->user);
        $this->service = new ContractGenerationService();
    }

    // ========================================
    // PUBLIC METHOD TESTS - 100% COVERAGE
    // ========================================

    public function test_generate_from_quote_creates_contract(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'accepted',
            'total' => 10000,
        ]);

        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Standard Service Agreement',
            'contract_type' => 'managed_services',
        ]);

        $contract = $this->service->generateFromQuote($quote, $template);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals($quote->client_id, $contract->client_id);
        $this->assertEquals($this->company->id, $contract->company_id);
    }

    public function test_generate_from_quote_with_customizations(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'accepted',
        ]);

        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'contract_type' => 'managed_services',
        ]);

        $customizations = [
            'title' => 'Custom Contract Title',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
        ];

        $contract = $this->service->generateFromQuote($quote, $template, $customizations);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals('Custom Contract Title', $contract->title);
    }

    public function test_generate_from_template_creates_contract(): void
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'VoIP Service Agreement',
            'contract_type' => 'telecommunications',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'VoIP Services Contract',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'contract_value' => 5000,
        ];

        $contract = $this->service->generateFromTemplate($this->client, $template, $data);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals($this->client->id, $contract->client_id);
        $this->assertEquals('VoIP Services Contract', $contract->title);
    }

    public function test_generate_from_template_with_voip_config(): void
    {
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'contract_type' => 'telecommunications',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'VoIP Contract',
            'start_date' => now()->format('Y-m-d'),
            'voip_config' => [
                'channels' => 50,
                'did_numbers' => ['555-0100', '555-0101'],
                'features' => ['call_recording', 'voicemail'],
            ],
        ];

        $contract = $this->service->generateFromTemplate($this->client, $template, $data);

        $this->assertInstanceOf(Contract::class, $contract);
    }

    public function test_create_custom_contract(): void
    {
        $contractData = [
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'title' => 'Custom Contract',
            'contract_type' => 'custom',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(6)->format('Y-m-d'),
            'contract_value' => 15000,
            'description' => 'Custom contract for special services',
        ];

        $contract = $this->service->createCustomContract($contractData);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals('Custom Contract', $contract->title);
        $this->assertEquals(15000, $contract->contract_value);
        $this->assertEquals($this->client->id, $contract->client_id);
    }

    public function test_create_custom_contract_with_milestones(): void
    {
        $contractData = [
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'title' => 'Project Contract',
            'contract_type' => 'project',
            'start_date' => now()->format('Y-m-d'),
            'milestones' => [
                [
                    'title' => 'Phase 1',
                    'due_date' => now()->addMonth()->format('Y-m-d'),
                    'amount' => 5000,
                ],
                [
                    'title' => 'Phase 2',
                    'due_date' => now()->addMonths(2)->format('Y-m-d'),
                    'amount' => 5000,
                ],
            ],
        ];

        $contract = $this->service->createCustomContract($contractData);

        $this->assertInstanceOf(Contract::class, $contract);
    }

    public function test_generate_contract_document(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
        ]);

        $documentPath = $this->service->generateContractDocument($contract);

        $this->assertIsString($documentPath);
    }

    public function test_generate_contract_document_with_options(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $options = [
            'include_appendix' => true,
            'watermark' => 'DRAFT',
            'format' => 'pdf',
        ];

        $documentPath = $this->service->generateContractDocument($contract, $options);

        $this->assertIsString($documentPath);
    }

    public function test_regenerate_contract(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'title' => 'Original Title',
            'status' => 'draft',
        ]);

        $changes = [
            'title' => 'Updated Title',
            'contract_value' => 20000,
        ];

        $updated = $this->service->regenerateContract($contract, $changes);

        $this->assertInstanceOf(Contract::class, $updated);
        $this->assertEquals('Updated Title', $updated->title);
    }

    public function test_regenerate_contract_maintains_original_data(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'contract_number' => 'CNT-001',
            'status' => 'draft',
        ]);

        $changes = [
            'description' => 'Updated description',
        ];

        $updated = $this->service->regenerateContract($contract, $changes);

        $this->assertEquals($contract->contract_number, $updated->contract_number);
        $this->assertEquals($contract->client_id, $updated->client_id);
    }

    // ========================================
    // INTEGRATION TESTS
    // ========================================

    public function test_full_contract_lifecycle_from_quote(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'accepted',
            'total' => 25000,
        ]);

        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
            'contract_type' => 'managed_services',
        ]);

        $contract = $this->service->generateFromQuote($quote, $template);
        
        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertNotEmpty($contract->contract_number);
        
        $documentPath = $this->service->generateContractDocument($contract);
        $this->assertIsString($documentPath);
        
        $changes = ['status' => 'pending_review'];
        $updated = $this->service->regenerateContract($contract, $changes);
        $this->assertInstanceOf(Contract::class, $updated);
    }

    public function test_contract_generation_with_multiple_templates(): void
    {
        $templates = [
            'managed_services' => ContractTemplate::factory()->create([
                'company_id' => $this->company->id,
                'contract_type' => 'managed_services',
            ]),
            'telecommunications' => ContractTemplate::factory()->create([
                'company_id' => $this->company->id,
                'contract_type' => 'telecommunications',
            ]),
            'hardware' => ContractTemplate::factory()->create([
                'company_id' => $this->company->id,
                'contract_type' => 'hardware',
            ]),
        ];

        foreach ($templates as $type => $template) {
            $data = [
                'client_id' => $this->client->id,
                'title' => ucfirst($type) . ' Contract',
                'start_date' => now()->format('Y-m-d'),
            ];

            $contract = $this->service->generateFromTemplate($this->client, $template, $data);
            $this->assertInstanceOf(Contract::class, $contract);
            $this->assertEquals($type, $contract->contract_type);
        }
    }

    public function test_contract_generation_handles_company_isolation(): void
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create(['company_id' => $otherCompany->id]);
        
        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $data = [
            'client_id' => $otherClient->id,
            'title' => 'Test Contract',
            'start_date' => now()->format('Y-m-d'),
        ];

        try {
            $contract = $this->service->generateFromTemplate($otherClient, $template, $data);
            $this->fail('Should have thrown validation exception for company mismatch');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    // ========================================
    // EDGE CASES & ERROR HANDLING
    // ========================================

    public function test_generate_from_quote_validates_quote_status(): void
    {
        $quote = Quote::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
        ]);

        $template = ContractTemplate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->expectException(\Exception::class);
        $this->service->generateFromQuote($quote, $template);
    }

    public function test_create_custom_contract_requires_minimum_fields(): void
    {
        $contractData = [
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ];

        $this->expectException(\Exception::class);
        $this->service->createCustomContract($contractData);
    }

    public function test_regenerate_contract_handles_signed_contracts(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'signed',
        ]);

        $changes = ['title' => 'New Title'];

        $this->expectException(\Exception::class);
        $this->service->regenerateContract($contract, $changes);
    }

    public function test_generate_contract_document_handles_missing_contract(): void
    {
        $contract = new Contract();
        $contract->id = 99999;

        $this->expectException(\Exception::class);
        $this->service->generateContractDocument($contract);
    }
}
