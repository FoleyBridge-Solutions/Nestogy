/**
 * Quote Versioning and Revision Tracking Component
 * Manages quote versions, change tracking, and revision history
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('quoteVersioning', (config = {}) => ({
        // Configuration
        enableAutoVersioning: config.enableAutoVersioning !== false,
        maxVersions: config.maxVersions || 50,
        enableChangeTracking: config.enableChangeTracking !== false,
        
        // Version state
        currentQuote: null,
        versions: [],
        currentVersion: null,
        
        // Revision tracking
        revisionHistory: [],
        pendingChanges: new Map(),
        changesSinceLastSave: false,
        
        // Version comparison
        compareMode: false,
        compareVersions: {
            left: null,
            right: null
        },
        
        // UI state
        showVersionHistory: false,
        showVersionComparison: false,
        showRestoreConfirmation: false,
        versionToRestore: null,
        
        // Change tracking
        changeTracker: {
            enabled: true,
            changes: [],
            lastSnapshot: null,
            trackingFields: [
                'client_id', 'quote_number', 'status', 'total_amount',
                'discount_amount', 'tax_amount', 'notes', 'items'
            ]
        },
        
        init() {
            this.setupEventListeners();
            this.initializeChangeTracking();
        },
        
        setupEventListeners() {
            // Listen for quote changes
            document.addEventListener('quote-loaded', (e) => {
                this.setCurrentQuote(e.detail.quote);
            });
            
            document.addEventListener('quote-saved', (e) => {
                this.handleQuoteSaved(e.detail.quote);
            });
            
            // Listen for form changes
            document.addEventListener('quote-field-changed', (e) => {
                this.trackChange(e.detail.field, e.detail.oldValue, e.detail.newValue);
            });
            
            // Auto-save timer for versioning
            if (this.enableAutoVersioning) {
                setInterval(() => {
                    this.checkForAutoVersion();
                }, 300000); // Every 5 minutes
            }
        },
        
        async setCurrentQuote(quote) {
            this.currentQuote = quote;
            await this.loadVersionHistory();
            this.initializeChangeTracking();
        },
        
        async loadVersionHistory() {
            if (!this.currentQuote?.id) return;
            
            try {
                const response = await fetch(`/api/quotes/${this.currentQuote.id}/versions`);
                if (response.ok) {
                    const data = await response.json();
                    this.versions = data.versions;
                    this.revisionHistory = data.revisions;
                    this.currentVersion = data.current_version;
                }
            } catch (error) {
                console.error('Failed to load version history:', error);
            }
        },
        
        initializeChangeTracking() {
            if (!this.enableChangeTracking || !this.currentQuote) return;
            
            this.changeTracker.lastSnapshot = this.createSnapshot(this.currentQuote);
            this.changeTracker.changes = [];
            this.pendingChanges.clear();
            this.changesSinceLastSave = false;
        },
        
        createSnapshot(quote) {
            const snapshot = {};
            this.changeTracker.trackingFields.forEach(field => {
                snapshot[field] = this.getFieldValue(quote, field);
            });
            return snapshot;
        },
        
        getFieldValue(obj, path) {
            return path.split('.').reduce((current, key) => {
                return current && current[key] !== undefined ? current[key] : null;
            }, obj);
        },
        
        trackChange(field, oldValue, newValue) {
            if (!this.enableChangeTracking) return;
            
            const change = {
                id: Date.now(),
                field: field,
                old_value: oldValue,
                new_value: newValue,
                timestamp: new Date().toISOString(),
                user_id: this.getCurrentUserId()
            };
            
            this.changeTracker.changes.push(change);
            this.pendingChanges.set(field, change);
            this.changesSinceLastSave = true;
            
            // Dispatch change event
            this.$dispatch('quote-change-tracked', { change });
        },
        
        async createVersion(description = '', isAutomatic = false) {
            if (!this.currentQuote?.id) return null;
            
            try {
                const versionData = {
                    quote_id: this.currentQuote.id,
                    description: description || (isAutomatic ? 'Auto-save version' : 'Manual version'),
                    is_automatic: isAutomatic,
                    changes: Array.from(this.pendingChanges.values()),
                    snapshot: this.createSnapshot(this.currentQuote)
                };
                
                const response = await fetch(`/api/quotes/${this.currentQuote.id}/versions`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(versionData)
                });
                
                if (response.ok) {
                    const newVersion = await response.json();
                    this.versions.unshift(newVersion);
                    this.currentVersion = newVersion;
                    
                    // Clear pending changes
                    this.pendingChanges.clear();
                    this.changesSinceLastSave = false;
                    
                    // Update change tracker
                    this.changeTracker.lastSnapshot = this.createSnapshot(this.currentQuote);
                    
                    return newVersion;
                } else {
                    throw new Error('Failed to create version');
                }
                
            } catch (error) {
                console.error('Version creation error:', error);
                return null;
            }
        },
        
        async restoreVersion(versionId) {
            const version = this.versions.find(v => v.id === versionId);
            if (!version) return;
            
            this.versionToRestore = version;
            this.showRestoreConfirmation = true;
        },
        
        async confirmRestoreVersion() {
            if (!this.versionToRestore) return;
            
            try {
                const response = await fetch(
                    `/api/quotes/${this.currentQuote.id}/versions/${this.versionToRestore.id}/restore`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    }
                );
                
                if (response.ok) {
                    const restoredQuote = await response.json();
                    
                    // Update current quote
                    this.currentQuote = restoredQuote;
                    
                    // Create a new version for the restore
                    await this.createVersion(`Restored from version ${this.versionToRestore.version_number}`);
                    
                    // Dispatch restore event
                    this.$dispatch('quote-version-restored', {
                        quote: restoredQuote,
                        restoredVersion: this.versionToRestore
                    });
                    
                    this.showRestoreConfirmation = false;
                    this.versionToRestore = null;
                    
                } else {
                    throw new Error('Failed to restore version');
                }
                
            } catch (error) {
                console.error('Version restore error:', error);
                this.showError('Failed to restore version');
            }
        },
        
        async deleteVersion(versionId) {
            if (!confirm('Are you sure you want to delete this version?')) return;
            
            try {
                const response = await fetch(
                    `/api/quotes/${this.currentQuote.id}/versions/${versionId}`,
                    {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    }
                );
                
                if (response.ok) {
                    this.versions = this.versions.filter(v => v.id !== versionId);
                } else {
                    throw new Error('Failed to delete version');
                }
                
            } catch (error) {
                console.error('Version delete error:', error);
                this.showError('Failed to delete version');
            }
        },
        
        compareVersions(leftVersionId, rightVersionId) {
            const leftVersion = this.versions.find(v => v.id === leftVersionId);
            const rightVersion = this.versions.find(v => v.id === rightVersionId);
            
            if (!leftVersion || !rightVersion) return;
            
            this.compareVersions.left = leftVersion;
            this.compareVersions.right = rightVersion;
            this.compareMode = true;
            this.showVersionComparison = true;
        },
        
        getVersionDiff(leftVersion, rightVersion) {
            const diff = {
                added: [],
                removed: [],
                modified: []
            };
            
            const leftData = leftVersion.snapshot;
            const rightData = rightVersion.snapshot;
            
            // Find added and modified fields
            Object.keys(rightData).forEach(key => {
                if (!(key in leftData)) {
                    diff.added.push({
                        field: key,
                        value: rightData[key]
                    });
                } else if (JSON.stringify(leftData[key]) !== JSON.stringify(rightData[key])) {
                    diff.modified.push({
                        field: key,
                        old_value: leftData[key],
                        new_value: rightData[key]
                    });
                }
            });
            
            // Find removed fields
            Object.keys(leftData).forEach(key => {
                if (!(key in rightData)) {
                    diff.removed.push({
                        field: key,
                        value: leftData[key]
                    });
                }
            });
            
            return diff;
        },
        
        handleQuoteSaved(quote) {
            if (this.enableAutoVersioning && this.changesSinceLastSave) {
                this.createVersion('Auto-save on quote save', true);
            }
        },
        
        checkForAutoVersion() {
            if (this.changesSinceLastSave && this.pendingChanges.size > 0) {
                this.createVersion('Auto-save checkpoint', true);
            }
        },
        
        exportVersionHistory() {
            const exportData = {
                quote_id: this.currentQuote.id,
                quote_number: this.currentQuote.quote_number,
                versions: this.versions,
                revisions: this.revisionHistory,
                exported_at: new Date().toISOString()
            };
            
            const blob = new Blob([JSON.stringify(exportData, null, 2)], {
                type: 'application/json'
            });
            
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `quote-${this.currentQuote.quote_number}-history.json`;
            a.click();
            
            URL.revokeObjectURL(url);
        },
        
        getCurrentUserId() {
            // Get current user ID from meta tag or global variable
            return document.querySelector('meta[name="user-id"]')?.content || null;
        },
        
        formatTimestamp(timestamp) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date(timestamp));
        },
        
        getChangeTypeIcon(changeType) {
            const icons = {
                'added': '➕',
                'modified': '✏️',
                'removed': '➖'
            };
            return icons[changeType] || '📝';
        },
        
        showError(message) {
            this.$dispatch('notification', {
                type: 'error',
                message: message
            });
        },
        
        showSuccess(message) {
            this.$dispatch('notification', {
                type: 'success',
                message: message
            });
        },
        
        // Computed properties
        get hasVersions() {
            return this.versions.length > 0;
        },
        
        get hasChanges() {
            return this.changesSinceLastSave;
        },
        
        get canCreateVersion() {
            return this.currentQuote && this.changesSinceLastSave;
        },
        
        get versionsCount() {
            return this.versions.length;
        },
        
        get automaticVersionsCount() {
            return this.versions.filter(v => v.is_automatic).length;
        },
        
        get manualVersionsCount() {
            return this.versions.filter(v => !v.is_automatic).length;
        },
        
        get recentChanges() {
            return this.changeTracker.changes.slice(-10).reverse();
        },
        
        get latestVersion() {
            return this.versions[0] || null;
        }
    }));
});