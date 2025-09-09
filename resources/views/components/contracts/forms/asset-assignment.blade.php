<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Asset Assignment & Coverage</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Configure automatic asset assignment and coverage rules</p>
    </div>

    <div class="space-y-8">
        <!-- Asset Assignment Rules -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Automatic Asset Assignment</h4>
            
            <div class="space-y-6">
                <!-- Auto Assignment Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">Assignment Options</h5>
                        <div class="space-y-3">
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="slaTerms.auto_assign_assets" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Auto-assign existing client assets</span>
                            </label>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="slaTerms.auto_assign_new_assets" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Auto-assign new assets added to client</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-6">Approval Requirements</h5>
                        <div class="space-y-3">
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="slaTerms.require_manual_approval" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Require manual approval for assignments</span>
                            </label>
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" x-model="slaTerms.notify_on_assignment" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">Notify on asset assignments</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset Preview -->
        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Asset Assignment Preview</h4>
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Asset preview will appear here</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Once schedules are configured, matching assets will be shown for review
                </p>
            </div>
        </div>
    </div>
</div>
