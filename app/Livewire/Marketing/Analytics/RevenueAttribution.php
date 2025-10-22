<?php

namespace App\Livewire\Marketing\Analytics;

use App\Livewire\BaseIndexComponent;
use App\Domains\Lead\Models\Lead;
use App\Domains\Client\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RevenueAttribution extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'leads_generated', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return [];
    }

    protected function getColumns(): array
    {
        return [
            'source' => [
                'label' => 'Source',
                'sortable' => true,
                'filterable' => false,
            ],
            'leads_generated' => [
                'label' => 'Leads',
                'sortable' => true,
            ],
            'conversions' => [
                'label' => 'Conversions',
                'sortable' => true,
            ],
            'total_revenue' => [
                'label' => 'Revenue',
                'sortable' => true,
                'type' => 'currency',
            ],
            'avg_revenue_per_lead' => [
                'label' => 'Avg/Lead',
                'sortable' => false,
                'type' => 'currency',
            ],
            'roi' => [
                'label' => 'ROI',
                'sortable' => false,
            ],
        ];
    }

    protected function getStats(): array
    {
        $leads = Lead::where('company_id', $this->companyId)
            ->where('status', Lead::STATUS_CONVERTED)
            ->whereNotNull('utm_source')
            ->with('client')
            ->get();

        $totalRevenue = 0;
        $leadCount = 0;
        
        foreach ($leads as $lead) {
            if ($lead->client) {
                $totalRevenue += $lead->client->invoices()->sum('total') ?? 0;
                $leadCount++;
            }
        }
        
        $avgRevenue = $leadCount > 0 ? $totalRevenue / $leadCount : 0;
        $totalLeads = Lead::where('company_id', $this->companyId)->whereNotNull('utm_source')->count();

        return [
            [
                'label' => 'Total Revenue',
                'value' => '$' . number_format($totalRevenue, 0),
                'icon' => 'currency-dollar',
                'color' => 'green',
            ],
            [
                'label' => 'Avg Revenue/Lead',
                'value' => '$' . number_format($avgRevenue, 0),
                'icon' => 'chart-bar',
                'color' => 'blue',
            ],
            [
                'label' => 'Converted Leads',
                'value' => number_format($leadCount),
                'icon' => 'check-circle',
                'color' => 'purple',
            ],
            [
                'label' => 'Total Leads',
                'value' => number_format($totalLeads),
                'icon' => 'users',
                'color' => 'yellow',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'currency-dollar',
            'title' => 'No revenue data',
            'message' => 'Revenue attribution data will appear here once leads convert to clients.',
            'action' => route('leads.index'),
            'actionLabel' => 'View Leads',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return Lead::query()
            ->select('utm_source as source')
            ->selectRaw('COUNT(*) as leads_generated')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as conversions', [Lead::STATUS_CONVERTED])
            ->whereNotNull('utm_source')
            ->groupBy('utm_source');
    }

    public function render()
    {
        $columns = $this->getVisibleColumns();
        $items = $this->getItems();

        $items->getCollection()->transform(function ($item) {
            $convertedLeads = Lead::where('company_id', $this->companyId)
                ->where('utm_source', $item->source)
                ->where('status', Lead::STATUS_CONVERTED)
                ->with('client.invoices')
                ->get();

            $totalRevenue = 0;
            foreach ($convertedLeads as $lead) {
                if ($lead->client) {
                    $totalRevenue += $lead->client->invoices->sum('total') ?? 0;
                }
            }

            $item->total_revenue = $totalRevenue;
            $item->avg_revenue_per_lead = $item->leads_generated > 0 
                ? $totalRevenue / $item->leads_generated 
                : 0;
            $item->roi = 'N/A';
            
            return $item;
        });

        return view('livewire.marketing.analytics.revenue-attribution', [
            'items' => $items,
            'columns' => $columns,
            'stats' => $this->getStats(),
            'emptyState' => $this->getEmptyState(),
        ]);
    }
}
