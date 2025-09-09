@props([
    'rings' => 3,
    'centerContent' => null,
    'items' => [],
])

<div 
    x-data="orbitalLayout"
    class="orbital-container relative w-full min-h-[600px] lg:min-h-[700px] flex items-center justify-center overflow-hidden"
>
    <!-- Orbital Rings (visual guides) -->
    @for ($i = 1; $i <= $rings; $i++)
        <div 
            class="orbital-ring orbital-ring-{{ $i }} absolute border border-dashed border-gray-300 dark:border-gray-700 rounded-full pointer-events-none"
            style="
                --ring-size: {{ 180 + ($i - 1) * 150 }}px;
                width: var(--ring-size);
                height: var(--ring-size);
                animation: rotate-slow {{ 60 + $i * 20 }}s linear infinite;
            "
        ></div>
    @endfor

    <!-- Center Hub -->
    <div class="orbital-center absolute z-20">
        {{ $centerContent }}
    </div>

    <!-- Orbital Items -->
    @foreach($items as $item)
        <div 
            class="orbital-item absolute transition-all duration-300 hover:scale-105 hover:z-30"
            data-ring="{{ $item['ring'] }}"
            data-position="{{ $item['position'] }}"
            data-item-id="{{ $item['id'] ?? '' }}"
            x-on:click="selectItem('{{ $item['id'] ?? '' }}')"
            style="{{ $item['style'] ?? '' }}"
        >
            {{ $item['content'] }}
        </div>
    @endforeach

    <!-- Detail Panel Overlay -->
    <div 
        x-show="selectedItem"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-x-full"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-full"
        class="fixed right-0 top-0 h-full w-96 bg-white dark:bg-gray-900 shadow-2xl z-50 overflow-y-auto"
        @click.away="selectedItem = null"
    >
        <div class="p-6">
            <flux:button 
                variant="ghost" 
                size="sm" 
                icon="x-mark" 
                x-on:click="selectedItem = null"
                class="absolute top-4 right-4"
            />
            <div x-html="selectedItemContent"></div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @keyframes rotate-slow {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @keyframes pulse-glow {
        0%, 100% { 
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.5);
            transform: scale(1);
        }
        50% { 
            box-shadow: 0 0 20px 5px rgba(59, 130, 246, 0.3);
            transform: scale(1.02);
        }
    }

    .orbital-item[data-has-alert="true"] {
        animation: pulse-glow 2s ease-in-out infinite;
    }

    /* Ring 1 Positions (4 items) */
    .orbital-item[data-ring="1"][data-position="top"] {
        top: 50%;
        left: 50%;
        transform: translate(-50%, calc(-50% - 90px));
    }
    .orbital-item[data-ring="1"][data-position="right"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% + 90px), -50%);
    }
    .orbital-item[data-ring="1"][data-position="bottom"] {
        top: 50%;
        left: 50%;
        transform: translate(-50%, calc(-50% + 90px));
    }
    .orbital-item[data-ring="1"][data-position="left"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% - 90px), -50%);
    }

    /* Ring 2 Positions (4 items, diagonal) */
    .orbital-item[data-ring="2"][data-position="top-right"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% + 115px), calc(-50% - 115px));
    }
    .orbital-item[data-ring="2"][data-position="bottom-right"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% + 115px), calc(-50% + 115px));
    }
    .orbital-item[data-ring="2"][data-position="bottom-left"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% - 115px), calc(-50% + 115px));
    }
    .orbital-item[data-ring="2"][data-position="top-left"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% - 115px), calc(-50% - 115px));
    }

    /* Ring 3 Positions (8 items) */
    .orbital-item[data-ring="3"][data-position="n"] {
        top: 50%;
        left: 50%;
        transform: translate(-50%, calc(-50% - 225px));
    }
    .orbital-item[data-ring="3"][data-position="ne"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% + 160px), calc(-50% - 160px));
    }
    .orbital-item[data-ring="3"][data-position="e"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% + 225px), -50%);
    }
    .orbital-item[data-ring="3"][data-position="se"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% + 160px), calc(-50% + 160px));
    }
    .orbital-item[data-ring="3"][data-position="s"] {
        top: 50%;
        left: 50%;
        transform: translate(-50%, calc(-50% + 225px));
    }
    .orbital-item[data-ring="3"][data-position="sw"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% - 160px), calc(-50% + 160px));
    }
    .orbital-item[data-ring="3"][data-position="w"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% - 225px), -50%);
    }
    .orbital-item[data-ring="3"][data-position="nw"] {
        top: 50%;
        left: 50%;
        transform: translate(calc(-50% - 160px), calc(-50% - 160px));
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .orbital-container {
            min-height: 500px;
        }
        
        .orbital-item[data-ring="1"][data-position="top"],
        .orbital-item[data-ring="1"][data-position="bottom"] {
            transform: translate(-50%, calc(-50% - 70px));
        }
        .orbital-item[data-ring="1"][data-position="bottom"] {
            transform: translate(-50%, calc(-50% + 70px));
        }
        .orbital-item[data-ring="1"][data-position="left"],
        .orbital-item[data-ring="1"][data-position="right"] {
            transform: translate(calc(-50% - 70px), -50%);
        }
        .orbital-item[data-ring="1"][data-position="right"] {
            transform: translate(calc(-50% + 70px), -50%);
        }
        
        /* Hide Ring 3 on mobile */
        .orbital-item[data-ring="3"] {
            display: none;
        }
    }

    /* Connection lines on hover */
    .orbital-item::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-top: 1px dashed transparent;
        transform-origin: left center;
        transition: all 0.3s ease;
        pointer-events: none;
    }

    .orbital-item:hover::before {
        border-color: rgba(59, 130, 246, 0.3);
        width: var(--line-length, 100px);
        transform: rotate(var(--line-angle, 0deg));
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orbitalLayout', () => ({
        selectedItem: null,
        selectedItemContent: '',
        
        selectItem(itemId) {
            if (this.selectedItem === itemId) {
                this.selectedItem = null;
                return;
            }
            
            this.selectedItem = itemId;
            this.loadItemDetails(itemId);
        },
        
        async loadItemDetails(itemId) {
            // This would normally fetch details via AJAX
            // For now, we'll use placeholder content
            this.selectedItemContent = `<h3 class="text-lg font-semibold mb-4">Loading ${itemId} details...</h3>`;
        }
    }));
});
</script>
@endpush