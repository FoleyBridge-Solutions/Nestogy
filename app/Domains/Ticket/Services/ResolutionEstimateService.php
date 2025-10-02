<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketTimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResolutionEstimateService
{
    protected const CACHE_TTL = 3600;

    protected const MIN_SAMPLE_SIZE = 5;

    protected const FALLBACK_ESTIMATES = [
        'Critical' => 4,
        'High' => 8,
        'Medium' => 24,
        'Low' => 48,
    ];

    public function calculateEstimate(Ticket $ticket): ?Carbon
    {
        $estimatedHours = $this->getEstimatedHours($ticket);

        if ($estimatedHours === null) {
            return null;
        }

        return now()->addHours($estimatedHours);
    }

    public function getEstimatedHours(Ticket $ticket): ?float
    {
        $cacheKey = $this->getCacheKey($ticket);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ticket) {
            return $this->computeEstimatedHours($ticket);
        });
    }

    protected function computeEstimatedHours(Ticket $ticket): ?float
    {
        $factors = [
            'priority' => $ticket->priority,
            'category' => $ticket->category,
            'client_id' => $ticket->client_id,
            'complexity' => $this->estimateComplexity($ticket),
        ];

        $historicalEstimate = $this->getHistoricalAverage($factors);

        if ($historicalEstimate !== null) {
            return $this->adjustEstimate($historicalEstimate, $ticket);
        }

        return $this->getFallbackEstimate($ticket);
    }

    protected function getHistoricalAverage(array $factors): ?float
    {
        $query = Ticket::query()
            ->where('is_resolved', true)
            ->whereNotNull('resolved_at')
            ->where('priority', $factors['priority']);

        if (! empty($factors['category'])) {
            $query->where('category', $factors['category']);
        }

        if (! empty($factors['client_id'])) {
            $query->where('client_id', $factors['client_id']);
        }

        $resolvedTickets = $query->get();

        if ($resolvedTickets->count() < self::MIN_SAMPLE_SIZE) {
            return $this->getHistoricalAverageByPriorityOnly($factors['priority']);
        }

        $resolutionTimes = $resolvedTickets->map(function ($ticket) {
            return $ticket->created_at->diffInHours($ticket->resolved_at);
        })->filter(function ($hours) {
            return $hours > 0 && $hours < 720;
        });

        if ($resolutionTimes->isEmpty()) {
            return null;
        }

        return round($resolutionTimes->median(), 2);
    }

    protected function getHistoricalAverageByPriorityOnly(string $priority): ?float
    {
        $cacheKey = "resolution_estimate_priority_{$priority}";

        return Cache::remember($cacheKey, self::CACHE_TTL * 2, function () use ($priority) {
            $resolvedTickets = Ticket::query()
                ->where('is_resolved', true)
                ->whereNotNull('resolved_at')
                ->where('priority', $priority)
                ->get();

            if ($resolvedTickets->count() < self::MIN_SAMPLE_SIZE) {
                return null;
            }

            $resolutionTimes = $resolvedTickets->map(function ($ticket) {
                return $ticket->created_at->diffInHours($ticket->resolved_at);
            })->filter(function ($hours) {
                return $hours > 0 && $hours < 720;
            });

            if ($resolutionTimes->isEmpty()) {
                return null;
            }

            return round($resolutionTimes->median(), 2);
        });
    }

    protected function adjustEstimate(float $baseEstimate, Ticket $ticket): float
    {
        $adjustmentFactor = 1.0;

        if ($ticket->assignee) {
            $technicianPerformance = $this->getTechnicianPerformanceFactor($ticket->assignee->id);
            $adjustmentFactor *= $technicianPerformance;
        }

        $workloadFactor = $this->getCurrentWorkloadFactor($ticket->assigned_to);
        $adjustmentFactor *= $workloadFactor;

        if ($ticket->hasNegativeSentiment()) {
            $adjustmentFactor *= 1.15;
        }

        $adjustedEstimate = $baseEstimate * $adjustmentFactor;

        return round(max($adjustedEstimate, 0.5), 2);
    }

    protected function getTechnicianPerformanceFactor(int $userId): float
    {
        $cacheKey = "tech_performance_{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL * 2, function () use ($userId) {
            $recentTickets = Ticket::query()
                ->where('assigned_to', $userId)
                ->where('is_resolved', true)
                ->whereNotNull('resolved_at')
                ->where('created_at', '>', now()->subMonths(3))
                ->get();

            if ($recentTickets->count() < 3) {
                return 1.0;
            }

            $avgResolutionTime = $recentTickets->avg(function ($ticket) {
                return $ticket->created_at->diffInHours($ticket->resolved_at);
            });

            $companyAvg = Ticket::query()
                ->where('company_id', $recentTickets->first()->company_id)
                ->where('is_resolved', true)
                ->whereNotNull('resolved_at')
                ->where('created_at', '>', now()->subMonths(3))
                ->get()
                ->avg(function ($ticket) {
                    return $ticket->created_at->diffInHours($ticket->resolved_at);
                });

            if ($companyAvg <= 0) {
                return 1.0;
            }

            $performanceRatio = $avgResolutionTime / $companyAvg;

            return max(min($performanceRatio, 1.5), 0.7);
        });
    }

    protected function getCurrentWorkloadFactor(?int $userId): float
    {
        if (! $userId) {
            return 1.0;
        }

        $activeTickets = Ticket::query()
            ->where('assigned_to', $userId)
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->count();

        if ($activeTickets <= 3) {
            return 1.0;
        } elseif ($activeTickets <= 6) {
            return 1.2;
        } elseif ($activeTickets <= 10) {
            return 1.4;
        } else {
            return 1.6;
        }
    }

    protected function estimateComplexity(Ticket $ticket): int
    {
        $complexity = 1;

        $detailsLength = strlen($ticket->details ?? '');
        if ($detailsLength > 500) {
            $complexity = 3;
        } elseif ($detailsLength > 200) {
            $complexity = 2;
        }

        if ($ticket->asset_id || $ticket->location_id) {
            $complexity = max($complexity, 2);
        }

        if ($ticket->project_id) {
            $complexity = max($complexity, 3);
        }

        return min($complexity, 3);
    }

    protected function getFallbackEstimate(Ticket $ticket): float
    {
        $baseHours = self::FALLBACK_ESTIMATES[$ticket->priority] ?? 24;

        $complexity = $this->estimateComplexity($ticket);
        $complexityMultiplier = match ($complexity) {
            1 => 0.8,
            2 => 1.0,
            3 => 1.3,
            default => 1.0,
        };

        return round($baseHours * $complexityMultiplier, 2);
    }

    protected function getCacheKey(Ticket $ticket): string
    {
        return sprintf(
            'ticket_estimate_%d_%s_%s_%s',
            $ticket->id,
            $ticket->priority,
            $ticket->category ?? 'none',
            $ticket->client_id ?? 'none'
        );
    }

    public function updateEstimateForTicket(Ticket $ticket): void
    {
        $estimatedResolutionAt = $this->calculateEstimate($ticket);

        $ticket->update([
            'estimated_resolution_at' => $estimatedResolutionAt,
        ]);

        $this->clearCache($ticket);
    }

    public function clearCache(Ticket $ticket): void
    {
        Cache::forget($this->getCacheKey($ticket));
    }

    public function recalculateAllEstimates(?int $companyId = null): int
    {
        $query = Ticket::query()
            ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
            ->where('is_resolved', false);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $tickets = $query->get();
        $updated = 0;

        foreach ($tickets as $ticket) {
            try {
                $this->updateEstimateForTicket($ticket);
                $updated++;
            } catch (\Exception $e) {
                \Log::error("Failed to update estimate for ticket {$ticket->id}: {$e->getMessage()}");
            }
        }

        return $updated;
    }

    public function getEstimateAccuracy(?int $companyId = null): array
    {
        $query = Ticket::query()
            ->where('is_resolved', true)
            ->whereNotNull('resolved_at')
            ->whereNotNull('estimated_resolution_at')
            ->where('created_at', '>', now()->subMonths(3));

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $tickets = $query->get();

        if ($tickets->isEmpty()) {
            return [
                'total_tickets' => 0,
                'accurate_estimates' => 0,
                'accuracy_percentage' => 0,
                'avg_variance_hours' => 0,
            ];
        }

        $accurateCount = 0;
        $totalVariance = 0;

        foreach ($tickets as $ticket) {
            $estimatedHours = $ticket->created_at->diffInHours($ticket->estimated_resolution_at);
            $actualHours = $ticket->created_at->diffInHours($ticket->resolved_at);
            $variance = abs($estimatedHours - $actualHours);

            $totalVariance += $variance;

            if ($variance <= ($actualHours * 0.25)) {
                $accurateCount++;
            }
        }

        return [
            'total_tickets' => $tickets->count(),
            'accurate_estimates' => $accurateCount,
            'accuracy_percentage' => round(($accurateCount / $tickets->count()) * 100, 2),
            'avg_variance_hours' => round($totalVariance / $tickets->count(), 2),
        ];
    }
}
