/**
 * Search and Filters Component
 * Provides advanced search and filtering capabilities for products and services
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('searchAndFilters', (config = {}) => ({
        // Configuration
        enableFuzzySearch: config.enableFuzzySearch !== false,
        enableSearchHistory: config.enableSearchHistory !== false,
        maxSearchHistory: config.maxSearchHistory || 10,
        debounceDelay: config.debounceDelay || 300,
        enableAutoComplete: config.enableAutoComplete !== false,
        minSearchLength: config.minSearchLength || 2,
        
        // Search state
        query: '',
        searchHistory: [],
        suggestions: [],
        isSearching: false,
        searchResults: [],
        totalResults: 0,
        
        // Filter state
        activeFilters: new Map(),
        availableFilters: new Map(),
        filterCategories: [
            {
                id: 'category',
                name: 'Category',
                type: 'select',
                options: []
            },
            {
                id: 'price_range',
                name: 'Price Range',
                type: 'range',
                min: 0,
                max: 10000,
                step: 50
            },
            {
                id: 'availability',
                name: 'Availability',
                type: 'checkbox',
                options: [
                    { value: 'in_stock', label: 'In Stock' },
                    { value: 'out_of_stock', label: 'Out of Stock' },
                    { value: 'backordered', label: 'Backordered' }
                ]
            },
            {
                id: 'brand',
                name: 'Brand',
                type: 'multi_select',
                options: []
            },
            {
                id: 'tags',
                name: 'Tags',
                type: 'multi_select',
                options: []
            },
            {
                id: 'rating',
                name: 'Rating',
                type: 'range',
                min: 0,
                max: 5,
                step: 0.5
            },
            {
                id: 'date_added',
                name: 'Date Added',
                type: 'date_range',
                defaultRange: 'last_30_days'
            }
        ],
        
        // Advanced search options
        advancedSearch: {
            enabled: false,
            fields: ['name', 'description', 'sku', 'tags'],
            searchFields: new Map([
                ['name', true],
                ['description', false],
                ['sku', false],
                ['tags', false]
            ]),
            exactMatch: false,
            caseSensitive: false,
            includeVariants: true
        },
        
        // Sort options
        sortOptions: [
            { value: 'relevance', label: 'Relevance', default: true },
            { value: 'name_asc', label: 'Name (A-Z)' },
            { value: 'name_desc', label: 'Name (Z-A)' },
            { value: 'price_asc', label: 'Price (Low to High)' },
            { value: 'price_desc', label: 'Price (High to Low)' },
            { value: 'created_desc', label: 'Newest First' },
            { value: 'created_asc', label: 'Oldest First' },
            { value: 'popularity', label: 'Most Popular' },
            { value: 'rating', label: 'Highest Rated' }
        ],
        currentSort: 'relevance',
        
        // UI state
        showAdvancedSearch: false,
        showFilters: true,
        showSuggestions: false,
        filtersCollapsed: false,
        
        // Search performance
        searchDebouncer: null,
        searchStartTime: 0,
        searchTimes: [],
        
        // Saved searches
        savedSearches: [],
        
        // Filter presets
        filterPresets: [
            {
                id: 'popular_products',
                name: 'Popular Products',
                filters: { rating: [4, 5], availability: ['in_stock'] }
            },
            {
                id: 'new_arrivals',
                name: 'New Arrivals',
                filters: { date_added: 'last_7_days' }
            },
            {
                id: 'on_sale',
                name: 'On Sale',
                filters: { tags: ['sale', 'discount'] }
            }
        ],
        
        // Initialize search and filters
        init() {
            this.loadSearchHistory();
            this.loadSavedSearches();
            this.loadFilterOptions();
            this.setupEventListeners();
            this.initializeAutoComplete();
        },
        
        // Setup event listeners
        setupEventListeners() {
            // Search input with debouncing
            this.$watch('query', (newQuery) => {
                this.handleSearchInput(newQuery);
            });
            
            // Filter changes
            this.$watch('activeFilters', () => {
                this.performSearch();
            }, { deep: true });
            
            // Sort changes
            this.$watch('currentSort', () => {
                this.performSearch();
            });
            
            // Advanced search field changes
            this.$watch('advancedSearch.searchFields', () => {
                if (this.advancedSearch.enabled && this.query) {
                    this.performSearch();
                }
            }, { deep: true });
            
            // Click outside to hide suggestions
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.search-container')) {
                    this.showSuggestions = false;
                }
            });
        },
        
        // Handle search input
        handleSearchInput(query) {
            if (this.searchDebouncer) {
                clearTimeout(this.searchDebouncer);
            }
            
            if (query.length >= this.minSearchLength) {
                this.showSuggestions = true;
                
                if (this.enableAutoComplete) {
                    this.loadSuggestions(query);
                }
                
                this.searchDebouncer = setTimeout(() => {
                    this.performSearch();
                }, this.debounceDelay);
            } else {
                this.showSuggestions = false;
                this.suggestions = [];
                
                if (query.length === 0) {
                    this.clearSearchResults();
                }
            }
        },
        
        // Perform search
        async performSearch() {
            if (!this.query && this.activeFilters.size === 0) {
                this.clearSearchResults();
                return;
            }
            
            this.isSearching = true;
            this.searchStartTime = performance.now();
            
            try {
                const searchParams = this.buildSearchParams();
                const response = await fetch('/api/search/products', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(searchParams)
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.processSearchResults(data);
                    
                    // Track search performance
                    const searchTime = performance.now() - this.searchStartTime;
                    this.searchTimes.push(searchTime);
                    
                    // Add to search history
                    if (this.query && this.enableSearchHistory) {
                        this.addToSearchHistory(this.query);
                    }
                } else {
                    throw new Error('Search request failed');
                }
                
            } catch (error) {
                console.error('Search error:', error);
                this.searchResults = [];
                this.totalResults = 0;
            } finally {
                this.isSearching = false;
                this.showSuggestions = false;
            }
        },
        
        // Build search parameters
        buildSearchParams() {
            const params = {
                query: this.query,
                sort: this.currentSort,
                filters: {},
                options: {
                    fuzzy_search: this.enableFuzzySearch,
                    exact_match: this.advancedSearch.exactMatch,
                    case_sensitive: this.advancedSearch.caseSensitive,
                    include_variants: this.advancedSearch.includeVariants
                }
            };
            
            // Add active filters
            this.activeFilters.forEach((value, key) => {
                params.filters[key] = value;
            });
            
            // Add search fields for advanced search
            if (this.advancedSearch.enabled) {
                params.search_fields = Array.from(this.advancedSearch.searchFields.entries())
                    .filter(([field, enabled]) => enabled)
                    .map(([field]) => field);
            }
            
            return params;
        },
        
        // Process search results
        processSearchResults(data) {
            this.searchResults = data.results || [];
            this.totalResults = data.total || 0;
            
            // Update filter options based on results
            if (data.facets) {
                this.updateFilterOptions(data.facets);
            }
            
            // Dispatch search completed event
            this.$dispatch('search-completed', {
                query: this.query,
                results: this.searchResults,
                total: this.totalResults,
                filters: Object.fromEntries(this.activeFilters)
            });
        },
        
        // Load search suggestions
        async loadSuggestions(query) {
            try {
                const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(query)}&limit=8`);
                if (response.ok) {
                    const data = await response.json();
                    this.suggestions = data.suggestions || [];
                }
            } catch (error) {
                console.error('Failed to load suggestions:', error);
                this.suggestions = [];
            }
        },
        
        // Initialize autocomplete
        initializeAutoComplete() {
            if (!this.enableAutoComplete) return;
            
            // Setup keyboard navigation for suggestions
            document.addEventListener('keydown', (e) => {
                if (!this.showSuggestions || this.suggestions.length === 0) return;
                
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.navigateSuggestions(e.key === 'ArrowDown' ? 1 : -1);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    this.selectHighlightedSuggestion();
                } else if (e.key === 'Escape') {
                    this.showSuggestions = false;
                }
            });
        },
        
        // Navigate through suggestions
        navigateSuggestions(direction) {
            const currentIndex = this.suggestions.findIndex(s => s.highlighted);
            let newIndex = currentIndex + direction;
            
            // Wrap around
            if (newIndex < 0) newIndex = this.suggestions.length - 1;
            if (newIndex >= this.suggestions.length) newIndex = 0;
            
            // Update highlights
            this.suggestions.forEach((suggestion, index) => {
                suggestion.highlighted = index === newIndex;
            });
        },
        
        // Select highlighted suggestion
        selectHighlightedSuggestion() {
            const highlighted = this.suggestions.find(s => s.highlighted);
            if (highlighted) {
                this.selectSuggestion(highlighted);
            }
        },
        
        // Select suggestion
        selectSuggestion(suggestion) {
            this.query = suggestion.text;
            this.showSuggestions = false;
            this.performSearch();
        },
        
        // Add filter
        addFilter(filterId, value) {
            const filter = this.filterCategories.find(f => f.id === filterId);
            if (!filter) return;
            
            if (filter.type === 'multi_select') {
                const existing = this.activeFilters.get(filterId) || [];
                if (!existing.includes(value)) {
                    existing.push(value);
                    this.activeFilters.set(filterId, existing);
                }
            } else {
                this.activeFilters.set(filterId, value);
            }
            
            this.performSearch();
        },
        
        // Remove filter
        removeFilter(filterId, value = null) {
            if (value === null) {
                this.activeFilters.delete(filterId);
            } else {
                const existing = this.activeFilters.get(filterId) || [];
                const filtered = existing.filter(v => v !== value);
                
                if (filtered.length > 0) {
                    this.activeFilters.set(filterId, filtered);
                } else {
                    this.activeFilters.delete(filterId);
                }
            }
            
            this.performSearch();
        },
        
        // Clear all filters
        clearAllFilters() {
            this.activeFilters.clear();
            this.performSearch();
        },
        
        // Apply filter preset
        applyFilterPreset(presetId) {
            const preset = this.filterPresets.find(p => p.id === presetId);
            if (!preset) return;
            
            this.activeFilters.clear();
            
            Object.entries(preset.filters).forEach(([filterId, value]) => {
                this.activeFilters.set(filterId, value);
            });
            
            this.performSearch();
        },
        
        // Load filter options
        async loadFilterOptions() {
            try {
                const response = await fetch('/api/search/filter-options');
                if (response.ok) {
                    const data = await response.json();
                    this.updateFilterCategories(data);
                }
            } catch (error) {
                console.error('Failed to load filter options:', error);
            }
        },
        
        // Update filter categories with dynamic options
        updateFilterCategories(options) {
            this.filterCategories.forEach(category => {
                if (options[category.id]) {
                    category.options = options[category.id];
                }
            });
        },
        
        // Update filter options based on search results
        updateFilterOptions(facets) {
            Object.entries(facets).forEach(([filterId, options]) => {
                const category = this.filterCategories.find(c => c.id === filterId);
                if (category) {
                    category.options = options;
                }
            });
        },
        
        // Add to search history
        addToSearchHistory(query) {
            if (!this.searchHistory.includes(query)) {
                this.searchHistory.unshift(query);
                
                // Limit history size
                if (this.searchHistory.length > this.maxSearchHistory) {
                    this.searchHistory = this.searchHistory.slice(0, this.maxSearchHistory);
                }
                
                this.saveSearchHistory();
            }
        },
        
        // Load search history
        loadSearchHistory() {
            try {
                const history = localStorage.getItem('search_history');
                if (history) {
                    this.searchHistory = JSON.parse(history);
                }
            } catch (error) {
                console.warn('Failed to load search history:', error);
            }
        },
        
        // Save search history
        saveSearchHistory() {
            try {
                localStorage.setItem('search_history', JSON.stringify(this.searchHistory));
            } catch (error) {
                console.warn('Failed to save search history:', error);
            }
        },
        
        // Clear search history
        clearSearchHistory() {
            this.searchHistory = [];
            localStorage.removeItem('search_history');
        },
        
        // Save current search
        saveCurrentSearch() {
            if (!this.query && this.activeFilters.size === 0) return;
            
            const searchName = prompt('Enter a name for this search:');
            if (!searchName) return;
            
            const savedSearch = {
                id: Date.now(),
                name: searchName,
                query: this.query,
                filters: Object.fromEntries(this.activeFilters),
                sort: this.currentSort,
                created_at: new Date().toISOString()
            };
            
            this.savedSearches.push(savedSearch);
            this.saveSavedSearches();
        },
        
        // Load saved search
        loadSavedSearch(searchId) {
            const savedSearch = this.savedSearches.find(s => s.id === searchId);
            if (!savedSearch) return;
            
            this.query = savedSearch.query;
            this.currentSort = savedSearch.sort;
            
            this.activeFilters.clear();
            Object.entries(savedSearch.filters).forEach(([key, value]) => {
                this.activeFilters.set(key, value);
            });
            
            this.performSearch();
        },
        
        // Delete saved search
        deleteSavedSearch(searchId) {
            this.savedSearches = this.savedSearches.filter(s => s.id !== searchId);
            this.saveSavedSearches();
        },
        
        // Load saved searches
        loadSavedSearches() {
            try {
                const saved = localStorage.getItem('saved_searches');
                if (saved) {
                    this.savedSearches = JSON.parse(saved);
                }
            } catch (error) {
                console.warn('Failed to load saved searches:', error);
            }
        },
        
        // Save saved searches
        saveSavedSearches() {
            try {
                localStorage.setItem('saved_searches', JSON.stringify(this.savedSearches));
            } catch (error) {
                console.warn('Failed to save searches:', error);
            }
        },
        
        // Clear search results
        clearSearchResults() {
            this.searchResults = [];
            this.totalResults = 0;
            this.query = '';
        },
        
        // Toggle advanced search
        toggleAdvancedSearch() {
            this.showAdvancedSearch = !this.showAdvancedSearch;
            this.advancedSearch.enabled = this.showAdvancedSearch;
            
            if (this.query) {
                this.performSearch();
            }
        },
        
        // Toggle search field for advanced search
        toggleSearchField(field) {
            this.advancedSearch.searchFields.set(
                field,
                !this.advancedSearch.searchFields.get(field)
            );
        },
        
        // Get filter display value
        getFilterDisplayValue(filterId, value) {
            const filter = this.filterCategories.find(f => f.id === filterId);
            if (!filter) return value;
            
            if (filter.type === 'select' || filter.type === 'multi_select') {
                const option = filter.options.find(o => o.value === value);
                return option ? option.label : value;
            }
            
            return value;
        },
        
        // Export search results
        async exportSearchResults(format = 'csv') {
            try {
                const params = new URLSearchParams({
                    format,
                    query: this.query,
                    sort: this.currentSort
                });
                
                // Add filters
                this.activeFilters.forEach((value, key) => {
                    params.append(`filters[${key}]`, JSON.stringify(value));
                });
                
                const response = await fetch(`/api/search/export?${params.toString()}`);
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `search-results.${format}`;
                    a.click();
                    window.URL.revokeObjectURL(url);
                }
            } catch (error) {
                console.error('Failed to export search results:', error);
            }
        },
        
        // Computed properties
        get hasResults() {
            return this.searchResults.length > 0;
        },
        
        get hasQuery() {
            return this.query.length > 0;
        },
        
        get hasFilters() {
            return this.activeFilters.size > 0;
        },
        
        get activeFilterCount() {
            return this.activeFilters.size;
        },
        
        get isEmptySearch() {
            return !this.hasQuery && !this.hasFilters;
        },
        
        get averageSearchTime() {
            return this.searchTimes.length > 0
                ? this.searchTimes.reduce((a, b) => a + b, 0) / this.searchTimes.length
                : 0;
        },
        
        get searchResultsText() {
            if (this.totalResults === 0) return 'No results found';
            if (this.totalResults === 1) return '1 result found';
            return `${this.totalResults.toLocaleString()} results found`;
        },
        
        get canSaveSearch() {
            return this.hasQuery || this.hasFilters;
        }
    }));
});