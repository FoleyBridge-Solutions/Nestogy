<?php

namespace Database\Seeders\Dev;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating products and services catalog...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("  Creating products for {$company->name}...");

            // Get or create product categories
            $categories = Category::where('company_id', $company->id)
                ->where('type', 'income')
                ->get();

            if ($categories->isEmpty()) {
                // Create default categories if none exist
                $defaultCategories = [
                    'Managed Services',
                    'Professional Services',
                    'Hardware',
                    'Software Licenses',
                    'Cloud Services',
                ];

                foreach ($defaultCategories as $catName) {
                    $categories->push(Category::create([
                        'company_id' => $company->id,
                        'name' => $catName,
                        'type' => 'income',
                        'color' => fake()->hexColor(),
                    ]));
                }
            }

            $vendors = Vendor::where('company_id', $company->id)->get();

            // Define product templates
            $productTemplates = [
                // Managed Services
                [
                    'category' => 'Managed Services',
                    'products' => [
                        ['name' => 'Essential IT Support', 'price' => 99, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Professional IT Support', 'price' => 199, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Enterprise IT Support', 'price' => 499, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => '24/7 Monitoring', 'price' => 149, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Backup Management', 'price' => 89, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Security Management', 'price' => 179, 'type' => 'service', 'recurring' => 'monthly'],
                    ],
                ],
                // Professional Services
                [
                    'category' => 'Professional Services',
                    'products' => [
                        ['name' => 'On-Site Support', 'price' => 150, 'type' => 'service', 'recurring' => null],
                        ['name' => 'Remote Support', 'price' => 125, 'type' => 'service', 'recurring' => null],
                        ['name' => 'Emergency Support', 'price' => 250, 'type' => 'service', 'recurring' => null],
                        ['name' => 'Project Management', 'price' => 175, 'type' => 'service', 'recurring' => null],
                        ['name' => 'Network Assessment', 'price' => 1500, 'type' => 'service', 'recurring' => null],
                        ['name' => 'Security Audit', 'price' => 2500, 'type' => 'service', 'recurring' => null],
                        ['name' => 'IT Consultation', 'price' => 200, 'type' => 'service', 'recurring' => null],
                    ],
                ],
                // Hardware
                [
                    'category' => 'Hardware',
                    'products' => [
                        ['name' => 'Desktop Computer - Basic', 'price' => 599, 'type' => 'product', 'recurring' => null],
                        ['name' => 'Desktop Computer - Professional', 'price' => 999, 'type' => 'product', 'recurring' => null],
                        ['name' => 'Laptop - Basic', 'price' => 799, 'type' => 'product', 'recurring' => null],
                        ['name' => 'Laptop - Professional', 'price' => 1499, 'type' => 'product', 'recurring' => null],
                        ['name' => 'Server - Entry Level', 'price' => 2999, 'type' => 'product', 'recurring' => null],
                        ['name' => 'Network Switch - 24 Port', 'price' => 499, 'type' => 'product', 'recurring' => null],
                        ['name' => 'Firewall Appliance', 'price' => 899, 'type' => 'product', 'recurring' => null],
                        ['name' => 'UPS Battery Backup', 'price' => 299, 'type' => 'product', 'recurring' => null],
                    ],
                ],
                // Software Licenses
                [
                    'category' => 'Software Licenses',
                    'products' => [
                        ['name' => 'Microsoft 365 Business Basic', 'price' => 6, 'type' => 'license', 'recurring' => 'monthly'],
                        ['name' => 'Microsoft 365 Business Standard', 'price' => 12.50, 'type' => 'license', 'recurring' => 'monthly'],
                        ['name' => 'Microsoft 365 Business Premium', 'price' => 22, 'type' => 'license', 'recurring' => 'monthly'],
                        ['name' => 'Adobe Creative Cloud', 'price' => 54.99, 'type' => 'license', 'recurring' => 'monthly'],
                        ['name' => 'Antivirus Pro', 'price' => 4.99, 'type' => 'license', 'recurring' => 'monthly'],
                        ['name' => 'Backup Software License', 'price' => 9.99, 'type' => 'license', 'recurring' => 'monthly'],
                        ['name' => 'Remote Desktop License', 'price' => 14.99, 'type' => 'license', 'recurring' => 'monthly'],
                    ],
                ],
                // Cloud Services
                [
                    'category' => 'Cloud Services',
                    'products' => [
                        ['name' => 'Cloud Backup - 100GB', 'price' => 29, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Cloud Backup - 500GB', 'price' => 99, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Cloud Backup - 1TB', 'price' => 149, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Email Hosting - Basic', 'price' => 5, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Email Hosting - Professional', 'price' => 10, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Virtual Server - Small', 'price' => 49, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Virtual Server - Medium', 'price' => 99, 'type' => 'service', 'recurring' => 'monthly'],
                        ['name' => 'Virtual Server - Large', 'price' => 199, 'type' => 'service', 'recurring' => 'monthly'],
                    ],
                ],
            ];

            $totalProducts = 0;

            foreach ($productTemplates as $template) {
                $category = $categories->firstWhere('name', $template['category']);
                if (! $category) {
                    $category = $categories->first();
                }

                foreach ($template['products'] as $productData) {
                    // Add some variation to base prices
                    $price = $productData['price'] * fake()->randomFloat(2, 0.9, 1.2);

                    Product::create([
                        'company_id' => $company->id,
                        'name' => $productData['name'],
                        'description' => fake()->paragraph(),
                        'sku' => strtoupper(fake()->bothify('??###??')),
                        'type' => $productData['type'],
                        'category_id' => $category->id,
                        'vendor_id' => $vendors->isNotEmpty() ? $vendors->random()->id : null,
                        'base_price' => $price,
                        'cost' => $price * fake()->randomFloat(2, 0.4, 0.7), // 40-70% of price
                        'recurring_type' => $productData['recurring'],
                        'taxable' => fake()->boolean(80),
                        'active' => fake()->boolean(90),
                        'stock_quantity' => $productData['type'] === 'product' ? fake()->numberBetween(0, 100) : null,
                        'min_stock_level' => $productData['type'] === 'product' ? fake()->numberBetween(5, 20) : null,
                        'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
                        'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
                    ]);

                    $totalProducts++;
                }
            }

            $this->command->info("    ✓ Created {$totalProducts} products and services");
        }

        $this->command->info('Product catalog created successfully.');
    }
}
