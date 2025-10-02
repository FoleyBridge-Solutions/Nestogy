<?php

namespace App\Livewire\Dashboard\Widgets;

use App\Domains\Ticket\Models\TicketTimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class TeamActivity extends Component
{
    public $teamMembers = [];
    public $activeTimers = [];
    public $unassignedTickets = 0;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $companyId = auth()->user()->company_id;

        $this->teamMembers = cache()->remember("team_activity_{$companyId}", 300, function () use ($companyId) {
            return User::where('company_id', $companyId)
                ->with(['roles'])
                ->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['admin', 'technician', 'manager']);
                })
                ->withCount([
                    'assignedTickets as active_count' => function ($q) {
                        $q->whereIn('status', ['Open', 'In Progress', 'Awaiting Customer']);
                    }
                ])
                ->get()
                ->map(function ($user) {
                    $hasActiveTimer = TicketTimeEntry::where('user_id', $user->id)
                        ->whereNull('ended_at')
                        ->exists();

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'active_count' => $user->active_count,
                        'status' => $hasActiveTimer ? 'working' : 'idle',
                    ];
                })
                ->take(8);
        });

        $this->activeTimers = cache()->remember("active_timers_{$companyId}", 60, function () use ($companyId) {
            return TicketTimeEntry::where('company_id', $companyId)
                ->whereNull('ended_at')
                ->count();
        });

        $this->unassignedTickets = cache()->remember("unassigned_tickets_{$companyId}", 60, function () use ($companyId) {
            return \App\Domains\Ticket\Models\Ticket::where('company_id', $companyId)
                ->whereNull('assigned_to')
                ->whereIn('status', ['Open', 'In Progress'])
                ->count();
        });
    }

    public function refresh()
    {
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.team-activity');
    }
}
