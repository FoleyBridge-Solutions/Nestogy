<?php

namespace App\Livewire\Leads;

use App\Livewire\BaseIndexComponent;
use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Models\LeadSource;
use App\Domains\Core\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\View;
use App\Domains\Lead\Services\LeadScoringService;

class LeadIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'total_score', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['first_name', 'last_name', 'email', 'phone', 'company_name', 'title'];
    }

    protected function getColumns(): array
    {
        return [
            'full_name' => [
                'label' => 'Lead',
                'sortable' => true,
                'filterable' => false,
                'component' => 'lead.cells.name',
            ],
            'company_name' => [
                'label' => 'Company',
                'sortable' => true,
                'filterable' => false,
                'component' => 'lead.cells.company',
            ],
            'total_score' => [
                'label' => 'Score',
                'sortable' => true,
                'filterable' => true,
                'filter_type' => 'numeric_range',
                'component' => 'lead.cells.score',
                'step' => '1',
                'clickable' => true,
            ],
            'status' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => Lead::getStatuses(),
                'component' => 'lead.cells.status',
            ],
            'priority' => [
                'label' => 'Priority',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => Lead::getPriorities(),
                'component' => 'lead.cells.priority',
            ],
            'leadSource.name' => [
                'label' => 'Source',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
            ],
            'assignedUser.name' => [
                'label' => 'Assigned To',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'dynamic_options' => true,
            ],
            'estimated_value' => [
                'label' => 'Est. Value',
                'sortable' => true,
                'filterable' => true,
                'filter_type' => 'numeric_range',
                'type' => 'currency',
                'prefix' => '$',
                'step' => '100',
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
        $baseQuery = Lead::where('company_id', $this->companyId);

        $total = $baseQuery->clone()->count();
        $qualified = $baseQuery->clone()->where('status', Lead::STATUS_QUALIFIED)->count();
        $converted = $baseQuery->clone()->where('status', Lead::STATUS_CONVERTED)->count();
        $avgScore = $baseQuery->clone()->avg('total_score') ?? 0;
        $totalValue = $baseQuery->clone()->sum('estimated_value') ?? 0;

        return [
            [
                'label' => 'Total Leads',
                'value' => number_format($total),
                'icon' => 'user-group',
                'color' => 'blue',
            ],
            [
                'label' => 'Qualified',
                'value' => number_format($qualified),
                'icon' => 'check-circle',
                'color' => 'green',
            ],
            [
                'label' => 'Converted',
                'value' => number_format($converted),
                'icon' => 'check-badge',
                'color' => 'purple',
            ],
            [
                'label' => 'Avg Score',
                'value' => round($avgScore) . '/100',
                'icon' => 'chart-bar',
                'color' => 'yellow',
            ],
            [
                'label' => 'Est. Value',
                'value' => '$' . number_format($totalValue, 0),
                'icon' => 'currency-dollar',
                'color' => 'emerald',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'user-group',
            'title' => 'No leads found',
            'message' => 'Get started by creating your first lead or importing from a CSV file.',
            'action' => route('leads.create'),
            'actionLabel' => 'Create Lead',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return Lead::query()
            ->with(['leadSource', 'assignedUser'])
            ->withCount(['activities']);
    }

    protected function getRowActions($item): array
    {
        return [
            [
                'label' => 'Edit',
                'href' => route('leads.edit', $item),
                'icon' => 'pencil',
            ],
            [
                'label' => 'Convert to Client',
                'wire:click' => 'convertLead(' . $item->id . ')',
                'icon' => 'arrow-right-circle',
            ],
        ];
    }

    public function getBulkActions(): array
    {
        return [
            [
                'label' => 'Delete Selected',
                'method' => 'bulkDelete',
                'variant' => 'danger',
                'confirm' => 'Are you sure you want to delete the selected leads?',
            ],
            [
                'label' => 'Mark as Qualified',
                'method' => 'bulkMarkQualified',
                'variant' => 'ghost',
            ],
            [
                'label' => 'Mark as Contacted',
                'method' => 'bulkMarkContacted',
                'variant' => 'ghost',
            ],
        ];
    }

    public function convertLead($leadId)
    {
        $this->redirect(route('leads.convert', $leadId));
    }

    public function bulkMarkQualified()
    {
        if (empty($this->selected)) {
            return;
        }

        Lead::whereIn('id', $this->selected)
            ->where('company_id', $this->companyId)
            ->update([
                'status' => Lead::STATUS_QUALIFIED,
                'qualified_at' => now(),
            ]);

        $this->selected = [];
        $this->dispatch('alert', message: count($this->selected) . ' leads marked as qualified', type: 'success');
    }

    public function bulkMarkContacted()
    {
        if (empty($this->selected)) {
            return;
        }

        $count = count($this->selected);
        
        Lead::whereIn('id', $this->selected)
            ->where('company_id', $this->companyId)
            ->update([
                'status' => Lead::STATUS_CONTACTED,
                'last_contact_date' => now(),
            ]);

        $this->selected = [];
        $this->dispatch('alert', message: $count . ' leads marked as contacted', type: 'success');
    }

    public function renderCellModal($cellKey, $item)
    {
        if ($cellKey === 'total_score' && $item) {
            return View::make('lead.modals.score-breakdown', ['lead' => $item])->render();
        }

        return '';
    }

    public function recalculateScore($leadId)
    {
        $lead = Lead::where('company_id', $this->companyId)->findOrFail($leadId);
        
        $scoringService = app(LeadScoringService::class);
        $scores = $scoringService->calculateTotalScore($lead);
        
        $lead->update($scores);
        
        $this->selectedItemForModal = $lead->fresh();
        
        $this->dispatch('alert', message: 'Score recalculated successfully', type: 'success');
    }
}
