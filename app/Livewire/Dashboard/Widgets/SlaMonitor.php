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
        $startDate = $this->getStartDate();

        $activeTickets = $this->getActiveTickets($companyId);
        $periodTickets = $this->getPeriodTickets($companyId, $startDate);

        $slaStatus = $this->checkActiveSlaStatus($activeTickets);
        $metByPriority = $this->initializeMetByPriority();

        $resolvedInPeriod = $this->getResolvedTickets($companyId, $startDate);
        $allRelevantTickets = $activeTickets->merge($resolvedInPeriod);

        $metByPriority = $this->calculateSlaCompliance($allRelevantTickets, $metByPriority);
        $avgResponseTimes = $this->calculateAverageResponseTimes($allRelevantTickets);
        $overallCompliance = $this->calculateOverallCompliance($metByPriority);

        $this->slaMetrics = [
            'overall_compliance' => $overallCompliance,
            'total_tickets' => $periodTickets->count(),
            'breached_count' => $slaStatus['breachedCount'],
            'warning_count' => $slaStatus['warningCount'],
            'by_priority' => $metByPriority,
            'avg_response_times' => $avgResponseTimes,
        ];

        $this->breachedTickets = $slaStatus['breached'];
        $this->warningTickets = $slaStatus['warnings'];
        $this->loading = false;
    }

    protected function getStartDate()
    {
        return match ($this->period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            default => Carbon::today()
        };
    }

    protected function getActiveTickets($companyId)
    {
        return Ticket::where('company_id', $companyId)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->with(['priorityQueue', 'client'])
            ->get();
    }

    protected function getPeriodTickets($companyId, $startDate)
    {
        return Ticket::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->with(['priorityQueue', 'client'])
            ->get();
    }

    protected function getResolvedTickets($companyId, $startDate)
    {
        return Ticket::where('company_id', $companyId)
            ->whereIn('status', ['resolved', 'closed'])
            ->where('updated_at', '>=', $startDate)
            ->with(['priorityQueue'])
            ->get();
    }

    protected function initializeMetByPriority()
    {
        return [
            'critical' => ['total' => 0, 'met' => 0, 'target' => 4],
            'high' => ['total' => 0, 'met' => 0, 'target' => 8],
            'medium' => ['total' => 0, 'met' => 0, 'target' => 24],
            'low' => ['total' => 0, 'met' => 0, 'target' => 48],
        ];
    }

    protected function checkActiveSlaStatus($activeTickets)
    {
        $breachedCount = 0;
        $warningCount = 0;
        $breached = [];
        $warnings = [];

        foreach ($activeTickets as $ticket) {
            if (! $ticket->priorityQueue || ! $ticket->priorityQueue->sla_deadline) {
                continue;
            }

            $priority = strtolower($ticket->priority ?? 'medium');
            $deadline = $ticket->priorityQueue->sla_deadline;
            $hoursRemaining = now()->diffInHours($deadline, false);

            if ($hoursRemaining < 0) {
                $breachedCount++;
                if (count($breached) < 5) {
                    $breached[] = $this->formatTicketStatus($ticket, $priority, abs($hoursRemaining), 'overdue');
                }
            } elseif ($hoursRemaining <= 2) {
                $warningCount++;
                if (count($warnings) < 5) {
                    $warnings[] = $this->formatTicketStatus($ticket, $priority, $hoursRemaining, 'remaining');
                }
            }
        }

        return compact('breachedCount', 'warningCount', 'breached', 'warnings');
    }

    protected function formatTicketStatus($ticket, $priority, $hours, $type)
    {
        $baseData = [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'priority' => $priority,
            'client' => $ticket->client?->company_name ?? 'Unknown',
        ];

        if ($type === 'overdue') {
            $baseData['hours_overdue'] = $hours;
        } else {
            $baseData['time_remaining'] = $hours;
        }

        return $baseData;
    }

    protected function calculateSlaCompliance($allRelevantTickets, $metByPriority)
    {
        foreach ($allRelevantTickets as $ticket) {
            $priority = strtolower($ticket->priority ?? 'medium');

            if (! isset($metByPriority[$priority])) {
                continue;
            }

            $slaHours = $metByPriority[$priority]['target'];
            $metByPriority[$priority]['total']++;

            if ($this->ticketMetSla($ticket, $slaHours)) {
                $metByPriority[$priority]['met']++;
            }
        }

        return $metByPriority;
    }

    protected function ticketMetSla($ticket, $slaHours)
    {
        if ($ticket->priorityQueue && $ticket->priorityQueue->sla_deadline) {
            return $this->checkPriorityQueueSla($ticket);
        }

        return $this->checkFallbackSla($ticket, $slaHours);
    }

    protected function checkPriorityQueueSla($ticket)
    {
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            return $ticket->updated_at <= $ticket->priorityQueue->sla_deadline;
        }

        return now() <= $ticket->priorityQueue->sla_deadline;
    }

    protected function checkFallbackSla($ticket, $slaHours)
    {
        $responseTime = in_array($ticket->status, ['resolved', 'closed'])
            ? $ticket->created_at->diffInHours($ticket->updated_at)
            : $ticket->created_at->diffInHours(now());

        return $responseTime <= $slaHours;
    }

    protected function calculateAverageResponseTimes($allRelevantTickets)
    {
        $avgResponseTimes = [];

        foreach (['critical', 'high', 'medium', 'low'] as $priority) {
            $priorityTickets = $allRelevantTickets->where('priority', $priority);

            if ($priorityTickets->count() > 0) {
                $avgResponseTimes[$priority] = $this->calculateAverageForPriority($priorityTickets);
            } else {
                $avgResponseTimes[$priority] = 0;
            }
        }

        return $avgResponseTimes;
    }

    protected function calculateAverageForPriority($priorityTickets)
    {
        $totalHours = 0;
        $count = 0;

        foreach ($priorityTickets as $ticket) {
            $totalHours += $this->getTicketResponseTime($ticket);
            $count++;
        }

        return $count > 0 ? round($totalHours / $count, 1) : 0;
    }

    protected function getTicketResponseTime($ticket)
    {
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            return $ticket->created_at->diffInHours($ticket->updated_at);
        }

        return $ticket->created_at->diffInHours(now());
    }

    protected function calculateOverallCompliance($metByPriority)
    {
        $totalMeasured = array_sum(array_column($metByPriority, 'total'));
        $totalMet = array_sum(array_column($metByPriority, 'met'));

        return $totalMeasured > 0 ? round(($totalMet / $totalMeasured) * 100, 1) : 100;
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
