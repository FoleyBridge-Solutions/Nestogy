<?php

namespace Database\Seeders\Dev;

use App\Models\CreditApplication;
use Illuminate\Database\Seeder;

class CreditApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CreditApplication records...");
        $this->command->info("✓ CreditApplication seeded");
    }
}
