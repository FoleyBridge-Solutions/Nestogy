@props([
    'templates' => [],
    'selectedTemplate' => null,
    'templateFilter' => []
])

<div>
    <div class="flex items-center justify-between mb-3">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Template</span>
        <div class="flex items-center space-x-2">
            <select x-model="templateFilter.category" @change="filterTemplates()" 
                    class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                <option value="">All Categories</option>
                <option value="msp">MSP</option>
                <option value="voip">VoIP</option>
                <option value="var">VAR</option>
                <option value="compliance">Compliance</option>
            </select>
            <select x-model="templateFilter.billingModel" @change="filterTemplates()"
                    class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                <option value="">All Models</option>
                <option value="fixed">Fixed</option>
                <option value="per_asset">Per Asset</option>
                <option value="per_contact">Per User</option>
                <option value="tiered">Tiered</option>
                <option value="hybrid">Hybrid</option>
            </select>
        </div>
    </div>
    
    <!-- Professional Template Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Custom Contract Option -->
        <div class="border-b border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
             :class="!selectedTemplate ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500' : ''"
             @click="selectTemplate(null)">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center"
                             :class="!selectedTemplate ? 'border-blue-500 bg-blue-500' : ''">
                            <div x-show="!selectedTemplate" class="w-2 h-2 bg-white rounded-full"></div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gray-600 rounded-lg text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">Custom Contract</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Start from scratch with complete customization</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                            Manual
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">—</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Options -->
        <template x-for="template in filteredTemplates" :key="template.id">
            <div class="border-b border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
                 :class="selectedTemplate && selectedTemplate.id === template.id ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500' : ''"
                 @click="selectTemplate(template)">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center"
                                 :class="selectedTemplate && selectedTemplate.id === template.id ? 'border-blue-500 bg-blue-500' : ''">
                                <div x-show="selectedTemplate && selectedTemplate.id === template.id" class="w-2 h-2 bg-white rounded-full"></div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="p-2 rounded-lg text-white"
                                     :class="getCategoryIconBg(template.category)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white truncate" x-text="template.name"></h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate" x-text="template.description"></p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
                                  :class="getBillingModelStyle(template.billing_model)"
                                  x-text="getBillingModelLabel(template.billing_model)"></span>
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
                                  x-text="template.category.charAt(0).toUpperCase() + template.category.slice(1)"></span>
                            <span x-show="template.usage_count > 0" class="text-sm text-gray-500 dark:text-gray-400" x-text="template.usage_count + ' uses'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Selected Template Details -->
    <div x-show="selectedTemplate" x-transition class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700 p-4">
        <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Template Details</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-400">Billing Model:</span>
                <span class="text-blue-900 dark:text-blue-300 font-medium" x-text="selectedTemplate ? getBillingModelLabel(selectedTemplate.billing_model) : ''"></span>
            </div>
            <div class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-400">Variables:</span>
                <span class="text-blue-900 dark:text-blue-300 font-medium" x-text="selectedTemplate && selectedTemplate.variable_fields && Array.isArray(selectedTemplate.variable_fields) ? selectedTemplate.variable_fields.length + ' fields' : '0 fields'"></span>
            </div>
            <div class="flex justify-between">
                <span class="text-blue-700 dark:text-blue-400">Category:</span>
                <span class="text-blue-900 dark:text-blue-300 font-medium" x-text="selectedTemplate ? selectedTemplate.category.charAt(0).toUpperCase() + selectedTemplate.category.slice(1) : ''"></span>
            </div>
        </div>
    </div>
</div>