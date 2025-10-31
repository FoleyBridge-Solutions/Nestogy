<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Skipping activity logs - not critical for testing.');
    }
}
