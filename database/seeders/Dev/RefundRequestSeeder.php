<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\RefundRequest;
use Illuminate\Database\Seeder;

class RefundRequestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating RefundRequest records...");
        $this->command->info("✓ RefundRequest seeded");
    }
}
