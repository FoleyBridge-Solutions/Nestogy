@props([
    'title' => '',
    'subtitle' => null,
    'backRoute' => null,
    'backLabel' => 'Back',
    'actions' => null
])

<div class="space-y-6">
    <!-- Page Header -->
    <x-page-header 
        :title="$title"
        :subtitle="$subtitle"
        :back-route="$backRoute"
        :back-label="$backLabel"
    >
        @if($actions)
            <x-slot name="actions">
                {{ $actions }}
            </x-slot>
        @endif
    </x-page-header>

    <!-- Main Content Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column (Main Content - 2/3 width) -->
            <div class="lg:col-span-2 space-y-6">
                {{ $slot }}
            </div>

            <!-- Right Column (Sidebar - 1/3 width) -->
            <div class="lg:col-span-1 space-y-6">
                {{ $sidebar ?? '' }}
            </div>
        </div>
    </div>
</div>