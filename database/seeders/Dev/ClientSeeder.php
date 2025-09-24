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
        $this->command->info('Creating enhanced client dataset...');
        
        // Skip the root company (ID 1)
        $companies = Company::where('id', '>', 1)->get();
        
        foreach ($companies as $company) {
            $companySize = $company->size ?? 'medium';
            $this->command->info("  Creating clients for {$company->name} ({$companySize} company)...");
            
            // Get default SLA for this company
            $defaultSla = SLA::where('company_id', $company->id)->first();
            
            // Determine number of clients based on company size
            switch($companySize) {
                case 'solo':
                    $numClients = rand(5, 15);  // Solo operators have fewer clients
                    break;
                case 'small':
                    $numClients = rand(20, 40);  // Small shops manage 20-40 clients
                    break;
                case 'medium':
                    $numClients = rand(50, 100); // Medium MSPs manage 50-100 clients
                    break;
                case 'large':
                    $numClients = rand(100, 200); // Large MSPs manage 100-200 clients
                    break;
                case 'enterprise':
                    $numClients = rand(200, 500); // Enterprise MSPs manage hundreds of clients
                    break;
                default:
                    $numClients = rand(30, 50);
                    break;
            }
            
            // Create a mix of client sizes and statuses
            $clientTypes = [
                ['size' => 'enterprise', 'rate_range' => [200, 300], 'count' => (int)($numClients * 0.15)],
                ['size' => 'medium', 'rate_range' => [150, 200], 'count' => (int)($numClients * 0.35)],
                ['size' => 'small', 'rate_range' => [100, 150], 'count' => (int)($numClients * 0.50)],
            ];
            
            $totalCreated = 0;
            foreach ($clientTypes as $type) {
                for ($i = 0; $i < $type['count']; $i++) {
                    $createdDate = fake()->dateTimeBetween('-2 years', 'now');
                    
                    // Older clients more likely to be active
                    $daysSinceCreation = now()->diffInDays($createdDate);
                    if ($daysSinceCreation > 365) {
                        $status = fake()->randomElement(['active', 'active', 'active', 'active', 'inactive']);
                    } elseif ($daysSinceCreation > 90) {
                        $status = fake()->randomElement(['active', 'active', 'active', 'suspended', 'inactive']);
                    } else {
                        $status = fake()->randomElement(['active', 'active', 'suspended']);
                    }
                    
                    Client::factory()
                        ->forCompany($company)
                        ->state([
                            'sla_id' => $defaultSla?->id,
                            'hourly_rate' => fake()->numberBetween($type['rate_range'][0], $type['rate_range'][1]),
                            'status' => $status,
                            'created_at' => $createdDate,
                            'updated_at' => fake()->dateTimeBetween($createdDate, 'now'),
                            'notes' => fake()->optional(0.3)->paragraph(),
                            'industry' => fake()->randomElement([
                                'Healthcare', 'Finance', 'Manufacturing', 'Retail', 'Education',
                                'Technology', 'Legal', 'Real Estate', 'Non-Profit', 'Government',
                                'Construction', 'Hospitality', 'Transportation', 'Energy'
                            ]),
                            'employee_count' => match($type['size']) {
                                'enterprise' => fake()->numberBetween(500, 5000),
                                'medium' => fake()->numberBetween(50, 500),
                                'small' => fake()->numberBetween(1, 50),
                            },
                        ])
                        ->create();
                    
                    $totalCreated++;
                }
            }
            
            $this->command->info("    ✓ Created {$totalCreated} clients with varied sizes and histories");
        }
        
        $this->command->info('Enhanced client dataset created successfully.');
    }
}