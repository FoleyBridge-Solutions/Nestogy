@props(['item'])

@php
    $sent = $item->emails_sent ?? 0;
    $opened = $item->emails_opened ?? 0;
    $clicked = $item->emails_clicked ?? 0;
    $openRate = $sent > 0 ? round(($opened / $sent) * 100) : 0;
    $clickRate = $sent > 0 ? round(($clicked / $sent) * 100) : 0;
@endphp

<div class="text-sm">
    <div class="flex items-center gap-4 mb-1">
        <div class="flex items-center gap-1">
            <flux:icon.paper-airplane class="size-4 text-gray-400" />
            <span class="text-gray-700 dark:text-gray-300">{{ $sent }}</span>
        </div>
        <div class="flex items-center gap-1">
            <flux:icon.envelope-open class="size-4 text-green-500" />
            <span class="text-gray-700 dark:text-gray-300">{{ $opened }}</span>
        </div>
        <div class="flex items-center gap-1">
            <flux:icon.cursor-arrow-rays class="size-4 text-blue-500" />
            <span class="text-gray-700 dark:text-gray-300">{{ $clicked }}</span>
        </div>
    </div>
    <div class="text-xs text-gray-500">
        {{ $openRate }}% open • {{ $clickRate }}% click
    </div>
</div>
