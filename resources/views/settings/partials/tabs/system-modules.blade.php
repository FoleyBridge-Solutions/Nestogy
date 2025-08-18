<div class="p-6">
    <div class="space-y-6">
        <!-- Core Modules -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Core Modules</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Client Management</h4>
                            <p class="text-sm text-gray-500">Manage client accounts and contacts</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" checked disabled class="h-4 w-4 text-gray-400 rounded">
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Ticket System</h4>
                            <p class="text-sm text-gray-500">Support ticket management</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="modules[tickets]" value="1" checked class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Asset Management</h4>
                            <p class="text-sm text-gray-500">Track client assets and inventory</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="modules[assets]" value="1" checked class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Modules -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Financial Modules</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Invoicing</h4>
                            <p class="text-sm text-gray-500">Generate and send invoices</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="modules[invoicing]" value="1" checked class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Payment Processing</h4>
                            <p class="text-sm text-gray-500">Accept online payments</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="modules[payments]" value="1" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Recurring Billing</h4>
                            <p class="text-sm text-gray-500">Automated recurring charges</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="modules[recurring_billing]" value="1" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Optional Modules -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Optional Modules</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Project Management</h4>
                            <p class="text-sm text-gray-500">Manage client projects</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="modules[projects]" value="1" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Time Tracking</h4>
                            <p class="text-sm text-gray-500">Track billable hours</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="modules[time_tracking]" value="1" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Knowledge Base</h4>
                            <p class="text-sm text-gray-500">Self-service documentation</p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="modules[knowledge_base]" value="1" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Module Settings
        </button>
    </div>
</div>