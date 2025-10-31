<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class DomainsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Domains Seeder...');

        // Note: If there's a Domain model, implement it here
        // Check if App\Domains\Client\Models\Domain or similar exists
        
        $modelPath = app_path('Domains/Client/Models/Domain.php');
        
        if (!file_exists($modelPath)) {
            $this->command->warn('Domain model not found. Skipping.');
            return;
        }

        $domainClass = 'App\\Domains\\Client\\Models\\Domain';
        
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $clients = Client::where('company_id', $company->id)->get();
            
            if ($clients->isEmpty()) {
                continue;
            }

            // Create 1-3 domains per client
            foreach ($clients as $client) {
                $domainClass::factory()
                    ->count(rand(1, 3))
                    ->for($company)
                    ->for($client)
                    ->create();
            }
        }

        $this->command->info('Domains Seeder completed!');
    }
}
