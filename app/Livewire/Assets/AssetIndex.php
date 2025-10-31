<?php

namespace App\Livewire\Assets;

use App\Livewire\BaseIndexComponent;
use App\Domains\Asset\Models\Asset;
use App\Domains\Core\Models\User;
use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\Location;
use Illuminate\Database\Eloquent\Builder;

class AssetIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'created_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'serial', 'asset_tag', 'model', 'make', 'ip', 'mac'];
    }

    protected function getColumns(): array
    {
        return [
            'name' => [
                'label' => 'Asset Name',
                'sortable' => true,
                'filterable' => false,
            ],
            'type' => [
                'label' => 'Type',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => array_combine(Asset::TYPES, Asset::TYPES),
            ],
            'client.name' => [
                'label' => 'Client',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => $this->getClientOptions(),
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => array_combine(Asset::STATUSES, Asset::STATUSES),
                'component' => 'components.asset.cells.status',
            ],
            'make' => [
                'label' => 'Make',
                'sortable' => true,
                'filterable' => false,
            ],
            'model' => [
                'label' => 'Model',
                'sortable' => true,
                'filterable' => false,
            ],
            'serial' => [
                'label' => 'Serial Number',
                'sortable' => true,
                'filterable' => false,
            ],
            'ip' => [
                'label' => 'IP Address',
                'sortable' => true,
                'filterable' => false,
            ],
            'warranty_expire' => [
                'label' => 'Warranty Expiry',
                'sortable' => true,
                'type' => 'date',
                'component' => 'components.asset.cells.warranty',
            ],
            'support_status' => [
                'label' => 'Support Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => Asset::SUPPORT_STATUSES,
                'component' => 'components.asset.cells.support-status',
            ],
        ];
    }

    protected function getStats(): array
    {
        $baseQuery = Asset::where('company_id', $this->companyId)->whereNull('archived_at');

        $total = $baseQuery->clone()->count();
        $deployed = $baseQuery->clone()->where('status', 'Deployed')->count();
        $ready = $baseQuery->clone()->where('status', 'Ready To Deploy')->count();
        $supported = $baseQuery->clone()->where('support_status', 'supported')->count();
        $warrantyExpiring = $baseQuery->clone()->whereNotNull('warranty_expire')->where('warranty_expire', '<=', now()->addDays(90))->where('warranty_expire', '>', now())->count();

        return [
            ['label' => 'Total Assets', 'value' => $total, 'icon' => 'cube', 'iconBg' => 'bg-blue-500'],
            ['label' => 'Deployed', 'value' => $deployed, 'icon' => 'check-circle', 'iconBg' => 'bg-green-500'],
            ['label' => 'Ready to Deploy', 'value' => $ready, 'icon' => 'archive-box', 'iconBg' => 'bg-emerald-500'],
            ['label' => 'Supported', 'value' => $supported, 'icon' => 'shield-check', 'iconBg' => 'bg-purple-500'],
            ['label' => 'Warranty Expiring', 'value' => $warrantyExpiring, 'icon' => 'calendar', 'iconBg' => 'bg-amber-500'],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'title' => 'No Assets',
            'message' => 'No assets found. Create your first asset to get started.',
            'icon' => 'cube',
            'action' => [
                'label' => 'Create Asset',
                'href' => route('assets.create'),
            ],
            'actionLabel' => 'Create Asset',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return Asset::where('company_id', $this->companyId)->whereNull('archived_at');
    }

    protected function getRowActions($item)
    {
        return [
            ['label' => 'View', 'href' => route('assets.show', $item->id), 'icon' => 'eye'],
            ['label' => 'Edit', 'href' => route('assets.edit', $item->id), 'icon' => 'pencil'],
            ['label' => 'Delete', 'wire:click' => 'deleteItem('.$item->id.')', 'icon' => 'trash', 'variant' => 'danger'],
        ];
    }

    protected function getBulkActions()
    {
        return [
            ['label' => 'Delete', 'method' => 'bulkDelete', 'variant' => 'danger', 'confirm' => 'Are you sure you want to delete selected assets?'],
        ];
    }

    /**
     * Get client options for filtering
     */
    private function getClientOptions(): array
    {
        $clients = Client::where('company_id', $this->companyId)
            ->pluck('name', 'id')
            ->toArray();

        return $clients;
    }
}
