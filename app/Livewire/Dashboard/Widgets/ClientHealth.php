<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Lazy;
use App\Models\Client;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Invoice;
use App\Models\Payment;
use App\Traits\LazyLoadable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

#[Lazy]
class ClientHealth extends Component
{
    use LazyLoadable;
    public Collection $clients;
    public bool $loading = true;
    public string $sortBy = 'health_score'; // health_score, name, revenue, tickets
    public string $sortDirection = 'desc';
    public string $filter = 'all'; // all, at_risk, healthy, critical
    public int $limit = 10;
    
    public function mount()
    {
        $this->clients = collect();
        $this->loadClientHealth();
    }
    
    #[On('refresh-client-health')]
    public function loadClientHealth()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;
        
        $clients = Client::where('company_id', $companyId)
            ->where('status', 'active')
            ->with(['tickets', 'invoices', 'payments'])
            ->get()
            ->map(function ($client) {
                return $this->calculateHealthMetrics($client);
            });
        
        // Apply filter
        if ($this->filter !== 'all') {
            $clients = $clients->filter(function ($client) {
                return match($this->filter) {
                    'at_risk' => $client['health_status'] === 'at_risk',
                    'healthy' => $client['health_status'] === 'healthy',
                    'critical' => $client['health_status'] === 'critical',
                    default => true
                };
            });
        }
        
        // Sort clients
        $clients = $clients->sortBy($this->sortBy, SORT_REGULAR, $this->sortDirection === 'desc');
        
        $this->clients = $clients->take($this->limit);
        $this->loading = false;
    }
    
    protected function calculateHealthMetrics($client)
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);
        
        // Ticket metrics
        $recentTickets = $client->tickets()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->get();
            
        $openTickets = $client->tickets()
            ->whereIn('status', ['open', 'in-progress', 'waiting'])
            ->count();
            
        $criticalTickets = $client->tickets()
            ->where('priority', 'critical')
            ->whereIn('status', ['open', 'in-progress'])
            ->count();
            
        // Average resolution time - fallback if resolved_at doesn't exist
        try {
            $avgResolutionTime = $client->tickets()
                ->whereNotNull('resolved_at')
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->selectRaw('AVG(EXTRACT(epoch FROM (resolved_at - created_at)) / 3600) as avg_hours')
                ->first()
                ->avg_hours ?? 0;
        } catch (\Exception $e) {
            // Fallback: use updated_at for resolved/closed tickets
            $avgResolutionTime = $client->tickets()
                ->whereIn('status', ['resolved', 'closed'])
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->selectRaw('AVG(EXTRACT(epoch FROM (updated_at - created_at)) / 3600) as avg_hours')
                ->first()
                ->avg_hours ?? 0;
        }
        
        // Financial metrics
        $monthlyRevenue = $client->payments()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->sum('amount');
            
        $previousMonthRevenue = $client->payments()
            ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
            ->sum('amount');
            
        $overdueAmount = $client->invoices()
            ->where('status', 'sent')
            ->where('due_date', '<', $now)
            ->sum('amount');
            
        $paymentDelay = $client->payments()
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('AVG(payments.created_at::date - invoices.due_date::date) as avg_delay')
            ->first()
            ->avg_delay ?? 0;
        
        // Activity metrics
        $lastTicket = $client->tickets()
            ->orderBy('created_at', 'desc')
            ->first();
            
        $daysSinceLastTicket = $lastTicket ? 
            abs($lastTicket->created_at->diffInDays($now)) : 999;
            
        $lastPayment = $client->payments()
            ->orderBy('created_at', 'desc')
            ->first();
            
        $daysSinceLastPayment = $lastPayment ? 
            abs($lastPayment->created_at->diffInDays($now)) : 999;
        
        // Calculate health score (0-100)
        $healthScore = 100;
        
        // Deduct for critical tickets
        $healthScore -= $criticalTickets * 10;
        
        // Deduct for open tickets
        $healthScore -= min($openTickets * 2, 20);
        
        // Deduct for overdue payments
        if ($overdueAmount > 0) {
            $healthScore -= min(($overdueAmount / 1000) * 5, 25);
        }
        
        // Deduct for payment delays
        if ($paymentDelay > 0) {
            $healthScore -= min($paymentDelay * 2, 15);
        }
        
        // Deduct for inactivity
        if ($daysSinceLastTicket > 60) {
            $healthScore -= 10;
        }
        
        // Deduct for declining revenue
        if ($previousMonthRevenue > 0 && $monthlyRevenue < $previousMonthRevenue * 0.8) {
            $healthScore -= 15;
        }
        
        // Ensure score is between 0 and 100
        $healthScore = max(0, min(100, $healthScore));
        
        // Determine health status
        $healthStatus = match(true) {
            $healthScore >= 80 => 'healthy',
            $healthScore >= 60 => 'stable',
            $healthScore >= 40 => 'at_risk',
            default => 'critical'
        };
        
        // Determine trend
        $trend = 'stable';
        if ($monthlyRevenue > $previousMonthRevenue * 1.1) {
            $trend = 'improving';
        } elseif ($monthlyRevenue < $previousMonthRevenue * 0.9) {
            $trend = 'declining';
        }
        
        return [
            'id' => $client->id,
            'name' => $client->name,
            'health_score' => $healthScore,
            'health_status' => $healthStatus,
            'trend' => $trend,
            'open_tickets' => $openTickets,
            'critical_tickets' => $criticalTickets,
            'avg_resolution_time' => round($avgResolutionTime, 1),
            'monthly_revenue' => $monthlyRevenue,
            'revenue_change' => $previousMonthRevenue > 0 ? 
                round((($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 1) : 0,
            'overdue_amount' => $overdueAmount,
            'days_since_contact' => (int) min($daysSinceLastTicket, $daysSinceLastPayment),
            'client' => $client,
        ];
    }
    
    public function sort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
        
        $this->loadClientHealth();
    }
    
    /**
     * Livewire lifecycle hook for when filter property changes
     */
    public function updatedFilter($value)
    {
        $this->loadClientHealth();
    }
    
    public function loadMore()
    {
        $this->limit += 10;
        $this->loadClientHealth();
    }
    
    public function render()
    {
        return view('livewire.dashboard.widgets.client-health');
    }
}