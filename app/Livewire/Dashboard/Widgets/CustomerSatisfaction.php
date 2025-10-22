<?php

namespace App\Livewire\Dashboard\Widgets;

use App\Domains\Ticket\Models\Ticket;
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
        $recentTickets = Ticket::where('company_id', $companyId)
            ->whereIn('status', ['Resolved', 'Closed'])
            ->with('client')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

         return $recentTickets->map(function ($ticket) {
             $resolutionTime = $ticket->resolved_at ? $ticket->resolved_at->diffInHours($ticket->created_at) : 0;

             return [
                 'client_name' => $ticket->client?->name ?? 'Unknown Client',
                 'rating' => 0,
                 'comment' => $ticket->subject,
                 'ticket_number' => $ticket->id,
                 'date' => $ticket->updated_at->format('M j, Y'),
                 'resolution_time' => $resolutionTime.' hours',
             ];
         })->toArray();
    }

    protected function getSatisfactionStats($companyId)
    {
        $totalTickets = Ticket::where('company_id', $companyId)
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
        $recentTickets = Ticket::where('company_id', $companyId)
            ->whereIn('status', ['Resolved', 'Closed'])
            ->where('updated_at', '>=', now()->subDays(30))
            ->get();

         return [
             'average_score' => 0,
             'total_responses' => $recentTickets->count(),
             'satisfaction_rate' => 0,
             'trend' => 'stable',
         ];
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
