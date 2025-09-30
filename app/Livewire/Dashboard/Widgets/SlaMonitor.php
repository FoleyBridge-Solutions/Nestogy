<?php

namespace App\Livewire\Dashboard\Widgets;

use App\Domains\Ticket\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class SlaMonitor extends Component
{
    public array $slaMetrics = [];

    public array $breachedTickets = [];

    public array $warningTickets = [];

    public bool $loading = true;

    public string $period = 'today'; // today, week, month

    public function mount()
    {
        $this->loadSlaData();
    }

    #[On('refresh-sla-monitor')]
    public function loadSlaData()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;

        // Get date range
        $startDate = match ($this->period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            default => Carbon::today()
        };

        // Get ALL active tickets with priority queues to check for SLA breaches
        $activeTickets = Ticket::where('company_id', $companyId)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->with(['priorityQueue', 'client'])
            ->get();

        // Get tickets created in the selected period for statistics
        $periodTickets = Ticket::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->with(['priorityQueue', 'client'])
            ->get();

        $totalTickets = $periodTickets->count();
        $breachedCount = 0;
        $warningCount = 0;
        $metByPriority = [
            'critical' => ['total' => 0, 'met' => 0, 'target' => 4],
            'high' => ['total' => 0, 'met' => 0, 'target' => 8],
            'medium' => ['total' => 0, 'met' => 0, 'target' => 24],
            'low' => ['total' => 0, 'met' => 0, 'target' => 48],
        ];

        $breached = [];
        $warnings = [];

        // Check ALL active tickets for current SLA breaches/warnings
        foreach ($activeTickets as $ticket) {
            $priority = strtolower($ticket->priority ?? 'medium');

            // Check if ticket has SLA breach via priority queue
            if ($ticket->priorityQueue && $ticket->priorityQueue->sla_deadline) {
                $deadline = $ticket->priorityQueue->sla_deadline;
                $hoursRemaining = now()->diffInHours($deadline, false);

                if ($hoursRemaining < 0) { // Breached
                    $breachedCount++;
                    if (count($breached) < 5) {
                        $breached[] = [
                            'id' => $ticket->id,
                            'subject' => $ticket->subject,
                            'priority' => $priority,
                            'hours_overdue' => abs($hoursRemaining),
                            'client' => $ticket->client?->company_name ?? 'Unknown',
                        ];
                    }
                } elseif ($hoursRemaining <= 2) { // Warning zone (less than 2 hours)
                    $warningCount++;
                    if (count($warnings) < 5) {
                        $warnings[] = [
                            'id' => $ticket->id,
                            'subject' => $ticket->subject,
                            'priority' => $priority,
                            'time_remaining' => $hoursRemaining,
                            'client' => $ticket->client?->company_name ?? 'Unknown',
                        ];
                    }
                }
            }
        }

        // Calculate compliance for ALL tickets (both resolved in period and currently active)
        // First, get resolved tickets that were closed in the selected period
        $resolvedInPeriod = Ticket::where('company_id', $companyId)
            ->whereIn('status', ['resolved', 'closed'])
            ->where('updated_at', '>=', $startDate)
            ->with(['priorityQueue'])
            ->get();

        // Combine with active tickets for complete picture
        $allRelevantTickets = $activeTickets->merge($resolvedInPeriod);

        foreach ($allRelevantTickets as $ticket) {
            $priority = strtolower($ticket->priority ?? 'medium');

            if (! isset($metByPriority[$priority])) {
                continue;
            }

            $slaHours = $metByPriority[$priority]['target'];
            $metByPriority[$priority]['total']++;

            // Check if ticket met/is meeting SLA
            if ($ticket->priorityQueue && $ticket->priorityQueue->sla_deadline) {
                // Use the actual SLA deadline from priority queue
                if (in_array($ticket->status, ['resolved', 'closed'])) {
                    // For closed tickets, check if they were resolved before SLA deadline
                    $metSla = $ticket->updated_at <= $ticket->priorityQueue->sla_deadline;
                } else {
                    // For open tickets, check if they're still within SLA
                    $metSla = now() <= $ticket->priorityQueue->sla_deadline;
                }

                if ($metSla) {
                    $metByPriority[$priority]['met']++;
                }
            } else {
                // Fallback to simple time calculation if no priority queue
                $responseTime = in_array($ticket->status, ['resolved', 'closed'])
                    ? $ticket->created_at->diffInHours($ticket->updated_at)
                    : $ticket->created_at->diffInHours(now());

                if ($responseTime <= $slaHours) {
                    $metByPriority[$priority]['met']++;
                }
            }
        }

        // Calculate overall compliance
        $totalMeasured = array_sum(array_column($metByPriority, 'total'));
        $totalMet = array_sum(array_column($metByPriority, 'met'));
        $overallCompliance = $totalMeasured > 0 ? round(($totalMet / $totalMeasured) * 100, 1) : 100;

        // Calculate average response times using all relevant tickets
        $avgResponseTimes = [];
        foreach (['critical', 'high', 'medium', 'low'] as $priority) {
            $priorityTickets = $allRelevantTickets->where('priority', $priority);
            if ($priorityTickets->count() > 0) {
                $totalHours = 0;
                $count = 0;
                foreach ($priorityTickets as $ticket) {
                    if (in_array($ticket->status, ['resolved', 'closed'])) {
                        // For closed tickets, use actual resolution time
                        $totalHours += $ticket->created_at->diffInHours($ticket->updated_at);
                    } else {
                        // For open tickets, use current elapsed time
                        $totalHours += $ticket->created_at->diffInHours(now());
                    }
                    $count++;
                }
                $avgResponseTimes[$priority] = $count > 0 ? round($totalHours / $count, 1) : 0;
            } else {
                // If no tickets of this priority, show the target as a reference
                $avgResponseTimes[$priority] = 0;
            }
        }

        $this->slaMetrics = [
            'overall_compliance' => $overallCompliance,
            'total_tickets' => $totalTickets,
            'breached_count' => $breachedCount,
            'warning_count' => $warningCount,
            'by_priority' => $metByPriority,
            'avg_response_times' => $avgResponseTimes,
        ];

        $this->breachedTickets = $breached;
        $this->warningTickets = $warnings;
        $this->loading = false;
    }

    /**
     * Livewire lifecycle hook for when period property changes
     */
    public function updatedPeriod($value)
    {
        if (in_array($value, ['today', 'week', 'month'])) {
            $this->loadSlaData();
        }
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.sla-monitor');
    }
}
