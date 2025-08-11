<!-- Command Palette Component -->
<div x-data="commandPalette()" 
     x-init="init()"
     @keydown.window.prevent.ctrl.slash="open()"
     @keydown.window.prevent.slash="handleSlash($event)"
     @open-command-palette.window="open()"
     class="relative z-50">
    
    <!-- Command Palette Modal -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
         style="display: none;">
        
        <!-- Command Palette Container -->
        <div @click.stop
             x-show="isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative mx-auto mt-[10vh] max-w-2xl">
            
            <div class="bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden">
                <!-- Search Input -->
                <div class="relative border-b border-gray-200">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg x-show="!loading" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <svg x-show="loading" class="h-5 w-5 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    
                    <input type="text"
                           x-ref="commandInput"
                           x-model="query"
                           @input.debounce.150ms="handleInput()"
                           @keydown.down.prevent="navigateDown()"
                           @keydown.up.prevent="navigateUp()"
                           @keydown.enter.prevent="selectItem()"
                           @keydown.escape="close()"
                           class="w-full pl-12 pr-4 py-4 text-lg text-gray-900 placeholder-gray-400 border-0 focus:ring-0 focus:outline-none"
                           placeholder="Type a command or search..."
                           autocomplete="off">
                    
                    <!-- Help text -->
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <span class="text-xs text-gray-400">
                            <kbd class="px-1.5 py-0.5 text-xs font-medium bg-gray-100 border border-gray-200 rounded">ESC</kbd>
                            to close
                        </span>
                    </div>
                </div>
                
                <!-- Results/Suggestions -->
                <div class="max-h-96 overflow-y-auto">
                    <!-- Quick Actions/Commands -->
                    <div x-show="suggestions.length > 0" class="py-2">
                        <div class="px-4 py-2">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <span x-text="query.length > 0 ? 'Matching Commands' : 'Quick Actions'"></span>
                            </h3>
                        </div>
                        <template x-for="(item, index) in suggestions" :key="'suggestion-' + index">
                            <button @click="executeCommand(item.command)"
                                    @mouseenter="selectedIndex = index"
                                    :class="{
                                        'bg-indigo-50 border-l-2 border-indigo-500': selectedIndex === index,
                                        'hover:bg-gray-50': selectedIndex !== index
                                    }"
                                    class="w-full px-4 py-3 flex items-center space-x-3 transition-colors duration-150">
                                <span class="text-2xl flex-shrink-0" x-text="item.icon"></span>
                                <div class="flex-1 text-left">
                                    <div class="text-sm font-medium text-gray-900" x-text="item.command"></div>
                                    <div class="text-xs text-gray-500" x-text="item.description"></div>
                                </div>
                                <template x-if="item.shortcut">
                                    <kbd class="px-2 py-1 text-xs font-medium bg-gray-100 border border-gray-200 rounded" x-text="item.shortcut"></kbd>
                                </template>
                            </button>
                        </template>
                    </div>
                    
                    <!-- Search Results -->
                    <div x-show="results.length > 0" class="py-2">
                        <template x-if="query.length > 0">
                            <div class="px-4 py-2">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Search Results</h3>
                            </div>
                        </template>
                        <template x-for="(result, index) in results" :key="'result-' + index">
                            <a :href="result.url"
                               @click="handleResultClick(result, $event)"
                               @mouseenter="selectedIndex = suggestions.length + index"
                               :class="{
                                   'bg-indigo-50 border-l-2 border-indigo-500': selectedIndex === suggestions.length + index,
                                   'hover:bg-gray-50': selectedIndex !== suggestions.length + index
                               }"
                               class="w-full px-4 py-3 flex items-center space-x-3 transition-colors duration-150 block">
                                <span class="text-2xl flex-shrink-0" x-text="result.icon"></span>
                                <div class="flex-1 text-left">
                                    <div class="text-sm font-medium text-gray-900" x-text="result.title"></div>
                                    <div class="text-xs text-gray-500" x-text="result.subtitle"></div>
                                </div>
                                <template x-if="result.meta">
                                    <div class="flex items-center space-x-2">
                                        <template x-if="result.meta.status">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                  :class="{
                                                      'bg-red-100 text-red-800': result.meta.priority === 'critical' || result.meta.status === 'overdue',
                                                      'bg-orange-100 text-orange-800': result.meta.priority === 'urgent',
                                                      'bg-green-100 text-green-800': result.meta.status === 'paid' || result.meta.status === 'active',
                                                      'bg-gray-100 text-gray-800': true
                                                  }"
                                                  x-text="result.meta.status"></span>
                                        </template>
                                        <template x-if="result.meta.amount">
                                            <span class="text-sm font-medium text-gray-900" x-text="result.meta.amount"></span>
                                        </template>
                                    </div>
                                </template>
                            </a>
                        </template>
                    </div>
                    
                    <!-- No Results -->
                    <div x-show="query.length > 0 && results.length === 0 && suggestions.length === 0 && !loading" 
                         class="px-4 py-8 text-center">
                        <div class="text-gray-400 mb-2">
                            <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 20h.01M12 4h.01M20 12h.01M4 12h.01M20 20h.01M4 4h.01M4 20h.01M20 4h.01"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">No results found for "<span x-text="query"></span>"</p>
                        <p class="text-xs text-gray-400 mt-1">Try a different search term or command</p>
                    </div>
                    
                    <!-- Loading State -->
                    <div x-show="loading" class="px-4 py-8 text-center">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-500">Searching...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Footer with hints -->
                <div class="border-t border-gray-200 px-4 py-3 bg-gray-50">
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <div class="flex items-center space-x-4">
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-xs">↑↓</kbd> Navigate</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-xs">Enter</kbd> Select</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-xs">Ctrl+/</kbd> Open</span>
                        </div>
                        <div>
                            Type <span class="font-medium text-gray-700">help</span> for commands
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function commandPalette() {
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
            this.$nextTick(() => {
                this.$refs.commandInput.focus();
            });
        },
        
        close() {
            this.isOpen = false;
            this.query = '';
            this.results = [];
            this.selectedIndex = 0;
        },
        
        handleSlash(event) {
            // Only open if not in an input field
            if (event.target.tagName !== 'INPUT' && event.target.tagName !== 'TEXTAREA') {
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
                
                const data = await response.json();
                this.suggestions = data;
            } catch (error) {
                console.error('Suggestions error:', error);
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
            }
        },
        
        navigateUp() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            }
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
            // Track analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'command_palette_click', {
                    'result_type': result.type,
                    'query': this.query,
                });
            }
        },
        
        showError(message) {
            // You could show a toast notification here
            console.error(message);
        }
    };
}
</script>