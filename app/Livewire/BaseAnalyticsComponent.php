<?php

namespace App\Livewire;

use App\Domains\Core\Services\NavigationService;
use App\Livewire\Concerns\WithAuthenticatedUser;
use Livewire\Component;

abstract class BaseAnalyticsComponent extends Component
{
    use WithAuthenticatedUser;

    public array $dateRange = [];
    
    public string $period = '30_days';
    
    public array $chartDataCache = [];

    protected $queryString = [
        'period' => ['except' => '30_days'],
    ];

    public function mount()
    {
        $this->initializeDateRange();
    }

    protected function initializeDateRange()
    {
        $this->dateRange = match($this->period) {
            'today' => [
                now()->startOfDay()->toDateString(),
                now()->endOfDay()->toDateString(),
            ],
            '7_days' => [
                now()->subDays(6)->startOfDay()->toDateString(),
                now()->endOfDay()->toDateString(),
            ],
            '30_days' => [
                now()->subDays(29)->startOfDay()->toDateString(),
                now()->endOfDay()->toDateString(),
            ],
            '90_days' => [
                now()->subDays(89)->startOfDay()->toDateString(),
                now()->endOfDay()->toDateString(),
            ],
            'this_month' => [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                now()->subMonth()->startOfMonth()->toDateString(),
                now()->subMonth()->endOfMonth()->toDateString(),
            ],
            'this_year' => [
                now()->startOfYear()->toDateString(),
                now()->endOfYear()->toDateString(),
            ],
            default => [
                now()->subDays(29)->startOfDay()->toDateString(),
                now()->endOfDay()->toDateString(),
            ],
        };
    }

    public function updatedPeriod()
    {
        $this->initializeDateRange();
        $this->chartDataCache = [];
    }

    public function updatedDateRange()
    {
        $this->chartDataCache = [];
    }

    protected function getPeriodOptions(): array
    {
        return [
            'today' => 'Today',
            '7_days' => 'Last 7 Days',
            '30_days' => 'Last 30 Days',
            '90_days' => 'Last 90 Days',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
        ];
    }

    protected function getStats(): array
    {
        return [];
    }

    protected function getCharts(): array
    {
        return [];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'chart-bar',
            'title' => 'No data available',
            'message' => 'Analytics data will appear here once available.',
            'action' => null,
            'actionLabel' => null,
        ];
    }

    protected function hasData(): bool
    {
        $stats = $this->getStats();
        return !empty($stats);
    }

    public function render()
    {
        $charts = $this->getCharts();
        
        foreach ($charts as $key => $chart) {
            $chartData = $chart['data'] ?? [];
            $this->chartDataCache[$key] = $chartData;
            $charts[$key]['cachedData'] = $chartData;
        }

        return view('livewire.base-analytics', [
            'stats' => $this->getStats(),
            'charts' => $charts,
            'periodOptions' => $this->getPeriodOptions(),
            'emptyState' => $this->getEmptyState(),
            'hasData' => $this->hasData(),
        ]);
    }
}
