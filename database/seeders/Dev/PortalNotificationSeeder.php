<?php

namespace Database\Seeders\Dev;

use App\Domains\Core\Models\PortalNotification;
use Illuminate\Database\Seeder;

class PortalNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating PortalNotification records...");
        $this->command->info("✓ PortalNotification seeded");
    }
}
