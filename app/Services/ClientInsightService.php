<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Collection;

class ClientInsightService
{
    /**
     * Get quick insights for a client
     */
    public function getQuickInsights(Client $client): array
    {
        $insights = [
            'priority_level' => 'normal',
            'status_indicators' => [],
            'recent_activity' => null,
            'next_action' => null,
            'risk_score' => 0,
        ];

        try {
            // Check for critical tickets
            $criticalTickets = $this->getCriticalTicketsCount($client);
            if ($criticalTickets > 0) {
                $insights['priority_level'] = 'critical';
                $insights['status_indicators'][] = [
                    'type' => 'critical',
                    'message' => "{$criticalTickets} critical ticket(s)",
                ];
                $insights['risk_score'] += 30;
            }

            // Check for overdue invoices
            $overdueInvoices = $this->getOverdueInvoicesCount($client);
            if ($overdueInvoices > 0) {
                $insights['priority_level'] = $insights['priority_level'] === 'critical' ? 'critical' : 'urgent';
                $insights['status_indicators'][] = [
                    'type' => 'financial',
                    'message' => "{$overdueInvoices} overdue invoice(s)",
                ];
                $insights['risk_score'] += 20;
            }

            // Check for scheduled work today
            if ($this->hasScheduledWorkToday($client)) {
                $insights['status_indicators'][] = [
                    'type' => 'scheduled',
                    'message' => 'Scheduled work today',
                ];
            }

            // Get recent activity
            $insights['recent_activity'] = $this->getRecentActivity($client);

            // Suggest next action
            $insights['next_action'] = $this->suggestNextAction($client, $insights);

        } catch (\Exception $e) {
            // Return default insights if queries fail
        }

        return $insights;
    }

    /**
     * Get count of critical tickets for a client
     */
    private function getCriticalTicketsCount(Client $client): int
    {
        try {
            return $client->tickets()
                ->where('priority', 'critical')
                ->whereIn('status', ['open', 'in-progress'])
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of overdue invoices for a client
     */
    private function getOverdueInvoicesCount(Client $client): int
    {
        try {
            return $client->invoices()
                ->where('status', 'overdue')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if client has scheduled work today
     */
    private function hasScheduledWorkToday(Client $client): bool
    {
        try {
            $today = now()->startOfDay();
            $tomorrow = now()->addDay()->startOfDay();

            return $client->tickets()
                ->whereBetween('scheduled_at', [$today, $tomorrow])
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get recent activity for a client
     */
    private function getRecentActivity(Client $client): ?array
    {
        try {
            // Get most recent ticket activity
            $recentTicket = $client->tickets()
                ->orderBy('updated_at', 'desc')
                ->first();

            // Get most recent invoice
            $recentInvoice = $client->invoices()
                ->orderBy('updated_at', 'desc')
                ->first();

            $activities = collect([]);

            if ($recentTicket) {
                $activities->push([
                    'type' => 'ticket',
                    'date' => $recentTicket->updated_at,
                    'description' => "Ticket: {$recentTicket->subject}",
                    'status' => $recentTicket->status,
                ]);
            }

            if ($recentInvoice) {
                $activities->push([
                    'type' => 'invoice',
                    'date' => $recentInvoice->updated_at,
                    'description' => "Invoice #{$recentInvoice->number}",
                    'status' => $recentInvoice->status,
                ]);
            }

            $mostRecent = $activities->sortByDesc('date')->first();

            if ($mostRecent) {
                return [
                    'type' => $mostRecent['type'],
                    'description' => $mostRecent['description'],
                    'date' => $mostRecent['date']->diffForHumans(),
                    'status' => $mostRecent['status'],
                ];
            }

        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Suggest next action based on client state
     */
    private function suggestNextAction(Client $client, array $insights): ?array
    {
        // Priority-based suggestions
        if ($insights['priority_level'] === 'critical') {
            return [
                'action' => 'Review critical tickets',
                'url' => route('tickets.index', ['client' => $client->id, 'priority' => 'critical']),
                'icon' => 'exclamation-triangle',
            ];
        }

        if (count($insights['status_indicators']) > 0) {
            $indicator = $insights['status_indicators'][0];
            if ($indicator['type'] === 'financial') {
                return [
                    'action' => 'Review overdue invoices',
                    'url' => route('financial.invoices.index', ['client' => $client->id, 'status' => 'overdue']),
                    'icon' => 'currency-dollar',
                ];
            }
        }

        // Default suggestion
        return [
            'action' => 'View client dashboard',
            'url' => route('clients.show'),
            'icon' => 'building-office',
        ];
    }

    /**
     * Get clients needing attention
     */
    public function getClientsNeedingAttention(int $companyId, int $limit = 5): Collection
    {
        try {
            // Get clients with critical issues
            return Client::where('company_id', $companyId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereHas('tickets', function ($subQuery) {
                        $subQuery->where('priority', 'critical')
                            ->whereIn('status', ['open', 'in-progress']);
                    })
                        ->orWhereHas('invoices', function ($subQuery) {
                            $subQuery->where('status', 'overdue');
                        });
                })
                ->orderBy('accessed_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get client health score (0-100)
     */
    public function getClientHealthScore(Client $client): int
    {
        $score = 100;

        try {
            // Deduct points for issues
            $criticalTickets = $this->getCriticalTicketsCount($client);
            $overdueInvoices = $this->getOverdueInvoicesCount($client);

            $score -= ($criticalTickets * 20);
            $score -= ($overdueInvoices * 15);

            // Bonus points for recent positive activity
            if ($client->accessed_at && $client->accessed_at->diffInDays() < 7) {
                $score += 5;
            }

            return max(0, min(100, $score));
        } catch (\Exception $e) {
            return 75; // Default middle score
        }
    }
}
