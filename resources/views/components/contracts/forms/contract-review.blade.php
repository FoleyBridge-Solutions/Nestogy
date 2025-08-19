<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Contract Review & Finalization</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Review all contract details before creation</p>
    </div>

    <div class="space-y-6">
        <!-- Contract Details Summary -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contract Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Title:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.title || 'Not specified'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Type:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.contract_type || 'Not specified'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Start Date:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.start_date || 'Not specified'"></span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Currency:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.currency_code || 'USD'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Payment Terms:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="form.payment_terms || 'Net 30 days'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Template:</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedTemplate?.name || 'Custom'"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Summary -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
            <h4 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-4">Contract Schedules Summary</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">A</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Infrastructure & SLA</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Coverage rules configured</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">B</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Pricing & Fees</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Billing model configured</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">C</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Additional Terms</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Custom terms defined</div>
                </div>
            </div>
        </div>

        <!-- Asset Assignment Summary -->
        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Asset Assignment Summary</h4>
            <div class="text-center py-6">
                <div class="text-3xl font-bold text-gray-600 dark:text-gray-400">0</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Assets will be assigned automatically</div>
                <div class="mt-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400">
                        Auto-assignment enabled
                    </span>
                </div>
            </div>
        </div>

        <!-- Contract Actions -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Contract Creation Options</h4>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Contract Status</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Initial status for the contract</div>
                    </div>
                    <select class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm dark:bg-gray-700 dark:text-white">
                        <option value="draft">Draft</option>
                        <option value="pending_review">Pending Review</option>
                        <option value="pending_signature">Pending Signature</option>
                    </select>
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Generate PDF</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Create PDF version of the contract</div>
                    </div>
                    <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">Send for Signature</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Send contract to client for digital signature</div>
                    </div>
                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                </div>
            </div>
        </div>
    </div>
</div>