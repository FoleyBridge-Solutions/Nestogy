<flux:card class="h-full">
    <!-- Header -->
    <div class="mb-6">
        <flux:heading size="lg" class="flex items-center gap-2">
            <flux:icon.bolt class="size-5 text-yellow-500" />
            Quick Actions
        </flux:heading>
        <flux:text size="sm" class="text-zinc-500 mt-1">
            Common tasks and shortcuts
        </flux:text>
    </div>
    
    <!-- Actions Grid -->
    @if(!empty($actions))
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($actions as $index => $action)
                @php
                    $component = isset($action['route']) ? 'a' : 'button';
                    $attributes = isset($action['route']) ? 
                        ['href' => route($action['route'])] : 
                        ['wire:click' => isset($action['action']) ? 'executeAction(\''.$action['action'].'\')' : ''];
                @endphp
                
                <{{ $component }} 
                    @if(isset($action['route']))
                        @try
                            href="{{ route($action['route']) }}"
                        @catch(\Exception $e)
                            href="#"
                        @endtry
                    @elseif(isset($action['action']))
                        wire:click="executeAction('{{ $action['action'] }}')"
                        type="button"
                    @endif
                    class="group p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:shadow-md hover:border-{{ $action['color'] }}-300 dark:hover:border-{{ $action['color'] }}-600 transition-all duration-200 text-left"
                    wire:key="action-{{ $index }}"
                >
                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0 p-2 rounded-lg transition-colors
                            @switch($action['color'])
                                @case('blue')
                                    bg-blue-100 dark:bg-blue-900/30 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50
                                    @break
                                @case('green')
                                    bg-green-100 dark:bg-green-900/30 group-hover:bg-green-200 dark:group-hover:bg-green-900/50
                                    @break
                                @case('purple')
                                    bg-purple-100 dark:bg-purple-900/30 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50
                                    @break
                                @case('orange')
                                    bg-orange-100 dark:bg-orange-900/30 group-hover:bg-orange-200 dark:group-hover:bg-orange-900/50
                                    @break
                                @case('red')
                                    bg-red-100 dark:bg-red-900/30 group-hover:bg-red-200 dark:group-hover:bg-red-900/50
                                    @break
                                @case('yellow')
                                    bg-yellow-100 dark:bg-yellow-900/30 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-900/50
                                    @break
                                @default
                                    bg-zinc-100 dark:bg-zinc-900/30 group-hover:bg-zinc-200 dark:group-hover:bg-zinc-900/50
                            @endswitch
                        ">
                            @php
                                $iconColorClass = match($action['color']) {
                                    'blue' => 'text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300',
                                    'green' => 'text-green-600 dark:text-green-400 group-hover:text-green-700 dark:group-hover:text-green-300',
                                    'purple' => 'text-purple-600 dark:text-purple-400 group-hover:text-purple-700 dark:group-hover:text-purple-300',
                                    'orange' => 'text-orange-600 dark:text-orange-400 group-hover:text-orange-700 dark:group-hover:text-orange-300',
                                    'red' => 'text-red-600 dark:text-red-400 group-hover:text-red-700 dark:group-hover:text-red-300',
                                    'yellow' => 'text-yellow-600 dark:text-yellow-400 group-hover:text-yellow-700 dark:group-hover:text-yellow-300',
                                    default => 'text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-700 dark:group-hover:text-zinc-300'
                                };
                            @endphp
                            
                            @switch($action['icon'])
                                @case('plus-circle')
                                    <flux:icon.plus-circle class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('user-plus')
                                    <flux:icon.user-plus class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('document-plus')
                                    <flux:icon.document-plus class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('calendar-days')
                                    <flux:icon.calendar-days class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('bell')
                                    <flux:icon.bell class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('chart-bar')
                                    <flux:icon.chart-bar class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('refresh')
                                    <flux:icon.arrow-path class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('cog')
                                    <flux:icon.cog class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('envelope')
                                    <flux:icon.envelope class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('pencil')
                                    <flux:icon.pencil class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @case('cog-6-tooth')
                                    <flux:icon.cog-6-tooth class="size-5 transition-colors {{ $iconColorClass }}" />
                                    @break
                                @default
                                    <flux:icon.bolt class="size-5 transition-colors {{ $iconColorClass }}" />
                            @endswitch
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            @php
                                $headingHoverClass = match($action['color']) {
                                    'blue' => 'group-hover:text-blue-600',
                                    'green' => 'group-hover:text-green-600',
                                    'purple' => 'group-hover:text-purple-600',
                                    'orange' => 'group-hover:text-orange-600',
                                    'red' => 'group-hover:text-red-600',
                                    'yellow' => 'group-hover:text-yellow-600',
                                    default => 'group-hover:text-zinc-600'
                                };
                                $arrowHoverClass = match($action['color']) {
                                    'blue' => 'group-hover:text-blue-500',
                                    'green' => 'group-hover:text-green-500',
                                    'purple' => 'group-hover:text-purple-500',
                                    'orange' => 'group-hover:text-orange-500',
                                    'red' => 'group-hover:text-red-500',
                                    'yellow' => 'group-hover:text-yellow-500',
                                    default => 'group-hover:text-zinc-500'
                                };
                            @endphp
                            <flux:heading size="sm" class="{{ $headingHoverClass }} transition-colors">
                                {{ $action['title'] }}
                            </flux:heading>
                            <flux:text size="xs" class="text-zinc-500 mt-1 line-clamp-2">
                                {{ $action['description'] }}
                            </flux:text>
                        </div>
                        
                        <!-- Arrow -->
                        <flux:icon.chevron-right class="size-4 text-zinc-400 {{ $arrowHoverClass }} transition-colors" />
                    </div>
                </{{ $component }}>
            @endforeach
        </div>
        
        <!-- Additional Actions -->
        <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <flux:text size="sm" class="text-zinc-500">
                    Need help? Visit our documentation
                </flux:text>
                
                <flux:button variant="ghost" size="sm" onclick="document.dispatchEvent(new KeyboardEvent('keydown', {key: 'k', metaKey: true, bubbles: true}))">
                    <flux:icon.command-line class="size-4" />
                    ⌘K
                </flux:button>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="flex items-center justify-center h-32">
            <div class="text-center">
                <flux:icon.bolt class="size-8 text-zinc-300 mx-auto mb-2" />
                <flux:text class="text-zinc-500">No quick actions available</flux:text>
            </div>
        </div>
    @endif
</flux:card>
