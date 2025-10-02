<div>
    <flux:card>
        <flux:heading size="lg">
            <i class="fas fa-bell mr-2"></i>
            Notification Preferences
        </flux:heading>
        
        <flux:subheading class="mb-6">
            Customize how and when you receive notifications about tickets and activities.
        </flux:subheading>

        <form wire:submit="save" class="space-y-6">
            <flux:separator />
            
            <div>
                <flux:heading size="md">Notification Channels</flux:heading>
                <flux:subheading>Choose how you want to receive notifications</flux:subheading>
                
                <div class="mt-4 space-y-3">
                    <flux:checkbox wire:model="email_enabled" label="Email Notifications" />
                    <flux:checkbox wire:model="in_app_enabled" label="In-App Notifications" />
                </div>
            </div>

            <flux:separator />

            <div>
                <flux:heading size="md">Ticket Notifications</flux:heading>
                <flux:subheading>Get notified about ticket activities</flux:subheading>
                
                <div class="mt-4 space-y-3">
                    <flux:checkbox 
                        wire:model="ticket_created" 
                        label="New Ticket Created"
                        description="Receive notifications when a new ticket is created" />
                    
                    <flux:checkbox 
                        wire:model="ticket_assigned" 
                        label="Ticket Assigned to Me"
                        description="Receive notifications when a ticket is assigned to you" />
                    
                    <flux:checkbox 
                        wire:model="ticket_status_changed" 
                        label="Ticket Status Changed"
                        description="Receive notifications when ticket status is updated" />
                    
                    <flux:checkbox 
                        wire:model="ticket_resolved" 
                        label="Ticket Resolved"
                        description="Receive notifications when a ticket is resolved" />
                    
                    <flux:checkbox 
                        wire:model="ticket_comment_added" 
                        label="New Comment Added"
                        description="Receive notifications when someone comments on a ticket" />
                </div>
            </div>

            <flux:separator />

            <div>
                <flux:heading size="md">SLA Notifications</flux:heading>
                <flux:subheading>Critical alerts for service level agreements</flux:subheading>
                
                <div class="mt-4 space-y-3">
                    <flux:checkbox 
                        wire:model="sla_breach_warning" 
                        label="SLA Breach Warning"
                        description="Receive notifications when tickets are approaching SLA deadlines" />
                    
                    <flux:checkbox 
                        wire:model="sla_breached" 
                        label="SLA Breached"
                        description="Receive critical notifications when SLA deadlines are exceeded" />
                </div>
            </div>

            <flux:separator />

            <div>
                <flux:heading size="md">Digest Settings</flux:heading>
                <flux:subheading>Receive a daily summary of all activities</flux:subheading>
                
                <div class="mt-4 space-y-4">
                    <flux:checkbox 
                        wire:model="daily_digest" 
                        label="Enable Daily Digest Email"
                        description="Receive a daily summary email instead of individual notifications" />
                    
                    @if($daily_digest)
                        <flux:field>
                            <flux:label>Digest Delivery Time</flux:label>
                            <flux:input 
                                type="time" 
                                wire:model="digest_time" 
                                placeholder="08:00" />
                            <flux:description>Choose when you want to receive your daily digest</flux:description>
                        </flux:field>
                    @endif
                </div>
            </div>

            <flux:separator />

            <div class="flex justify-between items-center">
                <flux:button type="button" variant="ghost" wire:click="mount">
                    <i class="fas fa-undo mr-2"></i>
                    Reset
                </flux:button>
                
                <flux:button type="submit" variant="primary">
                    <i class="fas fa-save mr-2"></i>
                    Save Preferences
                </flux:button>
            </div>
        </form>
    </flux:card>


</div>
