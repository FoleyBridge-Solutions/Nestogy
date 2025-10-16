<div>
    <flux:text variant="strong">{{ $item->prefix }}{{ $item->number }}</flux:text>
    @if($item->scope)
        <flux:text size="sm" variant="muted" class="block">{{ Str::limit($item->scope, 30) }}</flux:text>
    @endif
</div>
