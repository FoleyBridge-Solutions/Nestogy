<?php

namespace App\Domains\Client\Services;

use App\Models\Client;
use App\Domains\Ticket\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClientMetricsService
{
    protected $metricsCache = [];

    public function getMetrics(Client $client): array
    {
        // Use request-level cache to avoid duplicate queries within the same request
        $cacheKey = 'client_metrics_'.$client->id.'_'.request()->fingerprint();

        return Cache::remember($cacheKey, 1, function () use ($client) {
            // Store calculated values to avoid recalculating in other methods
            $this->metricsCache[$client->id] = [
                'sla_compliance' => $this->calculateSlaCompliance($client),
                'satisfaction_score' => $this->calculateSatisfactionScore($client),
                'avg_resolution_time' => $this->calculateAvgResolutionTime($client),
                'monthly_revenue' => $this->calculateMonthlyRevenue($client),
                'active_services' => $this->getActiveServices($client),
                'ticket_stats' => $this->getTicketStats($client),
                'project_stats' => $this->getProjectStats($client),
            ];

            // Calculate total monthly cost after services are fetched
            $this->metricsCache[$client->id]['total_monthly_cost'] = $this->calculateMonthlyServiceCost(
                $client,
                $this->metricsCache[$client->id]['active_services']
            );

            return $this->metricsCache[$client->id];
        });
    }

    protected function calculateSlaCompliance(Client $client): float
    {
        // Check cache first to avoid duplicate ticket queries
        if (isset($this->metricsCache[$client->id]['tickets_for_sla'])) {
            $tickets = $this->metricsCache[$client->id]['tickets_for_sla'];
        } else {
            $tickets = $client->tickets()
                ->whereNotNull('closed_at')
                ->where('created_at', '>=', now()->subMonths(3))
                ->select('id', 'created_at', 'closed_at', 'priority')
                ->get();

            $this->metricsCache[$client->id]['tickets_for_sla'] = $tickets;
        }

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
            $slaHours = match ($ticket->priority) {
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
        // Use cached ticket IDs to avoid duplicate query
        $ticketIds = Cache::remember('client_ticket_ids_'.$client->id, 1, function () use ($client) {
            return $client->tickets()->pluck('id');
        });

        // Check if we have a ticket_ratings table or satisfaction field
        $ratings = DB::table('ticket_ratings')
            ->whereIn('ticket_id', $ticketIds)
            ->where('created_at', '>=', now()->subMonths(6))
            ->pluck('rating');

        if ($ratings->isEmpty()) {
            // If no ratings system, use ticket resolution as proxy
            // Use a single query with conditional counting
            $ticketCounts = DB::selectOne("
                SELECT 
                    COUNT(CASE WHEN status = 'closed' AND created_at >= ? THEN 1 END) as resolved_tickets,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as total_tickets
                FROM tickets 
                WHERE client_id = ? AND company_id = ? AND archived_at IS NULL
            ", [
                now()->subMonths(3),
                now()->subMonths(3),
                $client->id,
                $client->company_id,
            ]);

            if ($ticketCounts->total_tickets === 0) {
                return 0.0;
            }

            // Base satisfaction on resolution rate + SLA compliance
            $resolutionRate = ($ticketCounts->resolved_tickets / $ticketCounts->total_tickets) * 100;
            $slaCompliance = isset($this->metricsCache[$client->id]['sla_compliance'])
                ? $this->metricsCache[$client->id]['sla_compliance']
                : $this->calculateSlaCompliance($client);

            return round(($resolutionRate * 0.6 + $slaCompliance * 0.4), 1);
        }

        return round($ratings->avg() * 20, 1); // Convert 5-star to percentage
    }

    protected function calculateAvgResolutionTime(Client $client): string
    {
        // Reuse cached tickets if available from SLA calculation
        if (isset($this->metricsCache[$client->id]['tickets_for_sla'])) {
            $tickets = $this->metricsCache[$client->id]['tickets_for_sla'];
        } else {
            $tickets = $client->tickets()
                ->whereNotNull('closed_at')
                ->where('created_at', '>=', now()->subMonths(3))
                ->select('id', 'created_at', 'closed_at')
                ->get();
        }

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
            return round($avgHours, 1).' hrs';
        } else {
            return round($avgHours / 24, 1).' days';
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
            ->get();

        foreach ($contracts as $contract) {
            // Extract services from contract data
            if ($contract->billing_model && $contract->billing_model !== 'fixed') {
                $services[] = [
                    'name' => $contract->title,
                    'quantity' => 1,
                    'price' => $contract->getMonthlyRecurringRevenue(),
                    'billing_cycle' => 'monthly',
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

    protected function calculateMonthlyServiceCost(Client $client, ?array $services = null): float
    {
        // Use passed services or fetch them
        $services = $services ?? $this->getActiveServices($client);
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
