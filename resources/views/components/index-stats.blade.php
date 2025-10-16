@props(['stats', 'hasItems' => true])

@if($hasItems)
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        @foreach($stats as $stat)
            @if(($stat['value'] ?? 0) > 0)
            <flux:card>
                <div class="p-4">
                    <flux:text variant="muted" size="sm">{{ $stat['label'] }}</flux:text>
                    <flux:heading size="lg">{{ $stat['formatted'] ?? $stat['value'] }}</flux:heading>
                </div>
            </flux:card>
            @endif
        @endforeach
    </div>
@endif
