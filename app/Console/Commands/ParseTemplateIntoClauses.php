<?php

namespace App\Console\Commands;

use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Contract\Models\ContractClause;
use App\Domains\Contract\Services\ContractClauseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ParseTemplateIntoClauses extends Command
{
    private const DEFAULT_TIMEOUT = 30;

    private const DEFAULT_PAGE_SIZE = 50;

    private const DEFAULT_BATCH_SIZE = 100;

    // Class constants to reduce duplication
    private const CLAUSE_TYPE_STANDARD = 'standard';
    private const CLAUSE_TYPE_CUSTOM = 'custom';
    private const MSG_PARSE_START = 'Parsing template into clauses...';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nestogy:parse-template-clauses
                            {template? : The template name or ID to parse}
                            {--company-id= : Company ID to create clauses for}
                            {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse a contract template into modular reusable clauses';

    /**
     * The contract clause service.
     */
    protected ContractClauseService $clauseService;

    /**
     * Create a new command instance.
     */
    public function __construct(ContractClauseService $clauseService)
    {
        parent::__construct();
        $this->clauseService = $clauseService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $templateIdentifier = $this->argument('template');
        $companyId = $this->option('company-id') ?? 1; // Default to company 1
        $isDryRun = $this->option('dry-run');

        // Find template
        if ($templateIdentifier) {
            if (is_numeric($templateIdentifier)) {
                $template = ContractTemplate::find($templateIdentifier);
            } else {
                $template = ContractTemplate::where('name', 'like', "%{$templateIdentifier}%")->first();
            }
        } else {
            // Default to Recurring Support Services Agreement
            $template = ContractTemplate::where('name', 'Recurring Support Services Agreement')->first();
        }

        if (!$template) {
            $this->error('Template not found. Available templates:');
            ContractTemplate::all()->each(function ($t) {
                $this->line("  {$t->id}: {$t->name}");
            });
            return 1;
        }

        $this->info("Parsing template: {$template->name}");
        $this->info("Company ID: {$companyId}");

        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        // Parse template into clauses
        try {
            DB::beginTransaction();

            $parsedClauses = $this->parseRecurringServicesTemplate($template, $companyId);

            $this->info("\nFound " . count($parsedClauses) . " clauses to create:");

            $createdClauses = [];
            foreach ($parsedClauses as $index => $clauseData) {
                $this->line(sprintf(
                    "  %d. %s (%s) - %d variables",
                    $index + 1,
                    $clauseData['name'],
                    $clauseData['category'],
                    count($clauseData['variables'])
                ));

                if (!$isDryRun) {
                    $clause = ContractClause::create($clauseData);
                    $createdClauses[] = $clause;
                    $this->info("    ✓ Created clause ID {$clause->id}");
                }
            }

            // Link clauses to template
            if (!$isDryRun && !empty($createdClauses)) {
                foreach ($createdClauses as $clause) {
                    $template->clauses()->attach($clause->id, [
                        'sort_order' => $clause->sort_order,
                        'is_required' => $clause->is_required,
                    ]);
                }
                $this->info("\n✓ Linked all clauses to template");
            }

            if ($isDryRun) {
                DB::rollBack();
                $this->warn("\nDRY RUN completed - no changes made");
            } else {
                DB::commit();
                $this->info("\n✓ Successfully parsed template into " . count($createdClauses) . " clauses");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error parsing template: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Parse the RECURRING SUPPORT SERVICES AGREEMENT template into clauses.
     */
    protected function parseRecurringServicesTemplate(ContractTemplate $template, int $companyId): array
    {
        $content = $template->template_content;

        // Define clause boundaries based on the actual template structure
        $clauseDefinitions = [
            [
                'name' => 'Contract Header',
                'slug' => 'contract-header',
                'category' => 'header',
                'clause_type' => 'required',
                'start' => 'RECURRING SUPPORT SERVICES AGREEMENT',
                'end' => 'RECITALS:',
                'sort_order' => 10,
                'is_required' => true,
            ],
            [
                'name' => 'Recitals',
                'slug' => 'recitals-msp',
                'category' => 'header',
                'clause_type' => 'required',
                'start' => 'RECITALS:',
                'end' => 'DEFINITIONS:',
                'sort_order' => 20,
                'is_required' => true,
            ],
            [
                'name' => 'Definitions',
                'slug' => 'definitions-comprehensive',
                'category' => 'definitions',
                'clause_type' => 'required',
                'start' => 'DEFINITIONS:',
                'end' => 'SCOPE OF SUPPORT SERVICES:',
                'sort_order' => self::DEFAULT_TIMEOUT,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Scope of Support Services',
                'slug' => 'scope-support-services',
                'category' => 'services',
                'clause_type' => 'conditional',
                'start' => 'SCOPE OF SUPPORT SERVICES:',
                'end' => 'CLIENT OBLIGATIONS AND RESPONSIBILITIES:',
                'sort_order' => 40,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Client Obligations and Responsibilities',
                'slug' => 'client-obligations',
                'category' => 'obligations',
                'clause_type' => 'required',
                'start' => 'CLIENT OBLIGATIONS AND RESPONSIBILITIES:',
                'end' => 'FEES AND PAYMENT TERMS:',
                'sort_order' => self::DEFAULT_PAGE_SIZE,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Fees and Payment Terms',
                'slug' => 'fees-payment-terms',
                'category' => 'financial',
                'clause_type' => 'required',
                'start' => 'FEES AND PAYMENT TERMS:',
                'end' => 'TERM AND TERMINATION:',
                'sort_order' => 60,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Term and Termination',
                'slug' => 'term-termination',
                'category' => 'legal',
                'clause_type' => 'required',
                'start' => 'TERM AND TERMINATION:',
                'end' => 'EXCLUSIONS FROM SUPPORT SERVICES:',
                'sort_order' => 70,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Exclusions from Support Services',
                'slug' => 'service-exclusions',
                'category' => 'exclusions',
                'clause_type' => 'required',
                'start' => 'EXCLUSIONS FROM SUPPORT SERVICES:',
                'end' => 'WARRANTIES AND DISCLAIMERS:',
                'sort_order' => 80,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Warranties and Disclaimers',
                'slug' => 'warranties-disclaimers',
                'category' => 'warranties',
                'clause_type' => 'required',
                'start' => 'WARRANTIES AND DISCLAIMERS:',
                'end' => 'LIMITATION OF LIABILITY:',
                'sort_order' => 90,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Limitation of Liability',
                'slug' => 'liability-limitation',
                'category' => 'warranties',
                'clause_type' => 'required',
                'start' => 'LIMITATION OF LIABILITY:',
                'end' => 'CONFIDENTIALITY:',
                'sort_order' => self::DEFAULT_BATCH_SIZE,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Confidentiality',
                'slug' => 'confidentiality',
                'category' => 'confidentiality',
                'clause_type' => 'required',
                'start' => 'CONFIDENTIALITY:',
                'end' => 'GOVERNING LAW AND DISPUTE RESOLUTION:',
                'sort_order' => 110,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Governing Law and Dispute Resolution',
                'slug' => 'governing-law-disputes',
                'category' => 'legal',
                'clause_type' => 'required',
                'start' => 'GOVERNING LAW AND DISPUTE RESOLUTION:',
                'end' => 'ENTIRE AGREEMENT:',
                'sort_order' => 120,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Administrative Clauses',
                'slug' => 'administrative-clauses',
                'category' => 'admin',
                'clause_type' => 'required',
                'start' => 'ENTIRE AGREEMENT:',
                'end' => 'IN WITNESS WHEREOF',
                'sort_order' => 130,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'numbered', 'level' => 1, 'format' => 'numeric']],
            ],
            [
                'name' => 'Signature Block',
                'slug' => 'signature-block',
                'category' => 'signature',
                'clause_type' => 'required',
                'start' => 'IN WITNESS WHEREOF',
                'end' => null, // Goes to end of document
                'sort_order' => 140,
                'is_required' => true,
                'metadata' => ['section_numbering' => ['type' => 'none']],
            ],
        ];

        $parsedClauses = [];

        foreach ($clauseDefinitions as $clauseDef) {
            $clauseContent = $this->extractClauseContent($content, $clauseDef['start'], $clauseDef['end']);

            if (!empty($clauseContent)) {
                $variables = $this->extractVariables($clauseContent);

                $parsedClauses[] = [
                    'company_id' => $companyId,
                    'name' => $clauseDef['name'],
                    'slug' => $clauseDef['slug'],
                    'category' => $clauseDef['category'],
                    'clause_type' => $clauseDef['clause_type'],
                    'content' => trim($clauseContent),
                    'variables' => $variables,
                    'sort_order' => $clauseDef['sort_order'],
                    'is_required' => $clauseDef['is_required'] ?? false,
                    'is_system' => true, // Mark as system clauses
                    'status' => 'active',
                    'version' => '1.0',
                    'applicable_contract_types' => ['managed_services'],
                    'metadata' => $clauseDef['metadata'] ?? null,
                    'description' => 'System clause parsed from ' . $template->name,
                ];
            }
        }

        return $parsedClauses;
    }

    /**
     * Extract clause content between start and end markers.
     */
    protected function extractClauseContent(string $content, string $start, ?string $end = null): string
    {
        $startPos = strpos($content, $start);
        if ($startPos === false) {
            return '';
        }

        $contentStart = $startPos;

        if ($end !== null) {
            $endPos = strpos($content, $end, $startPos + strlen($start));
            if ($endPos === false) {
                // If end marker not found, take rest of content
                $clauseContent = substr($content, $contentStart);
            } else {
                $clauseContent = substr($content, $contentStart, $endPos - $contentStart);
            }
        } else {
            // No end marker, take rest of content
            $clauseContent = substr($content, $contentStart);
        }

        return trim($clauseContent);
    }

    /**
     * Extract template variables from content.
     */
    protected function extractVariables(string $content): array
    {
        $pattern = '/\{\{([^}#\/]+)\}\}/';
        preg_match_all($pattern, $content, $matches);

        $variables = [];
        foreach ($matches[1] ?? [] as $match) {
            $variable = trim($match);

            // Skip conditional directives
            if (strpos($variable, '#if') === 0 || strpos($variable, '/if') === 0) {
                continue;
            }

            // Extract base variable name from formatted variables
            if (strpos($variable, '|') !== false) {
                $variable = trim(explode('|', $variable)[0]);
            }

            $variables[] = $variable;
        }

        return array_unique($variables);
    }
}
