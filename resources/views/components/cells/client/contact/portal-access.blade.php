@props(['item'])

@if($item->has_portal_access)
    <flux:badge color="blue" size="xs">Active</flux:badge>
@else
    <span class="text-zinc-400 text-sm">-</span>
@endif
