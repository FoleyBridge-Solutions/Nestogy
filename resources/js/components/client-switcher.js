export function clientSwitcher() {
    return {
        // State
        open: false,
        loading: false,
        searchQuery: '',
        selectedIndex: -1,
        clients: [],
        recentClients: [],
        currentClient: null,
        
        // Initialize component
        async init() {
            this.currentClient = this.getCurrentClientFromPage();
            this.loadRecentClients();
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.open = false;
                }
            });
            
            // Handle escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.open) {
                    this.close();
                }
            });
        },
        
        // Get current client from page data
        getCurrentClientFromPage() {
            const element = document.querySelector('[data-current-client]');
            if (element) {
                try {
                    return JSON.parse(element.dataset.currentClient);
                } catch (e) {
                    console.warn('Failed to parse current client data:', e);
                }
            }
            return null;
        },
        
        // Load recent clients from localStorage
        loadRecentClients() {
            try {
                const recent = localStorage.getItem('recent-clients');
                this.recentClients = recent ? JSON.parse(recent) : [];
            } catch (e) {
                this.recentClients = [];
            }
        },
        
        // Save recent client to localStorage
        saveRecentClient(client) {
            let recent = this.recentClients.filter(c => c.id !== client.id);
            recent.unshift(client);
            recent = recent.slice(0, 5); // Keep only 5 recent clients
            
            this.recentClients = recent;
            localStorage.setItem('recent-clients', JSON.stringify(recent));
        },
        
        // Toggle dropdown
        toggle() {
            if (this.open) {
                this.close();
            } else {
                this.openDropdown();
            }
        },
        
        // Open dropdown
        async openDropdown() {
            this.open = true;
            this.searchQuery = '';
            this.selectedIndex = -1;
            
            // Focus search input after transition
            await this.$nextTick();
            const searchInput = this.$el.querySelector('input[type="search"]');
            if (searchInput) {
                searchInput.focus();
            }
            
            // Load clients if not already loaded
            if (this.clients.length === 0 && !this.loading) {
                await this.loadClients();
            }
        },
        
        // Close dropdown
        close() {
            this.open = false;
            this.searchQuery = '';
            this.selectedIndex = -1;
        },
        
        // Load clients from API
        async loadClients(search = '') {
            this.loading = true;
            
            try {
                const url = new URL('/clients/active', window.location.origin);
                if (search) {
                    url.searchParams.set('q', search);
                }
                
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                this.clients = Array.isArray(data) ? data : [];
                
            } catch (error) {
                console.error('Failed to load clients:', error);
                this.clients = [];
                this.showError('Failed to load clients. Please try again.');
            } finally {
                this.loading = false;
            }
        },
        
        // Handle search input
        async onSearch() {
            this.selectedIndex = -1;
            
            // Debounce search
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(async () => {
                await this.loadClients(this.searchQuery);
            }, 300);
        },
        
        // Handle keyboard navigation
        async onKeyDown(event) {
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
                        await this.selectClient(items[this.selectedIndex]);
                    }
                    break;
                    
                case 'Escape':
                    this.close();
                    break;
            }
        },
        
        // Get selectable items (recent + filtered clients)
        getSelectableItems() {
            const items = [];
            
            // Add recent clients (excluding current client)
            if (this.searchQuery === '') {
                const recentFiltered = this.recentClients.filter(client => 
                    !this.currentClient || client.id !== this.currentClient.id
                );
                items.push(...recentFiltered);
            }
            
            // Add search results (excluding current client and recent clients)
            const clientsFiltered = this.clients.filter(client => {
                if (this.currentClient && client.id === this.currentClient.id) return false;
                if (this.searchQuery === '' && this.recentClients.some(recent => recent.id === client.id)) return false;
                return true;
            });
            
            items.push(...clientsFiltered);
            return items;
        },
        
        // Scroll to selected item
        scrollToSelected() {
            const dropdown = this.$el.querySelector('[data-dropdown-content]');
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
        },
        
        // Select client
        async selectClient(client) {
            if (!client || !client.id) return;
            
            // Show loading state
            this.loading = true;
            this.close();
            
            try {
                // Switch client via form submission
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/clients/select/${client.id}`;
                form.style.display = 'none';
                
                const tokenField = document.createElement('input');
                tokenField.type = 'hidden';
                tokenField.name = '_token';
                tokenField.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                
                form.appendChild(tokenField);
                document.body.appendChild(form);
                
                // Save to recent clients before submitting
                this.saveRecentClient(client);
                
                // Submit form
                form.submit();
                
            } catch (error) {
                console.error('Failed to select client:', error);
                this.loading = false;
                this.showError('Failed to switch client. Please try again.');
            }
        },
        
        // Clear client selection
        async clearSelection() {
            if (!this.currentClient) return;
            
            this.loading = true;
            this.close();
            
            try {
                window.location.href = '/clients/clear-selection';
            } catch (error) {
                console.error('Failed to clear client selection:', error);
                this.loading = false;
                this.showError('Failed to clear client selection. Please try again.');
            }
        },
        
        // Show error message
        showError(message) {
            // You can implement a toast notification system here
            console.error(message);
            // For now, just alert - in a real app you'd use a proper notification system
            alert(message);
        },
        
        // Get client initials
        getClientInitials(client) {
            if (!client || !client.name) return '?';
            return client.name
                .split(' ')
                .map(word => word.charAt(0))
                .slice(0, 2)
                .join('')
                .toUpperCase();
        },
        
        // Get client display name
        getClientDisplayName(client) {
            if (!client) return '';
            if (client.company_name && client.company_name !== client.name) {
                return `${client.name} (${client.company_name})`;
            }
            return client.name;
        },
        
        // Check if item is selected for keyboard navigation
        isItemSelected(index) {
            return this.selectedIndex === index;
        },
        
        // Format for display
        get filteredClients() {
            return this.getSelectableItems();
        },
        
        get hasRecentClients() {
            return this.recentClients.length > 0 && this.searchQuery === '';
        },
        
        get showNoResults() {
            return !this.loading && this.clients.length === 0 && this.searchQuery.length > 0;
        },
        
        get showEmpty() {
            return !this.loading && this.filteredClients.length === 0 && this.searchQuery === '';
        }
    };
}