<?php

namespace Database\Seeders\Dev;

use App\Domains\Ticket\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tickets = Ticket::all();
        $users = User::all();

        foreach ($tickets as $ticket) {
            // Generate 2-10 replies per ticket
            $replyCount = rand(2, 10);

            for ($i = 0; $i < $replyCount; $i++) {
                $isTechReply = fake()->boolean(70); // 70% tech replies
                $user = $isTechReply
                    ? $users->where('company_id', $ticket->company_id)->random()
                    : $users->random();

                $reply = TicketReply::factory()
                    ->create([
                        'company_id' => $ticket->company_id,
                        'ticket_id' => $ticket->id,
                        'replied_by' => $user->id,
                    ]);

                // Add some variety with factory states
                if ($i === 0) {
                    // First reply is usually public
                    $reply->update(['type' => TicketReply::TYPE_PUBLIC]);
                } elseif ($isTechReply && fake()->boolean(30)) {
                    // 30% of tech replies are internal
                    $reply->update(['type' => TicketReply::TYPE_INTERNAL]);
                }

                // Add sentiment for customer replies
                if (! $isTechReply && fake()->boolean(60)) {
                    if (fake()->boolean(70)) {
                        TicketReply::factory()
                            ->positiveSentiment()
                            ->create([
                                'company_id' => $ticket->company_id,
                                'ticket_id' => $ticket->id,
                                'replied_by' => $user->id,
                                'type' => TicketReply::TYPE_PUBLIC,
                            ]);
                    } else {
                        TicketReply::factory()
                            ->negativeSentiment()
                            ->create([
                                'company_id' => $ticket->company_id,
                                'ticket_id' => $ticket->id,
                                'replied_by' => $user->id,
                                'type' => TicketReply::TYPE_PUBLIC,
                            ]);
                    }
                }
            }
        }
    }
}
