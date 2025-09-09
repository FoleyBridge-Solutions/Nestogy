<div class="p-6">
    <div class="space-y-6">
        <!-- Ticket Automation -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Ticket Automation</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" id="auto_assign_tickets" name="auto_assign_tickets" value="1" class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="auto_assign_tickets" class="ml-3 block text-sm font-medium text-gray-700">Auto-assign tickets based on client</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="auto_escalate_tickets" name="auto_escalate_tickets" value="1" class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="auto_escalate_tickets" class="ml-3 block text-sm font-medium text-gray-700">Auto-escalate overdue tickets</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="auto_close_resolved" name="auto_close_resolved" value="1" class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="auto_close_resolved" class="ml-3 block text-sm font-medium text-gray-700">Auto-close resolved tickets after 48 hours</label>
                </div>
            </div>
        </div>

        <!-- Billing Automation -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Billing Automation</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" id="auto_generate_monthly" name="auto_generate_monthly" value="1" class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="auto_generate_monthly" class="ml-3 block text-sm font-medium text-gray-700">Auto-generate monthly recurring invoices</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="auto_send_payment_reminders" name="auto_send_payment_reminders" value="1" class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="auto_send_payment_reminders" class="ml-3 block text-sm font-medium text-gray-700">Auto-send payment reminders</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="auto_apply_late_fees" name="auto_apply_late_fees" value="1" class="h-4 w-4 text-blue-600 dark:text-blue-400-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="auto_apply_late_fees" class="ml-3 block text-sm font-medium text-gray-700">Auto-apply late fees to overdue invoices</label>
                </div>
            </div>
        </div>

        <!-- Workflow Rules -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Workflow Rules</h3>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li class="px-4 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">High Priority Ticket Alert</h4>
                                <p class="text-sm text-gray-500">Notify managers when high priority tickets are created</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                <button class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">Edit</button>
                            </div>
                        </div>
                    </li>
                    <li class="px-4 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">New Client Welcome</h4>
                                <p class="text-sm text-gray-500">Send welcome email and setup tasks for new clients</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                <button class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">Edit</button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="button" class="px-4 py-2 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700">
            Create Rule
        </button>
        <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Automation Settings
        </button>
    </div>
</div>
