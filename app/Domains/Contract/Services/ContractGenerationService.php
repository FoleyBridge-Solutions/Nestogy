<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractMilestone;
use App\Domains\Contract\Models\ContractSignature;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Core\Services\TemplateVariableMapper;
use App\Exceptions\ContractGenerationException;
use App\Exceptions\ContractStatusException;
use App\Models\Client;
use App\Models\Quote;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ContractGenerationService
 *
 * Enterprise contract generation service with template-based creation,
 * VoIP-specific features, digital signature integration, and compliance tracking.
 */
class ContractGenerationService
{
    protected $pdfService;

    protected $signatureService;

    protected $templateEngine;

    protected $clauseService;

    protected $variableMapper;

    public function __construct(
        ?PdfService $pdfService = null,
        ?DigitalSignatureService $signatureService = null,
        ?ContractClauseService $clauseService = null,
        ?\App\Domains\Core\Services\TemplateVariableMapper $variableMapper = null
    ) {
        $this->pdfService = $pdfService;
        $this->signatureService = $signatureService;
        $this->clauseService = $clauseService ?: new ContractClauseService;
        $this->variableMapper = $variableMapper ?: new \App\Domains\Core\Services\TemplateVariableMapper;
    }

    /**
     * Generate contract from quote using template
     */
    public function generateFromQuote(
        Quote $quote,
        ContractTemplate $template,
        array $customizations = []
    ): Contract {
        return DB::transaction(function () use ($quote, $template, $customizations) {
            Log::info('Starting contract generation from quote', [
                'quote_id' => $quote->id,
                'template_id' => $template->id,
                'user_id' => Auth::id(),
            ]);

            // Validate quote is eligible for contract generation
            $this->validateQuoteEligibility($quote);

            // Generate contract data from quote and template
            $contractData = $this->buildContractData($quote, $template, $customizations);

            // Create the contract
            $contract = Contract::create($contractData);

            // Generate VoIP-specific configurations
            $this->generateVoipConfiguration($contract, $quote);

            // Create milestones if specified in template or quote
            $this->createContractMilestones($contract, $quote, $template);

            // Setup signature requirements
            $this->setupSignatureRequirements($contract, $template);

            // Generate initial contract document
            $this->generateContractDocument($contract);

            // Initialize compliance tracking
            $this->initializeComplianceTracking($contract, $quote);

            Log::info('Contract generated successfully', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'quote_id' => $quote->id,
            ]);

            return $contract->fresh();
        });
    }

    /**
     * Generate contract directly from client and template
     */
    public function generateFromTemplate(
        Client $client,
        ContractTemplate $template,
        array $contractData
    ): Contract {
        return DB::transaction(function () use ($client, $template, $contractData) {
            Log::info('Starting contract generation from template', [
                'client_id' => $client->id,
                'template_id' => $template->id,
                'user_id' => Auth::id(),
            ]);

            // Build contract data from template defaults and provided data
            $processedData = $this->buildContractDataFromTemplate($client, $template, $contractData);

            // Create the contract
            $contract = Contract::create($processedData);

            // Apply template-specific configurations
            $this->applyTemplateConfigurations($contract, $template);

            // Setup signature requirements
            $this->setupSignatureRequirements($contract, $template);

            // Generate contract document
            $this->generateContractDocument($contract);

            Log::info('Contract generated from template', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
            ]);

            return $contract->fresh();
        });
    }

    /**
     * Create custom contract with full control
     */
    public function createCustomContract(array $contractData): Contract
    {
        return DB::transaction(function () use ($contractData) {
            Log::info('Creating custom contract', [
                'user_id' => Auth::id(),
            ]);

            // Add default values and validation
            $processedData = $this->processCustomContractData($contractData);

            // Create the contract
            $contract = Contract::create($processedData);

            // Setup basic signature requirements if not specified
            if (! isset($contractData['skip_signatures'])) {
                $this->setupBasicSignatureRequirements($contract);
            }

            Log::info('Custom contract created', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
            ]);

            return $contract;
        });
    }

    /**
     * Generate contract document (PDF)
     */
    public function generateContractDocument(Contract $contract, array $options = []): string
    {
        Log::info('Generating contract document', [
            'contract_id' => $contract->id,
        ]);

        // Load contract with all necessary relationships
        $contract->load([
            'client',
            'company',
            'quote',
            'template',
            'contractMilestones',
            'signatures',
            'schedules' => function ($query) {
                $query->where('status', 'active')->orderBy('schedule_letter');
            },
        ]);

        // Get template content or use default
        $templateContent = $this->getTemplateContent($contract);

        // Process template variables
        $processedContent = $this->processTemplateVariables($templateContent, $contract);

        // Debug: Log content before PDF generation
        Log::info('Content processed for PDF generation', [
            'contract_id' => $contract->id,
            'content_length' => strlen($processedContent),
            'has_content' => ! empty(trim($processedContent)),
            'content_preview' => substr($processedContent, 0, 200).'...',
        ]);

        if (empty(trim($processedContent))) {
            throw new ContractGenerationException('Processed content is empty - cannot generate PDF', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
            ]);
        }

        // Generate PDF
        $pdfPath = $this->generatePDF($processedContent, $contract, $options);

        // Store document path in contract
        $currentMetadata = $contract->metadata ?? [];
        $newMetadata = array_merge($currentMetadata, [
            'document_path' => $pdfPath,
            'last_generated' => now()->toISOString(),
            'generation_options' => $options,
        ]);

        $contract->update(['metadata' => $newMetadata]);

        Log::info('Contract document generated', [
            'contract_id' => $contract->id,
            'document_path' => $pdfPath,
        ]);

        return $pdfPath;
    }

    /**
     * Regenerate contract with updated data
     */
    public function regenerateContract(Contract $contract, array $changes = []): Contract
    {
        if ($contract->status === Contract::STATUS_SIGNED || $contract->status === Contract::STATUS_ACTIVE) {
            throw new ContractStatusException('regenerate', $contract->status, [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
            ]);
        }

        return DB::transaction(function () use ($contract, $changes) {
            Log::info('Regenerating contract', [
                'contract_id' => $contract->id,
                'changes' => array_keys($changes),
            ]);

            // Apply changes if provided
            if (! empty($changes)) {
                $contract->update($changes);
            }

            // Regenerate VoIP configuration if needed
            if (isset($changes['voip_specifications']) || isset($changes['pricing_structure'])) {
                $this->updateVoipConfiguration($contract);
            }

            // Regenerate document
            $this->generateContractDocument($contract);

            // Update milestones if necessary
            if (isset($changes['milestones'])) {
                $this->updateContractMilestones($contract, $changes['milestones']);
            }

            Log::info('Contract regenerated', [
                'contract_id' => $contract->id,
            ]);

            return $contract->fresh();
        });
    }

    /**
     * Validate quote eligibility for contract generation
     */
    protected function validateQuoteEligibility(Quote $quote): void
    {
        if (! $quote->isAccepted()) {
            throw new ContractGenerationException('Quote must be accepted before generating contract', [
                'quote_id' => $quote->id,
                'quote_status' => $quote->status ?? 'unknown',
            ]);
        }

        if ($quote->isConverted() && $quote->convertedInvoice) {
            Log::warning('Quote already converted to invoice', [
                'quote_id' => $quote->id,
                'invoice_id' => $quote->converted_invoice_id,
            ]);
        }

        if (! $quote->isFullyApproved()) {
            throw new ContractGenerationException('Quote must be fully approved before generating contract', [
                'quote_id' => $quote->id,
                'quote_status' => $quote->status ?? 'unknown',
            ]);
        }
    }

    /**
     * Build contract data from quote and template
     */
    protected function buildContractData(Quote $quote, ContractTemplate $template, array $customizations): array
    {
        $defaultData = [
            'company_id' => $quote->company_id,
            'client_id' => $quote->client_id,
            'quote_id' => $quote->id,
            'template_id' => $template->id,
            'contract_type' => $template->template_type,
            'contract_number' => $this->generateContractNumber(),
            'title' => $customizations['title'] ?? $this->generateContractTitle($quote, $template),
            'description' => $customizations['description'] ?? $quote->scope,
            'start_date' => $customizations['start_date'] ?? now(),
            'contract_value' => $quote->amount,
            'currency_code' => $quote->currency_code,
            'created_by' => Auth::id(),
        ];

        // Calculate end date based on template defaults or customizations
        if (isset($customizations['end_date'])) {
            $defaultData['end_date'] = $customizations['end_date'];
        } elseif (isset($customizations['term_months'])) {
            $defaultData['term_months'] = $customizations['term_months'];
            $defaultData['end_date'] = Carbon::parse($defaultData['start_date'])->addMonths($customizations['term_months']);
        } else {
            // Use template defaults
            $defaultTermMonths = $template->default_values['term_months'] ?? 12;
            $defaultData['term_months'] = $defaultTermMonths;
            $defaultData['end_date'] = Carbon::parse($defaultData['start_date'])->addMonths($defaultTermMonths);
        }

        // Apply template defaults
        if ($template->default_values) {
            foreach ($template->default_values as $key => $value) {
                if (! isset($customizations[$key]) && ! isset($defaultData[$key])) {
                    $defaultData[$key] = $value;
                }
            }
        }

        // Apply customizations
        $defaultData = array_merge($defaultData, $customizations);

        // Generate pricing structure from quote
        $defaultData['pricing_structure'] = $this->buildPricingStructure($quote, $template);

        return $defaultData;
    }

    /**
     * Build contract data from template for direct generation
     */
    protected function buildContractDataFromTemplate(Client $client, ContractTemplate $template, array $contractData): array
    {
        $defaultData = [
            'company_id' => $client->company_id,
            'client_id' => $client->id,
            'template_id' => $template->id,
            'contract_type' => $template->template_type,
            'contract_number' => $this->generateContractNumber(),
            'title' => $contractData['title'] ?? $template->name,
            'start_date' => $contractData['start_date'] ?? now(),
            'currency_code' => $contractData['currency_code'] ?? 'USD',
            'created_by' => Auth::id(),
        ];

        // Apply template defaults
        if ($template->default_values) {
            $defaultData = array_merge($defaultData, $template->default_values);
        }

        // Apply provided contract data
        $defaultData = array_merge($defaultData, $contractData);

        // Calculate end date if not provided
        if (! isset($defaultData['end_date']) && isset($defaultData['term_months'])) {
            $defaultData['end_date'] = Carbon::parse($defaultData['start_date'])->addMonths($defaultData['term_months']);
        }

        return $defaultData;
    }

    /**
     * Process custom contract data
     */
    protected function processCustomContractData(array $contractData): array
    {
        $defaultData = [
            'company_id' => Auth::user()->company_id,
            'contract_number' => $this->generateContractNumber(),
            'contract_type' => 'custom',
            'currency_code' => 'USD',
            'status' => Contract::STATUS_DRAFT,
            'signature_status' => Contract::SIGNATURE_PENDING,
            'renewal_type' => Contract::RENEWAL_MANUAL,
            'created_by' => Auth::id(),
        ];

        $processedData = array_merge($defaultData, $contractData);

        // Calculate end date if not provided but term_months is
        if (! isset($processedData['end_date']) && isset($processedData['term_months']) && isset($processedData['start_date'])) {
            $processedData['end_date'] = Carbon::parse($processedData['start_date'])->addMonths($processedData['term_months']);
        }

        return $processedData;
    }

    /**
     * Generate VoIP-specific configuration
     */
    protected function generateVoipConfiguration(Contract $contract, Quote $quote): void
    {
        if (! $quote->hasVoIPServices()) {
            return;
        }

        $voipConfig = [
            'services' => [],
            'equipment' => [],
            'service_levels' => [],
            'compliance' => [],
        ];

        // Map quote VoIP items to contract services
        foreach ($quote->voipItems as $item) {
            $serviceConfig = [
                'service_type' => $item->service_type,
                'service_name' => $item->name,
                'quantity' => $item->quantity,
                'monthly_cost' => $item->price,
                'setup_cost' => $item->setup_cost ?? 0,
                'specifications' => $item->voip_specifications ?? [],
            ];

            $voipConfig['services'][] = $serviceConfig;
        }

        // Generate SLA terms based on service types
        $voipConfig['service_levels'] = $this->generateSLATerms($quote);

        // Generate compliance requirements
        $voipConfig['compliance'] = $this->generateComplianceRequirements($quote, $contract->client);

        $contract->update([
            'voip_specifications' => $voipConfig,
            'sla_terms' => $this->generateDetailedSLA($quote),
            'compliance_requirements' => $this->generateComplianceTracking($quote),
        ]);
    }

    /**
     * Create contract milestones
     */
    protected function createContractMilestones(Contract $contract, Quote $quote, ContractTemplate $template): void
    {
        $milestones = [];

        // Get milestones from template
        if ($template && isset($template->default_values['milestones'])) {
            $milestones = array_merge($milestones, $template->default_values['milestones']);
        }

        // Generate VoIP-specific milestones
        if ($quote->hasVoIPServices()) {
            $milestones = array_merge($milestones, $this->generateVoipMilestones($quote));
        }

        // Get milestones from quote if specified
        if ($quote->milestones) {
            $milestones = array_merge($milestones, $quote->milestones);
        }

        // Create milestone records
        foreach ($milestones as $index => $milestone) {
            ContractMilestone::create([
                'contract_id' => $contract->id,
                'company_id' => $contract->company_id,
                'milestone_number' => sprintf('M%03d', $index + 1),
                'title' => $milestone['title'],
                'description' => $milestone['description'] ?? null,
                'milestone_type' => $milestone['type'] ?? 'project_phase',
                'planned_completion_date' => $this->calculateMilestoneDate($contract->start_date, $milestone),
                'milestone_value' => $milestone['value'] ?? 0,
                'billable' => $milestone['billable'] ?? false,
                'sort_order' => $index + 1,
                'created_by' => Auth::id(),
            ]);
        }
    }

    /**
     * Setup signature requirements
     */
    protected function setupSignatureRequirements(Contract $contract, ?ContractTemplate $template = null): void
    {
        $signatureSettings = $template->signature_settings ?? [];

        // Default signatures: client and company
        $defaultSignatories = [
            [
                'signer_type' => 'client',
                'signer_name' => $contract->client->name,
                'signer_email' => $contract->client->email,
                'signer_title' => $contract->client->title ?? 'Authorized Representative',
                'signing_order' => 1,
                'is_required' => true,
            ],
            [
                'signer_type' => 'company',
                'signer_name' => Auth::user()->name,
                'signer_email' => Auth::user()->email,
                'signer_title' => 'Authorized Representative',
                'signing_order' => 2,
                'is_required' => true,
            ],
        ];

        // Add custom signatories from template
        if (isset($signatureSettings['signatories'])) {
            $defaultSignatories = array_merge($defaultSignatories, $signatureSettings['signatories']);
        }

        // Create signature records
        foreach ($defaultSignatories as $signatory) {
            ContractSignature::create([
                'contract_id' => $contract->id,
                'company_id' => $contract->company_id,
                'signer_type' => $signatory['signer_type'] ?? $signatory['signatory_type'] ?? null,
                'signer_name' => $signatory['signer_name'] ?? $signatory['signatory_name'] ?? null,
                'signer_email' => $signatory['signer_email'] ?? $signatory['signatory_email'] ?? null,
                'signer_title' => $signatory['signer_title'] ?? $signatory['signatory_title'] ?? null,
                'signature_method' => $signatureSettings['signature_type'] ?? 'electronic',
                'status' => 'pending',
                'signing_order' => $signatory['signing_order'],
                'expires_at' => now()->addDays($signatureSettings['expiration_days'] ?? 30),
            ]);
        }
    }

    /**
     * Setup basic signature requirements (for custom contracts)
     */
    protected function setupBasicSignatureRequirements(Contract $contract): void
    {
        // Client signature
        ContractSignature::create([
            'contract_id' => $contract->id,
            'company_id' => $contract->company_id,
            'signer_type' => 'client',
            'signer_name' => $contract->client->name,
            'signer_email' => $contract->client->email,
            'signature_method' => 'electronic',
            'status' => 'pending',
            'signing_order' => 1,
            'expires_at' => now()->addDays(30),
        ]);

        // Company signature
        ContractSignature::create([
            'contract_id' => $contract->id,
            'company_id' => $contract->company_id,
            'signer_type' => 'company',
            'signer_name' => Auth::user()->name,
            'signer_email' => Auth::user()->email,
            'signature_method' => 'electronic',
            'status' => 'pending',
            'signing_order' => 2,
            'expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Initialize compliance tracking
     */
    protected function initializeComplianceTracking(Contract $contract, Quote $quote): void
    {
        if (! $quote->hasVoIPServices()) {
            return;
        }

        $complianceRequirements = [
            'regulatory' => [
                'fcc_compliance' => true,
                'e911_requirements' => $this->requiresE911($quote),
                'cpni_protection' => true,
                'accessibility_compliance' => true,
            ],
            'data_protection' => [
                'privacy_policy_required' => true,
                'data_retention_policy' => true,
                'breach_notification' => true,
            ],
            'service_requirements' => [
                'uptime_sla' => $this->getUptimeSLA($quote),
                'response_time_sla' => $this->getResponseTimeSLA($quote),
                'resolution_time_sla' => $this->getResolutionTimeSLA($quote),
            ],
        ];

        // Add industry-specific compliance if detected
        if ($this->requiresHIPAACompliance($contract->client)) {
            $complianceRequirements['industry_specific']['hipaa'] = true;
        }

        if ($this->requiresPCICompliance($quote)) {
            $complianceRequirements['industry_specific']['pci_dss'] = true;
        }

        $contract->update(['compliance_requirements' => $complianceRequirements]);
    }

    /**
     * Generate contract title
     */
    protected function generateContractTitle(Quote $quote, ContractTemplate $template): string
    {
        $baseTitle = $template->name;
        $clientName = $quote->client->name;

        return "{$baseTitle} - {$clientName}";
    }

    /**
     * Build pricing structure from quote
     */
    protected function buildPricingStructure(Quote $quote, ContractTemplate $template): array
    {
        $pricing = [
            'total_contract_value' => $quote->amount,
            'currency' => $quote->currency_code,
            'discount' => $quote->getDiscountAmount(),
            'line_items' => [],
        ];

        // Add quote items to pricing structure
        foreach ($quote->items as $item) {
            $pricing['line_items'][] = [
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->price,
                'total_price' => $item->subtotal,
                'service_type' => $item->service_type,
                'is_recurring' => !empty($item->recurring_id),
                'recurring_period' => $item->recurring_period ?? 'monthly',
            ];
        }

        // Add recurring/one-time breakdown
        // Note: is_recurring column doesn't exist, using recurring_id instead
        $recurringTotal = $quote->items()->whereNotNull('recurring_id')->sum('subtotal');
        $oneTimeTotal = $quote->items()->whereNull('recurring_id')->sum('subtotal');

        $pricing['breakdown'] = [
            'one_time' => $oneTimeTotal,
            'recurring_monthly' => $recurringTotal,
            'recurring_annual' => $recurringTotal * 12,
        ];

        return $pricing;
    }

    /**
     * Get template content from clauses
     */
    protected function getTemplateContent(Contract $contract): string
    {
        if ($contract->template && $contract->template->clauses()->exists()) {
            // Generate content from clauses
            $variables = $this->buildTemplateVariables($contract);

            return $this->clauseService->generateContractFromClauses($contract->template, $variables);
        }

        // Fallback to simple default template if no clauses exist
        return $this->generateDefaultTemplate($contract);
    }

    /**
     * Generate a simple default template when no clauses are configured
     */
    protected function generateDefaultTemplate(Contract $contract): string
    {
        $client = $contract->client;
        $company = $contract->company;
        
        $content = "<h1>{$contract->title}</h1>\n\n";
        $content .= "<p><strong>Contract Number:</strong> {$contract->contract_number}</p>\n";
        $content .= "<p><strong>Date:</strong> " . now()->format('F d, Y') . "</p>\n\n";
        
        $content .= "<h2>Parties</h2>\n";
        $content .= "<p><strong>Service Provider:</strong> {$company->name}</p>\n";
        $content .= "<p><strong>Client:</strong> {$client->name}</p>\n\n";
        
        $content .= "<h2>Contract Terms</h2>\n";
        $content .= "<p><strong>Start Date:</strong> " . $contract->start_date->format('F d, Y') . "</p>\n";
        if ($contract->end_date) {
            $content .= "<p><strong>End Date:</strong> " . $contract->end_date->format('F d, Y') . "</p>\n";
        }
        $content .= "<p><strong>Contract Value:</strong> $" . number_format($contract->contract_value, 2) . "</p>\n\n";
        
        if ($contract->description) {
            $content .= "<h2>Description</h2>\n";
            $content .= "<p>{$contract->description}</p>\n\n";
        }
        
        $content .= "<p><em>This is a system-generated contract. Please configure contract template clauses for customized contracts.</em></p>\n";
        
        return $content;
    }

    /**
     * Process template variables
     */
    protected function processTemplateVariables(string $templateContent, Contract $contract): string
    {
        Log::info('Processing template variables for clause-based contract', [
            'contract_id' => $contract->id,
            'template_id' => $contract->template->id ?? null,
            'content_length' => strlen($templateContent),
            'schedule_count' => $contract->schedules ? $contract->schedules->count() : 0,
        ]);

        // Template content is already processed from clauses in getTemplateContent()
        // Append schedules to the content
        $contentWithSchedules = $this->appendSchedulesToContent($templateContent, $contract);

        // Since template content is already HTML, wrap it properly instead of converting
        return $this->wrapContentAsHtml($contentWithSchedules);
    }

    /**
     * Append contract schedules to the main contract content
     */
    protected function appendSchedulesToContent(string $content, Contract $contract): string
    {
        if (! $contract->schedules || $contract->schedules->isEmpty()) {
            Log::info('No schedules to append for contract', ['contract_id' => $contract->id]);

            return $content;
        }

        Log::info('Appending schedules to contract content', [
            'contract_id' => $contract->id,
            'schedule_count' => $contract->schedules->count(),
        ]);

        $scheduleContent = "\n\n<div style=\"page-break-before: always;\"></div>\n";
        $scheduleContent .= "<div class=\"contract-schedules\">\n";
        $scheduleContent .= "<h1>CONTRACT SCHEDULES</h1>\n";

        foreach ($contract->schedules->sortBy('schedule_letter') as $schedule) {
            $scheduleContent .= $this->generateScheduleContent($schedule)."\n\n";
        }

        $scheduleContent .= "</div>\n";

        return $content.$scheduleContent;
    }

    /**
     * Generate formatted content for a contract schedule
     */
    protected function generateScheduleContent($schedule): string
    {
        // Create HTML-formatted schedule header
        $content = "\n<div class=\"schedule-section\">\n";
        $content .= '<h2>'.strtoupper($schedule->title)."</h2>\n";

        if ($schedule->description) {
            $content .= '<p><em>'.$schedule->description."</em></p>\n";
        }

        // If the schedule already has content, use that instead of generating new content
        if (! empty($schedule->content)) {
            $content .= $this->convertMarkdownToHtml($schedule->content)."\n";
        } else {
            // Only generate content if no pre-existing content is available
            // Add schedule-specific content based on type
            switch ($schedule->schedule_type) {
                case 'infrastructure':
                    $content .= $this->generateInfrastructureScheduleContent($schedule);
                    break;
                case 'pricing':
                    $content .= $this->generatePricingScheduleContent($schedule);
                    break;
                case 'terms':
                    $content .= $this->generateTermsScheduleContent($schedule);
                    break;
            }
        }

        $content .= "</div>\n";

        return $content;
    }

    /**
     * Generate infrastructure schedule content
     */
    protected function generateInfrastructureScheduleContent($schedule): string
    {
        $content = '';
        $data = $schedule->variable_values ?? [];

        if (! empty($data['supportedAssetTypes'])) {
            $content .= "SUPPORTED ASSET TYPES:\n";
            foreach ($data['supportedAssetTypes'] as $assetType) {
                $content .= '  - '.ucwords(str_replace('_', ' ', $assetType))."\n";
            }
            $content .= "\n";
        }

        if (! empty($data['sla'])) {
            $sla = $data['sla'];
            $content .= "SERVICE LEVEL AGREEMENTS:\n";
            $content .= '  Service Tier: '.ucfirst($sla['serviceTier'] ?? 'Standard')."\n";
            $content .= '  Response Time: '.($sla['responseTimeHours'] ?? 'N/A')." hours\n";
            $content .= '  Resolution Time: '.($sla['resolutionTimeHours'] ?? 'N/A')." hours\n";
            $content .= '  Uptime Target: '.($sla['uptimePercentage'] ?? 'N/A')."%\n\n";
        }

        if (! empty($data['coverageRules'])) {
            $coverage = $data['coverageRules'];
            $content .= "COVERAGE AND SUPPORT:\n";
            $content .= '  Business Hours: '.($coverage['businessHours'] ?? 'N/A')."\n";
            if (! empty($coverage['emergencyResponseTime'])) {
                $content .= '  Emergency Response: '.$coverage['emergencyResponseTime']." hour(s)\n";
            }
            if (! empty($coverage['includeRemoteSupport'])) {
                $content .= "  Remote Support: Included\n";
            }
            if (! empty($coverage['includeOnsiteSupport'])) {
                $content .= "  Onsite Support: Included\n";
            }
            $content .= "\n";
        }

        return $content;
    }

    /**
     * Generate pricing schedule content
     */
    protected function generatePricingScheduleContent($schedule): string
    {
        $content = '';
        $data = $schedule->variable_values ?? [];

        if (! empty($data['billingModel'])) {
            $content .= 'BILLING MODEL: '.ucwords(str_replace('_', ' ', $data['billingModel']))."\n\n";
        }

        if (! empty($data['basePricing'])) {
            $base = $data['basePricing'];
            $hasBasePricing = false;
            $basePricingContent = "BASE PRICING:\n";

            if (! empty($base['monthlyBase']) && $base['monthlyBase'] > 0) {
                $basePricingContent .= '  Monthly Base Fee: $'.number_format($base['monthlyBase'], 2)."\n";
                $hasBasePricing = true;
            }
            if (! empty($base['setupFee']) && $base['setupFee'] > 0) {
                $basePricingContent .= '  Setup Fee: $'.number_format($base['setupFee'], 2)."\n";
                $hasBasePricing = true;
            }
            if (! empty($base['hourlyRate']) && $base['hourlyRate'] > 0) {
                $basePricingContent .= '  Hourly Rate: $'.number_format($base['hourlyRate'], 2)."\n";
                $hasBasePricing = true;
            }

            if ($hasBasePricing) {
                $content .= $basePricingContent."\n";
            }
        }

        if (! empty($data['assetTypePricing'])) {
            $content .= "PER-ASSET MONTHLY PRICING:\n";
            foreach ($data['assetTypePricing'] as $assetType => $pricing) {
                if (! empty($pricing['enabled']) && ! empty($pricing['price'])) {
                    $assetName = ucwords(str_replace('_', ' ', $assetType));
                    $content .= '  '.$assetName.': $'.number_format($pricing['price'], 2)."/month\n";

                    // Include services if available
                    if (! empty($pricing['includedServices'])) {
                        $services = is_array($pricing['includedServices']) ? implode(', ', $pricing['includedServices']) : $pricing['includedServices'];
                        $content .= '    Included Services: '.$services."\n";
                    }
                }
            }
            $content .= "\n";
        }

        if (! empty($data['paymentTerms'])) {
            $terms = $data['paymentTerms'];
            $content .= "PAYMENT TERMS:\n";
            if (! empty($terms['billingFrequency'])) {
                $content .= '  Billing Frequency: '.ucfirst($terms['billingFrequency'])."\n";
            }
            if (! empty($terms['terms'])) {
                $content .= '  Payment Terms: '.str_replace('_', ' ', $terms['terms'])."\n";
            }
            $content .= "\n";
        }

        return $content;
    }

    /**
     * Generate terms schedule content
     */
    protected function generateTermsScheduleContent($schedule): string
    {
        $content = '';
        if ($schedule->content) {
            $content .= $schedule->content."\n";
        }

        return $content;
    }

    /**
     * Convert markdown content to HTML for contract output
     */
    protected function convertMarkdownToHtml(string $markdown): string
    {
        // Convert markdown to HTML
        $html = $markdown;

        // Convert headers
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^##### (.+)$/m', '<h5>$1</h5>', $html);
        $html = preg_replace('/^###### (.+)$/m', '<h6>$1</h6>', $html);

        // Convert bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        $html = preg_replace('/`(.+?)`/', '<code>$1</code>', $html);

        // Convert unordered lists
        $html = preg_replace('/^[\-\*\+] (.+)$/m', '<li>$1</li>', $html);

        // Convert ordered lists
        $html = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $html);

        // Wrap consecutive <li> tags in <ul> tags
        $html = preg_replace('/(<li>.*<\/li>\s*)+/s', '<ul>$0</ul>', $html);

        // Convert markdown tables to HTML tables
        $html = $this->convertMarkdownTables($html);

        // Convert line breaks to paragraphs
        $paragraphs = explode("\n\n", $html);
        $html = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (! empty($paragraph) && ! preg_match('/^<[h1-6ul]/', $paragraph)) {
                $html .= '<p>'.$paragraph.'</p>'."\n";
            } else {
                $html .= $paragraph."\n";
            }
        }

        // Clean up extra whitespace
        $html = preg_replace('/\n+/', "\n", $html);
        $html = trim($html);

        return $html;
    }

    /**
     * Convert markdown tables to HTML tables
     */
    protected function convertMarkdownTables(string $content): string
    {
        // Split content into lines for table processing
        $lines = explode("\n", $content);
        $result = [];
        $i = 0;

        while ($i < count($lines)) {
            $line = trim($lines[$i]);

            // Check if this line looks like a table row (starts and ends with |)
            if (preg_match('/^\|.*\|$/', $line)) {
                $tableLines = [];
                $tableStartIndex = $i;

                // Collect all consecutive table lines
                while ($i < count($lines) && preg_match('/^\|.*\|$/', trim($lines[$i]))) {
                    $tableLines[] = trim($lines[$i]);
                    $i++;
                }

                // Check if we have at least a header row
                if (count($tableLines) >= 1) {
                    $htmlTable = $this->buildHtmlTable($tableLines);
                    $result[] = $htmlTable;

                    continue; // Skip the increment at the end since we already advanced $i
                }
            }

            // Not a table line, add as-is
            $result[] = $lines[$i];
            $i++;
        }

        return implode("\n", $result);
    }

    /**
     * Build HTML table from markdown table lines
     */
    protected function buildHtmlTable(array $tableLines): string
    {
        if (empty($tableLines)) {
            return '';
        }

        $html = '<table class="schedule-table">'."\n";

        // Process each row
        for ($i = 0; $i < count($tableLines); $i++) {
            $line = $tableLines[$i];

            // Skip separator lines (like |----|----|)
            if (preg_match('/^\|[\s\-:]*\|$/', $line)) {
                continue;
            }

            // Parse table cells
            $cells = $this->parseTableCells($line);

            // Determine if this is a header row (first non-separator row)
            $isHeader = ($i === 0 || ($i === 1 && preg_match('/^\|[\s\-:]*\|$/', $tableLines[0])));

            $tag = $isHeader ? 'th' : 'td';
            $html .= '  <tr>'."\n";

            foreach ($cells as $cell) {
                $html .= "    <{$tag}>".trim($cell)."</{$tag}>"."\n";
            }

            $html .= '  </tr>'."\n";
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Parse table cells from a markdown table row
     */
    protected function parseTableCells(string $line): array
    {
        // Remove leading and trailing | characters
        $line = preg_replace('/^\|/', '', $line);
        $line = preg_replace('/\|$/', '', $line);

        // Split by | and trim each cell
        $cells = array_map('trim', explode('|', $line));

        return $cells;
    }

    /**
     * Wrap already-formatted HTML content with proper document structure
     */
    protected function wrapContentAsHtml(string $htmlContent): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Document</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        h1 {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            page-break-before: avoid;
        }
        h2 {
            font-size: 16px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
            page-break-before: avoid;
        }
        h3 {
            font-size: 14px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        h4 {
            font-size: 13px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        p {
            margin-bottom: 10px;
            text-align: justify;
        }
        ul, ol {
            margin-bottom: 15px;
            padding-left: 30px;
        }
        li {
            margin-bottom: 5px;
        }
        .contract-clause {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .contract-schedules {
            page-break-before: always;
        }
        .schedule-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-block {
            margin-bottom: 30px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            height: 20px;
            margin: 10px 0;
        }
        .schedule-table {
            border-collapse: collapse;
            width: 100%;
            margin: 15px 0;
            font-size: 11px;
        }
        .schedule-table th,
        .schedule-table td {
            border: 1px solid #333;
            padding: 8px 12px;
            text-align: left;
            vertical-align: top;
        }
        .schedule-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .schedule-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        @media print {
            body { padding: 20px; }
            .page-break { page-break-before: always; }
            .schedule-table { 
                page-break-inside: avoid; 
            }
        }
    </style>
</head>
<body>
'.$htmlContent.'
</body>
</html>';
    }

    /**
     * Convert plain text contract to properly formatted HTML
     */
    protected function convertPlainTextToHtml(string $content): string
    {
        // Start with basic HTML structure
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Document</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        h1 {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        h2 {
            font-size: 14px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 15px;
            text-decoration: underline;
        }
        h3 {
            font-size: 13px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        p {
            margin-bottom: 12px;
            text-align: justify;
        }
        .contract-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .parties-section {
            margin-bottom: 30px;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-block {
            float: left;
            width: 45%;
            margin-right: 10%;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            width: 200px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .schedule {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        ol, ul {
            margin-bottom: 15px;
            padding-left: 30px;
        }
        li {
            margin-bottom: 8px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>';

        // Split content into lines and process
        $lines = explode("\n", $content);
        $inSchedule = false;
        $inTable = false;
        $tableHeaders = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines but add spacing
            if (empty($line)) {
                $html .= '<br>';

                continue;
            }

            // Main title (all caps, first significant line)
            if (preg_match('/^[A-Z\s]{10,}$/', $line) && ! $inSchedule) {
                $html .= '<h1>'.htmlspecialchars($line).'</h1>';
            }
            // Schedule headers (SCHEDULE A, B, C, etc.)
            elseif (preg_match('/^SCHEDULE [A-Z]/', $line)) {
                if ($inTable) {
                    $html .= '</table>';
                    $inTable = false;
                }
                $html .= '<div class="page-break schedule"><h2>'.htmlspecialchars($line).'</h2>';
                $inSchedule = true;
            }
            // Section headers (numbered or lettered sections)
            elseif (preg_match('/^(\d+\.|\([a-z]\)|\([0-9]+\)|[A-Z]\.)\s+[A-Z]/', $line)) {
                $html .= '<h3>'.htmlspecialchars($line).'</h3>';
            }
            // Detect table headers (lines with multiple | separators)
            elseif (substr_count($line, '|') >= 2 && ! $inTable) {
                $tableHeaders = array_map('trim', explode('|', $line));
                $html .= '<table><thead><tr>';
                foreach ($tableHeaders as $header) {
                    if (! empty($header)) {
                        $html .= '<th>'.htmlspecialchars($header).'</th>';
                    }
                }
                $html .= '</tr></thead><tbody>';
                $inTable = true;
            }
            // Table rows
            elseif (substr_count($line, '|') >= 2 && $inTable) {
                $cells = array_map('trim', explode('|', $line));
                $html .= '<tr>';
                foreach ($cells as $cell) {
                    if (! empty($cell)) {
                        $html .= '<td>'.htmlspecialchars($cell).'</td>';
                    }
                }
                $html .= '</tr>';
            }
            // End of table (empty line or different content)
            elseif ($inTable && (empty($line) || substr_count($line, '|') < 2)) {
                $html .= '</tbody></table>';
                $inTable = false;
                if (! empty($line)) {
                    $html .= '<p>'.htmlspecialchars($line).'</p>';
                }
            }
            // Signature lines
            elseif (preg_match('/^(CLIENT|COMPANY|SERVICE PROVIDER):\s*_+/', $line)) {
                $html .= '<div class="signature-section">';
                $html .= '<div class="signature-block">';
                $html .= '<p><strong>'.htmlspecialchars($line).'</strong></p>';
                $html .= '<div class="signature-line"></div>';
                $html .= '<p>Date: _______________</p>';
                $html .= '</div>';
                $html .= '</div>';
            }
            // Regular paragraphs
            else {
                // Convert simple formatting
                $line = htmlspecialchars($line);
                $line = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $line);
                $line = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $line);

                $html .= '<p>'.$line.'</p>';
            }
        }

        // Close any open table
        if ($inTable) {
            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Build template variables using new TemplateVariableMapper
     */
    protected function buildTemplateVariables(Contract $contract): array
    {
        // Use the TemplateVariableMapper for template-aware variable generation
        $baseVariables = $this->variableMapper->generateVariables($contract);

        // Add variables from schedule data
        $scheduleVariables = $this->extractVariablesFromSchedules($contract);

        // Merge and prioritize schedule variables over base variables
        return array_merge($baseVariables, $scheduleVariables);
    }

    /**
     * Extract variables from contract schedules
     */
    protected function extractVariablesFromSchedules(Contract $contract): array
    {
        $variables = [];

        if (! $contract->schedules) {
            return $variables;
        }

        foreach ($contract->schedules as $schedule) {
            if (! $schedule->variable_values) {
                continue;
            }

            $data = $schedule->variable_values;

            // Extract infrastructure/SLA variables (Schedule A)
            if ($schedule->schedule_type === 'A') {
                if (! empty($data['sla']['serviceTier'])) {
                    $variables['service_tier'] = $data['sla']['serviceTier'];
                }
                if (! empty($data['sla']['responseTimeHours'])) {
                    $variables['response_time_hours'] = $data['sla']['responseTimeHours'];
                }
                if (! empty($data['sla']['resolutionTimeHours'])) {
                    $variables['resolution_time_hours'] = $data['sla']['resolutionTimeHours'];
                }
                if (! empty($data['sla']['uptimePercentage'])) {
                    $variables['uptime_percentage'] = $data['sla']['uptimePercentage'];
                }
                if (! empty($data['coverageRules']['businessHours'])) {
                    $variables['business_hours'] = $data['coverageRules']['businessHours'];
                }
                if (! empty($data['supportedAssetTypes'])) {
                    $variables['supported_asset_types'] = implode(', ', array_map(function ($type) {
                        return ucwords(str_replace('_', ' ', $type));
                    }, $data['supportedAssetTypes']));
                }
            }

            // Extract pricing variables (Schedule B)
            if ($schedule->schedule_type === 'B') {
                if (! empty($data['billingModel'])) {
                    $variables['billing_model'] = $data['billingModel'];
                }
                if (! empty($data['basePricing']['monthlyBase'])) {
                    $variables['monthly_base_fee'] = $data['basePricing']['monthlyBase'];
                }
                if (! empty($data['basePricing']['setupFee'])) {
                    $variables['setup_fee'] = $data['basePricing']['setupFee'];
                }
                if (! empty($data['basePricing']['hourlyRate'])) {
                    $variables['hourly_rate'] = $data['basePricing']['hourlyRate'];
                }

                // Extract asset pricing (use first available as default)
                if (! empty($data['assetTypePricing'])) {
                    $firstAsset = array_values($data['assetTypePricing'])[0] ?? null;
                    if ($firstAsset && ! empty($firstAsset['price'])) {
                        $variables['per_asset_rate'] = $firstAsset['price'];
                    }
                }

                // Set default values for common missing variables
                $variables['p1_hourly_rate'] = $variables['hourly_rate'] ?? 200;
                $variables['p2_hourly_rate'] = $variables['hourly_rate'] ?? 150;
                $variables['p3_hourly_rate'] = $variables['hourly_rate'] ?? 125;
                $variables['monitoring_hourly_rate'] = 75;
                $variables['project_hourly_rate'] = 150;
                $variables['consulting_hourly_rate'] = 175;
            }
        }

        // Add default values for commonly missing variables
        $variables['else'] = '';
        $variables['item'] = '';

        Log::info('Extracted variables from schedules', [
            'contract_id' => $contract->id,
            'extracted_count' => count($variables),
            'variables' => array_keys($variables),
        ]);

        return $variables;
    }

    /**
     * Generate PDF from processed content
     */
    protected function generatePDF(string $content, Contract $contract, array $options = []): string
    {
        if ($this->pdfService) {
            return $this->pdfService->generateContractPDF($content, $contract, $options);
        }

        // Fallback to DomPDF with improved options for HTML rendering
        $options = new Options;
        $options->set('defaultFont', 'Times-Roman');
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultMediaType', 'print');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "contract-{$contract->contract_number}-".time().'.pdf';
        $path = "contracts/{$contract->company_id}/{$filename}";

        // Generate PDF content
        $pdfContent = $dompdf->output();

        // Debug: Check if PDF content was generated
        if (empty($pdfContent)) {
            throw new ContractGenerationException('PDF content is empty - generation failed', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
            ]);
        }

        Log::info('PDF content generated', [
            'content_size' => strlen($pdfContent),
            'path' => $path,
        ]);

        // Save PDF to configured storage (S3)
        Storage::put($path, $pdfContent);

        return $path;
    }

    /**
     * Additional helper methods for VoIP-specific functionality
     */
    protected function generateSLATerms(Quote $quote): array
    {
        return [
            'uptime_guarantee' => '99.9%',
            'response_time' => '4 hours',
            'resolution_time' => '24 hours',
            'maintenance_window' => 'Sunday 2:00 AM - 6:00 AM EST',
        ];
    }

    protected function generateComplianceRequirements(Quote $quote, Client $client): array
    {
        return [
            'e911_compliance' => true,
            'fcc_regulations' => true,
            'data_privacy' => true,
            'service_quality' => true,
        ];
    }

    protected function generateVoipMilestones(Quote $quote): array
    {
        $milestones = [];

        if ($quote->hasVoIPServices()) {
            $milestones[] = [
                'title' => 'Service Configuration',
                'description' => 'Configure VoIP services and settings',
                'type' => 'service_activation',
                'days_offset' => 0,
                'billable' => false,
            ];

            $milestones[] = [
                'title' => 'Equipment Delivery',
                'description' => 'Delivery and installation of VoIP equipment',
                'type' => 'equipment_delivery',
                'days_offset' => 7,
                'billable' => true,
                'value' => $this->calculateEquipmentValue($quote),
            ];

            $milestones[] = [
                'title' => 'Service Activation',
                'description' => 'Activate VoIP services and perform testing',
                'type' => 'go_live',
                'days_offset' => 14,
                'billable' => false,
            ];
        }

        return $milestones;
    }

    protected function calculateMilestoneDate(Carbon $startDate, array $milestone): Carbon
    {
        $daysOffset = $milestone['days_offset'] ?? 0;

        return $startDate->copy()->addDays($daysOffset);
    }

    // Additional helper methods
    protected function formatAddress(Client $client): string
    {
        $address = [];
        if ($client->address) {
            $address[] = $client->address;
        }
        if ($client->city) {
            $address[] = $client->city;
        }
        if ($client->state) {
            $address[] = $client->state;
        }
        if ($client->postal_code) {
            $address[] = $client->postal_code;
        }

        return implode(', ', $address) ?: 'Address on file';
    }

    protected function formatCompanyAddress($company): string
    {
        $address = [];
        if ($company->address) {
            $address[] = $company->address;
        }
        if ($company->city) {
            $address[] = $company->city;
        }
        if ($company->state) {
            $address[] = $company->state;
        }

        // Handle different zip field names (Company uses 'zip', Client might use 'postal_code')
        $zipCode = $company->zip ?? $company->postal_code ?? null;
        if ($zipCode) {
            $address[] = $zipCode;
        }

        return implode(', ', $address) ?: 'Address on file';
    }

    protected function getCompanyShortName(string $companyName): string
    {
        // Extract short name from company name
        // "FoleyBridge Solutions" -> "FoleyBridge"
        $words = explode(' ', $companyName);

        // If multiple words, try to get a meaningful short name
        if (count($words) > 1) {
            // Remove common suffixes
            $suffixes = ['Solutions', 'Inc', 'LLC', 'Corp', 'Corporation', 'Company', 'Ltd', 'Limited'];
            $words = array_filter($words, function ($word) use ($suffixes) {
                return ! in_array($word, $suffixes);
            });
        }

        // Return first word or combination of first words
        return count($words) > 1 ? implode('', array_slice($words, 0, 2)) : $words[0];
    }

    /**
     * Check if contract has support for specific asset types
     */
    protected function hasAssetTypeSupport(Contract $contract, array $targetAssetTypes): bool
    {
        if ($contract->schedules && $contract->schedules->isNotEmpty()) {
            $infraSchedule = $contract->schedules->where('schedule_type', 'infrastructure')->first();
            if ($infraSchedule && isset($infraSchedule->schedule_data['supportedAssetTypes'])) {
                $supportedTypes = $infraSchedule->schedule_data['supportedAssetTypes'];

                // Check if any of the target asset types are supported
                foreach ($targetAssetTypes as $targetType) {
                    if (in_array($targetType, $supportedTypes)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function formatVoipServices(Contract $contract): string
    {
        if (! $contract->voip_specifications) {
            return '';
        }

        $services = is_string($contract->voip_specifications)
            ? json_decode($contract->voip_specifications, true)
            : $contract->voip_specifications;

        if (! $services || ! isset($services['services'])) {
            return '';
        }

        $formatted = "<h3>VoIP Services Included:</h3>\n<ul>\n";
        foreach ($services['services'] as $service) {
            $formatted .= "<li>{$service['service_name']} (Quantity: {$service['quantity']})</li>\n";
        }
        $formatted .= "</ul>\n";

        return $formatted;
    }

    protected function formatSLATerms(Contract $contract): string
    {
        if (! $contract->sla_terms) {
            return '';
        }

        $sla = is_string($contract->sla_terms)
            ? json_decode($contract->sla_terms, true)
            : $contract->sla_terms;

        if (! $sla) {
            return '';
        }

        $formatted = "<h3>Service Level Agreement:</h3>\n<ul>\n";
        if (isset($sla['uptime_guarantee'])) {
            $formatted .= "<li>Uptime Guarantee: {$sla['uptime_guarantee']}</li>\n";
        }
        if (isset($sla['response_time'])) {
            $formatted .= "<li>Response Time: {$sla['response_time']}</li>\n";
        }
        if (isset($sla['resolution_time'])) {
            $formatted .= "<li>Resolution Time: {$sla['resolution_time']}</li>\n";
        }
        $formatted .= "</ul>\n";

        return $formatted;
    }

    protected function formatComplianceTerms(Contract $contract): string
    {
        if (! $contract->compliance_requirements) {
            return '';
        }

        $compliance = is_string($contract->compliance_requirements)
            ? json_decode($contract->compliance_requirements, true)
            : $contract->compliance_requirements;

        if (! $compliance) {
            return '';
        }

        $formatted = "<h3>Compliance Requirements:</h3>\n<ul>\n";
        if (isset($compliance['regulatory']['fcc_compliance']) && $compliance['regulatory']['fcc_compliance']) {
            $formatted .= "<li>FCC Compliance Required</li>\n";
        }
        if (isset($compliance['data_protection']['privacy_policy_required']) && $compliance['data_protection']['privacy_policy_required']) {
            $formatted .= "<li>Privacy Policy Compliance</li>\n";
        }
        $formatted .= "</ul>\n";

        return $formatted;
    }

    protected function requiresE911(Quote $quote): bool
    {
        return true;
    }

    protected function getUptimeSLA(Quote $quote): string
    {
        return '99.9%';
    }

    protected function getResponseTimeSLA(Quote $quote): string
    {
        return '4 hours';
    }

    protected function getResolutionTimeSLA(Quote $quote): string
    {
        return '24 hours';
    }

    protected function requiresHIPAACompliance(Client $client): bool
    {
        return false;
    }

    protected function requiresPCICompliance(Quote $quote): bool
    {
        return false;
    }

    protected function calculateEquipmentValue(Quote $quote): float
    {
        return 0;
    }

    protected function updateVoipConfiguration(Contract $contract): void {}

    protected function updateContractMilestones(Contract $contract, array $milestones): void {}

    protected function applyTemplateConfigurations(Contract $contract, ContractTemplate $template): void {}

    protected function generateDetailedSLA(Quote $quote): array
    {
        return [];
    }

    protected function generateComplianceTracking(Quote $quote): array
    {
        return [];
    }

    /**
     * Generate unique contract number
     */
    protected function generateContractNumber(string $prefix = 'CNT'): string
    {
        // Get company ID from the authenticated user or fallback
        $companyId = Auth::user()->company_id ?? auth()->user()->company_id ?? 1;

        // Generate a unique contract number using database transaction for race condition safety
        return DB::transaction(function () use ($prefix, $companyId) {
            // Get the highest existing number for this company and prefix
            $lastNumber = Contract::where('company_id', $companyId)
                ->where('contract_number', 'like', $prefix.'-%')
                ->orderBy('contract_number', 'desc')
                ->value('contract_number');

            if ($lastNumber) {
                // Extract the numeric part and increment
                $parts = explode('-', $lastNumber);
                $nextNumber = (int) ($parts[1] ?? 0) + 1;
            } else {
                $nextNumber = 1;
            }

            // Format with leading zeros
            $contractNumber = sprintf('%s-%04d', $prefix, $nextNumber);

            // Double-check uniqueness in case of concurrent requests
            while (Contract::where('contract_number', $contractNumber)->exists()) {
                $nextNumber++;
                $contractNumber = sprintf('%s-%04d', $prefix, $nextNumber);
            }

            return $contractNumber;
        });
    }
}
