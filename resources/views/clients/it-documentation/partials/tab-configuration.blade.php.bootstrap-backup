<!-- Tab Configuration Modal -->
<div x-show="showTabConfig" 
     x-cloak
     @keydown.escape.window="showTabConfig = false"
     class="fixed inset-0 z-50 overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div x-show="showTabConfig" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showTabConfig = false"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             aria-hidden="true"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div x-show="showTabConfig" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Configure Documentation Tabs
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Select which tabs to include in this documentation. The General Information tab is always required.
                        </p>

                        <!-- Quick Enable Buttons -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quick Actions</label>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="enabledTabs = ['general']; showTabConfig = false; activeTab = 'general'"
                                        class="px-3 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded-md">
                                    Minimal (General Only)
                                </button>
                                <button type="button" @click="enabledTabs = ['general', 'technical', 'procedures']; showTabConfig = false; activeTab = 'general'"
                                        class="px-3 py-1 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-md">
                                    Basic Documentation
                                </button>
                                <button type="button" @click="enabledTabs = ['general', 'technical', 'procedures', 'network', 'testing']; showTabConfig = false; activeTab = 'general'"
                                        class="px-3 py-1 text-xs bg-green-100 hover:bg-green-200 text-green-700 rounded-md">
                                    Standard Documentation
                                </button>
                                <button type="button" @click="enabledTabs = Object.keys(availableTabs); showTabConfig = false; activeTab = 'general'"
                                        class="px-3 py-1 text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-md">
                                    Enable All Tabs
                                </button>
                            </div>
                        </div>

                        <!-- Tab Selection Grid -->
                        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <template x-for="(tab, tabId) in availableTabs" :key="tabId">
                                <div class="relative flex items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" 
                                               :value="tabId"
                                               :checked="enabledTabs.includes(tabId)"
                                               @change="toggleTab(tabId)"
                                               :disabled="tab.required"
                                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                               :class="{'bg-gray-100': tab.required}">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label class="font-medium text-gray-700 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"/>
                                            </svg>
                                            <span x-text="tab.name"></span>
                                            <span x-show="tab.required" class="ml-2 text-xs text-gray-500">(Required)</span>
                                        </label>
                                        <p class="text-xs text-gray-500" x-text="tab.description"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Category-based Recommendations -->
                        <div class="mt-4 p-3 bg-blue-50 rounded-md" x-show="formData.it_category">
                            <p class="text-sm text-blue-800">
                                <strong>Recommended tabs for <span x-text="getCategoryName()"></span>:</strong>
                                <span x-text="getRecommendedTabs()"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        @click="applyTabConfiguration()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Apply Configuration
                </button>
                <button type="button" 
                        @click="showTabConfig = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>