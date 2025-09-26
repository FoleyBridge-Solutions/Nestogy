<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\User;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ResourceAllocation extends Component
{
    public $teamMembers;
    public array $allocationSummary = [];
    public bool $loading = true;
    public string $view = 'workload'; // workload, availability, projects
    public bool $showAllMembers = false;
    
    public function mount()
    {
        $this->teamMembers = collect();
        $this->allocationSummary = [
            'total_members' => 0,
            'available' => 0,
            'moderate' => 0,
            'busy' => 0,
            'overloaded' => 0,
            'total_tickets' => 0,
            'total_projects' => 0,
        ];
        $this->loadAllocationData();
    }
    
    #[On('refresh-resource-allocation')]
    public function loadAllocationData()
    {
        $this->loading = true;
        
        // Ensure we have an authenticated user
        if (!Auth::check()) {
            $this->loading = false;
            return;
        }
        
        $companyId = Auth::user()->company_id;
        
        // Get all active team members
        $users = User::where('company_id', $companyId)
            ->where('status', true)
            ->get();
        
        \Log::info('ResourceAllocation: Found ' . $users->count() . ' users for company ' . $companyId);
        
        $mappedMembers = $users->map(function ($user) use ($companyId) {
            // Get user's active tickets
            $activeTickets = Ticket::where('company_id', $companyId)
                ->where('assigned_to', $user->id)
                ->whereNotIn('status', ['resolved', 'closed'])
                ->get();
            
            // Get user's active projects (where they are the manager and not completed)
            $activeProjects = Project::where('company_id', $companyId)
                ->where('manager_id', $user->id)
                ->whereNull('completed_at')
                ->count();
            
            // Calculate workload score (simple algorithm)
            $criticalTickets = $activeTickets->where('priority', 'critical')->count();
            $highTickets = $activeTickets->where('priority', 'high')->count();
            $mediumTickets = $activeTickets->where('priority', 'medium')->count();
            $lowTickets = $activeTickets->where('priority', 'low')->count();
            
            $workloadScore = ($criticalTickets * 4) + ($highTickets * 3) + ($mediumTickets * 2) + ($lowTickets * 1) + ($activeProjects * 5);
            
            // Determine utilization level
            $utilization = 'available';
            if ($workloadScore > 20) {
                $utilization = 'overloaded';
            } elseif ($workloadScore > 15) {
                $utilization = 'busy';
            } elseif ($workloadScore > 5) {
                $utilization = 'moderate';
            }
            
            // Get today's hours from time entries or ticket activity
            $todayHours = $this->getTodayHours($user);
            
            return [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->name ?? 'Technician',
                'avatar' => null,
                'tickets' => [
                    'total' => $activeTickets->count(),
                    'critical' => $criticalTickets,
                    'high' => $highTickets,
                    'medium' => $mediumTickets,
                    'low' => $lowTickets,
                ],
                'projects' => $activeProjects,
                'workload_score' => $workloadScore,
                'utilization' => $utilization,
                'today_hours' => $todayHours,
                'capacity_percentage' => min(100, ($workloadScore / 25) * 100),
                'available' => $utilization !== 'overloaded',
            ];
        });
        
        $this->teamMembers = $mappedMembers->toArray();
        \Log::info('ResourceAllocation: Mapped ' . count($this->teamMembers) . ' team members');
        
        // Calculate summary statistics
        $teamCollection = collect($this->teamMembers);
        $this->allocationSummary = [
            'total_members' => $teamCollection->count(),
            'available' => $teamCollection->where('utilization', 'available')->count(),
            'moderate' => $teamCollection->where('utilization', 'moderate')->count(),
            'busy' => $teamCollection->where('utilization', 'busy')->count(),
            'overloaded' => $teamCollection->where('utilization', 'overloaded')->count(),
            'total_tickets' => $teamCollection->sum(function($member) { return $member['tickets']['total'] ?? 0; }),
            'total_projects' => $teamCollection->sum('projects'),
        ];
        
        \Log::info('Allocation summary: ' . json_encode($this->allocationSummary));
        
        $this->loading = false;
    }
    
    /**
     * Livewire lifecycle hook for when view property changes
     */
    public function updatedView($value)
    {
        if (in_array($value, ['workload', 'availability', 'projects'])) {
            // View change doesn't require data reload for this widget
        }
    }
    
    public function reallocateTicket($userId)
    {
        // This would open a modal or redirect to reallocation interface
        $this->dispatch('open-reallocation-modal', ['user_id' => $userId]);
    }
    
    protected function getTodayHours($user)
    {
        try {
            // Try to get actual time entries for today
            if (class_exists('\App\Models\TimeEntry')) {
                $hours = \App\Models\TimeEntry::where('user_id', $user->id)
                    ->whereDate('created_at', Carbon::today())
                    ->sum('hours');
                    
                return round($hours, 1);
            }
        } catch (\Exception $e) {
            // TimeEntry model not available
        }
        
        // Fallback: estimate based on ticket activity today
        $ticketActivity = Ticket::where('assigned_to', $user->id)
            ->whereDate('updated_at', Carbon::today())
            ->count();
            
        // Estimate 0.5 hour per ticket update
        return min(8, $ticketActivity * 0.5);
    }
    
    public function toggleShowAllMembers()
    {
        $this->showAllMembers = !$this->showAllMembers;
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.resource-allocation');
    }
}