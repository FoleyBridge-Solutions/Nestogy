@props(['type' => 'default'])

@php
    $skeletonClasses = match($type) {
        'form' => 'space-y-6',
        'tabs' => 'space-y-4',
        'table' => 'space-y-3',
        'cards' => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6',
        'tab-form' => 'space-y-4',
        'tab-table' => 'space-y-3',
        'tab-cards' => 'grid grid-cols-1 md:grid-cols-2 gap-4',
        default => 'space-y-4'
    };
@endphp

<div class="{{ $skeletonClasses }} animate-pulse" role="status" aria-label="Loading content">
    @if($type === 'form')
        <!-- Form skeleton -->
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @for($i = 0; $i < 4; $i++)
                <div class="space-y-2">
                    <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-10 bg-gray-200 rounded"></div>
                </div>
                @endfor
            </div>
            <div class="space-y-2">
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                <div class="h-20 bg-gray-200 rounded"></div>
            </div>
            <div class="flex justify-end space-x-3">
                <div class="h-10 bg-gray-200 rounded w-20"></div>
                <div class="h-10 bg-gray-200 rounded w-24"></div>
            </div>
        </div>
    @elseif($type === 'tabs')
        <!-- Tabs skeleton -->
        <div class="border-b border-gray-200 pb-4">
            <div class="flex space-x-8">
                @for($i = 0; $i < 4; $i++)
                <div class="h-6 bg-gray-200 rounded w-24"></div>
                @endfor
            </div>
        </div>
        <div class="pt-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @for($i = 0; $i < 6; $i++)
                <div class="space-y-2">
                    <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-10 bg-gray-200 rounded"></div>
                </div>
                @endfor
            </div>
        </div>
    @elseif($type === 'table')
        <!-- Table skeleton -->
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <div class="h-6 bg-gray-200 rounded w-32"></div>
                <div class="h-8 bg-gray-200 rounded w-24"></div>
            </div>
            <div class="bg-white rounded-lg border">
                <div class="p-6 border-b">
                    <div class="flex space-x-4">
                        @for($i = 0; $i < 4; $i++)
                        <div class="h-4 bg-gray-200 rounded flex-1"></div>
                        @endfor
                    </div>
                </div>
                @for($i = 0; $i < 5; $i++)
                <div class="p-6 border-b last:border-b-0">
                    <div class="flex space-x-4">
                        @for($j = 0; $j < 4; $j++)
                        <div class="h-4 bg-gray-200 rounded flex-1"></div>
                        @endfor
                    </div>
                </div>
                @endfor
            </div>
        </div>
    @elseif($type === 'cards')
        <!-- Cards skeleton -->
        @for($i = 0; $i < 6; $i++)
        <div class="bg-white rounded-lg border p-6 space-y-4">
            <div class="h-5 bg-gray-200 rounded w-2/3"></div>
            <div class="space-y-2">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            </div>
            <div class="flex justify-between items-center">
                <div class="h-8 bg-gray-200 rounded w-20"></div>
                <div class="h-6 bg-gray-200 rounded w-16"></div>
            </div>
        </div>
        @endfor
    @elseif($type === 'tab-form')
        <!-- Tab Form skeleton -->
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @for($i = 0; $i < 3; $i++)
                <div class="space-y-2">
                    <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-9 bg-gray-200 rounded"></div>
                </div>
                @endfor
            </div>
            <div class="space-y-2">
                <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                <div class="h-16 bg-gray-200 rounded"></div>
            </div>
            <div class="space-y-3">
                @for($i = 0; $i < 2; $i++)
                <div class="flex items-center space-x-3">
                    <div class="h-4 w-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                </div>
                @endfor
            </div>
        </div>
    @elseif($type === 'tab-table')
        <!-- Tab Table skeleton -->
        <div class="p-6 space-y-4">
            <div class="flex justify-between items-center">
                <div class="h-5 bg-gray-200 rounded w-32"></div>
                <div class="h-8 bg-gray-200 rounded w-20"></div>
            </div>
            <div class="bg-white rounded border">
                <div class="p-6 border-b">
                    <div class="flex space-x-4">
                        @for($i = 0; $i < 3; $i++)
                        <div class="h-4 bg-gray-200 rounded flex-1"></div>
                        @endfor
                    </div>
                </div>
                @for($i = 0; $i < 4; $i++)
                <div class="p-6 border-b last:border-b-0">
                    <div class="flex space-x-4">
                        @for($j = 0; $j < 3; $j++)
                        <div class="h-4 bg-gray-200 rounded flex-1"></div>
                        @endfor
                    </div>
                </div>
                @endfor
            </div>
        </div>
    @elseif($type === 'tab-cards')
        <!-- Tab Cards skeleton -->
        <div class="p-6">
            @for($i = 0; $i < 4; $i++)
            <div class="bg-white rounded border p-6 space-y-3">
                <div class="h-5 bg-gray-200 rounded w-2/3"></div>
                <div class="space-y-2">
                    <div class="h-3 bg-gray-200 rounded"></div>
                    <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                </div>
                <div class="flex justify-between items-center">
                    <div class="h-7 bg-gray-200 rounded w-16"></div>
                    <div class="h-5 bg-gray-200 rounded w-12"></div>
                </div>
            </div>
            @endfor
        </div>
    @else
        <!-- Default skeleton -->
        <div class="space-y-4">
            <div class="h-6 bg-gray-200 rounded w-1/3"></div>
            <div class="space-y-2">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                <div class="h-4 bg-gray-200 rounded w-4/6"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @for($i = 0; $i < 4; $i++)
                <div class="h-24 bg-gray-200 rounded"></div>
                @endfor
            </div>
        </div>
    @endif
</div>
