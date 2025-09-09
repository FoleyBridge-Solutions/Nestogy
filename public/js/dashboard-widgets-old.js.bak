/**
 * Executive Dashboard Widget Management System
 * Handles widget lifecycle, data fetching, rendering, and local storage
 */

function executiveDashboard() {
    return {
        // Core State
        widgets: [],
        availableWidgets: [],
        presets: [],
        gridColumns: 12,
        editMode: false,
        showWidgetLibrary: false,
        showSettings: false,
        lastUpdated: 'Never',
        refreshInterval: null,
        sortableInstance: null,
        
        // Settings (persisted to localStorage)
        settings: {
            theme: 'auto',
            refreshInterval: 30, // seconds
            animations: true,
            compactMode: false,
        },
        
        // Widget Type Definitions
        widgetTypes: {
            revenue_kpi: {
                name: 'Revenue KPI',
                description: 'Total revenue with growth metrics',
                icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>',
                color: 'bg-green-100 text-green-600',
                defaultSize: { w: 3, h: 2 },
                minRefresh: 30,
            },
            mrr_kpi: {
                name: 'Monthly Recurring Revenue',
                description: 'MRR with new and churned breakdown',
                icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>',
                color: 'bg-blue-100 text-blue-600',
                defaultSize: { w: 3, h: 2 },
                minRefresh: 60,
            },
            ticket_status: {
                name: 'Ticket Status',
                description: 'Live ticket counts by status and priority',
                icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"/></svg>',
                color: 'bg-orange-100 text-orange-600',
                defaultSize: { w: 3, h: 2 },
                minRefresh: 10,
            },
            client_health: {
                name: 'Client Health Monitor',
                description: 'Real-time client health scores',
                icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>',
                color: 'bg-red-100 text-red-600',
                defaultSize: { w: 4, h: 3 },
                minRefresh: 30,
            },
            revenue_chart: {
                name: 'Revenue Trend Chart',
                description: 'Interactive revenue visualization',
                icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/></svg>',
                color: 'bg-purple-100 text-purple-600',
                defaultSize: { w: 6, h: 4 },
                minRefresh: 60,
            },
            team_performance: {
                name: 'Team Performance',
                description: 'Team productivity and efficiency metrics',
                icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>',
                color: 'bg-indigo-100 text-indigo-600',
                defaultSize: { w: 4, h: 3 },
                minRefresh: 30,
            },
            activity_feed: {
                name: 'Activity Feed',
                description: 'Real-time activity stream',
                icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>',
                color: 'bg-yellow-100 text-yellow-600',
                defaultSize: { w: 4, h: 4 },
                minRefresh: 10,
            },
            alerts: {
                name: 'System Alerts',
                description: 'Critical alerts and notifications',
                icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>',
                color: 'bg-red-100 text-red-600',
                defaultSize: { w: 4, h: 3 },
                minRefresh: 10,
            },
            forecast: {
                name: 'Revenue Forecast',
                description: 'AI-powered revenue predictions',
                icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>',
                color: 'bg-teal-100 text-teal-600',
                defaultSize: { w: 4, h: 3 },
                minRefresh: 300,
            },
        },
        
        // Initialize dashboard
        async init() {
            console.log('Initializing Executive Dashboard...');
            
            // Load settings from localStorage
            this.loadSettings();
            
            // Apply theme
            this.applyTheme();
            
            // Load saved configuration or defaults
            await this.loadDashboardConfig();
            
            // Load available widgets
            this.loadAvailableWidgets();
            
            // Load presets
            await this.loadPresets();
            
            // Initialize grid system
            this.initializeGrid();
            
            // Start data refresh cycle
            this.startRefreshCycle();
            
            // Set up auto-save
            this.setupAutoSave();
            
            console.log('Dashboard initialized successfully');
        },
        
        // Load settings from localStorage
        loadSettings() {
            const saved = localStorage.getItem('dashboard_settings');
            if (saved) {
                try {
                    this.settings = { ...this.settings, ...JSON.parse(saved) };
                } catch (e) {
                    console.error('Failed to load settings:', e);
                }
            }
            
            // Load grid columns
            const gridCols = localStorage.getItem('dashboard_grid_columns');
            if (gridCols) {
                this.gridColumns = parseInt(gridCols);
            }
        },
        
        // Save settings to localStorage
        saveSettings() {
            localStorage.setItem('dashboard_settings', JSON.stringify(this.settings));
            localStorage.setItem('dashboard_grid_columns', this.gridColumns.toString());
        },
        
        // Apply theme
        applyTheme() {
            const theme = this.settings.theme;
            
            if (theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        
        // Set theme
        setTheme(theme) {
            this.settings.theme = theme;
            this.applyTheme();
            this.saveSettings();
        },
        
        // Load dashboard configuration
        async loadDashboardConfig() {
            try {
                // First check localStorage
                const localConfig = localStorage.getItem('dashboard_config');
                if (localConfig) {
                    const config = JSON.parse(localConfig);
                    this.widgets = config.widgets || [];
                    this.gridColumns = config.gridColumns || 12;
                    console.log('Loaded config from localStorage');
                    
                    // Sync with server in background
                    this.syncWithServer();
                } else {
                    // Load from server
                    const response = await fetch('/api/dashboard/config/load', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        if (data.config) {
                            this.widgets = data.config.widgets || [];
                            this.gridColumns = data.config.layout?.columns || 12;
                            this.saveToLocalStorage();
                        } else {
                            this.loadDefaultConfig();
                        }
                    } else {
                        this.loadDefaultConfig();
                    }
                }
                
                // Load widget data
                await this.refreshAllWidgets();
                
            } catch (error) {
                console.error('Failed to load dashboard config:', error);
                this.loadDefaultConfig();
            }
        },
        
        // Load default configuration
        loadDefaultConfig() {
            this.widgets = [
                { id: 'w1', type: 'revenue_kpi', x: 0, y: 0, w: 3, h: 2, config: {} },
                { id: 'w2', type: 'mrr_kpi', x: 3, y: 0, w: 3, h: 2, config: {} },
                { id: 'w3', type: 'ticket_status', x: 6, y: 0, w: 3, h: 2, config: {} },
                { id: 'w4', type: 'client_health', x: 9, y: 0, w: 3, h: 2, config: {} },
                { id: 'w5', type: 'revenue_chart', x: 0, y: 2, w: 6, h: 4, config: { period: 'last_30_days' } },
                { id: 'w6', type: 'team_performance', x: 6, y: 2, w: 6, h: 4, config: {} },
            ];
            this.saveToLocalStorage();
        },
        
        // Save to localStorage
        saveToLocalStorage() {
            const config = {
                widgets: this.widgets,
                gridColumns: this.gridColumns,
                timestamp: new Date().toISOString(),
            };
            localStorage.setItem('dashboard_config', JSON.stringify(config));
        },
        
        // Sync with server
        async syncWithServer() {
            try {
                const response = await fetch('/api/dashboard/config/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        layout: { columns: this.gridColumns },
                        widgets: this.widgets,
                        preferences: this.settings,
                    }),
                });
                
                if (!response.ok) {
                    console.error('Failed to sync with server');
                }
            } catch (error) {
                console.error('Sync error:', error);
            }
        },
        
        // Load available widgets
        loadAvailableWidgets() {
            this.availableWidgets = Object.entries(this.widgetTypes).map(([type, config]) => ({
                type,
                ...config,
            }));
        },
        
        // Load presets
        async loadPresets() {
            try {
                const response = await fetch('/api/dashboard/presets', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.presets = data.presets || [];
                }
            } catch (error) {
                console.error('Failed to load presets:', error);
            }
            
            // Add default presets if none from server
            if (this.presets.length === 0) {
                this.presets = [
                    { id: 1, name: 'Executive View', description: 'High-level KPIs and trends' },
                    { id: 2, name: 'Operations', description: 'Tickets and team performance' },
                    { id: 3, name: 'Financial', description: 'Revenue and billing focus' },
                ];
            }
        },
        
        // Initialize grid system
        initializeGrid() {
            this.$nextTick(() => {
                const gridElement = document.getElementById('widget-grid');
                if (gridElement && typeof Sortable !== 'undefined') {
                    this.sortableInstance = Sortable.create(gridElement, {
                        animation: 150,
                        handle: '.widget-container',
                        disabled: !this.editMode,
                        onEnd: (evt) => {
                            this.reorderWidgets(evt.oldIndex, evt.newIndex);
                        },
                    });
                }
            });
        },
        
        // Toggle edit mode
        toggleEditMode() {
            this.editMode = !this.editMode;
            
            if (this.sortableInstance) {
                this.sortableInstance.option('disabled', !this.editMode);
            }
            
            if (!this.editMode) {
                this.saveToLocalStorage();
                this.syncWithServer();
            }
        },
        
        // Add widget
        addWidget(widgetType) {
            const id = 'w' + Date.now();
            const widget = {
                id,
                type: widgetType.type,
                x: 0,
                y: 0,
                w: widgetType.defaultSize.w,
                h: widgetType.defaultSize.h,
                config: {},
                loading: true,
            };
            
            this.widgets.push(widget);
            this.showWidgetLibrary = false;
            
            // Fetch data for new widget
            this.fetchWidgetData(widget);
            
            // Save configuration
            this.saveToLocalStorage();
        },
        
        // Remove widget
        removeWidget(widget) {
            const index = this.widgets.findIndex(w => w.id === widget.id);
            if (index > -1) {
                this.widgets.splice(index, 1);
                this.saveToLocalStorage();
            }
        },
        
        // Configure widget
        configureWidget(widget) {
            // This would open a configuration modal
            console.log('Configure widget:', widget);
        },
        
        // Reorder widgets
        reorderWidgets(oldIndex, newIndex) {
            const widget = this.widgets.splice(oldIndex, 1)[0];
            this.widgets.splice(newIndex, 0, widget);
            this.saveToLocalStorage();
        },
        
        // Fetch widget data
        async fetchWidgetData(widget) {
            widget.loading = true;
            
            try {
                const response = await fetch('/api/dashboard/realtime?widget_type=' + widget.type, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });
                
                if (response.ok) {
                    const result = await response.json();
                    widget.data = result.data;
                    widget.lastUpdated = new Date().toISOString();
                } else {
                    widget.error = 'Failed to load data';
                }
            } catch (error) {
                console.error('Error fetching widget data:', error);
                widget.error = error.message;
            } finally {
                widget.loading = false;
            }
        },
        
        // Refresh all widgets
        async refreshAllWidgets() {
            console.log('Refreshing all widgets...');
            this.lastUpdated = new Date().toLocaleTimeString();
            
            const promises = this.widgets.map(widget => this.fetchWidgetData(widget));
            await Promise.all(promises);
        },
        
        // Start refresh cycle
        startRefreshCycle() {
            this.stopRefreshCycle();
            
            if (this.settings.refreshInterval > 0) {
                this.refreshInterval = setInterval(() => {
                    this.refreshAllWidgets();
                }, this.settings.refreshInterval * 1000);
            }
        },
        
        // Stop refresh cycle
        stopRefreshCycle() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },
        
        // Update refresh interval
        updateRefreshInterval() {
            this.saveSettings();
            this.startRefreshCycle();
        },
        
        // Setup auto-save
        setupAutoSave() {
            // Auto-save every 30 seconds if there are changes
            setInterval(() => {
                if (this.hasUnsavedChanges) {
                    this.syncWithServer();
                    this.hasUnsavedChanges = false;
                }
            }, 30000);
        },
        
        // Apply preset
        async applyPreset(preset) {
            if (!confirm('This will replace your current dashboard layout. Continue?')) {
                return;
            }
            
            try {
                const response = await fetch('/api/dashboard/preset/apply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ preset_id: preset.id }),
                });
                
                if (response.ok) {
                    // Reload configuration
                    await this.loadDashboardConfig();
                    alert('Preset applied successfully');
                }
            } catch (error) {
                console.error('Failed to apply preset:', error);
                alert('Failed to apply preset');
            }
        },
        
        // Export configuration
        exportConfiguration() {
            const config = {
                widgets: this.widgets,
                gridColumns: this.gridColumns,
                settings: this.settings,
                exportDate: new Date().toISOString(),
            };
            
            const blob = new Blob([JSON.stringify(config, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'dashboard-config-' + new Date().getTime() + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },
        
        // Import configuration
        importConfiguration() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            
            input.onchange = (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                const reader = new FileReader();
                reader.onload = (event) => {
                    try {
                        const config = JSON.parse(event.target.result);
                        this.widgets = config.widgets || [];
                        this.gridColumns = config.gridColumns || 12;
                        this.settings = { ...this.settings, ...config.settings };
                        
                        this.saveToLocalStorage();
                        this.saveSettings();
                        this.syncWithServer();
                        this.refreshAllWidgets();
                        
                        alert('Configuration imported successfully');
                    } catch (error) {
                        console.error('Failed to import configuration:', error);
                        alert('Failed to import configuration: Invalid file format');
                    }
                };
                reader.readAsText(file);
            };
            
            input.click();
        },
        
        // Reset to default
        resetToDefault() {
            if (!confirm('This will reset your dashboard to default settings. All customizations will be lost. Continue?')) {
                return;
            }
            
            localStorage.removeItem('dashboard_config');
            localStorage.removeItem('dashboard_settings');
            localStorage.removeItem('dashboard_grid_columns');
            
            this.loadDefaultConfig();
            this.settings = {
                theme: 'auto',
                refreshInterval: 30,
                animations: true,
                compactMode: false,
            };
            this.gridColumns = 12;
            
            this.saveSettings();
            this.applyTheme();
            this.refreshAllWidgets();
        },
        
        // Render widget content
        renderWidget(widget) {
            if (widget.loading) {
                return this.renderLoadingState();
            }
            
            if (widget.error) {
                return this.renderErrorState(widget.error);
            }
            
            if (!widget.data) {
                return this.renderEmptyState();
            }
            
            // Render based on widget type
            switch (widget.type) {
                case 'revenue_kpi':
                    return this.renderRevenueKPI(widget.data);
                case 'mrr_kpi':
                    return this.renderMRRKPI(widget.data);
                case 'ticket_status':
                    return this.renderTicketStatus(widget.data);
                case 'client_health':
                    return this.renderClientHealth(widget.data);
                case 'revenue_chart':
                    return this.renderRevenueChart(widget.data);
                case 'team_performance':
                    return this.renderTeamPerformance(widget.data);
                case 'activity_feed':
                    return this.renderActivityFeed(widget.data);
                case 'alerts':
                    return this.renderAlerts(widget.data);
                case 'forecast':
                    return this.renderForecast(widget.data);
                default:
                    return this.renderEmptyState();
            }
        },
        
        // Render loading state
        renderLoadingState() {
            return `
                <div class="flex items-center justify-center h-full">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                </div>
            `;
        },
        
        // Render error state
        renderErrorState(error) {
            return `
                <div class="flex items-center justify-center h-full p-4">
                    <div class="text-center">
                        <svg class="w-8 h-8 text-red-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-slate-500">Failed to load</p>
                    </div>
                </div>
            `;
        },
        
        // Render empty state
        renderEmptyState() {
            return `
                <div class="flex items-center justify-center h-full p-4">
                    <div class="text-center">
                        <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-sm text-slate-500">No data available</p>
                    </div>
                </div>
            `;
        },
        
        // Widget Renderers
        renderRevenueKPI(data) {
            const growthIcon = data.growth >= 0 ? '↗' : '↘';
            const growthColor = data.growth >= 0 ? 'text-green-600' : 'text-red-600';
            
            return `
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Revenue</h3>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Live</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">${data.formatted}</div>
                    <div class="flex items-center mt-2 ${growthColor}">
                        <span class="text-lg mr-1">${growthIcon}</span>
                        <span class="text-sm font-medium">${Math.abs(data.growth)}%</span>
                        <span class="text-xs text-slate-500 ml-2">vs last period</span>
                    </div>
                </div>
            `;
        },
        
        renderMRRKPI(data) {
            const growthIcon = data.growth >= 0 ? '↗' : '↘';
            const growthColor = data.growth >= 0 ? 'text-green-600' : 'text-red-600';
            
            return `
                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400">MRR</h3>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Monthly</span>
                    </div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">${data.formatted}</div>
                    <div class="flex items-center mt-2 ${growthColor}">
                        <span class="text-lg mr-1">${growthIcon}</span>
                        <span class="text-sm font-medium">${Math.abs(data.growth)}%</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <div>
                            <div class="text-xs text-slate-500">New MRR</div>
                            <div class="text-sm font-medium text-green-600">+$${data.new_mrr.toLocaleString()}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Churned</div>
                            <div class="text-sm font-medium text-red-600">-$${data.churned_mrr.toLocaleString()}</div>
                        </div>
                    </div>
                </div>
            `;
        },
        
        renderTicketStatus(data) {
            return `
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400">Ticket Status</h3>
                        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">${data.total} Total</span>
                    </div>
                    <div class="space-y-2">
                        ${Object.entries(data.by_status).map(([status, count]) => `
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-600 dark:text-slate-400">${status}</span>
                                <span class="text-sm font-medium text-slate-900 dark:text-white">${count}</span>
                            </div>
                        `).join('')}
                    </div>
                    ${data.sla_breached > 0 ? `
                        <div class="mt-3 p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <div class="flex items-center text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-xs font-medium">${data.sla_breached} SLA Breached</span>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        },
        
        renderClientHealth(data) {
            const getStatusColor = (status) => {
                switch(status) {
                    case 'healthy': return 'bg-green-500';
                    case 'warning': return 'bg-yellow-500';
                    case 'critical': return 'bg-red-500';
                    default: return 'bg-slate-400';
                }
            };
            
            return `
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400">Client Health</h3>
                        <div class="flex space-x-2">
                            <span class="flex items-center text-xs">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                ${data.summary.healthy}
                            </span>
                            <span class="flex items-center text-xs">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1"></span>
                                ${data.summary.warning}
                            </span>
                            <span class="flex items-center text-xs">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                                ${data.summary.critical}
                            </span>
                        </div>
                    </div>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        ${data.clients.map(client => `
                            <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 ${getStatusColor(client.status)} rounded-full"></span>
                                    <span class="text-sm text-slate-900 dark:text-white">${client.client_name}</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="text-xs text-slate-500">${client.score}%</span>
                                    ${client.open_tickets > 0 ? `<span class="text-xs bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded">${client.open_tickets} tickets</span>` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        },
        
        renderActivityFeed(data) {
            const getActivityIcon = (type) => {
                switch(type) {
                    case 'ticket': return '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.276A1 1 0 0018 15V3z" clip-rule="evenodd"/></svg>';
                    case 'payment': return '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>';
                    default: return '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/></svg>';
                }
            };
            
            return `
                <div class="p-4">
                    <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-3">Recent Activity</h3>
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        ${data.map(activity => `
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-slate-600 dark:text-slate-400">
                                    ${getActivityIcon(activity.type)}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-slate-900 dark:text-white">${activity.title}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">${activity.client || ''}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        },
        
        renderAlerts(data) {
            const getAlertColor = (type) => {
                switch(type) {
                    case 'error': return 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400';
                    case 'warning': return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400';
                    case 'info': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400';
                    default: return 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
                }
            };
            
            return `
                <div class="p-4">
                    <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-3">System Alerts</h3>
                    ${data.length === 0 ? `
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-green-500 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-slate-500">All systems operational</p>
                        </div>
                    ` : `
                        <div class="space-y-2">
                            ${data.map(alert => `
                                <div class="p-3 rounded-lg ${getAlertColor(alert.type)}">
                                    <div class="font-medium text-sm">${alert.title}</div>
                                    <div class="text-xs mt-1">${alert.message}</div>
                                    ${alert.action ? `
                                        <a href="${alert.action}" class="text-xs underline mt-2 inline-block">Take Action →</a>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    `}
                </div>
            `;
        },
        
        renderTeamPerformance(data) {
            return `
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400">Team Performance</h3>
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">Today</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">${data.total_resolved}</div>
                            <div class="text-xs text-slate-500">Resolved</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">${data.total_open}</div>
                            <div class="text-xs text-slate-500">Open</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        ${data.team.slice(0, 5).map(member => `
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 ${member.status === 'online' ? 'bg-green-500' : 'bg-slate-400'} rounded-full"></span>
                                    <span class="text-sm text-slate-700 dark:text-slate-300">${member.name}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-green-600">${member.tickets_resolved}</span>
                                    <span class="text-xs text-slate-400">/</span>
                                    <span class="text-xs text-orange-600">${member.tickets_open}</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        },
        
        renderRevenueChart(data) {
            // In a real implementation, this would use Chart.js or similar
            return `
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400">Revenue Trend</h3>
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">${data.period}</span>
                    </div>
                    <div class="h-48 flex items-end justify-between space-x-2">
                        ${data.values.map((value, index) => {
                            const height = (value / Math.max(...data.values)) * 100;
                            return `
                                <div class="flex-1 bg-gradient-to-t from-purple-500 to-purple-300 rounded-t" style="height: ${height}%"></div>
                            `;
                        }).join('')}
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <div>
                            <div class="text-xs text-slate-500">Total</div>
                            <div class="text-sm font-medium text-slate-900 dark:text-white">$${data.total.toLocaleString()}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Average</div>
                            <div class="text-sm font-medium text-slate-900 dark:text-white">$${Math.round(data.average).toLocaleString()}</div>
                        </div>
                    </div>
                </div>
            `;
        },
        
        renderForecast(data) {
            return `
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-sm font-medium text-slate-600 dark:text-slate-400">Revenue Forecast</h3>
                        <span class="text-xs bg-teal-100 text-teal-700 px-2 py-1 rounded-full">${data.growth_rate}% Growth</span>
                    </div>
                    <div class="space-y-3">
                        ${data.forecast.map(item => `
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-600 dark:text-slate-400">${item.month}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">$${item.value.toLocaleString()}</span>
                                    <span class="text-xs text-slate-500">${item.confidence}% conf</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    <div class="mt-4 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Based on ${data.based_on_months} months of data</p>
                    </div>
                </div>
            `;
        },
    };
}