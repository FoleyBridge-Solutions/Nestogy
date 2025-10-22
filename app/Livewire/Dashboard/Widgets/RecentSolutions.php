<?php

namespace App\Livewire\Dashboard\Widgets;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class RecentSolutions extends Component
{
    public array $data = [];

    public bool $loading = true;

    public string $period = 'week';

    public function mount()
    {
        $this->loadData();
    }

    #[On('refresh-recentsolutions')]
    public function loadData()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;

        $startDate = match ($this->period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'quarter' => Carbon::now()->startOfQuarter(),
            default => Carbon::now()->subWeek()
        };

        $resolvedTickets = DB::table('tickets')
            ->where('tickets.company_id', $companyId)
            ->whereNotNull('tickets.resolved_at')
            ->where('tickets.resolved_at', '>=', $startDate)
            ->whereNotNull('tickets.resolution_summary')
            ->join('clients', 'tickets.client_id', '=', 'clients.id')
            ->select(
                'tickets.id',
                'tickets.number',
                'tickets.resolution_summary',
                'tickets.resolved_at',
                'tickets.subject',
                'clients.name as client_name'
            )
            ->orderByDesc('tickets.resolved_at')
            ->limit(10)
            ->get();

        $totalResolved = DB::table('tickets')
            ->where('tickets.company_id', $companyId)
            ->whereNotNull('tickets.resolved_at')
            ->where('tickets.resolved_at', '>=', $startDate)
            ->count();

        $this->data = [
            'items' => $resolvedTickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'number' => $ticket->number,
                    'client' => $ticket->client_name,
                    'subject' => $ticket->subject,
                    'summary' => $ticket->resolution_summary ? substr($ticket->resolution_summary, 0, 100) : 'No summary provided',
                    'date' => Carbon::parse($ticket->resolved_at)->format('M d, Y'),
                ];
            })->toArray(),
            'stats' => [
                [
                    'label' => 'Resolved This Period',
                    'value' => $totalResolved,
                    'type' => 'number',
                    'icon' => 'check-circle',
                ],
            ],
        ];

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.recent-solutions');
    }
}
