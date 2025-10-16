<?php

namespace Database\Seeders\Dev;

use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class TicketCommentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating TicketComment records...');
$tickets = Ticket::all();
        $count = 0;
        
        foreach ($tickets as $ticket) {
            $commentCount = rand(2, 8);
            $users = User::where('company_id', $ticket->company_id)->get();
            
            if ($users->isEmpty()) continue;
            
            for ($i = 0; $i < $commentCount; $i++) {
                TicketComment::factory()->create([
                    'ticket_id' => $ticket->id,
                    'company_id' => $ticket->company_id,
                    'user_id' => $users->random()->id,
                    'created_at' => fake()->dateTimeBetween($ticket->created_at, 'now'),
                ]);
                $count++;
            }
        }
        
        $this->command->info("✓ Created {$count} ticket comments");
    }
}
