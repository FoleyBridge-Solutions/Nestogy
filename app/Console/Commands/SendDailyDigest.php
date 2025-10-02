<?php

namespace App\Console\Commands;

use App\Mail\DailyDigest;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyDigest extends Command
{
    protected $signature = 'digest:send-daily';

    protected $description = 'Send daily digest emails to users who have opted in';

    public function handle(): int
    {
        $this->info('Starting daily digest email send...');

        $preferences = NotificationPreference::query()
            ->where('daily_digest', true)
            ->where('email_enabled', true)
            ->with('user')
            ->get();

        $sent = 0;
        $errors = 0;

        foreach ($preferences as $pref) {
            if (! $pref->user) {
                continue;
            }

            try {
                $digestData = $this->gatherDigestData($pref->user);
                
                if ($this->shouldSendDigest($digestData)) {
                    Mail::to($pref->user->email)->send(new DailyDigest($pref->user, $digestData));
                    $sent++;
                    $this->info("Sent digest to {$pref->user->email}");
                } else {
                    $this->info("Skipped digest for {$pref->user->email} (no activity)");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to send digest to {$pref->user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Daily digest complete: {$sent} sent, {$errors} errors");

        return self::SUCCESS;
    }

    protected function gatherDigestData(User $user): array
    {
        $since = now()->subDay();

        $data = [
            'new_tickets' => $this->getNewTickets($user, $since),
            'assigned_tickets' => $this->getAssignedTickets($user, $since),
            'resolved_tickets' => $this->getResolvedTickets($user, $since),
            'overdue_tickets' => $this->getOverdueTickets($user),
            'high_priority_tickets' => $this->getHighPriorityTickets($user),
            'activity_count' => 0,
        ];

        $data['activity_count'] = collect($data)->except('activity_count')->flatten()->count();

        return $data;
    }

    protected function getNewTickets(User $user, $since)
    {
        return Ticket::query()
            ->where('company_id', $user->company_id)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getAssignedTickets(User $user, $since)
    {
        return Ticket::query()
            ->where('assigned_to', $user->id)
            ->where('updated_at', '>=', $since)
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getResolvedTickets(User $user, $since)
    {
        return Ticket::query()
            ->where('company_id', $user->company_id)
            ->where('resolved_at', '>=', $since)
            ->whereNotNull('resolved_at')
            ->orderBy('resolved_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getOverdueTickets(User $user)
    {
        return Ticket::query()
            ->where('company_id', $user->company_id)
            ->whereHas('priorityQueue', function ($q) {
                $q->where('sla_deadline', '<', now());
            })
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();
    }

    protected function getHighPriorityTickets(User $user)
    {
        return Ticket::query()
            ->where('company_id', $user->company_id)
            ->whereIn('priority', [Ticket::PRIORITY_HIGH, Ticket::PRIORITY_CRITICAL])
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();
    }

    protected function shouldSendDigest(array $data): bool
    {
        return $data['activity_count'] > 0;
    }
}
