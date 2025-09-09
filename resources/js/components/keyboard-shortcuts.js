/**
 * Keyboard Shortcuts Component
 * Provides power user keyboard shortcuts for quote management
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('keyboardShortcuts', (config = {}) => ({
        // Configuration
        enabled: config.enabled !== false,
        showHelp: false,
        
        // Shortcuts registry
        shortcuts: {
            // Global shortcuts
            'ctrl+s': { action: 'save', description: 'Save quote', global: true },
            'ctrl+shift+s': { action: 'saveAndContinue', description: 'Save and continue editing', global: true },
            'ctrl+p': { action: 'preview', description: 'Preview PDF', global: true },
            'ctrl+shift+p': { action: 'print', description: 'Print quote', global: true },
            'ctrl+d': { action: 'duplicate', description: 'Duplicate quote', global: true },
            'ctrl+e': { action: 'email', description: 'Email quote', global: true },
            'ctrl+shift+e': { action: 'export', description: 'Export quote', global: true },
            'escape': { action: 'cancel', description: 'Cancel current action', global: true },
            'ctrl+z': { action: 'undo', description: 'Undo last action', global: true },
            'ctrl+y': { action: 'redo', description: 'Redo last action', global: true },
            
            // Navigation shortcuts
            'ctrl+1': { action: 'goToStep1', description: 'Go to Quote Details', section: 'navigation' },
            'ctrl+2': { action: 'goToStep2', description: 'Go to Items', section: 'navigation' },
            'ctrl+3': { action: 'goToStep3', description: 'Go to Preview', section: 'navigation' },
            'tab': { action: 'nextField', description: 'Next field', section: 'navigation' },
            'shift+tab': { action: 'prevField', description: 'Previous field', section: 'navigation' },
            
            // Item management shortcuts
            'ctrl+shift+a': { action: 'addItem', description: 'Add new item', section: 'items' },
            'ctrl+shift+d': { action: 'deleteSelectedItems', description: 'Delete selected items', section: 'items' },
            'ctrl+shift+up': { action: 'moveItemUp', description: 'Move item up', section: 'items' },
            'ctrl+shift+down': { action: 'moveItemDown', description: 'Move item down', section: 'items' },
            'ctrl+shift+c': { action: 'copyItem', description: 'Copy selected item', section: 'items' },
            'ctrl+shift+v': { action: 'pasteItem', description: 'Paste item', section: 'items' },
            
            // Quick actions
            'ctrl+q': { action: 'quickMode', description: 'Toggle Quick Mode', section: 'quick' },
            'ctrl+t': { action: 'toggleTemplate', description: 'Show/hide templates', section: 'quick' },
            'ctrl+f': { action: 'findItem', description: 'Find item', section: 'quick' },
            'f3': { action: 'findNext', description: 'Find next', section: 'quick' },
            'shift+f3': { action: 'findPrev', description: 'Find previous', section: 'quick' },
            
            // Calculator shortcuts (when focused on number fields)
            'ctrl+equals': { action: 'calculate', description: 'Calculate field', section: 'calculator' },
            'ctrl+shift+equals': { action: 'calculateAll', description: 'Recalculate all', section: 'calculator' },
            
            // Help
            'ctrl+slash': { action: 'toggleHelp', description: 'Show/hide shortcuts help', section: 'help' },
            'f1': { action: 'showHelp', description: 'Show help', section: 'help' }
        },
        
        // State
        activeShortcuts: new Map(),
        pressedKeys: new Set(),
        lastAction: null,
        actionHistory: [],
        copiedItem: null,
        searchMode: false,
        searchQuery: '',

        // Initialize shortcuts
        init() {
            this.setupEventListeners();
            this.loadUserPreferences();
            this.registerShortcuts();
        },

        // Setup event listeners
        setupEventListeners() {
            document.addEventListener('keydown', this.handleKeyDown.bind(this));
            document.addEventListener('keyup', this.handleKeyUp.bind(this));
            
            // Prevent default browser shortcuts that we override
            document.addEventListener('keydown', (e) => {
                const combo = this.getKeyCombo(e);
                if (this.shortcuts[combo] && this.shortcuts[combo].global) {
                    e.preventDefault();
                }
            });
            
            // Focus management
            document.addEventListener('focusin', this.handleFocusIn.bind(this));
            document.addEventListener('focusout', this.handleFocusOut.bind(this));
        },

        // Handle key down events
        handleKeyDown(e) {
            if (!this.enabled) return;
            
            // Skip if typing in input fields (except for global shortcuts)
            if (this.isTypingInInput(e.target) && !this.isGlobalShortcut(e)) {
                return;
            }

            const combo = this.getKeyCombo(e);
            this.pressedKeys.add(combo);

            // Execute shortcut if found
            if (this.shortcuts[combo]) {
                e.preventDefault();
                this.executeShortcut(combo, e);
            }
        },

        // Handle key up events
        handleKeyUp(e) {
            const combo = this.getKeyCombo(e);
            this.pressedKeys.delete(combo);
        },

        // Get key combination string
        getKeyCombo(e) {
            const parts = [];
            
            if (e.ctrlKey || e.metaKey) parts.push('ctrl');
            if (e.altKey) parts.push('alt');
            if (e.shiftKey) parts.push('shift');
            
            // Handle special keys
            let key = e.key.toLowerCase();
            if (key === ' ') key = 'space';
            if (key === 'arrowup') key = 'up';
            if (key === 'arrowdown') key = 'down';
            if (key === 'arrowleft') key = 'left';
            if (key === 'arrowright') key = 'right';
            if (key === '/') key = 'slash';
            if (key === '=') key = 'equals';
            
            parts.push(key);
            
            return parts.join('+');
        },

        // Check if typing in input field
        isTypingInInput(element) {
            const inputTypes = ['input', 'textarea', 'select'];
            const isContentEditable = element.contentEditable === 'true';
            return inputTypes.includes(element.tagName.toLowerCase()) || isContentEditable;
        },

        // Check if global shortcut
        isGlobalShortcut(e) {
            const combo = this.getKeyCombo(e);
            return this.shortcuts[combo]?.global;
        },

        // Execute shortcut action
        async executeShortcut(combo, event) {
            const shortcut = this.shortcuts[combo];
            if (!shortcut) return;

            try {
                this.lastAction = combo;
                this.actionHistory.push({
                    action: shortcut.action,
                    timestamp: Date.now(),
                    combo
                });

                // Keep history limited
                if (this.actionHistory.length > 50) {
                    this.actionHistory.shift();
                }

                await this.performAction(shortcut.action, event);

                // Dispatch shortcut event
                this.$dispatch('shortcut-executed', {
                    action: shortcut.action,
                    combo,
                    description: shortcut.description
                });

            } catch (error) {
                console.error('Shortcut execution error:', error);
                this.showNotification(`Error executing ${shortcut.description}: ${error.message}`, 'error');
            }
        },

        // Perform the actual action
        async performAction(action, event) {
            switch (action) {
                // Global actions
                case 'save':
                    await this.saveQuote();
                    break;
                case 'saveAndContinue':
                    await this.saveQuote(true);
                    break;
                case 'preview':
                    this.previewPDF();
                    break;
                case 'print':
                    this.printQuote();
                    break;
                case 'duplicate':
                    this.duplicateQuote();
                    break;
                case 'email':
                    this.emailQuote();
                    break;
                case 'export':
                    this.exportQuote();
                    break;
                case 'cancel':
                    this.cancelCurrentAction();
                    break;
                case 'undo':
                    this.undoAction();
                    break;
                case 'redo':
                    this.redoAction();
                    break;

                // Navigation
                case 'goToStep1':
                    this.goToStep(1);
                    break;
                case 'goToStep2':
                    this.goToStep(2);
                    break;
                case 'goToStep3':
                    this.goToStep(3);
                    break;
                case 'nextField':
                    this.focusNextField();
                    break;
                case 'prevField':
                    this.focusPrevField();
                    break;

                // Item management
                case 'addItem':
                    this.addNewItem();
                    break;
                case 'deleteSelectedItems':
                    this.deleteSelectedItems();
                    break;
                case 'moveItemUp':
                    this.moveSelectedItemUp();
                    break;
                case 'moveItemDown':
                    this.moveSelectedItemDown();
                    break;
                case 'copyItem':
                    this.copySelectedItem();
                    break;
                case 'pasteItem':
                    this.pasteItem();
                    break;

                // Quick actions
                case 'quickMode':
                    this.toggleQuickMode();
                    break;
                case 'toggleTemplate':
                    this.toggleTemplatePanel();
                    break;
                case 'findItem':
                    this.enterSearchMode();
                    break;
                case 'findNext':
                    this.findNext();
                    break;
                case 'findPrev':
                    this.findPrevious();
                    break;

                // Calculator
                case 'calculate':
                    this.calculateCurrentField();
                    break;
                case 'calculateAll':
                    this.recalculateAll();
                    break;

                // Help
                case 'toggleHelp':
                    this.toggleHelp();
                    break;
                case 'showHelp':
                    this.showHelp = true;
                    break;

                default:
                    console.warn('Unknown shortcut action:', action);
            }
        },

        // Action implementations
        async saveQuote(continueEditing = false) {
            if (this.$store.quote.saving) return;
            
            try {
                await this.$store.quote.save();
                this.showNotification('Quote saved successfully', 'success');
                
                if (!continueEditing) {
                    // Could redirect or close editor
                }
            } catch (error) {
                this.showNotification('Failed to save quote', 'error');
            }
        },

        previewPDF() {
            this.$dispatch('generate-pdf', { preview: true });
        },

        printQuote() {
            window.print();
        },

        duplicateQuote() {
            if (confirm('Duplicate this quote?')) {
                this.$dispatch('duplicate-quote');
            }
        },

        emailQuote() {
            this.$dispatch('email-quote');
        },

        exportQuote() {
            this.$dispatch('export-quote');
        },

        cancelCurrentAction() {
            // Close modals, cancel edits, etc.
            this.$store.quote.ui.showModal = false;
            this.searchMode = false;
            this.showHelp = false;
        },

        undoAction() {
            // Implement undo functionality
            this.$store.quote.undo();
        },

        redoAction() {
            // Implement redo functionality
            this.$store.quote.redo();
        },

        goToStep(step) {
            this.$store.quote.ui.currentStep = step;
        },

        focusNextField() {
            const focusable = this.getFocusableElements();
            const current = document.activeElement;
            const currentIndex = focusable.indexOf(current);
            const nextIndex = (currentIndex + 1) % focusable.length;
            focusable[nextIndex]?.focus();
        },

        focusPrevField() {
            const focusable = this.getFocusableElements();
            const current = document.activeElement;
            const currentIndex = focusable.indexOf(current);
            const prevIndex = currentIndex === 0 ? focusable.length - 1 : currentIndex - 1;
            focusable[prevIndex]?.focus();
        },

        getFocusableElements() {
            return Array.from(document.querySelectorAll(
                'input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )).filter(el => el.offsetParent !== null);
        },

        addNewItem() {
            this.$store.quote.addItem({
                name: '',
                description: '',
                quantity: 1,
                unit_price: 0
            });
        },

        deleteSelectedItems() {
            const selected = this.getSelectedItems();
            if (selected.length > 0 && confirm(`Delete ${selected.length} item(s)?`)) {
                selected.forEach(item => {
                    this.$store.quote.removeItem(item.id);
                });
            }
        },

        moveSelectedItemUp() {
            const selected = this.getSelectedItems()[0];
            if (selected) {
                this.$store.quote.moveItemUp(selected.id);
            }
        },

        moveSelectedItemDown() {
            const selected = this.getSelectedItems()[0];
            if (selected) {
                this.$store.quote.moveItemDown(selected.id);
            }
        },

        copySelectedItem() {
            const selected = this.getSelectedItems()[0];
            if (selected) {
                this.copiedItem = { ...selected };
                this.showNotification('Item copied', 'info');
            }
        },

        pasteItem() {
            if (this.copiedItem) {
                const newItem = { ...this.copiedItem };
                delete newItem.id;
                this.$store.quote.addItem(newItem);
                this.showNotification('Item pasted', 'success');
            }
        },

        getSelectedItems() {
            // Implementation depends on your selection system
            return this.$store.quote.selectedItems.filter(item => item.selected);
        },

        toggleQuickMode() {
            this.$store.quote.ui.quickMode = !this.$store.quote.ui.quickMode;
        },

        toggleTemplatePanel() {
            this.$store.quote.ui.showTemplates = !this.$store.quote.ui.showTemplates;
        },

        enterSearchMode() {
            this.searchMode = true;
            this.searchQuery = '';
            
            // Create search input if not exists
            this.createSearchInput();
        },

        createSearchInput() {
            const existing = document.querySelector('#shortcut-search');
            if (existing) {
                existing.focus();
                return;
            }

            const input = document.createElement('input');
            input.id = 'shortcut-search';
            input.type = 'text';
            input.placeholder = 'Search items...';
            input.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 z-50 px-4 py-2 border border-gray-300 rounded-lg shadow-lg';
            
            input.addEventListener('input', (e) => {
                this.searchQuery = e.target.value;
                this.performSearch();
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.exitSearchMode();
                } else if (e.key === 'Enter') {
                    this.selectFirstResult();
                }
            });
            
            input.addEventListener('blur', () => {
                setTimeout(() => this.exitSearchMode(), 100);
            });

            document.body.appendChild(input);
            input.focus();
        },

        exitSearchMode() {
            this.searchMode = false;
            this.searchQuery = '';
            const input = document.querySelector('#shortcut-search');
            if (input) {
                input.remove();
            }
        },

        performSearch() {
            // Highlight matching items
            const items = document.querySelectorAll('[data-item-name]');
            items.forEach(item => {
                const name = item.dataset.itemName.toLowerCase();
                const matches = name.includes(this.searchQuery.toLowerCase());
                item.classList.toggle('highlighted', matches);
            });
        },

        findNext() {
            if (!this.searchQuery) return;
            // Implementation for finding next match
        },

        findPrevious() {
            if (!this.searchQuery) return;
            // Implementation for finding previous match
        },

        calculateCurrentField() {
            const focused = document.activeElement;
            if (focused && focused.type === 'number') {
                // Try to evaluate as expression
                try {
                    const result = eval(focused.value);
                    if (!isNaN(result)) {
                        focused.value = result;
                        focused.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                } catch (e) {
                    // Invalid expression
                }
            }
        },

        recalculateAll() {
            this.$store.quote.recalculate();
        },

        toggleHelp() {
            this.showHelp = !this.showHelp;
        },

        // Utility methods
        showNotification(message, type = 'info') {
            this.$dispatch('notification', { message, type });
        },

        loadUserPreferences() {
            try {
                const prefs = localStorage.getItem('keyboard-shortcuts-prefs');
                if (prefs) {
                    const parsed = JSON.parse(prefs);
                    this.enabled = parsed.enabled !== false;
                }
            } catch (e) {
                console.warn('Failed to load shortcut preferences:', e);
            }
        },

        saveUserPreferences() {
            try {
                localStorage.setItem('keyboard-shortcuts-prefs', JSON.stringify({
                    enabled: this.enabled
                }));
            } catch (e) {
                console.warn('Failed to save shortcut preferences:', e);
            }
        },

        registerShortcuts() {
            // Allow external registration of shortcuts
            document.addEventListener('register-shortcut', (e) => {
                const { combo, action, description, section } = e.detail;
                this.shortcuts[combo] = { action, description, section };
            });
        },

        // Public API
        addShortcut(combo, action, description, section = 'custom') {
            this.shortcuts[combo] = { action, description, section };
        },

        removeShortcut(combo) {
            delete this.shortcuts[combo];
        },

        getShortcutsBySection(section) {
            return Object.entries(this.shortcuts)
                .filter(([combo, shortcut]) => shortcut.section === section)
                .reduce((acc, [combo, shortcut]) => {
                    acc[combo] = shortcut;
                    return acc;
                }, {});
        },

        // Handle focus events
        handleFocusIn(e) {
            // Store focused element for context-sensitive shortcuts
            this.focusedElement = e.target;
        },

        handleFocusOut(e) {
            this.focusedElement = null;
        },

        // Computed properties
        get shortcutGroups() {
            const groups = {};
            Object.entries(this.shortcuts).forEach(([combo, shortcut]) => {
                const section = shortcut.section || 'general';
                if (!groups[section]) {
                    groups[section] = {};
                }
                groups[section][combo] = shortcut;
            });
            return groups;
        }
    }));

    // Global keyboard shortcuts store
    Alpine.store('keyboardShortcuts', {
        enabled: true,
        
        enable() {
            this.enabled = true;
        },
        
        disable() {
            this.enabled = false;
        },
        
        toggle() {
            this.enabled = !this.enabled;
        }
    });
});