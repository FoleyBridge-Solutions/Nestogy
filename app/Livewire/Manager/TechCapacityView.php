<?php

namespace App\Livewire\Manager;

use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Livewire\Component;
use App\Traits\HasFluxToasts;

class TechCapacityView extends Component
{
    use HasFluxToasts;
    public $technicians = [];

    public $viewMode = 'grid';

    public $sortBy = 'workload';

    public $sortDirection = 'desc';

    public function mount()
    {
        $this->loadTechnicians();
    }

    public function loadTechnicians()
    {
        $companyId = auth()->user()->company_id;

        $techs = User::where('company_id', $companyId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['technician', 'admin', 'manager']);
            })
            ->get();

        $this->technicians = $techs->map(function ($tech) {
            return $this->calculateTechnicianMetrics($tech);
        })->toArray();

        $this->sortTechnicians();
    }

    protected function calculateTechnicianMetrics(User $tech): array
    {
        $activeTickets = Ticket::where('assigned_to', $tech->id)
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->get();

        $totalActive = $activeTickets->count();

        $criticalCount = $activeTickets->where('priority', Ticket::PRIORITY_CRITICAL)->count();
        $highCount = $activeTickets->where('priority', Ticket::PRIORITY_HIGH)->count();
        $mediumCount = $activeTickets->where('priority', Ticket::PRIORITY_MEDIUM)->count();
        $lowCount = $activeTickets->where('priority', Ticket::PRIORITY_LOW)->count();

        $overdueCount = $activeTickets->filter(function ($ticket) {
            return $ticket->priorityQueue && 
                   $ticket->priorityQueue->sla_deadline && 
                   now()->gt($ticket->priorityQueue->sla_deadline);
        })->count();

        $unresolvedOldest = $activeTickets->sortBy('created_at')->first();
        $oldestTicketAge = $unresolvedOldest ? $unresolvedOldest->created_at->diffInDays(now()) : 0;

        $resolvedLast7Days = Ticket::where('assigned_to', $tech->id)
            ->whereBetween('resolved_at', [now()->subDays(7), now()])
            ->count();

        $resolvedLast30Days = Ticket::where('assigned_to', $tech->id)
            ->whereBetween('resolved_at', [now()->subDays(30), now()])
            ->count();

        $avgResolutionTime = Ticket::where('assigned_to', $tech->id)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(30))
            ->get()
            ->avg(function ($ticket) {
                return $ticket->created_at->diffInHours($ticket->resolved_at);
            });

        $totalTimeLogged = Ticket::where('assigned_to', $tech->id)
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->with('timeEntries')
            ->get()
            ->flatMap->timeEntries
            ->where('user_id', $tech->id)
            ->sum('hours_worked');

        $workloadScore = $this->calculateWorkloadScore([
            'total_active' => $totalActive,
            'overdue_count' => $overdueCount,
            'critical_count' => $criticalCount,
            'high_count' => $highCount,
        ]);

        $capacityPercentage = min(($workloadScore / 20) * 100, 100);

        $availableCapacity = max(0, 100 - $capacityPercentage);

        return [
            'user' => $tech,
            'total_active' => $totalActive,
            'critical_count' => $criticalCount,
            'high_count' => $highCount,
            'medium_count' => $mediumCount,
            'low_count' => $lowCount,
            'overdue_count' => $overdueCount,
            'oldest_ticket_age' => $oldestTicketAge,
            'resolved_last_7_days' => $resolvedLast7Days,
            'resolved_last_30_days' => $resolvedLast30Days,
            'avg_resolution_time' => round($avgResolutionTime ?? 0, 1),
            'time_logged_this_week' => round($totalTimeLogged, 1),
            'workload_score' => $workloadScore,
            'capacity_percentage' => round($capacityPercentage, 1),
            'available_capacity' => round($availableCapacity, 1),
            'status' => $this->getCapacityStatus($capacityPercentage),
        ];
    }

    protected function calculateWorkloadScore(array $metrics): float
    {
        $score = 0;
        
        $score += $metrics['total_active'] * 1;
        $score += $metrics['overdue_count'] * 3;
        $score += $metrics['critical_count'] * 2;
        $score += $metrics['high_count'] * 1.5;

        return round($score, 1);
    }

    protected function getCapacityStatus(float $percentage): array
    {
        if ($percentage >= 90) {
            return ['label' => 'At Capacity', 'color' => 'red', 'icon' => 'fas fa-exclamation-circle'];
        } elseif ($percentage >= 75) {
            return ['label' => 'High Load', 'color' => 'orange', 'icon' => 'fas fa-exclamation-triangle'];
        } elseif ($percentage >= 50) {
            return ['label' => 'Moderate Load', 'color' => 'yellow', 'icon' => 'fas fa-info-circle'];
        } elseif ($percentage >= 25) {
            return ['label' => 'Light Load', 'color' => 'green', 'icon' => 'fas fa-check-circle'];
        } else {
            return ['label' => 'Available', 'color' => 'blue', 'icon' => 'fas fa-thumbs-up'];
        }
    }

    public function sortTechnicians()
    {
        $technicians = collect($this->technicians);

        $sorted = $technicians->sortBy(function ($tech) {
            return match ($this->sortBy) {
                'workload' => $tech['workload_score'],
                'capacity' => $tech['capacity_percentage'],
                'active' => $tech['total_active'],
                'overdue' => $tech['overdue_count'],
                'resolved' => $tech['resolved_last_7_days'],
                'name' => $tech['user']->name,
                default => $tech['workload_score'],
            };
        }, SORT_REGULAR, $this->sortDirection === 'desc');

        $this->technicians = $sorted->values()->toArray();
    }

    public function setSortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }

        $this->sortTechnicians();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function refresh()
    {
        $this->loadTechnicians();
    }

    public function render()
    {
        return view('livewire.manager.tech-capacity-view');
    }
}
