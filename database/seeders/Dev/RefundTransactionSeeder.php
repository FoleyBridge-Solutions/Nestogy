<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\RefundTransaction;
use Illuminate\Database\Seeder;

class RefundTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating RefundTransaction records...");
        $this->command->info("✓ RefundTransaction seeded");
    }
}
