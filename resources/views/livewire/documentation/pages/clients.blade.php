{{-- Client Management Guide --}}

<div class="prose prose-zinc dark:prose-invert max-w-none">
    <h2>Managing Your Clients</h2>

    <p>
        Client management is at the heart of Nestogy. Learn how to add clients, manage contacts, track locations, 
        and maintain detailed communication logs for each customer.
    </p>
</div>

<flux:separator class="my-8" />

<div class="space-y-12">
    {{-- Adding a New Client --}}
    <div>
        <flux:heading size="lg" class="mb-4">Adding a New Client</flux:heading>
        
        <flux:text class="mb-4">
            To create a new client in Nestogy:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li>Click <strong>Clients</strong> in the sidebar navigation</li>
                <li>Click the <strong>Create Client</strong> button in the top right</li>
                <li>Fill in the client information form</li>
                <li>Click <strong>Save</strong> to create the client</li>
            </ol>
        </div>

        <flux:callout icon="information-circle" color="blue" class="mt-6">
            <flux:callout.heading>Required Fields</flux:callout.heading>
            <flux:callout.text>
                Only the company name is required to create a client. However, adding contact information, 
                billing address, and primary contact will help you manage the relationship more effectively.
            </flux:callout.text>
        </flux:callout>
    </div>

    {{-- Client Information --}}
    <div>
        <flux:heading size="lg" class="mb-4">Client Information</flux:heading>
        
        <flux:text class="mb-4">
            Each client record contains several sections:
        </flux:text>

        <div class="space-y-6">
            <div>
                <flux:heading size="base" class="mb-2">Basic Information</flux:heading>
                <div class="prose prose-zinc dark:prose-invert max-w-none">
                    <ul>
                        <li>Company name</li>
                        <li>Industry type</li>
                        <li>Website URL</li>
                        <li>Phone and fax numbers</li>
                        <li>Tax ID or VAT number</li>
                    </ul>
                </div>
            </div>

            <div>
                <flux:heading size="base" class="mb-2">Addresses</flux:heading>
                <flux:text>
                    You can maintain multiple addresses for each client including billing address, 
                    shipping address, and physical office locations.
                </flux:text>
            </div>

            <div>
                <flux:heading size="base" class="mb-2">Custom Fields</flux:heading>
                <flux:text>
                    Add custom fields specific to your business needs through Settings → Custom Fields.
                </flux:text>
            </div>
        </div>
    </div>

    {{-- Managing Contacts --}}
    <div>
        <flux:heading size="lg" class="mb-4">Managing Contacts</flux:heading>
        
        <flux:text class="mb-4">
            Each client can have multiple contacts. To add a contact:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li>Navigate to the client's detail page</li>
                <li>Click the <strong>Contacts</strong> tab</li>
                <li>Click <strong>Add Contact</strong></li>
                <li>Enter contact information (name, email, phone, role)</li>
                <li>Set a primary contact if desired</li>
            </ol>
        </div>

        <flux:callout icon="user-group" color="purple" class="mt-6">
            <flux:callout.heading>Primary Contact</flux:callout.heading>
            <flux:callout.text>
                The primary contact receives all automated emails (invoices, ticket updates, etc.) by default. 
                You can always override this on a per-email basis.
            </flux:callout.text>
        </flux:callout>
    </div>

    {{-- Client Portal Access --}}
    <div>
        <flux:heading size="lg" class="mb-4">Client Portal Access</flux:heading>
        
        <flux:text class="mb-4">
            Give your clients self-service access through the client portal:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li>Navigate to the client's detail page</li>
                <li>Click the <strong>Portal Access</strong> tab</li>
                <li>Click <strong>Invite to Portal</strong> for each contact</li>
                <li>The contact will receive an email invitation</li>
                <li>They can set their password and access the portal</li>
            </ol>
        </div>

        <flux:text class="mt-4">
            Learn more in the <a href="{{ route('docs.show', 'client-portal') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Client Portal</a> documentation.
        </flux:text>
    </div>

    {{-- Communication Logs --}}
    <div>
        <flux:heading size="lg" class="mb-4">Communication Logs</flux:heading>
        
        <flux:text class="mb-4">
            Keep track of all interactions with your clients:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>Phone Calls</strong> - Log phone conversations with notes and duration</li>
                <li><strong>Emails</strong> - Automatically logged from the email system</li>
                <li><strong>Meetings</strong> - Record in-person or video meetings</li>
                <li><strong>Notes</strong> - Add general notes and observations</li>
            </ul>
        </div>

        <flux:text class="mt-4">
            Access communication logs from the client's <strong>Activity</strong> tab.
        </flux:text>
    </div>

    {{-- Client Status --}}
    <div>
        <flux:heading size="lg" class="mb-4">Client Status</flux:heading>
        
        <flux:text class="mb-4">
            Clients can have different statuses to organize your pipeline:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card>
                <flux:heading size="base" class="mb-2">Active</flux:heading>
                <flux:text variant="subtle">
                    Current paying customers receiving services
                </flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="base" class="mb-2">Lead</flux:heading>
                <flux:text variant="subtle">
                    Potential customers in the sales pipeline
                </flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="base" class="mb-2">Trial</flux:heading>
                <flux:text variant="subtle">
                    Customers in a trial or evaluation period
                </flux:text>
            </flux:card>

            <flux:card>
                <flux:heading size="base" class="mb-2">Inactive</flux:heading>
                <flux:text variant="subtle">
                    Former customers who are no longer active
                </flux:text>
            </flux:card>
        </div>
    </div>

    {{-- Client Dashboard --}}
    <div>
        <flux:heading size="lg" class="mb-4">Client Dashboard</flux:heading>
        
        <flux:text class="mb-4">
            Each client has a dedicated dashboard showing:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>Open Tickets</strong> - Active support requests</li>
                <li><strong>Recent Activity</strong> - Timeline of recent events</li>
                <li><strong>Invoices</strong> - Outstanding and paid invoices</li>
                <li><strong>Contracts</strong> - Active service agreements</li>
                <li><strong>Assets</strong> - Managed equipment and devices</li>
                <li><strong>Financial Summary</strong> - Revenue, outstanding balance, payment history</li>
            </ul>
        </div>
    </div>

    {{-- Bulk Actions --}}
    <div>
        <flux:heading size="lg" class="mb-4">Bulk Actions</flux:heading>
        
        <flux:text class="mb-4">
            Manage multiple clients at once:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li>Select clients using checkboxes in the client list</li>
                <li>Click the <strong>Actions</strong> dropdown</li>
                <li>Choose from: Export, Change Status, Send Email, Add Tag, Delete</li>
            </ul>
        </div>

        <flux:callout icon="exclamation-triangle" color="amber" variant="warning" class="mt-6">
            <flux:callout.heading>Warning: Bulk Delete</flux:callout.heading>
            <flux:callout.text>
                Deleting clients is permanent and will remove all associated tickets, invoices, and time entries. 
                Consider marking them as "Inactive" instead.
            </flux:callout.text>
        </flux:callout>
    </div>
</div>

<flux:separator class="my-8" />

{{-- Next Steps --}}
<div>
    <flux:heading size="lg" class="mb-4">Next Steps</flux:heading>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('docs.show', 'tickets') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="ticket" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Ticket System</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Create and manage support tickets
                    </flux:text>
                </div>
            </div>
        </a>

        <a href="{{ route('docs.show', 'contracts') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="document-text" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Contract Management</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Set up service agreements
                    </flux:text>
                </div>
            </div>
        </a>
    </div>
</div>
