@if($item->is_active)
    <flux:badge color="green" size="sm" inset="top bottom">Active</flux:badge>
@else
    <flux:badge color="zinc" size="sm" inset="top bottom">Inactive</flux:badge>
@endif
