<div class="p-6">
    <div class="space-y-6">
        <!-- Email Templates List -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Email Templates</h3>
                <button type="button" 
                        onclick="createTemplate()"
                        class="px-4 py-2 bg-blue-600-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-600-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Create Template
                </button>
            </div>

            <!-- Template Categories -->
            <div class="mb-6">
                <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
                    <button type="button" 
                            onclick="filterTemplates('all')"
                            class="template-filter-btn flex-1 py-2 px-3 text-sm font-medium text-gray-700 rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 active"
                            data-category="all">
                        All Templates
                    </button>
                    <button type="button" 
                            onclick="filterTemplates('tickets')"
                            class="template-filter-btn flex-1 py-2 px-3 text-sm font-medium text-gray-700 rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            data-category="tickets">
                        Tickets
                    </button>
                    <button type="button" 
                            onclick="filterTemplates('invoices')"
                            class="template-filter-btn flex-1 py-2 px-3 text-sm font-medium text-gray-700 rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            data-category="invoices">
                        Invoices
                    </button>
                    <button type="button" 
                            onclick="filterTemplates('system')"
                            class="template-filter-btn flex-1 py-2 px-3 text-sm font-medium text-gray-700 rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            data-category="system">
                        System
                    </button>
                </div>
            </div>

            <!-- Templates Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <!-- Ticket Templates -->
                    <li class="template-item" data-category="tickets">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Ticket
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Ticket Created</h4>
                                        <p class="text-sm text-gray-500">Sent when a new ticket is created</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <button onclick="editTemplate('ticket_created')" class="text-blue-600-600 hover:text-blue-600-900 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="previewTemplate('ticket_created')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                        Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="template-item" data-category="tickets">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Ticket
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Ticket Updated</h4>
                                        <p class="text-sm text-gray-500">Sent when a ticket status changes</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <button onclick="editTemplate('ticket_updated')" class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="previewTemplate('ticket_updated')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                        Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="template-item" data-category="tickets">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Ticket
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Ticket Resolved</h4>
                                        <p class="text-sm text-gray-500">Sent when a ticket is marked as resolved</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <button onclick="editTemplate('ticket_resolved')" class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="previewTemplate('ticket_resolved')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                        Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- Invoice Templates -->
                    <li class="template-item" data-category="invoices">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Invoice
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Invoice Created</h4>
                                        <p class="text-sm text-gray-500">Sent when a new invoice is generated</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <button onclick="editTemplate('invoice_created')" class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="previewTemplate('invoice_created')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                        Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="template-item" data-category="invoices">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Invoice
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Payment Reminder</h4>
                                        <p class="text-sm text-gray-500">Sent as payment reminder for overdue invoices</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <button onclick="editTemplate('payment_reminder')" class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="previewTemplate('payment_reminder')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                        Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- System Templates -->
                    <li class="template-item" data-category="system">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            System
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Welcome Email</h4>
                                        <p class="text-sm text-gray-500">Sent to new users when their account is created</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <button onclick="editTemplate('welcome_email')" class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="previewTemplate('welcome_email')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                        Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="template-item" data-category="system">
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            System
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Password Reset</h4>
                                        <p class="text-sm text-gray-500">Sent when a user requests a password reset</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                    <button onclick="editTemplate('password_reset')" class="text-blue-600 dark:text-blue-400-600 hover:text-blue-600 dark:text-blue-400-900 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="previewTemplate('password_reset')" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                        Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Template Variables Reference -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Available Template Variables</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Company Variables</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><code class="bg-gray-200 px-1 rounded">{{company.name}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{company.email}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{company.phone}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{company.address}}</code></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Client Variables</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><code class="bg-gray-200 px-1 rounded">{{client.name}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{client.email}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{client.contact_name}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{client.phone}}</code></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Ticket Variables</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><code class="bg-gray-200 px-1 rounded">{{ticket.number}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{ticket.subject}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{ticket.status}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{ticket.priority}}</code></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Invoice Variables</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><code class="bg-gray-200 px-1 rounded">{{invoice.number}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{invoice.total}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{invoice.due_date}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{invoice.status}}</code></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">User Variables</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><code class="bg-gray-200 px-1 rounded">{{user.name}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{user.email}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{user.first_name}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{user.last_name}}</code></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">System Variables</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><code class="bg-gray-200 px-1 rounded">{{app.name}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{app.url}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{current_date}}</code></li>
                            <li><code class="bg-gray-200 px-1 rounded">{{current_time}}</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="button" 
                onclick="exportTemplates()"
                class="px-4 py-2 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            Export Templates
        </button>
        <button type="button" 
                onclick="importTemplates()"
                class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            Import Templates
        </button>
    </div>
</div>

<script>
function filterTemplates(category) {
    // Update active button
    document.querySelectorAll('.template-filter-btn').forEach(btn => {
        btn.classList.remove('bg-white', 'shadow-sm', 'text-gray-900');
        btn.classList.add('text-gray-700');
    });
    
    const activeBtn = document.querySelector(`[data-category="${category}"]`);
    activeBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-900');
    activeBtn.classList.remove('text-gray-700');
    
    // Show/hide templates
    document.querySelectorAll('.template-item').forEach(item => {
        if (category === 'all' || item.getAttribute('data-category') === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function createTemplate() {
    console.log('Creating new template...');
    alert('Template creation dialog would be implemented here');
}

function editTemplate(templateKey) {
    console.log(`Editing template: ${templateKey}`);
    alert(`Template editor for ${templateKey} would be implemented here`);
}

function previewTemplate(templateKey) {
    console.log(`Previewing template: ${templateKey}`);
    alert(`Template preview for ${templateKey} would be implemented here`);
}

function exportTemplates() {
    console.log('Exporting templates...');
    alert('Template export functionality would be implemented here');
}

function importTemplates() {
    console.log('Importing templates...');
    alert('Template import functionality would be implemented here');
}
</script>
