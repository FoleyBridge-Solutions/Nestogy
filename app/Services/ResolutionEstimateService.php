<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ResolutionEstimateService
{
    protected array $priorityBaseHours = [
        'Critical' => 4,
        'High' => 8,
        'Medium' => 24,
        'Low' => 48,
    ];

    public function calculateEstimatedResolution(Ticket $ticket): ?Carbon
    {
        $baseHours = $this->priorityBaseHours[$ticket->priority] ?? 24;
        
        $workloadFactor = $this->getWorkloadFactor($ticket->assigned_to);
        $categoryFactor = $this->getCategoryFactor($ticket->category);
        $queueFactor = $this->getQueueFactor($ticket->client_id);
        
        $adjustedHours = $baseHours * $workloadFactor * $categoryFactor * $queueFactor;
        
        $estimatedAt = now()->addHours($adjustedHours);
        
        return $this->adjustForBusinessHours($estimatedAt);
    }

    protected function getWorkloadFactor(?int $userId): float
    {
        if (!$userId) {
            return 1.5;
        }

        $activeTickets = Ticket::where('assigned_to', $userId)
            ->whereIn('status', ['Open', 'In Progress'])
            ->count();

        if ($activeTickets <= 5) {
            return 1.0;
        } elseif ($activeTickets <= 10) {
            return 1.3;
        } else {
            return 1.6;
        }
    }

    protected function getCategoryFactor(?string $category): float
    {
        $complexCategories = [
            'Network' => 1.4,
            'Server' => 1.5,
            'Security' => 1.3,
            'Database' => 1.4,
        ];

        return $complexCategories[$category] ?? 1.0;
    }

    protected function getQueueFactor(int $clientId): float
    {
        $pendingTickets = Ticket::where('client_id', $clientId)
            ->whereIn('status', ['Open', 'In Progress'])
            ->count();

        if ($pendingTickets <= 3) {
            return 1.0;
        } elseif ($pendingTickets <= 10) {
            return 1.2;
        } else {
            return 1.4;
        }
    }

    protected function adjustForBusinessHours(Carbon $estimatedAt): Carbon
    {
        $businessHoursStart = 9;
        $businessHoursEnd = 17;
        
        while ($estimatedAt->isWeekend()) {
            $estimatedAt->addDay();
        }
        
        if ($estimatedAt->hour < $businessHoursStart) {
            $estimatedAt->setTime($businessHoursStart, 0);
        } elseif ($estimatedAt->hour >= $businessHoursEnd) {
            $estimatedAt->addDay()->setTime($businessHoursStart, 0);
            while ($estimatedAt->isWeekend()) {
                $estimatedAt->addDay();
            }
        }
        
        return $estimatedAt;
    }

    public function updateEstimateForTicket(Ticket $ticket): void
    {
        if (in_array($ticket->status, ['Resolved', 'Closed'])) {
            return;
        }

        $estimate = $this->calculateEstimatedResolution($ticket);
        $ticket->update(['estimated_resolution_at' => $estimate]);
    }

    public function recalculateForTechnician(int $userId): void
    {
        $tickets = Ticket::where('assigned_to', $userId)
            ->whereIn('status', ['Open', 'In Progress'])
            ->get();

        foreach ($tickets as $ticket) {
            $this->updateEstimateForTicket($ticket);
        }
    }

    public function getAverageResolutionTime(array $filters = []): float
    {
        $query = Ticket::whereNotNull('resolved_at')
            ->whereNotNull('created_at');

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        $avgMinutes = $query->selectRaw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at))/60) as avg_minutes')
            ->value('avg_minutes');

        return ($avgMinutes ?? 0) / 60;
    }
}
