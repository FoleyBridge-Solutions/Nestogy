/**
 * Quote Analytics and Tracking Component
 * Provides comprehensive analytics and performance tracking for quotes
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('quoteAnalytics', (config = {}) => ({
        // Configuration
        trackingEnabled: config.trackingEnabled !== false,
        realTimeUpdates: config.realTimeUpdates !== false,
        dataRetentionDays: config.dataRetentionDays || 365,
        
        // Analytics data
        dashboardData: {
            overview: {
                totalQuotes: 0,
                totalValue: 0,
                conversionRate: 0,
                avgQuoteValue: 0,
                activeQuotes: 0
            },
            trends: {
                daily: [],
                weekly: [],
                monthly: []
            },
            performance: {
                byUser: [],
                byClient: [],
                byCategory: [],
                byTemplate: []
            }
        },
        
        // Filters
        filters: {
            dateRange: 'last_30_days',
            startDate: null,
            endDate: null,
            userId: null,
            clientId: null,
            status: null,
            category: null
        },
        
        // Chart configurations
        chartConfigs: {
            conversionFunnel: {
                type: 'funnel',
                stages: ['Draft', 'Sent', 'Viewed', 'Negotiating', 'Accepted', 'Rejected']
            },
            valueOverTime: {
                type: 'line',
                metrics: ['total_value', 'average_value', 'count']
            },
            performanceMetrics: {
                type: 'bar',
                metrics: ['conversion_rate', 'avg_response_time', 'avg_quote_value']
            }
        },
        
        // Real-time tracking
        liveMetrics: {
            activeQuotes: 0,
            todayQuotes: 0,
            todayValue: 0,
            recentActivity: []
        },
        
        // Export options
        exportFormats: ['csv', 'excel', 'pdf', 'json'],
        
        // UI state
        loading: false,
        selectedMetric: 'overview',
        showExportModal: false,
        
        init() {
            this.loadAnalytics();
            this.setupEventListeners();
            if (this.realTimeUpdates) {
                this.startRealTimeUpdates();
            }
        },
        
        setupEventListeners() {
            // Listen for filter changes
            this.$watch('filters', () => {
                this.loadAnalytics();
            }, { deep: true });
            
            // Listen for quote events
            document.addEventListener('quote-created', () => {
                this.updateLiveMetrics();
            });
            
            document.addEventListener('quote-sent', () => {
                this.updateLiveMetrics();
            });
            
            document.addEventListener('quote-accepted', () => {
                this.updateLiveMetrics();
            });
        },
        
        async loadAnalytics() {
            this.loading = true;
            
            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    include: 'overview,trends,performance,funnel'
                });
                
                const response = await fetch(`/api/analytics/quotes?${params}`);
                if (response.ok) {
                    this.dashboardData = await response.json();
                }
            } catch (error) {
                console.error('Failed to load analytics:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async updateLiveMetrics() {
            if (!this.realTimeUpdates) return;
            
            try {
                const response = await fetch('/api/analytics/quotes/live');
                if (response.ok) {
                    this.liveMetrics = await response.json();
                }
            } catch (error) {
                console.error('Failed to update live metrics:', error);
            }
        },
        
        startRealTimeUpdates() {
            setInterval(() => {
                this.updateLiveMetrics();
            }, 30000); // Update every 30 seconds
        },
        
        // Filter methods
        setDateRange(range) {
            this.filters.dateRange = range;
            
            const now = new Date();
            switch (range) {
                case 'today':
                    this.filters.startDate = now.toISOString().split('T')[0];
                    this.filters.endDate = now.toISOString().split('T')[0];
                    break;
                case 'last_7_days':
                    this.filters.startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    this.filters.endDate = now.toISOString().split('T')[0];
                    break;
                case 'last_30_days':
                    this.filters.startDate = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    this.filters.endDate = now.toISOString().split('T')[0];
                    break;
                case 'last_90_days':
                    this.filters.startDate = new Date(now.getTime() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                    this.filters.endDate = now.toISOString().split('T')[0];
                    break;
                case 'custom':
                    // Let user set custom dates
                    break;
            }
        },
        
        clearFilters() {
            this.filters = {
                dateRange: 'last_30_days',
                startDate: null,
                endDate: null,
                userId: null,
                clientId: null,
                status: null,
                category: null
            };
        },
        
        // Export methods
        async exportData(format) {
            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    format: format
                });
                
                const response = await fetch(`/api/analytics/quotes/export?${params}`);
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `quote-analytics.${format}`;
                    a.click();
                    window.URL.revokeObjectURL(url);
                }
            } catch (error) {
                console.error('Export failed:', error);
            }
        },
        
        // Chart data preparation
        getConversionFunnelData() {
            const stages = this.chartConfigs.conversionFunnel.stages;
            return stages.map(stage => ({
                stage,
                count: this.dashboardData.funnel?.[stage.toLowerCase()] || 0,
                percentage: this.calculateStagePercentage(stage)
            }));
        },
        
        calculateStagePercentage(stage) {
            const totalQuotes = this.dashboardData.overview.totalQuotes;
            const stageCount = this.dashboardData.funnel?.[stage.toLowerCase()] || 0;
            return totalQuotes > 0 ? (stageCount / totalQuotes) * 100 : 0;
        },
        
        getTrendData(period = 'daily') {
            return this.dashboardData.trends[period] || [];
        },
        
        getPerformanceData(category) {
            return this.dashboardData.performance[category] || [];
        },
        
        // Metric calculations
        calculateGrowthRate(current, previous) {
            if (previous === 0) return current > 0 ? 100 : 0;
            return ((current - previous) / previous) * 100;
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },
        
        formatPercentage(value) {
            return `${value.toFixed(1)}%`;
        },
        
        formatNumber(value) {
            return new Intl.NumberFormat('en-US').format(value);
        },
        
        // Insights generation
        generateInsights() {
            const insights = [];
            const overview = this.dashboardData.overview;
            
            // Conversion rate insights
            if (overview.conversionRate < 20) {
                insights.push({
                    type: 'warning',
                    title: 'Low Conversion Rate',
                    message: 'Your quote conversion rate is below average. Consider reviewing your pricing strategy.',
                    action: 'View conversion analysis'
                });
            }
            
            // High value quotes
            if (overview.avgQuoteValue > 10000) {
                insights.push({
                    type: 'success',
                    title: 'High Value Quotes',
                    message: 'Your average quote value is performing well.',
                    action: 'Analyze high-value patterns'
                });
            }
            
            // Active quotes trend
            if (overview.activeQuotes > overview.totalQuotes * 0.3) {
                insights.push({
                    type: 'info',
                    title: 'High Active Quote Volume',
                    message: 'You have a high number of active quotes. Consider follow-up strategies.',
                    action: 'View active quotes'
                });
            }
            
            return insights;
        },
        
        // Performance benchmarks
        getBenchmarkData() {
            return {
                conversionRate: {
                    current: this.dashboardData.overview.conversionRate,
                    benchmark: 25,
                    industry: 30
                },
                avgQuoteValue: {
                    current: this.dashboardData.overview.avgQuoteValue,
                    benchmark: 5000,
                    industry: 7500
                },
                responseTime: {
                    current: this.getAverageResponseTime(),
                    benchmark: 24, // hours
                    industry: 48
                }
            };
        },
        
        getAverageResponseTime() {
            // Calculate from performance data
            const userData = this.dashboardData.performance.byUser || [];
            if (userData.length === 0) return 0;
            
            const totalTime = userData.reduce((sum, user) => sum + (user.avg_response_time || 0), 0);
            return totalTime / userData.length;
        },
        
        // Computed properties
        get totalQuotesFormatted() {
            return this.formatNumber(this.dashboardData.overview.totalQuotes);
        },
        
        get totalValueFormatted() {
            return this.formatCurrency(this.dashboardData.overview.totalValue);
        },
        
        get conversionRateFormatted() {
            return this.formatPercentage(this.dashboardData.overview.conversionRate);
        },
        
        get avgQuoteValueFormatted() {
            return this.formatCurrency(this.dashboardData.overview.avgQuoteValue);
        },
        
        get hasData() {
            return this.dashboardData.overview.totalQuotes > 0;
        },
        
        get insights() {
            return this.generateInsights();
        },
        
        get benchmarks() {
            return this.getBenchmarkData();
        }
    }));
});