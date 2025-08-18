@props([
    'id' => 'modal-' . uniqid(),
    'title' => '',
    'size' => 'md', // sm, md, lg, xl, full
    'closable' => true,
    'backdrop' => true,
    'show' => false
])

@php
$sizeClasses = [
    'sm' => 'sm:max-w-md',
    'md' => 'sm:max-w-lg',
    'lg' => 'sm:max-w-2xl',
    'xl' => 'sm:max-w-4xl',
    'full' => 'sm:max-w-full sm:mx-4'
];
$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div 
    id="{{ $id }}" 
    class="fixed inset-0 z-50 overflow-y-auto {{ $show ? '' : 'hidden' }}"
    x-data="{ 
        open: {{ $show ? 'true' : 'false' }},
        close() { 
            this.open = false; 
            setTimeout(() => this.$el.style.display = 'none', 150);
        },
        show() {
            this.$el.style.display = 'block';
            setTimeout(() => this.open = true, 10);
        }
    }"
    x-show="open"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="{{ $closable ? 'close()' : '' }}"
    style="{{ $show ? '' : 'display: none;' }}"
>
    <!-- Backdrop -->
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        @if($backdrop)
            <div 
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="{{ $closable ? 'close()' : '' }}"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>
        @endif

        <!-- Modal Container -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal Content -->
        <div 
            class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle {{ $sizeClass }} sm:w-full"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <!-- Header -->
            @if($title || $closable)
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        @if($title)
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                {{ $title }}
                            </h3>
                        @endif
                        
                        @if($closable)
                            <button 
                                @click="close()"
                                class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Body -->
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                {{ $slot }}
            </div>

            <!-- Footer -->
            @isset($footer)
                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>