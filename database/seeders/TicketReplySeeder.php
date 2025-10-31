<?php

namespace Database\Seeders;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class TicketReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating ticket replies/comments...');
        
        $tickets = Ticket::all();
        $totalComments = 0;

        foreach ($tickets as $index => $ticket) {
            // Generate 2-5 replies per ticket
            $replyCount = rand(2, 5);
            
            $users = User::where('company_id', $ticket->company_id)->get();
            
            if ($users->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < $replyCount; $i++) {
                TicketComment::create([
                    'company_id' => $ticket->company_id,
                    'ticket_id' => $ticket->id,
                    'author_id' => $users->random()->id,
                    'author_type' => 'user',
                    'content' => fake()->paragraphs(rand(1, 3), true),
                    'visibility' => fake()->boolean(30) ? 'internal' : 'public',
                    'source' => fake()->randomElement(['manual', 'email', 'api']),
                    'created_at' => fake()->dateTimeBetween($ticket->created_at, 'now'),
                    'updated_at' => fake()->dateTimeBetween($ticket->created_at, 'now'),
                ]);
                
                $totalComments++;
            }
            
            // Show progress
            if ($index % 1000 == 0 && $index > 0) {
                $this->command->info("  Processed {$index} tickets...");
            }
        }
        
        $this->command->info("Created {$totalComments} ticket comments!");
    }
}
