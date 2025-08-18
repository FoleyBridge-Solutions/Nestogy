window.assetSearchField = function(options = {}) {
    return {
        // Configuration
        name: options.name || 'asset_id',
        clientId: options.clientId || null,
        selectedAsset: options.selectedAsset || null,
        
        // State
        searchQuery: '',
        selectedAssetId: '',
        assets: [],
        filteredAssets: [],
        open: false,
        selectedIndex: -1,
        loadingAssets: false,
        
        // Initialize
        init() {
            console.log('AssetSearchField init with client:', this.clientId);
            
            // Set initial values if asset is pre-selected
            if (this.selectedAsset && this.selectedAsset.id) {
                console.log('Initial selected asset:', this.selectedAsset);
                this.searchQuery = this.selectedAsset.name || '';
                this.selectedAssetId = this.selectedAsset.id;
            }
            
            // Load assets if client is already selected
            if (this.clientId) {
                this.loadAssets();
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.close();
                }
            });
            
            // Listen for client selection events
            this.$watch('clientId', (newClientId) => {
                if (newClientId) {
                    this.loadAssets();
                } else {
                    this.clearAll();
                }
            });
            
            // Listen for client-selected events from client dropdown
            window.addEventListener('client-selected', (e) => {
                const newClient = e.detail.client;
                if (newClient && newClient.id) {
                    console.log('Asset field received client-selected event:', newClient.id);
                    this.clientId = newClient.id;
                } else {
                    console.log('Asset field received client-selected event: no client');
                    this.clientId = null;
                }
            });
        },
        
        // Load assets for the current client
        async loadAssets() {
            if (!this.clientId) {
                this.assets = [];
                return;
            }
            
            this.loadingAssets = true;
            
            try {
                const response = await fetch(`/clients/${this.clientId}/assets`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    this.assets = await response.json();
                    this.filteredAssets = [...this.assets];
                    console.log('Loaded assets:', this.assets.length);
                } else {
                    console.error('Failed to load assets:', response.status);
                    this.assets = [];
                }
            } catch (error) {
                console.error('Error loading assets:', error);
                this.assets = [];
            } finally {
                this.loadingAssets = false;
            }
        },
        
        // Open dropdown
        openDropdown() {
            if (this.assets.length > 0) {
                this.open = true;
                this.filteredAssets = [...this.assets];
                this.selectedIndex = -1;
            }
        },
        
        // Search assets
        search() {
            if (!this.searchQuery.trim()) {
                this.filteredAssets = [...this.assets];
                this.openDropdown();
                return;
            }
            
            const query = this.searchQuery.toLowerCase();
            this.filteredAssets = this.assets.filter(asset => 
                asset.name.toLowerCase().includes(query) ||
                asset.type.toLowerCase().includes(query) ||
                asset.serial.toLowerCase().includes(query) ||
                asset.model.toLowerCase().includes(query)
            );
            
            this.open = true; // Show dropdown even if no results to display "no results" message
            this.selectedIndex = -1;
        },
        
        // Handle keyboard navigation
        onKeyDown(event) {
            if (!this.open) {
                if (event.key === 'ArrowDown' || event.key === 'Enter') {
                    this.openDropdown();
                    event.preventDefault();
                }
                return;
            }
            
            switch (event.key) {
                case 'ArrowDown':
                    this.selectedIndex = Math.min(this.selectedIndex + 1, this.filteredAssets.length - 1);
                    event.preventDefault();
                    break;
                case 'ArrowUp':
                    this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                    event.preventDefault();
                    break;
                case 'Enter':
                    if (this.selectedIndex >= 0 && this.filteredAssets[this.selectedIndex]) {
                        this.selectAsset(this.filteredAssets[this.selectedIndex]);
                    }
                    event.preventDefault();
                    break;
                case 'Escape':
                    this.close();
                    event.preventDefault();
                    break;
            }
        },
        
        // Select an asset
        selectAsset(asset) {
            if (!asset) return;
            
            this.selectedAsset = asset;
            this.selectedAssetId = asset.id;
            this.searchQuery = asset.name;
            this.close();
            
            console.log('Asset selected:', asset);
            
            // Dispatch change event
            this.$dispatch('asset-selected', { asset });
        },
        
        // Clear selection
        clearSelection() {
            this.selectedAsset = null;
            this.selectedAssetId = '';
            this.searchQuery = '';
            this.close();
            
            // Dispatch change event
            this.$dispatch('asset-cleared');
        },
        
        // Clear all
        clearAll() {
            this.clearSelection();
            this.assets = [];
            this.filteredAssets = [];
        },
        
        // Close dropdown
        close() {
            this.open = false;
            this.selectedIndex = -1;
        },
        
        // Update assets when client changes (called from parent)
        updateClientId(newClientId) {
            this.clientId = newClientId;
        }
    };
};