<div class="p-6">
    <div class="space-y-6">
        <!-- Ticket Email Settings -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Ticket Email Integration</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <input type="checkbox" id="enable_email_tickets" name="enable_email_tickets" value="1" checked class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="enable_email_tickets" class="ml-3 block text-sm font-medium text-gray-700">Enable email-to-ticket conversion</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="email_notifications" name="email_notifications" value="1" checked class="h-4 w-4 text-blue-600-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="email_notifications" class="ml-3 block text-sm font-medium text-gray-700">Send email notifications for ticket updates</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="client_can_reply" name="client_can_reply" value="1" checked class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                    <label for="client_can_reply" class="ml-3 block text-sm font-medium text-gray-700">Allow clients to reply via email</label>
                </div>
            </div>
        </div>

        <!-- Email Processing Rules -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Email Processing Rules</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="default_priority" class="block text-sm font-medium text-gray-700 mb-1">Default Ticket Priority</label>
                    <select id="default_priority" name="default_priority" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label for="default_status" class="block text-sm font-medium text-gray-700 mb-1">Default Ticket Status</label>
                    <select id="default_status" name="default_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="open" selected>Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Subject Line Processing -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Subject Line Processing</h3>
            <div class="space-y-4">
                <div>
                    <label for="ticket_prefix" class="block text-sm font-medium text-gray-700 mb-1">Ticket ID Prefix</label>
                    <input type="text" id="ticket_prefix" name="ticket_prefix" value="[TICKET-" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="[TICKET-">
                    <p class="mt-1 text-sm text-gray-500">Prefix used in email subjects for ticket identification</p>
                </div>
                <div>
                    <label for="priority_keywords" class="block text-sm font-medium text-gray-700 mb-1">Priority Keywords</label>
                    <textarea id="priority_keywords" name="priority_keywords" rows="3" 
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                              placeholder="urgent, emergency, critical, down, outage">urgent, emergency, critical, down, outage</textarea>
                    <p class="mt-1 text-sm text-gray-500">Keywords that automatically set high priority (one per line or comma-separated)</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Ticket Email Settings
        </button>
    </div>
</div>