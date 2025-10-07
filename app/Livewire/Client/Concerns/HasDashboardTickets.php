<?php

namespace App\Livewire\Client\Concerns;

trait HasDashboardTickets
{
    protected function getTickets()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->tickets()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    protected function getTicketStats(): array
    {
        if (! $this->client) {
            return [];
        }

        $tickets = $this->client->tickets();

        return [
            'total_tickets' => $tickets->count(),
            'open_tickets' => $tickets->whereIn('status', ['Open', 'In Progress', 'Waiting', 'On Hold'])->count(),
            'resolved_this_month' => $tickets->whereIn('status', ['Resolved', 'Closed'])
                ->whereMonth('updated_at', now()->month)
                ->count(),
            'avg_response_time' => '< 1h',
        ];
    }

    protected function getRecentTickets()
    {
        if (! $this->client) {
            return collect();
        }

        return $this->client->tickets()
            ->with(['assignedTo', 'contact'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    protected function getTicketTrends()
    {
        if (! $this->client) {
            return [];
        }

        $months = [];
        $open = [];
        $closed = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M');

            $open[] = $this->client->tickets()
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->whereIn('status', ['Open', 'In Progress', 'Waiting', 'On Hold'])
                ->count();

            $closed[] = $this->client->tickets()
                ->whereYear('updated_at', $month->year)
                ->whereMonth('updated_at', $month->month)
                ->whereIn('status', ['Resolved', 'Closed'])
                ->count();
        }

        return [
            'labels' => $months,
            'open' => $open,
            'closed' => $closed,
        ];
    }
}
