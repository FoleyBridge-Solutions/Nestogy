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
         class="fixed inset-0 bg-gray-900/50 dark:bg-black/70 backdrop-blur-sm"
         style="display: none;">
        
        <!-- Command Palette Container -->
        <div @click.stop
             x-show="isOpen"
             x-trap.inert.noscroll="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 lg:scale-95 lg:-translate-y-4 translate-y-full"
             x-transition:enter-end="opacity-100 lg:scale-100 lg:translate-y-0 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 lg:scale-100 lg:translate-y-0 translate-y-0"
             x-transition:leave-end="opacity-0 lg:scale-95 lg:-translate-y-4 translate-y-full"
             class="relative mx-auto lg:mt-[10vh] lg:max-w-2xl 
                    max-lg:fixed max-lg:bottom-0 max-lg:left-0 max-lg:right-0 max-lg:w-full max-lg:max-h-[85vh]"
             x-data="{
                startY: 0,
                currentY: 0,
                handleTouchStart(e) {
                    this.startY = e.touches[0].clientY;
                },
                handleTouchMove(e) {
                    this.currentY = e.touches[0].clientY;
                    const deltaY = this.currentY - this.startY;
                    if (deltaY > 0) {
                        e.target.style.transform = `translateY(${deltaY}px)`;
                    }
                },
                handleTouchEnd(e) {
                    const deltaY = this.currentY - this.startY;
                    if (deltaY > 100) {
                        this.$parent.close();
                    } else {
                        e.target.style.transform = 'translateY(0)';
                    }
                }
             }"
             @touchstart="handleTouchStart($event)"
             @touchmove="handleTouchMove($event)" 
             @touchend="handleTouchEnd($event)">
            
            <div class="bg-white dark:bg-gray-800 lg:rounded-xl max-lg:rounded-t-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Mobile Pull Handle -->
                <div class="lg:hidden flex justify-center py-2 bg-gray-50 dark:bg-gray-700">
                    <div class="w-8 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                </div>
                <!-- Search Input -->
                <div class="relative border-b border-gray-200 dark:border-gray-700">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg x-show="!loading" class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                           class="w-full pl-12 pr-4 py-4 lg:py-4 max-lg:py-5 text-lg max-lg:text-base text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 border-0 focus:ring-0 focus:outline-none touch-manipulation bg-transparent"
                           placeholder="Type a command or search..."
                           autocomplete="off"
                           inputmode="text"
                           enterkeyhint="search">
                    
                    <!-- Help text -->
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <span class="text-xs text-gray-600 dark:text-gray-400 hidden lg:block">
                            <kbd class="px-1.5 py-0.5 text-xs font-medium bg-gray-800 dark:bg-gray-700 text-white dark:text-gray-200 border border-gray-600 dark:border-gray-600 rounded shadow-sm">ESC</kbd>
                            to close
                        </span>
                        <button @click="close()" class="lg:hidden p-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 pointer-events-auto touch-manipulation">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Results/Suggestions -->
                <div x-ref="resultsContainer" class="max-h-96 lg:max-h-96 max-lg:max-h-[50vh] overflow-y-auto">
                    <!-- Quick Actions/Commands -->
                    <div x-show="suggestions.length > 0" class="py-2">
                        <div class="px-4 py-2">
                            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <span x-text="query.length > 0 ? 'Matching Commands' : 'Quick Actions'"></span>
                            </h3>
                        </div>
                        <template x-for="(item, index) in suggestions" :key="'suggestion-' + index">
                            <button @click="executeCommand(item.command)"
                                    @mouseenter="selectedIndex = index"
                                    :x-ref="'item-' + index"
                                    :class="{
                                        'bg-indigo-50 dark:bg-indigo-900/30 border-l-2 border-indigo-500': selectedIndex === index,
                                        'hover:bg-gray-50 dark:hover:bg-gray-700': selectedIndex !== index
                                    }"
                                    class="w-full px-4 py-4 lg:py-3 flex items-center space-x-3 transition-colors duration-150 touch-manipulation min-h-[56px]">
                                <span class="text-2xl flex-shrink-0" x-text="item.icon"></span>
                                <div class="flex-1 text-left">
                                    <div class="text-base lg:text-sm font-medium text-gray-900 dark:text-gray-100" x-text="item.command"></div>
                                    <div class="text-sm lg:text-xs text-gray-500 dark:text-gray-400" x-text="item.description"></div>
                                </div>
                                <template x-if="item.shortcut">
                                    <kbd class="hidden lg:inline-block px-2 py-1 text-xs font-medium bg-gray-800 dark:bg-gray-700 text-white dark:text-gray-200 border border-gray-600 dark:border-gray-600 rounded shadow-sm" x-text="item.shortcut"></kbd>
                                </template>
                            </button>
                        </template>
                    </div>
                    
                    <!-- Search Results -->
                    <div x-show="results.length > 0" class="py-2">
                        <template x-if="query.length > 0">
                            <div class="px-4 py-2">
                                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Search Results</h3>
                            </div>
                        </template>
                        <template x-for="(result, index) in results" :key="'result-' + index">
                            <a :href="result.url"
                               @click="handleResultClick(result, $event)"
                               @mouseenter="selectedIndex = suggestions.length + index"
                               :x-ref="'item-' + (suggestions.length + index)"
                               :class="{
                                   'bg-indigo-50 dark:bg-indigo-900/30 border-l-2 border-indigo-500': selectedIndex === suggestions.length + index,
                                   'hover:bg-gray-50 dark:hover:bg-gray-700': selectedIndex !== suggestions.length + index
                               }"
                               class="w-full px-4 py-4 lg:py-3 flex items-center space-x-3 transition-colors duration-150 block touch-manipulation min-h-[56px]">
                                <span class="text-2xl flex-shrink-0" x-text="result.icon"></span>
                                <div class="flex-1 text-left">
                                    <div class="text-base lg:text-sm font-medium text-gray-900 dark:text-gray-100" x-text="result.title"></div>
                                    <div class="text-sm lg:text-xs text-gray-500 dark:text-gray-400" x-text="result.subtitle"></div>
                                </div>
                                <template x-if="result.meta">
                                    <div class="flex items-center space-x-2">
                                        <template x-if="result.meta.status">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                  :class="{
                                                      'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300': result.meta.priority === 'critical' || result.meta.status === 'overdue',
                                                      'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300': result.meta.priority === 'urgent',
                                                      'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300': result.meta.status === 'paid' || result.meta.status === 'active',
                                                      'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300': true
                                                  }"
                                                  x-text="result.meta.status"></span>
                                        </template>
                                        <template x-if="result.meta.amount">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="result.meta.amount"></span>
                                        </template>
                                    </div>
                                </template>
                            </a>
                        </template>
                    </div>
                    
                    <!-- No Results -->
                    <div x-show="query.length > 0 && results.length === 0 && suggestions.length === 0 && !loading" 
                         class="px-4 py-8 text-center">
                        <div class="text-gray-400 dark:text-gray-500 mb-2">
                            <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 20h.01M12 4h.01M20 12h.01M4 12h.01M20 20h.01M4 4h.01M4 20h.01M20 4h.01"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No results found for "<span x-text="query"></span>"</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Try a different search term or command</p>
                    </div>
                    
                    <!-- Loading State -->
                    <div x-show="loading" class="px-4 py-8 text-center">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Searching...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Footer with hints -->
                <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 bg-gray-50 dark:bg-gray-700">
                    <div class="flex items-center justify-between text-xs text-gray-700 dark:text-gray-300">
                        <div class="hidden lg:flex items-center space-x-4">
                            <span><kbd class="px-1 py-0.5 bg-gray-800 dark:bg-gray-600 text-white dark:text-gray-200 border border-gray-600 dark:border-gray-500 rounded text-xs shadow-sm">↑↓</kbd> Navigate</span>
                            <span><kbd class="px-1 py-0.5 bg-gray-800 dark:bg-gray-600 text-white dark:text-gray-200 border border-gray-600 dark:border-gray-500 rounded text-xs shadow-sm">Enter</kbd> Select</span>
                            <span><kbd class="px-1 py-0.5 bg-gray-800 dark:bg-gray-600 text-white dark:text-gray-200 border border-gray-600 dark:border-gray-500 rounded text-xs shadow-sm">Ctrl+/</kbd> Open</span>
                        </div>
                        <div class="lg:hidden flex items-center justify-center w-full">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Swipe down to close</span>
                        </div>
                        <div class="hidden lg:block">
                            Type <span class="font-medium text-gray-700 dark:text-gray-300">help</span> for commands
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
