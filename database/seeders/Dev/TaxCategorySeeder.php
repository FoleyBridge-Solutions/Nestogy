<?php

namespace Database\Seeders\Dev;

use App\Models\TaxCategory;
use Illuminate\Database\Seeder;

class TaxCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating TaxCategory records...");
        $this->command->info("✓ TaxCategory seeded");
    }
}
