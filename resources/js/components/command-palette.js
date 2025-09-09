/**
 * Command Palette Component
 * Handles global search and command execution
 */

export function commandPalette() {
    return {
        isOpen: false,
        query: '',
        results: [],
        suggestions: [],
        selectedIndex: 0,
        loading: false,
        
        init() {
            // Load initial suggestions
            this.loadSuggestions();
        },
        
        open() {
            this.isOpen = true;
            this.query = '';
            this.selectedIndex = 0;
            this.loadSuggestions();
            
            // Use defensive programming with retry mechanism for focus
            this.focusInput();
        },
        
        focusInput() {
            // Simple, reliable focus with proper timing
            setTimeout(() => {
                const input = this.$refs.commandInput;
                if (input && this.isOpen) {
                    input.focus();
                }
            }, 350); // Wait for transition to complete (300ms + buffer)
        },
        
        close() {
            this.isOpen = false;
            this.query = '';
            this.results = [];
            this.selectedIndex = 0;
        },
        
        handleSlash(event) {
            // Check if we're in any kind of editable element
            const isInEditableElement = (
                event.target.tagName === 'INPUT' ||
                event.target.tagName === 'TEXTAREA' ||
                event.target.contentEditable === 'true' ||
                event.target.closest('[contenteditable="true"]') ||
                event.target.closest('input') ||
                event.target.closest('textarea') ||
                event.target.closest('[role="textbox"]') ||
                // Check if any ancestor has contenteditable
                event.target.closest('[contenteditable]')
            );
            
            // Only open command palette if not in an editable element
            if (!isInEditableElement) {
                event.preventDefault();
                this.open();
            }
        },
        
        async handleInput() {
            // Always load suggestions that match the current query
            await this.loadSuggestions();
            
            if (this.query.length === 0) {
                this.results = [];
                return;
            }
            
            // Perform search for data items (not commands)
            await this.search();
        },
        
        isCommand(query) {
            const commandPatterns = [
                /^(create|new|add)\s+/i,
                /^(go to|open|show)\s+/i,
                /^(find|search|lookup)\s+/i,
                /^(start|switch)\s+/i,
            ];
            
            return commandPatterns.some(pattern => pattern.test(query));
        },
        
        async processCommand() {
            this.loading = true;
            
            try {
                const response = await fetch('/api/navigation/command', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ command: this.query }),
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.action === 'navigate' && data.url) {
                    window.location.href = data.url;
                } else if (data.action === 'search') {
                    this.query = data.query;
                    await this.search();
                } else if (data.action === 'error') {
                    this.showError(data.message);
                }
            } catch (error) {
                console.error('Command error:', error);
                this.showError('Command execution failed');
            } finally {
                this.loading = false;
            }
        },
        
        async search() {
            this.loading = true;
            
            try {
                const params = new URLSearchParams({
                    query: this.query,
                    context: 'global',
                });
                
                const response = await fetch(`/api/search/query?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                this.results = data.results || [];
                this.selectedIndex = 0;
            } catch (error) {
                console.error('Search error:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },
        
        async loadSuggestions() {
            try {
                const response = await fetch('/api/navigation/suggestions?q=' + encodeURIComponent(this.query), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                this.suggestions = data;
            } catch (error) {
                console.error('Suggestions error:', error);
                // Fallback suggestions
                this.suggestions = [
                    { command: 'show urgent', icon: '🔥', description: 'View urgent items' },
                    { command: 'show today', icon: '📅', description: "Today's schedule" },
                    { command: 'create ticket', icon: '🎫', description: 'Create new ticket' },
                ];
            }
        },
        
        async executeCommand(command) {
            this.query = command;
            await this.processCommand();
        },
        
        navigateDown() {
            const total = this.suggestions.length + this.results.length;
            if (total > 0) {
                this.selectedIndex = Math.min(this.selectedIndex + 1, total - 1);
                this.scrollToSelected();
            }
        },
        
        navigateUp() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
                this.scrollToSelected();
            }
        },
        
        scrollToSelected() {
            this.$nextTick(() => {
                const selectedElement = this.$refs[`item-${this.selectedIndex}`];
                if (selectedElement && this.$refs.resultsContainer) {
                    selectedElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }
            });
        },
        
        selectItem() {
            if (this.selectedIndex < this.suggestions.length) {
                const suggestion = this.suggestions[this.selectedIndex];
                if (suggestion) {
                    this.executeCommand(suggestion.command);
                }
            } else {
                const resultIndex = this.selectedIndex - this.suggestions.length;
                const result = this.results[resultIndex];
                if (result && result.url) {
                    window.location.href = result.url;
                }
            }
        },
        
        handleResultClick(result, event) {
            // Track analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', 'command_palette_click', {
                    'result_type': result.type,
                    'query': this.query,
                });
            }
        },
        
        showError(message) {
            console.error(message);
            
            // Create a simple error notification
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 p-4 rounded-lg bg-red-500 text-white shadow-lg z-50 max-w-sm';
            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span class="text-sm font-medium">${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 3000);
        }
    };
}