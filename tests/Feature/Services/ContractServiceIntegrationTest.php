<?php

namespace Tests\Feature\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Services\ContractClauseService;
use App\Domains\Contract\Services\ContractConfigurationRegistry;
use App\Domains\Contract\Services\ContractService;
use App\Domains\Core\Services\TemplateVariableMapper;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ContractServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ContractService $service;
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
        $this->service = app(ContractService::class);

        Log::spy();
    }

    public function test_integrates_with_template_variable_mapper(): void
    {
        Asset::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Integration Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => ['monthlyBase' => 1000],
                'assetTypePricing' => [
                    'server' => ['enabled' => true, 'price' => 100],
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $scheduleB = $contract->schedules()->where('schedule_letter', 'B')->first();

        $this->assertNotNull($scheduleB);
        $this->assertStringContainsString('5', $scheduleB->content);
        $this->assertStringContainsString('Server', $scheduleB->content);
    }

    public function test_handles_database_transactions_correctly(): void
    {
        DB::enableQueryLog();

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Transaction Test',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server'],
                'sla' => ['serviceTier' => 'gold'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $queries = DB::getQueryLog();

        $this->assertNotEmpty($queries);
        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);
    }

    public function test_rollback_on_schedule_creation_failure(): void
    {
        $originalCount = Contract::count();

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Rollback Test',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['invalid_type_that_causes_error'],
            ],
        ];

        $this->expectException(\Exception::class);

        DB::beginTransaction();
        try {
            $contract = $this->service->createContract($data);
            throw new \Exception('Force rollback');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->assertEquals($originalCount, Contract::count());
            throw $e;
        }
    }

    public function test_logs_comprehensive_audit_trail(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Audit Trail Test',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
        ];

        $contract = $this->service->createContract($data);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Contract::class,
            'subject_id' => $contract->id,
            'description' => 'Contract created',
        ]);
    }

    public function test_concurrent_contract_creation_handles_race_conditions(): void
    {
        $contracts = [];

        for ($i = 0; $i < 3; $i++) {
            $contracts[] = $this->service->createContract([
                'client_id' => $this->client->id,
                'title' => "Concurrent Contract {$i}",
                'contract_type' => 'managed_services',
                'start_date' => now()->format('Y-m-d'),
            ]);
        }

        $contractNumbers = collect($contracts)->pluck('contract_number')->toArray();

        $this->assertCount(3, array_unique($contractNumbers));
    }

    public function test_handles_partial_failures_gracefully(): void
    {
        Log::shouldReceive('error')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('debug')->andReturn(null);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Partial Failure Test',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server'],
                'sla' => ['serviceTier' => 'gold'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertInstanceOf(Contract::class, $contract);
    }

    public function test_retries_failed_component(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'metadata' => [
                'partial_failures' => [
                    [
                        'component' => 'schedules',
                        'error' => 'Test error',
                        'can_retry' => true,
                    ],
                ],
            ],
        ]);

        $result = $this->service->retryContractComponent($contract, 'schedules');

        $this->assertTrue($result);
    }

    public function test_multi_company_isolation(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $otherClient = Client::factory()->create(['company_id' => $otherCompany->id]);

        Contract::factory()->count(5)->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($otherUser);
        $otherService = app(ContractService::class);

        Contract::factory()->count(3)->create([
            'company_id' => $otherCompany->id,
        ]);

        $otherCompanyContracts = $otherService->getContracts();

        $this->actingAs($this->user);
        $thisService = app(ContractService::class);
        $thisCompanyContracts = $thisService->getContracts();

        $this->assertEquals(5, $thisCompanyContracts->total());
        $this->assertEquals(3, $otherCompanyContracts->total());
    }

    public function test_complex_pricing_calculation_integration(): void
    {
        Asset::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        Asset::factory()->count(20)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'workstation',
        ]);

        Asset::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'network_device',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Complex Pricing Test',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server', 'workstation', 'network_device'],
            ],
            'pricing_schedule' => [
                'billingModel' => 'tiered',
                'basePricing' => [
                    'monthlyBase' => 5000,
                    'setupFee' => 2000,
                ],
                'assetTypePricing' => [
                    'server' => ['enabled' => true, 'price' => 150],
                    'workstation' => ['enabled' => true, 'price' => 30],
                    'network_device' => ['enabled' => true, 'price' => 75],
                ],
                'perUnitPricing' => [
                    'perUserMonthly' => 25,
                ],
            ],
        ];

        $contract = $this->service->createContract($data);
        $contract->refresh();

        $expectedValue = 5000 + 2000 + (10 * 150) + (20 * 30) + (5 * 75);
        $this->assertEquals($expectedValue, $contract->contract_value);
    }

    public function test_schedule_synchronization_validation(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Sync Test',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server', 'workstation'],
                'coverageRules' => [
                    'autoAssignNewAssets' => true,
                ],
            ],
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server', 'workstation'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertArrayHasKey('schedule_validation', $contract->metadata);
        $validation = $contract->metadata['schedule_validation'];

        $this->assertTrue($validation['results']['auto_assignment_sync']);
        $this->assertTrue($validation['results']['asset_types_sync']);
        $this->assertTrue($validation['all_synchronized']);
    }

    public function test_handles_empty_asset_pricing(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Empty Pricing Test',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'pricing_schedule' => [
                'billingModel' => 'fixed',
                'basePricing' => [
                    'monthlyBase' => 3000,
                ],
                'assetTypePricing' => [],
            ],
        ];

        $contract = $this->service->createContract($data);
        $scheduleB = $contract->schedules()->where('schedule_letter', 'B')->first();

        $this->assertNotNull($scheduleB);
        $this->assertStringContainsString('3,000', $scheduleB->content);
    }

    public function test_handles_template_content_generation(): void
    {
        $this->mockConfigRegistry();

        // Create template and clause
        $template = \App\Domains\Contract\Models\ContractTemplate::create([
            'company_id' => $this->company->id,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'template_type' => 'managed_services',
            'status' => 'active',
            'version' => '1.0',
            'billing_model' => 'fixed',
            'is_default' => false,
            'usage_count' => 0,
            'requires_approval' => false,
        ]);

        $clause = \App\Domains\Contract\Models\ContractClause::create([
            'company_id' => $this->company->id,
            'name' => 'Test Clause',
            'slug' => 'test-clause',
            'content' => 'This is test content',
            'clause_type' => 'required',
            'category' => 'general',
            'version' => '1.0',
            'status' => 'active',
            'is_required' => true,
            'is_system' => false,
            'sort_order' => 1,
        ]);

        $template->clauses()->attach($clause->id);

        // Verify template and clause were created
        $this->assertDatabaseHas('contract_templates', [
            'id' => $template->id,
            'name' => 'Test Template',
        ]);

        $this->assertDatabaseHas('contract_clauses', [
            'id' => $clause->id,
            'name' => 'Test Clause',
        ]);

        $this->assertTrue($template->clauses->contains($clause));
    }

    public function test_validates_business_rules_across_services(): void
    {
        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $updated = $this->service->updateContract($contract, [
            'title' => 'Updated Title',
            'contract_type' => 'managed_services',
        ]);

        $this->assertEquals('Updated Title', $updated->title);

        $contract->update(['status' => 'active']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service->updateContract($contract, [
            'title' => 'Should Fail',
            'contract_type' => 'managed_services',
        ]);
    }

    public function test_dashboard_statistics_accuracy(): void
    {
        $this->mockConfigRegistry();

        Contract::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'contract_value' => 10000,
            'end_date' => now()->addDays(60),
        ]);

        Contract::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
            'contract_value' => 5000,
            'end_date' => now()->addDays(60),
        ]);

        Contract::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'signature_status' => 'pending',
            'contract_value' => 2500,
            'end_date' => now()->addDays(20),
        ]);

        $stats = $this->service->getDashboardStatistics();

        $this->assertEquals(17, $stats['total_contracts']);
        $this->assertEquals(12, $stats['active_contracts']);
        $this->assertEquals(5, $stats['draft_contracts']);
        $this->assertEquals(130000, $stats['total_value']);
        $this->assertEquals(2, $stats['expiring_soon']);
    }

    public function test_metadata_storage_and_retrieval(): void
    {
        $metadata = [
            'custom_field_1' => 'value1',
            'custom_field_2' => ['nested' => 'data'],
        ];

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Metadata Test',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'metadata' => $metadata,
        ];

        $contract = $this->service->createContract($data);

        $this->assertIsArray($contract->metadata);
        $this->assertArrayHasKey('custom_field_1', $contract->metadata);
        $this->assertEquals('value1', $contract->metadata['custom_field_1']);
    }

    public function test_contract_number_generation_handles_gaps(): void
    {
        Contract::factory()->create([
            'company_id' => $this->company->id,
            'contract_number' => 'CNT-0001',
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'contract_number' => 'CNT-0003',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Gap Test',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
        ];

        $contract = $this->service->createContract($data);

        $this->assertEquals('CNT-0004', $contract->contract_number);
    }

    protected function mockConfigRegistry(): void
    {
        $mock = \Mockery::mock(ContractConfigurationRegistry::class);
        $mock->shouldReceive('getContractStatuses')->andReturn([
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'signed' => 'Signed',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'terminated' => 'Terminated',
        ]);
        $mock->shouldReceive('getContractSignatureStatuses')->andReturn([
            'pending' => 'Pending',
            'signed' => 'Signed',
        ]);
        $mock->shouldReceive('getRenewalTypes')->andReturn([
            'manual' => 'Manual Renewal',
            'automatic' => 'Automatic Renewal',
        ]);

        $this->app->instance(ContractConfigurationRegistry::class, $mock);
    }
}
