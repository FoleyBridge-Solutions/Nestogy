@extends('layouts.app')

@section('title', 'Contract Dashboard')

@section('content')
<div class="w-full">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Contract Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-0">Overview of all contract activities and performance</p>
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" color="zinc" type="button"   id="customize-dashboard">
                <i class="fas fa-cog"></i> Customize
            </flux:button>
            <flux:button variant="ghost" color="zinc" type="button"   id="refresh-dashboard">
                <i class="fas fa-refresh"></i> Refresh
            </flux:button>
            <div class="dropdown">
                <flux:button variant="ghost" color="zinc" class="dropdown-toggle" type="button" >
                    <i class="fas fa-download"></i> Export
                </flux:button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" data-export="pdf">Export as PDF</a></li>
                    <li><a class="dropdown-item" href="#" data-export="excel">Export as Excel</a></li>
                    <li><a class="dropdown-item" href="#" data-export="csv">Export as CSV</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="flex flex-wrap mb-4" id="stats-widgets">
        @foreach($statsWidgets as $widget)
            <div class="xl:w-1/4 md:w-1/2 mb-3" data-widget-id="{{ $widget['id'] }}">
                <x-contracts.widgets.stats-card :widget="$widget" />
            </div>
        @endforeach
    </div>

    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid" id="dashboard-grid">
        @foreach($dashboardWidgets as $widget)
            <div class="dashboard-widget" 
                 data-widget-id="{{ $widget['id'] }}" 
                 data-widget-type="{{ $widget['type'] }}"
                 data-grid-position="{{ json_encode($widget['grid_position'] ?? []) }}">
                
                <div class="card widget-card">
                    <div class="card-header flex justify-between items-center">
                        <div class="flex items-center">
                            @if($widget['icon'] ?? false)
                                <i class="{{ $widget['icon'] }} mr-2"></i>
                            @endif
                            <h6 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-0">{{ $widget['title'] }}</h6>
                        </div>
                        <div class="widget-actions">
                            @if($widget['filterable'] ?? false)
                                <button type="button" class="btn px-3 py-1 text-sm  >
                                    <i class="fas fa-filter"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <!-- Widget-specific filters will be populated here -->
                                </div>
                            @endif
                            @if($widget['expandable'] ?? false)
                                <button type="button" class="btn px-3 py-1 text-sm  widget-expand" title="Expand">
                                    <i class="fas fa-expand"></i>
                                </button>
                            @endif
                            <button type="button" class="btn px-3 py-1 text-sm  widget-refresh" title="Refresh">
                                <i class="fas fa-refresh"></i>
                            </button>
                            <div class="dropdown">
                                <button type="button" class="btn px-3 py-1 text-sm  >
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item widget-configure" href="#">Configure</a></li>
                                    <li><a class="dropdown-item widget-clone" href="#">Duplicate</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-red-600 dark:text-red-400 widget-remove" href="#">Remove</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body widget-content">
                        @include("contracts.widgets.{$widget['type']}", ['widget' => $widget])
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Add Widget Area -->
    <div class="text-center py-4" id="add-widget-area">
        <flux:button variant="ghost" class="px-6 py-3 text-lg" type="button"   id="add-widget-btn">
            <i class="fas fa-plus"></i> Add Widget
        </flux:button>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Customize your dashboard by adding widgets</p>
    </div>
</div>

<!-- Widget Customization Modal -->
<flux:modal name="customize-modal" class="max-w-4xl">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Customize Dashboard</flux:heading>
        </div>
        <div class="flex flex-wrap -mx-3">
            <div class="w-full md:w-1/4 px-3">
                <div class="space-y-2" id="widget-categories">
                    <flux:button variant="primary" class="w-full justify-start" data-category="overview">Overview</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start" data-category="financial">Financial</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start" data-category="performance">Performance</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start" data-category="analytics">Analytics</flux:button>
                    <flux:button variant="ghost" class="w-full justify-start" data-category="custom">Custom</flux:button>
                </div>
            </div>
            <div class="w-full md:w-3/4 px-3">
                <div class="widget-library" id="widget-library">
                    <!-- Widget library will be populated via JavaScript -->
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" id="save-dashboard">Save Changes</flux:button>
        </div>
    </div>
</flux:modal>

<!-- Widget Configuration Modal -->
<flux:modal name="widget-config-modal" class="max-w-2xl">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Widget Configuration</flux:heading>
        </div>
        <div id="widget-config-form">
            <!-- Widget configuration form will be populated here -->
        </div>
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" id="save-widget-config">Save Configuration</flux:button>
        </div>
    </div>
</flux:modal>

@endsection

@push('styles')
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
        min-height: 200px;
    }

    .dashboard-widget {
        transition: all 0.3s ease;
    }

    .dashboard-widget.dragging {
        opacity: 0.6;
        transform: rotate(3deg);
    }

    .widget-card {
        height: 100%;
        border: 2px solid transparent;
        transition: all 0.2s ease;
    }

    .widget-card:hover {
        border-color: #dee2e6;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .widget-card.drag-over {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }

    .widget-actions . {
        background: none;
        border: none;
        color: #6c757d;
        padding: 0.25rem 0.5rem;
    }

    .widget-actions . {
        color: #495057;
        background-color: #f8f9fa;
    }

    .widget-content {
        min-height: 200px;
        position: relative;
    }

    .widget-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 150px;
        color: #6c757d;
    }

    .widget-error {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 150px;
        color: #dc3545;
        flex-direction: column;
    }

    .widget-library {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }

    .widget-library-item {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .widget-library-item:hover {
        border-color: #0d6efd;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .widget-library-item.selected {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }

    #add-widget-area {
        border: 2px dashed #dee2e6;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
        transition: all 0.2s ease;
    }

    #add-widget-area:hover {
        border-color: #0d6efd;
        background-color: #e7f3ff;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .widget-actions {
            display: none;
        }
        
        .widget-card:hover .widget-actions {
            display: flex;
        }
    }

    /* Grid layout for larger screens */
    @media (min-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    @media (min-width: 1600px) {
        .dashboard-grid {
            grid-template-columns: repeat(6, 1fr);
        }
    }

    /* Widget size variants */
    .widget-small { grid-column: span 1; grid-flex flex-wrap: span 1; }
    .widget-medium { grid-column: span 2; grid-flex flex-wrap: span 1; }
    .widget-large { grid-column: span 3; grid-flex flex-wrap: span 2; }
    .widget-full { grid-column: 1 / -1; grid-flex flex-wrap: span 1; }

    /* Animation for new widgets */
    @keyframes widgetFadeIn {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .widget-new {
        animation: widgetFadeIn 0.4s ease-out;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dashboard = new ContractDashboard();
});

class ContractDashboard {
    constructor() {
        this.widgets = [];
        this.isDragging = false;
        this.currentWidget = null;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadWidgets();
        this.initializeSortable();
    }

    setupEventListeners() {
        // Customize dashboard
        document.getElementById('customize-dashboard').addEventListener('click', () => {
            this.showCustomizeModal();
        });

        // Refresh dashboard
        document.getElementById('refresh-dashboard').addEventListener('click', () => {
            this.refreshAllWidgets();
        });

        // Add widget
        document.getElementById('add-widget-btn').addEventListener('click', () => {
            this.showCustomizeModal();
        });

        // Save dashboard configuration
        document.getElementById('save-dashboard').addEventListener('click', () => {
            this.saveDashboardConfig();
        });

        // Widget actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.widget-refresh')) {
                const widget = e.target.closest('.dashboard-widget');
                this.refreshWidget(widget.dataset.widgetId);
            } else if (e.target.closest('.widget-configure')) {
                const widget = e.target.closest('.dashboard-widget');
                this.configureWidget(widget.dataset.widgetId);
            } else if (e.target.closest('.widget-clone')) {
                const widget = e.target.closest('.dashboard-widget');
                this.cloneWidget(widget.dataset.widgetId);
            } else if (e.target.closest('.widget-remove')) {
                const widget = e.target.closest('.dashboard-widget');
                this.removeWidget(widget.dataset.widgetId);
            } else if (e.target.closest('.widget-expand')) {
                const widget = e.target.closest('.dashboard-widget');
                this.expandWidget(widget.dataset.widgetId);
            }
        });

        // Export functionality
        document.querySelectorAll('[data-export]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportDashboard(e.target.dataset.export);
            });
        });

        // Widget library selection
        document.addEventListener('click', (e) => {
            if (e.target.closest('.widget-library-item')) {
                const item = e.target.closest('.widget-library-item');
                document.querySelectorAll('.widget-library-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
            }
        });

        // Category navigation
        document.querySelectorAll('[data-category]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-category]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.loadWidgetLibrary(btn.dataset.category);
            });
        });
    }

    loadWidgets() {
        // Load current widget configuration from server
        fetch('/api/contracts/dashboard/widgets')
            .then(response => response.json())
            .then(data => {
                this.widgets = data.widgets || [];
                this.renderWidgets();
            })
            .catch(error => {
                console.error('Failed to load widgets:', error);
                this.showError('Failed to load dashboard widgets');
            });
    }

    renderWidgets() {
        const grid = document.getElementById('dashboard-grid');
        // Widgets are already rendered server-side, just initialize them
        this.widgets.forEach(widget => {
            const element = document.querySelector(`[data-widget-id="${widget.id}"]`);
            if (element) {
                this.initializeWidget(element, widget);
            }
        });
    }

    initializeWidget(element, widget) {
        // Initialize widget-specific functionality
        if (widget.type === 'chart') {
            this.initializeChart(element, widget);
        } else if (widget.type === 'table') {
            this.initializeTable(element, widget);
        } else if (widget.type === 'timeline') {
            this.initializeTimeline(element, widget);
        }

        // Set grid position if specified
        if (widget.grid_position) {
            const pos = widget.grid_position;
            element.style.gridColumn = pos.column ? `${pos.column} / span ${pos.width || 1}` : '';
            element.style.gridRow = pos.flex flex-wrap ? `${pos.flex flex-wrap} / span ${pos.height || 1}` : '';
        }
    }

    initializeSortable() {
        const grid = document.getElementById('dashboard-grid');
        
        // Simple drag and drop implementation
        let draggedElement = null;
        
        grid.addEventListener('dragstart', (e) => {
            if (e.target.closest('.dashboard-widget')) {
                draggedElement = e.target.closest('.dashboard-widget');
                draggedElement.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            }
        });

        grid.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        grid.addEventListener('drop', (e) => {
            e.preventDefault();
            const dropTarget = e.target.closest('.dashboard-widget');
            
            if (dropTarget && draggedElement && dropTarget !== draggedElement) {
                const rect = dropTarget.getBoundingClientRect();
                const midPoint = rect.top + rect.height / 2;
                
                if (e.clientY < midPoint) {
                    dropTarget.parentNode.insertBefore(draggedElement, dropTarget);
                } else {
                    dropTarget.parentNode.insertBefore(draggedElement, dropTarget.nextSibling);
                }
                
                this.saveDashboardLayout();
            }
        });

        grid.addEventListener('dragend', () => {
            if (draggedElement) {
                draggedElement.classList.remove('dragging');
                draggedElement = null;
            }
        });

        // Make widgets draggable
        document.querySelectorAll('.dashboard-widget').forEach(widget => {
            widget.draggable = true;
        });
    }

    showCustomizeModal() {
        const modal = new bootstrap.Modal(document.getElementById('customize-modal'));
        modal.show();
        this.loadWidgetLibrary('overview');
    }

    loadWidgetLibrary(category) {
        const library = document.getElementById('widget-library');
        library.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

        fetch(`/api/contracts/dashboard/widget-library?category=${category}`)
            .then(response => response.json())
            .then(data => {
                this.renderWidgetLibrary(data.widgets);
            })
            .catch(error => {
                console.error('Failed to load widget library:', error);
                library.innerHTML = '<div class="text-center text-red-600 dark:text-red-400">Failed to load widgets</div>';
            });
    }

    renderWidgetLibrary(widgets) {
        const library = document.getElementById('widget-library');
        
        library.innerHTML = widgets.map(widget => `
            <div class="widget-library-item" data-widget-type="${widget.type}">
                <div class="flex items-center mb-2">
                    <i class="${widget.icon} mr-2"></i>
                    <strong>${widget.title}</strong>
                </div>
                <p class="text-gray-600 dark:text-gray-400 small mb-0">${widget.description}</p>
                <div class="mt-2">
                    ${widget.tags.map(tag => `<span class="badge bg-light text-dark mr-1">${tag}</span>`).join('')}
                </div>
            </div>
        `).join('');

        // Add click handlers for library items
        library.querySelectorAll('.widget-library-item').forEach(item => {
            item.addEventListener('dblclick', () => {
                this.addWidget(item.dataset.widgetType);
            });
        });
    }

    addWidget(type) {
        const widgetConfig = {
            type: type,
            title: 'New Widget',
            settings: {}
        };

        fetch('/api/contracts/dashboard/widgets', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(widgetConfig)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh to show new widget
            } else {
                this.showError('Failed to add widget: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Failed to add widget:', error);
            this.showError('Failed to add widget');
        });
    }

    refreshWidget(widgetId) {
        const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
        const content = widget.querySelector('.widget-content');
        
        content.innerHTML = '<div class="widget-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

        fetch(`/api/contracts/dashboard/widgets/${widgetId}/refresh`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = '<div class="widget-error"><i class="fas fa-exclamation-triangle mb-2"></i>Failed to load widget</div>';
            }
        })
        .catch(error => {
            console.error('Failed to refresh widget:', error);
            content.innerHTML = '<div class="widget-error"><i class="fas fa-exclamation-triangle mb-2"></i>Failed to load widget</div>';
        });
    }

    refreshAllWidgets() {
        document.querySelectorAll('.dashboard-widget').forEach(widget => {
            this.refreshWidget(widget.dataset.widgetId);
        });
    }

    configureWidget(widgetId) {
        fetch(`/api/contracts/dashboard/widgets/${widgetId}/config`)
            .then(response => response.json())
            .then(data => {
                this.showWidgetConfigModal(data.widget, data.config);
            })
            .catch(error => {
                console.error('Failed to load widget config:', error);
                this.showError('Failed to load widget configuration');
            });
    }

    showWidgetConfigModal(widget, config) {
        const modal = new bootstrap.Modal(document.getElementById('widget-config-modal'));
        const form = document.getElementById('widget-config-form');
        
        // Generate configuration form based on widget type
        form.innerHTML = this.generateConfigForm(widget, config);
        
        modal.show();
        
        // Store current widget for saving
        this.currentWidget = widget;
    }

    generateConfigForm(widget, config) {
        // This would generate a dynamic form based on widget configuration options
        return `
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Widget Title</label>
                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="title" value="${widget.title}">
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Refresh Interval (seconds)</label>
                <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="refresh_interval">
                    <option value="0">Manual only</option>
                    <option value="30" ${widget.refresh_interval == 30 ? 'selected' : ''}>30 seconds</option>
                    <option value="60" ${widget.refresh_interval == 60 ? 'selected' : ''}>1 minute</option>
                    <option value="300" ${widget.refresh_interval == 300 ? 'selected' : ''}>5 minutes</option>
                    <option value="900" ${widget.refresh_interval == 900 ? 'selected' : ''}>15 minutes</option>
                </select>
            </div>
            <!-- Additional widget-specific configuration would be added here -->
        `;
    }

    cloneWidget(widgetId) {
        if (confirm('Clone this widget?')) {
            fetch(`/api/contracts/dashboard/widgets/${widgetId}/clone`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    this.showError('Failed to clone widget: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Failed to clone widget:', error);
                this.showError('Failed to clone widget');
            });
        }
    }

    removeWidget(widgetId) {
        if (confirm('Remove this widget from your dashboard?')) {
            fetch(`/api/contracts/dashboard/widgets/${widgetId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`[data-widget-id="${widgetId}"]`).remove();
                } else {
                    this.showError('Failed to remove widget: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Failed to remove widget:', error);
                this.showError('Failed to remove widget');
            });
        }
    }

    expandWidget(widgetId) {
        const widget = document.querySelector(`[data-widget-id="${widgetId}"]`);
        // Toggle full-width class
        widget.classList.toggle('widget-full');
        this.saveDashboardLayout();
    }

    saveDashboardConfig() {
        const widgets = Array.from(document.querySelectorAll('.dashboard-widget')).map((element, index) => ({
            id: element.dataset.widgetId,
            type: element.dataset.widgetType,
            order: index,
            grid_position: {
                column: element.style.gridColumn,
                flex flex-wrap: element.style.gridRow
            }
        }));

        fetch('/api/contracts/dashboard/layout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ widgets })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('customize-modal')).hide();
                this.showSuccess('Dashboard saved successfully');
            } else {
                this.showError('Failed to save dashboard: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Failed to save dashboard:', error);
            this.showError('Failed to save dashboard');
        });
    }

    saveDashboardLayout() {
        this.saveDashboardConfig();
    }

    exportDashboard(format) {
        const url = `/api/contracts/dashboard/export?format=${format}`;
        window.open(url, '_blank');
    }

    initializeChart(element, widget) {
        // Initialize chart widgets (would integrate with Chart.js)
        const canvas = element.querySelector('canvas');
        if (canvas && window.Chart) {
            // Chart initialization code
        }
    }

    initializeTable(element, widget) {
        // Initialize table widgets
        const table = element.querySelector('table');
        if (table) {
            // Table enhancement code
        }
    }

    initializeTimeline(element, widget) {
        // Initialize timeline widgets
        const timeline = element.querySelector('.timeline');
        if (timeline) {
            // Timeline initialization code
        }
    }

    showError(message) {
        // Show error notification
        alert('Error: ' + message);
    }

    showSuccess(message) {
        // Show success notification
        alert('Success: ' + message);
    }
}
</script>
@endpush
