<?php

namespace App\Livewire\Manager;

use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\HasFluxToasts;

class TeamDashboard extends Component
{
    use HasFluxToasts;
    public $teamStats = [];

    public $activeTickets = [];

    public $overdueTickets = [];

    public $technicianStats = [];

    public $slaCompliance = [];

    public $refreshInterval = 30000;

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        $companyId = auth()->user()->company_id;

        $this->teamStats = $this->getTeamStats($companyId);
        $this->activeTickets = $this->getActiveTickets($companyId);
        $this->overdueTickets = $this->getOverdueTickets($companyId);
        $this->technicianStats = $this->getTechnicianStats($companyId);
        $this->slaCompliance = $this->getSLACompliance($companyId);
    }

    protected function getTeamStats($companyId)
    {
        $totalOpen = Ticket::where('company_id', $companyId)
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->count();

        $totalOverdue = Ticket::where('company_id', $companyId)
            ->whereHas('priorityQueue', function ($q) {
                $q->where('sla_deadline', '<', now());
            })
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->count();

        $highPriority = Ticket::where('company_id', $companyId)
            ->whereIn('priority', [Ticket::PRIORITY_HIGH, Ticket::PRIORITY_CRITICAL])
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->count();

        $unassigned = Ticket::where('company_id', $companyId)
            ->whereNull('assigned_to')
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->count();

        $resolvedToday = Ticket::where('company_id', $companyId)
            ->whereDate('resolved_at', today())
            ->count();

        $avgResolutionTime = Ticket::where('company_id', $companyId)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(30))
            ->get()
            ->avg(function ($ticket) {
                return $ticket->created_at->diffInHours($ticket->resolved_at);
            });

        return [
            'total_open' => $totalOpen,
            'total_overdue' => $totalOverdue,
            'high_priority' => $highPriority,
            'unassigned' => $unassigned,
            'resolved_today' => $resolvedToday,
            'avg_resolution_time' => round($avgResolutionTime ?? 0, 1),
        ];
    }

    protected function getActiveTickets($companyId)
    {
        return Ticket::where('company_id', $companyId)
            ->with(['assignee:id,name,email', 'client:id,name', 'priorityQueue:id,ticket_id,sla_deadline'])
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->orderByRaw("FIELD(priority, 'Critical', 'High', 'Medium', 'Low')")
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get();
    }

    protected function getOverdueTickets($companyId)
    {
        return Ticket::where('company_id', $companyId)
            ->with(['assignee:id,name,email', 'client:id,name', 'priorityQueue:id,ticket_id,sla_deadline'])
            ->whereHas('priorityQueue', function ($q) {
                $q->where('sla_deadline', '<', now());
            })
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->orderBy('created_at', 'asc')
            ->limit(15)
            ->get();
    }

    protected function getTechnicianStats($companyId)
    {
        $technicians = User::where('company_id', $companyId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['technician', 'admin', 'manager']);
            })
            ->get();

        $stats = [];

        foreach ($technicians as $tech) {
            $activeTickets = Ticket::where('assigned_to', $tech->id)
                ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
                ->count();

            $overdueTickets = Ticket::where('assigned_to', $tech->id)
                ->whereHas('priorityQueue', function ($q) {
                    $q->where('sla_deadline', '<', now());
                })
                ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
                ->count();

            $resolvedToday = Ticket::where('assigned_to', $tech->id)
                ->whereDate('resolved_at', today())
                ->count();

            $resolvedThisWeek = Ticket::where('assigned_to', $tech->id)
                ->whereBetween('resolved_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

            $avgResolutionTime = Ticket::where('assigned_to', $tech->id)
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', now()->subDays(30))
                ->get()
                ->avg(function ($ticket) {
                    return $ticket->created_at->diffInHours($ticket->resolved_at);
                });

            $workloadScore = $this->calculateWorkloadScore($activeTickets, $overdueTickets);

            $stats[] = [
                'user' => $tech,
                'active_tickets' => $activeTickets,
                'overdue_tickets' => $overdueTickets,
                'resolved_today' => $resolvedToday,
                'resolved_this_week' => $resolvedThisWeek,
                'avg_resolution_time' => round($avgResolutionTime ?? 0, 1),
                'workload_score' => $workloadScore,
                'workload_status' => $this->getWorkloadStatus($workloadScore),
            ];
        }

        usort($stats, function ($a, $b) {
            return $b['active_tickets'] <=> $a['active_tickets'];
        });

        return $stats;
    }

    protected function getSLACompliance($companyId)
    {
        $total = Ticket::where('company_id', $companyId)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(30))
            ->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'met' => 0,
                'breached' => 0,
                'compliance_rate' => 0,
            ];
        }

        $met = Ticket::where('company_id', $companyId)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(30))
            ->whereHas('priorityQueue', function ($q) {
                $q->whereRaw('tickets.resolved_at <= ticket_priority_queues.sla_deadline');
            })
            ->count();

        $breached = $total - $met;

        return [
            'total' => $total,
            'met' => $met,
            'breached' => $breached,
            'compliance_rate' => round(($met / $total) * 100, 1),
        ];
    }

    protected function calculateWorkloadScore($activeTickets, $overdueTickets)
    {
        return ($activeTickets * 1) + ($overdueTickets * 3);
    }

    protected function getWorkloadStatus($score)
    {
        if ($score >= 15) {
            return ['label' => 'Overloaded', 'color' => 'red'];
        } elseif ($score >= 10) {
            return ['label' => 'Heavy', 'color' => 'orange'];
        } elseif ($score >= 5) {
            return ['label' => 'Moderate', 'color' => 'yellow'];
        } else {
            return ['label' => 'Light', 'color' => 'green'];
        }
    }

    public function refresh()
    {
        $this->loadDashboardData();
        $this->dispatch('dashboard-refreshed');
    }

    public function render()
    {
        return view('livewire.manager.team-dashboard');
    }
}
