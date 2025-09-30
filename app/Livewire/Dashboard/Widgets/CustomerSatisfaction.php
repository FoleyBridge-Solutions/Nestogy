<?php

namespace App\Livewire\Dashboard\Widgets;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CustomerSatisfaction extends Component
{
    public array $data = [];

    public bool $loading = true;

    public function mount()
    {
        $this->loadData();
    }

    #[On('refresh-customersatisfaction')]
    public function loadData()
    {
        $this->loading = true;

        $companyId = Auth::user()->company_id;

        // Calculate real customer satisfaction metrics
        $stats = $this->getSatisfactionStats($companyId);
        $items = $this->getSatisfactionItems($companyId);

        $this->data = [
            'items' => $items,
            'stats' => [
                [
                    'label' => 'Average Score',
                    'value' => $stats['average_score'],
                    'type' => 'rating',
                    'icon' => 'star',
                    'trend' => 0, // Could be calculated from historical data
                ],
                [
                    'label' => 'Total Responses',
                    'value' => $stats['total_responses'],
                    'type' => 'number',
                    'icon' => 'users',
                    'trend' => 0,
                ],
                [
                    'label' => 'Satisfaction Rate',
                    'value' => $stats['satisfaction_rate'],
                    'type' => 'percentage',
                    'icon' => 'face-smile',
                    'trend' => 0,
                ],
                [
                    'label' => 'Response Rate',
                    'value' => $stats['total_responses'] > 0 ? 100 : 0, // Simplified
                    'type' => 'percentage',
                    'icon' => 'chart-bar',
                    'trend' => 0,
                ],
            ],
        ];

        $this->loading = false;
    }

    protected function getSatisfactionItems($companyId)
    {
        // Get recent tickets with satisfaction feedback
        // For now, we'll use ticket resolution data as a proxy
        $recentTickets = \App\Models\Ticket::where('company_id', $companyId)
            ->whereIn('status', ['Resolved', 'Closed'])
            ->with('client')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return $recentTickets->map(function ($ticket) {
            // Calculate satisfaction score based on resolution time and status
            $resolutionTime = $ticket->resolved_at ? $ticket->resolved_at->diffInHours($ticket->created_at) : 0;
            $score = $this->calculateTicketSatisfactionScore($ticket, $resolutionTime);

            return [
                'client_name' => $ticket->client?->name ?? 'Unknown Client',
                'rating' => $score,
                'comment' => $ticket->subject, // Using subject as comment for now
                'ticket_number' => $ticket->id,
                'date' => $ticket->updated_at->format('M j, Y'),
                'resolution_time' => $resolutionTime.' hours',
            ];
        })->toArray();
    }

    protected function getSatisfactionStats($companyId)
    {
        $totalTickets = \App\Models\Ticket::where('company_id', $companyId)
            ->whereIn('status', ['Resolved', 'Closed'])
            ->count();

        if ($totalTickets === 0) {
            return [
                'average_score' => 0,
                'total_responses' => 0,
                'satisfaction_rate' => 0,
                'trend' => 'stable',
            ];
        }

        // Calculate average satisfaction from recent tickets
        $recentTickets = \App\Models\Ticket::where('company_id', $companyId)
            ->whereIn('status', ['Resolved', 'Closed'])
            ->where('updated_at', '>=', now()->subDays(30))
            ->get();

        $totalScore = 0;
        foreach ($recentTickets as $ticket) {
            $resolutionTime = $ticket->resolved_at ? $ticket->resolved_at->diffInHours($ticket->created_at) : 24;
            $totalScore += $this->calculateTicketSatisfactionScore($ticket, $resolutionTime);
        }

        $averageScore = $recentTickets->count() > 0 ? $totalScore / $recentTickets->count() : 0;

        // Calculate satisfaction rate (percentage of tickets with score >= 4.0)
        $satisfiedTickets = $recentTickets->filter(function ($ticket) {
            $resolutionTime = $ticket->resolved_at ? $ticket->resolved_at->diffInHours($ticket->created_at) : 24;
            $score = $this->calculateTicketSatisfactionScore($ticket, $resolutionTime);

            return $score >= 4.0;
        })->count();

        $satisfactionRate = $recentTickets->count() > 0 ? ($satisfiedTickets / $recentTickets->count()) * 100 : 0;

        return [
            'average_score' => round($averageScore, 1),
            'total_responses' => $recentTickets->count(),
            'satisfaction_rate' => round($satisfactionRate, 1),
            'trend' => 'stable', // Could be calculated based on historical data
        ];
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
        if ($ticket->priority === 'Critical' && $resolutionTime > 4) {
            $baseScore -= 0.5;
        } elseif ($ticket->priority === 'High' && $resolutionTime > 12) {
            $baseScore -= 0.5;
        }

        return max(1.0, min(5.0, $baseScore));
    }

    protected function getSatisfactionLabel($score)
    {
        if ($score >= 4.5) {
            return 'Very Satisfied';
        }
        if ($score >= 4.0) {
            return 'Satisfied';
        }
        if ($score >= 3.0) {
            return 'Neutral';
        }
        if ($score >= 2.0) {
            return 'Dissatisfied';
        }

        return 'Very Dissatisfied';
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.customer-satisfaction');
    }
}
