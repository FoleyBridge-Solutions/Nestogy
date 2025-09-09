<?php

namespace App\Domains\Client\Services;

use App\Models\Client;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClientMetricsService
{
    public function getMetrics(Client $client): array
    {
        return [
            'sla_compliance' => $this->calculateSlaCompliance($client),
            'satisfaction_score' => $this->calculateSatisfactionScore($client),
            'avg_resolution_time' => $this->calculateAvgResolutionTime($client),
            'monthly_revenue' => $this->calculateMonthlyRevenue($client),
            'active_services' => $this->getActiveServices($client),
            'total_monthly_cost' => $this->calculateMonthlyServiceCost($client),
            'ticket_stats' => $this->getTicketStats($client),
            'project_stats' => $this->getProjectStats($client),
        ];
    }

    protected function calculateSlaCompliance(Client $client): float
    {
        $tickets = $client->tickets()
            ->whereNotNull('closed_at')
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();

        if ($tickets->isEmpty()) {
            return 100.0;
        }

        $totalTickets = $tickets->count();
        $slaMetTickets = 0;

        foreach ($tickets as $ticket) {
            $createdAt = Carbon::parse($ticket->created_at);
            $closedAt = Carbon::parse($ticket->closed_at);
            $resolutionHours = $createdAt->diffInHours($closedAt);
            
            // Define SLA based on priority
            $slaHours = match($ticket->priority) {
                'critical' => 4,
                'high' => 8,
                'medium' => 24,
                'low' => 48,
                default => 24,
            };
            
            if ($resolutionHours <= $slaHours) {
                $slaMetTickets++;
            }
        }

        return round(($slaMetTickets / $totalTickets) * 100, 1);
    }

    protected function calculateSatisfactionScore(Client $client): float
    {
        // Check if we have a ticket_ratings table or satisfaction field
        $ratings = DB::table('ticket_ratings')
            ->whereIn('ticket_id', $client->tickets()->pluck('id'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->pluck('rating');

        if ($ratings->isEmpty()) {
            // If no ratings system, use ticket resolution as proxy
            $resolvedTickets = $client->tickets()
                ->where('status', 'closed')
                ->where('created_at', '>=', now()->subMonths(3))
                ->count();
                
            $totalTickets = $client->tickets()
                ->where('created_at', '>=', now()->subMonths(3))
                ->count();
                
            if ($totalTickets === 0) {
                return 0.0;
            }
            
            // Base satisfaction on resolution rate + SLA compliance
            $resolutionRate = ($resolvedTickets / $totalTickets) * 100;
            $slaCompliance = $this->calculateSlaCompliance($client);
            
            return round(($resolutionRate * 0.6 + $slaCompliance * 0.4), 1);
        }

        return round($ratings->avg() * 20, 1); // Convert 5-star to percentage
    }

    protected function calculateAvgResolutionTime(Client $client): string
    {
        $tickets = $client->tickets()
            ->whereNotNull('closed_at')
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();

        if ($tickets->isEmpty()) {
            return 'N/A';
        }

        $totalHours = 0;
        foreach ($tickets as $ticket) {
            $createdAt = Carbon::parse($ticket->created_at);
            $closedAt = Carbon::parse($ticket->closed_at);
            $totalHours += $createdAt->diffInHours($closedAt);
        }

        $avgHours = $totalHours / $tickets->count();

        if ($avgHours < 24) {
            return round($avgHours, 1) . ' hrs';
        } else {
            return round($avgHours / 24, 1) . ' days';
        }
    }

    protected function calculateMonthlyRevenue(Client $client): float
    {
        return $client->invoices()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'paid')
            ->sum('amount');
    }

    protected function getActiveServices(Client $client): array
    {
        $services = [];
        
        // Get services from active contracts
        $contracts = $client->contracts()
            ->where('status', 'active')
            ->with('products')
            ->get();

        foreach ($contracts as $contract) {
            foreach ($contract->products as $product) {
                $services[] = [
                    'name' => $product->name,
                    'quantity' => $product->pivot->quantity ?? 1,
                    'price' => $product->pivot->price ?? $product->price,
                    'billing_cycle' => $product->billing_cycle ?? 'monthly',
                ];
            }
        }

        // If no contracts, try to get from recurring invoices
        if (empty($services)) {
            $recurringItems = DB::table('invoice_items')
                ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->where('invoices.client_id', $client->id)
                ->where('invoices.is_recurring', true)
                ->select('invoice_items.description as name', 'invoice_items.quantity', 'invoice_items.price')
                ->get();

            foreach ($recurringItems as $item) {
                $services[] = [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'billing_cycle' => 'monthly',
                ];
            }
        }

        return $services;
    }

    protected function calculateMonthlyServiceCost(Client $client): float
    {
        $services = $this->getActiveServices($client);
        $totalMonthly = 0;

        foreach ($services as $service) {
            $monthlyAmount = $service['price'] * $service['quantity'];
            
            // Convert to monthly if different billing cycle
            if ($service['billing_cycle'] === 'yearly') {
                $monthlyAmount = $monthlyAmount / 12;
            } elseif ($service['billing_cycle'] === 'quarterly') {
                $monthlyAmount = $monthlyAmount / 3;
            }
            
            $totalMonthly += $monthlyAmount;
        }

        return $totalMonthly;
    }

    protected function getTicketStats(Client $client): array
    {
        $now = now();
        
        return [
            'open' => $client->tickets()->whereIn('status', ['open', 'pending'])->count(),
            'in_progress' => $client->tickets()->where('status', 'in_progress')->count(),
            'closed_this_month' => $client->tickets()
                ->where('status', 'closed')
                ->whereMonth('closed_at', $now->month)
                ->whereYear('closed_at', $now->year)
                ->count(),
            'total' => $client->tickets()->count(),
        ];
    }

    protected function getProjectStats(Client $client): array
    {
        return [
            'active' => $client->projects()->whereIn('status', ['active', 'in_progress'])->count(),
            'completed' => $client->projects()->where('status', 'completed')->count(),
            'on_budget' => $client->projects()
                ->whereNotNull('budget')
                ->whereNotNull('actual_cost')
                ->whereRaw('actual_cost <= budget')
                ->count(),
            'total' => $client->projects()->count(),
        ];
    }
}