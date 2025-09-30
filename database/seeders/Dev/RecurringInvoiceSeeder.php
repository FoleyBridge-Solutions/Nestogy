<?php

namespace Database\Seeders\Dev;

use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\RecurringInvoice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RecurringInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating recurring invoices...');

        // Get active clients
        $clients = Client::where('status', 'active')->get();
        $totalRecurring = 0;

        foreach ($clients as $client) {
            // 60% chance of having recurring services
            if (! fake()->boolean(60)) {
                continue;
            }

            $products = Product::where('company_id', $client->company_id)
                ->whereNotNull('recurring_type')
                ->get();

            if ($products->isEmpty()) {
                continue;
            }

            $categories = Category::where('company_id', $client->company_id)
                ->where('type', 'income')
                ->get();

            // Number of recurring invoices per client (usually 1-2)
            $numRecurring = fake()->randomElement([1, 1, 1, 2]);

            for ($i = 0; $i < $numRecurring; $i++) {
                $startDate = fake()->dateTimeBetween('-1 year', '-1 month');
                $frequency = fake()->randomElement(['monthly', 'monthly', 'quarterly', 'annually']);

                // Next invoice date based on frequency
                $nextDate = match ($frequency) {
                    'monthly' => Carbon::parse($startDate)->addMonth()->startOfMonth(),
                    'quarterly' => Carbon::parse($startDate)->addQuarter()->startOfMonth(),
                    'annually' => Carbon::parse($startDate)->addYear()->startOfMonth(),
                };

                // Ensure next date is in the future
                while ($nextDate->isPast()) {
                    $nextDate = match ($frequency) {
                        'monthly' => $nextDate->addMonth(),
                        'quarterly' => $nextDate->addQuarter(),
                        'annually' => $nextDate->addYear(),
                    };
                }

                // Calculate amount based on selected products
                $selectedProducts = $products->random(rand(1, min(5, $products->count())));
                $amount = 0;
                $items = [];

                foreach ($selectedProducts as $product) {
                    $quantity = fake()->numberBetween(1, 10);
                    $price = $product->price;
                    $amount += $quantity * $price;

                    $items[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'quantity' => $quantity,
                        'price' => $price,
                    ];
                }

                RecurringInvoice::create([
                    'company_id' => $client->company_id,
                    'client_id' => $client->id,
                    'category_id' => $categories->isNotEmpty() ? $categories->random()->id : null,
                    'name' => fake()->randomElement([
                        'Monthly Managed Services',
                        'Quarterly Maintenance',
                        'Annual Support Contract',
                        'Cloud Services Subscription',
                        'Security Monitoring Services',
                        'Backup Services',
                        'Software Licensing',
                    ]),
                    'frequency' => $frequency,
                    'start_date' => $startDate,
                    'next_date' => $nextDate,
                    'end_date' => fake()->optional(0.2)->dateTimeBetween('+1 year', '+3 years'),
                    'amount' => $amount,
                    'tax_rate' => 8.0,
                    'items' => json_encode($items),
                    'auto_send' => fake()->boolean(70),
                    'is_active' => fake()->boolean(90),
                    'last_generated_at' => Carbon::parse($startDate)->diffInDays(now()) > 30 ?
                        fake()->dateTimeBetween($startDate, '-1 week') : null,
                    'notes' => fake()->optional(0.3)->sentence(),
                    'created_at' => $startDate,
                    'updated_at' => fake()->dateTimeBetween($startDate, 'now'),
                ]);

                $totalRecurring++;
            }
        }

        $this->command->info("Created {$totalRecurring} recurring invoices.");
    }
}
