<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTemplate;
use App\Models\Quote;
use App\Models\Client;
use App\Domains\Contract\Models\ContractSignature;
use App\Domains\Contract\Models\ContractMilestone;
use App\Domains\Contract\Services\ContractClauseService;
use App\Services\TemplateVariableMapper;
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
    protected $clauseService;
    protected $variableMapper;

    public function __construct(
        PdfService $pdfService = null,
        DigitalSignatureService $signatureService = null,
        ContractClauseService $clauseService = null,
        TemplateVariableMapper $variableMapper = null
    ) {
        $this->pdfService = $pdfService;
        $this->signatureService = $signatureService;
        $this->clauseService = $clauseService ?: new ContractClauseService();
        $this->variableMapper = $variableMapper ?: new TemplateVariableMapper();
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
            'company',
            'quote',
            'template',
            'contractMilestones',
            'signatures'
        ]);

        // Get template content or use default
        $templateContent = $this->getTemplateContent($contract);

        // Process template variables
        $processedContent = $this->processTemplateVariables($templateContent, $contract);

        // Debug: Log content before PDF generation
        Log::info('Content processed for PDF generation', [
            'contract_id' => $contract->id,
            'content_length' => strlen($processedContent),
            'has_content' => !empty(trim($processedContent)),
            'content_preview' => substr($processedContent, 0, 200) . '...'
        ]);

        if (empty(trim($processedContent))) {
            throw new \Exception('Processed content is empty - cannot generate PDF');
        }

        // Generate PDF
        $pdfPath = $this->generatePDF($processedContent, $contract, $options);

        // Store document path in contract
        $currentMetadata = $contract->metadata ?? [];
        $newMetadata = array_merge($currentMetadata, [
            'document_path' => $pdfPath,
            'last_generated' => now()->toISOString(),
            'generation_options' => $options
        ]);
        
        $contract->update(['metadata' => $newMetadata]);

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
            'contract_number' => $this->generateContractNumber(),
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
     * Get template content from clauses
     */
    protected function getTemplateContent(Contract $contract): string
    {
        if ($contract->template && $contract->template->clauses()->exists()) {
            // Generate content from clauses
            $variables = $this->buildTemplateVariables($contract);
            return $this->clauseService->generateContractFromClauses($contract->template, $variables);
        }

        // Fallback to default template if no clauses exist
        throw new \Exception('Contract template has no clauses configured. All templates must use the modern clause-based system.');
    }

    /**
     * Process template variables
     */
    protected function processTemplateVariables(string $templateContent, Contract $contract): string
    {
        Log::info('Processing template variables for clause-based contract', [
            'contract_id' => $contract->id,
            'template_id' => $contract->template->id ?? null,
            'content_length' => strlen($templateContent)
        ]);
        
        // Template content is already processed from clauses in getTemplateContent()
        // Just convert to HTML format
        return $this->convertPlainTextToHtml($templateContent);
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
            if (preg_match('/^[A-Z\s]{10,}$/', $line) && !$inSchedule) {
                $html .= '<h1>' . htmlspecialchars($line) . '</h1>';
            }
            // Schedule headers (SCHEDULE A, B, C, etc.)
            elseif (preg_match('/^SCHEDULE [A-Z]/', $line)) {
                if ($inTable) {
                    $html .= '</table>';
                    $inTable = false;
                }
                $html .= '<div class="page-break schedule"><h2>' . htmlspecialchars($line) . '</h2>';
                $inSchedule = true;
            }
            // Section headers (numbered or lettered sections)
            elseif (preg_match('/^(\d+\.|\([a-z]\)|\([0-9]+\)|[A-Z]\.)\s+[A-Z]/', $line)) {
                $html .= '<h3>' . htmlspecialchars($line) . '</h3>';
            }
            // Detect table headers (lines with multiple | separators)
            elseif (substr_count($line, '|') >= 2 && !$inTable) {
                $tableHeaders = array_map('trim', explode('|', $line));
                $html .= '<table><thead><tr>';
                foreach ($tableHeaders as $header) {
                    if (!empty($header)) {
                        $html .= '<th>' . htmlspecialchars($header) . '</th>';
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
                    if (!empty($cell)) {
                        $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                    }
                }
                $html .= '</tr>';
            }
            // End of table (empty line or different content)
            elseif ($inTable && (empty($line) || substr_count($line, '|') < 2)) {
                $html .= '</tbody></table>';
                $inTable = false;
                if (!empty($line)) {
                    $html .= '<p>' . htmlspecialchars($line) . '</p>';
                }
            }
            // Signature lines
            elseif (preg_match('/^(CLIENT|COMPANY|SERVICE PROVIDER):\s*_+/', $line)) {
                $html .= '<div class="signature-section">';
                $html .= '<div class="signature-block">';
                $html .= '<p><strong>' . htmlspecialchars($line) . '</strong></p>';
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
                
                $html .= '<p>' . $line . '</p>';
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
        return $this->variableMapper->generateVariables($contract);
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
        $options = new Options();
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

        $filename = "contract-{$contract->contract_number}-" . time() . ".pdf";
        $path = "contracts/{$contract->company_id}/{$filename}";
        
        // Generate PDF content
        $pdfContent = $dompdf->output();
        
        // Debug: Check if PDF content was generated
        if (empty($pdfContent)) {
            throw new \Exception('PDF content is empty - generation failed');
        }
        
        Log::info('PDF content generated', [
            'content_size' => strlen($pdfContent),
            'path' => $path
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


    // Additional helper methods
    protected function formatAddress(Client $client): string
    {
        $address = [];
        if ($client->address) $address[] = $client->address;
        if ($client->city) $address[] = $client->city;
        if ($client->state) $address[] = $client->state;
        if ($client->postal_code) $address[] = $client->postal_code;
        
        return implode(', ', $address) ?: 'Address on file';
    }

    protected function formatCompanyAddress($company): string
    {
        $address = [];
        if ($company->address) $address[] = $company->address;
        if ($company->city) $address[] = $company->city;
        if ($company->state) $address[] = $company->state;
        
        // Handle different zip field names (Company uses 'zip', Client might use 'postal_code')
        $zipCode = $company->zip ?? $company->postal_code ?? null;
        if ($zipCode) $address[] = $zipCode;
        
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
            $words = array_filter($words, function($word) use ($suffixes) {
                return !in_array($word, $suffixes);
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
        if (!$contract->voip_specifications) {
            return '';
        }
        
        $services = is_string($contract->voip_specifications) 
            ? json_decode($contract->voip_specifications, true) 
            : $contract->voip_specifications;
            
        if (!$services || !isset($services['services'])) {
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
        if (!$contract->sla_terms) {
            return '';
        }
        
        $sla = is_string($contract->sla_terms) 
            ? json_decode($contract->sla_terms, true) 
            : $contract->sla_terms;
            
        if (!$sla) {
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
        if (!$contract->compliance_requirements) {
            return '';
        }
        
        $compliance = is_string($contract->compliance_requirements) 
            ? json_decode($contract->compliance_requirements, true) 
            : $contract->compliance_requirements;
            
        if (!$compliance) {
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
                ->where('contract_number', 'like', $prefix . '-%')
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