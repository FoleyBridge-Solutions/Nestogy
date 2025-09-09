@props([
    'client' => null,
    'stats' => [],
])

<div class="orbital-center-content bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-6 border-2 border-gray-200 dark:border-gray-700 min-w-[200px] text-center">
    <!-- Client Avatar -->
    <div class="flex justify-center mb-3">
        @if($client->avatar)
            <img 
                class="w-20 h-20 rounded-full object-cover ring-4 ring-white dark:ring-gray-800 shadow-lg" 
                src="{{ Storage::url($client->avatar) }}" 
                alt="{{ $client->name }}"
            >
        @else
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center ring-4 ring-white dark:ring-gray-800 shadow-lg">
                <span class="text-2xl font-bold text-white">
                    {{ strtoupper(substr($client->name, 0, 2)) }}
                </span>
            </div>
        @endif
    </div>
    
    <!-- Client Name -->
    <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2 truncate max-w-[180px]">
        {{ $client->name }}
    </h1>
    
    <!-- Status Badges -->
    <div class="flex justify-center gap-1 mb-3 flex-wrap">
        <flux:badge 
            :color="$client->is_active ? 'green' : 'red'" 
            size="sm"
        >
            {{ $client->is_active ? 'Active' : 'Inactive' }}
        </flux:badge>
        
        @if($client->type)
            <flux:badge color="zinc" size="sm">
                {{ ucfirst($client->type) }}
            </flux:badge>
        @endif
        
        @if($client->lead)
            <flux:badge color="yellow" size="sm">Lead</flux:badge>
        @else
            <flux:badge color="blue" size="sm">Customer</flux:badge>
        @endif
    </div>
    
    <!-- Key Metrics -->
    @if(count($stats) > 0)
        <div class="flex justify-center items-center gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
            @foreach($stats as $stat)
                <div class="text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $stat['value'] }}</div>
                </div>
                @if(!$loop->last)
                    <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>
                @endif
            @endforeach
        </div>
    @endif
</div>