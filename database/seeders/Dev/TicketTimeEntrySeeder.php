<?php

namespace Database\Seeders\Dev;

use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class TicketTimeEntrySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating TicketTimeEntry records...');
$tickets = Ticket::all();
        $count = 0;
        
        foreach ($tickets as $ticket) {
            $entryCount = rand(1, 5);
            $users = User::where('company_id', $ticket->company_id)->get();
            
            if ($users->isEmpty()) continue;
            
            for ($i = 0; $i < $entryCount; $i++) {
                TicketTimeEntry::factory()->create([
                    'ticket_id' => $ticket->id,
                    'company_id' => $ticket->company_id,
                    'user_id' => $users->random()->id,
                    'created_at' => fake()->dateTimeBetween($ticket->created_at, 'now'),
                ]);
                $count++;
            }
        }
        
        $this->command->info("✓ Created {$count} ticket time entries");
    }
}
