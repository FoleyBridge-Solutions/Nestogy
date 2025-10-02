<?php

namespace App\Jobs;

use App\Domains\Ticket\Models\Ticket;
use App\Mail\Tickets\SLABreached;
use App\Mail\Tickets\SLABreachWarning;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class CheckSLABreaches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const WARNING_HOURS = 2;

    public function handle(NotificationService $notificationService): void
    {
        $this->checkForBreachedTickets($notificationService);
        $this->checkForWarningTickets($notificationService);
    }

    protected function checkForBreachedTickets(NotificationService $notificationService): void
    {
        $breachedTickets = Ticket::query()
            ->with(['priorityQueue', 'assignee', 'client'])
            ->whereHas('priorityQueue', function ($q) {
                $q->where('sla_deadline', '<', now())
                    ->where('is_active', true);
            })
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->get();

        foreach ($breachedTickets as $ticket) {
            $cacheKey = "sla_breached_notified_{$ticket->id}";

            if (Cache::has($cacheKey)) {
                continue;
            }

            $slaDeadline = $ticket->priorityQueue->sla_deadline;
            $hoursOverdue = (int) now()->diffInHours($slaDeadline);

            $this->sendBreachNotifications($ticket, $slaDeadline, $hoursOverdue);

            $notificationService->notifySLABreached($ticket, $hoursOverdue);

            Cache::put($cacheKey, true, now()->addHours(24));

            \Log::warning("SLA BREACHED for ticket #{$ticket->number} - {$hoursOverdue}h overdue", [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->number,
                'priority' => $ticket->priority,
                'assigned_to' => $ticket->assignee?->name,
                'client' => $ticket->client?->name,
                'sla_deadline' => $slaDeadline,
                'hours_overdue' => $hoursOverdue,
            ]);
        }
    }

    protected function checkForWarningTickets(NotificationService $notificationService): void
    {
        $now = now();
        $warningThreshold = $now->copy()->addHours(self::WARNING_HOURS);

        $warningTickets = Ticket::query()
            ->with(['priorityQueue', 'assignee', 'client'])
            ->whereHas('priorityQueue', function ($q) use ($now, $warningThreshold) {
                $q->where('sla_deadline', '>', $now)
                    ->where('sla_deadline', '<=', $warningThreshold)
                    ->where('is_active', true);
            })
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->get();

        foreach ($warningTickets as $ticket) {
            $cacheKey = "sla_warning_notified_{$ticket->id}";

            if (Cache::has($cacheKey)) {
                continue;
            }

            $slaDeadline = $ticket->priorityQueue->sla_deadline;
            $hoursRemaining = (int) now()->diffInHours($slaDeadline, false);

            if ($hoursRemaining <= 0) {
                continue;
            }

            $this->sendWarningNotifications($ticket, $slaDeadline, $hoursRemaining);

            $notificationService->notifySLABreachWarning($ticket, $hoursRemaining);

            Cache::put($cacheKey, true, $slaDeadline->addHours(1));

            \Log::info("SLA WARNING for ticket #{$ticket->number} - {$hoursRemaining}h remaining", [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->number,
                'priority' => $ticket->priority,
                'assigned_to' => $ticket->assignee?->name,
                'client' => $ticket->client?->name,
                'sla_deadline' => $slaDeadline,
                'hours_remaining' => $hoursRemaining,
            ]);
        }
    }

    protected function sendBreachNotifications(Ticket $ticket, $slaDeadline, int $hoursOverdue): void
    {
        $recipients = $this->getNotificationRecipients($ticket, 'sla_breached');

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new SLABreached($ticket, $slaDeadline, $hoursOverdue));
            } catch (\Exception $e) {
                \Log::error("Failed to send SLA breach email to {$recipient->email}: {$e->getMessage()}");
            }
        }
    }

    protected function sendWarningNotifications(Ticket $ticket, $slaDeadline, int $hoursRemaining): void
    {
        $recipients = $this->getNotificationRecipients($ticket, 'sla_breach_warning');

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new SLABreachWarning($ticket, $slaDeadline, $hoursRemaining));
            } catch (\Exception $e) {
                \Log::error("Failed to send SLA warning email to {$recipient->email}: {$e->getMessage()}");
            }
        }
    }

    protected function getNotificationRecipients(Ticket $ticket, string $eventType): array
    {
        $recipients = collect();

        if ($ticket->assignee) {
            $recipients->push($ticket->assignee);
        }

        $managers = \App\Models\User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'manager']);
        })->where('company_id', $ticket->company_id)->get();

        $recipients = $recipients->merge($managers);

        return $recipients->filter(function ($user) use ($eventType) {
            $prefs = NotificationPreference::getOrCreateForUser($user);
            return $prefs->shouldSendEmail($eventType);
        })->unique('id')->values()->all();
    }
}
