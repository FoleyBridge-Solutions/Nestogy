<div class="flex items-center gap-2">
    @if($item->is_active)
        <flux:badge color="green" label="Active" />
    @else
        <flux:badge color="gray" label="Inactive" />
    @endif
</div>
