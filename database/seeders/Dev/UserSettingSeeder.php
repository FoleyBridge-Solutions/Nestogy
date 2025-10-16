<?php

namespace Database\Seeders\Dev;

use App\Domains\Core\Models\UserSetting;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class UserSettingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating UserSetting records...');
$users = User::all();
        
        foreach ($users as $user) {
            if (!UserSetting::where('user_id', $user->id)->exists()) {
                UserSetting::factory()->create([
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                ]);
            }
        }
        
        $this->command->info("✓ Created ".UserSetting::count()." user settings");
    }
}
