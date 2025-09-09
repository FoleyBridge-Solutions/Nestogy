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
        
        $statusCounts = $query->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();
            
        $data = [];
        $statusConfig = [
            'open' => ['label' => 'Open', 'color' => 'blue', 'order' => 1],
            'in-progress' => ['label' => 'In Progress', 'color' => 'yellow', 'order' => 2],
            'waiting' => ['label' => 'Waiting', 'color' => 'orange', 'order' => 3],
            'on-hold' => ['label' => 'On Hold', 'color' => 'purple', 'order' => 4],
            'resolved' => ['label' => 'Resolved', 'color' => 'green', 'order' => 5],
            'closed' => ['label' => 'Closed', 'color' => 'gray', 'order' => 6],
        ];
        
        foreach ($statusCounts as $status) {
            $config = $statusConfig[$status->status] ?? ['label' => ucfirst($status->status), 'color' => 'gray', 'order' => 99];
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
        $data = [];
        $days = $this->period === 'week' ? 7 : ($this->period === 'month' ? 30 : 90);
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            
            $query = Ticket::where('company_id', $companyId)
                ->whereDate('created_at', $date);
                
            
            $created = clone $query;
            // For resolved tickets, use updated_at with status check
            $resolved = Ticket::where('company_id', $companyId)
                ->whereIn('status', ['resolved', 'closed'])
                ->whereDate('updated_at', $date);
                
            
            $data[] = [
                'date' => $date->toDateString(),
                'created' => (int)$created->count(),
                'resolved' => (int)$resolved->count(),
            ];
        }
        
        // Only set chartData if we have meaningful data
        $this->chartData = !empty($data) ? $data : [];
    }
    
    protected function calculateStats($companyId)
    {
        $query = Ticket::where('company_id', $companyId);
        
        
        // Total tickets
        $totalTickets = (clone $query)->count();
        
        // Open tickets
        $openTickets = (clone $query)->whereIn('status', ['open', 'in-progress', 'waiting'])->count();
        
        // In progress tickets
        $inProgressTickets = (clone $query)->where('status', 'in-progress')->count();
        
        // Critical tickets
        $criticalTickets = (clone $query)
            ->where('priority', 'critical')
            ->whereIn('status', ['open', 'in-progress'])
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
        
        // Today's tickets
        $todaysTickets = (clone $query)->whereDate('created_at', today())->count();
        
        // Resolution rate
        $resolvedTickets = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();
        $resolutionRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0;
        
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
    
    public function render()
    {
        return view('livewire.dashboard.widgets.ticket-chart');
    }
}