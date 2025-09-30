<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Lazy;
use App\Domains\Ticket\Models\Ticket;
use App\Traits\LazyLoadable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

#[Lazy]
class TicketChart extends Component
{
    use LazyLoadable;
    public ?array $chartData = [];
    public string $view = 'status'; // status, priority, category, timeline
    public string $period = 'week'; // week, month, quarter
    public string $ticketFilter = 'current'; // current, all, historical
    public bool $loading = true;
    public array $stats = [];
    
    public function mount()
    {
        $this->trackLoadTime('mount');
        // Initialize with empty array to prevent JSON parse errors
        $this->chartData = [];
        $this->loading = true;
        $this->loadChartData();
    }
    
    #[On('refresh-ticket-chart')]
    public function loadChartData()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;
        
        switch ($this->view) {
            case 'status':
                $this->loadStatusData($companyId);
                break;
                
            case 'priority':
                $this->loadPriorityData($companyId);
                break;
                
            case 'category':
                $this->loadCategoryData($companyId);
                break;
                
            case 'timeline':
                $this->loadTimelineData($companyId);
                break;
        }
        
        $this->calculateStats($companyId);
        $this->loading = false;
    }
    
    protected function loadStatusData($companyId)
    {
        $query = Ticket::where('company_id', $companyId);
        
        // Apply ticket filter using model constants
        if ($this->ticketFilter === 'current') {
            // Current tickets: only active statuses
            $query->whereIn('status', Ticket::ACTIVE_STATUSES);
        } elseif ($this->ticketFilter === 'historical') {
            // Historical tickets: only closed, resolved, and cancelled
            $query->whereIn('status', Ticket::HISTORICAL_STATUSES);
        }
        // 'all' filter shows everything - no additional filtering
        
        $statusCounts = $query->selectRaw('LOWER(status) as status, count(*) as count')
            ->groupBy('status')
            ->get();
            
        $data = [];
        $statusConfig = [
            'open' => ['label' => 'Open', 'color' => 'blue', 'order' => 1],
            'in-progress' => ['label' => 'In Progress', 'color' => 'yellow', 'order' => 2],
            'in_progress' => ['label' => 'In_progress', 'color' => 'yellow', 'order' => 2], // Handle underscore variant
            'waiting' => ['label' => 'Waiting', 'color' => 'orange', 'order' => 3],
            'on-hold' => ['label' => 'On Hold', 'color' => 'purple', 'order' => 4],
            'resolved' => ['label' => 'Resolved', 'color' => 'green', 'order' => 5],
            'closed' => ['label' => 'Closed', 'color' => 'gray', 'order' => 6],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'red', 'order' => 7],
            'canceled' => ['label' => 'Cancelled', 'color' => 'red', 'order' => 7], // Handle alternate spelling
        ];
        
        foreach ($statusCounts as $status) {
            $statusKey = strtolower($status->status);
            $config = $statusConfig[$statusKey] ?? ['label' => ucfirst($status->status), 'color' => 'gray', 'order' => 99];
            $data[] = [
                'name' => $config['label'],
                'value' => (int)$status->count,
                'color' => $config['color'],
                'order' => $config['order'],
            ];
        }
        
        // Sort by predefined order and set chartData
        if (!empty($data)) {
            usort($data, function($a, $b) {
                return $a['order'] <=> $b['order'];
            });
            $this->chartData = $data;
        } else {
            $this->chartData = [];
        }
    }
    
    protected function loadPriorityData($companyId)
    {
        $query = Ticket::where('company_id', $companyId)
            ->whereIn('status', ['open', 'in-progress', 'waiting']);
        
        
        $priorityCounts = $query->selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->get();
            
        $data = [];
        $priorityConfig = [
            'critical' => ['label' => 'Critical', 'color' => 'red', 'order' => 1],
            'high' => ['label' => 'High', 'color' => 'orange', 'order' => 2],
            'medium' => ['label' => 'Medium', 'color' => 'yellow', 'order' => 3],
            'low' => ['label' => 'Low', 'color' => 'green', 'order' => 4],
        ];
        
        foreach ($priorityCounts as $priority) {
            $config = $priorityConfig[$priority->priority] ?? ['label' => ucfirst($priority->priority), 'color' => 'gray', 'order' => 99];
            $data[] = [
                'name' => $config['label'],
                'value' => (int)$priority->count,
                'color' => $config['color'],
                'order' => $config['order'],
            ];
        }
        
        if (!empty($data)) {
            usort($data, function($a, $b) {
                return $a['order'] <=> $b['order'];
            });
            $this->chartData = $data;
        } else {
            $this->chartData = [];
        }
    }
    
    protected function loadCategoryData($companyId)
    {
        $query = Ticket::where('company_id', $companyId);
        
        // Apply ticket filter using model constants
        if ($this->ticketFilter === 'current') {
            $query->whereIn('status', Ticket::ACTIVE_STATUSES);
        } elseif ($this->ticketFilter === 'historical') {
            $query->whereIn('status', Ticket::HISTORICAL_STATUSES);
        }
        
        $categoryCounts = $query->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(8)
            ->get();
            
        $data = [];
        $colors = ['blue', 'green', 'purple', 'orange', 'pink', 'teal', 'indigo', 'yellow'];
        
        foreach ($categoryCounts as $index => $category) {
            $data[] = [
                'name' => $category->category ?: 'Uncategorized',
                'value' => (int)$category->count,
                'color' => $colors[$index] ?? 'gray',
            ];
        }

        $this->chartData = !empty($data) ? $data : [];
    }
    
    protected function loadTimelineData($companyId)
    {
        $days = $this->period === 'week' ? 7 : ($this->period === 'month' ? 30 : 90);
        $startDate = Carbon::now()->subDays($days - 1);
        $endDate = Carbon::now();
        
        // Get all created tickets in one query
        $createdTickets = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();
        
        // Get all resolved tickets in one query
        $resolvedTickets = Ticket::where('company_id', $companyId)
            ->whereIn('status', ['resolved', 'closed'])
            ->whereBetween('updated_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();
        
        // Build the data array
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            
            $data[] = [
                'date' => $date,
                'created' => (int)($createdTickets[$date] ?? 0),
                'resolved' => (int)($resolvedTickets[$date] ?? 0),
            ];
        }
        
        // Only set chartData if we have meaningful data
        $this->chartData = !empty($data) ? $data : [];
    }
    
    protected function calculateStats($companyId)
    {
        $baseQuery = Ticket::where('company_id', $companyId);
        
        // Apply ticket filter for main stats using model constants
        if ($this->ticketFilter === 'current') {
            $query = (clone $baseQuery)->whereIn('status', Ticket::ACTIVE_STATUSES);
        } elseif ($this->ticketFilter === 'historical') {
            $query = (clone $baseQuery)->whereIn('status', Ticket::HISTORICAL_STATUSES);
        } else {
            $query = clone $baseQuery;
        }
        
        // Total tickets (filtered)
        $totalTickets = $query->count();
        
        // Open tickets (only relevant for current and all)
        $openTickets = $this->ticketFilter !== 'historical' 
            ? (clone $baseQuery)->whereIn('status', ['open', 'Open', 'in-progress', 'In-Progress', 'In Progress', 'in_progress', 'In_progress', 'waiting', 'Waiting'])->count()
            : 0;
        
        // In progress tickets (only relevant for current and all)
        $inProgressTickets = $this->ticketFilter !== 'historical'
            ? (clone $baseQuery)->whereIn('status', ['in-progress', 'In-Progress', 'In Progress', 'in_progress', 'In_progress'])->count()
            : 0;
        
        // Critical tickets (always from active tickets only)
        $criticalTickets = (clone $baseQuery)
            ->whereIn('priority', ['critical', 'Critical'])
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->count();
        
        // Average resolution time (in hours) - fallback if resolved_at doesn't exist
        try {
            $avgResolution = (clone $query)
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(EXTRACT(epoch FROM (resolved_at - created_at)) / 3600) as avg_hours')
                ->first()
                ->avg_hours ?? 0;
        } catch (\Exception $e) {
            // Fallback: use updated_at for closed/resolved tickets
            $avgResolution = (clone $query)
                ->whereIn('status', ['Resolved', 'Closed'])
                ->selectRaw('AVG(EXTRACT(epoch FROM (updated_at - created_at)) / 3600) as avg_hours')
                ->first()
                ->avg_hours ?? 0;
        }
        
        // Today's tickets (always from base query - shows all new tickets today)
        $todaysTickets = (clone $baseQuery)->whereDate('created_at', today())->count();
        
        // Resolved tickets (always count from base query for accurate stats)
        $resolvedTickets = (clone $baseQuery)->whereIn('status', Ticket::HISTORICAL_STATUSES)->count();
        
        // Resolution rate (based on filtered tickets if not historical view)
        $resolutionRate = $this->ticketFilter === 'historical' && $totalTickets > 0 
            ? 100 // All historical tickets are resolved by definition
            : ($totalTickets > 0 ? round(($resolvedTickets / ((clone $baseQuery)->count())) * 100, 1) : 0);
        
        $this->stats = [
            'total' => $totalTickets,
            'open' => $openTickets,
            'in_progress' => $inProgressTickets,
            'resolved' => $resolvedTickets,
            'critical' => $criticalTickets,
            'avgResolution' => round($avgResolution, 1),
            'today' => $todaysTickets,
            'resolutionRate' => $resolutionRate,
        ];
    }
    
    /**
     * Livewire lifecycle hook for when view property changes
     */
    public function updatedView($value)
    {
        if (in_array($value, ['status', 'priority', 'category', 'timeline'])) {
            $this->loadChartData();
        }
    }
    
    /**
     * Livewire lifecycle hook for when period property changes
     */
    public function updatedPeriod($value)
    {
        if (in_array($value, ['week', 'month', 'quarter'])) {
            $this->loadChartData();
        }
    }
    
    /**
     * Livewire lifecycle hook for when ticketFilter property changes
     */
    public function updatedTicketFilter($value)
    {
        if (in_array($value, ['current', 'all', 'historical'])) {
            $this->loadChartData();
        }
    }
    
    public function render()
    {
        return view('livewire.dashboard.widgets.ticket-chart');
    }
}