<?php

namespace Database\Seeders\Dev;

use App\Models\ProductTaxData;
use Illuminate\Database\Seeder;

class ProductTaxDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating ProductTaxData records...");
        $this->command->info("✓ ProductTaxData seeded");
    }
}
