<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\ProductBundle;
use Illuminate\Database\Seeder;

class ProductBundleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Product Bundle Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create 5-15 product bundles per company
            ProductBundle::factory()
                ->count(rand(5, 15))
                ->for($company)
                ->create();
        }

        $this->command->info('Product Bundle Seeder completed!');
    }
}
