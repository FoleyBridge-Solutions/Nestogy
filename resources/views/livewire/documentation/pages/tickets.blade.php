{{-- Ticket System Guide --}}

<div class="prose prose-zinc dark:prose-invert max-w-none">
    <h2>Managing Support Tickets</h2>

    <p>
        The ticket system is the core of your support workflow in Nestogy. Learn how to create tickets, 
        assign them to technicians, track time, manage SLAs, and provide excellent customer support.
    </p>
</div>

<flux:separator class="my-8" />

<div class="space-y-12">
    {{-- Creating a Ticket --}}
    <div>
        <flux:heading size="lg" class="mb-4">Creating a Ticket</flux:heading>
        
        <flux:text class="mb-4">
            To create a new support ticket:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li>Select a client using the client switcher</li>
                <li>Click <strong>New Ticket</strong> in quick actions or sidebar</li>
                <li>Enter ticket details:
                    <ul>
                        <li><strong>Title</strong> - Brief summary of the issue</li>
                        <li><strong>Description</strong> - Detailed problem description</li>
                        <li><strong>Priority</strong> - Low, Medium, High, or Critical</li>
                        <li><strong>Category</strong> - Issue type (Hardware, Software, Network, etc.)</li>
                        <li><strong>Assigned To</strong> - Select a technician</li>
                    </ul>
                </li>
                <li>Click <strong>Create Ticket</strong></li>
            </ol>
        </div>

        <flux:callout icon="bolt" color="amber" class="mt-6">
            <flux:callout.heading>Email to Ticket</flux:callout.heading>
            <flux:callout.text>
                Tickets can also be created automatically from emails sent to your support address. 
                Configure this in Settings → Email Integration.
            </flux:callout.text>
        </flux:callout>
    </div>

    {{-- Ticket Priorities --}}
    <div>
        <flux:heading size="lg" class="mb-4">Ticket Priorities</flux:heading>
        
        <flux:text class="mb-4">
            Priorities help you triage and organize your support queue:
        </flux:text>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:card class="border-l-4 border-l-red-500">
                <flux:heading size="base" class="mb-2">Critical</flux:heading>
                <flux:text variant="subtle" class="text-sm">
                    System down, business stopped. Immediate response required.
                </flux:text>
            </flux:card>

            <flux:card class="border-l-4 border-l-orange-500">
                <flux:heading size="base" class="mb-2">High</flux:heading>
                <flux:text variant="subtle" class="text-sm">
                    Major functionality impaired. Work within 2 hours.
                </flux:text>
            </flux:card>

            <flux:card class="border-l-4 border-l-yellow-500">
                <flux:heading size="base" class="mb-2">Medium</flux:heading>
                <flux:text variant="subtle" class="text-sm">
                    Important but not urgent. Work within 8 hours.
                </flux:text>
            </flux:card>

            <flux:card class="border-l-4 border-l-green-500">
                <flux:heading size="base" class="mb-2">Low</flux:heading>
                <flux:text variant="subtle" class="text-sm">
                    Minor issues or requests. Work within 24 hours.
                </flux:text>
            </flux:card>
        </div>
    </div>

    {{-- Ticket Workflow --}}
    <div>
        <flux:heading size="lg" class="mb-4">Ticket Workflow</flux:heading>
        
        <flux:text class="mb-4">
            Tickets move through several statuses during their lifecycle:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li><strong>Open</strong> - New ticket waiting to be worked</li>
                <li><strong>In Progress</strong> - Technician actively working on the issue</li>
                <li><strong>Waiting on Customer</strong> - Needs information or action from client</li>
                <li><strong>Waiting on Third Party</strong> - Waiting for vendor or external party</li>
                <li><strong>Resolved</strong> - Issue fixed, waiting for customer confirmation</li>
                <li><strong>Closed</strong> - Ticket completed and confirmed by customer</li>
            </ol>
        </div>
    </div>

    {{-- Time Tracking --}}
    <div>
        <flux:heading size="lg" class="mb-4">Time Tracking on Tickets</flux:heading>
        
        <flux:text class="mb-4">
            Track time spent on each ticket for accurate billing:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>Start Timer</strong> - Click the timer button to begin tracking</li>
                <li><strong>Manual Entry</strong> - Add time manually with start/end times</li>
                <li><strong>Time Log</strong> - View all time entries in the ticket's Time tab</li>
                <li><strong>Billable vs Non-Billable</strong> - Mark entries as billable for invoicing</li>
            </ul>
        </div>

        <flux:text class="mt-4">
            Learn more in the <a href="{{ route('docs.show', 'time-tracking') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Time Tracking</a> documentation.
        </flux:text>
    </div>

    {{-- Comments and Updates --}}
    <div>
        <flux:heading size="lg" class="mb-4">Comments & Updates</flux:heading>
        
        <flux:text class="mb-4">
            Keep everyone informed with ticket comments:
        </flux:text>

        <div class="space-y-4">
            <div>
                <flux:heading size="base" class="mb-2">Internal Notes</flux:heading>
                <flux:text>
                    Private notes visible only to your team. Use these for internal communication 
                    about the ticket without notifying the client.
                </flux:text>
            </div>

            <div>
                <flux:heading size="base" class="mb-2">Public Comments</flux:heading>
                <flux:text>
                    Comments visible to the client. The customer receives an email notification 
                    when you add a public comment.
                </flux:text>
            </div>

            <div>
                <flux:heading size="base" class="mb-2">@Mentions</flux:heading>
                <flux:text>
                    Use @mention to notify specific team members. They'll receive a notification 
                    even if they're not assigned to the ticket.
                </flux:text>
            </div>
        </div>
    </div>

    {{-- Attachments --}}
    <div>
        <flux:heading size="lg" class="mb-4">Attachments</flux:heading>
        
        <flux:text class="mb-4">
            Attach files to tickets for documentation:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li>Screenshots and error messages</li>
                <li>Log files and diagnostic reports</li>
                <li>Configuration files</li>
                <li>Documentation and guides</li>
            </ul>
        </div>

        <flux:callout icon="document-arrow-up" color="blue" class="mt-6">
            <flux:callout.heading>File Size Limit</flux:callout.heading>
            <flux:callout.text>
                Individual files must be under 25MB. For larger files, use a file sharing service 
                and include the link in the ticket comments.
            </flux:callout.text>
        </flux:callout>
    </div>

    {{-- SLA Management --}}
    <div>
        <flux:heading size="lg" class="mb-4">SLA Management</flux:heading>
        
        <flux:text class="mb-4">
            Service Level Agreements ensure timely responses:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>Response Time</strong> - Time to first response</li>
                <li><strong>Resolution Time</strong> - Time to resolve the issue</li>
                <li><strong>SLA Warnings</strong> - Visual indicators when deadlines approach</li>
                <li><strong>Breach Notifications</strong> - Alerts when SLAs are violated</li>
            </ul>
        </div>

        <flux:text class="mt-4">
            Configure SLAs in the <a href="{{ route('docs.show', 'contracts') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Contract Management</a> section.
        </flux:text>
    </div>

    {{-- Ticket Templates --}}
    <div>
        <flux:heading size="lg" class="mb-4">Ticket Templates</flux:heading>
        
        <flux:text class="mb-4">
            Create templates for common issues to save time:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li>Go to Settings → Ticket Templates</li>
                <li>Click <strong>Create Template</strong></li>
                <li>Define title, description, category, and priority</li>
                <li>When creating tickets, select a template to pre-fill fields</li>
            </ol>
        </div>
    </div>

    {{-- Automation Rules --}}
    <div>
        <flux:heading size="lg" class="mb-4">Automation Rules</flux:heading>
        
        <flux:text class="mb-4">
            Automate common ticket actions:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li>Auto-assign tickets based on category or client</li>
                <li>Auto-escalate high priority tickets</li>
                <li>Send reminders for tickets waiting on customer</li>
                <li>Close resolved tickets after X days of inactivity</li>
            </ul>
        </div>

        <flux:text class="mt-4">
            Configure automation in Settings → Ticket Automation.
        </flux:text>
    </div>
</div>

<flux:separator class="my-8" />

{{-- Next Steps --}}
<div>
    <flux:heading size="lg" class="mb-4">Next Steps</flux:heading>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('docs.show', 'time-tracking') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="clock" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Time Tracking</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Track time on tickets
                    </flux:text>
                </div>
            </div>
        </a>

        <a href="{{ route('docs.show', 'invoices') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="currency-dollar" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Invoice & Billing</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Bill for your time
                    </flux:text>
                </div>
            </div>
        </a>
    </div>
</div>
