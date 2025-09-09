@props(['contractType', 'contracts', 'columns', 'filters' => [], 'actions' => [], 'bulk_actions' => [], 'pagination' => true])

@php
    // Get contract type configuration
    $typeConfig = $contractType ?? [];
    $listConfig = $typeConfig['list_config'] ?? [];
    
    // Extract list configuration
    $searchable = $listConfig['searchable'] ?? true;
    $sortable = $listConfig['sortable'] ?? true;
    $filterable = $listConfig['filterable'] ?? true;
    $exportable = $listConfig['exportable'] ?? true;
    $bulkActions = $listConfig['bulk_actions'] ?? $bulk_actions;
    $rowActions = $listConfig['row_actions'] ?? $actions;
    $pageSize = $listConfig['page_size'] ?? 25;
    $showStats = $listConfig['show_stats'] ?? true;
    $compactMode = $listConfig['compact_mode'] ?? false;
    $groupBy = $listConfig['group_by'] ?? null; // Group contracts by field
    
    // Normalize columns configuration
    $displayColumns = [];
    foreach ($columns as $column) {
        if (is_string($column)) {
            $displayColumns[] = [
                'key' => $column,
                'label' => ucfirst(str_replace('_', ' ', $column)),
                'sortable' => true,
                'searchable' => true,
                'type' => 'text'
            ];
        } else {
            $displayColumns[] = array_merge([
                'sortable' => true,
                'searchable' => false,
                'type' => 'text',
                'align' => 'left',
                'width' => null,
                'format' => null
            ], $column);
        }
    }
    
    // Default bulk actions
    $defaultBulkActions = [
        'delete' => ['label' => 'Delete Selected', 'icon' => 'fas fa-trash', 'color' => 'danger', 'confirm' => true],
        'export' => ['label' => 'Export Selected', 'icon' => 'fas fa-download', 'color' => 'primary'],
        'archive' => ['label' => 'Archive Selected', 'icon' => 'fas fa-archive', 'color' => 'secondary']
    ];
    
    // Default flex flex-wrap actions
    $defaultRowActions = [
        'view' => ['label' => 'View', 'icon' => 'fas fa-eye', 'color' => 'primary'],
        'edit' => ['label' => 'Edit', 'icon' => 'fas fa-edit', 'color' => 'warning'],
        'delete' => ['label' => 'Delete', 'icon' => 'fas fa-trash', 'color' => 'danger', 'confirm' => true]
    ];
    
    $availableBulkActions = array_merge($defaultBulkActions, $bulkActions);
    $availableRowActions = array_merge($defaultRowActions, $rowActions);
    
    // Generate unique table ID
    $tableId = 'contracts_table_' . uniqid();
@endphp

<div class="dynamic-contract-list" data-contract-type="{{ $typeConfig['slug'] ?? 'default' }}">
    {{-- List Header --}}
    <div class="list-header">
        <div class="flex flex-wrap -mx-4 items-center mb-6">
            <div class="flex-1 px-6-md-6">
                <h4 class="mb-0">
                    {{ $typeConfig['plural_label'] ?? 'Contracts' }}
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary ml-2" id="total-count">
                        {{ $contracts->total() ?? count($contracts) }}
                    </span>
                </h4>
                @if($showStats)
                    <div class="contract-stats mt-2" id="contract-stats">
                        <small class="text-gray-600 dark:text-gray-400">Loading stats...</small>
                    </div>
                @endif
            </div>
            <div class="flex-1 px-6-md-6 text-end">
                <div class="list-actions">
                    @if($searchable)
                        <div class="search-container mx-auto inline-block mr-2">
                            <div class="flex">
                                <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="global-search" 
                                       placeholder="Search contracts..." />
                                <button class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" type="button" id="clear-search">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                    
                    @if($filterable)
                        <button class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-primary mr-2"  
                                 id="toggle-filters">
                            <i class="fas fa-filter"></i> Filters
                            <span class="filter-count inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary ml-1" style="display: none;">0</span>
                        </button>
                    @endif
                    
                    @if($exportable)
                        <div class="px-6 py-2 font-medium rounded-md transition-colors-group mr-2">
                            <button class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-success dropdown-toggle" 
                                     id="export-dropdown">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-format="csv">
                                    <i class="fas fa-file-csv"></i> CSV
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-format="excel">
                                    <i class="fas fa-file-excel"></i> Excel
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-format="pdf">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a></li>
                            </ul>
                        </div>
                    @endif
                    
                    <a href="{{ route('contracts.create', ['type' => $typeConfig['slug'] ?? 'default']) }}" 
                       class="btn px-6 py-2 font-medium rounded-md transition-colors-primary">
                        <i class="fas fa-plus"></i> Create {{ $typeConfig['label'] ?? 'Contract' }}
                    </a>
                </div>
            </div>
        </div>
        
        {{-- Advanced Filters Panel --}}
        @if($filterable)
            <div class="collapse" id="filters-panel">
                <div class="card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body mb-6">
                    <form id="filters-form" class="flex flex-wrap -mx-4 g-3">
                        @foreach($filters as $filter)
                            <div class="flex-1 px-6-md-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $filter['label'] }}</label>
                                @if($filter['type'] === 'select')
                                    <select name="filter[{{ $filter['key'] }}]" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm filter-input">
                                        <option value="">All {{ $filter['label'] }}</option>
                                        @foreach($filter['options'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @elseif($filter['type'] === 'date')
                                    <input type="date" name="filter[{{ $filter['key'] }}]" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm filter-input" />
                                @elseif($filter['type'] === 'daterange')
                                    <div class="flex">
                                        <input type="date" name="filter[{{ $filter['key'] }}][from]" 
                                               class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm filter-input" placeholder="From" />
                                        <span class="flex-text">to</span>
                                        <input type="date" name="filter[{{ $filter['key'] }}][to]" 
                                               class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm filter-input" placeholder="To" />
                                    </div>
                                @else
                                    <input type="text" name="filter[{{ $filter['key'] }}]" 
                                           class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm filter-input" 
                                           placeholder="Enter {{ $filter['label'] }}" />
                                @endif
                            </div>
                        @endforeach
                        <div class="flex-1 px-6-12">
                            <button type="submit" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary mr-2">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" id="clear-filters">
                                <i class="fas fa-times"></i> Clear All
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
        
        {{-- Bulk Actions Bar --}}
        @if(!empty($availableBulkActions))
            <div class="bulk-actions-bar alert px-6 py-6 rounded mb-6-info" style="display: none;" id="bulk-actions-bar">
                <div class="flex items-center justify-between">
                    <div>
                        <strong id="selected-count">0</strong> contracts selected
                    </div>
                    <div class="bulk-actions">
                        @foreach($availableBulkActions as $actionKey => $action)
                            <flux:button variant="primary" class="$action['color'] }} px-6 py-2 font-medium rounded-md transition-colors-sm mr-2" data-action="{{ $actionKey }}"
                                    @if($action['confirm'] ?? false) data-confirm="true" @endif>
                                <i class="{{ $action['icon'] }}"></i> {{ $action['label'] }}
                            </flux:button>
                        @endforeach
                        <button class="btn border border-gray-600 text-gray-600 hover:bg-gray-50 px-6 py-2 font-medium rounded-md transition-colors-sm" id="clear-selection">
                            Clear Selection
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
    
    {{-- Contracts Table --}}
    <div class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-container mx-auto">
        <div class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-responsive">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 {{ $compactMode ? 'min-w-full divide-y divide-gray-200 dark:divide-gray-700-sm' : '' }}" id="{{ $tableId }}">
                <thead class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-light">
                    <tr>
                        @if(!empty($availableBulkActions))
                            <th width="40">
                                <input type="checkbox" class="flex items-center-input" id="select-all" />
                            </th>
                        @endif
                        
                        @foreach($displayColumns as $column)
                            <th @if($column['width']) width="{{ $column['width'] }}" @endif
                                @if($column['sortable']) 
                                    class="sortable" 
                                    data-sort="{{ $column['key'] }}" 
                                    style="cursor: pointer;" 
                                @endif>
                                <div class="flex items-center justify-content-{{ $column['align'] === 'center' ? 'center' : ($column['align'] === 'right' ? 'end' : 'start') }}">
                                    {{ $column['label'] }}
                                    @if($column['sortable'])
                                        <i class="fas fa-sort text-gray-600 dark:text-gray-400 ml-1 sort-icon"></i>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                        
                        @if(!empty($availableRowActions))
                            <th width="120" class="text-center" scope="col-span-12">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="contracts-tbody">
                    @if($contracts->count() > 0)
                        @foreach($contracts as $contract)
                            <tr class="contract-flex flex-wrap -mx-4" data-id="{{ $contract->id }}">
                                @if(!empty($availableBulkActions))
                                    <td>
                                        <input type="checkbox" class="flex items-center-input flex flex-wrap -mx-4-checkbox" 
                                               value="{{ $contract->id }}" />
                                    </td>
                                @endif
                                
                                @foreach($displayColumns as $column)
                                    <td class="text-{{ $column['align'] }}">
                                        @php
                                            $value = data_get($contract, $column['key']);
                                            
                                            // Format value based on type
                                            if ($column['format'] && is_callable($column['format'])) {
                                                $displayValue = $column['format']($value, $contract);
                                            } elseif ($column['type'] === 'currency') {
                                                $displayValue = '$' . number_format($value, 2);
                                            } elseif ($column['type'] === 'date') {
                                                $displayValue = $value ? \Carbon\Carbon::parse($value)->format('M j, Y') : '-';
                                            } elseif ($column['type'] === 'datetime') {
                                                $displayValue = $value ? \Carbon\Carbon::parse($value)->format('M j, Y g:i A') : '-';
                                            } elseif ($column['type'] === 'status') {
                                                $statusConfig = $typeConfig['statuses'][$value] ?? [];
                                                $color = $statusConfig['color'] ?? 'secondary';
                                                $displayValue = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-' . $color . '">' . ($statusConfig['label'] ?? $value) . '</span>';
                                            } elseif ($column['type'] === 'boolean') {
                                                $displayValue = $value ? 
                                                    '<i class="fas fa-check text-green-600 dark:text-green-400"></i>' : 
                                                    '<i class="fas fa-times text-red-600 dark:text-red-400"></i>';
                                            } elseif ($column['type'] === 'progress') {
                                                $percentage = is_numeric($value) ? $value : 0;
                                                $color = $percentage >= 75 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger');
                                                $displayValue = '
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-' . $color . '" 
                                                             style="width: ' . $percentage . '%"></div>
                                                    </div>
                                                    <small>' . $percentage . '%</small>';
                                            } elseif ($column['type'] === 'link') {
                                                $url = $column['url'] ?? '#';
                                                if (is_callable($column['url'])) {
                                                    $url = $column['url']($contract);
                                                }
                                                $displayValue = '<a href="' . $url . '">' . $value . '</a>';
                                            } else {
                                                $displayValue = $value ?: '-';
                                            }
                                        @endphp
                                        
                                        {!! $displayValue !!}
                                    </td>
                                @endforeach
                                
                                @if(!empty($availableRowActions))
                                    <td class="text-center">
                                        <div class="px-6 py-2 font-medium rounded-md transition-colors-group">
                                            @foreach($availableRowActions as $actionKey => $action)
                                                @php
                                                    $url = route("contracts.{$actionKey}", $contract->id);
                                                    if (isset($action['url']) && is_callable($action['url'])) {
                                                        $url = $action['url']($contract);
                                                    }
                                                @endphp
                                                
                                                @if($actionKey === 'delete')
                                                    <form method="POST" action="{{ $url }}" class="inline delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <flux:button variant="primary" class="$action['color'] }} px-6 py-2 font-medium rounded-md transition-colors-sm" type="submit" 
                                                                 
                                                                title="{{ $action['label'] }}"
                                                                onclick="return confirm('Are you sure?')">
                                                            <i class="{{ $action['icon'] }}"></i>
                                                        </flux:button>
                                                    </form>
                                                @else
                                                    <flux:button variant="primary" href="{{ $url }}" class="$action['color'] }} px-6 py-2 font-medium rounded-md transition-colors-sm" title="{{ $action['label'] }}">
                                                        <i class="{{ $action['icon'] }}"></i>
                                                    </flux:button>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="{{ count($displayColumns) + (!empty($availableBulkActions) ? 1 : 0) + (!empty($availableRowActions) ? 1 : 0) }}" 
                                class="text-center py-8">
                                <div class="empty-state">
                                    <i class="fas fa-file-contract fa-3x text-gray-600 dark:text-gray-400 mb-6"></i>
                                    <h5 class="text-gray-600 dark:text-gray-400">No {{ $typeConfig['plural_label'] ?? 'contracts' }} found</h5>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        @if(request()->has('search') || request()->has('filter'))
                                            Try adjusting your search criteria or filters
                                        @else
                                            Get started by creating your first {{ $typeConfig['label'] ?? 'contract' }}
                                        @endif
                                    </p>
                                    @if(!request()->has('search') && !request()->has('filter'))
                                        <a href="{{ route('contracts.create', ['type' => $typeConfig['slug'] ?? 'default']) }}" 
                                           class="btn px-6 py-2 font-medium rounded-md transition-colors-primary">
                                            <i class="fas fa-plus"></i> Create {{ $typeConfig['label'] ?? 'Contract' }}
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- Pagination --}}
    @if($pagination && method_exists($contracts, 'links'))
        <div class="flex justify-between items-center mt-6">
            <div class="pagination-info">
                <small class="text-gray-600 dark:text-gray-400">
                    Showing {{ $contracts->firstItem() ?: 0 }} to {{ $contracts->lastItem() ?: 0 }} 
                    of {{ $contracts->total() }} results
                </small>
            </div>
            <div>
                {{ $contracts->appends(request()->query())->links() }}
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableContainer = document.querySelector('.dynamic-contract-list');
        const table = document.getElementById('{{ $tableId }}');
        const tbody = document.getElementById('contracts-tbody');
        const selectAllCheckbox = document.getElementById('select-all');
        const bulkActionsBar = document.getElementById('bulk-actions-bar');
        const selectedCountSpan = document.getElementById('selected-count');
        const globalSearch = document.getElementById('global-search');
        const filtersForm = document.getElementById('filters-form');
        
        let selectedRows = new Set();
        let currentSort = { column: null, direction: 'asc' };
        let currentFilters = {};
        let searchTimeout;
        
        // Initialize
        initializeTable();
        
        function initializeTable() {
            setupBulkSelection();
            setupSorting();
            setupSearch();
            setupFilters();
            setupExport();
            loadStats();
        }
        
        // Bulk selection functionality
        function setupBulkSelection() {
            if (!selectAllCheckbox) return;
            
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = table.querySelectorAll('.flex flex-wrap-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    updateRowSelection(checkbox);
                });
                updateBulkActionsBar();
            });
            
            // Individual flex flex-wrap checkboxes
            table.addEventListener('change', function(e) {
                if (e.target.classList.contains('flex flex-wrap-checkbox')) {
                    updateRowSelection(e.target);
                    updateSelectAllState();
                    updateBulkActionsBar();
                }
            });
            
            // Bulk action buttons
            document.querySelectorAll('[data-action]').forEach(button => {
                button.addEventListener('click', function() {
                    const action = this.dataset.action;
                    const needsConfirm = this.dataset.confirm === 'true';
                    
                    if (selectedRows.size === 0) {
                        alert('Please select contracts first');
                        return;
                    }
                    
                    if (needsConfirm && !confirm(`Are you sure you want to ${action} ${selectedRows.size} contracts?`)) {
                        return;
                    }
                    
                    performBulkAction(action, Array.from(selectedRows));
                });
            });
            
            // Clear selection button
            document.getElementById('clear-selection')?.addEventListener('click', function() {
                clearSelection();
            });
        }
        
        function updateRowSelection(checkbox) {
            const contractId = checkbox.value;
            if (checkbox.checked) {
                selectedRows.add(contractId);
                checkbox.closest('tr').classList.add('table-warning');
            } else {
                selectedRows.delete(contractId);
                checkbox.closest('tr').classList.remove('table-warning');
            }
        }
        
        function updateSelectAllState() {
            const checkboxes = table.querySelectorAll('.flex flex-wrap-checkbox');
            const checkedCount = table.querySelectorAll('.flex flex-wrap-checkbox:checked').length;
            
            selectAllCheckbox.checked = checkedCount === checkboxes.length && checkboxes.length > 0;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        }
        
        function updateBulkActionsBar() {
            if (selectedRows.size > 0) {
                bulkActionsBar.style.display = 'block';
                selectedCountSpan.textContent = selectedRows.size;
            } else {
                bulkActionsBar.style.display = 'none';
            }
        }
        
        function clearSelection() {
            selectedRows.clear();
            table.querySelectorAll('.flex flex-wrap-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('tr').classList.remove('table-warning');
            });
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            updateBulkActionsBar();
        }
        
        // Sorting functionality
        function setupSorting() {
            @if($sortable)
            table.addEventListener('click', function(e) {
                const sortableHeader = e.target.closest('.sortable');
                if (!sortableHeader) return;
                
                const column = sortableHeader.dataset.sort;
                const currentDirection = currentSort.column === column ? currentSort.direction : 'asc';
                const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                
                // Update sort icons
                table.querySelectorAll('.sort-icon').forEach(icon => {
                    icon.className = 'fas fa-sort text-gray-600 dark:text-gray-400 ml-1 sort-icon';
                });
                
                const icon = sortableHeader.querySelector('.sort-icon');
                icon.className = `fas fa-sort-${newDirection === 'asc' ? 'up' : 'down'} text-blue-600 dark:text-blue-400 ml-1 sort-icon`;
                
                currentSort = { column, direction: newDirection };
                reloadTable();
            });
            @endif
        }
        
        // Search functionality
        function setupSearch() {
            @if($searchable)
            globalSearch?.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    reloadTable();
                }, 300);
            });
            
            document.getElementById('clear-search')?.addEventListener('click', function() {
                globalSearch.value = '';
                reloadTable();
            });
            @endif
        }
        
        // Filters functionality
        function setupFilters() {
            @if($filterable)
            filtersForm?.addEventListener('submit', function(e) {
                e.preventDefault();
                applyFilters();
            });
            
            document.getElementById('clear-filters')?.addEventListener('click', function() {
                filtersForm.reset();
                currentFilters = {};
                updateFilterCount();
                reloadTable();
            });
            
            // Auto-apply filters on change
            filtersForm?.addEventListener('change', function(e) {
                if (e.target.classList.contains('filter-input')) {
                    setTimeout(applyFilters, 100);
                }
            });
            @endif
        }
        
        function applyFilters() {
            const formData = new FormData(filtersForm);
            currentFilters = {};
            
            for (const [key, value] of formData.entries()) {
                if (value.trim()) {
                    currentFilters[key] = value;
                }
            }
            
            updateFilterCount();
            reloadTable();
        }
        
        function updateFilterCount() {
            const filterCount = Object.keys(currentFilters).length;
            const badge = document.querySelector('.filter-count');
            
            if (filterCount > 0) {
                badge.textContent = filterCount;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Export functionality
        function setupExport() {
            @if($exportable)
            document.querySelectorAll('[data-format]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const format = this.dataset.format;
                    exportData(format);
                });
            });
            @endif
        }
        
        function exportData(format) {
            const params = new URLSearchParams();
            params.append('format', format);
            params.append('type', '{{ $typeConfig['slug'] ?? 'default' }}');
            
            // Add current filters and search
            if (globalSearch?.value) {
                params.append('search', globalSearch.value);
            }
            
            Object.entries(currentFilters).forEach(([key, value]) => {
                params.append(key, value);
            });
            
            // Add selected rows if any
            if (selectedRows.size > 0) {
                params.append('ids', Array.from(selectedRows).join(','));
            }
            
            window.open(`{{ route('contracts.export') }}?${params.toString()}`, '_blank');
        }
        
        // Reload table with current parameters
        function reloadTable() {
            const params = new URLSearchParams(window.location.search);
            
            // Update search parameter
            if (globalSearch?.value) {
                params.set('search', globalSearch.value);
            } else {
                params.delete('search');
            }
            
            // Update sort parameters
            if (currentSort.column) {
                params.set('sort', currentSort.column);
                params.set('direction', currentSort.direction);
            }
            
            // Update filter parameters
            Object.entries(currentFilters).forEach(([key, value]) => {
                params.set(key, value);
            });
            
            // Update URL without page reload
            const newUrl = `${window.location.pathname}?${params.toString()}`;
            window.history.pushState({}, '', newUrl);
            
            // Load new data via AJAX
            loadTableData(params.toString());
        }
        
        // Load table data via AJAX
        function loadTableData(queryString) {
            const url = `{{ request()->url() }}?${queryString}&ajax=1`;
            
            tbody.innerHTML = '<tr><td colspan="100%" class="text-center py-6"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.html) {
                        tbody.innerHTML = data.html;
                    }
                    
                    if (data.pagination) {
                        updatePagination(data.pagination);
                    }
                    
                    if (data.stats) {
                        updateStats(data.stats);
                    }
                    
                    document.getElementById('total-count').textContent = data.total || 0;
                    
                    clearSelection();
                })
                .catch(error => {
                    console.error('Error loading table data:', error);
                    tbody.innerHTML = '<tr><td colspan="100%" class="text-center py-6 text-red-600 dark:text-red-400"><i class="fas fa-exclamation-triangle"></i> Error loading data</td></tr>';
                });
        }
        
        // Bulk actions
        function performBulkAction(action, contractIds) {
            const url = `{{ route('contracts.bulk') }}`;
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('action', action);
            formData.append('ids', contractIds.join(','));
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage('success', data.message || `${action} completed successfully`);
                    reloadTable();
                    clearSelection();
                } else {
                    showMessage('error', data.message || 'Operation failed');
                }
            })
            .catch(error => {
                console.error('Bulk action error:', error);
                showMessage('error', 'Operation failed');
            });
        }
        
        // Load and display stats
        function loadStats() {
            @if($showStats)
            const statsContainer = document.getElementById('contract-stats');
            if (!statsContainer) return;
            
            fetch(`{{ route('contracts.stats') }}?type={{ $typeConfig['slug'] ?? 'default' }}`)
                .then(response => response.json())
                .then(data => {
                    const statsHtml = Object.entries(data).map(([key, value]) => {
                        const label = key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                        return `<span class="mr-4"><strong>${value}</strong> ${label}</span>`;
                    }).join('');
                    
                    statsContainer.innerHTML = `<small class="text-gray-600 dark:text-gray-400">${statsHtml}</small>`;
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                });
            @endif
        }
        
        // Utility functions
        function showMessage(type, message) {
            // Implementation depends on your notification system
            // This could integrate with toastr, bootstrap alerts, etc.
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} px-6 py-6 rounded mb-6-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" ></button>
                </div>
            `;
            
            // Insert at top of container
            tableContainer.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                const alert = tableContainer.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }
        
        function updatePagination(paginationHtml) {
            const paginationContainer = document.querySelector('.pagination');
            if (paginationContainer) {
                paginationContainer.innerHTML = paginationHtml;
            }
        }
        
        function updateStats(stats) {
            const statsContainer = document.getElementById('contract-stats');
            if (statsContainer && stats) {
                const statsHtml = Object.entries(stats).map(([key, value]) => {
                    const label = key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    return `<span class="mr-4"><strong>${value}</strong> ${label}</span>`;
                }).join('');
                
                statsContainer.innerHTML = `<small class="text-gray-600 dark:text-gray-400">${statsHtml}</small>`;
            }
        }
    });
</script>
@endpush
