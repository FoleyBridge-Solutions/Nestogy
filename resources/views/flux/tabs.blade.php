@aware([ 'variant' ])

@props([
    'size' => null,
    'variant' => null,
    'scrollable' => false,
])

@php
if ($variant === 'pills') {
    $classes = Flux::classes()
        ->add('flex gap-4 h-8')
        ;
} elseif ($variant === 'segmented') {
    $classes = Flux::classes()
        ->add('inline-flex p-1')
        ->add($scrollable ? '' : 'rounded-lg bg-zinc-800/5 dark:bg-white/10')
        ->add($size === 'sm' ? 'h-8 py-[3px] px-[3px]' : 'h-10 p-1')
        ->add($size === 'sm' ? match ($variant) {
            'segmented' => '-my-px h-[calc(2rem+2px)]',
            default => '',
        } : '')
        ;
} else {
    $classes = Flux::classes()
        ->add('flex gap-4 h-10 border-b')
        ->add($scrollable ? 'border-transparent' : 'border-zinc-800/10 dark:border-white/20')
        ;
}

$scrollableFade = $attributes->pluck('scrollable:fade', false);
$scrollableScrollbar = $attributes->pluck('scrollable:scrollbar', null);

$wrapperClasses = Flux::classes()
    ->add('relative')
    ->add($variant === 'segmented' ? 'rounded-lg bg-zinc-800/5 dark:bg-white/10' : '');

$borderClasses = Flux::classes()
    ->add('absolute inset-x-0 bottom-0 h-px')
    ->add($variant === null ? 'bg-zinc-800/10 dark:bg-white/20' : '')
    ;

$scrollAreaClasses = Flux::classes()
    ->add('relative flex overflow-auto')
    ->add($scrollableFade ? [
        '[--flux-scroll-percentage:0%]', // This is controlled by JavaScript...
        'mask-r-from-[max(calc(100%-6rem),var(--flux-scroll-percentage))]',
        'rtl:mask-r-from-100% rtl:mask-l-from-[max(calc(100%-6rem),var(--flux-scroll-percentage))]',
    ] : '')
    ->add($scrollableScrollbar === 'hide' ? 'flux-no-scrollbar' : '')
    ;
@endphp

<?php if ($scrollable): ?>
    <div class="{{ $wrapperClasses }}">
        <div class="{{ $borderClasses }}"></div>

        <ui-tabs-scroll-area class="{{ $scrollAreaClasses }}">
            <div class="min-w-full flex-none">
                <ui-tabs {{ $attributes->class($classes) }} data-flux-tabs>
                    {{ $slot }}
                </ui-tabs>
            </div>
        </ui-tabs-scroll-area>
    </div>
<?php else: ?>
    <ui-tabs {{ $attributes->class($classes) }} data-flux-tabs>
        {{ $slot }}
    </ui-tabs>
<?php endif; ?>
