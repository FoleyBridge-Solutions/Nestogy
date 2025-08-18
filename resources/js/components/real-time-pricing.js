/**
 * Real-Time Pricing Updates Component
 * Handles live pricing updates and synchronization across quote system
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('realTimePricing', (config = {}) => ({
        // Configuration
        updateInterval: config.updateInterval || 30000, // 30 seconds
        enableWebSocket: config.enableWebSocket !== false,
        enablePushNotifications: config.enablePushNotifications !== false,
        maxRetries: config.maxRetries || 3,
        retryDelay: config.retryDelay || 5000,
        
        // Connection state
        connection: {
            websocket: null,
            status: 'disconnected', // disconnected, connecting, connected, error
            lastUpdate: null,
            reconnectAttempts: 0,
            heartbeatInterval: null
        },
        
        // Pricing state
        pricingData: new Map(),
        pendingUpdates: new Map(),
        lastSyncTime: null,
        
        // Subscription management
        subscriptions: new Set(),
        priceWatchers: new Map(),
        
        // Update queue
        updateQueue: [],
        isProcessingQueue: false,
        
        // Conflict resolution
        conflictResolver: {
            enabled: true,
            strategy: 'latest_wins', // latest_wins, user_priority, merge
            pendingConflicts: []
        },
        
        // Performance tracking
        metrics: {
            updateCount: 0,
            averageLatency: 0,
            errorCount: 0,
            connectionUptime: 0
        },
        
        // Notification settings
        notifications: {
            priceChanges: true,
            connectionStatus: false,
            conflictAlerts: true
        },
        
        // Initialize real-time pricing
        init() {
            this.initializeConnection();
            this.setupEventListeners();
            this.startPeriodicSync();
            this.loadPersistedData();
        },
        
        // Initialize WebSocket or fallback connection
        initializeConnection() {
            if (this.enableWebSocket && window.WebSocket) {
                this.initializeWebSocket();
            } else {
                this.initializePolling();
            }
        },
        
        // Initialize WebSocket connection
        initializeWebSocket() {
            try {
                const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                const wsUrl = `${protocol}//${window.location.host}/ws/pricing-updates`;
                
                this.connection.status = 'connecting';
                this.connection.websocket = new WebSocket(wsUrl);
                
                this.connection.websocket.onopen = this.handleWebSocketOpen.bind(this);
                this.connection.websocket.onmessage = this.handleWebSocketMessage.bind(this);
                this.connection.websocket.onclose = this.handleWebSocketClose.bind(this);
                this.connection.websocket.onerror = this.handleWebSocketError.bind(this);
                
            } catch (error) {
                console.error('Failed to initialize WebSocket:', error);
                this.initializePolling();
            }
        },
        
        // Initialize polling fallback
        initializePolling() {
            this.connection.status = 'connected';
            
            setInterval(() => {
                this.fetchPricingUpdates();
            }, this.updateInterval);
            
            this.startHeartbeat();
        },
        
        // Setup event listeners
        setupEventListeners() {
            // Listen for quote item changes
            document.addEventListener('quote-items-changed', (e) => {
                this.subscribeToItems(e.detail.items);
            });
            
            // Listen for page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.handlePageHidden();
                } else {
                    this.handlePageVisible();
                }
            });
            
            // Listen for network status
            window.addEventListener('online', () => {
                this.handleNetworkOnline();
            });
            
            window.addEventListener('offline', () => {
                this.handleNetworkOffline();
            });
            
            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                this.cleanup();
            });
        },
        
        // WebSocket event handlers
        handleWebSocketOpen() {
            this.connection.status = 'connected';
            this.connection.reconnectAttempts = 0;
            this.connection.lastUpdate = Date.now();
            
            // Send authentication and subscription data
            this.sendWebSocketMessage({
                type: 'auth',
                token: this.getAuthToken()
            });
            
            // Subscribe to existing items
            this.resubscribeToItems();
            
            this.startHeartbeat();
            this.notifyConnectionStatus('connected');
        },
        
        handleWebSocketMessage(event) {
            try {
                const message = JSON.parse(event.data);
                this.processWebSocketMessage(message);
            } catch (error) {
                console.error('Failed to parse WebSocket message:', error);
            }
        },
        
        handleWebSocketClose(event) {
            this.connection.status = 'disconnected';
            this.stopHeartbeat();
            
            if (!event.wasClean) {
                this.attemptReconnection();
            }
            
            this.notifyConnectionStatus('disconnected');
        },
        
        handleWebSocketError(error) {
            console.error('WebSocket error:', error);
            this.connection.status = 'error';
            this.metrics.errorCount++;
        },
        
        // Process WebSocket messages
        processWebSocketMessage(message) {
            switch (message.type) {
                case 'price_update':
                    this.handlePriceUpdate(message.data);
                    break;
                case 'bulk_update':
                    this.handleBulkUpdate(message.data);
                    break;
                case 'heartbeat':
                    this.handleHeartbeat();
                    break;
                case 'error':
                    this.handleServerError(message.data);
                    break;
                case 'conflict':
                    this.handlePriceConflict(message.data);
                    break;
            }
        },
        
        // Handle individual price updates
        handlePriceUpdate(update) {
            const { item_id, old_price, new_price, timestamp, source } = update;
            
            // Check for conflicts
            if (this.hasPendingChanges(item_id)) {
                this.resolveConflict(item_id, update);
                return;
            }
            
            // Apply update
            this.applyPriceUpdate(item_id, new_price, timestamp);
            
            // Notify watchers
            this.notifyPriceWatchers(item_id, old_price, new_price);
            
            // Update metrics
            this.updateMetrics(update);
        },
        
        // Handle bulk price updates
        handleBulkUpdate(updates) {
            updates.forEach(update => {
                this.handlePriceUpdate(update);
            });
        },
        
        // Apply price update to local state
        applyPriceUpdate(itemId, newPrice, timestamp) {
            const currentData = this.pricingData.get(itemId) || {};
            
            this.pricingData.set(itemId, {
                ...currentData,
                price: newPrice,
                last_updated: timestamp,
                source: 'server'
            });
            
            // Update quote items if loaded
            this.updateQuoteItemPrice(itemId, newPrice);
            
            // Persist to localStorage
            this.persistPricingData();
        },
        
        // Update quote item prices
        updateQuoteItemPrice(itemId, newPrice) {
            // Dispatch event to update quote store
            this.$dispatch('price-updated', {
                item_id: itemId,
                new_price: newPrice,
                timestamp: Date.now()
            });
        },
        
        // Subscribe to price updates for specific items
        subscribeToItems(items) {
            const itemIds = items.map(item => item.id || item.product_id).filter(Boolean);
            
            itemIds.forEach(itemId => {
                if (!this.subscriptions.has(itemId)) {
                    this.subscriptions.add(itemId);
                    
                    if (this.connection.websocket && this.connection.status === 'connected') {
                        this.sendWebSocketMessage({
                            type: 'subscribe',
                            item_id: itemId
                        });
                    }
                }
            });
        },
        
        // Unsubscribe from price updates
        unsubscribeFromItems(itemIds) {
            itemIds.forEach(itemId => {
                this.subscriptions.delete(itemId);
                
                if (this.connection.websocket && this.connection.status === 'connected') {
                    this.sendWebSocketMessage({
                        type: 'unsubscribe',
                        item_id: itemId
                    });
                }
            });
        },
        
        // Add price watcher callback
        addPriceWatcher(itemId, callback) {
            if (!this.priceWatchers.has(itemId)) {
                this.priceWatchers.set(itemId, new Set());
            }
            this.priceWatchers.get(itemId).add(callback);
        },
        
        // Remove price watcher
        removePriceWatcher(itemId, callback) {
            const watchers = this.priceWatchers.get(itemId);
            if (watchers) {
                watchers.delete(callback);
                if (watchers.size === 0) {
                    this.priceWatchers.delete(itemId);
                }
            }
        },
        
        // Notify price watchers
        notifyPriceWatchers(itemId, oldPrice, newPrice) {
            const watchers = this.priceWatchers.get(itemId);
            if (watchers) {
                watchers.forEach(callback => {
                    try {
                        callback(itemId, oldPrice, newPrice);
                    } catch (error) {
                        console.error('Price watcher callback error:', error);
                    }
                });
            }
        },
        
        // Fetch pricing updates via API
        async fetchPricingUpdates() {
            if (this.subscriptions.size === 0) return;
            
            try {
                const itemIds = Array.from(this.subscriptions);
                const response = await fetch('/api/pricing/updates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        item_ids: itemIds,
                        last_sync: this.lastSyncTime
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.processFetchedUpdates(data.updates);
                    this.lastSyncTime = data.sync_time;
                } else {
                    throw new Error(`HTTP ${response.status}`);
                }
                
            } catch (error) {
                console.error('Failed to fetch pricing updates:', error);
                this.handleFetchError(error);
            }
        },
        
        // Process fetched updates
        processFetchedUpdates(updates) {
            updates.forEach(update => {
                this.handlePriceUpdate(update);
            });
        },
        
        // Handle price conflicts
        resolveConflict(itemId, serverUpdate) {
            const localData = this.pendingUpdates.get(itemId);
            const conflict = {
                item_id: itemId,
                local_change: localData,
                server_update: serverUpdate,
                timestamp: Date.now()
            };
            
            this.conflictResolver.pendingConflicts.push(conflict);
            
            switch (this.conflictResolver.strategy) {
                case 'latest_wins':
                    this.resolveByLatest(conflict);
                    break;
                case 'user_priority':
                    this.resolveByUserPriority(conflict);
                    break;
                case 'merge':
                    this.resolveByCombining(conflict);
                    break;
                default:
                    this.showConflictDialog(conflict);
            }
        },
        
        // Resolve conflict by latest timestamp
        resolveByLatest(conflict) {
            const localTime = conflict.local_change.timestamp;
            const serverTime = new Date(conflict.server_update.timestamp).getTime();
            
            if (serverTime > localTime) {
                this.applyServerUpdate(conflict);
            } else {
                this.keepLocalChange(conflict);
            }
        },
        
        // Resolve conflict by user priority
        resolveByUserPriority(conflict) {
            // Always keep user changes
            this.keepLocalChange(conflict);
        },
        
        // Show conflict resolution dialog
        showConflictDialog(conflict) {
            if (this.notifications.conflictAlerts) {
                this.$dispatch('price-conflict', {
                    conflict: conflict,
                    resolve: (resolution) => {
                        this.applyConflictResolution(conflict, resolution);
                    }
                });
            }
        },
        
        // Apply conflict resolution
        applyConflictResolution(conflict, resolution) {
            switch (resolution) {
                case 'keep_local':
                    this.keepLocalChange(conflict);
                    break;
                case 'use_server':
                    this.applyServerUpdate(conflict);
                    break;
                case 'merge':
                    this.resolveByCombining(conflict);
                    break;
            }
        },
        
        // Send WebSocket message
        sendWebSocketMessage(message) {
            if (this.connection.websocket && this.connection.status === 'connected') {
                this.connection.websocket.send(JSON.stringify(message));
            }
        },
        
        // Start heartbeat
        startHeartbeat() {
            this.stopHeartbeat();
            
            this.connection.heartbeatInterval = setInterval(() => {
                if (this.connection.websocket && this.connection.status === 'connected') {
                    this.sendWebSocketMessage({ type: 'ping' });
                } else {
                    this.fetchPricingUpdates();
                }
            }, 30000);
        },
        
        // Stop heartbeat
        stopHeartbeat() {
            if (this.connection.heartbeatInterval) {
                clearInterval(this.connection.heartbeatInterval);
                this.connection.heartbeatInterval = null;
            }
        },
        
        // Handle heartbeat response
        handleHeartbeat() {
            this.connection.lastUpdate = Date.now();
        },
        
        // Attempt WebSocket reconnection
        attemptReconnection() {
            if (this.connection.reconnectAttempts >= this.maxRetries) {
                console.warn('Max reconnection attempts reached');
                this.initializePolling();
                return;
            }
            
            this.connection.reconnectAttempts++;
            
            setTimeout(() => {
                if (this.connection.status === 'disconnected') {
                    this.initializeWebSocket();
                }
            }, this.retryDelay * Math.pow(2, this.connection.reconnectAttempts));
        },
        
        // Resubscribe to items after reconnection
        resubscribeToItems() {
            this.subscriptions.forEach(itemId => {
                this.sendWebSocketMessage({
                    type: 'subscribe',
                    item_id: itemId
                });
            });
        },
        
        // Handle page visibility changes
        handlePageHidden() {
            // Reduce update frequency when page is hidden
            if (this.connection.websocket) {
                this.sendWebSocketMessage({ type: 'reduce_frequency' });
            }
        },
        
        handlePageVisible() {
            // Resume normal update frequency
            if (this.connection.websocket) {
                this.sendWebSocketMessage({ type: 'normal_frequency' });
            }
            
            // Fetch any missed updates
            this.fetchPricingUpdates();
        },
        
        // Handle network status changes
        handleNetworkOnline() {
            if (this.connection.status === 'disconnected') {
                this.initializeConnection();
            }
        },
        
        handleNetworkOffline() {
            this.connection.status = 'disconnected';
            this.notifyConnectionStatus('offline');
        },
        
        // Get authentication token
        getAuthToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        
        // Check for pending changes
        hasPendingChanges(itemId) {
            return this.pendingUpdates.has(itemId);
        },
        
        // Start periodic sync
        startPeriodicSync() {
            setInterval(() => {
                this.syncPendingChanges();
            }, 60000); // Sync every minute
        },
        
        // Sync pending changes
        async syncPendingChanges() {
            if (this.pendingUpdates.size === 0) return;
            
            const changes = Array.from(this.pendingUpdates.entries());
            
            try {
                const response = await fetch('/api/pricing/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.getAuthToken()
                    },
                    body: JSON.stringify({ changes })
                });
                
                if (response.ok) {
                    this.pendingUpdates.clear();
                }
            } catch (error) {
                console.error('Failed to sync pending changes:', error);
            }
        },
        
        // Load persisted data
        loadPersistedData() {
            try {
                const stored = localStorage.getItem('pricing_data');
                if (stored) {
                    const data = JSON.parse(stored);
                    this.pricingData = new Map(data.entries || []);
                    this.lastSyncTime = data.lastSyncTime;
                }
            } catch (error) {
                console.warn('Failed to load persisted pricing data:', error);
            }
        },
        
        // Persist pricing data
        persistPricingData() {
            try {
                const data = {
                    entries: Array.from(this.pricingData.entries()),
                    lastSyncTime: this.lastSyncTime
                };
                localStorage.setItem('pricing_data', JSON.stringify(data));
            } catch (error) {
                console.warn('Failed to persist pricing data:', error);
            }
        },
        
        // Update metrics
        updateMetrics(update) {
            this.metrics.updateCount++;
            
            if (update.latency) {
                const count = this.metrics.updateCount;
                this.metrics.averageLatency = 
                    (this.metrics.averageLatency * (count - 1) + update.latency) / count;
            }
        },
        
        // Notify connection status
        notifyConnectionStatus(status) {
            if (this.notifications.connectionStatus) {
                this.$dispatch('pricing-connection-status', { status });
            }
        },
        
        // Handle fetch errors
        handleFetchError(error) {
            this.metrics.errorCount++;
            
            // Implement exponential backoff
            setTimeout(() => {
                if (this.connection.status === 'connected') {
                    this.fetchPricingUpdates();
                }
            }, Math.min(this.retryDelay * Math.pow(2, this.metrics.errorCount), 60000));
        },
        
        // Cleanup on page unload
        cleanup() {
            this.stopHeartbeat();
            
            if (this.connection.websocket) {
                this.connection.websocket.close();
            }
            
            this.persistPricingData();
        },
        
        // Public API methods
        getCurrentPrice(itemId) {
            const data = this.pricingData.get(itemId);
            return data ? data.price : null;
        },
        
        isConnected() {
            return this.connection.status === 'connected';
        },
        
        getConnectionStatus() {
            return this.connection.status;
        },
        
        forceSync() {
            if (this.connection.websocket) {
                this.sendWebSocketMessage({ type: 'force_sync' });
            } else {
                this.fetchPricingUpdates();
            }
        },
        
        // Computed properties
        get connectionStatusText() {
            const statusTexts = {
                disconnected: 'Disconnected',
                connecting: 'Connecting...',
                connected: 'Connected',
                error: 'Connection Error'
            };
            return statusTexts[this.connection.status] || 'Unknown';
        },
        
        get hasRealtimeData() {
            return this.pricingData.size > 0;
        },
        
        get subscriptionCount() {
            return this.subscriptions.size;
        },
        
        get pendingConflictCount() {
            return this.conflictResolver.pendingConflicts.length;
        }
    }));
});