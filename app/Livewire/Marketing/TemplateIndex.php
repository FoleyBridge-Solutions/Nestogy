<?php

namespace App\Livewire\Marketing;

use App\Livewire\BaseIndexComponent;
use App\Domains\Marketing\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\View;

class TemplateIndex extends BaseIndexComponent
{
    protected function getDefaultSort(): array
    {
        return ['field' => 'updated_at', 'direction' => 'desc'];
    }

    protected function getSearchFields(): array
    {
        return ['name', 'subject', 'category'];
    }

    protected function getColumns(): array
    {
        return [
            'name' => [
                'label' => 'Template Name',
                'sortable' => true,
                'filterable' => false,
                'component' => 'marketing.templates.cells.name',
            ],
            'subject' => [
                'label' => 'Subject Line',
                'sortable' => true,
                'filterable' => false,
                'component' => 'marketing.templates.cells.subject',
                'clickable' => true,
            ],
            'category' => [
                'label' => 'Category',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => EmailTemplate::getCategories(),
                'component' => 'marketing.templates.cells.category',
            ],
            'is_active' => [
                'label' => 'Status',
                'sortable' => true,
                'filterable' => true,
                'type' => 'select',
                'options' => [
                    '1' => 'Active',
                    '0' => 'Inactive',
                ],
                'component' => 'marketing.templates.cells.status',
            ],
            'updated_at' => [
                'label' => 'Last Modified',
                'sortable' => true,
                'type' => 'date',
            ],
        ];
    }

    protected function getStats(): array
    {
        $baseQuery = EmailTemplate::where('company_id', $this->companyId);

        $total = $baseQuery->clone()->count();
        $active = $baseQuery->clone()->where('is_active', true)->count();
        $byCategory = $baseQuery->clone()->selectRaw('category, count(*) as count')->groupBy('category')->pluck('count', 'category');

        return [
            [
                'label' => 'Total Templates',
                'value' => number_format($total),
                'icon' => 'document-duplicate',
                'color' => 'blue',
            ],
            [
                'label' => 'Active',
                'value' => number_format($active),
                'icon' => 'check-circle',
                'color' => 'green',
            ],
            [
                'label' => 'Marketing',
                'value' => number_format($byCategory['marketing'] ?? 0),
                'icon' => 'megaphone',
                'color' => 'purple',
            ],
            [
                'label' => 'Transactional',
                'value' => number_format($byCategory['transactional'] ?? 0),
                'icon' => 'envelope',
                'color' => 'yellow',
            ],
        ];
    }

    protected function getEmptyState(): array
    {
        return [
            'icon' => 'document-duplicate',
            'title' => 'No templates found',
            'message' => 'Get started by creating your first email template.',
            'action' => route('marketing.templates.create'),
            'actionLabel' => 'Create Template',
        ];
    }

    protected function getBaseQuery(): Builder
    {
        return EmailTemplate::query()
            ->with(['creator'])
            ->withCount(['campaigns']);
    }

    protected function getRowActions($item): array
    {
        return [
            [
                'label' => 'Edit',
                'href' => route('marketing.templates.edit', $item),
                'icon' => 'pencil',
            ],
            [
                'label' => 'Duplicate',
                'wire:click' => 'duplicateTemplate(' . $item->id . ')',
                'icon' => 'document-duplicate',
            ],
            [
                'label' => 'Delete',
                'wire:click' => 'deleteTemplate(' . $item->id . ')',
                'wire:confirm' => 'Are you sure you want to delete this template?',
                'icon' => 'trash',
                'variant' => 'danger',
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
                'confirm' => 'Are you sure you want to delete the selected templates?',
            ],
            [
                'label' => 'Activate',
                'method' => 'bulkActivate',
                'variant' => 'ghost',
            ],
            [
                'label' => 'Deactivate',
                'method' => 'bulkDeactivate',
                'variant' => 'ghost',
            ],
        ];
    }

    public function renderCellModal($cellKey, $item)
    {
        if ($cellKey === 'subject' && $item) {
            return View::make('marketing.templates.modals.preview', ['template' => $item])->render();
        }

        return '';
    }

    public function duplicateTemplate($templateId)
    {
        $template = EmailTemplate::where('company_id', $this->companyId)->findOrFail($templateId);

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->created_by = auth()->id();
        $newTemplate->save();

        $this->dispatch('alert', message: 'Template duplicated successfully', type: 'success');
    }

    public function deleteTemplate($templateId)
    {
        $template = EmailTemplate::where('company_id', $this->companyId)->findOrFail($templateId);
        $template->delete();

        $this->dispatch('alert', message: 'Template deleted successfully', type: 'success');
    }

    public function bulkActivate()
    {
        if (empty($this->selected)) {
            return;
        }

        EmailTemplate::whereIn('id', $this->selected)
            ->where('company_id', $this->companyId)
            ->update(['is_active' => true]);

        $this->selected = [];
        $this->dispatch('alert', message: count($this->selected) . ' templates activated', type: 'success');
    }

    public function bulkDeactivate()
    {
        if (empty($this->selected)) {
            return;
        }

        EmailTemplate::whereIn('id', $this->selected)
            ->where('company_id', $this->companyId)
            ->update(['is_active' => false]);

        $this->selected = [];
        $this->dispatch('alert', message: count($this->selected) . ' templates deactivated', type: 'success');
    }
}
