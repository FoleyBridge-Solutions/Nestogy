<?php

namespace App\Livewire\Dashboard\Widgets;

use App\Domains\Ticket\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ResponseTimes extends Component
{
    public array $responseData = [];

    public ?array $chartData = [];

    public bool $loading = true;

    public string $period = 'week';

    public function mount()
    {
        $this->loadResponseData();
    }

    #[On('refresh-response-times')]
    public function loadResponseData()
    {
        $this->loading = true;
        $companyId = Auth::user()->company_id;

        $startDate = match ($this->period) {
            'day' => Carbon::now()->subDay(),
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            default => Carbon::now()->subWeek()
        };

        // Calculate average response times
         $avgFirstResponse = DB::table('tickets')
             ->where('company_id', $companyId)
             ->where('created_at', '>=', $startDate)
             ->select(DB::raw('AVG(EXTRACT(EPOCH FROM (NOW() - created_at))/60) as avg_minutes'))
             ->value('avg_minutes') ?? 0;

        // Calculate average resolution times
        // Note: resolved_at column doesn't exist, using updated_at for closed tickets
        $avgResolution = DB::table('tickets')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', ['resolved', 'closed'])
            ->select(DB::raw('AVG(EXTRACT(EPOCH FROM (updated_at - created_at))/60) as avg_minutes'))
            ->value('avg_minutes') ?? 0;

         // Get response times by priority
         $byPriority = DB::table('tickets')
             ->where('company_id', $companyId)
             ->where('created_at', '>=', $startDate)
             ->whereNotNull('resolved_at')
             ->select(
                 'priority',
                 DB::raw('COUNT(*) as count'),
                 DB::raw('AVG(EXTRACT(EPOCH FROM (resolved_at - created_at))/60) as avg_response_minutes'),
                 DB::raw('MIN(EXTRACT(EPOCH FROM (resolved_at - created_at))/60) as min_response_minutes'),
                 DB::raw('MAX(EXTRACT(EPOCH FROM (resolved_at - created_at))/60) as max_response_minutes')
             )
             ->groupBy('priority')
             ->get()
             ->map(function ($item) {
                 return (object) [
                     'priority' => $item->priority,
                     'avg_response' => $item->avg_response_minutes ?? 0,
                     'min_response' => $item->min_response_minutes ?? 0,
                     'max_response' => $item->max_response_minutes ?? 0,
                     'count' => $item->count,
                 ];
             })->keyBy('priority');

        // Get daily trends for chart - optimized bulk query
        $days = match ($this->period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 7
        };

        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days - 1);

        // Get all ticket counts in one query
        $dailyCounts = DB::table('tickets')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

         // Build the trends data
         $dailyTrendData = DB::table('tickets')
             ->where('company_id', $companyId)
             ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
             ->whereNotNull('resolved_at')
             ->selectRaw('DATE(created_at) as date, AVG(EXTRACT(EPOCH FROM (resolved_at - created_at))/60) as avg_response_minutes')
             ->groupBy('date')
             ->pluck('avg_response_minutes', 'date')
             ->toArray();

         $dailyTrends = collect();
         for ($i = $days - 1; $i >= 0; $i--) {
             $date = Carbon::now()->subDays($i)->toDateString();
             $ticketCount = $dailyCounts[$date] ?? 0;
             $avgResponse = $dailyTrendData[$date] ?? 0;

             $dailyTrends->push((object) [
                 'date' => $date,
                 'avg_response' => $avgResponse,
                 'ticket_count' => $ticketCount,
             ]);
         }

        $this->responseData = [
            'avg_first_response' => round($avgFirstResponse / 60, 1), // Convert to hours
            'avg_resolution' => round($avgResolution / 60, 1),
            'by_priority' => $byPriority->mapWithKeys(function ($item) {
                return [$item->priority => [
                    'avg' => round($item->avg_response / 60, 1),
                    'min' => round($item->min_response / 60, 1),
                    'max' => round($item->max_response / 60, 1),
                    'count' => $item->count,
                ]];
            })->toArray(),
            'improvement' => $this->calculateImprovement($avgFirstResponse),
        ];

        $this->chartData = $dailyTrends->map(function ($item) {
            return [
                'date' => Carbon::parse($item->date)->format('M d'),
                'response' => round($item->avg_response / 60, 1),
            ];
        })->toArray();

        $this->loading = false;
    }

     protected function calculateImprovement($current)
     {
         return 0;
     }

    /**
     * Livewire lifecycle hook for when period property changes
     */
    public function updatedPeriod($value)
    {
        if (in_array($value, ['day', 'week', 'month'])) {
            $this->loadResponseData();
        }
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.response-times');
    }
}
