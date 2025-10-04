<?php

namespace Tests\Feature\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Services\ContractConfigurationRegistry;
use App\Domains\Contract\Services\ContractService;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ContractServiceWorkflowTest extends TestCase
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

    public function test_complete_contract_creation_workflow(): void
    {
        Asset::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $data = [
            'client_id' => $this->client->id,
            'title' => 'Complete Managed Services Agreement',
            'contract_type' => 'managed_services',
            'description' => 'Full IT infrastructure support',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'contract_type' => 'managed_services',
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server', 'workstation', 'network_device'],
                'sla' => [
                    'serviceTier' => 'gold',
                    'responseTimeHours' => 4,
                    'resolutionTimeHours' => 24,
                    'uptimePercentage' => 99.9,
                ],
                'coverageRules' => [
                    'businessHours' => '24x7',
                    'emergencySupport' => 'included',
                    'autoAssignNewAssets' => true,
                ],
            ],
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => [
                    'monthlyBase' => 2500,
                    'setupFee' => 1000,
                ],
                'assetTypePricing' => [
                    'server' => ['enabled' => true, 'price' => 100],
                    'workstation' => ['enabled' => true, 'price' => 25],
                    'network_device' => ['enabled' => true, 'price' => 50],
                ],
            ],
            'additional_terms' => [
                'termination' => [
                    'noticePeriod' => '60 days',
                    'earlyTerminationFee' => 5000,
                ],
                'disputeResolution' => [
                    'method' => 'arbitration',
                    'governingLaw' => 'State of Texas',
                ],
            ],
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
                'service_tier' => 'gold',
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals('Complete Managed Services Agreement', $contract->title);
        $this->assertNotEmpty($contract->contract_number);

        $this->assertGreaterThanOrEqual(3, $contract->schedules()->count());

        $scheduleA = $contract->schedules()->where('schedule_letter', 'A')->first();
        $scheduleB = $contract->schedules()->where('schedule_letter', 'B')->first();
        $scheduleC = $contract->schedules()->where('schedule_letter', 'C')->first();

        $this->assertNotNull($scheduleA);
        $this->assertNotNull($scheduleB);
        $this->assertNotNull($scheduleC);

        $this->assertEquals(10, Asset::where('supporting_contract_id', $contract->id)->count());

        $expectedValue = 2500 + 1000 + (10 * 100);
        $this->assertEquals($expectedValue, $contract->contract_value);
    }

    public function test_contract_lifecycle_workflow(): void
    {
        $this->mockConfigRegistry();

        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft',
        ]);

        $this->assertEquals('draft', $contract->status);

        $updated = $this->service->updateContract($contract, [
            'title' => 'Updated Title',
            'contract_type' => 'managed_services',
        ]);
        $this->assertEquals('Updated Title', $updated->title);

        $updated->update(['status' => 'signed']);

        $activated = $this->service->activateContract($updated);
        $this->assertEquals('active', $activated->status);

        $suspended = $this->service->suspendContract($activated, 'Non-payment');
        $this->assertEquals('suspended', $suspended->status);

        $reactivated = $this->service->reactivateContract($suspended);
        $this->assertEquals('active', $reactivated->status);

        $terminated = $this->service->terminateContract($reactivated, 'Contract completed');
        $this->assertEquals('terminated', $terminated->status);
    }

    public function test_multi_client_contract_isolation(): void
    {
        $client1 = Client::factory()->create(['company_id' => $this->company->id]);
        $client2 = Client::factory()->create(['company_id' => $this->company->id]);

        Asset::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $client1->id,
            'type' => 'server',
        ]);

        Asset::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $client2->id,
            'type' => 'server',
        ]);

        $contract1 = $this->service->createContract([
            'client_id' => $client1->id,
            'title' => 'Client 1 Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ]);

        $contract2 = $this->service->createContract([
            'client_id' => $client2->id,
            'title' => 'Client 2 Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ]);

        $this->assertEquals(5, Asset::where('supporting_contract_id', $contract1->id)->count());
        $this->assertEquals(3, Asset::where('supporting_contract_id', $contract2->id)->count());

        $this->assertEquals(0, Asset::where('client_id', $client1->id)
            ->where('supporting_contract_id', $contract2->id)
            ->count());
    }

    public function test_contract_renewal_workflow(): void
    {
        $this->mockConfigRegistry();

        $originalContract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
            'end_date' => now()->addDays(15),
            'contract_value' => 10000,
        ]);

        $renewalData = [
            'client_id' => $this->client->id,
            'title' => $originalContract->title . ' (Renewal)',
            'contract_type' => 'managed_services',
            'start_date' => $originalContract->end_date->addDay()->format('Y-m-d'),
            'end_date' => $originalContract->end_date->addYear()->format('Y-m-d'),
            'contract_value' => 12000,
            'contract_type' => $originalContract->contract_type,
        ];

        $renewedContract = $this->service->createContract($renewalData);

        $this->assertStringContainsString('Renewal', $renewedContract->title);
        $this->assertEquals(12000, $renewedContract->contract_value);
        $this->assertTrue($renewedContract->start_date->gt($originalContract->end_date));
    }

    public function test_contract_amendment_workflow(): void
    {
        Asset::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
        ]);

        $originalContract = $this->service->createContract([
            'client_id' => $this->client->id,
            'title' => 'Original Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => ['monthlyBase' => 1000],
                'assetTypePricing' => [
                    'server' => ['enabled' => true, 'price' => 50],
                ],
            ],
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ]);

        $originalValue = $originalContract->contract_value;

        Asset::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'type' => 'server',
            'supporting_contract_id' => $originalContract->id,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updateContractValueWithAssets');
        $method->setAccessible(true);
        $method->invoke($this->service, $originalContract);

        $originalContract->refresh();

        $this->assertGreaterThan($originalValue, $originalContract->contract_value);
    }

    public function test_telecom_contract_complete_workflow(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'VoIP Services Agreement',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'telecommunications',
            'telecom_schedule' => [
                'channelCount' => 50,
                'callingPlan' => 'unlimited_us_canada',
                'qos' => [
                    'meanOpinionScore' => '4.3',
                    'uptimePercent' => '99.99',
                ],
                'compliance' => [
                    'fccCompliant' => true,
                    'karisLaw' => true,
                    'rayBaums' => true,
                ],
            ],
            'pricing_schedule' => [
                'billingModel' => 'per_channel',
                'basePricing' => ['monthlyBase' => 500],
                'telecomPricing' => [
                    'perChannel' => 25,
                    'callingPlan' => 100,
                    'e911' => 5,
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $telecomSchedule = $contract->schedules()->where('schedule_letter', 'D')->first();
        $this->assertNotNull($telecomSchedule);
        $this->assertEquals(50, $telecomSchedule->variable_values['channelCount']);

        $pricingSchedule = $contract->schedules()->where('schedule_letter', 'B')->first();
        $this->assertNotNull($pricingSchedule);
    }

    public function test_hardware_procurement_workflow(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Hardware Procurement Agreement',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'hardware',
            'hardware_schedule' => [
                'selectedCategories' => ['servers', 'storage', 'networking'],
                'procurementModel' => 'var_resale',
                'leadTimeDays' => 7,
                'services' => [
                    'basicInstallation' => true,
                    'rackAndStack' => true,
                ],
                'warranty' => [
                    'hardwarePeriod' => '3_year',
                    'onSiteSupport' => true,
                ],
                'pricing' => [
                    'markupModel' => 'tiered',
                    'categoryMarkup' => [
                        'servers' => 15,
                        'storage' => 18,
                        'networking' => 20,
                    ],
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $hardwareSchedule = $contract->schedules()->where('schedule_letter', 'E')->first();
        $this->assertNotNull($hardwareSchedule);
        $this->assertEquals('var_resale', $hardwareSchedule->variable_values['procurementModel']);
        $this->assertContains('servers', $hardwareSchedule->variable_values['selectedCategories']);
    }

    public function test_compliance_audit_workflow(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Compliance & Audit Services',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'contract_type' => 'compliance',
            'compliance_schedule' => [
                'selectedFrameworks' => ['hipaa', 'sox', 'pci_dss'],
                'riskLevel' => 'high',
                'audits' => [
                    'internal' => true,
                    'external' => true,
                    'penetrationTesting' => true,
                ],
                'frequency' => [
                    'comprehensive' => 'annually',
                    'interim' => 'quarterly',
                ],
                'training' => [
                    'selectedPrograms' => ['hipaa_awareness', 'security_basics'],
                    'frequency' => 'annually',
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $complianceSchedule = $contract->schedules()->where('schedule_letter', 'D')->first();
        $this->assertNotNull($complianceSchedule);
        $this->assertContains('hipaa', $complianceSchedule->variable_values['selectedFrameworks']);
        $this->assertEquals('high', $complianceSchedule->variable_values['riskLevel']);
    }

    public function test_dynamic_builder_workflow(): void
    {
        $data = [
            'contract' => [
                'client_id' => $this->client->id,
                'title' => 'Dynamic Builder Contract',
                'contract_type' => 'managed_services',
                'start_date' => now()->format('Y-m-d'),
            ],
            'components' => [],
        ];

        $contract = $this->service->createFromBuilder($data, $this->user);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertTrue($contract->is_programmable);
        $this->assertStringStartsWith('PRG-', $contract->contract_number);
    }

    public function test_expiring_contracts_notification_workflow(): void
    {
        $this->mockConfigRegistry();

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'end_date' => now()->addDays(20),
            'status' => 'active',
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'end_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'end_date' => now()->addDays(5),
            'status' => 'active',
        ]);

        $expiringIn30Days = $this->service->getExpiringContracts(30);
        $expiringIn15Days = $this->service->getExpiringContracts(15);

        $this->assertCount(3, $expiringIn30Days);
        $this->assertCount(2, $expiringIn15Days);
    }

    public function test_contract_search_and_filter_workflow(): void
    {
        Contract::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Infrastructure Support Contract',
            'contract_type' => 'managed_services',
            'status' => 'active',
            'end_date' => now()->addDays(30),
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Software License Agreement',
            'contract_type' => 'software',
            'status' => 'draft',
            'end_date' => now()->addDays(30),
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Hardware Procurement',
            'contract_type' => 'hardware',
            'status' => 'active',
            'end_date' => now()->addDays(30),
        ]);

        $searchResults = $this->service->searchContracts('Infrastructure');
        $this->assertCount(1, $searchResults);

        $activeContracts = $this->service->getContracts(['status' => 'active']);
        $this->assertEquals(2, $activeContracts->total());

        $managedServices = $this->service->getContracts(['contract_type' => 'managed_services']);
        $this->assertEquals(1, $managedServices->total());
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
