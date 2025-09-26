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
    public int $limit = 7;
    public int $loadCount = 0;
    public ?array $selectedClientDetails = null;
    public bool $showScoreModal = false;
    
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
        $this->loadCount++;
        
        // Progressive loading: 3 → 10 → 20 → 30...
        if ($this->loadCount === 1) {
            $this->limit = 10;  // First load: show 10 total
        } elseif ($this->loadCount === 2) {
            $this->limit = 20;  // Second load: show 20 total
        } else {
            $this->limit += 10; // Subsequent loads: add 10 more
        }
        
        $this->loadClientHealth();
    }
    
    public function showScoreDetails($clientId)
    {
        $client = $this->clients->firstWhere('id', $clientId);
        if ($client) {
            $this->selectedClientDetails = $this->getDetailedScoreBreakdown($client);
            $this->showScoreModal = true;
            \Log::info('Modal opened for client: ' . $client['name']);
        } else {
            \Log::warning('Client not found with ID: ' . $clientId);
        }
    }
    
    public function closeScoreModal()
    {
        $this->showScoreModal = false;
        $this->selectedClientDetails = null;
    }
    
    protected function getDetailedScoreBreakdown($clientData)
    {
        $breakdown = [
            'client_name' => $clientData['name'],
            'total_score' => $clientData['health_score'],
            'health_status' => $clientData['health_status'],
            'base_score' => 100,
            'deductions' => [],
            'metrics' => [
                'critical_tickets' => $clientData['critical_tickets'],
                'open_tickets' => $clientData['open_tickets'],
                'avg_resolution_time' => $clientData['avg_resolution_time'],
                'monthly_revenue' => $clientData['monthly_revenue'],
                'revenue_change' => $clientData['revenue_change'],
                'overdue_amount' => $clientData['overdue_amount'],
                'days_since_contact' => $clientData['days_since_contact'],
            ]
        ];
        
        // Critical tickets deduction
        if ($clientData['critical_tickets'] > 0) {
            $deduction = $clientData['critical_tickets'] * 10;
            $breakdown['deductions'][] = [
                'reason' => 'Critical Tickets',
                'detail' => $clientData['critical_tickets'] . ' critical ticket(s) open',
                'calculation' => $clientData['critical_tickets'] . ' × 10 points',
                'amount' => $deduction,
                'icon' => 'exclamation-triangle',
                'color' => 'red'
            ];
        }
        
        // Open tickets deduction
        if ($clientData['open_tickets'] > 0) {
            $deduction = min($clientData['open_tickets'] * 2, 20);
            $breakdown['deductions'][] = [
                'reason' => 'Open Tickets',
                'detail' => $clientData['open_tickets'] . ' open ticket(s)',
                'calculation' => 'Min(' . $clientData['open_tickets'] . ' × 2, 20) points',
                'amount' => $deduction,
                'icon' => 'ticket',
                'color' => 'orange'
            ];
        }
        
        // Overdue payments deduction
        if ($clientData['overdue_amount'] > 0) {
            $deduction = min(($clientData['overdue_amount'] / 1000) * 5, 25);
            $breakdown['deductions'][] = [
                'reason' => 'Overdue Payments',
                'detail' => '$' . number_format($clientData['overdue_amount'], 2) . ' overdue',
                'calculation' => 'Min($' . number_format($clientData['overdue_amount']/1000, 1) . 'k × 5, 25) points',
                'amount' => round($deduction, 1),
                'icon' => 'currency-dollar',
                'color' => 'red'
            ];
        }
        
        // Get the actual client model for payment delay calculation
        $client = $clientData['client'];
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        
        $paymentDelay = $client->payments()
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('AVG(payments.created_at::date - invoices.due_date::date) as avg_delay')
            ->first()
            ->avg_delay ?? 0;
            
        // Payment delays deduction
        if ($paymentDelay > 0) {
            $deduction = min($paymentDelay * 2, 15);
            $breakdown['deductions'][] = [
                'reason' => 'Payment Delays',
                'detail' => 'Average ' . round($paymentDelay) . ' days late',
                'calculation' => 'Min(' . round($paymentDelay) . ' × 2, 15) points',
                'amount' => round($deduction, 1),
                'icon' => 'clock',
                'color' => 'yellow'
            ];
        }
        
        // Inactivity deduction
        if ($clientData['days_since_contact'] > 60) {
            $breakdown['deductions'][] = [
                'reason' => 'Inactivity',
                'detail' => 'No contact for ' . $clientData['days_since_contact'] . ' days',
                'calculation' => 'Fixed 10 points (>60 days)',
                'amount' => 10,
                'icon' => 'user-minus',
                'color' => 'gray'
            ];
        }
        
        // Declining revenue deduction
        if ($clientData['revenue_change'] < -20) {
            $breakdown['deductions'][] = [
                'reason' => 'Declining Revenue',
                'detail' => number_format(abs($clientData['revenue_change']), 1) . '% revenue decrease',
                'calculation' => 'Fixed 15 points (>20% decline)',
                'amount' => 15,
                'icon' => 'arrow-trending-down',
                'color' => 'red'
            ];
        }
        
        // Calculate total deductions
        $breakdown['total_deductions'] = array_sum(array_column($breakdown['deductions'], 'amount'));
        
        return $breakdown;
    }
    
    public function render()
    {
        return view('livewire.dashboard.widgets.client-health');
    }
}