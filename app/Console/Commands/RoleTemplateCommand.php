<?php

namespace App\Console\Commands;

use App\Domains\Company\Models\Company;
use App\Domains\Security\Services\TenantRoleService;
use Illuminate\Console\Command;

class RoleTemplateCommand extends Command
{
    protected $signature = 'roles:sync-templates 
                          {--company= : Sync roles for specific company ID}
                          {--all : Sync roles for all companies}
                          {--validate : Validate that template permissions exist}
                          {--show : Show role template configuration}';

    protected $description = 'Manage role templates for companies';

    public function handle()
    {
        $service = app(TenantRoleService::class);

        if ($this->option('show')) {
            return $this->showTemplates($service);
        }

        if ($this->option('validate')) {
            return $this->validateTemplates($service);
        }

        if ($this->option('all')) {
            return $this->syncAllCompanies($service);
        }

        if ($companyId = $this->option('company')) {
            return $this->syncCompany($service, $companyId);
        }

        $this->error('Please specify an option: --show, --validate, --all, or --company=ID');
        return Command::FAILURE;
    }

    private function showTemplates(TenantRoleService $service): int
    {
        $this->info('📋 Role Template Configuration:');
        $this->newLine();

        $templates = $service->getTemplate();

        foreach ($templates as $roleName => $config) {
            $this->line("  <fg=cyan>{$config['title']}</> ({$roleName})");
            $this->line("  <fg=gray>{$config['description']}</>");
            $this->line("  <fg=yellow>Permissions:</> " . count($config['permissions']));
            $this->newLine();
        }

        return Command::SUCCESS;
    }

    private function validateTemplates(TenantRoleService $service): int
    {
        $this->info('🔍 Validating role templates...');
        $this->newLine();

        $missing = $service->validateTemplates();

        if (empty($missing)) {
            $this->info('✅ All template permissions exist in database!');
            return Command::SUCCESS;
        }

        $this->warn('⚠️  Missing permissions found:');
        $this->newLine();

        foreach ($missing as $roleName => $permissions) {
            $this->line("  <fg=red>{$roleName}:</>");
            foreach ($permissions as $permission) {
                $this->line("    - {$permission}");
            }
            $this->newLine();
        }

        $this->newLine();
        $this->info('💡 Run: php artisan permissions:discover --sync');
        $this->info('   to auto-discover missing permissions from policies');

        return Command::FAILURE;
    }

    private function syncCompany(TenantRoleService $service, int $companyId): int
    {
        $company = Company::find($companyId);

        if (!$company) {
            $this->error("Company {$companyId} not found");
            return Command::FAILURE;
        }

        $this->info("Syncing role templates for: {$company->name}");
        $this->newLine();

        $result = $service->syncRolesToTemplates($companyId);

        if ($result['total'] === 0) {
            $this->info('✅ All roles are up to date!');
        } else {
            $this->table(
                ['Role', 'Permissions Added'],
                array_map(fn($r) => [$r['name'], $r['added']], $result['updated'])
            );
            $this->info("✅ Updated {$result['total']} roles");
        }

        return Command::SUCCESS;
    }

    private function syncAllCompanies(TenantRoleService $service): int
    {
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->warn('No companies found');
            return Command::SUCCESS;
        }

        $this->info("Syncing role templates for {$companies->count()} companies...");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($companies->count());
        $progressBar->start();

        $totalUpdated = 0;
        $errors = 0;

        foreach ($companies as $company) {
            try {
                $result = $service->syncRolesToTemplates($company->id);
                $totalUpdated += $result['total'];
            } catch (\Exception $e) {
                $errors++;
                $this->error("\nError syncing company {$company->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Companies Processed', $companies->count()],
                ['Roles Updated', $totalUpdated],
                ['Errors', $errors],
            ]
        );

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
