<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use App\Domains\Contract\Models\Contract;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Contract Seeder...');
        
        // Skip root company
        $companies = Company::where('id', '>', 1)->get();
        
        foreach ($companies as $company) {
            $this->command->info("Creating contracts for company: {$company->name}");
            
            $clients = Client::where('company_id', $company->id)
                            ->where('status', 'active')
                            ->get();
            
            $users = User::where('company_id', $company->id)->get();
            
            if ($users->isEmpty()) {
                continue;
            }
            
            foreach ($clients as $client) {
                // Most active clients should have at least one contract
                if (fake()->boolean(80)) {
                    Contract::factory()
                        ->forClient($client)
                        ->active()
                        ->state([
                            'created_by' => $users->random()->id,
                        ])
                        ->create();
                    
                    // Some clients have multiple contracts
                    if (fake()->boolean(30)) {
                        Contract::factory()
                            ->forClient($client)
                            ->state([
                                'created_by' => $users->random()->id,
                                'status' => fake()->randomElement(['draft', 'expired', 'terminated']),
                            ])
                            ->create();
                    }
                }
            }
            
            $this->command->info("Completed contracts for company: {$company->name}");
        }
        
        $this->command->info('Contract Seeder completed!');
    }
}