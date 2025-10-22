@props(['item'])

@php
    $recipient = $item->lead ?? $item->contact;
    $name = $recipient ? ($recipient->full_name ?? $recipient->name ?? 'Unknown') : 'Unknown';
    $email = $recipient->email ?? '';
    $type = $item->lead_id ? 'Lead' : 'Contact';
@endphp

<div class="flex items-center">
    <div class="size-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mr-3">
        <span class="text-blue-600 dark:text-blue-300 font-semibold text-sm">
            {{ strtoupper(substr($name, 0, 1)) }}
        </span>
    </div>
    <div>
        <div class="font-medium text-gray-900 dark:text-white">
            {{ $name }}
        </div>
        <div class="text-sm text-gray-500">
            {{ $email }}
        </div>
        <div class="text-xs text-gray-400">
            {{ $type }}
        </div>
    </div>
</div>
