<?php

namespace Tests\Unit\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Services\ContractService;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ContractServiceAssetTest extends TestCase
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

    public function test_assigns_assets_by_type(): void
    {
        Asset::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
            'supporting_contract_id' => null,
        ]);

        Asset::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'workstation',
            'supporting_contract_id' => null,
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server', 'workstation'],
                'service_tier' => 'gold',
            ],
        ];

        $contract = $this->service->createContract($data);

        $servers = Asset::where('type', 'server')
            ->where('supporting_contract_id', $contract->id)
            ->get();
        $workstations = Asset::where('type', 'workstation')
            ->where('supporting_contract_id', $contract->id)
            ->get();

        $this->assertCount(5, $servers);
        $this->assertCount(3, $workstations);
    }

    public function test_only_assigns_specified_asset_types(): void
    {
        Asset::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        Asset::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'printer',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $assignedServers = Asset::where('type', 'server')
            ->where('supporting_contract_id', $contract->id)
            ->count();
        $assignedPrinters = Asset::where('type', 'printer')
            ->where('supporting_contract_id', $contract->id)
            ->count();

        $this->assertEquals(3, $assignedServers);
        $this->assertEquals(0, $assignedPrinters);
    }

    public function test_skips_already_assigned_assets(): void
    {
        $existingContract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        Asset::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
            'supporting_contract_id' => $existingContract->id,
        ]);

        Asset::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
            'supporting_contract_id' => null,
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'New Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertEquals(2, Asset::where('supporting_contract_id', $contract->id)->count());
        $this->assertEquals(3, Asset::where('supporting_contract_id', $existingContract->id)->count());
    }

    public function test_sets_asset_support_metadata(): void
    {
        Asset::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
                'service_tier' => 'platinum',
            ],
        ];

        $contract = $this->service->createContract($data);

        $asset = Asset::where('supporting_contract_id', $contract->id)->first();

        $this->assertTrue($asset->auto_assigned_support);
        $this->assertEquals('supported', $asset->support_status);
        $this->assertEquals('enterprise', $asset->support_level);
        $this->assertNotNull($asset->support_assigned_at);
        $this->assertEquals($this->user->id, $asset->support_assigned_by);
    }

    public function test_determines_support_level_from_service_tier(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('determineSupportLevel');
        $method->setAccessible(true);

        $this->assertEquals('basic', $method->invoke($this->service, ['sla_terms' => ['service_tier' => 'bronze']]));
        $this->assertEquals('standard', $method->invoke($this->service, ['sla_terms' => ['service_tier' => 'silver']]));
        $this->assertEquals('premium', $method->invoke($this->service, ['sla_terms' => ['service_tier' => 'gold']]));
        $this->assertEquals('enterprise', $method->invoke($this->service, ['sla_terms' => ['service_tier' => 'platinum']]));
        $this->assertEquals('standard', $method->invoke($this->service, ['sla_terms' => ['service_tier' => 'unknown']]));
    }

    public function test_updates_contract_value_with_asset_pricing(): void
    {
        Asset::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        Asset::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'workstation',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server', 'workstation'],
            ],
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => [
                    'monthlyBase' => 1000,
                ],
                'assetTypePricing' => [
                    'server' => [
                        'enabled' => true,
                        'price' => 100,
                    ],
                    'workstation' => [
                        'enabled' => true,
                        'price' => 25,
                    ],
                ],
            ],
        ];

        $contract = $this->service->createContract($data);
        $contract->refresh();

        $expectedValue = 1000 + (5 * 100) + (10 * 25);
        $this->assertEquals($expectedValue, $contract->contract_value);
    }

    public function test_updates_schedule_asset_assignments(): void
    {
        Asset::factory()->count(8)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server'],
                'sla' => ['serviceTier' => 'gold'],
            ],
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $scheduleA = $contract->schedules()->where('schedule_letter', 'A')->first();

        $this->assertNotNull($scheduleA);
        $this->assertEquals(8, $scheduleA->asset_count);
        $this->assertTrue($scheduleA->auto_assign_assets);
        $this->assertArrayHasKey('asset_assignment_results', $scheduleA->metadata);
    }

    public function test_stores_assignment_results_in_metadata(): void
    {
        Asset::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ];

        $contract = $this->service->createContract($data);
        $contract->refresh();

        $this->assertArrayHasKey('asset_assignment_results', $contract->metadata);
        $this->assertEquals(3, $contract->metadata['asset_assignment_results']['total_assigned']);
        $this->assertArrayHasKey('by_type', $contract->metadata['asset_assignment_results']);
    }

    public function test_handles_no_available_assets(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertEquals(0, $contract->metadata['asset_assignment_results']['total_assigned']);
    }

    public function test_logs_asset_assignment_activities(): void
    {
        Asset::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ];

        $contract = $this->service->createContract($data);

        Log::shouldHaveReceived('info')
            ->with('Starting asset assignment process', \Mockery::any());
        Log::shouldHaveReceived('info')
            ->with('Asset assignment process completed', \Mockery::any());
    }

    public function test_validates_asset_pricing_table_generation(): void
    {
        Asset::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'assetTypePricing' => [
                    'server' => [
                        'enabled' => true,
                        'price' => 100,
                    ],
                ],
            ],
        ];

        $contract = $this->service->createContract($data);
        $scheduleB = $contract->schedules()->where('schedule_letter', 'B')->first();

        $this->assertNotNull($scheduleB);
        $this->assertStringContainsString('<table', $scheduleB->content);
        $this->assertStringContainsString('Server', $scheduleB->content);
        $this->assertStringContainsString('5', $scheduleB->content);
    }

    public function test_calculates_contract_value_with_base_and_asset_pricing(): void
    {
        Asset::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => [
                    'monthlyBase' => 500,
                    'setupFee' => 200,
                ],
                'assetTypePricing' => [
                    'server' => [
                        'enabled' => true,
                        'price' => 75,
                    ],
                ],
            ],
        ];

        $contract = $this->service->createContract($data);
        $contract->refresh();

        $expectedValue = 500 + 200 + (3 * 75);
        $this->assertEquals($expectedValue, $contract->contract_value);
    }

    public function test_handles_pricing_without_asset_fees(): void
    {
        Asset::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
            'pricing_schedule' => [
                'billingModel' => 'fixed',
                'basePricing' => [
                    'monthlyBase' => 2500,
                ],
            ],
        ];

        $contract = $this->service->createContract($data);
        $scheduleB = $contract->schedules()->where('schedule_letter', 'B')->first();

        $this->assertStringContainsString('Included', $scheduleB->content);
    }

    public function test_processes_asset_evaluation_rules(): void
    {
        $asset = Asset::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
                'service_tier' => 'gold',
            ],
        ];

        $contract = $this->service->createContract($data);

        $asset->refresh();
        
        // support_evaluation_rules is stored as JSON in DB but cast to array by model
        $evaluationRules = is_string($asset->support_evaluation_rules) 
            ? json_decode($asset->support_evaluation_rules, true)
            : $asset->support_evaluation_rules;

        $this->assertEquals('server', $evaluationRules['asset_type']);
        $this->assertEquals('gold', $evaluationRules['service_tier']);
        $this->assertTrue($evaluationRules['auto_assigned']);
        $this->assertEquals('contract_wizard', $evaluationRules['assigned_via']);
    }

    public function test_handles_mixed_pricing_models(): void
    {
        Asset::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        Asset::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'workstation',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server', 'workstation'],
            ],
            'pricing_schedule' => [
                'billingModel' => 'hybrid',
                'basePricing' => [
                    'monthlyBase' => 1000,
                ],
                'assetTypePricing' => [
                    'server' => [
                        'enabled' => true,
                        'price' => 100,
                    ],
                    'workstation' => [
                        'enabled' => false,
                        'price' => 0,
                    ],
                ],
            ],
        ];

        $contract = $this->service->createContract($data);
        $contract->refresh();

        $expectedValue = 1000 + (2 * 100);
        $this->assertEquals($expectedValue, $contract->contract_value);
    }

    public function test_assignment_respects_company_boundaries(): void
    {
        $otherCompany = Company::factory()->create();
        $otherClient = Client::factory()->create(['company_id' => $otherCompany->id]);

        Asset::factory()->count(3)->create([
            'company_id' => $otherCompany->id,
            'client_id' => $otherClient->id,
            'type' => 'server',
        ]);

        Asset::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertEquals(2, Asset::where('supporting_contract_id', $contract->id)->count());
        $this->assertEquals(0, Asset::where('company_id', $otherCompany->id)
            ->whereNotNull('supporting_contract_id')
            ->count());
    }
}
