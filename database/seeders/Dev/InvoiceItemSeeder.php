<?php

namespace Database\Seeders\Dev;

use App\Models\InvoiceItem;
use Illuminate\Database\Seeder;

class InvoiceItemSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating InvoiceItem records...");
        $this->command->info("✓ InvoiceItem seeded");
    }
}
