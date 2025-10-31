<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Skipping notifications - not critical for testing.');
    }
}
