@props(['content'])

<div class="space-y-8">
    {{-- Intro Section --}}
    @if(isset($content['intro']))
        <flux:text class="text-lg">
            {{ $content['intro']['content'] }}
        </flux:text>
    @endif

    {{-- Main Sections --}}
    @if(isset($content['sections']))
        @foreach($content['sections'] as $section)
            <div class="space-y-4">
                @if(isset($section['heading']))
                    <flux:heading size="lg">{{ $section['heading'] }}</flux:heading>
                @endif

                @if(isset($section['content']))
                    <flux:text>
                        {{ $section['content'] }}
                    </flux:text>
                @endif

                @if(isset($section['list']))
                    <div class="prose prose-zinc dark:prose-invert max-w-none">
                        @if($section['list']['type'] === 'ordered')
                            <ol>
                                @foreach($section['list']['items'] as $item)
                                    <li>{!! nl2br(e($item)) !!}</li>
                                @endforeach
                            </ol>
                        @else
                            <ul>
                                @foreach($section['list']['items'] as $item)
                                    <li>{!! nl2br(e($item)) !!}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                @if(isset($section['type']) && $section['type'] === 'list')
                    <div class="prose prose-zinc dark:prose-invert max-w-none">
                        <ul>
                            @foreach($section['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(isset($section['callout']))
                    <flux:callout 
                        icon="{{ $section['callout']['icon'] }}" 
                        color="{{ $section['callout']['color'] }}"
                        @if(isset($section['callout']['type']) && $section['callout']['type'] === 'success') variant="success" @endif
                        @if(isset($section['callout']['type']) && $section['callout']['type'] === 'warning') variant="warning" @endif
                        @if(isset($section['callout']['type']) && $section['callout']['type'] === 'danger') variant="danger" @endif
                    >
                        <flux:callout.heading>{{ $section['callout']['heading'] }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ $section['callout']['content'] }}
                        </flux:callout.text>
                    </flux:callout>
                @endif

                @if(isset($section['link']))
                    <flux:text>
                        <a href="{{ route('docs.show', $section['link']['route']) }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">
                            {{ $section['link']['text'] }}
                        </a>
                    </flux:text>
                @endif
            </div>

            @if(!$loop->last)
                <flux:separator class="my-8" />
            @endif
        @endforeach
    @endif

    {{-- Next Steps Section --}}
    @if(isset($content['next_steps']))
        <flux:separator class="my-8" />
        
        <div class="space-y-4">
            <flux:heading size="lg">{{ $content['next_steps']['heading'] }}</flux:heading>
            
            @if(isset($content['next_steps']['content']))
                <flux:text>{{ $content['next_steps']['content'] }}</flux:text>
            @endif

            @if(isset($content['next_steps']['cards']))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($content['next_steps']['cards'] as $card)
                        <a href="{{ route('docs.show', $card['route']) }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
                            <div class="flex items-start gap-3">
                                <flux:icon name="{{ $card['icon'] }}" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                                <div>
                                    <flux:heading size="base" class="mb-1">{{ $card['title'] }}</flux:heading>
                                    <flux:text variant="subtle" class="text-sm">
                                        {{ $card['description'] }}
                                    </flux:text>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Help Section --}}
    @if(isset($content['help']))
        <flux:separator class="my-8" />
        
        <div class="space-y-4">
            <flux:heading size="lg">{{ $content['help']['heading'] }}</flux:heading>
            
            @if(isset($content['help']['content']))
                <flux:text>{{ $content['help']['content'] }}</flux:text>
            @endif

            @if(isset($content['help']['list']))
                <div class="prose prose-zinc dark:prose-invert max-w-none">
                    <ul>
                        @foreach($content['help']['list']['items'] as $item)
                            <li>{!! \Illuminate\Support\Str::markdown($item) !!}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(isset($content['help']['callout']))
                <flux:callout 
                    icon="{{ $content['help']['callout']['icon'] }}" 
                    color="{{ $content['help']['callout']['color'] }}"
                    @if(isset($content['help']['callout']['type']) && $content['help']['callout']['type'] === 'success') variant="success" @endif
                >
                    <flux:callout.heading>{{ $content['help']['callout']['heading'] }}</flux:callout.heading>
                    <flux:callout.text>
                        {{ $content['help']['callout']['content'] }}
                    </flux:callout.text>
                </flux:callout>
            @endif
        </div>
    @endif
</div>
