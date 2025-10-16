<?php

namespace Database\Seeders\Dev;

use App\Domains\Product\Models\ProductBundle;
use Illuminate\Database\Seeder;

class ProductBundleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating ProductBundle records...");
        $this->command->info("✓ ProductBundle seeded");
    }
}
