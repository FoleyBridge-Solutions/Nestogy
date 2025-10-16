<?php

namespace Database\Seeders\Dev;

use App\Models\TicketWatcher;
use App\Models\Ticket;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class TicketWatcherSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating TicketWatcher records...');
$tickets = Ticket::all();
        $count = 0;
        
        foreach ($tickets as $ticket) {
            $users = User::where('company_id', $ticket->company_id)->get();
            
            if ($users->isEmpty()) continue;
            
            $watcherCount = rand(1, 3);
            $selectedUsers = $users->random(min($watcherCount, $users->count()));
            
            foreach ($selectedUsers as $user) {
                TicketWatcher::factory()->create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'company_id' => $ticket->company_id,
                ]);
                $count++;
            }
        }
        
        $this->command->info("✓ Created {$count} ticket watchers");
    }
}
