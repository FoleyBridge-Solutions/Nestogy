/**
 * Auto-save Component
 * Automatically saves quote drafts every 30 seconds with conflict resolution
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('autoSave', (config = {}) => ({
        // Configuration
        interval: config.interval || 30000, // 30 seconds
        apiEndpoint: config.apiEndpoint || '/api/quotes/auto-save',
        maxRetries: config.maxRetries || 3,
        conflictResolution: config.conflictResolution || 'merge', // 'merge', 'overwrite', 'prompt'
        
        // State
        enabled: true,
        saving: false,
        lastSaved: null,
        saveCount: 0,
        retryCount: 0,
        internetConnected: navigator.onLine,
        
        // Data state tracking
        lastSavedData: null,
        pendingSaves: [],
        saveQueue: [],
        
        // Status
        status: 'idle', // 'idle', 'saving', 'saved', 'error', 'conflict'
        message: '',
        
        // Conflict handling
        conflictData: null,
        showConflictModal: false,
        
        // Initialize auto-save
        init() {
            this.setupWatchers();
            this.setupEventListeners();
            this.startAutoSave();
            this.loadPreviousDraft();
        },

        // Setup watchers for quote data changes
        setupWatchers() {
            // Watch for changes in quote data
            this.$watch('$store.quote.document', () => {
                this.markAsChanged();
            }, { deep: true });

            this.$watch('$store.quote.selectedItems', () => {
                this.markAsChanged();
            }, { deep: true });

            this.$watch('$store.quote.pricing', () => {
                this.markAsChanged();
            }, { deep: true });
        },

        // Setup event listeners
        setupEventListeners() {
            // Network status
            window.addEventListener('online', () => {
                this.internetConnected = true;
                this.processOfflineQueue();
            });

            window.addEventListener('offline', () => {
                this.internetConnected = false;
                this.status = 'offline';
            });

            // Page visibility for pause/resume
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseAutoSave();
                } else {
                    this.resumeAutoSave();
                }
            });

            // Before unload warning
            window.addEventListener('beforeunload', (e) => {
                if (this.hasUnsavedChanges()) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                    return e.returnValue;
                }
            });

            // Manual save shortcuts
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    this.saveNow();
                }
            });
        },

        // Start auto-save timer
        startAutoSave() {
            if (this.autoSaveTimer) {
                clearInterval(this.autoSaveTimer);
            }

            this.autoSaveTimer = setInterval(() => {
                if (this.enabled && this.hasUnsavedChanges() && this.internetConnected) {
                    this.saveNow();
                }
            }, this.interval);
        },

        // Stop auto-save timer
        stopAutoSave() {
            if (this.autoSaveTimer) {
                clearInterval(this.autoSaveTimer);
                this.autoSaveTimer = null;
            }
        },

        // Pause auto-save
        pauseAutoSave() {
            this.enabled = false;
        },

        // Resume auto-save
        resumeAutoSave() {
            this.enabled = true;
        },

        // Mark data as changed
        markAsChanged() {
            this.status = 'changed';
        },

        // Check if there are unsaved changes
        hasUnsavedChanges() {
            const currentData = this.getCurrentData();
            return JSON.stringify(currentData) !== JSON.stringify(this.lastSavedData);
        },

        // Get current quote data
        getCurrentData() {
            return {
                document: { ...this.$store.quote.document },
                selectedItems: [...this.$store.quote.selectedItems],
                pricing: { ...this.$store.quote.pricing },
                timestamp: Date.now()
            };
        },

        // Save now (manual or auto)
        async saveNow(force = false) {
            if (this.saving && !force) return;

            try {
                this.saving = true;
                this.status = 'saving';
                this.message = 'Saving draft...';

                const currentData = this.getCurrentData();

                // Skip if no changes and not forced
                if (!force && !this.hasUnsavedChanges()) {
                    this.status = 'idle';
                    this.saving = false;
                    return;
                }

                // Add to queue if offline
                if (!this.internetConnected) {
                    this.addToOfflineQueue(currentData);
                    this.status = 'offline';
                    this.message = 'Saved offline. Will sync when connection is restored.';
                    this.saving = false;
                    return;
                }

                const response = await this.sendSaveRequest(currentData);

                if (response.success) {
                    this.handleSaveSuccess(response, currentData);
                } else {
                    throw new Error(response.message || 'Save failed');
                }

            } catch (error) {
                this.handleSaveError(error);
            } finally {
                this.saving = false;
            }
        },

        // Send save request to server
        async sendSaveRequest(data) {
            const payload = {
                quote_id: this.$store.quote.quoteId,
                document: data.document,
                items: data.selectedItems,
                pricing: data.pricing,
                timestamp: data.timestamp,
                checksum: this.generateChecksum(data)
            };

            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Auto-Save': 'true'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            // Handle conflicts
            if (response.status === 409) {
                this.handleConflict(result);
                return { success: false, conflict: true };
            }

            if (!response.ok) {
                throw new Error(result.message || `HTTP ${response.status}`);
            }

            return result;
        },

        // Handle successful save
        handleSaveSuccess(response, data) {
            this.lastSavedData = data;
            this.lastSaved = new Date();
            this.saveCount++;
            this.retryCount = 0;
            this.status = 'saved';
            this.message = `Saved at ${this.lastSaved.toLocaleTimeString()}`;

            // Store draft key for recovery
            if (response.draft_key) {
                localStorage.setItem('quote_draft_key', response.draft_key);
            }

            // Dispatch success event
            this.$dispatch('auto-save-success', {
                timestamp: this.lastSaved,
                saveCount: this.saveCount
            });

            // Clear success message after delay
            setTimeout(() => {
                if (this.status === 'saved') {
                    this.status = 'idle';
                    this.message = '';
                }
            }, 3000);
        },

        // Handle save error
        handleSaveError(error) {
            this.retryCount++;
            this.status = 'error';
            
            if (this.retryCount < this.maxRetries) {
                this.message = `Save failed (retry ${this.retryCount}/${this.maxRetries}). Retrying...`;
                
                // Exponential backoff retry
                setTimeout(() => {
                    this.saveNow(true);
                }, Math.pow(2, this.retryCount) * 1000);
            } else {
                this.message = 'Save failed. Changes stored locally.';
                
                // Store locally as backup
                this.storeLocalBackup();
                
                // Reset retry count
                this.retryCount = 0;
            }

            console.error('Auto-save error:', error);
            
            // Dispatch error event
            this.$dispatch('auto-save-error', {
                error: error.message,
                retryCount: this.retryCount
            });
        },

        // Handle version conflicts
        handleConflict(conflictData) {
            this.conflictData = conflictData;
            this.status = 'conflict';
            
            switch (this.conflictResolution) {
                case 'overwrite':
                    this.resolveConflict('overwrite');
                    break;
                case 'merge':
                    this.resolveConflict('merge');
                    break;
                case 'prompt':
                default:
                    this.showConflictModal = true;
                    this.message = 'Version conflict detected. Please resolve.';
                    break;
            }
        },

        // Resolve version conflict
        async resolveConflict(strategy) {
            try {
                let resolvedData;
                
                switch (strategy) {
                    case 'overwrite':
                        resolvedData = this.getCurrentData();
                        break;
                    case 'use_server':
                        resolvedData = this.conflictData.server_version;
                        this.loadData(resolvedData);
                        break;
                    case 'merge':
                        resolvedData = this.mergeData(this.getCurrentData(), this.conflictData.server_version);
                        this.loadData(resolvedData);
                        break;
                }

                // Save resolved version
                const response = await this.sendSaveRequest({
                    ...resolvedData,
                    force: true,
                    resolve_conflict: strategy
                });

                if (response.success) {
                    this.handleSaveSuccess(response, resolvedData);
                    this.showConflictModal = false;
                    this.conflictData = null;
                }

            } catch (error) {
                this.handleSaveError(error);
            }
        },

        // Merge conflicting data
        mergeData(local, server) {
            const merged = { ...server };
            
            // Merge document fields (prefer local changes)
            merged.document = { ...server.document, ...local.document };
            
            // Merge items (combine both sets, prefer local)
            const serverItemsMap = new Map(server.selectedItems.map(item => [item.id || item.temp_id, item]));
            const localItemsMap = new Map(local.selectedItems.map(item => [item.id || item.temp_id, item]));
            
            merged.selectedItems = [
                ...Array.from(localItemsMap.values()),
                ...Array.from(serverItemsMap.values()).filter(item => !localItemsMap.has(item.id || item.temp_id))
            ];
            
            // Use local pricing (likely more recent)
            merged.pricing = local.pricing;
            
            return merged;
        },

        // Load data into store
        loadData(data) {
            this.$store.quote.document = data.document;
            this.$store.quote.selectedItems = data.selectedItems;
            this.$store.quote.pricing = data.pricing;
        },

        // Offline queue management
        addToOfflineQueue(data) {
            this.saveQueue.push({
                data,
                timestamp: Date.now(),
                retries: 0
            });
            
            // Limit queue size
            if (this.saveQueue.length > 10) {
                this.saveQueue.shift();
            }
            
            this.storeLocalBackup();
        },

        // Process offline queue when connection restored
        async processOfflineQueue() {
            if (this.saveQueue.length === 0) return;

            this.message = 'Syncing offline changes...';
            
            while (this.saveQueue.length > 0) {
                const queueItem = this.saveQueue.shift();
                
                try {
                    await this.sendSaveRequest(queueItem.data);
                } catch (error) {
                    // Re-add to queue if failed
                    queueItem.retries++;
                    if (queueItem.retries < 3) {
                        this.saveQueue.unshift(queueItem);
                    }
                    break;
                }
            }
            
            this.message = 'Sync completed';
            setTimeout(() => this.message = '', 2000);
        },

        // Local storage backup
        storeLocalBackup() {
            try {
                const backup = {
                    data: this.getCurrentData(),
                    timestamp: Date.now(),
                    url: window.location.href
                };
                
                localStorage.setItem('quote_backup', JSON.stringify(backup));
                localStorage.setItem('quote_offline_queue', JSON.stringify(this.saveQueue));
            } catch (error) {
                console.error('Failed to store local backup:', error);
            }
        },

        // Load previous draft
        loadPreviousDraft() {
            try {
                const backup = localStorage.getItem('quote_backup');
                const queue = localStorage.getItem('quote_offline_queue');
                
                if (backup) {
                    const backupData = JSON.parse(backup);
                    
                    // Only load if recent (last 24 hours) and same URL
                    if (Date.now() - backupData.timestamp < 24 * 60 * 60 * 1000 && 
                        backupData.url === window.location.href) {
                        
                        this.showDraftRecoveryPrompt(backupData.data);
                    }
                }
                
                if (queue) {
                    this.saveQueue = JSON.parse(queue);
                }
                
            } catch (error) {
                console.error('Failed to load previous draft:', error);
            }
        },

        // Show draft recovery prompt
        showDraftRecoveryPrompt(draftData) {
            if (confirm('A previous draft was found. Would you like to restore it?')) {
                this.loadData(draftData);
                this.lastSavedData = draftData;
                this.message = 'Draft restored from local backup';
            }
        },

        // Generate checksum for data integrity
        generateChecksum(data) {
            const str = JSON.stringify(data);
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                const char = str.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash; // Convert to 32bit integer
            }
            return hash.toString();
        },

        // Manual controls
        enableAutoSave() {
            this.enabled = true;
            this.startAutoSave();
        },

        disableAutoSave() {
            this.enabled = false;
            this.stopAutoSave();
        },

        setInterval(seconds) {
            this.interval = seconds * 1000;
            if (this.enabled) {
                this.startAutoSave();
            }
        },

        // Clear all saved data
        clearSavedData() {
            this.lastSavedData = null;
            this.saveQueue = [];
            localStorage.removeItem('quote_backup');
            localStorage.removeItem('quote_offline_queue');
            localStorage.removeItem('quote_draft_key');
        },

        // Cleanup on destroy
        destroy() {
            this.stopAutoSave();
            if (this.hasUnsavedChanges()) {
                this.storeLocalBackup();
            }
        },

        // Computed properties
        get saveIntervalText() {
            return this.interval < 60000 ? 
                `${this.interval / 1000}s` : 
                `${this.interval / 60000}m`;
        },

        get statusIcon() {
            const icons = {
                'idle': '',
                'changed': '●',
                'saving': '⟳',
                'saved': '✓',
                'error': '✗',
                'conflict': '⚠',
                'offline': '📡'
            };
            return icons[this.status] || '';
        },

        get statusClass() {
            const classes = {
                'idle': 'text-gray-500',
                'changed': 'text-yellow-500',
                'saving': 'text-blue-500',
                'saved': 'text-green-500',
                'error': 'text-red-500',
                'conflict': 'text-orange-500',
                'offline': 'text-gray-400'
            };
            return classes[this.status] || 'text-gray-500';
        }
    }));

    // Auto-save store for global access
    Alpine.store('autoSave', {
        enabled: true,
        interval: 30000,
        
        enable() {
            this.enabled = true;
        },
        
        disable() {
            this.enabled = false;
        },
        
        setInterval(seconds) {
            this.interval = seconds * 1000;
        }
    });
});