<?php

namespace App\Livewire\Projects;

use App\Livewire\BaseIndexComponent;
use App\Domains\Project\Models\Project;
use App\Domains\Core\Models\User;
use App\Domains\Client\Models\Client;
use Illuminate\Database\Eloquent\Builder;

class ProjectIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'created_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'description', 'prefix', 'number'];
    }

    protected function getColumns(): array
    {
        return [
            'name' => [
                'label' => 'Project Name',
                'sortable' => true,
                'filterable' => false,
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
                'options' => [
                    'pending' => 'Planning',
                    'active' => 'Active',
                    'on_hold' => 'On Hold',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ],
            ],
            'priority' => [
                'label' => 'Priority',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'critical' => 'Critical',
                ],
            ],
            'progress' => [
                'label' => 'Progress',
                'sortable' => true,
                'filterable' => false,
                'type' => 'badge',
            ],
            'start_date' => [
                'label' => 'Start Date',
                'sortable' => true,
                'type' => 'date',
            ],
            'due' => [
                'label' => 'Due Date',
                'sortable' => true,
                'type' => 'date',
            ],
            'manager.name' => [
                'label' => 'Manager',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => $this->getManagerOptions(),
            ],
            'budget' => [
                'label' => 'Budget',
                'sortable' => true,
                'type' => 'currency',
                'prefix' => '$',
            ],
        ];
    }

    protected function getStats(): array
    {
        $baseQuery = Project::where('company_id', $this->companyId);

        $total = $baseQuery->clone()->count();
        $active = $baseQuery->clone()->where('status', 'active')->count();
        $completed = $baseQuery->clone()->where('status', 'completed')->count();
        $overdue = $baseQuery->clone()->whereNull('completed_at')->whereNotNull('due')->where('due', '<', now())->count();
        $dueSoon = $baseQuery->clone()->whereNull('completed_at')->whereNotNull('due')->where('due', '>=', now())->where('due', '<=', now()->addDays(7))->count();

        return [
            ['label' => 'Total Projects', 'value' => $total, 'icon' => 'briefcase', 'iconBg' => 'bg-blue-500'],
            ['label' => 'Active', 'value' => $active, 'icon' => 'play-circle', 'iconBg' => 'bg-green-500'],
            ['label' => 'Completed', 'value' => $completed, 'icon' => 'check-circle', 'iconBg' => 'bg-emerald-500'],
            ['label' => 'Overdue', 'value' => $overdue, 'icon' => 'exclamation-circle', 'iconBg' => 'bg-red-500'],
            ['label' => 'Due Soon', 'value' => $dueSoon, 'icon' => 'calendar', 'iconBg' => 'bg-amber-500'],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'briefcase',
            'title' => 'No Projects',
            'message' => 'No projects found. Create your first project to get started.',
            'action' => route('projects.create'),
            'actionLabel' => 'Create Project',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return Project::where('company_id', $this->companyId);
    }

    protected function getRowActions($item)
    {
        return [
            ['label' => 'View', 'href' => route('projects.show', $item->id), 'icon' => 'eye'],
            ['label' => 'Edit', 'href' => route('projects.edit', $item->id), 'icon' => 'pencil'],
            ['label' => 'Delete', 'wire:click' => 'deleteItem('.$item->id.')', 'icon' => 'trash', 'variant' => 'danger'],
        ];
    }

    protected function getBulkActions()
    {
        return [
            ['label' => 'Delete', 'method' => 'bulkDelete', 'variant' => 'danger', 'confirm' => 'Are you sure you want to delete selected projects?'],
        ];
    }

    private function getClientOptions(): array
    {
        $clients = Client::where('company_id', $this->companyId)
            ->pluck('name', 'id')
            ->toArray();

        return $clients;
    }

    private function getManagerOptions(): array
    {
        $managers = User::where('company_id', $this->companyId)
            ->pluck('name', 'id')
            ->toArray();

        return $managers;
    }
}
