<div 
    x-data="assetRmmStatusComponent({{ $asset->id }})"
    x-init="initEcho()">
    
    <script>
        function assetRmmStatusComponent(assetId) {
            return {
                assetId: assetId,
                echoChannel: null,
                
                initEcho() {
                    console.log('🚀 Initializing Echo for asset', this.assetId);
                    console.log('Echo available:', typeof window.Echo !== 'undefined');
                    console.log('Window Echo:', window.Echo);
                    
                    if (typeof window.Echo === 'undefined') {
                        console.error('❌ Echo is not available!');
                        return;
                    }
                    
                    const channelName = `assets.${this.assetId}`;
                    console.log('📡 Subscribing to channel:', channelName);
                    
                    try {
                        this.echoChannel = window.Echo.channel(channelName);
                        console.log('✓ Channel object created:', this.echoChannel);
                        
                        // Listen for subscription success
                        this.echoChannel.subscription.bind('pusher:subscription_succeeded', () => {
                            console.log('✓✓✓ Successfully subscribed to', channelName);
                        });
                        
                        // Listen for subscription error
                        this.echoChannel.subscription.bind('pusher:subscription_error', (err) => {
                            console.error('❌ Subscription error:', err);
                        });
                        
                        // Listen for the event (no prefix)
                        this.echoChannel.listen('AssetStatusUpdated', (event) => {
                            console.log('🎉 EVENT RECEIVED (no prefix):', event);
                            this.$wire.call('handleStatusUpdate', event);
                        });
                        
                        // Listen for the event (with dot prefix)
                        this.echoChannel.listen('.AssetStatusUpdated', (event) => {
                            console.log('🎉 EVENT RECEIVED (dot prefix):', event);
                            this.$wire.call('handleStatusUpdate', event);
                        });
                        
                        console.log('✓ Event listeners attached');
                        
                    } catch (error) {
                        console.error('❌ Error setting up Echo:', error);
                    }
                }
            }
        }
    </script>
    {{-- Real-time Update Notification --}}
    @if($showUpdateNotification)
    <div 
        x-data="{ show: true }"
        x-init="setTimeout(() => { show = false; $wire.hideNotification() }, 3000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center gap-2"
    >
        <flux:icon.arrow-path class="size-4 text-blue-600 dark:text-blue-400 animate-spin" />
        <flux:text class="text-blue-800 dark:text-blue-200 text-sm">Status updated in real-time</flux:text>
    </div>
    @endif

    <flux:card>
        <flux:heading class="flex items-center gap-2 mb-4">
            <flux:icon.signal class="size-5" />
            Network Information
            <span class="ml-auto">
                <flux:badge 
                    :color="$isOnline ? 'green' : 'zinc'" 
                    class="animate-pulse"
                    wire:poll.5s
                >
                    <span class="flex items-center gap-1">
                        <span class="size-2 rounded-full {{ $isOnline ? 'bg-green-400' : 'bg-zinc-400' }}"></span>
                        Live
                    </span>
                </flux:badge>
            </span>
        </flux:heading>
        
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <flux:text variant="subtle">Local IP Address</flux:text>
                @if($asset->ip)
                    <flux:text variant="strong" class="font-mono">{{ $asset->ip }}</flux:text>
                @else
                    <flux:text variant="subtle">N/A</flux:text>
                @endif
            </div>
            
            @if($rmmPublicIp)
            <div class="flex justify-between items-center">
                <flux:text variant="subtle">Public IP Address</flux:text>
                <flux:text variant="strong" class="font-mono">{{ $rmmPublicIp }}</flux:text>
            </div>
            @endif
            
            @if($asset->nat_ip)
            <div class="flex justify-between items-center">
                <flux:text variant="subtle">NAT IP</flux:text>
                <flux:text variant="strong" class="font-mono">{{ $asset->nat_ip }}</flux:text>
            </div>
            @endif
            
            @if($asset->mac)
            <div class="flex justify-between items-center">
                <flux:text variant="subtle">MAC Address</flux:text>
                <flux:text variant="strong" class="font-mono">{{ $asset->mac }}</flux:text>
            </div>
            @endif
            
            <div class="flex justify-between items-center">
                <flux:text variant="subtle">RMM Status</flux:text>
                <div>
                    <div class="flex items-center gap-2">
                        <flux:badge :color="$isOnline ? 'green' : 'red'">
                            {{ $isOnline ? 'Online' : 'Offline' }}
                        </flux:badge>
                        @if($lastSeen)
                            <flux:text variant="subtle" class="text-xs">
                                Last seen {{ $lastSeen }}
                            </flux:text>
                        @endif
                    </div>
                </div>
            </div>
            
            @if($asset->network)
            <div class="flex justify-between items-center">
                <flux:text variant="subtle">Network</flux:text>
                <flux:text variant="strong">{{ $asset->network->name }} ({{ $asset->network->network }})</flux:text>
            </div>
            @endif
            
            @if($asset->uri)
            <div class="flex justify-between items-center">
                <flux:text variant="subtle">URI/URL</flux:text>
                <flux:link href="{{ $asset->uri }}" target="_blank" class="text-sm break-all">
                    {{ $asset->uri }}
                </flux:link>
            </div>
            @endif
            
            @if($asset->uri_2)
            <div class="flex justify-between items-center">
                <flux:text variant="subtle">Secondary URI</flux:text>
                <flux:link href="{{ $asset->uri_2 }}" target="_blank" class="text-sm break-all">
                    {{ $asset->uri_2 }}
                </flux:link>
            </div>
            @endif
        </div>
    </flux:card>
</div>
