<div>
    <div class="space-y-2">
        @forelse($activities as $activity)
            <a href="{{ $activity['url'] ?? '#' }}" 
               class="flex items-start space-x-2 p-2 rounded hover:bg-gray-50 transition-colors block">
                <div class="flex-shrink-0 mt-0.5">
                    @if($activity['type'] === 'ticket')
                        <flux:icon name="ticket" class="w-4 h-4 text-{{ $activity['color'] ?? 'gray' }}-500" />
                    @elseif($activity['type'] === 'invoice')
                        <flux:icon name="document-text" class="w-4 h-4 text-{{ $activity['color'] ?? 'gray' }}-500" />
                    @elseif($activity['type'] === 'project')
                        <flux:icon name="clipboard-document-list" class="w-4 h-4 text-{{ $activity['color'] ?? 'gray' }}-500" />
                    @elseif($activity['type'] === 'asset')
                        <flux:icon name="computer-desktop" class="w-4 h-4 text-{{ $activity['color'] ?? 'gray' }}-500" />
                    @elseif($activity['type'] === 'contract')
                        <flux:icon name="document-duplicate" class="w-4 h-4 text-{{ $activity['color'] ?? 'gray' }}-500" />
                    @else
                        <flux:icon name="information-circle" class="w-4 h-4 text-gray-500" />
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium truncate">{{ $activity['title'] }}</p>
                    @if(isset($activity['description']))
                        <p class="text-xs text-gray-600 truncate">{{ $activity['description'] }}</p>
                    @endif
                    <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}</p>
                </div>
                @if(isset($activity['status']))
                    <flux:badge size="sm" 
                        :color="match($activity['status']) {
                            'closed', 'paid', 'completed', 'active' => 'green',
                            'open', 'pending' => 'yellow',
                            'overdue', 'high', 'critical' => 'red',
                            default => 'zinc'
                        }">
                        {{ ucfirst($activity['status']) }}
                    </flux:badge>
                @endif
            </a>
        @empty
            <div class="text-center py-4 text-sm text-gray-500">
                No recent activity
            </div>
        @endforelse
    </div>
    
    @if($hasMore && count($activities) > 0)
        <div class="mt-3 text-center">
            <flux:button 
                wire:click="loadMore" 
                wire:loading.attr="disabled"
                variant="ghost" 
                size="sm">
                <span wire:loading.remove>Load More</span>
                <span wire:loading>Loading...</span>
            </flux:button>
        </div>
    @endif
</div>