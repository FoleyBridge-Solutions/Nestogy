@props(['stats' => []])

@if(!empty($stats))
    @php
        $visibleStats = collect($stats)->filter(fn($stat) => ($stat['value'] ?? 0) > 0 || ($stat['alwaysShow'] ?? false));
        $count = $visibleStats->count();
        
        $gridClass = match($count) {
            1 => 'grid-cols-1',
            2 => 'grid-cols-1 lg:grid-cols-2',
            3 => 'grid-cols-1 lg:grid-cols-3',
            4 => 'grid-cols-2 lg:grid-cols-4',
            5 => 'grid-cols-2 lg:grid-cols-5',
            6 => 'grid-cols-2 lg:grid-cols-6',
            default => 'grid-cols-2 lg:grid-cols-4',
        };
    @endphp
    
    <div class="grid {{ $gridClass }} gap-4 mb-6">
        @foreach($stats as $stat)
            @if(($stat['value'] ?? 0) > 0 || ($stat['alwaysShow'] ?? false))
                <flux:card>
                    <div class="p-4">
                        @if(isset($stat['icon']))
                            <div class="flex items-center mb-2">
                                <div class="w-10 h-10 {{ $stat['iconBg'] ?? 'bg-blue-500' }} rounded-full flex items-center justify-center">
                                    <flux:icon name="{{ $stat['icon'] }}" variant="solid" class="w-5 h-5 text-white" />
                                </div>
                            </div>
                        @endif
                        
                        <flux:text variant="muted" size="sm">{{ $stat['label'] }}</flux:text>
                        
                        <flux:heading 
                            size="lg" 
                            class="{{ $stat['valueClass'] ?? '' }}"
                        >
                            @if(isset($stat['prefix'])){{ $stat['prefix'] }}@endif{{ $stat['value'] }}@if(isset($stat['suffix'])){{ $stat['suffix'] }}@endif
                        </flux:heading>
                    </div>
                </flux:card>
            @endif
        @endforeach
    </div>
@endif
