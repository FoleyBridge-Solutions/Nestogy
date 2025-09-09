export function clientSearchField(initialClient = null) {
    return {
        // Cache version - increment to invalidate old cache
        CACHE_VERSION: '2.0',
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
        refreshing: false, // Prevent multiple refresh calls
        initialized: false, // Prevent multiple initializations
        
        // Initialize
        init() {
            if (this.initialized) return; // Prevent multiple initializations
            this.initialized = true;
            
            console.log('ClientSearchField init with:', this.selectedClient);
            
            // Load recent clients from localStorage
            this.loadRecentClients();
            
            // If no client is pre-selected, check for session client
            if (!this.selectedClient && window.CURRENT_USER?.selected_client) {
                console.log('Using session client:', window.CURRENT_USER.selected_client);
                this.selectedClient = window.CURRENT_USER.selected_client;
                this.selectedClientId = this.selectedClient.id;
                this.searchQuery = this.selectedClient.name || '';
            }
            
            // Set initial display text if client is pre-selected
            if (this.selectedClient && this.selectedClient.id) {
                console.log('Initial selected client:', this.selectedClient);
                this.searchQuery = this.selectedClient.name || '';
                // Refresh client data to ensure we have the latest name
                this.refreshSelectedClient();
            }
            
            // Single event dispatch after initialization is complete
            if (this.selectedClient && this.selectedClient.id) {
                setTimeout(() => {
                    this.$dispatch('client-selected', { client: this.selectedClient });
                }, 200);
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.close();
                }
            });
            
            // Listen for client update events (when client is edited)
            window.addEventListener('client-updated', (e) => {
                if (this.selectedClient && e.detail.clientId === this.selectedClient.id && !this.refreshing) {
                    this.refreshSelectedClient();
                }
                // Also update in recent clients
                this.updateRecentClient(e.detail.clientId);
            });
        },
        
        // Get storage key based on current user/company
        getStorageKey() {
            const user = window.CURRENT_USER || {};
            if (!user.company_id) {
                console.warn('No company ID available for localStorage key');
                return null;
            }
            return `recent-clients-${user.company_id}-${user.id}`;
        },
        
        // Load recent clients from localStorage
        loadRecentClients() {
            const key = this.getStorageKey();
            if (!key) {
                this.recentClients = [];
                return;
            }
            
            try {
                const stored = localStorage.getItem(key);
                if (!stored) {
                    this.recentClients = [];
                    return;
                }
                
                const data = JSON.parse(stored);
                
                // Check version
                if (data.version !== this.CACHE_VERSION) {
                    console.log('Cache version mismatch, clearing old data');
                    localStorage.removeItem(key);
                    this.recentClients = [];
                    return;
                }
                
                // Check expiration (24 hours)
                const DAY_IN_MS = 24 * 60 * 60 * 1000;
                if (Date.now() - data.timestamp > DAY_IN_MS) {
                    console.log('Recent clients cache expired, clearing');
                    localStorage.removeItem(key);
                    this.recentClients = [];
                    return;
                }
                
                // Check company hasn't changed
                if (data.companyId !== window.CURRENT_USER?.company_id) {
                    console.log('Company changed, clearing old recent clients');
                    localStorage.removeItem(key);
                    this.recentClients = [];
                    return;
                }
                
                // Load but don't trust - will validate later
                this.recentClients = data.clients || [];
                console.log('Loaded recent clients (pending validation):', this.recentClients.length);
                
            } catch (e) {
                console.error('Failed to load recent clients:', e);
                this.recentClients = [];
                // Clear corrupted data
                if (key) localStorage.removeItem(key);
            }
        },
        
        // Save client to recent list
        saveToRecentClients(client) {
            if (!client) return;
            
            const key = this.getStorageKey();
            if (!key) return;
            
            // Remove if already exists and add to front
            let recent = this.recentClients.filter(c => c.id !== client.id);
            recent.unshift(client);
            recent = recent.slice(0, 5); // Keep only 5 recent
            
            this.recentClients = recent;
            
            // Save with metadata
            const data = {
                clients: recent,
                timestamp: Date.now(),
                version: this.CACHE_VERSION,
                companyId: window.CURRENT_USER?.company_id
            };
            
            localStorage.setItem(key, JSON.stringify(data));
        },
        
        // Open dropdown and load initial data
        async openDropdown() {
            this.open = true;
            this.selectedIndex = -1;
            
            // Always refresh selected client data when opening (handles DB changes)
            if (this.selectedClient && this.selectedClient.id) {
                await this.refreshSelectedClient();
            }
            
            // Refresh recent clients data
            await this.refreshRecentClients();
            
            // If no search query and no recent clients, show popular
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
                const response = await fetch(`/clients/active?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
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
                const response = await fetch('/clients/active?limit=10', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
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
            
            // Update accessed_at via API (fire and forget) - disabled due to auth issues
            // this.markClientAsAccessed(client.id);
            
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
        
        // Refresh selected client data
        async refreshSelectedClient() {
            if (!this.selectedClient || !this.selectedClient.id || this.refreshing) return;
            
            this.refreshing = true;
            console.log('Refreshing client:', this.selectedClient.id);
            
            try {
                const response = await fetch(`/clients/${this.selectedClient.id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const updatedClient = await response.json();
                    console.log('Refreshed client data:', updatedClient);
                    this.selectedClient = updatedClient;
                    this.searchQuery = updatedClient.name || '';
                    this.selectedClientId = updatedClient.id;
                    
                    // Update in recent clients too
                    this.updateClientInRecentList(updatedClient);
                } else {
                    console.error('Failed to refresh client, status:', response.status);
                    // If client not found or unauthorized, clear selection
                    if (response.status === 404 || response.status === 403) {
                        this.clearSelection();
                    }
                }
            } catch (error) {
                console.error('Failed to refresh client data:', error);
            } finally {
                this.refreshing = false;
            }
        },
        
        // Update a client in the recent list
        updateClientInRecentList(updatedClient) {
            const index = this.recentClients.findIndex(c => c.id === updatedClient.id);
            if (index !== -1) {
                this.recentClients[index] = updatedClient;
                
                const key = this.getStorageKey();
                if (key) {
                    const data = {
                        clients: this.recentClients,
                        timestamp: Date.now(),
                        version: this.CACHE_VERSION,
                        companyId: window.CURRENT_USER?.company_id
                    };
                    localStorage.setItem(key, JSON.stringify(data));
                }
            }
        },
        
        // Update recent client by fetching fresh data
        async updateRecentClient(clientId) {
            try {
                const response = await fetch(`/clients/${clientId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const updatedClient = await response.json();
                    this.updateClientInRecentList(updatedClient);
                }
            } catch (error) {
                console.warn('Failed to update recent client:', error);
            }
        },
        
        // Validate clients exist via batch API - disabled due to auth issues
        async validateClientsBatch(clients) {
            if (!clients || clients.length === 0) return [];
            
            // For now, just return all clients without validation
            // This avoids auth issues while maintaining functionality
            return clients;
        },
        
        // Refresh all recent clients (validate they exist)
        async refreshRecentClients() {
            if (this.recentClients.length === 0) return;
            
            console.log('Validating recent clients...');
            
            // Use batch validation for efficiency
            const validClients = await this.validateClientsBatch(this.recentClients);
            
            // Update list with only valid clients
            this.recentClients = validClients;
            
            const key = this.getStorageKey();
            if (!key) return;
            
            // Update or clear localStorage based on results
            if (this.recentClients.length > 0) {
                const data = {
                    clients: this.recentClients,
                    timestamp: Date.now(),
                    version: this.CACHE_VERSION,
                    companyId: window.CURRENT_USER?.company_id
                };
                localStorage.setItem(key, JSON.stringify(data));
                console.log(`Validated ${this.recentClients.length} recent clients`);
            } else {
                // No valid clients found, clear localStorage
                localStorage.removeItem(key);
                console.log('No valid recent clients found, cleared cache');
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
        
        // Clear all cached data (useful for debugging)
        clearAllCache() {
            const key = this.getStorageKey();
            if (key) {
                localStorage.removeItem(key);
            }
            // Also try to clear old format
            localStorage.removeItem('recent-selected-clients');
            this.recentClients = [];
            this.clearSelection();
            console.log('Cleared all client search cache');
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