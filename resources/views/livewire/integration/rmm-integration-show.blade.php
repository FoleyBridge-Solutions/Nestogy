<div>
    <div class="grid gap-6 md:grid-cols-2 mb-6">
        <flux:card>
            <flux:heading size="lg" class="mb-4">Status</flux:heading>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <flux:text>Active</flux:text>
                    <flux:badge :color="$integration->is_active ? 'green' : 'zinc'">
                        {{ $integration->is_active ? 'Yes' : 'No' }}
                    </flux:badge>
                </div>
                <div class="flex justify-between">
                    <flux:text>Total Agents</flux:text>
                    <flux:text>{{ $integration->total_agents ?? 0 }}</flux:text>
                </div>
                <div class="flex justify-between">
                    <flux:text>Last Sync</flux:text>
                    <flux:text>{{ $integration->last_sync_at?->diffForHumans() ?? 'Never' }}</flux:text>
                </div>
                <div class="flex justify-between">
                    <flux:text>Client Mappings</flux:text>
                    <flux:text>{{ count($mappings) }}</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">Actions</flux:heading>
            <div class="space-y-3">
                <flux:button variant="outline" class="w-full" wire:click="testConnection">
                    Test Connection
                </flux:button>
                <flux:button variant="outline" class="w-full" wire:click="syncAgents">
                    Sync Agents
                </flux:button>
                <flux:button variant="outline" class="w-full" wire:click="syncAlerts">
                    Sync Alerts
                </flux:button>
                <flux:button variant="primary" class="w-full" wire:click="openClientMappingModal">
                    Manage Client Mappings
                </flux:button>
            </div>
        </flux:card>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <flux:card>
            <flux:heading size="lg" class="mb-4">Integration Details</flux:heading>
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="font-medium">Type</flux:text>
                    <flux:text>{{ $integration->rmm_type }}</flux:text>
                </div>
                <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="font-medium">API URL</flux:text>
                    <flux:text>{{ $integration->api_url }}</flux:text>
                </div>
                <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:text class="font-medium">Created</flux:text>
                    <flux:text>{{ $integration->created_at->format('M d, Y') }}</flux:text>
                </div>
                <div class="flex justify-between py-2">
                    <flux:text class="font-medium">Last Updated</flux:text>
                    <flux:text>{{ $integration->updated_at->diffForHumans() }}</flux:text>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">Client Mappings</flux:heading>
            @if(count($mappings) > 0)
                <div class="space-y-2">
                    @foreach($mappings as $mapping)
                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                            <div>
                                <flux:text class="font-medium">{{ $mapping['client_name'] }}</flux:text>
                                <flux:text class="text-xs text-zinc-500">→ {{ $mapping['rmm_client_name'] }}</flux:text>
                            </div>
                            <flux:button 
                                size="sm" 
                                variant="ghost" 
                                icon="trash"
                                wire:click="removeMapping({{ $mapping['id'] }})"
                                wire:confirm="Are you sure you want to remove this mapping?"
                            ></flux:button>
                        </div>
                    @endforeach
                </div>
            @else
                <flux:text class="text-zinc-500">No client mappings configured. Click "Manage Client Mappings" to add them.</flux:text>
            @endif
        </flux:card>
    </div>

    @if($actionResult)
        <div class="mt-6 p-4 {{ $actionType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' }} border rounded-lg">
            <div class="font-medium {{ $actionType === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                {{ $actionResult }}
            </div>
        </div>
    @endif

    {{-- Client Mapping Modal --}}
    <flux:modal wire:model="showClientMappingModal" class="md:w-[800px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Map Clients</flux:heading>
                <flux:subheading>Link your Nestogy clients to RMM clients for proper agent synchronization</flux:subheading>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                {{-- Nestogy Clients --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Nestogy Clients</flux:heading>
                    @if($loadingClients)
                        <div class="flex items-center justify-center py-8">
                            <flux:text class="text-zinc-500">Loading clients...</flux:text>
                        </div>
                    @else
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($nestogyClients as $client)
                                <div 
                                    wire:click="$set('selectedNestogyClientId', {{ $client['id'] }})"
                                    class="p-3 rounded border cursor-pointer transition-colors
                                        {{ $selectedNestogyClientId === $client['id'] ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}
                                        {{ $client['is_mapped'] ? 'opacity-60' : '' }}"
                                >
                                    <flux:text class="font-medium">{{ $client['display_name'] }}</flux:text>
                                    @if($client['is_mapped'])
                                        <flux:text class="text-xs text-zinc-500">Mapped to: {{ $client['rmm_client_name'] }}</flux:text>
                                    @endif
                                </div>
                            @empty
                                <flux:text class="text-zinc-500">No Nestogy clients found</flux:text>
                            @endforelse
                        </div>
                    @endif
                </div>

                {{-- RMM Clients --}}
                <div>
                    <flux:heading size="sm" class="mb-3">RMM Clients</flux:heading>
                    @if($loadingClients)
                        <div class="flex items-center justify-center py-8">
                            <flux:text class="text-zinc-500">Loading clients...</flux:text>
                        </div>
                    @else
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($rmmClients as $client)
                                <div 
                                    wire:click="$set('selectedRmmClientId', {{ $client['id'] }})"
                                    class="p-3 rounded border cursor-pointer transition-colors
                                        {{ $selectedRmmClientId === $client['id'] ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600' }}
                                        {{ $client['is_mapped'] ? 'opacity-60' : '' }}"
                                >
                                    <flux:text class="font-medium">{{ $client['name'] }}</flux:text>
                                    @if($client['is_mapped'])
                                        <flux:text class="text-xs text-zinc-500">Already mapped</flux:text>
                                    @endif
                                </div>
                            @empty
                                <flux:text class="text-zinc-500">No RMM clients found</flux:text>
                            @endforelse
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button 
                    variant="primary" 
                    wire:click="createMapping"
                    :disabled="!$selectedNestogyClientId || !$selectedRmmClientId"
                >
                    Create Mapping
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
