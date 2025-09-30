<?php

namespace Database\Seeders\Dev;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Seeder;

class SimpleProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating products (simplified)...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Get or create a category
            $category = Category::firstOrCreate(
                ['company_id' => $company->id, 'type' => 'income', 'name' => 'Services'],
                ['color' => '#4A90E2']
            );

            $products = [
                ['name' => 'Managed IT Support', 'price' => 150, 'type' => 'service'],
                ['name' => 'Help Desk Support', 'price' => 99, 'type' => 'service'],
                ['name' => 'Network Monitoring', 'price' => 75, 'type' => 'service'],
                ['name' => 'Backup Management', 'price' => 89, 'type' => 'service'],
                ['name' => 'Security Management', 'price' => 120, 'type' => 'service'],
                ['name' => 'Cloud Services', 'price' => 199, 'type' => 'service'],
                ['name' => 'Email Hosting', 'price' => 15, 'type' => 'service'],
                ['name' => 'Microsoft 365 License', 'price' => 25, 'type' => 'license'],
                ['name' => 'Antivirus License', 'price' => 8, 'type' => 'license'],
                ['name' => 'On-site Support', 'price' => 175, 'type' => 'service'],
            ];

            foreach ($products as $productData) {
                Product::create([
                    'company_id' => $company->id,
                    'name' => $productData['name'],
                    'description' => 'Professional '.$productData['name'].' services',
                    'sku' => strtoupper(fake()->bothify('??###')),
                    'type' => $productData['type'],
                    'category_id' => $category->id,
                    'base_price' => $productData['price'],
                    'cost' => $productData['price'] * 0.6,
                    'is_active' => true,
                    'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
                ]);
            }

            $this->command->info("  Created products for {$company->name}");
        }

        $this->command->info('Products created successfully.');
    }
}
