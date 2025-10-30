<div class="relative" x-data="notificationCenter()" x-init="init()">
    <!-- Bell Icon Button -->
    <button 
        @click="$wire.toggleDropdown()"
        type="button"
        class="relative p-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
    >
        <i class="fas fa-bell text-xl"></i>
        
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Panel -->
    <div 
        x-show="@js($showDropdown)"
        @click.away="$wire.toggleDropdown()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
        style="display: none;"
    >
        <!-- Header with Push Notification Toggle -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Notifications
                </h3>
                @if($unreadCount > 0)
                    <button 
                        wire:click="markAllAsRead"
                        class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        Mark all read
                    </button>
                @endif
            </div>
            
            <!-- Push Notification Toggle -->
            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-900 rounded-md">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-mobile-alt text-gray-600 dark:text-gray-400 text-sm"></i>
                    <span class="text-xs text-gray-700 dark:text-gray-300">Push Notifications</span>
                </div>
                
                <button 
                    x-show="isSupported && !@js($isPushSubscribed)" 
                    @click="enablePushNotifications()"
                    :disabled="permission === 'denied'"
                    class="text-xs px-3 py-1 rounded-md transition"
                    :class="permission === 'denied' ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'"
                >
                    <span x-show="permission !== 'denied'">Enable</span>
                    <span x-show="permission === 'denied'">Blocked</span>
                </button>
                
                <button 
                    x-show="isSupported && @js($isPushSubscribed)" 
                    @click="disablePushNotifications()"
                    class="text-xs px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 transition"
                >
                    ✓ Enabled
                </button>
                
                <span 
                    x-show="!isSupported" 
                    class="text-xs text-gray-500 dark:text-gray-400"
                >
                    Not supported
                </span>
            </div>
        </div>

        <!-- Notification List -->
        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div 
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 {{ $notification->read_at ? '' : 'bg-blue-50 dark:bg-blue-900/20' }}"
                >
                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0 mt-1">
                            @php
                                $iconClass = match($notification->data['type'] ?? 'default') {
                                    'ticket_created' => 'fa-ticket-alt text-blue-600',
                                    'ticket_assigned' => 'fa-user-check text-green-600',
                                    'ticket_status_changed' => 'fa-exchange-alt text-yellow-600',
                                    'ticket_resolved' => 'fa-check-circle text-green-600',
                                    'ticket_comment_added' => 'fa-comment text-purple-600',
                                    'sla_breach_warning' => 'fa-exclamation-triangle text-orange-600',
                                    'sla_breached' => 'fa-exclamation-circle text-red-600',
                                    default => 'fa-bell text-gray-600',
                                };
                            @endphp
                            <i class="fas {{ $iconClass }}"></i>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $notification->data['title'] ?? 'Notification' }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $notification->data['message'] ?? '' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>

                        <!-- Unread Indicator -->
                        @if(!$notification->read_at)
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-bell-slash text-3xl mb-2"></i>
                    <p class="text-sm">No notifications yet</p>
                </div>
            @endforelse
        </div>

        <!-- Footer -->
        @if($notifications->count() > 0)
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-center">
                <a 
                    href="{{ route('notifications.index') }}"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    View all notifications
                </a>
            </div>
        @endif
    </div>

    @script
    <script>
        Alpine.data('notificationCenter', () => ({
            isSupported: false,
            permission: 'default',
            
            init() {
                // Check browser support
                this.isSupported = ('serviceWorker' in navigator && 'PushManager' in window);
                
                if (this.isSupported) {
                    this.permission = Notification.permission;
                }
                
                console.log('[NotificationCenter] Initialized', {
                    isSupported: this.isSupported,
                    permission: this.permission
                });
            },
            
            async enablePushNotifications() {
                try {
                    if (!this.isSupported) {
                        alert('Push notifications are not supported in your browser');
                        return;
                    }

                    // Register service worker if not already registered
                    let registration = await navigator.serviceWorker.getRegistration();
                    if (!registration) {
                        console.log('[NotificationCenter] Registering service worker...');
                        registration = await navigator.serviceWorker.register('/sw.js');
                    }
                    
                    await navigator.serviceWorker.ready;
                    console.log('[NotificationCenter] Service worker ready');

                    // Request permission
                    const permission = await Notification.requestPermission();
                    this.permission = permission;
                    
                    console.log('[NotificationCenter] Permission:', permission);
                    
                    if (permission !== 'granted') {
                        alert('Please allow notifications in your browser settings');
                        return;
                    }

                    // Subscribe to push notifications
                    console.log('[NotificationCenter] Subscribing to push...');
                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: @js(config('webpush.vapid.public_key'))
                    });

                    // Send subscription to server via Livewire
                    const subscriptionData = subscription.toJSON();
                    console.log('[NotificationCenter] Subscription created, sending to server...');
                    $wire.subscribeToPush(subscriptionData);

                } catch (error) {
                    console.error('[NotificationCenter] Failed to enable notifications:', error);
                    alert('Failed to enable push notifications: ' + error.message);
                }
            },
            
            async disablePushNotifications() {
                try {
                    const registration = await navigator.serviceWorker.getRegistration();
                    if (!registration) return;
                    
                    const subscription = await registration.pushManager.getSubscription();
                    if (!subscription) return;
                    
                    console.log('[NotificationCenter] Unsubscribing from push...');
                    
                    // Unsubscribe from push
                    await subscription.unsubscribe();
                    
                    // Notify server via Livewire
                    $wire.unsubscribeFromPush(subscription.endpoint);
                    
                    console.log('[NotificationCenter] Unsubscribed successfully');
                    
                } catch (error) {
                    console.error('[NotificationCenter] Failed to disable notifications:', error);
                }
            }
        }));
    </script>
    @endscript
</div>
