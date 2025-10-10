<?php

namespace Database\Seeders\Dev;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating NotificationPreference records...');
$users = User::all();
        
        foreach ($users as $user) {
            if (!NotificationPreference::where('user_id', $user->id)->exists()) {
                NotificationPreference::factory()->create([
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                ]);
            }
        }
        
        $this->command->info("✓ Created ".NotificationPreference::count()." notification preferences");
    }
}
