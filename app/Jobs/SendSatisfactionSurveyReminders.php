<?php

namespace App\Jobs;

use App\Domains\Ticket\Models\Ticket;
use App\Mail\Tickets\SatisfactionSurveyReminder;
use App\Models\TicketRating;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendSatisfactionSurveyReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $cutoffDate = now()->subHours(24);

        $resolvedTickets = Ticket::where('is_resolved', true)
            ->where('resolved_at', '<=', $cutoffDate)
            ->where('resolved_at', '>=', now()->subDays(7))
            ->whereDoesntHave('ratings')
            ->with(['contact', 'client'])
            ->get();

        $sentCount = 0;

        foreach ($resolvedTickets as $ticket) {
            try {
                if (!$ticket->contact || !$ticket->contact->email) {
                    continue;
                }

                $surveyUrl = route('portal.tickets.survey', [
                    'ticket' => $ticket->id,
                    'token' => $this->generateSurveyToken($ticket),
                ]);

                Mail::to($ticket->contact->email)
                    ->queue(new SatisfactionSurveyReminder($ticket, $surveyUrl));

                $sentCount++;

            } catch (\Exception $e) {
                Log::error('Failed to send satisfaction survey reminder', [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Satisfaction survey reminders sent', [
            'count' => $sentCount,
            'total_eligible' => $resolvedTickets->count(),
        ]);
    }

    protected function generateSurveyToken(Ticket $ticket): string
    {
        return hash_hmac(
            'sha256',
            "{$ticket->id}:{$ticket->resolved_at}",
            config('app.key')
        );
    }
}
