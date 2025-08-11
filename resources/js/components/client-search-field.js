export function clientSearchField(initialClient = null) {
    return {
        // State
        open: false,
        loading: false,
        searchQuery: '',
        selectedClient: initialClient,
        selectedClientId: initialClient?.id || '',
        clients: [],
        recentClients: [],
        selectedIndex: -1,
        searchTimeout: null,
        
        // Initialize
        init() {
            // Load recent clients from localStorage
            this.loadRecentClients();
            
            // Set initial display text if client is pre-selected
            if (this.selectedClient) {
                this.searchQuery = this.selectedClient.name;
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.close();
                }
            });
        },
        
        // Load recent clients from localStorage
        loadRecentClients() {
            try {
                const recent = localStorage.getItem('recent-selected-clients');
                this.recentClients = recent ? JSON.parse(recent) : [];
            } catch (e) {
                this.recentClients = [];
            }
        },
        
        // Save client to recent list
        saveToRecentClients(client) {
            if (!client) return;
            
            // Remove if already exists and add to front
            let recent = this.recentClients.filter(c => c.id !== client.id);
            recent.unshift(client);
            recent = recent.slice(0, 5); // Keep only 5 recent
            
            this.recentClients = recent;
            localStorage.setItem('recent-selected-clients', JSON.stringify(recent));
        },
        
        // Open dropdown and load initial data
        async openDropdown() {
            this.open = true;
            this.selectedIndex = -1;
            
            // If no search query, show recent clients
            if (!this.searchQuery && this.recentClients.length === 0) {
                await this.loadPopularClients();
            }
        },
        
        // Close dropdown
        close() {
            this.open = false;
            this.selectedIndex = -1;
            
            // If no client selected, clear the search
            if (!this.selectedClient) {
                this.searchQuery = '';
            }
        },
        
        // Handle search input
        async search() {
            // Clear previous timeout
            clearTimeout(this.searchTimeout);
            
            // Reset selection
            this.selectedIndex = -1;
            
            // If search is cleared and no client selected
            if (!this.searchQuery) {
                this.clients = [];
                if (!this.selectedClient) {
                    this.selectedClientId = '';
                }
                return;
            }
            
            // If search matches selected client, don't search
            if (this.selectedClient && this.searchQuery === this.selectedClient.name) {
                return;
            }
            
            // Clear selection if user is typing something different
            if (this.selectedClient) {
                this.selectedClient = null;
                this.selectedClientId = '';
            }
            
            // Debounce search
            this.searchTimeout = setTimeout(async () => {
                await this.performSearch(this.searchQuery);
            }, 300);
        },
        
        // Perform the actual search
        async performSearch(query) {
            if (!query || query.length < 2) {
                this.clients = [];
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await fetch(`/api/search/clients?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                this.clients = Array.isArray(data) ? data : [];
                
            } catch (error) {
                console.error('Search failed:', error);
                this.clients = [];
                this.showError('Failed to search clients');
            } finally {
                this.loading = false;
            }
        },
        
        // Load popular/active clients when no search
        async loadPopularClients() {
            this.loading = true;
            
            try {
                const response = await fetch('/api/clients/active?limit=10', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                this.clients = Array.isArray(data) ? data : [];
                
            } catch (error) {
                console.error('Failed to load popular clients:', error);
                this.clients = [];
            } finally {
                this.loading = false;
            }
        },
        
        // Select a client
        selectClient(client) {
            if (!client) return;
            
            this.selectedClient = client;
            this.selectedClientId = client.id;
            this.searchQuery = client.name;
            this.saveToRecentClients(client);
            this.close();
            
            // Update accessed_at via API (fire and forget)
            this.markClientAsAccessed(client.id);
            
            // Dispatch change event for dependent fields
            this.$dispatch('client-selected', { client });
        },
        
        // Mark client as accessed
        async markClientAsAccessed(clientId) {
            try {
                await fetch(`/api/clients/${clientId}/mark-accessed`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            } catch (error) {
                // Silently fail - not critical
                console.warn('Failed to mark client as accessed:', error);
            }
        },
        
        // Clear selection
        clearSelection() {
            this.selectedClient = null;
            this.selectedClientId = '';
            this.searchQuery = '';
            this.clients = [];
            this.$dispatch('client-cleared');
        },
        
        // Keyboard navigation
        onKeyDown(event) {
            if (!this.open) {
                if (event.key === 'ArrowDown' || event.key === 'Enter') {
                    event.preventDefault();
                    this.openDropdown();
                }
                return;
            }
            
            const items = this.getSelectableItems();
            
            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                    this.scrollToSelected();
                    break;
                    
                case 'ArrowUp':
                    event.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                    this.scrollToSelected();
                    break;
                    
                case 'Enter':
                    event.preventDefault();
                    if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                        this.selectClient(items[this.selectedIndex]);
                    }
                    break;
                    
                case 'Escape':
                    event.preventDefault();
                    this.close();
                    break;
                    
                case 'Tab':
                    // Let tab close the dropdown but continue to next field
                    this.close();
                    break;
            }
        },
        
        // Get all selectable items
        getSelectableItems() {
            const items = [];
            
            // Add recent clients if no search
            if (!this.searchQuery || this.searchQuery.length < 2) {
                items.push(...this.recentClients);
            }
            
            // Add search results (exclude duplicates from recent)
            const recentIds = this.recentClients.map(c => c.id);
            const uniqueClients = this.clients.filter(c => !recentIds.includes(c.id));
            items.push(...uniqueClients);
            
            return items;
        },
        
        // Scroll to selected item
        scrollToSelected() {
            this.$nextTick(() => {
                const dropdown = this.$refs.dropdown;
                const items = dropdown?.querySelectorAll('[data-client-item]');
                
                if (dropdown && items && items[this.selectedIndex]) {
                    const selectedItem = items[this.selectedIndex];
                    const dropdownRect = dropdown.getBoundingClientRect();
                    const itemRect = selectedItem.getBoundingClientRect();
                    
                    if (itemRect.bottom > dropdownRect.bottom) {
                        dropdown.scrollTop += itemRect.bottom - dropdownRect.bottom + 8;
                    } else if (itemRect.top < dropdownRect.top) {
                        dropdown.scrollTop -= dropdownRect.top - itemRect.top + 8;
                    }
                }
            });
        },
        
        // Check if item is selected
        isItemSelected(index) {
            return this.selectedIndex === index;
        },
        
        // Get client initials for avatar
        getClientInitials(client) {
            if (!client || !client.name) return '?';
            return client.name
                .split(' ')
                .map(word => word.charAt(0))
                .slice(0, 2)
                .join('')
                .toUpperCase();
        },
        
        // Show error message
        showError(message) {
            // You can implement a toast notification here
            console.error(message);
        },
        
        // Computed properties
        get showRecentClients() {
            return !this.searchQuery && this.recentClients.length > 0;
        },
        
        get showSearchResults() {
            return this.searchQuery && this.searchQuery.length >= 2;
        },
        
        get showNoResults() {
            return !this.loading && this.showSearchResults && this.clients.length === 0;
        },
        
        get showEmpty() {
            return !this.loading && !this.searchQuery && this.recentClients.length === 0 && this.clients.length === 0;
        },
        
        get displayItems() {
            return this.getSelectableItems();
        }
    };
}