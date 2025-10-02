<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    <button 
        @click="open = !open" 
        class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition"
        aria-label="Notifications">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        
        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div 
        x-show="open" 
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 md:w-96 bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden z-50"
        style="display: none;">
        
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 flex items-center justify-between">
            <h3 class="text-white font-semibold flex items-center">
                <i class="fas fa-bell mr-2"></i>
                Notifications
                @if($unreadCount > 0)
                    <span class="ml-2 px-2 py-0.5 text-xs bg-white/20 rounded-full">{{ $unreadCount }}</span>
                @endif
            </h3>
            
            @if($unreadCount > 0)
                <button 
                    wire:click="markAllAsRead" 
                    class="text-xs text-white hover:text-blue-100 underline">
                    Mark all read
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div 
                    wire:key="notification-{{ $notification->id }}"
                    class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition {{ $notification->is_read ? 'opacity-60' : 'bg-blue-50 dark:bg-blue-900/20' }}">
                    
                    <a 
                        href="{{ $notification->link ?? '#' }}" 
                        wire:click="markAsRead({{ $notification->id }})"
                        class="block px-4 py-3">
                        
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-{{ $notification->color }}-100 dark:bg-{{ $notification->color }}-900 flex items-center justify-center">
                                    <i class="{{ $notification->icon ?? 'fas fa-bell' }} text-{{ $notification->color }}-600 dark:text-{{ $notification->color }}-400"></i>
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $notification->title }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $notification->message }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                            
                            @if(!$notification->is_read)
                                <div class="flex-shrink-0">
                                    <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                                </div>
                            @endif
                        </div>
                    </a>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No notifications yet</p>
                </div>
            @endforelse
        </div>

        @if(count($notifications) > 0)
            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-2 text-center">
                <a href="{{ route('notifications.index') }}" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                    View all notifications
                </a>
            </div>
        @endif
    </div>

    <script>
        window.addEventListener('notification-created', () => {
            @this.call('handleNewNotification');
        });

        setInterval(() => {
            @this.call('loadNotifications');
        }, 30000);
    </script>
</div>
