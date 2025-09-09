{{-- Hardware Schedule - For VAR and hardware procurement contract templates --}}
<div class="space-y-8">
    <!-- Schedule Header -->
    <div class="border-b border-gray-200 dark:border-gray-600 pb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            Schedule A - Hardware Products & Services
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Configure hardware procurement, installation services, warranty terms, and pricing models.
        </p>
    </div>

    <!-- Product Categories -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
        <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Product Categories & Specifications
        </h4>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Hardware Categories -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">
                    Product Categories
                </label>
                <div class="space-y-3">
                    <template x-for="category in hardwareCategories" :key="category.value">
                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-6">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" :value="category.value" 
                                           x-model="hardwareSchedule.selectedCategories" 
                                           class="text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="category.label"></div>
                                        <div class="text-xs text-gray-500" x-text="category.description"></div>
                                    </div>
                                </div>
                                <div class="text-blue-600" x-html="category.icon"></div>
                            </label>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Procurement Model -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Procurement Model
                </label>
                <select x-model="hardwareSchedule.procurementModel" 
                        class="w-full px-6 py-6 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select procurement model...</option>
                    <option value="direct_resale">Direct Resale</option>
                    <option value="drop_ship">Drop Ship</option>
                    <option value="stock_and_ship">Stock and Ship</option>
                    <option value="configure_to_order">Configure to Order</option>
                    <option value="build_to_order">Build to Order</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">How hardware will be sourced and delivered</p>

                <!-- Lead Times -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Standard Lead Time
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="number" x-model="hardwareSchedule.leadTimeDays" min="0" 
                               placeholder="Days"
                               class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        <select x-model="hardwareSchedule.leadTimeType" 
                                class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                            <option value="business_days">Business Days</option>
                            <option value="calendar_days">Calendar Days</option>
                            <option value="weeks">Weeks</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Installation & Configuration Services -->
    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
        <h4 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Installation & Configuration Services
        </h4>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Installation Services -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">
                    Installation Services
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.basicInstallation" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Basic Installation</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.rackAndStack" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Rack & Stack</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.cabling" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Network Cabling</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.powerConfiguration" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Power Configuration</span>
                    </label>
                </div>
            </div>

            <!-- Configuration Services -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">
                    Configuration Services
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.basicConfiguration" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Basic Configuration</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.advancedConfiguration" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Advanced Configuration</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.customConfiguration" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Custom Configuration</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.testing" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">System Testing</span>
                    </label>
                </div>
            </div>

            <!-- Professional Services -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">
                    Professional Services
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.projectManagement" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Project Management</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.training" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">User Training</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.documentation" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Documentation</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="hardwareSchedule.services.migration" 
                               class="text-green-600 focus:ring-green-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Data Migration</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Service Level Agreement -->
        <div class="mt-6 p-6 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
            <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-6">Installation SLA</h5>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Installation Timeline
                    </label>
                    <input type="text" x-model="hardwareSchedule.sla.installationTimeline" 
                           placeholder="e.g., Within 5 business days"
                           class="w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Configuration Complete
                    </label>
                    <input type="text" x-model="hardwareSchedule.sla.configurationTimeline" 
                           placeholder="e.g., Within 2 business days"
                           class="w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Support Response
                    </label>
                    <select x-model="hardwareSchedule.sla.supportResponse" 
                            class="w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                        <option value="2_hours">2 Hours</option>
                        <option value="4_hours">4 Hours</option>
                        <option value="8_hours">8 Hours</option>
                        <option value="24_hours">24 Hours</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Warranty & Support -->
    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
        <h4 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Warranty & Support Terms
        </h4>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Standard Warranty -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">
                    Standard Warranty Coverage
                </label>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Hardware Warranty Period</label>
                        <select x-model="hardwareSchedule.warranty.hardwarePeriod" 
                                class="w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500">
                            <option value="1_year">1 Year</option>
                            <option value="2_years">2 Years</option>
                            <option value="3_years">3 Years</option>
                            <option value="5_years">5 Years</option>
                            <option value="lifetime">Lifetime Limited</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Support Period</label>
                        <select x-model="hardwareSchedule.warranty.supportPeriod" 
                                class="w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500">
                            <option value="90_days">90 Days</option>
                            <option value="1_year">1 Year</option>
                            <option value="2_years">2 Years</option>
                            <option value="3_years">3 Years</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="hardwareSchedule.warranty.onSiteSupport" 
                                   class="text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">On-Site Support Available</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" x-model="hardwareSchedule.warranty.advancedReplacement" 
                                   class="text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Advanced Replacement</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Extended Warranty Options -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">
                    Extended Warranty Options
                </label>
                <div class="space-y-4">
                    <template x-for="option in $data.extendedWarrantyOptions || []" :key="option.value">
                        <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-6">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" :value="option.value" 
                                           x-model="hardwareSchedule.warranty.extendedOptions" 
                                           class="text-purple-600 focus:ring-purple-500">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="option.label"></div>
                                        <div class="text-xs text-gray-500" x-text="option.description"></div>
                                    </div>
                                </div>
                                <div class="text-sm font-medium text-purple-600" x-text="option.markup"></div>
                            </label>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing & Markup -->
    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-6 border border-orange-200 dark:border-orange-800">
        <h4 class="text-lg font-semibold text-orange-900 dark:text-orange-100 mb-6 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
            </svg>
            Pricing Model & Markup Structure
        </h4>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Markup Model -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">
                    Markup Model
                </label>
                <select x-model="hardwareSchedule.pricing.markupModel" 
                        class="w-full px-6 py-6 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500 mb-6">
                    <option value="">Select markup model...</option>
                    <option value="fixed_percentage">Fixed Percentage</option>
                    <option value="tiered_percentage">Tiered Percentage</option>
                    <option value="fixed_dollar">Fixed Dollar Amount</option>
                    <option value="value_based">Value-Based Pricing</option>
                </select>

                <!-- Markup Percentages by Category -->
                <div x-show="hardwareSchedule.pricing.markupModel === 'fixed_percentage'" class="space-y-3">
                    <h5 class="font-medium text-gray-900 dark:text-gray-100">Category Markup Percentages</h5>
                    <template x-for="category in hardwareCategories" :key="category.value">
                        <div x-show="hardwareSchedule.selectedCategories.includes(category.value)" 
                             class="flex items-center justify-between bg-white dark:bg-gray-700 p-6 rounded-lg border border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="category.label"></span>
                            <div class="flex items-center space-x-2">
                                <input type="number" 
                                       x-model="hardwareSchedule.pricing.categoryMarkup[category.value]"
                                       min="0" max="100" step="0.1"
                                       class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-orange-500">
                                <span class="text-sm text-gray-500">%</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Volume Discounts -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Volume Discount Tiers
                    </label>
                    <div class="space-y-2">
                        <template x-for="(tier, index) in hardwareSchedule.pricing.volumeTiers" :key="index">
                            <div class="flex items-center space-x-3 bg-white dark:bg-gray-700 p-6 rounded-lg border border-gray-200 dark:border-gray-600">
                                <input type="number" x-model="tier.minimumAmount" placeholder="Min $" 
                                       class="w-24 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                                <span class="text-gray-500">→</span>
                                <input type="number" x-model="tier.discountPercent" placeholder="%" step="0.1" 
                                       class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                                <span class="text-sm text-gray-500">% discount</span>
                                <button type="button" @click="hardwareSchedule.pricing.volumeTiers.splice(index, 1)" 
                                        class="text-red-600 hover:text-red-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="hardwareSchedule.pricing.volumeTiers.push({minimumAmount: '', discountPercent: ''})" 
                                class="text-sm text-orange-600 hover:text-orange-800">
                            + Add Volume Tier
                        </button>
                    </div>
                </div>
            </div>

            <!-- Service Pricing -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">
                    Service Pricing
                </label>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Installation Rate (per hour)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                            <input type="number" x-model="hardwareSchedule.pricing.installationRate" step="0.01" 
                                   class="w-full pl-7 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Configuration Rate (per hour)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                            <input type="number" x-model="hardwareSchedule.pricing.configurationRate" step="0.01" 
                                   class="w-full pl-7 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Project Management Rate (per hour)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                            <input type="number" x-model="hardwareSchedule.pricing.projectManagementRate" step="0.01" 
                                   class="w-full pl-7 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Travel Rate (per mile)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                            <input type="number" x-model="hardwareSchedule.pricing.travelRate" step="0.01" 
                                   class="w-full pl-7 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Terms -->
        <div class="mt-6 p-6 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
            <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-6">Payment Terms</h5>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Hardware Payment</label>
                    <select x-model="hardwareSchedule.pricing.hardwarePaymentTerms" 
                            class="w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500">
                        <option value="net_30">Net 30</option>
                        <option value="due_on_receipt">Due on Receipt</option>
                        <option value="50_50_split">50% Down, 50% on Delivery</option>
                        <option value="progress_billing">Progress Billing</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Service Payment</label>
                    <select x-model="hardwareSchedule.pricing.servicePaymentTerms" 
                            class="w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500">
                        <option value="net_30">Net 30</option>
                        <option value="due_on_completion">Due on Completion</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div>
                    <label class="flex items-center pt-6">
                        <input type="checkbox" x-model="hardwareSchedule.pricing.taxExempt" 
                               class="text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Tax Exempt Customer</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
