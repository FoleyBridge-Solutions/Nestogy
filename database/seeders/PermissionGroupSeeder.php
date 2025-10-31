<?php

namespace Database\Seeders;

use App\Domains\Core\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Permission Group Seeder...');

        // Create 10-15 permission groups
        PermissionGroup::factory()
            ->count(rand(10, 15))
            ->create();

        $this->command->info('Permission Group Seeder completed!');
    }
}
