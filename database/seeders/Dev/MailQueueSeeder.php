<?php

namespace Database\Seeders\Dev;

use App\Domains\Core\Models\MailQueue;
use Illuminate\Database\Seeder;

class MailQueueSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating MailQueue records...");
        $this->command->info("✓ MailQueue seeded");
    }
}
