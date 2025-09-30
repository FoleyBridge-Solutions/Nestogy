<?php

namespace Database\Seeders\Dev;

use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating leads with various stages...');

        $companies = Company::where('id', '>', 1)->get();
        $totalLeads = 0;

        foreach ($companies as $company) {
            $this->command->info("  Creating leads for {$company->name}...");

            $users = User::where('company_id', $company->id)->get();

            if ($users->isEmpty()) {
                continue;
            }

            // Create 30-50 leads per company
            $numLeads = rand(30, 50);

            for ($i = 0; $i < $numLeads; $i++) {
                $createdDate = fake()->dateTimeBetween('-6 months', 'now');
                $daysOld = Carbon::parse($createdDate)->diffInDays(now());

                // Status based on age and progression
                if ($daysOld > 90) {
                    $status = fake()->randomElement(['converted', 'lost', 'lost', 'inactive']);
                } elseif ($daysOld > 30) {
                    $status = fake()->randomElement(['qualified', 'proposal', 'negotiation', 'lost']);
                } else {
                    $status = fake()->randomElement(['new', 'contacted', 'qualified', 'proposal']);
                }

                // Source distribution
                $source = fake()->randomElement([
                    'website', 'website', 'website',  // Most common
                    'referral', 'referral',
                    'cold_call',
                    'email_campaign',
                    'social_media',
                    'trade_show',
                    'partner',
                ]);

                // Industry varies
                $industry = fake()->randomElement([
                    'Healthcare', 'Finance', 'Manufacturing', 'Retail', 'Education',
                    'Technology', 'Legal', 'Real Estate', 'Non-Profit', 'Government',
                    'Construction', 'Hospitality', 'Transportation', 'Energy',
                ]);

                // Company size
                $companySize = fake()->randomElement([
                    '1-10', '11-50', '51-200', '201-500', '500+',
                ]);

                // Estimated value based on company size
                $estimatedValue = match ($companySize) {
                    '500+' => fake()->numberBetween(50000, 200000),
                    '201-500' => fake()->numberBetween(20000, 50000),
                    '51-200' => fake()->numberBetween(10000, 30000),
                    '11-50' => fake()->numberBetween(5000, 15000),
                    '1-10' => fake()->numberBetween(1000, 5000),
                };

                Lead::create([
                    'company_id' => $company->id,
                    'assigned_to' => $users->random()->id,
                    'company_name' => fake()->company(),
                    'contact_name' => fake()->name(),
                    'email' => fake()->companyEmail(),
                    'phone' => fake()->phoneNumber(),
                    'website' => fake()->optional(0.7)->url(),
                    'industry' => $industry,
                    'company_size' => $companySize,
                    'status' => $status,
                    'source' => $source,
                    'estimated_value' => $estimatedValue,
                    'probability' => match ($status) {
                        'converted' => 100,
                        'lost' => 0,
                        'inactive' => 0,
                        'negotiation' => 75,
                        'proposal' => 50,
                        'qualified' => 25,
                        'contacted' => 10,
                        'new' => 5,
                    },
                    'notes' => fake()->optional(0.6)->paragraph(),
                    'next_follow_up' => in_array($status, ['new', 'contacted', 'qualified', 'proposal']) ?
                        fake()->dateTimeBetween('now', '+2 weeks') : null,
                    'converted_at' => $status === 'converted' ?
                        fake()->dateTimeBetween($createdDate, 'now') : null,
                    'lost_reason' => $status === 'lost' ?
                        fake()->randomElement([
                            'Price too high',
                            'Went with competitor',
                            'No longer needed',
                            'Budget constraints',
                            'Timing not right',
                            'Lost contact',
                        ]) : null,
                    'created_at' => $createdDate,
                    'updated_at' => fake()->dateTimeBetween($createdDate, 'now'),
                ]);

                $totalLeads++;
            }

            $this->command->info("    ✓ Created {$numLeads} leads");
        }

        $this->command->info("Created {$totalLeads} leads total.");
    }
}
