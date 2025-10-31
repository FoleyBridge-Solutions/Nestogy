<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\Product;
use App\Domains\Tax\Models\ProductTaxData;
use Illuminate\Database\Seeder;

class ProductTaxDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Product Tax Data Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating product tax data for company: {$company->name}");

            $products = Product::where('company_id', $company->id)->get();

            if ($products->isEmpty()) {
                $this->command->warn("No products found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create tax data for 50-70% of products
            $productCount = (int) ($products->count() * rand(50, 70) / 100);
            $selectedProducts = $products->random(min($productCount, $products->count()));

            foreach ($selectedProducts as $product) {
                ProductTaxData::factory()
                    ->for($company)
                    ->for($product)
                    ->create();
            }

            $this->command->info("Completed product tax data for company: {$company->name}");
        }

        $this->command->info('Product Tax Data Seeder completed!');
    }
}
