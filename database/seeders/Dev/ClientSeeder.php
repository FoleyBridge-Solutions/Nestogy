<?php

namespace Database\Seeders\Dev;

use App\Domains\Ticket\Models\SLA;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Seeder;

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

            // Determine number of clients based on company size - more realistic ratios
            switch ($companySize) {
                case 'solo':
                    $numClients = rand(3, 8);  // Solo operators have very few clients
                    break;
                case 'small':
                    $numClients = rand(10, 20);  // Small shops manage 10-20 clients
                    break;
                case 'medium':
                    $numClients = rand(30, 60); // Mid-market MSPs manage 30-60 clients
                    break;
                case 'medium-large':
                    $numClients = rand(50, 80); // Larger mid-market manage 50-80 clients
                    break;
                case 'large':
                    $numClients = rand(70, 100); // Upper mid-market manage 70-100 clients
                    break;
                default:
                    $numClients = rand(25, 45);
                    break;
            }

            // Create a mix of client sizes and statuses
            $clientTypes = [
                ['size' => 'enterprise', 'rate_range' => [200, 300], 'count' => (int) ($numClients * 0.15)],
                ['size' => 'medium', 'rate_range' => [150, 200], 'count' => (int) ($numClients * 0.35)],
                ['size' => 'small', 'rate_range' => [100, 150], 'count' => (int) ($numClients * 0.50)],
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
                                'Construction', 'Hospitality', 'Transportation', 'Energy',
                            ]),
                            'employee_count' => match ($type['size']) {
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
