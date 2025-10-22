<?php

namespace App\Livewire\Marketing\Analytics;

use App\Livewire\BaseIndexComponent;
use App\Domains\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Attribution extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'leads_count', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return [];
    }

    protected function getColumns(): array
    {
        return [
            'utm_source' => [
                'label' => 'Source',
                'sortable' => true,
                'filterable' => false,
            ],
            'leads_count' => [
                'label' => 'Leads',
                'sortable' => true,
            ],
            'qualified_count' => [
                'label' => 'Qualified',
                'sortable' => true,
            ],
            'converted_count' => [
                'label' => 'Converted',
                'sortable' => true,
            ],
            'conversion_rate' => [
                'label' => 'Conv. Rate',
                'sortable' => false,
            ],
            'avg_score' => [
                'label' => 'Avg Score',
                'sortable' => true,
            ],
            'estimated_value' => [
                'label' => 'Est. Value',
                'sortable' => true,
                'type' => 'currency',
            ],
        ];
    }

    protected function getStats(): array
    {
        $baseQuery = Lead::where('company_id', $this->companyId);

        $totalLeads = $baseQuery->clone()->count();
        $withSource = $baseQuery->clone()->whereNotNull('utm_source')->count();
        $qualified = $baseQuery->clone()->where('status', Lead::STATUS_QUALIFIED)->count();
        $converted = $baseQuery->clone()->where('status', Lead::STATUS_CONVERTED)->count();
        
        $sourceRate = $totalLeads > 0 ? round(($withSource / $totalLeads) * 100, 1) : 0;
        $conversionRate = $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 1) : 0;

        return [
            [
                'label' => 'Total Leads',
                'value' => number_format($totalLeads),
                'icon' => 'users',
                'color' => 'blue',
            ],
            [
                'label' => 'With Attribution',
                'value' => $sourceRate . '%',
                'icon' => 'link',
                'color' => 'green',
            ],
            [
                'label' => 'Qualified',
                'value' => number_format($qualified),
                'icon' => 'star',
                'color' => 'yellow',
            ],
            [
                'label' => 'Converted',
                'value' => number_format($converted),
                'icon' => 'check-circle',
                'color' => 'purple',
            ],
            [
                'label' => 'Overall Conv. Rate',
                'value' => $conversionRate . '%',
                'icon' => 'chart-bar',
                'color' => 'emerald',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'link',
            'title' => 'No attribution data',
            'message' => 'Lead attribution data will appear here once leads have UTM parameters.',
            'action' => route('leads.index'),
            'actionLabel' => 'View Leads',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return Lead::query()
            ->select('utm_source')
            ->selectRaw('COUNT(*) as leads_count')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as qualified_count', [Lead::STATUS_QUALIFIED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as converted_count', [Lead::STATUS_CONVERTED])
            ->selectRaw('AVG(total_score) as avg_score')
            ->selectRaw('SUM(estimated_value) as estimated_value')
            ->whereNotNull('utm_source')
            ->groupBy('utm_source');
    }

    public function render()
    {
        $columns = $this->getVisibleColumns();
        $items = $this->getItems();

        $items->getCollection()->transform(function ($item) {
            $item->conversion_rate = $item->leads_count > 0 
                ? round(($item->converted_count / $item->leads_count) * 100, 1) . '%' 
                : '0%';
            $item->avg_score = round($item->avg_score ?? 0, 1);
            return $item;
        });

        return view('livewire.marketing.analytics.attribution', [
            'items' => $items,
            'columns' => $columns,
            'stats' => $this->getStats(),
            'emptyState' => $this->getEmptyState(),
        ]);
    }
}
