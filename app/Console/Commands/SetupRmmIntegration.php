<?php

namespace App\Console\Commands;

use App\Domains\Integration\Models\RmmIntegration;
use App\Models\Company;
use Illuminate\Console\Command;

class SetupRmmIntegration extends Command
{
    // Class constants to reduce duplication
    private const PROVIDER_CONNECTWISE = 'connectwise';

    private const PROVIDER_DATTO = 'datto';

    private const PROVIDER_NINJA = 'ninja';

    private const MSG_SETUP_START = 'Setting up RMM integration...';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rmm:setup
                            {company_id=1 : The company ID to set up RMM integration for}
                            {--type=TRMM : RMM type (TRMM for TacticalRMM)}
                            {--name= : Name for the integration}
                            {--api-url= : API URL for the RMM system}
                            {--api-key= : API key for authentication}
                            {--test : Test the connection after setup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up RMM integration for a company with encrypted credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->argument('company_id');
        $rmmType = $this->option('type');

        $company = $this->validateCompany($companyId);
        if (! $company) {
            return 1;
        }

        $this->info("Setting up RMM integration for company: {$company->name}");

        $existingIntegration = $this->findExistingIntegration($companyId, $rmmType);
        if ($existingIntegration && ! $this->shouldUpdateExisting($rmmType)) {
            return 0;
        }

        $credentials = $this->gatherCredentials();
        if (! $this->validateCredentials($credentials)) {
            return 1;
        }

        try {
            $integration = $this->saveIntegration($existingIntegration, $companyId, $rmmType, $credentials);
            $this->displayIntegrationDetails($integration, $company, $companyId, $credentials['apiUrl']);

            if ($this->shouldTestConnection() && ! $this->testConnection($integration)) {
                return 1;
            }

            $this->displaySuccess($integration);

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to set up integration: '.$e->getMessage());

            return 1;
        }
    }

    private function validateCompany(int $companyId): ?Company
    {
        $company = Company::find($companyId);
        if (! $company) {
            $this->error("Company with ID {$companyId} not found.");
        }

        return $company;
    }

    private function findExistingIntegration(int $companyId, string $rmmType): ?RmmIntegration
    {
        return RmmIntegration::where('company_id', $companyId)
            ->where('rmm_type', $rmmType)
            ->first();
    }

    private function shouldUpdateExisting(string $rmmType): bool
    {
        if (! $this->confirm("An integration of type {$rmmType} already exists for this company. Do you want to update it?")) {
            $this->info('Setup cancelled.');

            return false;
        }

        return true;
    }

    private function gatherCredentials(): array
    {
        return [
            'name' => $this->option('name') ?: $this->ask('Enter a name for this integration', 'Tactical RMM Integration'),
            'apiUrl' => $this->option('api-url') ?: $this->ask('Enter the API URL for your RMM system'),
            'apiKey' => $this->option('api-key') ?: $this->secret('Enter the API key for authentication'),
        ];
    }

    private function validateCredentials(array $credentials): bool
    {
        if (! $credentials['apiUrl'] || ! $credentials['apiKey']) {
            $this->error('API URL and API key are required.');

            return false;
        }

        if (! filter_var($credentials['apiUrl'], FILTER_VALIDATE_URL)) {
            $this->error('Invalid API URL format.');

            return false;
        }

        return true;
    }

    private function saveIntegration(?RmmIntegration $existing, int $companyId, string $rmmType, array $credentials): RmmIntegration
    {
        if ($existing) {
            $existing->name = $credentials['name'];
            $existing->api_url = $credentials['apiUrl'];
            $existing->api_key = $credentials['apiKey'];
            $existing->is_active = true;
            $existing->save();

            $this->info('Integration updated successfully!');

            return $existing;
        }

        $integration = RmmIntegration::createWithCredentials([
            'company_id' => $companyId,
            'rmm_type' => $rmmType,
            'name' => $credentials['name'],
            'api_url' => $credentials['apiUrl'],
            'api_key' => $credentials['apiKey'],
            'is_active' => true,
        ]);

        $this->info('Integration created successfully!');

        return $integration;
    }

    private function displayIntegrationDetails(RmmIntegration $integration, Company $company, int $companyId, string $apiUrl): void
    {
        $this->line('');
        $this->line('<info>Integration Details:</info>');
        $this->line("ID: {$integration->id}");
        $this->line("Company: {$company->name} (ID: {$companyId})");
        $this->line("Type: {$integration->getRmmTypeLabel()}");
        $this->line("Name: {$integration->name}");
        $this->line("API URL: {$apiUrl}");
        $this->line('Status: '.($integration->is_active ? 'Active' : 'Inactive'));
    }

    private function shouldTestConnection(): bool
    {
        return $this->option('test') || $this->confirm('Do you want to test the connection?', true);
    }

    private function testConnection(RmmIntegration $integration): bool
    {
        $this->line('');
        $this->info('Testing connection...');

        $connectionTest = $integration->testConnection();

        if ($connectionTest['success']) {
            $this->line('<info>✓ Connection test successful!</info>');
            if (isset($connectionTest['data']['version'])) {
                $this->line("Server version: {$connectionTest['data']['version']}");
            }

            return true;
        }

        $this->line('<error>✗ Connection test failed!</error>');
        $this->line("Error: {$connectionTest['message']}");

        return false;
    }

    private function displaySuccess(RmmIntegration $integration): void
    {
        $this->line('');
        $this->line('<info>Setup completed successfully!</info>');

        $this->line('');
        $this->line('<comment>Next steps:</comment>');
        $this->line('1. Run agent sync: php artisan rmm:sync-agents '.$integration->id);
        $this->line('2. Run alert sync: php artisan rmm:sync-alerts '.$integration->id);
        $this->line('3. Set up scheduled jobs for automatic synchronization');
    }
}
