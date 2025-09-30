<?php

namespace App\Plugins\FieldTypes;

use App\Contracts\ContractFieldInterface;
use App\Models\Asset;
use App\Support\ValidationResult;
use Illuminate\Support\Facades\Auth;

/**
 * Asset selector field type plugin
 * Allows selection of assets with advanced filtering
 */
class AssetSelectorFieldType implements ContractFieldInterface
{
    protected array $config = [];

    public function getName(): string
    {
        return 'Asset Selector';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Multi-select field for choosing assets with filtering and search capabilities';
    }

    public function getAuthor(): string
    {
        return 'Nestogy Core Team';
    }

    public function getConfigurationSchema(): array
    {
        return [
            'multiple' => [
                'type' => 'boolean',
                'description' => 'Allow multiple asset selection',
                'default' => true,
            ],
            'asset_types' => [
                'type' => 'array',
                'description' => 'Limit to specific asset types',
                'items' => ['type' => 'string'],
                'default' => [],
            ],
            'client_scoped' => [
                'type' => 'boolean',
                'description' => 'Only show assets for the contract client',
                'default' => true,
            ],
            'status_filter' => [
                'type' => 'array',
                'description' => 'Filter assets by status',
                'items' => ['type' => 'string'],
                'default' => ['active', 'deployed'],
            ],
            'show_asset_details' => [
                'type' => 'boolean',
                'description' => 'Show asset details in selection',
                'default' => true,
            ],
            'enable_search' => [
                'type' => 'boolean',
                'description' => 'Enable asset search functionality',
                'default' => true,
            ],
            'max_selections' => [
                'type' => 'integer',
                'description' => 'Maximum number of assets that can be selected',
                'minimum' => 1,
                'default' => null,
            ],
            'required_tags' => [
                'type' => 'array',
                'description' => 'Assets must have these tags to be selectable',
                'items' => ['type' => 'string'],
                'default' => [],
            ],
            'excluded_assets' => [
                'type' => 'array',
                'description' => 'Asset IDs to exclude from selection',
                'items' => ['type' => 'integer'],
                'default' => [],
            ],
        ];
    }

    public function validateConfiguration(array $config): array
    {
        $errors = [];

        if (isset($config['max_selections']) && $config['max_selections'] < 1) {
            $errors[] = 'max_selections must be at least 1';
        }

        if (isset($config['asset_types']) && ! is_array($config['asset_types'])) {
            $errors[] = 'asset_types must be an array';
        }

        return $errors;
    }

    public function initialize(array $config = []): void
    {
        $this->config = array_merge([
            'multiple' => true,
            'asset_types' => [],
            'client_scoped' => true,
            'status_filter' => ['active', 'deployed'],
            'show_asset_details' => true,
            'enable_search' => true,
            'max_selections' => null,
            'required_tags' => [],
            'excluded_assets' => [],
        ], $config);
    }

    public function isCompatible(): bool
    {
        return class_exists(Asset::class);
    }

    public function getRequiredPermissions(): array
    {
        return ['view-assets'];
    }

    public function getDependencies(): array
    {
        return ['App\Models\Asset'];
    }

    public function validate($value, array $config, array $context = []): ValidationResult
    {
        $this->initialize($config);

        if (empty($value)) {
            return ValidationResult::success();
        }

        $assetIds = is_array($value) ? $value : [$value];
        $errors = [];

        // Check if assets exist and are accessible
        $companyId = Auth::user()->company_id;
        $query = Asset::where('company_id', $companyId)->whereIn('id', $assetIds);

        // Apply client scoping if configured
        if ($this->config['client_scoped'] && ! empty($context['client_id'])) {
            $query->where('client_id', $context['client_id']);
        }

        // Apply asset type filter
        if (! empty($this->config['asset_types'])) {
            $query->whereIn('type', $this->config['asset_types']);
        }

        // Apply status filter
        if (! empty($this->config['status_filter'])) {
            $query->whereIn('status', $this->config['status_filter']);
        }

        // Apply excluded assets
        if (! empty($this->config['excluded_assets'])) {
            $query->whereNotIn('id', $this->config['excluded_assets']);
        }

        $validAssets = $query->get();
        $validAssetIds = $validAssets->pluck('id')->toArray();

        // Check for invalid asset IDs
        $invalidIds = array_diff($assetIds, $validAssetIds);
        if (! empty($invalidIds)) {
            $errors[] = 'Selected assets are not valid or accessible: '.implode(', ', $invalidIds);
        }

        // Check maximum selections
        if ($this->config['max_selections'] && count($assetIds) > $this->config['max_selections']) {
            $errors[] = "Maximum {$this->config['max_selections']} assets can be selected";
        }

        // Check required tags
        if (! empty($this->config['required_tags'])) {
            foreach ($validAssets as $asset) {
                $assetTags = $asset->tags ?? [];
                $hasRequiredTags = ! empty(array_intersect($this->config['required_tags'], $assetTags));

                if (! $hasRequiredTags) {
                    $errors[] = "Asset '{$asset->name}' does not have required tags: ".implode(', ', $this->config['required_tags']);
                }
            }
        }

        return empty($errors) ?
            ValidationResult::success(['validated_assets' => $validAssets]) :
            ValidationResult::failure($errors);
    }

    public function render(array $config, $currentValue = null, array $context = []): string
    {
        $this->initialize($config);

        $fieldId = $context['field_id'] ?? 'asset_selector_'.uniqid();
        $fieldName = $context['field_name'] ?? 'asset_ids';
        $multiple = $this->config['multiple'] ? 'multiple' : '';
        $selectedAssets = $this->getSelectedAssets($currentValue, $context);

        $html = '<div class="asset-selector-container" data-config="'.htmlspecialchars(json_encode($this->config)).'">';

        if ($this->config['enable_search']) {
            $html .= '<div class="asset-search-container mb-3">';
            $html .= '<input type="text" class="form-control asset-search-input" placeholder="Search assets..." data-field-id="'.$fieldId.'">';
            $html .= '<div class="asset-search-results"></div>';
            $html .= '</div>';
        }

        $html .= '<select id="'.$fieldId.'" name="'.$fieldName.'[]" class="form-control asset-selector" '.$multiple.' data-client-id="'.($context['client_id'] ?? '').'">';

        // Add selected assets as options
        foreach ($selectedAssets as $asset) {
            $details = $this->config['show_asset_details'] ?
                " ({$asset->type} - {$asset->status})" : '';
            $html .= '<option value="'.$asset->id.'" selected>'.
                     htmlspecialchars($asset->name.$details).'</option>';
        }

        $html .= '</select>';

        if ($this->config['max_selections']) {
            $html .= '<small class="form-text text-muted">Maximum '.$this->config['max_selections'].' assets can be selected</small>';
        }

        $html .= '</div>';

        return $html;
    }

    public function renderDisplay($value, array $config, array $context = []): string
    {
        $this->initialize($config);

        if (empty($value)) {
            return '<span class="text-muted">No assets selected</span>';
        }

        $selectedAssets = $this->getSelectedAssets($value, $context);

        if ($selectedAssets->isEmpty()) {
            return '<span class="text-muted">No assets found</span>';
        }

        $html = '<div class="selected-assets">';

        foreach ($selectedAssets as $asset) {
            $statusBadge = '<span class="badge bg-'.$this->getStatusColor($asset->status).'">'.
                          ucfirst($asset->status).'</span>';

            $html .= '<div class="asset-display-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded">';
            $html .= '<div>';
            $html .= '<strong>'.htmlspecialchars($asset->name).'</strong>';

            if ($this->config['show_asset_details']) {
                $html .= '<br><small class="text-muted">'.
                        htmlspecialchars($asset->type).' • '.
                        htmlspecialchars($asset->serial_number ?? 'N/A').'</small>';
            }

            $html .= '</div>';
            $html .= '<div>'.$statusBadge.'</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function processValue($inputValue, array $config, array $context = []): mixed
    {
        if (empty($inputValue)) {
            return [];
        }

        // Ensure array format
        $assetIds = is_array($inputValue) ? $inputValue : [$inputValue];

        // Filter out empty values and convert to integers
        $assetIds = array_filter(array_map('intval', $assetIds));

        return $config['multiple'] ?? true ? $assetIds : ($assetIds[0] ?? null);
    }

    public function formatValue($value, array $config, array $context = []): mixed
    {
        if (empty($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    public function getFieldType(): string
    {
        return 'asset_selector';
    }

    public function getConfigurationOptions(): array
    {
        return [
            'multiple' => [
                'label' => 'Allow Multiple Selection',
                'type' => 'checkbox',
                'description' => 'Allow users to select multiple assets',
            ],
            'client_scoped' => [
                'label' => 'Client Scoped',
                'type' => 'checkbox',
                'description' => 'Only show assets belonging to the contract client',
            ],
            'show_asset_details' => [
                'label' => 'Show Asset Details',
                'type' => 'checkbox',
                'description' => 'Display additional asset information in the selector',
            ],
            'enable_search' => [
                'label' => 'Enable Search',
                'type' => 'checkbox',
                'description' => 'Allow searching through available assets',
            ],
            'max_selections' => [
                'label' => 'Maximum Selections',
                'type' => 'number',
                'description' => 'Limit the number of assets that can be selected',
            ],
            'asset_types' => [
                'label' => 'Asset Types',
                'type' => 'multiselect',
                'description' => 'Limit selection to specific asset types',
                'options' => $this->getAvailableAssetTypes(),
            ],
            'status_filter' => [
                'label' => 'Status Filter',
                'type' => 'multiselect',
                'description' => 'Only show assets with these statuses',
                'options' => $this->getAvailableStatuses(),
            ],
        ];
    }

    public function getDefaultConfiguration(): array
    {
        return [
            'multiple' => true,
            'client_scoped' => true,
            'show_asset_details' => true,
            'enable_search' => true,
            'asset_types' => [],
            'status_filter' => ['active', 'deployed'],
        ];
    }

    public function getValidationRulesSchema(): array
    {
        return [
            'required' => [
                'description' => 'At least one asset must be selected',
                'params' => [],
            ],
            'min_count' => [
                'description' => 'Minimum number of assets required',
                'params' => ['count' => ['type' => 'integer', 'minimum' => 1]],
            ],
            'max_count' => [
                'description' => 'Maximum number of assets allowed',
                'params' => ['count' => ['type' => 'integer', 'minimum' => 1]],
            ],
            'asset_type' => [
                'description' => 'Assets must be of specific type(s)',
                'params' => ['types' => ['type' => 'array', 'items' => ['type' => 'string']]],
            ],
        ];
    }

    public function getSupportedValidationTypes(): array
    {
        return ['required', 'min_count', 'max_count', 'asset_type'];
    }

    public function isSearchable(): bool
    {
        return true;
    }

    public function isSortable(): bool
    {
        return false; // Asset selections don't have a natural sort order
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function modifySearchQuery($query, string $field, $value, string $operator = '='): mixed
    {
        if (empty($value)) {
            return $query;
        }

        $assetIds = is_array($value) ? $value : [$value];

        switch ($operator) {
            case 'in':
                return $query->whereHas('supportedAssets', function ($q) use ($assetIds) {
                    $q->whereIn('assets.id', $assetIds);
                });

            case 'not_in':
                return $query->whereDoesntHave('supportedAssets', function ($q) use ($assetIds) {
                    $q->whereIn('assets.id', $assetIds);
                });

            case 'count_gte':
                return $query->has('supportedAssets', '>=', (int) $value);

            case 'count_lte':
                return $query->has('supportedAssets', '<=', (int) $value);

            default:
                return $query;
        }
    }

    public function getFilterOptions(array $config): array
    {
        return [
            'has_assets' => [
                'label' => 'Has Assets',
                'type' => 'select',
                'options' => [
                    'yes' => 'Yes',
                    'no' => 'No',
                ],
            ],
            'asset_count' => [
                'label' => 'Asset Count',
                'type' => 'range',
                'min' => 0,
                'max' => 1000,
            ],
            'asset_types' => [
                'label' => 'Asset Types',
                'type' => 'multiselect',
                'options' => $this->getAvailableAssetTypes(),
            ],
        ];
    }

    public function getClientScript(array $config): string
    {
        return '
        document.addEventListener("DOMContentLoaded", function() {
            const assetSelectors = document.querySelectorAll(".asset-selector");
            
            assetSelectors.forEach(function(selector) {
                // Initialize TomSelect for better UX
                if (typeof TomSelect !== "undefined") {
                    new TomSelect(selector, {
                        plugins: ["remove_button"],
                        maxItems: '.($config['max_selections'] ?? 'null').',
                        create: false,
                        load: function(query, callback) {
                            if (query.length < 2) return callback();
                            
                            fetch("/api/assets/search?q=" + encodeURIComponent(query) + 
                                  "&client_id=" + selector.dataset.clientId)
                                .then(response => response.json())
                                .then(data => {
                                    callback(data.map(asset => ({
                                        value: asset.id,
                                        text: asset.name + " (" + asset.type + ")"
                                    })));
                                })
                                .catch(() => callback());
                        }
                    });
                }
            });
            
            // Handle search functionality
            const searchInputs = document.querySelectorAll(".asset-search-input");
            
            searchInputs.forEach(function(input) {
                let searchTimeout;
                
                input.addEventListener("input", function() {
                    clearTimeout(searchTimeout);
                    const query = this.value;
                    const fieldId = this.dataset.fieldId;
                    const resultsContainer = this.parentNode.querySelector(".asset-search-results");
                    
                    if (query.length < 2) {
                        resultsContainer.innerHTML = "";
                        return;
                    }
                    
                    searchTimeout = setTimeout(() => {
                        searchAssets(query, fieldId, resultsContainer);
                    }, 300);
                });
            });
            
            function searchAssets(query, fieldId, resultsContainer) {
                // Implementation for asset search
                // This would make an AJAX call to search for assets
                resultsContainer.innerHTML = "<small>Searching...</small>";
                
                setTimeout(() => {
                    resultsContainer.innerHTML = "<small>Search functionality requires AJAX implementation</small>";
                }, 1000);
            }
        });';
    }

    public function getStyles(array $config): string
    {
        return '
        .asset-selector-container .asset-display-item {
            transition: background-color 0.2s ease;
        }
        
        .asset-selector-container .asset-display-item:hover {
            background-color: #f8f9fa;
        }
        
        .asset-search-results {
            position: relative;
            z-index: 1000;
        }
        
        .asset-selector {
            min-height: 38px;
        }
        
        .selected-assets {
            max-height: 300px;
            overflow-y: auto;
        }';
    }

    public function getFieldDependencies(array $config): array
    {
        $dependencies = [];

        if ($config['client_scoped'] ?? true) {
            $dependencies[] = 'client_id';
        }

        return $dependencies;
    }

    public function shouldDisplay(array $config, array $formData, array $context = []): bool
    {
        // Only display if client is selected and user has permission to view assets
        if ($config['client_scoped'] ?? true) {
            return ! empty($formData['client_id']);
        }

        return Auth::user()->can('view-assets');
    }

    public function getExportValue($value, array $config): mixed
    {
        if (empty($value)) {
            return [];
        }

        $assets = $this->getSelectedAssets($value);

        return $assets->map(function ($asset) {
            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'type' => $asset->type,
                'serial_number' => $asset->serial_number,
                'status' => $asset->status,
            ];
        })->toArray();
    }

    public function importValue($importValue, array $config): mixed
    {
        if (empty($importValue)) {
            return [];
        }

        // If importing asset IDs directly
        if (is_array($importValue) && is_numeric($importValue[0] ?? null)) {
            return array_map('intval', $importValue);
        }

        // If importing asset data, try to find by name or serial number
        $assetIds = [];

        foreach ($importValue as $assetData) {
            $query = Asset::query();

            if (! empty($assetData['id'])) {
                $query->where('id', $assetData['id']);
            } elseif (! empty($assetData['name'])) {
                $query->where('name', $assetData['name']);
            } elseif (! empty($assetData['serial_number'])) {
                $query->where('serial_number', $assetData['serial_number']);
            } else {
                continue;
            }

            $asset = $query->first();
            if ($asset) {
                $assetIds[] = $asset->id;
            }
        }

        return $assetIds;
    }

    public function getContractSummary($value, array $config): array
    {
        if (empty($value)) {
            return [];
        }

        $assets = $this->getSelectedAssets($value);
        $assetsByType = $assets->groupBy('type');

        return [
            'total_assets' => $assets->count(),
            'asset_breakdown' => $assetsByType->map->count()->toArray(),
            'primary_types' => $assetsByType->keys()->take(3)->toArray(),
        ];
    }

    /**
     * Get selected assets
     */
    protected function getSelectedAssets($value, array $context = []): \Illuminate\Support\Collection
    {
        if (empty($value)) {
            return collect();
        }

        $assetIds = is_array($value) ? $value : [$value];
        $companyId = Auth::user()->company_id;

        return Asset::where('company_id', $companyId)
            ->whereIn('id', $assetIds)
            ->get();
    }

    /**
     * Get available asset types
     */
    protected function getAvailableAssetTypes(): array
    {
        return [
            'server' => 'Server',
            'workstation' => 'Workstation',
            'laptop' => 'Laptop',
            'mobile_device' => 'Mobile Device',
            'network_device' => 'Network Device',
            'printer' => 'Printer',
            'other' => 'Other',
        ];
    }

    /**
     * Get available statuses
     */
    protected function getAvailableStatuses(): array
    {
        return [
            'active' => 'Active',
            'deployed' => 'Deployed',
            'maintenance' => 'Maintenance',
            'retired' => 'Retired',
            'inventory' => 'Inventory',
        ];
    }

    /**
     * Get status color for badges
     */
    protected function getStatusColor(string $status): string
    {
        $colors = [
            'active' => 'success',
            'deployed' => 'primary',
            'maintenance' => 'warning',
            'retired' => 'secondary',
            'inventory' => 'info',
        ];

        return $colors[$status] ?? 'secondary';
    }
}
