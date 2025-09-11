<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Company;
use App\Domains\Ticket\Models\SLA;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating clients for each company...');
        
        // Skip the root company (ID 1)
        $companies = Company::where('id', '>', 1)->get();
        
        foreach ($companies as $company) {
            $this->command->info("  Creating clients for {$company->name}...");
            
            // Get default SLA for this company
            $defaultSla = SLA::where('company_id', $company->id)->first();
            
            // Create 8-12 clients per company
            $numClients = rand(8, 12);
            
            Client::factory()
                ->count($numClients)
                ->forCompany($company)
                ->state(function (array $attributes) use ($defaultSla) {
                    return [
                        'sla_id' => $defaultSla?->id,
                        'hourly_rate' => fake()->randomElement([125, 150, 175, 200, 225]),
                        'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'suspended']),
                    ];
                })
                ->create();
            
            $this->command->info("    ✓ Created {$numClients} clients");
        }
        
        $this->command->info('Clients created successfully.');
    }
}