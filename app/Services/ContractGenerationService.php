<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Quote;
use App\Models\Client;
use App\Models\ContractSignature;
use App\Models\ContractMilestone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

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

    public function __construct(
        PdfService $pdfService = null,
        DigitalSignatureService $signatureService = null
    ) {
        $this->pdfService = $pdfService;
        $this->signatureService = $signatureService;
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
                'user_id' => Auth::id()
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
                'quote_id' => $quote->id
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
                'user_id' => Auth::id()
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
                'contract_number' => $contract->contract_number
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
                'user_id' => Auth::id()
            ]);

            // Add default values and validation
            $processedData = $this->processCustomContractData($contractData);

            // Create the contract
            $contract = Contract::create($processedData);

            // Setup basic signature requirements if not specified
            if (!isset($contractData['skip_signatures'])) {
                $this->setupBasicSignatureRequirements($contract);
            }

            Log::info('Custom contract created', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number
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
            'contract_id' => $contract->id
        ]);

        // Load contract with all necessary relationships
        $contract->load([
            'client',
            'quote',
            'template',
            'contractMilestones',
            'signatures'
        ]);

        // Get template content or use default
        $templateContent = $this->getTemplateContent($contract);

        // Process template variables
        $processedContent = $this->processTemplateVariables($templateContent, $contract);

        // Generate PDF
        $pdfPath = $this->generatePDF($processedContent, $contract, $options);

        // Store document path in contract
        $contract->update([
            'metadata' => array_merge($contract->metadata ?? [], [
                'document_path' => $pdfPath,
                'last_generated' => now(),
                'generation_options' => $options
            ])
        ]);

        Log::info('Contract document generated', [
            'contract_id' => $contract->id,
            'document_path' => $pdfPath
        ]);

        return $pdfPath;
    }

    /**
     * Regenerate contract with updated data
     */
    public function regenerateContract(Contract $contract, array $changes = []): Contract
    {
        return DB::transaction(function () use ($contract, $changes) {
            Log::info('Regenerating contract', [
                'contract_id' => $contract->id,
                'changes' => array_keys($changes)
            ]);

            // Apply changes if provided
            if (!empty($changes)) {
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
                'contract_id' => $contract->id
            ]);

            return $contract->fresh();
        });
    }

    /**
     * Validate quote eligibility for contract generation
     */
    protected function validateQuoteEligibility(Quote $quote): void
    {
        if (!$quote->isAccepted()) {
            throw new \Exception('Quote must be accepted before generating contract');
        }

        if ($quote->isConverted() && $quote->convertedInvoice) {
            Log::warning('Quote already converted to invoice', [
                'quote_id' => $quote->id,
                'invoice_id' => $quote->converted_invoice_id
            ]);
        }

        if (!$quote->isFullyApproved()) {
            throw new \Exception('Quote must be fully approved before generating contract');
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
            'template_type' => $template->template_type,
            'contract_type' => $template->template_type,
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
                if (!isset($customizations[$key]) && !isset($defaultData[$key])) {
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
            'template_type' => $template->template_type,
            'contract_type' => $template->template_type,
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
        if (!isset($defaultData['end_date']) && isset($defaultData['term_months'])) {
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
            'currency_code' => 'USD',
            'status' => Contract::STATUS_DRAFT,
            'signature_status' => Contract::SIGNATURE_PENDING,
            'renewal_type' => Contract::RENEWAL_MANUAL,
            'created_by' => Auth::id(),
        ];

        $processedData = array_merge($defaultData, $contractData);

        // Calculate end date if not provided but term_months is
        if (!isset($processedData['end_date']) && isset($processedData['term_months']) && isset($processedData['start_date'])) {
            $processedData['end_date'] = Carbon::parse($processedData['start_date'])->addMonths($processedData['term_months']);
        }

        return $processedData;
    }

    /**
     * Generate VoIP-specific configuration
     */
    protected function generateVoipConfiguration(Contract $contract, Quote $quote): void
    {
        if (!$quote->hasVoIPServices()) {
            return;
        }

        $voipConfig = [
            'services' => [],
            'equipment' => [],
            'service_levels' => [],
            'compliance' => []
        ];

        // Map quote VoIP items to contract services
        foreach ($quote->voipItems as $item) {
            $serviceConfig = [
                'service_type' => $item->service_type,
                'service_name' => $item->name,
                'quantity' => $item->quantity,
                'monthly_cost' => $item->price,
                'setup_cost' => $item->setup_cost ?? 0,
                'specifications' => $item->voip_specifications ?? []
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
            'compliance_requirements' => $this->generateComplianceTracking($quote)
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
                'created_by' => Auth::id()
            ]);
        }
    }

    /**
     * Setup signature requirements
     */
    protected function setupSignatureRequirements(Contract $contract, ContractTemplate $template = null): void
    {
        $signatureSettings = $template->signature_settings ?? [];
        
        // Default signatures: client and company
        $defaultSignatories = [
            [
                'signatory_type' => 'client',
                'signatory_name' => $contract->client->name,
                'signatory_email' => $contract->client->email,
                'signatory_title' => $contract->client->title ?? 'Authorized Representative',
                'signing_order' => 1,
                'is_required' => true
            ],
            [
                'signatory_type' => 'company',
                'signatory_name' => Auth::user()->name,
                'signatory_email' => Auth::user()->email,
                'signatory_title' => 'Authorized Representative',
                'signing_order' => 2,
                'is_required' => true
            ]
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
                'signatory_type' => $signatory['signatory_type'],
                'signatory_name' => $signatory['signatory_name'],
                'signatory_email' => $signatory['signatory_email'],
                'signatory_title' => $signatory['signatory_title'] ?? null,
                'signature_type' => $signatureSettings['signature_type'] ?? 'electronic',
                'status' => 'pending',
                'signing_order' => $signatory['signing_order'],
                'is_required' => $signatory['is_required'] ?? true,
                'expires_at' => now()->addDays($signatureSettings['expiration_days'] ?? 30),
                'created_by' => Auth::id()
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
            'signatory_type' => 'client',
            'signatory_name' => $contract->client->name,
            'signatory_email' => $contract->client->email,
            'signature_type' => 'electronic',
            'status' => 'pending',
            'signing_order' => 1,
            'is_required' => true,
            'expires_at' => now()->addDays(30),
            'created_by' => Auth::id()
        ]);

        // Company signature
        ContractSignature::create([
            'contract_id' => $contract->id,
            'company_id' => $contract->company_id,
            'signatory_type' => 'company',
            'signatory_name' => Auth::user()->name,
            'signatory_email' => Auth::user()->email,
            'signature_type' => 'electronic',
            'status' => 'pending',
            'signing_order' => 2,
            'is_required' => true,
            'expires_at' => now()->addDays(30),
            'created_by' => Auth::id()
        ]);
    }

    /**
     * Initialize compliance tracking
     */
    protected function initializeComplianceTracking(Contract $contract, Quote $quote): void
    {
        if (!$quote->hasVoIPServices()) {
            return;
        }

        $complianceRequirements = [
            'regulatory' => [
                'fcc_compliance' => true,
                'e911_requirements' => $this->requiresE911($quote),
                'cpni_protection' => true,
                'accessibility_compliance' => true
            ],
            'data_protection' => [
                'privacy_policy_required' => true,
                'data_retention_policy' => true,
                'breach_notification' => true
            ],
            'service_requirements' => [
                'uptime_sla' => $this->getUptimeSLA($quote),
                'response_time_sla' => $this->getResponseTimeSLA($quote),
                'resolution_time_sla' => $this->getResolutionTimeSLA($quote)
            ]
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
            'line_items' => []
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
                'is_recurring' => $item->is_recurring ?? false,
                'recurring_period' => $item->recurring_period ?? 'monthly'
            ];
        }

        // Add recurring/one-time breakdown
        $recurringTotal = $quote->items()->where('is_recurring', true)->sum('subtotal');
        $oneTimeTotal = $quote->items()->where('is_recurring', '!=', true)->sum('subtotal');

        $pricing['breakdown'] = [
            'one_time' => $oneTimeTotal,
            'recurring_monthly' => $recurringTotal,
            'recurring_annual' => $recurringTotal * 12
        ];

        return $pricing;
    }

    /**
     * Get template content
     */
    protected function getTemplateContent(Contract $contract): string
    {
        if ($contract->template && $contract->template->template_content) {
            return $contract->template->template_content;
        }

        // Return default template based on contract type
        return $this->getDefaultTemplate($contract->contract_type);
    }

    /**
     * Process template variables
     */
    protected function processTemplateVariables(string $templateContent, Contract $contract): string
    {
        $variables = $this->buildTemplateVariables($contract);
        
        $processedContent = $templateContent;
        foreach ($variables as $key => $value) {
            $processedContent = str_replace("{{" . $key . "}}", $value, $processedContent);
        }

        return $processedContent;
    }

    /**
     * Build template variables
     */
    protected function buildTemplateVariables(Contract $contract): array
    {
        $contract->load(['client', 'quote']);

        return [
            'contract_number' => $contract->contract_number,
            'contract_title' => $contract->title,
            'client_name' => $contract->client->name,
            'client_address' => $this->formatAddress($contract->client),
            'contract_value' => $contract->formatCurrency($contract->contract_value),
            'start_date' => $contract->start_date->format('F j, Y'),
            'end_date' => $contract->end_date ? $contract->end_date->format('F j, Y') : 'Ongoing',
            'term_months' => $contract->term_months,
            'payment_terms' => $contract->payment_terms ?? 'Net 30',
            'governing_law' => $contract->governing_law ?? 'State of [STATE]',
            'company_name' => config('app.company_name', 'Nestogy'),
            'company_address' => config('app.company_address', ''),
            'current_date' => now()->format('F j, Y'),
            'voip_services' => $this->formatVoipServices($contract),
            'sla_terms' => $this->formatSLATerms($contract),
            'compliance_terms' => $this->formatComplianceTerms($contract)
        ];
    }

    /**
     * Generate PDF from processed content
     */
    protected function generatePDF(string $content, Contract $contract, array $options = []): string
    {
        if ($this->pdfService) {
            return $this->pdfService->generateContractPDF($content, $contract, $options);
        }

        // Fallback to DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($content);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "contract-{$contract->contract_number}-" . time() . ".pdf";
        $path = "contracts/{$contract->company_id}/{$filename}";
        
        Storage::disk('local')->put($path, $dompdf->output());

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
            'maintenance_window' => 'Sunday 2:00 AM - 6:00 AM EST'
        ];
    }

    protected function generateComplianceRequirements(Quote $quote, Client $client): array
    {
        return [
            'e911_compliance' => true,
            'fcc_regulations' => true,
            'data_privacy' => true,
            'service_quality' => true
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
                'billable' => false
            ];

            $milestones[] = [
                'title' => 'Equipment Delivery',
                'description' => 'Delivery and installation of VoIP equipment',
                'type' => 'equipment_delivery',
                'days_offset' => 7,
                'billable' => true,
                'value' => $this->calculateEquipmentValue($quote)
            ];

            $milestones[] = [
                'title' => 'Service Activation',
                'description' => 'Activate VoIP services and perform testing',
                'type' => 'go_live',
                'days_offset' => 14,
                'billable' => false
            ];
        }

        return $milestones;
    }

    protected function calculateMilestoneDate(Carbon $startDate, array $milestone): Carbon
    {
        $daysOffset = $milestone['days_offset'] ?? 0;
        return $startDate->copy()->addDays($daysOffset);
    }

    protected function getDefaultTemplate(string $contractType): string
    {
        // Return basic default template content
        // In a real implementation, this would load from files or database
        return "
        <h1>{{contract_title}}</h1>
        <p>Contract Number: {{contract_number}}</p>
        <p>This agreement is entered into on {{current_date}} between {{company_name}} and {{client_name}}.</p>
        <h2>Terms and Conditions</h2>
        <p>Contract Value: {{contract_value}}</p>
        <p>Term: {{start_date}} to {{end_date}}</p>
        {{voip_services}}
        {{sla_terms}}
        {{compliance_terms}}
        ";
    }

    // Additional helper methods would be implemented here...
    protected function formatAddress(Client $client): string { return ''; }
    protected function formatVoipServices(Contract $contract): string { return ''; }
    protected function formatSLATerms(Contract $contract): string { return ''; }
    protected function formatComplianceTerms(Contract $contract): string { return ''; }
    protected function requiresE911(Quote $quote): bool { return true; }
    protected function getUptimeSLA(Quote $quote): string { return '99.9%'; }
    protected function getResponseTimeSLA(Quote $quote): string { return '4 hours'; }
    protected function getResolutionTimeSLA(Quote $quote): string { return '24 hours'; }
    protected function requiresHIPAACompliance(Client $client): bool { return false; }
    protected function requiresPCICompliance(Quote $quote): bool { return false; }
    protected function calculateEquipmentValue(Quote $quote): float { return 0; }
    protected function updateVoipConfiguration(Contract $contract): void {}
    protected function updateContractMilestones(Contract $contract, array $milestones): void {}
    protected function applyTemplateConfigurations(Contract $contract, ContractTemplate $template): void {}
    protected function generateDetailedSLA(Quote $quote): array { return []; }
    protected function generateComplianceTracking(Quote $quote): array { return []; }
}