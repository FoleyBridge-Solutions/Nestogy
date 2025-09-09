<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\ContractTypeDefinition;
use App\Domains\Contract\Models\ContractViewDefinition;
use App\Domains\Contract\Models\ContractListConfiguration;
use App\Domains\Contract\Models\ContractDetailConfiguration;
use App\Domains\Contract\Models\ContractFieldDefinition;
use Illuminate\Support\Facades\Auth;

/**
 * DynamicContractViewBuilder
 * 
 * Builds dynamic views for contract display based on company-specific
 * configuration. Handles index, show, edit views and their layouts.
 */
class DynamicContractViewBuilder
{
    /**
     * Build index view configuration
     */
    public function buildIndexView(string $contractType): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $typeDefinition = ContractTypeDefinition::where('company_id', $user->company_id)
            ->where('slug', $contractType)
            ->first();

        if (!$typeDefinition) {
            throw new \Exception("Contract type '{$contractType}' not found");
        }

        // Get view definition
        $viewDefinition = ContractViewDefinition::where('company_id', $user->company_id)
            ->where('contract_type_slug', $contractType)
            ->where('view_type', ContractViewDefinition::VIEW_INDEX)
            ->first();

        // Get list configuration
        $listConfig = ContractListConfiguration::where('company_id', $user->company_id)
            ->where('contract_type_slug', $contractType)
            ->first();

        return [
            'contract_type' => $contractType,
            'contract_type_name' => $typeDefinition->name,
            'view_type' => 'index',
            'layout' => $viewDefinition?->getLayoutConfig() ?? $this->getDefaultIndexLayout(),
            'columns' => $listConfig?->getColumnsConfig() ?? $this->getDefaultColumns(),
            'filters' => $this->buildFilters($contractType, $listConfig),
            'search' => $listConfig?->getSearchConfig() ?? $this->getDefaultSearchConfig(),
            'sorting' => $listConfig?->getSortingConfig() ?? $this->getDefaultSortingConfig(),
            'pagination' => $listConfig?->getPaginationConfig() ?? $this->getDefaultPaginationConfig(),
            'bulk_actions' => $listConfig?->getBulkActionsConfig() ?? [],
            'export' => $listConfig?->getExportConfig() ?? $this->getDefaultExportConfig(),
            'actions' => $this->buildIndexActions($contractType, $viewDefinition),
            'show_row_actions' => $listConfig?->show_row_actions ?? true,
            'show_bulk_actions' => $listConfig?->show_bulk_actions ?? true,
        ];
    }

    /**
     * Build detail view configuration
     */
    public function buildDetailView($contract): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $contractType = $contract->contract_type;
        
        $typeDefinition = ContractTypeDefinition::where('company_id', $user->company_id)
            ->where('slug', $contractType)
            ->first();

        if (!$typeDefinition) {
            throw new \Exception("Contract type '{$contractType}' not found");
        }

        // Get view definition
        $viewDefinition = ContractViewDefinition::where('company_id', $user->company_id)
            ->where('contract_type_slug', $contractType)
            ->where('view_type', ContractViewDefinition::VIEW_SHOW)
            ->first();

        // Get detail configuration
        $detailConfig = ContractDetailConfiguration::where('company_id', $user->company_id)
            ->where('contract_type_slug', $contractType)
            ->first();

        return [
            'contract_type' => $contractType,
            'contract_type_name' => $typeDefinition->name,
            'contract' => $contract,
            'view_type' => 'show',
            'layout' => $viewDefinition?->getLayoutConfig() ?? $this->getDefaultDetailLayout(),
            'sections' => $this->buildDetailSections($contract, $detailConfig),
            'tabs' => $detailConfig?->getTabsConfig() ?? $this->getDefaultTabs(),
            'sidebar' => $this->buildSidebar($contract, $detailConfig),
            'actions' => $this->buildDetailActions($contract, $viewDefinition),
            'timeline' => $this->buildTimeline($contract, $detailConfig),
            'related_data' => $this->buildRelatedData($contract, $detailConfig),
        ];
    }

    /**
     * Build dashboard widgets configuration
     */
    public function buildDashboardWidgets(string $contractType = null): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $query = \App\Domains\Contract\Models\ContractDashboardWidget::where('company_id', $user->company_id)
            ->active()
            ->orderBy('position_y')
            ->orderBy('position_x');

        if ($contractType) {
            $query->where(function ($q) use ($contractType) {
                $q->whereNull('contract_types_filter')
                  ->orWhereJsonContains('contract_types_filter', $contractType);
            });
        }

        $widgets = $query->get();

        $dashboardConfig = [
            'grid_columns' => 12,
            'grid_gap' => 'medium',
            'widgets' => [],
        ];

        foreach ($widgets as $widget) {
            $dashboardConfig['widgets'][] = [
                'widget_slug' => $widget->widget_slug,
                'widget_type' => $widget->widget_type,
                'title' => $widget->title,
                'description' => $widget->description,
                'position' => [
                    'x' => $widget->position_x,
                    'y' => $widget->position_y,
                    'width' => $widget->width,
                    'height' => $widget->height,
                ],
                'config' => $widget->config ?? [],
                'data_source' => $widget->data_source_config ?? [],
                'display' => $widget->display_config ?? [],
                'filters' => $widget->filter_config ?? [],
            ];
        }

        return $dashboardConfig;
    }

    /**
     * Build filters for index view
     */
    protected function buildFilters(string $contractType, $listConfig): array
    {
        $filters = $listConfig?->getFiltersConfig() ?? [];
        
        // Get dynamic status options
        $statusDefinitions = \App\Domains\Contract\Models\ContractStatusDefinition::where('company_id', Auth::user()->company_id)
            ->active()
            ->orderBy('sort_order')
            ->get();

        foreach ($filters as &$filter) {
            if ($filter['field'] === 'status') {
                $filter['options'] = $statusDefinitions->map(function ($status) {
                    return [
                        'value' => $status->slug,
                        'label' => $status->name,
                        'color' => $status->color,
                    ];
                })->toArray();
            }
        }

        return $filters;
    }

    /**
     * Build detail sections
     */
    protected function buildDetailSections($contract, $detailConfig): array
    {
        $sections = $detailConfig?->getSectionsConfig() ?? $this->getDefaultDetailSections();
        
        // Process sections and populate with contract data
        foreach ($sections as &$section) {
            if (isset($section['fields'])) {
                foreach ($section['fields'] as &$field) {
                    $field['value'] = $this->getContractFieldValue($contract, $field['field']);
                }
            }
        }

        return $sections;
    }

    /**
     * Get contract field value for display
     */
    protected function getContractFieldValue($contract, string $fieldPath)
    {
        // Handle dot notation for nested fields
        $parts = explode('.', $fieldPath);
        $value = $contract;

        foreach ($parts as $part) {
            if (is_object($value) && isset($value->{$part})) {
                $value = $value->{$part};
            } elseif (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Build sidebar configuration
     */
    protected function buildSidebar($contract, $detailConfig): array
    {
        return $detailConfig?->getSidebarConfig() ?? [
            'widgets' => [
                [
                    'type' => 'quick_stats',
                    'title' => 'Quick Stats',
                    'fields' => [
                        'status',
                        'contract_value',
                        'start_date',
                        'end_date',
                    ],
                ],
                [
                    'type' => 'related_records',
                    'title' => 'Related Records',
                    'relations' => [
                        'invoices',
                        'amendments',
                        'signatures',
                    ],
                ],
            ],
        ];
    }

    /**
     * Build timeline configuration
     */
    protected function buildTimeline($contract, $detailConfig): array
    {
        return $detailConfig?->getTimelineConfig() ?? [
            'enabled' => true,
            'events' => [
                'created',
                'status_changes',
                'amendments',
                'signatures',
                'invoices',
            ],
        ];
    }

    /**
     * Build related data configuration
     */
    protected function buildRelatedData($contract, $detailConfig): array
    {
        return $detailConfig?->getRelatedDataConfig() ?? [
            'invoices' => [
                'title' => 'Invoices',
                'model' => 'Invoice',
                'limit' => 10,
            ],
            'amendments' => [
                'title' => 'Amendments',
                'model' => 'ContractAmendment',
                'limit' => 5,
            ],
        ];
    }

    /**
     * Build index actions
     */
    protected function buildIndexActions(string $contractType, $viewDefinition): array
    {
        $actions = $viewDefinition?->getActionsConfig() ?? [];
        
        // Add default create action
        $actions[] = [
            'label' => 'Create Contract',
            'icon' => 'plus',
            'url' => route("contracts.{$contractType}.create"),
            'class' => 'btn-primary',
            'requires_permission' => 'create_contracts',
        ];

        return $actions;
    }

    /**
     * Build detail actions
     */
    protected function buildDetailActions($contract, $viewDefinition): array
    {
        $actions = $viewDefinition?->getActionsConfig() ?? [];
        
        // Add default actions
        $defaultActions = [
            [
                'label' => 'Edit',
                'icon' => 'edit',
                'url' => route("contracts.{$contract->contract_type}.edit", $contract),
                'class' => 'btn-secondary',
                'requires_permission' => 'update_contracts',
            ],
            [
                'label' => 'Download PDF',
                'icon' => 'download',
                'url' => route("contracts.{$contract->contract_type}.pdf", $contract),
                'class' => 'btn-outline',
            ],
        ];

        return array_merge($defaultActions, $actions);
    }

    /**
     * Get default configurations
     */
    protected function getDefaultIndexLayout(): array
    {
        return [
            'theme' => 'default',
            'sidebar' => false,
            'breadcrumbs' => true,
            'actions_bar' => true,
            'filters_sidebar' => true,
        ];
    }

    protected function getDefaultDetailLayout(): array
    {
        return [
            'theme' => 'default',
            'sidebar' => true,
            'breadcrumbs' => true,
            'actions_bar' => true,
            'tabs' => true,
        ];
    }

    protected function getDefaultColumns(): array
    {
        return [
            [
                'field' => 'contract_number',
                'label' => 'Contract #',
                'sortable' => true,
                'width' => '120px',
            ],
            [
                'field' => 'title',
                'label' => 'Title',
                'sortable' => true,
            ],
            [
                'field' => 'client.name',
                'label' => 'Client',
                'sortable' => true,
            ],
            [
                'field' => 'status',
                'label' => 'Status',
                'sortable' => true,
                'type' => 'status_badge',
            ],
            [
                'field' => 'contract_value',
                'label' => 'Value',
                'sortable' => true,
                'type' => 'currency',
            ],
            [
                'field' => 'created_at',
                'label' => 'Created',
                'sortable' => true,
                'type' => 'datetime',
            ],
        ];
    }

    protected function getDefaultSearchConfig(): array
    {
        return [
            'enabled' => true,
            'placeholder' => 'Search contracts...',
            'fields' => ['contract_number', 'title', 'description'],
        ];
    }

    protected function getDefaultSortingConfig(): array
    {
        return [
            'default_field' => 'created_at',
            'default_direction' => 'desc',
        ];
    }

    protected function getDefaultPaginationConfig(): array
    {
        return [
            'per_page' => 25,
            'per_page_options' => [10, 25, 50, 100],
        ];
    }

    protected function getDefaultExportConfig(): array
    {
        return [
            'formats' => ['csv', 'xlsx'],
            'default_format' => 'csv',
        ];
    }

    protected function getDefaultTabs(): array
    {
        return [
            [
                'slug' => 'overview',
                'label' => 'Overview',
                'icon' => 'eye',
                'active' => true,
            ],
            [
                'slug' => 'details',
                'label' => 'Details',
                'icon' => 'info',
            ],
            [
                'slug' => 'timeline',
                'label' => 'Timeline',
                'icon' => 'clock',
            ],
        ];
    }

    protected function getDefaultDetailSections(): array
    {
        return [
            [
                'slug' => 'basic_info',
                'title' => 'Basic Information',
                'icon' => 'info',
                'fields' => [
                    ['field' => 'title', 'label' => 'Title'],
                    ['field' => 'description', 'label' => 'Description'],
                    ['field' => 'contract_number', 'label' => 'Contract Number'],
                    ['field' => 'contract_type', 'label' => 'Type'],
                    ['field' => 'status', 'label' => 'Status'],
                ],
            ],
            [
                'slug' => 'financial',
                'title' => 'Financial Information',
                'icon' => 'currency-dollar',
                'fields' => [
                    ['field' => 'contract_value', 'label' => 'Contract Value', 'type' => 'currency'],
                    ['field' => 'currency_code', 'label' => 'Currency'],
                    ['field' => 'payment_terms', 'label' => 'Payment Terms'],
                ],
            ],
            [
                'slug' => 'dates',
                'title' => 'Important Dates',
                'icon' => 'calendar',
                'fields' => [
                    ['field' => 'start_date', 'label' => 'Start Date', 'type' => 'date'],
                    ['field' => 'end_date', 'label' => 'End Date', 'type' => 'date'],
                    ['field' => 'signed_at', 'label' => 'Signed Date', 'type' => 'datetime'],
                ],
            ],
        ];
    }
}