<?php

namespace App\Console\Commands;

use App\Models\ContractClause;
use App\Models\ContractTemplate;
use App\Services\ContractClauseService;
use Illuminate\Console\Command;

class ManageContractClauses extends Command
{

    // Class constants to reduce duplication
    private const ACTION_LIST = 'list';
    private const ACTION_ADD = 'add';
    private const ACTION_REMOVE = 'remove';
    private const MSG_MANAGE_START = 'Managing contract clauses...';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nestogy:manage-clauses
                            {action : Action to perform (list, validate, create, update)}
                            {--company-id=1 : Company ID to work with}
                            {--template= : Template name or ID for validation}
                            {--category= : Clause category filter}
                            {--name= : Clause name for create/update}
                            {--content= : Clause content for create/update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage contract clauses - list, validate, create, and update clauses';

    protected ContractClauseService $clauseService;

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
        $action = $this->argument('action');
        $companyId = $this->option('company-id');

        switch ($action) {
            case 'list':
                return $this->listClauses($companyId);
            case 'validate':
                return $this->validateTemplate();
            case 'create':
                return $this->createClause($companyId);
            case 'update':
                return $this->updateClause();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: list, validate, create, update');
                return 1;
        }
    }

    protected function listClauses(int $companyId): int
    {
        $category = $this->option('category');

        $query = ContractClause::where('company_id', $companyId);

        if ($category) {
            $query->where('category', $category);
        }

        $clauses = $query->orderBy('category')->orderBy('sort_order')->get();

        if ($clauses->isEmpty()) {
            $this->info('No clauses found.');
            return 0;
        }

        $this->info("Contract Clauses for Company ID {$companyId}:");
        $this->newLine();

        $currentCategory = null;
        foreach ($clauses as $clause) {
            if ($currentCategory !== $clause->category) {
                $currentCategory = $clause->category;
                $this->line("<fg=yellow>Category: {$currentCategory}</>");
            }

            $status = $clause->status === 'active' ? '✓' : '✗';
            $type = $clause->clause_type;
            $required = $clause->is_required ? '[REQ]' : '[OPT]';

            $this->line("  {$status} {$clause->name} {$required} ({$type})");

            if ($clause->hasDependencies()) {
                $deps = implode(', ', $clause->getDependencies());
                $this->line("    <fg=cyan>Dependencies: {$deps}</>");
            }

            if ($clause->hasConflicts()) {
                $conflicts = implode(', ', $clause->getConflicts());
                $this->line("    <fg=red>Conflicts: {$conflicts}</>");
            }
        }

        return 0;
    }

    protected function validateTemplate(): int
    {
        $templateIdentifier = $this->option('template');

        if (!$templateIdentifier) {
            $this->error('Template name or ID required for validation');
            return 1;
        }

        // Find template
        if (is_numeric($templateIdentifier)) {
            $template = ContractTemplate::find($templateIdentifier);
        } else {
            $template = ContractTemplate::where('name', 'like', "%{$templateIdentifier}%")->first();
        }

        if (!$template) {
            $this->error('Template not found');
            return 1;
        }

        $this->info("Validating template: {$template->name}");
        $this->newLine();

        // Check dependencies
        $dependencyErrors = $this->clauseService->validateClauseDependencies($template);

        if (!empty($dependencyErrors)) {
            $this->error('Dependency Validation Errors:');
            foreach ($dependencyErrors as $error) {
                $this->line("  ✗ {$error}");
            }
        } else {
            $this->info('✓ All clause dependencies satisfied');
        }

        // Check missing required clauses
        $missingCategories = $this->clauseService->getMissingRequiredClauses($template);

        if (!empty($missingCategories)) {
            $this->warn('Missing Required Categories:');
            foreach ($missingCategories as $category) {
                $this->line("  ! Missing: {$category}");
            }
        } else {
            $this->info('✓ All required clause categories present');
        }

        // Show clause count by category
        $this->newLine();
        $this->info('Clause Distribution:');
        $categories = $template->clauses->groupBy('category');
        foreach ($categories as $category => $clauses) {
            $this->line("  {$category}: {$clauses->count()} clauses");
        }

        return 0;
    }

    protected function createClause(int $companyId): int
    {
        $name = $this->option('name');
        $content = $this->option('content');

        if (!$name) {
            $name = $this->ask('Clause name');
        }

        if (!$content) {
            $content = $this->ask('Clause content (use {{variable}} for template variables)');
        }

        $categories = ContractClause::getAvailableCategories();
        $categoryKeys = array_keys($categories);

        $category = $this->choice('Select category', $categoryKeys);
        $type = $this->choice('Clause type', ['required', 'conditional', 'optional']);

        $clause = ContractClause::create([
            'company_id' => $companyId,
            'name' => $name,
            'category' => $category,
            'clause_type' => $type,
            'content' => $content,
            'status' => 'active',
            'version' => '1.0',
            'is_system' => false,
            'created_by' => 1, // Default user
        ]);

        $this->info("✓ Created clause: {$clause->name} (ID: {$clause->id})");

        return 0;
    }

    protected function updateClause(): int
    {
        $this->error('Update functionality not yet implemented');
        $this->info('Use the list command to see existing clauses');
        return 1;
    }
}
