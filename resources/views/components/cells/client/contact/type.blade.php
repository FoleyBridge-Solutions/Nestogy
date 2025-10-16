@props(['item'])

<div class="flex flex-wrap gap-1">
    @if($item->primary)
        <flux:tooltip content="Primary Contact">
            <flux:badge color="blue" size="xs">P</flux:badge>
        </flux:tooltip>
    @endif
    @if($item->billing)
        <flux:tooltip content="Billing Contact">
            <flux:badge color="emerald" size="xs">B</flux:badge>
        </flux:tooltip>
    @endif
    @if($item->technical)
        <flux:tooltip content="Technical Contact">
            <flux:badge color="purple" size="xs">T</flux:badge>
        </flux:tooltip>
    @endif
    @if($item->important)
        <flux:tooltip content="Important Contact">
            <flux:badge color="amber" size="xs">!</flux:badge>
        </flux:tooltip>
    @endif
    @if(!$item->primary && !$item->billing && !$item->technical && !$item->important)
        <span class="text-zinc-400 text-sm">-</span>
    @endif
</div>
