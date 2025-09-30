<?php

namespace App\Console\Commands;

use App\Domains\Core\Services\ConfigurationValidationService;
use Illuminate\Console\Command;

class ValidateConfiguration extends Command
{
    // Class constants to reduce duplication
    private const VALIDATION_PASS = 'pass';

    private const VALIDATION_FAIL = 'fail';

    private const MSG_VALIDATE_START = 'Validating configuration...';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:validate
                            {--show-warnings : Show configuration warnings}
                            {--check-missing : Show only missing required configurations}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate application configuration and check for missing or invalid settings';

    /**
     * Execute the console command.
     */
    public function handle(ConfigurationValidationService $validator): int
    {
        $this->info('Validating Nestogy ERP Configuration...');
        $this->newLine();

        // Check for missing configs only
        if ($this->option('check-missing')) {
            return $this->checkMissingConfigs($validator);
        }

        // Run full validation
        $isValid = $validator->validate();
        $report = $validator->getReport();

        // Output as JSON if requested
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));

            return $isValid ? 0 : 1;
        }

        // Display results
        $this->displayResults($report);

        return $isValid ? 0 : 1;
    }

    /**
     * Check and display missing configurations
     */
    protected function checkMissingConfigs(ConfigurationValidationService $validator): int
    {
        $missing = $validator->getMissingConfigs();

        if (empty($missing)) {
            $this->info('✓ All required configurations are present');

            return 0;
        }

        $this->error('Missing required configurations:');
        foreach ($missing as $config) {
            $this->line("  - {$config}");
        }

        return 1;
    }

    /**
     * Display validation results
     */
    protected function displayResults(array $report): void
    {
        // Display errors
        if (! empty($report['errors'])) {
            $this->error('Configuration Errors Found:');
            foreach ($report['errors'] as $error) {
                $this->line("  ✗ {$error}", 'error');
            }
            $this->newLine();
        }

        // Display warnings if requested
        if ($this->option('show-warnings') && ! empty($report['warnings'])) {
            $this->warn('Configuration Warnings:');
            foreach ($report['warnings'] as $warning) {
                $this->line("  ⚠ {$warning}", 'warn');
            }
            $this->newLine();
        }

        // Display summary
        $this->displaySummary($report);
    }

    /**
     * Display validation summary
     */
    protected function displaySummary(array $report): void
    {
        $this->info('Validation Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Errors', $report['error_count']],
                ['Warnings', $report['warning_count']],
            ]
        );

        if ($report['valid']) {
            $this->newLine();
            $this->info('✓ Configuration validation passed!');

            if ($report['warning_count'] > 0 && ! $this->option('show-warnings')) {
                $this->line('  (Use --show-warnings to see warnings)');
            }
        } else {
            $this->newLine();
            $this->error('✗ Configuration validation failed!');
            $this->line('  Please fix the errors above before proceeding.');
        }
    }
}
