{{-- RMM Integration Settings Form --}}
<div class="space-y-8">
    {{-- RMM Integrations List --}}
    @if(isset($settings['_integrations']) && count($settings['_integrations']) > 0)
        <div>
            <flux:heading size="lg" class="mb-4">Configured RMM Integrations</flux:heading>
            
            <div class="grid gap-4 mb-6">
                @foreach($settings['_integrations'] as $integration)
                    <flux:card>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                    <flux:icon.server-stack class="w-6 h-6" />
                                </div>
                                <div>
                                    <flux:heading size="md">{{ $integration['name'] }}</flux:heading>
                                    <flux:text class="text-sm">
                                        Type: {{ $integration['type'] }} • 
                                        {{ $integration['total_agents'] ?? 0 }} agents
                                        @if($integration['last_sync'])
                                            • Last sync: {{ $integration['last_sync'] }}
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <flux:badge :color="$integration['is_active'] ? 'green' : 'zinc'">
                                    {{ $integration['is_active'] ? 'Active' : 'Inactive' }}
                                </flux:badge>
                                <a href="{{ route('settings.integrations.rmm.show', $integration['id']) }}">
                                    <flux:button 
                                        type="button"
                                        variant="ghost" 
                                        size="sm"
                                        icon="arrow-right"
                                    >
                                        Manage
                                    </flux:button>
                                </a>
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
            
            <a href="{{ route('settings.integrations.rmm.create') }}">
                <flux:button 
                    type="button"
                    icon="plus"
                    variant="outline"
                >
                    Add New RMM Integration
                </flux:button>
            </a>
        </div>
        
        <flux:separator />
    @else
        <div>
            <flux:heading size="lg" class="mb-2">No RMM Integrations Configured</flux:heading>
            <flux:text class="mb-4">
                Connect your Remote Monitoring and Management system to automatically sync agents, alerts, and device information.
            </flux:text>
            
            <a href="{{ route('settings.integrations.rmm.create') }}">
                <flux:button 
                    type="button"
                    icon="plus"
                    variant="primary"
                >
                    Configure RMM Integration
                </flux:button>
            </a>
        </div>
        
        <flux:separator />
    @endif
    
    {{-- Sync Settings --}}
    <div>
        <flux:heading size="lg" class="mb-4">Synchronization Settings</flux:heading>
        <flux:subheading class="mb-6">Configure how RMM data is synchronized with the system</flux:subheading>
        
        <div class="space-y-6">
            {{-- Auto Sync Enabled --}}
            <flux:field>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:label>Enable Automatic Sync</flux:label>
                        <flux:description>Automatically sync data from RMM systems at regular intervals</flux:description>
                    </div>
                    <flux:switch name="auto_sync_enabled" :checked="$settings['auto_sync_enabled'] ?? true" />
                </div>
            </flux:field>
            
            {{-- Sync Interval --}}
            <flux:field>
                <flux:label>Sync Interval (minutes)</flux:label>
                <flux:description>How often to sync data from RMM systems</flux:description>
                <flux:input 
                    type="number" 
                    name="sync_interval_minutes" 
                    value="{{ $settings['sync_interval_minutes'] ?? 60 }}"
                    min="5"
                    max="1440"
                />
            </flux:field>
            
            {{-- Sync Agents Enabled --}}
            <flux:field>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:label>Sync Agents/Devices</flux:label>
                        <flux:description>Automatically sync device information from RMM systems</flux:description>
                    </div>
                    <flux:switch name="sync_agents_enabled" :checked="$settings['sync_agents_enabled'] ?? true" />
                </div>
            </flux:field>
            
            {{-- Sync Alerts Enabled --}}
            <flux:field>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:label>Sync Alerts</flux:label>
                        <flux:description>Automatically sync alerts and notifications from RMM systems</flux:description>
                    </div>
                    <flux:switch name="sync_alerts_enabled" :checked="$settings['sync_alerts_enabled'] ?? true" />
                </div>
            </flux:field>
        </div>
    </div>
    
    <flux:separator />
    
    {{-- Alert Settings --}}
    <div>
        <flux:heading size="lg" class="mb-4">Alert Settings</flux:heading>
        <flux:subheading class="mb-6">Configure how RMM alerts are handled</flux:subheading>
        
        <div class="space-y-6">
            {{-- Alert Severity Filter --}}
            <flux:field>
                <flux:label>Alert Severity Filter</flux:label>
                <flux:description>Select which alert severity levels to sync</flux:description>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-2">
                    @php
                        $selectedSeverities = $settings['alert_severity_filter'] ?? ['critical', 'high', 'medium'];
                    @endphp
                    
                    @foreach(['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low', 'info' => 'Info'] as $value => $label)
                        <label class="flex items-center gap-2 p-3 rounded-lg border border-zinc-300 dark:border-zinc-600 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <input 
                                type="checkbox" 
                                name="alert_severity_filter[]" 
                                value="{{ $value }}"
                                {{ in_array($value, $selectedSeverities) ? 'checked' : '' }}
                                class="rounded border-zinc-300 dark:border-zinc-600"
                            />
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </flux:field>
            
            {{-- Create Tickets from Alerts --}}
            <flux:field>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:label>Create Tickets from Alerts</flux:label>
                        <flux:description>Automatically create support tickets from RMM alerts</flux:description>
                    </div>
                    <flux:switch name="create_tickets_from_alerts" :checked="$settings['create_tickets_from_alerts'] ?? false" />
                </div>
            </flux:field>
        </div>
    </div>
</div>
