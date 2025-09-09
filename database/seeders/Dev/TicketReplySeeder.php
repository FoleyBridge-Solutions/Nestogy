<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TicketReply;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as Faker;

class TicketReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting TicketReply Seeder...');
        $faker = Faker::create();

        DB::transaction(function () use ($faker) {
            $tickets = Ticket::with('company')->get();
            $totalTickets = $tickets->count();
            $currentCount = 0;

            foreach ($tickets as $ticket) {
                $currentCount++;
                if ($currentCount % 100 === 0) {
                    $this->command->info("Processing ticket replies: {$currentCount}/{$totalTickets}");
                }
                
                $users = User::where('company_id', $ticket->company_id)->get();
                
                // Generate 2-10 replies per ticket
                $replyCount = $faker->numberBetween(2, 10);
                $currentTime = Carbon::parse($ticket->created_at);
                
                for ($i = 0; $i < $replyCount; $i++) {
                    $currentTime = $currentTime->addMinutes($faker->numberBetween(15, 240));
                    
                    // Don't create replies after ticket is closed
                    if ($ticket->closed_at && $currentTime > $ticket->closed_at) {
                        break;
                    }
                    
                    $this->createReply($ticket, $users, $currentTime, $i === $replyCount - 1, $faker);
                }
            }
        });

        $this->command->info('TicketReply Seeder completed!');
    }

    /**
     * Create a single ticket reply
     */
    private function createReply($ticket, $users, $replyTime, $isLast, $faker)
    {
        $isTechReply = $faker->boolean(70); // 70% tech replies, 30% client responses
        $isInternal = $isTechReply ? $faker->boolean(30) : false; // 30% of tech replies are internal
        
        TicketReply::create([
            'ticket_id' => $ticket->id,
            'company_id' => $ticket->company_id,
            'user_id' => $isTechReply ? $users->random()->id : null,
            'contact_id' => !$isTechReply ? $ticket->contact_id : null,
            'reply' => $this->generateReplyContent($ticket, $isTechReply, $isInternal, $isLast, $faker),
            'is_internal' => $isInternal,
            'time_worked' => $isTechReply ? $faker->numberBetween(15, 240) : 0,
            'billable' => $isTechReply && !$isInternal ? $faker->boolean(80) : false,
            'status_changed_from' => $faker->boolean(20) ? $ticket->status : null,
            'status_changed_to' => $faker->boolean(20) ? $faker->randomElement(['in_progress', 'on_hold', 'resolved']) : null,
            'created_at' => $replyTime,
            'updated_at' => $replyTime,
        ]);
    }

    /**
     * Generate realistic reply content
     */
    private function generateReplyContent($ticket, $isTechReply, $isInternal, $isLast, $faker)
    {
        if ($isInternal) {
            $templates = [
                "Internal note: Checked with {$faker->name()} from networking team. Issue appears to be {$faker->bs()}.",
                "Escalating to Level 2 support. Customer is a VIP client.",
                "Vendor ticket opened with reference #" . strtoupper($faker->bothify('???-########')),
                "Time spent on research and testing: {$faker->numberBetween(30, 120)} minutes",
                "Waiting for customer approval to proceed with the proposed solution."
            ];
        } elseif ($isTechReply) {
            if ($isLast && $ticket->status === 'closed') {
                $templates = [
                    "Issue has been resolved. The problem was {$faker->bs()}. I've implemented a permanent fix and tested to confirm everything is working properly.\n\nPlease let us know if you experience any further issues.",
                    "This ticket has been completed. All requested changes have been implemented and verified. The system is now functioning as expected.\n\nTicket closed.",
                    "Resolution completed successfully. Root cause: {$faker->bs()}. Corrective action taken: {$faker->catchPhrase()}.\n\nThank you for your patience."
                ];
            } else {
                $templates = [
                    "Thank you for contacting support. I've reviewed your ticket and I'm currently investigating the issue. I'll update you shortly with my findings.",
                    "I've identified the issue. It appears to be related to {$faker->bs()}. I'm working on implementing a solution now.",
                    "Update: I've made some progress on this issue. I've {$faker->bs()} and am now testing the changes.",
                    "Can you please provide the following additional information:\n- When did this issue first occur?\n- Are other users experiencing the same problem?\n- Have there been any recent changes to your system?",
                    "I've attempted a fix for this issue. Can you please test and confirm if the problem is resolved?"
                ];
            }
        } else {
            // Client response
            $templates = [
                "Yes, that seems to have fixed it. Thank you!",
                "No, I'm still experiencing the same issue. The error message is: {$faker->catchPhrase()}",
                "The issue is intermittent. It worked for a while but then started happening again.",
                "I need this resolved ASAP as it's affecting our {$faker->bs()}.",
                "Additional information as requested: {$faker->paragraph(2)}"
            ];
        }
        
        return $faker->randomElement($templates);
    }
}