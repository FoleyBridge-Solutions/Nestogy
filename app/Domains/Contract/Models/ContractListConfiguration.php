<?php

namespace App\Domains\Contract\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ContractListConfiguration Model
 * 
 * Configures how contract lists are displayed including columns,
 * filters, sorting, and available actions.
 */
class ContractListConfiguration extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'contract_list_configurations';

    protected $fillable = [
        'company_id',
        'contract_type_slug',
        'columns_config',
        'filters_config',
        'search_config',
        'sorting_config',
        'pagination_config',
        'bulk_actions_config',
        'export_config',
        'show_row_actions',
        'show_bulk_actions',
        'is_active',
    ];

    protected $casts = [
        'columns_config' => 'array',
        'filters_config' => 'array',
        'search_config' => 'array',
        'sorting_config' => 'array',
        'pagination_config' => 'array',
        'bulk_actions_config' => 'array',
        'export_config' => 'array',
        'show_row_actions' => 'boolean',
        'show_bulk_actions' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get columns configuration with defaults
     */
    public function getColumnsConfig(): array
    {
        return array_merge([
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
                'field' => 'start_date',
                'label' => 'Start Date',
                'sortable' => true,
                'type' => 'date',
            ],
            [
                'field' => 'created_at',
                'label' => 'Created',
                'sortable' => true,
                'type' => 'datetime',
            ],
        ], $this->columns_config ?? []);
    }

    /**
     * Get filters configuration
     */
    public function getFiltersConfig(): array
    {
        return array_merge([
            [
                'field' => 'status',
                'label' => 'Status',
                'type' => 'select',
                'options' => [], // Will be populated dynamically
            ],
            [
                'field' => 'client_id',
                'label' => 'Client',
                'type' => 'client_selector',
            ],
            [
                'field' => 'date_range',
                'label' => 'Date Range',
                'type' => 'date_range',
            ],
        ], $this->filters_config ?? []);
    }

    /**
     * Get search configuration
     */
    public function getSearchConfig(): array
    {
        return array_merge([
            'enabled' => true,
            'placeholder' => 'Search contracts...',
            'fields' => ['contract_number', 'title', 'description'],
        ], $this->search_config ?? []);
    }

    /**
     * Get sorting configuration
     */
    public function getSortingConfig(): array
    {
        return array_merge([
            'default_field' => 'created_at',
            'default_direction' => 'desc',
            'allowed_fields' => [
                'contract_number',
                'title',
                'status',
                'contract_value',
                'start_date',
                'end_date',
                'created_at',
            ],
        ], $this->sorting_config ?? []);
    }

    /**
     * Get pagination configuration
     */
    public function getPaginationConfig(): array
    {
        return array_merge([
            'per_page' => 25,
            'per_page_options' => [10, 25, 50, 100],
            'show_totals' => true,
        ], $this->pagination_config ?? []);
    }

    /**
     * Get bulk actions configuration
     */
    public function getBulkActionsConfig(): array
    {
        return array_merge([
            [
                'action' => 'export',
                'label' => 'Export Selected',
                'icon' => 'download',
            ],
            [
                'action' => 'delete',
                'label' => 'Delete Selected',
                'icon' => 'trash',
                'confirm' => 'Are you sure you want to delete selected contracts?',
                'requires_permission' => 'delete_contracts',
            ],
        ], $this->bulk_actions_config ?? []);
    }

    /**
     * Get export configuration
     */
    public function getExportConfig(): array
    {
        return array_merge([
            'formats' => ['csv', 'xlsx', 'pdf'],
            'default_format' => 'csv',
            'filename_template' => 'contracts_{date}_{time}',
        ], $this->export_config ?? []);
    }

    /**
     * Scope to get active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}