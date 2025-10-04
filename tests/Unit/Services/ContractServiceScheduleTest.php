<?php

namespace Tests\Unit\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSchedule;
use App\Domains\Contract\Services\ContractService;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ContractServiceScheduleTest extends TestCase
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

    public function test_creates_schedule_a_infrastructure(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
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
                    'includeRemoteSupport' => true,
                    'includeOnsiteSupport' => false,
                    'autoAssignNewAssets' => true,
                ],
                'exclusions' => [
                    'assetTypes' => '',
                    'services' => '',
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $scheduleA = $contract->schedules()->where('schedule_letter', 'A')->first();

        $this->assertNotNull($scheduleA);
        $this->assertEquals('A', $scheduleA->schedule_letter);
        $this->assertEquals(ContractSchedule::TYPE_INFRASTRUCTURE, $scheduleA->schedule_type);
        $this->assertStringContainsString('Infrastructure', $scheduleA->title);
        $this->assertNotEmpty($scheduleA->content);
        $this->assertEquals(['server', 'workstation', 'network_device'], $scheduleA->supported_asset_types);
        $this->assertTrue($scheduleA->auto_assign_assets);
    }

    public function test_creates_schedule_b_pricing(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => [
                    'monthlyBase' => 2500,
                    'setupFee' => 500,
                    'hourlyRate' => 150,
                ],
                'perUnitPricing' => [
                    'perUserMonthly' => 50,
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
                'paymentTerms' => [
                    'billingFrequency' => 'monthly',
                    'terms' => 'net_30',
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $scheduleB = $contract->schedules()->where('schedule_letter', 'B')->first();

        $this->assertNotNull($scheduleB);
        $this->assertEquals('B', $scheduleB->schedule_letter);
        $this->assertEquals(ContractSchedule::TYPE_PRICING, $scheduleB->schedule_type);
        $this->assertStringContainsString('Pricing', $scheduleB->title);
        $this->assertNotEmpty($scheduleB->content);
        $this->assertEquals('per_asset', $scheduleB->pricing_structure['billingModel']);
        $this->assertEquals(2500, $scheduleB->pricing_structure['basePricing']['monthlyBase']);
    }

    public function test_creates_schedule_c_additional_terms(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'additional_terms' => [
                'termination' => [
                    'noticePeriod' => '60 days',
                    'earlyTerminationFee' => 5000,
                ],
                'liability' => [
                    'limitAmount' => 100000,
                    'type' => 'capped',
                ],
                'dataProtection' => [
                    'gdprCompliant' => true,
                    'dataRetention' => '7 years',
                ],
                'disputeResolution' => [
                    'method' => 'arbitration',
                    'governingLaw' => 'State of Texas',
                ],
                'customClauses' => [
                    'non_compete' => '12 months',
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $scheduleC = $contract->schedules()->where('schedule_letter', 'C')->first();

        $this->assertNotNull($scheduleC);
        $this->assertEquals('C', $scheduleC->schedule_letter);
        $this->assertEquals(ContractSchedule::TYPE_ADDITIONAL, $scheduleC->schedule_type);
        $this->assertStringContainsString('Additional Terms', $scheduleC->title);
        $this->assertNotEmpty($scheduleC->content);
    }

    public function test_creates_telecom_schedule(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Telecom Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'telecom_schedule' => [
                'channelCount' => 20,
                'callingPlan' => 'unlimited_us_canada',
                'internationalCalling' => 'additional',
                'emergencyServices' => 'enabled',
                'qos' => [
                    'meanOpinionScore' => '4.2',
                    'jitterMs' => 30,
                    'packetLossPercent' => 0.1,
                    'uptimePercent' => '99.9',
                ],
                'carrier' => [
                    'primary' => 'Carrier A',
                    'backup' => 'Carrier B',
                ],
                'protocol' => 'sip',
                'codecs' => ['G.711', 'G.722', 'G.729'],
                'compliance' => [
                    'fccCompliant' => true,
                    'karisLaw' => true,
                    'rayBaums' => true,
                ],
                'security' => [
                    'encryption' => true,
                    'fraudProtection' => true,
                    'callRecording' => false,
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $telecomSchedule = $contract->schedules()->where('schedule_letter', 'D')->first();

        $this->assertNotNull($telecomSchedule);
        $this->assertEquals('D', $telecomSchedule->schedule_letter);
        $this->assertEquals('D', $telecomSchedule->schedule_type);
        $this->assertStringContainsString('Telecommunications', $telecomSchedule->title);
        $this->assertEquals(20, $telecomSchedule->variable_values['channelCount']);
        $this->assertTrue($telecomSchedule->variable_values['compliance']['karisLaw']);
    }

    public function test_creates_hardware_schedule(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Hardware Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'hardware_schedule' => [
                'selectedCategories' => ['servers', 'networking', 'storage'],
                'procurementModel' => 'direct_resale',
                'leadTimeDays' => 5,
                'leadTimeType' => 'business_days',
                'services' => [
                    'basicInstallation' => true,
                    'rackAndStack' => true,
                    'cabling' => true,
                    'powerConfiguration' => true,
                    'basicConfiguration' => true,
                ],
                'sla' => [
                    'installationTimeline' => 'Within 5 business days',
                    'configurationTimeline' => 'Within 2 business days',
                    'supportResponse' => '4_hours',
                ],
                'warranty' => [
                    'hardwarePeriod' => '3_year',
                    'supportPeriod' => '3_year',
                    'onSiteSupport' => true,
                    'advancedReplacement' => true,
                    'extendedOptions' => ['5_year_available'],
                ],
                'pricing' => [
                    'markupModel' => 'fixed_percentage',
                    'categoryMarkup' => [
                        'servers' => 15,
                        'networking' => 20,
                        'storage' => 18,
                    ],
                    'volumeTiers' => [
                        ['min' => 10000, 'discount' => 2],
                        ['min' => 50000, 'discount' => 5],
                    ],
                    'hardwarePaymentTerms' => 'net_30',
                    'servicePaymentTerms' => 'net_30',
                    'taxExempt' => false,
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $hardwareSchedule = $contract->schedules()->where('schedule_letter', 'E')->first();

        $this->assertNotNull($hardwareSchedule);
        $this->assertEquals('E', $hardwareSchedule->schedule_letter);
        $this->assertEquals('E', $hardwareSchedule->schedule_type);
        $this->assertStringContainsString('Hardware', $hardwareSchedule->title);
        $this->assertEquals('direct_resale', $hardwareSchedule->variable_values['procurementModel']);
        $this->assertTrue($hardwareSchedule->variable_values['services']['rackAndStack']);
    }

    public function test_creates_compliance_schedule(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Compliance Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'compliance_schedule' => [
                'selectedFrameworks' => ['hipaa', 'pci_dss', 'sox'],
                'scope' => 'Full enterprise compliance',
                'riskLevel' => 'high',
                'industrySector' => 'healthcare',
                'audits' => [
                    'internal' => true,
                    'external' => true,
                    'penetrationTesting' => true,
                    'vulnerabilityScanning' => true,
                    'riskAssessment' => true,
                ],
                'frequency' => [
                    'comprehensive' => 'annually',
                    'interim' => 'quarterly',
                    'vulnerability' => 'monthly',
                ],
                'deliverables' => [
                    'executiveSummary' => true,
                    'detailedFindings' => true,
                    'remediationPlan' => true,
                    'complianceMatrix' => true,
                    'dashboardReporting' => true,
                ],
                'training' => [
                    'selectedPrograms' => ['hipaa_awareness', 'security_basics', 'incident_response'],
                    'deliveryMethod' => 'hybrid',
                    'frequency' => 'annually',
                    'tracking' => [
                        'attendance' => true,
                        'assessments' => true,
                        'certifications' => true,
                    ],
                    'minimumScore' => 80,
                ],
                'monitoring' => [
                    'siem' => true,
                    'logManagement' => true,
                    'fileIntegrity' => true,
                    'accessMonitoring' => true,
                    'changeManagement' => true,
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $complianceSchedule = $contract->schedules()->where('schedule_letter', 'D')->first();

        $this->assertNotNull($complianceSchedule);
        $this->assertEquals('D', $complianceSchedule->schedule_letter);
        $this->assertEquals('D', $complianceSchedule->schedule_type);
        $this->assertStringContainsString('Compliance', $complianceSchedule->title);
        $this->assertEquals(['hipaa', 'pci_dss', 'sox'], $complianceSchedule->variable_values['selectedFrameworks']);
        $this->assertEquals('high', $complianceSchedule->variable_values['riskLevel']);
    }

    public function test_creates_multiple_schedules_in_single_contract(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Comprehensive Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server'],
                'sla' => ['serviceTier' => 'gold'],
            ],
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => ['monthlyBase' => 1000],
            ],
            'additional_terms' => [
                'termination' => ['noticePeriod' => '30 days'],
            ],
            'telecom_schedule' => [
                'channelCount' => 10,
            ],
        ];

        $contract = $this->service->createContract($data);

        $schedules = $contract->schedules;

        $this->assertGreaterThanOrEqual(4, $schedules->count());
        $this->assertNotNull($schedules->where('schedule_letter', 'A')->first());
        $this->assertNotNull($schedules->where('schedule_letter', 'B')->first());
        $this->assertNotNull($schedules->where('schedule_letter', 'C')->first());
        $this->assertNotNull($schedules->where('schedule_letter', 'D')->first());
    }

    public function test_schedule_a_contains_sla_metrics(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server'],
                'sla' => [
                    'serviceTier' => 'platinum',
                    'responseTimeHours' => 1,
                    'resolutionTimeHours' => 4,
                    'uptimePercentage' => 99.99,
                ],
            ],
        ];

        $contract = $this->service->createContract($data);
        $scheduleA = $contract->schedules()->where('schedule_letter', 'A')->first();

        $this->assertStringContainsString('platinum', strtolower($scheduleA->content));
        $this->assertStringContainsString('99.99', $scheduleA->content);
    }

    public function test_schedule_b_contains_pricing_table(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => [
                    'monthlyBase' => 2500,
                    'setupFee' => 500,
                ],
                'assetTypePricing' => [
                    'server' => ['enabled' => true, 'price' => 100],
                ],
            ],
        ];

        $contract = $this->service->createContract($data);
        $scheduleB = $contract->schedules()->where('schedule_letter', 'B')->first();

        $this->assertStringContainsString('2,500', $scheduleB->content);
        $this->assertStringContainsString('500', $scheduleB->content);
    }

    public function test_schedule_c_contains_termination_terms(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'additional_terms' => [
                'termination' => [
                    'noticePeriod' => '90 days',
                    'earlyTerminationFee' => 10000,
                ],
                'disputeResolution' => [
                    'method' => 'arbitration',
                    'governingLaw' => 'State of California',
                ],
            ],
        ];

        $contract = $this->service->createContract($data);
        $scheduleC = $contract->schedules()->where('schedule_letter', 'C')->first();

        $this->assertStringContainsString('90 days', $scheduleC->content);
        $this->assertStringContainsString('10,000', $scheduleC->content);
        $this->assertStringContainsString('arbitration', strtolower($scheduleC->content));
    }

    public function test_validates_schedule_configuration(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server'],
                'coverageRules' => ['autoAssignNewAssets' => true],
            ],
            'sla_terms' => [
                'auto_assign_new_assets' => true,
                'supported_asset_types' => ['server'],
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertArrayHasKey('schedule_validation', $contract->metadata);
        $this->assertTrue($contract->metadata['schedule_validation']['all_synchronized']);
    }

    public function test_updates_contract_pricing_from_schedules(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'pricing_schedule' => [
                'billingModel' => 'per_asset',
                'basePricing' => [
                    'monthlyBase' => 3000,
                    'setupFee' => 1000,
                ],
            ],
        ];

        $contract = $this->service->createContract($data);

        $this->assertNotNull($contract->pricing_structure);
        $this->assertEquals('per_asset', $contract->pricing_structure['billing_model']);
        $this->assertEquals(3000, $contract->pricing_structure['recurring_monthly']);
    }

    public function test_schedule_creation_logs_activities(): void
    {
        $data = [
            'client_id' => $this->client->id,
            'title' => 'Test Contract',
            'contract_type' => 'managed_services',
            'contract_type' => 'managed_services',
            'start_date' => now()->format('Y-m-d'),
            'infrastructure_schedule' => [
                'supportedAssetTypes' => ['server'],
                'sla' => ['serviceTier' => 'gold'],
            ],
        ];

        $contract = $this->service->createContract($data);

        Log::shouldHaveReceived('info')
            ->with('Starting contract schedule creation', \Mockery::any());
    }
}
