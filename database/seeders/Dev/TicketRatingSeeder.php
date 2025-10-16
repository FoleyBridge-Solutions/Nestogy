<?php

namespace Database\Seeders\Dev;

use App\Domains\Ticket\Models\TicketRating;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketRatingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating TicketRating records...');
$tickets = Ticket::whereIn('status', ['resolved', 'closed'])->get();
        $count = 0;
        
        foreach ($tickets as $ticket) {
            if (fake()->boolean(40)) {
                TicketRating::factory()->create([
                    'ticket_id' => $ticket->id,
                    'company_id' => $ticket->company_id,
                    'created_at' => fake()->dateTimeBetween($ticket->closed_at ?? $ticket->created_at, 'now'),
                ]);
                $count++;
            }
        }
        
        $this->command->info("✓ Created {$count} ticket ratings");
    }
}
