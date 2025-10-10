<?php

namespace Database\Seeders\Dev;

use App\Models\ClientPortalUser;
use App\Models\ClientPortalSession;
use Illuminate\Database\Seeder;

class ClientPortalSessionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating ClientPortalSession records...');
$users = ClientPortalUser::all();
        $count = 0;
        
        foreach ($users as $user) {
            $sessionCount = rand(5, 20);
            for ($i = 0; $i < $sessionCount; $i++) {
                ClientPortalSession::factory()->create([
                    'client_portal_user_id' => $user->id,
                    'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
                ]);
                $count++;
            }
        }
        
        $this->command->info("✓ Created {$count} portal sessions");
    }
}
