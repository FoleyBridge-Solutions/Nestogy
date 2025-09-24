<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Lazy;
use App\Models\Invoice;
use App\Models\User;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Client;
use App\Models\Payment;
use App\Traits\LazyLoadable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

#[Lazy]
class KpiGrid extends Component  
{
    use LazyLoadable;
    
    public array $kpis = [];
    public bool $loading = true;
    public string $period = 'month'; // month, quarter, year, all
    protected ?string $revenueRecognitionMethod = null;
    
    public function mount(string $period = 'month')
    {
        if (in_array($period, ['month', 'quarter', 'year', 'all'], true)) {
            $this->period = $period;
        }
        $this->trackLoadTime('mount');
        $this->loadKpis();
    }
    
    #[On('set-kpi-period')]
    public function setPeriod(string $period): void
    {
        if (!in_array($period, ['month', 'quarter', 'year', 'all'], true)) {
            return;
        }

        if ($this->period !== $period) {
            $this->period = $period;
            $this->loadKpis();
        }
    }

    #[On('dashboard-data-loaded')]
    public function handleDataLoad($data)
    {
        if (isset($data['kpis'])) {
            $this->kpis = $data['kpis'];
            $this->loading = false;
        }
    }
    
    #[On('refresh-kpi-grid')]
    public function loadKpis()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;
        $baseQuery = ['company_id' => $companyId];
        $method = $this->getRevenueRecognitionMethod();

        // Get date range based on selected period
        [$startDate, $endDate, $previousStartDate, $previousEndDate] = $this->getDateRanges();

        // Calculate revenue based on recognition method
        if ($method === 'cash') {
            $totalRevenue = $this->sumPaymentsBetween($companyId, $startDate, $endDate);
            $previousRevenue = $this->sumPaymentsBetween($companyId, $previousStartDate, $previousEndDate);
        } else {
            $totalRevenue = Invoice::where($baseQuery)
                ->where('status', Invoice::STATUS_PAID)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            $previousRevenue = Invoice::where($baseQuery)
                ->where('status', Invoice::STATUS_PAID)
                ->whereBetween('date', [$previousStartDate, $previousEndDate])
                ->sum('amount');
        }

        $revenueChange = $this->calculatePercentageChange($totalRevenue, $previousRevenue);
            
        // Pending Invoices
        $pendingInvoices = Invoice::where($baseQuery)
            ->whereIn('status', ['draft', 'sent'])
            ->sum('amount');
        $pendingCount = Invoice::where($baseQuery)
            ->whereIn('status', ['draft', 'sent'])
            ->count();
            
        // Active Clients - with actual change calculation
        $activeClients = Client::where('company_id', $companyId)
            ->where('status', 'active')
            ->count();
        
        $newClientsThisPeriod = Client::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
                
        // Open Tickets
        $openTickets = Ticket::where($baseQuery)
            ->whereIn('status', ['open', 'in_progress', 'waiting'])
            ->count();
            
        // Critical Tickets
        $criticalTickets = Ticket::where($baseQuery)
            ->where('priority', 'critical')
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
            
        // Overdue Invoices (current as of end date)
        $overdueInvoices = Invoice::where($baseQuery)
            ->whereIn('status', ['overdue', 'Overdue'])
            ->where('due_date', '<', $endDate)
            ->sum('amount');

        // Previous period's overdue for comparison
        $previousOverdue = Invoice::where($baseQuery)
            ->whereIn('status', ['overdue', 'Overdue'])
            ->where('due_date', '<', $previousEndDate)
            ->sum('amount');
        
        $overdueChange = $this->calculatePercentageChange($overdueInvoices, $previousOverdue);
            
        // Average Resolution Time with trend
        $avgResolutionHours = $this->calculateAverageResolutionTime($companyId, $startDate, $endDate);
        $previousAvgResolution = $this->calculateAverageResolutionTime($companyId, $previousStartDate, $previousEndDate);
        $resolutionChange = round($avgResolutionHours - $previousAvgResolution, 1);

        // Customer Satisfaction with trend
        $satisfaction = $this->calculateCustomerSatisfaction($companyId, $startDate, $endDate);
        $previousSatisfaction = $this->calculateCustomerSatisfaction($companyId, $previousStartDate, $previousEndDate);
        $satisfactionChange = round($satisfaction - $previousSatisfaction, 1);
        
        // Team Utilization
        $utilization = $this->calculateTeamUtilization($companyId);
        
        $periodLabel = match($this->period) {
            'quarter' => 'Quarterly',
            'year' => 'Yearly',
            'all' => 'Total',
            default => 'Monthly'
        };
        
        $comparisonLabel = match($this->period) {
            'quarter' => 'vs last quarter',
            'year' => 'vs last year',
            'all' => 'all time',
            default => 'vs last month'
        };
        
        $kpis = [
            [
                'label' => $periodLabel . ' Revenue',
                'value' => $totalRevenue,
                'format' => 'currency',
                'icon' => 'currency-dollar',
                'color' => 'green',
                'trend' => $revenueChange >= 0 ? 'up' : 'down',
                'trendValue' => ($revenueChange >= 0 ? '+' : '') . $revenueChange . '%',
                'description' => $comparisonLabel,
                'previousValue' => $previousRevenue,
            ],
            [
                'label' => 'Pending Invoices',
                'value' => $pendingInvoices,
                'format' => 'currency',
                'icon' => 'document-text',
                'color' => 'blue',
                'trend' => 'stable',
                'trendValue' => $pendingCount . ' invoices',
                'description' => 'awaiting payment'
            ],
            [
                'label' => 'Active Clients',
                'value' => $activeClients,
                'format' => 'number',
                'icon' => 'user-group',
                'color' => 'purple',
                'trend' => $newClientsThisPeriod > 0 ? 'up' : 'stable',
                'trendValue' => $newClientsThisPeriod > 0 ? '+' . $newClientsThisPeriod : '0',
                'description' => 'new this ' . ($this->period === 'all' ? 'period' : $this->period)
            ],
            [
                'label' => 'Open Tickets',
                'value' => $openTickets,
                'format' => 'number',
                'icon' => 'ticket',
                'color' => 'orange',
                'trend' => $criticalTickets > 0 ? 'warning' : 'stable',
                'trendValue' => $criticalTickets . ' critical',
                'description' => 'requiring attention'
            ],
            [
                'label' => 'Overdue Amount',
                'value' => $overdueInvoices,
                'format' => 'currency',
                'icon' => 'exclamation-triangle',
                'color' => 'red',
                'trend' => $overdueChange < 0 ? 'down' : ($overdueChange > 0 ? 'up' : 'stable'),
                'trendValue' => ($overdueChange >= 0 ? '+' : '') . $overdueChange . '%',
                'description' => $comparisonLabel,
                'previousValue' => $previousOverdue,
            ],
            [
                'label' => 'Avg Resolution',
                'value' => $avgResolutionHours,
                'format' => 'hours',
                'icon' => 'clock',
                'color' => 'indigo',
                'trend' => $resolutionChange < 0 ? 'up' : ($resolutionChange > 0 ? 'down' : 'stable'),
                'trendValue' => ($resolutionChange > 0 ? '+' : '') . $resolutionChange . ' hrs',
                'description' => $comparisonLabel,
                'previousValue' => $previousAvgResolution,
            ],
            [
                'label' => 'Satisfaction',
                'value' => $satisfaction,
                'format' => 'rating',
                'icon' => 'star',
                'color' => 'yellow',
                'trend' => $satisfactionChange > 0 ? 'up' : ($satisfactionChange < 0 ? 'down' : 'stable'),
                'trendValue' => ($satisfactionChange >= 0 ? '+' : '') . $satisfactionChange,
                'description' => 'out of 5.0',
                'previousValue' => $previousSatisfaction,
            ],
            [
                'label' => 'Team Utilization',
                'value' => $utilization,
                'format' => 'percentage',
                'icon' => 'chart-bar',
                'color' => 'teal',
                'trend' => $utilization > 80 ? 'warning' : ($utilization < 40 ? 'low' : 'stable'),
                'trendValue' => $utilization . '%',
                'description' => 'capacity used'
            ],
        ];
        
        $this->kpis = $kpis;
        $this->loading = false;
    }

    protected function getRevenueRecognitionMethod(): string
    {
        if ($this->revenueRecognitionMethod !== null) {
            return $this->revenueRecognitionMethod;
        }

        $settings = optional(Auth::user()->company->setting);
        $method = data_get($settings?->revenue_recognition_settings, 'method');

        return $this->revenueRecognitionMethod = in_array($method, ['cash', 'accrual'], true) ? $method : 'accrual';
    }

    protected function sumPaymentsBetween(int $companyId, Carbon $startDate, Carbon $endDate): float
    {
        return Payment::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereNotNull('payment_date')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
    }

    protected function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function calculateAverageResolutionTime($companyId, $startDate = null, $endDate = null)
    {
        try {
            $date = $date ?? now();
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            // Calculate average resolution time from resolved tickets
            $avgTime = Ticket::where('company_id', $companyId)
                ->whereIn('status', ['resolved', 'closed'])
                ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>', 'created_at')
                ->selectRaw('AVG(EXTRACT(epoch FROM (resolved_at - created_at)) / 3600) as avg_hours')
                ->first()
                ->avg_hours ?? 0;

            return round($avgTime, 1);
        } catch (\Exception $e) {
            // Fallback: use updated_at if resolved_at doesn't exist
            try {
                if (!$startDate || !$endDate) {
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfMonth();
                }
                
                $avgTime = Ticket::where('company_id', $companyId)
                    ->whereIn('status', ['resolved', 'closed'])
                    ->whereBetween('updated_at', [$startDate, $endDate])
                    ->selectRaw('AVG(EXTRACT(epoch FROM (updated_at - created_at)) / 3600) as avg_hours')
                    ->first()
                    ->avg_hours ?? 0;

                return round($avgTime, 1);
            } catch (\Exception $e2) {
                return 0;
            }
        }
    }

    protected function calculateCustomerSatisfaction($companyId, $startDate = null, $endDate = null)
    {
        try {
            if (!$startDate || !$endDate) {
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
            }
            
            // Get resolved tickets in the period
            $resolvedTickets = Ticket::where('company_id', $companyId)
                ->whereIn('status', ['resolved', 'closed'])
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->get();
            
            if ($resolvedTickets->count() === 0) {
                return 0;
            }
            
            // Calculate average satisfaction score based on resolution time
            $totalScore = 0;
            foreach ($resolvedTickets as $ticket) {
                $resolutionTime = $ticket->created_at->diffInHours($ticket->updated_at);
                $score = $this->calculateTicketSatisfactionScore($ticket, $resolutionTime);
                $totalScore += $score;
            }
            
            $averageScore = $totalScore / $resolvedTickets->count();
            return round($averageScore, 1);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    protected function calculateTicketSatisfactionScore($ticket, $resolutionTime)
    {
        // Simple scoring algorithm based on resolution time and ticket priority
        $baseScore = 5.0;

        // Deduct points for longer resolution times
        if ($resolutionTime > 24) {
            $baseScore -= 1.0;
        } elseif ($resolutionTime > 8) {
            $baseScore -= 0.5;
        }

        // Deduct points for high priority tickets that took longer
        if (strtolower($ticket->priority) === 'critical' && $resolutionTime > 4) {
            $baseScore -= 0.5;
        } elseif (strtolower($ticket->priority) === 'high' && $resolutionTime > 12) {
            $baseScore -= 0.5;
        }

        return max(1.0, min(5.0, $baseScore));
    }

    protected function calculateTeamUtilization($companyId)
    {
        try {
            // Calculate team utilization based on active tickets vs team capacity
            $activeTickets = Ticket::where('company_id', $companyId)
                ->whereIn('status', ['open', 'in_progress', 'waiting'])
                ->count();

            // Get number of active technicians
            $activeTechnicians = User::where('company_id', $companyId)
                ->where('status', true)
                ->whereHas('roles', function($query) {
                    $query->whereIn('name', ['technician', 'manager', 'admin']);
                })
                ->count();

            if ($activeTechnicians === 0) {
                return 0;
            }

            // Assume each technician can handle 5 active tickets (simplified capacity model)
            $capacity = $activeTechnicians * 5;
            $utilization = min(100, ($activeTickets / $capacity) * 100);

            return round($utilization, 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function getDateRanges()
    {
        $now = now();
        
        switch ($this->period) {
            case 'quarter':
                $startDate = $now->copy()->startOfQuarter();
                $endDate = $now->copy()->endOfQuarter();
                $previousStartDate = $now->copy()->subQuarter()->startOfQuarter();
                $previousEndDate = $now->copy()->subQuarter()->endOfQuarter();
                break;
                
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $previousStartDate = $now->copy()->subYear()->startOfYear();
                $previousEndDate = $now->copy()->subYear()->endOfYear();
                break;
                
            case 'all':
                $startDate = Carbon::parse('2000-01-01'); // Or company creation date
                $endDate = $now->copy()->endOfDay();
                $previousStartDate = Carbon::parse('1999-01-01'); // Will result in 0 for comparison
                $previousEndDate = Carbon::parse('1999-12-31');
                break;
                
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $previousStartDate = $now->copy()->subMonth()->startOfMonth();
                $previousEndDate = $now->copy()->subMonth()->endOfMonth();
                break;
        }
        
        return [$startDate, $endDate, $previousStartDate, $previousEndDate];
    }
    
    public function render()
    {
        return view('livewire.dashboard.widgets.kpi-grid');
    }
}
