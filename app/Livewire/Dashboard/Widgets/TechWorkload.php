<?php

namespace App\Livewire\Dashboard\Widgets;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class TechWorkload extends Component
{
    public $technicians = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $companyId = auth()->user()->company_id;
        $startOfWeek = Carbon::now()->startOfWeek();

        $this->technicians = cache()->remember("tech_workload_{$companyId}_{$startOfWeek->format('Y-m-d')}", 300, function () use ($companyId, $startOfWeek) {
            return User::where('company_id', $companyId)
                ->with(['roles', 'assignedTickets' => function ($q) {
                    $q->select('id', 'assigned_to', 'status', 'priority')
                      ->whereIn('status', ['Open', 'In Progress', 'Awaiting Customer']);
                }])
                ->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['technician', 'admin']);
                })
                ->withCount([
                    'assignedTickets as active_tickets' => function ($q) {
                        $q->whereIn('status', ['Open', 'In Progress', 'Awaiting Customer']);
                    },
                    'assignedTickets as critical_tickets' => function ($q) {
                        $q->where('priority', 'Critical')
                          ->whereIn('status', ['Open', 'In Progress']);
                    }
                ])
                ->get()
                ->map(function ($tech) use ($startOfWeek) {
                    $hoursThisWeek = TicketTimeEntry::where('user_id', $tech->id)
                        ->where('work_date', '>=', $startOfWeek)
                        ->sum('minutes') / 60;

                    $workloadScore = ($tech->critical_tickets * 10) + ($tech->active_tickets * 1) + ($hoursThisWeek / 10);

                    return [
                        'id' => $tech->id,
                        'name' => $tech->name,
                        'active_tickets' => $tech->active_tickets,
                        'critical' => $tech->critical_tickets,
                        'hours_this_week' => round($hoursThisWeek, 1),
                        'workload_score' => round($workloadScore, 1),
                        'workload_color' => $this->getWorkloadColor($workloadScore),
                    ];
                })
                ->sortByDesc('workload_score')
                ->take(8)
                ->values();
        });
    }

    protected function getWorkloadColor($score)
    {
        if ($score >= 50) {
            return 'red';
        }
        
        if ($score >= 30) {
            return 'orange';
        }
        
        if ($score >= 15) {
            return 'yellow';
        }
        
        return 'green';
    }

    public function refresh()
    {
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.tech-workload');
    }
}
