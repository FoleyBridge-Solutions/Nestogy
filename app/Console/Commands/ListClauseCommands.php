<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ListClauseCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nestogy:clause-help';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show available contract clause management commands and examples';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Contract Clause Management Commands');
        $this->newLine();

        $this->line('<fg=yellow>Available Commands:</>');
        $this->newLine();

        $commands = [
            [
                'command' => 'nestogy:manage-clauses list',
                'description' => 'List all clauses for a company',
                'example' => 'php artisan nestogy:manage-clauses list --company-id=1 --category=sla'
            ],
            [
                'command' => 'nestogy:manage-clauses validate',
                'description' => 'Validate template clause dependencies and completeness',
                'example' => 'php artisan nestogy:manage-clauses validate --template="Recurring Support Services Agreement"'
            ],
            [
                'command' => 'nestogy:manage-clauses create',
                'description' => 'Create a new contract clause',
                'example' => 'php artisan nestogy:manage-clauses create --company-id=1'
            ],
            [
                'command' => 'nestogy:parse-template-clauses',
                'description' => 'Parse existing template into modular clauses',
                'example' => 'php artisan nestogy:parse-template-clauses "Recurring Support Services Agreement"'
            ],
        ];

        foreach ($commands as $cmd) {
            $this->line("<fg=green>{$cmd['command']}</>");
            $this->line("  {$cmd['description']}");
            $this->line("  <fg=cyan>Example:</> {$cmd['example']}");
            $this->newLine();
        }

        $this->info('📋 Available Clause Categories:');
        $categories = \App\Models\ContractClause::getAvailableCategories();
        foreach ($categories as $key => $description) {
            $this->line("  <fg=yellow>{$key}:</> {$description}");
        }

        $this->newLine();
        $this->info('📝 Template Variables Usage:');
        $this->line('  Use {{variable_name}} in clause content for dynamic substitution');
        $this->line('  Common variables: {{client_name}}, {{service_provider_name}}, {{contract_value}}');
        $this->line('  Conditional blocks: {{#if variable}}content{{/if}}');

        $this->newLine();
        $this->info('🔍 For more details on any command, use: php artisan help [command-name]');

        return 0;
    }
}
