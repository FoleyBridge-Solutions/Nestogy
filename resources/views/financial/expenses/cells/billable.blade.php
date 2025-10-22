@if($item->is_billable)
    <flux:badge color="green" size="sm" inset="top bottom">Yes</flux:badge>
@else
    <flux:badge color="zinc" size="sm" inset="top bottom">No</flux:badge>
@endif
