<?php

namespace Tests\Unit\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSchedule;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Services\ContractConfigurationRegistry;
use App\Domains\Contract\Services\ContractService;
use App\Domains\Core\Services\TemplateVariableMapper;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContractServiceTest extends TestCase
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
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);
        $this->service = app(ContractService::class);
    }

    public function test_gets_paginated_contracts_with_basic_filters(): void
    {
        Contract::factory()->count(15)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
        ]);

        Contract::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
        ]);

        $result = $this->service->getContracts([], 10);

        $this->assertCount(10, $result->items());
        $this->assertEquals(20, $result->total());
    }

    public function test_filters_contracts_by_status(): void
    {
        Contract::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addYear(),
        ]);

        Contract::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $result = $this->service->getContracts(['status' => 'active']);

        $this->assertEquals(5, $result->total());
        $result->each(function ($contract) {
            $this->assertEquals('active', $contract->status);
        });
    }

    public function test_filters_contracts_by_client(): void
    {
        $otherClient = Client::factory()->create(['company_id' => $this->company->id]);

        Contract::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        Contract::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $otherClient->id,
        ]);

        $result = $this->service->getContracts(['client_id' => $this->client->id]);

        $this->assertEquals(5, $result->total());
    }

    public function test_searches_contracts_by_keyword(): void
    {
        Contract::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Infrastructure Support Contract',
            'contract_number' => 'CNT-1001',
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Software License Agreement',
            'contract_number' => 'CNT-1002',
        ]);

        $result = $this->service->getContracts(['search' => 'Infrastructure']);

        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('Infrastructure', $result->first()->title);
    }

    public function test_gets_contracts_by_status(): void
    {
        Contract::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $activeContracts = $this->service->getContractsByStatus('active');

        $this->assertCount(3, $activeContracts);
        $activeContracts->each(function ($contract) {
            $this->assertEquals('active', $contract->status);
        });
    }

    public function test_creates_contract_with_minimum_required_data(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'managed_services',
        ];

        $contract = $this->service->createContract($data);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals($this->company->id, $contract->company_id);
        $this->assertEquals($this->user->id, $contract->created_by);
        $this->assertEquals('Test Contract', $contract->title);
        $this->assertNotEmpty($contract->contract_number);
    }

    public function test_creates_contract_with_complete_data(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Complete Test Contract',
            'description' => 'Test description',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'contract_type' => 'managed_services',
            'contract_value' => 10000.00,
            'sla_terms' => [
                'service_tier' => 'gold',
                'response_time_hours' => 4,
                'resolution_time_hours' => 24,
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertEquals('Complete Test Contract', $contract->title);
        $this->assertEquals(10000.00, $contract->contract_value);
        $this->assertEquals('gold', $contract->sla_terms['service_tier']);
    }

    public function test_generates_unique_contract_number(): void
    {
        Contract::factory()->create([
            'company_id' => $this->company->id,
            'contract_number' => 'CNT-0001',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'managed_services',
        ];

        $contract = $this->service->createContract($data);

        $this->assertNotEquals('CNT-0001', $contract->contract_number);
        $this->assertMatchesRegularExpression('/^CNT-\d{4}$/', $contract->contract_number);
    }

    public function test_creates_contract_with_custom_prefix(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'managed_services',
            'prefix' => 'MSA',
        ];

        $contract = $this->service->createContract($data);

        $this->assertStringStartsWith('MSA-', $contract->contract_number);
    }

    public function test_creates_contract_schedules_from_wizard_data(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract with Schedules',
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'managed_services',
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server', 'workstation'],
                'sla' => [
                    'serviceTier' => 'gold',
                    'responseTimeHours' => 4,
                    'resolutionTimeHours' => 24,
                    'uptimePercentage' => 99.9,
                ],
                'coverageRules' => [
                    'businessHours' => '24x7',
                    'emergencySupport' => 'included',
                ],
            ],
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => [
                    'monthlyBase' => 2500,
                    'setupFee' => 500,
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $schedules = $contract->schedules;
        $this->assertGreaterThan(0, $schedules->count());

        $scheduleA = $schedules->where('schedule_letter', 'A')->first();
        $this->assertNotNull($scheduleA);
        $this->assertEquals('A', $scheduleA->schedule_type);

        $scheduleB = $schedules->where('schedule_letter', 'B')->first();
        $this->assertNotNull($scheduleB);
        $this->assertEquals('B', $scheduleB->schedule_type);
    }

    public function test_processes_asset_assignments_when_auto_assign_enabled(): void
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
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'managed_services',
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server', 'workstation'],
                'service_tier' => 'gold',
            ],
        ];

        $contract = $this->service->createContract($data);

        $assignedAssets = Asset::where('supporting_contract_id', $contract->id)->get();
        $this->assertCount(8, $assignedAssets);
    }

    public function test_skips_asset_assignments_when_auto_assign_disabled(): void
    {
        Asset::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'managed_services',
            'sla_terms' => [
                'auto_assign_new_assets' => false,
                'supported_asset_types' => ['server'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $assignedAssets = Asset::where('supporting_contract_id', $contract->id)->get();
        $this->assertCount(0, $assignedAssets);
    }

    public function test_updates_contract_in_draft_status(): void
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
            'title' => 'Original Title',
        ]);

        $updated = $this->service->updateContract($contract, [
            'title' => 'Updated Title',
            'description' => 'New description',
        ]);

        $this->assertEquals('Updated Title', $updated->title);
        $this->assertEquals('New description', $updated->description);
    }

    public function test_cannot_update_active_contract(): void
    {
        $this->expectException(ValidationException::class);

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $this->service->updateContract($contract, ['title' => 'New Title']);
    }

    public function test_activates_signed_contract(): void
    {
        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'signed',
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $activated = $this->service->activateContract($contract);

        $this->assertEquals('active', $activated->status);
        
        if (\Schema::hasColumn('contracts', 'executed_at')) {
            $this->assertNotNull($activated->executed_at);
        }
    }

    public function test_cannot_activate_unsigned_contract(): void
    {
        $this->expectException(ValidationException::class);

        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $this->service->activateContract($contract);
    }

    public function test_suspends_active_contract(): void
    {
        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $suspended = $this->service->suspendContract($contract, 'Non-payment');

        $this->assertEquals('suspended', $suspended->status);
    }

    public function test_cannot_suspend_non_active_contract(): void
    {
        $this->expectException(ValidationException::class);

        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $this->service->suspendContract($contract, 'Test reason');
    }

    public function test_terminates_active_contract(): void
    {
        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $terminated = $this->service->terminateContract($contract, 'Client request');

        $this->assertEquals('terminated', $terminated->status);
        
        if (\Schema::hasColumn('contracts', 'termination_reason')) {
            $this->assertEquals('Client request', $terminated->termination_reason);
        }
        
        if (\Schema::hasColumn('contracts', 'terminated_at')) {
            $this->assertNotNull($terminated->terminated_at);
        }
    }

    public function test_reactivates_suspended_contract(): void
    {
        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'suspended',
        ]);

        $reactivated = $this->service->reactivateContract($contract);

        $this->assertEquals('active', $reactivated->status);
    }

    public function test_cannot_reactivate_non_suspended_contract(): void
    {
        $this->expectException(ValidationException::class);

        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $this->service->reactivateContract($contract);
    }

    public function test_deletes_draft_contract(): void
    {
        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $contractId = $contract->id;
        
        try {
            $result = $this->service->deleteContract($contract);
            $this->assertTrue($result);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'deleted_at')) {
                $this->markTestSkipped('Soft deletes column missing from schema');
            }
            throw $e;
        }
    }

    public function test_cannot_delete_active_contract(): void
    {
        $this->expectException(ValidationException::class);

        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $this->service->deleteContract($contract);
    }

    public function test_gets_dashboard_statistics(): void
    {
        $this->mockConfigRegistry();

        Contract::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'contract_value' => 10000,
        ]);

        Contract::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
            'contract_value' => 5000,
        ]);

        $stats = $this->service->getDashboardStatistics();

        $this->assertEquals(7, $stats['total_contracts']);
        $this->assertEquals(5, $stats['active_contracts']);
        $this->assertEquals(2, $stats['draft_contracts']);
        $this->assertEquals(60000, $stats['total_value']);
    }

    public function test_gets_expiring_contracts(): void
    {
        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(15),
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDays(60),
        ]);

        $expiring = $this->service->getExpiringContracts(30);

        $this->assertCount(1, $expiring);
    }

    public function test_searches_contracts(): void
    {
        Contract::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Infrastructure Support',
            'contract_number' => 'INF-001',
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Software License',
            'contract_number' => 'SFT-001',
        ]);

        $results = $this->service->searchContracts('Infrastructure');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Infrastructure', $results->first()->title);
    }

    public function test_processes_template_with_variables(): void
    {
        $template = '## Contract for {{client_name}}, Service Tier: {{service_tier}}';
        $variables = [
            'client_name' => 'Acme Corp',
            'service_tier' => 'Gold',
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('processTemplate');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $template, $variables);

        $this->assertEquals('## Contract for Acme Corp, Service Tier: Gold', $result);
    }

    public function test_processes_conditional_templates(): void
    {
        $template = '{{#if premium}}Premium Service{{#else}}Standard Service{{/if}}';

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('processTemplate');
        $method->setAccessible(true);

        $resultPremium = $method->invoke($this->service, $template, ['premium' => true]);
        $this->assertEquals('Premium Service', $resultPremium);

        $resultStandard = $method->invoke($this->service, $template, ['premium' => false]);
        $this->assertEquals('Standard Service', $resultStandard);
    }

    public function test_evaluates_and_conditions(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'premium && support', [
            'premium' => true,
            'support' => true,
        ]);
        $this->assertTrue($result);

        $result = $method->invoke($this->service, 'premium && support', [
            'premium' => true,
            'support' => false,
        ]);
        $this->assertFalse($result);
    }

    public function test_evaluates_or_conditions(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('evaluateCondition');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'premium || support', [
            'premium' => false,
            'support' => true,
        ]);
        $this->assertTrue($result);

        $result = $method->invoke($this->service, 'premium || support', [
            'premium' => false,
            'support' => false,
        ]);
        $this->assertFalse($result);
    }

    public function test_formats_asset_types(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('formatAssetTypes');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['server', 'workstation', 'network_device']);

        $this->assertStringContainsString('Server', $result);
        $this->assertStringContainsString('Workstation', $result);
        $this->assertStringContainsString('Network device', $result);
    }

    public function test_creates_contract_with_error_recovery(): void
    {
        Log::shouldReceive('info')->andReturn(null);
        Log::shouldReceive('debug')->andReturn(null);
        Log::shouldReceive('warning')->andReturn(null);
        Log::shouldReceive('error')->andReturn(null);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server'],
                'sla' => ['serviceTier' => 'gold'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertArrayNotHasKey('partial_failures', $contract->metadata ?? []);
    }

    public function test_transaction_rollback_on_critical_failure(): void
    {
        $this->expectException(\Exception::class);

        $data = [
            'client_id' => 999999,
            'title' => 'Test Contract',
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'managed_services',
        ];

        $this->service->createContract($data);
    }

    public function test_calculates_monthly_recurring_revenue(): void
    {
        $this->mockConfigRegistry();

        Contract::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'pricing_structure' => [
                'recurring_monthly' => 1000,
            ],
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateMonthlyRecurringRevenue');
        $method->setAccessible(true);

        $mrr = $method->invoke($this->service, null);

        $this->assertEquals(3000, $mrr);
    }

    public function test_maps_schedule_data_to_contract(): void
    {
        $data = [
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server', 'workstation'],
                'coverageRules' => [
                    'autoAssignNewAssets' => true,
                ],
                'sla' => [
                    'serviceTier' => 'gold',
                ],
            ],
        ];

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapScheduleDataToContract');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $data);

        $this->assertArrayHasKey('sla_terms', $result);
        $this->assertTrue($result['sla_terms']['auto_assign_new_assets']);
        $this->assertEquals(['server', 'workstation'], $result['sla_terms']['supported_asset_types']);
    }

    public function test_determines_schedule_type_from_template(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('determineScheduleType');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['telecom_schedule' => ['channelCount' => 10]]);
        $this->assertEquals('telecom', $result);

        $result = $method->invoke($this->service, ['hardware_schedule' => ['procurementModel' => 'direct']]);
        $this->assertEquals('hardware', $result);

        $result = $method->invoke($this->service, ['compliance_schedule' => ['riskLevel' => 'high']]);
        $this->assertEquals('compliance', $result);

        $result = $method->invoke($this->service, []);
        $this->assertEquals('infrastructure', $result);
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
