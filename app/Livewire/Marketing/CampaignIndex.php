<?php

namespace App\Livewire\Marketing;

use App\Livewire\BaseIndexComponent;
use App\Domains\Marketing\Models\MarketingCampaign;
use App\Domains\Core\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CampaignIndex extends BaseIndexComponent
{
    public $statusFilter = '';
    public $typeFilter = '';

    protected function getDefaultSort(): array
    {
        return ['field' => 'created_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'description'];
    }

    protected function getColumns(): array
    {
        return [
            'name' => [
                'label' => 'Campaign',
                'sortable' => true,
                'filterable' => false,
            ],
            'type' => [
                'label' => 'Type',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => MarketingCampaign::getTypes(),
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => MarketingCampaign::getStatuses(),
            ],
            'total_recipients' => [
                'label' => 'Recipients',
                'sortable' => true,
                'filterable' => false,
            ],
            'total_converted' => [
                'label' => 'Conversions',
                'sortable' => true,
                'filterable' => false,
            ],
            'createdBy.name' => [
                'label' => 'Created By',
                'sortable' => false,
                'filterable' => true,
                'type' => 'select',
                'options' => $this->getUserOptions(),
            ],
            'created_at' => [
                'label' => 'Created',
                'sortable' => true,
                'type' => 'date',
            ],
        ];
    }

    protected function getStats(): array
    {
        $baseQuery = MarketingCampaign::where('company_id', $this->companyId)->whereNull('deleted_at');

        $total = $baseQuery->clone()->count();
        $active = $baseQuery->clone()->where('status', MarketingCampaign::STATUS_ACTIVE)->count();
        $draft = $baseQuery->clone()->where('status', MarketingCampaign::STATUS_DRAFT)->count();
        $totalRecipients = $baseQuery->clone()->sum('total_recipients');
        $totalConversions = $baseQuery->clone()->sum('total_converted');

        return [
            ['label' => 'Total Campaigns', 'value' => $total, 'icon' => 'megaphone', 'iconBg' => 'bg-blue-500'],
            ['label' => 'Active', 'value' => $active, 'icon' => 'play', 'iconBg' => 'bg-green-500'],
            ['label' => 'Drafts', 'value' => $draft, 'icon' => 'document', 'iconBg' => 'bg-gray-500'],
            ['label' => 'Total Recipients', 'value' => number_format($totalRecipients), 'icon' => 'users', 'iconBg' => 'bg-purple-500'],
            ['label' => 'Total Conversions', 'value' => number_format($totalConversions), 'icon' => 'check-circle', 'iconBg' => 'bg-emerald-500'],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'title' => 'No Campaigns',
            'message' => 'No marketing campaigns found. Create your first campaign to get started.',
            'icon' => 'megaphone',
            'action' => [
                'label' => 'Create Campaign',
                'href' => route('marketing.campaigns.create'),
            ],
            'actionLabel' => 'Create Campaign',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return MarketingCampaign::where('company_id', $this->companyId)
            ->whereNull('deleted_at')
            ->with(['createdBy']);
    }

    protected function applyCustomFilters($query)
    {
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }
        
        return $query;
    }

    protected function getRowActions($item)
    {
        $actions = [
            ['label' => 'View', 'href' => route('marketing.campaigns.show', $item->id), 'icon' => 'eye'],
        ];

        if ($item->canBeEdited()) {
            $actions[] = ['label' => 'Edit', 'href' => route('marketing.campaigns.edit', $item->id), 'icon' => 'pencil'];
        }

        $actions[] = ['label' => 'Analytics', 'href' => route('marketing.campaigns.analytics', $item->id), 'icon' => 'chart-bar'];

        if ($item->status === MarketingCampaign::STATUS_DRAFT) {
            $actions[] = ['label' => 'Delete', 'wire:click' => 'deleteItem('.$item->id.')', 'icon' => 'trash', 'variant' => 'danger'];
        }

        return $actions;
    }

    protected function getBulkActions()
    {
        return [];
    }

    protected function getQueryStringProperties(): array
    {
        return [
            'search' => ['except' => ''],
            'statusFilter' => ['except' => ''],
            'typeFilter' => ['except' => ''],
            'sortField' => ['except' => 'created_at'],
            'sortDirection' => ['except' => 'desc'],
        ];
    }

    private function getUserOptions(): array
    {
        $users = User::where('company_id', $this->companyId)
            ->pluck('name', 'id')
            ->toArray();

        return $users;
    }
}
