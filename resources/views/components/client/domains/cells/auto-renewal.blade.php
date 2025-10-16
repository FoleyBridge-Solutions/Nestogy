<div class="flex items-center gap-2">
    @if($item->auto_renewal)
        <flux:badge color="green" label="Enabled" />
    @else
        <flux:badge color="gray" label="Disabled" />
    @endif
</div>
