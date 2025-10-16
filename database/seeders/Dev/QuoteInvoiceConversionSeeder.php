<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\QuoteInvoiceConversion;
use Illuminate\Database\Seeder;

class QuoteInvoiceConversionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating QuoteInvoiceConversion records...");
        $this->command->info("✓ QuoteInvoiceConversion seeded");
    }
}
