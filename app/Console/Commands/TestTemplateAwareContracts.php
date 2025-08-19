<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TemplateVariableMapper;
use App\Services\TemplateContentGenerator;
use App\Services\DefinitionRegistry;
use App\Models\ContractTemplate;
use App\Models\Contract;
use App\Models\Client;

/**
 * Test Template-Aware Contract Generation
 *
 * Tests the new template-aware contract system across all 33 template types
 * to ensure proper variable mapping, content generation, and backward compatibility.
 */
class TestTemplateAwareContracts extends Command
{
    private const MAX_RETRIES = 3;

    private const DEFAULT_TIMEOUT = 30;

    private const DEFAULT_PAGE_SIZE = 50;

    protected $signature = 'test:template-contracts {--template-type=} {--category=} {--details}';
    protected $description = 'Test template-aware contract generation across all template types';

    protected $variableMapper;
    protected $contentGenerator;
    protected $definitionRegistry;

    public function __construct(
        TemplateVariableMapper $variableMapper,
        TemplateContentGenerator $contentGenerator,
        DefinitionRegistry $definitionRegistry
    ) {
        parent::__construct();
        $this->variableMapper = $variableMapper;
        $this->contentGenerator = $contentGenerator;
        $this->definitionRegistry = $definitionRegistry;
    }

    public function handle()
    {
        $this->info('🚀 Testing Template-Aware Contract Generation System');
        $this->newLine();

        // Test specific template type if provided
        if ($templateType = $this->option('template-type')) {
            return $this->testSpecificTemplateType($templateType);
        }

        // Test specific category if provided
        if ($category = $this->option('category')) {
            return $this->testTemplateCategory($category);
        }

        // Test all template categories
        $this->testAllTemplateCategories();

        $this->newLine();
        $this->info('✅ Template-aware contract generation testing completed!');
    }

    protected function testAllTemplateCategories()
    {
        $categories = [
            'msp' => 'MSP Templates (11 types)',
            'voip' => 'VoIP Templates (8 types)',
            'var' => 'VAR Templates (6 types)',
            'compliance' => 'Compliance Templates (4 types)',
            'general' => 'General Templates (4 types)'
        ];

        foreach ($categories as $category => $description) {
            $this->info("🔍 Testing {$description}");
            $this->testTemplateCategory($category);
            $this->newLine();
        }
    }

    protected function testTemplateCategory(string $category)
    {
        // Get template types for this category
        $templateTypes = $this->getTemplateTypesForCategory($category);

        if (empty($templateTypes)) {
            $this->warn("No template types found for category: {$category}");
            return;
        }

        $this->line("Found " . count($templateTypes) . " template types for {$category} category");

        foreach ($templateTypes as $templateType) {
            $result = $this->testTemplateType($templateType, $category);

            if ($result['success']) {
                $this->line("  ✅ {$templateType}: Variables mapped, Content generated");
            } else {
                $this->line("  ❌ {$templateType}: {$result['error']}");
            }
        }
    }

    protected function testSpecificTemplateType(string $templateType)
    {
        $this->info("🔍 Testing specific template type: {$templateType}");

        $category = $this->getCategoryForTemplateType($templateType);
        $result = $this->testTemplateType($templateType, $category);

        if ($result['success']) {
            $this->info("✅ Test passed for {$templateType}");

            if ($this->option('details')) {
                $this->showDetailedResults($result);
            }
        } else {
            $this->error("❌ Test failed for {$templateType}: {$result['error']}");
        }
    }

    protected function testTemplateType(string $templateType, string $expectedCategory): array
    {
        try {
            // Create mock contract with test data
            $contract = $this->createMockContract($templateType);

            // Test 1: Variable Mapping
            $variables = $this->variableMapper->generateVariables($contract);
            $detectedCategory = $this->variableMapper->getTemplateCategory($contract->template);

            if ($detectedCategory !== $expectedCategory) {
                return [
                    'success' => false,
                    'error' => "Category mismatch: expected {$expectedCategory}, got {$detectedCategory}"
                ];
            }

            // Test 2: Content Generation
            $serviceContent = $this->contentGenerator->generateServiceContent($contract, $variables);

            if (empty($serviceContent)) {
                return [
                    'success' => false,
                    'error' => "No service content generated"
                ];
            }

            // Test: Definition Registry
            $definitions = $this->definitionRegistry->getDefinitionsForTemplateCategory($detectedCategory);

            // Test 4: Check for legacy Section A/B/C references
            if ($this->hasLegacySectionReferences($serviceContent)) {
                return [
                    'success' => false,
                    'error' => "Legacy Section A/B/C references found in generated content"
                ];
            }

            return [
                'success' => true,
                'template_type' => $templateType,
                'category' => $detectedCategory,
                'variables_count' => count($variables),
                'definitions_count' => count($definitions),
                'content_length' => strlen($serviceContent),
                'variables' => $variables,
                'content' => $serviceContent,
                'definitions' => array_keys($definitions)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Exception: " . $e->getMessage()
            ];
        }
    }

    protected function createMockContract(string $templateType): Contract
    {
        // Create mock contract template
        $template = new ContractTemplate([
            'template_type' => $templateType,
            'name' => ucwords(str_replace('_', ' ', $templateType)) . ' Template',
            'category' => $this->getCategoryForTemplateType($templateType),
        ]);

        // Create mock client
        $client = new Client([
            'name' => 'Test Client Inc.',
            'company_id' => 1,
        ]);

        // Create mock contract with template-specific test data
        $contract = new Contract([
            'title' => 'Test Contract',
            'contract_type' => $templateType,
            'client_id' => 1,
            'company_id' => 1,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'currency_code' => 'USD',
        ]);

        // Set relationships
        $contract->setRelation('template', $template);
        $contract->setRelation('client', $client);
        $contract->setRelation('schedules', collect($this->getMockScheduleData($templateType)));

        return $contract;
    }

    protected function getMockScheduleData(string $templateType): array
    {
        $category = $this->getCategoryForTemplateType($templateType);

        return match ($category) {
            'msp' => $this->getMspMockData(),
            'voip' => $this->getVoipMockData(),
            'var' => $this->getVarMockData(),
            'compliance' => $this->getComplianceMockData(),
            'general' => $this->getGeneralMockData(),
            default => []
        };
    }

    protected function getMspMockData(): array
    {
        return [
            (object) [
                'schedule_type' => 'infrastructure',
                'schedule_data' => [
                    'supportedAssetTypes' => ['server', 'workstation', 'network_device'],
                    'sla' => [
                        'serviceTier' => 'gold',
                        'responseTimeHours' => 2,
                        'resolutionTimeHours' => 12,
                        'uptimePercentage' => 99.9
                    ],
                    'coverageRules' => [
                        'businessHours' => '24x7',
                        'includeRemoteSupport' => true,
                        'includeOnsiteSupport' => true
                    ]
                ]
            ],
            (object) [
                'schedule_type' => 'pricing',
                'schedule_data' => [
                    'billingModel' => 'per_asset',
                    'basePricing' => [
                        'monthlyBase' => '$2500',
                        'hourlyRate' => '$150'
                    ]
                ]
            ]
        ];
    }

    protected function getVoipMockData(): array
    {
        return [
            (object) [
                'schedule_type' => 'telecom',
                'schedule_data' => [
                    'channelCount' => 25,
                    'callingPlan' => 'unlimited_local_long_distance',
                    'protocol' => 'sip',
                    'qos' => [
                        'meanOpinionScore' => '4.2',
                        'jitterMs' => self::DEFAULT_TIMEOUT,
                        'packetLossPercent' => 0.1,
                        'latencyMs' => 80
                    ],
                    'compliance' => [
                        'fccCompliant' => true,
                        'karisLaw' => true,
                        'rayBaums' => true
                    ]
                ]
            ]
        ];
    }

    protected function getVarMockData(): array
    {
        return [
            (object) [
                'schedule_type' => 'hardware',
                'schedule_data' => [
                    'selectedCategories' => ['servers', 'networking', 'storage'],
                    'procurementModel' => 'direct_resale',
                    'leadTimeDays' => 5,
                    'services' => [
                        'basicInstallation' => true,
                        'basicConfiguration' => true,
                        'projectManagement' => true
                    ],
                    'warranty' => [
                        'hardwarePeriod' => '3_year',
                        'supportPeriod' => '3_year',
                        'onSiteSupport' => true
                    ]
                ]
            ]
        ];
    }

    protected function getComplianceMockData(): array
    {
        return [
            (object) [
                'schedule_type' => 'compliance',
                'schedule_data' => [
                    'selectedFrameworks' => ['hipaa', 'sox'],
                    'riskLevel' => 'high',
                    'industrySector' => 'Healthcare',
                    'audits' => [
                        'internal' => true,
                        'external' => true,
                        'penetrationTesting' => true
                    ],
                    'frequency' => [
                        'comprehensive' => 'annually',
                        'interim' => 'quarterly'
                    ]
                ]
            ]
        ];
    }

    protected function getGeneralMockData(): array
    {
        return [
            (object) [
                'schedule_type' => 'general',
                'schedule_data' => [
                    'billingModel' => 'consumption_based',
                    'serviceDescription' => 'Flexible services model'
                ]
            ]
        ];
    }

    protected function getTemplateTypesForCategory(string $category): array
    {
        return match ($category) {
            'msp' => [
                'managed_services', 'cybersecurity_services', 'backup_dr', 'cloud_migration',
                'm365_management', 'break_fix', 'enterprise_managed', 'mdr_services',
                'support_contract', 'maintenance_agreement', 'sla_contract'
            ],
            'voip' => [
                'hosted_pbx', 'sip_trunking', 'unified_communications', 'international_calling',
                'contact_center', 'e911_services', 'number_porting', 'service_agreement'
            ],
            'var' => [
                'hardware_procurement', 'software_licensing', 'vendor_partner',
                'solution_integration', 'equipment_lease', 'installation_contract'
            ],
            'compliance' => [
                'business_associate', 'professional_services', 'data_processing', 'master_service'
            ],
            'general' => [
                'consumption_based', 'international_service'
            ],
            default => []
        };
    }

    protected function getCategoryForTemplateType(string $templateType): string
    {
        return TemplateVariableMapper::TEMPLATE_CATEGORIES[$templateType] ?? 'general';
    }

    protected function hasLegacySectionReferences(string $content): bool
    {
        return preg_match('/Section [ABC](?![a-z])/i', $content) === 1;
    }

    protected function showDetailedResults(array $result)
    {
        $this->line("📊 Detailed Results:");
        $this->line("  Template Type: {$result['template_type']}");
        $this->line("  Category: {$result['category']}");
        $this->line("  Variables Generated: {$result['variables_count']}");
        $this->line("  Definitions Available: {$result['definitions_count']}");
        $this->line("  Content Length: {$result['content_length']} characters");

        if ($this->option('details')) {
            $this->newLine();
            $this->line("🔧 Generated Variables:");
            foreach ($result['variables'] as $key => $value) {
                $displayValue = is_array($value) ? '[array]' : (is_bool($value) ? ($value ? 'true' : 'false') : substr($value, 0, self::DEFAULT_PAGE_SIZE));
                $this->line("  {$key}: {$displayValue}");
            }

            $this->newLine();
            $this->line("📖 Available Definitions:");
            foreach ($result['definitions'] as $definition) {
                $this->line("  - {$definition}");
            }

            $this->newLine();
            $this->line("📄 Generated Content Preview:");
            $this->line(substr($result['content'], 0, 300) . '...');
        }
    }
}
