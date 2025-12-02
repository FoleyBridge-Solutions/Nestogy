<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Lead\Models\Lead;
use App\Domains\Product\Models\Product;
use App\Domains\Financial\Models\Quote;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\Category;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating quotes...');

        $companies = Company::where('id', '>', 1)->get();
        $totalQuotes = 0;

        foreach ($companies as $company) {
            $this->command->info("  Creating quotes for {$company->name}...");

            $clients = Client::where('company_id', $company->id)->get();
            $leads = Lead::where('company_id', $company->id)
                ->whereIn('status', ['qualified', 'proposal', 'negotiation'])
                ->get();
            $products = Product::where('company_id', $company->id)->get();
            $users = User::where('company_id', $company->id)->get();
            $category = Category::where('company_id', $company->id)->first();

            if ($users->isEmpty() || $products->isEmpty() || !$category) {
                continue;
            }

            // Create quotes for existing clients (renewals, upgrades)
            foreach ($clients->take(20) as $client) {
                $createdDate = fake()->dateTimeBetween('-3 months', 'now');
                $validUntil = Carbon::parse($createdDate)->addDays(30);

                // Status based on age
                if ($validUntil->isPast()) {
                    $status = fake()->randomElement(['approved', 'sent', 'draft']);
                } else {
                    $status = fake()->randomElement(['draft', 'sent', 'approved']);
                }

                $quote = Quote::create([
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'created_by' => $users->random()->id,
                    'prefix' => 'Q',
                    'number' => $totalQuotes + 1,
                    'scope' => fake()->randomElement([
                        'Service Upgrade',
                        'Annual Renewal',
                        'Additional Services',
                        'Hardware Refresh',
                        'Security Enhancement',
                        'Cloud Migration',
                    ]),
                    'status' => $status,
                    'date' => Carbon::parse($createdDate)->format('Y-m-d'),
                    'expire' => $validUntil->format('Y-m-d'),
                    'discount_amount' => fake()->optional(0.3)->numberBetween(50, 500),
                    'amount' => fake()->numberBetween(1000, 25000),
                    'currency_code' => 'USD',
                    'note' => fake()->optional(0.5)->paragraph(),
                    'category_id' => $category->id,
                    'sent_at' => $status === 'sent' ? fake()->dateTimeBetween($createdDate, 'now') : null,
                    'created_at' => $createdDate,
                    'updated_at' => fake()->dateTimeBetween($createdDate, 'now'),
                ]);

                $totalQuotes++;
            }

            // Create quotes for leads
            foreach ($leads as $lead) {
                $createdDate = fake()->dateTimeBetween('-2 months', 'now');
                $validUntil = Carbon::parse($createdDate)->addDays(30);

                $status = fake()->randomElement(['draft', 'sent', 'approved']);

                $quote = Quote::create([
                    'company_id' => $company->id,
                    'client_id' => $lead->client_id ?? Client::where('company_id', $company->id)->first()->id,
                    'created_by' => $users->random()->id,
                    'prefix' => 'Q',
                    'number' => $totalQuotes + 1,
                    'scope' => fake()->randomElement([
                        'Initial Service Proposal',
                        'Managed IT Services',
                        'Complete IT Solution',
                        'Starter Package',
                        'Enterprise Solution',
                    ]),
                    'status' => $status,
                    'date' => Carbon::parse($createdDate)->format('Y-m-d'),
                    'expire' => $validUntil->format('Y-m-d'),
                    'discount_amount' => fake()->optional(0.4)->numberBetween(100, 1000),
                    'amount' => fake()->numberBetween(2000, 50000),
                    'currency_code' => 'USD',
                    'note' => fake()->optional(0.6)->paragraph(),
                    'category_id' => $category->id,
                    'sent_at' => $status === 'sent' ? fake()->dateTimeBetween($createdDate, 'now') : null,
                    'created_at' => $createdDate,
                    'updated_at' => fake()->dateTimeBetween($createdDate, 'now'),
                ]);

                $totalQuotes++;
            }

            $this->command->info("  Created quotes for {$company->name}");
        }

        $this->command->info("Created {$totalQuotes} quotes successfully.");
    }
}
